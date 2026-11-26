/**
 * Dior Medical — Doctor Dashboard Navigation Safety Controller
 * Provides isolated, rock-solid sidebar navigation with zero dependencies.
 * Ensures Doctor Dashboard tabs ALWAYS switch smoothly even if other widgets fail.
 */
(function () {
    'use strict';

    // Normalize tab ID aliases
    function normalizeTabId(tabId) {
        if (!tabId) return 'doc-overview';
        var t = String(tabId).trim();
        if (t === 'feedback' || t === 'doc-feedback' || t === 'patient-review' || t === 'reviews') {
            return 'doc-patient-review';
        }
        if (t === 'notifications' || t === 'doc-notifications') {
            return 'doc-notifications-system';
        }
        if (t === 'documents' || t === 'doc-documents') {
            return 'doc-documents-upload';
        }
        if (t === 'patients' || t === 'doc-patients') {
            return 'doc-patients-all';
        }
        if (t === 'appointments') {
            return 'doc-appointments';
        }
        if (t === 'settings') {
            return 'doc-settings';
        }
        return t;
    }

    // Global submenu toggle
    window.diorToggleSubmenu = function (e, menuId) {
        if (e && e.preventDefault) e.preventDefault();
        var targetMenu = document.getElementById(menuId);
        if (!targetMenu) return;

        var isAlreadyOpen = targetMenu.classList.contains('active') || targetMenu.classList.contains('open');

        document.querySelectorAll('#dior-doc-sidebar .dior-nav-item.has-submenu').forEach(function (item) {
            if (item.id !== menuId) {
                item.classList.remove('active', 'open');
                var icon = item.querySelector('.submenu-icon');
                if (icon) {
                    icon.classList.remove('fa-minus');
                    icon.classList.add('fa-plus');
                }
            }
        });

        if (isAlreadyOpen) {
            targetMenu.classList.remove('active', 'open');
            var icon = targetMenu.querySelector('.submenu-icon');
            if (icon) {
                icon.classList.remove('fa-minus');
                icon.classList.add('fa-plus');
            }
        } else {
            targetMenu.classList.add('active', 'open');
            var icon = targetMenu.querySelector('.submenu-icon');
            if (icon) {
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-minus');
            }
        }
    };

    // Mobile drawer close helper
    window.diorDocCloseMobile = function () {
        var sidebar = document.getElementById('dior-doc-sidebar');
        var backdrop = document.getElementById('dior-doc-backdrop');
        if (sidebar) sidebar.classList.remove('mobile-open');
        if (backdrop) backdrop.classList.remove('active');
        if (backdrop) backdrop.classList.remove('open');
        document.body.classList.remove('dior-drawer-open');
        document.body.style.removeProperty('overflow');
    };

    // Mobile drawer open helper
    window.diorDocOpenMobile = function () {
        var sidebar = document.getElementById('dior-doc-sidebar');
        var backdrop = document.getElementById('dior-doc-backdrop');
        if (!sidebar) return;
        var isOpen = sidebar.classList.contains('mobile-open');
        if (isOpen) {
            sidebar.classList.remove('mobile-open');
            if (backdrop) backdrop.classList.remove('active');
            if (backdrop) backdrop.classList.remove('open');
            document.body.classList.remove('dior-drawer-open');
            document.body.style.removeProperty('overflow');
        } else {
            sidebar.classList.add('mobile-open');
            if (backdrop) backdrop.classList.add('active');
            if (backdrop) backdrop.classList.add('open');
            document.body.classList.add('dior-drawer-open');
            document.body.style.overflow = 'hidden';
        }
    };

    // Close the drawer when the user taps anywhere outside the sidebar.
    // Do not treat the hamburger toggle as an outside click; it must remain a toggle.
    document.addEventListener('pointerdown', function (event) {
        var sidebar = document.getElementById('dior-doc-sidebar');
        if (!sidebar || !sidebar.classList.contains('mobile-open')) return;

        var target = event.target;
        if (sidebar.contains(target)) return;
        if (target.closest && target.closest('.dior-mobile-menu-toggle')) return;

        window.diorDocCloseMobile();
    }, true);

    // Add an explicit close control inside the mobile drawer.
    function ensureMobileCloseButton() {
        var sidebar = document.getElementById('dior-doc-sidebar');
        if (!sidebar || sidebar.querySelector('.dior-mobile-drawer-close')) return;

        var header = sidebar.querySelector('.dior-side-header') || sidebar;
        var closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'dior-mobile-drawer-close';
        closeButton.setAttribute('aria-label', 'Close navigation menu');
        closeButton.setAttribute('title', 'Close menu');
        closeButton.innerHTML = '&times;';
        closeButton.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            window.diorDocCloseMobile();
        });
        header.appendChild(closeButton);

        if (!document.getElementById('dior-mobile-drawer-close-styles')) {
            var style = document.createElement('style');
            style.id = 'dior-mobile-drawer-close-styles';
            style.textContent = `
                .dior-mobile-drawer-close { display:none; }
                @media (max-width: 767.98px) {
                    #dior-doctor-app #dior-doc-sidebar .dior-side-header { position:relative; }
                    #dior-doctor-app #dior-doc-sidebar .dior-mobile-drawer-close {
                        display:flex; align-items:center; justify-content:center;
                        position:absolute; top:12px; right:12px; z-index:20;
                        width:34px; height:34px; padding:0; border:1px solid rgba(255,255,255,.35);
                        border-radius:50%; background:rgba(255,255,255,.14);
                        color:#fff; font-size:25px; line-height:1; cursor:pointer;
                    }
                    #dior-doctor-app #dior-doc-sidebar .dior-mobile-drawer-close:focus-visible {
                        outline:2px solid #fff; outline-offset:2px;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            window.diorDocCloseMobile();
        }
    });

    // Main tab switcher
    window.diorDocSwitchTab = function (tabId, updateUrl) {
        var app = document.getElementById('dior-doctor-app');
        if (!tabId) return false;
        var normalizedId = normalizeTabId(tabId);

        // Find matching section panel
        var activePanel = document.getElementById('tab-' + normalizedId);
        if (!activePanel && app) {
            // Check if standard tab without prefix exists
            activePanel = document.getElementById(normalizedId);
        }
        if (!activePanel) {
            console.warn('[Dior Navigation] Panel not found for tab:', tabId, '(normalized: ' + normalizedId + ')');
            return false;
        }

        // Close notification dropdown if open
        var notifDropdown = document.getElementById('dior-doc-notif-dd');
        if (notifDropdown) {
            notifDropdown.classList.remove('open', 'active');
        }

        // Update nav buttons
        var buttons = document.querySelectorAll('#dior-doc-sidebar .dior-nav-btn');
        buttons.forEach(function (b) {
            b.classList.remove('active');
        });

        var activeBtn = document.querySelector('#dior-doc-sidebar .dior-nav-btn[data-tab="' + normalizedId + '"]');
        if (activeBtn) {
            activeBtn.classList.add('active');

            // Strict Accordion: Expand parent submenu if inside one, close all others
            var parentSubmenu = activeBtn.closest('.dior-nav-item.has-submenu');
            document.querySelectorAll('#dior-doc-sidebar .dior-nav-item.has-submenu').forEach(function(sub) {
                if (parentSubmenu && sub === parentSubmenu) {
                    sub.classList.add('active', 'open');
                    var subIcon = sub.querySelector('.submenu-icon');
                    if (subIcon) {
                        subIcon.classList.remove('fa-plus');
                        subIcon.classList.add('fa-minus');
                    }
                } else {
                    sub.classList.remove('active', 'open');
                    var subIcon = sub.querySelector('.submenu-icon');
                    if (subIcon) {
                        subIcon.classList.remove('fa-minus');
                        subIcon.classList.add('fa-plus');
                    }
                }
            });

            // Dismiss badge with transition if present
            var badge = activeBtn.querySelector('.nav-count-badge');
            if (badge) {
                badge.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                badge.style.opacity = '0';
                badge.style.transform = 'scale(0.5)';
                setTimeout(function () {
                    badge.style.display = 'none';
                }, 250);
            }
        }

        // Update tab panels
        var panels = document.querySelectorAll('#dior-doc-content > .dior-tab-panel');
        panels.forEach(function (panel) {
            if (panel === activePanel) {
                panel.classList.add('active');
                panel.removeAttribute('hidden');
                panel.style.removeProperty('display');
                panel.style.display = 'block';
            } else {
                panel.classList.remove('active');
                panel.setAttribute('hidden', 'hidden');
                panel.style.display = 'none';
            }
        });

        // Update URL hash
        if (updateUrl !== false && window.history && window.history.replaceState) {
            try {
                window.history.replaceState(null, '', '#tab=' + encodeURIComponent(normalizedId));
            } catch (e) { }
        }

        // Refresh table pagination if table exists in active panel
        if (window.diorDoctorInitTablePagination) {
            activePanel.querySelectorAll('.dior-doc-table, .dior-table, .dior-clean-table').forEach(function (table) {
                if (table._diorRenderPage) {
                    table._diorRenderPage(1);
                } else {
                    window.diorDoctorInitTablePagination(table, 5);
                }
            });
        }

        // Close mobile drawer on selection
        window.diorDocCloseMobile();

        return true;
    };

    function boot() {
        var app = document.getElementById('dior-doctor-app');
        if (!app || app.__diorDoctorNavigationReady) return;
        app.__diorDoctorNavigationReady = true;
        ensureMobileCloseButton();

        // Bind each tab button directly instead of relying on bubbling through
        // other dashboard scripts that may stop or intercept click events.
        app.querySelectorAll('#dior-doc-sidebar .dior-nav-btn[data-tab]').forEach(function (button) {
            if (button.__diorTabClickBound) return;
            button.__diorTabClickBound = true;
            button.addEventListener('click', function (event) {
                var tab = button.getAttribute('data-tab');
                if (!tab) return;
                event.preventDefault();
                window.diorDocSwitchTab(tab, true);
            });
        });

        // Submenu parent buttons retain their existing inline toggle handler.

        // Sync tab from URL hash on load
        function syncTabFromUrl(updateUrl) {
            var hashMatch = window.location.hash.match(/^#tab=([^&]+)/);
            var requestedTab = hashMatch ? decodeURIComponent(hashMatch[1]) : 'doc-overview';
            requestedTab = normalizeTabId(requestedTab);

            var panel = document.getElementById('tab-' + requestedTab);
            var tabToLoad = panel ? requestedTab : 'doc-overview';
            window.diorDocSwitchTab(tabToLoad, updateUrl);
        }

        window.addEventListener('hashchange', function () {
            syncTabFromUrl(false);
        });

        // Initialize immediately
        syncTabFromUrl(false);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();


/* Dior tab visibility recovery: keeps the selected panel visible if another
   dashboard script or stale cached handler interferes with panel visibility. */
(function () {
    if (window.__diorTabVisibilityRecoveryInstalled) return;
    window.__diorTabVisibilityRecoveryInstalled = true;
    function normalize(id) {
        id = String(id || '').trim();
        if (id === 'patients' || id === 'doc-patients') return 'doc-patients-all';
        if (id === 'appointments') return 'doc-appointments';
        if (id === 'feedback' || id === 'doc-feedback' || id === 'reviews' || id === 'patient-review') return 'doc-patient-review';
        if (id === 'notifications' || id === 'doc-notifications') return 'doc-notifications-system';
        if (id === 'documents' || id === 'doc-documents') return 'doc-documents-upload';
        return id;
    }
    var previous = window.diorDocSwitchTab;
    window.diorDocSwitchTab = function (tabId, updateUrl) {
        var id = normalize(tabId), result = false;
        try { if (typeof previous === 'function') result = previous.apply(this, arguments); }
        catch (error) { console.error('[Dior] Tab handler recovery:', error); }
        var panel = document.getElementById('tab-' + id);
        var content = document.getElementById('dior-doc-content');
        if (!panel || !content) return result;
        content.querySelectorAll(':scope > .dior-tab-panel').forEach(function (item) {
            item.classList.remove('active');
            item.setAttribute('hidden', 'hidden');
            item.style.setProperty('display', 'none', 'important');
        });
        panel.classList.add('active');
        panel.removeAttribute('hidden');
        panel.style.setProperty('display', 'block', 'important');
        panel.style.setProperty('visibility', 'visible', 'important');
        panel.style.setProperty('opacity', '1', 'important');
        document.querySelectorAll('#dior-doc-sidebar .dior-nav-btn[data-tab]').forEach(function (button) {
            button.classList.toggle('active', button.getAttribute('data-tab') === id);
        });
        if (updateUrl !== false) {
            try { window.history.replaceState(null, '', '#tab=' + encodeURIComponent(id)); } catch (e) {}
        }
        return true;
    };
    function sync() {
        var match = window.location.hash.match(/^#tab=([^&]+)/);
        var id = normalize(match ? decodeURIComponent(match[1]) : 'doc-overview');
        if (document.getElementById('tab-' + id)) window.diorDocSwitchTab(id, false);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', sync, { once: true });
    else window.setTimeout(sync, 0);
    window.addEventListener('hashchange', sync);
})();

/* Dior Doctor Sidebar Collapse Controller (ChatGPT / WordPress style) */
window.diorToggleDoctorSidebarCollapse = window.diorToggleDoctorSidebarCollapse || function (e) {
    if (e) {
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
    }
    var app = document.getElementById('dior-doctor-app') || document.querySelector('.dior-doctor-wrap');
    if (!app) return;

    var isCollapsed = app.classList.toggle('dior-sidebar-collapsed');
    var icon = document.getElementById('dior-doc-collapse-icon');
    var btn = document.getElementById('dior-doc-sidebar-toggle');

    if (icon) {
        if (isCollapsed) {
            icon.classList.remove('fa-angles-left');
            icon.classList.add('fa-angles-right');
        } else {
            icon.classList.remove('fa-angles-right');
            icon.classList.add('fa-angles-left');
        }
    }
    if (btn) {
        var label = btn.querySelector('.nav-label');
        if (isCollapsed) {
            btn.setAttribute('title', 'Expand Menu');
            if (label) label.textContent = 'Expand Menu';
        } else {
            btn.setAttribute('title', 'Collapse Menu');
            if (label) label.textContent = 'Collapse Menu';
        }
    }

    try {
        localStorage.setItem('dior_doctor_sidebar_collapsed', isCollapsed ? '1' : '0');
    } catch (err) {}

    setTimeout(function () {
        window.dispatchEvent(new Event('resize'));
    }, 280);
};

// Auto-restore saved collapsed state on DOMContentLoaded
(function () {
    function restoreState() {
        try {
            if (localStorage.getItem('dior_doctor_sidebar_collapsed') === '1' && window.innerWidth >= 1025) {
                var app = document.getElementById('dior-doctor-app') || document.querySelector('.dior-doctor-wrap');
                if (app) {
                    app.classList.add('dior-sidebar-collapsed');
                    var icon = document.getElementById('dior-doc-collapse-icon');
                    var btn = document.getElementById('dior-doc-sidebar-toggle');
                    if (icon) {
                        icon.classList.remove('fa-angles-left');
                        icon.classList.add('fa-angles-right');
                    }
                    if (btn) {
                        btn.setAttribute('title', 'Expand Menu');
                        var label = btn.querySelector('.nav-label');
                        if (label) label.textContent = 'Expand Menu';
                    }
                }
            }
        } catch (e) {}
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreState, { once: true });
    } else {
        restoreState();
    }
})();



/* Doctor header dropdown fallbacks.
 * These globals live in the always-enqueued navigation controller so the
 * header remains functional even if an optional dashboard script fails.
 */
(function () {
    'use strict';

    window.diorToggleProfileDropdown = window.diorToggleProfileDropdown || function (event) {
        if (event && event.preventDefault) event.preventDefault();
        if (event && event.stopPropagation) event.stopPropagation();

        var dropdown = document.getElementById('dior-profile-dropdown');
        var notifications = document.getElementById('dior-doc-notif-dd');
        if (!dropdown) return false;

        if (notifications) notifications.classList.remove('open', 'active');
        dropdown.classList.toggle('open');
        return true;
    };

    window.diorDocToggleNotif = window.diorDocToggleNotif || function (event) {
        if (event && event.preventDefault) event.preventDefault();
        if (event && event.stopPropagation) event.stopPropagation();

        var dropdown = document.getElementById('dior-doc-notif-dd');
        var profile = document.getElementById('dior-profile-dropdown');
        if (!dropdown) return false;

        if (profile) profile.classList.remove('open', 'active');
        dropdown.classList.toggle('open');
        return true;
    };

    if (!document.__diorHeaderDropdownOutsideClickBound) {
        document.__diorHeaderDropdownOutsideClickBound = true;
        document.addEventListener('click', function (event) {
            var profileWrap = document.querySelector('.dior-user-pill-wrap');
            var profileDropdown = document.getElementById('dior-profile-dropdown');
            var notifWrap = document.querySelector('.dior-notif-wrap');
            var notifDropdown = document.getElementById('dior-doc-notif-dd');

            if (profileWrap && !profileWrap.contains(event.target) && profileDropdown) {
                profileDropdown.classList.remove('open', 'active');
            }
            if (notifWrap && !notifWrap.contains(event.target) && notifDropdown) {
                notifDropdown.classList.remove('open', 'active');
            }
        });
    }
})();
