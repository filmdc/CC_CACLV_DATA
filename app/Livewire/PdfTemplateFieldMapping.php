<?php

namespace App\Livewire;

use App\Models\PdfFormTemplate;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class PdfTemplateFieldMapping extends Component
{
    use WithPermissionChecks;

    public $templateId;
    public $template;

    public function mount($id)
    {
        $this->authorizeAction('pdf_templates.edit');

        $this->templateId = $id;
        $this->template = PdfFormTemplate::with('fieldMappings')->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.pdf-template-field-mapping');
    }
}
