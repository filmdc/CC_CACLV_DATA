@extends('layouts.frontend')
@section('content')
<?php $contract2_id = $_REQUEST['contract_id'];
      $contract_title = $_REQUEST['title']; ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    {{ trans('global.create') }} {{ trans('cruds.reportDueDate.title_singular') }}
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route("frontend.report-due-dates.store") }}" enctype="multipart/form-data" onsubmit="return confirmAddToCalendar();">
                        @method('POST')
                        @csrf
                        <div class="form-group">
                          <input type="hidden" name="contract2_id" value="{{ $contract2_id }}" />
                            <label class="required" for="contract_id">{{ trans('cruds.reportDueDate.fields.contract') }}</label>
                            <select class="form-control select2" name="contract_id" id="contract_id" required>
                             @if($contract2_id != NULL && $contract_title != NULL){
                       
                                <option value="{{ $contract2_id }}" selected>{{ $contract_title }}</option>
                               
                              
                                @foreach($contracts as $id => $entry)
                                    <option value="{{ $id }}" {{ old('contract_id') == $id ? '' : '' }}>{{ $entry }}</option>
                                @endforeach

                                @else

                                @foreach($contracts as $id => $entry)
                                    <option value="{{ $id }}" {{ old('contract_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach

                                @endif 
                            </select>
                            @if($errors->has('contract'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('contract') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.reportDueDate.fields.contract_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label class="required" for="due_date">{{ trans('cruds.reportDueDate.fields.due_date') }}</label>
                            <input class="form-control date" type="text" name="due_date" id="due_date" value="{{ old('due_date') }}" required>
                            @if($errors->has('due_date'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('due_date') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.reportDueDate.fields.due_date_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="documents">{{ trans('cruds.reportDueDate.fields.documents') }}</label>
                            <div class="needsclick dropzone" id="documents-dropzone">
                            </div>
                            @if($errors->has('documents'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('documents') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.reportDueDate.fields.documents_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label class="required">{{ trans('cruds.reportDueDate.fields.status') }}</label>
                            <select class="form-control" name="status" id="status" required>
                                <option value disabled {{ old('status', null) === null ? 'selected' : '' }}>{{ trans('global.pleaseSelect') }}</option>
                                @foreach(App\Models\ReportDueDate::STATUS_SELECT as $key => $label)
                                    <option value="{{ $key }}" {{ old('status', 'Status of Report') === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('status'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('status') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.reportDueDate.fields.status_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="description">{{ trans('cruds.reportDueDate.fields.description') }}</label>
                            <textarea class="form-control ckeditor" name="description" id="description">{!! old('description') !!}</textarea>
                            @if($errors->has('description'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('description') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.reportDueDate.fields.description_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="emails">{{ trans('cruds.reportDueDate.fields.email') }}</label>
                            <div style="padding-bottom: 4px">
                                <span class="btn btn-info btn-xs select-all" style="border-radius: 0">{{ trans('global.select_all') }}</span>
                                <span class="btn btn-info btn-xs deselect-all" style="border-radius: 0">{{ trans('global.deselect_all') }}</span>
                            </div>
                            <select class="form-control select2" name="emails[]" id="emails" multiple>
                                @foreach($emails as $id => $email)
                                    <option value="{{ $id }}" {{ in_array($id, old('emails', [])) ? 'selected' : '' }}>{{ $email }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('emails'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('emails') }}
                                </div>
                            @endif
                            <span class="help-block">{{ trans('cruds.reportDueDate.fields.email_helper') }}</span>
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
    var uploadedDocumentsMap = {}
Dropzone.options.documentsDropzone = {
    url: '{{ route('frontend.report-due-dates.storeMedia') }}',
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
                xhr.open('POST', '{{ route('frontend.report-due-dates.storeCKEditorImages') }}', true);
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
<script>

// function submitFormWithAjax() {
//     var form = $('form');
//     $.ajax({
//         url: form.attr('action'),
//         type: 'POST',
//         data: form.serialize(),
//         success: function(response) {
//             // Assuming response contains the new due date ID
//             var newDueDateId = response.newDueDateId; 
//             confirmAddToCalendar(newDueDateId);
//         },
//         error: function(xhr, status, error) {
//             // Handle error
//         }
//     });
//     return false; // Prevent default form submission
// }

// function confirmAddToCalendar(dueDateId) {
//     if (confirm('Would you like to add this due date to the calendar?')) {
//       $.ajax({
//     url: '/api/v1/add-to-calendar', // Updated URL
//     type: 'POST',
//     headers: {
//         'X-CSRF-TOKEN': "{{ csrf_token() }}"
//     },
//     data: {
//         due_date_id: dueDateId
//     },
//     success: function(data) {
//         alert(data.message); // Show success message
//     },
//     error: function(xhr, status, error) {
//         // Handle error
//     }
// });
//     }
// }

// $('form').on('submit', submitFormWithAjax);
// </script>

@endsection