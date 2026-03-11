<?php
$pageTitle = 'TB Cases';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$sql = "SELECT c.*, p.first_name, p.last_name
        FROM tb_cases c
        JOIN patients p ON p.id = c.patient_id
        ORDER BY c.id DESC";
$rows = $pdo->query($sql)->fetchAll();
?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">TB Cases</div>
  <a href="/HealthLogs/public/tb/cases/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Case</a>
</div>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Patient</th>
        <th class="text-left px-4 py-2">Diagnosis</th>
        <th class="text-left px-4 py-2">Type</th>
        <th class="text-left px-4 py-2">Status</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td class="px-4 py-4" colspan="5">No cases found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['diagnosis_date']) ?></td>
            <td class="px-4 py-2"><?= h($r['case_type']) ?></td>
            <td class="px-4 py-2"><?= h($r['status']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/HealthLogs/public/tb/cases/form.php?id=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" action="/HealthLogs/public/tb/cases/delete.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                <button class="text-red-600 ml-2" data-confirm="Delete this case?">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>

