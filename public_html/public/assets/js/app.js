(function () {
    'use strict';

    // ----------------------------------------------------------------------
    // Mobile navigation toggle
    // ----------------------------------------------------------------------
    function initMobileNav() {
        var toggle = document.getElementById('navToggle');
        var menu = document.getElementById('mobileMenu');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', function () {
            var isOpen = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
        });

        // Close menu when clicking outside or on a menu link (mobile).
        document.addEventListener('click', function (event) {
            if (!menu.classList.contains('is-open')) return;
            if (menu.contains(event.target) || toggle.contains(event.target)) return;
            menu.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Buka menu');
        });

        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                menu.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Buka menu');
            });
        });
    }

    // ----------------------------------------------------------------------
    // Flash messages (auto-dismiss + manual close)
    // ----------------------------------------------------------------------
    function initFlash() {
        var flashes = document.querySelectorAll('[data-flash]');
        if (!flashes.length) return;

        flashes.forEach(function (el) {
            var closeBtn = el.querySelector('.flash-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () { dismiss(el); });
            }

            // Auto-dismiss after 5 seconds.
            setTimeout(function () { dismiss(el); }, 5000);
        });
    }

    function dismiss(el) {
        if (!el || el.classList.contains('is-fading')) return;
        el.classList.add('is-fading');
        setTimeout(function () {
            if (el && el.parentNode) {
                el.parentNode.removeChild(el);
            }
        }, 240);
    }

    // ----------------------------------------------------------------------
    // Form: disable submit button to prevent double submission
    // ----------------------------------------------------------------------
    function initForms() {
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                var submit = form.querySelector('button[type="submit"]');
                if (!submit) return;
                // Use rAF to allow the request to start first.
                requestAnimationFrame(function () {
                    submit.setAttribute('disabled', 'disabled');
                    submit.style.opacity = '0.7';
                    submit.style.cursor = 'wait';
                });
            });
        });
    }

    // ----------------------------------------------------------------------
    // Active nav link highlight based on current path
    // ----------------------------------------------------------------------
    function highlightActiveNav() {
        var path = window.location.pathname.replace(/\/+$/, '') || '/';
        document.querySelectorAll('.nav-link').forEach(function (link) {
            var href = link.getAttribute('href') || '';
            var target = href.replace(/#.*$/, '').replace(/\/+$/, '') || '/';
            if (target === path) {
                link.classList.add('is-active');
            }
        });
    }

    // ----------------------------------------------------------------------
    // Boot
    // ----------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', function () {
        initMobileNav();
        initFlash();
        initForms();
        initPasswordToggle();
        highlightActiveNav();
    });
})();
