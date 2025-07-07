<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Invitation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Report Due</h1>

		@php
            $dueDateTime = new DateTime($dueDate->due_date);
        @endphp
        
        <h1><strong>Title:</strong> {{ $dueDate->contract->title }}</h1>
        <p><strong>Date:</strong> {{ $dueDateTime->format('l, F j, Y') }}</p>
        <p><strong>Program:</strong> {{ $dueDate->contract->program }}</p>
        <p><strong>Purpose:</strong> {{ $dueDate->contract->purpose }}</p>
        <p><strong>Notes:</strong> {{ $dueDate->contract->notes }}</p>
        
        <p>This is an all-day event.</p>

        <p>Best regards,</p>
        <p><em>Your Organization's Name</em></p>
    </div>
</body>
</html>
