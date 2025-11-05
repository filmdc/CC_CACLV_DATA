<?php

namespace App\Services;

use App\Models\HousingJob;
use App\Models\PendingApproval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MilestoneApprovalService
{
    /**
     * Check if a housing job can be completed based on approval status
     */
    public function canCompleteJob(HousingJob $job): bool
    {
        $progress = $this->getApprovalProgress($job);

        return $progress['documents_approved'] === $progress['documents_total']
            && $progress['tasks_approved'] === $progress['tasks_total']
            && $progress['documents_total'] > 0; // Must have at least some required documents
    }

    /**
     * Get all pending approval items for a job
     */
    public function getPendingApprovalsForJob(HousingJob $job): Collection
    {
        return PendingApproval::forJob($job->id)
            ->pending()
            ->with(['submittedBy', 'approver', 'approvable'])
            ->latest('submitted_at')
            ->get();
    }

    /**
     * Get approval progress statistics for a job
     */
    public function getApprovalProgress(HousingJob $job): array
    {
        // Count property documents requiring approval
        $propertyDocuments = $job->property?->documents()
            ->where('requires_approval', true)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN approval_status = "approved" THEN 1 ELSE 0 END) as approved')
            ->first();

        // Count contractor documents requiring approval
        $contractorDocuments = $job->contractor?->documents()
            ->where('requires_approval', true)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN approval_status = "approved" THEN 1 ELSE 0 END) as approved')
            ->first();

        // Count job document checklist items requiring approval
        $jobDocuments = $job->documentChecklists()
            ->where('requires_approval', true)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN approval_status = "approved" THEN 1 ELSE 0 END) as approved')
            ->first();

        // Count work plan goals requiring approval (if linked to this job via service)
        $workPlanGoals = DB::table('work_plan_goals')
            ->join('work_plans', 'work_plan_goals.work_plan_id', '=', 'work_plans.id')
            ->where('work_plan_goals.service_id', $job->service_id)
            ->where('work_plan_goals.requires_approval', true)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN approval_status = "approved" THEN 1 ELSE 0 END) as approved')
            ->first();

        $documentsTotal = ($propertyDocuments->total ?? 0)
            + ($contractorDocuments->total ?? 0)
            + ($jobDocuments->total ?? 0);

        $documentsApproved = ($propertyDocuments->approved ?? 0)
            + ($contractorDocuments->approved ?? 0)
            + ($jobDocuments->approved ?? 0);

        $tasksTotal = $workPlanGoals->total ?? 0;
        $tasksApproved = $workPlanGoals->approved ?? 0;

        return [
            'documents_total' => $documentsTotal,
            'documents_approved' => $documentsApproved,
            'documents_pending' => $documentsTotal - $documentsApproved,
            'documents_progress_percent' => $documentsTotal > 0
                ? round(($documentsApproved / $documentsTotal) * 100, 1)
                : 0,

            'tasks_total' => $tasksTotal,
            'tasks_approved' => $tasksApproved,
            'tasks_pending' => $tasksTotal - $tasksApproved,
            'tasks_progress_percent' => $tasksTotal > 0
                ? round(($tasksApproved / $tasksTotal) * 100, 1)
                : 0,

            'overall_total' => $documentsTotal + $tasksTotal,
            'overall_approved' => $documentsApproved + $tasksApproved,
            'overall_pending' => ($documentsTotal + $tasksTotal) - ($documentsApproved + $tasksApproved),
            'overall_progress_percent' => ($documentsTotal + $tasksTotal) > 0
                ? round((($documentsApproved + $tasksApproved) / ($documentsTotal + $tasksTotal)) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get detailed breakdown of pending items
     */
    public function getPendingItemsBreakdown(HousingJob $job): array
    {
        $breakdown = [
            'property_documents' => [],
            'contractor_documents' => [],
            'job_documents' => [],
            'work_plan_goals' => [],
        ];

        // Property documents
        if ($job->property) {
            $breakdown['property_documents'] = $job->property->documents()
                ->where('requires_approval', true)
                ->where('approval_status', '!=', 'approved')
                ->get()
                ->map(fn($doc) => [
                    'id' => $doc->id,
                    'type' => 'property_document',
                    'name' => $doc->document_name,
                    'status' => $doc->approval_status,
                    'version' => $doc->document_version ?? 1,
                ])
                ->toArray();
        }

        // Contractor documents
        if ($job->contractor) {
            $breakdown['contractor_documents'] = $job->contractor->documents()
                ->where('requires_approval', true)
                ->where('approval_status', '!=', 'approved')
                ->get()
                ->map(fn($doc) => [
                    'id' => $doc->id,
                    'type' => 'contractor_document',
                    'name' => $doc->document_name,
                    'status' => $doc->approval_status,
                    'version' => $doc->document_version ?? 1,
                ])
                ->toArray();
        }

        // Job documents
        $breakdown['job_documents'] = $job->documentChecklists()
            ->where('requires_approval', true)
            ->where('approval_status', '!=', 'approved')
            ->get()
            ->map(fn($doc) => [
                'id' => $doc->id,
                'type' => 'job_document',
                'name' => $doc->document_name,
                'status' => $doc->approval_status,
                'version' => $doc->document_version ?? 1,
            ])
            ->toArray();

        // Work plan goals
        if ($job->service_id) {
            $breakdown['work_plan_goals'] = DB::table('work_plan_goals')
                ->join('work_plans', 'work_plan_goals.work_plan_id', '=', 'work_plans.id')
                ->where('work_plan_goals.service_id', $job->service_id)
                ->where('work_plan_goals.requires_approval', true)
                ->where('work_plan_goals.approval_status', '!=', 'approved')
                ->select('work_plan_goals.*')
                ->get()
                ->map(fn($goal) => [
                    'id' => $goal->id,
                    'type' => 'work_plan_goal',
                    'name' => $goal->goal_name,
                    'status' => $goal->approval_status,
                ])
                ->toArray();
        }

        return $breakdown;
    }

    /**
     * Get a summary message about what's blocking job completion
     */
    public function getCompletionBlockerMessage(HousingJob $job): ?string
    {
        $progress = $this->getApprovalProgress($job);

        if ($progress['overall_total'] === 0) {
            return 'No approval requirements configured for this job.';
        }

        if ($progress['overall_pending'] === 0) {
            return null; // No blockers
        }

        $messages = [];

        if ($progress['documents_pending'] > 0) {
            $messages[] = "{$progress['documents_pending']} document(s) require approval";
        }

        if ($progress['tasks_pending'] > 0) {
            $messages[] = "{$progress['tasks_pending']} task(s) require approval";
        }

        return 'Cannot complete job - ' . implode(' and ', $messages) . '.';
    }
}
