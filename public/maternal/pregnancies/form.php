<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM pregnancies WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
}

$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name ASC")->fetchAll();

$pageTitle = $rec ? 'Edit Pregnancy' : 'New Pregnancy';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <form method="post" action="/HealthLogs/public/maternal/pregnancies/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
      <label class="block text-sm text-slate-600">LMP Date</label>
      <input name="lmp_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['lmp_date'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">EDD Date</label>
      <input name="edd_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['edd_date'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Gravida</label>
      <input name="gravida" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['gravida'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Para</label>
      <input name="para" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['para'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Status</label>
      <?php $status = $rec['status'] ?? 'ongoing'; ?>
      <select name="status" class="mt-1 w-full border rounded px-3 py-2">
        <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
        <option value="delivered" <?= $status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
        <option value="terminated" <?= $status === 'terminated' ? 'selected' : '' ?>>Terminated</option>
      </select>
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/maternal/pregnancies/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
