<?php
require __DIR__ . '/../../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !can_manage_clinical_records()) {
    header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
    exit;
}

$pregnancyId = (int)($_POST['pregnancy_id'] ?? 0);
$deliveryDatetime = trim($_POST['delivery_datetime'] ?? '');
$deliveryType = trim($_POST['delivery_type'] ?? 'VD (Vaginal)');
$birthWeightGrams = !empty($_POST['birth_weight_grams']) ? (int)$_POST['birth_weight_grams'] : null;
$deliveryPlace = trim($_POST['delivery_place'] ?? 'BHS / RHU Public');
$birthAttendant = trim($_POST['birth_attendant'] ?? 'MW (Midwife)');

$recordInitialPnc = !empty($_POST['record_initial_pnc']);
$motherCondition = trim($_POST['mother_condition'] ?? 'Stable');
$babyCondition = trim($_POST['baby_condition'] ?? 'Healthy');
$pncNotes = trim($_POST['pnc_notes'] ?? '');

if ($pregnancyId <= 0) {
    $_SESSION['error_message'] = 'Invalid pregnancy record.';
    header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
    exit;
}

$formattedDeliveryDt = $deliveryDatetime !== ''
    ? str_replace('T', ' ', $deliveryDatetime) . (strlen($deliveryDatetime) === 16 ? ':00' : '')
    : date('Y-m-d H:i:s');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, patient_id, status, para FROM pregnancies WHERE id = ? FOR UPDATE");
    $stmt->execute([$pregnancyId]);
    $preg = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$preg) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Pregnancy record not found.';
        header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
        exit;
    }

    $wasOngoing = ($preg['status'] !== 'delivered');
    $newPara = $wasOngoing ? ((int)($preg['para'] ?? 0) + 1) : (int)($preg['para'] ?? 1);

    $upd = $pdo->prepare("
        UPDATE pregnancies
        SET status = 'delivered',
            para = ?,
            delivery_datetime = ?,
            delivery_type = ?,
            birth_weight_grams = ?,
            delivery_place = ?,
            birth_attendant = ?
        WHERE id = ?
    ");
    $upd->execute([
        $newPara,
        $formattedDeliveryDt,
        $deliveryType ?: 'VD (Vaginal)',
        $birthWeightGrams,
        $deliveryPlace ?: 'BHS / RHU Public',
        $birthAttendant ?: 'MW (Midwife)',
        $pregnancyId,
    ]);

    // Update mother's classification to postpartum if currently pregnant
    $pdo->prepare("
        UPDATE patients
        SET classification = 'postpartum'
        WHERE id = ? AND classification = 'pregnant'
    ")->execute([(int)$preg['patient_id']]);

    // Optionally log Contact 1 (<24h) Postnatal Visit right away
    if ($recordInitialPnc) {
        $pncStmt = $pdo->prepare("
            INSERT INTO postnatal_visits (pregnancy_id, visit_datetime, mother_condition, baby_condition, notes, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $defaultNote = $pncNotes !== ''
            ? $pncNotes
            : sprintf('Immediate postpartum check (<24h) • %s • Baby weight: %s', $deliveryType, $birthWeightGrams ? $birthWeightGrams . 'g' : 'N/A');
        $pncStmt->execute([
            $pregnancyId,
            $formattedDeliveryDt,
            $motherCondition ?: 'Stable',
            $babyCondition ?: 'Healthy',
            $defaultNote,
            $_SESSION['user_id'] ?? null,
        ]);
    }

    $pdo->commit();
    $_SESSION['success_message'] = 'Delivery recorded! Postnatal care (4PNC) and follow-up visits are now unlocked for this mother.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Delivery save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Could not save delivery details. Please try again.';
}

header("Location: /HealthLogs/public/maternal/tcl.php?pregnancy_id={$pregnancyId}#postnatal-section");
exit;

