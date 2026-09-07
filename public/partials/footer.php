      </section>
    </main>
  </div>
  <script>
    (function () {
      const sidebar = document.getElementById('appSidebar');
      const overlay = document.getElementById('appOverlay');
      const toggle = document.getElementById('sidebarToggle');

      if (!sidebar || !overlay || !toggle) return;

      const open = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100', 'pointer-events-auto');
        document.body.classList.add('overflow-hidden');
      };

      const close = () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.remove('opacity-100', 'pointer-events-auto');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        document.body.classList.remove('overflow-hidden');
      };

      toggle.addEventListener('click', () => {
        if (sidebar.classList.contains('-translate-x-full')) {
          open();
        } else {
          close();
        }
      });

      overlay.addEventListener('click', close);
      window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          close();
        }
      });

      window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
          close();
        }
      });
    })();

    (function () {
      if (typeof Swal === 'undefined') return;

      const runConfirm = (target, onConfirm) => {
        const title = target.getAttribute('data-confirm-title') || 'Are you sure?';
        const text = target.getAttribute('data-confirm') || 'This action cannot be undone.';
        const confirmText = target.getAttribute('data-confirm-cta') || 'Yes, proceed';

        Swal.fire({
          title,
          text,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#0f172a',
          cancelButtonColor: '#94a3b8',
          confirmButtonText: confirmText,
          cancelButtonText: 'Cancel',
          reverseButtons: true,
          focusCancel: true
        }).then((result) => {
          if (result.isConfirmed) {
            onConfirm();
          }
        });
      };

      const forms = document.querySelectorAll('form[data-confirm]');
      forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
          event.preventDefault();
          runConfirm(form, () => form.submit());
        });
      });

      const triggers = document.querySelectorAll('[data-confirm]:not(form)');
      triggers.forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
          event.preventDefault();
          runConfirm(trigger, () => {
            if (trigger.tagName === 'A') {
              window.location.href = trigger.getAttribute('href') || '#';
              return;
            }
            const form = trigger.closest('form');
            if (form) {
              form.submit();
            }
          });
        });
      });
    })();

    // Automatic SMS Reminder Dispatch Heartbeat
    (function () {
      let isChecking = false;
      const checkReminders = () => {
        if (isChecking) return;
        isChecking = true;
        fetch('/HealthLogs/public/reminders/auto_check.php')
          .then((res) => res.json())
          .then((data) => {
            if (data && data.status === 'dispatched' && data.sent_count > 0) {
              if (typeof Swal !== 'undefined') {
                Swal.fire({
                  toast: true,
                  position: 'top-end',
                  icon: 'success',
                  title: 'SMS Reminders Dispatched',
                  text: 'Successfully sent ' + data.sent_count + ' scheduled SMS reminder(s) via TextBee.',
                  showConfirmButton: false,
                  timer: 6000
                });
              }
              if (window.location.pathname.includes('/reminders.php')) {
                setTimeout(() => window.location.reload(), 2500);
              }
            }
          })
          .catch(() => {})
          .finally(() => {
            isChecking = false;
          });
      };

      // Run 4 seconds after page load, then every 30 seconds
      setTimeout(checkReminders, 4000);
      setInterval(checkReminders, 30000);
    })();
  </script>
</body>
</html>
