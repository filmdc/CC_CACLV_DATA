<?php

namespace App\Http\Requests;

use App\Models\ReportDueDate;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateReportDueDateRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('report_due_date_edit');
    }

    public function rules()
    {
        return [
            'due_date' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'documents' => [
                'array',
            ],
            'status' => [
                'required',
            ],
            'emails.*' => [
                'integer',
            ],
            'emails' => [
                'array',
            ],
            'contract_id' => [
                'required',
                'integer',
            ],
            'reminder_second' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'reminder_third' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'reminder_first' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'notified' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
        ];
    }
}
