<?php
require_once __DIR__ . '/../config/db.php';

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /HealthLogs/public/index.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - HealthLogs</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
    body.app-body {
      font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif;
      color: var(--ink);
      background:
        radial-gradient(1200px 600px at 10% -10%, var(--bg-1), transparent 60%),
        radial-gradient(1000px 500px at 100% 0%, var(--bg-2), transparent 55%),
        #f8fafc;
      margin: 0;
      overflow-x: hidden;
    }
    .login-card {
      background: var(--card);
      border: 1px solid var(--line);
      box-shadow: var(--shadow);
      border-radius: 18px;
    }
    .hero-orb {
      position: absolute;
      width: 420px;
      height: 420px;
      border-radius: 999px;
      background: radial-gradient(circle at 30% 30%, rgba(37,99,235,0.25), transparent 60%),
        radial-gradient(circle at 60% 60%, rgba(14,165,164,0.2), transparent 65%);
      filter: blur(0);
      opacity: 0.9;
    }
    .login-shell {
      position: relative;
      isolation: isolate;
    }
    .brand {
      font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
      letter-spacing: 0.02em;
    }
    .login-card input {
      background: rgba(248, 250, 252, 0.9);
      border: 1px solid var(--line);
      border-radius: 12px;
    }
    .login-card input:focus {
      outline: 2px solid rgba(14,165,164,0.25);
      border-color: rgba(14,165,164,0.6);
    }
    .login-card label {
      font-weight: 500;
    }
  </style>
</head>
<body class="app-body">
  <div class="min-h-screen flex items-center justify-center px-4 py-10 login-shell">
    <span class="hero-orb -left-24 -top-32"></span>
    <span class="hero-orb -right-32 top-32"></span>

    <div class="login-card w-full max-w-5xl overflow-hidden relative">
      <div class="grid grid-cols-1 md:grid-cols-2">
        <div class="p-8 md:p-10">
          <div class="brand text-3xl font-semibold">HealthLogs</div>
          <p class="text-sm text-slate-500 mt-2">Secure access for Barangay Health Units.</p>

          <?php if ($error): ?>
            <div class="mt-4 text-sm text-red-600">Invalid username or password.</div>
          <?php endif; ?>

          <form method="post" action="/HealthLogs/public/auth.php" class="mt-6 space-y-4">
            <div>
              <label class="block text-sm text-slate-600">Username</label>
              <input name="username" required class="mt-1 w-full border rounded px-4 py-3" placeholder="e.g. admin" />
            </div>
            <div>
              <label class="block text-sm text-slate-600">Password</label>
              <input name="password" type="password" required class="mt-1 w-full border rounded px-4 py-3" placeholder="Enter password" />
            </div>
            <button class="bg-slate-900 text-white px-4 py-3 rounded-xl w-full shadow" type="submit">Login</button>
          </form>

          <div class="mt-6 text-xs text-slate-500">
            <div class="font-semibold text-slate-600">Sample Accounts</div>
            <div class="mt-1">Superadmin: <span class="font-mono">superadmin</span> / <span class="font-mono">superadmin123</span></div>
            <div>Admin: <span class="font-mono">admin</span> / <span class="font-mono">admin123</span></div>
            <div>Health Worker: <span class="font-mono">bhw</span> / <span class="font-mono">bhw123</span></div>
          </div>
        </div>

        <div class="hidden md:flex flex-col justify-between p-8 md:p-10" style="background: linear-gradient(145deg, rgba(14,165,164,0.18), rgba(37,99,235,0.18));">
          <div>
            <div class="text-xs uppercase tracking-widest text-slate-500">Health Insights</div>
            <div class="text-2xl font-semibold mt-2">Connected BHU Care</div>
            <p class="text-sm text-slate-600 mt-2">
              Track immunization, maternal health, TB monitoring, and medicine inventory
              in one centralized dashboard.
            </p>
          </div>
          <div class="text-xs text-slate-500">
            Secure. Organized. Ready for forecasting.
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
