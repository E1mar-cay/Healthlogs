# Quick Start: Database Seeding

## 🚀 One-Command Setup

```bash
cd C:\xampp\htdocs\HealthLogs\scripts
php seed_all.php
```

That's it! This will populate your database with:
- ✅ 100 patients
- ✅ 30 children with immunization records
- ✅ 15-20 pregnancies with prenatal/postnatal visits
- ✅ 7-10 TB cases with follow-ups
- ✅ 38 medicines with batches and transactions
- ✅ Automatic reminders for all modules

## ⏱️ Expected Time
**Total:** ~15-30 seconds

## 📊 What You'll Get

### Patients Module
- 100 diverse patients (ages 0-80)
- Philippine names and addresses
- Contact info, PhilHealth numbers
- Allergies and medical conditions

### Immunization Module
- 30 children (0-5 years)
- 10 standard vaccines
- 300-500 schedules
- 200-400 completed immunizations
- Upcoming vaccination reminders

### Maternal Health Module
- 15-20 pregnancy records
- 60-100 prenatal visits
- 20-40 postnatal visits
- Vital signs tracking
- Prenatal visit reminders

### TB Monitoring Module
- 7-10 TB cases
- 50-100 follow-up visits
- Treatment adherence tracking
- Active case reminders

### Medicine Inventory Module
- 38 common BHU medicines
- 100-150 batches
- 1000-1500 transactions
- Low stock alerts
- Expiring medicine warnings

## 🔄 Reset & Re-seed

If you need to start fresh:

```bash
# 1. Clear all data
mysql -u root -p healthlogs < scripts/clear_data.sql

# 2. Re-run seeders
php seed_all.php
```

## 🎯 Individual Modules

Need to seed just one module?

```bash
# Patients only
php seed_patients.php

# Immunization only
php seed_immunization.php

# Maternal health only
php seed_maternal.php

# TB monitoring only
php seed_tb.php

# Medicine inventory only
php seed_inventory.php
```

**Note:** Most modules require patients to exist first!

## ✅ Verify Success

After seeding, check:

1. **Login:** http://localhost/HealthLogs/public/login.php
2. **Credentials:** admin / admin123
3. **Navigate to each module:**
   - Patient Records → Should show 100 patients
   - Immunization → Should show schedules and records
   - Maternal Health → Should show pregnancies
   - TB Monitoring → Should show cases
   - Medicine Inventory → Should show 38 medicines

## 🐛 Troubleshooting

### "Database connection failed"
```bash
# Check config/db.php
# Ensure MySQL is running
# Verify database exists
```

### "No eligible patients found"
```bash
# Run patients seeder first
php seed_patients.php
```

### "Duplicate entry" errors
```bash
# Clear database and re-seed
mysql -u root -p healthlogs < scripts/clear_data.sql
php seed_all.php
```

## 📝 Notes

- Seeders create realistic test data
- Some medicines will show low stock (intentional)
- Some batches will be expiring soon (intentional)
- Reminders are automatically created
- Data is randomized each run

## ⚠️ Production Warning

**NEVER run seeders in production!**

These are for development/testing only.

## 🎉 Next Steps

After seeding:
1. Explore the populated modules
2. Test CRUD operations
3. Try the forecasting feature
4. Test reminder system
5. Generate reports

Enjoy your fully populated HealthLogs system! 🏥
