<?php
$pageTitle = 'Enroll Patient to NCD Program';
require __DIR__ . '/../../partials/bootstrap.php';

$errors = [];
$patients = [];

try {
    $patients = $pdo->query("
        SELECT id, first_name, last_name, middle_name, birth_date, barangay, sex, contact_no 
        FROM patients 
        WHERE status = 'active'
        ORDER BY last_name ASC, first_name ASC
    ")->fetchAll();
} catch (Throwable $e) {}

// Auto-generate next NCD code
$currYear = date('Y');
$nextCode = 'NCD-' . $currYear . '-0001';
try {
    $lastCode = $pdo->query("SELECT ncd_code FROM ncd_records WHERE ncd_code LIKE 'NCD-{$currYear}-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($lastCode && preg_match('/NCD-\d{4}-(\d+)/', $lastCode, $m)) {
        $seq = (int)$m[1] + 1;
        $nextCode = sprintf('NCD-%s-%04d', $currYear, $seq);
    }
} catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $ncd_code = trim($_POST['ncd_code'] ?? $nextCode);
    $registration_date = trim($_POST['registration_date'] ?? date('Y-m-d'));
    $diagnosis_type = trim($_POST['diagnosis_type'] ?? 'hypertension');
    $date_diagnosed = trim($_POST['date_diagnosed'] ?? date('Y-m-d'));
    $philpen_risk_level = trim($_POST['philpen_risk_level'] ?? 'low');
    $is_smoker = trim($_POST['is_smoker'] ?? 'never');
    $is_alcohol_drinker = trim($_POST['is_alcohol_drinker'] ?? 'never');
    $maintenance_meds = trim($_POST['maintenance_meds'] ?? '');
    $target_bp = trim($_POST['target_bp'] ?? '<140/90');
    $target_fbs = trim($_POST['target_fbs'] ?? '<126 mg/dL');
    $status = trim($_POST['status'] ?? 'active');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($patient_id <= 0) {
        $errors[] = 'Please select a patient to enroll.';
    }
    if (empty($ncd_code)) {
        $errors[] = 'NCD code is required.';
    }
    if (empty($registration_date)) {
        $errors[] = 'Registration date is required.';
    }
    if (empty($date_diagnosed)) {
        $errors[] = 'Date diagnosed is required.';
    }

    // Check duplicate active record
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id, ncd_code FROM ncd_records WHERE patient_id = ? AND status IN ('active', 'controlled', 'uncontrolled')");
        $chk->execute([$patient_id]);
        if ($ex = $chk->fetch()) {
            $errors[] = "This patient already has an active NCD registry profile ({$ex['ncd_code']}).";
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ncd_records (
                    patient_id, ncd_code, registration_date, diagnosis_type, date_diagnosed,
                    philpen_risk_level, is_smoker, is_alcohol_drinker, maintenance_meds,
                    target_bp, target_fbs, status, remarks
                ) VALUES (
                    :patient_id, :ncd_code, :registration_date, :diagnosis_type, :date_diagnosed,
                    :philpen_risk_level, :is_smoker, :is_alcohol_drinker, :maintenance_meds,
                    :target_bp, :target_fbs, :status, :remarks
                )
            ");
            $stmt->execute([
                'patient_id' => $patient_id,
                'ncd_code' => $ncd_code,
                'registration_date' => $registration_date,
                'diagnosis_type' => $diagnosis_type,
                'date_diagnosed' => $date_diagnosed,
                'philpen_risk_level' => $philpen_risk_level,
                'is_smoker' => $is_smoker,
                'is_alcohol_drinker' => $is_alcohol_drinker,
                'maintenance_meds' => $maintenance_meds ?: null,
                'target_bp' => $target_bp ?: '<140/90',
                'target_fbs' => $target_fbs ?: '<126 mg/dL',
                'status' => $status,
                'remarks' => $remarks ?: null,
            ]);

            $newRecordId = (int)$pdo->lastInsertId();
            set_flash('success', "NCD Client [{$ncd_code}] successfully enrolled!");
            header("Location: /HealthLogs/public/ncd/visits/index.php?record_id={$newRecordId}");
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Failed to enroll client: ' . $e->getMessage();
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
        <div class="text-2xl font-bold text-slate-900 mt-1">Enroll Patient into NCD Program</div>
        <p class="text-sm text-slate-500 mt-1">PhilPEN assessment and chronic lifestyle disease registry enrollment.</p>
      </div>
      <span class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center text-xl font-bold">
        <i class="fas fa-heart-pulse"></i>
      </span>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 text-sm">
      <div class="font-bold mb-1 flex items-center gap-1.5"><i class="fas fa-circle-exclamation"></i> Please resolve the following errors:</div>
      <ul class="list-disc list-inside space-y-0.5">
        <?php foreach ($errors as $err): ?>
          <li><?= h($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" class="bg-white rounded-xl shadow p-5 sm:p-8 space-y-6">
    <!-- Section 1: Patient Identity -->
    <div>
      <h3 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
        <i class="fas fa-id-card text-slate-500"></i> 1. Patient Profile
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Select Patient *</label>
          <select name="patient_id" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-slate-400">
            <option value="">-- Choose Patient --</option>
            <?php foreach ($patients as $p): ?>
              <?php 
                $age = $p['birth_date'] ? (date('Y') - date('Y', strtotime($p['birth_date']))) : '';
                $sel = (isset($_POST['patient_id']) && (int)$_POST['patient_id'] === (int)$p['id']) ? 'selected' : '';
              ?>
              <option value="<?= (int)$p['id'] ?>" <?= $sel ?>>
                <?= h($p['last_name'] . ', ' . $p['first_name'] . ' (' . ($p['sex'] === 'male' ? 'M' : 'F') . ', ' . $age . 'yo) - Brgy. ' . $p['barangay']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">NCD Tracking Code *</label>
          <input type="text" name="ncd_code" value="<?= h($_POST['ncd_code'] ?? $nextCode) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Registration Date *</label>
          <input type="date" name="registration_date" value="<?= h($_POST['registration_date'] ?? date('Y-m-d')) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400" />
        </div>
      </div>
    </div>

    <!-- Section 2: Clinical Details -->
    <div>
      <h3 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
        <i class="fas fa-stethoscope text-slate-500"></i> 2. Clinical Diagnosis &amp; PhilPEN Assessment
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Primary Diagnosis *</label>
          <select name="diagnosis_type" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
            <option value="hypertension" <?= (($_POST['diagnosis_type'] ?? '') === 'hypertension') ? 'selected' : '' ?>>Hypertension (HPN)</option>
            <option value="diabetes" <?= (($_POST['diagnosis_type'] ?? '') === 'diabetes') ? 'selected' : '' ?>>Type 2 Diabetes Mellitus (DM)</option>
            <option value="hypertension_diabetes" <?= (($_POST['diagnosis_type'] ?? '') === 'hypertension_diabetes') ? 'selected' : '' ?>>Hypertension &amp; Type 2 Diabetes</option>
            <option value="cardiovascular" <?= (($_POST['diagnosis_type'] ?? '') === 'cardiovascular') ? 'selected' : '' ?>>Cardiovascular Disease (CVD / CAD)</option>
            <option value="asthma_copd" <?= (($_POST['diagnosis_type'] ?? '') === 'asthma_copd') ? 'selected' : '' ?>>Bronchial Asthma / COPD</option>
            <option value="chronic_kidney" <?= (($_POST['diagnosis_type'] ?? '') === 'chronic_kidney') ? 'selected' : '' ?>>Chronic Kidney Disease (CKD)</option>
            <option value="cancer" <?= (($_POST['diagnosis_type'] ?? '') === 'cancer') ? 'selected' : '' ?>>Cancer Registry</option>
            <option value="other" <?= (($_POST['diagnosis_type'] ?? '') === 'other') ? 'selected' : '' ?>>Other Chronic Condition</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Date Diagnosed *</label>
          <input type="date" name="date_diagnosed" value="<?= h($_POST['date_diagnosed'] ?? date('Y-m-d')) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">PhilPEN CVD Risk Level *</label>
          <select name="philpen_risk_level" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="low" <?= (($_POST['philpen_risk_level'] ?? '') === 'low') ? 'selected' : '' ?>>Low Risk (&lt;10%)</option>
            <option value="moderate" <?= (($_POST['philpen_risk_level'] ?? '') === 'moderate') ? 'selected' : '' ?>>Moderate Risk (10% to &lt;20%)</option>
            <option value="high" <?= (($_POST['philpen_risk_level'] ?? '') === 'high') ? 'selected' : '' ?>>High Risk (20% to &lt;30%)</option>
            <option value="very_high" <?= (($_POST['philpen_risk_level'] ?? '') === 'very_high') ? 'selected' : '' ?>>Very High Risk (&ge;30%)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Program Status</label>
          <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="active" <?= (($_POST['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active (Under Monitoring)</option>
            <option value="controlled" <?= (($_POST['status'] ?? '') === 'controlled') ? 'selected' : '' ?>>Controlled (Target Achieved)</option>
            <option value="uncontrolled" <?= (($_POST['status'] ?? '') === 'uncontrolled') ? 'selected' : '' ?>>Uncontrolled / High Alert</option>
            <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Section 3: Lifestyle & Treatment -->
    <div>
      <h3 class="text-xs uppercase font-bold text-slate-700 tracking-wider mb-4 pb-2 border-b border-slate-200 flex items-center gap-2">
        <i class="fas fa-pills text-slate-500"></i> 3. Lifestyle Factors &amp; Maintenance Regimen
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Smoking History</label>
          <select name="is_smoker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="never" <?= (($_POST['is_smoker'] ?? '') === 'never') ? 'selected' : '' ?>>Never Smoked</option>
            <option value="former" <?= (($_POST['is_smoker'] ?? '') === 'former') ? 'selected' : '' ?>>Former Smoker (Quit)</option>
            <option value="current" <?= (($_POST['is_smoker'] ?? '') === 'current') ? 'selected' : '' ?>>Current Smoker</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Alcohol History</label>
          <select name="is_alcohol_drinker" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="never" <?= (($_POST['is_alcohol_drinker'] ?? '') === 'never') ? 'selected' : '' ?>>Non-Drinker</option>
            <option value="occasional" <?= (($_POST['is_alcohol_drinker'] ?? '') === 'occasional') ? 'selected' : '' ?>>Occasional Drinker</option>
            <option value="regular" <?= (($_POST['is_alcohol_drinker'] ?? '') === 'regular') ? 'selected' : '' ?>>Regular Drinker</option>
            <option value="heavy" <?= (($_POST['is_alcohol_drinker'] ?? '') === 'heavy') ? 'selected' : '' ?>>Heavy Binge Drinker</option>
          </select>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Maintenance Medications</label>
          <input type="text" name="maintenance_meds" value="<?= h($_POST['maintenance_meds'] ?? '') ?>" placeholder="e.g. Losartan 50mg 1 tab OD, Metformin 500mg 1 tab BID" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Target BP (mmHg)</label>
          <input type="text" name="target_bp" value="<?= h($_POST['target_bp'] ?? '<140/90') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Target Blood Sugar (mg/dL)</label>
          <input type="text" name="target_fbs" value="<?= h($_POST['target_fbs'] ?? '<126 mg/dL') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Clinical Remarks / Diet Guidance</label>
          <textarea name="remarks" rows="3" placeholder="Additional clinical notes, dietary instructions, or allergies..." class="w-full border rounded-lg px-3 py-2 text-sm"><?= h($_POST['remarks'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
      <a href="/HealthLogs/public/ncd/records/index.php" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold transition">
        Cancel
      </a>
      <button type="submit" class="px-6 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold shadow transition inline-flex items-center gap-1.5">
        <i class="fas fa-check"></i> Complete Enrollment
      </button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
