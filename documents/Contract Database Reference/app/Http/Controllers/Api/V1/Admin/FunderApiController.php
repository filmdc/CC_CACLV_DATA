<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFunderRequest;
use App\Http\Requests\UpdateFunderRequest;
use App\Http\Resources\Admin\FunderResource;
use App\Models\Funder;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FunderApiController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('funder_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new FunderResource(Funder::all());
    }

    public function store(StoreFunderRequest $request)
    {
        $funder = Funder::create($request->all());

        return (new FunderResource($funder))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Funder $funder)
    {
        abort_if(Gate::denies('funder_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new FunderResource($funder);
    }

    public function update(UpdateFunderRequest $request, Funder $funder)
    {
        $funder->update($request->all());

        return (new FunderResource($funder))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Funder $funder)
    {
        abort_if(Gate::denies('funder_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $funder->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
