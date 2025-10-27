<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reporting Due Dates - {{ $contract->title }}</title>
    <style>
        @page { margin: 32px 40px 36px; }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
        }

        h1, h2 {
            margin: 0;
            color: #0f172a;
        }

        h1 { font-size: 20px; margin-bottom: 6px; }
        h2 { font-size: 14px; margin-top: 18px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.1em; color: #1d4ed8; }

        .meta {
            font-size: 11px;
            color: #64748b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th, td {
            border: 1px solid #dbeafe;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #bfdbfe;
            color: #1e3a8a;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
        }

        .badge-active { background-color: #d1fae5; color: #065f46; }
        .badge-inactive { background-color: #fee2e2; color: #991b1b; }

        .footer-note {
            margin-top: 18px;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <header>
        <h1>Reporting Due Dates</h1>
        <div class="meta">
            Contract: {{ $contract->title }} • Generated {{ $generatedAt->format('M j, Y g:i A') }}
        </div>
    </header>

    <section>
        <table>
            <thead>
                <tr>
                    <th>Report Type</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Submissions</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dueDateSummaries as $dueDate)
                    <tr>
                        <td>
                            {{ $dueDate['type'] }}<br>
                            @if($dueDate['active'])
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge badge-inactive">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $dueDate['due_date'] }}</td>
                        <td>{{ $dueDate['status'] }}</td>
                        <td>
                            {{ $dueDate['submitted_count'] }} submitted
                            @if($dueDate['last_submitted_date'])
                                <br><span class="meta">Last: {{ $dueDate['last_submitted_date'] }}</span>
                            @endif
                        </td>
                        <td>{{ $dueDate['description'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <p class="footer-note">
        Due-date information reflects the state of CAP Track at the time of export. Submission files follow in the next section.
    </p>
</body>
</html>
