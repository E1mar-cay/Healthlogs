<?php
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$patient = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$id]);
    $patient = $stmt->fetch();
}

$pageTitle = $patient ? 'Edit Patient' : 'New Patient';
require __DIR__ . '/../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <form method="post" action="/HealthLogs/public/patients/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($patient): ?>
      <input type="hidden" name="id" value="<?= (int)$patient['id'] ?>" />
    <?php endif; ?>

    <div>
      <label class="block text-sm text-slate-600">First Name</label>
      <input name="first_name" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['first_name'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Last Name</label>
      <input name="last_name" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['last_name'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Middle Name</label>
      <input name="middle_name" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['middle_name'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Suffix</label>
      <input name="suffix" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['suffix'] ?? '') ?>" />
    </div>

    <div>
      <label class="block text-sm text-slate-600">Sex</label>
      <?php $sex = $patient['sex'] ?? 'male'; ?>
      <select name="sex" class="mt-1 w-full border rounded px-3 py-2">
        <option value="male" <?= $sex === 'male' ? 'selected' : '' ?>>Male</option>
        <option value="female" <?= $sex === 'female' ? 'selected' : '' ?>>Female</option>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Birth Date</label>
      <input type="date" name="birth_date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['birth_date'] ?? '') ?>" />
    </div>

    <div>
      <label class="block text-sm text-slate-600">Barangay</label>
      <input name="barangay" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['barangay'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Contact No</label>
      <input name="contact_no" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['contact_no'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Email</label>
      <input name="email" type="email" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['email'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Address Line</label>
      <input name="address_line" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['address_line'] ?? '') ?>" />
    </div>

    <div>
      <label class="block text-sm text-slate-600">PhilHealth No</label>
      <input name="philhealth_no" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['philhealth_no'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">National ID</label>
      <input name="national_id" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['national_id'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Blood Type</label>
      <input name="blood_type" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['blood_type'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Status</label>
      <?php $status = $patient['status'] ?? 'active'; ?>
      <select name="status" class="mt-1 w-full border rounded px-3 py-2">
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        <option value="deceased" <?= $status === 'deceased' ? 'selected' : '' ?>>Deceased</option>
      </select>
    </div>

    <div>
      <label class="block text-sm text-slate-600">Household ID</label>
      <input name="household_id" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($patient['household_id'] ?? '') ?>" />
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/patients/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
