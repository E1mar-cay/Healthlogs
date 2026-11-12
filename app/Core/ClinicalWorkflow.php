<?php

class ClinicalWorkflow
{
    /**
     * Automatically sync a follow-up visit date into `patient_appointments` and `reminders`
     * so clinical program follow-ups appear in the central Appointments schedule & SMS queue.
     */
    public static function syncFollowUpAppointment(
        PDO $pdo,
        int $patientId,
        ?string $nextVisitDate,
        string $serviceCode,
        string $reminderType,
        string $reason,
        string $personnel = 'Barangay Health Worker'
    ): void {
        if ($patientId <= 0 || empty($nextVisitDate)) {
            return;
        }
        $dateOnly = substr(trim($nextVisitDate), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOnly)) {
            return;
        }

        try {
            // 1. Resolve service_id from health_services
            $srvStmt = $pdo->prepare("SELECT id, service_name FROM health_services WHERE service_code = ? LIMIT 1");
            $srvStmt->execute([$serviceCode]);
            $srv = $srvStmt->fetch(PDO::FETCH_ASSOC);
            if (!$srv) {
                $srvStmt = $pdo->query("SELECT id, service_name FROM health_services ORDER BY id ASC LIMIT 1");
                $srv = $srvStmt->fetch(PDO::FETCH_ASSOC);
            }
            if (!$srv) {
                return;
            }
            $serviceId = (int)$srv['id'];

            // 2. Fetch patient first name for friendly SMS reminder
            $patStmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE id = ? LIMIT 1");
            $patStmt->execute([$patientId]);
            $pat = $patStmt->fetch(PDO::FETCH_ASSOC);
            if (!$pat) {
                return;
            }
            $firstName = $pat['first_name'] ?? 'Patient';

            // 3. Check if an appointment already exists on that date for this patient + service
            $chkApt = $pdo->prepare("
                SELECT id FROM patient_appointments
                WHERE patient_id = ? AND service_id = ? AND appointment_date = ?
                  AND status IN ('scheduled', 'rescheduled')
                LIMIT 1
            ");
            $chkApt->execute([$patientId, $serviceId, $dateOnly]);
            if (!$chkApt->fetchColumn()) {
                $maxId = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM patient_appointments")->fetchColumn() + 1;
                $aptCode = 'APT-' . date('Y') . '-' . str_pad((string)$maxId, 4, '0', STR_PAD_LEFT);
                $insApt = $pdo->prepare("
                    INSERT INTO patient_appointments
                    (appointment_code, patient_id, service_id, appointment_date, appointment_time, status, reason, assigned_personnel, created_by)
                    VALUES (?, ?, ?, ?, '08:30:00', 'scheduled', ?, ?, ?)
                ");
                $insApt->execute([
                    $aptCode,
                    $patientId,
                    $serviceId,
                    $dateOnly,
                    $reason,
                    $personnel,
                    $_SESSION['user_id'] ?? null,
                ]);
            }

            // 4. Sync SMS Reminder in `reminders` table
            $validReminderTypes = ['immunization', 'prenatal', 'postnatal', 'family_planning', 'non_communicable', 'general'];
            $remType = in_array($reminderType, $validReminderTypes, true) ? $reminderType : 'general';
            $chkRem = $pdo->prepare("
                SELECT id FROM reminders
                WHERE patient_id = ? AND reminder_type = ? AND due_date = ?
                LIMIT 1
            ");
            $chkRem->execute([$patientId, $remType, $dateOnly]);
            if (!$chkRem->fetchColumn()) {
                $formattedDate = date('M d, Y', strtotime($dateOnly));
                $msg = "Good day {$firstName}! Reminder from Barangay Health Center: Your {$reason} is scheduled on {$formattedDate}.";
                $insRem = $pdo->prepare("
                    INSERT INTO reminders (patient_id, reminder_type, due_date, message, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $insRem->execute([$patientId, $remType, $dateOnly, substr($msg, 0, 250)]);
            }
        } catch (Throwable $e) {
            // Non-blocking workflow helper
        }
    }

    /**
     * Automatically deduct dispensed medicine from available non-expired batches using FEFO
     * (First-Expired, First-Out) and log the transaction in `medicine_transactions`.
     */
    public static function dispenseMedicineFEFO(
        PDO $pdo,
        $medicineIdOrKeyword,
        int $qty,
        string $reference,
        string $notes
    ): bool {
        if ($qty <= 0) {
            return false;
        }

        try {
            if (is_numeric($medicineIdOrKeyword) && (int)$medicineIdOrKeyword > 0) {
                $medStmt = $pdo->prepare("SELECT id, name, unit FROM medicines WHERE id = ? LIMIT 1");
                $medStmt->execute([(int)$medicineIdOrKeyword]);
            } else {
                $kw = '%' . trim((string)$medicineIdOrKeyword) . '%';
                $medStmt = $pdo->prepare("SELECT id, name, unit FROM medicines WHERE name LIKE ? OR generic_name LIKE ? ORDER BY id ASC LIMIT 1");
                $medStmt->execute([$kw, $kw]);
            }
            $med = $medStmt->fetch(PDO::FETCH_ASSOC);
            if (!$med) {
                return false;
            }
            $medId = (int)$med['id'];

            // Prevent duplicate auto-dispense for the exact same reference and medicine
            if ($reference !== '') {
                $dupStmt = $pdo->prepare("SELECT id FROM medicine_transactions WHERE medicine_id = ? AND reference = ? AND transaction_type = 'dispensed' LIMIT 1");
                $dupStmt->execute([$medId, $reference]);
                if ($dupStmt->fetchColumn()) {
                    return false;
                }
            }

            // Find earliest non-expired batch with positive stock (FEFO)
            $batchStmt = $pdo->prepare("
                SELECT mb.id, mb.batch_no, COALESCE(SUM(mt.quantity), 0) AS batch_stock
                FROM medicine_batches mb
                LEFT JOIN medicine_transactions mt ON mt.batch_id = mb.id
                WHERE mb.medicine_id = ?
                  AND mb.expiry_date >= CURDATE()
                GROUP BY mb.id, mb.batch_no, mb.expiry_date
                HAVING batch_stock > 0
                ORDER BY mb.expiry_date ASC, mb.id ASC
                LIMIT 1
            ");
            $batchStmt->execute([$medId]);
            $batch = $batchStmt->fetch(PDO::FETCH_ASSOC);
            if (!$batch) {
                return false;
            }

            $dispenseQty = min($qty, (int)$batch['batch_stock']);
            if ($dispenseQty <= 0) {
                return false;
            }

            $oldStock = class_exists('ActivityLogger') ? ActivityLogger::getMedicineStock($medId) : 0;
            $insTx = $pdo->prepare("
                INSERT INTO medicine_transactions
                (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes, recorded_by)
                VALUES (?, ?, 'dispensed', ?, NOW(), ?, ?, ?)
            ");
            $insTx->execute([
                $medId,
                (int)$batch['id'],
                -$dispenseQty,
                $reference ?: null,
                $notes,
                $_SESSION['user_id'] ?? null,
            ]);
            $txId = (int)$pdo->lastInsertId();

            if (class_exists('ActivityLogger')) {
                $newStock = ActivityLogger::getMedicineStock($medId);
                ActivityLogger::logInventory(
                    'dispense',
                    "Auto-dispensed {$dispenseQty} {$med['unit']} of {$med['name']} (Batch {$batch['batch_no']}) — {$notes}",
                    'medicine_transactions',
                    (string)$txId,
                    [
                        'transaction_id' => $txId,
                        'medicine_id' => $medId,
                        'medicine_name' => $med['name'],
                        'batch_id' => (int)$batch['id'],
                        'batch_no' => $batch['batch_no'],
                        'quantity' => -$dispenseQty,
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                        'reference' => $reference,
                    ]
                );
            }
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

