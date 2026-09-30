<?php
require __DIR__ . '/../partials/bootstrap.php';

$patientId = (int)($_GET['patient_id'] ?? 0);
$patients = $pdo->query("
    SELECT id, first_name, last_name, middle_name, birth_date, sex, barangay, contact_no, classification, is_4ps, is_pwd
    FROM patients
    WHERE status = 'active'
    ORDER BY last_name ASC, first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$services = $pdo->query("
    SELECT id, service_code, service_name, category, description, target_classification, estimated_duration_minutes
    FROM health_services
    WHERE is_active = 1
    ORDER BY service_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Schedule Appointment - HealthLogs</title>
  <link rel="stylesheet" href="/HealthLogs/public/assets/css/fontawesome.min.css">
  <script src="/HealthLogs/public/assets/js/tailwind.js"></script>
</head>
<body class="bg-slate-50 p-4 font-sans text-slate-900">
  <?php display_flash_messages(true, true); ?>

  <form method="post" action="/HealthLogs/public/appointments/save.php" target="_parent" class="space-y-4 bg-white p-5 rounded-xl border border-slate-200">
    <!-- Patient Selector -->
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Select Patient <span class="text-rose-500">*</span></label>
      <select id="patientSelect" name="patient_id" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500">
        <option value="">-- Choose a patient from registry --</option>
        <?php foreach ($patients as $p): ?>
          <?php
            $pSelected = ($patientId === (int)$p['id']) ? 'selected' : '';
            $ageStr = PatientClassifier::formatAge($p['birth_date']);
            $classLabel = ucfirst(str_replace('_', ' ', $p['classification'] ?? 'adult'));
          ?>
          <option value="<?= (int)$p['id'] ?>" <?= $pSelected ?>
                  data-classification="<?= h($p['classification']) ?>"
                  data-age="<?= h($ageStr) ?>"
                  data-purok="<?= h($p['barangay']) ?>"
                  data-contact="<?= h($p['contact_no'] ?: 'None') ?>">
            <?= h($p['last_name'] . ', ' . $p['first_name'] . ($p['middle_name'] ? ' ' . $p['middle_name'] : '')) ?> 
            (<?= $classLabel ?> • <?= $ageStr ?> • <?= h($p['barangay']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <div id="patientMetaPills" class="mt-2 flex flex-wrap gap-2 text-xs"></div>
    </div>

    <!-- Health Service Required -->
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Required Health Service <span class="text-rose-500">*</span></label>
      <select id="serviceSelect" name="service_id" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500">
        <option value="">-- Select Health Service --</option>
        <?php foreach ($services as $s): ?>
          <option value="<?= (int)$s['id'] ?>"
                  data-category="<?= h($s['category']) ?>"
                  data-description="<?= h($s['description']) ?>"
                  data-target="<?= h($s['target_classification']) ?>"
                  data-duration="<?= (int)$s['estimated_duration_minutes'] ?>">
            <?= h($s['service_name']) ?> (<?= ucfirst(h($s['category'])) ?> • ~<?= (int)$s['estimated_duration_minutes'] ?>m)
          </option>
        <?php endforeach; ?>
      </select>
      <div id="serviceDescription" class="mt-1 text-xs text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
        Choose a service to view standard clinical protocols and estimated visit duration.
      </div>
    </div>

    <!-- Date & Time -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Appointment Date <span class="text-rose-500">*</span></label>
        <input type="date" name="appointment_date" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Appointment Time <span class="text-rose-500">*</span></label>
        <input type="time" name="appointment_time" required value="08:30" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" />
      </div>
    </div>

    <!-- Assigned Personnel -->
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Assigned Health Personnel</label>
      <input name="assigned_personnel" value="Barangay Health Worker" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" placeholder="e.g. BHW Elena Bautista, Midwife Corazon Rivera, Nurse Maricar Ramos" />
    </div>

    <!-- Reason / Symptoms / Clinical Notes -->
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Reason for Visit / Clinical Symptoms</label>
      <textarea name="reason" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" placeholder="e.g. Pentavalent 3rd dose vaccine follow-up, monthly BP check and maintenance refill, prenatal 24w checkup..."></textarea>
    </div>

    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
      <button type="submit" class="px-5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
        <i class="fas fa-calendar-check"></i> Book Appointment
      </button>
    </div>
  </form>

  <script>
  (function() {
    const serviceSelect = document.getElementById('serviceSelect');
    const serviceDesc = document.getElementById('serviceDescription');
    const patientSelect = document.getElementById('patientSelect');
    const patientMeta = document.getElementById('patientMetaPills');

    function updateServiceDesc() {
      const opt = serviceSelect.options[serviceSelect.selectedIndex];
      if (opt && opt.value) {
        const desc = opt.getAttribute('data-description');
        const target = opt.getAttribute('data-target');
        serviceDesc.innerHTML = '<strong>Protocol:</strong> ' + desc + '<br><span class="text-teal-700">Target: ' + target + '</span>';
      } else {
        serviceDesc.textContent = 'Choose a service to view standard clinical protocols and estimated visit duration.';
      }
    }

    function updatePatientMeta() {
      const opt = patientSelect.options[patientSelect.selectedIndex];
      if (opt && opt.value) {
        const cls = opt.getAttribute('data-classification');
        const age = opt.getAttribute('data-age');
        const prk = opt.getAttribute('data-purok');
        const phn = opt.getAttribute('data-contact');
        patientMeta.innerHTML = 
          '<span class="px-2 py-0.5 rounded-full bg-teal-50 text-teal-800 border border-teal-200 font-semibold">' + cls.toUpperCase() + '</span>' +
          '<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-medium">' + age + '</span>' +
          '<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-medium">' + prk + '</span>' +
          '<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-medium">Contact: ' + phn + '</span>';
      } else {
        patientMeta.innerHTML = '';
      }
    }

    serviceSelect.addEventListener('change', updateServiceDesc);
    patientSelect.addEventListener('change', updatePatientMeta);
    updateServiceDesc();
    updatePatientMeta();
  })();
  </script>
</body>
</html>
