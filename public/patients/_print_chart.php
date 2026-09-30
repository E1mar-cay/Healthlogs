<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Official Patient Clinical Chart - <?= h($fullName) ?> - HealthLogs</title>
  <link rel="icon" type="image/jpeg" href="/HealthLogs/public/assets/images/logo.jpeg">
  <style>
    @page {
      size: auto;
      margin: 14mm 12mm 14mm 12mm;
    }
    body {
      font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #0f172a;
      margin: 0;
      padding: 10px;
      font-size: 11px;
      line-height: 1.45;
    }
    .official-header {
      border-bottom: 2px solid #0f172a;
      padding-bottom: 10px;
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
    .rep-title { font-size: 9.5px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 600; }
    .agency-title { font-size: 10.5px; text-transform: uppercase; color: #334155; font-weight: 600; margin-top: 1px; }
    .hub-title { font-size: 15px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; margin-top: 2px; }
    .sys-title { font-size: 10.5px; color: #0f766e; font-weight: 700; margin-top: 1px; }
    
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
    .section-title {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #0f766e;
      border-bottom: 1.5px solid #0f766e;
      padding-bottom: 3px;
      margin-top: 14px;
      margin-bottom: 8px;
    }
    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }
    .grid-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 10px;
    }
    .data-card {
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 8px 10px;
      background: #fafafa;
    }
    .data-label {
      font-size: 9px;
      text-transform: uppercase;
      font-weight: 700;
      color: #64748b;
    }
    .data-val {
      font-size: 11px;
      font-weight: 600;
      color: #0f172a;
      margin-top: 1px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 6px;
      margin-bottom: 10px;
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
      padding: 5px 6px;
      text-align: left;
    }
    td {
      border: 1px solid #e2e8f0;
      padding: 4px 6px;
      color: #1e293b;
    }
    .signatory-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 15px;
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
    .watermark-footer {
      margin-top: 18px;
      text-align: center;
      font-size: 8.5px;
      color: #94a3b8;
      border-top: 1px dashed #cbd5e1;
      padding-top: 6px;
    }
  </style>
</head>
<body>
  <div class="official-header">
    <div style="width: 55px; height: 55px; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1;">
      <img src="/HealthLogs/public/assets/images/logo.jpeg" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
    </div>
    <div class="header-center">
      <div class="rep-title">Republic of the Philippines • Province of Isabela</div>
      <div class="agency-title">City Health Office • City of Ilagan</div>
      <div class="hub-title">Barangay Tangcul Rural Health Center</div>
      <div class="sys-title">HealthLogs Certified Clinical Patient Summary Record</div>
    </div>
    <div style="width: 55px;"></div>
  </div>

  <div class="doc-meta-box">
    <div>
      <div class="doc-title">Master Patient Clinical Chart</div>
      <div style="color: #64748b; font-size: 10px; margin-top: 2px;">Patient ID: <strong>#<?= (int)$patient['id'] ?></strong> • PhilHealth: <strong><?= h($patient['philhealth_no'] ?: 'None') ?></strong></div>
    </div>
    <div style="text-align: right;">
      <div>Generated: <strong><?= date('F j, Y, h:i A') ?></strong></div>
      <div style="color: #0f766e; font-weight: 700; font-size: 10px;">Classification: <?= strtoupper(str_replace('_', ' ', $patient['classification'] ?? 'adult')) ?></div>
    </div>
  </div>

  <!-- Demographics -->
  <div class="section-title">I. Patient Identification & Classification</div>
  <div class="grid-3">
    <div class="data-card">
      <div class="data-label">Full Name</div>
      <div class="data-val"><?= h($fullName) ?></div>
    </div>
    <div class="data-card">
      <div class="data-label">Exact Age / Birth Date</div>
      <div class="data-val"><?= PatientClassifier::formatAge($patient['birth_date']) ?> (<?= date('M d, Y', strtotime($patient['birth_date'])) ?>)</div>
    </div>
    <div class="data-card">
      <div class="data-label">Sex / Blood Type</div>
      <div class="data-val"><?= ucfirst(h($patient['sex'])) ?> • Blood: <?= h($patient['blood_type'] ?: 'Unknown') ?></div>
    </div>
    <div class="data-card">
      <div class="data-label">Address & Purok</div>
      <div class="data-val"><?= h($patient['barangay']) ?> (Brgy. Tangcul)</div>
    </div>
    <div class="data-card">
      <div class="data-label">Classification / Priority</div>
      <div class="data-val">
        <?= ucfirst(str_replace('_', ' ', h($patient['classification'] ?? 'adult'))) ?>
        <?= !empty($patient['is_4ps']) ? ' [4Ps Indigent]' : '' ?>
        <?= !empty($patient['is_pwd']) ? ' [PWD]' : '' ?>
      </div>
    </div>
    <div class="data-card">
      <div class="data-label">Emergency Contact</div>
      <div class="data-val"><?= h($patient['emergency_contact_name'] ?: 'None') ?> (<?= h($patient['emergency_contact_phone'] ?: 'No phone') ?>)</div>
    </div>
  </div>

  <!-- Medical History -->
  <div class="section-title">II. Allergies & Chronic Conditions</div>
  <div class="grid-2">
    <div>
      <div style="font-weight: 700; color: #dc2626; font-size: 10px; margin-bottom: 2px;">Documented Allergies:</div>
      <?php if (empty($allergies)): ?>
        <div style="color: #64748b; font-style: italic;">No known allergies documented.</div>
      <?php else: ?>
        <ul style="margin: 0; padding-left: 15px;">
          <?php foreach ($allergies as $a): ?>
            <li><strong><?= h($a['allergen']) ?></strong> - <?= h($a['reaction']) ?> (Noted: <?= date('M d, Y', strtotime($a['noted_on'])) ?>)</li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div>
      <div style="font-weight: 700; color: #0f766e; font-size: 10px; margin-bottom: 2px;">Diagnosed Conditions:</div>
      <?php if (empty($conditions)): ?>
        <div style="color: #64748b; font-style: italic;">No chronic conditions documented.</div>
      <?php else: ?>
        <ul style="margin: 0; padding-left: 15px;">
          <?php foreach ($conditions as $c): ?>
            <li><strong><?= h($c['condition_name']) ?></strong> (<?= ucfirst(h($c['status'])) ?>) - <?= h($c['notes']) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- Consultations & Services Rendered -->
  <div class="section-title">III. Recent Consultations & Services Rendered</div>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Service / Encounter Type</th>
        <th>Clinical Findings & Management Notes</th>
        <th>Healthcare Provider</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($allEncounters)): ?>
        <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 8px;">No consultation records logged.</td></tr>
      <?php else: ?>
        <?php foreach (array_slice($allEncounters, 0, 10) as $enc): ?>
          <tr>
            <td style="white-space: nowrap; font-weight: 600;"><?= date('M d, Y', strtotime($enc['date'])) ?></td>
            <td style="font-weight: 700; color: #0f766e;"><?= h($enc['type']) ?>: <?= h($enc['title']) ?></td>
            <td><?= h($enc['details']) ?></td>
            <td style="white-space: nowrap;"><?= h($enc['staff']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Immunizations -->
  <div class="section-title">IV. Immunization Administration Record</div>
  <table>
    <thead>
      <tr>
        <th>Vaccine</th>
        <th>Dose</th>
        <th>Date Given</th>
        <th>Lot No</th>
        <th>Administered By</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($immunizations)): ?>
        <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 8px;">No vaccine records logged.</td></tr>
      <?php else: ?>
        <?php foreach ($immunizations as $imm): ?>
          <tr>
            <td style="font-weight: 700;"><?= h($imm['vaccine_name']) ?> (<?= h($imm['vaccine_code']) ?>)</td>
            <td style="text-align: center;">Dose <?= (int)$imm['dose_no'] ?></td>
            <td><?= date('M d, Y', strtotime($imm['administered_on'])) ?></td>
            <td style="font-family: monospace;"><?= h($imm['lot_no'] ?: '—') ?></td>
            <td><?= h($imm['administered_by_name'] ?? 'Health Center Staff') ?></td>
            <td><?= h($imm['notes'] ?: 'Completed') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Signatories -->
  <div class="signatory-grid">
    <div class="sig-box">
      <div class="sig-label">Prepared / Recorded by:</div>
      <div class="sig-name"><?= h($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Barangay Health Worker') ?></div>
      <div class="sig-role"><?= ($_SESSION['role'] ?? '') === 'admin' ? 'Administrator' : 'Barangay Health Worker' ?></div>
      <div class="sig-date">Date: <?= date('M d, Y') ?></div>
    </div>
    <div class="sig-box">
      <div class="sig-label">Verified by:</div>
      <div class="sig-name">___________________________</div>
      <div class="sig-role">Supervising Public Health Nurse, RN</div>
      <div class="sig-date">Date: ____________________</div>
    </div>
    <div class="sig-box">
      <div class="sig-label">Approved by:</div>
      <div class="sig-name">___________________________</div>
      <div class="sig-role">Municipal / City Health Officer, MD</div>
      <div class="sig-date">Date: ____________________</div>
    </div>
  </div>

  <div class="watermark-footer">
    Certified Official Medical Record • Barangay Tangcul Rural Health Station, City of Ilagan • Timestamp: <?= date('Y-m-d H:i:s') ?>
  </div>

  <script>
    window.addEventListener('load', function() {
      setTimeout(function() { window.print(); }, 300);
    });
  </script>
</body>
</html>
