@extends('layouts.frontend')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    {{ trans('global.create') }} {{ trans('cruds.contract.title_singular') }}
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route("frontend.contracts.store") }}" enctype="multipart/form-data">
                        @method('POST')
                        @csrf
                        <div class="form-group">
                            <label class="required" for="title">{{ trans('cruds.contract.fields.title') }}</label>
                            <input class="form-control" type="text" name="title" id="title" value="{{ old('title', '') }}" required>
                            @if($errors->has('title'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('title') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.title_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="contract_number">{{ trans('cruds.contract.fields.contract_number') }}</label>
                            <input class="form-control" type="text" name="contract_number" id="contract_number" value="{{ old('contract_number', '') }}">
                            @if($errors->has('contract_number'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('contract_number') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.contract_number_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="cfda_number">{{ trans('cruds.contract.fields.cfda_number') }}</label>
                            <input class="form-control" type="text" name="cfda_number" id="cfda_number" value="{{ old('cfda_number', '') }}">
                            @if($errors->has('cfda_number'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('cfda_number') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.cfda_number_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="purpose">{{ trans('cruds.contract.fields.purpose') }}</label>
                            <textarea class="form-control ckeditor" name="purpose" id="purpose">{!! old('purpose') !!}</textarea>
                            @if($errors->has('purpose'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('purpose') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.purpose_helper') }}</span>
                        </div>
                        <!-- <div class="form-group">
                            <label for="date_of_award">{{ trans('cruds.contract.fields.date_of_award') }}</label>
                            <input class="form-control date" type="text" name="date_of_award" id="date_of_award" value="{{ old('date_of_award') }}">
                            @if($errors->has('date_of_award'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('date_of_award') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.date_of_award_helper') }}</span>
                        </div> -->
                        <div class="form-group">
                            <label for="programs">{{ trans('cruds.contract.fields.program') }}</label>
                            <div style="padding-bottom: 4px">
                                <span class="btn btn-info btn-xs select-all" style="border-radius: 0">{{ trans('global.select_all') }}</span>
                                <span class="btn btn-info btn-xs deselect-all" style="border-radius: 0">{{ trans('global.deselect_all') }}</span>
                            </div>
                            <select class="form-control select2" name="programs[]" id="programs" multiple>
                                @foreach($programs as $id => $program)
                                    <option value="{{ $id }}" {{ in_array($id, old('programs', [])) ? 'selected' : '' }}>{{ $program }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('programs'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('programs') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.program_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="funder_id">{{ trans('cruds.contract.fields.funder') }}</label>
                            <select class="form-control select2" name="funder_id" id="funder_id">
                                @foreach($funders as $id => $entry)
                                    <option value="{{ $id }}" {{ old('funder_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('funder'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('funder') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.funder_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="amount">{{ trans('cruds.contract.fields.amount') }}</label>
                            <input class="form-control" type="number" name="amount" id="amount" value="{{ old('amount', '0.00') }}" step="0.01">
                            @if($errors->has('amount'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('amount') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.amount_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="start_date">{{ trans('cruds.contract.fields.start_date') }}</label>
                            <input class="form-control date" type="text" name="start_date" id="start_date" value="{{ old('start_date') }}">
                            @if($errors->has('start_date'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('start_date') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.start_date_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="end_date">{{ trans('cruds.contract.fields.end_date') }}</label>
                            <input class="form-control date" type="text" name="end_date" id="end_date" value="{{ old('end_date') }}">
                            @if($errors->has('end_date'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('end_date') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.end_date_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="documents">{{ trans('cruds.contract.fields.documents') }}</label>
                            <div class="needsclick dropzone" id="documents-dropzone">
                            </div>
                            @if($errors->has('documents'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('documents') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.documents_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="notes">{{ trans('cruds.contract.fields.notes') }}</label>
                            <textarea class="form-control ckeditor" name="notes" id="notes">{!! old('notes') !!}</textarea>
                            @if($errors->has('notes'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('notes') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.notes_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="grant_code">{{ trans('cruds.contract.fields.grant_code') }}</label>
                            <input class="form-control" type="text" name="grant_code" id="grant_code" value="{{ old('grant_code', '') }}">
                            @if($errors->has('grant_code'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('grant_code') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.contract.fields.grant_code_helper') }}</span>
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
    $(document).ready(function () {
  function SimpleUploadAdapter(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = function(loader) {
      return {
        upload: function() {
          return loader.file
            .then(function (file) {
              return new Promise(function(resolve, reject) {
                // Init request
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '{{ route('frontend.contracts.storeCKEditorImages') }}', true);
                xhr.setRequestHeader('x-csrf-token', window._token);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.responseType = 'json';

                // Init listeners
                var genericErrorText = `Couldn't upload file: ${ file.name }.`;
                xhr.addEventListener('error', function() { reject(genericErrorText) });
                xhr.addEventListener('abort', function() { reject() });
                xhr.addEventListener('load', function() {
                  var response = xhr.response;

                  if (!response || xhr.status !== 201) {
                    return reject(response && response.message ? `${genericErrorText}\n${xhr.status} ${response.message}` : `${genericErrorText}\n ${xhr.status} ${xhr.statusText}`);
                  }

                  $('form').append('<input type="hidden" name="ck-media[]" value="' + response.id + '">');

                  resolve({ default: response.url });
                });

                if (xhr.upload) {
                  xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                      loader.uploadTotal = e.total;
                      loader.uploaded = e.loaded;
                    }
                  });
                }

                // Send request
                var data = new FormData();
                data.append('upload', file);
                data.append('crud_id', '{{ $contract->id ?? 0 }}');
                xhr.send(data);
              });
            })
        }
      };
    }
  }

  var allEditors = document.querySelectorAll('.ckeditor');
  for (var i = 0; i < allEditors.length; ++i) {
    ClassicEditor.create(
      allEditors[i], {
        extraPlugins: [SimpleUploadAdapter]
      }
    );
  }
});
</script>

<script>
    var uploadedDocumentsMap = {}
Dropzone.options.documentsDropzone = {
    url: '{{ route('frontend.contracts.storeMedia') }}',
    maxFilesize: 50, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 50
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="documents[]" value="' + response.name + '">')
      uploadedDocumentsMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedDocumentsMap[file.name]
      }
      $('form').find('input[name="documents[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($contract) && $contract->documents)
          var files =
            {!! json_encode($contract->documents) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="documents[]" value="' + file.file_name + '">')
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