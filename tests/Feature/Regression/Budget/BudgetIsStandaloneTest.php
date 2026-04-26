<?php

/**
 * P-24 tripwire: the Housing budget module must remain a standalone
 * tracker — not integrated with Sage Intacct, MIP, DonorPerfect, or any
 * external fiscal connector. See the "Housing budget module — design
 * intent" section of CLAUDE.md and the class docblocks on ProjectBudget
 * and ProjectExpense.
 *
 * This test is grep-based and scoped to use-statement imports and FQN
 * class refs only. Free-text mentions in comments / docblocks (e.g.
 * "// not connected to Sage") deliberately do NOT trip the wire — the
 * point is to catch real coupling, not docblock language.
 */
$disallowedSymbols = ['Sage', 'Intacct', 'Mip', 'DonorPerfect'];

$auditFiles = [
    'app/Models/ProjectBudget.php',
    'app/Models/ProjectExpense.php',
    'app/Models/HousingJob.php',
    'app/Livewire/ProjectManagement.php',
    'app/Livewire/ProjectsManagement.php',
];

it('the housing budget code does not import or FQN-ref fiscal systems (P-24)', function () use ($disallowedSymbols, $auditFiles) {
    $alternation = '('.implode('|', $disallowedSymbols).')';
    // Use statements: `use Foo\Bar\Sage\…;`, `use Sage\…;` etc.
    $usePattern = '/^\s*use\s+[^;]*\b'.$alternation.'\b/m';
    // FQN refs anywhere: `\Sage\…`, `App\…\Intacct\…`, `Mip\…\Foo`.
    // Match a backslash-bounded namespace segment containing the symbol.
    $fqnPattern = '/\\\\'.$alternation.'\\\\/';

    $offenders = [];
    foreach ($auditFiles as $relative) {
        $file = base_path($relative);
        expect(file_exists($file))->toBeTrue("audit target missing: {$relative}");

        $content = file_get_contents($file);

        if (preg_match_all($usePattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $offenders[] = "{$relative}:{$line} — use-statement: ".trim($match[0]);
            }
        }
        if (preg_match_all($fqnPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $offenders[] = "{$relative}:{$line} — FQN ref: ".trim($match[0]);
            }
        }
    }

    if (! empty($offenders)) {
        $msg = "Housing budget code must not import or reference fiscal systems. Found:\n  - ".implode("\n  - ", $offenders);
        $msg .= "\n\nIf you have a legitimate reason to add fiscal coupling here, escalate to David before building. See CLAUDE.md → 'Housing budget module — design intent'.";
        expect($offenders)->toBeEmpty($msg);
    }

    expect($offenders)->toBeEmpty();
})->group('regression');

it('the tripwire grep does not false-positive on "sage" inside common words (P-24 sanity)', function () use ($disallowedSymbols) {
    // Sanity: confirm the regex would NOT trip on substrings like "usage",
    // "message", "passage", or "sage" appearing in a comment without
    // namespace separators on either side.
    $alternation = '('.implode('|', $disallowedSymbols).')';
    $usePattern = '/^\s*use\s+[^;]*\b'.$alternation.'\b/m';
    $fqnPattern = '/\\\\'.$alternation.'\\\\/';

    $hayloft = <<<'PHP'
<?php
// Increment template usage counter
$message = $e->getMessage();
session()->flash('error', 'Failed: '.$e->getMessage());
// We are explicitly NOT connected to Sage Intacct here.
$passage = "the sage advice was: don't do that";
PHP;

    expect(preg_match($usePattern, $hayloft))->toBe(0);
    expect(preg_match($fqnPattern, $hayloft))->toBe(0);
})->group('regression');

it('the tripwire grep DOES catch a real fiscal-system import (P-24 sanity)', function () use ($disallowedSymbols) {
    $alternation = '('.implode('|', $disallowedSymbols).')';
    $usePattern = '/^\s*use\s+[^;]*\b'.$alternation.'\b/m';
    $fqnPattern = '/\\\\'.$alternation.'\\\\/';

    $offending = <<<'PHP'
<?php

namespace App\Models;

use App\Services\Sage\IntacctClient;
use App\Services\DonorPerfect\Connector;

class ProjectBudget extends Model
{
    public function syncToSage()
    {
        return resolve(\App\Services\Mip\Sync::class)->push($this);
    }
}
PHP;

    expect(preg_match($usePattern, $offending))->toBeGreaterThan(0);
    expect(preg_match($fqnPattern, $offending))->toBeGreaterThan(0);
})->group('regression');
