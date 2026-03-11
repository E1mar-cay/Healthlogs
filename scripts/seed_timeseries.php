<?php
require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Manila');

function upsert_series(PDO $pdo, string $key, string $date, float $value): void {
    $stmt = $pdo->prepare("INSERT INTO timeseries_daily (series_key, series_date, value) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE value = VALUES(value)");
    $stmt->execute([$key, $date, $value]);
}

$days = 90;
for ($i = $days; $i >= 1; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $visits = 20 + rand(0, 15) + (int)(5 * sin($i / 6));
    $med = 80 + rand(0, 40) + (int)(10 * sin($i / 5));
    upsert_series($pdo, 'visits_total', $date, $visits);
    upsert_series($pdo, 'medicine_total', $date, $med);
}

echo "Seeded timeseries_daily for visits_total and medicine_total.\n";
