@echo off
echo Starting PHP Development Server for your project...

REM Get the directory where this batch file is located
set PROJECT_DIR=%~dp0

REM Set the public directory as document root
set PUBLIC_DIR=%PROJECT_DIR%public

REM Start PHP server on port 8080
echo Starting server at http://localhost:8080
echo Press Ctrl+C to stop the server
echo.

REM Start the PHP development server
php -S localhost:8080 -t "%PUBLIC_DIR%" 