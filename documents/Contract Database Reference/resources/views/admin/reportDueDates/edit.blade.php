@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.edit') }} {{ trans('cruds.reportDueDate.title_singular') }}
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route("admin.report-due-dates.update", [$reportDueDate->id]) }}" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <div class="form-group">
                <label class="required" for="due_date">{{ trans('cruds.reportDueDate.fields.due_date') }}</label>
                <input class="form-control date {{ $errors->has('due_date') ? 'is-invalid' : '' }}" type="text" name="due_date" id="due_date" value="{{ old('due_date', $reportDueDate->due_date) }}" required>
                @if($errors->has('due_date'))
                    <span class="text-danger">{{ $errors->first('due_date') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.reportDueDate.fields.due_date_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="documents">{{ trans('cruds.reportDueDate.fields.documents') }}</label>
                <div class="needsclick dropzone {{ $errors->has('documents') ? 'is-invalid' : '' }}" id="documents-dropzone">
                </div>
                @if($errors->has('documents'))
                    <span class="text-danger">{{ $errors->first('documents') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.reportDueDate.fields.documents_helper') }}</span>
            </div>
            <div class="form-group">
                <label class="required">{{ trans('cruds.reportDueDate.fields.status') }}</label>
                <select class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status" id="status" required>
                    <option value disabled {{ old('status', null) === null ? 'selected' : '' }}>{{ trans('global.pleaseSelect') }}</option>
                    @foreach(App\Models\ReportDueDate::STATUS_SELECT as $key => $label)
                        <option value="{{ $key }}" {{ old('status', $reportDueDate->status) === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @if($errors->has('status'))
                    <span class="text-danger">{{ $errors->first('status') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.reportDueDate.fields.status_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="description">{{ trans('cruds.reportDueDate.fields.description') }}</label>
                <textarea class="form-control ckeditor {{ $errors->has('description') ? 'is-invalid' : '' }}" name="description" id="description">{!! old('description', $reportDueDate->description) !!}</textarea>
                @if($errors->has('description'))
                    <span class="text-danger">{{ $errors->first('description') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.reportDueDate.fields.description_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="emails">{{ trans('cruds.reportDueDate.fields.email') }}</label>
                <div style="padding-bottom: 4px">
                    <span class="btn btn-info btn-xs select-all" style="border-radius: 0">{{ trans('global.select_all') }}</span>
                    <span class="btn btn-info btn-xs deselect-all" style="border-radius: 0">{{ trans('global.deselect_all') }}</span>
                </div>
                <select class="form-control select2 {{ $errors->has('emails') ? 'is-invalid' : '' }}" name="emails[]" id="emails" multiple>
                    @foreach($emails as $id => $email)
                        <option value="{{ $id }}" {{ (in_array($id, old('emails', [])) || $reportDueDate->emails->contains($id)) ? 'selected' : '' }}>{{ $email }}</option>
                    @endforeach
                </select>
                @if($errors->has('emails'))
                    <span class="text-danger">{{ $errors->first('emails') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.reportDueDate.fields.email_helper') }}</span>
            </div>
            <div class="form-group">
                <label class="required" for="contract_id">{{ trans('cruds.reportDueDate.fields.contract') }}</label>
                <select class="form-control select2 {{ $errors->has('contract') ? 'is-invalid' : '' }}" name="contract_id" id="contract_id" required>
                    @foreach($contracts as $id => $entry)
                        <option value="{{ $id }}" {{ (old('contract_id') ? old('contract_id') : $reportDueDate->contract->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                    @endforeach
                </select>
                @if($errors->has('contract'))
                    <span class="text-danger">{{ $errors->first('contract') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.reportDueDate.fields.contract_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="reminder_second">{{ trans('cruds.reportDueDate.fields.reminder_second') }}</label>
                <input class="form-control date {{ $errors->has('reminder_second') ? 'is-invalid' : '' }}" type="text" name="reminder_second" id="reminder_second" value="{{ old('reminder_second', $reportDueDate->reminder_second) }}">
                @if($errors->has('reminder_second'))
                    <span class="text-danger">{{ $errors->first('reminder_second') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.reportDueDate.fields.reminder_second_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="reminder_third">{{ trans('cruds.reportDueDate.fields.reminder_third') }}</label>
                <input class="form-control date {{ $errors->has('reminder_third') ? 'is-invalid' : '' }}" type="text" name="reminder_third" id="reminder_third" value="{{ old('reminder_third', $reportDueDate->reminder_third) }}">
                @if($errors->has('reminder_third'))
                    <span class="text-danger">{{ $errors->first('reminder_third') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.reportDueDate.fields.reminder_third_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="reminder_first">{{ trans('cruds.reportDueDate.fields.reminder_first') }}</label>
                <input class="form-control date {{ $errors->has('reminder_first') ? 'is-invalid' : '' }}" type="text" name="reminder_first" id="reminder_first" value="{{ old('reminder_first', $reportDueDate->reminder_first) }}">
                @if($errors->has('reminder_first'))
                    <span class="text-danger">{{ $errors->first('reminder_first') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.reportDueDate.fields.reminder_first_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="notified">{{ trans('cruds.reportDueDate.fields.notified') }}</label>
                <input class="form-control {{ $errors->has('notified') ? 'is-invalid' : '' }}" type="number" name="notified" id="notified" value="{{ old('notified', $reportDueDate->notified) }}" step="1">
                @if($errors->has('notified'))
                    <span class="text-danger">{{ $errors->first('notified') }}</span>
                @endif
                <span class="help-block">{{ trans('cruds.reportDueDate.fields.notified_helper') }}</span>
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

@section('scripts')
<script>
    var uploadedDocumentsMap = {}
Dropzone.options.documentsDropzone = {
    url: '{{ route('admin.report-due-dates.storeMedia') }}',
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
@if(isset($reportDueDate) && $reportDueDate->documents)
          var files =
            {!! json_encode($reportDueDate->documents) !!}
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
                xhr.open('POST', '{{ route('admin.report-due-dates.storeCKEditorImages') }}', true);
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
                data.append('crud_id', '{{ $reportDueDate->id ?? 0 }}');
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

@endsection