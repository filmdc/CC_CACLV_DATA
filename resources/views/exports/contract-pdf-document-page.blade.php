<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
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

        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 13px; margin-top: 4px; margin-bottom: 12px; color: #2563eb; letter-spacing: 0.05em; text-transform: uppercase; }
        h3 { font-size: 13px; margin-bottom: 6px; }

        .meta {
            font-size: 10.5px;
            color: #64748b;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
        }

        .detail-table th {
            width: 30%;
            background-color: #eff6ff;
            color: #1e40af;
            font-weight: 600;
        }

        .content {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background-color: #f8fafc;
        }

        .content p {
            margin: 0 0 8px;
        }

        .content pre {
            font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
            font-size: 11px;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            padding: 8px;
            overflow-wrap: anywhere;
        }

        .content ul,
        .content ol {
            margin: 8px 0 8px 18px;
        }
    </style>
</head>
<body>
    <header>
        <h1>{{ $title }}</h1>
        @if($subtitle)
            <h2>{{ $subtitle }}</h2>
        @endif
        <div class="meta">
            Generated {{ $generatedAt->format('M j, Y g:i A') }}
        </div>
    </header>

    @if(!empty($details))
        <table class="detail-table">
            <tbody>
                @foreach($details as $label => $value)
                    <tr>
                        <th>{{ $label }}</th>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="content">
        {!! $bodyHtml !!}
    </div>
</body>
</html>
