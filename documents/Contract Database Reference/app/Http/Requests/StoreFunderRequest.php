<?php

namespace App\Http\Requests;

use App\Models\Funder;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreFunderRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('funder_create');
    }

    public function rules()
    {
        return [
            'funder_name' => [
                'string',
                'required',
                'unique:funders',
            ],
        ];
    }
}
