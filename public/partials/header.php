<?php
require __DIR__ . '/bootstrap.php';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$isActive = function (string $prefix) use ($currentPath): bool {
  return strncmp($currentPath, $prefix, strlen($prefix)) === 0;
};
$isDashboard = ($currentPath === '/HealthLogs/public/' || $currentPath === '/HealthLogs/public/index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>HealthLogs</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap');
    :root {
      --bg-1: #eef2ff;
      --bg-2: #f0fdf4;
      --ink: #0b1220;
      --muted: #5b6b82;
      --accent: #0ea5a4;
      --accent-2: #2563eb;
      --card: rgba(255, 255, 255, 0.92);
      --line: rgba(15, 23, 42, 0.08);
      --shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
    }
    html, body { height: 100%; padding: 0; margin: 0; }
    body.app-body {
      font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif;
      color: var(--ink);
      background:
        radial-gradient(1200px 600px at 10% -10%, var(--bg-1), transparent 60%),
        radial-gradient(1000px 500px at 100% 0%, var(--bg-2), transparent 55%),
        #f8fafc;
      margin: 0;
    }
    .app-shell { min-height: 100vh; display: flex; align-items: stretch; width: 100%; margin: 0; }
    .app-sidebar {
      background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
      color: #e2e8f0;
      border-right: 1px solid rgba(255,255,255,0.08);
      height: 100vh;
      top: 0;
      left: 0;
      margin-top: 0;
      padding-top: 0;
    }
    .app-sidebar::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 12px;
      background: #0f172a;
      z-index: 1;
    }
    .app-sidebar > * { position: relative; z-index: 2; }
    .app-main { flex: 1; }
    @media (min-width: 768px) {
      .app-sidebar { position: fixed; }
      .app-main { margin-left: 18rem; }
      .app-topbar { padding-left: 18rem; }
    }
    .app-topbar { position: sticky; top: 0; z-index: 20; }
    .app-brand {
      font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
      letter-spacing: 0.02em;
    }
    .app-brand-badge {
      background: linear-gradient(120deg, rgba(14,165,164,0.2), rgba(37,99,235,0.2));
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 999px;
      padding: 4px 10px;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.18em;
      color: #cbd5f5;
    }
    .nav-link {
      display: flex;
      gap: 10px;
      align-items: center;
      padding: 9px 12px;
      border-radius: 12px;
      color: #cbd5f5;
      transition: all .15s ease;
    }
    .nav-link:hover { background: rgba(255,255,255,0.08); color: #fff; }
    .nav-link.active {
      background: linear-gradient(120deg, rgba(14,165,164,0.22), rgba(37,99,235,0.25));
      color: #fff;
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.3);
    }
    .nav-link .nav-icon {
      width: 22px;
      height: 22px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: rgba(226, 232, 240, 0.8);
      font-size: 10px;
      font-weight: 700;
      border-radius: 8px;
      background: rgba(148, 163, 184, 0.18);
    }
    .nav-link.active .nav-icon,
    .nav-link:hover .nav-icon {
      color: #fff;
      background: rgba(255, 255, 255, 0.18);
    }
    .nav-section {
      text-transform: uppercase;
      letter-spacing: 0.2em;
      font-size: 10px;
      color: rgba(226, 232, 240, 0.45);
      padding: 10px 12px 4px;
    }
    .app-topbar {
      background: rgba(255,255,255,0.7);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--line);
    }
    .app-title {
      font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
      font-weight: 600;
      letter-spacing: 0.01em;
    }
    .app-content .bg-white {
      background: var(--card);
      border: 1px solid var(--line);
      box-shadow: var(--shadow);
    }
    .app-content table { border-collapse: collapse; width: 100%; }
    .app-content thead { background: rgba(148, 163, 184, 0.15); }
    .app-content th {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--muted);
    }
    .app-content td { color: #0f172a; }
    .app-content input,
    .app-content select,
    .app-content textarea {
      background: rgba(248, 250, 252, 0.9);
      border: 1px solid var(--line);
      border-radius: 12px;
    }
    .app-content input:focus,
    .app-content select:focus,
    .app-content textarea:focus {
      outline: 2px solid rgba(14,165,164,0.25);
      border-color: rgba(14,165,164,0.6);
    }
    .app-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(37,99,235,0.12);
      color: #1d4ed8;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
    }
    @media print {
      body.app-body { background: #fff; }
      .app-sidebar, .app-topbar, #appOverlay { display: none !important; }
      .app-main { margin: 0 !important; }
      .app-content { padding: 0 !important; }
      .bg-white { box-shadow: none !important; }
    }
  </style>
</head>
<body class="app-body">
  <div class="app-shell">
    <div id="appOverlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm opacity-0 pointer-events-none transition md:hidden z-30"></div>
    <aside id="appSidebar" class="w-72 flex flex-col app-sidebar fixed inset-y-0 left-0 z-40 -translate-x-full md:translate-x-0 transition-transform duration-200">
      <div class="px-6 py-6">
        <div class="app-brand text-2xl font-semibold">HealthLogs</div>
        <div class="app-brand-badge mt-2">Barangay Care Hub</div>
      </div>
      <nav class="flex-1 px-4 space-y-0.5">
        <div class="nav-section">Core</div>
        <a class="nav-link <?= $isDashboard ? 'active' : '' ?>" href="/HealthLogs/public/index.php">
          <span class="nav-icon">DB</span> Dashboard
        </a>
        <a class="nav-link <?= $isActive('/HealthLogs/public/patients') ? 'active' : '' ?>" href="/HealthLogs/public/patients/index.php">
          <span class="nav-icon">PT</span> Patient Records
        </a>

        <div class="nav-section">Programs</div>
        <a class="nav-link <?= $isActive('/HealthLogs/public/immunization') ? 'active' : '' ?>" href="/HealthLogs/public/immunization.php">
          <span class="nav-icon">IM</span> Immunization
        </a>
        <a class="nav-link <?= $isActive('/HealthLogs/public/maternal') ? 'active' : '' ?>" href="/HealthLogs/public/maternal.php">
          <span class="nav-icon">MH</span> Maternal Health
        </a>
        <a class="nav-link <?= $isActive('/HealthLogs/public/tb') ? 'active' : '' ?>" href="/HealthLogs/public/tb.php">
          <span class="nav-icon">TB</span> TB Monitoring
        </a>
        <a class="nav-link <?= $isActive('/HealthLogs/public/inventory') ? 'active' : '' ?>" href="/HealthLogs/public/inventory.php">
          <span class="nav-icon">IN</span> Medicine Inventory
        </a>

        <?php if (in_array($_SESSION['role'] ?? 'health_worker', ['admin', 'superadmin'], true)): ?>
          <div class="nav-section">Insights</div>
          <a class="nav-link <?= $isActive('/HealthLogs/public/reminders') ? 'active' : '' ?>" href="/HealthLogs/public/reminders.php">
            <span class="nav-icon">RM</span> Reminders
          </a>
          <a class="nav-link <?= $isActive('/HealthLogs/public/forecast') ? 'active' : '' ?>" href="/HealthLogs/public/forecast.php">
            <span class="nav-icon">FC</span> Forecasting
          </a>
        <?php endif; ?>
      </nav>
      <div class="px-4 pb-6 mt-auto pt-2 border-t border-slate-700/40">
        <a class="nav-link" href="/HealthLogs/public/logout.php">
          <span class="nav-icon">LG</span> Logout
        </a>
      </div>
    </aside>

    <main class="app-main">
      <header class="app-topbar">
        <div class="w-full px-4 md:px-6 py-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button id="sidebarToggle" class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-white/80 text-slate-700 shadow-sm">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
              </svg>
            </button>
            <div class="app-title text-lg"><?= $pageTitle ?? 'Dashboard' ?></div>
          </div>
          <div class="flex items-center gap-4 text-sm"></div>
        </div>
      </header>

      <section class="w-full px-6 py-6 app-content">
