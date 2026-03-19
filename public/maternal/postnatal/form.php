<?php
require __DIR__ . '/../../partials/bootstrap.php';

$motherConditionOptions = ['Stable', 'Recovering Well', 'Needs Follow-Up', 'With Warning Signs', 'Referred'];
$babyConditionOptions = ['Healthy', 'Stable', 'Needs Follow-Up', 'Low Birth Weight', 'With Warning Signs', 'Referred'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM postnatal_visits WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
}

$pregs = $pdo->query("SELECT pr.id, p.first_name, p.last_name, pr.lmp_date FROM pregnancies pr JOIN patients p ON p.id = pr.patient_id ORDER BY pr.id DESC")->fetchAll();

$selectedMotherCondition = trim((string)($rec['mother_condition'] ?? ''));
if ($selectedMotherCondition !== '' && !in_array($selectedMotherCondition, $motherConditionOptions, true)) {
    $motherConditionOptions[] = $selectedMotherCondition;
}

$selectedBabyCondition = trim((string)($rec['baby_condition'] ?? ''));
if ($selectedBabyCondition !== '' && !in_array($selectedBabyCondition, $babyConditionOptions, true)) {
    $babyConditionOptions[] = $selectedBabyCondition;
}

$pageTitle = $rec ? 'Edit Postnatal Visit' : 'New Postnatal Visit';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <form method="post" action="/HealthLogs/public/maternal/postnatal/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($rec): ?>
      <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
    <?php endif; ?>

    <div>
      <label class="block text-sm text-slate-600">Pregnancy</label>
      <select name="pregnancy_id" required class="mt-1 w-full border rounded px-3 py-2">
        <?php foreach ($pregs as $p): ?>
          <?php $sel = ($rec['pregnancy_id'] ?? 0) == $p['id'] ? 'selected' : ''; ?>
          <option value="<?= (int)$p['id'] ?>" <?= $sel ?>><?= h($p['last_name'] . ', ' . $p['first_name']) ?> (LMP: <?= h($p['lmp_date']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Visit Date/Time</label>
      <input name="visit_datetime" type="datetime-local" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(str_replace(' ', 'T', $rec['visit_datetime'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Mother Condition</label>
      <select name="mother_condition" class="mt-1 w-full border rounded px-3 py-2">
        <option value="">Select mother condition</option>
        <?php foreach ($motherConditionOptions as $option): ?>
          <option value="<?= h($option) ?>" <?= (($rec['mother_condition'] ?? '') === $option) ? 'selected' : '' ?>><?= h($option) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Baby Condition</label>
      <select name="baby_condition" class="mt-1 w-full border rounded px-3 py-2">
        <option value="">Select baby condition</option>
        <?php foreach ($babyConditionOptions as $option): ?>
          <option value="<?= h($option) ?>" <?= (($rec['baby_condition'] ?? '') === $option) ? 'selected' : '' ?>><?= h($option) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm text-slate-600">Notes</label>
      <textarea name="notes" class="mt-1 w-full border rounded px-3 py-2" rows="2"><?= h($rec['notes'] ?? '') ?></textarea>
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/maternal/postnatal/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
