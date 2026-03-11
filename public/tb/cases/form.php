<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM tb_cases WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
}

$patients = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name ASC")->fetchAll();

$pageTitle = $rec ? 'Edit TB Case' : 'New TB Case';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <form method="post" action="/HealthLogs/public/tb/cases/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
      <label class="block text-sm text-slate-600">Case No</label>
      <input name="case_no" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['case_no'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Diagnosis Date</label>
      <input name="diagnosis_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['diagnosis_date'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Case Type</label>
      <?php $ctype = $rec['case_type'] ?? 'drug_susceptible'; ?>
      <select name="case_type" class="mt-1 w-full border rounded px-3 py-2">
        <option value="drug_susceptible" <?= $ctype === 'drug_susceptible' ? 'selected' : '' ?>>Drug Susceptible</option>
        <option value="drug_resistant" <?= $ctype === 'drug_resistant' ? 'selected' : '' ?>>Drug Resistant</option>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Status</label>
      <?php $status = $rec['status'] ?? 'active'; ?>
      <select name="status" class="mt-1 w-full border rounded px-3 py-2">
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
        <option value="defaulted" <?= $status === 'defaulted' ? 'selected' : '' ?>>Defaulted</option>
        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
        <option value="died" <?= $status === 'died' ? 'selected' : '' ?>>Died</option>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Treatment Start</label>
      <input name="treatment_start" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['treatment_start'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Treatment End</label>
      <input name="treatment_end" type="date" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['treatment_end'] ?? '') ?>" />
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm text-slate-600">Notes</label>
      <textarea name="notes" class="mt-1 w-full border rounded px-3 py-2" rows="2"><?= h($rec['notes'] ?? '') ?></textarea>
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/tb/cases/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
