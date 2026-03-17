@echo off
REM HealthLogs Email Configuration Test
REM Double-click this file to test your email setup

echo.
echo ========================================
echo   HealthLogs Email Configuration Test
echo ========================================
echo.

REM Check if PHP is in PATH
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP not found in PATH
    echo.
    echo Please add PHP to your PATH or use:
    echo C:\xampp\php\php.exe test_email.php
    echo.
    pause
    exit /b 1
)

REM Check if we're in the scripts directory
if not exist "test_email.php" (
    echo ERROR: test_email.php not found
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

echo Running email configuration test...
echo.

php test_email.php

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo   Email Test Completed!
    echo ========================================
    echo.
) else (
    echo.
    echo ========================================
    echo   Email Test Failed!
    echo ========================================
    echo.
    echo Please check:
    echo   1. Composer dependencies installed (composer install)
    echo   2. .env file configured with Gmail credentials
    echo   3. Gmail App Password generated
    echo   4. 2-Factor Authentication enabled on Gmail
    echo.
    echo See GMAIL_SETUP_GUIDE.md for detailed instructions.
    echo.
)

pause
