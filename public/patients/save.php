<?php
require __DIR__ . '/../partials/bootstrap.php';

function field($key, $default = null) {
    return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : $default;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Validate input
$validator = new Validator($_POST);
$validator
    ->validate('first_name', ['required', 'max' => 80], 'First name')
    ->validate('last_name', ['required', 'max' => 80], 'Last name')
    ->validate('middle_name', ['max' => 80], 'Middle name')
    ->validate('sex', ['required', 'sex'], 'Sex')
    ->validate('birth_date', ['required', 'date'], 'Birth date')
    ->validate('blood_type', ['blood_type'], 'Blood type')
    ->validate('email', ['email'], 'Email')
    ->validate('contact_no', ['phone'], 'Contact number')
    ->validate('barangay', ['required', 'max' => 120], 'Barangay')
    ->validate('city_municipality', ['required', 'max' => 120], 'City/Municipality')
    ->validate('province', ['required', 'max' => 120], 'Province')
    ->validate('status', ['required', 'in' => ['active', 'inactive', 'deceased']], 'Status');

if ($validator->fails()) {
    $_SESSION['validation_errors'] = $validator->allErrors();
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
$city_municipality = sanitize_string(field('city_municipality', ''));
$province = sanitize_string(field('province', ''));
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
    'city_municipality' => $city_municipality,
    'province' => $province,
    'status' => field('status', 'active'),
];

try {
    $pdo->beginTransaction();
    
    if ($id) {
        $sql = "UPDATE patients SET
                    household_id = ?, philhealth_no = ?, national_id = ?,
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    sex = ?, birth_date = ?, blood_type = ?,
                    contact_no = ?, email = ?, address_line = ?, barangay = ?,
                    city_municipality = ?, province = ?, status = ?
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
            $data['city_municipality'],
            $data['province'],
            $data['status'],
            $id,
        ]);
    } else {
        $sql = "INSERT INTO patients (
                    household_id, philhealth_no, national_id,
                    first_name, middle_name, last_name, suffix,
                    sex, birth_date, blood_type,
                    contact_no, email, address_line, barangay,
                    city_municipality, province, status
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
            $data['city_municipality'],
            $data['province'],
            $data['status'],
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
