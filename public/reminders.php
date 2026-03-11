<?php
$pageTitle = 'Reminders';
require __DIR__ . '/partials/bootstrap.php';
require __DIR__ . '/partials/header.php';

$sql = "SELECT r.*, p.first_name, p.last_name
        FROM reminders r
        JOIN patients p ON p.id = r.patient_id
        ORDER BY r.due_date DESC";
$rows = $pdo->query($sql)->fetchAll();
$statusOptions = [];
if (!empty($rows)) {
    $statusOptions = array_values(array_unique(array_map(function ($row) {
        return strtolower((string)$row['status']);
    }, $rows)));
    sort($statusOptions);
}
?>

<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
  <div>
    <div class="text-sm text-slate-500">Follow-up scheduling</div>
    <div class="text-2xl font-semibold">Reminders</div>
    <p class="text-sm text-slate-500 mt-1">Track upcoming visits and outreach with actionable status tags.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="app-chip">Active Queue</span>
    <a href="/HealthLogs/public/reminders/form.php" class="bg-slate-900 text-white px-4 py-2 rounded-lg shadow">New Reminder</a>
  </div>
</div>

<div class="mt-6 bg-white rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between px-4 py-4 border-b border-slate-100">
    <div class="text-sm text-slate-500">
      <span id="remindersCount">0 reminders</span>
    </div>
    <div class="flex flex-col gap-2 md:flex-row md:items-center">
      <input
        id="reminderSearch"
        class="w-full md:w-64 border rounded-lg px-3 py-2 text-sm"
        type="search"
        placeholder="Search patient, type, date"
      />
      <select id="reminderStatus" class="w-full md:w-48 border rounded-lg px-3 py-2 text-sm">
        <option value="all">All Statuses</option>
        <?php foreach ($statusOptions as $status): ?>
          <option value="<?= h($status) ?>"><?= h(ucwords($status)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table id="remindersTable" class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Patient</th>
        <th class="text-left px-4 py-2">Type</th>
        <th class="text-left px-4 py-2">Due Date</th>
        <th class="text-left px-4 py-2">Status</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr data-empty><td class="px-4 py-4" colspan="5">No reminders found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <?php
            $status = strtolower((string)$r['status']);
            $badgeClass = 'bg-slate-100 text-slate-700';
            if ($status === 'pending') { $badgeClass = 'bg-amber-100 text-amber-700'; }
            if ($status === 'done' || $status === 'completed') { $badgeClass = 'bg-emerald-100 text-emerald-700'; }
            if ($status === 'overdue') { $badgeClass = 'bg-rose-100 text-rose-700'; }
          ?>
          <tr
            class="border-t"
            data-row="1"
            data-status="<?= h($status) ?>"
            data-search="<?= h(strtolower($r['last_name'] . ' ' . $r['first_name'] . ' ' . $r['reminder_type'] . ' ' . $r['due_date'] . ' ' . $r['status'])) ?>"
          >
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['reminder_type']) ?></td>
            <td class="px-4 py-2"><?= h($r['due_date']) ?></td>
            <td class="px-4 py-2">
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= h($badgeClass) ?>">
                <?= h($r['status']) ?>
              </span>
            </td>
            <td class="px-4 py-2">
              <a class="text-blue-600 font-medium" href="/HealthLogs/public/reminders/form.php?id=<?= (int)$r['id'] ?>">Edit</a>
              <form
                method="post"
                action="/HealthLogs/public/reminders/delete.php"
                class="inline"
                data-confirm="Delete this reminder? This cannot be undone."
                data-confirm-title="Delete reminder"
                data-confirm-cta="Yes, delete"
              >
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                <button class="text-red-600 ml-2 font-medium">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="mt-6 bg-white p-5 rounded shadow">
  <div class="text-sm text-slate-500">Scheduler</div>
  <div class="text-lg font-semibold">Run Reminder Cron</div>
  <p class="text-slate-600 mt-1">Manual trigger for testing. In production, run `php scripts/cron_reminders.php` on a schedule.</p>
  <form
    method="post"
    action="/HealthLogs/public/reminders/run_cron.php"
    class="mt-3"
    data-confirm="Run the reminder scheduler now?"
    data-confirm-title="Run scheduler"
    data-confirm-cta="Run now"
  >
    <button class="bg-slate-900 text-white px-4 py-2 rounded-lg shadow">Run Now</button>
  </form>
</div>

<script>
  (function () {
    const searchInput = document.getElementById('reminderSearch');
    const statusSelect = document.getElementById('reminderStatus');
    const table = document.getElementById('remindersTable');
    const countEl = document.getElementById('remindersCount');
    if (!searchInput || !statusSelect || !table || !countEl) return;

    const rows = Array.from(table.querySelectorAll('tbody tr[data-row="1"]'));
    const emptyRow = table.querySelector('tbody tr[data-empty]');

    const applyFilters = () => {
      const query = searchInput.value.trim().toLowerCase();
      const status = statusSelect.value;
      let visible = 0;

      rows.forEach((row) => {
        const rowStatus = row.getAttribute('data-status') || '';
        const rowSearch = row.getAttribute('data-search') || '';
        const matchesQuery = !query || rowSearch.includes(query);
        const matchesStatus = status === 'all' || rowStatus === status;
        const shouldShow = matchesQuery && matchesStatus;
        row.classList.toggle('hidden', !shouldShow);
        if (shouldShow) visible += 1;
      });

      if (emptyRow) {
        emptyRow.classList.toggle('hidden', visible !== 0);
      }

      countEl.textContent = `${visible} reminder${visible === 1 ? '' : 's'}`;
    };

    searchInput.addEventListener('input', applyFilters);
    statusSelect.addEventListener('change', applyFilters);
    applyFilters();
  })();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
