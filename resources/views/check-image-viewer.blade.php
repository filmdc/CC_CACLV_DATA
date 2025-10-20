<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Image - {{ $filename }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a1a;
            color: #fff;
            padding: 20px;
        }
        .header {
            background: #2563eb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            color: #dbeafe;
            font-size: 14px;
        }
        .image-container {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .page-label {
            background: #2563eb;
            color: #fff;
            padding: 8px 16px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 600;
        }
        .check-image {
            max-width: 100%;
            height: auto;
            display: block;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .actions {
            margin-top: 20px;
            text-align: center;
        }
        .btn {
            background: #2563eb;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
            font-size: 14px;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .btn-secondary {
            background: #6b7280;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Check Image Viewer</h1>
        <p>{{ $filename }} • {{ $pageCount }} {{ $pageCount > 1 ? 'pages' : 'page' }}</p>
    </div>

    @foreach($pages as $index => $page)
    <div class="image-container">
        <div class="page-label">
            @if($pageCount === 2)
                {{ $index === 0 ? 'Front of Check' : 'Back of Check' }}
            @else
                Page {{ $index + 1 }} of {{ $pageCount }}
            @endif
        </div>
        <img src="{{ $page['url'] }}" alt="Check page {{ $index + 1 }}" class="check-image">
    </div>
    @endforeach

    <div class="actions">
        <button onclick="window.print()" class="btn">Print</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>
</body>
</html>
