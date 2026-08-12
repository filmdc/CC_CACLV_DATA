<?php

namespace App\Livewire\Homes;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.homes')]
class Stub extends Component
{
    public string $heading = 'Coming soon';

    public string $note = '';

    public function mount(): void
    {
        $map = [
            'homes.projects' => [
                'Projects',
                'The redesigned Projects list and Project Detail page plug in here. Building this is the next phase — for now, active rehab jobs continue to live in the main CAP TRACK app.',
            ],
            'homes.reports' => [
                'Reports',
                'Read-only reporting for funders and the board. Coming in a later phase.',
            ],
            'homes.settings' => [
                'Settings',
                'Fund codes · Requirements library · PDF form templates · Contractors · Properties. These setup surfaces will move here, functionally unchanged — out of the daily path.',
            ],
        ];

        [$this->heading, $this->note] = $map[request()->route()?->getName()] ?? ['Coming soon', ''];
    }

    public function render()
    {
        return view('livewire.homes.stub');
    }
}
