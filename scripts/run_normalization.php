<?php
/**
 * Database Normalization Runner
 * Run this script to normalize the existing HealthLogs database
 */

require_once __DIR__ . '/../config/db.php';

echo "Starting database normalization...\n";

try {
    // Read and execute migration script
    $migrationSql = file_get_contents(__DIR__ . '/migrate_to_normalized.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $migrationSql)));
    
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !str_starts_with($statement, '--')) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    $pdo->commit();
    echo "Database normalization completed successfully!\n";
    
    // Display summary
    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM patients) as patients,
            (SELECT COUNT(*) FROM medicines) as medicines,
            (SELECT COUNT(*) FROM medicine_batches) as batches,
            (SELECT COUNT(*) FROM immunization_records) as immunizations,
            (SELECT COUNT(*) FROM pregnancies) as pregnancies
    ")->fetch();
    
    echo "\nDatabase Summary:\n";
    echo "- Patients: {$stats['patients']}\n";
    echo "- Medicines: {$stats['medicines']}\n";
    echo "- Medicine Batches: {$stats['batches']}\n";
    echo "- Immunization Records: {$stats['immunizations']}\n";
    echo "- Pregnancies: {$stats['pregnancies']}\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error during normalization: " . $e->getMessage() . "\n";
    exit(1);
}