<?php
// Shared Enroll Family Planning Client Modal Component
$currYear = date('Y');
$modalNextCode = 'FP-' . $currYear . '-0001';
try {
    $lastCode = $pdo->query("SELECT client_code FROM fp_records WHERE client_code LIKE 'FP-{$currYear}-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($lastCode && preg_match('/FP-\d{4}-(\d+)/', $lastCode, $m)) {
        $seq = (int)$m[1] + 1;
        $modalNextCode = sprintf('FP-%s-%04d', $currYear, $seq);
    }
} catch (Throwable $e) {}

$modalPatients = [];
try {
    $modalPatients = $pdo->query("
        SELECT id, first_name, last_name, middle_name, birth_date, barangay, sex, contact_no 
        FROM patients 
        WHERE status = 'active'
        ORDER BY last_name ASC, first_name ASC
    ")->fetchAll();
} catch (Throwable $e) {}
?>

<!-- Enroll Client Modal -->
<div id="enrollClientModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
  <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full my-8 overflow-hidden animate-in fade-in zoom-in duration-150 border border-slate-200">
    <!-- Modal Header -->
    <div class="px-5 py-4 bg-gradient-to-r from-purple-800 to-indigo-800 text-white flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <span class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-sm font-bold shadow-xs">
          <i class="fas fa-user-plus"></i>
        </span>
        <div>
          <div class="text-[10px] uppercase tracking-wider text-purple-200 font-bold">RPRH Clinical Form 1</div>
          <h3 class="text-base sm:text-lg font-bold">Enroll Family Planning Client</h3>
        </div>
      </div>
      <button type="button" onclick="closeEnrollModal()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-purple-200 hover:text-white flex items-center justify-center text-lg font-bold transition">
        &times;
      </button>
    </div>

    <!-- Modal Form -->
    <form action="/HealthLogs/public/family_planning/records/save.php" method="POST" class="p-5 sm:p-6 space-y-4 max-h-[80vh] overflow-y-auto">
      <input type="hidden" name="return_to" value="<?= h($_SERVER['REQUEST_URI'] ?? '/HealthLogs/public/family_planning.php') ?>" />

      <!-- Section 1: Identification -->
      <div>
        <div class="text-xs uppercase font-bold text-purple-700 tracking-wider mb-2.5 flex items-center gap-1.5 border-b pb-1">
          <i class="fas fa-id-card text-purple-600"></i> 1. Client Identification
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Select Patient *</label>
            <select name="patient_id" id="modalPatientSelect" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-purple-500">
              <option value="">-- Choose Registered Patient --</option>
              <?php foreach ($modalPatients as $p): ?>
                <?php 
                  $age = $p['birth_date'] ? (date('Y') - date('Y', strtotime($p['birth_date']))) : '';
                ?>
                <option value="<?= (int)$p['id'] ?>">
                  <?= h($p['last_name'] . ', ' . $p['first_name'] . ' (' . ($p['sex'] === 'male' ? 'M' : 'F') . ', ' . $age . 'yo) - Brgy. ' . $p['barangay']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Client Code *</label>
            <input type="text" name="client_code" value="<?= h($modalNextCode) ?>" required class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-purple-500" />
            <span class="text-[10px] text-slate-400">Clinic Tracking Number</span>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Registration Date *</label>
            <input type="date" name="registration_date" value="<?= date('Y-m-d') ?>" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500" />
          </div>
        </div>
      </div>

      <!-- Section 2: Method & Acceptance -->
      <div>
        <div class="text-xs uppercase font-bold text-purple-700 tracking-wider mb-2.5 flex items-center gap-1.5 border-b pb-1">
          <i class="fas fa-pills text-purple-600"></i> 2. Family Planning Acceptance
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Client Classification *</label>
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
              <option value="other">Other Method</option>
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

      <!-- Section 3: Partner & Children -->
      <div>
        <div class="text-xs uppercase font-bold text-purple-700 tracking-wider mb-2.5 flex items-center gap-1.5 border-b pb-1">
          <i class="fas fa-heart text-purple-600"></i> 3. Partner &amp; Children Information
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
            <label class="block text-xs font-semibold text-slate-700 mb-1">Plan for More Children</label>
            <select name="plan_more_children" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
              <option value="no">No more children (Limiting)</option>
              <option value="yes">Yes (Spacing births)</option>
              <option value="undecided">Undecided</option>
            </select>
          </div>
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks / Clinical Notes</label>
        <textarea name="remarks" rows="2" placeholder="Notes on orientation, counseling, vital signs..." class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
      </div>

      <!-- Modal Footer -->
      <div class="pt-4 border-t flex items-center justify-end gap-2.5">
        <button type="button" onclick="closeEnrollModal()" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
          Cancel
        </button>
        <button type="submit" class="px-5 py-2 rounded-lg bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold shadow transition flex items-center gap-1.5">
          <i class="fas fa-check"></i> Enroll Client
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openEnrollModal(patientId) {
  const modal = document.getElementById('enrollClientModal');
  if (modal) {
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    if (patientId) {
      const select = document.getElementById('modalPatientSelect');
      if (select) select.value = patientId;
    }
  }
}

function closeEnrollModal() {
  const modal = document.getElementById('enrollClientModal');
  if (modal) {
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }
}

// Close on escape key
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    closeEnrollModal();
  }
});

// Close when clicking outside modal content
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('enrollClientModal');
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeEnrollModal();
      }
    });
  }

  // Auto-open modal if requested via URL query (?open_enroll=1 or ?modal=enroll)
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('open_enroll') === '1' || urlParams.get('modal') === 'enroll') {
    openEnrollModal();
  }
});
</script>
