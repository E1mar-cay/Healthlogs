<?php
$pageTitle = 'User Management Test';
require __DIR__ . '/partials/bootstrap.php';

if (!in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header('Location: /HealthLogs/public/login.php');
    exit;
}

require __DIR__ . '/partials/header.php';

// Get users
$users = $pdo->query("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id")->fetchAll();
?>

<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">User Management Test</h1>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm"><?= $user['id'] ?></td>
                        <td class="px-6 py-4 text-sm"><?= h($user['username']) ?></td>
                        <td class="px-6 py-4 text-sm"><?= h($user['role_name']) ?></td>
                        <td class="px-6 py-4 text-sm"><?= h($user['status']) ?></td>
                        <td class="px-6 py-4 text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                <a href="/HealthLogs/public/users/form.php?id=<?= $user['id'] ?>" 
                                   class="text-blue-600 hover:text-blue-900 px-2 py-1 border border-blue-600 rounded">
                                    Edit
                                </a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?= $user['id'] ?>, '<?= h($user['username']) ?>')" 
                                            class="text-red-600 hover:text-red-900 px-2 py-1 border border-red-600 rounded">
                                        Delete
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-400 px-2 py-1">Current User</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-6">
        <a href="/HealthLogs/public/users/form.php" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
            Add New User
        </a>
    </div>
</div>

<script>
function deleteUser(userId, username) {
    if (confirm(`Are you sure you want to delete user "${username}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/HealthLogs/public/users/delete.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = userId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>