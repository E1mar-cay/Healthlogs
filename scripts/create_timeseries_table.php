<?php
require_once __DIR__ . '/../config/db.php';

echo "Creating missing timeseries_daily table...\n";

$sql = "
CREATE TABLE IF NOT EXISTS timeseries_daily (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  series_key VARCHAR(120) NOT NULL,
  series_date DATE NOT NULL,
  value DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_series_day (series_key, series_date),
  INDEX idx_series_date (series_date)
) ENGINE=InnoDB;
";

try {
    $pdo->exec($sql);
    echo "✓ timeseries_daily table created successfully\n";
} catch (Exception $e) {
    echo "✗ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}