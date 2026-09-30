<?php
/**
 * Patient form fields (shared by form.php and form_embed.php).
 * Expects: $patient (array|null), form tag opened/closed by caller.
 */
$classifications = PatientClassifier::getClassifications();
$currentClass = old('classification', $patient['classification'] ?? '');
$currentBirthDate = old('birth_date', $patient['birth_date'] ?? '');
$currentSex = old('sex', $patient['sex'] ?? 'male');
$isPwd = (bool)old('is_pwd', $patient['is_pwd'] ?? 0);
$is4ps = (bool)old('is_4ps', $patient['is_4ps'] ?? 0);
$currentCivil = old('civil_status', $patient['civil_status'] ?? 'single');
$currentPhilhealthCat = old('philhealth_category', $patient['philhealth_category'] ?? 'non_member');
?>
    <?php if ($patient): ?>
      <input type="hidden" name="id" value="<?= (int)$patient['id'] ?>" />
    <?php endif; ?>

    <!-- Personal Demographics -->
    <div class="md:col-span-2 pb-2 border-b border-slate-200">
      <div class="text-sm font-bold text-slate-800 flex items-center gap-2">
        <i class="fas fa-id-card text-teal-600"></i> Primary Identification & Demographics
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">First Name <span class="text-rose-500">*</span></label>
      <input name="first_name" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500" value="<?= h(old('first_name', $patient['first_name'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Last Name <span class="text-rose-500">*</span></label>
      <input name="last_name" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500" value="<?= h(old('last_name', $patient['last_name'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Middle Name</label>
      <input name="middle_name" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= h(old('middle_name', $patient['middle_name'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Suffix (e.g. Jr., III)</label>
      <input name="suffix" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= h(old('suffix', $patient['suffix'] ?? '')) ?>" />
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Biological Sex <span class="text-rose-500">*</span></label>
      <select id="patientSexSelect" name="sex" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
        <option value="male" <?= $currentSex === 'male' ? 'selected' : '' ?>>Male</option>
        <option value="female" <?= $currentSex === 'female' ? 'selected' : '' ?>>Female</option>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Birth Date <span class="text-rose-500">*</span></label>
      <input id="patientBirthDate" type="date" name="birth_date" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= h($currentBirthDate) ?>" />
      <span id="calculatedAgePreview" class="text-[11px] text-teal-700 font-medium mt-1 block">
        <?= $currentBirthDate ? 'Calculated Age: ' . PatientClassifier::formatAge($currentBirthDate) : 'Enter birth date to auto-calculate age & classification' ?>
      </span>
    </div>

    <!-- Patient Classification & Priority Groups (The Core Feature) -->
    <div class="md:col-span-2 mt-2 pt-3 border-t border-slate-200">
      <div class="flex items-center justify-between">
        <div class="text-sm font-bold text-slate-800 flex items-center gap-2">
          <i class="fas fa-layer-group text-teal-600"></i> Patient Classification & Priority Groups
        </div>
        <span class="text-[11px] text-slate-500">Barangay Rural Health Unit Service Targeting</span>
      </div>
      <p class="text-xs text-slate-500 mt-0.5">Ensure specific patient categories—including babies, pregnant women, and senior citizens—receive targeted healthcare services.</p>
    </div>

    <div class="md:col-span-2">
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Target Patient Classification <span class="text-rose-500">*</span></label>
      <select id="patientClassificationSelect" name="classification" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500">
        <option value="">-- Select Patient Classification --</option>
        <?php foreach ($classifications as $key => $c): ?>
          <?php $selected = ($currentClass === $key) ? 'selected' : ''; ?>
          <option value="<?= $key ?>" <?= $selected ?>>
            <?= h($c['label']) ?> (<?= h($c['category']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <div id="classificationDescription" class="mt-1 text-xs text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
        <?= $currentClass && isset($classifications[$currentClass]) ? h($classifications[$currentClass]['description']) : 'Selecting a classification automatically determines routine immunization eligibility, maternal protocols, and priority queues.' ?>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Civil Status</label>
      <select name="civil_status" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
        <option value="child" <?= $currentCivil === 'child' ? 'selected' : '' ?>>Child / Minor</option>
        <option value="single" <?= $currentCivil === 'single' ? 'selected' : '' ?>>Single</option>
        <option value="married" <?= $currentCivil === 'married' ? 'selected' : '' ?>>Married</option>
        <option value="widowed" <?= $currentCivil === 'widowed' ? 'selected' : '' ?>>Widowed</option>
        <option value="separated" <?= $currentCivil === 'separated' ? 'selected' : '' ?>>Separated</option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Priority Group Flags</label>
      <div class="flex flex-wrap gap-4 mt-2">
        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
          <input type="checkbox" name="is_4ps" value="1" <?= $is4ps ? 'checked' : '' ?> class="rounded text-teal-600 focus:ring-teal-500 w-4 h-4" />
          <span>4Ps / Indigent Beneficiary</span>
        </label>
        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
          <input type="checkbox" name="is_pwd" value="1" <?= $isPwd ? 'checked' : '' ?> class="rounded text-teal-600 focus:ring-teal-500 w-4 h-4" />
          <span>Person with Disability (PWD)</span>
        </label>
      </div>
    </div>

    <!-- Location & Contact -->
    <div class="md:col-span-2 mt-2 pt-3 border-t border-slate-200">
      <div class="text-sm font-bold text-slate-800 flex items-center gap-2">
        <i class="fas fa-location-dot text-teal-600"></i> Location & Contact Details
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Purok <span class="text-xs text-teal-700 font-semibold">(Barangay Tangcul)</span> <span class="text-rose-500">*</span></label>
      <?php 
        $currentPurok = old('barangay', $patient['barangay'] ?? 'Purok 1'); 
        $stdPuroks = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7'];
      ?>
      <select name="barangay" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
        <?php foreach ($stdPuroks as $prk): ?>
          <option value="<?= $prk ?>" <?= $currentPurok === $prk ? 'selected' : '' ?>><?= $prk ?></option>
        <?php endforeach; ?>
        <?php if ($currentPurok !== '' && !in_array($currentPurok, $stdPuroks, true)): ?>
          <option value="<?= h($currentPurok) ?>" selected><?= h($currentPurok) ?></option>
        <?php endif; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Address Details (House No. / Street)</label>
      <input name="address_line" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" placeholder="e.g. Near Chapel, Purok 2" value="<?= h(old('address_line', $patient['address_line'] ?? '')) ?>" />
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Contact Number (SMS Notifications)</label>
      <input name="contact_no" placeholder="09XXXXXXXXX" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= h(old('contact_no', $patient['contact_no'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Email Address (Optional)</label>
      <input name="email" type="email" placeholder="patient@example.com" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= h(old('email', $patient['email'] ?? '')) ?>" />
    </div>

    <!-- Health Insurance & Emergency Contact -->
    <div class="md:col-span-2 mt-2 pt-3 border-t border-slate-200">
      <div class="text-sm font-bold text-slate-800 flex items-center gap-2">
        <i class="fas fa-heart-pulse text-teal-600"></i> PhilHealth & Emergency Contact
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">PhilHealth Identification No. (PIN)</label>
      <input name="philhealth_no" placeholder="XX-XXXXXXXXX-X" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= h(old('philhealth_no', $patient['philhealth_no'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">PhilHealth Membership Category</label>
      <select name="philhealth_category" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
        <option value="non_member" <?= $currentPhilhealthCat === 'non_member' ? 'selected' : '' ?>>Non-Member / Unenrolled</option>
        <option value="indigent_4ps" <?= $currentPhilhealthCat === 'indigent_4ps' ? 'selected' : '' ?>>Indigent / 4Ps Sponsored</option>
        <option value="senior_citizen" <?= $currentPhilhealthCat === 'senior_citizen' ? 'selected' : '' ?>>Senior Citizen (RA 10645)</option>
        <option value="formal_economy" <?= $currentPhilhealthCat === 'formal_economy' ? 'selected' : '' ?>>Formal Economy (Employed)</option>
        <option value="informal_economy" <?= $currentPhilhealthCat === 'informal_economy' ? 'selected' : '' ?>>Informal Economy (Self-employed)</option>
        <option value="lifetime_member" <?= $currentPhilhealthCat === 'lifetime_member' ? 'selected' : '' ?>>Lifetime Member</option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Emergency Contact / Guardian Name</label>
      <input name="emergency_contact_name" placeholder="e.g. Maria Santos (Mother / Spouse)" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= h(old('emergency_contact_name', $patient['emergency_contact_name'] ?? '')) ?>" />
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Emergency Contact Phone</label>
      <input name="emergency_contact_phone" placeholder="09XXXXXXXXX" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= h(old('emergency_contact_phone', $patient['emergency_contact_phone'] ?? '')) ?>" />
    </div>

    <!-- Clinical Details -->
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Blood Type</label>
      <?php $currBlood = strtoupper((string)old('blood_type', $patient['blood_type'] ?? '')); ?>
      <select name="blood_type" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
        <option value="">Unknown / Not specified</option>
        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bt): ?>
          <option value="<?= $bt ?>" <?= $currBlood === $bt ? 'selected' : '' ?>><?= $bt ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Record Status</label>
      <?php $status = old('status', $patient['status'] ?? 'active'); ?>
      <select name="status" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        <option value="deceased" <?= $status === 'deceased' ? 'selected' : '' ?>>Deceased</option>
      </select>
    </div>

    <div class="md:col-span-2 mt-2 border-t border-slate-200 pt-4">
      <div class="text-sm font-medium text-slate-700">Initial Clinical Condition / Health Observation (Optional)</div>
      <p class="text-xs text-slate-500 mt-1">Specify any initial medical condition, chronic disease, or health observation.</p>
    </div>
    <div class="md:col-span-2">
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Initial Condition</label>
      <input name="condition_name" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" placeholder="e.g. Hypertension, Type 2 Diabetes, Asthma, Allergy, etc. (Optional)" value="<?= h(old('condition_name', '')) ?>" />
    </div>

<script>
(function() {
  const birthInput = document.getElementById('patientBirthDate');
  const classSelect = document.getElementById('patientClassificationSelect');
  const agePreview = document.getElementById('calculatedAgePreview');
  const descDiv = document.getElementById('classificationDescription');
  const sexSelect = document.getElementById('patientSexSelect');

  const classDescriptions = <?= json_encode(array_map(function($c) { return $c['description']; }, $classifications)) ?>;

  if (classSelect && descDiv) {
    classSelect.addEventListener('change', function() {
      const val = this.value;
      if (classDescriptions[val]) {
        descDiv.textContent = classDescriptions[val];
      }
    });
  }

  if (birthInput && classSelect) {
    birthInput.addEventListener('change', function() {
      const dobVal = this.value;
      if (!dobVal) return;
      const dob = new Date(dobVal);
      const today = new Date();
      if (isNaN(dob.getTime())) return;

      let ageYears = today.getFullYear() - dob.getFullYear();
      let m = today.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        ageYears--;
      }
      let totalMonths = (today.getFullYear() - dob.getFullYear()) * 12 + (today.getMonth() - dob.getMonth());
      if (today.getDate() < dob.getDate()) totalMonths--;

      let ageStr = '';
      if (totalMonths < 12) {
        ageStr = totalMonths + ' mos old (Baby / Infant)';
      } else if (ageYears < 5) {
        ageStr = ageYears + ' yrs old (Under-5 Child)';
      } else {
        ageStr = ageYears + ' yrs old';
      }
      if (agePreview) {
        agePreview.textContent = 'Calculated Age: ' + ageStr;
      }

      // Auto-suggest classification if user has not explicitly locked it
      if (!classSelect.value || classSelect.value === 'adult') {
        if (totalMonths < 12) {
          classSelect.value = 'infant';
        } else if (ageYears < 5) {
          classSelect.value = 'under_five';
        } else if (ageYears < 10) {
          classSelect.value = 'school_age';
        } else if (ageYears < 20) {
          classSelect.value = 'adolescent';
        } else if (ageYears >= 60) {
          classSelect.value = 'senior';
        } else {
          classSelect.value = 'adult';
        }
        if (classDescriptions[classSelect.value] && descDiv) {
          descDiv.textContent = classDescriptions[classSelect.value];
        }
      }
    });
  }
})();
</script>
