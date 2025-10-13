# Laravel Apache Setup Guide

This guide will help you set up your Laravel project to run under Apache without needing `php artisan serve`.

## Prerequisites
- Apache2 installed
- PHP 8.3+ installed
- MySQL/MariaDB installed (if using database)
- Node.js and npm installed

## Step-by-Step Setup

### 1. Install Required PHP Extensions

```bash
sudo apt-get update
sudo apt-get install -y php8.3-dom php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-intl libapache2-mod-php8.3
```

### 2. Install Composer (if not already installed)

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Install Project Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Build frontend assets
npm run build
```

### 4. Configure Environment

```bash
# Copy environment file if it doesn't exist
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with your database credentials
nano .env
```

Update these values in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=CC_CACLV_DATA
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password
```

### 5. Set File Permissions

```bash
# Give web server write access to storage and cache
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Configure Apache Virtual Host

Create a new Apache configuration file:

```bash
sudo nano /etc/apache2/sites-available/laravel-app.conf
```

Add this configuration (replace `/var/www/CC_CAP_TRACK` with your actual project path):

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/CC_CAP_TRACK/public
    
    <Directory /var/www/CC_CAP_TRACK/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/laravel-error.log
    CustomLog ${APACHE_LOG_DIR}/laravel-access.log combined
</VirtualHost>
```

### 7. Enable Apache Modules and Site

```bash
# Enable mod_rewrite (required for Laravel routing)
sudo a2enmod rewrite
sudo a2enmod headers

# Disable default site
sudo a2dissite 000-default

# Enable your Laravel site
sudo a2ensite laravel-app

# Restart Apache
sudo systemctl restart apache2
```

### 8. Run Database Migrations

```bash
# Create database tables
php artisan migrate

# Seed initial data (optional)
php artisan db:seed
```

### 9. Optimize for Production (Optional)

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

## Troubleshooting

### Permission Issues
If you see permission errors, run:
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### 500 Internal Server Error
Check Apache error logs:
```bash
sudo tail -f /var/log/apache2/laravel-error.log
```

### .htaccess Not Working
Ensure `AllowOverride All` is set in your Apache configuration and mod_rewrite is enabled.

### Database Connection Issues
- Verify MySQL is running: `sudo systemctl status mysql`
- Check credentials in `.env` file
- Create database if it doesn't exist: `mysql -u root -p -e "CREATE DATABASE CC_CACLV_DATA"`

## Accessing Your Application

After completing these steps, your Laravel application should be accessible at:
- http://localhost (if using default port 80)
- http://your-server-ip (from remote machines)

## Security Notes for Production

1. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
2. Use HTTPS (configure SSL certificate)
3. Configure firewall rules
4. Keep PHP and packages updated
5. Set up proper backup procedures

## Quick Commands Reference

```bash
# Clear all caches
php artisan optimize:clear

# View application logs
tail -f storage/logs/laravel.log

# Check Apache status
sudo systemctl status apache2

# Restart Apache
sudo systemctl restart apache2
```