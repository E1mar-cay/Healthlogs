<?php
require_once __DIR__ . '/../config/db.php';

echo "=== MIGRATING LOCATION DATA TO PUROKS (BARANGAY TANGCUL) ===\n\n";

$puroks = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7'];

// 1. Fetch all distinct current barangay values
$currentBrgys = $pdo->query("SELECT DISTINCT barangay FROM patients WHERE barangay IS NOT NULL AND barangay != ''")->fetchAll(PDO::FETCH_COLUMN);

echo "Found " . count($currentBrgys) . " current distinct location values.\n";

// Map each existing dummy barangay to a consistent Purok (1 to 7)
$mapping = [];
$i = 0;
foreach ($currentBrgys as $b) {
    if (preg_match('/^Purok\s*\d+/i', $b)) {
        $mapping[$b] = $b;
    } else {
        $mapping[$b] = $puroks[$i % count($puroks)];
        $i++;
    }
}

$updateStmt = $pdo->prepare("UPDATE patients SET barangay = ? WHERE barangay = ?");
$totalUpdated = 0;
foreach ($mapping as $old => $new) {
    if ($old !== $new) {
        $updateStmt->execute([$new, $old]);
        $cnt = $updateStmt->rowCount();
        $totalUpdated += $cnt;
        echo " - Replaced '$old' with '$new' ($cnt rows)\n";
    }
}

// Update households if exists
try {
    $hhBrgys = $pdo->query("SELECT DISTINCT barangay FROM households WHERE barangay IS NOT NULL AND barangay != ''")->fetchAll(PDO::FETCH_COLUMN);
    $updateHh = $pdo->prepare("UPDATE households SET barangay = ? WHERE barangay = ?");
    foreach ($hhBrgys as $hb) {
        $targetPurok = $mapping[$hb] ?? $puroks[array_rand($puroks)];
        $updateHh->execute([$targetPurok, $hb]);
    }
    echo "Updated households table to Puroks successfully.\n";
} catch (Throwable $e) {
    echo "Note on households: " . $e->getMessage() . "\n";
}

echo "\nMigration complete! Total patient rows updated: $totalUpdated\n";

echo "\n=== CURRENT PUROK DISTRIBUTION IN PATIENTS ===\n";
$dist = $pdo->query("SELECT barangay, COUNT(*) as cnt FROM patients GROUP BY barangay ORDER BY barangay ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($dist as $d) {
    echo "  {$d['barangay']}: {$d['cnt']} patients\n";
}
