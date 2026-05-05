<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM pregnancies WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if (!$rec) {
        $_SESSION['error_message'] = 'Pregnancy record not found';
        header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
        exit;
    }
}
$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name ASC")->fetchAll();
$title = $rec ? 'Edit Pregnancy' : 'New Pregnancy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <base target="_parent">
  <title><?= h($title) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 p-4 text-slate-900">
  <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm max-w-3xl mx-auto">
    <div class="mb-5">
      <h1 class="text-xl font-semibold"><?= h($title) ?></h1>
      <p class="text-sm text-slate-500 mt-1">Register or update a pregnancy record.</p>
    </div>
    <?php display_flash_messages(); ?>
    <form method="post" action="/HealthLogs/public/maternal/pregnancies/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed">
      <?php if ($rec): ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" /><?php endif; ?>
      <div>
        <label class="block text-sm text-slate-600">Mother / Patient</label>
        <select name="patient_id" required class="mt-1 w-full border rounded px-3 py-2">
          <?php foreach ($patients as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= old('patient_id', $rec['patient_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>><?= h($p['last_name'] . ', ' . $p['first_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Last Menstrual Period</label>
        <input name="lmp_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('lmp_date', $rec['lmp_date'] ?? '')) ?>" />
        <div class="mt-1 text-xs text-slate-500">Start date of the last menstrual period.</div>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Expected Delivery Date</label>
        <input name="edd_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('edd_date', $rec['edd_date'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Total Pregnancies</label>
        <input name="gravida" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('gravida', $rec['gravida'] ?? '')) ?>" />
        <div class="mt-1 text-xs text-slate-500">How many times the patient has been pregnant.</div>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Births</label>
        <input name="para" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('para', $rec['para'] ?? '')) ?>" />
        <div class="mt-1 text-xs text-slate-500">Number of pregnancies that reached delivery.</div>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Pregnancy Status</label>
        <?php $status = old('status', $rec['status'] ?? 'ongoing'); ?>
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
</body>
</html>
