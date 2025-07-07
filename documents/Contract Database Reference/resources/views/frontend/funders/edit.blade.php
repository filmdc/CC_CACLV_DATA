@extends('layouts.frontend')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    {{ trans('global.edit') }} {{ trans('cruds.funder.title_singular') }}
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route("frontend.funders.update", [$funder->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group">
                            <label class="required" for="funder_name">{{ trans('cruds.funder.fields.funder_name') }}</label>
                            <input class="form-control" type="text" name="funder_name" id="funder_name" value="{{ old('funder_name', $funder->funder_name) }}" required>
                            @if($errors->has('funder_name'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('funder_name') }}
                                </div>
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

        </div>
    </div>
</div>
@endsection