<?php
require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Manila');

$days = 365;
$start = date('Y-m-d', strtotime("-{$days} days"));
$end = date('Y-m-d');

// Visits: daily count
$sqlVisits = "SELECT DATE(visit_datetime) AS d, COUNT(*) AS c
    FROM visits
    WHERE visit_datetime >= ? AND visit_datetime < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY DATE(visit_datetime)
    ORDER BY d";
$stmt = $pdo->prepare($sqlVisits);
$stmt->execute([$start, $end]);
$visits = $stmt->fetchAll();

// Medicine demand: daily sum of dispensed quantity
$sqlMed = "SELECT DATE(transaction_datetime) AS d, SUM(quantity) AS qty
    FROM medicine_transactions
    WHERE transaction_type = 'dispensed'
      AND transaction_datetime >= ? AND transaction_datetime < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY DATE(transaction_datetime)
    ORDER BY d";
$stmt = $pdo->prepare($sqlMed);
$stmt->execute([$start, $end]);
$meds = $stmt->fetchAll();

$upsert = $pdo->prepare("INSERT INTO timeseries_daily (series_key, series_date, value)
    VALUES (?,?,?) ON DUPLICATE KEY UPDATE value = VALUES(value)");

foreach ($visits as $v) {
    $upsert->execute(['visits_total', $v['d'], (float)$v['c']]);
}
foreach ($meds as $m) {
    $upsert->execute(['medicine_total', $m['d'], (float)$m['qty']]);
}

echo "Built timeseries_daily from visits and medicine_transactions (last {$days} days).\n";
