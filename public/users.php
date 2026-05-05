<?php
$pageTitle = 'User Management';
require __DIR__ . '/partials/bootstrap.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /HealthLogs/public/login.php');
    exit;
}

require __DIR__ . '/partials/header.php';

// Get search and pagination parameters
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build search query
$searchWhere = '';
$searchParams = [];
if ($search) {
    $searchWhere = "WHERE (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $searchParams = [$searchTerm, $searchTerm, $searchTerm];
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM users u $searchWhere";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($searchParams);
$totalRecords = $countStmt->fetch()['total'];

// Get users with pagination
$sql = "SELECT u.*, r.name as role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        $searchWhere 
        ORDER BY u.created_at DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($searchParams);
$users = $stmt->fetchAll();

// Get roles for dropdown
$rolesStmt = $pdo->query("SELECT * FROM roles WHERE name <> 'superadmin' ORDER BY name");
$roles = $rolesStmt->fetchAll();

// Calculate pagination
$totalPages = ceil($totalRecords / $limit);
$paginator = new Paginator($page, $totalPages, $totalRecords, $limit);
?>

<div class="flex flex-col gap-6">
    <?php display_flash_messages(); ?>

    <!-- Header -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
                <p class="text-gray-600 mt-1">Manage system users and their roles</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                    <?= $totalRecords ?> Total Users
                </span>
                <button type="button"
                        id="userModalOpenNew"
                        data-embed-url="/HealthLogs/public/users/form_embed.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add User
                </button>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white p-4 rounded-lg shadow">
        <form method="GET" class="flex gap-3">
            <div class="flex-1">
                <input type="text" 
                       name="search" 
                       value="<?= h($search) ?>" 
                       placeholder="Search users by username, name, or email..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" 
                    class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <?php if ($search): ?>
                <a href="/HealthLogs/public/users.php" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-users text-4xl mb-3 text-gray-300"></i>
                                <p class="text-lg font-medium">No users found</p>
                                <p class="text-sm">
                                    <?= $search ? 'Try adjusting your search criteria' : 'Start by adding your first user' ?>
                                </p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <span class="text-blue-600 font-medium text-sm">
                                                    <?php
                                                    $nameParts = explode(' ', trim($user['full_name'] ?? ''));
                                                    $initials = '';
                                                    if (count($nameParts) >= 2) {
                                                        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                                                    } else {
                                                        $initials = strtoupper(substr($nameParts[0] ?? 'U', 0, 2));
                                                    }
                                                    echo $initials;
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= h($user['full_name'] ?? 'Unknown User') ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                @<?= h($user['username']) ?>
                                                <?php if ($user['email']): ?>
                                                    • <?= h($user['email']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $roleColors = [
                                        'admin' => 'bg-blue-100 text-blue-800',
                                        'health_worker' => 'bg-green-100 text-green-800'
                                    ];
                                    $roleColor = $roleColors[$user['role_name']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $roleColor ?>">
                                        <?= h(ucfirst(str_replace('_', ' ', $user['role_name']))) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                class="user-modal-edit text-blue-600 hover:text-blue-900 transition-colors px-2 py-1 border border-blue-300 rounded hover:bg-blue-50"
                                                data-embed-url="/HealthLogs/public/users/form_embed.php?id=<?= (int)$user['id'] ?>"
                                                title="Edit User">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST"
                                                  action="/HealthLogs/public/users/delete.php"
                                                  class="inline"
                                                  data-confirm-title="Delete user"
                                                  data-confirm="Are you sure you want to delete user &quot;<?= h($user['username']) ?>&quot;? This action cannot be undone."
                                                  data-confirm-cta="Yes, delete user">
                                                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                                <button class="text-red-600 hover:text-red-900 transition-colors px-2 py-1 border border-red-300 rounded hover:bg-red-50"
                                                        title="Delete User">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-400 px-2 py-1 text-xs">Current User</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <?= $paginator->render('/HealthLogs/public/users.php', ['search' => $search]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="userFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
    <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="userFormModalBackdrop"></button>
    <div class="relative z-10 mx-auto mt-10 max-w-5xl px-4">
        <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
            <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
                <div class="text-sm font-semibold text-slate-800">User form</div>
                <button type="button" id="userFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
            </div>
            <iframe id="userFormModalFrame" class="w-full min-h-[70vh] border-0 flex-1" title="User form"></iframe>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('userFormModal');
    var frame = document.getElementById('userFormModalFrame');
    var backdrop = document.getElementById('userFormModalBackdrop');
    var closeBtn = document.getElementById('userFormModalClose');

    function openModal(url) {
        if (!modal || !frame || !url) return;
        frame.src = url;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        if (closeBtn) closeBtn.focus();
    }

    function closeModal() {
        if (!modal || !frame) return;
        frame.src = 'about:blank';
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    var newBtn = document.getElementById('userModalOpenNew');
    if (newBtn) {
        newBtn.addEventListener('click', function () {
            openModal(newBtn.getAttribute('data-embed-url') || '');
        });
    }

    document.querySelectorAll('.user-modal-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.getAttribute('data-embed-url') || '');
        });
    });

    if (backdrop) backdrop.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
