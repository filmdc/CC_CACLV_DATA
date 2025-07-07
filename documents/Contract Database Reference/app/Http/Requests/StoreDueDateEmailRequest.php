<?php

namespace App\Http\Requests;

use App\Models\DueDateEmail;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreDueDateEmailRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('due_date_email_create');
    }

    public function rules()
    {
        return [
            'email' => [
                'required',
                'unique:due_date_emails',
            ],
        ];
    }
}
