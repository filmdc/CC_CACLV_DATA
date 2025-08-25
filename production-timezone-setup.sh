#!/bin/bash

# Production Server Timezone and Cron Setup Script
# This script configures the system timezone and sets up Laravel scheduler cron job

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_PATH="/var/www/CC_CAP_TRACK"
TIMEZONE="America/New_York"
PHP_USER=$(whoami)

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Production Timezone & Cron Setup Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Function to check if running as appropriate user
check_user() {
    echo -e "${YELLOW}Running as user: $PHP_USER${NC}"
    echo ""
}

# Function to update .env file
update_env_file() {
    echo -e "${YELLOW}Updating .env file...${NC}"
    
    if [ -f "$APP_PATH/.env" ]; then
        # Check if APP_TIMEZONE exists
        if grep -q "^APP_TIMEZONE=" "$APP_PATH/.env"; then
            # Update existing
            sed -i "s/^APP_TIMEZONE=.*/APP_TIMEZONE=$TIMEZONE/" "$APP_PATH/.env"
            echo -e "${GREEN}✓ Updated APP_TIMEZONE in .env${NC}"
        else
            # Add after APP_URL
            sed -i "/^APP_URL=/a APP_TIMEZONE=$TIMEZONE" "$APP_PATH/.env"
            echo -e "${GREEN}✓ Added APP_TIMEZONE to .env${NC}"
        fi
    else
        echo -e "${RED}✗ .env file not found!${NC}"
        exit 1
    fi
}

# Function to clear Laravel caches
clear_caches() {
    echo -e "${YELLOW}Clearing Laravel caches...${NC}"
    
    cd "$APP_PATH"
    
    php artisan config:clear
    echo -e "${GREEN}✓ Configuration cache cleared${NC}"
    
    php artisan cache:clear
    echo -e "${GREEN}✓ Application cache cleared${NC}"
    
    php artisan view:clear
    echo -e "${GREEN}✓ View cache cleared${NC}"
    
    # Rebuild config cache for production
    php artisan config:cache
    echo -e "${GREEN}✓ Configuration cached for production${NC}"
}

# Function to set up cron job
setup_cron() {
    echo -e "${YELLOW}Setting up Laravel scheduler cron job...${NC}"
    
    # Check if cron job already exists
    if crontab -l 2>/dev/null | grep -q "schedule:run.*$APP_PATH"; then
        echo -e "${GREEN}✓ Laravel scheduler cron job already exists${NC}"
    else
        # Add the cron job
        (crontab -l 2>/dev/null; echo "* * * * * cd $APP_PATH && /usr/bin/php artisan schedule:run >> /dev/null 2>&1") | crontab -
        echo -e "${GREEN}✓ Laravel scheduler cron job added${NC}"
    fi
    
    echo ""
    echo "Current crontab for $PHP_USER:"
    crontab -l | grep -E "(schedule:run|$APP_PATH)" || echo "No Laravel cron jobs found"
}

# Function to test timezone configuration
test_timezone() {
    echo -e "${YELLOW}Testing timezone configuration...${NC}"
    
    cd "$APP_PATH"
    
    # Get current time from PHP
    CURRENT_TIME=$(php artisan tinker --execute="echo now()->format('Y-m-d H:i:s T');")
    CONFIG_TZ=$(php artisan tinker --execute="echo config('app.timezone');")
    
    echo -e "Configured timezone: ${GREEN}$CONFIG_TZ${NC}"
    echo -e "Current time: ${GREEN}$CURRENT_TIME${NC}"
}

# Function to test email configuration
test_email() {
    echo -e "${YELLOW}Checking email configuration...${NC}"
    
    cd "$APP_PATH"
    
    # Check mail configuration
    MAIL_MAILER=$(php artisan tinker --execute="echo config('mail.default');")
    echo -e "Mail driver: ${GREEN}$MAIL_MAILER${NC}"
    
    # Check if mail is properly configured
    if [ "$MAIL_MAILER" == "log" ]; then
        echo -e "${YELLOW}⚠ Mail is set to 'log' driver - emails will be written to log files${NC}"
    elif [ "$MAIL_MAILER" == "smtp" ]; then
        echo -e "${GREEN}✓ SMTP mail driver configured${NC}"
    fi
}

# Function to verify scheduler tasks
verify_scheduler() {
    echo -e "${YELLOW}Verifying scheduled tasks...${NC}"
    
    cd "$APP_PATH"
    
    echo "Scheduled tasks:"
    php artisan schedule:list
}

# Function to run a test of the scheduled commands
test_scheduled_commands() {
    echo -e "${YELLOW}Testing scheduled commands...${NC}"
    
    cd "$APP_PATH"
    
    # Test if commands exist and can run
    echo -n "Testing contract:send-reminders command... "
    if php artisan contract:send-reminders --dry-run > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${YELLOW}⚠ Command may not support --dry-run flag${NC}"
    fi
    
    echo -n "Testing contract:process-scheduled-tests command... "
    if php artisan contract:process-scheduled-tests > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${RED}✗ Command failed${NC}"
    fi
}

# Main execution
main() {
    check_user
    
    # Backup current .env
    echo -e "${YELLOW}Creating backup of .env file...${NC}"
    cp "$APP_PATH/.env" "$APP_PATH/.env.backup.$(date +%Y%m%d_%H%M%S)"
    echo -e "${GREEN}✓ Backup created${NC}"
    echo ""
    
    update_env_file
    echo ""
    
    clear_caches
    echo ""
    
    setup_cron
    echo ""
    
    test_timezone
    echo ""
    
    test_email
    echo ""
    
    verify_scheduler
    echo ""
    
    test_scheduled_commands
    echo ""
    
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Setup completed successfully!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Verify the timezone is correct for your location"
    echo "2. Test the email system using the Contract Notification Test Dashboard"
    echo "3. Monitor the Laravel log file for scheduler activity:"
    echo "   tail -f $APP_PATH/storage/logs/laravel.log"
    echo ""
    echo "To manually run scheduled commands:"
    echo "   php artisan contract:send-reminders"
    echo "   php artisan contract:process-scheduled-tests"
}

# Run main function
main

# Create a systemd service file (optional - for systemd-based systems)
if command -v systemctl &> /dev/null; then
    echo ""
    echo -e "${YELLOW}Optional: Creating systemd service file template...${NC}"
    
    cat > "$APP_PATH/laravel-scheduler.service.template" << 'EOF'
[Unit]
Description=Laravel Scheduler
After=network.target

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/var/www/CC_CAP_TRACK
ExecStart=/usr/bin/php /var/www/CC_CAP_TRACK/artisan schedule:run
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    cat > "$APP_PATH/laravel-scheduler.timer.template" << 'EOF'
[Unit]
Description=Run Laravel Scheduler every minute
Requires=laravel-scheduler.service

[Timer]
OnCalendar=*-*-* *:*:00
Persistent=true

[Install]
WantedBy=timers.target
EOF

    echo -e "${GREEN}✓ Systemd service templates created${NC}"
    echo ""
    echo "To use systemd instead of cron (optional):"
    echo "1. Copy the service files:"
    echo "   sudo cp $APP_PATH/laravel-scheduler.service.template /etc/systemd/system/laravel-scheduler.service"
    echo "   sudo cp $APP_PATH/laravel-scheduler.timer.template /etc/systemd/system/laravel-scheduler.timer"
    echo "2. Update the User and WorkingDirectory in the service file"
    echo "3. Enable and start the timer:"
    echo "   sudo systemctl enable laravel-scheduler.timer"
    echo "   sudo systemctl start laravel-scheduler.timer"
fi