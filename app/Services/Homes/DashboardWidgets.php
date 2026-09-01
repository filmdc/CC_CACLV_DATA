<?php

namespace App\Services\Homes;

use App\Models\SiteSetting;

/**
 * Which cards the Housing portal's Today dashboard shows.
 *
 * Site-wide, not per-user: an admin decides what the dashboard is for, and
 * the project manager gets a consistent home screen. Stored as one JSON
 * SiteSetting so it rides the existing settings cache.
 *
 * The stored value is a sparse {key: bool} map layered over REGISTRY
 * defaults, so a widget added here later appears at its own default rather
 * than being silently hidden by an older saved value.
 */
class DashboardWidgets
{
    public const SETTING_KEY = 'homes_dashboard_widgets';

    /**
     * key => [label, sub, default]
     *
     * 'follow_ups' defaults to false: the follow-up queue duplicated what
     * Calls & Intakes already surfaces under its "Needs something" pill, so
     * it is off out of the box and re-enabled here when wanted.
     */
    public const REGISTRY = [
        'pinned' => [
            'label' => 'Pinned',
            'sub' => 'Calls and projects pinned to stay in front of you.',
            'default' => true,
        ],
        'follow_ups' => [
            'label' => 'Follow-ups due',
            'sub' => 'The follow-up queue, overdue first, with a one-click Done.',
            'default' => false,
        ],
        'waiting' => [
            'label' => 'Waiting on documents',
            'sub' => 'Intakes still missing paperwork, with what is outstanding.',
            'default' => true,
        ],
        'ready' => [
            'label' => 'Ready to become projects',
            'sub' => 'Intakes whose requirements are all met, ready to convert.',
            'default' => true,
        ],
        'glance' => [
            'label' => 'This week at a glance',
            'sub' => 'Calls logged, new intakes, active projects, fund expiring.',
            'default' => true,
        ],
    ];

    /** Registry defaults as a plain key => bool map. */
    public static function defaults(): array
    {
        return array_map(fn (array $w) => $w['default'], self::REGISTRY);
    }

    /** Effective on/off state for every known widget, in registry order. */
    public function state(): array
    {
        $stored = json_decode((string) SiteSetting::get(self::SETTING_KEY, '{}'), true);
        $stored = is_array($stored) ? $stored : [];

        $state = [];
        foreach (self::REGISTRY as $key => $widget) {
            $state[$key] = array_key_exists($key, $stored)
                ? (bool) $stored[$key]
                : $widget['default'];
        }

        return $state;
    }

    public function isEnabled(string $key): bool
    {
        return $this->state()[$key] ?? false;
    }

    /**
     * Persist the on/off map. Unknown keys are dropped so a stale form post
     * cannot write junk into the setting.
     */
    public function save(array $state): void
    {
        $clean = [];
        foreach (array_keys(self::REGISTRY) as $key) {
            $clean[$key] = (bool) ($state[$key] ?? false);
        }

        SiteSetting::set(self::SETTING_KEY, json_encode($clean), 'json');
    }

    /** Back to the built-in defaults (removes the override entirely). */
    public function resetToDefaults(): void
    {
        SiteSetting::set(self::SETTING_KEY, json_encode(self::defaults()), 'json');
    }
}
