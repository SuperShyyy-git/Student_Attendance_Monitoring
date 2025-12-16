@echo off
REM Auto Chat ID Fetcher Service Launcher
REM This runs the background service with XAMPP PHP

cd /d "%~dp0"

REM Set XAMPP PHP path
set PHP_PATH=C:\xampp\php\php.exe

REM Check if PHP exists at the expected location
if not exist "%PHP_PATH%" (
    echo Error: PHP not found at %PHP_PATH%
    echo Please update the PHP_PATH variable in this script
    pause
    exit /b 1
)

echo.
echo ========================================
echo Auto Chat ID Fetcher Service
echo ========================================
echo.
echo Starting background service using XAMPP...
echo PHP: %PHP_PATH%
echo Service will fetch Chat IDs every 3 seconds
echo.
echo Check logs at: logs\auto_chatid_service.log
echo.
echo Ctrl+C to stop the service
echo ========================================
echo.

REM Run the PHP service - disable display_errors to avoid UI popups
"%PHP_PATH%" -d display_errors=0 auto_chatid_service.php

pause
