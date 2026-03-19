@echo off
echo HealthLogs - Fresh Database Setup
echo ================================

echo.
echo Step 1: Clearing existing data...
echo y | C:\xampp\php\php.exe clear_all_data.php

echo.
echo Step 2: Running all seeders...
C:\xampp\php\php.exe seed_all.php

echo.
echo Setup complete!
pause