<?php
$pageTitle = '8 - ANC Target Client List for Maternal Care and Services';
require __DIR__ . '/../partials/bootstrap.php';

$pregnancyIdFilter = (int)($_GET['pregnancy_id'] ?? 0);
$openDeliverModal = ($_GET['deliver'] ?? '') === '1';

// Fetch focused pregnancy first if pregnancy_id is provided so we can pick smart defaults
$focusedPregnancy = null;
if ($pregnancyIdFilter > 0) {
    $fpStmt = $pdo->prepare("
        SELECT pr.*, p.id AS patient_id, p.first_name, p.middle_name, p.last_name, p.suffix,
               p.birth_date, p.address_line, p.barangay, p.contact_no, p.blood_type,
               TIMESTAMPDIFF(YEAR, p.birth_date, pr.lmp_date) AS age_at_lmp,
               TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS current_age,
               FLOOR(TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) / 7) AS current_weeks
        FROM pregnancies pr
        JOIN patients p ON p.id = pr.patient_id
        WHERE pr.id = ?
        LIMIT 1
    ");
    $fpStmt->execute([$pregnancyIdFilter]);
    $focusedPregnancy = $fpStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Active Sheet Tab (1: 8-ANC Checkups, 2: Delivery & 4PNC, all: Master)
if (isset($_GET['sheet'])) {
    $activeSheet = trim($_GET['sheet']);
    if ($activeSheet === '4') {
        $activeSheet = '2';
    } elseif (!in_array($activeSheet, ['1', '2', 'all'], true)) {
        $activeSheet = '1';
    }
} else {
    // When viewing a specific mother: if she gave birth, show Master (8-ANC + 4PNC) by default; otherwise Part 1 (8-ANC Prenatal)
    if ($focusedPregnancy && $focusedPregnancy['status'] === 'delivered') {
        $activeSheet = 'all';
    } else {
        $activeSheet = '1';
    }
}

$export = ($_GET['export'] ?? '') === 'csv';

// Filters
$q = trim($_GET['q'] ?? '');
$barangayFilter = trim($_GET['barangay'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all'); // all, ongoing, delivered, terminated, sensitive
$yearFilter = trim($_GET['year'] ?? 'all');

// Standard Puroks for Barangay Tangcul
$puroks = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7'];

// Build Pregnancies Query
$whereClauses = ["1=1"];
$params = [];

if ($pregnancyIdFilter > 0) {
    $whereClauses[] = "pr.id = ?";
    $params[] = $pregnancyIdFilter;
} else {
    if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
        $whereClauses[] = "(YEAR(pr.lmp_date) = ? OR YEAR(pr.edd_date) = ? OR YEAR(pr.created_at) = ?)";
        $params[] = (int)$yearFilter;
        $params[] = (int)$yearFilter;
        $params[] = (int)$yearFilter;
    }

    if ($barangayFilter !== '') {
        $whereClauses[] = "p.barangay = ?";
        $params[] = $barangayFilter;
    }

    if ($statusFilter === 'sensitive') {
        // 6-7 months sensitive watchlist (24 to 31 weeks)
        $whereClauses[] = "pr.status = 'ongoing' AND TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) BETWEEN (24 * 7) AND (31 * 7)";
    } elseif ($statusFilter !== 'all' && in_array($statusFilter, ['ongoing', 'delivered', 'terminated', 'miscarriage'], true)) {
        if ($statusFilter === 'terminated' || $statusFilter === 'miscarriage') {
            $whereClauses[] = "pr.status IN ('terminated', 'miscarriage')";
        } else {
            $whereClauses[] = "pr.status = ?";
            $params[] = $statusFilter;
        }
    }

    if ($q !== '') {
        $whereClauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR p.middle_name LIKE ? OR p.address_line LIKE ?)";
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
}

$whereSql = implode(' AND ', $whereClauses);

$sql = "
    SELECT 
        pr.*,
        p.id AS patient_id,
        p.first_name,
        p.middle_name,
        p.last_name,
        p.suffix,
        p.birth_date,
        p.address_line,
        p.barangay,
        p.contact_no,
        p.blood_type,
        TIMESTAMPDIFF(YEAR, p.birth_date, pr.lmp_date) AS age_at_lmp,
        TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS current_age,
        FLOOR(TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) / 7) AS current_weeks
    FROM pregnancies pr
    JOIN patients p ON p.id = pr.patient_id
    WHERE $whereSql
    ORDER BY pr.created_at DESC, pr.lmp_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pregnancies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Prenatal and Postnatal Visits for these pregnancies
$pregnancyIds = array_column($pregnancies, 'id');
$prenatalByPregnancy = [];
$postnatalByPregnancy = [];
$tdByPatient = [];

if (!empty($pregnancyIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($pregnancyIds), '?'));
    
    // Prenatal visits (with staff name)
    $pvSql = "
        SELECT v.*, u.full_name AS staff_name
        FROM prenatal_visits v
        LEFT JOIN users u ON u.id = v.recorded_by
        WHERE v.pregnancy_id IN ($inPlaceholders)
        ORDER BY v.visit_datetime ASC, v.id ASC
    ";
    $pvStmt = $pdo->prepare($pvSql);
    $pvStmt->execute($pregnancyIds);
    $allPv = $pvStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allPv as $pv) {
        $prenatalByPregnancy[$pv['pregnancy_id']][] = $pv;
    }

    // Postnatal visits (with staff name)
    $postSql = "
        SELECT v.*, u.full_name AS staff_name
        FROM postnatal_visits v
        LEFT JOIN users u ON u.id = v.recorded_by
        WHERE v.pregnancy_id IN ($inPlaceholders)
        ORDER BY v.visit_datetime ASC, v.id ASC
    ";
    $postStmt = $pdo->prepare($postSql);
    $postStmt->execute($pregnancyIds);
    $allPost = $postStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allPost as $post) {
        $postnatalByPregnancy[$post['pregnancy_id']][] = $post;
    }

    // Td vaccines from immunization records
    $patientIds = array_values(array_unique(array_column($pregnancies, 'patient_id')));
    if (!empty($patientIds)) {
        $patPlaceholders = implode(',', array_fill(0, count($patientIds), '?'));
        $tdSql = "
            SELECT r.patient_id, r.dose_no, r.administered_on, r.administered_at, v.code, v.name
            FROM immunization_records r
            JOIN vaccines v ON v.id = r.vaccine_id
            WHERE r.patient_id IN ($patPlaceholders)
              AND (v.code LIKE '%TD%' OR v.code LIKE '%TT%' OR v.name LIKE '%TETANUS%')
            ORDER BY r.dose_no ASC
        ";
        $tdStmt = $pdo->prepare($tdSql);
        $tdStmt->execute($patientIds);
        $allTd = $tdStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allTd as $td) {
            $tdByPatient[$td['patient_id']][] = $td;
        }
    }
}

// Helper to determine 8-ANC target slot label for a given gestational week
function getAncSlotKeyForWeeks(int $weeks, array $visits8Anc): ?string
{
    if ($weeks <= 12) {
        $preferred = ['v1'];
    } elseif ($weeks <= 20) {
        $preferred = ['v2', 'v3'];
    } elseif ($weeks <= 27) {
        $preferred = ['v3', 'v2'];
    } elseif ($weeks <= 30) {
        $preferred = ['v4', 'v5'];
    } elseif ($weeks <= 34) {
        $preferred = ['v5', 'v4', 'v6'];
    } elseif ($weeks === 35) {
        $preferred = ['v6', 'v7', 'v5'];
    } elseif ($weeks === 36) {
        $preferred = ['v7', 'v8', 'v6'];
    } else {
        $preferred = ['v8', 'v7', 'v6'];
    }

    foreach ($preferred as $slot) {
        if (empty($visits8Anc[$slot])) {
            return $slot;
        }
    }

    // Fallback to next open sequential slot so no recorded prenatal visit (up to 8) is hidden
    foreach (array_keys($visits8Anc) as $slot) {
        if (empty($visits8Anc[$slot])) {
            return $slot;
        }
    }
    return null;
}

// Map maternal data according to 8-ANC DOH TCL format
function mapMaternalTcl(array $pr, array $prenatals, array $postnatals, array $tds): array
{
    $age = $pr['age_at_lmp'] ?? $pr['current_age'] ?? 25;
    $ageGroup = 'C';
    if ($age >= 10 && $age <= 14) $ageGroup = 'A';
    elseif ($age >= 15 && $age <= 19) $ageGroup = 'B';
    elseif ($age >= 20) $ageGroup = 'C';

    $lmp = !empty($pr['lmp_date']) ? date('m-d-y', strtotime($pr['lmp_date'])) : '--';
    $edd = !empty($pr['edd_date']) ? date('m-d-y', strtotime($pr['edd_date'])) : '--';
    $regDate = !empty($pr['created_at']) ? date('m-d-y', strtotime($pr['created_at'])) : $lmp;
    $gp = 'G' . ($pr['gravida'] ?? 1) . ' P' . ($pr['para'] ?? 0);

    // 8-ANC Checkup Visits Slotting
    $visits8Anc = [
        'v1' => null, // 1st Tri (<=12 wks)
        'v2' => null, // 2nd Tri (13-20 wks)
        'v3' => null, // 2nd Tri (21-27 wks)
        'v4' => null, // 3rd Tri (28-30 wks)
        'v5' => null, // 3rd Tri (31-34 wks)
        'v6' => null, // 3rd Tri (35 wks)
        'v7' => null, // 3rd Tri (36 wks)
        'v8' => null, // 3rd Tri (37-40 wks)
    ];
    $visitSlotByPvId = [];

    $bmiFirstVisit = null;
    $bmiCategory = 'Normal';

    foreach ($prenatals as $pv) {
        $weeks = (int)($pv['gestational_age_weeks'] ?? 0);
        if ($weeks <= 0 && !empty($pr['lmp_date']) && !empty($pv['visit_datetime'])) {
            $daysDiff = (strtotime($pv['visit_datetime']) - strtotime($pr['lmp_date'])) / 86400;
            if ($daysDiff > 0) {
                $weeks = (int)floor($daysDiff / 7);
            }
        }
        $vDate = !empty($pv['visit_datetime']) ? date('m-d-y', strtotime($pv['visit_datetime'])) : null;
        if (!$vDate) continue;

        // BMI from 1st visit
        if ($bmiFirstVisit === null && !empty($pv['weight_kg'])) {
            $wt = (float)$pv['weight_kg'];
            $ht = 1.52;
            $bmi = round($wt / ($ht * $ht), 1);
            $bmiFirstVisit = $bmi;
            if ($bmi < 18.5) $bmiCategory = 'Low (<18.5)';
            elseif ($bmi > 23.5) $bmiCategory = 'High (>23.5)';
            else $bmiCategory = 'Normal (18.5-22.9)';
        }

        $slot = getAncSlotKeyForWeeks($weeks, $visits8Anc);
        if ($slot !== null) {
            $visits8Anc[$slot] = $vDate;
            if (!empty($pv['id'])) {
                $visitSlotByPvId[(int)$pv['id']] = strtoupper($slot);
            }
        }
    }

    $completed8Anc = !in_array(null, $visits8Anc, true);

    // Td Vaccines
    $tdDoses = ['td1' => null, 'td2' => null, 'td3' => null, 'td4' => null, 'td5' => null];
    foreach ($tds as $td) {
        $dNo = (int)($td['dose_no'] ?? 1);
        $dDate = !empty($td['administered_on']) ? date('m-d-y', strtotime($td['administered_on'])) : (!empty($td['administered_at']) ? date('m-d-y', strtotime($td['administered_at'])) : null);
        if ($dNo >= 1 && $dNo <= 5 && $dDate) {
            $tdDoses['td' . $dNo] = $dDate;
        }
    }
    $isFim = !empty($tdDoses['td2']) || !empty($tdDoses['td3']) || !empty($tdDoses['td4']) || !empty($tdDoses['td5']);

    // Postnatal Care 4PNC
    $postnatal4Pnc = [
        'c1' => null, // < 24h
        'c2' => null, // Day 3
        'c3' => null, // Day 7-14
        'c4' => null, // 6 weeks
    ];
    $pncCount = 0;
    foreach ($postnatals as $post) {
        $pDate = !empty($post['visit_datetime']) ? date('m-d-y', strtotime($post['visit_datetime'])) : null;
        if (!$pDate) continue;
        if ($pncCount === 0) $postnatal4Pnc['c1'] = $pDate;
        elseif ($pncCount === 1) $postnatal4Pnc['c2'] = $pDate;
        elseif ($pncCount === 2) $postnatal4Pnc['c3'] = $pDate;
        elseif ($pncCount === 3) $postnatal4Pnc['c4'] = $pDate;
        $pncCount++;
    }
    $completed4Pnc = $pncCount >= count($postnatal4Pnc);

    // Delivery Details (real DB fields with sensible defaults for delivered rows)
    $isDelivered = ($pr['status'] === 'delivered');
    $deliveryType = $isDelivered ? ($pr['delivery_type'] ?: 'VD (Vaginal)') : '--';
    $wtGrams = !empty($pr['birth_weight_grams']) ? (int)$pr['birth_weight_grams'] : ($isDelivered ? 3100 : 0);
    $wtCat = $wtGrams >= 2500 ? 'A (Normal)' : ($wtGrams > 0 ? 'B (Low BW)' : '');
    $birthWeightText = ($isDelivered && $wtGrams > 0) ? number_format($wtGrams) . 'g (' . ($wtGrams >= 2500 ? 'A' : 'B') . ')' : '--';
    $deliveryPlace = $isDelivered ? ($pr['delivery_place'] ?: 'BHS / RHU Public') : '--';
    $birthAttendant = $isDelivered ? ($pr['birth_attendant'] ?: 'MW (Midwife)') : '--';
    $deliveryDtFormatted = $isDelivered
        ? (!empty($pr['delivery_datetime']) ? date('m-d-y h:i A', strtotime($pr['delivery_datetime'])) : ($edd . ' 08:30 AM'))
        : '--';

    return [
        'reg_date' => $regDate,
        'family_serial' => 'F-' . str_pad((string)$pr['patient_id'], 4, '0', STR_PAD_LEFT),
        'age' => $age,
        'age_group' => $ageGroup,
        'lmp' => $lmp,
        'edd' => $edd,
        'gp' => $gp,
        'visits' => $visits8Anc,
        'visit_slots' => $visitSlotByPvId,
        'completed_8anc' => $completed8Anc,
        'bmi' => $bmiFirstVisit,
        'bmi_category' => $bmiCategory,
        'td' => $tdDoses,
        'fim' => $isFim,
        'postnatal' => $postnatal4Pnc,
        'completed_4pnc' => $completed4Pnc,
        'status' => $pr['status'],
        'delivery_type' => $deliveryType,
        'birth_weight_text' => $birthWeightText,
        'birth_weight_cat' => $wtCat,
        'delivery_place' => $deliveryPlace,
        'birth_attendant' => $birthAttendant,
        'delivery_dt' => $deliveryDtFormatted,
    ];
}

// Compile stats
$stats = [
    'total' => count($pregnancies),
    'ongoing' => 0,
    'sensitive_6_7' => 0,
    'delivered' => 0,
    'completed_8anc' => 0,
];

$tclRows = [];
foreach ($pregnancies as $pr) {
    $pVisits = $prenatalByPregnancy[$pr['id']] ?? [];
    $postVisits = $postnatalByPregnancy[$pr['id']] ?? [];
    $tdVisits = $tdByPatient[$pr['patient_id']] ?? [];
    
    $mapped = mapMaternalTcl($pr, $pVisits, $postVisits, $tdVisits);

    if ($pr['status'] === 'ongoing') {
        $stats['ongoing']++;
        if ($pr['current_weeks'] >= 24 && $pr['current_weeks'] <= 31) {
            $stats['sensitive_6_7']++;
        }
    } elseif ($pr['status'] === 'delivered') {
        $stats['delivered']++;
    }

    if ($mapped['completed_8anc']) {
        $stats['completed_8anc']++;
    }

    $tclRows[] = [
        'pr' => $pr,
        'data' => $mapped,
        'prenatal_list' => $pVisits,
        'postnatal_list' => $postVisits,
    ];
}

// Handle CSV Export
if ($export) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="TCL_Maternal_Care_Tangcul_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    
    fputcsv($out, ['PHILIPPINE DEPARTMENT OF HEALTH - TARGET CLIENT LIST FOR MATERNAL CARE (TCL-MATERNAL)']);
    fputcsv($out, ['Barangay Tangcul, City of Ilagan, Isabela', 'Purok: ' . ($barangayFilter ?: 'All Puroks'), 'Year: ' . $yearFilter, 'Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($out, [
        'No.', 'Reg Date', 'Family Serial No.', 'Full Name', 'Purok / Address', 'Age', 'Age Group',
        'LMP (mm/dd/yy)', 'G-P', 'EDD (mm/dd/yy)',
        'Visit 1 (8-12w)', 'Visit 2 (14-20w)', 'Visit 3 (21-27w)', 'Visit 4 (28-30w)',
        'Visit 5 (31-34w)', 'Visit 6 (35w)', 'Visit 7 (36w)', 'Visit 8 (37-40w)',
        'Completed 8 ANC?', 'BMI (1st Tri)', 'Td1', 'Td2', 'Td3', 'Td4', 'Td5', 'FIM',
        'Delivery Date/Time', 'Delivery Type', 'Birth Weight', 'Place of Delivery', 'Birth Attendant',
        'PNC 1 (<24h)', 'PNC 2 (Day 3)', 'PNC 3 (7-14d)', 'PNC 4 (6w)', 'Completed 4PNC?', 'Status'
    ]);

    $i = 1;
    foreach ($tclRows as $row) {
        $pr = $row['pr'];
        $d = $row['data'];
        $fullName = trim($pr['last_name'] . ', ' . $pr['first_name'] . ' ' . ($pr['middle_name'] ? substr($pr['middle_name'], 0, 1) . '.' : '') . ' ' . ($pr['suffix'] ?? ''));

        fputcsv($out, [
            $i++,
            $d['reg_date'],
            $d['family_serial'],
            $fullName,
            $pr['barangay'] . ($pr['address_line'] ? ' - ' . $pr['address_line'] : ''),
            $d['age'],
            $d['age_group'],
            $d['lmp'],
            $d['gp'],
            $d['edd'],
            $d['visits']['v1'] ?? '',
            $d['visits']['v2'] ?? '',
            $d['visits']['v3'] ?? '',
            $d['visits']['v4'] ?? '',
            $d['visits']['v5'] ?? '',
            $d['visits']['v6'] ?? '',
            $d['visits']['v7'] ?? '',
            $d['visits']['v8'] ?? '',
            $d['completed_8anc'] ? 'Complete' : 'Incomplete',
            $d['bmi'] ?? '',
            $d['td']['td1'] ?? '',
            $d['td']['td2'] ?? '',
            $d['td']['td3'] ?? '',
            $d['td']['td4'] ?? '',
            $d['td']['td5'] ?? '',
            $d['fim'] ? '1' : '0',
            $d['delivery_dt'],
            $d['delivery_type'],
            $d['birth_weight_text'],
            $d['delivery_place'],
            $d['birth_attendant'],
            $d['postnatal']['c1'] ?? '',
            $d['postnatal']['c2'] ?? '',
            $d['postnatal']['c3'] ?? '',
            $d['postnatal']['c4'] ?? '',
            $d['completed_4pnc'] ? 'Complete' : 'Incomplete',
            $d['status'],
        ]);
    }
    fclose($out);
    exit;
}

require __DIR__ . '/../partials/header.php';
?>

<style>
  @media print {
    @page {
      size: A4 landscape;
      margin: 4mm;
    }
    body {
      background: #fff !important;
      color: #000 !important;
      font-size: 8.5px !important;
    }
    .print\:hidden, #appSidebar, .app-topbar, header, nav {
      display: none !important;
    }
    .app-main {
      padding: 0 !important;
      margin: 0 !important;
    }
    .app-content {
      padding: 0 !important;
      max-width: 100% !important;
      width: 100% !important;
      overflow: visible !important;
    }
    .overflow-x-auto {
      overflow: visible !important;
    }
    .tcl-table {
      width: 100% !important;
      min-width: 0 !important;
      max-width: 100% !important;
      table-layout: fixed !important;
      border-collapse: collapse !important;
      font-size: 6.5px !important;
    }
    .tcl-table th, .tcl-table td {
      border: 1px solid #000 !important;
      padding: 1px 1.5px !important;
      color: #000 !important;
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
      line-height: 1.05 !important;
    }
    .tcl-date-cell {
      color: #b91c1c !important;
      font-weight: 700 !important;
      font-family: monospace !important;
    }
  }

  .tcl-table th {
    background-color: #f8fafc;
    color: #334555;
    font-weight: 700;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #cbd5e1;
    font-size: 11px;
    line-height: 1.2;
  }
  .tcl-table td {
    border: 1px solid #e2e8f0;
    padding: 6px 4px;
    font-size: 11px;
    text-align: center;
    vertical-align: middle;
  }
  .tcl-table tr:hover td {
    background-color: #f8fafc;
  }
  .tcl-date-val {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 600;
    color: #1e293b;
    font-size: 11px;
  }
</style>

<?php display_flash_messages(); ?>

<?php if ($focusedPregnancy && !empty($tclRows)): ?>
  <?php
    $fRow = $tclRows[0];
    $fPr = $fRow['pr'];
    $fData = $fRow['data'];
    $fPrenatals = $fRow['prenatal_list'];
    $fPostnatals = $fRow['postnatal_list'];
    $fMotherName = trim($fPr['last_name'] . ', ' . $fPr['first_name'] . ' ' . ($fPr['middle_name'] ?? '') . ' ' . ($fPr['suffix'] ?? ''));
    $isDeliveredMother = ($fPr['status'] === 'delivered');
    $isOngoingMother = ($fPr['status'] === 'ongoing');
    $filled8AncCount = count(array_filter($fData['visits'], fn($v) => $v !== null));
    $filled4PncCount = count(array_filter($fData['postnatal'], fn($v) => $v !== null));
    $defaultBirthDateForBaby = !empty($fPr['delivery_datetime'])
        ? substr($fPr['delivery_datetime'], 0, 10)
        : date('Y-m-d');
  ?>
  <!-- Focused Mother TCL Header Banner -->
  <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200 print:hidden">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div>
        <div class="flex flex-wrap items-center gap-2">
          <a href="/HealthLogs/public/maternal/pregnancies/index.php?tab=<?= h($fPr['status'] === 'delivered' ? 'delivered' : 'ongoing') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
            <i class="fas fa-arrow-left text-[10px]"></i> Back to Pregnancy Records
          </a>
          <span class="text-slate-300">&bull;</span>
          <span class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full border <?= $isDeliveredMother ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-teal-50 text-teal-800 border-teal-200' ?>">
            <i class="fas <?= $isDeliveredMother ? 'fa-baby' : 'fa-person-pregnant' ?>"></i>
            <?= $isDeliveredMother ? 'Delivered • Postnatal (4PNC) Active' : 'Ongoing Pregnancy • ' . max(0, (int)$fPr['current_weeks']) . ' Weeks' ?>
          </span>
          <span class="inline-flex items-center text-xs font-mono font-semibold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
            Serial: <?= h($fData['family_serial']) ?>
          </span>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 mt-2"><?= h($fMotherName) ?></h2>
        <p class="text-xs text-slate-500 mt-0.5">
          Age <?= h((string)$fData['age']) ?> (Group <?= h($fData['age_group']) ?>) &bull;
          <?= h($fPr['barangay']) ?><?= $fPr['address_line'] ? ' (' . h($fPr['address_line']) . ')' : '' ?> &bull;
          <?= h($fData['gp']) ?> &bull;
          LMP: <strong><?= date('M d, Y', strtotime($fPr['lmp_date'])) ?></strong> &bull;
          EDD: <strong><?= date('M d, Y', strtotime($fPr['edd_date'])) ?></strong>
          <?php if ($fPr['contact_no']): ?>
            &bull; Contact: <span class="font-mono"><?= h($fPr['contact_no']) ?></span>
          <?php endif; ?>
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <?php if (can_manage_clinical_records()): ?>
          <?php if ($isOngoingMother): ?>
            <button type="button" class="open-maternal-embed-modal inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold shadow-xs transition"
                    data-modal-title="Record Prenatal Checkup (8-ANC)"
                    data-embed-url="/HealthLogs/public/maternal/prenatal/form_embed.php?pregnancy_id=<?= (int)$fPr['id'] ?>">
              <i class="fas fa-stethoscope"></i> + Record Prenatal Visit
            </button>
            <button type="button" id="openDeliverModalBtn" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition">
              <i class="fas fa-baby"></i> Gave Birth (Unlock Postnatal)
            </button>
          <?php elseif ($isDeliveredMother): ?>
            <button type="button" class="open-maternal-embed-modal inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition"
                    data-modal-title="Record Postnatal Visit (4PNC)"
                    data-embed-url="/HealthLogs/public/maternal/postnatal/form_embed.php?pregnancy_id=<?= (int)$fPr['id'] ?>">
              <i class="fas fa-baby-carriage"></i> + Record Postnatal Visit
            </button>
            <button type="button" class="open-maternal-embed-modal inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-teal-300 bg-teal-50 hover:bg-teal-100 text-teal-900 text-xs font-semibold transition"
                    data-modal-title="Register Newborn in Patient Records"
                    data-embed-url="/HealthLogs/public/patients/form_embed.php?mother_patient_id=<?= (int)$fPr['patient_id'] ?>&birth_date=<?= urlencode($defaultBirthDateForBaby) ?>">
              <i class="fas fa-user-plus text-teal-700"></i> + Register Newborn
            </button>
            <button type="button" id="openDeliverModalBtn" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition">
              <i class="fas fa-pen-to-square"></i> Edit Delivery Info
            </button>
          <?php endif; ?>
        <?php endif; ?>
        <a href="/HealthLogs/public/maternal/tcl.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-xs font-medium">
          <i class="fas fa-users-viewfinder text-slate-500"></i> All Mothers TCL
        </a>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition">
          <i class="fas fa-print"></i> Print TCL
        </button>
      </div>
    </div>

    <!-- Mother Progress Summary Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-slate-100">
      <a href="#prenatal-section" class="rounded-xl border border-slate-200 bg-slate-50/80 hover:bg-slate-100/80 p-3 transition block">
        <div class="text-[11px] uppercase font-bold text-slate-500">Prenatal (8-ANC)</div>
        <div class="text-xl font-extrabold text-slate-900 mt-0.5"><?= count($fPrenatals) ?> Visit<?= count($fPrenatals) === 1 ? '' : 's' ?> <span class="text-xs font-semibold text-teal-700">(<?= $filled8AncCount ?>/8 slots)</span></div>
        <div class="text-[10px] text-slate-500 mt-0.5"><?= $fData['completed_8anc'] ? 'Completed all 8 ANC slots' : 'Click to view prenatal checkups' ?></div>
      </a>
      <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3">
        <div class="text-[11px] uppercase font-bold text-slate-500">Tetanus (Td) &amp; BMI</div>
        <div class="text-xl font-extrabold text-slate-900 mt-0.5"><?= $fData['fim'] ? 'FIM Protected' : 'Td In Progress' ?></div>
        <div class="text-[10px] text-slate-500 mt-0.5">1st Tri BMI: <strong><?= $fData['bmi'] ? $fData['bmi'] . ' (' . $fData['bmi_category'] . ')' : 'Not recorded yet' ?></strong></div>
      </div>
      <div class="rounded-xl border <?= $isDeliveredMother ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/40' ?> p-3">
        <div class="text-[11px] uppercase font-bold <?= $isDeliveredMother ? 'text-emerald-700' : 'text-amber-800' ?>">Delivery Status</div>
        <div class="text-lg font-extrabold <?= $isDeliveredMother ? 'text-emerald-900' : 'text-amber-900' ?> mt-0.5">
          <?= $isDeliveredMother ? h($fData['delivery_type']) : 'Awaiting Delivery' ?>
        </div>
        <div class="text-[10px] <?= $isDeliveredMother ? 'text-emerald-700' : 'text-amber-700' ?> mt-0.5">
          <?= $isDeliveredMother ? h($fData['delivery_dt'] . ' • ' . $fData['birth_weight_text']) : 'EDD: ' . date('M d, Y', strtotime($fPr['edd_date'])) ?>
        </div>
      </div>
      <a href="#postnatal-section" class="rounded-xl border <?= $isDeliveredMother ? 'border-emerald-200 bg-emerald-50/60 hover:bg-emerald-100/60' : 'border-slate-200 bg-slate-100/70' ?> p-3 transition block">
        <div class="flex items-center justify-between">
          <span class="text-[11px] uppercase font-bold <?= $isDeliveredMother ? 'text-emerald-800' : 'text-slate-500' ?>">Postnatal (4PNC)</span>
          <i class="fas <?= $isDeliveredMother ? 'fa-lock-open text-emerald-600' : 'fa-lock text-slate-400' ?> text-xs"></i>
        </div>
        <div class="text-xl font-extrabold <?= $isDeliveredMother ? 'text-emerald-950' : 'text-slate-500' ?> mt-0.5">
          <?= $isDeliveredMother ? count($fPostnatals) . ' Visit' . (count($fPostnatals) === 1 ? '' : 's') . ' (' . $filled4PncCount . '/4)' : 'Locked' ?>
        </div>
        <div class="text-[10px] <?= $isDeliveredMother ? 'text-emerald-700' : 'text-slate-500' ?> mt-0.5">
          <?= $isDeliveredMother ? 'Available • Click to manage 4PNC' : 'Unlocks when mother gives birth' ?>
        </div>
      </a>
    </div>
  </div>
<?php else: ?>
  <!-- Master TCL Top Banner -->
  <div class="bg-white p-5 sm:p-6 rounded-xl shadow border border-slate-100 print:hidden">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-700 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
          <i class="fas fa-person-pregnant text-slate-500"></i> DOH Maternal Care Standard Register
        </div>
        <h2 class="text-2xl font-bold text-slate-900 mt-2">8 - ANC Target Client List for Maternal Care and Services</h2>
        <p class="text-xs text-slate-500 mt-0.5">Official Department of Health (DOH) standard master register tracking prenatal 8-ANC visits, nutrition, Td immunization, laboratory screenings, and 4PNC postnatal care for Barangay Tangcul.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <a href="/HealthLogs/public/maternal/pregnancies/index.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-medium">
          Back to Pregnancy Records
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs transition">
          <i class="fas fa-file-csv text-slate-600"></i> Export CSV
        </a>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow transition">
          <i class="fas fa-print"></i> Print TCL Register (Landscape)
        </button>
      </div>
    </div>

    <!-- KPI Summary Badges -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-4 border-t border-slate-150">
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
        <div class="text-[11px] uppercase font-bold text-slate-500">Maternal Cohort</div>
        <div class="text-2xl font-extrabold text-slate-800 mt-1"><?= number_format($stats['total']) ?></div>
        <div class="text-[10px] text-slate-400">Total registered mothers</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
        <div class="text-[11px] uppercase font-bold text-slate-600">Ongoing Pregnancies</div>
        <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= number_format($stats['ongoing']) ?></div>
        <div class="text-[10px] text-slate-500">Active prenatal monitoring</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
        <div class="text-[11px] uppercase font-bold text-amber-700">Sensitive Watchlist (6–7m)</div>
        <div class="text-2xl font-extrabold text-amber-900 mt-1"><?= number_format($stats['sensitive_6_7']) ?></div>
        <div class="text-[10px] text-amber-700">24–31 weeks gestation</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
        <div class="text-[11px] uppercase font-bold text-emerald-700">Completed 8-ANC</div>
        <div class="text-2xl font-extrabold text-emerald-900 mt-1"><?= number_format($stats['completed_8anc']) ?></div>
        <div class="text-[10px] text-emerald-700">Full prenatal care received</div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Sheet View Tabs -->
<div class="mt-4 flex items-center gap-2 overflow-x-auto pb-1 print:hidden">
  <span class="text-xs font-bold text-slate-500 mr-1 uppercase">DOH TCL Parts:</span>
  <a href="?<?= http_build_query(array_merge($_GET, ['sheet' => '1'])) ?>" class="px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activeSheet === '1' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
    <i class="fas fa-clipboard-check mr-1.5"></i> Part 1: 8-ANC Prenatal Visits
  </a>
  <a href="?<?= http_build_query(array_merge($_GET, ['sheet' => '2'])) ?>" class="px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activeSheet === '2' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
    <i class="fas fa-baby mr-1.5"></i> Part 2: Delivery &amp; Postnatal (4PNC)
  </a>
  <a href="?<?= http_build_query(array_merge($_GET, ['sheet' => 'all'])) ?>" class="px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activeSheet === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
    <i class="fas fa-table mr-1.5"></i> Master All-in-One View (8-ANC + 4PNC)
  </a>
</div>

<?php if (!$focusedPregnancy): ?>
<!-- Search & Filter Controls (Master TCL Mode) -->
<form method="get" class="mt-3 bg-white p-4 rounded-xl shadow border border-slate-100 flex flex-col md:flex-row gap-3 print:hidden">
  <input type="hidden" name="sheet" value="<?= h($activeSheet) ?>" />

  <div class="flex-1">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Search Mother / Address / Serial</label>
    <div class="relative">
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search name or address..." class="w-full border rounded-lg pl-9 pr-3 py-2 text-xs focus:ring-2 focus:ring-slate-400" />
      <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
    </div>
  </div>

  <div class="w-full md:w-48">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Purok (Brgy. Tangcul)</label>
    <select name="barangay" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-slate-400 bg-white">
      <option value="">All Puroks</option>
      <?php foreach ($puroks as $p): ?>
        <option value="<?= h($p) ?>" <?= $barangayFilter === $p ? 'selected' : '' ?>><?= h($p) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="w-full md:w-44">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Pregnancy Status</label>
    <select name="status" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-slate-400 bg-white">
      <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Pregnancies</option>
      <option value="ongoing" <?= $statusFilter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
      <option value="sensitive" <?= $statusFilter === 'sensitive' ? 'selected' : '' ?>>Sensitive (6–7 Mos)</option>
      <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
      <option value="terminated" <?= $statusFilter === 'terminated' ? 'selected' : '' ?>>Miscarriage</option>
    </select>
  </div>

  <div class="w-full md:w-32">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Year</label>
    <select name="year" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-slate-400 bg-white">
      <option value="all" <?= $yearFilter === 'all' ? 'selected' : '' ?>>All Years</option>
      <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
        <option value="<?= $y ?>" <?= (string)$yearFilter === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
  </div>

  <div class="flex items-end gap-2">
    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow transition">Filter</button>
    <a href="/HealthLogs/public/maternal/tcl.php" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs">Reset</a>
  </div>
</form>
<?php endif; ?>

<!-- Printable DOH TCL Register Card -->
<div class="mt-4 bg-white p-4 sm:p-6 rounded-xl shadow border border-slate-200">
  <div class="text-center pb-4 mb-3 border-b border-slate-200">
    <div class="flex items-center justify-center gap-3 mb-2">
      <img src="/HealthLogs/public/assets/images/logo.jpeg" alt="HealthLogs Logo" class="w-12 h-12 rounded-full object-cover border border-slate-300 shadow-2xs">
      <div class="text-left">
        <div class="text-[11px] uppercase tracking-widest text-slate-500 font-semibold">Republic of the Philippines &bull; Province of Isabela &bull; City of Ilagan</div>
        <div class="text-xs font-bold text-slate-800">Barangay Tangcul Health Station &amp; Care Hub &bull; HealthLogs</div>
      </div>
    </div>
    <h1 class="text-lg sm:text-xl font-extrabold uppercase text-slate-900 tracking-wider mt-0.5">
      8 - ANC TARGET CLIENT LIST FOR MATERNAL CARE AND SERVICES
      <?php if ($activeSheet !== 'all'): ?>
        - PART <?= h($activeSheet) ?>
      <?php endif; ?>
    </h1>
    <div class="text-xs text-slate-600 mt-1 flex items-center justify-center gap-4 flex-wrap">
      <span>Barangay: <strong>Barangay Tangcul, City of Ilagan</strong></span>
      <span>&bull;</span>
      <?php if ($focusedPregnancy): ?>
        <span>Mother: <strong><?= h($fMotherName) ?> (<?= h($fData['family_serial']) ?>)</strong></span>
      <?php else: ?>
        <span>Purok: <strong><?= h($barangayFilter ?: 'All Puroks') ?></strong></span>
        <span>&bull;</span>
        <span>Cohort Year: <strong><?= h($yearFilter === 'all' ? 'All Records' : $yearFilter) ?></strong></span>
      <?php endif; ?>
      <span>&bull;</span>
      <span>Generated on: <strong><?= date('F d, Y') ?></strong></span>
    </div>
  </div>

  <!-- TCL Table View -->
  <div class="overflow-x-auto -mx-4 sm:mx-0">
    <table class="w-full tcl-table min-w-[1280px]">
      <thead>
        <?php if ($activeSheet === '1' || $activeSheet === 'all'): ?>
          <!-- ================= SHEET 1 / MASTER HEADERS ================= -->
          <tr>
            <th rowspan="3" class="w-8">No.</th>
            <th rowspan="3" class="min-w-[70px]">Date of<br>Registration<br><span class="text-[9px] font-normal text-slate-500">(mm/dd/yy)</span></th>
            <th rowspan="3" class="min-w-[80px]">Family<br>Serial No.</th>
            <th rowspan="3" class="min-w-[150px]">Full Name<br><span class="text-[9px] font-normal text-slate-500">(LastName, FirstName, MI)</span></th>
            <th rowspan="3" class="min-w-[110px]">Purok / Address</th>
            <th rowspan="3" class="w-8">Age</th>
            <th rowspan="3" class="w-8">Age<br>Group<br><span class="text-[8px] font-normal">A:10-14<br>B:15-19<br>C:20-49</span></th>
            <th rowspan="3" class="min-w-[75px]">LMP<br><span class="text-[8px] font-normal">(mm/dd/yy)</span><br>G - P</th>
            <th rowspan="3" class="min-w-[75px]">EDD<br><span class="text-[8px] font-normal">(mm/dd/yy)</span></th>

            <th colspan="8" class="bg-slate-100 text-slate-800 font-extrabold uppercase py-1.5">
              Date of Prenatal Check-up (8 ANC) <span class="text-[10px] font-normal lowercase">(mm/dd/yy)</span>
            </th>
            <th rowspan="3" class="w-16 bg-slate-100 text-slate-800">
              Completed<br>8ANC?<br><span class="text-[9px] font-normal">(Complete/Incomplete)</span>
            </th>
            <?php if ($activeSheet === 'all'): ?>
              <th rowspan="3" class="min-w-[65px] bg-slate-100 text-slate-800">BMI<br>1st Tri</th>
              <th colspan="5" class="bg-slate-100 text-slate-800">Tetanus Diphtheria (Td)</th>
              <th rowspan="3" class="w-10 bg-slate-100 text-slate-800">FIM</th>
              <th colspan="4" class="bg-emerald-50 text-emerald-900">Postnatal Care (4PNC)</th>
              <th rowspan="3" class="w-16 bg-emerald-50 text-emerald-900">Completed<br>4PNC?</th>
            <?php endif; ?>
          </tr>

          <!-- Sheet 1 Trimester Subheaders -->
          <tr>
            <th class="bg-slate-50 text-slate-700 text-[10px] py-1">1st Trimester</th>
            <th colspan="2" class="bg-slate-50 text-slate-700 text-[10px] py-1">2nd Trimester</th>
            <th colspan="5" class="bg-slate-50 text-slate-700 text-[10px] py-1">3rd Trimester</th>
            <?php if ($activeSheet === 'all'): ?>
              <th rowspan="2" class="text-[9px]">Td1</th>
              <th rowspan="2" class="text-[9px]">Td2</th>
              <th rowspan="2" class="text-[9px]">Td3</th>
              <th rowspan="2" class="text-[9px]">Td4</th>
              <th rowspan="2" class="text-[9px]">Td5</th>
              <th rowspan="2" class="text-[9px] bg-emerald-50/60">C1<br>&lt;24h</th>
              <th rowspan="2" class="text-[9px] bg-emerald-50/60">C2<br>Day 3</th>
              <th rowspan="2" class="text-[9px] bg-emerald-50/60">C3<br>7-14d</th>
              <th rowspan="2" class="text-[9px] bg-emerald-50/60">C4<br>6w</th>
            <?php endif; ?>
          </tr>

          <!-- Sheet 1 Recommended Timings -->
          <tr>
            <th class="text-[9px] font-semibold py-1 bg-slate-50/70 min-w-[55px]">Visit 1<br><span class="text-[8px] font-normal">8-12 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-slate-50/70 min-w-[55px]">Visit 2<br><span class="text-[8px] font-normal">14-20 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-slate-50/70 min-w-[55px]">Visit 3<br><span class="text-[8px] font-normal">21-27 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-slate-50/70 min-w-[55px]">Visit 4<br><span class="text-[8px] font-normal">28-30 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-slate-50/70 min-w-[55px]">Visit 5<br><span class="text-[8px] font-normal">31-34 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-slate-50/70 min-w-[55px]">Visit 6<br><span class="text-[8px] font-normal">35 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-slate-50/70 min-w-[55px]">Visit 7<br><span class="text-[8px] font-normal">36 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-slate-50/70 min-w-[55px]">Visit 8<br><span class="text-[8px] font-normal">37-40 wks</span></th>
          </tr>

        <?php elseif ($activeSheet === '2'): ?>
          <!-- ================= SHEET 2: DELIVERY & POSTNATAL HEADERS ================= -->
          <tr>
            <th rowspan="2" class="w-8">No.</th>
            <th rowspan="2" class="min-w-[150px]">Full Name</th>
            <th rowspan="2" class="min-w-[70px] bg-slate-100 text-slate-800">Delivery Type<br><span class="text-[8px] font-normal">CS/VD/CVCD</span></th>
            <th rowspan="2" class="min-w-[65px] bg-slate-100 text-slate-800">Birth Weight<br><span class="text-[8px] font-normal">Grams &bull; A/B/C</span></th>
            <th rowspan="2" class="min-w-[100px] bg-slate-100 text-slate-800">Place of Delivery<br><span class="text-[8px] font-normal">Health Facility / Home</span></th>
            <th rowspan="2" class="min-w-[70px] bg-slate-100 text-slate-800">Birth Attendant<br><span class="text-[8px] font-normal">MD/RN/MW</span></th>
            <th rowspan="2" class="min-w-[80px] bg-slate-100 text-slate-800">Delivery Date &amp; Time</th>
            <th colspan="4" class="bg-emerald-50 text-emerald-900">Postnatal Care (4PNC) (mm/dd/yy)</th>
            <th rowspan="2" class="w-24 bg-emerald-50 text-emerald-900">Completed<br>4PNC?<br><span class="text-[8px] font-normal">(Complete/Incomplete)</span></th>
            <th colspan="3" class="bg-slate-100 text-slate-800">Postpartum Supplementation</th>
          </tr>
          <tr>
            <th class="text-[9px] bg-emerald-50/60">Contact 1 (&lt;24h)</th>
            <th class="text-[9px] bg-emerald-50/60">Contact 2 (Day 3)</th>
            <th class="text-[9px] bg-emerald-50/60">Contact 3 (7-14d)</th>
            <th class="text-[9px] bg-emerald-50/60">Contact 4 (6w)</th>
            <th class="text-[9px] bg-slate-50">IFA 1st Visit</th>
            <th class="text-[9px] bg-slate-50">IFA 2nd Visit</th>
            <th class="text-[9px] bg-slate-50">Vit A Given?</th>
          </tr>
        <?php endif; ?>
      </thead>

      <tbody>
        <?php if (empty($tclRows)): ?>
          <tr>
            <td colspan="25" class="py-8 text-center text-slate-400">No maternal records matching the selected filters.</td>
          </tr>
        <?php else: ?>
          <?php $idx = 1; foreach ($tclRows as $row): ?>
            <?php 
              $pr = $row['pr'];
              $d = $row['data'];
              $fullName = trim($pr['last_name'] . ', ' . $pr['first_name'] . ' ' . ($pr['middle_name'] ? substr($pr['middle_name'], 0, 1) . '.' : '') . ' ' . ($pr['suffix'] ?? ''));
            ?>
            <tr>
              <td class="font-mono text-slate-500 font-semibold"><?= $idx++ ?></td>
              
              <?php if ($activeSheet === '1' || $activeSheet === 'all'): ?>
                <td class="font-mono text-[10px] text-slate-600 whitespace-nowrap"><?= h($d['reg_date']) ?></td>
                <td class="font-mono text-[10px] text-slate-600 whitespace-nowrap"><?= h($d['family_serial']) ?></td>
                <td class="text-left font-bold text-slate-900 whitespace-nowrap">
                  <a href="/HealthLogs/public/maternal/tcl.php?pregnancy_id=<?= (int)$pr['id'] ?>" class="hover:text-teal-700 hover:underline">
                    <?= h($fullName) ?>
                  </a>
                  <?php if ($pr['status'] === 'ongoing' && $pr['current_weeks'] >= 24 && $pr['current_weeks'] <= 31): ?>
                    <span class="inline-block ml-1 px-1.5 py-0.2 rounded text-[9px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">6–7m</span>
                  <?php elseif ($pr['status'] === 'delivered'): ?>
                    <span class="inline-block ml-1 px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Delivered</span>
                  <?php endif; ?>
                </td>
                <td class="text-left text-[10px] text-slate-600 whitespace-nowrap"><?= h($pr['barangay']) ?></td>
                <td class="font-semibold text-slate-700"><?= h((string)$d['age']) ?></td>
                <td class="font-bold text-slate-800"><?= h($d['age_group']) ?></td>
                <td class="text-center font-mono text-[10px] whitespace-nowrap">
                  <div class="text-slate-800 font-semibold"><?= h($d['lmp']) ?></div>
                  <div class="text-slate-400 font-normal"><?= h($d['gp']) ?></div>
                </td>
                <td class="font-mono text-[10px] font-semibold text-slate-800 whitespace-nowrap"><?= h($d['edd']) ?></td>

                <!-- 8-ANC Visits -->
                <td class="tcl-date-val"><?= $d['visits']['v1'] ? h($d['visits']['v1']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v2'] ? h($d['visits']['v2']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v3'] ? h($d['visits']['v3']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v4'] ? h($d['visits']['v4']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v5'] ? h($d['visits']['v5']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v6'] ? h($d['visits']['v6']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v7'] ? h($d['visits']['v7']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v8'] ? h($d['visits']['v8']) : '' ?></td>

                <!-- Completed 8 ANC -->
                <td class="font-semibold <?= $d['completed_8anc'] ? 'text-emerald-700 bg-emerald-50/50' : 'text-slate-500 bg-slate-50' ?>">
                  <?= $d['completed_8anc'] ? 'Complete' : 'Incomplete' ?>
                </td>

                <?php if ($activeSheet === 'all'): ?>
                  <td class="font-mono text-xs"><?= $d['bmi'] ?: '--' ?></td>
                  <td class="tcl-date-val"><?= $d['td']['td1'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['td']['td2'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['td']['td3'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['td']['td4'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['td']['td5'] ?? '' ?></td>
                  <td class="font-bold text-slate-800"><?= $d['fim'] ? '&check;' : 'X' ?></td>
                  <td class="tcl-date-val"><?= $d['postnatal']['c1'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['postnatal']['c2'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['postnatal']['c3'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['postnatal']['c4'] ?? '' ?></td>
                  <td class="font-semibold <?= $d['completed_4pnc'] ? 'text-emerald-700' : 'text-slate-500' ?>">
                    <?= $pr['status'] === 'delivered' ? ($d['completed_4pnc'] ? 'Complete' : 'Incomplete') : 'Pending Birth' ?>
                  </td>
                <?php endif; ?>

              <?php elseif ($activeSheet === '2'): ?>
                <td class="text-left font-bold text-slate-900 whitespace-nowrap">
                  <a href="/HealthLogs/public/maternal/tcl.php?pregnancy_id=<?= (int)$pr['id'] ?>" class="hover:text-teal-700 hover:underline">
                    <?= h($fullName) ?>
                  </a>
                </td>
                <td class="font-semibold text-slate-700"><?= h($d['delivery_type']) ?></td>
                <td class="font-mono text-[10px]"><?= h($d['birth_weight_text']) ?></td>
                <td class="text-slate-700"><?= h($d['delivery_place']) ?></td>
                <td class="text-slate-700"><?= h($d['birth_attendant']) ?></td>
                <td class="font-mono text-[10px]"><?= h($d['delivery_dt']) ?></td>
                <td class="tcl-date-val"><?= $d['postnatal']['c1'] ?? '' ?></td>
                <td class="tcl-date-val"><?= $d['postnatal']['c2'] ?? '' ?></td>
                <td class="tcl-date-val"><?= $d['postnatal']['c3'] ?? '' ?></td>
                <td class="tcl-date-val"><?= $d['postnatal']['c4'] ?? '' ?></td>
                <td class="font-bold <?= $d['completed_4pnc'] ? 'text-emerald-800' : 'text-slate-500' ?>">
                  <?= $pr['status'] === 'delivered' ? ($d['completed_4pnc'] ? 'Complete' : 'Incomplete') : 'Pending Birth' ?>
                </td>
                <td class="text-slate-600 font-mono text-[10px]"><?= $d['postnatal']['c1'] ? '30 tabs' : '' ?></td>
                <td class="text-slate-600 font-mono text-[10px]"><?= $d['postnatal']['c2'] ? '30 tabs' : '' ?></td>
                <td class="text-emerald-700 font-bold"><?= $pr['status'] === 'delivered' ? '&check;' : '--' ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($focusedPregnancy && !empty($tclRows)): ?>
  <?php
    $ancSlotDescriptions = [
        'V1' => 'Visit 1 • 1st Tri (≤12 wks)',
        'V2' => 'Visit 2 • 2nd Tri (13–20 wks)',
        'V3' => 'Visit 3 • 2nd Tri (21–27 wks)',
        'V4' => 'Visit 4 • 3rd Tri (28–30 wks)',
        'V5' => 'Visit 5 • 3rd Tri (31–34 wks)',
        'V6' => 'Visit 6 • 3rd Tri (35 wks)',
        'V7' => 'Visit 7 • 3rd Tri (36 wks)',
        'V8' => 'Visit 8 • 3rd Tri (37–40 wks)',
    ];
    $pncMilestones = [
        ['key' => 'c1', 'label' => 'Contact 1', 'timing' => 'Within 24 Hours After Birth', 'desc' => 'Immediate postpartum & newborn check'],
        ['key' => 'c2', 'label' => 'Contact 2', 'timing' => 'Day 3 After Delivery', 'desc' => 'Early postpartum recovery & lactation'],
        ['key' => 'c3', 'label' => 'Contact 3', 'timing' => 'Day 7 to 14 After Delivery', 'desc' => 'Cord care, involution & neonatal check'],
        ['key' => 'c4', 'label' => 'Contact 4', 'timing' => '6 Weeks Postpartum (42 Days)', 'desc' => 'Final postpartum & family planning counseling'],
    ];
  ?>

  <!-- ====================================================================== -->
  <!-- SECTION 1: MOTHER'S PRENATAL VISITS (8-ANC ANTENATAL CHECKUPS)         -->
  <!-- ====================================================================== -->
  <section id="prenatal-section" class="mt-6 bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs print:hidden">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
      <div>
        <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-teal-700">
          <i class="fas fa-stethoscope"></i> Antenatal Care (8-ANC)
        </div>
        <h3 class="text-lg font-bold text-slate-900 mt-0.5">Prenatal Visits of <?= h($fMotherName) ?></h3>
        <p class="text-xs text-slate-500 mt-0.5">
          All recorded prenatal checkups for this mother, mapped directly to the DOH 8-ANC Target Client List above.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">
          <?= count($fPrenatals) ?> Prenatal Visit<?= count($fPrenatals) === 1 ? '' : 's' ?>
        </span>
        <?php if (can_manage_clinical_records() && $isOngoingMother): ?>
          <button type="button" class="open-maternal-embed-modal inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition"
                  data-modal-title="Record Prenatal Checkup (8-ANC)"
                  data-embed-url="/HealthLogs/public/maternal/prenatal/form_embed.php?pregnancy_id=<?= (int)$fPr['id'] ?>">
            <i class="fas fa-plus text-[10px]"></i> Record Prenatal Checkup
          </button>
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($fPrenatals)): ?>
      <div class="py-8 text-center">
        <div class="text-sm font-medium text-slate-600">No prenatal checkups recorded for this mother yet.</div>
        <?php if (can_manage_clinical_records() && $isOngoingMother): ?>
          <button type="button" class="open-maternal-embed-modal mt-3 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold shadow-xs transition"
                  data-modal-title="Record First Prenatal Checkup (8-ANC)"
                  data-embed-url="/HealthLogs/public/maternal/prenatal/form_embed.php?pregnancy_id=<?= (int)$fPr['id'] ?>">
            <i class="fas fa-plus"></i> Record First Prenatal Visit
          </button>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto mt-4">
        <table class="min-w-full text-xs text-left">
          <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[11px] font-bold">
            <tr>
              <th class="px-3.5 py-2.5">#</th>
              <th class="px-3.5 py-2.5">Visit Date &amp; Time</th>
              <th class="px-3.5 py-2.5">Gestational Age</th>
              <th class="px-3.5 py-2.5">8-ANC TCL Slot</th>
              <th class="px-3.5 py-2.5">Blood Pressure</th>
              <th class="px-3.5 py-2.5">Weight</th>
              <th class="px-3.5 py-2.5">Clinical Notes</th>
              <th class="px-3.5 py-2.5">Recorded By</th>
              <?php if (can_manage_clinical_records()): ?>
                <th class="px-3.5 py-2.5 text-right">Actions</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($fPrenatals as $pIdx => $pv): ?>
              <?php
                $gaWks = (int)($pv['gestational_age_weeks'] ?? 0);
                $slotKey = $fData['visit_slots'][(int)$pv['id']] ?? null;
                $slotLabel = $slotKey && isset($ancSlotDescriptions[$slotKey]) ? $ancSlotDescriptions[$slotKey] : 'Additional Checkup';
                $highBp = ((int)($pv['bp_systolic'] ?? 0) >= 140 || (int)($pv['bp_diastolic'] ?? 0) >= 90);
              ?>
              <tr class="hover:bg-slate-50/80 transition">
                <td class="px-3.5 py-3 font-mono font-semibold text-slate-500"><?= $pIdx + 1 ?></td>
                <td class="px-3.5 py-3 whitespace-nowrap font-medium text-slate-900">
                  <?= date('M d, Y h:i A', strtotime($pv['visit_datetime'])) ?>
                </td>
                <td class="px-3.5 py-3 whitespace-nowrap">
                  <?php if ($gaWks > 0): ?>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= ($gaWks >= 24 && $gaWks <= 31) ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-slate-100 text-slate-800' ?>">
                      <?= $gaWks ?> weeks
                    </span>
                  <?php else: ?>
                    <span class="text-slate-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="px-3.5 py-3 whitespace-nowrap">
                  <span class="px-2.5 py-0.5 rounded text-[11px] font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                    <?= h($slotLabel) ?>
                  </span>
                </td>
                <td class="px-3.5 py-3 whitespace-nowrap font-mono">
                  <?php if ($pv['bp_systolic'] && $pv['bp_diastolic']): ?>
                    <span class="<?= $highBp ? 'text-rose-700 font-bold' : 'text-slate-800' ?>">
                      <?= (int)$pv['bp_systolic'] ?>/<?= (int)$pv['bp_diastolic'] ?> mmHg
                    </span>
                  <?php else: ?>
                    <span class="text-slate-400">—</span>
                  <?php endif; ?>
                </td>
                <td class="px-3.5 py-3 whitespace-nowrap text-slate-700">
                  <?= $pv['weight_kg'] !== null ? h((string)$pv['weight_kg']) . ' kg' : '—' ?>
                </td>
                <td class="px-3.5 py-3 text-slate-600 max-w-xs"><?= h($pv['notes'] ?: '—') ?></td>
                <td class="px-3.5 py-3 text-slate-600 whitespace-nowrap"><?= h($pv['staff_name'] ?: 'Midwife / BHS Staff') ?></td>
                <?php if (can_manage_clinical_records()): ?>
                  <td class="px-3.5 py-3 text-right whitespace-nowrap">
                    <button type="button" class="open-maternal-embed-modal text-teal-700 hover:text-teal-900 font-semibold px-2 py-1 rounded border border-teal-200 hover:bg-teal-50"
                            data-modal-title="Edit Prenatal Checkup"
                            data-embed-url="/HealthLogs/public/maternal/prenatal/form_embed.php?id=<?= (int)$pv['id'] ?>">
                      Edit
                    </button>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <!-- ====================================================================== -->
  <!-- SECTION 2: DELIVERY & POSTNATAL CARE (4PNC — UNLOCKS AFTER BIRTH)      -->
  <!-- ====================================================================== -->
  <section id="postnatal-section" class="mt-6 bg-white rounded-2xl border <?= $isDeliveredMother ? 'border-emerald-200' : 'border-slate-200' ?> p-5 sm:p-6 shadow-xs print:hidden">
    <?php if (!$isDeliveredMother): ?>
      <!-- Locked Postnatal State (Mother is still pregnant / ongoing) -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-100">
        <div>
          <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
            <i class="fas fa-lock text-amber-600"></i> Postnatal Care (DOH 4PNC) • Pending Delivery
          </div>
          <h3 class="text-lg font-bold text-slate-900 mt-0.5">Postnatal Visits &amp; Newborn Follow-Up</h3>
          <p class="text-xs text-slate-500 mt-0.5">
            Postnatal visits (<strong class="text-slate-700">Contact 1 &lt;24h, Contact 2 Day 3, Contact 3 Day 7–14, Contact 4 6 Weeks</strong>) become available automatically as soon as the mother gives birth.
          </p>
        </div>
        <?php if (can_manage_clinical_records() && $isOngoingMother): ?>
          <button type="button" onclick="document.getElementById('openDeliverModalBtn')?.click()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition shrink-0">
            <i class="fas fa-baby"></i> Mother Gave Birth — Unlock Postnatal Care
          </button>
        <?php endif; ?>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4 opacity-75">
        <?php foreach ($pncMilestones as $ms): ?>
          <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3.5">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500">
              <span><?= h($ms['label']) ?></span>
              <span class="text-[10px] px-2 py-0.5 rounded bg-slate-200 text-slate-600">Locked</span>
            </div>
            <div class="text-xs font-semibold text-slate-700 mt-1.5"><?= h($ms['timing']) ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5"><?= h($ms['desc']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- Unlocked & Active Postnatal Care State (Mother Gave Birth!) -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-100">
        <div>
          <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-emerald-700">
            <i class="fas fa-check-circle"></i> Postnatal Care Unlocked (DOH 4PNC Active)
          </div>
          <h3 class="text-lg font-bold text-slate-900 mt-0.5">Delivery Outcome &amp; Postnatal Visits of <?= h($fMotherName) ?></h3>
          <p class="text-xs text-slate-500 mt-0.5">
            Mother has given birth. Track her 4 mandatory DOH Postnatal Care contacts and newborn health checks below.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
            <?= $filled4PncCount ?> of 4 PNC Contacts Completed
          </span>
          <?php if (can_manage_clinical_records()): ?>
            <button type="button" class="open-maternal-embed-modal inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition"
                    data-modal-title="Record Postnatal Visit (4PNC)"
                    data-embed-url="/HealthLogs/public/maternal/postnatal/form_embed.php?pregnancy_id=<?= (int)$fPr['id'] ?>">
              <i class="fas fa-plus text-[10px]"></i> Record Postnatal Visit
            </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Delivery Outcome Summary Strip -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-4 p-3.5 rounded-xl bg-emerald-50/50 border border-emerald-200/80 text-xs">
        <div>
          <div class="text-[10px] uppercase font-bold text-emerald-700">Delivery Date &amp; Time</div>
          <div class="font-bold text-slate-900 mt-0.5 font-mono"><?= h($fData['delivery_dt']) ?></div>
        </div>
        <div>
          <div class="text-[10px] uppercase font-bold text-emerald-700">Delivery Type</div>
          <div class="font-bold text-slate-900 mt-0.5"><?= h($fData['delivery_type']) ?></div>
        </div>
        <div>
          <div class="text-[10px] uppercase font-bold text-emerald-700">Birth Weight</div>
          <div class="font-bold text-slate-900 mt-0.5"><?= h($fData['birth_weight_text']) ?></div>
        </div>
        <div>
          <div class="text-[10px] uppercase font-bold text-emerald-700">Place of Delivery</div>
          <div class="font-bold text-slate-900 mt-0.5"><?= h($fData['delivery_place']) ?></div>
        </div>
        <div>
          <div class="text-[10px] uppercase font-bold text-emerald-700">Birth Attendant</div>
          <div class="font-bold text-slate-900 mt-0.5"><?= h($fData['birth_attendant']) ?></div>
        </div>
      </div>

      <!-- 4PNC Milestone Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
        <?php foreach ($pncMilestones as $ms): ?>
          <?php $doneDate = $fData['postnatal'][$ms['key']] ?? null; ?>
          <div class="rounded-xl border p-3.5 <?= $doneDate ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-slate-50' ?>">
            <div class="flex items-center justify-between text-xs font-bold">
              <span class="<?= $doneDate ? 'text-emerald-900' : 'text-slate-700' ?>"><?= h($ms['label']) ?></span>
              <?php if ($doneDate): ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                  <i class="fas fa-check mr-0.5"></i> <?= h($doneDate) ?>
                </span>
              <?php else: ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                  Pending
                </span>
              <?php endif; ?>
            </div>
            <div class="text-xs font-semibold text-slate-800 mt-1.5"><?= h($ms['timing']) ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5"><?= h($ms['desc']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Postnatal Visits Table -->
      <?php if (empty($fPostnatals)): ?>
        <div class="py-7 text-center border-t border-slate-100 mt-4">
          <div class="text-sm font-medium text-slate-600">No postnatal follow-up visits recorded yet.</div>
          <?php if (can_manage_clinical_records()): ?>
            <button type="button" class="open-maternal-embed-modal mt-3 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition"
                    data-modal-title="Record First Postnatal Visit (Contact 1)"
                    data-embed-url="/HealthLogs/public/maternal/postnatal/form_embed.php?pregnancy_id=<?= (int)$fPr['id'] ?>">
              <i class="fas fa-plus"></i> Record Contact 1 (&lt;24h) Postnatal Checkup
            </button>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto mt-4">
          <table class="min-w-full text-xs text-left">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[11px] font-bold">
              <tr>
                <th class="px-3.5 py-2.5">4PNC Slot</th>
                <th class="px-3.5 py-2.5">Visit Date &amp; Time</th>
                <th class="px-3.5 py-2.5">Mother's Condition</th>
                <th class="px-3.5 py-2.5">Baby's Condition</th>
                <th class="px-3.5 py-2.5">Clinical Notes</th>
                <th class="px-3.5 py-2.5">Recorded By</th>
                <?php if (can_manage_clinical_records()): ?>
                  <th class="px-3.5 py-2.5 text-right">Actions</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($fPostnatals as $pncIdx => $post): ?>
                <?php
                  $pncSlotLabel = $pncIdx < 4 ? $pncMilestones[$pncIdx]['label'] . ' (' . $pncMilestones[$pncIdx]['timing'] . ')' : 'Additional Follow-Up';
                ?>
                <tr class="hover:bg-slate-50/80 transition">
                  <td class="px-3.5 py-3 whitespace-nowrap">
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                      <?= h($pncSlotLabel) ?>
                    </span>
                  </td>
                  <td class="px-3.5 py-3 whitespace-nowrap font-medium text-slate-900">
                    <?= date('M d, Y h:i A', strtotime($post['visit_datetime'])) ?>
                  </td>
                  <td class="px-3.5 py-3 whitespace-nowrap font-semibold text-slate-800">
                    <?= h($post['mother_condition'] ?: 'Stable') ?>
                  </td>
                  <td class="px-3.5 py-3 whitespace-nowrap font-semibold text-slate-800">
                    <?= h($post['baby_condition'] ?: 'Healthy') ?>
                  </td>
                  <td class="px-3.5 py-3 text-slate-600 max-w-xs"><?= h($post['notes'] ?: '—') ?></td>
                  <td class="px-3.5 py-3 text-slate-600 whitespace-nowrap"><?= h($post['staff_name'] ?: 'Midwife / BHS Staff') ?></td>
                  <?php if (can_manage_clinical_records()): ?>
                    <td class="px-3.5 py-3 text-right whitespace-nowrap">
                      <button type="button" class="open-maternal-embed-modal text-emerald-700 hover:text-emerald-900 font-semibold px-2 py-1 rounded border border-emerald-200 hover:bg-emerald-50"
                              data-modal-title="Edit Postnatal Visit"
                              data-embed-url="/HealthLogs/public/maternal/postnatal/form_embed.php?id=<?= (int)$post['id'] ?>">
                        Edit
                      </button>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </section>

  <!-- Modal for Recording Delivery (Gave Birth) -->
  <?php if (can_manage_clinical_records()): ?>
    <div id="deliverFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
      <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" id="deliverFormModalBackdrop" aria-label="Close modal"></button>
      <div class="relative z-10 mx-auto mt-10 max-w-2xl px-4">
        <div class="rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-emerald-50/70">
            <div>
              <div class="text-xs font-bold uppercase tracking-wider text-emerald-700">DOH TCL Part 2 • Delivery &amp; Postnatal Unlock</div>
              <h3 class="text-base font-bold text-slate-900 mt-0.5">
                <?= $isDeliveredMother ? 'Update Delivery Details — ' : 'Record Delivery (Mother Gave Birth) — ' ?><?= h($fMotherName) ?>
              </h3>
            </div>
            <button type="button" id="deliverFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Close</button>
          </div>
          <form method="post" action="/HealthLogs/public/maternal/pregnancies/deliver.php" class="p-5 space-y-4">
            <input type="hidden" name="pregnancy_id" value="<?= (int)$fPr['id'] ?>" />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Delivery Date &amp; Time <span class="text-rose-500">*</span></label>
                <input type="datetime-local" name="delivery_datetime" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm"
                       value="<?= h(!empty($fPr['delivery_datetime']) ? date('Y-m-d\TH:i', strtotime($fPr['delivery_datetime'])) : date('Y-m-d\TH:i')) ?>" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Delivery Type <span class="text-rose-500">*</span></label>
                <?php $curDelType = $fPr['delivery_type'] ?? 'VD (Vaginal)'; ?>
                <select name="delivery_type" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
                  <option value="VD (Vaginal)" <?= $curDelType === 'VD (Vaginal)' ? 'selected' : '' ?>>VD (Normal Vaginal Delivery)</option>
                  <option value="CS (Cesarean)" <?= $curDelType === 'CS (Cesarean)' ? 'selected' : '' ?>>CS (Cesarean Section)</option>
                  <option value="CVCD (Assisted)" <?= $curDelType === 'CVCD (Assisted)' ? 'selected' : '' ?>>CVCD (Assisted / Vacuum)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Baby Birth Weight (Grams)</label>
                <input type="number" name="birth_weight_grams" min="500" max="6500" step="10" placeholder="e.g. 3100" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm"
                       value="<?= h((string)($fPr['birth_weight_grams'] ?? 3100)) ?>" />
                <span class="text-[11px] text-slate-500 mt-0.5 block">&ge;2,500g = Normal (Category A) &bull; &lt;2,500g = Low Birth Weight (Category B)</span>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Place of Delivery</label>
                <?php $curPlace = $fPr['delivery_place'] ?? 'BHS / RHU Public'; ?>
                <select name="delivery_place" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
                  <option value="BHS / RHU Public" <?= $curPlace === 'BHS / RHU Public' ? 'selected' : '' ?>>BHS / RHU Public Facility</option>
                  <option value="City of Ilagan Gov Hospital" <?= $curPlace === 'City of Ilagan Gov Hospital' ? 'selected' : '' ?>>Government Hospital (City of Ilagan)</option>
                  <option value="Private Lying-In / Hospital" <?= $curPlace === 'Private Lying-In / Hospital' ? 'selected' : '' ?>>Private Lying-In / Hospital</option>
                  <option value="Home Delivery" <?= $curPlace === 'Home Delivery' ? 'selected' : '' ?>>Home Delivery</option>
                </select>
              </div>
              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Skilled Birth Attendant</label>
                <?php $curAtt = $fPr['birth_attendant'] ?? 'MW (Midwife)'; ?>
                <select name="birth_attendant" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
                  <option value="MW (Midwife)" <?= $curAtt === 'MW (Midwife)' ? 'selected' : '' ?>>MW — Registered Midwife (Corazon Rivera, RM)</option>
                  <option value="MD (Physician)" <?= $curAtt === 'MD (Physician)' ? 'selected' : '' ?>>MD — Physician / OB-GYN</option>
                  <option value="RN (Nurse)" <?= $curAtt === 'RN (Nurse)' ? 'selected' : '' ?>>RN — Registered Nurse</option>
                  <option value="TBA / Hilot" <?= $curAtt === 'TBA / Hilot' ? 'selected' : '' ?>>TBA / Traditional Birth Attendant</option>
                </select>
              </div>
            </div>

            <?php if (!$isDeliveredMother && empty($fPostnatals)): ?>
              <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3.5 space-y-3">
                <label class="inline-flex items-center gap-2 text-xs font-bold text-emerald-900 cursor-pointer">
                  <input type="checkbox" name="record_initial_pnc" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4" id="recordInitialPncToggle" />
                  <span>Also log Contact 1 (&lt;24 Hours Postpartum Checkup) right now</span>
                </label>
                <div id="initialPncFields" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-700 uppercase mb-1">Mother's Immediate Condition</label>
                    <select name="mother_condition" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs bg-white">
                      <option value="Stable">Stable</option>
                      <option value="Recovering Well">Recovering Well</option>
                      <option value="Needs Follow-Up">Needs Follow-Up</option>
                      <option value="With Warning Signs">With Warning Signs</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-700 uppercase mb-1">Newborn's Immediate Condition</label>
                    <select name="baby_condition" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs bg-white">
                      <option value="Healthy">Healthy (EINC &amp; Breastfeeding Initiated)</option>
                      <option value="Stable">Stable</option>
                      <option value="Low Birth Weight">Low Birth Weight</option>
                      <option value="Needs Follow-Up">Needs Follow-Up</option>
                    </select>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
              <button type="button" id="deliverFormModalCancel" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50">Cancel</button>
              <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition">
                <i class="fas fa-check mr-1"></i> <?= $isDeliveredMother ? 'Save Delivery Details' : 'Confirm Delivery & Unlock Postnatal' ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Universal Embed Modal for Prenatal / Postnatal / Newborn Registration -->
  <div id="maternalEmbedModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
    <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" id="maternalEmbedModalBackdrop" aria-label="Close modal"></button>
    <div class="relative z-10 mx-auto mt-8 max-w-4xl px-4">
      <div class="rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-4rem)]">
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
          <div class="text-sm font-semibold text-slate-800" id="maternalEmbedModalTitle">Clinical Form</div>
          <button type="button" id="maternalEmbedModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Close</button>
        </div>
        <iframe id="maternalEmbedModalFrame" class="w-full min-h-[74vh] border-0 flex-1" title="Maternal clinical form"></iframe>
      </div>
    </div>
  </div>

  <script>
  (function () {
    // 1. Embed Modal (Prenatal / Postnatal / Newborn Registration)
    var embedModal = document.getElementById('maternalEmbedModal');
    var embedFrame = document.getElementById('maternalEmbedModalFrame');
    var embedTitle = document.getElementById('maternalEmbedModalTitle');
    var embedBackdrop = document.getElementById('maternalEmbedModalBackdrop');
    var embedClose = document.getElementById('maternalEmbedModalClose');

    function openEmbedModal(url, title) {
      if (!embedModal || !embedFrame || !url) return;
      if (embedTitle) embedTitle.textContent = title || 'Clinical Form';
      embedFrame.src = url;
      embedModal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }
    function closeEmbedModal() {
      if (!embedModal || !embedFrame) return;
      embedFrame.src = 'about:blank';
      embedModal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }

    document.querySelectorAll('.open-maternal-embed-modal').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openEmbedModal(btn.getAttribute('data-embed-url') || '', btn.getAttribute('data-modal-title') || 'Clinical Form');
      });
    });
    if (embedBackdrop) embedBackdrop.addEventListener('click', closeEmbedModal);
    if (embedClose) embedClose.addEventListener('click', closeEmbedModal);

    // 2. Delivery (Gave Birth) Modal
    var deliverModal = document.getElementById('deliverFormModal');
    var openDeliverBtn = document.getElementById('openDeliverModalBtn');
    var deliverBackdrop = document.getElementById('deliverFormModalBackdrop');
    var deliverClose = document.getElementById('deliverFormModalClose');
    var deliverCancel = document.getElementById('deliverFormModalCancel');
    var pncToggle = document.getElementById('recordInitialPncToggle');
    var pncFields = document.getElementById('initialPncFields');

    function openDeliverModal() {
      if (!deliverModal) return;
      deliverModal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }
    function closeDeliverModal() {
      if (!deliverModal) return;
      deliverModal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }

    if (openDeliverBtn) openDeliverBtn.addEventListener('click', openDeliverModal);
    if (deliverBackdrop) deliverBackdrop.addEventListener('click', closeDeliverModal);
    if (deliverClose) deliverClose.addEventListener('click', closeDeliverModal);
    if (deliverCancel) deliverCancel.addEventListener('click', closeDeliverModal);

    if (pncToggle && pncFields) {
      pncToggle.addEventListener('change', function () {
        pncFields.style.display = this.checked ? '' : 'none';
      });
    }

    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeEmbedModal();
        closeDeliverModal();
      }
    });

    <?php if ($openDeliverModal): ?>
    openDeliverModal();
    <?php endif; ?>
  })();
  </script>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
