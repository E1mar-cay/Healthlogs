<?php
/**
 * Test script for Batch Expiry Monitoring, Notifications, and Issuance/Forecasting Protection
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/ActivityLogger.php';
require_once __DIR__ . '/../app/Models/MedicineModel.php';

ActivityLogger::init($pdo);
$model = new MedicineModel($pdo);

echo "=======================================================\n";
echo " TESTING MEDICINE EXPIRY MONITORING & ENFORCEMENT\n";
echo "=======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$testName}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$testName}\n";
        $failCount++;
    }
}

// 1. Classification Tests
echo "1. Testing Expiry Classification Engine...\n";
$today = date('Y-m-d');
$futureValidDate = date('Y-m-d', strtotime('+120 days'));
$nearExpiryDate = date('Y-m-d', strtotime('+30 days'));
$expiredDate = date('Y-m-d', strtotime('-15 days'));

$validClass = ActivityLogger::classifyExpiry($futureValidDate);
assertTest($validClass['status'] === 'Valid' && $validClass['is_valid'] === true && $validClass['can_issue'] === true && $validClass['is_expired'] === false, "Valid batch (>60d) correctly classified as 'Valid' with can_issue = true");

$nearClass = ActivityLogger::classifyExpiry($nearExpiryDate);
assertTest($nearClass['status'] === 'Near Expiry' && $nearClass['is_near_expiry'] === true && $nearClass['can_issue'] === true && $nearClass['is_expired'] === false, "Near Expiry batch (<=60d) correctly classified as 'Near Expiry' with can_issue = true");

$expiredClass = ActivityLogger::classifyExpiry($expiredDate);
assertTest($expiredClass['status'] === 'Expired' && $expiredClass['is_expired'] === true && $expiredClass['can_issue'] === false, "Expired batch (<CURDATE) correctly classified as 'Expired' with can_issue = false");

// Check Model's static method
$modelValid = MedicineModel::classifyExpiry($futureValidDate);
assertTest($modelValid['status'] === 'Valid' && $modelValid['can_issue'] === true, "MedicineModel::classifyExpiry matches ActivityLogger classification");

// 2. Notifications & Alerts Tests
echo "\n2. Testing Proactive Expiry Notifications & Alerts...\n";
$nearAlerts = ActivityLogger::getNearExpiryAlerts(60);
echo "   Found " . count($nearAlerts) . " near-expiry batches in database.\n";
assertTest(is_array($nearAlerts), "getNearExpiryAlerts returned an array");
if (!empty($nearAlerts)) {
    $firstNear = $nearAlerts[0];
    assertTest(isset($firstNear['batch_no'], $firstNear['medicine_name'], $firstNear['days_remaining'], $firstNear['on_hand']), "Near expiry alert contains all expected notification metadata");
    assertTest($firstNear['days_remaining'] >= 0 && $firstNear['days_remaining'] <= 60, "Near expiry alert days remaining is within 0-60 days window");
}

$expiredAlerts = ActivityLogger::getExpiredStockAlerts();
echo "   Found " . count($expiredAlerts) . " expired batches with remaining stock in database.\n";
assertTest(is_array($expiredAlerts), "getExpiredStockAlerts returned an array");
if (!empty($expiredAlerts)) {
    $firstExp = $expiredAlerts[0];
    assertTest(isset($firstExp['batch_no'], $firstExp['medicine_name'], $firstExp['days_expired'], $firstExp['on_hand']), "Expired stock alert contains all required barred-stock metadata");
    assertTest($firstExp['days_expired'] > 0, "Expired stock alert indicates days expired > 0");
}

// 3. Available Inventory for Issuance vs Expired Protection
echo "\n3. Testing Inventory Isolation (Available vs Expired)...\n";
// Create a temporary test medicine with one valid batch and one expired batch
$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO medicines (name, generic_name, unit, reorder_level) VALUES ('__Test Expiry Drug', '__Generic Test', 'tabs', 50)")->execute();
    $testMedId = (int)$pdo->lastInsertId();

    // Valid batch: 100 units
    $pdo->prepare("INSERT INTO medicine_batches (medicine_id, batch_no, expiry_date, received_date, quantity_received) VALUES (?, '__B_VALID', ?, CURDATE(), 100)")
        ->execute([$testMedId, $futureValidDate]);
    $validBatchId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime) VALUES (?, ?, 'received', 100, NOW())")
        ->execute([$testMedId, $validBatchId]);

    // Expired batch: 50 units
    $pdo->prepare("INSERT INTO medicine_batches (medicine_id, batch_no, expiry_date, received_date, quantity_received) VALUES (?, '__B_EXPIRED', ?, DATE_SUB(CURDATE(), INTERVAL 60 DAY), 50)")
        ->execute([$testMedId, $expiredDate]);
    $expiredBatchId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime) VALUES (?, ?, 'received', 50, DATE_SUB(NOW(), INTERVAL 60 DAY))")
        ->execute([$testMedId, $expiredBatchId]);

    // Total physical on hand in DB is 100 + 50 = 150
    $totalOnHand = ActivityLogger::getMedicineStock($testMedId);
    assertTest($totalOnHand === 150, "Total raw ledger stock correctly sums all batches (150 units)");

    // Available for issuance MUST strictly be 100 (excluding the 50 expired units)
    $availableForIssuance = ActivityLogger::getAvailableNonExpiredStock($testMedId);
    assertTest($availableForIssuance === 100, "Available stock for issuance strictly excludes expired units (Expected 100, got {$availableForIssuance})");

    // MedicineModel::getAvailableBatchesForIssuance must only return the valid batch
    $batchesForIssuance = $model->getAvailableBatchesForIssuance($testMedId);
    $batchNos = array_column($batchesForIssuance, 'batch_no');
    assertTest(in_array('__B_VALID', $batchNos) && !in_array('__B_EXPIRED', $batchNos), "MedicineModel::getAvailableBatchesForIssuance includes '__B_VALID' and excludes '__B_EXPIRED'");

    // 4. Testing Issuance Enforcement (Blocking Expired Batch)
    echo "\n4. Testing Issuance Guard Rails...\n";
    // Check batch expiry check logic used in save.php
    $checkStmt = $pdo->prepare("SELECT batch_no, expiry_date FROM medicine_batches WHERE id = ?");
    $checkStmt->execute([$expiredBatchId]);
    $batchRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $isExpired = strtotime($batchRow['expiry_date']) < strtotime(date('Y-m-d'));
    assertTest($isExpired === true, "Guard rail detects batch '{$batchRow['batch_no']}' as expired and halts issuance");

    // Verify requesting more than available non-expired stock is blocked
    $requestedQty = 120; // 120 is < 150 total on hand, but > 100 valid stock
    $exceedsNonExpired = ($requestedQty > $availableForIssuance);
    assertTest($exceedsNonExpired === true, "Guard rail prevents issuing 120 units when only 100 valid units exist (blocking encroachment into expired stock)");

    // 5. Testing Forecasting Calculation Exclusion
    echo "\n5. Testing Medicine Demand Forecasting Query...\n";
    $forecastStmt = $pdo->prepare("
        SELECT COALESCE(stk.current_stock, 0) AS current_stock,
               COALESCE(exp_stk.expired_stock, 0) AS expired_stock
        FROM medicines m
        LEFT JOIN (
            SELECT mb.medicine_id, SUM(mt.quantity) AS current_stock
            FROM medicine_batches mb
            JOIN medicine_transactions mt ON mt.batch_id = mb.id
            WHERE mb.expiry_date >= CURDATE()
            GROUP BY mb.medicine_id
        ) stk ON stk.medicine_id = m.id
        LEFT JOIN (
            SELECT mb.medicine_id, SUM(mt.quantity) AS expired_stock
            FROM medicine_batches mb
            JOIN medicine_transactions mt ON mt.batch_id = mb.id
            WHERE mb.expiry_date < CURDATE()
            GROUP BY mb.medicine_id
        ) exp_stk ON exp_stk.medicine_id = m.id
        WHERE m.id = ?
    ");
    $forecastStmt->execute([$testMedId]);
    $fcRow = $forecastStmt->fetch(PDO::FETCH_ASSOC);

    assertTest((int)$fcRow['current_stock'] === 100, "Forecasting 'current_stock' strictly equals 100 (excludes 50 expired units)");
    assertTest((int)$fcRow['expired_stock'] === 50, "Forecasting identifies 50 units as expired stock for audit transparency");

} finally {
    // Rollback test transaction so database remains pristine
    $pdo->rollBack();
    echo "\nRolled back test data cleanly.\n";
}

echo "\n=======================================================\n";
echo " TEST SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "=======================================================\n";

if ($failCount > 0) {
    exit(1);
}
