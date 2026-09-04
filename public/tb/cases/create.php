<?php
require __DIR__ . '/../../partials/bootstrap.php';
$pageTitle = 'Register New TB Case';

$errors = [];
$patients = [];

try {
    $patients = $pdo->query("SELECT id, first_name, last_name, barangay, birth_date FROM patients ORDER BY last_name ASC, first_name ASC")->fetchAll();
} catch (Throwable $e) {
    $errors[] = 'Error fetching patient list.';
}

// Auto case number (e.g. TB-2026-0001)
$nextCaseNo = 'TB-' . date('Y') . '-0001';
try {
    $lastId = (int)$pdo->query("SELECT MAX(id) FROM tb_cases")->fetchColumn();
    $nextCaseNo = 'TB-' . date('Y') . '-' . str_pad((string)($lastId + 1), 4, '0', STR_PAD_LEFT);
} catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $case_number = trim($_POST['case_number'] ?? '');
    $registration_date = trim($_POST['registration_date'] ?? '');
    $tb_type = trim($_POST['tb_type'] ?? 'pulmonary');
    $case_definition = trim($_POST['case_definition'] ?? 'new');
    $bacteriological_status = trim($_POST['bacteriological_status'] ?? 'bacteriologically_confirmed');
    $treatment_category = trim($_POST['treatment_category'] ?? 'category_1');
    $treatment_start_date = trim($_POST['treatment_start_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($patient_id <= 0) {
        $errors[] = 'Please select a patient.';
    }
    if (empty($case_number)) {
        $errors[] = 'Case number is required.';
    }
    if (empty($registration_date)) {
        $errors[] = 'Registration date is required.';
    }
    if (empty($treatment_start_date)) {
        $errors[] = 'Treatment start date is required.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tb_cases (
                    patient_id, case_number, registration_date, tb_type, case_definition,
                    bacteriological_status, treatment_category, treatment_start_date, status, notes
                ) VALUES (
                    :patient_id, :case_number, :registration_date, :tb_type, :case_definition,
                    :bacteriological_status, :treatment_category, :treatment_start_date, 'active', :notes
                )
            ");
            $stmt->execute([
                'patient_id' => $patient_id,
                'case_number' => $case_number,
                'registration_date' => $registration_date,
                'tb_type' => $tb_type,
                'case_definition' => $case_definition,
                'bacteriological_status' => $bacteriological_status,
                'treatment_category' => $treatment_category,
                'treatment_start_date' => $treatment_start_date,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            header("Location: /HealthLogs/public/tb/cases/index.php?msg=created");
            exit;
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'case_number')) {
                $errors[] = 'Case number already exists. Please use a unique case number.';
            } else {
                $errors[] = 'Failed to register TB case: ' . $e->getMessage();
            }
        }
    }
}

require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Case Intake</div>
      <div class="text-2xl font-semibold">Register New TB Case</div>
      <p class="text-sm text-slate-500 mt-1">Enroll patient into National Tuberculosis Control Program (DOTS).</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="/HealthLogs/public/tb/cases/index.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 underline">Back to Registry</a>
    </div>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="mt-6 bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-sm space-y-1">
    <?php foreach ($errors as $err): ?>
      <p>&bull; <?= h($err) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="POST" class="mt-6 bg-white rounded-xl shadow p-4 sm:p-6 space-y-4">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Select Patient -->
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-slate-700 mb-1">Select Patient *</label>
      <select name="patient_id" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">-- Choose Patient --</option>
        <?php foreach ($patients as $p): ?>
          <option value="<?= $p['id'] ?>" <?= (isset($_POST['patient_id']) && (int)$_POST['patient_id'] === (int)$p['id']) ? 'selected' : '' ?>>
            <?= h($p['last_name'] . ', ' . $p['first_name']) ?> (Brgy. <?= h($p['barangay']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Case Number -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">TB Case Number *</label>
      <input type="text" name="case_number" value="<?= h($_POST['case_number'] ?? $nextCaseNo) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm font-mono" />
    </div>

    <!-- Registration Date -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Registration Date *</label>
      <input type="date" name="registration_date" value="<?= h($_POST['registration_date'] ?? date('Y-m-d')) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm" />
    </div>

    <!-- Anatomical Site / TB Type -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Anatomical Site / TB Type *</label>
      <select name="tb_type" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="pulmonary" <?= ($_POST['tb_type'] ?? '') === 'pulmonary' ? 'selected' : '' ?>>Pulmonary TB (PTB)</option>
        <option value="extra_pulmonary" <?= ($_POST['tb_type'] ?? '') === 'extra_pulmonary' ? 'selected' : '' ?>>Extra-Pulmonary TB (EPTB)</option>
      </select>
    </div>

    <!-- Case Definition -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Registration Definition *</label>
      <select name="case_definition" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="new" <?= ($_POST['case_definition'] ?? '') === 'new' ? 'selected' : '' ?>>New Case</option>
        <option value="relapse" <?= ($_POST['case_definition'] ?? '') === 'relapse' ? 'selected' : '' ?>>Relapse</option>
        <option value="treatment_after_failure" <?= ($_POST['case_definition'] ?? '') === 'treatment_after_failure' ? 'selected' : '' ?>>Treatment After Failure</option>
        <option value="loss_to_follow_up" <?= ($_POST['case_definition'] ?? '') === 'loss_to_follow_up' ? 'selected' : '' ?>>Loss to Follow-up</option>
        <option value="other" <?= ($_POST['case_definition'] ?? '') === 'other' ? 'selected' : '' ?>>Other / Transfer In</option>
      </select>
    </div>

    <!-- Bacteriological Status -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Bacteriological Status *</label>
      <select name="bacteriological_status" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="bacteriologically_confirmed" <?= ($_POST['bacteriological_status'] ?? '') === 'bacteriologically_confirmed' ? 'selected' : '' ?>>Bacteriologically Confirmed</option>
        <option value="clinically_diagnosed" <?= ($_POST['bacteriological_status'] ?? '') === 'clinically_diagnosed' ? 'selected' : '' ?>>Clinically Diagnosed</option>
      </select>
    </div>

    <!-- Regimen Category -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Treatment Category *</label>
      <select name="treatment_category" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="category_1" <?= ($_POST['treatment_category'] ?? '') === 'category_1' ? 'selected' : '' ?>>Category I (2RHZE / 4RH)</option>
        <option value="category_2" <?= ($_POST['treatment_category'] ?? '') === 'category_2' ? 'selected' : '' ?>>Category II (2RHZES / 1RHZE / 5RHE)</option>
        <option value="mdr_tb" <?= ($_POST['treatment_category'] ?? '') === 'mdr_tb' ? 'selected' : '' ?>>MDR-TB Regimen</option>
      </select>
    </div>

    <!-- Treatment Start Date -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Treatment Start Date *</label>
      <input type="date" name="treatment_start_date" value="<?= h($_POST['treatment_start_date'] ?? date('Y-m-d')) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm" />
    </div>

    <!-- Clinical Notes -->
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-slate-700 mb-1">Clinical Notes & Remarks</label>
      <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm"><?= h($_POST['notes'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="flex flex-col-reverse sm:flex-row items-center gap-3 pt-4 border-t">
    <a href="/HealthLogs/public/tb/cases/index.php" class="w-full sm:w-auto text-center px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">Cancel</a>
    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center bg-slate-900 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition">Save & Register Case</button>
  </div>
</form>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
