<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyDueDateEmailRequest;
use App\Http\Requests\StoreDueDateEmailRequest;
use App\Http\Requests\UpdateDueDateEmailRequest;
use App\Models\DueDateEmail;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DueDateEmailController extends Controller
{
    use CsvImportTrait;

    public function index()
    {
        abort_if(Gate::denies('due_date_email_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $dueDateEmails = DueDateEmail::all();

        return view('admin.dueDateEmails.index', compact('dueDateEmails'));
    }

    public function create()
    {
        abort_if(Gate::denies('due_date_email_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.dueDateEmails.create');
    }

    public function store(StoreDueDateEmailRequest $request)
    {
        $dueDateEmail = DueDateEmail::create($request->all());

        return redirect()->route('admin.due-date-emails.index');
    }

    public function edit(DueDateEmail $dueDateEmail)
    {
        abort_if(Gate::denies('due_date_email_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.dueDateEmails.edit', compact('dueDateEmail'));
    }

    public function update(UpdateDueDateEmailRequest $request, DueDateEmail $dueDateEmail)
    {
        $dueDateEmail->update($request->all());

        return redirect()->route('admin.due-date-emails.index');
    }

    public function show(DueDateEmail $dueDateEmail)
    {
        abort_if(Gate::denies('due_date_email_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.dueDateEmails.show', compact('dueDateEmail'));
    }

    public function destroy(DueDateEmail $dueDateEmail)
    {
        abort_if(Gate::denies('due_date_email_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $dueDateEmail->delete();

        return back();
    }

    public function massDestroy(MassDestroyDueDateEmailRequest $request)
    {
        DueDateEmail::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
