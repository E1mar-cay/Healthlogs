<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM immunization_records WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if (!$rec) {
        $_SESSION['error_message'] = 'Immunization record not found';
        header('Location: /HealthLogs/public/immunization/records/index.php');
        exit;
    }
}
$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name ASC")->fetchAll();
$vaccines = $pdo->query("SELECT id, name FROM vaccines ORDER BY name ASC")->fetchAll();
$title = $rec ? 'Edit Immunization Record' : 'New Immunization Record';
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
      <p class="text-sm text-slate-500 mt-1">Capture administered doses and vaccine details.</p>
    </div>
    <?php display_flash_messages(); ?>
    <form method="post" action="/HealthLogs/public/immunization/records/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
        <label class="block text-sm text-slate-600">Administered On</label>
        <input name="administered_on" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('administered_on', $rec['administered_on'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Administered At</label>
        <input name="administered_at" type="datetime-local" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('administered_at', str_replace(' ', 'T', $rec['administered_at'] ?? ''))) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Lot No</label>
        <input name="lot_no" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('lot_no', $rec['lot_no'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Notes</label>
        <textarea name="notes" class="mt-1 w-full border rounded px-3 py-2" rows="2"><?= h(old('notes', $rec['notes'] ?? '')) ?></textarea>
      </div>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
        <a class="text-slate-600" href="/HealthLogs/public/immunization/records/index.php">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
