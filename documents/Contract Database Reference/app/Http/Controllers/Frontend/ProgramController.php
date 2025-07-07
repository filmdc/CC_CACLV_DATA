<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyProgramRequest;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\Program;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProgramController extends Controller
{
    use CsvImportTrait;

    public function index()
    {
        abort_if(Gate::denies('program_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $programs = Program::all();

        return view('frontend.programs.index', compact('programs'));
    }

    public function create()
    {
        abort_if(Gate::denies('program_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('frontend.programs.create');
    }

    public function store(StoreProgramRequest $request)
    {
        $program = Program::create($request->all());

        return redirect()->route('frontend.programs.index');
    }

    public function edit(Program $program)
    {
        abort_if(Gate::denies('program_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('frontend.programs.edit', compact('program'));
    }

    public function update(UpdateProgramRequest $request, Program $program)
    {
        $program->update($request->all());

        return redirect()->route('frontend.programs.index');
    }

    public function show(Program $program)
    {
        abort_if(Gate::denies('program_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('frontend.programs.show', compact('program'));
    }

    public function destroy(Program $program)
    {
        abort_if(Gate::denies('program_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $program->delete();

        return back();
    }

    public function massDestroy(MassDestroyProgramRequest $request)
    {
        Program::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
