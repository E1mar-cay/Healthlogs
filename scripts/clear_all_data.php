<?php
/**
 * Clear Seeded Data
 * Removes all seeded data while preserving structure
 */

require_once __DIR__ . '/../config/db.php';

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║              Clear Seeded Data                             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "⚠️  This will delete ALL data from the database!\n";
echo "Are you sure you want to continue? (y/N): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "Operation cancelled.\n";
    exit(0);
}

echo "\nClearing data...\n";

$pdo->beginTransaction();
try {
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Clear data in reverse dependency order
    $tables = [
        'timeseries_daily',
        'reminders',
        'medicine_transactions',
        'medicine_batches',
        'medicines',
        'postnatal_visits',
        'prenatal_visits',
        'pregnancies',
        'immunization_schedule',
        'immunization_records',
        'vaccines',
        'visits',
        'patient_conditions',
        'patient_allergies',
        'patients',
        'households',
        'users',
        'roles'
    ];
    
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        if ($count > 0) {
            $pdo->exec("DELETE FROM $table");
            $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
            echo "✓ Cleared $table ($count records)\n";
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $pdo->commit();
    echo "\n✓ All data cleared successfully!\n";
    echo "You can now run seed_all.php for a fresh start.\n\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Error clearing data: " . $e->getMessage() . "\n";
    exit(1);
}