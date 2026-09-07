@echo off
REM HealthLogs Reminder SMS Sender
REM Double-click this file to send pending SMS reminders

echo.
echo ========================================
echo   HealthLogs Reminder SMS Sender
echo ========================================
echo.

REM Check if PHP is in PATH
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP not found in PATH
    echo.
    echo Please add PHP to your PATH or use:
    echo C:\xampp\php\php.exe cron_reminders.php
    echo.
    pause
    exit /b 1
)

REM Check if we're in the scripts directory
if not exist "cron_reminders.php" (
    echo ERROR: cron_reminders.php not found
    echo.
    echo Please run this script from the scripts directory:
    echo C:\xampp\htdocs\HealthLogs\scripts\
    echo.
    pause
    exit /b 1
)

REM Check if .env file exists
if not exist "..\.env" (
    echo WARNING: .env file not found
    echo.
    echo Please copy .env.example to .env and configure SMS credentials.
    echo.
    pause
    exit /b 1
)

echo This will send SMS reminders to patients with:
echo   - Pending reminders (any picked due date)
echo   - Valid mobile contact numbers
echo.
echo Press Ctrl+C to cancel, or
pause

echo.
echo Sending SMS reminders via TextBee...
echo.

php cron_reminders.php

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo   Reminder SMS Sent Successfully!
    echo ========================================
    echo.
    echo Check the log file for details:
    echo   ..\storage\reminder_cron.log
    echo.
) else (
    echo.
    echo ========================================
    echo   Some SMS Failed to Send!
    echo ========================================
    echo.
    echo Please check the error messages above.
    echo Check device connectivity and .env configuration.
    echo.
)

pause
