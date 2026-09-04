<?php
$pageTitle = 'Reminders';
require __DIR__ . '/partials/bootstrap.php';
require __DIR__ . '/partials/header.php';

$sql = "SELECT r.*, p.first_name, p.last_name, p.contact_no
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

<?php display_flash_messages(); ?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow mb-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Follow-up scheduling</div>
      <div class="text-2xl font-semibold">Reminders</div>
      <p class="text-sm text-slate-500 mt-1">Track upcoming visits and dispatch instant TextBee SMS outreach.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip">Active Queue</span>
      <button type="button" id="reminderModalOpenNew" data-embed-url="/HealthLogs/public/reminders/form_embed.php" class="w-full sm:w-auto inline-flex items-center justify-center bg-slate-900 text-white px-4 py-2.5 rounded-lg shadow text-sm font-medium hover:bg-slate-800 transition">
        <i class="fas fa-plus mr-1.5 text-xs"></i>New Reminder
      </button>
    </div>
  </div>
</div>

<div class="bg-white rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between p-4 border-b border-slate-100">
    <div class="text-sm text-slate-500">
      <span id="remindersCount">0 reminders</span>
    </div>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
      <input
        id="reminderSearch"
        class="w-full sm:w-64 border rounded-lg px-3 py-2 text-sm"
        type="search"
        placeholder="Search patient, type, date"
      />
      <select id="reminderStatus" class="w-full sm:w-48 border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="all">All Statuses</option>
        <?php foreach ($statusOptions as $status): ?>
          <option value="<?= h($status) ?>"><?= h(ucwords($status)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
    <table id="remindersTable" class="min-w-full text-sm min-w-[650px]">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2.5">Patient</th>
        <th class="text-left px-4 py-2.5">Contact No</th>
        <th class="text-left px-4 py-2.5">Type</th>
        <th class="text-left px-4 py-2.5">Due Date</th>
        <th class="text-left px-4 py-2.5">Status</th>
        <th class="text-left px-4 py-2.5">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr data-empty><td class="px-4 py-4" colspan="6">No reminders found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <?php
            $status = strtolower((string)$r['status']);
            $badgeClass = 'bg-slate-100 text-slate-700';
            if ($status === 'pending') { $badgeClass = 'bg-amber-100 text-amber-700'; }
            if ($status === 'sent') { $badgeClass = 'bg-teal-100 text-teal-700'; }
            if ($status === 'done' || $status === 'completed') { $badgeClass = 'bg-emerald-100 text-emerald-700'; }
            if ($status === 'overdue' || $status === 'failed') { $badgeClass = 'bg-rose-100 text-rose-700'; }
          ?>
          <tr
            class="border-t hover:bg-slate-50/80 transition"
            data-row="1"
            data-status="<?= h($status) ?>"
            data-search="<?= h(strtolower($r['last_name'] . ' ' . $r['first_name'] . ' ' . ($r['contact_no'] ?? '') . ' ' . $r['reminder_type'] . ' ' . $r['due_date'] . ' ' . $r['status'])) ?>"
          >
            <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-3 text-slate-600 font-mono text-xs whitespace-nowrap"><?= h($r['contact_no'] ?: '—') ?></td>
            <td class="px-4 py-3 capitalize whitespace-nowrap"><?= h($r['reminder_type']) ?></td>
            <td class="px-4 py-3 whitespace-nowrap"><?= h($r['due_date']) ?></td>
            <td class="px-4 py-3 whitespace-nowrap">
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= h($badgeClass) ?>">
                <?= h(ucfirst($r['status'])) ?>
              </span>
            </td>
            <td class="px-4 py-3 whitespace-nowrap">
              <div class="inline-flex items-center gap-2">
                <form method="post" action="/HealthLogs/public/reminders/send_sms.php" class="inline">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                  <button type="submit" class="inline-flex items-center gap-1 text-xs bg-teal-600 hover:bg-teal-700 text-white font-medium px-2.5 py-1.5 rounded-lg shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Send SMS
                  </button>
                </form>
                <button type="button" class="reminder-modal-edit text-blue-600 hover:underline font-medium text-xs px-2 py-1" data-embed-url="/HealthLogs/public/reminders/form_embed.php?id=<?= (int)$r['id'] ?>">Edit</button>
                <form
                  method="post"
                  action="/HealthLogs/public/reminders/delete.php"
                  class="inline"
                  data-confirm="Delete this reminder? This cannot be undone."
                  data-confirm-title="Delete reminder"
                  data-confirm-cta="Yes, delete"
                >
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                  <button class="text-red-600 hover:underline font-medium text-xs px-2 py-1">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="mt-6 bg-white p-4 sm:p-5 rounded-xl shadow">
  <div class="text-sm text-slate-500">Scheduler</div>
  <div class="text-lg font-semibold">Run Reminder Cron</div>
  <p class="text-slate-600 mt-1 text-sm">Manual trigger for testing. In production, run `php scripts/cron_reminders.php` on a schedule.</p>
  <form
    method="post"
    action="/HealthLogs/public/reminders/run_cron.php"
    class="mt-3"
    data-confirm="Run the reminder scheduler now?"
    data-confirm-title="Run scheduler"
    data-confirm-cta="Run now"
  >
    <button class="w-full sm:w-auto inline-flex items-center justify-center bg-slate-900 text-white px-4 py-2.5 rounded-lg shadow text-sm font-medium hover:bg-slate-800 transition">Run Now</button>
  </form>
</div>

<div id="reminderFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="reminderFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-4 sm:mt-10 max-w-5xl px-2 sm:px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-2.5rem)] sm:max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Reminder form</div>
        <button type="button" id="reminderFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="reminderFormModalFrame" class="w-full min-h-[70vh] sm:min-h-[75vh] border-0 flex-1" title="Reminder form"></iframe>
    </div>
  </div>
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

  (function () {
    var modal = document.getElementById('reminderFormModal');
    var frame = document.getElementById('reminderFormModalFrame');
    var backdrop = document.getElementById('reminderFormModalBackdrop');
    var closeBtn = document.getElementById('reminderFormModalClose');

    function openModal(url) {
      if (!modal || !frame || !url) return;
      frame.src = url;
      modal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
      if (closeBtn) closeBtn.focus();
    }

    function closeModal() {
      if (!modal || !frame) return;
      frame.src = 'about:blank';
      modal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }

    var newBtn = document.getElementById('reminderModalOpenNew');
    if (newBtn) {
      newBtn.addEventListener('click', function () {
        openModal(newBtn.getAttribute('data-embed-url') || '');
      });
    }

    document.querySelectorAll('.reminder-modal-edit').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openModal(btn.getAttribute('data-embed-url') || '');
      });
    });

    if (backdrop) backdrop.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });
  })();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
