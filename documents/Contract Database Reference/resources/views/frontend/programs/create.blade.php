@extends('layouts.frontend')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    {{ trans('global.create') }} {{ trans('cruds.program.title_singular') }}
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route("frontend.programs.store") }}" enctype="multipart/form-data">
                        @method('POST')
                        @csrf
                        <div class="form-group">
                            <label for="program_name">{{ trans('cruds.program.fields.program_name') }}</label>
                            <input class="form-control" type="text" name="program_name" id="program_name" value="{{ old('program_name', '') }}">
                            @if($errors->has('program_name'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('program_name') }}
                                </div>
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

        </div>
    </div>
</div>
@endsection