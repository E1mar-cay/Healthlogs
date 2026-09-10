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
  <title>Login - HealthLogs</title>
  <!-- Local Assets for 100% Offline Support -->
  <script src="/HealthLogs/public/assets/js/tailwind.js"></script>
  <?php if ($recaptchaSiteKey !== ''): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
  <link rel="stylesheet" href="/HealthLogs/public/assets/css/fontawesome.min.css">
  <style>
    
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
        overflow-y: auto;
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
<body class="flex items-center justify-center p-3 sm:p-4 md:p-6 min-h-screen">
  <div class="w-full max-w-4xl mx-auto my-auto">
    <div class="bg-white/80 backdrop-blur-xl border border-white/40 shadow-2xl rounded-2xl sm:rounded-3xl overflow-hidden">
      <div class="grid grid-cols-1 md:grid-cols-2">
        <!-- Left Side: Login Form -->
        <div class="p-6 sm:p-8 md:p-10 flex flex-col justify-center">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-500/30 mb-6">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
              <path d="M7 7h10v10H7z"></path>
            </svg>
          </div>
          <div class="brand text-2xl sm:text-3xl font-semibold text-slate-900">HealthLogs</div>
          <p class="text-xs sm:text-sm text-slate-600 mt-2">Secure access for Barangay Health Units.</p>

          <?php if ($error): ?>
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600 flex items-center gap-2">
              <i class="fas fa-exclamation-circle flex-shrink-0"></i>
              <span><?= $error === 'captcha' ? 'Please complete the reCAPTCHA verification.' : 'Invalid username or password.' ?></span>
            </div>
          <?php endif; ?>

          <form method="post" action="/HealthLogs/public/auth.php" class="mt-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700">Username</label>
              <input name="username" required class="mt-2 w-full px-4 py-2.5 sm:py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm sm:text-base" placeholder="e.g. admin" />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-slate-700">Password</label>
              <div class="relative mt-2">
                <input id="loginPassword" name="password" type="password" required class="w-full pl-4 pr-11 py-2.5 sm:py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm sm:text-base" placeholder="Enter password" />
                <button type="button" id="toggleLoginPassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors" title="Show password" aria-label="Toggle password visibility">
                  <i class="fas fa-eye text-sm sm:text-base"></i>
                </button>
              </div>
            </div>

            <?php if ($recaptchaSiteKey !== ''): ?>
            <div id="recaptchaSection" class="mt-3 min-h-[76px] flex flex-col justify-center">
              <div id="recaptchaContainer" class="flex justify-center sm:justify-start"></div>
              <div id="recaptchaOfflineNotice" class="hidden text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg p-2.5 items-center gap-2">
                <i class="fas fa-wifi-slash text-slate-400"></i>
                <span>Offline Mode active &bull; Security check bypassed</span>
              </div>
              <div id="recaptchaLoadingNotice" class="text-xs text-slate-400 flex items-center gap-2 py-2">
                <i class="fas fa-circle-notch fa-spin"></i>
                <span>Checking security verification...</span>
              </div>
            </div>
            <?php endif; ?>
            
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 sm:py-3 rounded-lg font-medium shadow-md hover:shadow-lg transition-all mt-6 text-sm sm:text-base" type="submit">
              <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
          </form>


        </div>

        <!-- Right Side: Info (Hidden on Mobile) -->
        <div class="hidden md:flex flex-col justify-between p-8 md:p-10 relative overflow-hidden" style="background: linear-gradient(145deg, rgba(14,165,164,0.18), rgba(37,99,235,0.18));">
          <!-- Background Watermark Logo -->
          <div class="absolute -right-10 -bottom-10 pointer-events-none opacity-[0.14] select-none transform rotate-12">
            <svg class="w-80 h-80 text-teal-900" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="50" cy="50" r="46" fill="currentColor" fill-opacity="0.05" stroke="currentColor" stroke-width="4"/>
              <circle cx="50" cy="50" r="38" stroke="currentColor" stroke-width="2" stroke-dasharray="4 4"/>
              <rect x="42" y="20" width="16" height="60" rx="3" fill="currentColor"/>
              <rect x="20" y="42" width="60" height="16" rx="3" fill="currentColor"/>
              <circle cx="50" cy="50" r="9" fill="#ffffff" fill-opacity="0.9"/>
              <path d="M50 43 L53 48 L58 50 L53 52 L50 57 L47 52 L42 50 L47 48 Z" fill="currentColor"/>
            </svg>
          </div>

          <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-600/15 border border-teal-600/25 text-teal-900 text-xs uppercase tracking-widest font-semibold">
              <i class="fas fa-heartbeat text-teal-700"></i>
              Health Insights
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-3">Barangay Health Care Hub</div>
            <p class="text-sm text-slate-700 mt-2 leading-relaxed">
              Track immunization, maternal health, consultations, and medicine inventory in one centralized platform.
            </p>
            <div class="mt-6 space-y-3">
              <div class="flex items-start gap-3 bg-white/50 p-3 rounded-xl border border-white/70 shadow-sm backdrop-blur-xs">
                <i class="fas fa-check-circle text-teal-600 mt-0.5 flex-shrink-0 text-sm"></i>
                <span class="text-sm text-slate-800 font-medium">Real-time patient records & monitoring</span>
              </div>
              <div class="flex items-start gap-3 bg-white/50 p-3 rounded-xl border border-white/70 shadow-sm backdrop-blur-xs">
                <i class="fas fa-chart-line text-teal-600 mt-0.5 flex-shrink-0 text-sm"></i>
                <span class="text-sm text-slate-800 font-medium">ARIMA predictive disease forecasting</span>
              </div>
              <div class="flex items-start gap-3 bg-white/50 p-3 rounded-xl border border-white/70 shadow-sm backdrop-blur-xs">
                <i class="fas fa-bell text-teal-600 mt-0.5 flex-shrink-0 text-sm"></i>
                <span class="text-sm text-slate-800 font-medium">Automated health alerts & reminders</span>
              </div>
            </div>
          </div>
          <div class="relative z-10 text-xs text-slate-600 font-medium flex items-center gap-1.5 pt-4 border-t border-slate-300/50">
            <i class="fas fa-shield-alt text-teal-700"></i>
            <span>Secure. Organized. Ready for forecasting.</span>
          </div>
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

    // Dynamic reCAPTCHA Auto Online/Offline Handler
    (function () {
      var siteKey = <?= json_encode($recaptchaSiteKey) ?>;
      if (!siteKey) return;

      var container = document.getElementById('recaptchaContainer');
      var offlineNotice = document.getElementById('recaptchaOfflineNotice');
      var loadingNotice = document.getElementById('recaptchaLoadingNotice');
      var widgetId = null;
      var scriptInjected = false;

      function setOfflineState() {
        if (loadingNotice) loadingNotice.style.display = 'none';
        if (container) container.style.display = 'none';
        if (offlineNotice) {
          offlineNotice.classList.remove('hidden');
          offlineNotice.style.display = 'flex';
        }
      }

      function setOnlineState() {
        if (offlineNotice) {
          offlineNotice.classList.add('hidden');
          offlineNotice.style.display = 'none';
        }
        if (container) container.style.display = 'block';
      }

      window.onRecaptchaLoaded = function () {
        if (loadingNotice) loadingNotice.style.display = 'none';
        setOnlineState();
        if (container && widgetId === null && window.grecaptcha) {
          try {
            widgetId = grecaptcha.render('recaptchaContainer', {
              'sitekey': siteKey
            });
          } catch (e) {
            console.warn('reCAPTCHA render notice:', e);
          }
        }
      };

      function checkAndLoadRecaptcha() {
        if (!navigator.onLine) {
          setOfflineState();
          return;
        }

        if (window.grecaptcha && typeof window.grecaptcha.render === 'function') {
          window.onRecaptchaLoaded();
          return;
        }

        if (!scriptInjected) {
          scriptInjected = true;
          var script = document.createElement('script');
          script.src = 'https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoaded&render=explicit';
          script.async = true;
          script.defer = true;

          var fallbackTimer = setTimeout(function () {
            if (widgetId === null && (!window.grecaptcha || typeof window.grecaptcha.render !== 'function')) {
              setOfflineState();
            }
          }, 3500);

          script.onerror = function () {
            clearTimeout(fallbackTimer);
            setOfflineState();
          };

          document.head.appendChild(script);
        } else {
          setOnlineState();
        }
      }

      // Real-time network transitions
      window.addEventListener('online', function () {
        checkAndLoadRecaptcha();
      });

      window.addEventListener('offline', function () {
        setOfflineState();
      });

      // Initial check on page load
      checkAndLoadRecaptcha();

      // Form validation
      var form = container ? container.closest('form') : null;
      if (form) {
        form.addEventListener('submit', function (e) {
          if (navigator.onLine && widgetId !== null && window.grecaptcha && container.style.display !== 'none') {
            var response = grecaptcha.getResponse(widgetId);
            if (!response) {
              e.preventDefault();
              alert('Please complete the reCAPTCHA verification to log in.');
            }
          }
        });
      }
    })();
  </script>
</body>
</html>
