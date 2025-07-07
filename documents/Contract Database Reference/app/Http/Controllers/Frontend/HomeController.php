<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyContractRequest;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Contract;
use App\Models\Funder;
use App\Models\Program;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class HomeController
{
    use MediaUploadingTrait, CsvImportTrait;

    public function index()
    {
        abort_if(Gate::denies('contract_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contracts = Contract::with(['programs', 'funder', 'media'])->get();

        $programs = Program::get();

        $funders = Funder::get();

        return view('frontend.home', compact('contracts', 'funders', 'programs'));
    }

    public function create()
    {
        abort_if(Gate::denies('contract_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $programs = Program::pluck('program_name', 'id');

        $funders = Funder::pluck('funder_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('frontend.contracts.create', compact('funders', 'programs'));
    }

    public function store(StoreContractRequest $request)
    {
        $contract = Contract::create($request->all());
        $contract->programs()->sync($request->input('programs', []));
        foreach ($request->input('documents', []) as $file) {
            $contract->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $contract->id]);
        }

        return redirect()->route('frontend.home');
    }

    public function edit(Contract $contract)
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $programs = Program::pluck('program_name', 'id');

        $funders = Funder::pluck('funder_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $contract->load('programs', 'funder');

        return view('frontend.contracts.edit', compact('contract', 'funders', 'programs'));
    }

    public function update(UpdateContractRequest $request, Contract $contract)
    {
        $contract->update($request->all());
        $contract->programs()->sync($request->input('programs', []));
        if (count($contract->documents) > 0) {
            foreach ($contract->documents as $media) {
                if (! in_array($media->file_name, $request->input('documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $contract->documents->pluck('file_name')->toArray();
        foreach ($request->input('documents', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $contract->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
            }
        }

        return redirect()->route('frontend.home');
    }

    public function show(Contract $contract)
    {
        abort_if(Gate::denies('contract_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contract->load('programs', 'funder', 'contractReportDueDates', 'contractReports');

        return view('frontend.contracts.show', compact('contract'));
    }

    public function destroy(Contract $contract)
    {
        abort_if(Gate::denies('contract_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contract->delete();

        return back();
    }

    public function massDestroy(MassDestroyContractRequest $request)
    {
        $contracts = Contract::find(request('ids'));

        foreach ($contracts as $contract) {
            $contract->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('contract_create') && Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new Contract();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
