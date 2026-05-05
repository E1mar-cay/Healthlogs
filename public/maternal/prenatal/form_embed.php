<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM prenatal_visits WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if (!$rec) {
        $_SESSION['error_message'] = 'Prenatal visit not found';
        header('Location: /HealthLogs/public/maternal/prenatal/index.php');
        exit;
    }
}
$pregs = $pdo->query("SELECT pr.id, p.first_name, p.last_name, pr.lmp_date FROM pregnancies pr JOIN patients p ON p.id = pr.patient_id ORDER BY pr.id DESC")->fetchAll();
$title = $rec ? 'Edit Prenatal Visit' : 'New Prenatal Visit';
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
      <p class="text-sm text-slate-500 mt-1">Document prenatal monitoring and notes.</p>
    </div>
    <?php display_flash_messages(); ?>
    <form method="post" action="/HealthLogs/public/maternal/prenatal/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed">
      <?php if ($rec): ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" /><?php endif; ?>
      <div>
        <label class="block text-sm text-slate-600">Pregnancy</label>
        <select name="pregnancy_id" required class="mt-1 w-full border rounded px-3 py-2">
          <?php foreach ($pregs as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= old('pregnancy_id', $rec['pregnancy_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>><?= h($p['last_name'] . ', ' . $p['first_name']) ?> (LMP: <?= h($p['lmp_date']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Visit Date/Time</label>
        <input name="visit_datetime" type="datetime-local" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('visit_datetime', str_replace(' ', 'T', $rec['visit_datetime'] ?? ''))) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Gestational Age (weeks)</label>
        <input name="gestational_age_weeks" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('gestational_age_weeks', $rec['gestational_age_weeks'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">BP Systolic</label>
        <input name="bp_systolic" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('bp_systolic', $rec['bp_systolic'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">BP Diastolic</label>
        <input name="bp_diastolic" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('bp_diastolic', $rec['bp_diastolic'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Weight (kg)</label>
        <input name="weight_kg" type="number" step="0.01" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('weight_kg', $rec['weight_kg'] ?? '')) ?>" />
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm text-slate-600">Notes</label>
        <textarea name="notes" class="mt-1 w-full border rounded px-3 py-2" rows="2"><?= h(old('notes', $rec['notes'] ?? '')) ?></textarea>
      </div>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
        <a class="text-slate-600" href="/HealthLogs/public/maternal/prenatal/index.php">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
