-- Data Migration Script for Normalized Database
-- This script migrates existing data to the normalized structure

SET FOREIGN_KEY_CHECKS = 0;

-- Create temporary backup tables
CREATE TABLE patients_backup AS SELECT * FROM patients;
CREATE TABLE medicine_batches_backup AS SELECT * FROM medicine_batches;
CREATE TABLE medicine_transactions_backup AS SELECT * FROM medicine_transactions;

-- Update patients table structure (remove redundant city/province columns)
ALTER TABLE patients 
DROP COLUMN city_municipality,
DROP COLUMN province;

-- Add current_stock column to medicine_batches if not exists
ALTER TABLE medicine_batches 
ADD COLUMN current_stock INT UNSIGNED NOT NULL DEFAULT 0 AFTER quantity_received;

-- Calculate current stock for each batch
UPDATE medicine_batches mb
SET current_stock = (
    SELECT COALESCE(
        mb.quantity_received + COALESCE(SUM(
            CASE 
                WHEN mt.transaction_type = 'received' THEN mt.quantity
                WHEN mt.transaction_type = 'returned' THEN mt.quantity
                ELSE -ABS(mt.quantity)
            END
        ), 0), 
        mb.quantity_received
    )
    FROM medicine_transactions mt 
    WHERE mt.batch_id = mb.id
);

-- Rename timeseries_daily to analytics_daily for better naming
RENAME TABLE timeseries_daily TO analytics_daily;

-- Update analytics_daily column names
ALTER TABLE analytics_daily 
CHANGE COLUMN series_key metric_name VARCHAR(120) NOT NULL,
CHANGE COLUMN series_date metric_date DATE NOT NULL,
CHANGE COLUMN value metric_value DECIMAL(12,2) NOT NULL;

-- Update unique constraint
ALTER TABLE analytics_daily 
DROP INDEX uq_series_day,
ADD UNIQUE KEY uq_metric_date (metric_name, metric_date);

-- Clean up any invalid reminder types
DELETE FROM reminders WHERE reminder_type NOT IN ('immunization','prenatal','postnatal','general');

-- Update empty reminder types to 'general'
UPDATE reminders SET reminder_type = 'general' WHERE reminder_type = '';

-- Add indexes for better performance
ALTER TABLE patients 
ADD INDEX idx_patients_name (last_name, first_name),
ADD INDEX idx_patients_birth_date (birth_date),
ADD INDEX idx_patients_status (status);

ALTER TABLE visits
ADD INDEX idx_visits_patient_date (patient_id, visit_datetime);

ALTER TABLE immunization_schedule
ADD INDEX idx_schedule_patient (patient_id),
ADD INDEX idx_schedule_status (status);

ALTER TABLE immunization_records
ADD INDEX idx_immunization_patient (patient_id);

ALTER TABLE pregnancies
ADD INDEX idx_pregnancies_patient (patient_id),
ADD INDEX idx_pregnancies_status (status);

ALTER TABLE prenatal_visits
ADD INDEX idx_prenatal_pregnancy (pregnancy_id);

ALTER TABLE postnatal_visits
ADD INDEX idx_postnatal_pregnancy (pregnancy_id);

ALTER TABLE medicine_batches
ADD INDEX idx_batches_medicine (medicine_id);

ALTER TABLE medicine_transactions
ADD INDEX idx_transactions_medicine (medicine_id),
ADD INDEX idx_transactions_type (transaction_type);

ALTER TABLE reminders
ADD INDEX idx_reminders_patient (patient_id);

ALTER TABLE medicines
ADD INDEX idx_medicines_name (name);

-- Drop backup tables
DROP TABLE patients_backup;
DROP TABLE medicine_batches_backup;
DROP TABLE medicine_transactions_backup;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Database migration completed successfully!' as Status;