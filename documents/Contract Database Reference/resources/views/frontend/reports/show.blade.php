@extends('layouts.frontend')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    {{ trans('global.show') }} {{ trans('cruds.report.title') }}
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('frontend.reports.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.report.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $report->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.report.fields.upload') }}
                                    </th>
                                    <td>
                                        @foreach($report->upload as $key => $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank">
                                                {{ trans('global.view_file') }}
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.report.fields.contract') }}
                                    </th>
                                    <td>
                                        {{ $report->contract->title ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.report.fields.due_date') }}
                                    </th>
                                    <td>
                                        {{ $report->due_date->due_date ?? '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('frontend.reports.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection