<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Restores a participant (and all associated records) from a deletion snapshot written
 * by ParticipantDeletionService. Rows are re-inserted with their ORIGINAL ids — which
 * are normally still free, since the delete just released them — so foreign keys line
 * up exactly as before. Rows whose id already exists are skipped (the restore is
 * idempotent and never clobbers live data).
 */
class ParticipantRestoreService
{
    /**
     * @return array<string,int> Count of rows restored per table.
     */
    public function restore(string $snapshotPath, string $disk = 'local'): array
    {
        if (! Storage::disk($disk)->exists($snapshotPath)) {
            throw new \InvalidArgumentException("Snapshot not found: {$snapshotPath}");
        }

        $snapshot = json_decode(Storage::disk($disk)->get($snapshotPath), true);

        if (! is_array($snapshot) || ! isset($snapshot['participant']['id'])) {
            throw new \InvalidArgumentException('File is not a valid participant deletion snapshot.');
        }

        return DB::transaction(function () use ($snapshot) {
            Schema::disableForeignKeyConstraints();

            try {
                $restored = [];

                // Parents first, then children, then grandchildren.
                if (! empty($snapshot['household'])) {
                    $restored['households'] = $this->insertRows('households', [$snapshot['household']]);
                }

                $restored['participants'] = $this->insertRows('participants', [$snapshot['participant']]);
                $restored['program_enrollments'] = $this->insertRows('program_enrollments', $snapshot['program_enrollments'] ?? []);
                $restored['service_enrollments'] = $this->insertRows('service_enrollments', $snapshot['service_enrollments'] ?? []);
                $restored['properties'] = $this->insertRows('properties', $snapshot['properties'] ?? []);
                $restored['housing_jobs'] = $this->insertRows('housing_jobs', $snapshot['housing_jobs'] ?? []);

                foreach (($snapshot['housing_job_children'] ?? []) as $table => $rows) {
                    $restored[$table] = $this->insertRows($table, $rows);
                }

                $restored['communication_logs'] = $this->insertRows('communication_logs', $snapshot['communication_logs'] ?? []);
                $restored['intake_requirements'] = $this->insertRows('intake_requirements', $snapshot['intake_requirements'] ?? []);
                $restored['potential_duplicates'] = $this->insertRows('potential_duplicates', $snapshot['potential_duplicates'] ?? []);
                $restored['tags'] = $this->insertRows('taggables', $snapshot['tags'] ?? []);

                return array_filter($restored, fn ($count) => $count > 0);
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });
    }

    /**
     * Insert snapshot rows, skipping any whose id already exists in the table.
     */
    private function insertRows(string $table, array $rows): int
    {
        if (empty($rows) || ! Schema::hasTable($table)) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $row = (array) $row;
            $id = $row['id'] ?? null;

            if ($id !== null && DB::table($table)->where('id', $id)->exists()) {
                continue;
            }

            DB::table($table)->insert($row);
            $count++;
        }

        return $count;
    }
}
