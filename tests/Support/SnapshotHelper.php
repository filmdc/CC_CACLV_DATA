<?php

namespace Tests\Support;

use PHPUnit\Framework\Assert as PHPUnit;

class SnapshotHelper
{
    /**
     * Assert that the given raw HTML — after normalization — matches the
     * snapshot stored at tests/Support/__snapshots__/{$name}.html.
     *
     * On first run (no snapshot present) the file is written and the assertion
     * passes with a note so the snapshot can be reviewed and committed.
     *
     * Set REGRESSION_UPDATE_SNAPSHOTS=1 in the environment to force-rewrite
     * all snapshots (useful after an intentional UI change).
     *
     * Normalization strip-list (matches REGRESSION_HARNESS.md):
     *   - wire:id / wire:snapshot / wire:effects / wire:initial-data / wire:socket
     *     attribute values (these are regenerated on every render)
     *   - CSRF token fields (<input name="_token">, <meta name="csrf-token">)
     *   - Livewire component checksum markers (data-csrf, x-csrf-token)
     *   - ISO 8601 timestamps down to the second (normalized to a fixed token)
     *   - Database autoincrement IDs in resource URLs (/project/N/, /property/N,
     *     /participant/N, /contract/N, /contractor/N) normalized to __ID__
     *   - <option value="N"> where N is digits-only — typically a FK dropdown
     *     (e.g. program_id filter). Non-numeric option values (enum strings
     *     like "pending") are preserved.
     *   - Trailing whitespace on each line + multiple blank lines
     */
    public static function assertHtmlMatchesSnapshot(string $name, string $rawHtml): void
    {
        $dir = __DIR__.'/__snapshots__';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $snapshotPath = "{$dir}/{$name}.html";
        $normalized = self::normalize($rawHtml);

        if (getenv('REGRESSION_UPDATE_SNAPSHOTS') === '1') {
            file_put_contents($snapshotPath, $normalized);
            PHPUnit::assertTrue(true, "Snapshot '{$name}' force-rewritten per REGRESSION_UPDATE_SNAPSHOTS=1.");

            return;
        }

        if (! file_exists($snapshotPath)) {
            file_put_contents($snapshotPath, $normalized);
            fwrite(STDERR, "\n[SnapshotHelper] New snapshot created: {$snapshotPath} — review and commit.\n");
            PHPUnit::assertTrue(true, "Snapshot '{$name}' created on first run.");

            return;
        }

        $expected = (string) file_get_contents($snapshotPath);

        if ($expected !== $normalized) {
            $actualDumpPath = sys_get_temp_dir()."/snapshot_actual_{$name}.html";
            file_put_contents($actualDumpPath, $normalized);
            fwrite(STDERR, "\n[SnapshotHelper] Actual output written to {$actualDumpPath} — diff against {$snapshotPath}\n");
        }

        PHPUnit::assertSame(
            $expected,
            $normalized,
            "HTML differs from committed snapshot '{$name}.html'. "
            .'If the change is intentional, re-run with REGRESSION_UPDATE_SNAPSHOTS=1 and commit the updated file.'
        );
    }

    public static function normalize(string $html): string
    {
        $patterns = [
            // APP_URL host — varies between local dev (captrack.caclv.org) and
            // CI (localhost). Mask the scheme+host of any absolute URL so
            // snapshots are environment-portable.
            '#https?://[^/"\'\s<>]+#' => '__APP_URL__',
            // Livewire 3 conditional render markers. These are framework
            // internals and emit/strip differently across environments
            // (debug mode, view cache state). Drop them.
            '/<!--\[if (?:BLOCK|ENDBLOCK)\]><!\[endif\]-->/' => '',
            // Livewire identifiers — regenerated every render.
            '/wire:id="[^"]*"/' => 'wire:id=""',
            '/wire:snapshot="[^"]*"/' => 'wire:snapshot=""',
            '/wire:effects="[^"]*"/' => 'wire:effects=""',
            '/wire:initial-data="[^"]*"/' => 'wire:initial-data=""',
            '/wire:socket="[^"]*"/' => 'wire:socket=""',
            // CSRF tokens. Use a value-attribute-only match so additional
            // attributes (e.g. autocomplete="off") elsewhere on the same
            // tag don't break normalization.
            '/(name="_token"[^>]*?\s+value=)"[^"]*"/i' => '$1""',
            '/(name="csrf-token"[^>]*?\s+content=)"[^"]*"/i' => '$1""',
            '/data-csrf="[^"]*"/' => 'data-csrf=""',
            '/x-csrf-token="[^"]*"/' => 'x-csrf-token=""',
            // Livewire component IDs leak through x-init expressions
            // like `window.Livewire.find('XYZ').on('$refresh', ...)`.
            // The ID is regenerated every render.
            '/Livewire\.find\(\'[^\']*\'\)/' => "Livewire.find('__WIRE_ID__')",
            // ISO 8601 timestamps.
            '/\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:?\d{2})?/' => '__TIMESTAMP__',
            // Resource URLs with autoincrement IDs.
            '#/project/\d+/#' => '/project/__ID__/',
            '#/participant/\d+\b#' => '/participant/__ID__',
            '#/property/\d+\b#' => '/property/__ID__',
            '#/contractor/\d+\b#' => '/contractor/__ID__',
            '#/contract/\d+\b#' => '/contract/__ID__',
            // Numeric <option value="N"> — typically FK dropdowns. String enum
            // values like "pending" are left intact.
            '/<option\s+value="\d+"/' => '<option value="__ID__"',
            // wire:click="method(N)" / wire:click="method(N, ...)" — the first
            // numeric arg is almost always a DB row id.
            '/wire:click="([a-zA-Z_][a-zA-Z0-9_]*)\(\d+(,[^)]*)?\)"/' => 'wire:click="$1(__ID__$2)"',
            // data-* attributes that hold autoincrement ids (Phase 3A
            // dashboard rows tag themselves data-project-id, data-
            // requirement-id, etc. for selectable testing).
            '/data-(project|requirement|milestone|task|document|fund-code)-id="\d+"/' => 'data-$1-id="__ID__"',
            // URL fragments like #milestone-42, #task-9 used for scroll-to-anchor.
            '/#([a-zA-Z][a-zA-Z_-]*)-\d+\b/' => '#$1-__ID__',
        ];

        $normalized = preg_replace(array_keys($patterns), array_values($patterns), $html) ?? $html;

        // Strip leading + trailing whitespace per line (HTML indentation drifts
        // between local view-cache state and CI fresh-compile state — the
        // structure matters, not the indentation), drop empty lines, then
        // collapse runs of blank lines.
        $lines = array_filter(
            array_map(fn ($line) => trim($line), explode("\n", $normalized)),
            fn ($line) => $line !== ''
        );
        $normalized = implode("\n", $lines);

        return rtrim($normalized)."\n";
    }
}
