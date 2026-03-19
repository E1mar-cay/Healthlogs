<?php
/**
 * Master Seeder Script
 * Runs all seeders in the correct order
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         HealthLogs - Master Database Seeder               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$startTime = microtime(true);

// Define seeders in order of execution
$seeders = [
    'seed_users.php' => 'User Accounts & Roles',
    'seed_patients.php' => 'Patient Records & Households',
    'seed_visits.php' => 'General Visits',
    'seed_immunization.php' => 'Immunization Module',
    'seed_maternal.php' => 'Maternal Health Module',
    'seed_inventory.php' => 'Medicine Inventory Module',
    'build_timeseries.php' => 'Time Series Data',
];

$success = 0;
$failed = 0;

foreach ($seeders as $file => $description) {
    echo "┌─────────────────────────────────────────────────────────┐\n";
    echo "│ Running: $description\n";
    echo "└─────────────────────────────────────────────────────────┘\n";
    
    $seederPath = __DIR__ . '/' . $file;
    
    if (!file_exists($seederPath)) {
        echo "✗ Seeder file not found: $file\n\n";
        $failed++;
        continue;
    }
    
    // Execute seeder
    ob_start();
    $exitCode = 0;
    
    try {
        include $seederPath;
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        $exitCode = 1;
    }
    
    $output = ob_get_clean();
    echo $output;
    
    if ($exitCode === 0) {
        $success++;
        echo "✓ $description completed successfully\n\n";
    } else {
        $failed++;
        echo "✗ $description failed\n\n";
    }
    
    // Small delay between seeders
    usleep(500000); // 0.5 seconds
}

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    Seeding Summary                         ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║ Total Seeders: " . count($seeders) . "                                           ║\n";
echo "║ Successful: $success                                              ║\n";
echo "║ Failed: $failed                                                  ║\n";
echo "║ Duration: {$duration}s                                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if ($failed === 0) {
    echo "🎉 All seeders completed successfully!\n\n";
    echo "You can now:\n";
    echo "  1. Login to the system at: http://localhost/HealthLogs/public/login.php\n";
    echo "  2. Use default credentials:\n";
    echo "     - Superadmin: superadmin / superadmin123\n";
    echo "     - Admin: admin / admin123\n";
    echo "     - Health Worker: bhw / bhw123\n";
    echo "  3. Explore the populated modules with realistic data\n\n";
} else {
    echo "⚠️  Some seeders failed. Please check the errors above.\n\n";
    exit(1);
}
