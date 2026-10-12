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
$originalBatchNo = $rec ? (string)$rec['batch_no'] : '';
$originalMedicineId = $rec ? (int)$rec['medicine_id'] : 0;

$buildBatchOptions = static function (array $usedBatchNumbers, string $allowedBatchNo = ''): array {
    $usedLookup = array_fill_keys($usedBatchNumbers, true);
    if ($allowedBatchNo !== '') {
        unset($usedLookup[$allowedBatchNo]);
    }

    $options = [];
    for ($i = 1; $i <= 50; $i++) {
        $candidate = str_pad((string)$i, 3, '0', STR_PAD_LEFT);
        if (!isset($usedLookup[$candidate])) {
            $options[] = $candidate;
        }
    }

    if ($allowedBatchNo !== '' && !in_array($allowedBatchNo, $options, true)) {
        array_unshift($options, $allowedBatchNo);
    }

    return $options;
};

$allowedBatch = ($rec && $originalMedicineId === $selectedMedicineId) ? $originalBatchNo : '';
$initialBatchOptions = $buildBatchOptions($batchNumbersByMedicine[$selectedMedicineId] ?? [], $allowedBatch);

if (!in_array($selectedBatchNo, $initialBatchOptions, true)) {
    $selectedBatchNo = $initialBatchOptions[0] ?? '';
}

$pageTitle = $rec ? 'Edit Batch' : 'New Batch';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <?php display_flash_messages(true, true); ?>
  <form method="post" action="/HealthLogs/public/inventory/batches/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($rec): ?>
      <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
    <?php endif; ?>

    <div>
      <label class="block text-sm text-slate-600">Medicine</label>
      <select id="medicine_id" name="medicine_id" required class="mt-1 w-full border rounded px-3 py-2">
        <?php foreach ($meds as $m): ?>
          <?php $sel = $selectedMedicineId == $m['id'] ? 'selected' : ''; ?>
          <option value="<?= (int)$m['id'] ?>" <?= $sel ?>><?= h($m['name']) ?></option>
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
const originalBatchNo = <?= json_encode($rec ? (string)$rec['batch_no'] : '') ?>;
const originalMedicineId = <?= $rec ? (int)$rec['medicine_id'] : 'null' ?>;
const medicineSelect = document.getElementById('medicine_id');
const batchSelect = document.getElementById('batch_no');
const preservedSelectedBatch = batchSelect?.dataset.selectedBatch || '';

function buildBatchOptionsForMedicine(medicineId) {
  const used = new Set((batchNumbersByMedicine[medicineId] || []).map(String));
  const isOriginalRecordMed = originalMedicineId !== null && Number(medicineId) === originalMedicineId;
  if (isOriginalRecordMed && originalBatchNo) {
    used.delete(String(originalBatchNo));
  }
  const options = [];
  for (let i = 1; i <= 50; i += 1) {
    const candidate = String(i).padStart(3, '0');
    if (!used.has(candidate)) options.push(candidate);
  }
  if (isOriginalRecordMed && originalBatchNo && !options.includes(String(originalBatchNo))) {
    options.unshift(String(originalBatchNo));
  }
  return options;
}

function renderBatchOptions(preferredBatch) {
  if (!medicineSelect || !batchSelect) return;
  const medicineId = medicineSelect.value;
  const options = buildBatchOptionsForMedicine(medicineId);

  batchSelect.innerHTML = '';
  if (options.length === 0) {
    const option = document.createElement('option');
    option.value = '';
    option.textContent = 'No batch numbers available (1-50 full)';
    batchSelect.appendChild(option);
    return;
  }

  const selectedValue = (preferredBatch && options.includes(preferredBatch)) ? preferredBatch : options[0];

  options.forEach((optionValue) => {
    const option = document.createElement('option');
    option.value = optionValue;
    option.textContent = optionValue;
    if (optionValue === selectedValue) option.selected = true;
    batchSelect.appendChild(option);
  });
}

if (medicineSelect && batchSelect) {
  medicineSelect.addEventListener('change', () => {
    batchSelect.dataset.selectedBatch = '';
    renderBatchOptions();
  });
  renderBatchOptions(preservedSelectedBatch || batchSelect.value);
}
</script>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
