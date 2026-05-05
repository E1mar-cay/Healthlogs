<?php
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM reminders WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();

    if (!$rec) {
        $_SESSION['error_message'] = 'Reminder not found';
        header('Location: /HealthLogs/public/reminders.php');
        exit;
    }
}

$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name ASC")->fetchAll();
$title = $rec ? 'Edit Reminder' : 'New Reminder';
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
      <p class="text-sm text-slate-500 mt-1">Manage follow-up schedules and outreach timing.</p>
    </div>

    <?php display_flash_messages(); ?>
    <?php display_validation_errors(); ?>

    <form method="post" action="/HealthLogs/public/reminders/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed">
      <?php if ($rec): ?>
        <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
      <?php endif; ?>

      <div>
        <label class="block text-sm text-slate-600">Patient</label>
        <select name="patient_id" required class="mt-1 w-full border rounded px-3 py-2">
          <?php foreach ($patients as $p): ?>
            <?php $sel = (old('patient_id', $rec['patient_id'] ?? 0) == $p['id']) ? 'selected' : ''; ?>
            <option value="<?= (int)$p['id'] ?>" <?= $sel ?>><?= h($p['last_name'] . ', ' . $p['first_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Type</label>
        <?php $type = old('reminder_type', $rec['reminder_type'] ?? 'immunization'); ?>
        <select name="reminder_type" class="mt-1 w-full border rounded px-3 py-2">
          <option value="immunization" <?= $type === 'immunization' ? 'selected' : '' ?>>Immunization</option>
          <option value="prenatal" <?= $type === 'prenatal' ? 'selected' : '' ?>>Prenatal</option>
          <option value="postnatal" <?= $type === 'postnatal' ? 'selected' : '' ?>>Postnatal</option>
          <option value="general" <?= $type === 'general' ? 'selected' : '' ?>>General</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Due Date</label>
        <input name="due_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('due_date', $rec['due_date'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Status</label>
        <?php $status = old('status', $rec['status'] ?? 'pending'); ?>
        <select name="status" class="mt-1 w-full border rounded px-3 py-2">
          <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Sent</option>
          <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
          <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm text-slate-600">Message</label>
        <textarea name="message" class="mt-1 w-full border rounded px-3 py-2" rows="2" required><?= h(old('message', $rec['message'] ?? '')) ?></textarea>
      </div>

      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
        <a class="text-slate-600" href="/HealthLogs/public/reminders.php">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
