<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DonorPerfect Gifts Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 20px;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #1f2937;
        }

        .export-info {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #4f46e5;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }

        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
        }

        .summary {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #f3f4f6;
            border-left: 4px solid #4f46e5;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <h1>DonorPerfect Gifts Export</h1>

    <div class="export-info">
        Generated on {{ $export_date }} at {{ $export_time }}
    </div>

    <div class="summary">
        <strong>Total Gifts:</strong> {{ $total_count }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($gifts as $gift)
                <tr>
                    @foreach($headers as $header)
                        <td>{{ $gift[$header] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        DonorPerfect Gifts Export - Community Action Partnership
    </div>
</body>
</html>
