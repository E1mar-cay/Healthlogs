@echo off
title HealthLogs SMS Reminder Scheduler Daemon
echo ====================================================
echo   HealthLogs SMS Reminder Scheduler Daemon
echo   Keep this window minimized to automatically check
echo   and dispatch SMS reminders at your scheduled time.
echo ====================================================
echo.

cd /d "%~dp0"

:loop
cls
echo [%date% %time%] Checking scheduled reminders...
php cron_reminders.php
echo.
echo Waiting 60 seconds before next check...
echo (Press Ctrl+C to stop)
timeout /t 60 /nobreak >nul
goto loop
