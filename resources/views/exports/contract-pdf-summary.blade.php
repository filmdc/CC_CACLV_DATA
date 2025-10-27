<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contract Summary - {{ $summary['title'] }}</title>
    <style>
        @page { margin: 32px 40px 36px; }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
        }

        h1, h2, h3 {
            margin: 0;
            color: #0f172a;
        }

        h1 { font-size: 22px; margin-bottom: 6px; }
        h2 { font-size: 15px; margin-top: 18px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.08em; color: #1d4ed8; }
        h3 { font-size: 13px; margin-bottom: 4px; }

        .meta {
            font-size: 11px;
            color: #64748b;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .grid th,
        .grid td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        .grid th {
            width: 28%;
            background-color: #e0f2fe;
            color: #0f172a;
            font-weight: 600;
        }

        .rich-text {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background-color: #f8fafc;
        }

        .rich-text p {
            margin: 0 0 6px;
        }

        .rich-text ul,
        .rich-text ol {
            margin: 6px 0 6px 18px;
        }

        .footer-note {
            margin-top: 24px;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <header>
        <h1>{{ $summary['title'] }}</h1>
        <div class="meta">
            Generated {{ $generatedAt->format('M j, Y g:i A') }}
        </div>
    </header>

    <section>
        <h2>Contract Overview</h2>
        <table class="grid">
            <tbody>
                <tr>
                    <th>Contract Number</th>
                    <td>{{ $summary['contract_number'] }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ $summary['status'] }}</td>
                </tr>
                <tr>
                    <th>Grant Code</th>
                    <td>{{ $summary['grant_code'] }}</td>
                </tr>
                <tr>
                    <th>CFDA Number</th>
                    <td>{{ $summary['cfda_number'] }}</td>
                </tr>
                <tr>
                    <th>Contract Amount</th>
                    <td>{{ $summary['amount'] ? '$' . $summary['amount'] : 'Not specified' }}</td>
                </tr>
                <tr>
                    <th>Programs</th>
                    <td>{{ $summary['programs'] }}</td>
                </tr>
                <tr>
                    <th>Funder</th>
                    <td>{{ $summary['funder'] }}</td>
                </tr>
                <tr>
                    <th>Start Date</th>
                    <td>{{ $summary['start_date'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>End Date</th>
                    <td>{{ $summary['end_date'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Date of Award</th>
                    <td>{{ $summary['date_of_award'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Archived?</th>
                    <td>{{ $summary['archived'] ? 'Yes' . ($summary['date_archived'] ? ' ('.$summary['date_archived'].')' : '') : 'No' }}</td>
                </tr>
                <tr>
                    <th>Record History</th>
                    <td>
                        Created {{ $summary['created_at'] ?? 'N/A' }}
                        @if($summary['created_by']) by {{ $summary['created_by'] }} @endif
                        <br>
                        Updated {{ $summary['updated_at'] ?? 'N/A' }}
                        @if($summary['updated_by']) by {{ $summary['updated_by'] }} @endif
                    </td>
                </tr>
                <tr>
                    <th>Linked Documents</th>
                    <td>{{ $summary['documents_count'] }}</td>
                </tr>
                <tr>
                    <th>Reporting Due Dates</th>
                    <td>{{ $summary['report_due_dates_count'] }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Purpose</h2>
        <div class="rich-text">
            {!! $summary['purpose'] !!}
        </div>
    </section>

    <p class="footer-note">
        This page captures the primary contract details as stored in CAP Track. Attachments and report submissions follow in the subsequent sections of this document.
    </p>
</body>
</html>
