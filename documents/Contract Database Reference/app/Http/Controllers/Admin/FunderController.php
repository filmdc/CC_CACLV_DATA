<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyFunderRequest;
use App\Http\Requests\StoreFunderRequest;
use App\Http\Requests\UpdateFunderRequest;
use App\Models\Funder;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FunderController extends Controller
{
    use CsvImportTrait;

    public function index()
    {
        abort_if(Gate::denies('funder_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $funders = Funder::all();

        return view('admin.funders.index', compact('funders'));
    }

    public function create()
    {
        abort_if(Gate::denies('funder_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.funders.create');
    }

    public function store(StoreFunderRequest $request)
    {
        $funder = Funder::create($request->all());

        return redirect()->route('admin.funders.index');
    }

    public function edit(Funder $funder)
    {
        abort_if(Gate::denies('funder_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.funders.edit', compact('funder'));
    }

    public function update(UpdateFunderRequest $request, Funder $funder)
    {
        $funder->update($request->all());

        return redirect()->route('admin.funders.index');
    }

    public function show(Funder $funder)
    {
        abort_if(Gate::denies('funder_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.funders.show', compact('funder'));
    }

    public function destroy(Funder $funder)
    {
        abort_if(Gate::denies('funder_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $funder->delete();

        return back();
    }

    public function massDestroy(MassDestroyFunderRequest $request)
    {
        $funders = Funder::find(request('ids'));

        foreach ($funders as $funder) {
            $funder->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
