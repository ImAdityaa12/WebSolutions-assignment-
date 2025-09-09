@echo off
echo ========================================
echo WebSolutions - Project Startup Script
echo ========================================
echo.

REM Check if we're in the right directory
if not exist "index.html" (
    echo ERROR: index.html not found!
    echo Please run this script from the project root directory.
    pause
    exit /b 1
)

echo Checking PHP installation...
php --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP not found in PATH!
    echo Please install PHP or XAMPP and add to PATH.
    echo.
    echo XAMPP Users: Make sure Apache is running in XAMPP Control Panel
    pause
    exit /b 1
)

echo PHP found! Starting development server...
echo.
echo ========================================
echo Server Information:
echo ========================================
echo Website URL: http://localhost:8000
echo Admin Panel: http://localhost:8000/admin/view-submissions.php
echo Admin Password: WebSolutions2025!
echo ========================================
echo.
echo Press Ctrl+C to stop the server
echo.

REM Start PHP built-in server
php -S localhost:8000