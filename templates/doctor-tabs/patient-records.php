<?php
global $wpdb;
$pr_doctor_id = get_current_user_id();
$pr_is_admin = current_user_can('manage_options');
$pr_doctor_ids = class_exists('Dior_Doctor_Resolver')
    ? Dior_Doctor_Resolver::get_accessible_doctor_ids($pr_doctor_id)
    : [$pr_doctor_id];
$pr_linked_ids = $wpdb->get_col($wpdb->prepare(
    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wpddb_doctor_user_id' AND meta_value = %s",
    (string) $pr_doctor_id
));
$pr_doctor_ids = array_values(array_unique(array_filter(array_map('intval', array_merge((array) $pr_doctor_ids, (array) $pr_linked_ids)))));
$pr_patients_table = $wpdb->prefix . 'dior_patients';
$pr_appointments_table = $wpdb->prefix . 'dior_appointments';
$pr_prescriptions_table = $wpdb->prefix . 'dior_prescriptions';
$pr_today = current_time('Y-m-d');
$records = [];

if ($pr_is_admin) {
    $records = $wpdb->get_results(
        "SELECT p.*,
            (SELECT a.visit_type FROM {$pr_appointments_table} a
             WHERE (a.patient_id = p.user_id OR a.patient_id = p.id) AND a.is_deleted = 0
             ORDER BY a.appt_date DESC, a.created_at DESC LIMIT 1) AS diagnosis,
            (SELECT MIN(a.appt_date) FROM {$pr_appointments_table} a
             WHERE (a.patient_id = p.user_id OR a.patient_id = p.id) AND a.is_deleted = 0
               AND a.appt_date >= '{$pr_today}' AND a.status NOT IN ('Cancelled','Completed','No Show')) AS next_followup
         FROM {$pr_patients_table} p
         WHERE p.status <> 'deleted'
         ORDER BY p.created_at DESC",
        ARRAY_A
    );
} elseif (!empty($pr_doctor_ids)) {
    $pr_placeholders = implode(',', array_fill(0, count($pr_doctor_ids), '%d'));
    $pr_sql = "SELECT p.*,
        (SELECT a.visit_type FROM {$pr_appointments_table} a
         WHERE (a.patient_id = p.user_id OR a.patient_id = p.id)
           AND a.doctor_id IN ({$pr_placeholders}) AND a.is_deleted = 0
         ORDER BY a.appt_date DESC, a.created_at DESC LIMIT 1) AS diagnosis,
        (SELECT MIN(a.appt_date) FROM {$pr_appointments_table} a
         WHERE (a.patient_id = p.user_id OR a.patient_id = p.id)
           AND a.doctor_id IN ({$pr_placeholders}) AND a.is_deleted = 0
           AND a.appt_date >= %s AND a.status NOT IN ('Cancelled','Completed','No Show')) AS next_followup
     FROM {$pr_patients_table} p
     WHERE p.status <> 'deleted'
       AND (
         EXISTS (SELECT 1 FROM {$pr_appointments_table} ax
                 WHERE (ax.patient_id = p.user_id OR ax.patient_id = p.id)
                   AND ax.doctor_id IN ({$pr_placeholders}) AND ax.is_deleted = 0)
         OR EXISTS (SELECT 1 FROM {$pr_prescriptions_table} rx
                    WHERE (rx.patient_id = p.user_id OR rx.patient_id = p.id)
                      AND rx.doctor_id IN ({$pr_placeholders}))
       )
     ORDER BY p.created_at DESC";
    $pr_args = array_merge($pr_doctor_ids, $pr_doctor_ids, [$pr_today], $pr_doctor_ids, $pr_doctor_ids);
    $records = $wpdb->get_results($wpdb->prepare($pr_sql, ...$pr_args), ARRAY_A);
}
?>
<section class="dior-tab-panel dior-appt-screen" id="tab-doc-patients-records" style="display: none;">
    <!-- Breadcrumb Header -->
    <div class="mb-4" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 class="mb-0 text-dark" style="font-size: 20px; font-weight: 700;">Patient Records</h4>
        </div>
        <div>
            <ul class="breadcrumb-list"
                style="display: flex; list-style: none; padding: 0; margin: 0; gap: 8px; align-items: center;">
                <li><a href="#"><i class="fas fa-house" style="font-size: 14px; color: #1A528E;"></i></a></li>
                <li><span style="color: #94A3B8;">/</span></li>
                <li><a href="javascript:void(0)"
                        style="font-size: 14px; color: #64748B; text-decoration: none; font-weight: 500;">Patients</a>
                </li>
                <li><span style="color: #94A3B8;">/</span></li>
                <li class="active"><span style="font-size: 14px; color: #1E293B; font-weight: 700;">Patient
                        Records</span></li>
            </ul>
        </div>
    </div>

    <!-- Subtab Navigation Bar -->
    <div class="dior-subtab-nav-bar px-1 mb-4">
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-patients-all"
            onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-patients-all');">
            <i class="fas fa-users"></i> All Patients
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-patients-add"
            onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-patients-add');">
            <i class="fas fa-user-plus"></i> Add Patient
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-patients-edit"
            onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-patients-edit');">
            <i class="fas fa-user-pen"></i> Edit Patient
        </button>
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-patients-records"
            onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-patients-records');">
            <i class="fas fa-clipboard-user"></i> Patient Records
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-patients-profile"
            onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-patients-profile');">
            <i class="fas fa-id-card"></i> Patient Profile
        </button>
    </div>

    <!-- Main Content -->
    <div class="view-appointment-card" style="margin-top: 10px;">
        <div class="va-header-container" style="border-bottom: 1px solid #E2E8F0; padding-bottom: 0;">
            <div class="va-title-container" style="position: relative; padding-bottom: 15px;">
                <h4 class="va-card-title"><i class="fas fa-clipboard-user text-primary me-2"></i>Patient Records</h4>
                <!-- Blue Accent Line -->
                <div
                    style="position: absolute; bottom: -1px; left: 0; width: 40px; height: 3px; background-color: #1A528E; border-radius: 3px 3px 0 0;">
                </div>
            </div>

            <div class="va-header-actions" style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <div class="input-group input-group-sm dior-bs-search-group" style="width: 240px;">

                    <input type="text" class="va-search-input form-control border-start-0 ps-0"
                        placeholder="Search records..." onkeyup="diorDocFilterPatientRecords(this.value)">
                </div>

                <div class="va-actions-group">
                    <button type="button" class="va-icon-btn btn" id="dior-pr-bulk-delete" style="display: none;"
                        title="Delete Selected">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button type="button" class="va-icon-btn btn" title="Export to Excel">
                        <i class="fas fa-file-arrow-down"></i>
                    </button>
                    <button type="button" class="va-icon-btn btn" title="Refresh" onclick="window.location.reload()">
                        <i class="fas fa-rotate-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="va-table-wrapper">
            <table class="va-table table-hover" id="dior-patient-records-table" data-no-datatables="true">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="pr-select-all" class="dior-checkbox form-check-input">
                        </th>
                        <th>PATIENT ID <i class="fas fa-sort"
                                style="color: #CBD5E1; margin-left: 5px; font-size: 10px;"></i></th>
                        <th>FULL NAME <i class="fas fa-sort"
                                style="color: #CBD5E1; margin-left: 5px; font-size: 10px;"></i></th>
                        <th>GENDER <i class="fas fa-sort"
                                style="color: #CBD5E1; margin-left: 5px; font-size: 10px;"></i></th>
                        <th>ADMISSION DATE <i class="fas fa-sort"
                                style="color: #CBD5E1; margin-left: 5px; font-size: 10px;"></i></th>
                        <th>DIAGNOSIS <i class="fas fa-sort"
                                style="color: #CBD5E1; margin-left: 5px; font-size: 10px;"></i></th>
                        <th>NEXT FOLLOW-UP <i class="fas fa-sort"
                                style="color: #CBD5E1; margin-left: 5px; font-size: 10px;"></i></th>
                        <th>STATUS <i class="fas fa-sort"
                                style="color: #CBD5E1; margin-left: 5px; font-size: 10px;"></i></th>
                        <th style="text-align: center;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)): ?>
                        <?php foreach ($records as $r):
                            $patient_id = (int) ($r['user_id'] ?? 0);
                            $patient_uid = $r['patient_uid'] ?? ('DM-' . $patient_id);
                            $patient_name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: 'Patient';
                            $gender = ucfirst(strtolower($r['gender'] ?? ''));
                            $diagnosis = $r['diagnosis'] ?? '';
                            $admission_raw = $r['created_at'] ?? '';
                            $admission_date = $admission_raw ? mysql2date('M j, Y', $admission_raw) : '—';
                            $followup_raw = $r['next_followup'] ?? '';
                            $followup_date = $followup_raw ? mysql2date('M j, Y', $followup_raw) : '—';
                            $status = strtolower($r['status'] ?? 'active');
                            $status_class = $status === 'active' ? 'col-green' : ($status === 'deleted' ? 'col-indigo' : 'col-blue');
                            ?>
                            <tr class="pr-row" data-patient-id="<?php echo esc_attr($patient_id); ?>"
                                data-search="<?php echo esc_attr(strtolower($patient_name . ' ' . $patient_uid . ' ' . $diagnosis)); ?>">
                                <td data-label="Select" style="text-align:center;">
                                    <input type="checkbox" class="dior-checkbox pr-row-checkbox form-check-input"
                                        value="<?php echo esc_attr($patient_id); ?>">
                                </td>
                                <td data-label="Patient ID"><?php echo esc_html($patient_uid); ?></td>
                                <td data-label="Patient" style="font-weight:500;color:#1E293B;">
                                    <?php echo esc_html($patient_name); ?></td>
                                <td data-label="Gender">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <?php if ($gender): ?><i
                                                class="fas <?php echo $gender === 'Male' ? 'fa-mars' : ($gender === 'Female' ? 'fa-venus' : 'fa-genderless'); ?>"
                                                style="color:<?php echo $gender === 'Female' ? '#EC4899' : '#3B82F6'; ?>;"></i><?php endif; ?>
                                        <span><?php echo esc_html($gender ?: '—'); ?></span>
                                    </div>
                                </td>
                                <td data-label="Admission date">
                                    <div style="display:flex;align-items:center;gap:6px;"><i class="far fa-calendar"
                                            style="color:#3B82F6;"></i><span><?php echo esc_html($admission_date); ?></span>
                                    </div>
                                </td>
                                <td data-label="Diagnosis"><?php echo esc_html($diagnosis ?: '—'); ?></td>
                                <td data-label="Follow-up date">
                                    <div style="display:flex;align-items:center;gap:6px;"><i class="far fa-calendar"
                                            style="color:#3B82F6;"></i><span><?php echo esc_html($followup_date); ?></span>
                                    </div>
                                </td>
                                <td data-label="Status"><span
                                        class="dior-st <?php echo esc_attr($status_class); ?>"><?php echo esc_html(ucfirst($status)); ?></span>
                                </td>
                                <td data-label="Actions">
                                    <div class="va-row-actions" style="justify-content:center;">
                                        <button type="button" class="va-action-btn-sm va-btn-edit btn pr-edit-btn"
                                            data-patient-id="<?php echo esc_attr($patient_id); ?>" title="Edit patient"><i
                                                class="fas fa-pen"></i></button>
                                        <button type="button" class="va-action-btn-sm va-btn-delete btn pr-delete-btn"
                                            data-patient-id="<?php echo esc_attr($patient_id); ?>"
                                            title="Archive patient record"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="pr-empty-row">
                            <td colspan="9" style="text-align:center;padding:40px;color:#94a3b8;">No patient records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</section>

<style>
    /* Custom status badges for this specific table */
    .dior-st.col-green {
        background-color: #DCFCE7;
        color: #16A34A;
    }

    .dior-st.col-blue {
        background-color: #DBEAFE;
        color: #2563EB;
    }

    .dior-st.col-indigo {
        background-color: #E0E7FF;
        color: #1A528E;
    }

    .dior-st {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {

        #dior-doctor-app #dior-doc-content #tab-doc-patients-records,
        #dior-doctor-app #dior-doc-content #tab-doc-patients-records *,
        #dior-doctor-app #dior-doc-content #tab-doc-patients-records *::before,
        #dior-doctor-app #dior-doc-content #tab-doc-patients-records *::after {
            box-sizing: border-box !important;
        }

        #dior-doctor-app #dior-doc-content #tab-doc-patients-records.dior-tab-panel {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 14px 0 24px !important;
            overflow-x: clip;
        }

        #dior-doctor-app #dior-doc-content #tab-doc-patients-records .view-appointment-card,
        #dior-doctor-app #dior-doc-content #tab-doc-patients-records .va-header-container {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        #dior-doctor-app #dior-doc-content #tab-doc-patients-records .view-appointment-card {
            padding-left: 0 !important;
            padding-right: 0 !important;
            overflow: hidden;
        }

        #tab-doc-patients-records>.mb-4:first-child {
            align-items: flex-start !important;
        }

        #tab-doc-patients-records>.mb-4:first-child .breadcrumb-list {
            display: none !important;
        }

        #tab-doc-patients-records .dior-subtab-nav-bar {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            padding: 0 !important;
        }

        #tab-doc-patients-records .dior-subtab-pill {
            width: 100%;
            min-height: 40px;
            margin: 0 !important;
            font-size: 12px;
            white-space: nowrap;
        }

        #tab-doc-patients-records .dior-subtab-pill:last-child {
            grid-column: 1 / -1;
        }

        #tab-doc-patients-records .va-header-container {
            align-items: stretch !important;
            flex-direction: column;
            padding: 12px 12px 10px !important;
        }

        #tab-doc-patients-records .va-title-container {
            padding-bottom: 10px !important;
        }

        #tab-doc-patients-records .va-header-actions,
        #tab-doc-patients-records .dior-bs-search-group {
            width: 100% !important;
            margin: 0 !important;
        }

        #tab-doc-patients-records .va-header-actions {
            flex-wrap: wrap;
        }

        #tab-doc-patients-records .va-actions-group {
            width: 100%;
        }

        #tab-doc-patients-records .va-actions-group .va-icon-btn {
            flex: 1;
        }

        #tab-doc-patients-records .va-table-wrapper {
            overflow: hidden !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        #tab-doc-patients-records #dior-patient-records-table {
            display: block;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            table-layout: fixed !important;
            background: transparent !important;
        }

        #tab-doc-patients-records #dior-patient-records-table thead {
            display: none;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody {
            display: grid;
            gap: 10px;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody tr.pr-row {
            position: relative;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 14px;
            padding: 14px 12px 12px 44px;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody tr.pr-row[data-filter-hidden="true"] {
            display: none;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td {
            min-width: 0;
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
            white-space: normal !important;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(1) {
            position: absolute;
            left: 12px;
            top: 16px;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(2) {
            grid-column: 1 / 2;
            grid-row: 2;
            font-size: 11px !important;
            color: #64748B !important;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(2)::before {
            content: attr(data-label) ": ";
            font-weight: 600;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(3) {
            grid-column: 1 / -1;
            grid-row: 1;
            padding-right: 88px !important;
            font-size: 15px !important;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(4) {
            grid-column: 2;
            grid-row: 2;
            justify-self: end;
            color: #64748B !important;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(5),
        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(6),
        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(7) {
            display: block;
            font-size: 12px !important;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(5)::before,
        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(6)::before,
        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(7)::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 4px;
            color: #94A3B8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(6) {
            grid-column: 1 / -1;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(8) {
            position: absolute;
            top: 13px;
            right: 12px;
        }

        #tab-doc-patients-records #dior-patient-records-table tbody td:nth-child(9) {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid #F1F5F9 !important;
            padding-top: 10px !important;
        }

        #tab-doc-patients-records .va-row-actions {
            gap: 8px;
        }
    }
</style>

<script>
    (function () {
        'use strict';
        var root = document.getElementById('tab-doc-patients-records');
        if (!root || root.dataset.prInitialized === '1') return;
        root.dataset.prInitialized = '1';

        var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        var nonce = '<?php echo esc_js(wp_create_nonce('dior_doctor_nonce')); ?>';
        var table = root.querySelector('#dior-patient-records-table');
        var tbody = table ? table.querySelector('tbody') : null;
        var selectAll = root.querySelector('#pr-select-all');
        var bulkButton = root.querySelector('#dior-pr-bulk-delete');
        var searchInput = root.querySelector('.va-search-input');
        var info = root.querySelector('#pr-showing-info');
        var pagination = root.querySelector('#pr-pagination');
        var perPage = 10;
        var currentPage = 1;
        var rows = tbody ? Array.from(tbody.querySelectorAll('tr.pr-row')) : [];

        function checkedBoxes() {
            return Array.from(tbody.querySelectorAll('.pr-row-checkbox:checked'));
        }
        function updateSelection() {
            var checked = checkedBoxes();
            if (bulkButton) bulkButton.style.display = checked.length ? 'inline-flex' : 'none';
            if (selectAll) {
                selectAll.checked = rows.length > 0 && checked.length === rows.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < rows.length;
            }
        }
        function visibleRows() {
            var term = (searchInput ? searchInput.value : '').toLowerCase().trim();
            return rows.filter(function (row) {
                return !term || (row.dataset.search || row.textContent.toLowerCase()).indexOf(term) !== -1;
            });
        }
        function renderPage(page) {
            var visible = visibleRows();
            var total = visible.length;
            var pages = Math.max(1, Math.ceil(total / perPage));
            currentPage = Math.min(Math.max(1, page || 1), pages);
            var start = (currentPage - 1) * perPage;
            rows.forEach(function (row) { row.style.display = 'none'; });
            visible.slice(start, start + perPage).forEach(function (row) { row.style.display = ''; });
            if (info) info.textContent = total ? 'Showing ' + (start + 1) + ' to ' + Math.min(start + perPage, total) + ' of ' + total + ' patient records' : 'No patient records found';
            renderPagination(pages);
            updateSelection();
        }
        function renderPagination(pages) {
            if (!pagination) return;
            pagination.innerHTML = '';
            if (pages <= 1) return;
            function addButton(label, target, disabled, active) {
                var button = document.createElement('button');
                button.type = 'button';
                button.textContent = label;
                button.disabled = !!disabled;
                button.className = 'btn btn-sm ' + (active ? 'btn-primary' : 'btn-outline-secondary');
                button.style.minWidth = '34px';
                button.addEventListener('click', function () { renderPage(target); });
                pagination.appendChild(button);
            }
            addButton('‹', currentPage - 1, currentPage === 1, false);
            for (var p = 1; p <= pages; p++) addButton(String(p), p, false, p === currentPage);
            addButton('›', currentPage + 1, currentPage === pages, false);
        }
        function requestArchive(ids) {
            if (!ids.length) return;
            var confirmAction = window.Swal
                ? Swal.fire({ icon: 'warning', title: 'Archive patient record(s)?', text: 'Records will be hidden from active lists. Patient accounts and clinical history will be retained.', showCancelButton: true, confirmButtonText: 'Yes, archive', confirmButtonColor: '#dc2626' })
                : Promise.resolve({ isConfirmed: window.confirm('Archive selected patient record(s)? Patient accounts and clinical history will be retained.') });
            Promise.resolve(confirmAction).then(function (answer) {
                if (!answer || !answer.isConfirmed) return;
                var data = new FormData();
                data.append('nonce', nonce);
                data.append('action', ids.length === 1 ? 'dior_doc_patient_delete' : 'dior_doc_patient_bulk_delete');
                if (ids.length === 1) data.append('patient_id', ids[0]);
                else ids.forEach(function (id) { data.append('patient_ids[]', id); });
                if (bulkButton) bulkButton.disabled = true;
                fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
                    .then(function (response) { return response.json(); })
                    .then(function (result) {
                        if (!result || !result.success) throw new Error(result && result.data && result.data.message ? result.data.message : 'Unable to archive patient record.');
                        var removed = ids.map(String);
                        rows = rows.filter(function (row) {
                            if (removed.indexOf(String(row.dataset.patientId)) !== -1) {
                                row.remove();
                                return false;
                            }
                            return true;
                        });
                        renderPage(currentPage);
                        if (window.Swal) Swal.fire({ icon: 'success', title: 'Updated', text: result.data.message || 'Patient record archived.' });
                    })
                    .catch(function (error) {
                        if (window.Swal) Swal.fire({ icon: 'error', title: 'Archive failed', text: error.message });
                        else window.alert(error.message);
                    })
                    .finally(function () { if (bulkButton) bulkButton.disabled = false; });
            });
        }

        if (selectAll) selectAll.addEventListener('change', function () {
            var visible = visibleRows();
            visible.forEach(function (row) {
                var checkbox = row.querySelector('.pr-row-checkbox');
                if (checkbox) checkbox.checked = selectAll.checked;
            });
            updateSelection();
        });
        if (tbody) {
            tbody.addEventListener('change', function (event) {
                if (event.target.matches('.pr-row-checkbox')) updateSelection();
            });
            tbody.addEventListener('click', function (event) {
                var deleteButton = event.target.closest('.pr-delete-btn');
                if (deleteButton) {
                    requestArchive([deleteButton.dataset.patientId]);
                    return;
                }
                var editButton = event.target.closest('.pr-edit-btn');
                if (editButton) {
                    window.diorSelectedPatientId = editButton.dataset.patientId;
                    if (typeof window.diorDocSwitchTab === 'function') window.diorDocSwitchTab('doc-patients-edit');
                }
            });
        }
        if (bulkButton) bulkButton.addEventListener('click', function () {
            requestArchive(checkedBoxes().map(function (checkbox) { return checkbox.value; }));
        });
        if (searchInput) searchInput.addEventListener('input', function () { renderPage(1); });
        renderPage(1);
    })();
</script>