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
    
    body.app-body {
      font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif;
      color: var(--ink);
      background:
        radial-gradient(1200px 600px at 10% -10%, var(--bg-1), transparent 60%),
        radial-gradient(1000px 500px at 100% 0%, var(--bg-2), transparent 55%),
        #f8fafc;
      margin: 0;
      overflow-x: hidden;
      min-height: 100vh;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
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
      width: 100%;
      min-height: 100vh;
    }
    
    .brand {
      font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
      letter-spacing: 0.02em;
    }

    .brand-mark {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(145deg, rgba(37,99,235,0.16), rgba(14,165,164,0.2));
      border: 1px solid rgba(15, 23, 42, 0.08);
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .brand-mark svg {
      width: 28px;
      height: 28px;
      color: #0f4ccf;
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
    
    @media (max-width: 768px) {
      .hero-orb {
        width: 300px;
        height: 300px;
      }
      .hero-orb:first-of-type {
        left: -150px;
        top: -150px;
      }
      .hero-orb:last-of-type {
        right: -150px;
        top: 0;
      }
    }

    @media (max-width: 640px) {
      html,
      body.app-body {
        height: 100dvh;
        min-height: 100dvh;
        overflow: hidden;
      }

      .login-shell {
        height: 100dvh;
        min-height: 100dvh;
        padding-top: 12px;
        padding-bottom: 12px;
        align-items: center;
        overflow: hidden;
      }

      .login-card {
        border-radius: 16px;
        width: min(100%, 420px);
        max-height: calc(100dvh - 24px);
        min-height: 0;
        overflow: hidden;
      }

      .login-card > .grid {
        display: block;
      }

      .login-card > .grid > :last-child {
        display: none !important;
      }

      .hero-orb {
        width: 220px;
        height: 220px;
        opacity: 0.65;
      }

      .hero-orb:first-of-type {
        left: -120px;
        top: -120px;
      }

      .hero-orb:last-of-type {
        right: -120px;
        top: 24px;
      }

      .login-card .p-6 {
        padding: 1.25rem;
      }

      .brand-mark {
        width: 46px;
        height: 46px;
        border-radius: 14px;
      }

      .brand-mark svg {
        width: 24px;
        height: 24px;
      }

      .login-card form {
        margin-bottom: 0;
      }
    }
  </style>
</head>
<body class="app-body">
  <div class="min-h-screen flex items-center justify-center px-3 py-3 sm:px-4 sm:py-10 login-shell">
    <span class="hero-orb -left-24 -top-32"></span>
    <span class="hero-orb -right-32 top-32"></span>

    <div class="login-card w-full max-w-md md:max-w-5xl overflow-hidden relative">
      <div class="grid grid-cols-1 md:grid-cols-2">
        <!-- Left Side: Login Form -->
        <div class="p-6 sm:p-8 md:p-10">
          <div class="brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 4v16"></path>
              <path d="M4 12h16"></path>
              <path d="M7 7h10v10H7z"></path>
            </svg>
          </div>
          <div class="brand text-2xl sm:text-3xl font-semibold text-slate-900">HealthLogs</div>
          <p class="text-xs sm:text-sm text-slate-600 mt-2">Secure access for Barangay Health Units.</p>

          <?php if ($error): ?>
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600 flex items-center gap-2">
              <i class="fas fa-exclamation-circle flex-shrink-0"></i>
              <span>Invalid username or password.</span>
            </div>
          <?php endif; ?>

          <form method="post" action="/HealthLogs/public/auth.php" class="mt-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700">Username</label>
              <input name="username" required class="mt-2 w-full px-4 py-2.5 sm:py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm sm:text-base" placeholder="e.g. admin" />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-slate-700">Password</label>
              <input name="password" type="password" required class="mt-2 w-full px-4 py-2.5 sm:py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm sm:text-base" placeholder="Enter password" />
            </div>
            
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 sm:py-3 rounded-lg font-medium shadow-md hover:shadow-lg transition-all mt-6 text-sm sm:text-base" type="submit">
              <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
          </form>

          <?php if (getenv('APP_ENV') === 'development'): ?>
          <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg text-xs sm:text-sm text-amber-900">
            <div class="font-semibold flex items-center gap-2 mb-2">
              <i class="fas fa-info-circle flex-shrink-0"></i>
              Sample Accounts (Dev Only)
            </div>
            <div class="space-y-1 font-mono text-amber-800 text-xs">
              <div><span class="font-semibold">Admin:</span> admin / admin123</div>
              <div><span class="font-semibold">Health Worker:</span> bhw / bhw123</div>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Right Side: Info (Hidden on Mobile) -->
        <div class="hidden md:flex flex-col justify-between p-8 md:p-10" style="background: linear-gradient(145deg, rgba(14,165,164,0.18), rgba(37,99,235,0.18));">
          <div>
            <div class="text-xs uppercase tracking-widest text-slate-600 font-semibold">Health Insights</div>
            <div class="text-2xl font-bold text-slate-900 mt-3">Connected BHU Care</div>
            <p class="text-sm text-slate-700 mt-3 leading-relaxed">
              Track immunization, maternal health, and medicine inventory in one centralized dashboard.
            </p>
            <div class="mt-6 space-y-3">
              <div class="flex items-start gap-3">
                <i class="fas fa-check-circle text-teal-600 mt-1 flex-shrink-0 text-sm"></i>
                <span class="text-sm text-slate-700">Real-time patient records</span>
              </div>
              <div class="flex items-start gap-3">
                <i class="fas fa-check-circle text-teal-600 mt-1 flex-shrink-0 text-sm"></i>
                <span class="text-sm text-slate-700">ARIMA forecasting</span>
              </div>
              <div class="flex items-start gap-3">
                <i class="fas fa-check-circle text-teal-600 mt-1 flex-shrink-0 text-sm"></i>
                <span class="text-sm text-slate-700">Automated reminders</span>
              </div>
            </div>
          </div>
          <div class="text-xs text-slate-600 font-medium">
            <i class="fas fa-shield-alt mr-1"></i>
            Secure. Organized. Ready for forecasting.
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
