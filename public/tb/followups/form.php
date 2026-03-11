<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM tb_followups WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
}

$cases = $pdo->query("SELECT c.id, p.first_name, p.last_name, c.diagnosis_date FROM tb_cases c JOIN patients p ON p.id = c.patient_id ORDER BY c.id DESC")->fetchAll();

$pageTitle = $rec ? 'Edit TB Follow-up' : 'New TB Follow-up';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <form method="post" action="/HealthLogs/public/tb/followups/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($rec): ?>
      <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
    <?php endif; ?>

    <div>
      <label class="block text-sm text-slate-600">TB Case</label>
      <select name="tb_case_id" required class="mt-1 w-full border rounded px-3 py-2">
        <?php foreach ($cases as $c): ?>
          <?php $sel = ($rec['tb_case_id'] ?? 0) == $c['id'] ? 'selected' : ''; ?>
          <option value="<?= (int)$c['id'] ?>" <?= $sel ?>><?= h($c['last_name'] . ', ' . $c['first_name']) ?> (Dx: <?= h($c['diagnosis_date']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Follow-up Date/Time</label>
      <input name="followup_datetime" type="datetime-local" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(str_replace(' ', 'T', $rec['followup_datetime'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Adherence</label>
      <?php $adh = $rec['adherence'] ?? 'good'; ?>
      <select name="adherence" class="mt-1 w-full border rounded px-3 py-2">
        <option value="good" <?= $adh === 'good' ? 'selected' : '' ?>>Good</option>
        <option value="poor" <?= $adh === 'poor' ? 'selected' : '' ?>>Poor</option>
        <option value="missed" <?= $adh === 'missed' ? 'selected' : '' ?>>Missed</option>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Weight (kg)</label>
      <input name="weight_kg" type="number" step="0.01" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['weight_kg'] ?? '') ?>" />
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm text-slate-600">Symptoms</label>
      <textarea name="symptoms" class="mt-1 w-full border rounded px-3 py-2" rows="2"><?= h($rec['symptoms'] ?? '') ?></textarea>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm text-slate-600">Notes</label>
      <textarea name="notes" class="mt-1 w-full border rounded px-3 py-2" rows="2"><?= h($rec['notes'] ?? '') ?></textarea>
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/tb/followups/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
