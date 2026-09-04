<?php
require __DIR__ . '/../../partials/bootstrap.php';
$pageTitle = 'Edit TB Case';

$id = (int)($_GET['id'] ?? 0);
$errors = [];
$case = null;

try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.first_name, p.last_name, p.barangay, p.sex, p.birth_date
        FROM tb_cases c
        JOIN patients p ON p.id = c.patient_id
        WHERE c.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $case = $stmt->fetch();
} catch (Throwable $e) {
    $errors[] = 'Error fetching TB case details.';
}

if (!$case) {
    require __DIR__ . '/../../partials/header.php';
    echo '<div class="p-6 bg-white rounded shadow text-center text-slate-500">TB Case not found. <a href="/HealthLogs/public/tb/cases/index.php" class="text-slate-900 font-medium underline">Return to registry</a></div>';
    require __DIR__ . '/../../partials/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tb_type = trim($_POST['tb_type'] ?? 'pulmonary');
    $case_definition = trim($_POST['case_definition'] ?? 'new');
    $bacteriological_status = trim($_POST['bacteriological_status'] ?? 'bacteriologically_confirmed');
    $treatment_category = trim($_POST['treatment_category'] ?? 'category_1');
    $treatment_start_date = trim($_POST['treatment_start_date'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $treatment_outcome = trim($_POST['treatment_outcome'] ?? '');
    $treatment_outcome_date = trim($_POST['treatment_outcome_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($treatment_start_date)) {
        $errors[] = 'Treatment start date is required.';
    }

    if ($status === 'completed' && empty($treatment_outcome)) {
        $errors[] = 'Please select a treatment outcome when setting case to Completed.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE tb_cases SET
                    tb_type = :tb_type,
                    case_definition = :case_definition,
                    bacteriological_status = :bacteriological_status,
                    treatment_category = :treatment_category,
                    treatment_start_date = :treatment_start_date,
                    status = :status,
                    treatment_outcome = :treatment_outcome,
                    treatment_outcome_date = :treatment_outcome_date,
                    notes = :notes
                WHERE id = :id
            ");
            $stmt->execute([
                'tb_type' => $tb_type,
                'case_definition' => $case_definition,
                'bacteriological_status' => $bacteriological_status,
                'treatment_category' => $treatment_category,
                'treatment_start_date' => $treatment_start_date,
                'status' => $status,
                'treatment_outcome' => $treatment_outcome !== '' ? $treatment_outcome : null,
                'treatment_outcome_date' => $treatment_outcome_date !== '' ? $treatment_outcome_date : null,
                'notes' => $notes !== '' ? $notes : null,
                'id' => $id
            ]);

            header("Location: /HealthLogs/public/tb/cases/index.php?msg=updated");
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Failed to update TB case: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Case Update</div>
      <div class="text-2xl font-semibold">Edit Case: <?= h($case['case_number']) ?></div>
      <p class="text-sm text-slate-500 mt-1">Patient: <strong><?= h($case['last_name'] . ', ' . $case['first_name']) ?></strong> (Brgy. <?= h($case['barangay']) ?>)</p>
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
    <!-- Anatomical Site / TB Type -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Anatomical Site / TB Type *</label>
      <select name="tb_type" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="pulmonary" <?= $case['tb_type'] === 'pulmonary' ? 'selected' : '' ?>>Pulmonary TB (PTB)</option>
        <option value="extra_pulmonary" <?= $case['tb_type'] === 'extra_pulmonary' ? 'selected' : '' ?>>Extra-Pulmonary TB (EPTB)</option>
      </select>
    </div>

    <!-- Case Definition -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Registration Group / Definition *</label>
      <select name="case_definition" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="new" <?= $case['case_definition'] === 'new' ? 'selected' : '' ?>>New Case</option>
        <option value="relapse" <?= $case['case_definition'] === 'relapse' ? 'selected' : '' ?>>Relapse</option>
        <option value="treatment_after_failure" <?= $case['case_definition'] === 'treatment_after_failure' ? 'selected' : '' ?>>Treatment After Failure</option>
        <option value="loss_to_follow_up" <?= $case['case_definition'] === 'loss_to_follow_up' ? 'selected' : '' ?>>Loss to Follow-up</option>
        <option value="other" <?= $case['case_definition'] === 'other' ? 'selected' : '' ?>>Other / Transfer In</option>
      </select>
    </div>

    <!-- Bacteriological Status -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Bacteriological Status *</label>
      <select name="bacteriological_status" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="bacteriologically_confirmed" <?= $case['bacteriological_status'] === 'bacteriologically_confirmed' ? 'selected' : '' ?>>Bacteriologically Confirmed</option>
        <option value="clinically_diagnosed" <?= $case['bacteriological_status'] === 'clinically_diagnosed' ? 'selected' : '' ?>>Clinically Diagnosed</option>
      </select>
    </div>

    <!-- Regimen Category -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Treatment Category *</label>
      <select name="treatment_category" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="category_1" <?= $case['treatment_category'] === 'category_1' ? 'selected' : '' ?>>Category I (2RHZE / 4RH)</option>
        <option value="category_2" <?= $case['treatment_category'] === 'category_2' ? 'selected' : '' ?>>Category II (2RHZES / 1RHZE / 5RHE)</option>
        <option value="mdr_tb" <?= $case['treatment_category'] === 'mdr_tb' ? 'selected' : '' ?>>MDR-TB Regimen</option>
      </select>
    </div>

    <!-- Treatment Start Date -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Treatment Start Date *</label>
      <input type="date" name="treatment_start_date" value="<?= h($case['treatment_start_date']) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm" />
    </div>

    <!-- Overall Status -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Overall Case Status *</label>
      <select name="status" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="active" <?= $case['status'] === 'active' ? 'selected' : '' ?>>Active Treatment</option>
        <option value="completed" <?= $case['status'] === 'completed' ? 'selected' : '' ?>>Completed / Closed</option>
        <option value="discontinued" <?= $case['status'] === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
      </select>
    </div>

    <!-- Treatment Outcome -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Treatment Outcome</label>
      <select name="treatment_outcome" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">-- None / Ongoing --</option>
        <option value="cured" <?= $case['treatment_outcome'] === 'cured' ? 'selected' : '' ?>>Cured</option>
        <option value="treatment_completed" <?= $case['treatment_outcome'] === 'treatment_completed' ? 'selected' : '' ?>>Treatment Completed</option>
        <option value="treatment_failed" <?= $case['treatment_outcome'] === 'treatment_failed' ? 'selected' : '' ?>>Treatment Failed</option>
        <option value="died" <?= $case['treatment_outcome'] === 'died' ? 'selected' : '' ?>>Died</option>
        <option value="lost_to_follow_up" <?= $case['treatment_outcome'] === 'lost_to_follow_up' ? 'selected' : '' ?>>Lost to Follow-up</option>
        <option value="evaluated_not" <?= $case['treatment_outcome'] === 'evaluated_not' ? 'selected' : '' ?>>Not Evaluated / Transferred Out</option>
      </select>
    </div>

    <!-- Outcome Date -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Outcome Date</label>
      <input type="date" name="treatment_outcome_date" value="<?= h($case['treatment_outcome_date'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
    </div>

    <!-- Clinical Notes -->
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-slate-700 mb-1">Clinical Notes & Progress</label>
      <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm"><?= h($case['notes'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="flex flex-col-reverse sm:flex-row items-center gap-3 pt-4 border-t">
    <a href="/HealthLogs/public/tb/cases/index.php" class="w-full sm:w-auto text-center px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">Cancel</a>
    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center bg-slate-900 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition">Update Case Details</button>
  </div>
</form>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
