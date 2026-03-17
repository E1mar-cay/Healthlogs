@echo off
REM HealthLogs Database Seeder - Windows Batch Script
REM Double-click this file to run all seeders

echo.
echo ========================================
echo   HealthLogs Database Seeder
echo ========================================
echo.

REM Check if PHP is in PATH
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP not found in PATH
    echo.
    echo Please add PHP to your PATH or use:
    echo C:\xampp\php\php.exe seed_all.php
    echo.
    pause
    exit /b 1
)

REM Check if we're in the scripts directory
if not exist "seed_all.php" (
    echo ERROR: seed_all.php not found
    echo.
    echo Please run this script from the scripts directory:
    echo C:\xampp\htdocs\HealthLogs\scripts\
    echo.
    pause
    exit /b 1
)

echo Starting database seeding...
echo.
echo This will populate your database with:
echo   - 100 patients
echo   - 30 children with immunization records
echo   - 15-20 pregnancies
echo   - 7-10 TB cases
echo   - 38 medicines with inventory
echo.
echo Press Ctrl+C to cancel, or
pause

echo.
echo Running seeders...
echo.

php seed_all.php

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo   Seeding Completed Successfully!
    echo ========================================
    echo.
    echo You can now login at:
    echo http://localhost/HealthLogs/public/login.php
    echo.
    echo Credentials: admin / admin123
    echo.
) else (
    echo.
    echo ========================================
    echo   Seeding Failed!
    echo ========================================
    echo.
    echo Please check the error messages above.
    echo.
)

pause
