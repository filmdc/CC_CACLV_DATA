<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Approval Timeline Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1f2937; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        .header h1 { font-size: 20px; color: #4f46e5; margin: 0 0 5px 0; }
        .header .subtitle { font-size: 12px; color: #6b7280; }
        .stats-grid { width: 100%; margin-bottom: 20px; }
        .stats-grid td { width: 25%; padding: 10px; text-align: center; }
        .stat-box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 8px; }
        .stat-value { font-size: 22px; font-weight: bold; }
        .stat-label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-total .stat-value { color: #4f46e5; }
        .stat-approved .stat-value { color: #059669; }
        .stat-rejected .stat-value { color: #dc2626; }
        .stat-resubmitted .stat-value { color: #d97706; }
        .section-title { font-size: 14px; font-weight: bold; color: #1f2937; margin: 20px 0 8px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .breakdown-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .breakdown-table th { background: #f3f4f6; text-align: left; padding: 6px 10px; font-size: 10px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        .breakdown-table td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .data-table th { background: #4f46e5; color: #ffffff; padding: 8px 6px; font-size: 10px; text-align: left; text-transform: uppercase; letter-spacing: 0.3px; }
        .data-table td { padding: 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        .data-table tr:nth-child(even) td { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-resubmitted { background: #fef3c7; color: #92400e; }
        .footer { text-align: center; margin-top: 30px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Approval Timeline Report</h1>
        <div class="subtitle">
            {{ \Carbon\Carbon::parse($dateFrom)->format('M j, Y') }} &mdash; {{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}
            &nbsp;|&nbsp; Detail Level: {{ ucfirst($detailLevel) }}
            &nbsp;|&nbsp; Generated: {{ $generatedAt }}
        </div>
    </div>

    <table class="stats-grid">
        <tr>
            <td>
                <div class="stat-box stat-total">
                    <div class="stat-value">{{ number_format($stats['total']) }}</div>
                    <div class="stat-label">Total Actions</div>
                </div>
            </td>
            <td>
                <div class="stat-box stat-approved">
                    <div class="stat-value">{{ number_format($stats['approved']) }}</div>
                    <div class="stat-label">Approved</div>
                </div>
            </td>
            <td>
                <div class="stat-box stat-rejected">
                    <div class="stat-value">{{ number_format($stats['rejected']) }}</div>
                    <div class="stat-label">Rejected</div>
                </div>
            </td>
            <td>
                <div class="stat-box stat-resubmitted">
                    <div class="stat-value">{{ number_format($stats['resubmitted']) }}</div>
                    <div class="stat-label">Resubmitted</div>
                </div>
            </td>
        </tr>
    </table>

    @if($detailLevel === 'summary' || $detailLevel === 'detailed')
        @if($stats['byType']->isNotEmpty())
        <div class="section-title">Breakdown by Type</div>
        <table class="breakdown-table">
            <thead><tr><th>Type</th><th>Count</th></tr></thead>
            <tbody>
                @foreach($stats['byType'] as $item)
                <tr><td>{{ $item['name'] }}</td><td>{{ $item['count'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if(is_countable($stats['byProgram']) && count($stats['byProgram']) > 0)
        <div class="section-title">Breakdown by Program</div>
        <table class="breakdown-table">
            <thead><tr><th>Program</th><th>Count</th></tr></thead>
            <tbody>
                @foreach($stats['byProgram'] as $item)
                <tr><td>{{ $item->name }}</td><td>{{ $item->count }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if($stats['byApprover']->isNotEmpty())
        <div class="section-title">Breakdown by Approver</div>
        <table class="breakdown-table">
            <thead><tr><th>Approver</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($stats['byApprover'] as $item)
                <tr><td>{{ $item['name'] }}</td><td>{{ $item['count'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @endif
    @endif

    @if($detailLevel !== 'summary' && $rows->isNotEmpty())
        @if($detailLevel === 'summary')
            {{-- No data table in summary mode --}}
        @else
            <div class="section-title">Activity Detail</div>
            @foreach($rows->chunk(50) as $chunkIndex => $chunk)
                @if($chunkIndex > 0)
                    <div class="page-break"></div>
                    <div class="section-title">Activity Detail (continued)</div>
                @endif
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Actioned By</th>
                            <th>Program</th>
                            @if($detailLevel === 'detailed')
                                <th>Category</th>
                                <th>Step</th>
                                <th>Reason/Notes</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chunk as $row)
                        <tr>
                            <td>{{ $row['Date'] }}</td>
                            <td>
                                <span class="badge badge-{{ strtolower($row['Action']) }}">{{ $row['Action'] }}</span>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($row['Item'], 40) }}</td>
                            <td>{{ $row['Type'] }}</td>
                            <td>{{ $row['Actioned By'] }}</td>
                            <td>{{ $row['Program'] }}</td>
                            @if($detailLevel === 'detailed')
                                <td>{{ $row['Category'] }}</td>
                                <td>{{ $row['Chain Step'] }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($row['Reason/Notes'], 60) }}</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @endif
    @endif

    <div class="footer">
        Community Action Partnership of Clark County &nbsp;|&nbsp; Generated {{ $generatedAt }}
    </div>
</body>
</html>
