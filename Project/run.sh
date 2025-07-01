#!/bin/bash

echo "Starting PHP Development Server for your project..."

# Get the directory where this script is located
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

# Set the public directory as document root
PUBLIC_DIR="$PROJECT_DIR/public"

# Start PHP server on port 8080
echo "Starting server at http://localhost:8080"
echo "Press Ctrl+C to stop the server"
echo ""

# Start the PHP development server
php -S localhost:8080 -t "$PUBLIC_DIR" 