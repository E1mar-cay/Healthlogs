<?php
$pageTitle = 'Patient Record Management';
require __DIR__ . '/../partials/header.php';

$stmt = $pdo->query("SELECT * FROM patients ORDER BY id DESC");
$patients = $stmt->fetchAll();
$totalPatients = count($patients);
$activePatients = 0;
$inactivePatients = 0;
foreach ($patients as $patient) {
    $status = strtolower((string)$patient['status']);
    if ($status === 'active') {
        $activePatients++;
    } else {
        $inactivePatients++;
    }
}
?>

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

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Total</div>
    <div class="text-2xl font-semibold mt-2"><?= h($totalPatients) ?></div>
    <div class="text-sm text-slate-500 mt-1">Registered patients</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Active</div>
    <div class="text-2xl font-semibold mt-2"><?= h($activePatients) ?></div>
    <div class="text-sm text-slate-500 mt-1">Currently active</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Inactive</div>
    <div class="text-2xl font-semibold mt-2"><?= h($inactivePatients) ?></div>
    <div class="text-sm text-slate-500 mt-1">Dormant or archived</div>
  </div>
</div>

<div class="mt-6 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Name</th>
        <th class="text-left px-4 py-2">Sex</th>
        <th class="text-left px-4 py-2">Birth Date</th>
        <th class="text-left px-4 py-2">Barangay</th>
        <th class="text-left px-4 py-2">Status</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($patients)): ?>
        <tr><td class="px-4 py-4" colspan="6">No patients found.</td></tr>
      <?php else: ?>
        <?php foreach ($patients as $p): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($p['last_name'] . ', ' . $p['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($p['sex']) ?></td>
            <td class="px-4 py-2"><?= h($p['birth_date']) ?></td>
            <td class="px-4 py-2"><?= h($p['barangay']) ?></td>
            <td class="px-4 py-2"><?= h($p['status']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/HealthLogs/public/patients/form.php?id=<?= (int)$p['id'] ?>">Edit</a>
              <form method="post" action="/HealthLogs/public/patients/delete.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                <button class="text-red-600 ml-2" data-confirm="Delete this patient?">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>

