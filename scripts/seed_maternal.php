<?php
/**
 * Maternal Health Module Seeder
 * Generates pregnancies, prenatal visits, and postnatal visits
 */

require_once __DIR__ . '/../config/db.php';

echo "Seeding maternal health module...\n";

// Get users for recorded_by fields
$users = $pdo->query("SELECT id FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
if (empty($users)) {
    echo "No users found. Please run seed_users.php first.\n";
    exit(1);
}

// Get female patients of childbearing age (15-45 years)
$minDate = date('Y-m-d', strtotime('-45 years'));
$maxDate = date('Y-m-d', strtotime('-15 years'));

$females = $pdo->query("
    SELECT id, first_name, last_name, birth_date 
    FROM patients 
    WHERE sex = 'female' 
    AND birth_date BETWEEN '$minDate' AND '$maxDate'
    AND status = 'active'
    ORDER BY RAND()
    LIMIT 40
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($females)) {
    echo "No eligible female patients found. Please run seed_patients.php first.\n";
    exit(1);
}

echo "Found " . count($females) . " eligible female patients\n";

$pregnancyCount = 0;
$prenatalCount = 0;
$postnatalCount = 0;

$pdo->beginTransaction();
try {
    foreach ($females as $female) {
        // 40% chance of having a pregnancy record
        if (rand(1, 100) <= 40) {
            
            // Determine pregnancy status
            $statusRand = rand(1, 100);
            if ($statusRand <= 60) {
                $status = 'ongoing';
            } elseif ($statusRand <= 90) {
                $status = 'delivered';
            } else {
                $status = 'terminated';
            }
            
            // Generate LMP date based on status
            if ($status === 'ongoing') {
                // LMP between 1-9 months ago
                $daysAgo = rand(30, 270);
                $lmpDate = date('Y-m-d', strtotime("-$daysAgo days"));
            } elseif ($status === 'delivered') {
                // LMP about 9-18 months ago (delivered in last 9 months)
                $daysAgo = rand(270, 540);
                $lmpDate = date('Y-m-d', strtotime("-$daysAgo days"));
            } else {
                // Terminated - LMP 6-12 months ago
                $daysAgo = rand(180, 365);
                $lmpDate = date('Y-m-d', strtotime("-$daysAgo days"));
            }
            
            $lmp = new DateTime($lmpDate);
            $edd = (clone $lmp)->modify('+280 days');
            
            // Gravida (number of pregnancies) and Para (number of births)
            $gravida = rand(1, 5);
            $para = $status === 'delivered' ? rand(1, $gravida) : rand(0, $gravida - 1);
            
            // Insert pregnancy
            $stmt = $pdo->prepare("
                INSERT INTO pregnancies (patient_id, lmp_date, edd_date, gravida, para, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $female['id'],
                $lmp->format('Y-m-d'),
                $edd->format('Y-m-d'),
                $gravida,
                $para,
                $status
            ]);
            
            $pregnancyId = $pdo->lastInsertId();
            $pregnancyCount++;
            
            // Generate prenatal visits
            $today = new DateTime();
            $gestationalWeeks = floor($lmp->diff($today)->days / 7);
            
            if ($status === 'ongoing' || $status === 'delivered') {
                // Number of prenatal visits based on gestational age
                $expectedVisits = min(floor($gestationalWeeks / 4), 9); // Visit every 4 weeks
                $actualVisits = rand(max(1, $expectedVisits - 2), $expectedVisits);
                
                for ($i = 0; $i < $actualVisits; $i++) {
                    $visitWeek = ($i + 1) * 4; // Visits at 4, 8, 12, 16, etc. weeks
                    $visitDate = (clone $lmp)->modify("+$visitWeek weeks");
                    
                    // Don't create future visits
                    if ($visitDate > $today) {
                        break;
                    }
                    
                    // Generate vital signs
                    $bpSystolic = rand(90, 140);
                    $bpDiastolic = rand(60, 90);
                    $weight = rand(45, 85) + (rand(0, 99) / 100);
                    
                    $notes = [
                        'Normal prenatal checkup',
                        'Patient doing well',
                        'No complications noted',
                        'Advised on nutrition',
                        'Fetal heartbeat normal',
                        'Routine monitoring',
                        'Patient counseled on delivery preparation'
                    ];
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO prenatal_visits (pregnancy_id, visit_datetime, gestational_age_weeks, bp_systolic, bp_diastolic, weight_kg, notes, recorded_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $pregnancyId,
                        $visitDate->format('Y-m-d H:i:s'),
                        $visitWeek,
                        $bpSystolic,
                        $bpDiastolic,
                        $weight,
                        $notes[array_rand($notes)],
                        $users[array_rand($users)]
                    ]);
                    $prenatalCount++;
                    
                    // Create visit record
                    $pdo->prepare("INSERT INTO visits (patient_id, visit_datetime, visit_type, reason, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([
                            $female['id'],
                            $visitDate->format('Y-m-d H:i:s'),
                            'maternal',
                            'Prenatal checkup - ' . $visitWeek . ' weeks',
                            'Prenatal visit',
                            $users[array_rand($users)]
                        ]);
                }
            }
            
            // Generate postnatal visits for delivered pregnancies
            if ($status === 'delivered') {
                $deliveryDate = (clone $lmp)->modify('+280 days');
                
                // Postnatal visits at 1 week, 6 weeks, and 3 months
                $postnatalSchedule = [7, 42, 90]; // days after delivery
                
                foreach ($postnatalSchedule as $daysAfter) {
                    $visitDate = (clone $deliveryDate)->modify("+$daysAfter days");
                    
                    // Only create if visit date has passed
                    if ($visitDate <= $today) {
                        // 80% chance of attending
                        if (rand(1, 100) <= 80) {
                            $motherConditions = [
                                'Recovering well',
                                'No complications',
                                'Breastfeeding established',
                                'Wound healing normally',
                                'Vital signs stable'
                            ];
                            
                            $babyConditions = [
                                'Baby healthy',
                                'Feeding well',
                                'Weight gain appropriate',
                                'No jaundice',
                                'Development normal'
                            ];
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO postnatal_visits (pregnancy_id, visit_datetime, mother_condition, baby_condition, notes, recorded_by)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $pregnancyId,
                                $visitDate->format('Y-m-d H:i:s'),
                                $motherConditions[array_rand($motherConditions)],
                                $babyConditions[array_rand($babyConditions)],
                                'Routine postnatal checkup',
                                $users[array_rand($users)]
                            ]);
                            $postnatalCount++;
                            
                            // Create visit record
                            $pdo->prepare("INSERT INTO visits (patient_id, visit_datetime, visit_type, reason, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?)")
                                ->execute([
                                    $female['id'],
                                    $visitDate->format('Y-m-d H:i:s'),
                                    'maternal',
                                    'Postnatal checkup - ' . $daysAfter . ' days postpartum',
                                    'Postnatal visit',
                                    $users[array_rand($users)]
                                ]);
                        }
                    }
                }
            }
            
            // Create reminders for ongoing pregnancies
            if ($status === 'ongoing') {
                $nextVisitWeek = (floor($gestationalWeeks / 4) + 1) * 4;
                $nextVisitDate = (clone $lmp)->modify("+$nextVisitWeek weeks");
                
                if ($nextVisitDate <= (clone $today)->modify('+30 days')) {
                    $message = "Prenatal checkup scheduled for {$female['first_name']} {$female['last_name']} at $nextVisitWeek weeks gestation";
                    
                    $pdo->prepare("INSERT INTO reminders (patient_id, reminder_type, due_date, message, status) VALUES (?, ?, ?, ?, ?)")
                        ->execute([
                            $female['id'],
                            'prenatal',
                            $nextVisitDate->format('Y-m-d'),
                            $message,
                            'pending'
                        ]);
                }
            }
        }
    }
    
    $pdo->commit();
    echo "✓ Successfully seeded $pregnancyCount pregnancies\n";
    echo "✓ Successfully seeded $prenatalCount prenatal visits\n";
    echo "✓ Successfully seeded $postnatalCount postnatal visits\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Error seeding maternal health data: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nMaternal health module seeding completed!\n";
