@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.show') }} {{ trans('cruds.reportDueDate.title') }}
    </div>

    <div class="card-body">
        <div class="form-group">
            <div class="form-group">
                <a class="btn btn-default" href="{{ route('admin.report-due-dates.index') }}">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th>
                            {{ trans('cruds.reportDueDate.fields.id') }}
                        </th>
                        <td>
                            {{ $reportDueDate->id }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.reportDueDate.fields.due_date') }}
                        </th>
                        <td>
                            {{ $reportDueDate->due_date }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.reportDueDate.fields.documents') }}
                        </th>
                        <td>
                            @foreach($reportDueDate->documents as $key => $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    {{ trans('global.view_file') }}
                                </a>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.reportDueDate.fields.status') }}
                        </th>
                        <td>
                            {{ App\Models\ReportDueDate::STATUS_SELECT[$reportDueDate->status] ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.reportDueDate.fields.description') }}
                        </th>
                        <td>
                            {!! $reportDueDate->description !!}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.reportDueDate.fields.email') }}
                        </th>
                        <td>
                            @foreach($reportDueDate->emails as $key => $email)
                                <span class="label label-info">{{ $email->email }}</span>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.reportDueDate.fields.contract') }}
                        </th>
                        <td>
                            {{ $reportDueDate->contract->contract_number ?? '' }}
                        </td>
                    </tr>
                   
                </tbody>
            </table>
            <div class="form-group">
                <a class="btn btn-default" href="{{ route('admin.report-due-dates.index') }}">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
        </div>
    </div>
</div>



@endsection