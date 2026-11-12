<?php

namespace App\Services;

use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Narrowing a project's funding down to the code it is actually paid from.
 *
 * A project inherits its fund codes wholesale: the intake's county decides
 * which codes are in play, every anticipated code carries over at
 * conversion, and the project ends up listing all of them. That is right at
 * conversion time — nobody knows yet which one will pay — and wrong by the
 * time the award lands, when the project is restricted to one code of that
 * county's set and the others are noise on the budget picker.
 *
 * This service is the one place that changes a live project's funding, from
 * either front door. It is deliberately NARROWING-shaped: the pool a project
 * may draw on stays the county's (plus whatever the intake anticipated), so
 * this never widens a project past what its county allows.
 *
 * Two things hang off a project's fund codes, and both are handled here:
 *
 *  * **Budget lines** carry `fund_code_id`. A code a budget line spends from
 *    cannot be dropped — the existing P-2.5 invariant, enforced by
 *    HousingJob::detachFundCode() and pre-checked here so the UI can say
 *    which categories are in the way before she commits.
 *  * **Requirements** instantiated from the code (`source_type='fund_code'`).
 *    Ones she has never touched came from a premise that turned out wrong
 *    and are offered for removal. Ones carrying ANY progress — done, marked
 *    not applicable, or holding a file — are never removed by this path.
 *    They stay on the project exactly as they are, and adding the fund code
 *    back puts them back in view untouched.
 */
class ProjectFundingService
{
    /**
     * The fund codes this project may draw on: its counties' codes, plus
     * whatever the originating intake anticipated.
     *
     * The second half matters for projects converted before counties
     * carried fund codes — without it, narrowing one of those would be a
     * one-way door with nothing to add back.
     *
     * @return Collection<int, FundCode>
     */
    public function availableFor(HousingJob $job): Collection
    {
        $countyIds = $job->housingServices()->withTrashed()->pluck('housing_services.id');

        $fromCounties = FundCode::query()
            ->whereHas('housingServices', fn ($q) => $q->whereIn('housing_services.id', $countyIds))
            ->get();

        $fromIntake = FundCode::query()
            ->whereHas('communicationLogs', fn ($q) => $q->where(
                'communication_logs.converted_to_job_id', $job->id
            ))
            ->get();

        return $fromCounties
            ->concat($fromIntake)
            ->concat($job->fundCodes()->get())
            ->unique('id')
            ->sortBy('code')
            ->values();
    }

    /**
     * What narrowing this project to $keepIds would do — computed without
     * changing anything, so the screen can show it before she commits.
     *
     * @param  array<int>  $keepIds  the fund code ids to keep
     * @return array<string,mixed>
     */
    public function preview(HousingJob $job, array $keepIds): array
    {
        $keepIds = array_map('intval', $keepIds);
        $attached = $job->fundCodes()->get();

        $keeping = $attached->whereIn('id', $keepIds)->values();
        $dropping = $attached->whereNotIn('id', $keepIds)->values();

        // Budget lines spending from a code being dropped — a hard stop.
        $blocked = [];
        foreach ($dropping as $code) {
            $categories = $job->budgetCategoriesReferencingFundCode($code->id);
            if (! empty($categories)) {
                $blocked[] = ['code' => $code->code, 'categories' => $categories];
            }
        }

        $requirements = $this->requirementsFrom($job, $dropping->pluck('id')->all());
        [$untouched, $withProgress] = $requirements->partition(
            fn (Requirement $r) => $this->isUntouched($r)
        );

        return [
            'keeping' => $keeping,
            'dropping' => $dropping,
            'blocked' => $blocked,
            'untouched' => $untouched->values(),
            'with_progress' => $withProgress->values(),
        ];
    }

    /**
     * Why this narrowing cannot go through, phrased for the project
     * manager, or null when it can. Mirrors HousingRecordDeleter's
     * blockedReason() — one gate, one message, shown inline.
     */
    public function blockedReason(HousingJob $job, array $keepIds): ?string
    {
        if (empty(array_filter($keepIds))) {
            return 'A project has to keep at least one fund code. Pick the one it is paid from.';
        }

        $preview = $this->preview($job, $keepIds);

        if ($preview['dropping']->isEmpty()) {
            return null;
        }

        if (! empty($preview['blocked'])) {
            $parts = [];
            foreach ($preview['blocked'] as $block) {
                $list = '"'.implode('", "', $block['categories']).'"';
                $parts[] = "{$block['code']} pays for {$list}";
            }

            return 'These budget lines still spend from a fund you are dropping — '
                .implode('; ', $parts)
                .'. Move or delete them first, then narrow the funding.';
        }

        return null;
    }

    /**
     * Narrow the project to $keepIds. Everything happens in one
     * transaction — a project left half-narrowed would show funding it is
     * no longer allowed to spend.
     *
     * @param  array<int>  $keepIds  the fund code ids to keep
     * @param  bool  $removeUntouched  also delete the never-started requirements
     *                                 that came from the dropped codes
     * @return array<string,mixed> summary of what changed
     *
     * @throws RuntimeException when a budget line still spends from a dropped code
     */
    public function restrictTo(HousingJob $job, array $keepIds, ?User $actor = null, bool $removeUntouched = true): array
    {
        $reason = $this->blockedReason($job, $keepIds);

        if ($reason !== null) {
            throw new RuntimeException($reason);
        }

        return DB::transaction(function () use ($job, $keepIds, $removeUntouched) {
            $preview = $this->preview($job, $keepIds);

            $removed = 0;
            if ($removeUntouched) {
                foreach ($preview['untouched'] as $requirement) {
                    // Hard delete — Requirement does not soft-delete. Only
                    // rows with nothing on them ever reach here, and the
                    // activity log keeps the before-state either way.
                    $requirement->delete();
                    $removed++;
                }
            }

            foreach ($preview['dropping'] as $code) {
                // Re-runs the budget guard defensively: a budget line could
                // have appeared between the pre-check and here.
                $job->detachFundCode($code->id);
            }

            return [
                'kept' => $preview['keeping']->pluck('code')->all(),
                'dropped' => $preview['dropping']->pluck('code')->all(),
                'requirements_removed' => $removed,
                'requirements_left_alone' => $preview['with_progress']->count()
                    + ($removeUntouched ? 0 : $preview['untouched']->count()),
            ];
        });
    }

    /**
     * Put a fund code back on the project, with the requirements it brings.
     *
     * Idempotent on both halves: re-attaching is a no-op on the pivot, and
     * RequirementInstantiator::forFundCode skips templates that already
     * have a row — so a requirement that survived an earlier narrowing
     * comes back in view rather than being duplicated.
     *
     * @throws RuntimeException when the code is not one this project's
     *                          county (or its intake) puts in play
     */
    public function add(HousingJob $job, FundCode $code, ?User $actor = null): void
    {
        if (! $this->availableFor($job)->contains('id', $code->id)) {
            throw new RuntimeException(
                "{$code->code} is not one of the funds this project's county covers. "
                .'Add it to the county first, under Settings.'
            );
        }

        DB::transaction(function () use ($job, $code, $actor) {
            $job->fundCodes()->syncWithoutDetaching([$code->id]);

            if ($actor !== null) {
                app(RequirementInstantiator::class)->forFundCode($job, $code, $actor);
            }
        });
    }

    /**
     * Requirements on this project that came from any of $fundCodeIds.
     *
     * @return Collection<int, Requirement>
     */
    protected function requirementsFrom(HousingJob $job, array $fundCodeIds): Collection
    {
        if (empty($fundCodeIds)) {
            return collect();
        }

        return Requirement::query()
            ->where('housing_job_id', $job->id)
            ->where('source_type', Requirement::SOURCE_FUND_CODE)
            ->whereIn('source_id', $fundCodeIds)
            ->with('media')
            ->get();
    }

    /**
     * Nothing has been done to this requirement — no status movement, no
     * file, no note. Only these are ever removed by a narrowing.
     */
    protected function isUntouched(Requirement $requirement): bool
    {
        return $requirement->status === Requirement::STATUS_OUTSTANDING
            && $requirement->getMedia('attachments')->isEmpty()
            && trim((string) $requirement->completion_note) === ''
            && trim((string) $requirement->worker_note) === '';
    }
}
