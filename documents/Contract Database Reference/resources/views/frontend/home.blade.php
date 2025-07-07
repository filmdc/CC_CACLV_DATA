@extends('layouts.frontend')

@section('content')@extends('layouts.frontend')
@section('content')
@php
  $icons = [
      'pdf' => 'fa-xl fa-file-pdf',
      'doc' => 'fa-xl fa-file-word',
      'docx' => 'fa-xl fa-file-word',
      'xls' => 'fa-xl fa-file-excel',
      'xlsx' => 'fa-xl fa-file-excel',
      'ppt' => 'fa-xl fa-file-powerpoint',
      'pptx' => 'fa-xl fa-file-powerpoint',
      'zip' => 'fa-xl fa-file-archive',
      'rar' => 'fa-xl fa-file-archive',
      'jpg' => 'fa-xl fa-file-image',
      'jpeg' => 'fa-xl fa-file-image',
      'png' => 'fa-xl fa-file-image',
      'gif' => 'fa-xl fa-file-image',
      'txt' => 'fa-xl fa-file-alt',
      'mp3' => 'fa-xl fa-file-audio',
      'wav' => 'fa-xl fa-file-audio',
      'mp4' => 'fa-xl fa-file-video',
      'avi' => 'fa-xl fa-file-video',
      // Add more extensions and icons here as needed
  ];
  $icolors = [
      'pdf' => 'iconColor-pdf',
      'doc' => 'iconColor-doc',
      'docx' => 'iconColor-docx',
      'xls' => 'iconColor-xls',
      'xlsx' => 'iconColor-xlsx',
      'ppt' => 'iconColor-ppt',
      'pptx' => 'iconColor-pptx',
      'zip' => 'iconColor-zip',
      'rar' => 'iconColor-rar',
      'jpg' => 'iconColor-jpg',
      'jpeg' => 'iconColor-jpeg',
      'png' => 'iconColor-png',
      'gif' => 'iconColor-gif',
      'txt' => 'iconColor-txt',
      'mp3' => 'iconColor-mp3',
      'wav' => 'iconColor-wav',
      'mp4' => 'iconColor-mp4',
      'avi' => 'iconColor-avi',
      // Add more extensions and icons here as needed
  ];
@endphp


<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
           
                <div style="margin-bottom: 10px;" class="row">
                    <div class="col-md-12">
                    <!-- @can('contract_create') -->
                        <!-- <a class="btn btn-success" href="{{ route('frontend.contracts.create') }}">
                            {{ trans('global.add') }} {{ trans('cruds.contract.title_singular') }}
                        </a> -->
                        <!-- <button class="btn btn-warning" data-toggle="modal" data-target="#csvImportModal">
                            {{ trans('global.app_csvImport') }}
                        </button> -->
                        
                        <!-- @include('csvImport.modal', ['model' => 'Contract', 'route' => 'admin.contracts.parseCsvImport'])
                        @endcan -->
                    </div>
                </div>
            
            <div class="card">
                <div class="card-header">
                    {{ trans('cruds.contract.title_singular') }} {{ trans('global.list') }} Dashboard
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="ContractsWithDueDates" class="table table-bordered table-striped table-hover datatable datatable-Home">
                            <thead>
                                <tr>
                                    <th>
                                        &nbsp;
                                    </th>
                                    <th>
                                        {{ trans('cruds.contract.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.contract.fields.title') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.contract.fields.contract_number') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.contract.fields.cfda_number') }}
                                    </th>
                             
                                    <th>
                                        {{ trans('cruds.contract.fields.program') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.contract.fields.funder') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.contract.fields.amount') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.contract.fields.start_date') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.contract.fields.end_date') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.contract.fields.documents') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.contract.fields.grant_code') }}
                                    </th>
                                    <th>
                                    </th>
                                </tr>
</thead>
                            <tbody>
                                @foreach($contracts as $key => $contract)
                                    <tr data-entry-id="{{ $contract->id }}">
                                    <td>
                                            @can('contract_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('frontend.contracts.show', $contract->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('contract_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('frontend.contracts.edit', $contract->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

         

                                        </td>
                                        <td>
                                            {{ $contract->id ?? '' }}
                                        </td>
                                        <td>
                                            {{ $contract->title ?? '' }}
                                        </td>
                                        <td>
                                            {{ $contract->contract_number ?? '' }}
                                        </td>
                                        <td>
                                            {{ $contract->cfda_number ?? '' }}
                                        </td> 
                                        <td>
                                            @foreach($contract->programs as $key => $item)
                                                <span>{{ $item->program_name }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            {{ $contract->funder->funder_name ?? '' }}
                                        </td>
                                        <td>
                                           ${{ number_format($contract->amount, 2, '.', ',') ?? '' }}
                                        </td>
                                        <td>
                                            {{ $contract->start_date ?? '' }}
                                        </td>
                                        <td>
                                            {{ $contract->end_date ?? '' }}
                                        </td>
                                        <td>
                                            @foreach($contract->documents as $key => $media)
                                           @php
                                            $extension = strtolower(pathinfo($media->getUrl(), PATHINFO_EXTENSION));
                                            $iconClass = isset($icons[$extension]) ? $icons[$extension] : 'file-icon';

                                            $iconColor = isset($icolors[$extension]) ? $icolors[$extension] : 'fa-file';


                                           @endphp
                                                <a href="{{ $media->getUrl() }}" target="_blank">
                                               
                                                <i class="fa {{ $iconClass }} {{$iconColor}}"></i>
                                                {{ trans('global.view_file') }}
                                                </a></br>
                                            @endforeach
                                        </td>
                                        <td>
                                            {{ $contract->grant_code ?? '' }}
                                        </td>
                                        
                                        <td>
                                        @can('contract_delete')
                                                <form action="{{ route('frontend.contracts.destroy', $contract->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="submit" class="btn btn-xs btn-danger" value="{{ trans('global.delete') }}">
                                                </form>
                                                @endcan
                                        </td>
                                       

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
@section('scripts')
@parent
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('contract_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('frontend.contracts.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }
  dtButtons.push(deleteButton)
@endcan

  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'asc' ]],
    pageLength: 100,
  });
  let table = $('#ContractsWithDueDates').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
let visibleColumnsIndexes = null;
$('.datatable thead').on('input', '.search', function (e) {
    e.stopPropagation(); // Prevent event bubbling up to the column sorting
    e.preventDefault();  // Prevent the default action

      let strict = $(this).attr('strict') || false
      let value = strict && this.value ? "^" + this.value + "$" : this.value


      let index = $(this).closest('th').index(); // Ensure correct column index is used


      if (visibleColumnsIndexes !== null) {
        index = visibleColumnsIndexes[index]
      }

      table
        .column(index)
        .search(value, strict)
        .draw()
  });
table.on('column-visibility.dt', function(e, settings, column, state) {
      visibleColumnsIndexes = []
      table.columns(":visible").every(function(colIdx) {
          visibleColumnsIndexes.push(colIdx);
      });
  })
 
})


</script>
@endsection

