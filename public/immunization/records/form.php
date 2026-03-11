<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM immunization_records WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
}

$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name ASC")->fetchAll();
$vaccines = $pdo->query("SELECT id, name FROM vaccines ORDER BY name ASC")->fetchAll();

$pageTitle = $rec ? 'Edit Immunization Record' : 'New Immunization Record';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <form method="post" action="/HealthLogs/public/immunization/records/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($rec): ?>
      <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
    <?php endif; ?>

    <div>
      <label class="block text-sm text-slate-600">Patient</label>
      <select name="patient_id" required class="mt-1 w-full border rounded px-3 py-2">
        <?php foreach ($patients as $p): ?>
          <?php $sel = ($rec['patient_id'] ?? 0) == $p['id'] ? 'selected' : ''; ?>
          <option value="<?= (int)$p['id'] ?>" <?= $sel ?>><?= h($p['last_name'] . ', ' . $p['first_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Vaccine</label>
      <select name="vaccine_id" required class="mt-1 w-full border rounded px-3 py-2">
        <?php foreach ($vaccines as $v): ?>
          <?php $sel = ($rec['vaccine_id'] ?? 0) == $v['id'] ? 'selected' : ''; ?>
          <option value="<?= (int)$v['id'] ?>" <?= $sel ?>><?= h($v['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Dose No</label>
      <input name="dose_no" type="number" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['dose_no'] ?? 1) ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Administered On</label>
      <input name="administered_on" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['administered_on'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Administered At</label>
      <input name="administered_at" type="datetime-local" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(str_replace(' ', 'T', $rec['administered_at'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Lot No</label>
      <input name="lot_no" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['lot_no'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Notes</label>
      <textarea name="notes" class="mt-1 w-full border rounded px-3 py-2" rows="2"><?= h($rec['notes'] ?? '') ?></textarea>
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/immunization/records/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
