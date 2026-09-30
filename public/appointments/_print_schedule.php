<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Daily Patient Appointments Manifest - HealthLogs</title>
  <link rel="icon" type="image/jpeg" href="/HealthLogs/public/assets/images/logo.jpeg">
  <style>
    @page {
      size: landscape;
      margin: 12mm;
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
      padding-bottom: 8px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .header-center {
      text-align: center;
      flex: 1;
      padding: 0 15px;
    }
    .rep-title { font-size: 9px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 600; }
    .agency-title { font-size: 10px; text-transform: uppercase; color: #334155; font-weight: 600; margin-top: 1px; }
    .hub-title { font-size: 14px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; margin-top: 1px; }
    .sys-title { font-size: 10px; color: #0f766e; font-weight: 700; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 10px;
    }
    th {
      background-color: #f1f5f9;
      color: #334155;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 8.5px;
      letter-spacing: 0.5px;
      border: 1px solid #cbd5e1;
      padding: 6px;
      text-align: left;
    }
    td {
      border: 1px solid #e2e8f0;
      padding: 5px 6px;
      color: #1e293b;
    }
    .signatory-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 20px;
      margin-top: 25px;
      page-break-inside: avoid;
    }
    .sig-box {
      border-top: 1px solid #0f172a;
      padding-top: 6px;
      text-align: center;
    }
    .sig-label { font-size: 9px; color: #64748b; text-transform: uppercase; }
    .sig-name { font-weight: 700; font-size: 11px; color: #0f172a; margin-top: 2px; }
    .sig-role { font-size: 9.5px; color: #334155; }
    .sig-date { font-size: 9px; color: #64748b; margin-top: 2px; }
  </style>
</head>
<body>
  <div class="official-header">
    <div style="width: 45px; height: 45px; border-radius: 6px; overflow: hidden; border: 1px solid #cbd5e1;">
      <img src="/HealthLogs/public/assets/images/logo.jpeg" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
    </div>
    <div class="header-center">
      <div class="rep-title">Republic of the Philippines • City Health Office • City of Ilagan</div>
      <div class="hub-title">Barangay Tangcul Rural Health Station</div>
      <div class="sys-title">Daily Patient Appointment & Service Manifest</div>
    </div>
    <div style="width: 45px; text-align: right; font-size: 10px; color: #64748b;">
      <?= date('M d, Y') ?>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Code</th>
        <th>Date & Time</th>
        <th>Patient Name</th>
        <th>Age & Classification</th>
        <th>Sex</th>
        <th>Purok</th>
        <th>Service Required</th>
        <th>Status</th>
        <th>Assigned Health Worker</th>
        <th>Reason / Clinical Notes</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($printRows)): ?>
        <tr><td colspan="10" style="text-align: center; color: #64748b; padding: 10px;">No appointments matching criteria.</td></tr>
      <?php else: ?>
        <?php foreach ($printRows as $r): ?>
          <tr>
            <td style="font-family: monospace; font-weight: 700;"><?= h($r['appointment_code']) ?></td>
            <td style="white-space: nowrap;"><?= date('M d, Y', strtotime($r['appointment_date'])) ?> <?= date('h:i A', strtotime($r['appointment_time'])) ?></td>
            <td style="font-weight: 700;"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td><?= PatientClassifier::formatAge($r['birth_date']) ?> (<?= ucfirst(str_replace('_', ' ', $r['classification'])) ?>)</td>
            <td style="text-transform: capitalize;"><?= h($r['sex']) ?></td>
            <td><?= h($r['barangay']) ?></td>
            <td style="font-weight: 600; color: #0f766e;"><?= h($r['service_name']) ?></td>
            <td style="text-transform: capitalize; font-weight: 700;"><?= h($r['status']) ?></td>
            <td><?= h($r['assigned_personnel']) ?></td>
            <td><?= h($r['clinical_notes'] ?: $r['reason'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="signatory-grid">
    <div class="sig-box">
      <div class="sig-label">Prepared by:</div>
      <div class="sig-name"><?= h($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Barangay Health Worker') ?></div>
      <div class="sig-role"><?= ($_SESSION['role'] ?? '') === 'admin' ? 'Administrator' : 'Barangay Health Worker' ?></div>
      <div class="sig-date">Date: <?= date('M d, Y') ?></div>
    </div>
    <div class="sig-box">
      <div class="sig-label">Verified by:</div>
      <div class="sig-name">___________________________</div>
      <div class="sig-role">Supervising Public Health Nurse</div>
      <div class="sig-date">Date: ____________________</div>
    </div>
    <div class="sig-box">
      <div class="sig-label">Attending / Noted by:</div>
      <div class="sig-name">___________________________</div>
      <div class="sig-role">Municipal Health Officer</div>
      <div class="sig-date">Date: ____________________</div>
    </div>
  </div>

  <script>
    window.addEventListener('load', function() {
      setTimeout(function() { window.print(); }, 300);
    });
  </script>
</body>
</html>
