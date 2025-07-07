<?php

namespace App\Http\Requests;

use App\Models\Funder;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateFunderRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('funder_edit');
    }

    public function rules()
    {
        return [
            'funder_name' => [
                'string',
                'required',
                'unique:funders,funder_name,' . request()->route('funder')->id,
            ],
        ];
    }
}
