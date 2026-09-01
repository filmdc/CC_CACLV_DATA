<?php

namespace App\Livewire\Homes;

use App\Livewire\Homes\Settings\DashboardWidgets;
use App\Traits\WithPermissionChecks;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings hub (homes.caclv.org) — the five quarterly-touched admin surfaces,
 * out of the daily path, each now a portal-native page.
 */
#[Layout('components.layouts.homes')]
class SettingsHub extends Component
{
    use WithPermissionChecks;

    public function render()
    {
        $surfaces = collect([
            [
                'label' => 'Dashboard widgets',
                'sub' => 'Which cards the Today home shows.',
                'route' => 'homes.settings.dashboard',
                'permission' => DashboardWidgets::PERMISSION,
                'admin_ok' => true,
            ],
            [
                'label' => 'Fund codes',
                'sub' => 'Funding sources, expiry dates, and their required documents.',
                'route' => 'homes.settings.fund-codes',
                'permission' => 'fund_codes.view',
            ],
            [
                'label' => 'Counties',
                'sub' => 'What picking a county attaches: its funds and required documents.',
                'route' => 'homes.settings.counties',
                'permission' => 'fund_codes.view',
            ],
            [
                'label' => 'Requirements library',
                'sub' => 'The reusable checklist items behind intakes and projects.',
                'route' => 'homes.settings.requirements',
                'permission' => 'requirements_library.view',
            ],
            [
                'label' => 'PDF form templates',
                'sub' => 'Fillable forms generated from participant data.',
                'route' => 'homes.settings.pdf-templates',
                'permission' => 'pdf_templates.view',
            ],
            [
                'label' => 'Contractors',
                'sub' => 'Licensing, insurance, and contact details.',
                'route' => 'homes.settings.contractors',
                'permission' => 'contractors.view',
            ],
            [
                'label' => 'Properties',
                'sub' => 'Addresses, property types, notes, and households.',
                'route' => 'homes.settings.properties',
                'permission' => 'properties.view',
            ],
        ])->filter(fn ($s) => $this->can($s['permission'])
            || (($s['admin_ok'] ?? false) && $this->isAdmin()))->values();

        return view('livewire.homes.settings-hub', [
            'surfaces' => $surfaces,
        ]);
    }
}
