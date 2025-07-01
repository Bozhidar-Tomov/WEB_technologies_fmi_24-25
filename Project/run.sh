#!/bin/bash

echo "Starting PHP Development Server for your project..."

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP not found in PATH. Please make sure PHP is installed and added to your PATH."
    echo "You can check by running 'php -v' in your terminal."
    exit 1
fi

# Get the directory where this script is located
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

# Set the public directory as document root
PUBLIC_DIR="$PROJECT_DIR/public"

# Check if public directory exists
if [ ! -d "$PUBLIC_DIR" ]; then
    echo "ERROR: Public directory not found at $PUBLIC_DIR"
    echo "Please make sure you're running this script from the project root directory."
    exit 1
fi

# Start PHP server on port 8080
echo "Starting server at http://localhost:8080"
echo "Press Ctrl+C to stop the server"
echo ""

# Start the PHP development server
php -S localhost:8080 -t "$PUBLIC_DIR" 