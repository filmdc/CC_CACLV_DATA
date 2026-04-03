<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DonorPerfect Sync Guide</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 40px 50px;
            color: #1f2937;
            line-height: 1.6;
        }

        .header {
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24px;
            color: #4f46e5;
            margin: 0 0 5px 0;
        }

        .header .subtitle {
            font-size: 13px;
            color: #6b7280;
        }

        h2 {
            font-size: 16px;
            color: #1f2937;
            border-left: 4px solid #4f46e5;
            padding-left: 12px;
            margin-top: 28px;
            margin-bottom: 12px;
        }

        h3 {
            font-size: 13px;
            color: #374151;
            margin-top: 18px;
            margin-bottom: 8px;
        }

        p, li {
            font-size: 12px;
            color: #374151;
        }

        ul {
            padding-left: 20px;
            margin: 8px 0;
        }

        li {
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 20px 0;
        }

        th {
            background-color: #4f46e5;
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }

        td {
            padding: 9px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background-color: #f3f4f6;
        }

        .callout {
            background-color: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 6px;
            padding: 14px 16px;
            margin: 16px 0;
        }

        .callout-title {
            font-weight: bold;
            color: #4338ca;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .callout p {
            margin: 0;
            font-size: 11px;
            color: #4338ca;
        }

        .tip-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 14px 16px;
            margin: 16px 0;
        }

        .tip-box .tip-title {
            font-weight: bold;
            color: #166534;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .tip-box li {
            font-size: 11px;
            color: #166534;
        }

        .badge {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        .badge-green {
            background-color: #dcfce7;
            color: #166534;
        }

        .footer {
            margin-top: 40px;
            padding-top: 12px;
            border-top: 2px solid #e5e7eb;
            font-size: 10px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>DonorPerfect Sync Guide</h1>
        <div class="subtitle">Quick Reference for Accounting &mdash; CAP TRACK</div>
    </div>

    <h2>How the Gift Feed Works</h2>
    <p>
        The DonorPerfect Gifts Feed pulls gift data directly from the DonorPerfect API.
        To keep page interactions fast (filtering, sorting, paging), data is briefly cached
        for <strong>5 minutes</strong> after each fetch. After 5 minutes the next page load
        automatically pulls fresh data from DonorPerfect.
    </p>

    <h2>Getting Fresh Data</h2>

    <h3>Automatic Refresh</h3>
    <ul>
        <li>Data refreshes automatically every <strong>5 minutes</strong>.</li>
        <li>Changing the <strong>start date</strong> or <strong>end date</strong> immediately fetches fresh data for the new range.</li>
    </ul>

    <h3>Manual Refresh</h3>
    <p>
        Click the <span class="badge">Refresh Data</span> button (top-right of the Gifts tab) at any time
        to force an immediate pull from DonorPerfect. Use this when:
    </p>
    <ul>
        <li>You just entered or edited gifts in DonorPerfect and want to see them right away.</li>
        <li>A batch was just posted and isn't showing yet.</li>
        <li>You need to confirm that a code change in DonorPerfect took effect.</li>
    </ul>

    <h2>When Changes Show Up</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 55%;">What Changed</th>
                <th style="width: 45%;">When It Appears</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>New gift entered in DonorPerfect</td>
                <td>Within 5 min, or click <span class="badge">Refresh Data</span></td>
            </tr>
            <tr>
                <td>Gift amount or code edited in DonorPerfect</td>
                <td>Within 5 min, or click <span class="badge">Refresh Data</span></td>
            </tr>
            <tr>
                <td>New batch posted in DonorPerfect</td>
                <td>Within 5 min, or click <span class="badge">Refresh Data</span></td>
            </tr>
            <tr>
                <td>Code mapping updated (Intacct codes tab)</td>
                <td><span class="badge-green badge">Immediately</span> &mdash; no refresh needed</td>
            </tr>
            <tr>
                <td>Conditional mapping updated</td>
                <td><span class="badge-green badge">Immediately</span> &mdash; no refresh needed</td>
            </tr>
            <tr>
                <td>Date range changed</td>
                <td><span class="badge-green badge">Immediately</span> &mdash; always fetches fresh data</td>
            </tr>
        </tbody>
    </table>

    <h2>Manual Batch Sync Page</h2>
    <p>
        The Manual Batch Sync page always requests fresh data from DonorPerfect when you
        select or change a date, or click <strong>Load Preview</strong>.
        No extra refresh step is needed on this page.
    </p>

    <h2>Troubleshooting Tips</h2>

    <div class="tip-box">
        <div class="tip-title">Common Solutions</div>
        <ul>
            <li><strong>Don't see a batch?</strong> Click "Refresh Data" first. If it still doesn't appear, verify the gift date in DonorPerfect matches the date range selected in the feed.</li>
            <li><strong>Code mapping not reflected?</strong> Intacct code mappings update instantly when saved. If the underlying DP code was changed in DonorPerfect, click "Refresh Data" to pull the updated code.</li>
            <li><strong>Filters hiding data?</strong> Check the filter dropdowns above the table. Click "Clear All Filters" to reset everything.</li>
            <li><strong>Need a different date range?</strong> Change the start/end dates &mdash; fresh data loads automatically.</li>
        </ul>
    </div>

    <div class="callout">
        <div class="callout-title">Need Help?</div>
        <p>If the feed is not returning data at all or shows an error message, contact your system administrator. Common issues include an expired DonorPerfect API key or a network connectivity problem.</p>
    </div>

    <div class="footer">
        CAP TRACK &mdash; DonorPerfect Sync Guide &mdash; Generated {{ now()->format('F j, Y') }}
    </div>

</body>
</html>
