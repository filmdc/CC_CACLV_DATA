#!/bin/bash

# Quick Production Setup Script for Timezone and Cron
# Run this with: bash quick-production-setup.sh

APP_PATH="/var/www/CC_CAP_TRACK"
cd "$APP_PATH"

echo "Quick Production Setup for CC_CAP_TRACK"
echo "======================================"
echo ""

# 1. Update .env file
echo "1. Updating timezone in .env..."
if grep -q "^APP_TIMEZONE=" .env; then
    sed -i "s/^APP_TIMEZONE=.*/APP_TIMEZONE=America\/New_York/" .env
else
    sed -i "/^APP_URL=/a APP_TIMEZONE=America/New_York" .env
fi
echo "   ✓ Done"

# 2. Clear and cache config
echo "2. Refreshing Laravel configuration..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache
echo "   ✓ Done"

# 3. Set up cron job
echo "3. Setting up cron job..."
if ! crontab -l 2>/dev/null | grep -q "schedule:run.*$APP_PATH"; then
    (crontab -l 2>/dev/null; echo "* * * * * cd $APP_PATH && /usr/bin/php artisan schedule:run >> /dev/null 2>&1") | crontab -
    echo "   ✓ Cron job added"
else
    echo "   ✓ Cron job already exists"
fi

# 4. Show current configuration
echo ""
echo "Current Configuration:"
echo "====================="
php artisan tinker --execute="echo 'Timezone: ' . config('app.timezone');"
php artisan tinker --execute="echo 'Current time: ' . now()->format('Y-m-d H:i:s T');"
echo ""
echo "Scheduled Tasks:"
php artisan schedule:list
echo ""
echo "Setup complete! The email scheduler is now active."