@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.create') }} {{ trans('cruds.program.title_singular') }}
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route("admin.programs.store") }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="program_name">{{ trans('cruds.program.fields.program_name') }}</label>
                <input class="form-control {{ $errors->has('program_name') ? 'is-invalid' : '' }}" type="text" name="program_name" id="program_name" value="{{ old('program_name', '') }}">
                @if($errors->has('program_name'))
                    <span class="text-danger">{{ $errors->first('program_name') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.program.fields.program_name_helper') }}</span>
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