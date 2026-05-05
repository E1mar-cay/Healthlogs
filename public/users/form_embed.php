<?php
require __DIR__ . '/../partials/bootstrap.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
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
        $_SESSION['error_message'] = 'User not found';
        header('Location: /HealthLogs/public/users.php');
        exit;
    }
}

$rolesStmt = $pdo->query("SELECT * FROM roles WHERE name <> 'superadmin' ORDER BY name");
$roles = $rolesStmt->fetchAll();
$title = $isEdit ? 'Edit User' : 'Add User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base target="_parent">
    <title><?= h($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 p-4 text-slate-900">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-3xl mx-auto">
        <div class="mb-5">
            <h1 class="text-xl font-semibold text-slate-900"><?= h($title) ?></h1>
            <p class="text-sm text-slate-500 mt-1">
                <?= $isEdit ? 'Update user information and account access.' : 'Create a new system user account.' ?>
            </p>
        </div>

        <?php display_flash_messages(); ?>
        <?php display_validation_errors(); ?>

        <form method="POST" action="/HealthLogs/public/users/save.php" class="space-y-5">
            <input type="hidden" name="form_context" value="embed">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
            <?php endif; ?>

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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email"
                       name="email"
                       value="<?= h(old('email', $user['email'] ?? '')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="juan@example.com">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="role_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Select a role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int)$role['id'] ?>" <?= (old('role_id', $user['role_id'] ?? '') == $role['id']) ? 'selected' : '' ?>>
                            <?= h(ucfirst(str_replace('_', ' ', $role['name']))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

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

            <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                <a href="/HealthLogs/public/users.php"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-center font-medium">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    <?= $isEdit ? 'Update User' : 'Create User' ?>
                </button>
            </div>
        </form>
    </div>
</body>
</html>
