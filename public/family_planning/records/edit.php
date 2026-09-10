<?php
$pageTitle = 'Edit Family Planning Client Profile';
require __DIR__ . '/../../partials/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /HealthLogs/public/family_planning/records/index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.*, p.first_name, p.last_name, p.barangay, p.birth_date, p.contact_no, p.sex
    FROM fp_records r
    JOIN patients p ON p.id = r.patient_id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$rec = $stmt->fetch();

if (!$rec) {
    set_flash('error', 'Family Planning record not found.');
    header('Location: /HealthLogs/public/family_planning/records/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_code = trim($_POST['client_code'] ?? $rec['client_code']);
    $registration_date = trim($_POST['registration_date'] ?? $rec['registration_date']);
    $client_type = trim($_POST['client_type'] ?? $rec['client_type']);
    $method_accepted = trim($_POST['method_accepted'] ?? $rec['method_accepted']);
    $source = trim($_POST['source'] ?? $rec['source']);
    $previous_method = trim($_POST['previous_method'] ?? '');
    $partner_name = trim($_POST['partner_name'] ?? '');
    $partner_occupation = trim($_POST['partner_occupation'] ?? '');
    $num_living_children = (int)($_POST['num_living_children'] ?? 0);
    $plan_more_children = trim($_POST['plan_more_children'] ?? 'undecided');
    $status = trim($_POST['status'] ?? 'active');
    $drop_out_reason = trim($_POST['drop_out_reason'] ?? '');
    $drop_out_date = trim($_POST['drop_out_date'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if (empty($client_code)) {
        $errors[] = 'Client code is required.';
    }

    if ($status === 'dropped_out' && empty($drop_out_reason)) {
        $errors[] = 'Please specify the drop out reason when marking a client as dropped out.';
    }

    if (empty($errors)) {
        try {
            $upStmt = $pdo->prepare("
                UPDATE fp_records SET
                    client_code = :client_code,
                    registration_date = :registration_date,
                    client_type = :client_type,
                    method_accepted = :method_accepted,
                    source = :source,
                    previous_method = :previous_method,
                    partner_name = :partner_name,
                    partner_occupation = :partner_occupation,
                    num_living_children = :num_living_children,
                    plan_more_children = :plan_more_children,
                    status = :status,
                    drop_out_reason = :drop_out_reason,
                    drop_out_date = :drop_out_date,
                    remarks = :remarks
                WHERE id = :id
            ");
            $upStmt->execute([
                'client_code' => $client_code,
                'registration_date' => $registration_date,
                'client_type' => $client_type,
                'method_accepted' => $method_accepted,
                'source' => $source,
                'previous_method' => $previous_method ?: null,
                'partner_name' => $partner_name ?: null,
                'partner_occupation' => $partner_occupation ?: null,
                'num_living_children' => $num_living_children,
                'plan_more_children' => $plan_more_children,
                'status' => $status,
                'drop_out_reason' => ($status === 'dropped_out' && $drop_out_reason !== '') ? $drop_out_reason : null,
                'drop_out_date' => ($status === 'dropped_out' && $drop_out_date !== '') ? $drop_out_date : null,
                'remarks' => $remarks ?: null,
                'id' => $id,
            ]);

            set_flash('success', "Family Planning Client [{$client_code}] updated successfully.");
            header("Location: /HealthLogs/public/family_planning/records/index.php");
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Failed to update record: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow mb-6">
  <div class="flex items-center justify-between">
    <div>
      <a href="/HealthLogs/public/family_planning/records/index.php" class="text-xs text-slate-500 font-semibold hover:text-slate-800 hover:underline">&larr; Back to Client Registry</a>
      <div class="text-2xl font-bold text-slate-900 mt-1">Edit Client Profile: <?= h($rec['client_code']) ?></div>
      <p class="text-sm text-slate-500 mt-1">Client: <strong><?= h($rec['first_name'] . ' ' . $rec['last_name']) ?></strong> (Brgy. <?= h($rec['barangay'] ?: 'N/A') ?>)</p>
    </div>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-sm space-y-1">
    <?php foreach ($errors as $err): ?>
      <div class="flex items-center gap-2">
        <i class="fas fa-circle-exclamation text-rose-600"></i>
        <span><?= h($err) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow p-5 sm:p-7 max-w-3xl">
  <form method="POST" class="space-y-6">
    <!-- Identification -->
    <div>
      <h3 class="text-sm uppercase font-bold tracking-wider text-slate-700 border-b border-slate-200 pb-2 mb-4">
        1. Client Identification
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Client Code *</label>
          <input type="text" name="client_code" value="<?= h($rec['client_code']) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Registration Date *</label>
          <input type="date" name="registration_date" value="<?= h($rec['registration_date']) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400" />
        </div>
      </div>
    </div>

    <!-- Acceptance & Method -->
    <div>
      <h3 class="text-sm uppercase font-bold tracking-wider text-slate-700 border-b border-slate-200 pb-2 mb-4">
        2. Family Planning Acceptance
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Client Type *</label>
          <select name="client_type" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="new_acceptor" <?= $rec['client_type'] === 'new_acceptor' ? 'selected' : '' ?>>New Acceptor (NA)</option>
            <option value="current_user" <?= $rec['client_type'] === 'current_user' ? 'selected' : '' ?>>Current User (CU)</option>
            <option value="restart" <?= $rec['client_type'] === 'restart' ? 'selected' : '' ?>>Restart</option>
            <option value="changing_method" <?= $rec['client_type'] === 'changing_method' ? 'selected' : '' ?>>Changing Method (CM)</option>
            <option value="changing_clinic" <?= $rec['client_type'] === 'changing_clinic' ? 'selected' : '' ?>>Changing Clinic (CC)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Method Accepted *</label>
          <select name="method_accepted" class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
            <option value="pills_coc" <?= $rec['method_accepted'] === 'pills_coc' ? 'selected' : '' ?>>Pills (COC - Combined)</option>
            <option value="pills_pop" <?= $rec['method_accepted'] === 'pills_pop' ? 'selected' : '' ?>>Pills (POP - Progestin Only)</option>
            <option value="injectable_dmpa" <?= $rec['method_accepted'] === 'injectable_dmpa' ? 'selected' : '' ?>>Injectable (DMPA / Depo)</option>
            <option value="implant" <?= $rec['method_accepted'] === 'implant' ? 'selected' : '' ?>>Subdermal Implant</option>
            <option value="iud_interval" <?= $rec['method_accepted'] === 'iud_interval' ? 'selected' : '' ?>>IUD (Interval)</option>
            <option value="iud_postpartum" <?= $rec['method_accepted'] === 'iud_postpartum' ? 'selected' : '' ?>>IUD (Postpartum)</option>
            <option value="condom" <?= $rec['method_accepted'] === 'condom' ? 'selected' : '' ?>>Condoms (Male)</option>
            <option value="btl" <?= $rec['method_accepted'] === 'btl' ? 'selected' : '' ?>>BTL (Bilateral Tubal Ligation)</option>
            <option value="nsv" <?= $rec['method_accepted'] === 'nsv' ? 'selected' : '' ?>>NSV (Non-Scalpel Vasectomy)</option>
            <option value="natural_lam" <?= $rec['method_accepted'] === 'natural_lam' ? 'selected' : '' ?>>Natural (LAM)</option>
            <option value="natural_sdm" <?= $rec['method_accepted'] === 'natural_sdm' ? 'selected' : '' ?>>Natural (SDM)</option>
            <option value="natural_stm" <?= $rec['method_accepted'] === 'natural_stm' ? 'selected' : '' ?>>Natural (STM)</option>
            <option value="other" <?= $rec['method_accepted'] === 'other' ? 'selected' : '' ?>>Other Method</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Previous Method</label>
          <input type="text" name="previous_method" value="<?= h($rec['previous_method'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Source of Supplies</label>
          <select name="source" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="public" <?= $rec['source'] === 'public' ? 'selected' : '' ?>>Public Health Center / RHU</option>
            <option value="private" <?= $rec['source'] === 'private' ? 'selected' : '' ?>>Private Clinic / Pharmacy</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Status & Drop Out -->
    <div>
      <h3 class="text-sm uppercase font-bold tracking-wider text-slate-700 border-b border-slate-200 pb-2 mb-4">
        3. Status &amp; Retention Tracking
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Client Status</label>
          <select name="status" id="statusSelect" onchange="toggleDropOutFields()" class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-semibold">
            <option value="active" <?= $rec['status'] === 'active' ? 'selected' : '' ?>>Active User</option>
            <option value="inactive" <?= $rec['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="dropped_out" <?= $rec['status'] === 'dropped_out' ? 'selected' : '' ?>>Dropped Out</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Drop Out Reason</label>
          <select name="drop_out_reason" id="dropOutReason" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">-- None --</option>
            <option value="side_effects" <?= $rec['drop_out_reason'] === 'side_effects' ? 'selected' : '' ?>>Side Effects</option>
            <option value="medical_reasons" <?= $rec['drop_out_reason'] === 'medical_reasons' ? 'selected' : '' ?>>Medical Complications</option>
            <option value="desire_pregnancy" <?= $rec['drop_out_reason'] === 'desire_pregnancy' ? 'selected' : '' ?>>Desire to Become Pregnant</option>
            <option value="loss_to_follow_up" <?= $rec['drop_out_reason'] === 'loss_to_follow_up' ? 'selected' : '' ?>>Lost to Follow-up</option>
            <option value="relocation" <?= $rec['drop_out_reason'] === 'relocation' ? 'selected' : '' ?>>Relocation / Moved Out</option>
            <option value="other" <?= $rec['drop_out_reason'] === 'other' ? 'selected' : '' ?>>Other Reason</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Drop Out Date</label>
          <input type="date" name="drop_out_date" id="dropOutDate" value="<?= h($rec['drop_out_date'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>
      </div>
    </div>

    <!-- Partner & Family -->
    <div>
      <h3 class="text-sm uppercase font-bold tracking-wider text-slate-700 border-b border-slate-200 pb-2 mb-4">
        4. Partner &amp; Reproductive Plan
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Partner / Spouse Name</label>
          <input type="text" name="partner_name" value="<?= h($rec['partner_name'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Partner's Occupation</label>
          <input type="text" name="partner_occupation" value="<?= h($rec['partner_occupation'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">No. of Living Children</label>
          <input type="number" name="num_living_children" min="0" max="25" value="<?= (int)$rec['num_living_children'] ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Plan More Children</label>
          <select name="plan_more_children" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="no" <?= $rec['plan_more_children'] === 'no' ? 'selected' : '' ?>>No more children (Limit)</option>
            <option value="yes" <?= $rec['plan_more_children'] === 'yes' ? 'selected' : '' ?>>Yes (Space births)</option>
            <option value="undecided" <?= $rec['plan_more_children'] === 'undecided' ? 'selected' : '' ?>>Undecided</option>
          </select>
        </div>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks</label>
      <textarea name="remarks" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm"><?= h($rec['remarks'] ?? '') ?></textarea>
    </div>

    <div class="pt-4 border-t flex items-center justify-end gap-3">
      <a href="/HealthLogs/public/family_planning/records/index.php" class="px-4 py-2 border rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">Cancel</a>
      <button type="submit" class="px-5 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow transition flex items-center gap-1.5">
        <i class="fas fa-save"></i> Save Changes
      </button>
    </div>
  </form>
</div>

<script>
function toggleDropOutFields() {
  const status = document.getElementById('statusSelect').value;
  const reason = document.getElementById('dropOutReason');
  const date = document.getElementById('dropOutDate');
  if (status === 'dropped_out') {
    reason.required = true;
    if (!date.value) {
      date.value = new Date().toISOString().split('T')[0];
    }
  } else {
    reason.required = false;
  }
}
</script>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
