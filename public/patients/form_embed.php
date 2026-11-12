<?php
/**
 * Patient form inside modal iframe (target="_top" submits to top window).
 */
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$patient = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$id]);
    $patient = $stmt->fetch();
}

$title = $patient ? 'Edit Patient' : 'New Patient';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <base target="_top" />
  <title><?= h($title) ?></title>
  <script src="/HealthLogs/public/assets/js/tailwind.js"></script>
  <script src="/HealthLogs/public/assets/js/sweetalert2.all.min.js"></script>
  <link rel="stylesheet" href="/HealthLogs/public/assets/css/fontawesome.min.css">
  <link rel="stylesheet" href="/HealthLogs/public/assets/css/card-icons.css">
</head>
<body class="bg-slate-50 p-4 text-slate-900">
  <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm max-w-4xl mx-auto">
    <h1 class="text-lg font-semibold mb-3"><?= h($title) ?></h1>
    <?php display_flash_messages(true, false); ?>
    <?php display_validation_errors(true); ?>
    <form id="patientEmbedForm" method="post" action="/HealthLogs/public/patients/save.php" target="_top" novalidate class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed" />
      <?php require __DIR__ . '/_form_fields.php'; ?>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button id="btnSubmitPatient" class="bg-slate-900 text-white px-4 py-2 rounded font-medium hover:bg-slate-800 transition" type="submit">Save</button>
        <a class="text-slate-600 px-3 py-2 rounded hover:bg-slate-100 transition" href="/HealthLogs/public/patients/index.php">Cancel</a>
      </div>
    </form>
  </div>

  <script>
  (function () {
    var form = document.getElementById('patientEmbedForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      var firstName = form.querySelector('[name="first_name"]');
      var lastName = form.querySelector('[name="last_name"]');
      var birthDate = form.querySelector('[name="birth_date"]');
      var barangay = form.querySelector('[name="barangay"]');
      var classification = form.querySelector('[name="classification"]');
      var motherName = form.querySelector('[name="mother_name"]');
      var fatherName = form.querySelector('[name="father_name"]');
      var guardianName = form.querySelector('[name="guardian_name"]');
      var parentContact = form.querySelector('[name="parent_contact_no"]');
      var contactNo = form.querySelector('[name="contact_no"]');

      var missing = [];
      if (!firstName || !firstName.value.trim()) missing.push('First Name');
      if (!lastName || !lastName.value.trim()) missing.push('Last Name');
      if (!birthDate || !birthDate.value.trim()) missing.push('Birth Date');
      if (!barangay || !barangay.value.trim()) missing.push('Barangay');

      var isMinor = false;
      var clsVal = classification ? classification.value : '';
      if (['infant', 'under_five', 'school_age', 'adolescent'].indexOf(clsVal) !== -1) {
        isMinor = true;
      } else if (birthDate && birthDate.value.trim()) {
        var dob = new Date(birthDate.value.trim());
        var today = new Date();
        if (!isNaN(dob.getTime())) {
          var ageYears = today.getFullYear() - dob.getFullYear();
          var m = today.getMonth() - dob.getMonth();
          if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
            ageYears--;
          }
          if (ageYears < 18) isMinor = true;
        }
      }

      if (isMinor) {
        var hasParentName = (motherName && motherName.value.trim() !== '') ||
                            (fatherName && fatherName.value.trim() !== '') ||
                            (guardianName && guardianName.value.trim() !== '');
        var hasParentPhone = (parentContact && parentContact.value.trim() !== '') ||
                             (contactNo && contactNo.value.trim() !== '');
        if (!hasParentName) {
          missing.push("Parent / Guardian Name (Mother's, Father's, or Guardian's Full Name is required for minors/pediatric patients)");
        }
        if (!hasParentPhone) {
          missing.push("Parent / Guardian Contact Number (Required for minors/pediatric patients)");
        }
      }

      if (missing.length > 0) {
        e.preventDefault();
        Swal.fire({
          icon: 'warning',
          title: 'Required Fields Missing',
          html: '<p class="text-sm">Please fill in the following required field(s):</p><ul class="mt-2 list-disc list-inside text-sm text-red-600 font-medium text-left">' + missing.map(function(m){ return '<li>' + m + '</li>'; }).join('') + '</ul>',
          confirmButtonColor: '#0f172a'
        });
        return false;
      }

      var btn = document.getElementById('btnSubmitPatient');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="inline-flex items-center"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>Saving...</span>';
      }
    });
  })();
  </script>
  <script src="/HealthLogs/public/assets/js/card-icons.js"></script>
</body>
</html>
