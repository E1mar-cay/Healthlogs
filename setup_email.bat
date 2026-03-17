@echo off
REM HealthLogs Email Setup Wizard
REM Automated setup for PHPMailer email integration

echo.
echo ========================================
echo   HealthLogs Email Setup Wizard
echo ========================================
echo.
echo This wizard will help you set up email
echo notifications for the reminder system.
echo.
pause

REM Step 1: Check Composer
echo.
echo [Step 1/5] Checking Composer installation...
echo.

where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Composer not found!
    echo.
    echo Please install Composer first:
    echo   1. Download from: https://getcomposer.org/download/
    echo   2. Run the installer
    echo   3. Restart this script
    echo.
    pause
    exit /b 1
)

echo ✓ Composer is installed
echo.

REM Step 2: Install PHPMailer
echo [Step 2/5] Installing PHPMailer...
echo.

if exist "vendor\phpmailer" (
    echo ✓ PHPMailer already installed
) else (
    echo Installing PHPMailer via Composer...
    composer install
    
    if %ERRORLEVEL% NEQ 0 (
        echo.
        echo ERROR: Failed to install PHPMailer
        echo.
        pause
        exit /b 1
    )
    
    echo ✓ PHPMailer installed successfully
)
echo.

REM Step 3: Create .env file
echo [Step 3/5] Creating .env configuration file...
echo.

if exist ".env" (
    echo ✓ .env file already exists
    echo.
    set /p OVERWRITE="Do you want to reconfigure? (y/n): "
    if /i not "%OVERWRITE%"=="y" goto skip_env
)

if not exist ".env.example" (
    echo ERROR: .env.example not found
    pause
    exit /b 1
)

copy .env.example .env >nul
echo ✓ Created .env file from template
echo.

:skip_env

REM Step 4: Configure email settings
echo [Step 4/5] Email Configuration
echo.
echo You need a Gmail account with App Password.
echo.
echo If you don't have an App Password yet:
echo   1. Go to: https://myaccount.google.com/security
echo   2. Enable 2-Factor Authentication
echo   3. Go to: https://myaccount.google.com/apppasswords
echo   4. Generate App Password for "Mail"
echo.
echo See GMAIL_SETUP_GUIDE.md for detailed instructions.
echo.

set /p CONFIGURE="Do you want to configure email now? (y/n): "
if /i not "%CONFIGURE%"=="y" goto skip_config

echo.
set /p EMAIL="Enter your Gmail address: "
set /p PASSWORD="Enter your Gmail App Password (16 chars): "

REM Update .env file
powershell -Command "(gc .env) -replace 'MAIL_USERNAME=.*', 'MAIL_USERNAME=%EMAIL%' | Out-File -encoding ASCII .env"
powershell -Command "(gc .env) -replace 'MAIL_PASSWORD=.*', 'MAIL_PASSWORD=%PASSWORD%' | Out-File -encoding ASCII .env"
powershell -Command "(gc .env) -replace 'MAIL_FROM_ADDRESS=.*', 'MAIL_FROM_ADDRESS=%EMAIL%' | Out-File -encoding ASCII .env"
powershell -Command "(gc .env) -replace 'MAIL_ENABLED=.*', 'MAIL_ENABLED=true' | Out-File -encoding ASCII .env"

echo.
echo ✓ Email configuration saved to .env
echo.

:skip_config

REM Step 5: Test email
echo [Step 5/5] Testing email configuration...
echo.

if not exist ".env" (
    echo WARNING: .env file not configured
    echo Please edit .env manually and run: scripts\test_email.bat
    goto end_setup
)

set /p TEST="Do you want to send a test email now? (y/n): "
if /i not "%TEST%"=="y" goto end_setup

echo.
cd scripts
call test_email.bat
cd ..

:end_setup

echo.
echo ========================================
echo   Setup Complete!
echo ========================================
echo.
echo Next steps:
echo   1. Review .env file if needed
echo   2. Run: scripts\test_email.bat (to test)
echo   3. Run: scripts\send_reminders.bat (to send)
echo.
echo Documentation:
echo   - EMAIL_SETUP.md - Complete setup guide
echo   - GMAIL_SETUP_GUIDE.md - Gmail instructions
echo   - EMAIL_QUICK_REFERENCE.md - Quick reference
echo.
echo ========================================
echo.

pause
