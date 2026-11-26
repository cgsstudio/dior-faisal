<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$doctor_id = get_current_user_id();
$is_admin  = current_user_can('administrator') || current_user_can('manage_options');
$table_appts    = $wpdb->prefix . 'dior_appointments';
$table_patients = $wpdb->prefix . 'dior_patients';

$cr_rows = [];
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_appts)) === $table_appts) {
    if ($is_admin) {
        $cr_rows = $wpdb->get_results(
            "SELECT a.*, p.first_name, p.last_name, p.avatar_url, u.display_name AS user_display_name, doc.display_name AS doctor_name
             FROM $table_appts a
             LEFT JOIN $table_patients p ON (p.user_id = a.patient_id OR p.id = a.patient_id)
             LEFT JOIN {$wpdb->users} u ON u.ID = a.patient_id
             LEFT JOIN {$wpdb->users} doc ON doc.ID = a.doctor_id
             WHERE a.is_deleted = 0
               AND NOT EXISTS (SELECT 1 FROM {$wpdb->usermeta} demo_patient WHERE demo_patient.user_id = a.patient_id AND demo_patient.meta_key = 'dior_demo_record' AND demo_patient.meta_value = '1')
               AND NOT EXISTS (SELECT 1 FROM {$wpdb->usermeta} demo_doctor WHERE demo_doctor.user_id = a.doctor_id AND demo_doctor.meta_key = 'dior_demo_record' AND demo_doctor.meta_value = '1')
             ORDER BY a.appt_date DESC, a.created_at DESC
             LIMIT 250",
            ARRAY_A
        );
    } else {
        $allowed_ids = class_exists('Dior_Doctor_Resolver')
            ? Dior_Doctor_Resolver::get_accessible_doctor_ids($doctor_id)
            : [$doctor_id];
        $allowed_ids = array_values(array_unique(array_filter(array_map('intval', (array) $allowed_ids))));
        if (empty($allowed_ids)) {
            $allowed_ids = [(int) $doctor_id];
        }
        $placeholders = implode(',', array_fill(0, count($allowed_ids), '%d'));
        $cr_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.first_name, p.last_name, p.avatar_url, u.display_name AS user_display_name, doc.display_name AS doctor_name
             FROM $table_appts a
             LEFT JOIN $table_patients p ON (p.user_id = a.patient_id OR p.id = a.patient_id)
             LEFT JOIN {$wpdb->users} u ON u.ID = a.patient_id
             LEFT JOIN {$wpdb->users} doc ON doc.ID = a.doctor_id
             WHERE a.is_deleted = 0 AND a.doctor_id IN ($placeholders)
               AND NOT EXISTS (SELECT 1 FROM {$wpdb->usermeta} demo_patient WHERE demo_patient.user_id = a.patient_id AND demo_patient.meta_key = 'dior_demo_record' AND demo_patient.meta_value = '1')
               AND NOT EXISTS (SELECT 1 FROM {$wpdb->usermeta} demo_doctor WHERE demo_doctor.user_id = a.doctor_id AND demo_doctor.meta_key = 'dior_demo_record' AND demo_doctor.meta_value = '1')
             ORDER BY a.appt_date DESC, a.created_at DESC
             LIMIT 250",
            ...$allowed_ids
        ), ARRAY_A);
    }
}

$total_consultations  = count($cr_rows);
$outpatient_visits    = 0;
$inpatient_admissions = 0;
$completed_count      = 0;
$monthly_opd          = array_fill(0, 12, 0);
$monthly_ipd          = array_fill(0, 12, 0);
$condition_counts     = [];
$current_year         = (int) current_time('Y');

foreach ($cr_rows as $r) {
    $vtype  = strtolower((string) ($r['visit_type'] ?? $r['type'] ?? ''));
    $status = strtolower((string) ($r['status'] ?? ''));
    $is_ipd = (strpos($vtype, 'inpatient') !== false || strpos($vtype, 'ipd') !== false);

    if ($is_ipd) {
        $inpatient_admissions++;
    } else {
        $outpatient_visits++;
    }
    if ($status === 'completed') {
        $completed_count++;
    }

    $dt = $r['appt_date'] ?? ($r['created_at'] ?? '');
    $ts = $dt ? strtotime($dt) : false;
    if ($ts && (int) date('Y', $ts) === $current_year) {
        $m_idx = (int) date('n', $ts) - 1;
        if ($is_ipd) {
            $monthly_ipd[$m_idx]++;
        } else {
            $monthly_opd[$m_idx]++;
        }
    }

    $cond = trim((string) ($r['condition_name'] ?? $r['condition'] ?? $r['visit_type'] ?? 'General Consultation'));
    if ($cond === '') {
        $cond = 'General Consultation';
    }
    $condition_counts[$cond] = ($condition_counts[$cond] ?? 0) + 1;
}

arsort($condition_counts);
$condition_counts = array_slice($condition_counts, 0, 5, true);
$disease_labels   = array_keys($condition_counts);
$disease_series   = array_values($condition_counts);

$opd_pct       = $total_consultations > 0 ? (int) round(($outpatient_visits / $total_consultations) * 100) : 0;
$ipd_pct       = $total_consultations > 0 ? (int) round(($inpatient_admissions / $total_consultations) * 100) : 0;
$recovery_rate = $total_consultations > 0 ? round(($completed_count / $total_consultations) * 100, 1) . '%' : '0%';
$recent_reports = array_slice($cr_rows, 0, 20);
?>
<section class="dior-tab-panel" id="tab-doc-analytics" style="display: none;">
    <!-- Breadcrumb Header -->
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="mb-0 text-dark fw-bold fs-4">Clinical Reports</h4>
        </div>
        <div>
            <ul class="va-breadcrumb-list">
                <li><a href="#"><i class="fas fa-house" style="font-size:14px;color:#1A528E;"></i></a></li>
                <li><span style="color:#94A3B8;">/</span></li>
                <li><a href="javascript:void(0)">Reports &amp; Analytics</a></li>
                <li><span style="color:#94A3B8;">/</span></li>
                <li class="active"><span>Clinical Reports</span></li>
            </ul>
        </div>
    </div>

    <!-- 4 Quick Stat KPI Cards -->
    <div class="row g-3 g-xl-4 mb-4">
        <!-- 1. Total Consultations -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 h-100 bg-white d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Consultations</span>
                        <h2 class="fw-bold mb-0" style="color:#1A528E;"><?php echo esc_html(number_format_i18n($total_consultations)); ?></h2>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                        style="width: 48px; height: 48px; background: rgba(26, 82, 142, 0.10); color: #1A528E; font-size: 20px;">
                        <i class="fas fa-briefcase-medical"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mt-3 pt-1">
                    <span class="badge rounded-pill px-2 py-1 small fw-semibold"
                        style="background: rgba(26, 82, 142, 0.10); color: #1A528E;">
                        <i class="fas fa-database me-1"></i>Live
                    </span>
                    <span class="text-muted small">All records</span>
                </div>
            </div>
        </div>

        <!-- 2. Outpatient Visits -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 h-100 bg-white d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Outpatient Visits</span>
                        <h2 class="fw-bold mb-0" style="color:#2C6CB1;"><?php echo esc_html(number_format_i18n($outpatient_visits)); ?></h2>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                        style="width: 48px; height: 48px; background: rgba(44, 108, 177, 0.10); color: #2C6CB1; font-size: 20px;">
                        <i class="far fa-user"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mt-3 pt-1">
                    <span class="badge rounded-pill px-2 py-1 small fw-semibold" style="background:rgba(44,108,177,.12);color:#2C6CB1;border:1px solid rgba(44,108,177,.16);">
                        <i class="fas fa-circle-check me-1"></i><?php echo esc_html($opd_pct); ?>% Total
                    </span>
                    <span class="text-muted small">OPD Volume</span>
                </div>
            </div>
        </div>

        <!-- 3. Inpatient Admissions -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 h-100 bg-white d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Inpatient Admissions</span>
                        <h2 class="fw-bold mb-0" style="color: #477EAB;"><?php echo esc_html(number_format_i18n($inpatient_admissions)); ?></h2>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                        style="width: 48px; height: 48px; background: rgba(71, 126, 171, 0.12); color: #477EAB; font-size: 20px;">
                        <i class="fas fa-bed"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mt-3 pt-1">
                    <span class="badge rounded-pill px-2 py-1 small fw-semibold"
                        style="background: rgba(71, 126, 171, 0.12); color: #477EAB;">
                        <i class="fas fa-hotel me-1"></i><?php echo esc_html($ipd_pct); ?>% Total
                    </span>
                    <span class="text-muted small">IPD Ward</span>
                </div>
            </div>
        </div>

        <!-- 4. Completion Rate -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 h-100 bg-white d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Completion Rate</span>
                        <h2 class="fw-bold mb-0" style="color: #7198BC;"><?php echo esc_html($recovery_rate); ?></h2>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                        style="width: 48px; height: 48px; background: rgba(113, 152, 188, 0.14); color: #7198BC; font-size: 20px;">
                        <i class="fas fa-shield-heart"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mt-3 pt-1">
                    <span class="badge rounded-pill px-2 py-1 small fw-semibold" style="background:rgba(113,152,188,.14);color:#477EAB;border:1px solid rgba(113,152,188,.2);">
                        <i class="fas fa-star me-1"></i>High Efficacy
                    </span>
                    <span class="text-muted small">Clinical Benchmark</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 g-xl-4 mb-4">
        <!-- Patient Visit Trends (Area Chart - Col 8) -->
        <div class="col-xl-8 col-lg-8 col-12">
            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3 border-0 bg-transparent p-0">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center fs-6">
                        <i class="fas fa-chart-line text-primary me-2"></i>Patient Visit Trends
                    </h5>
                    <span class="badge rounded-pill px-3 py-1 small fw-semibold"
                        style="background: rgba(26, 82, 142, 0.10); color: #1A528E;">Annual Stream</span>
                </div>
                <div class="p-0">
                    <div id="dior-clinical-visit-trends-chart" style="min-height: 325px;"></div>
                </div>
            </div>
        </div>

        <!-- Disease Distribution (Donut Chart - Col 4) -->
        <div class="col-xl-4 col-lg-4 col-12">
            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3 border-0 bg-transparent p-0">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center fs-6">
                        <i class="fas fa-chart-pie text-primary me-2"></i>Disease Distribution
                    </h5>
                    <span class="badge rounded-pill px-3 py-1 small fw-semibold"
                        style="background: rgba(71, 126, 171, 0.12); color: #477EAB;">Specialties</span>
                </div>
                <div class="p-0 d-flex align-items-center justify-content-center" style="min-height: 325px;">
                    <div id="dior-clinical-disease-distribution-chart" style="width: 100%; min-height: 310px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Master Table Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white mb-4">
        <!-- Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h4 class="fw-bold text-dark mb-1 fs-5">Recent Clinical Reports</h4>
                <div style="width: 48px; height: 3px; background: #1A528E; border-radius: 2px;"></div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Search Box -->
                <div class="input-group input-group-sm rounded-3 border overflow-hidden" style="width: 250px;">
                    <input type="text" id="dior-clinical-search-input" class="form-control border-0 shadow-none ps-2"
                        placeholder="Search records...">
                </div>

                <!-- Action Buttons -->
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-danger btn-sm rounded-3 px-3 shadow-sm"
                        id="dior-clinical-bulk-delete" style="display: none;" title="Delete selected items">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 shadow-sm fw-semibold"
                        id="dior-clinical-add-btn" title="Add new record">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-success btn-sm rounded-3 px-3 shadow-sm fw-semibold"
                        id="dior-clinical-export-btn" title="Export to Excel">
                        <i class="fas fa-file-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-info text-white btn-sm rounded-3 px-3 shadow-sm fw-semibold"
                        id="dior-clinical-refresh-btn" title="Refresh data">
                        <i class="fas fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Responsive Wrapper -->
        <div class="table-responsive mb-0">
            <table class="dior-table table align-middle text-nowrap mb-0 dior-no-table-interactions" id="dior-clinical-reports-table">
                <thead class="bg-light">
                    <tr class="text-secondary small text-uppercase">
                        <th style="width: 50px; text-align: center;">
                            <input type="checkbox" class="form-check-input" id="dior-clinical-check-all">
                        </th>
                        <th>PATIENT NAME <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>DOCTOR <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>DIAGNOSIS <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>VISIT DATE <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>STATUS <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th style="width: 100px; text-align: center;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="dior-clinical-reports-tbody">
                    <?php if (empty($recent_reports)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-file-medical fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                <span class="small fw-semibold">No clinical reports found.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_reports as $cr):
                            $p_name = trim((string) (($cr['first_name'] ?? '') . ' ' . ($cr['last_name'] ?? ''))) ?: ($cr['user_display_name'] ?? 'Patient');
                            $d_name = trim((string) ($cr['doctor_name'] ?? '')) ?: 'Attending Physician';
                            $diag   = trim((string) ($cr['condition_name'] ?? $cr['condition'] ?? $cr['visit_type'] ?? 'Consultation'));
                            $v_date = !empty($cr['appt_date']) ? wp_date('Y-m-d', strtotime($cr['appt_date'])) : '—';
                            $st_raw = trim((string) ($cr['status'] ?? 'Pending'));
                            $st_low = strtolower($st_raw);
                            $st_cls = 'bg-primary-subtle text-primary border-primary-subtle';
                            if ($st_low === 'completed') {
                                $st_cls = 'bg-success-subtle text-success border-success-subtle';
                            } elseif ($st_low === 'cancelled') {
                                $st_cls = 'bg-danger-subtle text-danger border-danger-subtle';
                            } elseif ($st_low === 'pending') {
                                $st_cls = 'bg-warning-subtle text-warning border-warning-subtle';
                            }
                            $avatar = !empty($cr['avatar_url']) ? $cr['avatar_url'] : ('https://ui-avatars.com/api/?name=' . urlencode($p_name) . '&background=1A528E&color=fff&size=80');
                        ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" class="form-check-input dior-clinical-row-check" value="<?php echo esc_attr($cr['appt_uid'] ?? ''); ?>">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo esc_url($avatar); ?>"
                                            class="rounded-circle object-fit-cover shadow-sm" style="width: 36px; height: 36px;" alt="<?php echo esc_attr($p_name); ?>">
                                        <span class="fw-semibold text-dark"><?php echo esc_html($p_name); ?></span>
                                    </div>
                                </td>
                                <td class="text-secondary"><?php echo esc_html($d_name); ?></td>
                                <td class="text-secondary"><?php echo esc_html($diag); ?></td>
                                <td class="text-secondary"><?php echo esc_html($v_date); ?></td>
                                <td>
                                    <span class="badge <?php echo esc_attr($st_cls); ?> border rounded-pill px-3 py-1 small fw-semibold">
                                        <?php echo esc_html($st_raw); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-2 p-1 px-2 dior-action-edit" data-appt-uid="<?php echo esc_attr($cr['appt_uid'] ?? ''); ?>" title="Edit record">
                                            <i class="fas fa-pen small"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-2 p-1 px-2 dior-action-del" data-appt-uid="<?php echo esc_attr($cr['appt_uid'] ?? ''); ?>" title="Delete record">
                                            <i class="far fa-trash-can small"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="d-flex flex-wrap align-items-center justify-content-between pt-3 mt-3 border-top gap-2 text-muted small">
            <div id="dior-clinical-count-display">0 selected / <?php echo count($recent_reports); ?> total</div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0" id="dior-clinical-pagination"></ul>
            </nav>
        </div>
    </div>
</section>
<script>
(function () {
    'use strict';
    var root = document.getElementById('tab-doc-analytics');
    if (!root || root.dataset.clinicalActionsReady === '1') return;
    root.dataset.clinicalActionsReady = '1';
    var vars = window.dior_doctor_vars || {};
    var ajaxUrl = vars.ajax_url || window.ajaxurl || '/wp-admin/admin-ajax.php';
    var nonce = vars.nonce || '';

    function request(action, data) {
        var params = new URLSearchParams(Object.assign({action:action, nonce:nonce}, data || {}));
        return fetch(ajaxUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}, body:params.toString()}).then(function(r){return r.json();});
    }
    function message(text, icon) {
        if (window.Swal) return Swal.fire({icon:icon || 'info', text:text, confirmButtonColor:'#1A528E'});
        window.alert(text); return Promise.resolve();
    }
    function reload() { window.location.reload(); }
    function selected() { return Array.from(root.querySelectorAll('.dior-clinical-row-check:checked')).map(function(el){return el.value;}).filter(Boolean); }

    root.addEventListener('click', function(event) {
        var edit = event.target.closest('.dior-action-edit');
        var del = event.target.closest('.dior-action-del');
        var refresh = event.target.closest('#dior-clinical-refresh-btn');
        var bulk = event.target.closest('#dior-clinical-bulk-delete');
        if (refresh) { reload(); return; }

        if (edit) {
            var uid = edit.getAttribute('data-appt-uid');
            var row = edit.closest('tr');
            if (!uid || !row) { message('Report reference is missing.', 'error'); return; }
            var cells = row.querySelectorAll('td');
            var diagnosis = cells[3] ? cells[3].textContent.trim() : '';
            var date = cells[4] ? cells[4].textContent.trim() : '';
            var status = cells[5] ? cells[5].textContent.trim() : 'Pending';
            if (!window.Swal) { message('Edit dialog is unavailable.', 'error'); return; }
            Swal.fire({
                title:'Edit Clinical Report',
                html:'<div class="text-start"><label class="form-label">Diagnosis</label><input id="dior-cr-diagnosis" class="form-control mb-3"><label class="form-label">Visit Date</label><input id="dior-cr-date" type="date" class="form-control mb-3"><label class="form-label">Status</label><select id="dior-cr-status" class="form-select"><option>Pending</option><option>Confirmed</option><option>In Progress</option><option>Completed</option><option>Cancelled</option><option>No Show</option></select></div>',
                didOpen:function(){
                    document.getElementById('dior-cr-diagnosis').value=diagnosis;
                    document.getElementById('dior-cr-date').value=/^\d{4}-\d{2}-\d{2}$/.test(date)?date:'';
                    document.getElementById('dior-cr-status').value=status;
                },
                showCancelButton:true, confirmButtonText:'Save Changes', confirmButtonColor:'#1A528E',
                preConfirm:function(){return {
                    appt_uid:uid,
                    condition_name:document.getElementById('dior-cr-diagnosis').value.trim(),
                    appt_date:document.getElementById('dior-cr-date').value,
                    status:document.getElementById('dior-cr-status').value
                };}
            }).then(function(result){
                if(!result.isConfirmed) return;
                request('dior_doc_clinical_report_update',result.value).then(function(res){
                    if(res.success) message(res.data.message || 'Report updated.','success').then(reload);
                    else message((res.data && res.data.message) || 'Unable to update report.','error');
                }).catch(function(){message('Network error while updating report.','error');});
            });
            return;
        }

        if (del) {
            var deleteUid = del.getAttribute('data-appt-uid');
            if (!deleteUid) { message('Report reference is missing.','error'); return; }
            var confirmDelete = window.Swal ? Swal.fire({icon:'warning',title:'Delete clinical report?',text:'This report will be removed from the active list.',showCancelButton:true,confirmButtonText:'Delete',confirmButtonColor:'#dc3545'}) : Promise.resolve({isConfirmed:window.confirm('Delete this report?')});
            confirmDelete.then(function(result){
                if(!result.isConfirmed) return;
                request('dior_doc_clinical_report_delete',{appt_uid:deleteUid}).then(function(res){
                    if(res.success) message(res.data.message || 'Report deleted.','success').then(reload);
                    else message((res.data && res.data.message) || 'Unable to delete report.','error');
                }).catch(function(){message('Network error while deleting report.','error');});
            });
            return;
        }

        if (bulk) {
            var ids=selected();
            if(!ids.length){message('Select at least one report first.','warning');return;}
            var confirmBulk=window.Swal?Swal.fire({icon:'warning',title:'Delete selected reports?',text:ids.length+' report(s) will be removed from the active list.',showCancelButton:true,confirmButtonText:'Delete Selected',confirmButtonColor:'#dc3545'}):Promise.resolve({isConfirmed:window.confirm('Delete selected reports?')});
            confirmBulk.then(function(result){
                if(!result.isConfirmed)return;
                request('dior_doc_clinical_report_bulk_delete',{appt_uids:ids}).then(function(res){
                    if(res.success)message(res.data.message || 'Selected reports deleted.','success').then(reload);
                    else message((res.data && res.data.message)||'Unable to delete selected reports.','error');
                }).catch(function(){message('Network error while deleting reports.','error');});
            });
        }
    });

    root.addEventListener('change',function(event){
        if(event.target.id==='dior-clinical-check-all'){
            root.querySelectorAll('.dior-clinical-row-check').forEach(function(box){box.checked=event.target.checked;});
        }
        if(event.target.classList.contains('dior-clinical-row-check')){
            var boxes=Array.from(root.querySelectorAll('.dior-clinical-row-check'));
            var count=boxes.filter(function(box){return box.checked;}).length;
            var master=root.querySelector('#dior-clinical-check-all');
            if(master){master.checked=boxes.length>0 && count===boxes.length;master.indeterminate=count>0 && count<boxes.length;}
            var bulkBtn=root.querySelector('#dior-clinical-bulk-delete');
            if(bulkBtn)bulkBtn.style.display=count?'':'none';
            var counter=root.querySelector('#dior-clinical-count-display');
            if(counter)counter.textContent=count+' selected / '+boxes.length+' total';
        }
    });

    var search=root.querySelector('#dior-clinical-search-input');
    var currentPage=1;
    var pageSize=10;
    function filteredRows(){
        var query=search?search.value.trim().toLowerCase():'';
        return Array.from(root.querySelectorAll('#dior-clinical-reports-tbody > tr')).filter(function(row){
            return row.querySelectorAll('td').length && row.innerText.toLowerCase().indexOf(query)!==-1;
        });
    }
    function renderTablePage(){
        var rows=Array.from(root.querySelectorAll('#dior-clinical-reports-tbody > tr')).filter(function(row){return row.querySelectorAll('td').length;});
        var visible=filteredRows();
        var pages=Math.max(1,Math.ceil(visible.length/pageSize));
        currentPage=Math.min(currentPage,pages);
        rows.forEach(function(row){row.style.display='none';});
        visible.forEach(function(row,index){
            if(index >= (currentPage-1)*pageSize && index < currentPage*pageSize) row.style.display='';
        });
        var pagination=root.querySelector('#dior-clinical-pagination');
        if(pagination){
            pagination.innerHTML='';
            function addPage(label,page,disabled,active){
                var li=document.createElement('li');li.className='page-item'+(disabled?' disabled':'')+(active?' active':'');
                var button=document.createElement('button');button.type='button';button.className='page-link rounded-2 me-1';button.textContent=label;
                button.disabled=!!disabled;button.addEventListener('click',function(){currentPage=page;renderTablePage();});
                li.appendChild(button);pagination.appendChild(li);
            }
            addPage('‹',Math.max(1,currentPage-1),currentPage===1,false);
            for(var page=1;page<=pages;page++)addPage(String(page),page,false,page===currentPage);
            addPage('›',Math.min(pages,currentPage+1),currentPage===pages,false);
        }
        var count=root.querySelector('#dior-clinical-count-display');
        if(count)count.textContent='Showing '+(visible.length?((currentPage-1)*pageSize+1):0)+' to '+Math.min(currentPage*pageSize,visible.length)+' of '+visible.length+' entries';
    }
    if(search)search.addEventListener('input',function(){currentPage=1;renderTablePage();});
    renderTablePage();

    var exportBtn=root.querySelector('#dior-clinical-export-btn');
    if(exportBtn)exportBtn.addEventListener('click',function(){
        var rows=Array.from(root.querySelectorAll('#dior-clinical-reports-table tr')).filter(function(row){return row.offsetParent!==null;});
        var csv=rows.map(function(row){return Array.from(row.querySelectorAll('th,td')).slice(1,-1).map(function(cell){return '"'+cell.innerText.replace(/"/g,'""').trim()+'"';}).join(',');}).join('\r\n');
        var blob=new Blob([csv],{type:'text/csv;charset=utf-8;'}), url=URL.createObjectURL(blob), a=document.createElement('a');
        a.href=url;a.download='clinical-reports.csv';a.click();URL.revokeObjectURL(url);
    });
})();
</script>



<style id="dior-clinical-reports-mobile-cards">
/* Clinical Reports responsive layout — desktop table remains unchanged. */
@media (max-width: 767.98px) {
    #dior-doctor-app #dior-doc-content #tab-doc-analytics {
        width:100%!important;
        max-width:100%!important;
        min-width:0!important;
        padding-left:0!important;
        padding-right:0!important;
        box-sizing:border-box!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics > .card {
        width:100%!important;
        max-width:none!important;
        min-width:0!important;
        margin-left:0!important;
        margin-right:0!important;
        padding:12px!important;
        border-radius:14px!important;
        box-sizing:border-box!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics > .card > .d-flex:first-child {
        align-items:flex-start!important;
        gap:12px!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics .input-group {
        width:100%!important;
        max-width:100%!important;
        flex:1 1 100%!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics .input-group input {
        min-height:40px!important;
        padding-left:12px!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics .table-responsive {
        width:100%!important;
        max-width:100%!important;
        overflow:visible!important;
        border:0!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table,
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table tbody {
        display:block!important;
        width:100%!important;
        min-width:0!important;
        margin:0!important;
        border:0!important;
        border-collapse:separate!important;
        border-spacing:0!important;
        table-layout:auto!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table thead {
        display:none!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr {
        position:relative!important;
        display:flex!important;
        flex-direction:column!important;
        width:100%!important;
        min-width:0!important;
        margin:0 0 12px!important;
        padding:12px 13px!important;
        overflow:hidden!important;
        background:#fff!important;
        border:1px solid #dce5ef!important;
        border-radius:14px!important;
        box-shadow:0 2px 8px rgba(15,43,76,.045)!important;
        box-sizing:border-box!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td {
        display:flex!important;
        align-items:center!important;
        justify-content:space-between!important;
        gap:12px!important;
        width:100%!important;
        min-width:0!important;
        min-height:34px!important;
        padding:7px 0!important;
        border:0!important;
        border-bottom:1px solid #edf1f6!important;
        background:transparent!important;
        text-align:right!important;
        white-space:normal!important;
        overflow-wrap:anywhere!important;
        box-sizing:border-box!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:first-child {
        position:absolute!important;
        top:14px!important;
        right:13px!important;
        z-index:2!important;
        display:flex!important;
        justify-content:flex-end!important;
        width:auto!important;
        min-height:22px!important;
        padding:0!important;
        border:0!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(2) {
        justify-content:flex-start!important;
        min-height:48px!important;
        padding:0 36px 9px 0!important;
        text-align:left!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(2) > div {
        display:flex!important;
        align-items:center!important;
        gap:10px!important;
        min-width:0!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(2) img {
        flex:0 0 38px!important;
        width:38px!important;
        height:38px!important;
        object-fit:cover!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(2) span {
        font-weight:700!important;
        color:#12345a!important;
        text-align:left!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(3)::before { content:"Doctor"; }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(4)::before { content:"Diagnosis"; }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(5)::before { content:"Visit Date"; }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(6)::before { content:"Status"; }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(n+3):nth-child(-n+6)::before {
        flex:0 0 82px!important;
        color:#8493a7!important;
        font-size:10px!important;
        font-weight:700!important;
        letter-spacing:.035em!important;
        text-align:left!important;
        text-transform:uppercase!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(7) {
        justify-content:flex-end!important;
        padding:10px 0 0!important;
        border:0!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr > td:nth-child(7) > div {
        display:flex!important;
        justify-content:flex-end!important;
        gap:8px!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-tbody > tr:not(:has(td)) {
        display:block!important;
    }
    #dior-doctor-app #dior-doc-content #tab-doc-analytics > .card > .d-flex.flex-wrap.align-items-center.justify-content-between.pt-3 {
        align-items:flex-start!important;
    }
}
</style>


<style id="dior-clinical-reports-global-table">
/* Clinical reports use the shared Dior table class and stable action-button states. */
#dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table {
    width:100%; border-collapse:separate; border-spacing:0; border:1px solid #dce5ef;
    border-radius:10px; overflow:hidden;
}
#dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table thead th {
    background:#f5f8fc; color:#52657b; font-size:12px; font-weight:700;
    letter-spacing:.035em; padding:13px 14px; border-bottom:1px solid #dce5ef; white-space:nowrap;
}
#dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table tbody td {
    padding:12px 14px; border-bottom:1px solid #edf1f6; vertical-align:middle;
}
#dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table tbody tr:last-child td { border-bottom:0; }
#dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table .dior-action-edit:hover,
#dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table .dior-action-edit:focus,
#dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table .dior-action-edit:active {
    color:#1a528e!important; background:#fff!important; border-color:#1a528e!important; box-shadow:none!important;
}
#dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table .dior-action-del:hover,
#dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table .dior-action-del:focus,
#dior-doctor-app #dior-doc-content #tab-doc-analytics #dior-clinical-reports-table .dior-action-del:active {
    color:#dc3545!important; background:#fff!important; border-color:#dc3545!important; box-shadow:none!important;
}
#dior-doctor-app #dior-doc-content #tab-doc-analytics .btn-primary:hover,
#dior-doctor-app #dior-doc-content #tab-doc-analytics .btn-success:hover,
#dior-doctor-app #dior-doc-content #tab-doc-analytics .btn-info:hover {
    background:#1a528e!important; border-color:#1a528e!important; color:#fff!important;
}
</style>

<style id="dior-clinical-reports-brand-colors">
/* Keep Clinical Reports aligned with the Dior Medical navy/blue interface. */
#dior-doctor-app #dior-doc-content #tab-doc-analytics {
    --dior-brand-primary:#1A528E;
    --dior-brand-secondary:#2C6CB1;
    --dior-brand-mid:#477EAB;
    --dior-brand-soft:#7198BC;
}
#dior-doctor-app #dior-doc-content #tab-doc-analytics .text-primary,
#dior-doctor-app #dior-doc-content #tab-doc-analytics .text-primary i {
    color:#1A528E!important;
}
#dior-doctor-app #dior-doc-content #tab-doc-analytics .btn-primary,
#dior-doctor-app #dior-doc-content #tab-doc-analytics .btn-info,
#dior-doctor-app #dior-doc-content #tab-doc-analytics .btn-success {
    color:#fff!important;
    background-color:#1A528E!important;
    border-color:#1A528E!important;
}
#dior-doctor-app #dior-doc-content #tab-doc-analytics .btn-primary:hover,
#dior-doctor-app #dior-doc-content #tab-doc-analytics .btn-info:hover,
#dior-doctor-app #dior-doc-content #tab-doc-analytics .btn-success:hover {
    background-color:#1A528E!important;
    border-color:#1A528E!important;
}
#dior-doctor-app #dior-doc-content #tab-doc-analytics .card {
    border-color:#DFE8F1!important;
}
</style>


<script>
(function() {
    var trendsChart = null;
    var donutChart = null;

    function initClinicalReports() {
        var panel = document.getElementById('tab-doc-analytics');
        if (!panel) return;

        // 1. Initialize ApexCharts when visible
        function renderCharts() {
            if (typeof ApexCharts === 'undefined') {
                setTimeout(renderCharts, 100);
                return;
            }

            // Only render when tab is displayed
            if (window.getComputedStyle(panel).display === 'none') {
                return;
            }

            var trendsEl = document.getElementById('dior-clinical-visit-trends-chart');
            if (trendsEl && !trendsChart) {
                var trendsOptions = {
                    series: [
                        { name: 'Outpatients', data: <?php echo wp_json_encode($monthly_opd); ?> },
                        { name: 'Inpatients', data: <?php echo wp_json_encode($monthly_ipd); ?> }
                    ],
                    chart: {
                        type: 'area',
                        height: 310,
                        toolbar: { show: false },
                        fontFamily: 'inherit'
                    },
                    colors: ['#1A528E', '#5B8DB8'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 3 },
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 90, 100] }
                    },
                    xaxis: {
                        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        labels: { style: { colors: '#64748b', fontSize: '12px' } }
                    },
                    yaxis: {
                        labels: { style: { colors: '#64748b', fontSize: '11px' } }
                    },
                    grid: {
                        borderColor: 'rgba(226, 232, 240, 0.6)',
                        strokeDashArray: 4
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        fontSize: '12px',
                        labels: { colors: '#475569' }
                    }
                };
                trendsChart = new ApexCharts(trendsEl, trendsOptions);
                trendsChart.render();
            }

            var donutEl = document.getElementById('dior-clinical-disease-distribution-chart');
            var diseaseSeries = <?php echo wp_json_encode($disease_series); ?>;
            var diseaseLabels = <?php echo wp_json_encode($disease_labels); ?>;
            if (donutEl && !donutChart) {
                if (!diseaseSeries.length) {
                    donutEl.innerHTML = '<div class="text-center text-muted small py-5">No clinical distribution data yet.</div>';
                } else {
                var donutOptions = {
                    series: diseaseSeries,
                    labels: diseaseLabels,
                    chart: {
                        type: 'donut',
                        height: 310,
                        fontFamily: 'inherit'
                    },
                    colors: ['#1A528E', '#2C6CB1', '#477EAB', '#7198BC', '#A3BED4'],
                    legend: {
                        position: 'bottom',
                        fontSize: '12px',
                        labels: { colors: '#475569' },
                        markers: { width: 10, height: 10 }
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%'
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return val.toFixed(1) + "%";
                        },
                        style: {
                            fontSize: '11px',
                            fontWeight: '600'
                        },
                        dropShadow: { enabled: true, blur: 2, opacity: 0.6 }
                    }
                };
                donutChart = new ApexCharts(donutEl, donutOptions);
                donutChart.render();
                }
            }
        }

        // Render charts immediately if panel is visible
        renderCharts();

        // Listen for visibility changes on tab-doc-analytics
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'style' && panel.style.display !== 'none') {
                    setTimeout(renderCharts, 50);
                }
            });
        });
        observer.observe(panel, { attributes: true });

        // 2. Table Interactions (Search, Checkboxes, Bulk Delete)
        var searchInput = document.getElementById('dior-clinical-search-input');
        var tableBody = document.getElementById('dior-clinical-reports-tbody');
        var checkAll = document.getElementById('dior-clinical-check-all');
        var countDisplay = document.getElementById('dior-clinical-count-display');
        var bulkDeleteBtn = document.getElementById('dior-clinical-bulk-delete');

        function updateSelectionState() {
            var allRows = tableBody.querySelectorAll('tr');
            var rowChecks = tableBody.querySelectorAll('.dior-clinical-row-check');
            var selectedCount = tableBody.querySelectorAll('.dior-clinical-row-check:checked').length;
            var totalCount = allRows.length;

            if (countDisplay) {
                countDisplay.textContent = selectedCount + ' selected / ' + totalCount + ' total';
            }

            if (bulkDeleteBtn) {
                bulkDeleteBtn.style.display = selectedCount > 0 ? 'inline-block' : 'none';
            }

            if (checkAll) {
                checkAll.checked = (selectedCount > 0 && selectedCount === rowChecks.length);
                checkAll.indeterminate = (selectedCount > 0 && selectedCount < rowChecks.length);
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                var rowChecks = tableBody.querySelectorAll('.dior-clinical-row-check');
                rowChecks.forEach(function(ck) {
                    ck.checked = checkAll.checked;
                });
                updateSelectionState();
            });
        }

        if (tableBody) {
            tableBody.addEventListener('change', function(e) {
                if (e.target && e.target.classList.contains('dior-clinical-row-check')) {
                    updateSelectionState();
                }
            });

            // Action button handlers (Edit, Delete)
            tableBody.addEventListener('click', function(e) {
                var editBtn = e.target.closest('.dior-action-edit');
                if (editBtn) {
                    var row = editBtn.closest('tr');
                    var patientName = row ? row.querySelector('span.fw-semibold')?.textContent : 'Patient';
                    if (window.Swal) {
                        Swal.fire({
                            title: 'Edit Clinical Report',
                            text: 'Editing record for ' + patientName,
                            icon: 'info',
                            confirmButtonColor: '#1A528E'
                        });
                    }
                    return;
                }

                var delBtn = e.target.closest('.dior-action-del');
                if (delBtn) {
                    var row = delBtn.closest('tr');
                    var patientName = row ? row.querySelector('span.fw-semibold')?.textContent : 'Patient';
                    if (window.Swal) {
                        Swal.fire({
                            title: 'Delete Report?',
                            text: 'Are you sure you want to delete report for ' + patientName + '?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes, delete'
                        }).then(function(res) {
                            if (res.isConfirmed) {
                                row.remove();
                                updateSelectionState();
                                Swal.fire({ title: 'Deleted!', text: 'Report removed.', icon: 'success', timer: 1500, showConfirmButton: false });
                            }
                        });
                    } else if (confirm('Are you sure you want to delete this report?')) {
                        row.remove();
                        updateSelectionState();
                    }
                }
            });
        }

        // Live search filter
        if (searchInput && tableBody) {
            searchInput.addEventListener('input', function() {
                var q = this.value.toLowerCase().trim();
                var rows = tableBody.querySelectorAll('tr');
                rows.forEach(function(r) {
                    var text = r.textContent.toLowerCase();
                    r.style.display = (text.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }

        // Header buttons (Add, Export, Refresh, Bulk Delete)
        var addBtn = document.getElementById('dior-clinical-add-btn');
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                if (window.Swal) {
                    Swal.fire({
                        title: 'Add Clinical Report',
                        text: 'Record creation form will open here.',
                        icon: 'info',
                        confirmButtonColor: '#1A528E'
                    });
                }
            });
        }

        var exportBtn = document.getElementById('dior-clinical-export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', function() {
                var csv = ['Patient Name,Doctor,Diagnosis,Visit Date,Status'];
                var rows = tableBody.querySelectorAll('tr');
                rows.forEach(function(r) {
                    var cols = r.querySelectorAll('td');
                    if (cols.length >= 6) {
                        var name = cols[1].innerText.trim();
                        var doc = cols[2].innerText.trim();
                        var diag = cols[3].innerText.trim();
                        var date = cols[4].innerText.trim();
                        var status = cols[5].innerText.trim();
                        csv.push('"' + name + '","' + doc + '","' + diag + '","' + date + '","' + status + '"');
                    }
                });
                var blob = new Blob([csv.join('\n')], { type: 'text/csv' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'clinical-reports-' + new Date().toISOString().slice(0, 10) + '.csv';
                a.click();
                URL.revokeObjectURL(url);
            });
        }

        var refreshBtn = document.getElementById('dior-clinical-refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                var icon = refreshBtn.querySelector('i');
                if (icon) icon.classList.add('fa-spin');
                setTimeout(function() {
                    if (icon) icon.classList.remove('fa-spin');
                    if (window.Swal) {
                        Swal.fire({ title: 'Refreshed!', text: 'Clinical data updated.', icon: 'success', timer: 1200, showConfirmButton: false });
                    }
                }, 600);
            });
        }

        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                var selectedRows = tableBody.querySelectorAll('.dior-clinical-row-check:checked');
                if (selectedRows.length === 0) return;

                if (window.Swal) {
                    Swal.fire({
                        title: 'Delete ' + selectedRows.length + ' Items?',
                        text: 'Are you sure you want to delete selected clinical reports?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete all'
                    }).then(function(res) {
                        if (res.isConfirmed) {
                            selectedRows.forEach(function(ck) {
                                ck.closest('tr')?.remove();
                            });
                            updateSelectionState();
                            Swal.fire({ title: 'Deleted!', text: 'Selected reports removed.', icon: 'success', timer: 1500, showConfirmButton: false });
                        }
                    });
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initClinicalReports);
    } else {
        initClinicalReports();
    }
})();
</script>