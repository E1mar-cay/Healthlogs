<?php
/**
 * General Visits Seeder
 * Adds general consultation visits for better time-series data
 */

require_once __DIR__ . '/../config/db.php';

echo "Seeding general visits...\n";

// Get active patients
$patients = $pdo->query("SELECT id FROM patients WHERE status = 'active' LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);

if (empty($patients)) {
    echo "No active patients found. Skipping general visits seeding.\n";
    echo "This is normal if running seeders for the first time.\n";
    exit(0);
}

// Get users for recorded_by
$users = $pdo->query("SELECT id FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);

$visitCount = 0;
$reasons = [
    'General consultation',
    'Follow-up visit',
    'Health checkup',
    'Fever and cough',
    'Hypertension monitoring',
    'Diabetes checkup',
    'Wound care',
    'Medication refill',
    'Health certificate',
    'Blood pressure check'
];

$pdo->beginTransaction();
try {
    // Generate visits over the last 90 days
    for ($i = 90; $i >= 1; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        
        // Random number of visits per day (5-25)
        $dailyVisits = rand(5, 25);
        
        for ($v = 0; $v < $dailyVisits; $v++) {
            $patientId = $patients[array_rand($patients)];
            $hour = rand(8, 16); // 8 AM to 4 PM
            $minute = rand(0, 59);
            $visitDateTime = $date . ' ' . sprintf('%02d:%02d:00', $hour, $minute);
            
            $pdo->prepare("INSERT INTO visits (patient_id, visit_datetime, visit_type, reason, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([
                    $patientId,
                    $visitDateTime,
                    'general',
                    $reasons[array_rand($reasons)],
                    'General consultation visit',
                    !empty($users) ? $users[array_rand($users)] : null
                ]);
            $visitCount++;
        }
    }
    
    $pdo->commit();
    echo "✓ Successfully seeded $visitCount general visits\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Error seeding visits: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nGeneral visits seeding completed!\n";