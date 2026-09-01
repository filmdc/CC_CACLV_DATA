<?php

namespace App\Livewire\Homes\Settings;

use App\Services\Homes\DashboardWidgets as Registry;
use App\Traits\WithPermissionChecks;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Dashboard widgets (homes.caclv.org/settings/dashboard).
 *
 * Admin-facing: choose which cards the Today home shows. Site-wide, so what
 * is toggled here is what the project manager sees.
 *
 * Gated on admin OR homes.dashboard.manage rather than route middleware,
 * because CheckPermission has no admin bypass — this way an administrator
 * reaches it with no permission seeding, and the permission exists for
 * delegating it to a non-admin later.
 */
#[Layout('components.layouts.homes')]
class DashboardWidgets extends Component
{
    use WithPermissionChecks;

    public const PERMISSION = 'homes.dashboard.manage';

    /** key => bool, mirrors the registry. */
    public array $widgets = [];

    public function mount(Registry $registry): void
    {
        abort_unless($this->canManage(), 403, 'You do not have permission to change the dashboard.');

        $this->widgets = $registry->state();
    }

    public function canManage(): bool
    {
        return $this->isAdmin() || $this->can(self::PERMISSION);
    }

    public function save(Registry $registry): void
    {
        abort_unless($this->canManage(), 403);

        $registry->save($this->widgets);
        $this->widgets = $registry->state();

        session()->flash('homes_message', 'Dashboard updated. Kaitlyn will see the change on her next visit to Today.');
    }

    public function resetToDefaults(Registry $registry): void
    {
        abort_unless($this->canManage(), 403);

        $registry->resetToDefaults();
        $this->widgets = $registry->state();

        session()->flash('homes_message', 'Dashboard reset to the standard set of cards.');
    }

    public function render()
    {
        return view('livewire.homes.settings.dashboard-widgets', [
            'registry' => Registry::REGISTRY,
            'defaults' => Registry::defaults(),
        ]);
    }
}
