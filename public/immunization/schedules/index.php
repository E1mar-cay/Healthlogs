<?php
$pageTitle = 'Immunization Schedules';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

// Get total count
$totalRecords = $pdo->query("SELECT COUNT(*) FROM immunization_schedule")->fetchColumn();

// Create paginator
$paginator = paginate($totalRecords, 20);

// Get schedules with pagination
$sql = "SELECT s.*, p.first_name, p.last_name, p.birth_date, v.name AS vaccine_name
        FROM immunization_schedule s
        JOIN patients p ON p.id = s.patient_id
        JOIN vaccines v ON v.id = s.vaccine_id
        ORDER BY s.scheduled_date DESC " . $paginator->getLimitSql();
$rows = $pdo->query($sql)->fetchAll();

// Get statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed
    FROM immunization_schedule
")->fetch();
?>

<?php display_flash_messages(); ?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Immunization Module</div>
      <div class="text-2xl font-semibold">Immunization Schedules</div>
      <p class="text-sm text-slate-500 mt-1">Track and manage vaccination schedules for all patients.</p>
    </div>
    <a href="/HealthLogs/public/immunization/schedules/form.php" class="bg-slate-900 text-white px-4 py-2 rounded-lg shadow">New Schedule</a>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Total</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['total']) ?></div>
    <div class="text-sm text-slate-500 mt-1">All schedules</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Scheduled</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['scheduled']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Upcoming</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Completed</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['completed']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Administered</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Missed</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['missed']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Overdue</div>
  </div>
</div>

<div class="mt-6 bg-white rounded shadow">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="text-left px-4 py-3">Patient</th>
          <th class="text-left px-4 py-3">Age</th>
          <th class="text-left px-4 py-3">Vaccine</th>
          <th class="text-left px-4 py-3">Dose</th>
          <th class="text-left px-4 py-3">Scheduled Date</th>
          <th class="text-left px-4 py-3">Status</th>
          <th class="text-left px-4 py-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td class="px-4 py-4 text-center text-slate-500" colspan="7">No schedules found.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $s): ?>
            <?php
              $birthDate = new DateTime($s['birth_date']);
              $today = new DateTime();
              $ageMonths = $birthDate->diff($today)->m + ($birthDate->diff($today)->y * 12);
              
              $statusColors = [
                'scheduled' => 'bg-blue-100 text-blue-800',
                'completed' => 'bg-green-100 text-green-800',
                'missed' => 'bg-red-100 text-red-800',
                'cancelled' => 'bg-gray-100 text-gray-800'
              ];
              $statusColor = $statusColors[$s['status']] ?? 'bg-gray-100 text-gray-800';
            ?>
            <tr class="border-t hover:bg-slate-50">
              <td class="px-4 py-3">
                <div class="font-medium"><?= h($s['last_name'] . ', ' . $s['first_name']) ?></div>
              </td>
              <td class="px-4 py-3"><?= h($ageMonths) ?> mos</td>
              <td class="px-4 py-3"><?= h($s['vaccine_name']) ?></td>
              <td class="px-4 py-3">Dose <?= h($s['dose_no']) ?></td>
              <td class="px-4 py-3"><?= h($s['scheduled_date']) ?></td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $statusColor ?>">
                  <?= h(ucfirst($s['status'])) ?>
                </span>
              </td>
              <td class="px-4 py-3">
                <a class="text-blue-600 hover:text-blue-800 font-medium" href="/HealthLogs/public/immunization/schedules/form.php?id=<?= (int)$s['id'] ?>">Edit</a>
                <form method="post" action="/HealthLogs/public/immunization/schedules/delete.php" class="inline" data-confirm="Delete this schedule?">
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>" />
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

<?php require __DIR__ . '/../../partials/footer.php'; ?>
