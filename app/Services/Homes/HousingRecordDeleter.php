<?php

namespace App\Services\Homes;

use App\Models\CommunicationLog;
use App\Models\HousingJob;
use RuntimeException;

/**
 * Recoverable deletes for the two records the housing project manager owns:
 * call/intake logs and projects.
 *
 * Nothing is destroyed. Both models soft-delete, so a delete removes the
 * record from every normal query and from her portal, while the row (and
 * everything hanging off it — timeline, requirements, milestones, budget,
 * expenses, documents) stays intact and restorable by an admin from
 * /admin/recycle-bin.
 *
 * The one ordering rule lives here: a call log that was promoted into a live
 * project cannot be deleted while that project exists. Deleting the log first
 * would leave a project with no visible origin, so the project goes first.
 * Portal screens and the recycle bin both go through this service, so the
 * rule is enforced in one place.
 */
class HousingRecordDeleter
{
    /**
     * Why this call log cannot be deleted yet, or null when it can.
     * Phrased for the project manager, not for a developer.
     */
    public function callBlockedReason(CommunicationLog $log): ?string
    {
        $job = $this->liveJobFor($log);

        if ($job === null) {
            return null;
        }

        return "This call was converted into project {$job->job_number}. "
            .'Delete that project first, then this call can be deleted.';
    }

    public function callCanBeDeleted(CommunicationLog $log): bool
    {
        return $this->callBlockedReason($log) === null;
    }

    /**
     * Soft-delete a call log. Throws when the ordering rule blocks it —
     * callers should check callBlockedReason() first and show the message.
     */
    public function deleteCall(CommunicationLog $log): void
    {
        $reason = $this->callBlockedReason($log);

        if ($reason !== null) {
            throw new RuntimeException($reason);
        }

        $log->delete();
    }

    /**
     * Soft-delete a project. Milestones, budget lines, expenses and documents
     * are reachable only through the job, so hiding the job hides the whole
     * project — and a restore brings all of it back untouched.
     */
    public function deleteProject(HousingJob $job): void
    {
        $job->delete();
    }

    public function restoreCall(CommunicationLog $log): void
    {
        $log->restore();
    }

    public function restoreProject(HousingJob $job): void
    {
        $job->restore();
    }

    /**
     * A note for the recycle bin: what restoring this call log will and will
     * not bring back. Null when there is nothing to warn about.
     */
    public function restoreCallNote(CommunicationLog $log): ?string
    {
        // A record that was merged away is not an ordinary delete — its
        // contents live on another log now, and restoring it brings back only
        // the shell they were moved off of.
        if ($log->merged_into_id !== null) {
            return "Merged into COMM-{$log->merged_into_id}"
                .($log->merged_at ? ' on '.$log->merged_at->isoFormat('MMM D, YYYY') : '')
                .'. Its timeline, documents and requirements are on that record now — '
                .'restoring this one brings back the empty entry, not its contents.';
        }

        $jobId = $log->converted_to_job_id ?? $log->housing_job_id;

        if ($jobId === null) {
            return null;
        }

        $job = HousingJob::withTrashed()->find($jobId);

        if ($job === null || ! $job->trashed()) {
            return null;
        }

        return "Its project {$job->job_number} is also deleted — restore that too if she needs it back.";
    }

    /** The non-deleted project this log was promoted into, if any. */
    protected function liveJobFor(CommunicationLog $log): ?HousingJob
    {
        $jobId = $log->converted_to_job_id ?? $log->housing_job_id;

        if ($jobId === null) {
            return null;
        }

        return HousingJob::find($jobId);
    }
}
