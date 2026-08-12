<?php

namespace App\Services\IntakeConversion;

use App\Models\CommunicationLog;
use App\Models\Household;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\Participant;
use App\Models\Property;
use App\Models\RequirementDefinition;
use App\Models\User;
use App\Services\DefaultMilestoneSeeder;
use App\Services\IntakeRequirementInstantiator;
use App\Support\AddressNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared convert-intake-to-project flow, extracted verbatim from
 * CommunicationsLogManagement so the main app and the housing portal
 * (homes.caclv.org) run the identical conversion: guards → participant/
 * property resolution → Phase-1 requirements gate (with audited override) →
 * HousingJob creation → convert stamp + carry-forward + default milestones.
 *
 * Known business refusals throw ConversionBlocked (with the exact user-facing
 * message); unexpected failures roll back and rethrow for the caller to
 * surface. Fund codes/counties intentionally carry over in FULL from the
 * log's anticipated set (parity decision — no single-fund picker).
 */
class ConvertIntakeToProject
{
    /**
     * Phase 1 (intake) requirements gate for a given log. Returns
     *   ['met','total','unmet','hasRequirements','ready']
     * counting non-orphaned IntakeRequirements whose RequirementDefinition
     * category falls in PHASE_1_CATEGORIES (eligibility + compliance).
     */
    public function gate(CommunicationLog $log): array
    {
        $reqs = $log->intakeRequirements()
            ->where('is_orphaned', false)
            ->with('definition')
            ->get()
            ->filter(fn ($r) => in_array(
                $r->definition?->category ?? RequirementDefinition::CATEGORY_ELIGIBILITY,
                RequirementDefinition::PHASE_1_CATEGORIES,
                true
            ));

        $total = $reqs->count();
        $met = $reqs->filter(fn ($r) => $r->isMet())->count();

        // A project is born from the county → fund codes → requirements
        // chain, so there must be something to track: at least one
        // non-orphaned requirement (county-derived OR ad-hoc, any phase).
        $hasRequirements = $log->intakeRequirements()
            ->where('is_orphaned', false)
            ->exists();

        return [
            'total' => $total,
            'met' => $met,
            'unmet' => $total - $met,
            'hasRequirements' => $hasRequirements,
            'ready' => $hasRequirements && ($total === 0 || $met === $total),
        ];
    }

    /**
     * Duplicate scan run before conversion: possible existing Participants
     * (name / phone / email) and Properties (AddressNormalizer fingerprint)
     * matching the log's contact details. Both front-ends MUST surface these
     * before creating new records — duplicate participants are the known
     * production data problem.
     *
     * @return array{participants: array, properties: array}
     */
    public function findDuplicates(CommunicationLog $log): array
    {
        $participantMatches = [];
        $propertyMatches = [];

        // Participant duplicates — only when no participant is linked yet.
        if (! $log->participant_id && $log->contact_first_name && $log->contact_last_name) {
            $matches = Participant::with('household')
                ->where(function ($query) use ($log) {
                    // Exact name match (case-insensitive)
                    $query->whereRaw('LOWER(first_name) = ?', [strtolower($log->contact_first_name)])
                        ->whereRaw('LOWER(last_name) = ?', [strtolower($log->contact_last_name)]);
                })
                ->orWhere(function ($query) use ($log) {
                    // Partial match - same last name and similar first name
                    $query->whereRaw('LOWER(last_name) = ?', [strtolower($log->contact_last_name)])
                        ->where(function ($q) use ($log) {
                            $q->whereRaw('LOWER(first_name) LIKE ?', [strtolower($log->contact_first_name).'%'])
                                ->orWhereRaw('? LIKE CONCAT(LOWER(first_name), "%")', [strtolower($log->contact_first_name)]);
                        });
                })
                ->limit(10)
                ->get();

            if ($log->contact_phone || $log->contact_email) {
                $contactMatches = Participant::with('household')
                    ->where(function ($query) use ($log) {
                        if ($log->contact_phone) {
                            $query->orWhere('telephone', 'LIKE', '%'.preg_replace('/[^0-9]/', '', $log->contact_phone).'%');
                        }
                        if ($log->contact_email) {
                            $query->orWhereRaw('LOWER(email) = ?', [strtolower($log->contact_email)]);
                        }
                    })
                    ->limit(5)
                    ->get();

                $matches = $matches->merge($contactMatches)->unique('id');
            }

            $participantMatches = $matches->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->full_name,
                    'phone' => $p->telephone,
                    'email' => $p->email,
                    'household_code' => $p->household?->household_code,
                    'household_id' => $p->household_id,
                ];
            })->values()->toArray();
        }

        // Property duplicates — only when no property is linked but an
        // address is provided. Routed through AddressNormalizer so the
        // conversion flow and the P-01 save-time warning share rules.
        if (! $log->property_id && $log->contact_address && $log->contact_city) {
            $logNorm = AddressNormalizer::normalize($log->contact_address, $log->contact_city);
            $logNumWord = AddressNormalizer::streetNumberAndFirstWord($log->contact_address);

            $matches = Property::query()
                ->whereNotNull('address_line_1')
                ->whereNotNull('city')
                ->limit(50)
                ->get()
                ->filter(function ($property) use ($logNorm, $logNumWord) {
                    $cand = AddressNormalizer::normalize($property->address_line_1, $property->city);
                    $candNumWord = AddressNormalizer::streetNumberAndFirstWord($property->address_line_1);

                    return $cand['fingerprint'] === $logNorm['fingerprint']
                        || ($logNumWord !== null
                            && $candNumWord !== null
                            && $cand['city'] === $logNorm['city']
                            && $candNumWord === $logNumWord);
                })
                ->take(10);

            $propertyMatches = $matches->map(function ($p) {
                return [
                    'id' => $p->id,
                    'address' => $p->address_line_1,
                    'city' => $p->city,
                    'state' => $p->state,
                    'zip' => $p->zip_code,
                    'full_address' => $p->address_line_1.', '.$p->city.', '.$p->state.' '.$p->zip_code,
                ];
            })->values()->toArray();
        }

        return [
            'participants' => $participantMatches,
            'properties' => $propertyMatches,
        ];
    }

    /**
     * Run the full conversion. Throws ConversionBlocked for known refusals
     * (everything rolled back); rethrows unexpected exceptions after rollback.
     */
    public function convert(CommunicationLog $log, ConversionInput $input, User $actor): ConversionResult
    {
        Log::info('convertToProject started', ['log_id' => $log->id]);

        // Check if already converted and block (user must use resetConversion first)
        if ($log->converted_to_job_id) {
            $existingJob = HousingJob::find($log->converted_to_job_id);
            $jobNumber = $existingJob?->job_number ?? "ID: {$log->converted_to_job_id}";

            throw new ConversionBlocked(
                ConversionBlocked::ALREADY_CONVERTED,
                "This communication log has already been converted to project {$jobNumber}. Use 'Reset Conversion' option first if you need to re-convert."
            );
        }

        // Server-side guard for the same condition the disabled button
        // enforces: a project needs at least one non-orphaned requirement
        // to track (county-derived or ad-hoc).
        if (! $this->gate($log)['hasRequirements']) {
            throw new ConversionBlocked(
                ConversionBlocked::NOTHING_TO_TRACK,
                'Cannot convert: this log has no requirements yet. Select a county or add an ad-hoc requirement first.'
            );
        }

        DB::beginTransaction();
        try {
            $participant = null;
            $household = null;
            $property = null;
            $createdResources = [];

            // ── Participant resolution ────────────────────────────────
            if ($log->participant_id) {
                // Already has a participant linked
                $participant = Participant::find($log->participant_id);
                $household = $participant?->household;
            } elseif (is_numeric($input->participantAction)) {
                // Caller selected an existing participant from duplicates
                $participant = Participant::find($input->participantAction);
                $household = $participant?->household;
                $createdResources[] = "linked to existing participant: {$participant->full_name}";

                $log->update([
                    'participant_id' => $participant->id,
                    'household_id' => $household?->id,
                ]);
            } elseif ($input->participantAction === 'create_new') {
                if ($log->contact_first_name && $log->contact_last_name) {
                    $household = Household::create([
                        'household_type' => 'Single Person',
                        'household_size' => 1,
                        'housing_status' => 'Unknown/not reported',
                    ]);

                    if ($log->contact_address && $log->contact_city) {
                        $household->addresses()->create([
                            'address' => $log->contact_address,
                            'city' => $log->contact_city,
                            'state' => strtoupper($log->contact_state ?? 'PA'),
                            'zip_code' => $log->contact_zip ?? '',
                            'is_current' => true,
                        ]);
                    }

                    $participant = Participant::create([
                        'household_id' => $household->id,
                        'first_name' => $log->contact_first_name,
                        'last_name' => $log->contact_last_name,
                        'telephone' => $log->contact_phone ?: null,
                        'email' => $log->contact_email ?: null,
                        'relationship_to_head' => 'self',
                        'is_head_of_household' => true,
                    ]);

                    $createdResources[] = "created participant: {$participant->full_name}";

                    $log->update([
                        'participant_id' => $participant->id,
                        'household_id' => $household->id,
                    ]);
                } else {
                    DB::rollBack();

                    throw new ConversionBlocked(
                        ConversionBlocked::MISSING_CONTACT_NAME,
                        'Cannot convert to project: No participant is linked and contact name is incomplete. Please edit the communication log and add contact first/last name or select a participant.'
                    );
                }
            }

            // ── Property resolution ───────────────────────────────────
            if ($log->property_id) {
                $property = Property::find($log->property_id);
            } elseif (is_numeric($input->propertyAction)) {
                $property = Property::find($input->propertyAction);
                $createdResources[] = "linked to existing property: {$property->address_line_1}";

                $log->update(['property_id' => $property->id]);
            } elseif ($input->propertyAction === 'create_new' && $log->contact_address && $log->contact_city) {
                $property = Property::create([
                    'address_line_1' => $log->contact_address,
                    'city' => $log->contact_city,
                    'state' => strtoupper($log->contact_state ?? 'PA'),
                    'zip_code' => $log->contact_zip ?? '',
                    'participant_id' => $participant?->id,
                    'household_id' => $household?->id,
                    'status' => 'active',
                    'created_by' => $actor->id,
                ]);

                $createdResources[] = "created property: {$property->address_line_1}";

                $log->update(['property_id' => $property->id]);
            } elseif ($input->propertyAction === 'skip') {
                $property = null;
            } else {
                // No property linked and no address to create from
                $property = null;
            }

            // Advisory note when the property already has an active project.
            if ($property) {
                $existingJob = HousingJob::where('property_id', $property->id)
                    ->whereIn('status', ['pending', 'approved', 'in_progress', 'on_hold'])
                    ->first();

                if ($existingJob) {
                    $createdResources[] = "Note: Active project already exists for this property (Job #{$existingJob->job_number})";
                }
            }

            // ── Requirements gate — block conversion if any Phase-1
            // non-orphaned intake requirement is unmet AND the caller did
            // not tick the audit-logged override. ─────────────────────
            $unmetCount = $log->intakeRequirements()
                ->where('is_orphaned', false)
                ->whereNotIn('status', IntakeRequirement::MET_STATUSES)
                ->with('definition')
                ->get()
                ->filter(fn ($r) => in_array(
                    $r->definition?->category ?? RequirementDefinition::CATEGORY_ELIGIBILITY,
                    RequirementDefinition::PHASE_1_CATEGORIES,
                    true
                ))
                ->count();

            if ($unmetCount > 0 && ! $input->requirementOverride) {
                DB::rollBack();

                throw new ConversionBlocked(
                    ConversionBlocked::UNMET_REQUIREMENTS,
                    "{$unmetCount} required application requirement(s) are unmet. Tick the override box and provide a reason to proceed."
                );
            }

            if ($input->requirementOverride && trim((string) $input->overrideReason) === '') {
                DB::rollBack();

                throw new ConversionBlocked(
                    ConversionBlocked::OVERRIDE_REASON_REQUIRED,
                    'A reason is required when overriding unmet requirements.'
                );
            }

            $job = HousingJob::create([
                'job_title' => $input->jobTitle,
                'job_description' => $input->jobDescription,
                // program_id is auto-derived silently (first housing-enabled
                // program) purely to keep project access control working —
                // it is no longer surfaced or chosen in the convert UI. The
                // legacy Service tie-in is dropped: the project's scope now
                // comes from the carried counties + fund codes + Phase 2
                // requirements (see IntakeRequirementInstantiator::
                // carryForwardToProject).
                'program_id' => $input->programId ?: null,
                'service_id' => null,
                'participant_id' => $participant?->id,
                'household_id' => $household?->id,
                'property_id' => $property?->id,
                'contractor_id' => $log->contractor_id,
                'status' => 'pending',
                'priority' => $log->priority === 'urgent' ? 'emergency' : ($log->priority === 'high' ? 'high' : 'medium'),
                'request_date' => now(),
                'created_by' => $actor->id,
                'project_manager_id' => $actor->id,
            ]);

            $log->convertToProject($job, $actor->id);

            // Record the override audit + carry counties/fund codes forward
            // and instantiate Phase-2 project requirements.
            app(IntakeRequirementInstantiator::class)->carryForwardToProject(
                $log,
                $job,
                $actor,
                $input->requirementOverride ? $input->overrideReason : null
            );

            // Materialize the curated Phase-2 universal milestones on the
            // brand-new project (same transaction — a failure rolls the
            // milestones back with the project).
            app(DefaultMilestoneSeeder::class)->seed($job, $actor);

            DB::commit();

            Log::info('convertToProject committed', ['job_id' => $job->id, 'job_number' => $job->job_number]);

            return new ConversionResult(
                job: $job,
                createdResources: $createdResources,
                propertyAssigned: $property !== null,
            );
        } catch (ConversionBlocked $e) {
            // Already rolled back at the throw site.
            Log::info('convertToProject blocked', ['log_id' => $log->id, 'reason' => $e->reasonCode]);

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Convert to project failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            throw $e;
        }
    }
}
