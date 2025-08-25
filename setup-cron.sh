#!/bin/bash

# Laravel Scheduler Cron Setup Script

echo "Setting up Laravel scheduler cron job..."

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo "Laravel scheduler cron job already exists."
else
    # Add the cron job
    (crontab -l 2>/dev/null; echo "* * * * * cd /var/www/CC_CAP_TRACK && php artisan schedule:run >> /dev/null 2>&1") | crontab -
    echo "Laravel scheduler cron job added successfully."
fi

# Display current crontab
echo ""
echo "Current crontab:"
crontab -l

echo ""
echo "To manually edit cron jobs, run: crontab -e"
echo "To remove all cron jobs, run: crontab -r"