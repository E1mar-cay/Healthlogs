<?php
$pageTitle = 'Patient Record Management';
require __DIR__ . '/../partials/header.php';

// Get total count
$totalRecords = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

// Create paginator
$paginator = paginate($totalRecords, 20);

// Get patients with pagination
$stmt = $pdo->prepare("SELECT * FROM patients ORDER BY id DESC " . $paginator->getLimitSql());
$stmt->execute();
$patients = $stmt->fetchAll();

// Calculate statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN status = 'deceased' THEN 1 ELSE 0 END) as deceased
    FROM patients
")->fetch();
?>

<?php display_flash_messages(); ?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Module</div>
      <div class="text-2xl font-semibold">Patient Records</div>
      <p class="text-sm text-slate-500 mt-1">Maintain core demographics, status, and barangay coverage.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">Patient Intake</span>
      <a href="/HealthLogs/public/patients/form.php" class="bg-slate-900 text-white px-4 py-2 rounded-lg shadow">New Patient</a>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Total</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['total']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Registered patients</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Active</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['active']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Currently active</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Inactive</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['inactive']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Dormant or archived</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Deceased</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['deceased']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Deceased records</div>
  </div>
</div>

<div class="mt-6 bg-white rounded shadow">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="text-left px-4 py-3">ID</th>
          <th class="text-left px-4 py-3">Name</th>
          <th class="text-left px-4 py-3">Sex</th>
          <th class="text-left px-4 py-3">Birth Date</th>
          <th class="text-left px-4 py-3">Age</th>
          <th class="text-left px-4 py-3">Barangay</th>
          <th class="text-left px-4 py-3">Contact</th>
          <th class="text-left px-4 py-3">Status</th>
          <th class="text-left px-4 py-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($patients)): ?>
          <tr><td class="px-4 py-4 text-center text-slate-500" colspan="9">No patients found.</td></tr>
        <?php else: ?>
          <?php foreach ($patients as $p): ?>
            <?php
              $birthDate = new DateTime($p['birth_date']);
              $today = new DateTime();
              $age = $birthDate->diff($today)->y;
              
              $statusColors = [
                'active' => 'bg-green-100 text-green-800',
                'inactive' => 'bg-yellow-100 text-yellow-800',
                'deceased' => 'bg-gray-100 text-gray-800'
              ];
              $statusColor = $statusColors[$p['status']] ?? 'bg-gray-100 text-gray-800';
            ?>
            <tr class="border-t hover:bg-slate-50">
              <td class="px-4 py-3 font-medium"><?= h($p['id']) ?></td>
              <td class="px-4 py-3">
                <div class="font-medium"><?= h($p['last_name'] . ', ' . $p['first_name']) ?></div>
                <?php if ($p['middle_name']): ?>
                  <div class="text-xs text-slate-500"><?= h($p['middle_name']) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 capitalize"><?= h($p['sex']) ?></td>
              <td class="px-4 py-3"><?= h($p['birth_date']) ?></td>
              <td class="px-4 py-3"><?= h($age) ?> yrs</td>
              <td class="px-4 py-3"><?= h($p['barangay']) ?></td>
              <td class="px-4 py-3">
                <?php if ($p['contact_no']): ?>
                  <div class="text-xs"><?= h($p['contact_no']) ?></div>
                <?php endif; ?>
                <?php if ($p['email']): ?>
                  <div class="text-xs text-slate-500"><?= h($p['email']) ?></div>
                <?php endif; ?>
                <?php if (!$p['contact_no'] && !$p['email']): ?>
                  <span class="text-xs text-slate-400">No contact</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $statusColor ?>">
                  <?= h(ucfirst($p['status'])) ?>
                </span>
              </td>
              <td class="px-4 py-3">
                <a class="text-blue-600 hover:text-blue-800 font-medium" href="/HealthLogs/public/patients/form.php?id=<?= (int)$p['id'] ?>">Edit</a>
                <form method="post" action="/HealthLogs/public/patients/delete.php" class="inline" data-confirm="Delete this patient and all related records?" data-confirm-title="Delete patient">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                  <button class="text-red-600 hover:text-red-800 ml-3 font-medium">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?= $paginator->render() ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
