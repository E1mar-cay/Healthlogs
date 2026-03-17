<?php
/**
 * TB Monitoring Module Seeder
 * Generates TB cases and follow-up visits
 */

require_once __DIR__ . '/../config/db.php';

echo "Seeding TB monitoring module...\n";

// Get adult patients (18+ years) - TB is more common in adults
$eighteenYearsAgo = date('Y-m-d', strtotime('-18 years'));

$adults = $pdo->query("
    SELECT id, first_name, last_name, birth_date, sex
    FROM patients 
    WHERE birth_date <= '$eighteenYearsAgo'
    AND status = 'active'
    ORDER BY RAND()
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($adults)) {
    echo "No eligible adult patients found. Please run seed_patients.php first.\n";
    exit(1);
}

echo "Found " . count($adults) . " eligible adult patients\n";

$caseCount = 0;
$followupCount = 0;

$pdo->beginTransaction();
try {
    foreach ($adults as $patient) {
        // 15% chance of having TB case (realistic prevalence)
        if (rand(1, 100) <= 15) {
            
            // Determine case type (90% drug susceptible, 10% drug resistant)
            $caseType = rand(1, 100) <= 90 ? 'drug_susceptible' : 'drug_resistant';
            
            // Determine status
            $statusRand = rand(1, 100);
            if ($statusRand <= 50) {
                $status = 'active';
            } elseif ($statusRand <= 80) {
                $status = 'completed';
            } elseif ($statusRand <= 90) {
                $status = 'defaulted';
            } elseif ($statusRand <= 97) {
                $status = 'failed';
            } else {
                $status = 'died';
            }
            
            // Generate diagnosis date (1-24 months ago)
            $monthsAgo = rand(1, 24);
            $diagnosisDate = date('Y-m-d', strtotime("-$monthsAgo months"));
            
            // Treatment start (usually within 1 week of diagnosis)
            $treatmentStart = date('Y-m-d', strtotime($diagnosisDate . ' +' . rand(0, 7) . ' days'));
            
            // Treatment end (if completed or failed)
            $treatmentEnd = null;
            if (in_array($status, ['completed', 'failed', 'died'])) {
                // Standard treatment is 6 months for drug susceptible, 18-24 months for drug resistant
                $treatmentMonths = $caseType === 'drug_susceptible' ? 6 : rand(18, 24);
                $treatmentEnd = date('Y-m-d', strtotime($treatmentStart . " +$treatmentMonths months"));
            }
            
            // Generate case number
            $caseNo = 'TB-' . date('Y', strtotime($diagnosisDate)) . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $notes = [
                'Pulmonary TB confirmed by sputum smear',
                'Extrapulmonary TB - lymph node involvement',
                'Smear positive pulmonary TB',
                'Bacteriologically confirmed TB',
                'Clinically diagnosed TB',
                'TB with HIV co-infection',
                'Relapse case',
                'New TB case'
            ];
            
            // Insert TB case
            $stmt = $pdo->prepare("
                INSERT INTO tb_cases (patient_id, case_no, diagnosis_date, case_type, status, treatment_start, treatment_end, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $patient['id'],
                $caseNo,
                $diagnosisDate,
                $caseType,
                $status,
                $treatmentStart,
                $treatmentEnd,
                $notes[array_rand($notes)]
            ]);
            
            $caseId = $pdo->lastInsertId();
            $caseCount++;
            
            // Generate follow-up visits
            $today = new DateTime();
            $startDate = new DateTime($treatmentStart);
            $endDate = $treatmentEnd ? new DateTime($treatmentEnd) : $today;
            
            // Follow-ups typically monthly for drug susceptible, bi-weekly for drug resistant
            $followupInterval = $caseType === 'drug_susceptible' ? 30 : 14;
            
            $currentDate = clone $startDate;
            $visitNumber = 0;
            
            while ($currentDate <= $endDate && $currentDate <= $today) {
                $visitNumber++;
                
                // Skip some visits for defaulted cases
                if ($status === 'defaulted' && $visitNumber > 3 && rand(1, 100) <= 40) {
                    $currentDate->modify("+$followupInterval days");
                    continue;
                }
                
                // Adherence based on status
                if ($status === 'completed') {
                    $adherence = rand(1, 100) <= 90 ? 'good' : 'poor';
                } elseif ($status === 'defaulted' || $status === 'failed') {
                    $adherence = rand(1, 100) <= 70 ? 'poor' : 'missed';
                } else {
                    $adherence = ['good', 'good', 'good', 'poor'][array_rand(['good', 'good', 'good', 'poor'])];
                }
                
                // Weight (gradually increasing for successful treatment)
                $baseWeight = rand(45, 75);
                $weightGain = $status === 'completed' ? ($visitNumber * 0.5) : 0;
                $weight = $baseWeight + $weightGain + (rand(-10, 10) / 10);
                
                // Symptoms
                $symptomsList = [
                    'Cough improving',
                    'No fever',
                    'Appetite improving',
                    'Weight gain noted',
                    'Persistent cough',
                    'Night sweats',
                    'Fatigue',
                    'Chest pain',
                    'Hemoptysis',
                    'No symptoms'
                ];
                
                $symptoms = $symptomsList[array_rand($symptomsList)];
                if ($status === 'completed' && $visitNumber > 3) {
                    $symptoms = 'No symptoms';
                }
                
                $followupNotes = [
                    'Patient tolerating medications well',
                    'Counseled on medication adherence',
                    'Side effects monitored',
                    'Sputum sample collected',
                    'Contact tracing discussed',
                    'Nutritional support provided',
                    'Patient education reinforced'
                ];
                
                $stmt = $pdo->prepare("
                    INSERT INTO tb_followups (tb_case_id, followup_datetime, adherence, weight_kg, symptoms, notes)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $caseId,
                    $currentDate->format('Y-m-d H:i:s'),
                    $adherence,
                    $weight,
                    $symptoms,
                    $followupNotes[array_rand($followupNotes)]
                ]);
                $followupCount++;
                
                $currentDate->modify("+$followupInterval days");
            }
            
            // Create reminder for next follow-up if case is active
            if ($status === 'active') {
                $nextFollowup = (clone $currentDate);
                
                if ($nextFollowup <= (clone $today)->modify('+30 days')) {
                    $message = "TB follow-up visit for {$patient['first_name']} {$patient['last_name']} - Case: $caseNo";
                    
                    $pdo->prepare("INSERT INTO reminders (patient_id, reminder_type, due_date, message, status) VALUES (?, ?, ?, ?, ?)")
                        ->execute([
                            $patient['id'],
                            'tb',
                            $nextFollowup->format('Y-m-d'),
                            $message,
                            'pending'
                        ]);
                }
            }
        }
    }
    
    $pdo->commit();
    echo "✓ Successfully seeded $caseCount TB cases\n";
    echo "✓ Successfully seeded $followupCount TB follow-up visits\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Error seeding TB monitoring data: " . $e->getMessage() . "\n";
    exit(1);
}

// Generate some statistics
$stats = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM tb_cases
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

echo "\nTB Case Statistics:\n";
foreach ($stats as $stat) {
    echo "  - " . ucfirst($stat['status']) . ": " . $stat['count'] . "\n";
}

echo "\nTB monitoring module seeding completed!\n";
