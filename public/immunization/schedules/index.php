<?php
$pageTitle = 'Immunization Schedules';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$sql = "SELECT s.*, p.first_name, p.last_name, v.name AS vaccine_name
        FROM immunization_schedule s
        JOIN patients p ON p.id = s.patient_id
        JOIN vaccines v ON v.id = s.vaccine_id
        ORDER BY s.scheduled_date DESC";
$rows = $pdo->query($sql)->fetchAll();
?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Immunization Schedules</div>
  <a href="/HealthLogs/public/immunization/schedules/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Schedule</a>
</div>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Patient</th>
        <th class="text-left px-4 py-2">Vaccine</th>
        <th class="text-left px-4 py-2">Dose</th>
        <th class="text-left px-4 py-2">Scheduled Date</th>
        <th class="text-left px-4 py-2">Status</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td class="px-4 py-4" colspan="6">No schedules found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $s): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($s['last_name'] . ', ' . $s['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($s['vaccine_name']) ?></td>
            <td class="px-4 py-2"><?= h($s['dose_no']) ?></td>
            <td class="px-4 py-2"><?= h($s['scheduled_date']) ?></td>
            <td class="px-4 py-2"><?= h($s['status']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/HealthLogs/public/immunization/schedules/form.php?id=<?= (int)$s['id'] ?>">Edit</a>
              <form method="post" action="/HealthLogs/public/immunization/schedules/delete.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>" />
                <button class="text-red-600 ml-2" data-confirm="Delete this schedule?">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>

