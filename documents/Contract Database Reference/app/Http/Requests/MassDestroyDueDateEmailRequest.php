<?php

namespace App\Http\Requests;

use App\Models\DueDateEmail;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyDueDateEmailRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('due_date_email_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:due_date_emails,id',
        ];
    }
}
