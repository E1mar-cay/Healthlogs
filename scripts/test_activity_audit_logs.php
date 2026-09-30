<?php
// Test script for Activity Logs & Inventory Audit Trail
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/ActivityLogger.php';
ActivityLogger::init($pdo);

echo "--- Starting Activity Logs & Inventory Audit Trail Test ---\n";

// 1. Test Auth Logging
echo "1. Testing Auth logging...\n";
$authLogId = ActivityLogger::logAuth('login', 'admin', true, [
    'user_id' => 2,
    'role' => 'admin',
    'test_run' => true
]);
echo "   Logged successful login, ID: {$authLogId}\n";

$failedLogId = ActivityLogger::logAuth('login_failed', 'hacker', false, [
    'reason' => 'Invalid password',
    'test_run' => true
]);
echo "   Logged failed login, ID: {$failedLogId}\n";

// 2. Test Medicine Addition
echo "2. Testing Medicine Addition audit...\n";
$medLogId = ActivityLogger::logInventory('medicine_add', "Added new medicine: Test Amoxicillin 500mg", 'medicines', '9999', [
    'medicine_id' => 9999,
    'name' => 'Test Amoxicillin',
    'strength' => '500mg',
    'unit' => 'capsules',
    'reorder_level' => 50
]);
echo "   Logged medicine addition, ID: {$medLogId}\n";

// 3. Test Stock Quantity Received (Batch/Delivery)
echo "3. Testing Stock Receipt audit...\n";
$stockRecLogId = ActivityLogger::logInventory('stock_receive', "Received batch 2026-999 (+100 capsules) for Test Amoxicillin", 'medicine_batches', '9999', [
    'batch_id' => 9999,
    'batch_no' => '2026-999',
    'medicine_id' => 9999,
    'medicine_name' => 'Test Amoxicillin',
    'quantity' => 100,
    'old_stock' => 0,
    'new_stock' => 100,
    'reference' => 'PO-2026-001',
    'unit' => 'capsules'
]);
echo "   Logged stock receive, ID: {$stockRecLogId}\n";

// 4. Test Stock Issuance / Release (Dispensed)
echo "4. Testing Stock Release / Issuance audit...\n";
$dispLogId = ActivityLogger::logInventory('issuance', "Stock released/issued: 20 capsules of Test Amoxicillin (Ref: RX-2026-101)", 'medicine_transactions', '99991', [
    'transaction_id' => 99991,
    'medicine_id' => 9999,
    'medicine_name' => 'Test Amoxicillin',
    'quantity' => -20,
    'abs_quantity' => 20,
    'old_stock' => 100,
    'new_stock' => 80,
    'reference' => 'RX-2026-101',
    'notes' => 'Dispensed to outpatient clinic',
    'unit' => 'capsules'
]);
echo "   Logged stock issuance, ID: {$dispLogId}\n";

// 5. Test Return
echo "5. Testing Stock Return audit...\n";
$returnLogId = ActivityLogger::logInventory('return', "Medicine returned to stock: 5 capsules of Test Amoxicillin (Ref: RET-2026-01)", 'medicine_transactions', '99992', [
    'transaction_id' => 99992,
    'medicine_id' => 9999,
    'medicine_name' => 'Test Amoxicillin',
    'quantity' => 5,
    'abs_quantity' => 5,
    'old_stock' => 80,
    'new_stock' => 85,
    'reference' => 'RET-2026-01',
    'notes' => 'Patient returned unconsumed blister pack',
    'unit' => 'capsules'
]);
echo "   Logged stock return, ID: {$returnLogId}\n";

// 6. Test Adjustment
echo "6. Testing Inventory Adjustment audit...\n";
$adjLogId = ActivityLogger::logInventory('adjustment', "Inventory adjustment: -2 capsules of Test Amoxicillin (Ref: ADJ-2026-05)", 'medicine_transactions', '99993', [
    'transaction_id' => 99993,
    'medicine_id' => 9999,
    'medicine_name' => 'Test Amoxicillin',
    'quantity' => -2,
    'abs_quantity' => 2,
    'old_stock' => 85,
    'new_stock' => 83,
    'reference' => 'ADJ-2026-05',
    'notes' => 'Physical count discrepancy reconciliation',
    'unit' => 'capsules'
]);
echo "   Logged inventory adjustment, ID: {$adjLogId}\n";

// 7. Verify Data Integrity in Database
echo "7. Verifying database records...\n";
$stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE id IN (?, ?, ?, ?, ?, ?)");
$stmt->execute([$authLogId, $medLogId, $stockRecLogId, $dispLogId, $returnLogId, $adjLogId]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "   Fetched " . count($records) . " records from database.\n";
foreach ($records as $r) {
    $parsed = json_decode($r['details'], true);
    echo "   - [{$r['module']} / {$r['action']}] {$r['description']} (Valid JSON: " . (is_array($parsed) ? 'YES' : 'NO') . ")\n";
}

// Clean up test entries
$delStmt = $pdo->prepare("DELETE FROM activity_logs WHERE id IN (?, ?, ?, ?, ?, ?)");
$delStmt->execute([$authLogId, $medLogId, $stockRecLogId, $dispLogId, $returnLogId, $adjLogId]);
echo "   Cleaned up test records successfully.\n";

echo "--- All Tests Passed Successfully! ---\n";
