-- Clear All Seeded Data
-- This script removes all data while preserving table structure
-- Use this before re-running seeders

SET FOREIGN_KEY_CHECKS = 0;

-- Clear medicine inventory data
TRUNCATE TABLE medicine_transactions;
TRUNCATE TABLE medicine_batches;
TRUNCATE TABLE medicines;

-- Clear TB monitoring data
TRUNCATE TABLE tb_followups;
TRUNCATE TABLE tb_cases;

-- Clear maternal health data
TRUNCATE TABLE postnatal_visits;
TRUNCATE TABLE prenatal_visits;
TRUNCATE TABLE pregnancies;

-- Clear immunization data
TRUNCATE TABLE immunization_records;
TRUNCATE TABLE immunization_schedule;
TRUNCATE TABLE vaccines;

-- Clear patient data
TRUNCATE TABLE reminders;
TRUNCATE TABLE patient_conditions;
TRUNCATE TABLE patient_allergies;
TRUNCATE TABLE visits;
TRUNCATE TABLE patients;

-- Clear household data (if any)
TRUNCATE TABLE households;

-- Clear timeseries data (if any)
TRUNCATE TABLE timeseries_daily;

SET FOREIGN_KEY_CHECKS = 1;

-- Display confirmation
SELECT 'All seeded data has been cleared!' as Status;
SELECT 'You can now run seed_all.php to repopulate the database.' as NextStep;
