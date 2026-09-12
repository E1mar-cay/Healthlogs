-- Schema additions for Non-Communicable Disease (NCD) Module
USE healthlogs;

-- 1. NCD Clients Registry (Hypertension, Diabetes, CVD, Asthma, CKD, etc.)
CREATE TABLE IF NOT EXISTS ncd_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL,
  ncd_code VARCHAR(40) NOT NULL UNIQUE,
  registration_date DATE NOT NULL,
  diagnosis_type ENUM(
    'hypertension', 
    'diabetes', 
    'hypertension_diabetes', 
    'cardiovascular', 
    'asthma_copd', 
    'chronic_kidney', 
    'cancer', 
    'other'
  ) NOT NULL DEFAULT 'hypertension',
  date_diagnosed DATE NOT NULL,
  philpen_risk_level ENUM('low', 'moderate', 'high', 'very_high') NOT NULL DEFAULT 'low',
  is_smoker ENUM('never', 'current', 'former') NOT NULL DEFAULT 'never',
  is_alcohol_drinker ENUM('never', 'occasional', 'regular', 'heavy') NOT NULL DEFAULT 'never',
  maintenance_meds VARCHAR(255) NULL,
  target_bp VARCHAR(20) NULL DEFAULT '<140/90',
  target_fbs VARCHAR(20) NULL DEFAULT '<126 mg/dL',
  status ENUM('active', 'controlled', 'uncontrolled', 'inactive', 'transferred', 'deceased') NOT NULL DEFAULT 'active',
  remarks TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ncd_records_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  INDEX idx_ncd_records_status (status),
  INDEX idx_ncd_records_diagnosis (diagnosis_type),
  INDEX idx_ncd_records_patient (patient_id),
  INDEX idx_ncd_records_risk (philpen_risk_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. NCD Consultations & Vital Signs / Refills Log
CREATE TABLE IF NOT EXISTS ncd_visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ncd_record_id BIGINT UNSIGNED NOT NULL,
  visit_date DATE NOT NULL,
  bp_systolic INT UNSIGNED NULL,
  bp_diastolic INT UNSIGNED NULL,
  blood_sugar_mgdl DECIMAL(6,2) NULL,
  blood_sugar_type ENUM('fbs', 'rbs', 'hba1c', 'none') NULL DEFAULT 'none',
  weight_kg DECIMAL(5,2) NULL,
  height_cm DECIMAL(5,2) NULL,
  bmi DECIMAL(4,1) NULL,
  waist_cm DECIMAL(5,2) NULL,
  medications_dispensed VARCHAR(255) NULL,
  quantity_dispensed INT UNSIGNED NULL DEFAULT 0,
  treatment_adherence ENUM('good', 'fair', 'poor') NOT NULL DEFAULT 'good',
  findings_complaints TEXT NULL,
  management_plan TEXT NULL,
  next_appointment_date DATE NULL,
  recorded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ncd_visits_record FOREIGN KEY (ncd_record_id) REFERENCES ncd_records(id) ON DELETE CASCADE,
  CONSTRAINT fk_ncd_visits_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_ncd_visits_date (visit_date),
  INDEX idx_ncd_visits_next_app (next_appointment_date),
  INDEX idx_ncd_visits_record (ncd_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ensure Reminders enum supports 'non_communicable'
ALTER TABLE reminders MODIFY COLUMN reminder_type ENUM('immunization','prenatal','postnatal','family_planning','non_communicable','general') NOT NULL;
