<?php
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "Testing Email Configuration\n";
echo "==========================\n\n";

// Display current configuration
echo "Current Mail Configuration:\n";
echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "MAIL_USERNAME: " . (config('mail.mailers.smtp.username') ? 'SET' : 'NOT SET') . "\n";
echo "MAIL_PASSWORD: " . (config('mail.mailers.smtp.password') ? 'SET' : 'NOT SET') . "\n";
echo "MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption') . "\n";
echo "MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . config('mail.from.name') . "\n\n";

// Test SMTP connection
echo "Testing SMTP Connection...\n";
try {
    $host = config('mail.mailers.smtp.host');
    $port = config('mail.mailers.smtp.port');
    $encryption = config('mail.mailers.smtp.encryption');
    
    // For STARTTLS, connect without SSL first
    if ($encryption === 'tls') {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        $connection = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
    } else {
        $connection = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            30
        );
    }
    
    if ($connection) {
        fclose($connection);
        echo "✓ SMTP connection test successful!\n\n";
    } else {
        echo "✗ SMTP connection test failed: $errstr ($errno)\n\n";
    }
} catch (\Exception $e) {
    echo "✗ SMTP connection test failed: " . $e->getMessage() . "\n\n";
}

// Prompt for test email
echo "Enter email address to send test to (or press Enter to skip): ";
$handle = fopen("php://stdin", "r");
$testEmail = trim(fgets($handle));

if ($testEmail && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo "\nSending test email to: $testEmail\n";
    
    try {
        Mail::raw('This is a test email from CAP TRACK to verify email configuration is working correctly.', function ($message) use ($testEmail) {
            $message->to($testEmail)
                    ->subject('CAP TRACK Email Test');
        });
        
        echo "✓ Test email sent successfully!\n";
    } catch (\Exception $e) {
        echo "✗ Failed to send test email: " . $e->getMessage() . "\n";
        echo "\nFull error:\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "Skipping email send test.\n";
}

echo "\nEmail testing complete.\n";