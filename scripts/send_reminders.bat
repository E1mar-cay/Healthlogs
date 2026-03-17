@echo off
REM HealthLogs Reminder Email Sender
REM Double-click this file to send pending reminder emails

echo.
echo ========================================
echo   HealthLogs Reminder Email Sender
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

REM Check if vendor directory exists
if not exist "..\vendor" (
    echo ERROR: PHPMailer not installed
    echo.
    echo Please install Composer dependencies first:
    echo   cd C:\xampp\htdocs\HealthLogs
    echo   composer install
    echo.
    pause
    exit /b 1
)

REM Check if .env file exists
if not exist "..\.env" (
    echo WARNING: .env file not found
    echo.
    echo Please copy .env.example to .env and configure:
    echo   copy .env.example .env
    echo.
    echo Then edit .env with your Gmail credentials.
    echo.
    pause
    exit /b 1
)

echo This will send email reminders to patients with:
echo   - Pending reminders
echo   - Due date today or earlier
echo   - Valid email addresses
echo.
echo Press Ctrl+C to cancel, or
pause

echo.
echo Sending reminder emails...
echo.

php cron_reminders.php

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo   Reminder Emails Sent Successfully!
    echo ========================================
    echo.
    echo Check the log file for details:
    echo   ..\storage\reminder_cron.log
    echo.
) else (
    echo.
    echo ========================================
    echo   Some Emails Failed!
    echo ========================================
    echo.
    echo Please check the error messages above.
    echo See EMAIL_SETUP.md for troubleshooting.
    echo.
)

pause
