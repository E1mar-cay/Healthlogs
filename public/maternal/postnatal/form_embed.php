<?php
require __DIR__ . '/../../partials/bootstrap.php';

$motherConditionOptions = ['Stable', 'Recovering Well', 'Needs Follow-Up', 'With Warning Signs', 'Referred'];
$babyConditionOptions = ['Healthy', 'Stable', 'Needs Follow-Up', 'Low Birth Weight', 'With Warning Signs', 'Referred'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selectedPregnancyId = (int)($_GET['pregnancy_id'] ?? 0);
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM postnatal_visits WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if (!$rec) {
        $_SESSION['error_message'] = 'Postnatal visit not found';
        header('Location: /HealthLogs/public/maternal/postnatal/index.php');
        exit;
    }
}
$linkedPregnancyId = $rec ? (int)$rec['pregnancy_id'] : $selectedPregnancyId;
$pregStmt = $pdo->prepare("
    SELECT pr.id, pr.lmp_date, pr.edd_date, pr.status, p.first_name, p.last_name
    FROM pregnancies pr
    JOIN patients p ON p.id = pr.patient_id
    WHERE pr.status = 'delivered' OR pr.id = ?
    ORDER BY pr.id DESC
");
$pregStmt->execute([$linkedPregnancyId]);
$pregs = $pregStmt->fetchAll(PDO::FETCH_ASSOC);
$selectedPregnancy = null;
foreach ($pregs as $pregnancy) {
    if ((int)$pregnancy['id'] === $linkedPregnancyId) {
        $selectedPregnancy = $pregnancy;
        break;
    }
}
$selectedMotherCondition = trim((string)old('mother_condition', $rec['mother_condition'] ?? ''));
if ($selectedMotherCondition !== '' && !in_array($selectedMotherCondition, $motherConditionOptions, true)) $motherConditionOptions[] = $selectedMotherCondition;
$selectedBabyCondition = trim((string)old('baby_condition', $rec['baby_condition'] ?? ''));
if ($selectedBabyCondition !== '' && !in_array($selectedBabyCondition, $babyConditionOptions, true)) $babyConditionOptions[] = $selectedBabyCondition;
$title = $rec ? 'Edit Postnatal Visit' : 'New Postnatal Visit';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <base target="_parent">
  <title><?= h($title) ?></title>
  <script src="/HealthLogs/public/assets/js/tailwind.js"></script>
  <script src="/HealthLogs/public/assets/js/sweetalert2.all.min.js"></script>
  <link rel="stylesheet" href="/HealthLogs/public/assets/css/fontawesome.min.css">
  <link rel="stylesheet" href="/HealthLogs/public/assets/css/card-icons.css">
</head>
<body class="bg-slate-50 p-4 text-slate-900">
  <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm max-w-4xl mx-auto">
    <div class="mb-5">
      <h1 class="text-xl font-semibold"><?= h($title) ?></h1>
      <p class="text-sm text-slate-500 mt-1">Capture recovery and newborn follow-up details.</p>
    </div>
    <?php display_flash_messages(); ?>
    <form method="post" action="/HealthLogs/public/maternal/postnatal/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed">
      <?php if ($rec): ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" /><?php endif; ?>
      <?php if ($selectedPregnancy): ?>
        <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
          <input type="hidden" name="pregnancy_id" value="<?= (int)$selectedPregnancy['id'] ?>" />
          <input type="hidden" name="return_pregnancy_id" value="<?= (int)$selectedPregnancy['id'] ?>" />
          <div class="text-xs font-medium text-slate-500">Delivered pregnancy record</div>
          <div class="mt-1 font-semibold text-slate-900"><?= h($selectedPregnancy['last_name'] . ', ' . $selectedPregnancy['first_name']) ?></div>
          <div class="mt-1 text-xs text-slate-600">LMP <?= h(date('M d, Y', strtotime($selectedPregnancy['lmp_date']))) ?> · EDD <?= h(date('M d, Y', strtotime($selectedPregnancy['edd_date']))) ?></div>
        </div>
      <?php else: ?>
        <div>
          <label class="block text-sm text-slate-600" for="postnatalPregnancy">Select delivered pregnancy</label>
          <select id="postnatalPregnancy" name="pregnancy_id" required data-searchable-select data-search-placeholder="Search mother name..." class="mt-1 w-full border rounded px-3 py-2">
            <option value="">Choose a mother</option>
            <?php foreach ($pregs as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= old('pregnancy_id', $linkedPregnancyId) == $p['id'] ? 'selected' : '' ?>><?= h($p['last_name'] . ', ' . $p['first_name']) ?> · EDD <?= h($p['edd_date']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div>
        <label class="block text-sm text-slate-600" for="postnatalVisitDate">Follow-up date and time</label>
        <input id="postnatalVisitDate" name="visit_datetime" type="datetime-local" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('visit_datetime', str_replace(' ', 'T', $rec['visit_datetime'] ?? ''))) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Mother’s recovery status</label>
        <select name="mother_condition" class="mt-1 w-full border rounded px-3 py-2">
          <option value="">Select mother condition</option>
          <?php foreach ($motherConditionOptions as $option): ?>
            <option value="<?= h($option) ?>" <?= $selectedMotherCondition === $option ? 'selected' : '' ?>><?= h($option) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Baby’s health status</label>
        <select name="baby_condition" class="mt-1 w-full border rounded px-3 py-2">
          <option value="">Select baby condition</option>
          <?php foreach ($babyConditionOptions as $option): ?>
            <option value="<?= h($option) ?>" <?= $selectedBabyCondition === $option ? 'selected' : '' ?>><?= h($option) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
        <div>
          <label class="block text-sm text-slate-600">Schedule next follow-up (optional)</label>
          <input name="next_appointment_date" type="date" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('next_appointment_date', '')) ?>" />
          <p class="mt-1 text-xs text-slate-500">Adding a date also creates an appointment and reminder.</p>
        </div>
        <div>
          <label class="block text-sm text-slate-600">Follow-up time (optional)</label>
          <input name="next_appointment_time" type="time" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('next_appointment_time', '')) ?>" />
        </div>
      <div class="md:col-span-2">
        <label class="block text-sm text-slate-600">Notes</label>
        <textarea name="notes" class="mt-1 w-full border rounded px-3 py-2" rows="2"><?= h(old('notes', $rec['notes'] ?? '')) ?></textarea>
      </div>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save follow-up</button>
        <a class="text-slate-600" href="<?= $linkedPregnancyId ? '/HealthLogs/public/maternal/pregnancies/view.php?id=' . $linkedPregnancyId : '/HealthLogs/public/maternal/postnatal/index.php' ?>">Cancel</a>
      </div>
    </form>
  </div>
  <script src="/HealthLogs/public/assets/js/card-icons.js"></script>
</body>
<script src="/HealthLogs/public/assets/js/searchable-select.js"></script>
</html>
