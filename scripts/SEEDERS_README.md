# Database Seeders Documentation

## Overview

This directory contains seeder scripts to populate the HealthLogs database with realistic dummy data for testing and development purposes.

## Available Seeders

### 1. `seed_patients.php`
**Generates:** Patient records with demographics, allergies, and conditions

**Data Created:**
- 100 patients with realistic Philippine names
- Mix of male/female patients (ages 0-80 years)
- Contact information (70% have phone, 30% have email)
- PhilHealth numbers (60% coverage)
- Status distribution: 90% active, 8% inactive, 2% deceased
- Patient allergies (20% of patients)
- Patient conditions (30% of patients)

**Locations:** Alinguigan, Bagong Bayan, Centro, Maligaya, San Isidro, San Jose, Santa Cruz, Santo Niño, Poblacion, Riverside

---

### 2. `seed_immunization.php`
**Generates:** Vaccines, immunization schedules, and records

**Data Created:**
- 10 standard vaccines (BCG, Hepatitis B, Pentavalent, OPV, IPV, PCV, MMR, MR, TD, HPV)
- 30 children (0-5 years old) if none exist
- Immunization schedules based on age and vaccine requirements
- Immunization records (80% completion rate)
- Status distribution: 80% completed, 15% missed, 5% scheduled
- Automatic reminders for upcoming vaccinations

**Vaccines Included:**
- BCG (birth)
- Hepatitis B (birth)
- Pentavalent/DPT-HepB-Hib (2, 4, 6 months)
- OPV/IPV (2, 4, 6 months)
- PCV (2, 4, 6 months)
- MMR (9, 12 months)
- And more...

---

### 3. `seed_maternal.php`
**Generates:** Pregnancies, prenatal visits, and postnatal visits

**Data Created:**
- 40% of eligible female patients (15-45 years) have pregnancy records
- Status distribution: 60% ongoing, 30% delivered, 10% terminated
- Prenatal visits (every 4 weeks based on gestational age)
- Postnatal visits at 1 week, 6 weeks, and 3 months after delivery
- Vital signs (BP, weight) for each visit
- Automatic reminders for upcoming prenatal visits

**Visit Details:**
- Gestational age tracking
- Blood pressure monitoring
- Weight tracking
- Clinical notes
- 80% attendance rate for postnatal visits

---

### 4. `seed_tb.php`
**Generates:** TB cases and follow-up visits

**Data Created:**
- 15% of adult patients (18+) have TB cases
- Case types: 90% drug susceptible, 10% drug resistant
- Status distribution: 50% active, 30% completed, 10% defaulted, 7% failed, 3% died
- Monthly follow-ups for drug susceptible (bi-weekly for drug resistant)
- Adherence tracking (good/poor/missed)
- Weight monitoring
- Symptom tracking
- Automatic reminders for active cases

**TB Medications Tracked:**
- Isoniazid
- Rifampicin
- Ethambutol
- Pyrazinamide

---

### 5. `seed_inventory.php`
**Generates:** Medicines, batches, and transactions

**Data Created:**
- 38 common medicines used in Philippine BHUs
- 2-4 batches per medicine
- Received transactions (stock in)
- Dispensed transactions (60-90% of received stock)
- Adjustment transactions (10% of batches)
- Expired item tracking
- Batch numbers and expiry dates

**Medicine Categories:**
- Antibiotics (Amoxicillin, Cefalexin, Cotrimoxazole, etc.)
- Analgesics/Antipyretics (Paracetamol, Ibuprofen, Mefenamic Acid)
- Antihypertensives (Amlodipine, Losartan, Atenolol)
- Antidiabetics (Metformin, Glibenclamide)
- Vitamins/Supplements (Ferrous Sulfate, Folic Acid, Vitamin C)
- Respiratory (Salbutamol, Cetirizine)
- Gastrointestinal (Omeprazole, ORS, Loperamide)
- TB Medications (Isoniazid, Rifampicin, Ethambutol, Pyrazinamide)
- Topical/Others (Betamethasone, Clotrimazole, Povidone Iodine)

---

## Usage

### Run All Seeders (Recommended)

```bash
cd C:\xampp\htdocs\HealthLogs\scripts
php seed_all.php
```

This will run all seeders in the correct order:
1. Patients
2. Immunization
3. Maternal Health
4. TB Monitoring
5. Medicine Inventory

### Run Individual Seeders

```bash
# Patients only
php seed_patients.php

# Immunization only (requires patients)
php seed_immunization.php

# Maternal health only (requires patients)
php seed_maternal.php

# TB monitoring only (requires patients)
php seed_tb.php

# Medicine inventory only
php seed_inventory.php
```

## Prerequisites

1. Database must be created and schema imported:
```bash
mysql -u root -p healthlogs < schema/healthlogs.sql
```

2. User accounts should exist (run `seed_users.php` first if needed):
```bash
php seed_users.php
```

3. PHP must be accessible from command line
4. Database connection configured in `config/db.php`

## Important Notes

### Data Volume
- **Patients:** 100 records
- **Immunization Schedules:** ~300-500 records
- **Immunization Records:** ~200-400 records
- **Pregnancies:** ~15-20 records
- **Prenatal Visits:** ~60-100 records
- **Postnatal Visits:** ~20-40 records
- **TB Cases:** ~7-10 records
- **TB Follow-ups:** ~50-100 records
- **Medicines:** 38 records
- **Medicine Batches:** ~100-150 records
- **Medicine Transactions:** ~1000-1500 records

### Execution Time
- Full seeding takes approximately 10-30 seconds
- Individual seeders: 2-10 seconds each

### Idempotency
- Seeders are NOT idempotent
- Running multiple times will create duplicate data
- Clear database before re-running:

```sql
-- Clear all data (keep structure)
TRUNCATE TABLE medicine_transactions;
TRUNCATE TABLE medicine_batches;
TRUNCATE TABLE medicines;
TRUNCATE TABLE tb_followups;
TRUNCATE TABLE tb_cases;
TRUNCATE TABLE postnatal_visits;
TRUNCATE TABLE prenatal_visits;
TRUNCATE TABLE pregnancies;
TRUNCATE TABLE immunization_records;
TRUNCATE TABLE immunization_schedule;
TRUNCATE TABLE reminders;
TRUNCATE TABLE patient_conditions;
TRUNCATE TABLE patient_allergies;
TRUNCATE TABLE visits;
TRUNCATE TABLE patients;
```

## Customization

### Adjust Data Volume

Edit the seeder files to change quantities:

```php
// In seed_patients.php
for ($i = 0; $i < 100; $i++) {  // Change 100 to desired number
```

### Modify Data Ranges

```php
// In seed_patients.php - Change age range
$age = rand(0, 80);  // Change max age

// In seed_maternal.php - Change pregnancy rate
if (rand(1, 100) <= 40) {  // Change 40 to desired percentage
```

### Add Custom Data

Add your own data arrays:

```php
// In seed_patients.php
$barangays = ['Your', 'Custom', 'Barangays'];
$lastNames = ['Your', 'Custom', 'Names'];
```

## Troubleshooting

### Error: "No eligible patients found"
**Solution:** Run `seed_patients.php` first

### Error: "Database connection failed"
**Solution:** Check `config/db.php` credentials

### Error: "Duplicate entry"
**Solution:** Clear existing data before re-running

### Error: "Foreign key constraint fails"
**Solution:** Run seeders in correct order (use `seed_all.php`)

### Low stock warnings
**Expected:** Some medicines will show low stock - this is intentional for testing

### Expiring medicines
**Expected:** Some batches will be expiring soon - this is intentional for testing

## Testing Scenarios

After seeding, you can test:

1. **Patient Management**
   - View patient list
   - Search patients
   - Edit patient records
   - View patient allergies/conditions

2. **Immunization Tracking**
   - View immunization schedules
   - Check missed vaccinations
   - Record new immunizations
   - View immunization coverage

3. **Maternal Health**
   - Track ongoing pregnancies
   - Record prenatal visits
   - View delivery records
   - Monitor postnatal care

4. **TB Monitoring**
   - View active TB cases
   - Track treatment adherence
   - Record follow-up visits
   - Monitor treatment outcomes

5. **Medicine Inventory**
   - Check stock levels
   - View low stock items
   - Track expiring medicines
   - Record dispensing transactions
   - Generate inventory reports

6. **Reminders**
   - View pending reminders
   - Test reminder notifications
   - Mark reminders as sent

7. **Forecasting**
   - Run ARIMA forecasts on visit data
   - Predict medicine demand
   - View trend analysis

## Production Warning

⚠️ **DO NOT run these seeders in production!**

These scripts are for development and testing only. They will:
- Create fake patient data
- Generate random medical records
- Populate inventory with test data

Always use real data entry processes in production environments.

## Support

For issues with seeders:
1. Check database connection
2. Verify schema is up to date
3. Check PHP error logs
4. Ensure sufficient database permissions

## License

Part of the HealthLogs project - for Barangay Health Units in the Philippines.
