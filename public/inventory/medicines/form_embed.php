<?php
require __DIR__ . '/../../partials/bootstrap.php';

$unitOptions = ['tablet', 'capsule', 'bottle', 'vial', 'ampule', 'sachet', 'tube', 'drop', 'ml', 'mg', 'g', 'piece'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();

    if (!$rec) {
        $_SESSION['error_message'] = 'Medicine not found';
        header('Location: /HealthLogs/public/inventory/medicines/index.php');
        exit;
    }
}

$title = $rec ? 'Edit Medicine' : 'New Medicine';
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
      <p class="text-sm text-slate-500 mt-1">Create or update a medicine profile.</p>
    </div>
    <?php display_flash_messages(); ?>
    <form method="post" action="/HealthLogs/public/inventory/medicines/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed">
      <?php if ($rec): ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" /><?php endif; ?>
      <div>
        <label class="block text-sm text-slate-600">Medicine Name</label>
        <input name="name" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('name', $rec['name'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Generic Name (Optional)</label>
        <input name="generic_name" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('generic_name', $rec['generic_name'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Medicine Type</label>
        <input name="formulation" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('formulation', $rec['formulation'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Dose / Strength</label>
        <input name="strength" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('strength', $rec['strength'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Stock Unit</label>
        <select name="unit" required class="mt-1 w-full border rounded px-3 py-2">
          <option value="">Select unit</option>
          <?php foreach ($unitOptions as $unitOption): ?>
            <option value="<?= h($unitOption) ?>" <?= old('unit', $rec['unit'] ?? '') === $unitOption ? 'selected' : '' ?>><?= h(ucfirst($unitOption)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Low Stock Alert</label>
        <input name="reorder_level" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('reorder_level', $rec['reorder_level'] ?? '')) ?>" />
        <div class="mt-1 text-xs text-slate-500">Show a warning when stock reaches this number or lower.</div>
      </div>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
        <a class="text-slate-600" href="/HealthLogs/public/inventory/medicines/index.php">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
