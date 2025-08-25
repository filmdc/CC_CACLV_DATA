#!/bin/bash

# Email System Verification Script
# Run this to verify the email system is properly configured

APP_PATH="/var/www/CC_CAP_TRACK"
cd "$APP_PATH"

echo "Email System Verification"
echo "========================"
echo ""

# Check timezone
echo "1. Timezone Configuration:"
echo -n "   Config timezone: "
php artisan tinker --execute="echo config('app.timezone');"
echo -n "   Current time: "
php artisan tinker --execute="echo now()->format('Y-m-d H:i:s T');"
echo ""

# Check cron job
echo "2. Cron Job Status:"
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo "   ✓ Cron job is configured"
    echo "   Cron entry:"
    crontab -l | grep "schedule:run"
else
    echo "   ✗ No cron job found!"
    echo "   Run: bash $APP_PATH/quick-production-setup.sh"
fi
echo ""

# Check scheduled tasks
echo "3. Scheduled Tasks:"
php artisan schedule:list
echo ""

# Check email configuration
echo "4. Email Configuration:"
echo -n "   Mail driver: "
php artisan tinker --execute="echo config('mail.default');"
echo -n "   From address: "
php artisan tinker --execute="echo config('mail.from.address');"
echo ""

# Check for scheduled test reminders
echo "5. Pending Scheduled Tests:"
php artisan tinker --execute="
    \$tests = json_decode(\App\Models\SiteSetting::get('scheduled_test_reminders', '[]'), true);
    echo 'Total scheduled tests: ' . count(\$tests) . PHP_EOL;
    foreach(\$tests as \$test) {
        echo '   - Contract ' . \$test['contract_id'] . ' scheduled for ' . \$test['scheduled_at'] . PHP_EOL;
    }
"
echo ""

# Recent email activity
echo "6. Recent Email Activity (last 10 entries):"
tail -n 100 storage/logs/laravel.log | grep -i "reminder\|email\|mail" | tail -n 10 || echo "   No recent email activity found"
echo ""

# Test commands
echo "7. Testing Commands:"
echo -n "   contract:process-scheduled-tests... "
if php artisan contract:process-scheduled-tests > /dev/null 2>&1; then
    echo "✓ OK"
else
    echo "✗ Failed"
fi

echo ""
echo "Verification complete!"
echo ""
echo "To manually test:"
echo "- Send a test email: Visit /admin/contract-notification-test"
echo "- Check logs: tail -f storage/logs/laravel.log"
echo "- Run scheduler manually: php artisan schedule:run"