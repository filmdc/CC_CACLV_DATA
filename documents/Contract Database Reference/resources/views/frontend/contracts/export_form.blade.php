@extends('layouts.frontend')

@section('content')
<div class="container">
    <h1>Export Contracts</h1>
    <form action="{{ route('frontend.contracts.export') }}" method="GET">
        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" id="start_date" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" id="end_date" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="program">Program:</label>
            <select name="program" id="program" class="form-control">
                <option value="">All Programs</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="funder">Funder:</label>
            <select name="funder" id="funder" class="form-control">
                <option value="">All Funders</option>
                @foreach($funders as $funder)
                    <option value="{{ $funder->id }}">{{ $funder->funder_name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Export</button>
    </form>
</div>
@endsection
