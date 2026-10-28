<?php
$pageTitle = 'Pregnancy Record';
require __DIR__ . '/../../partials/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT pr.*, p.id AS patient_id, p.first_name, p.middle_name, p.last_name, p.suffix,
           p.sex, p.birth_date, p.contact_no, p.email, p.address_line, p.barangay,
           p.blood_type, p.philhealth_no, p.emergency_contact_name, p.emergency_contact_phone
    FROM pregnancies pr
    JOIN patients p ON p.id = pr.patient_id
    WHERE pr.id = ?
");
$stmt->execute([$id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    $_SESSION['error_message'] = 'Pregnancy record not found.';
    header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
    exit;
}

$prenatalStmt = $pdo->prepare("
    SELECT v.*, u.full_name AS staff_name
    FROM prenatal_visits v
    LEFT JOIN users u ON u.id = v.recorded_by
    WHERE v.pregnancy_id = ?
    ORDER BY v.visit_datetime DESC
");
$prenatalStmt->execute([$id]);
$prenatalVisits = $prenatalStmt->fetchAll(PDO::FETCH_ASSOC);

$postnatalVisits = [];
if ($record['status'] === 'delivered') {
    $postnatalStmt = $pdo->prepare("
        SELECT v.*, u.full_name AS staff_name
        FROM postnatal_visits v
        LEFT JOIN users u ON u.id = v.recorded_by
        WHERE v.pregnancy_id = ?
        ORDER BY v.visit_datetime DESC
    ");
    $postnatalStmt->execute([$id]);
    $postnatalVisits = $postnatalStmt->fetchAll(PDO::FETCH_ASSOC);
}

$patientName = trim($record['last_name'] . ', ' . $record['first_name']
    . ($record['middle_name'] ? ' ' . $record['middle_name'] : '')
    . ($record['suffix'] ? ' ' . $record['suffix'] : ''));
require __DIR__ . '/../../partials/header.php';
?>

<?php display_flash_messages(); ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
  <div>
    <a href="/HealthLogs/public/maternal/pregnancies/index.php" class="text-xs text-slate-500 hover:text-slate-900">Pregnancy Records</a>
    <h1 class="text-xl font-semibold text-slate-900 mt-1"><?= h($patientName) ?></h1>
    <p class="text-sm text-slate-500">Pregnancy details and visit history for this mother.</p>
  </div>
</div>

<section class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
  <h2 class="text-sm font-semibold text-slate-900 mb-4">Patient Information</h2>
  <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-3 text-xs">
    <div><dt class="text-slate-500">Name</dt><dd class="mt-0.5 font-medium text-slate-900"><?= h($patientName) ?></dd></div>
    <div><dt class="text-slate-500">Age</dt><dd class="mt-0.5 font-medium text-slate-900"><?= h(PatientClassifier::formatAge($record['birth_date'])) ?></dd></div>
    <div><dt class="text-slate-500">Birth Date</dt><dd class="mt-0.5 font-medium text-slate-900"><?= date('M d, Y', strtotime($record['birth_date'])) ?></dd></div>
    <div><dt class="text-slate-500">Sex</dt><dd class="mt-0.5 font-medium text-slate-900"><?= ucfirst(h($record['sex'])) ?></dd></div>
    <div><dt class="text-slate-500">Address</dt><dd class="mt-0.5 font-medium text-slate-900"><?= h(trim(($record['address_line'] ? $record['address_line'] . ', ' : '') . $record['barangay'])) ?></dd></div>
    <div><dt class="text-slate-500">Contact Number</dt><dd class="mt-0.5 font-medium text-slate-900"><?= h($record['contact_no'] ?: '—') ?></dd></div>
    <div><dt class="text-slate-500">Email</dt><dd class="mt-0.5 font-medium text-slate-900"><?= h($record['email'] ?: '—') ?></dd></div>
    <div><dt class="text-slate-500">Blood Type</dt><dd class="mt-0.5 font-medium text-slate-900"><?= h($record['blood_type'] ?: '—') ?></dd></div>
    <div><dt class="text-slate-500">PhilHealth PIN</dt><dd class="mt-0.5 font-medium text-slate-900"><?= h($record['philhealth_no'] ?: '—') ?></dd></div>
    <div><dt class="text-slate-500">Emergency Contact</dt><dd class="mt-0.5 font-medium text-slate-900"><?= h($record['emergency_contact_name'] ?: '—') ?><?= $record['emergency_contact_phone'] ? ' · ' . h($record['emergency_contact_phone']) : '' ?></dd></div>
  </dl>
</section>

<section class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
  <h2 class="text-sm font-semibold text-slate-900 mb-4">Pregnancy Information</h2>
  <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
    <div><dt class="text-slate-500">LMP</dt><dd class="mt-0.5 font-medium text-slate-900"><?= date('M d, Y', strtotime($record['lmp_date'])) ?></dd></div>
    <div><dt class="text-slate-500">Expected Delivery</dt><dd class="mt-0.5 font-medium text-slate-900"><?= date('M d, Y', strtotime($record['edd_date'])) ?></dd></div>
    <div><dt class="text-slate-500">Pregnancy Count</dt><dd class="mt-0.5 font-medium text-slate-900"><?= h($record['gravida'] ?? '—') ?></dd></div>
    <div><dt class="text-slate-500">Births</dt><dd class="mt-0.5 font-medium text-slate-900"><?= h($record['para'] ?? '—') ?></dd></div>
    <div><dt class="text-slate-500">Status</dt><dd class="mt-0.5 font-medium text-slate-900"><?= ucfirst(h($record['status'] === 'terminated' ? 'miscarriage' : $record['status'])) ?></dd></div>
  </dl>
</section>

<section class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
  <div class="flex items-center justify-between gap-3 mb-3">
    <div>
      <h2 class="text-sm font-semibold text-slate-900">Prenatal checkups</h2>
      <p class="text-xs text-slate-500 mt-0.5">Visits during pregnancy, with gestational age and vital signs.</p>
    </div>
    <div class="flex items-center gap-3">
      <span class="text-xs text-slate-500"><?= count($prenatalVisits) ?> visit<?= count($prenatalVisits) === 1 ? '' : 's' ?></span>
      <?php if (can_manage_clinical_records() && $record['status'] === 'ongoing'): ?>
        <a href="/HealthLogs/public/maternal/prenatal/form.php?pregnancy_id=<?= (int)$record['id'] ?>" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">Record prenatal checkup</a>
      <?php endif; ?>
    </div>
  </div>
  <?php if (!$prenatalVisits): ?>
    <p class="py-5 text-center text-xs text-slate-500">No prenatal checkups recorded for this pregnancy yet.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="min-w-full text-xs">
        <thead class="border-b border-slate-200 text-left text-slate-500">
          <tr><th class="px-3 py-2 font-medium">Visit Date</th><th class="px-3 py-2 font-medium">Gestational Age</th><th class="px-3 py-2 font-medium">Blood Pressure</th><th class="px-3 py-2 font-medium">Weight</th><th class="px-3 py-2 font-medium">Notes</th><th class="px-3 py-2 font-medium">Recorded By</th><?php if (can_manage_clinical_records()): ?><th class="px-3 py-2 font-medium text-right">Action</th><?php endif; ?></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($prenatalVisits as $visit): ?>
            <tr>
              <td class="px-3 py-3 whitespace-nowrap text-slate-700"><?= date('M d, Y', strtotime($visit['visit_datetime'])) ?></td>
              <td class="px-3 py-3 text-slate-700"><?= $visit['gestational_age_weeks'] !== null ? (int)$visit['gestational_age_weeks'] . ' weeks' : '—' ?></td>
              <td class="px-3 py-3 text-slate-700"><?= $visit['bp_systolic'] && $visit['bp_diastolic'] ? (int)$visit['bp_systolic'] . '/' . (int)$visit['bp_diastolic'] : '—' ?></td>
              <td class="px-3 py-3 text-slate-700"><?= $visit['weight_kg'] !== null ? h($visit['weight_kg']) . ' kg' : '—' ?></td>
              <td class="px-3 py-3 text-slate-600"><?= h($visit['notes'] ?: '—') ?></td>
              <td class="px-3 py-3 text-slate-600"><?= h($visit['staff_name'] ?: '—') ?></td>
              <?php if (can_manage_clinical_records()): ?>
                <td class="px-3 py-3 text-right"><a href="/HealthLogs/public/maternal/prenatal/form.php?id=<?= (int)$visit['id'] ?>" class="text-teal-700 hover:underline">Edit</a></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php if ($record['status'] === 'delivered'): ?>
  <section class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
    <div class="flex items-center justify-between gap-3 mb-3">
      <div>
        <h2 class="text-sm font-semibold text-slate-900">After-birth follow-ups</h2>
        <p class="text-xs text-slate-500 mt-0.5">Postnatal checks for the mother and baby.</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-xs text-slate-500"><?= count($postnatalVisits) ?> visit<?= count($postnatalVisits) === 1 ? '' : 's' ?></span>
        <?php if (can_manage_clinical_records()): ?>
          <a href="/HealthLogs/public/maternal/postnatal/form.php?pregnancy_id=<?= (int)$record['id'] ?>" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">Record after-birth check</a>
        <?php endif; ?>
      </div>
    </div>
    <?php if (!$postnatalVisits): ?>
      <p class="py-5 text-center text-xs text-slate-500">No after-birth checks recorded for this pregnancy yet.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
          <thead class="border-b border-slate-200 text-left text-slate-500">
            <tr><th class="px-3 py-2 font-medium">Visit Date</th><th class="px-3 py-2 font-medium">Mother</th><th class="px-3 py-2 font-medium">Baby</th><th class="px-3 py-2 font-medium">Notes</th><th class="px-3 py-2 font-medium">Recorded By</th><?php if (can_manage_clinical_records()): ?><th class="px-3 py-2 font-medium text-right">Action</th><?php endif; ?></tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($postnatalVisits as $visit): ?>
              <tr>
                <td class="px-3 py-3 whitespace-nowrap text-slate-700"><?= date('M d, Y', strtotime($visit['visit_datetime'])) ?></td>
                <td class="px-3 py-3 text-slate-600"><?= h($visit['mother_condition'] ?: '—') ?></td>
                <td class="px-3 py-3 text-slate-600"><?= h($visit['baby_condition'] ?: '—') ?></td>
                <td class="px-3 py-3 text-slate-600"><?= h($visit['notes'] ?: '—') ?></td>
                <td class="px-3 py-3 text-slate-600"><?= h($visit['staff_name'] ?: '—') ?></td>
                <?php if (can_manage_clinical_records()): ?>
                  <td class="px-3 py-3 text-right"><a href="/HealthLogs/public/maternal/postnatal/form.php?id=<?= (int)$visit['id'] ?>" class="text-teal-700 hover:underline">Edit</a></td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
