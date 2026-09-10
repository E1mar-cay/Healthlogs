<?php
$pageTitle = 'Enroll Family Planning Client';
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

// Auto-generate client code like FP-2026-0001
$currYear = date('Y');
$nextCode = 'FP-' . $currYear . '-0001';
try {
    $lastCode = $pdo->query("SELECT client_code FROM fp_records WHERE client_code LIKE 'FP-{$currYear}-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($lastCode && preg_match('/FP-\d{4}-(\d+)/', $lastCode, $m)) {
        $seq = (int)$m[1] + 1;
        $nextCode = sprintf('FP-%s-%04d', $currYear, $seq);
    }
} catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $client_code = trim($_POST['client_code'] ?? $nextCode);
    $registration_date = trim($_POST['registration_date'] ?? date('Y-m-d'));
    $client_type = trim($_POST['client_type'] ?? 'new_acceptor');
    $method_accepted = trim($_POST['method_accepted'] ?? 'pills_coc');
    $source = trim($_POST['source'] ?? 'public');
    $previous_method = trim($_POST['previous_method'] ?? '');
    $partner_name = trim($_POST['partner_name'] ?? '');
    $partner_occupation = trim($_POST['partner_occupation'] ?? '');
    $num_living_children = (int)($_POST['num_living_children'] ?? 0);
    $plan_more_children = trim($_POST['plan_more_children'] ?? 'undecided');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($patient_id <= 0) {
        $errors[] = 'Please select a patient to enroll.';
    }
    if (empty($client_code)) {
        $errors[] = 'Client code is required.';
    }
    if (empty($registration_date)) {
        $errors[] = 'Registration date is required.';
    }

    // Check if patient is already enrolled in active FP record
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id, client_code FROM fp_records WHERE patient_id = ? AND status = 'active'");
        $chk->execute([$patient_id]);
        if ($ex = $chk->fetch()) {
            $errors[] = "This patient already has an active Family Planning record ({$ex['client_code']}).";
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO fp_records (
                    patient_id, client_code, registration_date, client_type, method_accepted,
                    source, previous_method, partner_name, partner_occupation,
                    num_living_children, plan_more_children, status, remarks
                ) VALUES (
                    :patient_id, :client_code, :registration_date, :client_type, :method_accepted,
                    :source, :previous_method, :partner_name, :partner_occupation,
                    :num_living_children, :plan_more_children, 'active', :remarks
                )
            ");
            $stmt->execute([
                'patient_id' => $patient_id,
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
                'remarks' => $remarks ?: null,
            ]);

            $newRecordId = (int)$pdo->lastInsertId();
            set_flash('success', "Family Planning Client [{$client_code}] successfully enrolled!");
            header("Location: /HealthLogs/public/family_planning/visits/index.php?record_id={$newRecordId}");
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Failed to enroll client: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow mb-6">
  <div class="flex items-center justify-between">
    <div>
      <a href="/HealthLogs/public/family_planning/records/index.php" class="text-xs text-slate-500 font-semibold hover:text-slate-800 hover:underline">&larr; Back to Client Registry</a>
      <div class="text-2xl font-bold text-slate-900 mt-1">Enroll Family Planning Client</div>
      <p class="text-sm text-slate-500 mt-1">Register a patient into the DOH Family Planning Program (Form 1 Profile).</p>
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
    <!-- Client Identification -->
    <div>
      <h3 class="text-sm uppercase font-bold tracking-wider text-slate-700 border-b border-slate-200 pb-2 mb-4">
        1. Client Identification
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Select Patient *</label>
          <select name="patient_id" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-slate-400">
            <option value="">-- Choose Patient --</option>
            <?php foreach ($patients as $p): ?>
              <?php 
                $age = $p['birth_date'] ? (date('Y') - date('Y', strtotime($p['birth_date']))) : '';
                $selected = (isset($_POST['patient_id']) && (int)$_POST['patient_id'] === (int)$p['id']) ? 'selected' : '';
              ?>
              <option value="<?= (int)$p['id'] ?>" <?= $selected ?>>
                <?= h($p['last_name'] . ', ' . $p['first_name'] . ' (' . ($p['sex'] === 'male' ? 'M' : 'F') . ', ' . $age . 'yo) - Brgy. ' . $p['barangay']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Client Code *</label>
          <input type="text" name="client_code" value="<?= h($_POST['client_code'] ?? $nextCode) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-slate-400" />
          <span class="text-[11px] text-slate-400">Standard DOH Clinic ID</span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Registration Date *</label>
          <input type="date" name="registration_date" value="<?= h($_POST['registration_date'] ?? date('Y-m-d')) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400" />
        </div>
      </div>
    </div>

    <!-- Contraceptive Method Profile -->
    <div>
      <h3 class="text-sm uppercase font-bold tracking-wider text-slate-700 border-b border-slate-200 pb-2 mb-4">
        2. Family Planning Acceptance
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Client Type *</label>
          <select name="client_type" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="new_acceptor">New Acceptor (NA)</option>
            <option value="current_user">Current User (CU)</option>
            <option value="restart">Restart (Restarter)</option>
            <option value="changing_method">Changing Method (CM)</option>
            <option value="changing_clinic">Changing Clinic (CC)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Method Accepted *</label>
          <select name="method_accepted" class="w-full border rounded-lg px-3 py-2 text-sm bg-white font-medium">
            <optgroup label="Modern Artificial Methods">
              <option value="pills_coc">Pills (COC - Combined Oral Contraceptives)</option>
              <option value="pills_pop">Pills (POP - Progestin-Only Pills)</option>
              <option value="injectable_dmpa">Injectable (DMPA / Depo-Provera)</option>
              <option value="implant">Subdermal Implant (Implanon/Norplant)</option>
              <option value="iud_interval">IUD (Interval Insertion)</option>
              <option value="iud_postpartum">IUD (Postpartum Insertion)</option>
              <option value="condom">Condoms (Male)</option>
              <option value="btl">BTL (Bilateral Tubal Ligation)</option>
              <option value="nsv">NSV (Non-Scalpel Vasectomy)</option>
            </optgroup>
            <optgroup label="Natural Family Planning">
              <option value="natural_lam">LAM (Lactational Amenorrhea Method)</option>
              <option value="natural_sdm">SDM (Standard Days Method)</option>
              <option value="natural_stm">STM (Symptothermal Method)</option>
            </optgroup>
            <option value="other">Other / Referral</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Previous Method (if any)</label>
          <input type="text" name="previous_method" placeholder="e.g., Pills, Condom, None" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Source of Supplies</label>
          <select name="source" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="public">Public Health Center / RHU</option>
            <option value="private">Private Clinic / Pharmacy / NGO</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Partner & Family Profile -->
    <div>
      <h3 class="text-sm uppercase font-bold tracking-wider text-slate-700 border-b border-slate-200 pb-2 mb-4">
        3. Partner &amp; Reproductive Information
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Partner / Spouse Name</label>
          <input type="text" name="partner_name" placeholder="Full name of spouse/partner" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Partner's Occupation</label>
          <input type="text" name="partner_occupation" placeholder="Occupation / Work" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">No. of Living Children</label>
          <input type="number" name="num_living_children" min="0" max="25" value="0" class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Plans for More Children</label>
          <select name="plan_more_children" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="no">No more children (Limit)</option>
            <option value="yes">Yes (Space births)</option>
            <option value="undecided">Undecided</option>
          </select>
        </div>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Clinical Remarks / Notes</label>
      <textarea name="remarks" rows="2" placeholder="Counseling notes, allergies, partner consent, etc." class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
    </div>

    <div class="pt-4 border-t flex items-center justify-end gap-3">
      <a href="/HealthLogs/public/family_planning/records/index.php" class="px-4 py-2 border rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">Cancel</a>
      <button type="submit" class="px-5 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow transition flex items-center gap-1.5">
        <i class="fas fa-check"></i> Enroll Client
      </button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
