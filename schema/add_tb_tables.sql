-- Schema additions for Tuberculosis (TB) Monitoring Module
USE healthlogs;

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
