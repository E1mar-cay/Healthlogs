-- HealthLogs Database Normalization Script
-- This script creates a normalized version of the database
-- Removes redundancy and optimizes structure

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables to recreate normalized structure
DROP TABLE IF EXISTS medicine_transactions;
DROP TABLE IF EXISTS medicine_batches;
DROP TABLE IF EXISTS medicines;
DROP TABLE IF EXISTS immunization_records;
DROP TABLE IF EXISTS immunization_schedule;
DROP TABLE IF EXISTS vaccines;
DROP TABLE IF EXISTS postnatal_visits;
DROP TABLE IF EXISTS prenatal_visits;
DROP TABLE IF EXISTS pregnancies;
DROP TABLE IF EXISTS patient_conditions;
DROP TABLE IF EXISTS patient_allergies;
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS reminders;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS households;
DROP TABLE IF EXISTS timeseries_daily;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS arima_parameters;
DROP TABLE IF EXISTS forecast_results;
DROP TABLE IF EXISTS forecast_runs;

-- Create normalized tables

-- 1. Roles table (unchanged - already normalized)
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- 2. Users table (simplified)
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- 3. Households table (simplified)
CREATE TABLE households (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    household_code VARCHAR(40) NOT NULL UNIQUE,
    head_name VARCHAR(120) DEFAULT NULL,
    address_line VARCHAR(255) NOT NULL,
    barangay VARCHAR(120) NOT NULL,
    city_municipality VARCHAR(120) NOT NULL DEFAULT 'Ilagan',
    province VARCHAR(120) NOT NULL DEFAULT 'Isabela',
    postal_code VARCHAR(20) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Patients table (normalized - removed redundant location fields)
CREATE TABLE patients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    household_id BIGINT UNSIGNED DEFAULT NULL,
    philhealth_no VARCHAR(30) DEFAULT NULL,
    national_id VARCHAR(30) DEFAULT NULL,
    first_name VARCHAR(80) NOT NULL,
    middle_name VARCHAR(80) DEFAULT NULL,
    last_name VARCHAR(80) NOT NULL,
    suffix VARCHAR(10) DEFAULT NULL,
    sex ENUM('male','female') NOT NULL,
    birth_date DATE NOT NULL,
    blood_type VARCHAR(5) DEFAULT NULL,
    contact_no VARCHAR(30) DEFAULT NULL,
    email VARCHAR(120) DEFAULT NULL,
    address_line VARCHAR(255) DEFAULT NULL,
    barangay VARCHAR(120) NOT NULL,
    status ENUM('active','inactive','deceased') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id),
    INDEX idx_patients_name (last_name, first_name),
    INDEX idx_patients_birth_date (birth_date),
    INDEX idx_patients_status (status)
);

-- 5. Patient conditions (normalized)
CREATE TABLE patient_conditions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    condition_name VARCHAR(120) NOT NULL,
    status ENUM('active','resolved','chronic') NOT NULL DEFAULT 'active',
    diagnosed_on DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_conditions_patient (patient_id),
    INDEX idx_conditions_status (status)
);

-- 6. Patient allergies (normalized)
CREATE TABLE patient_allergies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    allergen VARCHAR(120) NOT NULL,
    reaction VARCHAR(255) DEFAULT NULL,
    noted_on DATE DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_allergies_patient (patient_id)
);

-- 7. Visits table (simplified)
CREATE TABLE visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    visit_datetime DATETIME NOT NULL,
    visit_type ENUM('general','immunization','maternal','other') NOT NULL DEFAULT 'general',
    reason VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX idx_visits_datetime (visit_datetime),
    INDEX idx_visits_patient_date (patient_id, visit_datetime)
);

-- 8. Vaccines table (unchanged)
CREATE TABLE vaccines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    recommended_min_age_months INT UNSIGNED DEFAULT NULL,
    recommended_max_age_months INT UNSIGNED DEFAULT NULL,
    doses_required INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 9. Immunization schedule (normalized)
CREATE TABLE immunization_schedule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    vaccine_id INT UNSIGNED NOT NULL,
    dose_no INT UNSIGNED NOT NULL,
    scheduled_date DATE NOT NULL,
    status ENUM('scheduled','completed','missed','cancelled') NOT NULL DEFAULT 'scheduled',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (vaccine_id) REFERENCES vaccines(id),
    INDEX idx_schedule_date (scheduled_date),
    INDEX idx_schedule_patient (patient_id),
    INDEX idx_schedule_status (status)
);

-- 10. Immunization records (normalized)
CREATE TABLE immunization_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    vaccine_id INT UNSIGNED NOT NULL,
    dose_no INT UNSIGNED NOT NULL,
    administered_on DATE NOT NULL,
    administered_at DATETIME NOT NULL,
    lot_no VARCHAR(60) DEFAULT NULL,
    administered_by INT UNSIGNED DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (vaccine_id) REFERENCES vaccines(id),
    FOREIGN KEY (administered_by) REFERENCES users(id),
    INDEX idx_immunization_date (administered_on),
    INDEX idx_immunization_patient (patient_id)
);

-- 11. Pregnancies table (simplified)
CREATE TABLE pregnancies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    lmp_date DATE NOT NULL,
    edd_date DATE NOT NULL,
    gravida INT UNSIGNED DEFAULT NULL,
    para INT UNSIGNED DEFAULT NULL,
    status ENUM('ongoing','delivered','terminated') NOT NULL DEFAULT 'ongoing',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_pregnancies_patient (patient_id),
    INDEX idx_pregnancies_status (status)
);

-- 12. Prenatal visits (normalized)
CREATE TABLE prenatal_visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pregnancy_id BIGINT UNSIGNED NOT NULL,
    visit_datetime DATETIME NOT NULL,
    gestational_age_weeks INT UNSIGNED DEFAULT NULL,
    bp_systolic INT UNSIGNED DEFAULT NULL,
    bp_diastolic INT UNSIGNED DEFAULT NULL,
    weight_kg DECIMAL(5,2) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pregnancy_id) REFERENCES pregnancies(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX idx_prenatal_date (visit_datetime),
    INDEX idx_prenatal_pregnancy (pregnancy_id)
);

-- 13. Postnatal visits (normalized)
CREATE TABLE postnatal_visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pregnancy_id BIGINT UNSIGNED NOT NULL,
    visit_datetime DATETIME NOT NULL,
    mother_condition VARCHAR(255) DEFAULT NULL,
    baby_condition VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pregnancy_id) REFERENCES pregnancies(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX idx_postnatal_date (visit_datetime),
    INDEX idx_postnatal_pregnancy (pregnancy_id)
);

-- 14. Medicines table (simplified)
CREATE TABLE medicines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    generic_name VARCHAR(160) DEFAULT NULL,
    formulation VARCHAR(80) DEFAULT NULL,
    strength VARCHAR(60) DEFAULT NULL,
    unit VARCHAR(30) NOT NULL,
    reorder_level INT UNSIGNED DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_medicines_name (name)
);

-- 15. Medicine batches (normalized)
CREATE TABLE medicine_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id BIGINT UNSIGNED NOT NULL,
    batch_no VARCHAR(80) NOT NULL,
    expiry_date DATE NOT NULL,
    received_date DATE NOT NULL,
    quantity_received INT UNSIGNED NOT NULL,
    current_stock INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    UNIQUE KEY uq_batch (medicine_id, batch_no),
    INDEX idx_batches_expiry (expiry_date),
    INDEX idx_batches_medicine (medicine_id)
);

-- 16. Medicine transactions (simplified)
CREATE TABLE medicine_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED DEFAULT NULL,
    transaction_datetime DATETIME NOT NULL,
    transaction_type ENUM('received','dispensed','adjustment','expired','returned') NOT NULL,
    quantity INT NOT NULL,
    reference VARCHAR(120) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES medicine_batches(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX idx_transactions_datetime (transaction_datetime),
    INDEX idx_transactions_medicine (medicine_id),
    INDEX idx_transactions_type (transaction_type)
);

-- 17. Reminders table (simplified)
CREATE TABLE reminders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    reminder_type ENUM('immunization','prenatal','postnatal','general') NOT NULL,
    due_date DATE NOT NULL,
    message VARCHAR(255) NOT NULL,
    status ENUM('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
    sent_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_reminders_due (due_date),
    INDEX idx_reminders_status (status),
    INDEX idx_reminders_patient (patient_id)
);

-- 18. Analytics table (simplified)
CREATE TABLE analytics_daily (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(120) NOT NULL,
    metric_date DATE NOT NULL,
    metric_value DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_metric_date (metric_name, metric_date),
    INDEX idx_analytics_date (metric_date),
    INDEX idx_analytics_metric (metric_name)
);
-- 19. Forecasting tables (Model Logs, ARIMA Parameters, and Forecast Results)
CREATE TABLE forecast_runs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    series_key VARCHAR(120) NOT NULL,
    horizon INT NOT NULL,
    model_type VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL,
    error_message TEXT NULL,
    training_points INT NULL,
    history_points INT NULL,
    execution_time_seconds DECIMAL(6,3) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fr_series_key (series_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE arima_parameters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_id INT UNSIGNED NOT NULL,
    model_order VARCHAR(50) NULL,
    seasonal_order VARCHAR(50) NULL,
    aic DECIMAL(12,4) NULL,
    bic DECIMAL(12,4) NULL,
    log_likelihood DECIMAL(12,4) NULL,
    sigma2 DECIMAL(12,6) NULL,
    parameters_json TEXT NULL,
    CONSTRAINT fk_ap_run FOREIGN KEY (run_id) REFERENCES forecast_runs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE forecast_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_id INT UNSIGNED NOT NULL,
    forecast_date DATE NOT NULL,
    forecast_value DECIMAL(12,2) NOT NULL,
    lower_bound DECIMAL(12,2) NOT NULL,
    upper_bound DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_fres_run FOREIGN KEY (run_id) REFERENCES forecast_runs(id) ON DELETE CASCADE,
    UNIQUE KEY uq_run_date (run_id, forecast_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Insert default data
INSERT INTO roles (id, name) VALUES 
(1, 'admin'),
(2, 'health_worker');

INSERT INTO users (role_id, username, password_hash, full_name) VALUES
(1, 'admin', '$2y$10$0mvIR7AT0u9y4q.FMLpg8.tJ77mPD7zrZCdAuTjl78r2g4UYteO22', 'System Admin'),
(2, 'bhw', '$2y$10$5jqcR2FrgaMsuOPxeWkofeIZdrzsKFSufD5InCtLJGQ2Hi.oQR/ua', 'Barangay Health Worker');

INSERT INTO vaccines (name, code, recommended_min_age_months, recommended_max_age_months, doses_required) VALUES
('BCG', 'BCG', 0, 1, 1),
('Hepatitis B', 'HEPB', 0, 1, 1),
('Pentavalent', 'PENTA', 2, 6, 3),
('Oral Polio Vaccine', 'OPV', 2, 6, 3),
('Measles, Mumps, Rubella', 'MMR', 9, 24, 2);

SELECT 'Database normalized successfully!' as Status;
