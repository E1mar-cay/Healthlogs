<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM vaccines WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if (!$rec) {
        $_SESSION['error_message'] = 'Vaccine not found';
        header('Location: /HealthLogs/public/immunization/vaccines/index.php');
        exit;
    }
}
$title = $rec ? 'Edit Vaccine' : 'New Vaccine';
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
      <p class="text-sm text-slate-500 mt-1">Create or update a vaccine profile.</p>
    </div>
    <?php display_flash_messages(); ?>
    <form method="post" action="/HealthLogs/public/immunization/vaccines/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed">
      <?php if ($rec): ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" /><?php endif; ?>
      <div>
        <label class="block text-sm text-slate-600">Name</label>
        <input name="name" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('name', $rec['name'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Code</label>
        <input name="code" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('code', $rec['code'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Min Age (months)</label>
        <input name="recommended_min_age_months" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('recommended_min_age_months', $rec['recommended_min_age_months'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Max Age (months)</label>
        <input name="recommended_max_age_months" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('recommended_max_age_months', $rec['recommended_max_age_months'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Doses Required</label>
        <input name="doses_required" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('doses_required', $rec['doses_required'] ?? '')) ?>" />
      </div>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
        <a class="text-slate-600" href="/HealthLogs/public/immunization/vaccines/index.php">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
