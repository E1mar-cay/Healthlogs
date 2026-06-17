# HealthLogs - Barangay Health Management System

A comprehensive health management system for Barangay Health Units (BHU) to track patient records, immunization schedules, maternal health, TB monitoring, and medicine inventory.

## Features

- **Patient Records Management** - Complete demographic and health information tracking
- **Immunization Tracking** - Vaccine schedules, records, and reminders
- **Maternal Health** - Pregnancy tracking, prenatal and postnatal visits
- **Medicine Inventory** - Stock management with batch tracking and expiry monitoring
- **Automated Reminders** - SMS/Email notifications for upcoming appointments
- **Forecasting** - ARIMA-based predictive analytics for demand planning
- **Role-Based Access Control** - Admin and Health Worker roles
- **Dashboard Analytics** - Real-time insights and reporting

## Tech Stack

- **Backend:** PHP 8.2+ with PDO
- **Database:** MySQL/MariaDB
- **Frontend:** Tailwind CSS, Chart.js, SweetAlert2
- **Analytics:** Python (ARIMA forecasting with pmdarima)
- **Server:** Apache (XAMPP)

## Requirements

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.4+
- Apache Web Server
- Python 3.13+ (for forecasting)
- Composer (optional, for future dependencies)

## Installation

Follow these steps from start to finish to set up the HealthLogs application:

### Step 1: Clone the Repository

Navigate to your web server root directory (e.g., XAMPP's `htdocs` directory) and clone the repository:

```bash
cd C:\xampp\htdocs
git clone <repository-url> HealthLogs
cd HealthLogs
```

### Step 2: Environment Configuration

1. Copy the template environment file to create your local `.env` file:
   ```bash
   copy .env.example .env
   ```

2. Open the `.env` file and configure your database parameters:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=healthlogs
   DB_USER=root
   DB_PASS=
   ```

### Step 3: Database Setup

1. Open your XAMPP Control Panel and start **Apache** and **MySQL**.
2. Create a new database named `healthlogs`. You can do this in phpMyAdmin (`http://localhost/phpmyadmin`) or via MySQL command line:
   ```sql
   CREATE DATABASE healthlogs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the database schema:
   ```bash
   mysql -u root -p healthlogs < schema/healthlogs.sql
   ```
4. Seed the database with sample data (required for generating forecasts):
   ```bash
   # Seeds users, patients, maternal records, immunization schedules, and inventory
   php scripts/seed_all.php
   ```

### Step 4: Python Setup (for Forecasting)

The ARIMA forecasting module runs on Python. Set up a local virtual environment to isolate dependencies:

1. Create a Python virtual environment:
   ```bash
   python -m venv .venv
   ```
2. Activate the virtual environment:
   * **PowerShell (Windows):**
     ```powershell
     .venv\Scripts\Activate.ps1
     ```
   * **Command Prompt (Windows):**
     ```cmd
     .venv\Scripts\activate.bat
     ```
   * **macOS / Linux:**
     ```bash
     source .venv/bin/activate
     ```
3. Install the required libraries:
   ```bash
   pip install -r scripts/requirements.txt
   ```
4. Update your `.env` file to point `PYTHON_PATH` to your newly created virtual environment:
   * **Windows:**
     ```env
     PYTHON_PATH=c:\xampp\htdocs\HealthLogs\.venv\Scripts\python.exe
     ```
   * **macOS / Linux:**
     ```env
     PYTHON_PATH=/path/to/HealthLogs/.venv/bin/python
     ```
5. Verify that the forecasting script is working correctly:
   ```bash
   php scripts/test_forecasting.php
   ```

### Step 5: Email Configuration (for Reminders)

PHPMailer is used to send automated appointment reminders.

1. Install Composer dependencies:
   ```bash
   composer install
   ```
2. Open your `.env` file and enter your Gmail credentials (make sure to use a [Gmail App Password](GMAIL_SETUP_GUIDE.md)):
   ```env
   MAIL_ENABLED=true
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   ```
3. Run the email test script to confirm email delivery:
   ```bash
   php scripts/test_email.php
   ```

### Step 6: Create Storage Directory

Ensure the application has a folder to save logs and reports:
```bash
mkdir storage
```

### Step 7: Access the Application

1. Open your browser and navigate to:
   ```
   http://localhost/HealthLogs/public/login.php
   ```
2. Use the default credentials for development:
   * **Admin (Access to Forecasting):** Username `admin` / Password `admin123`
   * **Health Worker:** Username `bhw` / Password `bhw123`

## Project Structure

```
HealthLogs/
├── app/
│   ├── Controllers/      # MVC Controllers (future use)
│   ├── Core/            # Core classes (Database, Router, Validator)
│   ├── Models/          # Data models
│   └── Views/           # View templates (future use)
├── config/
│   └── db.php           # Database configuration
├── public/              # Public web root
│   ├── dashboards/      # Role-based dashboards
│   ├── immunization/    # Immunization module
│   ├── inventory/       # Medicine inventory module
│   ├── maternal/        # Maternal health module
│   ├── patients/        # Patient records module
│   ├── reminders/       # Reminders module
│   └── partials/       # Shared components (header, footer, bootstrap)
├── schema/
│   └── healthlogs.sql   # Database schema
├── scripts/             # Utility scripts and seeders
│   ├── forecast_arima.py
│   ├── cron_reminders.php
│   └── seed_*.php
├── storage/             # File storage (logs, uploads)
├── .env.example         # Environment configuration template
├── .gitignore          # Git ignore rules
└── README.md           # This file
```

## Usage

### Patient Management

1. Navigate to **Patient Records**
2. Click **New Patient** to add a patient
3. Fill in required fields (name, sex, birth date, barangay)
4. Submit to save

### Immunization Tracking

1. Go to **Immunization** module
2. Manage vaccines, schedules, and records
3. System automatically creates reminders for upcoming vaccinations

### Forecasting

1. Navigate to **Forecasting** (Admin only)
2. Select series (visits or medicine demand)
3. Set forecast horizon (days)
4. Click **Run Forecast** to generate predictions

### Reminders

1. Go to **Reminders** module
2. View pending, sent, and overdue reminders
3. Manually run cron job or set up automated scheduling

## Automated Tasks

### Reminder Cron Job

Set up a scheduled task to run:
```bash
php C:\xampp\htdocs\HealthLogs\scripts\cron_reminders.php
```

**Windows Task Scheduler:**
- Frequency: Daily at 8:00 AM
- Action: Start a program
- Program: `C:\xampp\php\php.exe`
- Arguments: `C:\xampp\htdocs\HealthLogs\scripts\cron_reminders.php`

## Security Considerations

### Production Deployment

1. **Change default passwords** for all user accounts
2. **Remove sample credentials** from login page (set `APP_ENV=production`)
3. **Use strong database password**
4. **Enable HTTPS** and update session settings
5. **Set proper file permissions** (read-only for PHP files)
6. **Enable error logging** (disable display_errors)
7. **Regular backups** of database and files
8. **Update dependencies** regularly

### Security Features Implemented

- ✅ Password hashing with bcrypt
- ✅ Prepared statements (SQL injection prevention)
- ✅ Input validation and sanitization
- ✅ XSS protection with htmlspecialchars()
- ✅ Session security settings
- ✅ Role-based access control (RBAC)
- ✅ Transaction management for data integrity

### TODO Security Enhancements

- [ ] CSRF token protection (add to all forms)
- [ ] Rate limiting on login attempts
- [ ] Account lockout mechanism
- [ ] Two-factor authentication (2FA)
- [ ] Audit logging for all data changes
- [ ] Content Security Policy (CSP) headers

## Troubleshooting

### Database Connection Failed
- Check `config/db.php` credentials
- Ensure MySQL service is running
- Verify database exists

### Python Forecast Not Working
- Check Python path in `.env` or `forecast.php`
- Install required packages: `pip install -r scripts/requirements.txt`
- Verify database connection in Python script

### Validation Errors Not Showing
- Ensure session is started
- Check `FlashHelper.php` is included in bootstrap
- Call `display_validation_errors()` in form

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is developed for Barangay Health Units in the Philippines.

## Support

For issues and questions, please create an issue in the repository.

## Changelog

### Version 1.0.0 (Current)
- Initial release
- Patient records management
- Immunization tracking
- Maternal health monitoring
- Medicine inventory
- ARIMA forecasting
- Reminder system
- Role-based dashboards
- Input validation and sanitization
- Transaction management
