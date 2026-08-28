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
    // Password visibility toggle
    // ----------------------------------------------------------------------
    function initPasswordToggle() {
        var buttons = document.querySelectorAll('[data-password-toggle]');
        buttons.forEach(function (btn) {
            var input = document.getElementById(btn.getAttribute('data-password-toggle'));
            if (!input) return;
            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.setAttribute('aria-label', show ? 'Sembunyi kata laluan' : 'Papar kata laluan');
                btn.classList.toggle('is-visible', show);
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
    // Sidebar collapse / expand with persistent state (accordion behaviour)
    // ----------------------------------------------------------------------
    //
    // Only one group may stay open at a time. When the user expands a section,
    // every other collapsible group is collapsed first.
    //
    // The group containing the active route always auto-expands after the user
    // toggles, so the active item is never hidden.
    function initSidebarCollapse() {
        var sidebar = document.querySelector('[data-sidebar]');
        if (!sidebar) return;

        var STORAGE_KEY = 'mk:sidebar:groups:v1';
        var stored = {};
        try {
            stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}') || {};
        } catch (err) {
            stored = {};
        }

        var allGroups = Array.prototype.slice.call(sidebar.querySelectorAll('[data-group]'));

        // Build a lookup of groupKey -> group node + aria/children refs.
        var registry = allGroups.map(function (group) {
            return {
                node: group,
                key: group.getAttribute('data-group-key') || '',
                button: group.querySelector('[data-group-toggle]'),
                children: group.querySelector('.app-nav-children'),
            };
        }).filter(function (entry) {
            return entry.button && entry.children;
        });

        // Default visual state: open unless user previously collapsed it.
        registry.forEach(function (entry) {
            var defaultOpen = entry.node.getAttribute('data-default-open') === 'true';
            var open = stored[entry.key] !== undefined ? stored[entry.key] === true : defaultOpen;
            applyState(entry.node, entry.button, entry.children, open);
        });

        // Accordion toggle: opening a group collapses every other collapsible
        // group first, then persists the new state.
        registry.forEach(function (entry) {
            entry.button.addEventListener('click', function () {
                var isOpen = entry.button.getAttribute('aria-expanded') === 'true';

                if (!isOpen) {
                    // Close everything else (accordion).
                    registry.forEach(function (other) {
                        if (other === entry) return;
                        applyState(other.node, other.button, other.children, false);
                        if (other.key) {
                            stored[other.key] = false;
                        }
                    });
                }

                var next = !isOpen;
                applyState(entry.node, entry.button, entry.children, next);
                if (entry.key) {
                    stored[entry.key] = next;
                }

                try {
                    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(stored));
                } catch (err) {
                    /* storage may be disabled — ignore */
                }
            });
        });

        // Always keep the group containing the active route open so users
        // never land on a hidden item. We close all *other* groups first so
        // the active group is the only one expanded on direct navigation.
        var activeKey = sidebar.getAttribute('data-active-group');
        if (activeKey) {
            registry.forEach(function (entry) {
                var isActive = entry.key === activeKey;
                applyState(entry.node, entry.button, entry.children, isActive);
                if (entry.key) {
                    stored[entry.key] = isActive;
                }
            });
            try {
                window.localStorage.setItem(STORAGE_KEY, JSON.stringify(stored));
            } catch (err) {
                /* ignore */
            }
        }
    }

    function applyState(group, button, children, open) {
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        group.classList.toggle('is-open', open);
        group.classList.toggle('is-collapsed', !open);
        if (open) {
            children.style.maxHeight = children.scrollHeight + 'px';
            children.style.overflow = 'hidden';
            // After transition, clear inline height so dynamic content can size naturally.
            setTimeout(function () {
                if (button.getAttribute('aria-expanded') === 'true') {
                    children.style.maxHeight = '';
                    children.style.overflow = '';
                }
            }, 260);
        } else {
            // Set explicit height first so the transition has a starting value.
            children.style.maxHeight = children.scrollHeight + 'px';
            // Force reflow.
            void children.offsetHeight;
            children.style.overflow = 'hidden';
            children.style.maxHeight = '0px';
        }
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
        initSidebarCollapse();
    });
})();
