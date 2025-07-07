<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateContractRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('contract_edit');
    }

    public function rules()
    {
        return [
            'contract_number' => [
                'string',
                'nullable',
            ],
            'date_of_award' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'programs.*' => [
                'integer',
            ],
            'programs' => [
                'array',
            ],
            'start_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'end_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'documents' => [
                'array',
            ],
            'grant_code' => [
                'string',
                'nullable',
            ],
            'title' => [
                'string',
                'required',
            ],
        ];
    }
}
