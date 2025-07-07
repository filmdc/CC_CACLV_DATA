<?php

namespace App\Http\Requests;

use App\Models\Funder;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyFunderRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('funder_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:funders,id',
        ];
    }
}
