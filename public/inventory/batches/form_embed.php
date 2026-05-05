<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM medicine_batches WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();

    if (!$rec) {
        $_SESSION['error_message'] = 'Batch not found';
        header('Location: /HealthLogs/public/inventory/batches/index.php');
        exit;
    }
}

$meds = $pdo->query("SELECT id, name FROM medicines ORDER BY name ASC")->fetchAll();
$existingBatchRows = $pdo->query("SELECT medicine_id, batch_no FROM medicine_batches ORDER BY medicine_id, batch_no")->fetchAll();
$batchNumbersByMedicine = [];
foreach ($existingBatchRows as $row) {
    $medicineId = (int)$row['medicine_id'];
    $batchNumbersByMedicine[$medicineId][] = (string)$row['batch_no'];
}
$selectedMedicineId = (int)old('medicine_id', $rec['medicine_id'] ?? ($meds[0]['id'] ?? 0));
$selectedBatchNo = (string)old('batch_no', $rec['batch_no'] ?? '');
$buildBatchOptions = static function (array $usedBatchNumbers, string $currentBatchNo = ''): array {
    $usedLookup = array_fill_keys($usedBatchNumbers, true);
    if ($currentBatchNo !== '') unset($usedLookup[$currentBatchNo]);
    $options = [];
    for ($i = 1; $i <= 50; $i++) {
        $candidate = str_pad((string)$i, 3, '0', STR_PAD_LEFT);
        if (!isset($usedLookup[$candidate])) $options[] = $candidate;
    }
    if ($currentBatchNo !== '' && !in_array($currentBatchNo, $options, true)) array_unshift($options, $currentBatchNo);
    return $options;
};
$initialBatchOptions = $buildBatchOptions($batchNumbersByMedicine[$selectedMedicineId] ?? [], $selectedBatchNo);
$title = $rec ? 'Edit Batch' : 'New Batch';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <base target="_parent">
  <title><?= h($title) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 p-4 text-slate-900">
  <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm max-w-4xl mx-auto">
    <div class="mb-5">
      <h1 class="text-xl font-semibold"><?= h($title) ?></h1>
      <p class="text-sm text-slate-500 mt-1">Manage batch metadata and opening stock.</p>
    </div>
    <?php display_flash_messages(); ?>
    <form method="post" action="/HealthLogs/public/inventory/batches/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed">
      <?php if ($rec): ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" /><?php endif; ?>
      <div>
        <label class="block text-sm text-slate-600">Medicine</label>
        <select id="medicine_id" name="medicine_id" required class="mt-1 w-full border rounded px-3 py-2">
          <?php foreach ($meds as $m): ?>
            <option value="<?= (int)$m['id'] ?>" <?= $selectedMedicineId == $m['id'] ? 'selected' : '' ?>><?= h($m['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Batch Number</label>
        <select id="batch_no" name="batch_no" required class="mt-1 w-full border rounded px-3 py-2" data-selected-batch="<?= h($selectedBatchNo) ?>">
          <?php foreach ($initialBatchOptions as $batchOption): ?>
            <option value="<?= h($batchOption) ?>" <?= $selectedBatchNo === $batchOption ? 'selected' : '' ?>><?= h($batchOption) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="mt-1 text-xs text-slate-500">Pick an available batch number for this medicine.</div>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Expiry Date</label>
        <input name="expiry_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('expiry_date', $rec['expiry_date'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Received Date</label>
        <input name="received_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('received_date', $rec['received_date'] ?? '')) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Quantity Received</label>
        <input name="quantity_received" type="number" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('quantity_received', $rec['quantity_received'] ?? '')) ?>" />
      </div>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
        <a class="text-slate-600" href="/HealthLogs/public/inventory/batches/index.php">Cancel</a>
      </div>
    </form>
  </div>
  <script>
  const batchNumbersByMedicine = <?= json_encode($batchNumbersByMedicine, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const medicineSelect = document.getElementById('medicine_id');
  const batchSelect = document.getElementById('batch_no');
  const preservedSelectedBatch = batchSelect?.dataset.selectedBatch || '';
  function buildBatchOptionsForMedicine(medicineId, selectedBatch) {
    const used = new Set((batchNumbersByMedicine[medicineId] || []).map(String));
    if (selectedBatch) used.delete(String(selectedBatch));
    const options = [];
    for (let i = 1; i <= 50; i += 1) {
      const candidate = String(i).padStart(3, '0');
      if (!used.has(candidate)) options.push(candidate);
    }
    if (selectedBatch && !options.includes(String(selectedBatch))) options.unshift(String(selectedBatch));
    return options;
  }
  function renderBatchOptions() {
    if (!medicineSelect || !batchSelect) return;
    const medicineId = medicineSelect.value;
    const currentSelected = batchSelect.value || preservedSelectedBatch;
    const options = buildBatchOptionsForMedicine(medicineId, currentSelected);
    batchSelect.innerHTML = '';
    options.forEach((optionValue) => {
      const option = document.createElement('option');
      option.value = optionValue;
      option.textContent = optionValue;
      if (optionValue === currentSelected) option.selected = true;
      batchSelect.appendChild(option);
    });
  }
  if (medicineSelect && batchSelect) {
    medicineSelect.addEventListener('change', () => {
      batchSelect.dataset.selectedBatch = '';
      renderBatchOptions();
    });
    renderBatchOptions();
  }
  </script>
</body>
</html>
