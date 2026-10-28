<?php
require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Manila');

$days = 365;
$start = date('Y-m-d', strtotime("-{$days} days"));
$end = date('Y-m-d');

// Unified Visits: daily count across all clinical modules (general, prenatal, postnatal, immunization, FP, NCD)
$sqlVisits = "SELECT DATE(v_date) AS d, COUNT(*) AS c
    FROM (
        SELECT visit_datetime AS v_date FROM visits
        UNION ALL
        SELECT visit_datetime AS v_date FROM prenatal_visits
        UNION ALL
        SELECT visit_datetime AS v_date FROM postnatal_visits
        UNION ALL
        SELECT CAST(administered_on AS DATETIME) AS v_date FROM immunization_records
        UNION ALL
        SELECT CAST(visit_date AS DATETIME) AS v_date FROM fp_visits
        UNION ALL
        SELECT CAST(visit_date AS DATETIME) AS v_date FROM ncd_visits
    ) all_v
    WHERE v_date >= ? AND v_date < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY DATE(v_date)
    ORDER BY d";
$stmt = $pdo->prepare($sqlVisits);
$stmt->execute([$start, $end]);
$visits = $stmt->fetchAll();

// Medicine demand: daily sum of ABS(dispensed quantity)
$sqlMed = "SELECT DATE(transaction_datetime) AS d, SUM(ABS(quantity)) AS qty
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
