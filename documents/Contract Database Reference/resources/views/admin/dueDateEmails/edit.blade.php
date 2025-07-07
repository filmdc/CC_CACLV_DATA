@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.edit') }} {{ trans('cruds.dueDateEmail.title_singular') }}
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route("admin.due-date-emails.update", [$dueDateEmail->id]) }}" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <div class="form-group">
                <label class="required" for="email">{{ trans('cruds.dueDateEmail.fields.email') }}</label>
                <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email" name="email" id="email" value="{{ old('email', $dueDateEmail->email) }}" required>
                @if($errors->has('email'))
                    <span class="text-danger">{{ $errors->first('email') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.dueDateEmail.fields.email_helper') }}</span>
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