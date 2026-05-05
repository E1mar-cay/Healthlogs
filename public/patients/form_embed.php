<?php
/**
 * Patient form inside modal iframe (<base target="_parent"> submits to top window).
 */
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$patient = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$id]);
    $patient = $stmt->fetch();
}

$title = $patient ? 'Edit Patient' : 'New Patient';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <base target="_parent" />
  <title><?= h($title) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 p-4 text-slate-900">
  <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm max-w-4xl mx-auto">
    <h1 class="text-lg font-semibold mb-3"><?= h($title) ?></h1>
    <?php display_flash_messages(true, false); ?>
    <?php display_validation_errors(true); ?>
    <form method="post" action="/HealthLogs/public/patients/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed" />
      <?php require __DIR__ . '/_form_fields.php'; ?>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
        <a class="text-slate-600" href="/HealthLogs/public/patients/index.php">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
