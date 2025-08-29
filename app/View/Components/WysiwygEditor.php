<?php

namespace App\View\Components;

use Illuminate\View\Component;

class WysiwygEditor extends Component
{
    public $model;
    public $rows;
    public $placeholder;
    public $id;
    public $class;

    public function __construct($model, $rows = 4, $placeholder = '', $id = null, $class = '')
    {
        $this->model = $model;
        $this->rows = $rows;
        $this->placeholder = $placeholder;
        $this->id = $id ?: 'wysiwyg_' . uniqid();
        $this->class = $class;
    }

    public function render()
    {
        return view('components.wysiwyg-editor');
    }
}