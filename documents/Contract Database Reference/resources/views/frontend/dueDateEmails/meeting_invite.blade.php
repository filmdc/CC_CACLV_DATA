@php
    // Format the dueDate as an all-day event date
	\Log::info('Due Date: ' . $dueDate->date);
    $eventDate = \Carbon\Carbon::createFromFormat('Y-m-d', $dueDate->date)->format('Ymd');
@endphp

BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Your Company//Your Product//EN
BEGIN:VEVENT
UID:scheduling@communityactionlv.org
DTSTAMP:{{ now()->format('Ymd\THis\Z') }}
DTSTART;VALUE=DATE:{{ $eventDate }}
DTEND;VALUE=DATE:{{ $eventDate }}
SUMMARY:{{ $contract->title }}
DESCRIPTION:{{ $contract->description }}
END:VEVENT
END:VCALENDAR