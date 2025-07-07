@extends('layouts.frontend')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    {{ trans('global.create') }} {{ trans('cruds.report.title_singular') }}
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route("frontend.reports.store") }}" enctype="multipart/form-data">
                        @method('POST')
                        @csrf
                        <div class="form-group">
                            <label for="upload">{{ trans('cruds.report.fields.upload') }}</label>
                            <div class="needsclick dropzone" id="upload-dropzone">
                            </div>
                            @if($errors->has('upload'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('upload') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.report.fields.upload_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="contract_id">{{ trans('cruds.report.fields.contract') }}</label>
                            <select class="form-control select2" name="contract_id" id="contract_id">
                                @foreach($contracts as $id => $entry)
                                    <option value="{{ $id }}" {{ old('contract_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('contract'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('contract') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.report.fields.contract_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="due_date_id">{{ trans('cruds.report.fields.due_date') }}</label>
                            <select class="form-control select2" name="due_date_id" id="due_date_id">
                                @foreach($due_dates as $id => $entry)
                                    <option value="{{ $id }}" {{ old('due_date_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('due_date'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('due_date') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.report.fields.due_date_helper') }}</span>
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

@section('scripts')
<script>
    var uploadedUploadMap = {}
Dropzone.options.uploadDropzone = {
    url: '{{ route('frontend.reports.storeMedia') }}',
    maxFilesize: 25, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 25
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="upload[]" value="' + response.name + '">')
      uploadedUploadMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedUploadMap[file.name]
      }
      $('form').find('input[name="upload[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($report) && $report->upload)
          var files =
            {!! json_encode($report->upload) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="upload[]" value="' + file.file_name + '">')
            }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}
</script>
@endsection