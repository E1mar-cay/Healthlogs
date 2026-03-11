<?php
$pageTitle = 'TB Follow-ups';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$sql = "SELECT f.*, p.first_name, p.last_name
        FROM tb_followups f
        JOIN tb_cases c ON c.id = f.tb_case_id
        JOIN patients p ON p.id = c.patient_id
        ORDER BY f.followup_datetime DESC";
$rows = $pdo->query($sql)->fetchAll();
?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">TB Follow-ups</div>
  <a href="/HealthLogs/public/tb/followups/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Follow-up</a>
</div>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Patient</th>
        <th class="text-left px-4 py-2">Date</th>
        <th class="text-left px-4 py-2">Adherence</th>
        <th class="text-left px-4 py-2">Weight</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td class="px-4 py-4" colspan="5">No follow-ups found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['followup_datetime']) ?></td>
            <td class="px-4 py-2"><?= h($r['adherence']) ?></td>
            <td class="px-4 py-2"><?= h($r['weight_kg']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/HealthLogs/public/tb/followups/form.php?id=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" action="/HealthLogs/public/tb/followups/delete.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                <button class="text-red-600 ml-2" data-confirm="Delete this follow-up?">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>

