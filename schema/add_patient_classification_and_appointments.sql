-- Schema migration: Patient Classification, Health Services List, and Appointments Module
USE healthlogs;

-- 1. Extend patients table with classification and RHU priority fields
ALTER TABLE patients 
  ADD COLUMN IF NOT EXISTS classification ENUM(
    'infant',
    'under_five',
    'school_age',
    'adolescent',
    'pregnant',
    'postpartum',
    'adult',
    'senior',
    'pwd',
    'indigent_4ps'
  ) NOT NULL DEFAULT 'adult' AFTER status,
  ADD COLUMN IF NOT EXISTS is_pwd TINYINT(1) NOT NULL DEFAULT 0 AFTER classification,
  ADD COLUMN IF NOT EXISTS is_4ps TINYINT(1) NOT NULL DEFAULT 0 AFTER is_pwd,
  ADD COLUMN IF NOT EXISTS civil_status ENUM('single', 'married', 'widowed', 'separated', 'child') NOT NULL DEFAULT 'single' AFTER is_4ps,
  ADD COLUMN IF NOT EXISTS philhealth_category ENUM('indigent_4ps', 'formal_economy', 'informal_economy', 'senior_citizen', 'lifetime_member', 'non_member') NOT NULL DEFAULT 'non_member' AFTER civil_status,
  ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(120) NULL AFTER philhealth_category,
  ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(30) NULL AFTER emergency_contact_name;

-- 2. Health Services Catalog (Barangay Rural Health Unit Services)
CREATE TABLE IF NOT EXISTS health_services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_code VARCHAR(50) NOT NULL UNIQUE,
  service_name VARCHAR(120) NOT NULL,
  category ENUM(
    'immunization',
    'maternal',
    'family_planning',
    'ncd',
    'child_health',
    'general_consultation',
    'infectious_disease',
    'wellness'
  ) NOT NULL,
  description TEXT NULL,
  target_classification VARCHAR(120) NULL DEFAULT 'all',
  estimated_duration_minutes INT UNSIGNED DEFAULT 15,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_hs_category (category),
  INDEX idx_hs_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Health Services
INSERT INTO health_services (service_code, service_name, category, description, target_classification, estimated_duration_minutes)
VALUES
('EPI_VACCINE', 'Expanded Program on Immunization (EPI Routine Vaccines)', 'immunization', 'Routine childhood immunizations: BCG, HepB, Pentavalent (DPT-HepB-Hib), OPV, IPV, PCV, and MMR.', 'infant,under_five', 15),
('PRENATAL_CARE', 'Maternal & Prenatal Health Check-up', 'maternal', 'Comprehensive prenatal examination, gestational age, fundal height, fetal heart tone, tetanus toxoid, and micronutrient supplementation.', 'pregnant', 20),
('POSTNATAL_CARE', 'Postnatal & Lactation Counseling', 'maternal', 'Postpartum maternal assessment, lochia check, episiotomy/wound evaluation, and exclusive breastfeeding counseling.', 'postpartum', 20),
('FAMILY_PLANNING', 'Family Planning Counseling & Contraceptive Dispensing', 'family_planning', 'Reproductive health counseling, oral contraceptive pills (COC/POP), DMPA injectables, condoms, and IUD services.', 'adult,adolescent', 20),
('CHILD_NUTRITION', 'Under-5 Growth Monitoring & Nutrition', 'child_health', 'Weight-for-age, height-for-age, MUAC screening, biannual Vitamin A supplementation, and routine deworming.', 'infant,under_five', 15),
('NCD_CHECKUP', 'Hypertension & Diabetes Screening & Maintenance', 'ncd', 'Blood pressure monitoring, random/fasting blood sugar testing, CVD risk scoring, lifestyle counseling, and maintenance medicine refill.', 'senior,adult', 20),
('GEN_CONSULTATION', 'General Outpatient Medical Consultation', 'general_consultation', 'Primary healthcare consultation, assessment of acute illnesses (cough, colds, fever, diarrhea), physical examination, and treatment.', 'all', 15),
('TB_DOTS', 'TB-DOTS Screening & Respiratory Assessment', 'infectious_disease', 'Screening for persistent cough >=2 weeks, sputum specimen collection/referral, DOTS adherence monitoring, and contact tracing.', 'all', 20),
('WOUND_CARE', 'First Aid, Wound Dressing & Minor Injury Care', 'general_consultation', 'Cleaning, disinfection, dressing of minor wounds/lacerations, burn management, and tetanus prophylaxis referral.', 'all', 15),
('SENIOR_WELLNESS', 'Senior Citizen Preventive Health & Wellness', 'wellness', 'Geriatric health assessment, visual screening, fall prevention, cognitive screening, and annual flu/pneumococcal vaccine coordination.', 'senior', 25)
ON DUPLICATE KEY UPDATE 
  service_name = VALUES(service_name),
  category = VALUES(category),
  description = VALUES(description),
  target_classification = VALUES(target_classification);

-- 3. Patient Appointments Table
CREATE TABLE IF NOT EXISTS patient_appointments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  appointment_code VARCHAR(40) NOT NULL UNIQUE,
  patient_id BIGINT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL DEFAULT '08:30:00',
  status ENUM('scheduled', 'rescheduled', 'completed', 'cancelled', 'missed') NOT NULL DEFAULT 'scheduled',
  reason VARCHAR(255) NULL,
  reschedule_reason VARCHAR(255) NULL,
  previous_appointment_date DATE NULL,
  cancellation_reason VARCHAR(255) NULL,
  completed_at DATETIME NULL,
  clinical_notes TEXT NULL,
  assigned_personnel VARCHAR(120) NULL DEFAULT 'Barangay Health Worker',
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_apt_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_apt_service FOREIGN KEY (service_id) REFERENCES health_services(id) ON DELETE RESTRICT,
  CONSTRAINT fk_apt_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_apt_date (appointment_date),
  INDEX idx_apt_status (status),
  INDEX idx_apt_patient (patient_id),
  INDEX idx_apt_service (service_id),
  INDEX idx_apt_date_status (appointment_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
