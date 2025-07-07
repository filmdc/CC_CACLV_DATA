<div class="m-3">
    @can('report_due_date_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.report-due-dates.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.reportDueDate.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="card">
        <div class="card-header">
            {{ trans('cruds.reportDueDate.title_singular') }} {{ trans('global.list') }}
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-contractReportDueDates">
                    <thead>
                        <tr>
                            <th width="10">

                            </th>
                            <th>
                                {{ trans('cruds.reportDueDate.fields.id') }}
                            </th>
                            <th>
                                {{ trans('cruds.reportDueDate.fields.due_date') }}
                            </th>
                            <th>
                                {{ trans('cruds.reportDueDate.fields.documents') }}
                            </th>
                            <th>
                                {{ trans('cruds.reportDueDate.fields.status') }}
                            </th>
                            <th>
                                {{ trans('cruds.reportDueDate.fields.email') }}
                            </th>
                            <th>
                                {{ trans('cruds.reportDueDate.fields.contract') }}
                            </th>
                            <th>
                                &nbsp;
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportDueDates as $key => $reportDueDate)
                            <tr data-entry-id="{{ $reportDueDate->id }}">
                                <td>

                                </td>
                                <td>
                                    {{ $reportDueDate->id ?? '' }}
                                </td>
                                <td>
                                    {{ $reportDueDate->due_date ?? '' }}
                                </td>
                                <td>
                                    @foreach($reportDueDate->documents as $key => $media)
                                        <a href="{{ $media->getUrl() }}" target="_blank">
                                            {{ trans('global.view_file') }}
                                        </a>
                                    @endforeach
                                </td>
                                <td>
                                    {{ App\Models\ReportDueDate::STATUS_SELECT[$reportDueDate->status] ?? '' }}
                                </td>
                                <td>
                                    @foreach($reportDueDate->emails as $key => $item)
                                        <span class="badge badge-info">{{ $item->email }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    {{ $reportDueDate->contract->title ?? '' }}
                                </td>
                                <td>
                                    @can('report_due_date_show')
                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.report-due-dates.show', $reportDueDate->id) }}">
                                            {{ trans('global.view') }}
                                        </a>
                                    @endcan

                                    @can('report_due_date_edit')
                                        <a class="btn btn-xs btn-info" href="{{ route('admin.report-due-dates.edit', $reportDueDate->id) }}">
                                            {{ trans('global.edit') }}
                                        </a>
                                    @endcan

                                    @can('report_due_date_delete')
                                        <form action="{{ route('admin.report-due-dates.destroy', $reportDueDate->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
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
@section('scripts')
@parent
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('report_due_date_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.report-due-dates.massDestroy') }}",
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
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  let table = $('.datatable-contractReportDueDates:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection