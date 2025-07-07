<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Http\Resources\Admin\ReportResource;
use App\Models\Report;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportApiController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new ReportResource(Report::with(['contract', 'due_date'])->get());
    }

    public function store(StoreReportRequest $request)
    {
        $report = Report::create($request->all());

        foreach ($request->input('upload', []) as $file) {
            $report->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('upload');
        }

        return (new ReportResource($report))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Report $report)
    {
        abort_if(Gate::denies('report_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new ReportResource($report->load(['contract', 'due_date']));
    }

    public function update(UpdateReportRequest $request, Report $report)
    {
        $report->update($request->all());

        if (count($report->upload) > 0) {
            foreach ($report->upload as $media) {
                if (! in_array($media->file_name, $request->input('upload', []))) {
                    $media->delete();
                }
            }
        }
        $media = $report->upload->pluck('file_name')->toArray();
        foreach ($request->input('upload', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $report->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('upload');
            }
        }

        return (new ReportResource($report))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Report $report)
    {
        abort_if(Gate::denies('report_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $report->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
