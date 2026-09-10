<?php
/**
 * Family Planning Seeder
 * Populates realistic client enrollment and consultation visit records
 */

require_once __DIR__ . '/../config/db.php';

echo "=== Seeding Family Planning Module ===\n";

try {
    // 1. Fetch eligible patients (aged 18-45)
    $patients = $pdo->query("
        SELECT id, first_name, last_name, birth_date, barangay, sex, contact_no
        FROM patients
        WHERE status = 'active'
        ORDER BY id ASC
        LIMIT 40
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($patients)) {
        echo "No patients found. Please seed patients first.\n";
        exit;
    }

    // Clear existing FP data
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE fp_visits;");
    $pdo->exec("TRUNCATE TABLE fp_records;");
    $pdo->exec("DELETE FROM reminders WHERE reminder_type = 'family_planning';");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $methods = [
        ['code' => 'pills_coc', 'name' => 'Pills (COC - Combined)', 'interval_days' => 30, 'qty' => 1],
        ['code' => 'pills_pop', 'name' => 'Pills (POP - Progestin Only)', 'interval_days' => 30, 'qty' => 1],
        ['code' => 'injectable_dmpa', 'name' => 'DMPA Injectable (Depo-Provera)', 'interval_days' => 90, 'qty' => 1],
        ['code' => 'implant', 'name' => 'Subdermal Implant Insertion/Check', 'interval_days' => 365, 'qty' => 1],
        ['code' => 'iud_interval', 'name' => 'IUD (Interval Check)', 'interval_days' => 180, 'qty' => 1],
        ['code' => 'condom', 'name' => 'Condoms (Pack of 10)', 'interval_days' => 30, 'qty' => 10],
        ['code' => 'natural_lam', 'name' => 'LAM Counseling & Check', 'interval_days' => 60, 'qty' => 1],
    ];

    $clientTypes = ['new_acceptor', 'current_user', 'current_user', 'changing_method', 'restart'];
    $partners = ['Juan Dela Cruz', 'Mark Santos', 'Reynaldo Reyes', 'Eduardo Ramos', 'Michael Bautista', 'Christian Aquino', 'Antonio Mendoza'];
    $partnerOccs = ['Farmer', 'Driver', 'Construction Worker', 'Carpenter', 'Vendor', 'Electrician', 'Security Guard'];

    $recordInsertStmt = $pdo->prepare("
        INSERT INTO fp_records (
            patient_id, client_code, registration_date, client_type, method_accepted,
            source, previous_method, partner_name, partner_occupation,
            num_living_children, plan_more_children, status, drop_out_reason, drop_out_date, remarks
        ) VALUES (
            :patient_id, :client_code, :registration_date, :client_type, :method_accepted,
            :source, :previous_method, :partner_name, :partner_occupation,
            :num_living_children, :plan_more_children, :status, :drop_out_reason, :drop_out_date, :remarks
        )
    ");

    $visitInsertStmt = $pdo->prepare("
        INSERT INTO fp_visits (
            fp_record_id, visit_date, method_prescribed, quantity_dispensed,
            next_appointment_date, bp_systolic, bp_diastolic, weight_kg,
            findings_complaints, recorded_by
        ) VALUES (
            :fp_record_id, :visit_date, :method_prescribed, :quantity_dispensed,
            :next_appointment_date, :bp_systolic, :bp_diastolic, :weight_kg,
            :findings_complaints, :recorded_by
        )
    ");

    $reminderInsertStmt = $pdo->prepare("
        INSERT INTO reminders (patient_id, reminder_type, due_date, message, status)
        VALUES (?, 'family_planning', ?, ?, ?)
    ");

    $totalRecords = 0;
    $totalVisits = 0;
    $totalReminders = 0;

    // Pick 20 diverse patients
    $selectedPatients = array_slice($patients, 0, 20);

    $userId = (int)($pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn() ?: null);

    foreach ($selectedPatients as $idx => $p) {
        $seq = $idx + 1;
        $clientCode = sprintf('FP-%s-%04d', date('Y'), $seq);
        $methodObj = $methods[$idx % count($methods)];
        $clientType = $clientTypes[$idx % count($clientTypes)];

        // Random registration date within past 6 months
        $daysAgo = rand(30, 180);
        $regDate = date('Y-m-d', strtotime("-{$daysAgo} days"));

        // Status
        $isDropped = ($idx === 18 || $idx === 19);
        $status = $isDropped ? 'dropped_out' : 'active';
        $dropReason = $isDropped ? ($idx === 18 ? 'side_effects' : 'relocation') : null;
        $dropDate = $isDropped ? date('Y-m-d', strtotime('-15 days')) : null;

        $recordInsertStmt->execute([
            'patient_id' => $p['id'],
            'client_code' => $clientCode,
            'registration_date' => $regDate,
            'client_type' => $clientType,
            'method_accepted' => $methodObj['code'],
            'source' => 'public',
            'previous_method' => $clientType === 'changing_method' ? 'Pills' : null,
            'partner_name' => $partners[$idx % count($partners)],
            'partner_occupation' => $partnerOccs[$idx % count($partnerOccs)],
            'num_living_children' => rand(1, 4),
            'plan_more_children' => ($idx % 3 === 0) ? 'yes' : (($idx % 3 === 1) ? 'no' : 'undecided'),
            'status' => $status,
            'drop_out_reason' => $dropReason,
            'drop_out_date' => $dropDate,
            'remarks' => 'Regular client; completed orientation on RPRH and proper contraceptive use.',
        ]);

        $recordId = (int)$pdo->lastInsertId();
        $totalRecords++;

        // Generate 1 to 3 visits
        $numVisits = rand(1, 3);
        $visitDate = $regDate;

        for ($v = 1; $v <= $numVisits; $v++) {
            $bpSys = rand(110, 130);
            $bpDia = rand(70, 85);
            $weight = rand(480, 680) / 10.0;

            // Calculate next appointment
            if ($v === $numVisits && !$isDropped) {
                // Vary upcoming: some due in 7 days, some overdue by 5 days, some in 30 days
                if ($idx % 4 === 0) {
                    // Overdue
                    $nextApp = date('Y-m-d', strtotime('-5 days'));
                } elseif ($idx % 4 === 1) {
                    // Due in 5 days
                    $nextApp = date('Y-m-d', strtotime('+5 days'));
                } else {
                    // Due in 30 days
                    $nextApp = date('Y-m-d', strtotime('+30 days'));
                }
            } else {
                $nextApp = date('Y-m-d', strtotime($visitDate . " + {$methodObj['interval_days']} days"));
            }

            $visitInsertStmt->execute([
                'fp_record_id' => $recordId,
                'visit_date' => $visitDate,
                'method_prescribed' => $methodObj['name'],
                'quantity_dispensed' => $methodObj['qty'],
                'next_appointment_date' => $nextApp,
                'bp_systolic' => $bpSys,
                'bp_diastolic' => $bpDia,
                'weight_kg' => $weight,
                'findings_complaints' => 'Patient has no significant complaints. Blood pressure and vitals stable.',
                'recorded_by' => $userId,
            ]);
            $totalVisits++;

            // Insert reminder for the final upcoming appointment
            if ($v === $numVisits && !$isDropped && !empty($nextApp)) {
                $isOverdue = ($nextApp < date('Y-m-d'));
                $remStatus = $isOverdue ? 'pending' : 'pending';
                $msg = "Good day {$p['first_name']}! Reminder from Barangay Health Center: Your Family Planning supply refill ({$methodObj['name']}) is scheduled on " . date('M d, Y', strtotime($nextApp)) . ".";

                $reminderInsertStmt->execute([
                    $p['id'],
                    $nextApp,
                    $msg,
                    $remStatus,
                ]);
                $totalReminders++;
            }

            // advance visit date
            $visitDate = date('Y-m-d', strtotime($visitDate . " + {$methodObj['interval_days']} days"));
        }
    }

    echo "✓ Successfully seeded {$totalRecords} FP client records.\n";
    echo "✓ Successfully seeded {$totalVisits} FP consultation/dispensing visits.\n";
    echo "✓ Successfully seeded {$totalReminders} Family Planning SMS reminders.\n";

} catch (Throwable $e) {
    echo "Error seeding Family Planning data: " . $e->getMessage() . "\n";
}
