<?php
$pageTitle = 'Patient Record Management';
require __DIR__ . '/../partials/bootstrap.php';

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$sexFilter = $_GET['sex'] ?? '';
$isPrintMode = (isset($_GET['print']) && $_GET['print'] === '1');

$whereParts = [];
$params = [];

if ($q !== '') {
    $whereParts[] = "(first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR barangay LIKE ? OR COALESCE(contact_no, '') LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if (in_array($statusFilter, ['active', 'inactive', 'deceased'], true)) {
    $whereParts[] = "status = ?";
    $params[] = $statusFilter;
}
if (in_array($sexFilter, ['male', 'female'], true)) {
    $whereParts[] = "sex = ?";
    $params[] = $sexFilter;
}

$whereSql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

// If print mode is requested, fetch ALL matching patients without pagination limit
if ($isPrintMode) {
    $stmt = $pdo->prepare("SELECT * FROM patients $whereSql ORDER BY id DESC");
    $stmt->execute($params);
    $allPatients = $stmt->fetchAll();

    $currentUserFullName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Health Center Staff';
    $currentUserRole = ($_SESSION['role'] ?? '') === 'admin' ? 'System Administrator / Admin' : 'Barangay Health Worker (BHW)';
    $currentDateTimeFormatted = date('F j, Y, h:i A');

    $filterParts = [];
    if ($q !== '') $filterParts[] = 'Search: "' . $q . '"';
    if ($statusFilter !== '') $filterParts[] = 'Status: ' . ucfirst($statusFilter);
    if ($sexFilter !== '') $filterParts[] = 'Gender: ' . ucfirst($sexFilter);
    $filterSummary = !empty($filterParts) ? implode(' | ', $filterParts) : 'All Patient Records (No Filters)';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <title>Official Patient Records List - HealthLogs</title>
      <style>
        @page {
          size: auto;
          margin: 15mm 12mm 15mm 12mm;
        }
        body {
          font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
          color: #0f172a;
          margin: 0;
          padding: 10px;
          font-size: 11px;
          line-height: 1.4;
        }
        .official-header {
          border-bottom: 2px solid #0f172a;
          padding-bottom: 12px;
          margin-bottom: 14px;
          display: flex;
          align-items: center;
          justify-content: space-between;
        }
        .header-center {
          text-align: center;
          flex: 1;
          padding: 0 15px;
        }
        .rep-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 600; }
        .agency-title { font-size: 11px; text-transform: uppercase; color: #334155; font-weight: 600; margin-top: 1px; }
        .hub-title { font-size: 15px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; margin-top: 2px; }
        .sys-title { font-size: 11px; color: #0f766e; font-weight: 700; margin-top: 1px; }
        .doc-meta-box {
          background: #f8fafc;
          border: 1px solid #cbd5e1;
          border-radius: 6px;
          padding: 8px 12px;
          margin-bottom: 14px;
          display: flex;
          justify-content: space-between;
          align-items: center;
          font-size: 11px;
        }
        .doc-title {
          font-size: 13px;
          font-weight: 700;
          color: #0f172a;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 10px;
          font-size: 10.5px;
        }
        th {
          background-color: #f1f5f9;
          color: #334155;
          font-weight: 700;
          text-transform: uppercase;
          font-size: 9px;
          letter-spacing: 0.5px;
          border: 1px solid #cbd5e1;
          padding: 6px 8px;
          text-align: left;
        }
        td {
          border: 1px solid #e2e8f0;
          padding: 5px 8px;
          color: #1e293b;
        }
        tr:nth-child(even) td {
          background-color: #f8fafc;
        }
        .signatory-grid {
          margin-top: 36px;
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 20px;
          page-break-inside: avoid;
          text-align: center;
        }
        .sig-box {
          display: flex;
          flex-direction: column;
        }
        .sig-label {
          font-size: 10px;
          color: #475569;
          text-align: left;
          margin-bottom: 36px;
        }
        .sig-name {
          font-weight: 700;
          text-transform: uppercase;
          font-size: 11.5px;
          border-bottom: 1px solid #0f172a;
          padding-bottom: 2px;
        }
        .sig-role {
          font-size: 9.5px;
          color: #475569;
          margin-top: 3px;
        }
        .sig-date {
          font-size: 9px;
          color: #94a3b8;
          margin-top: 2px;
        }
        .watermark-footer {
          margin-top: 20px;
          border-top: 1px dashed #cbd5e1;
          padding-top: 6px;
          font-size: 9px;
          color: #94a3b8;
          text-align: center;
        }
      </style>
    </head>
    <body>
      <div class="official-header">
        <div>
          <svg width="55" height="55" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="50" cy="50" r="46" fill="#0f766e" stroke="#115e59" stroke-width="2"/>
            <circle cx="50" cy="50" r="41" fill="#ffffff" stroke="#0f766e" stroke-width="1.5" stroke-dasharray="3 2"/>
            <path d="M43 25 h14 v18 h18 v14 h-18 v18 h-14 v-18 h-18 v-14 h18 z" fill="#0ea5a4" opacity="0.3"/>
            <rect x="44" y="24" width="12" height="52" rx="2" fill="#0f766e"/>
            <rect x="24" y="44" width="52" height="12" rx="2" fill="#0f766e"/>
            <circle cx="50" cy="50" r="7" fill="#ffffff"/>
            <path d="M50 45 L52 49 L56 50 L52 52 L50 56 L48 52 L44 50 L48 49 Z" fill="#0f766e"/>
          </svg>
        </div>
        <div class="header-center">
          <div class="rep-title">Republic of the Philippines</div>
          <div class="agency-title">Department of Health • Primary Care Services</div>
          <div class="hub-title">Barangay Health Center & Care Hub</div>
          <div class="sys-title">HealthLogs Information Management System</div>
        </div>
        <div style="text-align: right; font-size: 9.5px; color: #64748b;">
          <div><strong>Date:</strong> <?= date('M d, Y') ?></div>
          <div><strong>Time:</strong> <?= date('h:i A') ?></div>
        </div>
      </div>

      <div class="doc-meta-box">
        <div>
          <div class="doc-title">Official Patient Records Master List</div>
          <div style="color: #475569; margin-top: 2px;"><strong>Filter Scope:</strong> <?= h($filterSummary) ?> (<?= count($allPatients) ?> total records)</div>
        </div>
        <div style="text-align: right; color: #475569;">
          <div><strong>Generated By:</strong> <?= h($currentUserFullName) ?></div>
          <div><strong>Designation:</strong> <?= h($currentUserRole) ?></div>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Patient Name</th>
            <th>Sex</th>
            <th>Birth Date</th>
            <th>Age</th>
            <th>Barangay</th>
            <th>Contact No</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($allPatients)): ?>
            <tr><td colspan="8" style="text-align: center; color: #64748b; padding: 12px;">No patient records found.</td></tr>
          <?php else: ?>
            <?php foreach ($allPatients as $p): ?>
              <?php
                $birthDate = new DateTime($p['birth_date']);
                $today = new DateTime();
                $age = $birthDate->diff($today)->y;
              ?>
              <tr>
                <td><?= h($p['id']) ?></td>
                <td><strong><?= h($p['last_name'] . ', ' . $p['first_name'] . ($p['middle_name'] ? ' ' . $p['middle_name'] : '')) ?></strong></td>
                <td style="text-transform: capitalize;"><?= h($p['sex']) ?></td>
                <td><?= h($p['birth_date']) ?></td>
                <td><?= h($age) ?> yrs</td>
                <td><?= h($p['barangay']) ?></td>
                <td><?= h($p['contact_no'] ?: '—') ?></td>
                <td style="text-transform: capitalize; font-weight: 600;"><?= h($p['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="signatory-grid">
        <div class="sig-box">
          <div class="sig-label">Prepared by:</div>
          <div class="sig-name"><?= h($currentUserFullName) ?></div>
          <div class="sig-role"><?= h($currentUserRole) ?></div>
          <div class="sig-date">Date: <?= date('M d, Y') ?></div>
        </div>
        <div class="sig-box">
          <div class="sig-label">Verified by:</div>
          <div class="sig-name">___________________________</div>
          <div class="sig-role">Supervising Public Health Nurse</div>
          <div class="sig-date">Date: ____________________</div>
        </div>
        <div class="sig-box">
          <div class="sig-label">Approved by:</div>
          <div class="sig-name">___________________________</div>
          <div class="sig-role">Municipal Health Officer / Physician</div>
          <div class="sig-date">Date: ____________________</div>
        </div>
      </div>

      <div class="watermark-footer">
        Official HealthLogs System Generated Document • Certified Master Records • Timestamp: <?= h($currentDateTimeFormatted) ?>
      </div>

      <script>
        window.addEventListener('load', function() {
          setTimeout(function() { window.print(); }, 300);
        });
      </script>
    </body>
    </html>
    <?php
    exit;
}

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM patients $whereSql");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();

// Create paginator
$paginator = paginate($totalRecords, 20);

// Get patients with pagination
$stmt = $pdo->prepare("SELECT * FROM patients $whereSql ORDER BY id DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$patients = $stmt->fetchAll();

// Calculate statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN status = 'deceased' THEN 1 ELSE 0 END) as deceased
    FROM patients
")->fetch();

require __DIR__ . '/../partials/header.php';
?>

<?php display_flash_messages(); ?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Module</div>
      <div class="text-2xl font-semibold">Patient Records</div>
      <p class="text-sm text-slate-500 mt-1">Maintain core demographics, status, and barangay coverage.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip">Patient Intake</span>
      <a target="_blank" href="/HealthLogs/public/patients/index.php?<?= h(http_build_query(array_merge($_GET, ['print' => '1']))) ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-sm font-medium border border-slate-300 transition shadow-xs">
        <i class="fas fa-print text-xs text-teal-700"></i> Print All Records
      </a>
      <?php if (($_SESSION['role'] ?? '') !== 'admin'): ?>
        <button type="button" id="patientModalOpenNew" data-embed-url="/HealthLogs/public/patients/form_embed.php" class="bg-slate-900 text-white px-4 py-2 rounded-lg shadow hover:bg-slate-800 transition">New Patient</button>
        <a href="/HealthLogs/public/patients/form.php" class="text-sm text-slate-600 underline underline-offset-2">Open full-page form</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<form method="get" class="mt-6 bg-white rounded shadow p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2 md:col-span-2" placeholder="Search name, barangay, or contact" />
  <select name="status" class="w-full border rounded px-3 py-2">
    <option value="">All statuses</option>
    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    <option value="deceased" <?= $statusFilter === 'deceased' ? 'selected' : '' ?>>Deceased</option>
  </select>
  <select name="sex" class="w-full border rounded px-3 py-2">
    <option value="">All sexes</option>
    <option value="male" <?= $sexFilter === 'male' ? 'selected' : '' ?>>Male</option>
    <option value="female" <?= $sexFilter === 'female' ? 'selected' : '' ?>>Female</option>
  </select>
  <div class="md:col-span-4 flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Apply</button>
    <a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/patients/index.php">Clear</a>
  </div>
</form>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Total</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['total']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Registered patients</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Active</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['active']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Currently active</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Inactive</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['inactive']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Dormant or archived</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Deceased</div>
    <div class="text-2xl font-semibold mt-2"><?= h($stats['deceased']) ?></div>
    <div class="text-sm text-slate-500 mt-1">Deceased records</div>
  </div>
</div>

<div class="mt-6 bg-white rounded shadow">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="text-left px-4 py-3">ID</th>
          <th class="text-left px-4 py-3">Name</th>
          <th class="text-left px-4 py-3">Sex</th>
          <th class="text-left px-4 py-3">Birth Date</th>
          <th class="text-left px-4 py-3">Age</th>
          <th class="text-left px-4 py-3">Barangay</th>
          <th class="text-left px-4 py-3">Contact</th>
          <th class="text-left px-4 py-3">Status</th>
          <th class="text-left px-4 py-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($patients)): ?>
          <tr><td class="px-4 py-4 text-center text-slate-500" colspan="9">No patients found.</td></tr>
        <?php else: ?>
          <?php foreach ($patients as $p): ?>
            <?php
              $birthDate = new DateTime($p['birth_date']);
              $today = new DateTime();
              $age = $birthDate->diff($today)->y;
              
              $statusColors = [
                'active' => 'bg-green-100 text-green-800',
                'inactive' => 'bg-yellow-100 text-yellow-800',
                'deceased' => 'bg-gray-100 text-gray-800'
              ];
              $statusColor = $statusColors[$p['status']] ?? 'bg-gray-100 text-gray-800';
            ?>
            <tr class="border-t hover:bg-slate-50">
              <td class="px-4 py-3 font-medium"><?= h($p['id']) ?></td>
              <td class="px-4 py-3">
                <div class="font-medium"><?= h($p['last_name'] . ', ' . $p['first_name']) ?></div>
                <?php if ($p['middle_name']): ?>
                  <div class="text-xs text-slate-500"><?= h($p['middle_name']) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 capitalize"><?= h($p['sex']) ?></td>
              <td class="px-4 py-3"><?= h($p['birth_date']) ?></td>
              <td class="px-4 py-3"><?= h($age) ?> yrs</td>
              <td class="px-4 py-3"><?= h($p['barangay']) ?></td>
              <td class="px-4 py-3">
                <?php if ($p['contact_no']): ?>
                  <div class="text-xs"><?= h($p['contact_no']) ?></div>
                <?php endif; ?>
                <?php if ($p['email']): ?>
                  <div class="text-xs text-slate-500"><?= h($p['email']) ?></div>
                <?php endif; ?>
                <?php if (!$p['contact_no'] && !$p['email']): ?>
                  <span class="text-xs text-slate-400">No contact</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $statusColor ?>">
                  <?= h(ucfirst($p['status'])) ?>
                </span>
              </td>
              <td class="px-4 py-3">
                <button type="button" class="patient-modal-edit text-blue-600 hover:text-blue-800 font-medium mr-3" data-embed-url="/HealthLogs/public/patients/form_embed.php?id=<?= (int)$p['id'] ?>">Quick edit</button>
                <a class="text-slate-500 hover:text-slate-800 text-xs" href="/HealthLogs/public/patients/form.php?id=<?= (int)$p['id'] ?>" title="Open full-page editor">Full form</a>
                <form method="post" action="/HealthLogs/public/patients/delete.php" class="inline ml-2" data-confirm="Delete this patient and all related records?" data-confirm-title="Delete patient">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                  <button class="text-red-600 hover:text-red-800 ml-3 font-medium">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?= $paginator->render() ?>
</div>

<div id="patientFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="patientFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-3 sm:mt-6 max-w-6xl px-2 sm:px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Patient form</div>
        <button type="button" id="patientFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="patientFormModalFrame" class="w-full min-h-[75vh] border-0 flex-1" title="Patient form"></iframe>
    </div>
  </div>
</div>

<script>
(function () {
  var modal = document.getElementById('patientFormModal');
  var frame = document.getElementById('patientFormModalFrame');
  var backdrop = document.getElementById('patientFormModalBackdrop');
  var closeBtn = document.getElementById('patientFormModalClose');

  function openModal(url) {
    if (!modal || !frame) return;
    frame.src = url;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    closeBtn && closeBtn.focus();
  }

  function closeModal() {
    if (!modal || !frame) return;
    frame.src = 'about:blank';
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  var newBtn = document.getElementById('patientModalOpenNew');
  if (newBtn) {
    newBtn.addEventListener('click', function () {
      var url = newBtn.getAttribute('data-embed-url');
      openModal(url);
    });
  }

  document.querySelectorAll('.patient-modal-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openModal(btn.getAttribute('data-embed-url') || '');
    });
  });

  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  window.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });
})();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
