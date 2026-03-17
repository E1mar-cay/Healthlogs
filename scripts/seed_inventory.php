<?php
/**
 * Medicine Inventory Module Seeder
 * Generates medicines, batches, and transactions
 */

require_once __DIR__ . '/../config/db.php';

echo "Seeding medicine inventory module...\n";

// Common medicines in Philippine BHUs
$medicines = [
    // Antibiotics
    ['Amoxicillin', 'Amoxicillin', 'Capsule', '500mg', 'capsule', 200],
    ['Amoxicillin', 'Amoxicillin', 'Suspension', '250mg/5mL', 'bottle', 50],
    ['Cefalexin', 'Cefalexin', 'Capsule', '500mg', 'capsule', 150],
    ['Cotrimoxazole', 'Sulfamethoxazole + Trimethoprim', 'Tablet', '400mg+80mg', 'tablet', 200],
    ['Erythromycin', 'Erythromycin', 'Tablet', '500mg', 'tablet', 100],
    
    // Analgesics/Antipyretics
    ['Paracetamol', 'Paracetamol', 'Tablet', '500mg', 'tablet', 500],
    ['Paracetamol', 'Paracetamol', 'Syrup', '120mg/5mL', 'bottle', 100],
    ['Ibuprofen', 'Ibuprofen', 'Tablet', '400mg', 'tablet', 300],
    ['Mefenamic Acid', 'Mefenamic Acid', 'Capsule', '500mg', 'capsule', 200],
    
    // Antihypertensives
    ['Amlodipine', 'Amlodipine', 'Tablet', '5mg', 'tablet', 300],
    ['Amlodipine', 'Amlodipine', 'Tablet', '10mg', 'tablet', 300],
    ['Losartan', 'Losartan', 'Tablet', '50mg', 'tablet', 250],
    ['Atenolol', 'Atenolol', 'Tablet', '50mg', 'tablet', 200],
    
    // Antidiabetics
    ['Metformin', 'Metformin', 'Tablet', '500mg', 'tablet', 400],
    ['Metformin', 'Metformin', 'Tablet', '850mg', 'tablet', 300],
    ['Glibenclamide', 'Glibenclamide', 'Tablet', '5mg', 'tablet', 200],
    
    // Vitamins/Supplements
    ['Ferrous Sulfate', 'Ferrous Sulfate', 'Tablet', '325mg', 'tablet', 500],
    ['Folic Acid', 'Folic Acid', 'Tablet', '5mg', 'tablet', 300],
    ['Vitamin B Complex', 'B-Complex', 'Capsule', 'Multi', 'capsule', 250],
    ['Ascorbic Acid', 'Vitamin C', 'Tablet', '500mg', 'tablet', 400],
    ['Multivitamins', 'Multivitamins', 'Capsule', 'Multi', 'capsule', 300],
    
    // Respiratory
    ['Salbutamol', 'Salbutamol', 'Tablet', '2mg', 'tablet', 150],
    ['Salbutamol', 'Salbutamol', 'Nebule', '2.5mg/2.5mL', 'nebule', 100],
    ['Cetirizine', 'Cetirizine', 'Tablet', '10mg', 'tablet', 300],
    ['Carbocisteine', 'Carbocisteine', 'Capsule', '500mg', 'capsule', 200],
    
    // Gastrointestinal
    ['Omeprazole', 'Omeprazole', 'Capsule', '20mg', 'capsule', 200],
    ['Aluminum Hydroxide', 'Aluminum Hydroxide', 'Suspension', '200mg/5mL', 'bottle', 80],
    ['Loperamide', 'Loperamide', 'Capsule', '2mg', 'capsule', 150],
    ['ORS', 'Oral Rehydration Salts', 'Sachet', '27.9g', 'sachet', 500],
    ['Zinc Sulfate', 'Zinc Sulfate', 'Tablet', '20mg', 'tablet', 300],
    
    // TB Medications
    ['Isoniazid', 'Isoniazid', 'Tablet', '300mg', 'tablet', 200],
    ['Rifampicin', 'Rifampicin', 'Capsule', '450mg', 'capsule', 150],
    ['Ethambutol', 'Ethambutol', 'Tablet', '400mg', 'tablet', 150],
    ['Pyrazinamide', 'Pyrazinamide', 'Tablet', '500mg', 'tablet', 150],
    
    // Topical/Others
    ['Betamethasone', 'Betamethasone', 'Cream', '0.1%', 'tube', 50],
    ['Clotrimazole', 'Clotrimazole', 'Cream', '1%', 'tube', 60],
    ['Povidone Iodine', 'Povidone Iodine', 'Solution', '10%', 'bottle', 80],
    ['Alcohol', 'Ethyl Alcohol', 'Solution', '70%', 'bottle', 100],
];

echo "Seeding medicines...\n";

$medicineIds = [];
$stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, formulation, strength, unit, reorder_level) VALUES (?, ?, ?, ?, ?, ?)");

$pdo->beginTransaction();
try {
    foreach ($medicines as $medicine) {
        $stmt->execute($medicine);
        $medicineIds[] = $pdo->lastInsertId();
    }
    $pdo->commit();
    echo "✓ Successfully seeded " . count($medicines) . " medicines\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Error seeding medicines: " . $e->getMessage() . "\n";
    exit(1);
}

// Generate batches and transactions
echo "Seeding medicine batches and transactions...\n";

$batchCount = 0;
$transactionCount = 0;

$pdo->beginTransaction();
try {
    foreach ($medicineIds as $medicineId) {
        // Get medicine details
        $medicine = $pdo->query("SELECT * FROM medicines WHERE id = $medicineId")->fetch();
        
        // Generate 2-4 batches per medicine
        $numBatches = rand(2, 4);
        
        for ($b = 0; $b < $numBatches; $b++) {
            // Generate batch number
            $batchNo = 'BATCH-' . date('Y') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            
            // Received date (1-12 months ago)
            $monthsAgo = rand(1, 12);
            $receivedDate = date('Y-m-d', strtotime("-$monthsAgo months"));
            
            // Expiry date (1-3 years from received date)
            $yearsValid = rand(1, 3);
            $expiryDate = date('Y-m-d', strtotime($receivedDate . " +$yearsValid years"));
            
            // Quantity received (based on reorder level)
            $quantityReceived = $medicine['reorder_level'] * rand(2, 5);
            
            // Insert batch
            $stmt = $pdo->prepare("
                INSERT INTO medicine_batches (medicine_id, batch_no, expiry_date, received_date, quantity_received)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $medicineId,
                $batchNo,
                $expiryDate,
                $receivedDate,
                $quantityReceived
            ]);
            
            $batchId = $pdo->lastInsertId();
            $batchCount++;
            
            // Create received transaction
            $stmt = $pdo->prepare("
                INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_datetime, transaction_type, quantity, reference, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $medicineId,
                $batchId,
                $receivedDate . ' 09:00:00',
                'received',
                $quantityReceived,
                'PO-' . date('Y', strtotime($receivedDate)) . '-' . rand(1000, 9999),
                'Stock received from supplier'
            ]);
            $transactionCount++;
            
            // Generate dispensed transactions (60-90% of received quantity)
            $remainingQty = $quantityReceived;
            $dispensedPercent = rand(60, 90) / 100;
            $totalDispensed = floor($quantityReceived * $dispensedPercent);
            
            // Create 5-15 dispensing transactions
            $numDispensings = rand(5, 15);
            $avgDispensed = floor($totalDispensed / $numDispensings);
            
            $currentDate = new DateTime($receivedDate);
            $today = new DateTime();
            
            for ($d = 0; $d < $numDispensings && $remainingQty > 0; $d++) {
                // Random days between dispensings (3-20 days)
                $daysLater = rand(3, 20);
                $currentDate->modify("+$daysLater days");
                
                // Don't create future transactions
                if ($currentDate > $today) {
                    break;
                }
                
                // Quantity to dispense
                $qtyToDispense = min(rand($avgDispensed - 10, $avgDispensed + 10), $remainingQty);
                if ($qtyToDispense <= 0) {
                    break;
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_datetime, transaction_type, quantity, reference, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $medicineId,
                    $batchId,
                    $currentDate->format('Y-m-d H:i:s'),
                    'dispensed',
                    -$qtyToDispense, // Negative for dispensed
                    'RX-' . $currentDate->format('Ymd') . '-' . rand(100, 999),
                    'Dispensed to patients'
                ]);
                $transactionCount++;
                
                $remainingQty -= $qtyToDispense;
            }
            
            // Occasionally add adjustments or expired items
            if (rand(1, 100) <= 10) {
                $adjustmentTypes = ['adjustment', 'expired', 'returned'];
                $adjustmentType = $adjustmentTypes[array_rand($adjustmentTypes)];
                
                $adjustmentQty = rand(1, 10);
                if ($adjustmentType === 'expired' || $adjustmentType === 'adjustment') {
                    $adjustmentQty = -$adjustmentQty;
                }
                
                $notes = [
                    'adjustment' => 'Stock adjustment - inventory count',
                    'expired' => 'Expired items removed from stock',
                    'returned' => 'Returned unused medication'
                ];
                
                $stmt = $pdo->prepare("
                    INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_datetime, transaction_type, quantity, reference, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $medicineId,
                    $batchId,
                    $currentDate->format('Y-m-d H:i:s'),
                    $adjustmentType,
                    $adjustmentQty,
                    strtoupper($adjustmentType) . '-' . rand(1000, 9999),
                    $notes[$adjustmentType]
                ]);
                $transactionCount++;
            }
        }
    }
    
    $pdo->commit();
    echo "✓ Successfully seeded $batchCount medicine batches\n";
    echo "✓ Successfully seeded $transactionCount medicine transactions\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Error seeding batches/transactions: " . $e->getMessage() . "\n";
    exit(1);
}

// Generate inventory statistics
echo "\nGenerating inventory statistics...\n";

$lowStock = $pdo->query("
    SELECT m.name, m.reorder_level,
           COALESCE(SUM(mt.quantity), 0) as current_stock
    FROM medicines m
    LEFT JOIN medicine_transactions mt ON mt.medicine_id = m.id
    GROUP BY m.id
    HAVING current_stock < m.reorder_level
")->fetchAll(PDO::FETCH_ASSOC);

echo "\nLow Stock Items: " . count($lowStock) . "\n";
if (count($lowStock) > 0) {
    echo "Sample low stock items:\n";
    foreach (array_slice($lowStock, 0, 5) as $item) {
        echo "  - {$item['name']}: {$item['current_stock']} (reorder at {$item['reorder_level']})\n";
    }
}

$expiringSoon = $pdo->query("
    SELECT m.name, mb.batch_no, mb.expiry_date,
           DATEDIFF(mb.expiry_date, CURDATE()) as days_until_expiry
    FROM medicine_batches mb
    JOIN medicines m ON m.id = mb.medicine_id
    WHERE mb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
    ORDER BY mb.expiry_date
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

echo "\nExpiring Soon (within 90 days): " . count($expiringSoon) . "\n";
if (count($expiringSoon) > 0) {
    echo "Sample expiring items:\n";
    foreach (array_slice($expiringSoon, 0, 5) as $item) {
        echo "  - {$item['name']} ({$item['batch_no']}): expires in {$item['days_until_expiry']} days\n";
    }
}

echo "\nMedicine inventory module seeding completed!\n";
