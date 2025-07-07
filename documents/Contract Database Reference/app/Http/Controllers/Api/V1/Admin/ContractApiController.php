<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\Admin\ContractResource;
use App\Models\Contract;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContractApiController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('contract_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new ContractResource(Contract::with(['programs', 'funder'])->get());
    }

    public function store(StoreContractRequest $request)
    {
        $contract = Contract::create($request->all());
        $contract->programs()->sync($request->input('programs', []));
        foreach ($request->input('documents', []) as $file) {
            $contract->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
        }

        return (new ContractResource($contract))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Contract $contract)
    {
        abort_if(Gate::denies('contract_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new ContractResource($contract->load(['programs', 'funder']));
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

        return (new ContractResource($contract))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Contract $contract)
    {
        abort_if(Gate::denies('contract_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contract->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
