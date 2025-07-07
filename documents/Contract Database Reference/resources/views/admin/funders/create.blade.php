@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.create') }} {{ trans('cruds.funder.title_singular') }}
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route("admin.funders.store") }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="required" for="funder_name">{{ trans('cruds.funder.fields.funder_name') }}</label>
                <input class="form-control {{ $errors->has('funder_name') ? 'is-invalid' : '' }}" type="text" name="funder_name" id="funder_name" value="{{ old('funder_name', '') }}" required>
                @if($errors->has('funder_name'))
                    <span class="text-danger">{{ $errors->first('funder_name') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.funder.fields.funder_name_helper') }}</span>
            </div>
            <div class="form-group">
                <button class="btn btn-danger" type="submit">
                    {{ trans('global.save') }}
                </button>
            </div>
        </form>
    </div>
</div>



@endsection