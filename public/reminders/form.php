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

$pageTitle = $rec ? 'Edit Reminder' : 'New Reminder';
require __DIR__ . '/../partials/header.php';
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">
  <?php display_flash_messages(true, true); ?>
  <?php display_validation_errors(); ?>
  <form method="post" action="/HealthLogs/public/reminders/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($rec): ?>
      <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
    <?php endif; ?>

    <div>
      <label class="block text-sm text-slate-600 mb-1">Patient</label>
      <select name="patient_id" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <?php foreach ($patients as $p): ?>
          <?php $sel = (old('patient_id', $rec['patient_id'] ?? 0) == $p['id']) ? 'selected' : ''; ?>
          <option value="<?= (int)$p['id'] ?>" <?= $sel ?>><?= h($p['last_name'] . ', ' . $p['first_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Type</label>
      <?php $type = old('reminder_type', $rec['reminder_type'] ?? 'immunization'); ?>
      <select name="reminder_type" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="immunization" <?= $type === 'immunization' ? 'selected' : '' ?>>Immunization</option>
        <option value="prenatal" <?= $type === 'prenatal' ? 'selected' : '' ?>>Prenatal</option>
        <option value="postnatal" <?= $type === 'postnatal' ? 'selected' : '' ?>>Postnatal</option>
        <option value="tb_monitoring" <?= $type === 'tb_monitoring' ? 'selected' : '' ?>>TB Monitoring</option>
        <option value="general" <?= $type === 'general' ? 'selected' : '' ?>>General</option>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Due Date</label>
      <input name="due_date" type="date" required class="w-full border rounded-lg px-3 py-2 text-sm" value="<?= h(old('due_date', $rec['due_date'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Status</label>
      <?php $status = old('status', $rec['status'] ?? 'pending'); ?>
      <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Sent</option>
        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm text-slate-600 mb-1">Message</label>
      <textarea name="message" class="w-full border rounded-lg px-3 py-2 text-sm" rows="2" required><?= h(old('message', $rec['message'] ?? '')) ?></textarea>
    </div>

    <div class="md:col-span-2 bg-teal-50 border border-teal-200 p-3 rounded-xl flex items-center gap-3">
      <input type="checkbox" name="send_sms_now" id="send_sms_now" value="1" class="h-4 w-4 text-teal-600 rounded border-slate-300 focus:ring-teal-500" />
      <label for="send_sms_now" class="text-sm font-medium text-teal-900 cursor-pointer">
        Send instant SMS notification to patient via TextBee immediately on save
      </label>
    </div>

    <div class="md:col-span-2 flex flex-col-reverse sm:flex-row items-center gap-3 pt-2">
      <a class="w-full sm:w-auto text-center px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition" href="/HealthLogs/public/reminders.php">Cancel</a>
      <button class="w-full sm:w-auto inline-flex items-center justify-center bg-slate-900 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition" type="submit">Save</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
