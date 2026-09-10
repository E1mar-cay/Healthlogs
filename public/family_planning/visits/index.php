<?php
$pageTitle = 'Family Planning Follow-up Visits Log';
require __DIR__ . '/../../partials/bootstrap.php';

$recordId = (int)($_GET['record_id'] ?? 0);
$successMsg = '';
$errorMsg = '';

// Handle Logging New Visit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_visit') {
    $fp_record_id = (int)($_POST['fp_record_id'] ?? 0);
    $visit_date = trim($_POST['visit_date'] ?? date('Y-m-d'));
    $method_prescribed = trim($_POST['method_prescribed'] ?? '');
    $quantity = (int)($_POST['quantity_dispensed'] ?? 1);
    $next_appointment_date = trim($_POST['next_appointment_date'] ?? '');
    $bp_systolic = !empty($_POST['bp_systolic']) ? (int)$_POST['bp_systolic'] : null;
    $bp_diastolic = !empty($_POST['bp_diastolic']) ? (int)$_POST['bp_diastolic'] : null;
    $weight_kg = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null;
    $findings = trim($_POST['findings_complaints'] ?? '');
    $recorded_by = $_SESSION['user_id'] ?? null;

    if ($fp_record_id > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $visit_date)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO fp_visits (
                    fp_record_id, visit_date, method_prescribed, quantity_dispensed, 
                    next_appointment_date, bp_systolic, bp_diastolic, weight_kg, 
                    findings_complaints, recorded_by
                ) VALUES (
                    :fp_record_id, :visit_date, :method_prescribed, :quantity_dispensed,
                    :next_appointment_date, :bp_systolic, :bp_diastolic, :weight_kg,
                    :findings_complaints, :recorded_by
                )
            ");
            $stmt->execute([
                'fp_record_id' => $fp_record_id,
                'visit_date' => $visit_date,
                'method_prescribed' => $method_prescribed !== '' ? $method_prescribed : 'Method Dispensed',
                'quantity_dispensed' => $quantity > 0 ? $quantity : 1,
                'next_appointment_date' => ($next_appointment_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $next_appointment_date)) ? $next_appointment_date : null,
                'bp_systolic' => $bp_systolic,
                'bp_diastolic' => $bp_diastolic,
                'weight_kg' => $weight_kg,
                'findings_complaints' => $findings !== '' ? $findings : 'Routine follow-up / resupply',
                'recorded_by' => $recorded_by,
            ]);

            // Auto-schedule an SMS reminder if next appointment date is provided
            if ($next_appointment_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $next_appointment_date)) {
                $cStmt = $pdo->prepare("SELECT patient_id, client_code FROM fp_records WHERE id = ?");
                $cStmt->execute([$fp_record_id]);
                $cl = $cStmt->fetch();
                if ($cl && !empty($cl['patient_id'])) {
                    $reminderMsg = "Good day! Reminder from Barangay Health Center: Your Family Planning follow-up/refill ({$method_prescribed}) is scheduled on " . date('M d, Y', strtotime($next_appointment_date)) . ". Please bring your FP card.";
                    $remStmt = $pdo->prepare("
                        INSERT INTO reminders (patient_id, reminder_type, due_date, message, status)
                        VALUES (?, 'family_planning', ?, ?, 'pending')
                    ");
                    $remStmt->execute([$cl['patient_id'], $next_appointment_date, $reminderMsg]);
                }
            }

            $successMsg = "Visit successfully recorded! Next appointment scheduled for " . ($next_appointment_date ?: 'N/A') . ".";
        } catch (Throwable $e) {
            $errorMsg = 'Failed to save visit record: ' . $e->getMessage();
        }
    }
}

// Fetch active client details if record_id is passed
$selectedClient = null;
if ($recordId > 0) {
    $cStmt = $pdo->prepare("
        SELECT r.*, p.first_name, p.last_name, p.middle_name, p.contact_no, p.barangay, p.sex, p.birth_date,
               TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age
        FROM fp_records r
        JOIN patients p ON p.id = r.patient_id
        WHERE r.id = ?
    ");
    $cStmt->execute([$recordId]);
    $selectedClient = $cStmt->fetch();
}

// Fetch visits
$visitQuery = "
    SELECT v.*, r.client_code, r.method_accepted, p.first_name, p.last_name, p.contact_no, p.barangay, u.full_name AS recorded_by_name
    FROM fp_visits v
    JOIN fp_records r ON r.id = v.fp_record_id
    JOIN patients p ON p.id = r.patient_id
    LEFT JOIN users u ON u.id = v.recorded_by
";
$vParams = [];

if ($recordId > 0) {
    $visitQuery .= " WHERE v.fp_record_id = :record_id";
    $vParams['record_id'] = $recordId;
}

$visitQuery .= " ORDER BY v.visit_date DESC, v.id DESC LIMIT 100";
$vStmt = $pdo->prepare($visitQuery);
$vStmt->execute($vParams);
$visits = $vStmt->fetchAll();

// All active clients list for the visit logger dropdown if no record_id selected
$activeClientsList = [];
try {
    $activeClientsList = $pdo->query("
        SELECT r.id, r.client_code, r.method_accepted, p.first_name, p.last_name, p.barangay
        FROM fp_records r
        JOIN patients p ON p.id = r.patient_id
        WHERE r.status = 'active'
        ORDER BY p.last_name ASC, p.first_name ASC
    ")->fetchAll();
} catch (Throwable $e) {}

require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow mb-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500 font-semibold">
        <a href="/HealthLogs/public/family_planning.php" class="text-slate-500 hover:text-slate-800 hover:underline">&larr; Back to FP Dashboard</a>
      </div>
      <div class="text-2xl font-bold text-slate-900 mt-1">Family Planning Consultations &amp; Dispensing Log</div>
      <p class="text-sm text-slate-500 mt-1">Track clinic check-ups, contraceptive supplies dispensed, vitals, and next appointment dates.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/family_planning/records/index.php" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold transition">
        <i class="fas fa-address-book mr-1.5 text-xs"></i> Client Registry
      </a>
      <a href="/HealthLogs/public/family_planning/tcl.php" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold transition">
        <i class="fas fa-table-list mr-1.5 text-xs"></i> Target Client List
      </a>
    </div>
  </div>
</div>

<?php display_flash_messages(); ?>

<?php if (!empty($successMsg)): ?>
  <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl text-sm flex items-center justify-between shadow-sm">
    <div class="flex items-center gap-2">
      <i class="fas fa-check-circle text-emerald-600 text-base"></i>
      <span><?= h($successMsg) ?></span>
    </div>
    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
  </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
  <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-sm flex items-center justify-between shadow-sm">
    <div class="flex items-center gap-2">
      <i class="fas fa-exclamation-circle text-rose-600 text-base"></i>
      <span><?= h($errorMsg) ?></span>
    </div>
    <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 font-bold">&times;</button>
  </div>
<?php endif; ?>

<?php if ($selectedClient): ?>
  <!-- Selected Client Header Profile Card -->
  <div class="bg-white border border-slate-200 text-slate-900 rounded-xl shadow-sm p-5 sm:p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-800 border border-slate-200 uppercase tracking-wide">
            <?= h($selectedClient['client_code']) ?>
          </span>
          <span class="text-xs text-slate-500">Enrolled: <?= date('M d, Y', strtotime($selectedClient['registration_date'])) ?></span>
        </div>
        <h2 class="text-2xl font-bold mt-1.5 text-slate-900"><?= h($selectedClient['first_name'] . ' ' . $selectedClient['last_name']) ?></h2>
        <div class="text-xs text-slate-500 mt-0.5">
          <?= (int)$selectedClient['age'] ?> yrs old &bull; Brgy. <?= h($selectedClient['barangay']) ?> &bull; Contact: <?= h($selectedClient['contact_no'] ?: 'None') ?>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <div class="bg-slate-50 border border-slate-200 px-3.5 py-2 rounded-xl text-center">
          <div class="text-[10px] uppercase font-bold text-slate-500">Current Method</div>
          <div class="text-sm font-bold text-slate-900"><?= h(ucwords(str_replace('_', ' ', $selectedClient['method_accepted']))) ?></div>
        </div>
        <div class="bg-slate-50 border border-slate-200 px-3.5 py-2 rounded-xl text-center">
          <div class="text-[10px] uppercase font-bold text-slate-500">Status</div>
          <div class="text-sm font-bold text-emerald-700 capitalize"><?= h($selectedClient['status']) ?></div>
        </div>
        <a href="/HealthLogs/public/family_planning/visits/index.php" class="bg-white border border-slate-300 hover:bg-slate-50 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 transition">
          View All Clients
        </a>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Log Visit / Dispensing Form -->
<div class="bg-white rounded-xl shadow p-5 sm:p-6 mb-6 border border-slate-100">
  <div class="flex items-center justify-between border-b pb-3 mb-4">
    <div>
      <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
        <i class="fas fa-notes-medical text-slate-500"></i> Record Follow-up &amp; Contraceptive Dispensing
      </h3>
      <p class="text-xs text-slate-500">Record vital signs, pills/depo supply issued, complaints, and scheduled appointment.</p>
    </div>
  </div>

  <form method="POST" class="space-y-4">
    <input type="hidden" name="action" value="save_visit" />

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Family Planning Client *</label>
        <?php if ($selectedClient): ?>
          <input type="hidden" name="fp_record_id" value="<?= (int)$selectedClient['id'] ?>" />
          <input type="text" readonly value="<?= h($selectedClient['client_code'] . ' - ' . $selectedClient['first_name'] . ' ' . $selectedClient['last_name']) ?>" class="w-full border bg-slate-50 rounded-lg px-3 py-2 text-sm text-slate-700 font-semibold" />
        <?php else: ?>
          <select name="fp_record_id" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-slate-400">
            <option value="">-- Select Client --</option>
            <?php foreach ($activeClientsList as $ac): ?>
              <option value="<?= (int)$ac['id'] ?>">
                <?= h($ac['client_code'] . ' - ' . $ac['last_name'] . ', ' . $ac['first_name'] . ' (' . ucwords(str_replace('_', ' ', $ac['method_accepted'])) . ')') ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Visit Date *</label>
        <input type="date" name="visit_date" required value="<?= date('Y-m-d') ?>" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400" />
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Method Given / Dispensed *</label>
        <select name="method_prescribed" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-slate-400">
          <option value="Pills (COC - Combined)">Pills (COC - Combined)</option>
          <option value="Pills (POP - Progestin Only)">Pills (POP - Progestin Only)</option>
          <option value="DMPA Injectable (Depo-Provera)">DMPA Injectable (Depo-Provera)</option>
          <option value="Subdermal Implant Insertion/Check">Subdermal Implant Insertion/Check</option>
          <option value="IUD Insertion/Check">IUD Insertion/Check</option>
          <option value="Condoms (Pieces)">Condoms (Pieces)</option>
          <option value="Natural FP Counseling">Natural FP Counseling</option>
          <option value="Routine FP Consultation">Routine FP Consultation</option>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity Dispensed</label>
        <input type="number" name="quantity_dispensed" min="1" max="100" value="1" class="w-full border rounded-lg px-3 py-2 text-sm" />
        <span class="text-[10px] text-slate-400">Cycles, Vials, or Pieces</span>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Pressure (Systolic)</label>
        <input type="number" name="bp_systolic" placeholder="120" class="w-full border rounded-lg px-3 py-2 text-sm" />
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Blood Pressure (Diastolic)</label>
        <input type="number" name="bp_diastolic" placeholder="80" class="w-full border rounded-lg px-3 py-2 text-sm" />
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Weight (kg)</label>
        <input type="number" step="0.1" name="weight_kg" placeholder="52.0" class="w-full border rounded-lg px-3 py-2 text-sm" />
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Next Appointment Date</label>
        <input type="date" name="next_appointment_date" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400" />
        <span class="text-[11px] text-slate-500">Creates an automated SMS reminder in follow-up queue</span>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Clinical Findings &amp; Complaints</label>
        <input type="text" name="findings_complaints" placeholder="e.g. No headache, BP normal, 1 cycle dispensed." class="w-full border rounded-lg px-3 py-2 text-sm" />
      </div>
    </div>

    <div class="pt-3 border-t flex justify-end">
      <button type="submit" class="px-5 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow transition flex items-center gap-1.5">
        <i class="fas fa-save"></i> Save Visit Record
      </button>
    </div>
  </form>
</div>

<!-- Visits History Table -->
<div class="bg-white rounded-xl shadow p-5 sm:p-6">
  <div class="flex items-center justify-between border-b pb-3 mb-4">
    <h3 class="text-base font-bold text-slate-900">
      <?= $selectedClient ? 'Consultations for ' . h($selectedClient['first_name'] . ' ' . $selectedClient['last_name']) : 'Recent Consultations Log (All Clients)' ?>
    </h3>
    <span class="text-xs text-slate-500 font-medium"><?= count($visits) ?> visit record(s)</span>
  </div>

  <?php if (empty($visits)): ?>
    <div class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-300">
      <i class="fas fa-notes-medical text-3xl text-slate-400 mb-2"></i>
      <p class="text-sm font-semibold text-slate-700">No follow-up visits recorded yet.</p>
      <p class="text-xs text-slate-500 mt-1">Use the form above to record client consultations or contraceptive dispensing.</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
      <table class="w-full text-left text-sm min-w-[760px]">
        <thead>
          <tr class="border-b bg-slate-50 text-slate-500 uppercase text-xs">
            <th class="py-3 px-3">Date</th>
            <th class="py-3 px-3">Client</th>
            <th class="py-3 px-3">Method Dispensed</th>
            <th class="py-3 px-3">Qty</th>
            <th class="py-3 px-3">Vitals (BP / Wt)</th>
            <th class="py-3 px-3">Next Appointment</th>
            <th class="py-3 px-3">Recorded By</th>
          </tr>
        </thead>
        <tbody class="divide-y text-slate-700">
          <?php foreach ($visits as $v): ?>
            <tr class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-3 font-semibold text-slate-900 whitespace-nowrap">
                <?= date('M d, Y', strtotime($v['visit_date'])) ?>
              </td>
              <td class="py-3 px-3 whitespace-nowrap">
                <a href="/HealthLogs/public/family_planning/visits/index.php?record_id=<?= $v['fp_record_id'] ?>" class="font-medium text-purple-700 hover:underline">
                  <?= h($v['last_name'] . ', ' . $v['first_name']) ?>
                </a>
                <div class="text-xs text-slate-400 font-mono"><?= h($v['client_code']) ?></div>
              </td>
              <td class="py-3 px-3 whitespace-nowrap font-medium text-slate-800">
                <?= h($v['method_prescribed']) ?>
                <?php if (!empty($v['findings_complaints'])): ?>
                  <div class="text-xs text-slate-500 truncate max-w-xs font-normal"><?= h($v['findings_complaints']) ?></div>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3 whitespace-nowrap text-center font-bold">
                <?= (int)$v['quantity_dispensed'] ?>
              </td>
              <td class="py-3 px-3 whitespace-nowrap text-xs">
                <?php if (!empty($v['bp_systolic'])): ?>
                  <div>BP: <strong class="text-slate-800"><?= (int)$v['bp_systolic'] ?>/<?= (int)$v['bp_diastolic'] ?></strong></div>
                <?php endif; ?>
                <?php if (!empty($v['weight_kg'])): ?>
                  <div class="text-slate-500">Weight: <?= number_format((float)$v['weight_kg'], 1) ?> kg</div>
                <?php endif; ?>
                <?php if (empty($v['bp_systolic']) && empty($v['weight_kg'])): ?>
                  <span class="text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3 whitespace-nowrap text-xs font-mono">
                <?php if (!empty($v['next_appointment_date'])): ?>
                  <span class="font-semibold text-slate-800"><?= date('M d, Y', strtotime($v['next_appointment_date'])) ?></span>
                <?php else: ?>
                  <span class="text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3 whitespace-nowrap text-xs text-slate-500">
                <?= h($v['recorded_by_name'] ?: 'Staff') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
