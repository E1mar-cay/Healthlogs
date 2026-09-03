-- HealthLogs Database Schema
-- Generated: 2026-03-10

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS healthlogs;
CREATE DATABASE healthlogs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE healthlogs;

-- Users and roles
CREATE TABLE roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id INT UNSIGNED NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- Patients and household info
CREATE TABLE households (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  household_code VARCHAR(40) NOT NULL UNIQUE,
  head_name VARCHAR(120) NULL,
  address_line VARCHAR(255) NOT NULL,
  barangay VARCHAR(120) NOT NULL,
  city_municipality VARCHAR(120) NOT NULL,
  province VARCHAR(120) NOT NULL,
  postal_code VARCHAR(20) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE patients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  household_id BIGINT UNSIGNED NULL,
  philhealth_no VARCHAR(30) NULL,
  national_id VARCHAR(30) NULL,
  first_name VARCHAR(80) NOT NULL,
  middle_name VARCHAR(80) NULL,
  last_name VARCHAR(80) NOT NULL,
  suffix VARCHAR(10) NULL,
  sex ENUM('male','female') NOT NULL,
  birth_date DATE NOT NULL,
  blood_type VARCHAR(5) NULL,
  contact_no VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  address_line VARCHAR(255) NULL,
  barangay VARCHAR(120) NOT NULL,
  city_municipality VARCHAR(120) NOT NULL,
  province VARCHAR(120) NOT NULL,
  status ENUM('active','inactive','deceased') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_patients_household FOREIGN KEY (household_id) REFERENCES households(id)
) ENGINE=InnoDB;

CREATE TABLE patient_allergies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  allergen VARCHAR(120) NOT NULL,
  reaction VARCHAR(255) NULL,
  noted_on DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_allergies_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB;

CREATE TABLE patient_conditions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  condition_name VARCHAR(120) NOT NULL,
  status ENUM('active','resolved','chronic') NOT NULL DEFAULT 'active',
  diagnosed_on DATE NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_conditions_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB;

-- Visits (time-series ready)
CREATE TABLE visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  visit_datetime DATETIME NOT NULL,
  visit_type ENUM('general','immunization','maternal','other') NOT NULL DEFAULT 'general',
  reason VARCHAR(255) NULL,
  notes TEXT NULL,
  recorded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_visits_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_visits_user FOREIGN KEY (recorded_by) REFERENCES users(id),
  INDEX idx_visits_datetime (visit_datetime),
  INDEX idx_visits_type_datetime (visit_type, visit_datetime)
) ENGINE=InnoDB;

-- Immunization
CREATE TABLE vaccines (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(40) NOT NULL UNIQUE,
  recommended_min_age_months INT UNSIGNED NULL,
  recommended_max_age_months INT UNSIGNED NULL,
  doses_required INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE immunization_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  vaccine_id INT UNSIGNED NOT NULL,
  dose_no INT UNSIGNED NOT NULL,
  administered_on DATE NOT NULL,
  administered_at DATETIME NOT NULL,
  lot_no VARCHAR(60) NULL,
  administered_by INT UNSIGNED NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_imm_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_imm_vaccine FOREIGN KEY (vaccine_id) REFERENCES vaccines(id),
  CONSTRAINT fk_imm_user FOREIGN KEY (administered_by) REFERENCES users(id),
  INDEX idx_imm_administered_at (administered_at),
  INDEX idx_imm_vaccine_at (vaccine_id, administered_at)
) ENGINE=InnoDB;

CREATE TABLE immunization_schedule (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  vaccine_id INT UNSIGNED NOT NULL,
  dose_no INT UNSIGNED NOT NULL,
  scheduled_date DATE NOT NULL,
  status ENUM('scheduled','completed','missed','cancelled') NOT NULL DEFAULT 'scheduled',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_imm_sched_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  CONSTRAINT fk_imm_sched_vaccine FOREIGN KEY (vaccine_id) REFERENCES vaccines(id),
  INDEX idx_imm_sched_date (scheduled_date),
  INDEX idx_imm_sched_status (status)
) ENGINE=InnoDB;

-- Maternal health
CREATE TABLE pregnancies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  lmp_date DATE NOT NULL,
  edd_date DATE NOT NULL,
  gravida INT UNSIGNED NULL,
  para INT UNSIGNED NULL,
  status ENUM('ongoing','delivered','terminated') NOT NULL DEFAULT 'ongoing',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_preg_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB;

CREATE TABLE prenatal_visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pregnancy_id BIGINT UNSIGNED NOT NULL,
  visit_datetime DATETIME NOT NULL,
  gestational_age_weeks INT UNSIGNED NULL,
  bp_systolic INT UNSIGNED NULL,
  bp_diastolic INT UNSIGNED NULL,
  weight_kg DECIMAL(5,2) NULL,
  notes TEXT NULL,
  recorded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prenatal_preg FOREIGN KEY (pregnancy_id) REFERENCES pregnancies(id),
  CONSTRAINT fk_prenatal_user FOREIGN KEY (recorded_by) REFERENCES users(id),
  INDEX idx_prenatal_datetime (visit_datetime)
) ENGINE=InnoDB;

CREATE TABLE postnatal_visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pregnancy_id BIGINT UNSIGNED NOT NULL,
  visit_datetime DATETIME NOT NULL,
  mother_condition VARCHAR(255) NULL,
  baby_condition VARCHAR(255) NULL,
  notes TEXT NULL,
  recorded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_postnatal_preg FOREIGN KEY (pregnancy_id) REFERENCES pregnancies(id),
  CONSTRAINT fk_postnatal_user FOREIGN KEY (recorded_by) REFERENCES users(id),
  INDEX idx_postnatal_datetime (visit_datetime)
) ENGINE=InnoDB;



-- Medicine inventory
CREATE TABLE medicines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  generic_name VARCHAR(160) NULL,
  formulation VARCHAR(80) NULL,
  strength VARCHAR(60) NULL,
  unit VARCHAR(30) NOT NULL,
  reorder_level INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE medicine_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  medicine_id BIGINT UNSIGNED NOT NULL,
  batch_no VARCHAR(80) NOT NULL,
  expiry_date DATE NOT NULL,
  received_date DATE NOT NULL,
  quantity_received INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_batch_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id),
  UNIQUE KEY uq_batch (medicine_id, batch_no)
) ENGINE=InnoDB;

-- Inventory transactions (time-series ready)
CREATE TABLE medicine_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  medicine_id BIGINT UNSIGNED NOT NULL,
  batch_id BIGINT UNSIGNED NULL,
  transaction_datetime DATETIME NOT NULL,
  transaction_type ENUM('received','dispensed','adjustment','expired','returned') NOT NULL,
  quantity INT NOT NULL,
  reference VARCHAR(120) NULL,
  notes TEXT NULL,
  recorded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mtx_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id),
  CONSTRAINT fk_mtx_batch FOREIGN KEY (batch_id) REFERENCES medicine_batches(id),
  CONSTRAINT fk_mtx_user FOREIGN KEY (recorded_by) REFERENCES users(id),
  INDEX idx_mtx_datetime (transaction_datetime),
  INDEX idx_mtx_type_datetime (transaction_type, transaction_datetime),
  INDEX idx_mtx_medicine_datetime (medicine_id, transaction_datetime)
) ENGINE=InnoDB;

-- Reminders
CREATE TABLE reminders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  reminder_type ENUM('immunization','prenatal','postnatal','general') NOT NULL,
  due_date DATE NOT NULL,
  message VARCHAR(255) NOT NULL,
  status ENUM('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reminders_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
  INDEX idx_reminders_due (due_date),
  INDEX idx_reminders_status (status)
) ENGINE=InnoDB;

-- Generic time-series aggregates (optional, for faster ARIMA)
CREATE TABLE timeseries_daily (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  series_key VARCHAR(120) NOT NULL,
  series_date DATE NOT NULL,
  value DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_series_day (series_key, series_date),
  INDEX idx_series_date (series_date)
) ENGINE=InnoDB;

-- Forecasting table definitions: Model Logs, ARIMA Parameters, and Forecast Results
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

-- TB Monitoring
CREATE TABLE IF NOT EXISTS tb_cases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  case_number VARCHAR(40) NOT NULL UNIQUE,
  registration_date DATE NOT NULL,
  tb_type ENUM('pulmonary', 'extra_pulmonary') NOT NULL DEFAULT 'pulmonary',
  case_definition ENUM('new', 'relapse', 'treatment_after_failure', 'loss_to_follow_up', 'other') NOT NULL DEFAULT 'new',
  bacteriological_status ENUM('bacteriologically_confirmed', 'clinically_diagnosed') NOT NULL DEFAULT 'bacteriologically_confirmed',
  treatment_category ENUM('category_1', 'category_2', 'mdr_tb') NOT NULL DEFAULT 'category_1',
  treatment_start_date DATE NOT NULL,
  treatment_outcome ENUM('cured', 'treatment_completed', 'treatment_failed', 'died', 'lost_to_follow_up', 'evaluated_not') NULL DEFAULT NULL,
  treatment_outcome_date DATE NULL DEFAULT NULL,
  status ENUM('active', 'completed', 'discontinued') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tb_cases_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  INDEX idx_tb_cases_status (status),
  INDEX idx_tb_cases_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_dot_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tb_case_id BIGINT UNSIGNED NOT NULL,
  log_date DATE NOT NULL,
  status ENUM('taken', 'missed', 'supervised') NOT NULL DEFAULT 'taken',
  remarks VARCHAR(255) NULL,
  recorded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tb_dot_case FOREIGN KEY (tb_case_id) REFERENCES tb_cases(id) ON DELETE CASCADE,
  CONSTRAINT fk_tb_dot_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_tb_dot_day (tb_case_id, log_date),
  INDEX idx_tb_dot_date (log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_lab_examinations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tb_case_id BIGINT UNSIGNED NOT NULL,
  test_date DATE NOT NULL,
  test_type ENUM('sputum_smear', 'genexpert', 'chest_xray', 'culture') NOT NULL DEFAULT 'sputum_smear',
  timing ENUM('baseline', 'month_2', 'month_5', 'month_6', 'end_of_treatment', 'other') NOT NULL DEFAULT 'baseline',
  result VARCHAR(120) NOT NULL,
  laboratory_name VARCHAR(120) NULL,
  notes TEXT NULL,
  recorded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tb_lab_case FOREIGN KEY (tb_case_id) REFERENCES tb_cases(id) ON DELETE CASCADE,
  CONSTRAINT fk_tb_lab_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_tb_lab_date (test_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


