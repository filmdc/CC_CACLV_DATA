#!/bin/bash

# Laravel Site Refresh Script
# This script clears all caches and rebuilds assets for development

echo "🔄 Refreshing Laravel site..."
echo "================================"

# Clear Laravel caches
echo "📦 Clearing application cache..."
php artisan cache:clear

echo "⚙️  Clearing config cache..."
php artisan config:clear

echo "👁️  Clearing view cache..."
php artisan view:clear

echo "🛣️  Clearing route cache..."
php artisan route:clear

echo "🔧 Clearing compiled services..."
php artisan clear-compiled

# Rebuild assets
echo "🏗️  Building assets..."
npm run apache

# Fix permissions (if needed)
#echo "🔐 Setting storage permissions..."
#chmod -R 775 storage bootstrap/cache

# Reload Apache (optional - uncomment if you want to restart Apache)
echo "🔄 Reloading Apache..."
sudo systemctl reload apache2

echo "✅ Site refresh complete!"
echo "================================"
echo "Your site should now reflect all changes."