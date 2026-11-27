/**
 * DIOR Medical — Pharmacy (Medicine List / Add Medicine)
 *
 * Owns every action that has to survive a page reload:
 *  - Add / edit a medicine (AJAX → wp_dior_medicines)
 *  - Delete one row, or the checked rows from the toolbar
 *  - Export the visible list to CSV
 *  - Refresh the list without a full page reload
 *
 * Everything is scoped to `#dior-doctor-app` and degrades to a read-only view
 * (with a clear message) when the AJAX endpoint or nonce is unavailable, so a
 * cached page can never look like it saved something.
 */
(function () {
    'use strict';

    var LIST_TAB = 'doc-pharmacy-list';
    var ADD_TAB = 'doc-pharmacy-add';
    var TABLE_SELECTOR = '#tab-doc-pharmacy-list table.va-table';
    var OVERLAY_ID = 'dior-pharmacy-overlay';

    function vars() {
        return window.dior_doctor_vars || {};
    }

    function ajaxUrl() {
        return vars().ajax_url || '';
    }

    function nonce() {
        var field = document.getElementById('dior-pharmacy-nonce');
        return (field && field.value) || vars().nonce || '';
    }

    function hasBackend() {
        return !!(ajaxUrl() && nonce());
    }

    function table() {
        return document.querySelector(TABLE_SELECTOR);
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function toast(title, icon) {
        if (!window.Swal) return;
        window.Swal.fire({
            title: title,
            icon: icon || 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: icon === 'error' ? 4000 : 2600,
            timerProgressBar: true
        });
    }

    function request(action, payload) {
        if (!hasBackend()) {
            return Promise.reject(new Error('Pharmacy is not connected to the server. Please reload the dashboard.'));
        }

        var body = new FormData();
        body.append('action', 'dior_doc_' + action);
        body.append('nonce', nonce());
        Object.keys(payload || {}).forEach(function (key) {
            var value = payload[key];
            if (Array.isArray(value)) {
                value.forEach(function (item) { body.append(key + '[]', item); });
            } else if (value !== undefined && value !== null) {
                body.append(key, value);
            }
        });

        return fetch(ajaxUrl(), { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (response) {
                return response.json().catch(function () {
                    throw new Error('The server returned an unexpected response.');
                });
            })
            .then(function (json) {
                if (!json || !json.success) {
                    throw new Error((json && json.data && json.data.message) || 'The request could not be completed.');
                }
                return json.data || {};
            });
    }

    // ── Formatting ───────────────────────────────────────────────────────────
    function formatMoney(value) {
        var number = parseFloat(value);
        if (isNaN(number)) number = 0;
        return '$' + number.toFixed(2).replace(/\.00$/, '');
    }

    function formatDate(value, locale) {
        if (!value) return '—';
        var parts = String(value).split('-');
        if (parts.length !== 3) return String(value);
        var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        if (isNaN(date.getTime())) return String(value);
        try {
            return date.toLocaleDateString(locale || undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        } catch (e) {
            return date.toDateString();
        }
    }

    function dateCell(value, locale) {
        if (!value) {
            return '<span class="dior-pharm-muted">—</span>';
        }
        return '<i class="far fa-calendar va-icon-blue"></i> ' + escapeHtml(formatDate(value, locale));
    }

    function stockCell(stock) {
        var amount = parseInt(stock, 10);
        if (isNaN(amount)) amount = 0;
        var cls = amount <= 20 ? ' is-low' : '';
        return '<span class="dior-pharm-stock' + cls + '">' + amount + '</span>';
    }

    function buildRow(row) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-medicine-id', row.id || 0);

        var values = [
            { label: 'Medicine No', html: escapeHtml(row.medicine_no || '—') },
            { label: 'Medicine Name', html: '<strong>' + escapeHtml(row.medicine_name || '') + '</strong>' },
            { label: 'Category', html: escapeHtml(row.category || '—') },
            { label: 'Company', html: escapeHtml(row.company_name || '—') },
            { label: 'Purchase Date', html: dateCell(row.purchase_date, row.locale) },
            { label: 'Price', html: escapeHtml(formatMoney(row.price)) },
            { label: 'Expiry Date', html: dateCell(row.expiry_date, row.locale) },
            { label: 'Stock', html: stockCell(row.stock) }
        ];

        var cells = '<td class="text-center"><input type="checkbox" class="va-checkbox form-check-input" ' +
            'aria-label="Select ' + escapeHtml(row.medicine_name || 'medicine') + '"></td>';
        values.forEach(function (cell) {
            cells += '<td data-label="' + cell.label + '">' + cell.html + '</td>';
        });
        cells += '<td data-label="Actions"><div class="va-row-actions">' +
            '<button type="button" class="va-action-btn-sm va-btn-edit btn" title="Edit medicine" ' +
            'aria-label="Edit ' + escapeHtml(row.medicine_name || 'medicine') + '"><i class="fas fa-pen"></i></button>' +
            '<button type="button" class="va-action-btn-sm va-btn-delete btn" title="Delete medicine" ' +
            'aria-label="Delete ' + escapeHtml(row.medicine_name || 'medicine') + '"><i class="fas fa-trash"></i></button>' +
            '</div></td>';

        tr.innerHTML = cells;
        return tr;
    }

    // Update an existing <tr> from a payload so a save never depends on a
    // full page round-trip.
    function applyRowData(tr, data) {
        if (!tr || !data) return;
        var cells = tr.querySelectorAll('td');
        if (cells.length < 9) return;
        var set = function (index, html) {
            if (cells[index]) cells[index].innerHTML = html;
        };

        set(1, escapeHtml(data.medicine_no || '—'));
        set(2, '<strong>' + escapeHtml(data.medicine_name || '') + '</strong>');
        set(3, '<span class="dior-pharm-cat">' + escapeHtml(data.category || '—') + '</span>');
        set(4, escapeHtml(data.company_name || '—'));
        set(5, dateCell(data.purchase_date));
        set(6, escapeHtml(formatMoney(data.price)));
        set(7, dateCell(data.expiry_date));
        set(8, stockCell(data.stock));

        if (data.purchase_date) tr.setAttribute('data-purchase-date', data.purchase_date);
        if (data.expiry_date) tr.setAttribute('data-expiry-date', data.expiry_date);
    }

    function insertRow(data) {
        var tableEl = table();
        if (!tableEl) return;
        var tbody = tableEl.querySelector('tbody');
        if (!tbody) return;

        var empty = tbody.querySelector('.dior-pharm-empty-row');
        if (empty) empty.remove();

        var row = buildRow(data);
        tbody.appendChild(row);
        if (window.diorRefreshStaticTable) window.diorRefreshStaticTable(tableEl);
    }

    function emptyRow() {
        var tr = document.createElement('tr');
        tr.className = 'dior-pharm-empty-row';
        tr.innerHTML = '<td colspan="10" class="dior-pharm-empty">' +
            '<i class="fas fa-pills"></i>' +
            '<strong>No medicines in your inventory yet</strong>' +
            '<span>Use <em>Add Medicine</em> to record the first item.</span>' +
            '<button type="button" class="btn dior-pharm-empty-cta" data-dior-pharmacy-go="' + ADD_TAB + '">' +
            '<i class="fas fa-circle-plus"></i> Add Medicine</button>' +
            '</td>';
        return tr;
    }

    function readRowData(tr) {
        if (!tr) return null;
        var cells = tr.querySelectorAll('td');
        if (cells.length < 9) return null;

        function text(index) {
            return (cells[index].textContent || '').replace(/\s+/g, ' ').trim();
        }
        function stripDash(value) {
            return value === '—' ? '' : value;
        }

        return {
            id: tr.getAttribute('data-medicine-id') || '',
            medicine_no: stripDash(text(1)),
            medicine_name: stripDash(text(2)),
            category: stripDash(text(3)),
            company_name: stripDash(text(4)),
            purchase_date_raw: tr.getAttribute('data-purchase-date') || '',
            purchase_date: stripDash(text(5)),
            price: text(6).replace(/[^0-9.]/g, ''),
            expiry_date_raw: tr.getAttribute('data-expiry-date') || '',
            expiry_date: stripDash(text(7)),
            stock: text(8).replace(/[^0-9]/g, '')
        };
    }

    // ── Edit overlay (project styled) ────────────────────────────────────────
    function overlay(title) {
        var existing = document.getElementById(OVERLAY_ID);
        if (existing) existing.remove();

        var wrap = document.createElement('div');
        wrap.className = 'dior-pharm-overlay';
        wrap.id = OVERLAY_ID;
        wrap.setAttribute('role', 'dialog');
        wrap.setAttribute('aria-modal', 'true');
        wrap.innerHTML =
            '<div class="dior-pharm-dialog" role="document">' +
            '<div class="dior-pharm-dialog-head">' +
            '<h3 class="dior-pharm-dialog-title">' + escapeHtml(title) + '</h3>' +
            '<button type="button" class="dior-pharm-dialog-close" aria-label="Close">' +
            '<i class="fas fa-xmark"></i></button>' +
            '</div>' +
            '<form class="dior-pharm-form" novalidate></form>' +
            '</div>';

        document.body.appendChild(wrap);
        document.body.classList.add('dior-pharm-lock');

        function close() {
            wrap.remove();
            document.body.classList.remove('dior-pharm-lock');
            document.removeEventListener('keydown', onKey);
        }
        function onKey(event) {
            if (event.key === 'Escape') close();
        }

        wrap.querySelector('.dior-pharm-dialog-close').addEventListener('click', close);
        wrap.addEventListener('mousedown', function (event) {
            if (event.target === wrap) close();
        });
        document.addEventListener('keydown', onKey);

        return { root: wrap, form: wrap.querySelector('.dior-pharm-form'), close: close };
    }

    function fieldTemplate(id, label, inputHtml, required) {
        return '<div class="dior-pharm-field">' +
            '<label class="dior-pharm-label" for="' + id + '">' + label +
            (required ? ' <span class="dior-pharm-req">*</span>' : '') + '</label>' +
            inputHtml +
            '</div>';
    }

    var CATEGORIES = ['Tablet', 'Capsule', 'Syrup', 'Injectable', 'Ointment', 'Drops', 'Inhaler', 'Other'];

    function categoriesSelect(id, current) {
        var options = '<option value="">Select Category</option>';
        CATEGORIES.forEach(function (category) {
            options += '<option value="' + category + '"' +
                (category === current ? ' selected' : '') + '>' + category + '</option>';
        });
        // Preserve a legacy category that is not in the short list.
        if (current && CATEGORIES.indexOf(current) === -1) {
            options += '<option value="' + escapeHtml(current) + '" selected>' + escapeHtml(current) + '</option>';
        }
        return '<select class="dior-pharm-input" id="' + id + '" name="category">' + options + '</select>';
    }

    function toInputDate(value) {
        if (!value) return '';
        var raw = String(value).trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw;
        var parsed = new Date(raw);
        if (isNaN(parsed.getTime())) return '';
        var month = ('0' + (parsed.getMonth() + 1)).slice(-2);
        var day = ('0' + parsed.getDate()).slice(-2);
        return parsed.getFullYear() + '-' + month + '-' + day;
    }

    function openEditModal(tr) {
        var data = readRowData(tr);
        if (!data) return;

        var ui = overlay('Edit Medicine');
        ui.form.innerHTML =
            '<div class="dior-pharm-grid">' +
            fieldTemplate('dior-pharm-edit-no', 'No', '<input class="dior-pharm-input" id="dior-pharm-edit-no" name="medicine_no" type="text" value="' + escapeHtml(data.medicine_no) + '">') +
            fieldTemplate('dior-pharm-edit-name', 'Medicine Name', '<input class="dior-pharm-input" id="dior-pharm-edit-name" name="medicine_name" type="text" value="' + escapeHtml(data.medicine_name) + '" required>', true) +
            fieldTemplate('dior-pharm-edit-category', 'Category', categoriesSelect('dior-pharm-edit-category', data.category), true) +
            fieldTemplate('dior-pharm-edit-company', 'Company Name', '<input class="dior-pharm-input" id="dior-pharm-edit-company" name="company_name" type="text" value="' + escapeHtml(data.company_name) + '">') +
            fieldTemplate('dior-pharm-edit-purchase', 'Purchase Date', '<input class="dior-pharm-input" id="dior-pharm-edit-purchase" name="purchase_date" type="date" value="' + escapeHtml(toInputDate(data.purchase_date_raw || data.purchase_date)) + '">') +
            fieldTemplate('dior-pharm-edit-price', 'Price', '<input class="dior-pharm-input" id="dior-pharm-edit-price" name="price" type="number" min="0" step="0.01" value="' + escapeHtml(data.price) + '">') +
            fieldTemplate('dior-pharm-edit-expiry', 'Expiry Date', '<input class="dior-pharm-input" id="dior-pharm-edit-expiry" name="expiry_date" type="date" value="' + escapeHtml(toInputDate(data.expiry_date_raw || data.expiry_date)) + '">') +
            fieldTemplate('dior-pharm-edit-stock', 'Stock', '<input class="dior-pharm-input" id="dior-pharm-edit-stock" name="stock" type="number" min="0" step="1" value="' + escapeHtml(data.stock) + '">') +
            '</div>' +
            '<div class="dior-pharm-actions">' +
            '<button type="submit" class="dior-pharm-btn dior-pharm-btn-primary">Save Changes</button>' +
            '<button type="button" class="dior-pharm-btn dior-pharm-btn-ghost" data-dior-pharm-cancel>Cancel</button>' +
            '</div>';

        ui.form.querySelector('[data-dior-pharm-cancel]').addEventListener('click', ui.close);

        ui.form.addEventListener('submit', function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var submit = form.querySelector('[type="submit"]');
            var payload = {
                id: data.id,
                medicine_no: form.medicine_no.value,
                medicine_name: form.medicine_name.value,
                category: form.category.value,
                company_name: form.company_name.value,
                purchase_date: form.purchase_date.value,
                expiry_date: form.expiry_date.value,
                price: form.price.value,
                stock: form.stock.value
            };

            if (!payload.medicine_name.trim()) {
                toast('Medicine name is required.', 'error');
                form.medicine_name.focus();
                return;
            }
            if (!payload.category) {
                toast('Please choose a category.', 'error');
                form.category.focus();
                return;
            }

            submit.disabled = true;
            submit.textContent = 'Saving...';

            request('medicine_save', payload).then(function () {
                ui.close();
                applyRowData(tr, payload);
                if (window.diorRefreshStaticTable) window.diorRefreshStaticTable(table());
                toast('Medicine updated.');
            }).catch(function (error) {
                submit.disabled = false;
                submit.textContent = 'Save Changes';
                toast(error.message, 'error');
            });
        });

        var firstInput = ui.form.querySelector('input[name="medicine_name"]');
        if (firstInput) firstInput.focus();
    }

    // ── Delete ───────────────────────────────────────────────────────────────
    function deleteRows(rows, tableEl) {
        var list = Array.prototype.slice.call(rows || []).filter(function (row) {
            return row && row.parentNode;
        });
        if (!list.length) return;

        var ids = list.map(function (row) {
            return row.getAttribute('data-medicine-id');
        }).filter(function (id) {
            return !!id && id !== '0';
        });

        if (!ids.length) {
            toast('These records are not linked to the inventory yet. Reload the page and try again.', 'error');
            return;
        }

        request('medicine_bulk_delete', { ids: ids }).then(function (data) {
            list.forEach(function (row) { row.remove(); });
            var tableEl2 = tableEl || table();
            if (tableEl2 && window.diorRefreshStaticTable) {
                window.diorRefreshStaticTable(tableEl2);
            }
            toast(data.message || 'Medicine deleted.');
            ensureEmptyState();
        }).catch(function (error) {
            toast(error.message, 'error');
        });
    }

    function ensureEmptyState() {
        var tableEl = table();
        if (!tableEl) return;
        var tbody = tableEl.querySelector('tbody');
        if (!tbody) return;
        var rows = tbody.querySelectorAll('tr:not(.dior-pharm-empty-row)');
        var existing = tbody.querySelector('.dior-pharm-empty-row');
        if (!rows.length && !existing) {
            tbody.appendChild(emptyRow());
        } else if (rows.length && existing) {
            existing.remove();
        }
    }

    // ── Reload / export / refresh ────────────────────────────────────────────
    function reloadTable() {
        return fetch(window.location.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var fresh = doc.querySelector(TABLE_SELECTOR);
                var current = table();
                if (!fresh || !current) {
                    window.location.reload();
                    return;
                }
                var freshBody = fresh.querySelector('tbody');
                var body = current.querySelector('tbody');
                if (freshBody && body) {
                    body.innerHTML = freshBody.innerHTML;
                    if (window.diorRefreshStaticTable) window.diorRefreshStaticTable(current);
                    ensureEmptyState();
                }
            })
            .catch(function () {
                window.location.reload();
            });
    }

    function exportCsv(trigger) {
        var tableEl = table();
        if (!tableEl) return;
        var rows = tableEl.querySelectorAll('tbody > tr:not(.dior-pharm-empty-row)');
        if (!rows.length) {
            toast('There is nothing to export yet.', 'error');
            return;
        }

        var csv = [];
        var header = ['Medicine No', 'Medicine Name', 'Category', 'Company', 'Purchase Date', 'Price', 'Expiry Date', 'Stock'];
        csv.push(header.map(quote).join(','));

        rows.forEach(function (row) {
            var cells = row.querySelectorAll('td');
            if (cells.length < 9) return;
            var line = [];
            for (var i = 1; i <= 8; i++) {
                var value = (cells[i].textContent || '').replace(/\s+/g, ' ').trim();
                line.push(quote(value === '—' ? '' : value));
            }
            csv.push(line.join(','));
        });

        var blob = new Blob(['\ufeff' + csv.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        var filename = 'medicine-list-' + new Date().toISOString().slice(0, 10) + '.csv';
        if (window.navigator && window.navigator.msSaveBlob) {
            window.navigator.msSaveBlob(blob, filename);
        } else {
            var link = document.createElement('a');
            var url = URL.createObjectURL(blob);
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
        }

        if (trigger) {
            trigger.classList.add('is-done');
            setTimeout(function () { trigger.classList.remove('is-done'); }, 900);
        }
        toast('Medicine list exported.');
    }

    function quote(value) {
        return '"' + String(value === null || value === undefined ? '' : value).replace(/"/g, '""') + '"';
    }

    function refresh(trigger) {
        var icon = trigger ? trigger.querySelector('i') : null;
        if (trigger) trigger.disabled = true;
        if (icon) icon.classList.add('fa-spin');

        var done = function () {
            if (trigger) trigger.disabled = false;
            if (icon) icon.classList.remove('fa-spin');
        };

        reloadTable().then(done).catch(done).then(function () {
            toast('Medicine list refreshed.');
        });
    }

    // ── Toolbar wiring ───────────────────────────────────────────────────────
    function bindToolbar() {
        var panel = document.getElementById('tab-doc-pharmacy-list');
        if (!panel || panel.getAttribute('data-dior-pharm-bound') === '1') return;
        panel.setAttribute('data-dior-pharm-bound', '1');

        panel.addEventListener('click', function (event) {
            var exportBtn = event.target.closest('.va-actions-group .va-btn-success');
            if (exportBtn) {
                event.preventDefault();
                event.stopPropagation();
                exportCsv(exportBtn);
                return;
            }

            var refreshBtn = event.target.closest('.va-actions-group .va-btn-info');
            if (refreshBtn) {
                event.preventDefault();
                event.stopPropagation();
                refresh(refreshBtn);
                return;
            }

            var editBtn = event.target.closest('#tab-doc-pharmacy-list .va-btn-edit');
            if (editBtn) {
                var row = editBtn.closest('tr');
                if (row) {
                    event.preventDefault();
                    event.stopPropagation();
                    // The generic table layer only understands text inputs, so
                    // pharmacy rows use their own field-accurate modal.
                    event.stopImmediatePropagation();
                    openEditModal(row);
                }
            }
        }, true);

        // Empty-state CTA jumps to the Add Medicine subtab.
        panel.addEventListener('click', function (event) {
            var go = event.target.closest('[data-dior-pharmacy-go]');
            if (!go) return;
            event.preventDefault();
            switchTab(go.getAttribute('data-dior-pharmacy-go'));
        });
    }

    function switchTab(tabId) {
        if (typeof window.diorDocSwitchTab === 'function') {
            window.diorDocSwitchTab(tabId);
            return;
        }
        var panels = document.querySelectorAll('#dior-doc-content > .dior-tab-panel');
        panels.forEach(function (panel) {
            var isTarget = panel.id === 'tab-' + tabId;
            panel.classList.toggle('active', isTarget);
            panel.style.display = isTarget ? 'block' : 'none';
        });
    }

    // ── Add Medicine form ────────────────────────────────────────────────────
    function bindAddForm() {
        var form = document.getElementById('dior-pharmacy-add-form');
        if (!form || form.getAttribute('data-dior-pharm-bound') === '1') return;
        form.setAttribute('data-dior-pharm-bound', '1');

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitAddForm(form);
        });

        form.addEventListener('reset', function () {
            setTimeout(function () {
                clearErrors(form);
                var first = form.querySelector('input, select');
                if (first) first.focus();
            }, 0);
        });
    }

    function clearErrors(form) {
        form.querySelectorAll('.dior-pharm-field.has-error').forEach(function (field) {
            field.classList.remove('has-error');
        });
        form.classList.remove('is-submitting');
    }

    function markError(input) {
        var field = input.closest('.dior-pharm-field');
        if (field) field.classList.add('has-error');
        input.focus();
    }

    function submitAddForm(form) {
        if (form.classList.contains('is-submitting')) return;

        var payload = {
            medicine_no: form.medicine_no.value,
            medicine_name: form.medicine_name.value,
            category: form.category.value,
            company_name: form.company_name.value,
            purchase_date: form.purchase_date.value,
            expiry_date: form.expiry_date.value,
            price: form.price.value,
            stock: form.stock.value
        };

        clearErrors(form);

        if (!payload.medicine_name.trim()) {
            toast('Medicine name is required.', 'error');
            markError(form.medicine_name);
            return;
        }
        if (!payload.category) {
            toast('Please choose a category.', 'error');
            markError(form.category);
            return;
        }
        if (payload.purchase_date && payload.expiry_date && payload.expiry_date < payload.purchase_date) {
            toast('The expiry date cannot be earlier than the purchase date.', 'error');
            markError(form.expiry_date);
            return;
        }

        var submit = form.querySelector('[type="submit"]');
        if (submit) {
            submit.disabled = true;
            submit.dataset.label = submit.dataset.label || submit.innerHTML;
            submit.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Saving...';
        }
        form.classList.add('is-submitting');

        request('medicine_save', payload).then(function (data) {
            form.reset();
            clearErrors(form);
            if (submit) {
                submit.innerHTML = submit.dataset.label;
                submit.disabled = false;
            }
            insertRow({
                id: data.id || 0,
                medicine_no: (data.row && data.row.medicine_no) || '',
                medicine_name: payload.medicine_name,
                category: payload.category,
                company_name: payload.company_name,
                purchase_date: payload.purchase_date,
                expiry_date: payload.expiry_date,
                price: payload.price,
                stock: payload.stock
            });
            toast('Medicine added to the inventory.');
            switchTab(LIST_TAB);
        }).catch(function (error) {
            form.classList.remove('is-submitting');
            if (submit) {
                submit.innerHTML = submit.dataset.label;
                submit.disabled = false;
            }
            toast(error.message, 'error');
        });
    }

    // ── Boot ─────────────────────────────────────────────────────────────────
    function boot() {
        var app = document.getElementById('dior-doctor-app');
        if (!app) return;
        bindToolbar();
        bindAddForm();
        ensureEmptyState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Tabs are rendered/injected dynamically by the dashboard navigation layer,
    // so re-check when the DOM changes.
    if (window.MutationObserver) {
        var observer = new MutationObserver(function () {
            bindToolbar();
            bindAddForm();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    window.DiorPharmacy = {
        deleteRows: deleteRows,
        reload: reloadTable,
        refresh: refresh,
        exportCsv: exportCsv
    };
})();
