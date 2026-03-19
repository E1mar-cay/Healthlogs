<?php
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM reminders WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
}

$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name ASC")->fetchAll();

$pageTitle = $rec ? 'Edit Reminder' : 'New Reminder';
require __DIR__ . '/../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <form method="post" action="/HealthLogs/public/reminders/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
      <label class="block text-sm text-slate-600">Type</label>
      <?php $type = $rec['reminder_type'] ?? 'immunization'; ?>
      <select name="reminder_type" class="mt-1 w-full border rounded px-3 py-2">
        <option value="immunization" <?= $type === 'immunization' ? 'selected' : '' ?>>Immunization</option>
        <option value="prenatal" <?= $type === 'prenatal' ? 'selected' : '' ?>>Prenatal</option>
        <option value="postnatal" <?= $type === 'postnatal' ? 'selected' : '' ?>>Postnatal</option>
        <option value="general" <?= $type === 'general' ? 'selected' : '' ?>>General</option>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Due Date</label>
      <input name="due_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['due_date'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Status</label>
      <?php $status = $rec['status'] ?? 'pending'; ?>
      <select name="status" class="mt-1 w-full border rounded px-3 py-2">
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Sent</option>
        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm text-slate-600">Message</label>
      <textarea name="message" class="mt-1 w-full border rounded px-3 py-2" rows="2" required><?= h($rec['message'] ?? '') ?></textarea>
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/reminders.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
