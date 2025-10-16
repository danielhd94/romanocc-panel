#!/bin/bash

# RomanOcc Web - MySQL Startup Script
# Usage: ./start-mysql.sh

set -e

echo "🗄️  Starting MySQL for RomanOcc Web..."
echo "====================================="

# Check if MySQL is already running
if /Applications/XAMPP/xamppfiles/bin/mysql -u root -p'N0m3l0s3#1' -e "SELECT 1;" >/dev/null 2>&1; then
    echo "✅ MySQL is already running"
    echo "📍 Connection: mysql -u root -p'N0m3l0s3#1'"
    exit 0
fi

echo "⚠️  MySQL not running. Starting MySQL..."

# Try different methods to start MySQL
echo "🔄 Attempting to start MySQL..."

if /Applications/XAMPP/xamppfiles/bin/mysql.server start >/dev/null 2>&1; then
    echo "✅ MySQL started via mysql.server"
elif /Applications/XAMPP/xamppfiles/xampp startmysql >/dev/null 2>&1; then
    echo "✅ MySQL started via xampp startmysql"
elif /Applications/XAMPP/xamppfiles/xampp start >/dev/null 2>&1; then
    echo "✅ MySQL started via xampp start"
else
    echo "❌ Could not start MySQL automatically."
    echo ""
    echo "💡 Please try one of these options:"
    echo "   1. Start XAMPP Control Panel and start MySQL manually"
    echo "   2. Run: sudo /Applications/XAMPP/xamppfiles/xampp start"
    echo "   3. Run: sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start"
    echo ""
    echo "🔄 Waiting 5 seconds and checking if MySQL is now running..."
    sleep 5
    
    if /Applications/XAMPP/xamppfiles/bin/mysql -u root -p'N0m3l0s3#1' -e "SELECT 1;" >/dev/null 2>&1; then
        echo "✅ MySQL is now running"
    else
        echo "❌ MySQL is still not running."
        echo ""
        echo "🔧 Quick fix: Open XAMPP Control Panel and start MySQL manually."
        exit 1
    fi
fi

# Verify MySQL is working
echo "🔍 Verifying MySQL connection..."
if /Applications/XAMPP/xamppfiles/bin/mysql -u root -p'N0m3l0s3#1' -e "SELECT 'MySQL is working!' as status;" >/dev/null 2>&1; then
    echo "✅ MySQL is running and accessible"
    echo "📍 Connection string: mysql -u root -p'N0m3l0s3#1'"
    echo "🌐 You can now run ./start.sh to start the development server"
else
    echo "❌ MySQL started but connection failed"
    exit 1
fi

echo ""
echo "🎉 MySQL is ready!"
