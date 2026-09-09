<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/Recaptcha.php';

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /HealthLogs/public/index.php');
    exit;
}

$error = $_GET['error'] ?? '';
$recaptchaSiteKey = Recaptcha::siteKey();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login • HealthLogs - Barangay Care Hub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <?php if ($recaptchaSiteKey !== ''): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap');
    
    *, *::before, *::after {
      box-sizing: border-box;
    }

    body {
      font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #ffffff;
      color: #0f172a;
      overflow-x: hidden;
    }

    .brand-title-font {
      font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
    }

    /* Left Hero Panel: Signature Sidebar Dark Navy / Slate Gradient */
    .hero-panel {
      background: linear-gradient(180deg, #0f172a 0%, #111827 60%, #090d16 100%);
      position: relative;
      overflow: hidden;
      border-right: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* Background Ambient Watermark */
    .hero-watermark {
      position: absolute;
      right: -80px;
      bottom: -80px;
      width: 560px;
      height: 560px;
      opacity: 0.06;
      pointer-events: none;
      user-select: none;
    }

    /* Subtle Glass Pill */
    .frosted-pill {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.10);
      transition: all 0.2s ease;
    }

    .frosted-pill:hover {
      background: rgba(255, 255, 255, 0.10);
      border-color: rgba(96, 165, 250, 0.35);
      transform: translateX(4px);
    }

    /* Custom Input Styling */
    .form-input-field {
      background-color: #f8fafc;
      border: 1.5px solid #e2e8f0;
      color: #0f172a;
      transition: all 0.2s ease;
    }

    .form-input-field:focus {
      background-color: #ffffff;
      border-color: #2563eb;
      outline: none;
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
    }

    /* Primary Action Button: Signature Blue */
    .btn-login-primary {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 60%, #1e40af 100%);
      color: #ffffff;
      transition: all 0.25s ease;
    }

    .btn-login-primary:hover {
      background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 60%, #172554 100%);
      transform: translateY(-1px);
      box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
    }

    .btn-login-primary:active {
      transform: translateY(0);
    }
  </style>
</head>
<body class="min-h-screen">
  <div class="min-h-screen grid grid-cols-1 lg:grid-cols-12">
    
    <!-- LEFT PANEL: Signature Dark Navy / Slate Sidebar Blue Theme (7 cols on lg) -->
    <div class="lg:col-span-6 xl:col-span-7 hero-panel flex flex-col justify-between p-8 sm:p-12 lg:p-16 text-white min-h-[380px] lg:min-h-screen">
      
      <!-- Big Logo Watermark Background -->
      <div class="hero-watermark" aria-hidden="true">
        <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full text-blue-400">
          <circle cx="50" cy="50" r="46" fill="currentColor"/>
          <circle cx="50" cy="50" r="40" stroke="#000" stroke-width="1.5" stroke-dasharray="3 2" opacity="0.3"/>
          <rect x="43" y="20" width="14" height="60" rx="3" fill="#000" opacity="0.3"/>
          <rect x="20" y="43" width="60" height="14" rx="3" fill="#000" opacity="0.3"/>
          <circle cx="50" cy="50" r="8" fill="#000" opacity="0.3"/>
        </svg>
      </div>

      <!-- Top Branding -->
      <div class="relative z-10">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-500/10 border border-blue-400/20 text-blue-200 text-xs font-semibold backdrop-blur-xs">
          <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
          Republic of the Philippines • DOH Primary Care
        </div>

        <div class="flex items-center gap-3.5 mt-8">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-sky-400 flex items-center justify-center p-2 shadow-lg shadow-blue-900/50 shrink-0 border border-blue-300/30">
            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
              <circle cx="50" cy="50" r="46" fill="#ffffff" />
              <rect x="43" y="22" width="14" height="56" rx="3" fill="#1e40af"/>
              <rect x="22" y="43" width="56" height="14" rx="3" fill="#1e40af"/>
              <circle cx="50" cy="50" r="7" fill="#ffffff"/>
              <path d="M50 45 L52 49 L56 50 L52 52 L50 56 L48 52 L44 50 L48 49 Z" fill="#1e40af"/>
            </svg>
          </div>
          <div>
            <div class="brand-title-font text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-none">
              HealthLogs
            </div>
            <div class="text-xs font-medium text-slate-400 mt-1">
              Barangay Health Care Hub & Clinical Station
            </div>
          </div>
        </div>
      </div>

      <!-- Center Headline & Feature Highlights -->
      <div class="relative z-10 max-w-xl my-8">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold leading-snug tracking-tight text-white">
          Unified Primary Health Records & Epidemiological Intelligence
        </h1>
        <p class="text-xs sm:text-sm text-slate-300 mt-3 leading-relaxed">
          Centralized electronic logs, maternal checkups, child immunization monitoring, and predictive ARIMA disease forecasting for Barangay Health Units.
        </p>

        <!-- Feature Pills -->
        <div class="mt-6 space-y-2.5">
          <div class="frosted-pill px-4 py-3 rounded-xl flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-300 flex items-center justify-center shrink-0 text-sm">
              <i class="fas fa-users-medical"></i>
            </div>
            <div>
              <div class="text-xs font-bold text-white">Comprehensive Patient Records</div>
              <div class="text-[11px] text-slate-400">Real-time demographic profiles, visits, and clinical histories</div>
            </div>
          </div>

          <div class="frosted-pill px-4 py-3 rounded-xl flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-300 flex items-center justify-center shrink-0 text-sm">
              <i class="fas fa-chart-line-up"></i>
            </div>
            <div>
              <div class="text-xs font-bold text-white">ARIMA Outbreak Forecasting</div>
              <div class="text-[11px] text-slate-400">Predictive monthly clinic visit trends for proactive planning</div>
            </div>
          </div>

          <div class="frosted-pill px-4 py-3 rounded-xl flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-300 flex items-center justify-center shrink-0 text-sm">
              <i class="fas fa-prescription-bottle-medical"></i>
            </div>
            <div>
              <div class="text-xs font-bold text-white">Medicine Inventory & SMS Alerts</div>
              <div class="text-[11px] text-slate-400">Stock level tracking and automated appointment reminders</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Footnote -->
      <div class="relative z-10 pt-4 border-t border-slate-700/60 flex items-center justify-between text-[11px] text-slate-400">
        <span>Barangay Health Center Management System</span>
        <span class="flex items-center gap-1.5 font-medium text-slate-400">
          <i class="fas fa-circle-check text-emerald-400 text-[10px]"></i> Primary Care Services
        </span>
      </div>

    </div>

    <!-- RIGHT PANEL: Clean Login Form (5 cols on lg) -->
    <div class="lg:col-span-6 xl:col-span-5 bg-white flex flex-col justify-center px-6 sm:px-12 md:px-16 lg:px-16 py-10 lg:py-16 min-h-[500px]">
      <div class="w-full max-w-md mx-auto">
        
        <!-- Welcome Header -->
        <div class="mb-8">
          <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight brand-title-font">
            Welcome back
          </h2>
          <p class="text-slate-500 text-xs sm:text-sm mt-1.5 leading-relaxed">
            Please enter your authorized credentials to access the clinic workstation.
          </p>
        </div>

        <!-- Error Banner -->
        <?php if ($error): ?>
          <div class="mb-5 p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs sm:text-sm text-red-700 flex items-start gap-2.5 shadow-xs">
            <i class="fas fa-circle-exclamation text-red-500 text-base mt-0.5 shrink-0"></i>
            <div>
              <?= $error === 'captcha' ? 'Verification failed. Please complete the reCAPTCHA box below.' : 'Invalid username or password. Please verify your credentials and try again.' ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="post" action="/HealthLogs/public/auth.php" class="space-y-4">
          <!-- Username Input -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="usernameInput">
              Username
            </label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fas fa-user-circle text-base"></i>
              </span>
              <input 
                id="usernameInput"
                name="username" 
                type="text" 
                required 
                autocomplete="username"
                class="form-input-field w-full pl-10 pr-4 py-3 rounded-xl text-sm sm:text-base font-medium placeholder-slate-400" 
                placeholder="Enter username (e.g. admin)" 
              />
            </div>
          </div>

          <!-- Password Input -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="loginPassword">
              Password
            </label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fas fa-lock text-base"></i>
              </span>
              <input 
                id="loginPassword" 
                name="password" 
                type="password" 
                required 
                autocomplete="current-password"
                class="form-input-field w-full pl-10 pr-11 py-3 rounded-xl text-sm sm:text-base font-medium placeholder-slate-400" 
                placeholder="••••••••••••" 
              />
              <button 
                type="button" 
                id="toggleLoginPassword" 
                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors" 
                title="Show password" 
                aria-label="Toggle password visibility">
                <i class="fas fa-eye text-sm"></i>
              </button>
            </div>
          </div>

          <!-- reCAPTCHA if enabled -->
          <?php if ($recaptchaSiteKey !== ''): ?>
          <div class="pt-2 flex justify-start">
            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8') ?>"></div>
          </div>
          <?php endif; ?>

          <!-- Primary Sign In Button -->
          <div class="pt-2">
            <button 
              type="submit" 
              class="btn-login-primary w-full font-semibold py-3.5 px-6 rounded-xl text-sm sm:text-base flex items-center justify-center gap-2 cursor-pointer shadow-md">
              <span>Sign In to HealthLogs</span>
              <i class="fas fa-arrow-right text-xs"></i>
            </button>
          </div>
        </form>

        <!-- Help / Support Footer -->
        <div class="mt-8 pt-6 border-t border-slate-100 text-center text-xs text-slate-500 space-y-1.5">
          <div class="flex items-center justify-center gap-1.5 text-slate-600 font-medium">
            <i class="fas fa-circle-info text-blue-600"></i>
            <span>Need an account or password reset?</span>
          </div>
          <div>Please contact the <strong class="text-slate-700">Barangay Clinic Administrator</strong>.</div>
        </div>

      </div>
    </div>

  </div>

  <script>
    (function () {
      var toggleBtn = document.getElementById('toggleLoginPassword');
      var passwordInput = document.getElementById('loginPassword');
      if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function () {
          var isPassword = passwordInput.getAttribute('type') === 'password';
          passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
          var icon = toggleBtn.querySelector('i');
          if (icon) {
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
          }
          toggleBtn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
        });
      }
    })();
  </script>
</body>
</html>
