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

    // Single canonical tab controller. The Doctor Dashboard script adds optional widgets only.
    window.diorDocSwitchTab = function (tabId, updateUrl) {
        if (!tabId) return false;
    
        // Normalize legacy tab aliases before touching the URL or hiding any panel.
        const aliases = {
            feedback: 'doc-patient-review',
            'doc-feedback': 'doc-patient-review',
            'patient-review': 'doc-patient-review',
            reviews: 'doc-patient-review',
            notifications: 'doc-notifications-system',
            'doc-notifications': 'doc-notifications-system',
            documents: 'doc-documents-upload',
            'doc-documents': 'doc-documents-upload',
            patients: 'doc-patients-all',
            'doc-patients': 'doc-patients-all',
            appointments: 'doc-appointments',
            settings: 'doc-settings'
        };
        tabId = aliases[String(tabId)] || String(tabId);
    
        const content = document.getElementById('dior-doc-content');
        const activePanel = document.getElementById('tab-' + tabId);
    
        // Never hide the current tab if a stale or invalid menu target is clicked.
        if (!content || !activePanel || !content.contains(activePanel)) {
            console.warn('[Dior Doctor] Unknown dashboard tab:', tabId);
            return false;
        }
    
        if (updateUrl !== false) {
            try {
                history.replaceState(null, '', '#tab=' + encodeURIComponent(tabId));
            } catch (e) { }
        }
    
        const notifDropdown = document.getElementById('dior-doc-notif-dd');
        if (notifDropdown) {
            notifDropdown.classList.remove('open', 'active');
        }
    
        // Update sidebar active state and expand the selected submenu.
        const btns = document.querySelectorAll('#dior-doc-sidebar .dior-nav-btn[data-tab]');
        btns.forEach(function (button) {
            button.classList.toggle('active', button.getAttribute('data-tab') === tabId);
        });
    
        const activeBtn = document.querySelector('#dior-doc-sidebar .dior-nav-btn[data-tab="' + tabId + '"]');
        if (activeBtn) {
            const parentSubmenu = activeBtn.closest('.dior-nav-item.has-submenu');
            document.querySelectorAll('#dior-doc-sidebar .dior-nav-item.has-submenu').forEach(function (submenu) {
                const isParent = Boolean(parentSubmenu && submenu === parentSubmenu);
                submenu.classList.toggle('active', isParent);
                submenu.classList.toggle('open', isParent);
                const icon = submenu.querySelector('.submenu-icon');
                if (icon) {
                    icon.classList.toggle('fa-minus', isParent);
                    icon.classList.toggle('fa-plus', !isParent);
                }
            });
        }
    
        // Keep the in-panel navigation pills synchronized.
        document.querySelectorAll('.dior-subtab-pill[data-doc-tab]').forEach(function (pill) {
            pill.classList.toggle('active', pill.getAttribute('data-doc-tab') === tabId);
        });
    
        // Switch only direct tab panels belonging to this dashboard.
        content.querySelectorAll(':scope > .dior-tab-panel').forEach(function (panel) {
            const isActive = panel === activePanel;
            panel.classList.toggle('active', isActive);
            if (isActive) {
                panel.removeAttribute('hidden');
                panel.style.setProperty('display', 'block', 'important');
                panel.style.setProperty('visibility', 'visible', 'important');
                panel.style.setProperty('opacity', '1', 'important');
            } else {
                panel.setAttribute('hidden', 'hidden');
                panel.style.setProperty('display', 'none', 'important');
            }
        });
    
        // Re-render existing table pagination when a tab becomes visible.
        if (typeof window.diorDoctorInitTablePagination === 'function') {
            activePanel.querySelectorAll('.dior-doc-table, .dior-table:not(.dior-no-global-pagination), .dior-clean-table').forEach(function (table) {
                if (typeof table._diorRenderPage === 'function') {
                    table._diorRenderPage(1);
                } else {
                    window.diorDoctorInitTablePagination(table, 5);
                }
            });
        }
    
        // Refresh the Income Report chart only when its existing tab is opened.
        if (tabId === 'doc-accounts-income-report' && typeof window.diorRenderIncomeChart === 'function') {
            window.setTimeout(window.diorRenderIncomeChart, 50);
        }
    
        // Preserve the existing notification read behavior using the real tab ID.
        if (tabId === 'doc-notifications-system') {
            document.querySelectorAll('.doc-unread-badge, .dior-notif-indicator').forEach(function (badge) {
                badge.style.display = 'none';
            });
            if (window.diorDocAjax && window.diorDocNonce) {
                const data = new FormData();
                data.append('action', 'dior_doctor_mark_all_notif_read');
                data.append('nonce', window.diorDocNonce);
                fetch(window.diorDocAjax, { method: 'POST', body: data }).catch(function () {});
            }
        }
    
        if (typeof window.diorDocCloseMobile === 'function') {
            window.diorDocCloseMobile();
        }
        return true;
    };

    function boot() {
        var app = document.getElementById('dior-doctor-app');
        if (!app || app.__diorDoctorNavigationReady) return;
        app.__diorDoctorNavigationReady = true;
        ensureMobileCloseButton();

        // Sidebar tab clicks are handled by the document capture listener below.
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


/* Capture Doctor sidebar clicks before other dashboard scripts can intercept them. */
(function () {
    if (document.__diorDoctorNavCaptureBound) return;
    document.__diorDoctorNavCaptureBound = true;

    document.addEventListener('click', function (event) {
        var target = event.target;
        var button = target && target.closest
            ? target.closest('#dior-doctor-app #dior-doc-sidebar .dior-nav-btn[data-tab]')
            : null;
        if (!button) return;

        var tabId = button.getAttribute('data-tab');
        if (!tabId || typeof window.diorDocSwitchTab !== 'function') return;

        event.preventDefault();
        event.stopPropagation();
        window.diorDocSwitchTab(tabId, true);
    }, true);
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
