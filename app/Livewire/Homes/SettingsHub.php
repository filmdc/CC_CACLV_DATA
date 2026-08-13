<?php

namespace App\Livewire\Homes;

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
                'label' => 'Fund codes',
                'sub' => 'Funding sources, expiry dates, and their required documents.',
                'route' => 'homes.settings.fund-codes',
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
        ])->filter(fn ($s) => $this->can($s['permission']))->values();

        return view('livewire.homes.settings-hub', [
            'surfaces' => $surfaces,
        ]);
    }
}
