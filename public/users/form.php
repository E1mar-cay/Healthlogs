<?php
$pageTitle = 'User Form';
require __DIR__ . '/../partials/bootstrap.php';

if (!in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: /HealthLogs/public/login.php');
    exit;
}

$isEdit = isset($_GET['id']);
$user = null;

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = 'User not found';
        header('Location: /HealthLogs/public/users.php');
        exit;
    }
}

$rolesStmt = $pdo->query("SELECT * FROM roles ORDER BY name");
$roles = $rolesStmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>

<div class="max-w-2xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    <?= $isEdit ? 'Edit User' : 'Add User' ?>
                </h1>
                <p class="text-gray-600 text-sm mt-1">
                    <?= $isEdit ? 'Update user information' : 'Create a new user account' ?>
                </p>
            </div>
            <a href="/HealthLogs/public/users.php" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <?php display_validation_errors(); ?>
        
        <form method="POST" action="/HealthLogs/public/users/save.php" class="space-y-5">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <?php endif; ?>

            <!-- Full Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="full_name" 
                       value="<?= h(old('full_name', $user['full_name'] ?? '')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Juan Dela Cruz"
                       required>
            </div>

            <!-- Username -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Username <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="username" 
                       value="<?= h(old('username', $user['username'] ?? '')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="juan_dela_cruz"
                       required>
                <p class="text-xs text-gray-500 mt-1">Letters, numbers, underscores only</p>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email
                </label>
                <input type="email" 
                       name="email" 
                       value="<?= h(old('email', $user['email'] ?? '')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="juan@example.com">
            </div>

            <!-- Role -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="role_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Select a role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= (old('role_id', $user['role_id'] ?? '') == $role['id']) ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $role['name'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Password <?= $isEdit ? '' : '<span class="text-red-500">*</span>' ?>
                </label>
                <input type="password" 
                       name="password" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Enter password"
                       <?= $isEdit ? '' : 'required' ?>>
                <p class="text-xs text-gray-500 mt-1">
                    <?= $isEdit ? 'Leave blank to keep current password' : 'Minimum 6 characters' ?>
                </p>
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Confirm Password <?= $isEdit ? '' : '<span class="text-red-500">*</span>' ?>
                </label>
                <input type="password" 
                       name="password_confirmation" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Confirm password"
                       <?= $isEdit ? '' : 'required' ?>>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Status <span class="text-red-500">*</span>
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="status" value="active" 
                               <?= (old('status', $user['status'] ?? 'active') === 'active') ? 'checked' : '' ?> 
                               class="w-4 h-4 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Active - User can login</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="status" value="inactive" 
                               <?= (old('status', $user['status'] ?? 'active') === 'inactive') ? 'checked' : '' ?> 
                               class="w-4 h-4 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Inactive - User cannot login</span>
                    </label>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 pt-4 border-t border-gray-200">
                <a href="/HealthLogs/public/users.php" 
                   class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-center font-medium">
                    Cancel
                </a>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    <?= $isEdit ? 'Update' : 'Create' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>