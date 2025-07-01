@echo off
echo Starting PHP Development Server for your project...

REM Get the directory where this batch file is located (includes trailing backslash)
set PROJECT_DIR=%~dp0

REM Check if PHP is available
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP not found in PATH. Please make sure PHP is installed and added to your PATH.
    echo You can check by running 'php -v' in your command prompt.
    echo.
    pause
    exit /b 1
)

REM Set the public directory as document root
set PUBLIC_DIR=%PROJECT_DIR%public

REM Check if public directory exists
if not exist "%PUBLIC_DIR%" (
    echo ERROR: Public directory not found at %PUBLIC_DIR%
    echo Please make sure you're running this script from the project root directory.
    echo.
    pause
    exit /b 1
)

REM Start PHP server on port 8080
echo Starting server at http://localhost:8080
echo Press Ctrl+C to stop the server
echo.

REM Start the PHP development server
php -S localhost:8080 -t "%PUBLIC_DIR%" 