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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    html,
    body {
      height: 100%;
      padding: 0;
      margin: 0;
    }

    body.app-body {
      font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif;
      color: var(--ink);
      background:
        radial-gradient(1200px 600px at 10% -10%, var(--bg-1), transparent 60%),
        radial-gradient(1000px 500px at 100% 0%, var(--bg-2), transparent 55%),
        #f8fafc;
      margin: 0;
    }

    .app-shell {
      min-height: 100vh;
      display: flex;
      align-items: stretch;
      width: 100%;
      margin: 0;
    }

    .app-sidebar {
      background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
      color: #e2e8f0;
      border-right: 1px solid rgba(255, 255, 255, 0.08);
      height: 100vh;
      top: 0;
      left: 0;
      margin-top: 0;
      padding-top: 0;
      transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
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

    .app-sidebar>* {
      position: relative;
      z-index: 2;
    }

    .app-main {
      flex: 1;
      transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @media (min-width: 768px) {
      .app-sidebar {
        position: fixed;
        width: 18rem;
      }

      .app-main {
        margin-left: 18rem;
      }

      /* Collapsed Sidebar on Desktop */
      body.sidebar-collapsed .app-sidebar,
      html.sidebar-collapsed body .app-sidebar {
        width: 5rem;
      }

      body.sidebar-collapsed .app-main,
      html.sidebar-collapsed body .app-main {
        margin-left: 5rem;
      }

      body.sidebar-collapsed .app-brand-text,
      body.sidebar-collapsed .app-brand-badge,
      body.sidebar-collapsed .nav-text,
      body.sidebar-collapsed .nav-section,
      html.sidebar-collapsed body .app-brand-text,
      html.sidebar-collapsed body .app-brand-badge,
      html.sidebar-collapsed body .nav-text,
      html.sidebar-collapsed body .nav-section {
        display: none !important;
      }

      body.sidebar-collapsed .app-brand-container,
      html.sidebar-collapsed body .app-brand-container {
        padding: 1rem 0.5rem;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
      }

      body.sidebar-collapsed .nav-link,
      html.sidebar-collapsed body .nav-link {
        justify-content: center;
        padding: 9px 0;
        margin-left: 6px;
        margin-right: 6px;
        position: relative;
      }

      body.sidebar-collapsed .nav-link:hover::after,
      html.sidebar-collapsed body .nav-link:hover::after {
        content: attr(title);
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        margin-left: 10px;
        background: #0f172a;
        color: #f8fafc;
        padding: 5px 11px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.12);
        z-index: 50;
        pointer-events: none;
      }
    }

    .app-topbar {
      position: sticky;
      top: 0;
      z-index: 20;
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--line);
      transition: padding-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .app-brand {
      font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
      letter-spacing: 0.02em;
    }

    .app-brand-badge {
      background: linear-gradient(120deg, rgba(14, 165, 164, 0.2), rgba(37, 99, 235, 0.2));
      border: 1px solid rgba(255, 255, 255, 0.15);
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

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.08);
      color: #fff;
    }

    .nav-link.active {
      background: linear-gradient(120deg, rgba(14, 165, 164, 0.22), rgba(37, 99, 235, 0.25));
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
      flex-shrink: 0;
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

    .app-content table {
      border-collapse: collapse;
      width: 100%;
    }

    .app-content thead {
      background: rgba(148, 163, 184, 0.15);
    }

    .app-content th {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--muted);
    }

    .app-content td {
      color: #0f172a;
    }

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
      outline: 2px solid rgba(14, 165, 164, 0.25);
      border-color: rgba(14, 165, 164, 0.6);
    }

    .app-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(37, 99, 235, 0.12);
      color: #1d4ed8;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
    }

    /* Mobile-responsive table touch scrolling */
    .overflow-x-auto {
      -webkit-overflow-scrolling: touch;
    }

    @media print {
      body.app-body {
        background: #fff;
      }

      .app-sidebar,
      .app-topbar,
      #appOverlay {
        display: none !important;
      }

      .app-main {
        margin: 0 !important;
      }

      .app-content {
        padding: 0 !important;
      }

      .bg-white {
        box-shadow: none !important;
      }
    }
  </style>
  <script>
    (function() {
      try {
        if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth >= 768) {
          document.documentElement.classList.add('sidebar-collapsed');
        }
      } catch(e) {}
    })();
  </script>
</head>

<body class="app-body">
  <div class="app-shell">
    <div id="appOverlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm opacity-0 pointer-events-none transition md:hidden z-30"></div>
    <aside id="appSidebar" class="flex flex-col app-sidebar fixed inset-y-0 left-0 z-40 -translate-x-full md:translate-x-0">
      <div class="px-5 py-5 flex items-center justify-between app-brand-container">
        <div class="flex items-center gap-3 overflow-hidden">
          <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-blue-600 text-white font-bold flex items-center justify-center shadow-sm shrink-0">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 4v16"></path>
              <path d="M4 12h16"></path>
              <path d="M7 7h10v10H7z"></path>
            </svg>
          </span>
          <div class="app-brand-text overflow-hidden">
            <div class="app-brand text-xl font-bold text-white tracking-wide truncate">HealthLogs</div>
            <div class="app-brand-badge mt-1">Barangay Care Hub</div>
          </div>
        </div>
        <button id="sidebarCollapseBtn" type="button" class="hidden md:inline-flex text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800/80 transition shrink-0" title="Collapse / Expand Sidebar">
          <i class="fas fa-angles-left text-xs transition-transform duration-200" id="collapseIcon"></i>
        </button>
      </div>
      <nav class="flex-1 px-3 space-y-0.5 overflow-y-auto">
        <div class="nav-section">Core</div>
        <a class="nav-link <?= $isDashboard ? 'active' : '' ?>" href="/HealthLogs/public/index.php" title="Dashboard">
          <span class="nav-icon">DB</span>
          <span class="nav-text font-medium">Dashboard</span>
        </a>
        <a class="nav-link <?= $isActive('/HealthLogs/public/patients') ? 'active' : '' ?>" href="/HealthLogs/public/patients/index.php" title="Patient Records">
          <span class="nav-icon">PT</span>
          <span class="nav-text font-medium">Patient Records</span>
        </a>

        <div class="nav-section">Programs</div>
        <a class="nav-link <?= $isActive('/HealthLogs/public/immunization') ? 'active' : '' ?>" href="/HealthLogs/public/immunization.php" title="Immunization">
          <span class="nav-icon">IM</span>
          <span class="nav-text font-medium">Immunization</span>
        </a>
        <a class="nav-link <?= $isActive('/HealthLogs/public/maternal') ? 'active' : '' ?>" href="/HealthLogs/public/maternal.php" title="Maternal Health">
          <span class="nav-icon">MH</span>
          <span class="nav-text font-medium">Maternal Health</span>
        </a>
        <a class="nav-link <?= $isActive('/HealthLogs/public/tb') ? 'active' : '' ?>" href="/HealthLogs/public/tb.php" title="TB Monitoring">
          <span class="nav-icon">TB</span>
          <span class="nav-text font-medium">TB Monitoring</span>
        </a>

        <a class="nav-link <?= $isActive('/HealthLogs/public/inventory') ? 'active' : '' ?>" href="/HealthLogs/public/inventory.php" title="Medicine Inventory">
          <span class="nav-icon">IN</span>
          <span class="nav-text font-medium">Medicine Inventory</span>
        </a>
        <a class="nav-link <?= $isActive('/HealthLogs/public/reminders') ? 'active' : '' ?>" href="/HealthLogs/public/reminders.php" title="Reminders">
          <span class="nav-icon">RM</span>
          <span class="nav-text font-medium">Reminders</span>
        </a>

        <?php if (($_SESSION['role'] ?? 'health_worker') === 'admin'): ?>
          <div class="nav-section">Administration</div>
          <a class="nav-link <?= $isActive('/HealthLogs/public/reports') ? 'active' : '' ?>" href="/HealthLogs/public/reports.php" title="Reports">
            <span class="nav-icon">RP</span>
            <span class="nav-text font-medium">Reports</span>
          </a>
          <a class="nav-link <?= $isActive('/HealthLogs/public/users') ? 'active' : '' ?>" href="/HealthLogs/public/users.php" title="User Management">
            <span class="nav-icon">US</span>
            <span class="nav-text font-medium">User Management</span>
          </a>
          <a class="nav-link <?= $isActive('/HealthLogs/public/forecast') ? 'active' : '' ?>" href="/HealthLogs/public/forecast.php" title="Forecasting">
            <span class="nav-icon">FC</span>
            <span class="nav-text font-medium">Forecasting</span>
          </a>
        <?php endif; ?>
      </nav>
      <div class="px-3 pb-5 mt-auto pt-2 border-t border-slate-800/80">
        <a class="nav-link text-rose-300 hover:text-rose-100 hover:bg-rose-950/30" href="/HealthLogs/public/logout.php" title="Logout">
          <span class="nav-icon bg-rose-950/40 text-rose-300">LG</span>
          <span class="nav-text font-medium">Logout</span>
        </a>
      </div>
    </aside>

    <main class="app-main min-w-0">
      <header class="app-topbar">
        <div class="w-full px-3 sm:px-4 md:px-6 py-3.5 flex items-center justify-between">
          <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            <?php if (!$isDashboard): ?>
              <button type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '/HealthLogs/public/index.php'; }" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white/95 hover:bg-slate-100 text-slate-700 hover:text-slate-900 text-xs font-semibold shadow-xs transition shrink-0" title="Go back to previous page">
                <i class="fas fa-arrow-left text-xs text-teal-600"></i>
                <span class="hidden sm:inline">Back</span>
              </button>
            <?php endif; ?>
            <button id="sidebarToggle" aria-label="Toggle navigation" class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-white/90 text-slate-700 hover:bg-slate-100 shadow-xs shrink-0 transition" title="Toggle Sidebar">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
              </svg>
            </button>
            <div class="app-title text-base sm:text-lg font-semibold truncate"><?= $pageTitle ?? 'Dashboard' ?></div>
          </div>
          <div class="flex items-center gap-2 sm:gap-4 text-sm shrink-0"></div>
        </div>
      </header>

      <section class="w-full px-3 sm:px-4 md:px-6 py-4 md:py-6 app-content max-w-full">
