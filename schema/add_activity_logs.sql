-- HealthLogs Activity Logs & Audit Trail Schema
-- Provides comprehensive activity logging and inventory audit trail

CREATE TABLE IF NOT EXISTS activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  username VARCHAR(60) NULL,
  user_role VARCHAR(50) NULL,
  module VARCHAR(50) NOT NULL,
  action VARCHAR(50) NOT NULL,
  entity_type VARCHAR(60) NULL,
  entity_id VARCHAR(60) NULL,
  description VARCHAR(255) NOT NULL,
  details JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_act_created (created_at),
  INDEX idx_act_module (module),
  INDEX idx_act_action (action),
  INDEX idx_act_user (user_id),
  INDEX idx_act_entity (entity_type, entity_id),
  CONSTRAINT fk_act_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
