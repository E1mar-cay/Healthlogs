<?php
/**
 * Seed deterministic synthetic daily data for forecasting demonstrations.
 *
 * This only writes demo-prefixed series to timeseries_daily; it does not
 * create or overwrite operational visit and inventory data.
 */

require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Manila');

$startDate = new DateTimeImmutable('2023-01-01');
$endDate = new DateTimeImmutable('today');
$days = (int) $startDate->diff($endDate)->days;

$upsert = $pdo->prepare(
    'INSERT INTO timeseries_daily (series_key, series_date, value)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE value = VALUES(value)'
);

$noiseFor = static function (string $seriesKey, string $date): int {
    $hash = unpack('Nvalue', hash('crc32b', $seriesKey . ':' . $date, true));

    return ($hash['value'] % 9) - 4;
};

$pdo->beginTransaction();

try {
    $rowsSeeded = 0;

    for ($dayIndex = 0; $dayIndex <= $days; $dayIndex++) {
        $date = $startDate->modify("+{$dayIndex} days");
        $dateString = $date->format('Y-m-d');
        $weekday = (int) $date->format('N');
        $weekendFactor = $weekday >= 6 ? 0.55 : 1.0;
        $seasonality = sin(2 * M_PI * $dayIndex / 365.25);
        $trend = $dayIndex;

        $visits = max(
            0,
            (int) round(
                (17 + 0.006 * $trend + 2.5 * $seasonality + $noiseFor('visits_total', $dateString))
                * $weekendFactor
            )
        );
        $medicineDemand = max(
            0,
            (int) round(
                (86 + 0.015 * $trend + 10 * $seasonality + 2 * $noiseFor('medicine_total', $dateString))
                * ($weekday >= 6 ? 0.85 : 1.0)
            )
        );

        $upsert->execute(['demo_visits_total', $dateString, $visits]);
        $upsert->execute(['demo_medicine_total', $dateString, $medicineDemand]);
        $rowsSeeded += 2;
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Failed to seed forecasting data: {$e->getMessage()}\n");
    exit(1);
}

echo "Seeded {$rowsSeeded} synthetic daily values for demo_visits_total and demo_medicine_total ";
echo "from {$startDate->format('Y-m-d')} through {$endDate->format('Y-m-d')}.\n";
echo "This seeder only modifies demo-prefixed timeseries_daily rows and is safe to re-run.\n";
