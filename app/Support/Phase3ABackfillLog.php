<?php

namespace App\Support;

/**
 * Helper used by the four Phase 3A backfill migrations to persist a
 * machine-readable record of which rows each migration touched, so
 * `migrate:rollback` can reverse the data changes by ID.
 *
 * Storage path: database/seeders/phase_3a_backfill_ids.json. The file is
 * committed to git after the production migration run; for local/dev
 * runs the file gets regenerated. We deliberately skip file I/O during
 * automated test runs (APP_ENV=testing) so RefreshDatabase doesn't dirty
 * a tracked artifact between test passes.
 */
class Phase3ABackfillLog
{
    public string $relativePath = 'database/seeders/phase_3a_backfill_ids.json';

    public function read(): array
    {
        $abs = base_path($this->relativePath);

        if (! file_exists($abs)) {
            return [
                'version' => 'phase-3a',
                'generated_at' => null,
                'sections' => [],
            ];
        }

        $decoded = json_decode((string) file_get_contents($abs), true);

        return is_array($decoded)
            ? $decoded
            : ['version' => 'phase-3a', 'generated_at' => null, 'sections' => []];
    }

    public function write(string $section, array $payload): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $data = $this->read();
        $data['version'] = 'phase-3a';
        $data['generated_at'] = $data['generated_at'] ?? now()->toIso8601String();
        $data['sections'][$section] = array_merge($payload, ['written_at' => now()->toIso8601String()]);

        $abs = base_path($this->relativePath);
        $dir = dirname($abs);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $abs,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    public function section(string $section): ?array
    {
        return $this->read()['sections'][$section] ?? null;
    }
}
