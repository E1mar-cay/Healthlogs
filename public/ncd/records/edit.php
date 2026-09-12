<?php
$pageTitle = 'Edit NCD Patient Profile';
require __DIR__ . '/../../partials/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("
    SELECT r.*, p.first_name, p.last_name, p.middle_name, p.birth_date, p.barangay, p.sex, p.contact_no,
           TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age
    FROM ncd_records r
    JOIN patients p ON p.id = r.patient_id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    set_flash('error', 'NCD record not found.');
    header('Location: /HealthLogs/public/ncd/records/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosis_type = trim($_POST['diagnosis_type'] ?? $record['diagnosis_type']);
    $date_diagnosed = trim($_POST['date_diagnosed'] ?? $record['date_diagnosed']);
    $philpen_risk_level = trim($_POST['philpen_risk_level'] ?? $record['philpen_risk_level']);
    $is_smoker = trim($_POST['is_smoker'] ?? $record['is_smoker']);
    $is_alcohol_drinker = trim($_POST['is_alcohol_drinker'] ?? $record['is_alcohol_drinker']);
    $maintenance_meds = trim($_POST['maintenance_meds'] ?? '');
    $target_bp = trim($_POST['target_bp'] ?? '<140/90');
    $target_fbs = trim($_POST['target_fbs'] ?? '<126 mg/dL');
    $status = trim($_POST['status'] ?? 'active');
    $remarks = trim($_POST['remarks'] ?? '');

    if (empty($date_diagnosed)) {
        $errors[] = 'Date diagnosed is required.';
    }

    if (empty($errors)) {
        try {
            $uStmt = $pdo->prepare("
                UPDATE ncd_records SET
                    diagnosis_type = :diagnosis_type,
                    date_diagnosed = :date_diagnosed,
                    philpen_risk_level = :philpen_risk_level,
                    is_smoker = :is_smoker,
                    is_alcohol_drinker = :is_alcohol_drinker,
                    maintenance_meds = :maintenance_meds,
                    target_bp = :target_bp,
                    target_fbs = :target_fbs,
                    status = :status,
                    remarks = :remarks
                WHERE id = :id
            ");
            $uStmt->execute([
                'diagnosis_type' => $diagnosis_type,
                'date_diagnosed' => $date_diagnosed,
                'philpen_risk_level' => $philpen_risk_level,
                'is_smoker' => $is_smoker,
                'is_alcohol_drinker' => $is_alcohol_drinker,
                'maintenance_meds' => $maintenanceMeds = ($maintenance_meds !== '' ? $maintenance_meds : null),
                'target_bp' => $target_bp ?: '<140/90',
                'target_fbs' => $target_fbs ?: '<126 mg/dL',
                'status' => $status,
                'remarks' => $remarks ?: null,
                'id' => $id,
            ]);

            set_flash('success', "NCD Client [{$record['ncd_code']}] successfully updated!");
            header("Location: /HealthLogs/public/ncd/records/index.php");
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Failed to update record: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/../../partials/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
  <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-slate-500 font-semibold">
          <a href="/HealthLogs/public/ncd/records/index.php" class="text-slate-500 hover:text-slate-800 hover:underline">&larr; Back to NCD Registry</a>
        </div>
        <div class="text-2xl font-bold text-slate-900 mt-1">Edit NCD Record: <?= h($record['ncd_code']) ?></div>
        <p class="text-sm text-slate-500 mt-1">Patient: <strong><?= h($record['last_name'] . ', ' . $record['first_name']) ?></strong> (<?= $record['age'] ?>yo, Brgy. <?= h($record['barangay']) ?>)</p>
      </div>
      <span class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center text-xl font-bold">
        <i class="fas fa-pen-to-square"></i>
      </span>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 text-sm">
      <div class="font-bold mb-1"><i class="fas fa-circle-exclamation"></i> Error updating record:</div>
      <ul class="list-disc list-inside space-y-0.5">
        <?php foreach ($errors as $err): ?>
          <li><?= h($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" class="bg-white rounded-xl shadow p-5 sm:p-8 space-y-6">
    <!-- Section 1: Clinical Diagnosis & Risk -->
    <div>
      <h3 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
        <i class="fas fa-stethoscope text-slate-500"></i> Diagnosis &amp; PhilPEN Assessment
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Primary Diagnosis *</label>
          <select name="diagnosis_type" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
            <option value="hypertension" <?= $record['diagnosis_type'] === 'hypertension' ? 'selected' : '' ?>>Hypertension (HPN)</option>
            <option value="diabetes" <?= $record['diagnosis_type'] === 'diabetes' ? 'selected' : '' ?>>Type 2 Diabetes Mellitus (DM)</option>
            <option value="hypertension_diabetes" <?= $record['diagnosis_type'] === 'hypertension_diabetes' ? 'selected' : '' ?>>Hypertension &amp; Type 2 Diabetes</option>
            <option value="cardiovascular" <?= $record['diagnosis_type'] === 'cardiovascular' ? 'selected' : '' ?>>Cardiovascular Disease (CVD / CAD)</option>
            <option value="asthma_copd" <?= $record['diagnosis_type'] === 'asthma_copd' ? 'selected' : '' ?>>Bronchial Asthma / COPD</option>
            <option value="chronic_kidney" <?= $record['diagnosis_type'] === 'chronic_kidney' ? 'selected' : '' ?>>Chronic Kidney Disease (CKD)</option>
            <option value="cancer" <?= $record['diagnosis_type'] === 'cancer' ? 'selected' : '' ?>>Cancer Registry</option>
            <option value="other" <?= $record['diagnosis_type'] === 'other' ? 'selected' : '' ?>>Other Chronic Condition</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Date Diagnosed *</label>
          <input type="date" name="date_diagnosed" value="<?= h($record['date_diagnosed']) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">PhilPEN CVD Risk Level *</label>
          <select name="philpen_risk_level" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="low" <?= $record['philpen_risk_level'] === 'low' ? 'selected' : '' ?>>Low Risk (&lt;10%)</option>
            <option value="moderate" <?= $record['philpen_risk_level'] === 'moderate' ? 'selected' : '' ?>>Moderate Risk (10% to &lt;20%)</option>
            <option value="high" <?= $record['philpen_risk_level'] === 'high' ? 'selected' : '' ?>>High Risk (20% to &lt;30%)</option>
            <option value="very_high" <?= $record['philpen_risk_level'] === 'very_high' ? 'selected' : '' ?>>Very High Risk (&ge;30%)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Program Status</label>
          <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
            <option value="active" <?= $record['status'] === 'active' ? 'selected' : '' ?>>Active (Under Monitoring)</option>
            <option value="controlled" <?= $record['status'] === 'controlled' ? 'selected' : '' ?>>Controlled (Target Achieved)</option>
            <option value="uncontrolled" <?= $record['status'] === 'uncontrolled' ? 'selected' : '' ?>>Uncontrolled / High Alert</option>
            <option value="inactive" <?= $record['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="transferred" <?= $record['status'] === 'transferred' ? 'selected' : '' ?>>Transferred Out</option>
            <option value="deceased" <?= $record['status'] === 'deceased' ? 'selected' : '' ?>>Deceased</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Section 2: Lifestyle & Treatment -->
    <div>
      <h3 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
        <i class="fas fa-pills text-slate-500"></i> Lifestyle Factors &amp; Maintenance Regimen
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Smoking History</label>
          <select name="is_smoker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="never" <?= $record['is_smoker'] === 'never' ? 'selected' : '' ?>>Never Smoked</option>
            <option value="former" <?= $record['is_smoker'] === 'former' ? 'selected' : '' ?>>Former Smoker (Quit)</option>
            <option value="current" <?= $record['is_smoker'] === 'current' ? 'selected' : '' ?>>Current Smoker</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Alcohol History</label>
          <select name="is_alcohol_drinker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="never" <?= $record['is_alcohol_drinker'] === 'never' ? 'selected' : '' ?>>Non-Drinker</option>
            <option value="occasional" <?= $record['is_alcohol_drinker'] === 'occasional' ? 'selected' : '' ?>>Occasional Drinker</option>
            <option value="regular" <?= $record['is_alcohol_drinker'] === 'regular' ? 'selected' : '' ?>>Regular Drinker</option>
            <option value="heavy" <?= $record['is_alcohol_drinker'] === 'heavy' ? 'selected' : '' ?>>Heavy Binge Drinker</option>
          </select>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Maintenance Medications</label>
          <input type="text" name="maintenance_meds" value="<?= h($record['maintenance_meds'] ?? '') ?>" placeholder="e.g. Losartan 50mg OD, Amlodipine 5mg OD" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Target BP (mmHg)</label>
          <input type="text" name="target_bp" value="<?= h($record['target_bp'] ?? '<140/90') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Target Blood Sugar (mg/dL)</label>
          <input type="text" name="target_fbs" value="<?= h($record['target_fbs'] ?? '<126 mg/dL') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Clinical Remarks / Notes</label>
          <textarea name="remarks" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm"><?= h($record['remarks'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
      <a href="/HealthLogs/public/ncd/records/index.php" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold transition">
        Cancel
      </a>
      <button type="submit" class="px-6 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold shadow transition inline-flex items-center gap-1.5">
        <i class="fas fa-save"></i> Save Changes
      </button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
