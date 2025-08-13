#!/bin/bash

# RomanOcc Web - Development Server Startup Script
# Usage: ./start.sh

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

# Check if MySQL is running
if ! /Applications/XAMPP/xamppfiles/bin/mysql -u root -p'N0m3l0s3#1' -e "SELECT 1;" >/dev/null 2>&1; then
    echo "⚠️  Warning: MySQL not running. Starting MySQL..."
    /Applications/XAMPP/xamppfiles/bin/mysql.server start >/dev/null 2>&1 || {
        echo "❌ Error: Could not start MySQL. Please start it manually from XAMPP."
        exit 1
    }
    echo "✅ MySQL started"
else
    echo "✅ MySQL is running"
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "❌ Error: .env file not found. Please copy .env.example to .env and configure it."
    exit 1
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

# Run migrations if needed
echo "🗄️  Checking database migrations..."
php artisan migrate --force

echo ""
echo "🌐 Starting development server..."
echo "📍 URL: http://localhost:8000"
echo "📍 Network: http://0.0.0.0:8000"
echo "🛑 Press Ctrl+C to stop"
echo ""

# Start the development server
php artisan serve --host=0.0.0.0 --port=8000
