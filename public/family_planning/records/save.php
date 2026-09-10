<?php
require __DIR__ . '/../../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/family_planning/records/index.php');
    exit;
}

$returnTo = trim($_POST['return_to'] ?? '/HealthLogs/public/family_planning/records/index.php');
// Ensure return_to is a safe internal relative path
if (empty($returnTo) || strpos($returnTo, '/HealthLogs/') !== 0) {
    $returnTo = '/HealthLogs/public/family_planning/records/index.php';
}

$patient_id = (int)($_POST['patient_id'] ?? 0);
$client_code = trim($_POST['client_code'] ?? '');
$registration_date = trim($_POST['registration_date'] ?? date('Y-m-d'));
$client_type = trim($_POST['client_type'] ?? 'new_acceptor');
$method_accepted = trim($_POST['method_accepted'] ?? 'pills_coc');
$source = trim($_POST['source'] ?? 'public');
$previous_method = trim($_POST['previous_method'] ?? '');
$partner_name = trim($_POST['partner_name'] ?? '');
$partner_occupation = trim($_POST['partner_occupation'] ?? '');
$num_living_children = (int)($_POST['num_living_children'] ?? 0);
$plan_more_children = trim($_POST['plan_more_children'] ?? 'undecided');
$remarks = trim($_POST['remarks'] ?? '');

$errors = [];

if ($patient_id <= 0) {
    $errors[] = 'Please select a patient to enroll.';
}
if (empty($client_code)) {
    $errors[] = 'Client code is required.';
}
if (empty($registration_date)) {
    $errors[] = 'Registration date is required.';
}

// Check if patient already has an active record
if (empty($errors)) {
    $chk = $pdo->prepare("SELECT id, client_code FROM fp_records WHERE patient_id = ? AND status = 'active'");
    $chk->execute([$patient_id]);
    if ($ex = $chk->fetch()) {
        $errors[] = "This patient already has an active Family Planning record ({$ex['client_code']}).";
    }
}

// Check if client_code is unique
if (empty($errors)) {
    $codeChk = $pdo->prepare("SELECT id FROM fp_records WHERE client_code = ?");
    $codeChk->execute([$client_code]);
    if ($codeChk->fetch()) {
        // Auto-increment code if collision
        $currYear = date('Y');
        $lastCode = $pdo->query("SELECT client_code FROM fp_records WHERE client_code LIKE 'FP-{$currYear}-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
        if ($lastCode && preg_match('/FP-\d{4}-(\d+)/', $lastCode, $m)) {
            $seq = (int)$m[1] + 1;
            $client_code = sprintf('FP-%s-%04d', $currYear, $seq);
        }
    }
}

if (!empty($errors)) {
    flash('error', implode(' ', $errors));
    header("Location: " . $returnTo);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO fp_records (
            patient_id, client_code, registration_date, client_type, method_accepted,
            source, previous_method, partner_name, partner_occupation,
            num_living_children, plan_more_children, status, remarks
        ) VALUES (
            :patient_id, :client_code, :registration_date, :client_type, :method_accepted,
            :source, :previous_method, :partner_name, :partner_occupation,
            :num_living_children, :plan_more_children, 'active', :remarks
        )
    ");
    $stmt->execute([
        'patient_id' => $patient_id,
        'client_code' => $client_code,
        'registration_date' => $registration_date,
        'client_type' => $client_type,
        'method_accepted' => $method_accepted,
        'source' => $source,
        'previous_method' => $previous_method ?: null,
        'partner_name' => $partner_name ?: null,
        'partner_occupation' => $partner_occupation ?: null,
        'num_living_children' => $num_living_children,
        'plan_more_children' => $plan_more_children,
        'remarks' => $remarks ?: null,
    ]);

    $newRecordId = (int)$pdo->lastInsertId();

    // Fetch patient name for friendly toast
    $pStmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE id = ?");
    $pStmt->execute([$patient_id]);
    $pRow = $pStmt->fetch();
    $pName = $pRow ? ($pRow['first_name'] . ' ' . $pRow['last_name']) : $client_code;

    flash('success', "Success: {$pName} enrolled with code [{$client_code}]!");

    header("Location: " . $returnTo);
    exit;
} catch (Throwable $e) {
    flash('error', 'Failed to enroll client: ' . $e->getMessage());
    header("Location: " . $returnTo);
    exit;
}
