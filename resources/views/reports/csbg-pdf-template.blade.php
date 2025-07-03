<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSBG Module 4 Section C Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0 0 5px 0;
            color: #1e40af;
        }
        .header p {
            margin: 0;
            color: #64748b;
            font-size: 11px;
        }
        .metadata {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .metadata-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .metadata-item {
            margin-bottom: 8px;
        }
        .metadata-label {
            font-weight: bold;
            color: #374151;
        }
        .metadata-value {
            color: #6b7280;
        }
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #2563eb;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .category {
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .category-title {
            background-color: #f3f4f6;
            padding: 8px 12px;
            font-weight: bold;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th,
        .data-table td {
            padding: 6px 12px;
            text-align: left;
            border-bottom: 1px solid #f3f4f6;
        }
        .data-table th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #374151;
            font-size: 10px;
        }
        .data-table td {
            font-size: 10px;
        }
        .data-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .count {
            text-align: right;
            font-weight: bold;
        }
        .percentage {
            text-align: right;
            color: #6b7280;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CSBG Module 4 Section C Report</h1>
        <p>Community Services Block Grant - Participant Characteristics</p>
    </div>

    <div class="metadata">
        <div class="metadata-grid">
            <div class="metadata-item">
                <span class="metadata-label">Report Period:</span>
                <span class="metadata-value">{{ $periodStart }} - {{ $periodEnd }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Generated:</span>
                <span class="metadata-value">{{ $generatedAt }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Total Participants:</span>
                <span class="metadata-value">{{ number_format($reportData['participant_count']) }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Reference Data Set:</span>
                <span class="metadata-value">{{ $reportData['report_metadata']['reference_data_set']['name'] ?? 'N/A' }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Program Filter:</span>
                <span class="metadata-value">{{ $reportData['report_metadata']['program_filter'] }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Heads of Household Only:</span>
                <span class="metadata-value">{{ $reportData['report_metadata']['heads_of_household_only'] ? 'Yes' : 'No' }}</span>
            </div>
            @php
                $advancedFilters = $reportData['report_metadata']['advanced_filters'] ?? [];
                $hasAdvancedFilters = !empty($advancedFilters['gender']) || !empty($advancedFilters['ethnicity']) || 
                                     !empty($advancedFilters['education_level']) || !empty($advancedFilters['work_status']) ||
                                     $advancedFilters['age_min'] || $advancedFilters['age_max'] || 
                                     !empty($advancedFilters['household_type']);
            @endphp
            @if($hasAdvancedFilters)
                @if(!empty($advancedFilters['gender']))
                    <div class="metadata-item">
                        <span class="metadata-label">Gender Filter:</span>
                        <span class="metadata-value">{{ implode(', ', $advancedFilters['gender']) }}</span>
                    </div>
                @endif
                @if(!empty($advancedFilters['ethnicity']))
                    <div class="metadata-item">
                        <span class="metadata-label">Ethnicity Filter:</span>
                        <span class="metadata-value">{{ implode(', ', $advancedFilters['ethnicity']) }}</span>
                    </div>
                @endif
                @if(!empty($advancedFilters['education_level']))
                    <div class="metadata-item">
                        <span class="metadata-label">Education Filter:</span>
                        <span class="metadata-value">{{ implode(', ', $advancedFilters['education_level']) }}</span>
                    </div>
                @endif
                @if(!empty($advancedFilters['work_status']))
                    <div class="metadata-item">
                        <span class="metadata-label">Work Status Filter:</span>
                        <span class="metadata-value">{{ implode(', ', $advancedFilters['work_status']) }}</span>
                    </div>
                @endif
                @if($advancedFilters['age_min'] || $advancedFilters['age_max'])
                    <div class="metadata-item">
                        <span class="metadata-label">Age Range Filter:</span>
                        <span class="metadata-value">
                            @if($advancedFilters['age_min'])Min: {{ $advancedFilters['age_min'] }}@endif
                            @if($advancedFilters['age_max']){{ $advancedFilters['age_min'] ? ', ' : '' }}Max: {{ $advancedFilters['age_max'] }}@endif
                        </span>
                    </div>
                @endif
                @if(!empty($advancedFilters['household_type']))
                    <div class="metadata-item">
                        <span class="metadata-label">Household Type Filter:</span>
                        <span class="metadata-value">{{ implode(', ', $advancedFilters['household_type']) }}</span>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Demographics Section -->
    <div class="section">
        <div class="section-title">DEMOGRAPHICS</div>
        @foreach($reportData['demographics'] as $category => $data)
            <div class="category">
                <div class="category-title">{{ ucfirst(str_replace('_', ' ', $category)) }}</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Value</th>
                            <th class="count">Count</th>
                            <th class="percentage">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total = array_sum($data); @endphp
                        @foreach($data as $value => $count)
                            <tr>
                                <td>{{ $value }}</td>
                                <td class="count">{{ number_format($count) }}</td>
                                <td class="percentage">{{ $total > 0 ? number_format(($count / $total) * 100, 1) : '0' }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <!-- Characteristics Section -->
    <div class="section page-break">
        <div class="section-title">CHARACTERISTICS</div>
        @foreach($reportData['characteristics'] as $category => $data)
            <div class="category">
                <div class="category-title">{{ ucfirst(str_replace('_', ' ', $category)) }}</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Value</th>
                            <th class="count">Count</th>
                            <th class="percentage">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total = array_sum($data); @endphp
                        @foreach($data as $value => $count)
                            <tr>
                                <td>{{ $value }}</td>
                                <td class="count">{{ number_format($count) }}</td>
                                <td class="percentage">{{ $total > 0 ? number_format(($count / $total) * 100, 1) : '0' }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <!-- Employment & Income Section -->
    <div class="section page-break">
        <div class="section-title">EMPLOYMENT & INCOME</div>
        @foreach($reportData['employment_income'] as $category => $data)
            <div class="category">
                <div class="category-title">{{ ucfirst(str_replace('_', ' ', $category)) }}</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Value</th>
                            <th class="count">Count</th>
                            <th class="percentage">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total = array_sum($data); @endphp
                        @foreach($data as $value => $count)
                            <tr>
                                <td>{{ $value }}</td>
                                <td class="count">{{ number_format($count) }}</td>
                                <td class="percentage">{{ $total > 0 ? number_format(($count / $total) * 100, 1) : '0' }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <!-- Health & Housing Section -->
    <div class="section page-break">
        <div class="section-title">HEALTH & HOUSING</div>
        @foreach($reportData['health_housing'] as $category => $data)
            <div class="category">
                <div class="category-title">{{ ucfirst(str_replace('_', ' ', $category)) }}</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Value</th>
                            <th class="count">Count</th>
                            <th class="percentage">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total = array_sum($data); @endphp
                        @foreach($data as $value => $count)
                            <tr>
                                <td>{{ $value }}</td>
                                <td class="count">{{ number_format($count) }}</td>
                                <td class="percentage">{{ $total > 0 ? number_format(($count / $total) * 100, 1) : '0' }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <div class="footer">
        Generated by CAP TRACK System | {{ $generatedAt }}
    </div>
</body>
</html>