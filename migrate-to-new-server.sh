#!/bin/bash

# Laravel Project Migration Script for Ubuntu
# This script sets up a new Ubuntu server with Apache, MySQL, PHP and transfers
# the Laravel project from the source server (10.55.0.224)

set -e  # Exit on error

# Configuration
SOURCE_SERVER="10.55.0.224"
SOURCE_PROJECT_PATH="/var/www/CC_CAP_TRACK"
SOURCE_DB_NAME="CC_CACLV_DATA"
TARGET_PROJECT_PATH="/var/www/CC_CAP_TRACK"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root"
   exit 1
fi

# Get SSH credentials for source server
echo "==============================================="
echo "Laravel Project Migration Script"
echo "==============================================="
echo ""
read -p "Enter SSH username for source server ($SOURCE_SERVER): " SSH_USER
read -p "Enter MySQL root password for NEW server (will be set during installation): " NEW_MYSQL_ROOT_PASS
read -sp "Enter MySQL root password for SOURCE server ($SOURCE_SERVER): " SOURCE_MYSQL_ROOT_PASS
echo ""

# Test SSH connection
print_status "Testing SSH connection to source server..."
if ! ssh -o ConnectTimeout=5 ${SSH_USER}@${SOURCE_SERVER} "echo 'SSH connection successful'"; then
    print_error "Cannot connect to source server via SSH"
    exit 1
fi

# Update system
print_status "Updating system packages..."
apt update && apt upgrade -y

# Install Apache
print_status "Installing Apache..."
apt install -y apache2
systemctl enable apache2
systemctl start apache2

# Enable Apache modules
print_status "Enabling Apache modules..."
a2enmod rewrite headers expires

# Install MySQL
print_status "Installing MySQL..."
export DEBIAN_FRONTEND=noninteractive
echo "mysql-server mysql-server/root_password password $NEW_MYSQL_ROOT_PASS" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again password $NEW_MYSQL_ROOT_PASS" | debconf-set-selections
apt install -y mysql-server

# Secure MySQL installation
print_status "Securing MySQL installation..."
mysql --user=root --password="$NEW_MYSQL_ROOT_PASS" <<EOF
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF

# Install PHP and required extensions
print_status "Installing PHP and extensions..."
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update

# Install PHP 8.2 and extensions
apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring \
    php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl php8.2-redis \
    php8.2-sqlite3 libapache2-mod-php8.2

# Install additional dependencies
print_status "Installing additional dependencies..."
apt install -y git curl wget unzip rsync

# Install Composer
print_status "Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js and npm
print_status "Installing Node.js and npm..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Create project directory
print_status "Creating project directory..."
mkdir -p $TARGET_PROJECT_PATH
chown -R www-data:www-data /var/www

# Transfer project files
print_status "Transferring project files from source server..."
rsync -avz --progress ${SSH_USER}@${SOURCE_SERVER}:${SOURCE_PROJECT_PATH}/ ${TARGET_PROJECT_PATH}/

# Create database
print_status "Creating database..."
mysql --user=root --password="$NEW_MYSQL_ROOT_PASS" -e "CREATE DATABASE IF NOT EXISTS ${SOURCE_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Dump and transfer database
print_status "Dumping database from source server..."
ssh ${SSH_USER}@${SOURCE_SERVER} "mysqldump -u root -p${SOURCE_MYSQL_ROOT_PASS} ${SOURCE_DB_NAME}" > /tmp/database_dump.sql

print_status "Importing database..."
mysql --user=root --password="$NEW_MYSQL_ROOT_PASS" ${SOURCE_DB_NAME} < /tmp/database_dump.sql
rm /tmp/database_dump.sql

# Configure Apache
print_status "Configuring Apache..."
cat > /etc/apache2/sites-available/laravel.conf <<EOF
<VirtualHost *:80>
    DocumentRoot ${TARGET_PROJECT_PATH}
    
    <Directory ${TARGET_PROJECT_PATH}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/laravel-error.log
    CustomLog \${APACHE_LOG_DIR}/laravel-access.log combined
</VirtualHost>
EOF

# Disable default site and enable Laravel site
a2dissite 000-default.conf
a2ensite laravel.conf

# Set proper permissions
print_status "Setting file permissions..."
cd ${TARGET_PROJECT_PATH}
chown -R www-data:www-data .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache

# Update .env file with new database credentials
print_status "Updating .env file..."
if [ -f "${TARGET_PROJECT_PATH}/.env" ]; then
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${NEW_MYSQL_ROOT_PASS}/" ${TARGET_PROJECT_PATH}/.env
else
    print_warning ".env file not found. You'll need to create it manually."
fi

# Install Laravel dependencies
print_status "Installing Laravel dependencies..."
cd ${TARGET_PROJECT_PATH}
sudo -u www-data composer install --no-interaction --prefer-dist --optimize-autoloader

# Install Node dependencies and build assets
print_status "Installing Node dependencies and building assets..."
sudo -u www-data npm install
sudo -u www-data npm run build

# Run Laravel optimizations
print_status "Running Laravel optimizations..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Generate application key if needed
if grep -q "APP_KEY=$" ${TARGET_PROJECT_PATH}/.env || grep -q "APP_KEY=\s*$" ${TARGET_PROJECT_PATH}/.env; then
    print_status "Generating application key..."
    sudo -u www-data php artisan key:generate
fi

# Run migrations (optional - commented out for safety)
# print_status "Running database migrations..."
# sudo -u www-data php artisan migrate --force

# Restart services
print_status "Restarting services..."
systemctl restart apache2
systemctl restart php8.2-fpm
systemctl restart mysql

# Create a backup of original Apache config
cp /etc/apache2/apache2.conf /etc/apache2/apache2.conf.backup

# Final status check
print_status "Checking service status..."
systemctl status apache2 --no-pager
systemctl status mysql --no-pager
systemctl status php8.2-fpm --no-pager

# Display completion message
echo ""
echo "==============================================="
echo -e "${GREEN}Migration completed successfully!${NC}"
echo "==============================================="
echo ""
echo "Next steps:"
echo "1. Verify the .env file has correct settings"
echo "2. Update APP_URL in .env to match new server"
echo "3. Test the application by accessing http://$(hostname -I | awk '{print $1}')"
echo "4. Configure SSL certificate if needed"
echo "5. Update DNS records to point to new server"
echo ""
echo "Database: ${SOURCE_DB_NAME}"
echo "Project Path: ${TARGET_PROJECT_PATH}"
echo "MySQL Root Password: ${NEW_MYSQL_ROOT_PASS}"
echo ""
print_warning "Remember to update firewall rules if necessary!"