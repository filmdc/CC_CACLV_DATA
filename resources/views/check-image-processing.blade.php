<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Image Processing - {{ $filename }}</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            width: 100%;
        }
        .header {
            background: {{ isset($error) && $error ? '#dc2626' : '#2563eb' }};
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .header p {
            color: #dbeafe;
            font-size: 14px;
        }
        .message-box {
            background: #2a2a2a;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
            color: #e5e7eb;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .btn {
            background: #2563eb;
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
            font-size: 14px;
            font-weight: 500;
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
        .spinner {
            border: 4px solid #333;
            border-top: 4px solid #2563eb;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .info {
            background: #1e3a8a;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
            font-size: 14px;
            color: #dbeafe;
        }
        .progress-status {
            margin-top: 20px;
            padding: 15px;
            background: #374151;
            border-radius: 6px;
            font-size: 14px;
        }
        .check-count {
            font-weight: 600;
            color: #60a5fa;
            font-size: 18px;
        }
        .success-message {
            color: #10b981;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ isset($error) && $error ? 'Unable to Display Image' : 'Image Processing' }}</h1>
            <p>{{ $filename }}</p>
        </div>

        <div class="message-box">
            @if(!isset($error) || !$error)
                <div class="spinner"></div>
                <div class="icon">⏳</div>
            @else
                <div class="icon">⚠️</div>
            @endif

            <div class="message">
                {{ $message }}
            </div>

            @if($pageCount)
                <div class="info">
                    <strong>File Information:</strong><br>
                    This check image contains {{ $pageCount }} pages
                </div>
            @endif

            @if(!isset($error) || !$error)
                <div class="info">
                    <strong>What's happening?</strong><br>
                    Large check images need to be converted for viewing in your browser.
                    This process happens in the background and only needs to be done once.
                    After processing is complete, the image will load instantly.
                </div>
            @endif
        </div>

        <div class="actions">
            @if(!isset($error) || !$error)
                <button onclick="location.reload()" class="btn">Refresh Page</button>
            @endif
            <button onclick="window.close()" class="btn btn-secondary">Close</button>
        </div>

        @if(!isset($error) || !$error)
            <div class="progress-status" id="progressStatus">
                <div class="check-count" id="checkCount">Checking cache status...</div>
                <div style="margin-top: 8px; color: #9ca3af;" id="statusText">Initializing...</div>
            </div>
        @endif
    </div>

    @if(!isset($error) || !$error)
    <script>
        let checkCount = 0;
        let maxChecks = 60; // 60 checks * 5 seconds = 5 minutes max
        let checkInterval = 5000; // Check every 5 seconds

        function checkCacheStatus() {
            checkCount++;

            const statusEl = document.getElementById('statusText');
            const countEl = document.getElementById('checkCount');

            countEl.textContent = `Check ${checkCount} of ${maxChecks}`;
            statusEl.textContent = 'Checking if conversion is complete...';

            fetch('{{ route('donor-gifts.check-cache-status', ['filename' => $filename]) }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.ready) {
                    // Cache is ready! Show success and reload
                    statusEl.innerHTML = '<span class="success-message">✓ Conversion complete! Reloading...</span>';
                    countEl.textContent = 'Ready!';

                    // Stop the spinner
                    const spinner = document.querySelector('.spinner');
                    if (spinner) spinner.style.display = 'none';

                    // Reload after a brief delay to show the success message
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    // Not ready yet, continue checking
                    if (checkCount >= maxChecks) {
                        statusEl.textContent = 'Processing is taking longer than expected. Try refreshing manually.';
                        countEl.textContent = 'Max checks reached';
                    } else {
                        const nextCheck = Math.floor(checkInterval / 1000);
                        statusEl.textContent = `Not ready yet. Will check again in ${nextCheck} seconds...`;
                        setTimeout(checkCacheStatus, checkInterval);
                    }
                }
            })
            .catch(error => {
                console.error('Error checking cache status:', error);
                statusEl.textContent = 'Error checking status. Click "Refresh Page" to try again.';

                // Continue trying unless we've exceeded max checks
                if (checkCount < maxChecks) {
                    setTimeout(checkCacheStatus, checkInterval);
                }
            });
        }

        // Start checking after page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Wait 3 seconds before first check to give conversion a head start
            setTimeout(() => {
                checkCacheStatus();
            }, 3000);
        });
    </script>
    @endif
</body>
</html>
