-- Schema additions for Family Planning (FP) Module
-- Replaces Tuberculosis (TB) Monitoring
USE healthlogs;

-- 1. Family Planning Clients Registry
CREATE TABLE IF NOT EXISTS fp_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  client_code VARCHAR(40) NOT NULL UNIQUE,
  registration_date DATE NOT NULL,
  client_type ENUM('new_acceptor', 'current_user', 'restart', 'changing_method', 'changing_clinic') NOT NULL DEFAULT 'new_acceptor',
  method_accepted ENUM(
    'pills_coc', 
    'pills_pop', 
    'injectable_dmpa', 
    'implant', 
    'iud_interval', 
    'iud_postpartum', 
    'condom', 
    'btl', 
    'nsv', 
    'natural_lam', 
    'natural_sdm', 
    'natural_stm', 
    'other'
  ) NOT NULL DEFAULT 'pills_coc',
  source ENUM('public', 'private') NOT NULL DEFAULT 'public',
  previous_method VARCHAR(80) NULL,
  partner_name VARCHAR(120) NULL,
  partner_occupation VARCHAR(100) NULL,
  num_living_children INT UNSIGNED DEFAULT 0,
  plan_more_children ENUM('yes', 'no', 'undecided') DEFAULT 'undecided',
  drop_out_reason ENUM('side_effects', 'medical_reasons', 'desire_pregnancy', 'loss_to_follow_up', 'relocation', 'other') NULL DEFAULT NULL,
  drop_out_date DATE NULL DEFAULT NULL,
  status ENUM('active', 'inactive', 'dropped_out') NOT NULL DEFAULT 'active',
  remarks TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_fp_records_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  INDEX idx_fp_records_status (status),
  INDEX idx_fp_records_method (method_accepted),
  INDEX idx_fp_records_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Family Planning Consultations & Resupply Visits
CREATE TABLE IF NOT EXISTS fp_visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fp_record_id BIGINT UNSIGNED NOT NULL,
  visit_date DATE NOT NULL,
  method_prescribed VARCHAR(80) NOT NULL,
  quantity_dispensed INT UNSIGNED NULL DEFAULT 1,
  next_appointment_date DATE NULL,
  bp_systolic INT UNSIGNED NULL,
  bp_diastolic INT UNSIGNED NULL,
  weight_kg DECIMAL(5,2) NULL,
  findings_complaints TEXT NULL,
  recorded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fp_visits_record FOREIGN KEY (fp_record_id) REFERENCES fp_records(id) ON DELETE CASCADE,
  CONSTRAINT fk_fp_visits_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_fp_visits_date (visit_date),
  INDEX idx_fp_visits_next_app (next_appointment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Update Reminders Table Enum and convert existing TB reminders to Family Planning
UPDATE reminders SET reminder_type = 'general' WHERE reminder_type = 'tb_monitoring';
ALTER TABLE reminders MODIFY COLUMN reminder_type ENUM('immunization','prenatal','postnatal','family_planning','general') NOT NULL;

-- 4. Clean up legacy TB tables
DROP TABLE IF EXISTS tb_dot_logs;
DROP TABLE IF EXISTS tb_lab_examinations;
DROP TABLE IF EXISTS tb_cases;
