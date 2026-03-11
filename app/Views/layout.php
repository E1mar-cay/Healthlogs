<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>HealthLogs</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-100 text-slate-900">
  <div class="min-h-screen flex">
    <aside class="w-64 bg-slate-900 text-slate-100 hidden md:flex flex-col">
      <div class="px-6 py-5 text-xl font-semibold">HealthLogs</div>
      <nav class="flex-1 px-4 space-y-1">
        <a class="block px-3 py-2 rounded hover:bg-slate-800" href="/">Dashboard</a>
        <a class="block px-3 py-2 rounded hover:bg-slate-800" href="/patients">Patient Records</a>
        <a class="block px-3 py-2 rounded hover:bg-slate-800" href="/immunization">Immunization</a>
        <a class="block px-3 py-2 rounded hover:bg-slate-800" href="/maternal">Maternal Health</a>
        <a class="block px-3 py-2 rounded hover:bg-slate-800" href="/tb">TB Monitoring</a>
        <a class="block px-3 py-2 rounded hover:bg-slate-800" href="/inventory">Medicine Inventory</a>
        <a class="block px-3 py-2 rounded hover:bg-slate-800" href="/reminders">Reminders</a>
        <a class="block px-3 py-2 rounded hover:bg-slate-800" href="/forecast">Forecasting</a>
      </nav>
      <div class="px-6 py-4 text-xs text-slate-400">BHUs Admin</div>
    </aside>

    <main class="flex-1">
      <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
          <div class="text-lg font-semibold"><?= $pageTitle ?? 'Dashboard' ?></div>
          <div class="text-sm text-slate-500">Today</div>
        </div>
      </header>

      <section class="max-w-7xl mx-auto px-6 py-6">
        <?php require $viewFile; ?>
      </section>
    </main>
  </div>
</body>
</html>
