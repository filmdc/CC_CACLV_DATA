#!/bin/bash

# Laravel Permission Fix Script
# This script fixes common Laravel permission issues

echo "Fixing Laravel permissions..."

# Set the correct ownership for Laravel directories
echo "Setting ownership to www-data:www-data for storage and bootstrap/cache..."
sudo chown -R www-data:www-data /var/www/CC_CAP_TRACK/storage
sudo chown -R www-data:www-data /var/www/CC_CAP_TRACK/bootstrap/cache

# Set proper directory permissions (755 for directories, 644 for files)
echo "Setting directory permissions..."
sudo find /var/www/CC_CAP_TRACK/storage -type d -exec chmod 755 {} \;
sudo find /var/www/CC_CAP_TRACK/bootstrap/cache -type d -exec chmod 755 {} \;

# Set proper file permissions
echo "Setting file permissions..."
sudo find /var/www/CC_CAP_TRACK/storage -type f -exec chmod 644 {} \;
sudo find /var/www/CC_CAP_TRACK/bootstrap/cache -type f -exec chmod 644 {} \;

# Make sure Laravel can write to these directories
echo "Setting write permissions for Laravel..."
sudo chmod -R 775 /var/www/CC_CAP_TRACK/storage
sudo chmod -R 775 /var/www/CC_CAP_TRACK/bootstrap/cache

# Fix the main application permissions
echo "Setting application permissions..."
sudo chown -R www-data:www-data /var/www/CC_CAP_TRACK
sudo chmod -R 755 /var/www/CC_CAP_TRACK

# Make specific files executable
sudo chmod +x /var/www/CC_CAP_TRACK/artisan

echo "Permissions fixed!"
echo "You can now run this script with: sudo bash /var/www/CC_CAP_TRACK/fix_permissions.sh"