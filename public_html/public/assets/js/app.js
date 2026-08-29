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
    // Settings tabs (multi-tab admin settings page)
    // ----------------------------------------------------------------------
    function initSettingsTabs() {
        var tabs = document.querySelectorAll('[role="tablist"] .settings-tab');
        if (!tabs.length) return;

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (event) {
                event.preventDefault();
                var targetId = tab.getAttribute('data-tab');
                if (!targetId) return;

                tabs.forEach(function (other) {
                    var active = other === tab;
                    other.classList.toggle('is-active', active);
                    other.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                document.querySelectorAll('[data-pane]').forEach(function (pane) {
                    var active = pane.getAttribute('data-pane') === targetId;
                    pane.classList.toggle('is-active', active);
                    if (active) {
                        pane.removeAttribute('hidden');
                    } else {
                        pane.setAttribute('hidden', '');
                    }
                });
            });
        });
    }

    // ----------------------------------------------------------------------
    // Active nav link highlight based on current path (header only)
    // ----------------------------------------------------------------------
    //
    // Only the header's nav links are highlighted here — the sidebar items
    // already receive an `is-active` class on the server side (rendered with
    // exact-match logic), and we never want two items to look selected at
    // the same time. We additionally stamp `aria-current="page"` for screen
    // readers without touching the visual state.
    function highlightActiveNav() {
        var path = window.location.pathname.replace(/\/+$/, '') || '/';
        document.querySelectorAll('.nav-link').forEach(function (link) {
            var href = link.getAttribute('href') || '';
            var target = href.replace(/#.*$/, '').replace(/\/+$/, '') || '/';
            if (target === path) {
                link.classList.add('is-active');
                link.setAttribute('aria-current', 'page');
            } else {
                link.classList.remove('is-active');
                link.removeAttribute('aria-current');
            }
        });

        // Defensive: if multiple sidebar items ended up with is-active
        // (e.g. due to a hard reload racing with JS state), keep only the
        // one whose href exactly matches the current path.
        var sidebarLinks = document.querySelectorAll('.app-sidebar a.is-active');
        if (sidebarLinks.length > 1) {
            sidebarLinks.forEach(function (a) {
                var href = a.getAttribute('href') || '';
                var target = href.replace(/\/+$/, '') || '/';
                if (target !== path) {
                    a.classList.remove('is-active');
                    a.removeAttribute('aria-current');
                }
            });
        }
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
    // User menu (header avatar dropdown)
    // ----------------------------------------------------------------------
    //
    // The button toggles a popup menu (Profil, Tukar Kata Laluan, Log Keluar).
    // Clicking outside, pressing Escape, or focusing an item that navigates
    // away will close it. The dropdown uses the native `hidden` attribute
    // for the initial state so it stays hidden even with JS disabled.
    function initUserMenu() {
        var menus = document.querySelectorAll('[data-user-menu]');
        if (!menus.length) return;

        function close(menu) {
            var toggle = menu.querySelector('[data-user-menu-toggle]');
            var dropdown = menu.querySelector('.user-dropdown');
            if (!toggle || !dropdown) return;
            dropdown.setAttribute('hidden', '');
            toggle.setAttribute('aria-expanded', 'false');
        }

        function open(menu) {
            var toggle = menu.querySelector('[data-user-menu-toggle]');
            var dropdown = menu.querySelector('.user-dropdown');
            if (!toggle || !dropdown) return;
            dropdown.removeAttribute('hidden');
            toggle.setAttribute('aria-expanded', 'true');
        }

        menus.forEach(function (menu) {
            var toggle = menu.querySelector('[data-user-menu-toggle]');
            var dropdown = menu.querySelector('.user-dropdown');
            if (!toggle || !dropdown) return;

            toggle.addEventListener('click', function (event) {
                event.stopPropagation();
                var isOpen = toggle.getAttribute('aria-expanded') === 'true';
                // Close every other open menu first.
                menus.forEach(close);
                if (!isOpen) {
                    open(menu);
                }
            });

            // Close when an item is activated so the visual state matches
            // the navigation transition (also useful for keyboard users).
            dropdown.querySelectorAll('.user-dropdown-item').forEach(function (item) {
                item.addEventListener('click', function () { close(menu); });
            });
        });

        // Outside click closes any open menu.
        document.addEventListener('click', function (event) {
            menus.forEach(function (menu) {
                if (menu.contains(event.target)) return;
                close(menu);
            });
        });

        // Escape closes the most recently opened menu and returns focus.
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            menus.forEach(function (menu) {
                var toggle = menu.querySelector('[data-user-menu-toggle]');
                if (toggle && toggle.getAttribute('aria-expanded') === 'true') {
                    close(menu);
                    toggle.focus();
                }
            });
        });
    }

    // ----------------------------------------------------------------------
    // CAPTCHA refresh button
    // ----------------------------------------------------------------------
    //
    // Replaces the current math challenge with a freshly-issued one (fetched
    // from /captcha/refresh). Fails silently if the endpoint returns an
    // error or CAPTCHA is disabled — the user just gets a stale question
    // and a friendly error on submit.
    function initCaptchaRefresh() {
        var buttons = document.querySelectorAll('[data-captcha-refresh]');
        if (!buttons.length) return;

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var block = btn.closest('.captcha-field');
                if (!block) return;
                var formKey = block.getAttribute('data-captcha-form') || '';
                var questionEl = block.querySelector('.captcha-question');
                var tokenEl    = block.querySelector('input[name="captcha_token_' + formKey + '"]');
                var inputEl    = block.querySelector('input[name="captcha_answer_' + formKey + '"]');
                if (!questionEl) return;

                var originalLabel = btn.innerHTML;
                btn.setAttribute('disabled', 'disabled');
                btn.style.opacity = '0.5';

                fetch('/captcha/refresh', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                    .then(function (data) {
                        if (data && data.ok && data.question) {
                            questionEl.textContent = data.question;
                            if (tokenEl && data.token) { tokenEl.value = data.token; }
                            if (inputEl) { inputEl.value = ''; inputEl.focus(); }
                            block.classList.remove('is-error');
                        }
                    })
                    .catch(function () {
                        // Refresh is non-critical; the form will show an
                        // error on submit if the puzzle is actually stale.
                    })
                    .then(function () {
                        btn.removeAttribute('disabled');
                        btn.style.opacity = '';
                    });
            });
        });
    }

    // ----------------------------------------------------------------------
    // Profile avatar live preview
    // ----------------------------------------------------------------------
    //
    // When the user picks a new file, swap the preview pane to an <img>
    // pointing at a blob URL so they can see the result before saving.
    function initAvatarPreview() {
        var input = document.querySelector('[data-avatar-input]');
        var preview = document.querySelector('[data-avatar-preview]');
        if (!input || !preview) return;

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            if (!file.type || file.type.indexOf('image/') !== 0) return;

            // Revoke the previous blob URL to avoid leaking memory.
            if (preview._blobUrl) {
                URL.revokeObjectURL(preview._blobUrl);
            }
            var url = URL.createObjectURL(file);
            preview._blobUrl = url;
            preview.innerHTML = '';
            var img = document.createElement('img');
            img.alt = 'Pratonton avatar';
            img.src = url;
            preview.appendChild(img);
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
        initSidebarCollapse();
        initSettingsTabs();
        initUserMenu();
        initAvatarPreview();
        initCaptchaRefresh();
    });
})();
