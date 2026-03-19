<?php
/**
 * Patient Records Seeder
 * Generates realistic patient data for testing
 */

require_once __DIR__ . '/../config/db.php';

echo "Seeding patients...\n";

// Philippine common names
$firstNamesMale = ['Juan', 'Jose', 'Pedro', 'Antonio', 'Manuel', 'Francisco', 'Miguel', 'Ricardo', 'Roberto', 'Carlos', 'Ramon', 'Luis', 'Fernando', 'Eduardo', 'Mario', 'Rodrigo', 'Emilio', 'Rafael', 'Gabriel', 'Daniel'];
$firstNamesFemale = ['Maria', 'Ana', 'Rosa', 'Carmen', 'Teresa', 'Luz', 'Elena', 'Sofia', 'Isabel', 'Patricia', 'Angela', 'Cristina', 'Gloria', 'Josefa', 'Margarita', 'Beatriz', 'Victoria', 'Catalina', 'Dolores', 'Esperanza'];
$middleNames = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Mercado', 'Flores', 'Mendoza', 'Torres', 'Rivera', 'Ramos', 'Castillo', 'Aquino', 'Valdez', 'Santiago', 'Morales', 'Hernandez', 'Castro', 'Domingo', 'Pascual'];
$lastNames = ['Dela Cruz', 'Garcia', 'Reyes', 'Ramos', 'Mendoza', 'Santos', 'Gonzales', 'Flores', 'Villanueva', 'Castro', 'Martinez', 'Rivera', 'Torres', 'Bautista', 'Francisco', 'Lopez', 'Fernandez', 'Soriano', 'Aquino', 'Valdez', 'Santiago', 'Pascual', 'Mercado', 'Aguilar', 'Navarro'];
$suffixes = [null, null, null, null, null, 'Jr.', 'Sr.', 'II', 'III'];
$bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', null, null];
$barangays = ['Alinguigan', 'Bagong Bayan', 'Centro', 'Maligaya', 'San Isidro', 'San Jose', 'Santa Cruz', 'Santo Niño', 'Poblacion', 'Riverside'];
$streets = ['Rizal St.', 'Bonifacio Ave.', 'Mabini St.', 'Luna St.', 'Del Pilar St.', 'Aguinaldo Ave.', 'Quezon Blvd.', 'Roxas St.', 'Osmena Ave.', 'Magsaysay St.'];

// Generate 20 households first
echo "Creating households...\n";
$households = [];

// Check existing households to avoid duplicates
$existingCount = $pdo->query("SELECT COUNT(*) FROM households")->fetchColumn();
$startIndex = $existingCount;

for ($i = 0; $i < 20; $i++) {
    $householdCode = 'HH-' . date('Y') . '-' . str_pad($startIndex + $i + 1, 4, '0', STR_PAD_LEFT);
    
    // Check if household code already exists
    $exists = $pdo->prepare("SELECT id FROM households WHERE household_code = ?");
    $exists->execute([$householdCode]);
    $existingHousehold = $exists->fetch();
    if ($existingHousehold) {
        // Skip if exists, use existing ID
        $households[] = $existingHousehold['id'];
        continue;
    }
    
    $headName = ($i % 2 ? $firstNamesMale[array_rand($firstNamesMale)] : $firstNamesFemale[array_rand($firstNamesFemale)]) . ' ' . $lastNames[array_rand($lastNames)];
    
    $stmt = $pdo->prepare("INSERT INTO households (household_code, head_name, address_line, barangay, city_municipality, province) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $householdCode,
        $headName,
        $streets[array_rand($streets)] . ' ' . rand(1, 500),
        $barangays[array_rand($barangays)],
        'Ilagan',
        'Isabela'
    ]);
    $households[] = $pdo->lastInsertId();
}

// If no new households were created, get existing ones
if (empty($households)) {
    $households = $pdo->query("SELECT id FROM households LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
}

echo "✓ Available households: " . count($households) . "\n";

// Generate 100 patients
$patients = [];
for ($i = 0; $i < 100; $i++) {
    $sex = rand(0, 1) ? 'male' : 'female';
    $firstName = $sex === 'male' ? $firstNamesMale[array_rand($firstNamesMale)] : $firstNamesFemale[array_rand($firstNamesFemale)];
    $middleName = $middleNames[array_rand($middleNames)];
    $lastName = $lastNames[array_rand($lastNames)];
    $suffix = $suffixes[array_rand($suffixes)];
    
    // Generate birth date (ages 0-80 years)
    $age = rand(0, 80);
    $birthDate = date('Y-m-d', strtotime("-$age years -" . rand(0, 365) . " days"));
    
    // Generate contact info (70% have phone, 30% have email)
    $hasPhone = rand(1, 100) <= 70;
    $hasEmail = rand(1, 100) <= 30;
    $contactNo = $hasPhone ? '09' . rand(100000000, 999999999) : null;
    $email = $hasEmail ? strtolower($firstName . '.' . str_replace(' ', '', $lastName) . rand(1, 999) . '@gmail.com') : null;
    
    // Generate PhilHealth (60% have it)
    $philhealth = rand(1, 100) <= 60 ? rand(100000000000, 999999999999) : null;
    
    // Status distribution: 90% active, 8% inactive, 2% deceased
    $rand = rand(1, 100);
    if ($rand <= 90) {
        $status = 'active';
    } elseif ($rand <= 98) {
        $status = 'inactive';
    } else {
        $status = 'deceased';
    }
    
    // 60% chance of being assigned to a household
    $householdId = (rand(1, 100) <= 60 && !empty($households)) ? $households[array_rand($households)] : null;
    $patients[] = [
        'household_id' => $householdId,
        'philhealth_no' => $philhealth,
        'national_id' => null,
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'last_name' => $lastName,
        'suffix' => $suffix,
        'sex' => $sex,
        'birth_date' => $birthDate,
        'blood_type' => $bloodTypes[array_rand($bloodTypes)],
        'contact_no' => $contactNo,
        'email' => $email,
        'address_line' => $streets[array_rand($streets)] . ' ' . rand(1, 500),
        'barangay' => $barangays[array_rand($barangays)],
        'status' => $status
    ];
}

// Insert patients
$sql = "INSERT INTO patients (
    household_id, philhealth_no, national_id,
    first_name, middle_name, last_name, suffix,
    sex, birth_date, blood_type,
    contact_no, email, address_line, barangay, status
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $pdo->prepare($sql);

$pdo->beginTransaction();
try {
    foreach ($patients as $patient) {
        $stmt->execute([
            $patient['household_id'],
            $patient['philhealth_no'],
            $patient['national_id'],
            $patient['first_name'],
            $patient['middle_name'],
            $patient['last_name'],
            $patient['suffix'],
            $patient['sex'],
            $patient['birth_date'],
            $patient['blood_type'],
            $patient['contact_no'],
            $patient['email'],
            $patient['address_line'],
            $patient['barangay'],
            $patient['status']
        ]);
    }
    $pdo->commit();
    echo "✓ Successfully seeded " . count($patients) . " patients\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Error seeding patients: " . $e->getMessage() . "\n";
    exit(1);
}

// Add some patient allergies and conditions
echo "Seeding patient allergies and conditions...\n";

$allergies = ['Penicillin', 'Aspirin', 'Peanuts', 'Shellfish', 'Eggs', 'Milk', 'Soy', 'Wheat', 'Latex', 'Pollen'];
$conditions = ['Hypertension', 'Diabetes Type 2', 'Asthma', 'Arthritis', 'GERD', 'Migraine', 'Allergic Rhinitis', 'Hypothyroidism'];

// Get patient IDs
$patientIds = $pdo->query("SELECT id FROM patients WHERE status = 'active' ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);

// Add allergies to 20% of patients
foreach ($patientIds as $patientId) {
    if (rand(1, 100) <= 20) {
        $allergy = $allergies[array_rand($allergies)];
        $pdo->prepare("INSERT INTO patient_allergies (patient_id, allergen, reaction, noted_on) VALUES (?, ?, ?, ?)")
            ->execute([$patientId, $allergy, 'Mild to moderate reaction', date('Y-m-d', strtotime('-' . rand(30, 365) . ' days'))]);
    }
}

// Add conditions to 30% of patients
foreach ($patientIds as $patientId) {
    if (rand(1, 100) <= 30) {
        $condition = $conditions[array_rand($conditions)];
        $status = rand(1, 100) <= 70 ? 'chronic' : 'active';
        $pdo->prepare("INSERT INTO patient_conditions (patient_id, condition_name, status, diagnosed_on, notes) VALUES (?, ?, ?, ?, ?)")
            ->execute([$patientId, $condition, $status, date('Y-m-d', strtotime('-' . rand(90, 1825) . ' days')), 'Under monitoring']);
    }
}

echo "✓ Successfully seeded patient allergies and conditions\n";
echo "\nPatient seeding completed!\n";
