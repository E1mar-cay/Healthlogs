<?php
require __DIR__ . '/bootstrap.php';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$isActive = function (string $prefix) use ($currentPath): bool {
  return strncmp($currentPath, $prefix, strlen($prefix)) === 0;
};
$isDashboard = ($currentPath === '/HealthLogs/public/' || $currentPath === '/HealthLogs/public/index.php');

// Determine active clinical section for topbar breadcrumb context
$breadcrumbSection = 'Clinical Overview';
if ($isActive('/HealthLogs/public/patients') || $isActive('/HealthLogs/public/appointments')) {
  $breadcrumbSection = 'Core Registry';
} elseif (
  $isActive('/HealthLogs/public/immunization')
  || $isActive('/HealthLogs/public/maternal')
  || $isActive('/HealthLogs/public/family_planning')
  || $isActive('/HealthLogs/public/ncd')
) {
  $breadcrumbSection = 'Programs';
} elseif (
  $currentPath === '/HealthLogs/public/inventory.php'
  || $isActive('/HealthLogs/public/inventory/')
  || $isActive('/HealthLogs/public/medicine_supplies')
) {
  $breadcrumbSection = 'Pharmacy & Supply';
} elseif ($isActive('/HealthLogs/public/reminders')) {
  $breadcrumbSection = 'Outreach & SMS';
} elseif (
  $isActive('/HealthLogs/public/reports')
  || $isActive('/HealthLogs/public/users')
  || $isActive('/HealthLogs/public/forecast')
  || $isActive('/HealthLogs/public/activity_logs')
) {
  $breadcrumbSection = 'Administration';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= isset($pageTitle) ? h($pageTitle) . ' — HealthLogs' : 'HealthLogs EHR' ?></title>
  <link rel="icon" type="image/jpeg" href="/HealthLogs/public/assets/images/logo.jpeg">
  <!-- Local Assets for 100% Offline Support -->
  <script src="/HealthLogs/public/assets/js/tailwind.js"></script>
  <script src="/HealthLogs/public/assets/js/chart.umd.min.js"></script>
  <script src="/HealthLogs/public/assets/js/sweetalert2.all.min.js"></script>
  <link rel="stylesheet" href="/HealthLogs/public/assets/css/fontawesome.min.css">
  <link rel="stylesheet" href="/HealthLogs/public/assets/css/card-icons.css">
  <script src="/HealthLogs/public/assets/js/card-icons.js" defer></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap');

    :root {
      --bg-canvas: #f8fafc;
      --bg-glow-1: rgba(13, 148, 136, 0.06);
      --bg-glow-2: rgba(37, 99, 235, 0.05);
      --ink: #0f172a;
      --ink-secondary: #334155;
      --muted: #64748b;
      --accent: #0d9488;
      --accent-dark: #0f766e;
      --accent-light: #ccfbf1;
      --card: #ffffff;
      --line: #e2e8f0;
      --line-subtle: #f1f5f9;
      --shadow-card: 0 1px 3px 0 rgba(15, 23, 42, 0.04), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
      --shadow-card-hover: 0 4px 14px -2px rgba(13, 148, 136, 0.10), 0 2px 6px -1px rgba(15, 23, 42, 0.05);
      --font-body: 'Plus Jakarta Sans', 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      --font-display: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;
      --font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
    }

    html,
    body {
      height: 100%;
      padding: 0;
      margin: 0;
      font-size: 14px;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    body.app-body {
      font-family: var(--font-body);
      font-size: 14px;
      line-height: 1.55;
      color: var(--ink);
      background:
        radial-gradient(1100px 550px at 12% -8%, var(--bg-glow-1), transparent 60%),
        radial-gradient(900px 480px at 95% 0%, var(--bg-glow-2), transparent 55%),
        var(--bg-canvas);
      margin: 0;
    }

    .app-content {
      font-family: var(--font-body);
      line-height: 1.55;
    }

    .app-content h1,
    .app-content h2,
    .app-content h3,
    .app-content h4,
    .app-brand,
    .app-title {
      font-family: var(--font-display);
      letter-spacing: -0.02em;
    }

    .font-mono {
      font-family: var(--font-mono) !important;
    }

    /* Custom Clinical Scrollbars */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: transparent;
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(148, 163, 184, 0.35);
      border-radius: 999px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(13, 148, 136, 0.55);
    }

    .app-shell {
      min-height: 100vh;
      display: flex;
      align-items: stretch;
      width: 100%;
      margin: 0;
    }

    /* Deep Emerald-Slate EHR Sidebar */
    .app-sidebar {
      background:
        radial-gradient(320px 220px at 15% 0%, rgba(13, 148, 136, 0.16), transparent 70%),
        linear-gradient(180deg, #091321 0%, #0c192c 55%, #091524 100%);
      color: #e2e8f0;
      border-right: 1px solid rgba(148, 163, 184, 0.12);
      height: 100vh;
      top: 0;
      left: 0;
      margin-top: 0;
      padding-top: 0;
      box-shadow: 4px 0 24px rgba(9, 19, 33, 0.18);
      transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .app-sidebar > * {
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
        width: 17.5rem;
      }

      .app-main {
        margin-left: 17.5rem;
      }

      /* Collapsed Sidebar on Desktop */
      body.sidebar-collapsed .app-sidebar,
      html.sidebar-collapsed body .app-sidebar {
        width: 4.75rem;
      }

      body.sidebar-collapsed .app-main,
      html.sidebar-collapsed body .app-main {
        margin-left: 4.75rem;
      }

      body.sidebar-collapsed .app-brand-text,
      body.sidebar-collapsed .app-brand-badge,
      body.sidebar-collapsed .nav-text,
      body.sidebar-collapsed .nav-section,
      body.sidebar-collapsed .sidebar-status-card,
      html.sidebar-collapsed body .app-brand-text,
      html.sidebar-collapsed body .app-brand-badge,
      html.sidebar-collapsed body .nav-text,
      html.sidebar-collapsed body .nav-section,
      html.sidebar-collapsed body .sidebar-status-card {
        display: none !important;
      }

      body.sidebar-collapsed #navProgramsMenu,
      body.sidebar-collapsed #navInventoryMenu,
      html.sidebar-collapsed body #navProgramsMenu,
      html.sidebar-collapsed body #navInventoryMenu {
        padding-left: 0 !important;
        border-left: none !important;
        margin-left: 0 !important;
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
        margin-left: 12px;
        background: #0f172a;
        color: #f8fafc;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(45, 212, 191, 0.25);
        z-index: 50;
        pointer-events: none;
      }
    }

    /* Sticky Frosted Clinical Topbar */
    .app-topbar {
      position: sticky;
      top: 0;
      z-index: 20;
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--line);
      box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.03);
      transition: padding-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .app-brand {
      font-family: var(--font-display);
      letter-spacing: -0.01em;
    }

    .app-brand-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: rgba(13, 148, 136, 0.16);
      border: 1px solid rgba(45, 212, 191, 0.28);
      border-radius: 999px;
      padding: 2px 9px;
      font-size: 10px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: #5eead4;
    }

    .nav-link {
      position: relative;
      display: flex;
      gap: 11px;
      align-items: center;
      padding: 8.5px 11px;
      border-radius: 11px;
      color: #cbd5e1;
      font-size: 13px;
      font-weight: 500;
      transition: all 0.16s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-text {
      font-size: 13px;
      letter-spacing: 0.005em;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.06);
      color: #ffffff;
    }

    .nav-link.active {
      background: linear-gradient(90deg, rgba(13, 148, 136, 0.24) 0%, rgba(15, 118, 110, 0.08) 100%);
      color: #ffffff;
      font-weight: 600;
      border: 1px solid rgba(45, 212, 191, 0.22);
      box-shadow: 0 4px 14px rgba(6, 78, 59, 0.25);
    }

    .nav-link.active::before {
      content: "";
      position: absolute;
      left: 0;
      top: 22%;
      bottom: 22%;
      width: 3.5px;
      border-radius: 0 4px 4px 0;
      background: #2dd4bf;
      box-shadow: 0 0 8px rgba(45, 212, 191, 0.8);
    }

    .nav-link .nav-icon {
      width: 28px;
      height: 28px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #94a3b8;
      font-size: 12.5px;
      border-radius: 8px;
      background: rgba(30, 41, 59, 0.75);
      border: 1px solid rgba(148, 163, 184, 0.12);
      flex-shrink: 0;
      transition: all 0.16s ease;
    }

    .nav-link:hover .nav-icon {
      color: #5eead4;
      background: rgba(13, 148, 136, 0.18);
      border-color: rgba(45, 212, 191, 0.28);
    }

    .nav-link.active .nav-icon {
      color: #ffffff;
      background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
      border-color: rgba(94, 234, 212, 0.4);
      box-shadow: 0 2px 8px rgba(13, 148, 136, 0.45);
    }

    .nav-submenu {
      border-left: 1px solid rgba(148, 163, 184, 0.16);
      margin-left: 1.25rem;
      padding-left: 0.6rem;
    }

    .nav-section {
      text-transform: uppercase;
      letter-spacing: 0.14em;
      font-size: 10px;
      font-weight: 700;
      color: #64748b;
      padding: 14px 12px 5px;
    }

    .app-title {
      font-family: var(--font-display);
      font-weight: 700;
      letter-spacing: -0.015em;
      color: #0f172a;
    }

    /* Refined Clinical Cards */
    .app-content .bg-white {
      background: var(--card);
      border: 1px solid var(--line);
      box-shadow: var(--shadow-card);
    }

    .app-content .rounded.shadow,
    .app-content .rounded-lg.shadow {
      border-radius: 0.875rem;
    }

    .app-content a.bg-white:hover {
      border-color: rgba(13, 148, 136, 0.35);
      box-shadow: var(--shadow-card-hover);
    }

    /* Clinical Data Tables */
    .app-content table {
      border-collapse: collapse;
      width: 100%;
    }

    .app-content thead {
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
    }

    .app-content th {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      color: #475569;
    }

    .app-content tbody tr {
      transition: background-color 0.12s ease;
    }

    .app-content tbody tr:hover {
      background-color: rgba(240, 253, 250, 0.55);
    }

    .app-content td {
      color: #1e293b;
    }

    /* Clinical Form Inputs & Controls */
    .app-content input:not([type="checkbox"]):not([type="radio"]),
    .app-content select,
    .app-content textarea {
      background: #ffffff;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      color: #0f172a;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .app-content input:not([type="checkbox"]):not([type="radio"]):hover,
    .app-content select:hover,
    .app-content textarea:hover {
      border-color: #94a3b8;
    }

    .app-content input:focus,
    .app-content select:focus,
    .app-content textarea:focus {
      outline: none;
      border-color: #0d9488;
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    .app-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #f0fdfa;
      color: #0f766e;
      border: 1px solid #99f6e4;
      padding: 4px 11px;
      border-radius: 999px;
      font-size: 11.5px;
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
      <div class="px-4 py-4 flex items-center justify-between app-brand-container border-b border-slate-800/80">
        <div class="flex items-center gap-3 overflow-hidden">
          <div class="w-10 h-10 rounded-xl overflow-hidden shadow-md shrink-0 border border-teal-400/30 bg-slate-900 flex items-center justify-center ring-2 ring-teal-500/15">
            <img src="/HealthLogs/public/assets/images/logo.jpeg" alt="HealthLogs Logo" class="w-full h-full object-cover">
          </div>
          <div class="app-brand-text overflow-hidden">
            <div class="app-brand text-lg font-bold text-white tracking-tight truncate flex items-center gap-1.5">
              <span>HealthLogs</span>
              <span class="w-1.5 h-1.5 rounded-full bg-teal-400 inline-block"></span>
            </div>
            <div class="app-brand-badge mt-0.5">Brgy. Tangcul EHR</div>
          </div>
        </div>
        <button id="sidebarCollapseBtn" type="button" class="hidden md:inline-flex text-slate-400 hover:text-teal-300 p-1.5 rounded-lg hover:bg-slate-800/80 transition shrink-0 cursor-pointer" title="Collapse / Expand Sidebar">
          <i class="fas fa-angles-left text-xs transition-transform duration-200" id="collapseIcon"></i>
        </button>
      </div>

      <nav class="flex-1 px-3 py-2 space-y-0.5 overflow-y-auto">
        <div class="nav-section">Clinical Core</div>
        <a class="nav-link <?= $isDashboard ? 'active' : '' ?>" href="/HealthLogs/public/index.php" title="Dashboard">
          <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>
          <span class="nav-text">Dashboard</span>
        </a>
        <a class="nav-link <?= $isActive('/HealthLogs/public/patients') ? 'active' : '' ?>" href="/HealthLogs/public/patients/index.php" title="Patient Records">
          <span class="nav-icon"><i class="fas fa-hospital-user"></i></span>
          <span class="nav-text">Patient Records</span>
        </a>
        <a class="nav-link <?= $isActive('/HealthLogs/public/appointments') ? 'active' : '' ?>" href="/HealthLogs/public/appointments/index.php" title="Appointments">
          <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
          <span class="nav-text">Appointments</span>
        </a>

        <div class="nav-section">Health Programs</div>
        <?php
          $programsOpen = $isActive('/HealthLogs/public/immunization')
            || $isActive('/HealthLogs/public/maternal')
            || $isActive('/HealthLogs/public/family_planning')
            || $isActive('/HealthLogs/public/ncd')
            || $isActive('/HealthLogs/public/tb');
          $inventoryOpen = $currentPath === '/HealthLogs/public/inventory.php'
            || $isActive('/HealthLogs/public/inventory/')
            || $isActive('/HealthLogs/public/medicine_supplies');
        ?>
        <!-- Programs collapsible group -->
        <button type="button" id="navProgramsToggle"
          class="nav-link w-full text-left cursor-pointer <?= $programsOpen ? 'active' : '' ?>"
          aria-expanded="<?= $programsOpen ? 'true' : 'false' ?>"
          title="Health Programs">
          <span class="nav-icon"><i class="fas fa-notes-medical"></i></span>
          <span class="nav-text flex-1">Programs</span>
          <i class="fas fa-chevron-down nav-text text-[10px] text-slate-400 transition-transform duration-200 <?= $programsOpen ? 'rotate-180 text-teal-300' : '' ?>" id="navProgramsChevron"></i>
        </button>
        <div id="navProgramsMenu" class="<?= $programsOpen ? '' : 'hidden' ?> nav-submenu space-y-0.5 mt-0.5">
          <a class="nav-link text-xs <?= $isActive('/HealthLogs/public/immunization') ? 'active' : '' ?>" href="/HealthLogs/public/immunization/records/index.php" title="Child Immunization (EPI)">
            <span class="nav-icon"><i class="fas fa-syringe text-[11px]"></i></span>
            <span class="nav-text">Immunization</span>
          </a>
          <a class="nav-link text-xs <?= $isActive('/HealthLogs/public/maternal') ? 'active' : '' ?>" href="/HealthLogs/public/maternal.php" title="Maternal Health (ANC/PNC)">
            <span class="nav-icon"><i class="fas fa-person-pregnant text-[11px]"></i></span>
            <span class="nav-text">Maternal Health</span>
          </a>
          <a class="nav-link text-xs <?= $isActive('/HealthLogs/public/family_planning') ? 'active' : '' ?>" href="/HealthLogs/public/family_planning.php" title="Family Planning">
            <span class="nav-icon"><i class="fas fa-people-roof text-[11px]"></i></span>
            <span class="nav-text">Family Planning</span>
          </a>
          <a class="nav-link text-xs <?= $isActive('/HealthLogs/public/ncd') ? 'active' : '' ?>" href="/HealthLogs/public/ncd/records/index.php" title="Non-Communicable Diseases (PhilPEN)">
            <span class="nav-icon"><i class="fas fa-heart-pulse text-[11px]"></i></span>
            <span class="nav-text">Non-Communicable</span>
          </a>
        </div>

        <!-- Inventory collapsible group -->
        <button type="button" id="navInventoryToggle"
          class="nav-link w-full text-left cursor-pointer <?= $inventoryOpen ? 'active' : '' ?>"
          aria-expanded="<?= $inventoryOpen ? 'true' : 'false' ?>"
          title="Pharmacy & Inventory">
          <span class="nav-icon"><i class="fas fa-boxes-stacked"></i></span>
          <span class="nav-text flex-1">Inventory</span>
          <i class="fas fa-chevron-down nav-text text-[10px] text-slate-400 transition-transform duration-200 <?= $inventoryOpen ? 'rotate-180 text-teal-300' : '' ?>" id="navInventoryChevron"></i>
        </button>
        <div id="navInventoryMenu" class="<?= $inventoryOpen ? '' : 'hidden' ?> nav-submenu space-y-0.5 mt-0.5">
          <a class="nav-link text-xs <?= ($currentPath === '/HealthLogs/public/inventory.php' || $isActive('/HealthLogs/public/inventory/medicines')) ? 'active' : '' ?>" href="/HealthLogs/public/inventory.php" title="Medicine Catalog">
            <span class="nav-icon"><i class="fas fa-pills text-[11px]"></i></span>
            <span class="nav-text">Medicines</span>
          </a>
          <a class="nav-link text-xs <?= ($isActive('/HealthLogs/public/medicine_supplies') || $isActive('/HealthLogs/public/inventory/batches') || $isActive('/HealthLogs/public/inventory/transactions')) ? 'active' : '' ?>" href="/HealthLogs/public/medicine_supplies.php" title="Supply & Batches">
            <span class="nav-icon"><i class="fas fa-box-open text-[11px]"></i></span>
            <span class="nav-text">Supply &amp; Batches</span>
          </a>
        </div>

        <a class="nav-link <?= $isActive('/HealthLogs/public/reminders') ? 'active' : '' ?>" href="/HealthLogs/public/reminders.php" title="SMS Reminders">
          <span class="nav-icon"><i class="fas fa-bell"></i></span>
          <span class="nav-text">Reminders</span>
        </a>

        <?php if (($_SESSION['role'] ?? 'health_worker') === 'admin'): ?>
          <div class="nav-section">Administration</div>
          <a class="nav-link <?= $isActive('/HealthLogs/public/reports') ? 'active' : '' ?>" href="/HealthLogs/public/reports.php" title="Reports">
            <span class="nav-icon"><i class="fas fa-file-waveform"></i></span>
            <span class="nav-text">Reports</span>
          </a>
          <a class="nav-link <?= $isActive('/HealthLogs/public/users') ? 'active' : '' ?>" href="/HealthLogs/public/users.php" title="User Management">
            <span class="nav-icon"><i class="fas fa-user-doctor"></i></span>
            <span class="nav-text">User Management</span>
          </a>
          <a class="nav-link <?= $isActive('/HealthLogs/public/forecast') ? 'active' : '' ?>" href="/HealthLogs/public/forecast.php" title="SARIMA & Demand Forecasting">
            <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
            <span class="nav-text">Forecasting</span>
          </a>
          <a class="nav-link <?= $isActive('/HealthLogs/public/activity_logs') ? 'active' : '' ?>" href="/HealthLogs/public/activity_logs.php" title="Security & Activity Logs">
            <span class="nav-icon"><i class="fas fa-shield-halved"></i></span>
            <span class="nav-text">Activity Logs</span>
          </a>
        <?php endif; ?>
      </nav>

      <div class="px-3 pb-4 mt-auto pt-3 border-t border-slate-800/80 space-y-2">
        <div class="sidebar-status-card px-3 py-2 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center justify-between text-[11px]">
          <div class="flex items-center gap-2 text-slate-300 font-medium">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>EHR Hub Active</span>
          </div>
          <span class="text-[10px] font-mono text-teal-400">ONLINE</span>
        </div>
        <a class="nav-link text-rose-300 hover:text-rose-100 hover:bg-rose-950/40" href="/HealthLogs/public/logout.php" title="Logout">
          <span class="nav-icon !bg-rose-950/60 !border-rose-800/40 !text-rose-300"><i class="fas fa-right-from-bracket"></i></span>
          <span class="nav-text font-semibold">Sign Out</span>
        </a>
      </div>
    </aside>

    <main class="app-main min-w-0">
      <header class="app-topbar">
        <div class="w-full px-3 sm:px-5 md:px-6 py-3 flex items-center justify-between gap-3">
          <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            <button id="sidebarToggle" aria-label="Toggle navigation" class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 shadow-2xs shrink-0 transition" title="Toggle Sidebar">
              <i class="fas fa-bars text-sm"></i>
            </button>
            <?php if (!$isDashboard): ?>
              <button type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '/HealthLogs/public/index.php'; }" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-teal-50/60 hover:border-teal-300 text-slate-700 hover:text-teal-800 text-xs font-semibold shadow-2xs transition shrink-0 cursor-pointer" title="Go back to previous page">
                <i class="fas fa-arrow-left text-[11px] text-teal-600"></i>
                <span class="hidden sm:inline">Back</span>
              </button>
            <?php endif; ?>
            <div class="min-w-0">
              <div class="hidden sm:flex items-center gap-1.5 text-[11px] font-medium text-slate-400 leading-none mb-0.5">
                <a href="/HealthLogs/public/index.php" class="hover:text-teal-600 transition">Brgy. Tangcul EHR</a>
                <i class="fas fa-chevron-right text-[8px] text-slate-300"></i>
                <span class="text-teal-700 font-semibold"><?= h($breadcrumbSection) ?></span>
              </div>
              <div class="app-title text-base sm:text-lg font-bold text-slate-900 truncate leading-tight"><?= h($pageTitle ?? 'Dashboard') ?></div>
            </div>
          </div>

          <div class="flex items-center gap-2 sm:gap-2.5 text-sm shrink-0">
            <!-- Quick Clinical Action Shortcuts -->
            <div class="hidden lg:flex items-center gap-1.5 mr-1">
              <a href="/HealthLogs/public/patients/index.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100/80 text-teal-800 border border-teal-200/80 text-xs font-semibold transition" title="Open Patient Registry">
                <i class="fas fa-hospital-user text-teal-600 text-[11px]"></i>
                <span>Patients</span>
              </a>
              <a href="/HealthLogs/public/appointments/index.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold transition" title="View Clinical Appointments">
                <i class="fas fa-calendar-check text-slate-500 text-[11px]"></i>
                <span>Schedule</span>
              </a>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-100/80 text-slate-600 text-[11px] font-medium border border-slate-200/60">
                <i class="far fa-calendar text-teal-600"></i>
                <span><?= date('M d, Y') ?></span>
              </span>
            </div>

            <?php
              $headerNearExpiryAlerts = class_exists('ActivityLogger') ? ActivityLogger::getNearExpiryAlerts(60) : [];
              $headerLowStockAlerts = class_exists('ActivityLogger') && method_exists('ActivityLogger', 'getLowStockAlerts') ? ActivityLogger::getLowStockAlerts() : [];
              $headerExpiredAlerts = class_exists('ActivityLogger') ? ActivityLogger::getExpiredStockAlerts() : [];

              $nearCount = count($headerNearExpiryAlerts);
              $lowCount = count($headerLowStockAlerts);
              $expCount = count($headerExpiredAlerts);
              $headerTotalAlerts = $nearCount + $lowCount + $expCount;

              $defaultNotifTab = 'near';
              if ($nearCount > 0) {
                  $defaultNotifTab = 'near';
              } elseif ($lowCount > 0) {
                  $defaultNotifTab = 'low';
              } elseif ($expCount > 0) {
                  $defaultNotifTab = 'expired';
              }

              $sigPayload = [
                  'near' => array_map(fn($r) => $r['id'] . ':' . $r['on_hand'], $headerNearExpiryAlerts),
                  'low' => array_map(fn($r) => $r['medicine_id'] . ':' . $r['on_hand'], $headerLowStockAlerts),
                  'exp' => array_map(fn($r) => $r['id'] . ':' . $r['on_hand'], $headerExpiredAlerts),
              ];
              $headerAlertSignature = substr(md5(json_encode($sigPayload)), 0, 12);

              $primaryExpiredAlerts = array_slice($headerExpiredAlerts, 0, 4);
              $legacyExpiredAlerts = array_slice($headerExpiredAlerts, 4);
              $canManageInventoryNotif = function_exists('can_manage_clinical_records') && can_manage_clinical_records();
            ?>

            <!-- Expiry & Stock Notification Bell -->
            <div class="relative">
              <button id="expiryNotificationBtn" type="button" data-alert-sig="<?= h($headerAlertSignature) ?>" class="relative inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white/90 hover:bg-slate-100 text-slate-700 transition shadow-xs" title="Medicine & Inventory Alerts">
                <i class="fas fa-bell text-sm text-slate-600"></i>
                <?php if ($headerTotalAlerts > 0): ?>
                  <span id="expiryNotificationBadge" data-default-bg="<?= $expCount > 0 ? 'bg-rose-600' : 'bg-amber-500' ?>" class="absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full text-[10px] font-bold text-white <?= $expCount > 0 ? 'bg-rose-600' : 'bg-amber-500' ?> shadow-xs transition-colors">
                    <?= $headerTotalAlerts ?>
                  </span>
                <?php endif; ?>
              </button>

              <!-- Notifications Dropdown Panel -->
              <div id="expiryNotificationDropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-[410px] rounded-2xl bg-white shadow-2xl border border-slate-200/90 z-50 overflow-hidden text-xs">
                <!-- Header -->
                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="font-bold text-slate-900 text-sm truncate">Inventory Alerts</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 <?= $expCount > 0 ? 'bg-rose-100 text-rose-800' : ($headerTotalAlerts > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') ?>">
                      <?= $headerTotalAlerts ?> Alert<?= $headerTotalAlerts !== 1 ? 's' : '' ?>
                    </span>
                  </div>
                  <div class="flex items-center gap-2.5 shrink-0">
                    <?php if ($headerTotalAlerts > 0): ?>
                      <button type="button" id="markNotifSeenBtn" class="text-slate-500 hover:text-teal-700 font-semibold text-[11px] inline-flex items-center gap-1 transition" title="Dim notification badge until new alerts occur">
                        <i class="fas fa-check-double text-[10px]"></i> <span id="markNotifSeenLabel">Mark Seen</span>
                      </button>
                    <?php endif; ?>
                    <a href="/HealthLogs/public/inventory/batches/index.php" class="text-teal-600 hover:text-teal-700 font-semibold text-[11px]">View All</a>
                  </div>
                </div>

                <?php if ($headerTotalAlerts > 0): ?>
                  <!-- Category Filter Tabs -->
                  <div class="grid grid-cols-3 gap-1 p-1.5 bg-slate-100/80 border-b border-slate-200/70 text-[11px] font-semibold">
                    <button type="button" data-notif-tab="near" class="notif-tab-btn flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-lg transition <?= $defaultNotifTab === 'near' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' ?>">
                      <i class="fas fa-hourglass-half text-amber-500 text-[10px]"></i>
                      <span>Near Expiry</span>
                      <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $nearCount > 0 ? 'bg-amber-100 text-amber-800 font-bold' : 'bg-slate-200/70 text-slate-500' ?>"><?= $nearCount ?></span>
                    </button>
                    <button type="button" data-notif-tab="low" class="notif-tab-btn flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-lg transition <?= $defaultNotifTab === 'low' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' ?>">
                      <i class="fas fa-boxes-stacked text-orange-500 text-[10px]"></i>
                      <span>Low Stock</span>
                      <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $lowCount > 0 ? 'bg-orange-100 text-orange-800 font-bold' : 'bg-slate-200/70 text-slate-500' ?>"><?= $lowCount ?></span>
                    </button>
                    <button type="button" data-notif-tab="expired" class="notif-tab-btn flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-lg transition <?= $defaultNotifTab === 'expired' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' ?>">
                      <i class="fas fa-ban text-rose-500 text-[10px]"></i>
                      <span>Expired</span>
                      <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $expCount > 0 ? 'bg-rose-100 text-rose-800 font-bold' : 'bg-slate-200/70 text-slate-500' ?>"><?= $expCount ?></span>
                    </button>
                  </div>
                <?php endif; ?>

                <div class="max-h-80 overflow-y-auto">
                  <?php if ($headerTotalAlerts === 0): ?>
                    <div class="p-6 text-center text-slate-400">
                      <i class="fas fa-circle-check text-2xl text-emerald-500 mb-2 block"></i>
                      <div class="font-semibold text-slate-700">All Inventory Healthy</div>
                      <div class="text-[11px] text-slate-400 mt-0.5">No near-expiry, low-stock, or expired batches found.</div>
                    </div>
                  <?php else: ?>
                    <!-- TAB 1: Near Expiry Batches (FEFO Priority) -->
                    <div data-notif-panel="near" class="divide-y divide-slate-100 <?= $defaultNotifTab === 'near' ? '' : 'hidden' ?>">
                      <?php if ($nearCount === 0): ?>
                        <div class="p-6 text-center text-slate-400">
                          <i class="fas fa-check-circle text-xl text-emerald-500 mb-1.5 block"></i>
                          <div class="font-semibold text-slate-700">No Near-Expiry Batches</div>
                          <div class="text-[11px] text-slate-400 mt-0.5">No active batches are expiring within the next 60 days.</div>
                        </div>
                      <?php else: ?>
                        <div class="px-3.5 py-2 bg-amber-50/70 border-b border-amber-100 flex items-center justify-between text-[11px] text-amber-800">
                          <span><i class="fas fa-bolt mr-1 text-amber-600"></i> Prioritize these batches first (FEFO)</span>
                          <a href="/HealthLogs/public/inventory/batches/index.php?expiry_status=near_expiry" class="font-bold text-amber-900 hover:underline">Filter List</a>
                        </div>
                        <?php foreach ($headerNearExpiryAlerts as $near): ?>
                          <div class="p-3 hover:bg-amber-50/40 flex items-start gap-2.5 transition">
                            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 mt-0.5">
                              <i class="fas fa-triangle-exclamation text-xs"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                              <div class="flex items-center justify-between gap-1">
                                <a href="/HealthLogs/public/inventory/batches/index.php?expiry_status=near_expiry&q=<?= urlencode($near['batch_no']) ?>" class="font-bold text-slate-900 hover:text-teal-700 truncate">
                                  <?= h($near['medicine_name']) ?>
                                </a>
                                <span class="px-1.5 py-0.5 rounded bg-amber-100 text-[10px] font-bold text-amber-800 uppercase shrink-0">
                                  <?= (int)$near['days_remaining'] === 0 ? 'Expires Today' : (int)$near['days_remaining'] . 'd left' ?>
                                </span>
                              </div>
                              <div class="text-[11px] text-slate-500 mt-0.5">
                                Batch <span class="font-mono font-semibold text-slate-700"><?= h($near['batch_no']) ?></span> • <span class="font-semibold text-slate-800"><?= number_format($near['on_hand']) ?> <?= h($near['unit'] ?? 'units') ?></span>
                              </div>
                              <div class="flex items-center justify-between gap-2 mt-1">
                                <span class="text-[10px] text-amber-700">
                                  Expires <?= date('M d, Y', strtotime($near['expiry_date'])) ?> • <em>FEFO Priority</em>
                                </span>
                                <a href="/HealthLogs/public/inventory/batches/index.php?expiry_status=near_expiry&q=<?= urlencode($near['batch_no']) ?>" class="text-[10px] font-bold text-teal-700 hover:text-teal-800 shrink-0">
                                  Inspect →
                                </a>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>

                    <!-- TAB 2: Low Stock Medicines -->
                    <div data-notif-panel="low" class="divide-y divide-slate-100 <?= $defaultNotifTab === 'low' ? '' : 'hidden' ?>">
                      <?php if ($lowCount === 0): ?>
                        <div class="p-6 text-center text-slate-400">
                          <i class="fas fa-check-circle text-xl text-emerald-500 mb-1.5 block"></i>
                          <div class="font-semibold text-slate-700">Stock Levels Healthy</div>
                          <div class="text-[11px] text-slate-400 mt-0.5">All medicines are above their minimum reorder level.</div>
                        </div>
                      <?php else: ?>
                        <div class="px-3.5 py-2 bg-orange-50/70 border-b border-orange-100 flex items-center justify-between text-[11px] text-orange-800">
                          <span><i class="fas fa-arrow-trend-down mr-1 text-orange-600"></i> At or below minimum reorder threshold</span>
                          <a href="/HealthLogs/public/inventory/medicines/index.php?stock=low" class="font-bold text-orange-900 hover:underline">View Low Stock</a>
                        </div>
                        <?php foreach ($headerLowStockAlerts as $low): ?>
                          <?php $isZeroStock = (int)$low['on_hand'] <= 0; ?>
                          <div class="p-3 hover:bg-orange-50/40 flex items-start gap-2.5 transition">
                            <div class="w-7 h-7 rounded-lg <?= $isZeroStock ? 'bg-rose-100 text-rose-600' : 'bg-orange-100 text-orange-600' ?> flex items-center justify-center shrink-0 mt-0.5">
                              <i class="fas <?= $isZeroStock ? 'fa-circle-exclamation' : 'fa-box-open' ?> text-xs"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                              <div class="flex items-center justify-between gap-1">
                                <a href="/HealthLogs/public/inventory/medicines/index.php?q=<?= urlencode($low['medicine_name']) ?>" class="font-bold text-slate-900 hover:text-teal-700 truncate">
                                  <?= h($low['medicine_name']) ?>
                                </a>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase shrink-0 <?= $isZeroStock ? 'bg-rose-100 text-rose-800' : 'bg-orange-100 text-orange-800' ?>">
                                  <?= $isZeroStock ? 'Out of Stock' : 'Low Stock' ?>
                                </span>
                              </div>
                              <div class="text-[11px] text-slate-600 mt-0.5">
                                Available: <span class="font-bold <?= $isZeroStock ? 'text-rose-600' : 'text-orange-700' ?>"><?= number_format($low['on_hand']) ?> <?= h($low['unit'] ?? 'units') ?></span>
                                • Reorder Level: <span class="font-semibold text-slate-700"><?= number_format($low['reorder_level']) ?></span>
                              </div>
                              <div class="flex items-center justify-between gap-2 mt-1">
                                <span class="text-[10px] text-slate-400">Excludes any expired batches</span>
                                <a href="/HealthLogs/public/inventory/batches/index.php?q=<?= urlencode($low['medicine_name']) ?>" class="text-[10px] font-bold text-teal-700 hover:text-teal-800 shrink-0">
                                  Restock Batch →
                                </a>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>

                    <!-- TAB 3: Expired Batches (Quarantined / Needs Write-Off) -->
                    <div data-notif-panel="expired" class="divide-y divide-slate-100 <?= $defaultNotifTab === 'expired' ? '' : 'hidden' ?>">
                      <?php if ($expCount === 0): ?>
                        <div class="p-6 text-center text-slate-400">
                          <i class="fas fa-check-circle text-xl text-emerald-500 mb-1.5 block"></i>
                          <div class="font-semibold text-slate-700">No Unresolved Expired Stock</div>
                          <div class="text-[11px] text-slate-400 mt-0.5">All expired batches have been written off from physical inventory.</div>
                        </div>
                      <?php else: ?>
                        <!-- Bulk Write-Off Action Bar -->
                        <div class="px-3.5 py-2 bg-rose-50/80 border-b border-rose-100 flex items-center justify-between gap-2">
                          <span class="text-[11px] text-rose-800 font-medium">
                            <i class="fas fa-shield-halved mr-1 text-rose-600"></i> <?= $expCount ?> expired batch<?= $expCount !== 1 ? 'es' : '' ?> barred from issuance
                          </span>
                          <?php if ($canManageInventoryNotif): ?>
                            <form method="post" action="/HealthLogs/public/inventory/batches/write_off_expired.php" class="inline shrink-0" data-confirm="Write off and dispose all <?= $expCount ?> expired batches from physical stock? Each disposal will be recorded in the Medicine Audit Trail." data-confirm-title="Write Off All Expired Batches" data-confirm-cta="Yes, write off all">
                              <input type="hidden" name="write_off_all" value="1" />
                              <button type="submit" class="px-2 py-1 rounded-md bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold transition shadow-2xs">
                                <i class="fas fa-trash-can mr-1"></i> Write Off All (<?= $expCount ?>)
                              </button>
                            </form>
                          <?php else: ?>
                            <a href="/HealthLogs/public/inventory/batches/index.php?expiry_status=expired&availability=in_stock" class="text-[11px] font-bold text-rose-800 hover:underline shrink-0">Review All</a>
                          <?php endif; ?>
                        </div>

                        <?php foreach ($primaryExpiredAlerts as $exp): ?>
                          <div class="p-3 hover:bg-rose-50/40 flex items-start gap-2.5 transition">
                            <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                              <i class="fas fa-ban text-xs"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                              <div class="flex items-center justify-between gap-1">
                                <a href="/HealthLogs/public/inventory/batches/index.php?expiry_status=expired&q=<?= urlencode($exp['batch_no']) ?>" class="font-bold text-slate-900 hover:text-rose-700 truncate">
                                  <?= h($exp['medicine_name']) ?>
                                </a>
                                <span class="text-[10px] font-bold text-rose-600 uppercase shrink-0">Expired</span>
                              </div>
                              <div class="text-[11px] text-slate-500 mt-0.5">
                                Batch <span class="font-mono font-semibold text-slate-700"><?= h($exp['batch_no']) ?></span> • <span class="font-semibold text-rose-600"><?= number_format($exp['on_hand']) ?> <?= h($exp['unit'] ?? 'units') ?></span>
                              </div>
                              <div class="flex items-center justify-between gap-2 mt-1">
                                <span class="text-[10px] text-rose-500">
                                  Expired <?= (int)$exp['days_expired'] ?>d ago (<?= date('M d, Y', strtotime($exp['expiry_date'])) ?>)
                                </span>
                                <?php if ($canManageInventoryNotif): ?>
                                  <form method="post" action="/HealthLogs/public/inventory/batches/write_off_expired.php" class="inline shrink-0" data-confirm="Write off <?= number_format($exp['on_hand']) ?> <?= h($exp['unit'] ?? 'units') ?> of expired <?= h($exp['medicine_name']) ?> (Batch <?= h($exp['batch_no']) ?>)?" data-confirm-title="Write Off Expired Batch" data-confirm-cta="Yes, write off">
                                    <input type="hidden" name="batch_id" value="<?= (int)$exp['id'] ?>" />
                                    <button type="submit" class="px-2 py-0.5 rounded border border-rose-200 bg-white hover:bg-rose-600 hover:text-white text-rose-600 text-[10px] font-bold transition">
                                      Write Off
                                    </button>
                                  </form>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>

                        <?php if (!empty($legacyExpiredAlerts)): ?>
                          <div class="bg-slate-50/70">
                            <button type="button" id="toggleLegacyExpiredBtn" class="w-full px-3.5 py-2 text-left text-[11px] font-semibold text-slate-600 hover:text-slate-900 flex items-center justify-between transition">
                              <span><i class="fas fa-layer-group mr-1.5 text-slate-400"></i> Show <?= count($legacyExpiredAlerts) ?> older expired batch<?= count($legacyExpiredAlerts) !== 1 ? 'es' : '' ?></span>
                              <i id="toggleLegacyExpiredIcon" class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform"></i>
                            </button>
                            <div id="legacyExpiredList" class="hidden divide-y divide-slate-100 border-t border-slate-100">
                              <?php foreach ($legacyExpiredAlerts as $exp): ?>
                                <div class="p-3 hover:bg-rose-50/40 flex items-start gap-2.5 transition">
                                  <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                                    <i class="fas fa-ban text-xs"></i>
                                  </div>
                                  <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-1">
                                      <a href="/HealthLogs/public/inventory/batches/index.php?expiry_status=expired&q=<?= urlencode($exp['batch_no']) ?>" class="font-bold text-slate-900 hover:text-rose-700 truncate">
                                        <?= h($exp['medicine_name']) ?>
                                      </a>
                                      <span class="text-[10px] font-bold text-rose-600 uppercase shrink-0">Expired</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                      Batch <span class="font-mono font-semibold text-slate-700"><?= h($exp['batch_no']) ?></span> • <span class="font-semibold text-rose-600"><?= number_format($exp['on_hand']) ?> <?= h($exp['unit'] ?? 'units') ?></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 mt-1">
                                      <span class="text-[10px] text-rose-500">
                                        Expired <?= (int)$exp['days_expired'] ?>d ago (<?= date('M d, Y', strtotime($exp['expiry_date'])) ?>)
                                      </span>
                                      <?php if ($canManageInventoryNotif): ?>
                                        <form method="post" action="/HealthLogs/public/inventory/batches/write_off_expired.php" class="inline shrink-0" data-confirm="Write off <?= number_format($exp['on_hand']) ?> <?= h($exp['unit'] ?? 'units') ?> of expired <?= h($exp['medicine_name']) ?> (Batch <?= h($exp['batch_no']) ?>)?" data-confirm-title="Write Off Expired Batch" data-confirm-cta="Yes, write off">
                                          <input type="hidden" name="batch_id" value="<?= (int)$exp['id'] ?>" />
                                          <button type="submit" class="px-2 py-0.5 rounded border border-rose-200 bg-white hover:bg-rose-600 hover:text-white text-rose-600 text-[10px] font-bold transition">
                                            Write Off
                                          </button>
                                        </form>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="p-2 border-t border-slate-100 bg-slate-50 flex items-center justify-between px-3.5">
                  <a href="/HealthLogs/public/inventory/batches/index.php" class="text-slate-600 hover:text-slate-900 font-semibold text-[11px] py-1">
                    <i class="fas fa-boxes-stacked mr-1 text-slate-500"></i> Manage Batches
                  </a>
                  <a href="/HealthLogs/public/inventory/audit_trail.php" class="text-slate-600 hover:text-slate-900 font-semibold text-[11px] py-1">
                    <i class="fas fa-clipboard-check mr-1 text-teal-600"></i> Open Medicine Audit Trail
                  </a>
                </div>
              </div>
            </div>

            <!-- User pill -->
            <div class="flex items-center gap-2.5 pl-2.5 border-l border-slate-200">
              <div class="relative">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-teal-600 to-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase shadow-2xs ring-2 ring-teal-500/20">
                  <?= h(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
              </div>
              <div class="hidden sm:block text-left">
                <div class="text-xs font-bold text-slate-900 leading-tight"><?= h($_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'User')) ?></div>
                <div class="text-[10px] text-teal-700 uppercase tracking-wider font-bold"><?= h(str_replace('_', ' ', $_SESSION['role'] ?? 'Staff')) ?></div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <script>
      (function() {
        const notifBtn = document.getElementById('expiryNotificationBtn');
        const notifBadge = document.getElementById('expiryNotificationBadge');
        const notifDropdown = document.getElementById('expiryNotificationDropdown');
        const markSeenBtn = document.getElementById('markNotifSeenBtn');
        const markSeenLabel = document.getElementById('markNotifSeenLabel');
        const alertSig = notifBtn ? notifBtn.getAttribute('data-alert-sig') : '';

        function applySeenState(seen) {
          if (!notifBadge) return;
          const defaultBg = notifBadge.getAttribute('data-default-bg') || 'bg-rose-600';
          if (seen) {
            notifBadge.classList.remove('bg-rose-600', 'bg-amber-500', 'text-white');
            notifBadge.classList.add('bg-slate-200', 'text-slate-600');
            if (markSeenLabel) markSeenLabel.textContent = 'Seen';
          } else {
            notifBadge.classList.remove('bg-slate-200', 'text-slate-600');
            notifBadge.classList.add(defaultBg, 'text-white');
            if (markSeenLabel) markSeenLabel.textContent = 'Mark Seen';
          }
        }

        if (alertSig && localStorage.getItem('hl_seen_alert_sig') === alertSig) {
          applySeenState(true);
        }

        if (markSeenBtn && alertSig) {
          markSeenBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            localStorage.setItem('hl_seen_alert_sig', alertSig);
            applySeenState(true);
          });
        }

        if (notifBtn && notifDropdown) {
          notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpening = notifDropdown.classList.contains('hidden');
            notifDropdown.classList.toggle('hidden');
            if (isOpening && alertSig) {
              localStorage.setItem('hl_seen_alert_sig', alertSig);
              applySeenState(true);
            }
          });
          document.addEventListener('click', function(e) {
            if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
              notifDropdown.classList.add('hidden');
            }
          });
        }

        // Notification Dropdown Category Tabs
        const tabBtns = document.querySelectorAll('[data-notif-tab]');
        const tabPanels = document.querySelectorAll('[data-notif-panel]');
        tabBtns.forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const targetTab = btn.getAttribute('data-notif-tab');
            tabBtns.forEach(function(b) {
              const active = b.getAttribute('data-notif-tab') === targetTab;
              b.classList.toggle('bg-white', active);
              b.classList.toggle('text-slate-900', active);
              b.classList.toggle('shadow-2xs', active);
              b.classList.toggle('text-slate-600', !active);
            });
            tabPanels.forEach(function(p) {
              p.classList.toggle('hidden', p.getAttribute('data-notif-panel') !== targetTab);
            });
          });
        });

        // Collapsible legacy expired batches inside dropdown
        const toggleLegacyBtn = document.getElementById('toggleLegacyExpiredBtn');
        const legacyList = document.getElementById('legacyExpiredList');
        const toggleLegacyIcon = document.getElementById('toggleLegacyExpiredIcon');
        if (toggleLegacyBtn && legacyList) {
          toggleLegacyBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const hidden = legacyList.classList.toggle('hidden');
            if (toggleLegacyIcon) toggleLegacyIcon.classList.toggle('rotate-180', !hidden);
          });
        }

        // Collapsible nav groups
        [['navProgramsToggle','navProgramsMenu','navProgramsChevron'],
         ['navInventoryToggle','navInventoryMenu','navInventoryChevron']].forEach(function(ids) {
          var btn = document.getElementById(ids[0]);
          var menu = document.getElementById(ids[1]);
          var chevron = document.getElementById(ids[2]);
          if (!btn || !menu) return;
          btn.addEventListener('click', function() {
            var open = !menu.classList.contains('hidden');
            menu.classList.toggle('hidden', open);
            if (chevron) chevron.classList.toggle('rotate-180', !open);
            btn.setAttribute('aria-expanded', String(!open));
          });
        });
      })();
      </script>


      <section class="w-full px-3 sm:px-4 md:px-6 py-4 md:py-6 app-content max-w-full">
