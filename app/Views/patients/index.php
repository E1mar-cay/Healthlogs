<?php $pageTitle = 'Patient Record Management'; ?>

<div class="flex items-center justify-between">
  <div>
    <div class="text-sm text-slate-500">Module</div>
    <div class="text-lg font-semibold">Patients</div>
  </div>
  <a href="/patients/create" class="bg-slate-900 text-white px-4 py-2 rounded">New Patient</a>
</div>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Name</th>
        <th class="text-left px-4 py-2">Sex</th>
        <th class="text-left px-4 py-2">Birth Date</th>
        <th class="text-left px-4 py-2">Barangay</th>
        <th class="text-left px-4 py-2">Status</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($patients)): ?>
        <tr><td class="px-4 py-4" colspan="6">No patients found.</td></tr>
      <?php else: ?>
        <?php foreach ($patients as $p): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= htmlspecialchars($p['last_name'] . ', ' . $p['first_name']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($p['sex']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($p['birth_date']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($p['barangay']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($p['status']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/patients/edit?id=<?= (int)$p['id'] ?>">Edit</a>
              <form method="post" action="/patients/delete" class="inline">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                <button class="text-red-600 ml-2" onclick="return confirm('Delete this patient?');">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
