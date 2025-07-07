@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.show') }} {{ trans('cruds.contract.title') }}
    </div>

    <div class="card-body">
        <div class="form-group">
            <div class="form-group">
                <a class="btn btn-default" href="{{ route('admin.contracts.index') }}">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.id') }}
                        </th>
                        <td>
                            {{ $contract->id }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.title') }}
                        </th>
                        <td>
                            {{ $contract->title }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.contract_number') }}
                        </th>
                        <td>
                            {{ $contract->contract_number }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.purpose') }}
                        </th>
                        <td>
                            {!! $contract->purpose !!}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.date_of_award') }}
                        </th>
                        <td>
                            {{ $contract->date_of_award }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.program') }}
                        </th>
                        <td>
                            @foreach($contract->programs as $key => $program)
                                <span class="label label-info">{{ $program->program_name }}</span>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.funder') }}
                        </th>
                        <td>
                            {{ $contract->funder->funder_name ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.amount') }}
                        </th>
                        <td>
                            {{ $contract->amount }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.start_date') }}
                        </th>
                        <td>
                            {{ $contract->start_date }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.end_date') }}
                        </th>
                        <td>
                            {{ $contract->end_date }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.documents') }}
                        </th>
                        <td>
                            @foreach($contract->documents as $key => $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    {{ trans('global.view_file') }}
                                </a>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.notes') }}
                        </th>
                        <td>
                            {!! $contract->notes !!}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.contract.fields.grant_code') }}
                        </th>
                        <td>
                            {{ $contract->grant_code }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="form-group">
                <a class="btn btn-default" href="{{ route('admin.contracts.index') }}">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        {{ trans('global.relatedData') }}
    </div>
    <ul class="nav nav-tabs" role="tablist" id="relationship-tabs">
        <li class="nav-item">
            <a class="nav-link" href="#contract_report_due_dates" role="tab" data-toggle="tab">
                {{ trans('cruds.reportDueDate.title') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#contract_reports" role="tab" data-toggle="tab">
                {{ trans('cruds.report.title') }}
            </a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane" role="tabpanel" id="contract_report_due_dates">
            @includeIf('admin.contracts.relationships.contractReportDueDates', ['reportDueDates' => $contract->contractReportDueDates])
        </div>
        <div class="tab-pane" role="tabpanel" id="contract_reports">
            @includeIf('admin.contracts.relationships.contractReports', ['reports' => $contract->contractReports])
        </div>
    </div>
</div>

@endsection