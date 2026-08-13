<?php

namespace App\Livewire\Homes;

use App\Traits\WithPermissionChecks;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings (homes.caclv.org) — the five quarterly-touched admin surfaces,
 * out of the daily path. Per the design ("functionally unchanged — just out
 * of the way"), these remain the existing main-app pages; this screen is the
 * branded launcher. In production the .caclv.org session is shared, so the
 * links open without a re-login.
 */
#[Layout('components.layouts.homes')]
class Settings extends Component
{
    use WithPermissionChecks;

    public function render()
    {
        $mainApp = rtrim(env('MAIN_APP_URL', 'https://caclv-data.caclv.org'), '/');

        $surfaces = collect([
            [
                'label' => 'Fund codes',
                'sub' => 'Funding sources, expiry dates, and their requirement templates.',
                'url' => $mainApp.'/fund-codes',
                'permission' => null,
            ],
            [
                'label' => 'Requirements library',
                'sub' => 'The reusable checklist atoms behind intakes and projects.',
                'url' => $mainApp.'/requirements-library',
                'permission' => 'requirements_library.view',
            ],
            [
                'label' => 'PDF form templates',
                'sub' => 'Fillable forms generated from participant data.',
                'url' => $mainApp.'/admin/pdf-templates',
                'permission' => 'pdf_templates.view',
            ],
            [
                'label' => 'Contractors',
                'sub' => 'Licensing, insurance, and contact details.',
                'url' => $mainApp.'/contractor-management',
                'permission' => null,
            ],
            [
                'label' => 'Properties',
                'sub' => 'Addresses, property types, notes, and documents.',
                'url' => $mainApp.'/property-management',
                'permission' => null,
            ],
        ])->filter(fn ($s) => $s['permission'] === null || $this->can($s['permission']))->values();

        return view('livewire.homes.settings', [
            'surfaces' => $surfaces,
        ]);
    }
}
