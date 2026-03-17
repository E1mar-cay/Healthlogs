# HealthLogs - Barangay Health Management System

A comprehensive health management system for Barangay Health Units (BHU) to track patient records, immunization schedules, maternal health, TB monitoring, and medicine inventory.

## Features

- **Patient Records Management** - Complete demographic and health information tracking
- **Immunization Tracking** - Vaccine schedules, records, and reminders
- **Maternal Health** - Pregnancy tracking, prenatal and postnatal visits
- **TB Monitoring** - TB case management and follow-up tracking
- **Medicine Inventory** - Stock management with batch tracking and expiry monitoring
- **Automated Reminders** - SMS/Email notifications for upcoming appointments
- **Forecasting** - ARIMA-based predictive analytics for demand planning
- **Role-Based Access Control** - Superadmin, Admin, and Health Worker roles
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

### 1. Clone the Repository

```bash
cd C:\xampp\htdocs
git clone <repository-url> HealthLogs
cd HealthLogs
```

### 2. Database Setup

1. Create a new database:
```sql
CREATE DATABASE healthlogs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the schema:
```bash
mysql -u root -p healthlogs < schema/healthlogs.sql
```

3. Run seeders:
```bash
# Seed user accounts first
php scripts/seed_users.php

# Seed all modules with dummy data (recommended for testing)
php scripts/seed_all.php

# OR seed individual modules
php scripts/seed_patients.php
php scripts/seed_immunization.php
php scripts/seed_maternal.php
php scripts/seed_tb.php
php scripts/seed_inventory.php

# Optional: Seed time series data for forecasting
php scripts/seed_timeseries.php
```

### 3. Environment Configuration

1. Copy the example environment file:
```bash
copy .env.example .env
```

2. Edit `.env` and update your configuration:
```env
DB_HOST=127.0.0.1
DB_NAME=healthlogs
DB_USER=root
DB_PASS=your_password

PYTHON_PATH=C:\Path\To\python.exe
```

3. Update `config/db.php` to read from `.env` (or keep current configuration)

### 4. Python Setup (for Forecasting)

1. Install Python dependencies:
```bash
cd scripts
pip install -r requirements.txt
```

Required packages:
- pandas
- pymysql
- pmdarima

### 5. Email Configuration (for Reminders)

1. Install PHPMailer:
```bash
composer install
```

2. Configure email in `.env`:
```env
MAIL_ENABLED=true
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

3. Test email:
```bash
php scripts/test_email.php
```

See `GMAIL_SETUP_GUIDE.md` for detailed Gmail setup instructions.

### 6. File Permissions

Ensure the `storage/` directory is writable:
```bash
mkdir storage
chmod 755 storage
```

### 7. Access the Application

Open your browser and navigate to:
```
http://localhost/HealthLogs/public/login.php
```

## Default Credentials

**Development Only** (remove in production):

- **Superadmin:** `superadmin` / `superadmin123`
- **Admin:** `admin` / `admin123`
- **Health Worker:** `bhw` / `bhw123`

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
│   ├── tb/             # TB monitoring module
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

1. Navigate to **Forecasting** (Admin/Superadmin only)
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
- TB case management
- Medicine inventory
- ARIMA forecasting
- Reminder system
- Role-based dashboards
- Input validation and sanitization
- Transaction management
