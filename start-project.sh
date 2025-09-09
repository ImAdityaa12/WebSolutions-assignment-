#!/bin/bash

echo "========================================"
echo "WebSolutions - Project Startup Script"
echo "========================================"
echo

# Check if we're in the right directory
if [ ! -f "index.html" ]; then
    echo "ERROR: index.html not found!"
    echo "Please run this script from the project root directory."
    exit 1
fi

# Check PHP installation
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP not found!"
    echo "Please install PHP 7.4+ to run this project."
    echo
    echo "Installation commands:"
    echo "Ubuntu/Debian: sudo apt install php"
    echo "macOS: brew install php"
    echo "CentOS/RHEL: sudo yum install php"
    exit 1
fi

echo "PHP found! Starting development server..."
echo

echo "========================================"
echo "Server Information:"
echo "========================================"
echo "Website URL: http://localhost:8000"
echo "Admin Panel: http://localhost:8000/admin/view-submissions.php"
echo "Admin Password: WebSolutions2025!"
echo "========================================"
echo
echo "Press Ctrl+C to stop the server"
echo

# Start PHP built-in server
php -S localhost:8000