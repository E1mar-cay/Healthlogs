<?php
require __DIR__ . '/../partials/bootstrap.php';

// Check if user is superadmin or admin
if (!in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: /HealthLogs/public/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/users.php');
    exit;
}

$isEdit = !empty($_POST['id']);
$userId = $isEdit ? (int)$_POST['id'] : null;

// Validation rules
$rules = [
    'full_name' => 'required|max:120',
    'username' => 'required|min:3|max:50|username',
    'email' => 'email|max:120',
    'role_id' => 'required|numeric'
];

// Password validation (required for new users, optional for edit)
if (!$isEdit || !empty($_POST['password'])) {
    $rules['password'] = 'required|min:6|max:255';
    $rules['password_confirmation'] = 'required|same:password';
}

// Validate input
$validator = new Validator($_POST, $rules);

// Custom validation: Check if username is unique
if (!$validator->hasErrors('username')) {
    $checkSql = "SELECT id FROM users WHERE username = ?";
    $checkParams = [$_POST['username']];
    
    if ($isEdit) {
        $checkSql .= " AND id != ?";
        $checkParams[] = $userId;
    }
    
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($checkParams);
    
    if ($checkStmt->fetch()) {
        $validator->addError('username', 'Username already exists');
    }
}

// Custom validation: Check if role exists
if (!$validator->hasErrors('role_id')) {
    $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE id = ?");
    $roleStmt->execute([$_POST['role_id']]);
    
    if (!$roleStmt->fetch()) {
        $validator->addError('role_id', 'Invalid role selected');
    }
}

// If validation fails, redirect back with errors
if ($validator->hasErrors()) {
    $_SESSION['validation_errors'] = $validator->getErrors();
    $_SESSION['old_input'] = $_POST;
    
    $redirectUrl = $isEdit 
        ? "/HealthLogs/public/users/form.php?id=$userId"
        : "/HealthLogs/public/users/form.php";
    
    header("Location: $redirectUrl");
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Prepare data
    $data = [
        'full_name' => trim($_POST['full_name']),
        'username' => trim($_POST['username']),
        'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
        'role_id' => (int)$_POST['role_id'],
        'status' => $_POST['status'] ?? 'active'
    ];
    
    if ($isEdit) {
        // Update existing user
        $sql = "UPDATE users SET 
                full_name = ?, 
                username = ?, 
                email = ?, 
                role_id = ?, 
                status = ?";
        
        $params = [
            $data['full_name'],
            $data['username'],
            $data['email'],
            $data['role_id'],
            $data['status']
        ];
        
        // Add password if provided
        if (!empty($_POST['password'])) {
            $sql .= ", password_hash = ?";
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $userId;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $_SESSION['success'] = 'User updated successfully';
        
    } else {
        // Create new user
        $sql = "INSERT INTO users (full_name, username, email, password_hash, role_id, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['full_name'],
            $data['username'],
            $data['email'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            $data['role_id'],
            $data['status']
        ];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $_SESSION['success'] = 'User created successfully';
    }
    
    $pdo->commit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("User save error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while saving the user. Please try again.';
    
    $redirectUrl = $isEdit 
        ? "/HealthLogs/public/users/form.php?id=$userId"
        : "/HealthLogs/public/users/form.php";
    
    header("Location: $redirectUrl");
    exit;
}

// Clear old input on success
unset($_SESSION['old_input']);

header('Location: /HealthLogs/public/users.php');
exit;