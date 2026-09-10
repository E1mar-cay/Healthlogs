<?php
require __DIR__ . '/config/db.php';

echo "=== APPLYING BARANGAY MEDICINE INVENTORY (2023 & 2024) ===\n\n";

$adminUser = $pdo->query("SELECT id FROM users WHERE role_id = 1 LIMIT 1")->fetchColumn();
$recordedBy = $adminUser ?: null;

/**
 * Helper to get or create a medicine
 */
function getOrCreateMedicine(PDO $pdo, array $med): int {
    $stmt = $pdo->prepare("SELECT id FROM medicines WHERE name = ? AND formulation = ? AND strength = ? LIMIT 1");
    $stmt->execute([$med['name'], $med['formulation'], $med['strength']]);
    $id = $stmt->fetchColumn();

    if ($id) {
        return (int)$id;
    }

    // Try finding by name and strength
    $stmt = $pdo->prepare("SELECT id FROM medicines WHERE name = ? AND strength = ? LIMIT 1");
    $stmt->execute([$med['name'], $med['strength']]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int)$id;
    }

    $insert = $pdo->prepare("
        INSERT INTO medicines (name, generic_name, formulation, strength, unit, reorder_level)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insert->execute([
        $med['name'],
        $med['generic_name'] ?? $med['name'],
        $med['formulation'] ?? 'Tablet',
        $med['strength'] ?? '',
        $med['unit'] ?? 'piece',
        $med['reorder_level'] ?? 10
    ]);
    return (int)$pdo->lastInsertId();
}

/**
 * Helper to record a batch + received transaction
 */
function recordBatch(PDO $pdo, int $medicineId, string $batchNo, string $expiryDate, string $receivedDate, int $qty, string $reference, string $notes, ?int $recordedBy) {
    // Check if batch already exists
    $stmt = $pdo->prepare("SELECT id FROM medicine_batches WHERE medicine_id = ? AND batch_no = ? LIMIT 1");
    $stmt->execute([$medicineId, $batchNo]);
    $batchId = $stmt->fetchColumn();

    if ($batchId) {
        $update = $pdo->prepare("UPDATE medicine_batches SET expiry_date = ?, received_date = ?, quantity_received = ? WHERE id = ?");
        $update->execute([$expiryDate, $receivedDate, $qty, $batchId]);

        // Check transaction
        $txStmt = $pdo->prepare("SELECT id FROM medicine_transactions WHERE batch_id = ? AND transaction_type = 'received' LIMIT 1");
        $txStmt->execute([$batchId]);
        $txId = $txStmt->fetchColumn();
        if ($txId) {
            $updateTx = $pdo->prepare("UPDATE medicine_transactions SET quantity = ?, transaction_datetime = ?, reference = ?, notes = ? WHERE id = ?");
            $updateTx->execute([$qty, $receivedDate . ' 08:30:00', $reference, $notes, $txId]);
        } else {
            $insertTx = $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes, recorded_by) VALUES (?, ?, 'received', ?, ?, ?, ?, ?)");
            $insertTx->execute([$medicineId, $batchId, $qty, $receivedDate . ' 08:30:00', $reference, $notes, $recordedBy]);
        }
        return (int)$batchId;
    }

    $insert = $pdo->prepare("
        INSERT INTO medicine_batches (medicine_id, batch_no, expiry_date, received_date, quantity_received, current_stock)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insert->execute([$medicineId, $batchNo, $expiryDate, $receivedDate, $qty, $qty]);
    $batchId = (int)$pdo->lastInsertId();

    $insertTx = $pdo->prepare("
        INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes, recorded_by)
        VALUES (?, ?, 'received', ?, ?, ?, ?, ?)
    ");
    $insertTx->execute([$medicineId, $batchId, $qty, $receivedDate . ' 08:30:00', $reference, $notes, $recordedBy]);

    return $batchId;
}

// =========================================================================
// 1. PURCHASE BY BARANGAY YEAR 2023 (Received by: Sharon B. Israel, Midwife)
// =========================================================================
$receivedDate2023 = '2023-06-15';
$notes2023 = 'Purchase by Barangay Year 2023. Received by: Sharon B. Israel, Midwife.';

$items2023 = [
    [
        'med' => ['name' => 'Carbocisteine', 'generic_name' => 'Carbocisteine', 'formulation' => 'Capsule', 'strength' => '500mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'BRGY23-CARB500', 'expiry' => '2025-06-30'
    ],
    [
        'med' => ['name' => 'Mefenamic Acid', 'generic_name' => 'Mefenamic Acid', 'formulation' => 'Capsule', 'strength' => '500mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'BRGY23-MEF500', 'expiry' => '2025-06-30'
    ],
    [
        'med' => ['name' => 'Metoprolol', 'generic_name' => 'Metoprolol Tartrate', 'formulation' => 'Tablet', 'strength' => '50mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 5, 'batch_no' => 'BRGY23-MET50', 'expiry' => '2025-08-31'
    ],
    [
        'med' => ['name' => 'Amlodipine', 'generic_name' => 'Amlodipine Besilate', 'formulation' => 'Tablet', 'strength' => '5mg', 'unit' => 'box', 'reorder_level' => 3],
        'qty' => 5, 'batch_no' => 'BRGY23-AML5', 'expiry' => '2025-09-30'
    ],
    [
        'med' => ['name' => 'Ascorbic Acid', 'generic_name' => 'Vitamin C', 'formulation' => 'Tablet', 'strength' => '500mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 2, 'batch_no' => 'BRGY23-ASC500', 'expiry' => '2025-07-31'
    ],
    [
        'med' => ['name' => 'Dehydrosol', 'generic_name' => 'Oral Rehydration Salts', 'formulation' => 'Sachet', 'strength' => 'Standard ORS', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'BRGY23-DEHYD', 'expiry' => '2025-12-31'
    ],
    [
        'med' => ['name' => 'Lagundi', 'generic_name' => 'Vitex negundo L.', 'formulation' => 'Tablet', 'strength' => '300mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'BRGY23-LAG300', 'expiry' => '2025-05-31'
    ],
    [
        'med' => ['name' => 'Lagundi Syrup', 'generic_name' => 'Vitex negundo L.', 'formulation' => 'Syrup', 'strength' => '300mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 4, 'batch_no' => 'BRGY23-LAGSYR', 'expiry' => '2025-06-30'
    ],
    [
        'med' => ['name' => 'Clonidine', 'generic_name' => 'Clonidine Hydrochloride', 'formulation' => 'Tablet', 'strength' => '75mcg', 'unit' => 'box', 'reorder_level' => 1],
        'qty' => 1, 'batch_no' => 'BRGY23-CLON', 'expiry' => '2025-10-31'
    ],
    [
        'med' => ['name' => 'Dicycloverine', 'generic_name' => 'Dicycloverine Hydrochloride', 'formulation' => 'Tablet', 'strength' => '10mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'BRGY23-DICY10', 'expiry' => '2025-11-30'
    ],
    [
        'med' => ['name' => 'Ferrous Sulfate', 'generic_name' => 'Ferrous Sulfate', 'formulation' => 'Tablet', 'strength' => '500mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'BRGY23-FER500', 'expiry' => '2025-08-31'
    ],
    [
        'med' => ['name' => 'Ascorbic Acid Syrup', 'generic_name' => 'Vitamin C', 'formulation' => 'Syrup', 'strength' => '100mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'BRGY23-ASCSYR', 'expiry' => '2025-09-30'
    ],
    [
        'med' => ['name' => 'Multivitamins Syrup', 'generic_name' => 'Multivitamins', 'formulation' => 'Syrup', 'strength' => '60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'BRGY23-MULTSYR', 'expiry' => '2025-08-31'
    ],
    [
        'med' => ['name' => 'Paracetamol (4Fever) Syrup', 'generic_name' => 'Paracetamol', 'formulation' => 'Syrup', 'strength' => '120mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 4, 'batch_no' => 'BRGY23-PAR4F60', 'expiry' => '2025-10-31'
    ],
    [
        'med' => ['name' => 'Metoclopramide Hydrochloride Syrup', 'generic_name' => 'Metoclopramide HCl', 'formulation' => 'Syrup', 'strength' => '5mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 3],
        'qty' => 4, 'batch_no' => 'BRGY23-MET60', 'expiry' => '2025-07-31'
    ],
    [
        'med' => ['name' => 'Paracetamol (4Fever) Drops', 'generic_name' => 'Paracetamol', 'formulation' => 'Drops', 'strength' => '100mg/mL 15mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 6, 'batch_no' => 'BRGY23-PAR4F15', 'expiry' => '2025-11-30'
    ],
    [
        'med' => ['name' => 'Lagundi', 'generic_name' => 'Vitex negundo L.', 'formulation' => 'Tablet', 'strength' => '600mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'BRGY23-LAG600', 'expiry' => '2025-09-30'
    ],
    [
        'med' => ['name' => 'Paracetamol (Rapidol)', 'generic_name' => 'Paracetamol', 'formulation' => 'Tablet', 'strength' => '500mg', 'unit' => 'box', 'reorder_level' => 3],
        'qty' => 2, 'batch_no' => 'BRGY23-RAP500', 'expiry' => '2025-12-31'
    ],
    [
        'med' => ['name' => 'Ancovit-B Vitamins', 'generic_name' => 'Vitamin B-Complex', 'formulation' => 'Tablet', 'strength' => 'B1+B6+B12', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 2, 'batch_no' => 'BRGY23-ANCOVB', 'expiry' => '2025-10-31'
    ],
    [
        'med' => ['name' => 'Cinnarizine', 'generic_name' => 'Cinnarizine', 'formulation' => 'Tablet', 'strength' => '25mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'BRGY23-CIN25', 'expiry' => '2025-08-31'
    ],
    [
        'med' => ['name' => 'Metoclopramide', 'generic_name' => 'Metoclopramide HCl', 'formulation' => 'Tablet', 'strength' => '10mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'BRGY23-MET10', 'expiry' => '2025-06-30'
    ],
    [
        'med' => ['name' => 'Cetirizine', 'generic_name' => 'Cetirizine Hydrochloride', 'formulation' => 'Tablet', 'strength' => '10mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'BRGY23-CET10', 'expiry' => '2025-11-30'
    ],
    [
        'med' => ['name' => 'Losartan', 'generic_name' => 'Losartan Potassium', 'formulation' => 'Tablet', 'strength' => '50mg', 'unit' => 'box', 'reorder_level' => 3],
        'qty' => 5, 'batch_no' => 'BRGY23-LOS50', 'expiry' => '2025-12-31'
    ],
    [
        'med' => ['name' => 'Ascorbic Acid Drops', 'generic_name' => 'Vitamin C', 'formulation' => 'Drops', 'strength' => '100mg/mL 15mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'BRGY23-ASCDRP', 'expiry' => '2025-10-31'
    ],
    [
        'med' => ['name' => 'Multivitamins Drops', 'generic_name' => 'Multivitamins', 'formulation' => 'Drops', 'strength' => '15mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'BRGY23-MULTDRP', 'expiry' => '2025-09-30'
    ],
    [
        'med' => ['name' => 'Cetirizine Drops', 'generic_name' => 'Cetirizine HCl', 'formulation' => 'Drops', 'strength' => '2.5mg/mL 10mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 6, 'batch_no' => 'BRGY23-CETDRP', 'expiry' => '2025-12-31'
    ],
    [
        'med' => ['name' => 'Dicycloverine Syrup', 'generic_name' => 'Dicycloverine HCl', 'formulation' => 'Syrup', 'strength' => '10mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 3],
        'qty' => 4, 'batch_no' => 'BRGY23-DICSYR', 'expiry' => '2025-07-31'
    ],
    [
        'med' => ['name' => 'Cetirizine Syrup', 'generic_name' => 'Cetirizine HCl', 'formulation' => 'Syrup', 'strength' => '5mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 4, 'batch_no' => 'BRGY23-CETSYR', 'expiry' => '2025-11-30'
    ],
];

echo "1. Processing 2023 Barangay Purchases (" . count($items2023) . " items)...\n";
foreach ($items2023 as $item) {
    $medId = getOrCreateMedicine($pdo, $item['med']);
    $batchId = recordBatch(
        $pdo,
        $medId,
        $item['batch_no'],
        $item['expiry'],
        $receivedDate2023,
        $item['qty'],
        'PURCHASE-BRGY-2023',
        $notes2023,
        $recordedBy
    );
    echo "  -> [2023] {$item['med']['name']} ({$item['med']['strength']}) | Batch: {$item['batch_no']} | Qty: {$item['qty']} {$item['med']['unit']}\n";
}

// =========================================================================
// 2. PURCHASE BY BARANGAY TANGCUL 2024 (with actual Expiration Dates)
// =========================================================================
$receivedDate2024 = '2024-03-15';
$notes2024 = 'Purchase by Barangay Tangcul Year 2024.';

$items2024 = [
    [
        'med' => ['name' => 'Losartan', 'generic_name' => 'Losartan Potassium', 'formulation' => 'Tablet', 'strength' => '50mg', 'unit' => 'box', 'reorder_level' => 3],
        'qty' => 10, 'batch_no' => 'TANGCUL24-LOS50', 'expiry' => '2026-09-30'
    ],
    [
        'med' => ['name' => 'Amlodipine', 'generic_name' => 'Amlodipine Besilate', 'formulation' => 'Tablet', 'strength' => '5mg', 'unit' => 'box', 'reorder_level' => 3],
        'qty' => 10, 'batch_no' => 'TANGCUL24-AML5', 'expiry' => '2026-09-30'
    ],
    [
        'med' => ['name' => 'Clonidine', 'generic_name' => 'Clonidine Hydrochloride', 'formulation' => 'Tablet', 'strength' => '75mcg', 'unit' => 'box', 'reorder_level' => 1],
        'qty' => 1, 'batch_no' => 'TANGCUL24-CLON', 'expiry' => '2025-12-31'
    ],
    [
        'med' => ['name' => 'Cinnarizine', 'generic_name' => 'Cinnarizine', 'formulation' => 'Tablet', 'strength' => '25mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-CIN25', 'expiry' => '2026-10-31'
    ],
    [
        'med' => ['name' => 'Cefalexin', 'generic_name' => 'Cefalexin Monohydrate', 'formulation' => 'Capsule', 'strength' => '500mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-CEF500', 'expiry' => '2027-03-31'
    ],
    [
        'med' => ['name' => 'Mefenamic Acid', 'generic_name' => 'Mefenamic Acid', 'formulation' => 'Capsule', 'strength' => '500mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-MEF500', 'expiry' => '2027-01-31'
    ],
    [
        'med' => ['name' => 'Mefenamic Acid', 'generic_name' => 'Mefenamic Acid', 'formulation' => 'Capsule', 'strength' => '250mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-MEF250', 'expiry' => '2026-12-31'
    ],
    [
        'med' => ['name' => 'Lagundi (Offlem Forte)', 'generic_name' => 'Vitex negundo L.', 'formulation' => 'Tablet', 'strength' => '600mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-LAGFORTE', 'expiry' => '2026-04-30'
    ],
    [
        'med' => ['name' => 'Lagundi (Offlem)', 'generic_name' => 'Vitex negundo L.', 'formulation' => 'Tablet', 'strength' => '600mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-LAG600', 'expiry' => '2026-01-31'
    ],
    [
        'med' => ['name' => 'Cetirizine', 'generic_name' => 'Cetirizine Hydrochloride', 'formulation' => 'Tablet', 'strength' => '10mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-CET10', 'expiry' => '2026-10-31'
    ],
    [
        'med' => ['name' => 'Paracetamol', 'generic_name' => 'Paracetamol', 'formulation' => 'Tablet', 'strength' => '500mg', 'unit' => 'box', 'reorder_level' => 3],
        'qty' => 1, 'batch_no' => 'TANGCUL24-PAR500', 'expiry' => '2027-06-30'
    ],
    [
        'med' => ['name' => 'Multivitamins', 'generic_name' => 'Multivitamins', 'formulation' => 'Capsule', 'strength' => 'Multi', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-MULTCAP', 'expiry' => '2025-09-30'
    ],
    [
        'med' => ['name' => 'Ascorbic Acid', 'generic_name' => 'Vitamin C', 'formulation' => 'Tablet', 'strength' => '500mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-ASC500', 'expiry' => '2027-04-30'
    ],
    [
        'med' => ['name' => 'Iron + Folic Acid', 'generic_name' => 'Ferrous Fumarate + Folic Acid', 'formulation' => 'Tablet', 'strength' => '60mg/250mcg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-IRONFOLIC', 'expiry' => '2026-01-31'
    ],
    [
        'med' => ['name' => 'Vitamin B1 + B6 + B12', 'generic_name' => 'Vitamin B-Complex', 'formulation' => 'Tablet', 'strength' => 'B1+B6+B12', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-VITB', 'expiry' => '2025-11-30'
    ],
    [
        'med' => ['name' => 'Dicycloverine', 'generic_name' => 'Dicycloverine Hydrochloride', 'formulation' => 'Tablet', 'strength' => '10mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-DICY10', 'expiry' => '2026-12-31'
    ],
    [
        'med' => ['name' => 'Gauze Sponge', 'generic_name' => 'Sterile Gauze Sponge', 'formulation' => 'Supply', 'strength' => '4x4', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-GAUZE', 'expiry' => '2027-09-30'
    ],
    [
        'med' => ['name' => 'Povidone Iodine', 'generic_name' => 'Povidone Iodine', 'formulation' => 'Solution', 'strength' => '10% 30mL', 'unit' => 'bottle', 'reorder_level' => 3],
        'qty' => 3, 'batch_no' => 'TANGCUL24-POV30', 'expiry' => '2026-06-30'
    ],
    [
        'med' => ['name' => 'Amoxicillin Drops', 'generic_name' => 'Amoxicillin', 'formulation' => 'Drops', 'strength' => '100mg/mL 10mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'TANGCUL24-AMXDRP', 'expiry' => '2027-02-28'
    ],
    [
        'med' => ['name' => 'Ascorbic Acid Drops', 'generic_name' => 'Vitamin C', 'formulation' => 'Drops', 'strength' => '100mg/mL 15mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'TANGCUL24-ASCDRP', 'expiry' => '2025-11-30'
    ],
    [
        'med' => ['name' => 'Cetirizine Drops', 'generic_name' => 'Cetirizine HCl', 'formulation' => 'Drops', 'strength' => '2.5mg/mL 10mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'TANGCUL24-CETDRP', 'expiry' => '2025-12-31'
    ],
    [
        'med' => ['name' => 'Carbocisteine Drops', 'generic_name' => 'Carbocisteine', 'formulation' => 'Drops', 'strength' => '50mg/mL 15mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'TANGCUL24-CARBDRP', 'expiry' => '2025-11-30'
    ],
    [
        'med' => ['name' => 'Paracetamol Drops', 'generic_name' => 'Paracetamol', 'formulation' => 'Drops', 'strength' => '100mg/mL 15mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'TANGCUL24-PARDRP', 'expiry' => '2025-12-31'
    ],
    [
        'med' => ['name' => 'Micropore Tape', 'generic_name' => 'Surgical Paper Tape', 'formulation' => 'Supply', 'strength' => '1 inch', 'unit' => 'roll', 'reorder_level' => 2],
        'qty' => 1, 'batch_no' => 'TANGCUL24-MICROPORE', 'expiry' => '2026-11-30'
    ],
    [
        'med' => ['name' => 'Amoxicillin Suspension', 'generic_name' => 'Amoxicillin', 'formulation' => 'Suspension', 'strength' => '250mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'TANGCUL24-AMXSYR', 'expiry' => '2025-03-31'
    ],
    [
        'med' => ['name' => 'Cetirizine Syrup', 'generic_name' => 'Cetirizine HCl', 'formulation' => 'Syrup', 'strength' => '5mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'TANGCUL24-CETSYR', 'expiry' => '2027-09-30'
    ],
    [
        'med' => ['name' => 'Paracetamol Syrup', 'generic_name' => 'Paracetamol', 'formulation' => 'Syrup', 'strength' => '120mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'TANGCUL24-PARSYR', 'expiry' => '2026-01-31'
    ],
    [
        'med' => ['name' => 'Lagundi (Offlem) Syrup', 'generic_name' => 'Vitex negundo L.', 'formulation' => 'Syrup', 'strength' => '300mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'TANGCUL24-LAGSYR', 'expiry' => '2027-01-31'
    ],
    [
        'med' => ['name' => 'Ascorbic Acid (Myrevit-C)', 'generic_name' => 'Vitamin C', 'formulation' => 'Syrup', 'strength' => '100mg/5mL 60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 10, 'batch_no' => 'TANGCUL24-MYREVC', 'expiry' => '2025-12-31'
    ],
    [
        'med' => ['name' => 'Multivitamins (Multilem PNT)', 'generic_name' => 'Multivitamins', 'formulation' => 'Syrup', 'strength' => '60mL', 'unit' => 'bottle', 'reorder_level' => 5],
        'qty' => 4, 'batch_no' => 'TANGCUL24-MULTILEM', 'expiry' => '2027-01-31'
    ],
    [
        'med' => ['name' => 'Iron + Folic Acid', 'generic_name' => 'Ferrous Fumarate + Folic Acid', 'formulation' => 'Tablet', 'strength' => '600mg', 'unit' => 'box', 'reorder_level' => 2],
        'qty' => 2, 'batch_no' => 'TANGCUL24-IRON600', 'expiry' => '2027-01-31'
    ],
];

echo "\n2. Processing 2024 Barangay Tangcul Purchases (" . count($items2024) . " items)...\n";
foreach ($items2024 as $item) {
    $medId = getOrCreateMedicine($pdo, $item['med']);
    $batchId = recordBatch(
        $pdo,
        $medId,
        $item['batch_no'],
        $item['expiry'],
        $receivedDate2024,
        $item['qty'],
        'PURCHASE-BRGY-TANGCUL-2024',
        $notes2024,
        $recordedBy
    );
    echo "  -> [2024] {$item['med']['name']} ({$item['med']['strength']}) | Batch: {$item['batch_no']} | Qty: {$item['qty']} {$item['med']['unit']} | Exp: {$item['expiry']}\n";
}

echo "\n=== ALL MEDICINE INVENTORY ITEMS SUCCESSFULLY APPLIED ===\n";
