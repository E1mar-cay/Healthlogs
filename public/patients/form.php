<?php
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$patient = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$id]);
    $patient = $stmt->fetch();
}

$pageTitle = $patient ? 'Edit Patient' : 'New Patient';
require __DIR__ . '/../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <?php display_flash_messages(true, true); ?>
  <?php display_validation_errors(true); ?>
  <form method="post" action="/HealthLogs/public/patients/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" name="form_context" value="full" />
    <?php require __DIR__ . '/_form_fields.php'; ?>
    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/patients/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
