@echo off
echo HealthLogs Database Seeder
echo ========================

REM Change to scripts directory
cd /d "C:\xampp\htdocs\HealthLogs\scripts"

REM Check if seed_all.php exists
if not exist "seed_all.php" (
    echo ERROR: Cannot find seed_all.php
    echo Make sure you're in the correct directory
    pause
    exit /b 1
)

echo Running all seeders...
echo.

REM Try different PHP paths
if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" seed_all.php
) else (
    php seed_all.php
)

if %ERRORLEVEL% EQU 0 (
    echo.
    echo SUCCESS: Database seeded successfully!
    echo.
    echo Login at: http://localhost/HealthLogs/public/login.php
    echo Username: admin
    echo Password: admin123
) else (
    echo.
    echo ERROR: Seeding failed!
)

echo.
pause