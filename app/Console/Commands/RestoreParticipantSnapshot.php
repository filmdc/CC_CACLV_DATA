<?php

namespace App\Console\Commands;

use App\Services\ParticipantRestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RestoreParticipantSnapshot extends Command
{
    protected $signature = 'participant:restore
                            {snapshot? : Path (relative to storage/app/private) of the deletion snapshot JSON}
                            {--list : List available deletion snapshots instead of restoring}';

    protected $description = 'Restore a participant and all associated records from a deletion snapshot';

    public function handle(ParticipantRestoreService $service): int
    {
        if ($this->option('list') || ! $this->argument('snapshot')) {
            return $this->listSnapshots();
        }

        $path = $this->argument('snapshot');

        try {
            $this->info("Restoring from: {$path}");
            $restored = $service->restore($path);
        } catch (\Throwable $e) {
            $this->error('Restore failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (empty($restored)) {
            $this->warn('Nothing restored — every record in the snapshot already exists.');

            return self::SUCCESS;
        }

        $this->table(['Table', 'Rows restored'], collect($restored)->map(fn ($c, $t) => [$t, $c])->values()->all());
        $this->info('Restore complete.');

        return self::SUCCESS;
    }

    private function listSnapshots(): int
    {
        $files = Storage::disk('local')->files('deletion-snapshots');

        if (empty($files)) {
            $this->warn('No deletion snapshots found in storage/app/private/deletion-snapshots.');

            return self::SUCCESS;
        }

        $this->table(['Snapshot'], collect($files)->sort()->map(fn ($f) => [$f])->values()->all());
        $this->line('Restore with: php artisan participant:restore "<snapshot path>"');

        return self::SUCCESS;
    }
}
