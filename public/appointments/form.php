<?php
$pageTitle = 'Schedule Patient Appointment';
require __DIR__ . '/../partials/bootstrap.php';
require __DIR__ . '/../partials/header.php';

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
    WHERE is_active = 1 AND category IN ('immunization', 'maternal', 'family_planning')
    ORDER BY 
      CASE category
        WHEN 'immunization' THEN 1
        WHEN 'maternal' THEN 2
        WHEN 'family_planning' THEN 3
        ELSE 4
      END,
      id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$servicesByProgram = [
    'immunization' => [
        'name' => 'Immunization Program (Child Healthcare)',
        'items' => []
    ],
    'maternal' => [
        'name' => 'Maternal Health Program (Prenatal & Postnatal)',
        'items' => []
    ],
    'family_planning' => [
        'name' => 'Family Planning Program (Reproductive Health)',
        'items' => []
    ]
];

foreach ($services as $s) {
    if (isset($servicesByProgram[$s['category']])) {
        $servicesByProgram[$s['category']]['items'][] = $s;
    }
}
?>

<div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs max-w-3xl mx-auto">
  <div class="mb-5 pb-3 border-b border-slate-100 flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-slate-900">Schedule Patient Appointment</h1>
      <p class="text-xs text-slate-500 mt-0.5">Book appointments for the 3 core health programs (Immunization, Maternal Health, Family Planning).</p>
    </div>
    <a href="/HealthLogs/public/appointments/index.php" class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold transition">
      Back to Appointments
    </a>
  </div>

  <?php display_flash_messages(true, true); ?>

  <form method="post" action="/HealthLogs/public/appointments/save.php" class="space-y-4">
    <!-- Patient Selector with Search Function -->
    <div>
      <div class="flex items-center justify-between mb-1">
        <label class="block text-xs font-semibold text-slate-700 uppercase">Select Patient <span class="text-rose-500">*</span></label>
        <span id="patientMatchCount" class="text-[11px] text-slate-500 font-medium"></span>
      </div>
      <div class="relative mb-2">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
          <i class="fas fa-search text-xs"></i>
        </span>
        <input type="search" id="patientSearchInput" placeholder="Quick search patient by name, purok, or category..." 
          class="w-full pl-9 pr-8 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 bg-white" autocomplete="off" />
        <button type="button" id="clearPatientSearch" class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
          <i class="fas fa-times text-xs"></i>
        </button>
      </div>
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
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Required Health Service (3 Programs) <span class="text-rose-500">*</span></label>
      <select id="serviceSelect" name="service_id" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500">
        <option value="">-- Select Required Health Service --</option>
        <?php foreach ($servicesByProgram as $progKey => $prog): ?>
          <?php if (!empty($prog['items'])): ?>
            <optgroup label="<?= h($prog['name']) ?>">
              <?php foreach ($prog['items'] as $s): ?>
                <option value="<?= (int)$s['id'] ?>"
                        data-category="<?= h($s['category']) ?>"
                        data-description="<?= h($s['description']) ?>"
                        data-target="<?= h($s['target_classification']) ?>"
                        data-duration="<?= (int)$s['estimated_duration_minutes'] ?>">
                  <?= h($s['service_name']) ?> (~<?= (int)$s['estimated_duration_minutes'] ?>m)
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
      <div id="serviceDescription" class="mt-1 text-xs text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
        Choose a program service to view clinical protocol details and estimated duration.
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
      <a href="/HealthLogs/public/appointments/index.php" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition">
        Cancel
      </a>
      <button type="submit" class="px-5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
        <i class="fas fa-calendar-check"></i> Confirm Appointment
      </button>
    </div>
  </form>
</div>

<script>
(function() {
  const serviceSelect = document.getElementById('serviceSelect');
  const serviceDesc = document.getElementById('serviceDescription');
  const patientSelect = document.getElementById('patientSelect');
  const patientMeta = document.getElementById('patientMetaPills');
  const patientSearch = document.getElementById('patientSearchInput');
  const clearPatientSearch = document.getElementById('clearPatientSearch');
  const matchCount = document.getElementById('patientMatchCount');
  const allPatientOptions = Array.from(patientSelect.options).slice(1);

  function filterPatients() {
    if (!patientSearch) return;
    const term = patientSearch.value.trim().toLowerCase();
    if (clearPatientSearch) {
      clearPatientSearch.classList.toggle('hidden', term === '');
    }

    let visibleCount = 0;
    let firstVisibleOpt = null;

    allPatientOptions.forEach(opt => {
      const text = (opt.textContent + ' ' + (opt.getAttribute('data-purok') || '') + ' ' + (opt.getAttribute('data-classification') || '')).toLowerCase();
      const matches = (term === '' || text.includes(term));
      opt.hidden = !matches;
      opt.style.display = matches ? '' : 'none';
      if (matches) {
        visibleCount++;
        if (!firstVisibleOpt) firstVisibleOpt = opt;
      }
    });

    if (matchCount) {
      if (term === '') {
        matchCount.textContent = allPatientOptions.length + ' patients';
      } else {
        matchCount.textContent = visibleCount + ' match' + (visibleCount === 1 ? '' : 'es') + ' found';
      }
    }

    return { visibleCount, firstVisibleOpt };
  }

  if (patientSearch) {
    patientSearch.addEventListener('input', filterPatients);
    patientSearch.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        const res = filterPatients();
        if (res && res.firstVisibleOpt) {
          patientSelect.value = res.firstVisibleOpt.value;
          updatePatientMeta();
        }
      }
    });
  }

  if (clearPatientSearch) {
    clearPatientSearch.addEventListener('click', function() {
      if (patientSearch) {
        patientSearch.value = '';
        filterPatients();
        patientSearch.focus();
      }
    });
  }

  function updateServiceDesc() {
    const opt = serviceSelect.options[serviceSelect.selectedIndex];
    if (opt && opt.value) {
      const desc = opt.getAttribute('data-description');
      const target = opt.getAttribute('data-target');
      serviceDesc.innerHTML = '<strong>Protocol:</strong> ' + desc + '<br><span class="text-teal-700">Target Priority: ' + target + '</span>';
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
  filterPatients();
})();
</script>
