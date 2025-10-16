#!/bin/bash

# RomanOcc Web - Development Server Startup Script
# Usage: ./start.sh
# Note: This script works with XAMPP MySQL database

set -e

echo "🚀 Starting RomanOcc Web Development Server..."
echo "=============================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Make sure you're in the Laravel project root."
    exit 1
fi

# Check if PHP 8.3+ is available
PHP_VERSION=$(php -v | head -n 1 | cut -d' ' -f2 | cut -d'.' -f1,2)
REQUIRED_VERSION="8.3"

if [ "$(printf '%s\n' "$REQUIRED_VERSION" "$PHP_VERSION" | sort -V | head -n1)" != "$REQUIRED_VERSION" ]; then
    echo "❌ Error: PHP $REQUIRED_VERSION+ required, but found PHP $PHP_VERSION"
    echo "💡 Run: phpuse 8.3"
    exit 1
fi

echo "✅ PHP version: $(php -v | head -n 1 | cut -d' ' -f2)"

# Check if XAMPP MySQL is running
echo "🔍 Checking XAMPP MySQL connection..."
if /Applications/XAMPP/xamppfiles/bin/mysql -u root -p'N0m3l0s3#1' -e "SELECT 1;" >/dev/null 2>&1; then
    echo "✅ XAMPP MySQL is running"
    DB_ACCESSIBLE=true
else
    echo "⚠️  XAMPP MySQL is not running"
    echo "💡 Run ./start-mysql.sh first to start XAMPP MySQL"
    echo "   Or start MySQL manually from XAMPP Control Panel"
    DB_ACCESSIBLE=false
fi

# Check if .env exists, create it if not
if [ ! -f ".env" ]; then
    echo "📝 Creating .env file for XAMPP configuration..."
    cat > .env << 'EOF'
APP_NAME="RomanOcc Web"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=romanocc_web
DB_USERNAME=root
DB_PASSWORD=N0m3l0s3#1

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
EOF
    echo "✅ .env file created with XAMPP configuration"
    
    # Generate APP_KEY
    echo "🔑 Generating application key..."
    php artisan key:generate --force >/dev/null 2>&1
    echo "✅ Application key generated"
else
    echo "✅ .env file found"
fi

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction
fi

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "📦 Installing NPM dependencies..."
    npm install
fi

# Build assets if needed
if [ ! -f "public/build/manifest.json" ]; then
    echo "🔨 Building frontend assets..."
    npm run build
fi

# Create database if it doesn't exist and run migrations
echo "🗄️  Setting up database..."
if [ "$DB_ACCESSIBLE" = true ]; then
    echo "🔄 Creating database if it doesn't exist..."
    /Applications/XAMPP/xamppfiles/bin/mysql -u root -p'N0m3l0s3#1' -e "CREATE DATABASE IF NOT EXISTS romanocc_web;" >/dev/null 2>&1
    echo "✅ Database 'romanocc_web' is ready"
    
    echo "🔄 Running database migrations..."
    if php artisan migrate --force >/dev/null 2>&1; then
        echo "✅ Database migrations completed"
    else
        echo "⚠️  Migrations failed, but continuing..."
        echo "💡 Run 'php artisan migrate' manually if needed"
    fi
else
    echo "⚠️  Skipping database setup - XAMPP MySQL not accessible"
    echo "💡 Run ./start-mysql.sh first, then run 'php artisan migrate' manually"
fi

echo ""
echo "🌐 Starting development server..."
echo "📍 URL: http://localhost:8000"
echo "📍 Network: http://0.0.0.0:8000"
echo "🛑 Press Ctrl+C to stop"
echo ""

# Start the development server
php artisan serve --host=0.0.0.0 --port=8000
