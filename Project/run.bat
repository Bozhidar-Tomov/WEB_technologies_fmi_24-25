@echo off
echo Starting PHP Development Server for your project...

REM Get the directory where this batch file is located
set PROJECT_DIR=%~dp0

REM Set the public directory as document root
set PUBLIC_DIR=%PROJECT_DIR%public

REM Get configuration from PHP file
for /f "tokens=*" %%a in ('php -r "echo json_encode(require './config/app.php');"') do set CONFIG=%%a
for /f "tokens=*" %%a in ('php -r "echo (require './config/app.php')['server']['port'];"') do set PORT=%%a
for /f "tokens=*" %%a in ('php -r "echo (require './config/app.php')['server']['host'];"') do set HOST=%%a

REM Start PHP server with config values
echo Starting server at http://%HOST%:%PORT%
echo Press Ctrl+C to stop the server
echo.

REM Start the PHP development server
php -S %HOST%:%PORT% -t "%PUBLIC_DIR%" 