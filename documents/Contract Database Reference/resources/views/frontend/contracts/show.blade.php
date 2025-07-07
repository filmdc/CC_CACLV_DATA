@extends('layouts.frontend')
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
  
  function output($string, $number) {
    echo substr($string, $number);    
    }

    $previousURL = url()->previous();
    $backToRoute = route('frontend.contracts.index'); // Default back route

    if (strpos($previousURL, '/contracts/archived') !== false) {
        $backToRoute = route('frontend.contracts.archived');
    }

@endphp

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    {{ trans('global.show') }} {{ trans('cruds.contract.title') }}
                </div>
                @if($contract->archived == 'yes')
                     <button id="archiveButton" class="btn btn-warning" style="position: absolute; top: 10px; right: 10px;" onclick="toggleArchiveStatus(false)">Unarchive this Contract</button>
                       <p style="color: red; font-weight: bold;">Archived on {{ $contract->date_archived }}</p>
                        @else
                       <button id="archiveButton" class="btn btn-primary" style="position: absolute; top: 10px; right: 10px;" onclick="toggleArchiveStatus(true)">Archive this Contract</button>
                @endif

                <div class="card-body">
                   
                    <div class="form-group">
                     <a class="btn btn-default" href="{{ $backToRoute }}">
                         {{ trans('global.back_to_list') }}
                             </a>
                        </div>

                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>
                                 <h2>   {{ $contract->title }}</h2>
                                    </th>
                                    <td>
                                   <strong> {{ trans('cruds.contract.fields.id') }}:</strong>  {{ $contract->id }} <br />
                                   <strong>{{ trans('cruds.contract.fields.contract_number') }}:</strong>  {{ $contract->contract_number }}<br />
                                   <strong>{{ trans('cruds.contract.fields.cfda_number') }}:</strong>  {{ $contract->cfda_number }}<br />
                                   <strong>{{ trans('cruds.contract.fields.grant_code') }}:</strong> {{ $contract->grant_code }}<br />






                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <Strong>{{ trans('cruds.contract.fields.program') }}: </strong> @foreach($contract->programs as $key => $program)
                                                                                                        <span class="label label-info">{{ $program->program_name }}</span> 
                                                                                                         @endforeach </br>
                                      <strong> {{ trans('cruds.contract.fields.funder') }}:</strong> {{ $contract->funder->funder_name ?? '' }}<br />
                                       <strong>{{ trans('cruds.contract.fields.amount') }}:</strong> ${{ number_format($contract->amount, 2, '.', ',') }}<br />
                                    

                                    </td>
                                    <td>
                                    <!-- <strong> {{ trans('cruds.contract.fields.date_of_award') }}:</strong> {{ $contract->date_of_award }}<br /><br /> -->
                                    <strong> {{ trans('cruds.contract.fields.start_date') }}:</strong> {{ $contract->start_date }}<br />
                                    <strong> {{ trans('cruds.contract.fields.end_date') }}:</strong> {{ $contract->end_date }}<br />
                                    
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                    <strong>  {{ trans('cruds.contract.fields.purpose') }}:</strong> <br /> {!! $contract->purpose !!}
                                    </td>

                                </tr>
                                <tr>
                                    <td colspan="2">
                                    <strong>  {{ trans('cruds.contract.fields.notes') }}:</strong> <br /> {!! $contract->notes !!}
                                    </td>

                                </tr>
                                <tr>
                                    <td colspan="2">
                                     <strong>   {{ trans('cruds.contract.fields.documents') }} </strong><br />
                                    
                                    
                                     @foreach($contract->documents as $key => $media)
                                           @php
                                            $extension = strtolower(pathinfo($media->getUrl(), PATHINFO_EXTENSION));
                                            $iconClass = isset($icons[$extension]) ? $icons[$extension] : 'file-icon';

                                            $iconColor = isset($icolors[$extension]) ? $icolors[$extension] : 'fa-file';


                                           @endphp
                                                <a href="{{ $media->getUrl() }}" target="_blank">
                                               
                                                <i class="fa {{ $iconClass }} {{$iconColor}}"></i>@php
                                                output("$media->name.$extension", 14);
                                                @endphp
                                                <!-- {{ trans('global.view_file') }} -->
                                                
                                                </a><br />
                                            @endforeach
                                    </td>
                                </tr>
                                
                            </tbody>
                            
                        </table>
                        <div class="form-group">
                        <!-- @can('report_due_date_create')
                        <a class="btn btn-success" href="{{ route('frontend.report-due-dates.create', ['c_id' => $contract->id, 'title' => $contract->title]) }}">
                            {{ trans('global.add') }} {{ trans('cruds.reportDueDate.title_singular') }}
                              </a><br/>
                        @endcan -->
                     
                            <a class="btn btn-default" href="{{ route('frontend.contracts.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="card">
    <div class="card-header">
        {{ trans('global.relatedData') }}
    </div>
    <ul class="nav nav-tabs" role="tablist" id="relationship-tabs">
        <li class="nav-item">
            <a class="nav-link" href="#contract_report_due_dates" role="tab" data-toggle="tab">
                {{ trans('cruds.reportDueDate.title') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#contract_reports" role="tab" data-toggle="tab">
                {{ trans('cruds.report.title') }}
            </a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane" role="tabpanel" id="contract_report_due_dates">
            @includeIf('frontend.contracts.relationships.contractReportDueDates', ['reportDueDates' => $contract->contractReportDueDates])
        </div>
        <div class="tab-pane" role="tabpanel" id="contract_reports">
            @includeIf('frontend.contracts.relationships.contractReports', ['reports' => $contract->contractReports])
        </div>
    </div>
</div>
</div>
<script>
function toggleArchiveStatus(archive) {
    const confirmationMessage = archive ? 'Are you sure you want to archive this contract?' : 'Are you sure you want to unarchive this contract?';
    if(confirm(confirmationMessage)) {
        console.log("Confirmation Submitted"); // javascript working?
        axios.post('{{ route("frontend.contracts.toggleArchive", ["id" => $contract->id]) }}', {
        archived: archive ? 'yes' : '',
       _method: 'PATCH',
       _token: '{{ csrf_token() }}'

        })
        .then(response => {
            window.location.reload(); // Reload the page to reflect the changes
        })
        .catch(error => {
            console.error('There was an error toggling the archive status:', error);
        });
    }
}
</script>

@endsection