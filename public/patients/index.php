<?php
$pageTitle = 'Patient Record Management';
require __DIR__ . '/../partials/bootstrap.php';

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$sexFilter = $_GET['sex'] ?? '';
$purokFilter = $_GET['purok'] ?? '';
$isPrintMode = (isset($_GET['print']) && $_GET['print'] === '1');

$whereParts = [];
$params = [];

$classificationFilter = trim($_GET['classification'] ?? '');

if ($q !== '') {
    $whereParts[] = "(first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR barangay LIKE ? OR COALESCE(contact_no, '') LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($purokFilter !== '') {
    $whereParts[] = "barangay = ?";
    $params[] = $purokFilter;
}
if (in_array($statusFilter, ['active', 'inactive', 'deceased'], true)) {
    $whereParts[] = "status = ?";
    $params[] = $statusFilter;
}
if (in_array($sexFilter, ['male', 'female'], true)) {
    $whereParts[] = "sex = ?";
    $params[] = $sexFilter;
}
if ($classificationFilter === 'pwd') {
    $whereParts[] = "is_pwd = 1";
} elseif ($classificationFilter === '4ps') {
    $whereParts[] = "is_4ps = 1";
} elseif ($classificationFilter !== '' && array_key_exists($classificationFilter, PatientClassifier::getClassifications())) {
    $whereParts[] = "classification = ?";
    $params[] = $classificationFilter;
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
    if ($purokFilter !== '') $filterParts[] = 'Purok: ' . $purokFilter;
    if ($statusFilter !== '') $filterParts[] = 'Status: ' . ucfirst($statusFilter);
    if ($sexFilter !== '') $filterParts[] = 'Gender: ' . ucfirst($sexFilter);
    $filterSummary = !empty($filterParts) ? implode(' | ', $filterParts) : 'All Patient Records (No Filters)';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <title>Official Patient Records List - HealthLogs</title>
      <link rel="icon" type="image/jpeg" href="/HealthLogs/public/assets/images/logo.jpeg">
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
        <div style="display: flex; align-items: center;">
          <img src="/HealthLogs/public/assets/images/logo.jpeg" alt="HealthLogs Logo" style="width: 55px; height: 55px; object-fit: cover; border-radius: 10px; border: 1px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        </div>
        <div class="header-center">
          <div class="rep-title">Republic of the Philippines • Province of Isabela • City of Ilagan</div>
          <div class="agency-title">Barangay Tangcul Primary Care & Health Services</div>
          <div class="hub-title">Barangay Tangcul Health Station & Care Hub</div>
          <div class="sys-title">HealthLogs Information Management System</div>
        </div>
        <div style="text-align: right; font-size: 9.5px; color: #64748b;">
          <div><strong>Date:</strong> <?= date('M d, Y') ?></div>
          <div><strong>Time:</strong> <?= date('h:i A') ?></div>
        </div>
      </div>

      <div class="doc-meta-box">
        <div>
          <div class="doc-title">Official Patient Records Master List (Barangay Tangcul)</div>
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
            <th>Purok</th>
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
          <div class="sig-role">Municipal / City Health Officer</div>
          <div class="sig-date">Date: ____________________</div>
        </div>
      </div>

      <div class="watermark-footer">
        Official HealthLogs System Generated Document • Certified Master Records • Barangay Tangcul, City of Ilagan • Timestamp: <?= h($currentDateTimeFormatted) ?>
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

// Calculate statistics including priority classifications
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN classification IN ('infant', 'under_five') THEN 1 ELSE 0 END) as pediatrics,
        SUM(CASE WHEN classification = 'pregnant' THEN 1 ELSE 0 END) as pregnant,
        SUM(CASE WHEN classification = 'senior' THEN 1 ELSE 0 END) as seniors,
        SUM(CASE WHEN is_pwd = 1 OR is_4ps = 1 THEN 1 ELSE 0 END) as priority_groups
    FROM patients
")->fetch();

require __DIR__ . '/../partials/header.php';
?>

<?php display_flash_messages(); ?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500 font-medium">Barangay Rural Health Unit</div>
      <div class="text-2xl font-bold text-slate-900">Patient Records & Master Health Registry</div>
      <p class="text-sm text-slate-500 mt-1">Comprehensive patient records, classifications (babies, pregnant mothers, seniors, PWD), and health history for Barangay Tangcul.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/appointments/index.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition shadow-xs">
        <i class="fas fa-calendar-check text-xs"></i> Appointments
      </a>
      <a target="_blank" href="/HealthLogs/public/patients/index.php?<?= h(http_build_query(array_merge($_GET, ['print' => '1']))) ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-sm font-medium border border-slate-300 transition shadow-xs">
        <i class="fas fa-print text-xs text-teal-700"></i> Print All Records
      </a>
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="patientModalOpenNew" data-embed-url="/HealthLogs/public/patients/form_embed.php" class="bg-slate-900 text-white px-4 py-2 rounded-lg shadow hover:bg-slate-800 transition text-sm font-semibold">
          <i class="fas fa-user-plus mr-1"></i> New Patient
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-6">
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Registry</div>
    <div class="text-2xl font-bold text-slate-900 mt-1"><?= number_format($stats['total']) ?></div>
    <div class="text-xs text-slate-500 mt-0.5"><?= number_format($stats['active']) ?> currently active</div>
  </div>
  <div class="bg-white p-4 rounded-xl border border-pink-200 bg-pink-50/30 shadow-xs">
    <div class="text-[11px] font-bold uppercase tracking-wider text-pink-700">Babies & Under-5</div>
    <div class="text-2xl font-bold text-pink-900 mt-1"><?= number_format($stats['pediatrics']) ?></div>
    <div class="text-xs text-pink-700 mt-0.5">EPI & Nutrition priority</div>
  </div>
  <div class="bg-white p-4 rounded-xl border border-rose-200 bg-rose-50/30 shadow-xs">
    <div class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Pregnant Mothers</div>
    <div class="text-2xl font-bold text-rose-900 mt-1"><?= number_format($stats['pregnant']) ?></div>
    <div class="text-xs text-rose-700 mt-0.5">Maternal care program</div>
  </div>
  <div class="bg-white p-4 rounded-xl border border-purple-200 bg-purple-50/30 shadow-xs">
    <div class="text-[11px] font-bold uppercase tracking-wider text-purple-700">Senior Citizens</div>
    <div class="text-2xl font-bold text-purple-900 mt-1"><?= number_format($stats['seniors']) ?></div>
    <div class="text-xs text-purple-700 mt-0.5">60+ years (RA 9994)</div>
  </div>
  <div class="bg-white p-4 rounded-xl border border-emerald-200 bg-emerald-50/30 shadow-xs col-span-2 md:col-span-1">
    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">PWD & 4Ps Priority</div>
    <div class="text-2xl font-bold text-emerald-900 mt-1"><?= number_format($stats['priority_groups']) ?></div>
    <div class="text-xs text-emerald-700 mt-0.5">Assisted households</div>
  </div>
</div>

<form method="get" class="mt-6 bg-white rounded-xl shadow-xs border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded-lg px-3 py-2 text-sm md:col-span-2" placeholder="Search patient name, purok, contact..." />
  
  <select name="classification" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
    <option value="">All Classifications</option>
    <option value="infant" <?= $classificationFilter === 'infant' ? 'selected' : '' ?>>Babies / Infants (0-11m)</option>
    <option value="under_five" <?= $classificationFilter === 'under_five' ? 'selected' : '' ?>>Under-5 Children (1-4y)</option>
    <option value="school_age" <?= $classificationFilter === 'school_age' ? 'selected' : '' ?>>School-Aged (5-9y)</option>
    <option value="adolescent" <?= $classificationFilter === 'adolescent' ? 'selected' : '' ?>>Adolescents (10-19y)</option>
    <option value="pregnant" <?= $classificationFilter === 'pregnant' ? 'selected' : '' ?>>Pregnant Mothers</option>
    <option value="postpartum" <?= $classificationFilter === 'postpartum' ? 'selected' : '' ?>>Postpartum / Lactating</option>
    <option value="adult" <?= $classificationFilter === 'adult' ? 'selected' : '' ?>>Adults (20-59y)</option>
    <option value="senior" <?= $classificationFilter === 'senior' ? 'selected' : '' ?>>Senior Citizens (60+y)</option>
    <option value="pwd" <?= $classificationFilter === 'pwd' ? 'selected' : '' ?>>Persons with Disability (PWD)</option>
    <option value="4ps" <?= $classificationFilter === '4ps' ? 'selected' : '' ?>>4Ps / Indigent Priority</option>
  </select>

  <select name="purok" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
    <option value="">All Puroks</option>
    <?php for ($i = 1; $i <= 7; $i++): $pVal = "Purok $i"; ?>
      <option value="<?= $pVal ?>" <?= $purokFilter === $pVal ? 'selected' : '' ?>><?= $pVal ?></option>
    <?php endfor; ?>
  </select>

  <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
    <option value="">All statuses</option>
    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    <option value="deceased" <?= $statusFilter === 'deceased' ? 'selected' : '' ?>>Deceased</option>
  </select>

  <div class="flex gap-2">
    <button class="w-full bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-800 transition" type="submit">Filter</button>
    <a class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition" href="/HealthLogs/public/patients/index.php">Reset</a>
  </div>
</form>

<div class="mt-6 bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold uppercase">
        <tr>
          <th class="text-left px-4 py-3">Patient Name</th>
          <th class="text-left px-4 py-3">Classification & Age</th>
          <th class="text-left px-4 py-3">Sex</th>
          <th class="text-left px-4 py-3">Purok</th>
          <th class="text-left px-4 py-3">Contact</th>
          <th class="text-left px-4 py-3">Status</th>
          <th class="text-right px-4 py-3">Clinical Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($patients)): ?>
          <tr><td class="px-4 py-8 text-center text-slate-500" colspan="7">No patient records found matching criteria.</td></tr>
        <?php else: ?>
          <?php foreach ($patients as $p): ?>
            <?php
              $exactAge = PatientClassifier::formatAge($p['birth_date']);
              $statusColors = [
                'active' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'inactive' => 'bg-amber-100 text-amber-800 border-amber-200',
                'deceased' => 'bg-slate-100 text-slate-700 border-slate-200'
              ];
              $statusColor = $statusColors[$p['status']] ?? 'bg-slate-100 text-slate-800';
            ?>
            <tr class="hover:bg-slate-50/80 transition">
              <td class="px-4 py-3">
                <a href="/HealthLogs/public/patients/view.php?id=<?= (int)$p['id'] ?>" class="font-bold text-slate-900 hover:text-teal-700 transition">
                  <?= h($p['last_name'] . ', ' . $p['first_name'] . ($p['middle_name'] ? ' ' . $p['middle_name'] : '')) ?>
                </a>
                <div class="text-[11px] text-slate-400 font-mono">ID: #<?= (int)$p['id'] ?><?= !empty($p['philhealth_no']) ? ' • PH: ' . h($p['philhealth_no']) : '' ?></div>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <?= PatientClassifier::renderBadge($p['classification'] ?? 'adult', $p['birth_date']) ?>
                  <?php if (!empty($p['is_4ps'])): ?>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">4Ps</span>
                  <?php endif; ?>
                  <?php if (!empty($p['is_pwd'])): ?>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">PWD</span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="px-4 py-3 capitalize text-slate-700 font-medium"><?= h($p['sex']) ?></td>
              <td class="px-4 py-3 text-slate-700"><?= h($p['barangay']) ?></td>
              <td class="px-4 py-3">
                <?php if ($p['contact_no']): ?>
                  <div class="text-xs font-medium text-slate-800"><?= h($p['contact_no']) ?></div>
                <?php else: ?>
                  <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border <?= $statusColor ?>">
                  <?= h(ucfirst($p['status'])) ?>
                </span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <a href="/HealthLogs/public/patients/view.php?id=<?= (int)$p['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 font-semibold text-xs border border-teal-200 mr-2 transition">
                  <i class="fas fa-file-medical text-[11px]"></i> View Chart
                </a>
                <?php if (can_manage_clinical_records()): ?>
                  <button type="button" class="patient-modal-edit text-blue-600 hover:text-blue-800 font-medium text-xs mr-2" data-embed-url="/HealthLogs/public/patients/form_embed.php?id=<?= (int)$p['id'] ?>">Edit</button>
                  <form method="post" action="/HealthLogs/public/patients/delete.php" class="inline" data-confirm="Delete this patient and all related records?" data-confirm-title="Delete patient">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                    <button class="text-rose-600 hover:text-rose-800 font-medium text-xs">Delete</button>
                  </form>
                <?php endif; ?>
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
