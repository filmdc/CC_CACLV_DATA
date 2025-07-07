<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyReportDueDateRequest;
use App\Http\Requests\StoreReportDueDateRequest;
use App\Http\Requests\UpdateReportDueDateRequest;
use App\Models\Contract;
use App\Models\DueDateEmail;
use App\Models\ReportDueDate;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class ReportDueDateController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('report_due_date_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $reportDueDates = ReportDueDate::with(['emails', 'contract', 'media'])->get();

        $due_date_emails = DueDateEmail::get();

        $contracts = Contract::get();

        return view('admin.reportDueDates.index', compact('contracts', 'due_date_emails', 'reportDueDates'));
    }

    public function create()
    {
        abort_if(Gate::denies('report_due_date_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $emails = DueDateEmail::pluck('email', 'id');

        $contracts = Contract::pluck('contract_number', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.reportDueDates.create', compact('contracts', 'emails'));
    }

    public function store(StoreReportDueDateRequest $request)
    {
        $reportDueDate = ReportDueDate::create($request->all());
        $reportDueDate->emails()->sync($request->input('emails', []));
        foreach ($request->input('documents', []) as $file) {
            $reportDueDate->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $reportDueDate->id]);
        }

        return redirect()->route('admin.report-due-dates.index');
    }

    public function edit(ReportDueDate $reportDueDate)
    {
        abort_if(Gate::denies('report_due_date_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $emails = DueDateEmail::pluck('email', 'id');

        $contracts = Contract::pluck('contract_number', 'id')->prepend(trans('global.pleaseSelect'), '');

        $reportDueDate->load('emails', 'contract');

        return view('admin.reportDueDates.edit', compact('contracts', 'emails', 'reportDueDate'));
    }

    public function update(UpdateReportDueDateRequest $request, ReportDueDate $reportDueDate)
    {
        $reportDueDate->update($request->all());
        $reportDueDate->emails()->sync($request->input('emails', []));
        if (count($reportDueDate->documents) > 0) {
            foreach ($reportDueDate->documents as $media) {
                if (!in_array($media->file_name, $request->input('documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $reportDueDate->documents->pluck('file_name')->toArray();
        foreach ($request->input('documents', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $reportDueDate->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
            }
        }

        return redirect()->route('admin.report-due-dates.index');
    }

    public function show(ReportDueDate $reportDueDate)
    {
        abort_if(Gate::denies('report_due_date_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $reportDueDate->load('emails', 'contract');

        return view('admin.reportDueDates.show', compact('reportDueDate'));
    }

    public function destroy(ReportDueDate $reportDueDate)
    {
        abort_if(Gate::denies('report_due_date_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $reportDueDate->delete();

        return back();
    }

    public function massDestroy(MassDestroyReportDueDateRequest $request)
    {
        ReportDueDate::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('report_due_date_create') && Gate::denies('report_due_date_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new ReportDueDate();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
