<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM immunization_schedule WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if (!$rec) {
        $_SESSION['error_message'] = 'Schedule not found';
        header('Location: /HealthLogs/public/immunization/schedules/index.php');
        exit;
    }
}
$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name ASC")->fetchAll();
$vaccines = $pdo->query("SELECT id, name FROM vaccines ORDER BY name ASC")->fetchAll();
$title = $rec ? 'Edit Schedule' : 'New Schedule';
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
  <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm max-w-4xl mx-auto">
    <div class="mb-5">
      <h1 class="text-xl font-semibold"><?= h($title) ?></h1>
      <p class="text-sm text-slate-500 mt-1">Plan and track upcoming vaccine doses.</p>
    </div>
    <?php display_flash_messages(); ?>
    <form method="post" action="/HealthLogs/public/immunization/schedules/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed">
      <?php if ($rec): ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" /><?php endif; ?>
      <div>
        <label class="block text-sm text-slate-600">Patient</label>
        <select name="patient_id" required class="mt-1 w-full border rounded px-3 py-2">
          <?php foreach ($patients as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= old('patient_id', $rec['patient_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>><?= h($p['last_name'] . ', ' . $p['first_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Vaccine</label>
        <select name="vaccine_id" required class="mt-1 w-full border rounded px-3 py-2">
          <?php foreach ($vaccines as $v): ?>
            <option value="<?= (int)$v['id'] ?>" <?= old('vaccine_id', $rec['vaccine_id'] ?? 0) == $v['id'] ? 'selected' : '' ?>><?= h($v['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Dose No</label>
        <input name="dose_no" type="number" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('dose_no', $rec['dose_no'] ?? 1)) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Scheduled Date</label>
        <input name="scheduled_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('scheduled_date', $rec['scheduled_date'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Status</label>
        <?php $status = old('status', $rec['status'] ?? 'scheduled'); ?>
        <select name="status" class="mt-1 w-full border rounded px-3 py-2">
          <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
          <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
          <option value="missed" <?= $status === 'missed' ? 'selected' : '' ?>>Missed</option>
          <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
        <a class="text-slate-600" href="/HealthLogs/public/immunization/schedules/index.php">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
