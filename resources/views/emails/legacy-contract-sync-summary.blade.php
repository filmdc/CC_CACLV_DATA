@php
    /** @var \App\Models\LegacyContractSyncRun $run */
    $rows = [
        'Contracts imported' => $run->created_count,
        'Contracts updated' => $run->updated_count,
        'Due dates created' => $run->due_dates_created,
        'Due dates updated' => $run->due_dates_updated,
        'Attachments downloaded' => $run->media_synced,
        'Funders created' => $run->funders_created,
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Legacy Contract Sync Summary</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#18181b;">
<div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:8px;padding:24px;">

    <h1 style="margin:0 0 4px;font-size:18px;">Legacy Contract Sync</h1>
    <p style="margin:0 0 20px;color:#52525b;font-size:14px;">
        {{ $run->started_at?->format('D, M j Y \a\t g:i A') }}
        &middot; {{ ucfirst($run->triggered_by) }} run
        @if($run->duration_ms)
            &middot; {{ number_format($run->duration_ms / 1000, 1) }}s
        @endif
    </p>

    @php
        $banner = match ($run->status) {
            'success' => ['#f0fdf4', '#166534'],
            'partial' => ['#fefce8', '#854d0e'],
            'failed'  => ['#fef2f2', '#991b1b'],
            default   => ['#f4f4f5', '#3f3f46'],
        };
    @endphp
    <div style="background:{{ $banner[0] }};color:{{ $banner[1] }};padding:12px 16px;border-radius:6px;font-size:14px;font-weight:600;margin-bottom:20px;">
        {{ strtoupper($run->status) }} &mdash; {{ $run->summaryLine() }}
    </div>

    @if($run->error_message)
        <p style="margin:0 0 20px;padding:12px 16px;background:#fef2f2;color:#991b1b;border-radius:6px;font-size:13px;">
            {{ $run->error_message }}
        </p>
    @endif

    <h2 style="font-size:14px;margin:0 0 8px;color:#3f3f46;">What the legacy system looked like</h2>
    <table role="presentation" width="100%" style="border-collapse:collapse;font-size:14px;margin-bottom:24px;">
        <tr>
            <td style="padding:6px 0;border-bottom:1px solid #e4e4e7;">New in legacy</td>
            <td align="right" style="padding:6px 0;border-bottom:1px solid #e4e4e7;font-weight:600;">{{ $run->detected_new }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0;border-bottom:1px solid #e4e4e7;">Changed since last sync</td>
            <td align="right" style="padding:6px 0;border-bottom:1px solid #e4e4e7;font-weight:600;">{{ $run->detected_updated }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0;border-bottom:1px solid #e4e4e7;">Deleted in legacy, still here</td>
            <td align="right" style="padding:6px 0;border-bottom:1px solid #e4e4e7;font-weight:600;">{{ $run->detected_deleted }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0;">Unchanged</td>
            <td align="right" style="padding:6px 0;font-weight:600;">{{ $run->unchanged_count }}</td>
        </tr>
    </table>

    @if($run->madeChanges() || $run->media_synced)
        <h2 style="font-size:14px;margin:0 0 8px;color:#3f3f46;">What changed in CAP TRACK</h2>
        <table role="presentation" width="100%" style="border-collapse:collapse;font-size:14px;margin-bottom:24px;">
            @foreach($rows as $label => $value)
                @if($value)
                    <tr>
                        <td style="padding:6px 0;border-bottom:1px solid #e4e4e7;">{{ $label }}</td>
                        <td align="right" style="padding:6px 0;border-bottom:1px solid #e4e4e7;font-weight:600;">{{ $value }}</td>
                    </tr>
                @endif
            @endforeach
        </table>
    @endif

    @if($run->deferred_count)
        <p style="margin:0 0 20px;padding:12px 16px;background:#fefce8;color:#854d0e;border-radius:6px;font-size:13px;">
            {{ $run->deferred_count }} contract(s) were deferred to the next run because the drift exceeded the
            per-run safety limit.
        </p>
    @endif

    @if(!empty($run->deleted_legacy_ids))
        <h2 style="font-size:14px;margin:0 0 8px;color:#3f3f46;">Deleted in legacy &mdash; needs a decision</h2>
        <p style="margin:0 0 8px;font-size:13px;color:#52525b;">
            These contracts were removed from the legacy system but still exist in CAP TRACK. Nothing was changed
            automatically. Legacy IDs:
        </p>
        <p style="margin:0 0 24px;font-size:13px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:#3f3f46;">
            {{ implode(', ', array_slice($run->deleted_legacy_ids, 0, 60)) }}@if(count($run->deleted_legacy_ids) > 60) &hellip; (+{{ count($run->deleted_legacy_ids) - 60 }} more)@endif
        </p>
    @endif

    @if(!empty($run->errors))
        <h2 style="font-size:14px;margin:0 0 8px;color:#991b1b;">Errors</h2>
        <ul style="margin:0 0 24px;padding-left:20px;font-size:13px;color:#991b1b;">
            @foreach(array_slice($run->errors, 0, 25) as $error)
                <li style="margin-bottom:4px;">{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($run->warnings))
        <h2 style="font-size:14px;margin:0 0 8px;color:#854d0e;">Warnings</h2>
        <ul style="margin:0 0 24px;padding-left:20px;font-size:13px;color:#854d0e;">
            @foreach(array_slice($run->warnings, 0, 25) as $warning)
                <li style="margin-bottom:4px;">{{ $warning }}</li>
            @endforeach
            @if(count($run->warnings) > 25)
                <li>&hellip; and {{ count($run->warnings) - 25 }} more</li>
            @endif
        </ul>
    @endif

    <p style="margin:24px 0 0;padding-top:16px;border-top:1px solid #e4e4e7;font-size:12px;color:#71717a;">
        Sent by CAP TRACK &middot; run #{{ $run->id }}. Full history is on the Legacy Contract Import screen.
    </p>
</div>
</body>
</html>
