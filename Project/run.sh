#!/bin/bash

echo "Starting PHP Development Server for your project..."

# Get the directory where this script is located
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

# Set the public directory as document root
PUBLIC_DIR="$PROJECT_DIR/public"

# Get configuration from PHP file
HOST=$(php -r "echo (require '$PROJECT_DIR/config/app.php')['server']['host'];")
PORT=$(php -r "echo (require '$PROJECT_DIR/config/app.php')['server']['port'];")

# Start PHP server with config values
echo "Starting server at http://$HOST:$PORT"
echo "Press Ctrl+C to stop the server"
echo ""

# Start the PHP development server
php -S $HOST:$PORT -t "$PUBLIC_DIR" 