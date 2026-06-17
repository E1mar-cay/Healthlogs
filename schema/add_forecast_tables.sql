-- Migration to add forecasting tables: Model Logs, ARIMA Parameters, and Forecast Results

CREATE TABLE IF NOT EXISTS forecast_runs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  series_key VARCHAR(120) NOT NULL,
  horizon INT NOT NULL,
  model_type VARCHAR(50) NOT NULL, -- 'ARIMA' or 'Fast Forecast'
  status VARCHAR(20) NOT NULL, -- 'success', 'failed'
  error_message TEXT NULL,
  training_points INT NULL,
  history_points INT NULL,
  execution_time_seconds DECIMAL(6,3) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_fr_series_key (series_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS arima_parameters (
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

CREATE TABLE IF NOT EXISTS forecast_results (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  run_id INT UNSIGNED NOT NULL,
  forecast_date DATE NOT NULL,
  forecast_value DECIMAL(12,2) NOT NULL,
  lower_bound DECIMAL(12,2) NOT NULL,
  upper_bound DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_fres_run FOREIGN KEY (run_id) REFERENCES forecast_runs(id) ON DELETE CASCADE,
  UNIQUE KEY uq_run_date (run_id, forecast_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
