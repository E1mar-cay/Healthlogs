<?php
require __DIR__ . '/../partials/bootstrap.php';

function field($key, $default = null) {
    return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : $default;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Validate input using helper functions from app/Core/Validator.php
$validationErrors = [];
$firstNameRaw = field('first_name', '');
$lastNameRaw = field('last_name', '');
$middleNameRaw = field('middle_name');
$sexRaw = field('sex');
$birthDateRaw = field('birth_date');
$bloodTypeRaw = field('blood_type');
$emailRaw = field('email');
$contactNoRaw = field('contact_no');
$barangayRaw = field('barangay', '');
$statusRaw = field('status', 'active');

if (!validate_required($firstNameRaw) || !validate_max_length((string)$firstNameRaw, 80)) {
    $validationErrors['first_name'][] = 'First name is required and must not exceed 80 characters.';
}
if (!validate_required($lastNameRaw) || !validate_max_length((string)$lastNameRaw, 80)) {
    $validationErrors['last_name'][] = 'Last name is required and must not exceed 80 characters.';
}
if (!validate_max_length((string)$middleNameRaw, 80)) {
    $validationErrors['middle_name'][] = 'Middle name must not exceed 80 characters.';
}
if (!validate_sex($sexRaw)) {
    $validationErrors['sex'][] = 'Sex is required and must be male or female.';
}
if (!validate_required($birthDateRaw) || !validate_date($birthDateRaw)) {
    $validationErrors['birth_date'][] = 'Birth date is required and must be a valid date.';
}
if (!validate_blood_type($bloodTypeRaw)) {
    $validationErrors['blood_type'][] = 'Blood type is invalid.';
}
if (!validate_email($emailRaw)) {
    $validationErrors['email'][] = 'Email must be a valid email address.';
}
if (!validate_phone($contactNoRaw)) {
    $validationErrors['contact_no'][] = 'Contact number format is invalid.';
}
if (!validate_required($barangayRaw) || !validate_max_length((string)$barangayRaw, 120)) {
    $validationErrors['barangay'][] = 'Barangay is required and must not exceed 120 characters.';
}
if (!validate_in_array($statusRaw, ['active', 'inactive', 'deceased'])) {
    $validationErrors['status'][] = 'Status value is invalid.';
}

$conditionName = sanitize_string(field('condition_name'));
$diagnosedOn = field('diagnosed_on');
$conditionStatus = field('condition_status', 'active');
$conditionNotes = sanitize_string(field('condition_notes'));

if ($conditionName !== null && $conditionName !== '') {
    if (!validate_required($conditionName) || !validate_max_length($conditionName, 120)) {
        $validationErrors['condition_name'][] = 'Condition name is required and must not exceed 120 characters.';
    }
    if (!validate_date($diagnosedOn)) {
        $validationErrors['diagnosed_on'][] = 'Diagnosed on must be a valid date.';
    }
    if (!validate_in_array($conditionStatus, ['active', 'resolved', 'chronic'])) {
        $validationErrors['condition_status'][] = 'Condition status is invalid.';
    }
}

if (!empty($validationErrors)) {
    $flatErrors = [];
    foreach ($validationErrors as $fieldErrors) {
        if (is_array($fieldErrors)) {
            foreach ($fieldErrors as $msg) {
                $flatErrors[] = (string)$msg;
            }
        } else {
            $flatErrors[] = (string)$fieldErrors;
        }
    }
    $_SESSION['validation_errors'] = $flatErrors;
    $_SESSION['old_input'] = $_POST;
    header('Location: /HealthLogs/public/patients/form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

// Sanitize inputs
$first_name = sanitize_string(field('first_name', ''));
$middle_name = sanitize_string(field('middle_name'));
$last_name = sanitize_string(field('last_name', ''));
$suffix = sanitize_string(field('suffix'));
$email = sanitize_email(field('email'));
$contact_no = sanitize_phone(field('contact_no'));
$philhealth_no = sanitize_string(field('philhealth_no'));
$national_id = sanitize_string(field('national_id'));
$address_line = sanitize_string(field('address_line'));
$barangay = sanitize_string(field('barangay', ''));
$blood_type = sanitize_string(field('blood_type'));

// Normalize household_id to avoid FK errors
$household_id = field('household_id');
if ($household_id !== null) {
    $household_id = (int)$household_id;
    if ($household_id <= 0) {
        $household_id = null;
    } else {
        $chk = $pdo->prepare("SELECT id FROM households WHERE id = ? LIMIT 1");
        $chk->execute([$household_id]);
        if (!$chk->fetch()) {
            $household_id = null;
        }
    }
}

$data = [
    'household_id' => $household_id,
    'philhealth_no' => $philhealth_no,
    'national_id' => $national_id,
    'first_name' => $first_name,
    'middle_name' => $middle_name,
    'last_name' => $last_name,
    'suffix' => $suffix,
    'sex' => field('sex', 'male'),
    'birth_date' => field('birth_date', '2000-01-01'),
    'blood_type' => $blood_type,
    'contact_no' => $contact_no,
    'email' => $email,
    'address_line' => $address_line,
    'barangay' => $barangay,
    'status' => field('status', 'active'),
];

try {
    $pdo->beginTransaction();
    
    $patientId = $id;

    if ($id) {
        $sql = "UPDATE patients SET
                    household_id = ?, philhealth_no = ?, national_id = ?,
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    sex = ?, birth_date = ?, blood_type = ?,
                    contact_no = ?, email = ?, address_line = ?, barangay = ?, status = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['household_id'],
            $data['philhealth_no'],
            $data['national_id'],
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['suffix'],
            $data['sex'],
            $data['birth_date'],
            $data['blood_type'],
            $data['contact_no'],
            $data['email'],
            $data['address_line'],
            $data['barangay'],
            $data['status'],
            $id,
        ]);
    } else {
        $sql = "INSERT INTO patients (
                    household_id, philhealth_no, national_id,
                    first_name, middle_name, last_name, suffix,
                    sex, birth_date, blood_type,
                    contact_no, email, address_line, barangay, status
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['household_id'],
            $data['philhealth_no'],
            $data['national_id'],
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['suffix'],
            $data['sex'],
            $data['birth_date'],
            $data['blood_type'],
            $data['contact_no'],
            $data['email'],
            $data['address_line'],
            $data['barangay'],
            $data['status'],
        ]);
        $patientId = (int)$pdo->lastInsertId();
    }

    if ($patientId > 0 && $conditionName !== null && $conditionName !== '') {
        $condSql = "INSERT INTO patient_conditions (patient_id, condition_name, status, diagnosed_on, notes)
                    VALUES (?, ?, ?, ?, ?)";
        $condStmt = $pdo->prepare($condSql);
        $condStmt->execute([
            $patientId,
            $conditionName,
            in_array($conditionStatus, ['active', 'resolved', 'chronic'], true) ? $conditionStatus : 'active',
            ($diagnosedOn !== null && $diagnosedOn !== '') ? $diagnosedOn : null,
            $conditionNotes,
        ]);
    }
    
    $pdo->commit();
    $_SESSION['success_message'] = 'Patient saved successfully!';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = 'Failed to save patient: ' . $e->getMessage();
}

header('Location: /HealthLogs/public/patients/index.php');
exit;
