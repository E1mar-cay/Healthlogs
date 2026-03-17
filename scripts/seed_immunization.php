<?php
/**
 * Immunization Module Seeder
 * Generates vaccines, schedules, and immunization records
 */

require_once __DIR__ . '/../config/db.php';

echo "Seeding immunization module...\n";

// First, ensure vaccines exist
echo "Checking vaccines...\n";
$vaccineCount = $pdo->query("SELECT COUNT(*) FROM vaccines")->fetchColumn();

if ($vaccineCount == 0) {
    echo "Seeding vaccines...\n";
    $vaccines = [
        ['BCG', 'BCG', 0, 1, 1],
        ['Hepatitis B', 'HEPB', 0, 1, 1],
        ['Pentavalent (DPT-HepB-Hib)', 'PENTA', 2, 6, 3],
        ['Oral Polio Vaccine', 'OPV', 2, 6, 3],
        ['Inactivated Polio Vaccine', 'IPV', 3, 4, 1],
        ['Pneumococcal Conjugate Vaccine', 'PCV', 2, 6, 3],
        ['Measles, Mumps, Rubella', 'MMR', 9, 24, 2],
        ['Measles-Rubella', 'MR', 9, 12, 1],
        ['Tetanus Diphtheria', 'TD', 12, 180, 1],
        ['Human Papillomavirus', 'HPV', 108, 168, 2]
    ];

    $stmt = $pdo->prepare("INSERT INTO vaccines (name, code, recommended_min_age_months, recommended_max_age_months, doses_required) VALUES (?, ?, ?, ?, ?)");
    foreach ($vaccines as $vaccine) {
        $stmt->execute($vaccine);
    }
    echo "✓ Seeded " . count($vaccines) . " vaccines\n";
}

// Get all vaccines
$vaccines = $pdo->query("SELECT * FROM vaccines")->fetchAll(PDO::FETCH_ASSOC);

// Get children patients (0-5 years old)
$fiveYearsAgo = date('Y-m-d', strtotime('-5 years'));
$children = $pdo->query("SELECT id, birth_date FROM patients WHERE birth_date >= '$fiveYearsAgo' AND status = 'active' ORDER BY birth_date DESC")->fetchAll(PDO::FETCH_ASSOC);

if (empty($children)) {
    echo "No children found. Creating some...\n";
    
    // Create 30 children
    $firstNamesMale = ['Joshua', 'Nathan', 'Ethan', 'Gabriel', 'Samuel', 'Jacob', 'Matthew', 'Daniel', 'Lucas', 'Noah'];
    $firstNamesFemale = ['Sophia', 'Isabella', 'Emma', 'Olivia', 'Ava', 'Mia', 'Emily', 'Abigail', 'Madison', 'Chloe'];
    $lastNames = ['Dela Cruz', 'Garcia', 'Reyes', 'Santos', 'Gonzales', 'Flores', 'Ramos', 'Mendoza', 'Torres', 'Rivera'];
    $barangays = ['Alinguigan', 'Bagong Bayan', 'Centro', 'Maligaya', 'San Isidro'];
    
    $stmt = $pdo->prepare("INSERT INTO patients (first_name, middle_name, last_name, sex, birth_date, barangay, city_municipality, province, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    for ($i = 0; $i < 30; $i++) {
        $sex = rand(0, 1) ? 'male' : 'female';
        $firstName = $sex === 'male' ? $firstNamesMale[array_rand($firstNamesMale)] : $firstNamesFemale[array_rand($firstNamesFemale)];
        $lastName = $lastNames[array_rand($lastNames)];
        $middleName = $lastNames[array_rand($lastNames)];
        
        // Birth date between 0-5 years
        $daysOld = rand(0, 1825); // 5 years in days
        $birthDate = date('Y-m-d', strtotime("-$daysOld days"));
        
        $stmt->execute([
            $firstName,
            $middleName,
            $lastName,
            $sex,
            $birthDate,
            $barangays[array_rand($barangays)],
            'Ilagan',
            'Isabela',
            'active'
        ]);
    }
    
    // Reload children
    $children = $pdo->query("SELECT id, birth_date FROM patients WHERE birth_date >= '$fiveYearsAgo' AND status = 'active' ORDER BY birth_date DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Created " . count($children) . " children\n";
}

echo "Processing " . count($children) . " children for immunization...\n";

$scheduleCount = 0;
$recordCount = 0;

$pdo->beginTransaction();
try {
    foreach ($children as $child) {
        $birthDate = new DateTime($child['birth_date']);
        $today = new DateTime();
        $ageInMonths = $birthDate->diff($today)->m + ($birthDate->diff($today)->y * 12);
        
        foreach ($vaccines as $vaccine) {
            $minAge = $vaccine['recommended_min_age_months'];
            $maxAge = $vaccine['recommended_max_age_months'];
            $dosesRequired = $vaccine['doses_required'];
            
            // Check if child is in age range for this vaccine
            if ($ageInMonths >= $minAge && $ageInMonths <= $maxAge + 6) {
                
                for ($dose = 1; $dose <= $dosesRequired; $dose++) {
                    // Calculate when this dose should be given
                    $doseAgeMonths = $minAge + (($dose - 1) * 2); // 2 months between doses
                    $scheduledDate = (clone $birthDate)->modify("+$doseAgeMonths months");
                    
                    // Determine status
                    if ($scheduledDate < $today) {
                        // Past due - 80% completed, 15% missed, 5% scheduled
                        $rand = rand(1, 100);
                        if ($rand <= 80) {
                            // Completed - create record
                            $administeredDate = $scheduledDate->modify('+' . rand(0, 14) . ' days');
                            
                            $pdo->prepare("INSERT INTO immunization_records (patient_id, vaccine_id, dose_no, administered_on, administered_at, lot_no, notes) VALUES (?, ?, ?, ?, ?, ?, ?)")
                                ->execute([
                                    $child['id'],
                                    $vaccine['id'],
                                    $dose,
                                    $administeredDate->format('Y-m-d'),
                                    $administeredDate->format('Y-m-d H:i:s'),
                                    'LOT-' . rand(1000, 9999),
                                    'Routine immunization'
                                ]);
                            $recordCount++;
                            
                            // Also create schedule as completed
                            $pdo->prepare("INSERT INTO immunization_schedule (patient_id, vaccine_id, dose_no, scheduled_date, status) VALUES (?, ?, ?, ?, ?)")
                                ->execute([
                                    $child['id'],
                                    $vaccine['id'],
                                    $dose,
                                    $scheduledDate->format('Y-m-d'),
                                    'completed'
                                ]);
                            $scheduleCount++;
                            
                        } elseif ($rand <= 95) {
                            // Missed
                            $pdo->prepare("INSERT INTO immunization_schedule (patient_id, vaccine_id, dose_no, scheduled_date, status) VALUES (?, ?, ?, ?, ?)")
                                ->execute([
                                    $child['id'],
                                    $vaccine['id'],
                                    $dose,
                                    $scheduledDate->format('Y-m-d'),
                                    'missed'
                                ]);
                            $scheduleCount++;
                        } else {
                            // Still scheduled (late)
                            $pdo->prepare("INSERT INTO immunization_schedule (patient_id, vaccine_id, dose_no, scheduled_date, status) VALUES (?, ?, ?, ?, ?)")
                                ->execute([
                                    $child['id'],
                                    $vaccine['id'],
                                    $dose,
                                    $scheduledDate->format('Y-m-d'),
                                    'scheduled'
                                ]);
                            $scheduleCount++;
                        }
                    } elseif ($scheduledDate <= (clone $today)->modify('+3 months')) {
                        // Upcoming - create schedule
                        $pdo->prepare("INSERT INTO immunization_schedule (patient_id, vaccine_id, dose_no, scheduled_date, status) VALUES (?, ?, ?, ?, ?)")
                            ->execute([
                                $child['id'],
                                $vaccine['id'],
                                $dose,
                                $scheduledDate->format('Y-m-d'),
                                'scheduled'
                            ]);
                        $scheduleCount++;
                    }
                }
            }
        }
    }
    
    $pdo->commit();
    echo "✓ Successfully seeded $scheduleCount immunization schedules\n";
    echo "✓ Successfully seeded $recordCount immunization records\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Error seeding immunization data: " . $e->getMessage() . "\n";
    exit(1);
}

// Create reminders for upcoming immunizations
echo "Creating immunization reminders...\n";

$upcomingSchedules = $pdo->query("
    SELECT s.*, p.first_name, p.last_name, v.name as vaccine_name
    FROM immunization_schedule s
    JOIN patients p ON p.id = s.patient_id
    JOIN vaccines v ON v.id = s.vaccine_id
    WHERE s.status = 'scheduled' 
    AND s.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetchAll(PDO::FETCH_ASSOC);

$reminderCount = 0;
foreach ($upcomingSchedules as $schedule) {
    // Check if reminder already exists
    $exists = $pdo->prepare("SELECT id FROM reminders WHERE patient_id = ? AND reminder_type = 'immunization' AND due_date = ?");
    $exists->execute([$schedule['patient_id'], $schedule['scheduled_date']]);
    
    if (!$exists->fetch()) {
        $message = "Reminder: {$schedule['vaccine_name']} dose {$schedule['dose_no']} scheduled for {$schedule['first_name']} {$schedule['last_name']}";
        
        $pdo->prepare("INSERT INTO reminders (patient_id, reminder_type, due_date, message, status) VALUES (?, ?, ?, ?, ?)")
            ->execute([
                $schedule['patient_id'],
                'immunization',
                $schedule['scheduled_date'],
                $message,
                'pending'
            ]);
        $reminderCount++;
    }
}

echo "✓ Created $reminderCount immunization reminders\n";
echo "\nImmunization module seeding completed!\n";
