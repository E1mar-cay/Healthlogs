<?php
require __DIR__ . '/../partials/bootstrap.php';

function field($key, $default = null) {
    return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : $default;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

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
    'philhealth_no' => field('philhealth_no'),
    'national_id' => field('national_id'),
    'first_name' => field('first_name', ''),
    'middle_name' => field('middle_name'),
    'last_name' => field('last_name', ''),
    'suffix' => field('suffix'),
    'sex' => field('sex', 'male'),
    'birth_date' => field('birth_date', '2000-01-01'),
    'blood_type' => field('blood_type'),
    'contact_no' => field('contact_no'),
    'email' => field('email'),
    'address_line' => field('address_line'),
    'barangay' => field('barangay', ''),
    'city_municipality' => field('city_municipality', ''),
    'province' => field('province', ''),
    'status' => field('status', 'active'),
];

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

header('Location: /HealthLogs/public/patients/index.php');
exit;
