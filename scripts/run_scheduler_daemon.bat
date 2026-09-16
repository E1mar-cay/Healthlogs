@echo off

cd /d "%~dp0"

REM One-shot scheduler job. Configure Windows Task Scheduler to run this file
REM every 5 minutes, or use run_scheduler_hidden.vbs to avoid a console window.
if exist "C:\xampp\php\php.exe" (
	"C:\xampp\php\php.exe" cron_reminders.php
) else (
	php cron_reminders.php
)

exit /b %ERRORLEVEL%
