<?php
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$inv_table = $wpdb->prefix . 'dior_invoices';
$ir_rows = [];
$current_report_year = (int) current_time('Y');
$minimum_report_year = 2026; // Do not include historical report data before 2026.
$ir_year = isset($_GET['income_year']) ? absint($_GET['income_year']) : $current_report_year;
if ($ir_year < $minimum_report_year || $ir_year > $current_report_year) $ir_year = $current_report_year;
$report_doctor_ids = class_exists('Dior_Doctor_Resolver') ? Dior_Doctor_Resolver::get_accessible_doctor_ids(get_current_user_id()) : [get_current_user_id()];
$report_doctor_ids = array_values(array_unique(array_filter(array_map('absint', (array) $report_doctor_ids))));
if ($report_doctor_ids && $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $inv_table)) === $inv_table) {
    $report_in = implode(',', array_fill(0, count($report_doctor_ids), '%d'));
    $ir_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $inv_table WHERE doctor_id IN ($report_in) AND LOWER(status) IN ('paid','completed','succeeded') ORDER BY created_at DESC LIMIT 500",
        ...$report_doctor_ids
    ), ARRAY_A);
}
$manual_report_table = $wpdb->prefix . 'dior_doctor_bills';
if ($report_doctor_ids && $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $manual_report_table)) === $manual_report_table) {
    $report_in = implode(',', array_fill(0, count($report_doctor_ids), '%d'));
    $manual_report_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $manual_report_table WHERE doctor_id IN ($report_in) AND LOWER(status)='paid' ORDER BY created_at DESC LIMIT 500",
        ...$report_doctor_ids
    ), ARRAY_A);
    foreach ((array) $manual_report_rows as $manual_row) {
        $manual_row['amount'] = $manual_row['total_amount'];
        $manual_row['department'] = $manual_row['service_type'];
        $ir_rows[] = $manual_row;
    }
}
// Include Stripe payment receipts from patient ledgers where no invoice row exists.
$report_seen = [];
foreach ($ir_rows as $existing_row) {
    $key = (string) ($existing_row['invoice_uid'] ?? $existing_row['bill_uid'] ?? '');
    if ($key !== '') $report_seen[$key] = true;
}
if ($report_doctor_ids) {
    $payment_meta_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT pm.user_id, pm.meta_value FROM {$wpdb->usermeta} pm WHERE pm.meta_key=%s ORDER BY pm.umeta_id DESC LIMIT 500",
        'dior_payments'
    ), ARRAY_A);
    foreach ((array) $payment_meta_rows as $meta_row) {
        $patient_payments = maybe_unserialize($meta_row['meta_value']);
        if (!is_array($patient_payments)) continue;
        foreach ($patient_payments as $payment) {
            if (!is_array($payment) || !in_array(absint($payment['doctor_id'] ?? 0), $report_doctor_ids, true)) continue;
            if (!in_array(strtolower((string) ($payment['status'] ?? 'paid')), ['paid','completed','succeeded'], true)) continue;
            $payment_uid = sanitize_text_field($payment['invoice_uid'] ?? $payment['id'] ?? '');
            if ($payment_uid && isset($report_seen[$payment_uid])) continue;
            if ($payment_uid) $report_seen[$payment_uid] = true;
            $ir_rows[] = [
                'invoice_uid' => $payment_uid,
                'amount' => preg_replace('/[^0-9.\\-]/', '', (string) ($payment['total'] ?? $payment['amount'] ?? 0)),
                'department' => $payment['description'] ?? 'Patient payment',
                'created_at' => $payment['date'] ?? '',
                'status' => $payment['status'] ?? 'Paid',
            ];
        }
    }
}
$ir_total_revenue = 0.0;
$ir_monthly = array_fill(0, 12, 0.0);
$ir_dept_totals = [];
$ir_dept_monthly = [];
$ir_daily_categories = [];
$ir_daily_date_keys = [];
$ir_daily_values = array_fill(0, 14, 0.0);
$ir_dept_daily = [];
$ir_year_categories = [];
$ir_year_values = [];
$today = current_time('timestamp');
for ($i = 13; $i >= 0; $i--) {
    $day = strtotime('-' . $i . ' days', $today);
    $ir_daily_categories[] = wp_date('M j', $day);
    $ir_daily_date_keys[] = wp_date('Y-m-d', $day);
}
for ($year = $minimum_report_year; $year <= $current_report_year; $year++) {
    $ir_year_categories[] = (string) $year;
    $ir_year_values[$year] = 0.0;
}
foreach ($ir_rows as $row) {
    $amt = (float) ($row['total_amount'] ?? $row['amount'] ?? 0);
    $dept = trim((string) ($row['department'] ?? $row['service_type'] ?? 'General'));
    if ($dept === '') $dept = 'General';
    $ts = !empty($row['paid_at']) ? strtotime($row['paid_at']) : (!empty($row['created_at']) ? strtotime($row['created_at']) : false);
    if ($ts) {
        $row_year = (int) wp_date('Y', $ts);
        if ($row_year === $ir_year) {
            $ir_total_revenue += $amt;
            $ir_dept_totals[$dept] = ($ir_dept_totals[$dept] ?? 0.0) + $amt;
            if (!isset($ir_dept_monthly[$dept])) $ir_dept_monthly[$dept] = array_fill(0, 12, 0.0);
            if (!isset($ir_dept_daily[$dept])) $ir_dept_daily[$dept] = array_fill(0, 14, 0.0);
            $month_index = (int) wp_date('n', $ts) - 1;
            $ir_monthly[$month_index] += $amt;
            $ir_dept_monthly[$dept][$month_index] += $amt;
            $day_key = wp_date('Y-m-d', $ts);
            $daily_index = array_search($day_key, $ir_daily_date_keys, true);
            if ($daily_index !== false) $ir_dept_daily[$dept][$daily_index] += $amt;
            for ($i = 13; $i >= 0; $i--) {
                $target = wp_date('Y-m-d', strtotime('-' . $i . ' days', $today));
                if ($target === $day_key) $ir_daily_values[13 - $i] += $amt;
            }
        }
        if (isset($ir_year_values[$row_year])) $ir_year_values[$row_year] += $amt;
    }
}
arsort($ir_dept_totals);
$ir_top_dept = !empty($ir_dept_totals) ? array_key_first($ir_dept_totals) : '—';
$ir_top_dept_amt = !empty($ir_dept_totals) ? $ir_dept_totals[$ir_top_dept] : 0.0;
$ir_monthly_avg = $ir_total_revenue > 0 ? ($ir_total_revenue / 12) : 0.0;
$ir_chart_series = [];
foreach (array_slice($ir_dept_monthly, 0, 5, true) as $d_name => $d_vals) {
    $ir_chart_series[] = ['name' => $d_name, 'data' => array_map(static function ($v) { return round($v, 2); }, $d_vals)];
}
$ir_daily_series = [['name' => 'Collected Income', 'data' => array_map(static function ($v) { return round($v, 2); }, $ir_daily_values)]];
// Weekly view uses the most recent seven days from the daily report data.
$ir_weekly_categories = array_slice($ir_daily_categories, -7);
$ir_weekly_values = array_slice($ir_daily_values, -7);
$ir_weekly_series = [['name' => 'Collected Income', 'data' => array_map(static function ($v) { return round($v, 2); }, $ir_weekly_values)]];
$ir_year_series = [['name' => 'Collected Income', 'data' => array_map(static function ($v) { return round($v, 2); }, array_values($ir_year_values))]];
?>
<section class="dior-tab-panel" id="tab-doc-accounts-income-report" style="display: none;">
    <!-- Breadcrumb Header -->
    <div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; padding: 0 5px;">
        <div>
            <h4 class="mb-0 text-dark" style="font-size: 20px; font-weight: 700;">Income Report</h4>
        </div>
        <div>
            <ul class="va-breadcrumb-list">
                <li><a href="#"><i class="fas fa-house"></i></a></li>
                <li>/</li>
                <li><a href="#">Accounts</a></li>
                <li>/</li>
                <li class="active"><span>Income Report</span></li>
            </ul>
        </div>
    </div>

    <!-- Subtab Navigation Bar -->
    <div class="dior-subtab-nav-bar px-1 mb-4">
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-bill-list" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-bill-list');">
            <i class="fas fa-receipt"></i> Bill List
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-add-bill" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-add-bill');">
            <i class="fas fa-file-invoice-dollar"></i> Add Bill
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-income" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-income');">
            <i class="fas fa-arrow-trend-up"></i> Income
        </button>
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-accounts-income-report" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-income-report');">
            <i class="fas fa-chart-pie"></i> Income Report
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-invoice" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-invoice');">
            <i class="fas fa-file-invoice"></i> Invoice
        </button>
    </div>

    <!-- 4 KPI Stat Cards for Income Highlights (100% Native Bootstrap 5) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white d-flex flex-column justify-content-between">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-secondary small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Total Annual Revenue</span>
                        <div class="fs-4 fw-bold text-dark mt-1">$<?php echo esc_html(number_format($ir_total_revenue, 2)); ?></div>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-3 p-2.5">
                        <i class="fas fa-wallet fs-5"></i>
                    </span>
                </div>
                <div class="mt-2 text-secondary small">
                    <span class="text-success fw-semibold"><i class="fas fa-database me-1"></i>Live</span> current year
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white d-flex flex-column justify-content-between">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-secondary small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Monthly Average</span>
                        <div class="fs-4 fw-bold text-dark mt-1">$<?php echo esc_html(number_format($ir_monthly_avg, 2)); ?></div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-3 p-2.5">
                        <i class="fas fa-chart-line fs-5"></i>
                    </span>
                </div>
                <div class="mt-2 text-secondary small">
                    <span class="text-primary fw-semibold"><i class="fas fa-calendar-check me-1"></i>12 Months</span> average
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-sm-6 col-12">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white d-flex flex-column justify-content-between">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-secondary small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Total Invoices</span>
                        <div class="fs-4 fw-bold text-success mt-1"><?php echo esc_html(count($ir_rows)); ?></div>
                    </div>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-3 p-2.5">
                        <i class="fas fa-arrow-trend-up fs-5"></i>
                    </span>
                </div>
                <div class="mt-2 text-secondary small">
                    <span class="text-success fw-semibold"><i class="fas fa-circle-check me-1"></i>High Efficacy</span> clinical billing
                </div>
            </div>
        </div>
    </div>

    <!-- Income Chart Card (Interactive ApexCharts) -->
    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 border-bottom pb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1 fs-5 d-flex align-items-center gap-2">
                    <i class="fas fa-chart-line text-primary"></i> Income &amp; Revenue Trends
                </h5>
                <p class="text-secondary small mb-0">Track clinical collections, departmental distributions, and periodic growth rates.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0" style="min-width:0;">
                <span class="text-secondary small fw-semibold d-none d-sm-inline">Period:</span>
                <select id="dior-income-chart-filter" class="form-select form-select-sm rounded-3 py-1.5 fw-semibold" style="width: 210px; min-width: 210px; max-width: 100%;">
                    <option value="Monthly" selected>Monthly (Jan - Dec)</option>
                    <option value="Daily">Daily (Last 14 Days)</option>
                    <option value="Weekly">Weekly (Last 7 Days)</option>
                    <option value="Yearly">Yearly (2026 Onward)</option>
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 px-2.5 shadow-none" id="dior-income-chart-refresh" title="Reset Chart Zoom">
                    <i class="fas fa-rotate-right"></i>
                </button>
            </div>
        </div>
        <div class="p-0">
            <div id="dior-income-report-apex-chart" style="min-height: 360px; width: 100%;"></div>
        </div>
    </div>

    <!-- Income Table Card -->
    <div class="view-appointment-card">
        <div class="va-header-container">
            <div class="va-title-box">
                <h2><i class="fas fa-table-list text-primary me-2"></i>Departmental Income Breakdown</h2>
                <div class="va-title-line"></div>
            </div>
            <div class="va-actions-wrapper">
                <span class="text-secondary small fw-semibold d-none d-sm-inline">View:</span>
                <select id="dior-income-table-period" class="form-select form-select-sm rounded-3 py-1.5 fw-semibold" style="width: 210px; min-width: 180px;" aria-label="Income table period">
                    <option value="Monthly" selected>Monthly (Jan - Dec)</option>
                    <option value="Daily">Daily (Last 14 Days)</option>
                    <option value="Weekly">Weekly (Last 7 Days)</option>
                </select>
                <span class="text-secondary small fw-semibold d-none d-sm-inline">Fiscal Year:</span>
                <select id="dior-income-table-year" class="form-select form-select-sm rounded-3 py-1.5 fw-semibold" style="width: auto;" aria-label="Income report year">
                    <?php for ($year_option = $current_report_year; $year_option >= $minimum_report_year; $year_option--): ?>
                    <option value="<?php echo esc_attr($year_option); ?>" <?php selected($ir_year, $year_option); ?>><?php echo esc_html($year_option); ?></option>
                    <?php endfor; ?>
                </select>
                <div class="va-actions-group">
                    <button type="button" class="va-icon-btn va-btn-info btn" title="Refresh" onclick="window.location.reload()"><i class="fas fa-rotate-right"></i></button>
                </div>
            </div>
        </div>

        <div class="va-table-wrapper">
            <table class="va-table table table-hover" id="dior-income-report-table">
                <thead>
                    <tr>
                        <th class="ps-3">DEPARTMENT <i class="fas fa-sort"></i></th>
                        <th style="text-align: right;">JAN</th>
                        <th style="text-align: right;">FEB</th>
                        <th style="text-align: right;">MAR</th>
                        <th style="text-align: right;">APR</th>
                        <th style="text-align: right;">MAY</th>
                        <th style="text-align: right;">JUN</th>
                        <th style="text-align: right;">JUL</th>
                        <th style="text-align: right;">AUG</th>
                        <th style="text-align: right;">SEP</th>
                        <th style="text-align: right;">OCT</th>
                        <th style="text-align: right;">NOV</th>
                        <th style="text-align: right;">DEC</th>
                        <th style="text-align: right;" class="pe-3">YEARLY TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ir_dept_monthly)): ?>
                        <tr>
                            <td colspan="14" class="text-center text-muted py-5">
                                <i class="fas fa-chart-pie fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                <span class="small fw-semibold">No departmental income data available yet.</span>
                            </td>
                        </tr>
                    <?php else:
                        foreach ($ir_dept_monthly as $d_name => $months):
                            $row_sum = array_sum($months);
                    ?>
                        <tr>
                            <td class="ps-3 fw-bold text-dark"><?php echo esc_html($d_name); ?></td>
                            <?php foreach ($months as $m_val): ?>
                                <td class="text-end">$<?php echo esc_html(number_format($m_val, 2)); ?></td>
                            <?php endforeach; ?>
                            <td class="text-end pe-3 fw-bold text-dark">$<?php echo esc_html(number_format($row_sum, 2)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot class="table-light border-top">
                    <tr class="fw-bold">
                        <!-- Empty cell aligns totals with the checkbox column inserted by the table UI. -->
                        <td class="dior-income-footer-select-spacer" aria-hidden="true"></td>
                        <td class="ps-3 text-dark">Combined Clinic Revenue</td>
                        <?php foreach ($ir_monthly as $m_tot): ?>
                            <td class="text-end text-primary">$<?php echo esc_html(number_format($m_tot, 2)); ?></td>
                        <?php endforeach; ?>
                        <td class="text-end pe-3 text-success fs-6">$<?php echo esc_html(number_format($ir_total_revenue, 2)); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</section>

<style id="dior-income-report-chart-overlap-fix">
#tab-doc-accounts-income-report #dior-income-chart-filter{
    width:210px!important;
    min-width:210px!important;
    max-width:100%!important;
    flex:0 1 168px!important;
    white-space:nowrap!important;
}
#tab-doc-accounts-income-report #dior-income-report-apex-chart{
    min-width:0!important;
    overflow:hidden!important;
}
#tab-doc-accounts-income-report #dior-income-report-apex-chart .apexcharts-legend{
    padding-top:8px!important;
}
#tab-doc-accounts-income-report #dior-income-report-apex-chart .apexcharts-toolbar{
    z-index:5!important;
}
@media(max-width:767.98px){
    #tab-doc-accounts-income-report .card.border-0.shadow-sm.rounded-4>.d-flex.flex-wrap{
        align-items:flex-start!important;
    }
    #tab-doc-accounts-income-report .card.border-0.shadow-sm.rounded-4>.d-flex.flex-wrap>div:last-child{
        width:100%!important;
        justify-content:flex-start!important;
    }
    #tab-doc-accounts-income-report #dior-income-chart-filter{
        width:auto!important;
        min-width:0!important;
        flex:1 1 150px!important;
    }
    #tab-doc-accounts-income-report #dior-income-report-apex-chart .apexcharts-legend{
        justify-content:center!important;
        gap:4px 8px!important;
    }
}
</style>

<style id="dior-income-report-color-unification">
/* Keep all Income Report accents within Dior Medical's blue palette */
#tab-doc-accounts-income-report .text-success,
#tab-doc-accounts-income-report .text-info,
#tab-doc-accounts-income-report .text-warning,
#tab-doc-accounts-income-report .text-danger,
#tab-doc-accounts-income-report .text-primary,
#tab-doc-accounts-income-report .text-success *,
#tab-doc-accounts-income-report .text-info *,
#tab-doc-accounts-income-report .text-warning *,
#tab-doc-accounts-income-report .text-danger *,
#tab-doc-accounts-income-report .text-primary *{
    color:#1A528E!important;
}
#tab-doc-accounts-income-report .bg-success,
#tab-doc-accounts-income-report .bg-info,
#tab-doc-accounts-income-report .bg-warning,
#tab-doc-accounts-income-report .bg-danger{
    background-color:#EAF2FB!important;
}
#tab-doc-accounts-income-report .border-success,
#tab-doc-accounts-income-report .border-info,
#tab-doc-accounts-income-report .border-warning,
#tab-doc-accounts-income-report .border-danger{
    border-color:#D5E4F5!important;
}
/* KPI icon badges and icon glyphs */
#tab-doc-accounts-income-report>.row.g-3.mb-4 .badge,
#tab-doc-accounts-income-report>.row.g-3.mb-4 .badge i{
    color:#1A528E!important;
    background:#EAF2FB!important;
    border-color:#D5E4F5!important;
}
/* Department icons use one consistent brand accent */
#tab-doc-accounts-income-report #dior-income-report-table tbody td:first-child i{
    color:#1A528E!important;
}
/* Combined revenue footer should not introduce a separate green accent */
#tab-doc-accounts-income-report #dior-income-report-table tfoot td,
#tab-doc-accounts-income-report #dior-income-report-table tfoot td:last-child{
    color:#1A528E!important;
}
/* Keep success-style labels/icons visually consistent as well */
#tab-doc-accounts-income-report .fa-circle-check,
#tab-doc-accounts-income-report .fa-arrow-trend-up,
#tab-doc-accounts-income-report .fa-calendar-check{
    color:#1A528E!important;
}
</style>


<style id="dior-income-report-theme-colors">
/* Dior Medical theme: navy + clinical blue */
#tab-doc-accounts-income-report{
    --dior-primary:#1A528E;
    --dior-primary-dark:#0B3768;
    --dior-primary-soft:#EAF2FB;
    --dior-border:#DCE6F1;
    --dior-text:#1E293B;
}
#tab-doc-accounts-income-report .dior-subtab-pill{
    background:#1A528E!important;
    border-color:#1A528E!important;
    color:#fff!important;
}
#tab-doc-accounts-income-report .dior-subtab-pill:hover,
#tab-doc-accounts-income-report .dior-subtab-pill.active{
    background:#0B3768!important;
    border-color:#0B3768!important;
    color:#fff!important;
}
#tab-doc-accounts-income-report>.row.g-3.mb-4>.col-xl-3>.card{
    border:1px solid var(--dior-border)!important;
    border-top:3px solid var(--dior-primary)!important;
    border-radius:12px!important;
    box-shadow:0 3px 12px rgba(11,55,104,.06)!important;
}
#tab-doc-accounts-income-report>.row.g-3.mb-4>.col-xl-3:nth-child(2)>.card,
#tab-doc-accounts-income-report>.row.g-3.mb-4>.col-xl-3:nth-child(3)>.card,
#tab-doc-accounts-income-report>.row.g-3.mb-4>.col-xl-3:nth-child(4)>.card{
    border-top-color:#3B82B8!important;
}
#tab-doc-accounts-income-report>.row.g-3.mb-4 .badge{
    background:var(--dior-primary-soft)!important;
    color:var(--dior-primary)!important;
    border:1px solid #D5E4F5!important;
}
#tab-doc-accounts-income-report>.row.g-3.mb-4 .text-primary,
#tab-doc-accounts-income-report>.row.g-3.mb-4 .text-info{
    color:var(--dior-primary)!important;
}
#tab-doc-accounts-income-report>.row.g-3.mb-4 .text-success{
    color:#16845B!important;
}
#tab-doc-accounts-income-report>.card.border-0.shadow-sm.rounded-4{
    border:1px solid var(--dior-border)!important;
    border-radius:14px!important;
    box-shadow:0 3px 14px rgba(11,55,104,.055)!important;
}
#tab-doc-accounts-income-report h5 .text-primary,
#tab-doc-accounts-income-report .va-title-box h2 .text-primary{
    color:var(--dior-primary)!important;
}
#tab-doc-accounts-income-report #dior-income-chart-filter,
#tab-doc-accounts-income-report #dior-income-table-year{
    border-color:#C9D8E9!important;
    color:var(--dior-primary-dark)!important;
}
#tab-doc-accounts-income-report #dior-income-chart-refresh{
    background:var(--dior-primary)!important;
    border-color:var(--dior-primary)!important;
    color:#fff!important;
}
#tab-doc-accounts-income-report #dior-income-chart-refresh:hover{
    background:var(--dior-primary-dark)!important;
}
#tab-doc-accounts-income-report .view-appointment-card{
    border:1px solid var(--dior-border)!important;
    border-radius:14px!important;
    box-shadow:0 3px 14px rgba(11,55,104,.055)!important;
}
#tab-doc-accounts-income-report .va-title-box h2{
    color:var(--dior-primary-dark)!important;
}
#tab-doc-accounts-income-report .va-title-line{
    background:var(--dior-primary)!important;
}
#tab-doc-accounts-income-report #dior-income-report-table thead th{
    background:#F0F5FA!important;
    color:#496783!important;
    border-color:#DCE6F1!important;
}
#tab-doc-accounts-income-report #dior-income-report-table tbody tr:hover{
    background:#F5F9FE!important;
}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:first-child{
    color:var(--dior-primary-dark)!important;
}
#tab-doc-accounts-income-report #dior-income-report-table tfoot{
    background:#EAF2FB!important;
}
#tab-doc-accounts-income-report #dior-income-report-table tfoot td{
    color:var(--dior-primary)!important;
    border-color:#C9D8E9!important;
}
#tab-doc-accounts-income-report #dior-income-report-table tfoot td:last-child{
    color:#16845B!important;
}
@media(max-width:767.98px){
    #tab-doc-accounts-income-report #dior-income-report-table tbody tr{
        border-color:#DCE6F1!important;
    }
    #tab-doc-accounts-income-report #dior-income-report-table tbody td:first-child{
        color:#0B3768!important;
    }
}
</style>

<style id="dior-accounts-mobile-income-report">
/* Accounts mobile responsive layout, aligned with Patient Records */

@media(max-width:767.98px){
#dior-doctor-app #dior-doc-content #tab-doc-accounts-income-report{width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;padding:14px 0 24px!important;overflow-x:clip;box-sizing:border-box!important}
#tab-doc-accounts-income-report>.mb-4:first-child{align-items:flex-start!important}
#tab-doc-accounts-income-report>.mb-4:first-child .va-breadcrumb-list{display:none!important}
#tab-doc-accounts-income-report .dior-subtab-nav-bar{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;padding:0!important;margin:0 0 16px!important}
#tab-doc-accounts-income-report .dior-subtab-pill{width:100%!important;min-width:0!important;min-height:40px!important;margin:0!important;padding:8px 5px!important;font-size:12px!important;white-space:normal!important}
#tab-doc-accounts-income-report>.card,#tab-doc-accounts-income-report .view-appointment-card{width:100%!important;max-width:100%!important;min-width:0!important;margin-left:0!important;margin-right:0!important;box-sizing:border-box!important}
#tab-doc-accounts-income-report>.card{padding:12px!important;margin-bottom:14px!important}
#tab-doc-accounts-income-report .view-appointment-card{padding:0!important;overflow:hidden!important}
#tab-doc-accounts-income-report .va-header-container{display:flex!important;flex-direction:column!important;align-items:stretch!important;gap:12px!important;padding:12px!important}
#tab-doc-accounts-income-report .va-actions-wrapper{width:100%!important;display:flex!important;flex-wrap:wrap!important}
#tab-doc-accounts-income-report .va-table-wrapper{width:100%!important;max-width:100%!important;overflow:hidden!important;padding:0 10px 12px!important;box-sizing:border-box!important}
#tab-doc-accounts-income-report #dior-income-report-table{display:block!important;width:100%!important;min-width:0!important;table-layout:fixed!important}
#tab-doc-accounts-income-report #dior-income-report-table thead{display:none!important}
#tab-doc-accounts-income-report #dior-income-report-table tbody{display:grid!important;gap:10px!important;width:100%!important}
#tab-doc-accounts-income-report #dior-income-report-table tbody tr{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:9px 12px!important;width:100%!important;min-width:0!important;margin:0!important;padding:14px 12px!important;border:1px solid #E2E8F0!important;border-radius:14px!important;background:#fff!important;box-shadow:0 1px 3px rgba(15,23,42,.06)!important;box-sizing:border-box!important}
#tab-doc-accounts-income-report #dior-income-report-table tbody td{display:flex!important;flex-direction:column!important;align-items:flex-start!important;gap:3px!important;min-width:0!important;padding:0!important;border:0!important;background:transparent!important;white-space:normal!important;overflow-wrap:anywhere!important;font-size:11px!important;text-align:left!important}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:before{font-size:9px!important;font-weight:700!important;letter-spacing:.04em!important;text-transform:uppercase!important;color:#8493A7!important}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:first-child{grid-column:1/-1!important;padding-bottom:8px!important;border-bottom:1px solid #EDF1F6!important;font-size:14px!important;font-weight:700!important;color:#12345A!important}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(2):before{content:"January"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(3):before{content:"February"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(4):before{content:"March"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(5):before{content:"April"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(6):before{content:"May"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(7):before{content:"June"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(8):before{content:"July"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(9):before{content:"August"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(10):before{content:"September"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(11):before{content:"October"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(12):before{content:"November"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(13):before{content:"December"}
#tab-doc-accounts-income-report #dior-income-report-table tbody td:nth-child(14):before{content:"Yearly Total"}
#tab-doc-accounts-income-report #dior-income-report-table tfoot{display:block!important;width:100%!important;margin-top:10px!important}
#tab-doc-accounts-income-report #dior-income-report-table tfoot tr{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;padding:12px!important;border:1px solid #E2E8F0!important;border-radius:12px!important;background:#fff!important}
#tab-doc-accounts-income-report #dior-income-report-table tfoot td{display:block!important;min-width:0!important;padding:4px!important;border:0!important;white-space:normal!important;overflow-wrap:anywhere!important}
}

#tab-doc-accounts-income-report #dior-income-report-table tfoot .dior-income-footer-select-spacer{
    width:48px!important;min-width:48px!important;padding:0!important;border-right:0!important;
}
#tab-doc-accounts-income-report #dior-income-report-table tfoot td{vertical-align:top!important;white-space:nowrap!important;}
#tab-doc-accounts-income-report #dior-income-report-table tfoot td.ps-3{white-space:normal!important;min-width:150px!important;}
@media(max-width:767.98px){#tab-doc-accounts-income-report #dior-income-report-table tfoot .dior-income-footer-select-spacer{display:none!important;}}
</style>



<script>
(function () {
    'use strict';

    // Server-provided report data for the selected doctor and year.
    var incomeTableData = {
        dailyCategories: <?php echo wp_json_encode($ir_daily_categories); ?>,
        dailySeries: <?php echo wp_json_encode($ir_dept_daily); ?>,
        monthlyRows: <?php echo wp_json_encode($ir_dept_monthly); ?>,
        monthlyTotals: <?php echo wp_json_encode($ir_monthly); ?>
    };

    var incomeChartData = {
        Monthly: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            series: <?php echo wp_json_encode($ir_chart_series); ?>
        },
        Daily: {
            categories: <?php echo wp_json_encode($ir_daily_categories); ?>,
            series: <?php echo wp_json_encode($ir_daily_series); ?>
        },
        Weekly: {
            categories: <?php echo wp_json_encode($ir_weekly_categories); ?>,
            series: <?php echo wp_json_encode($ir_weekly_series); ?>
        },
        Yearly: {
            categories: <?php echo wp_json_encode($ir_year_categories); ?>,
            series: <?php echo wp_json_encode($ir_year_series); ?>
        }
    };

    var incomeChartInstance = null;
    var chartRetryTimer = null;

    // Escape department names before inserting them into table HTML.
    function escapeIncomeTableText(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'}[character];
        });
    }

    // Format numeric values consistently.
    function formatIncomeAmount(value) {
        var amount = Number(value || 0);
        return (Number.isFinite(amount) ? amount : 0).toFixed(2);
    }

    // Render the departmental table for Monthly, Daily, or Weekly periods.
    function renderIncomeTablePeriod() {
        var selector = document.getElementById('dior-income-table-period');
        var table = document.getElementById('dior-income-report-table');
        if (!selector || !table) return;

        var thead = table.querySelector('thead');
        var tbody = table.querySelector('tbody');
        var tfoot = table.querySelector('tfoot');
        if (!thead || !tbody || !tfoot) return;

        var period = selector.value || 'Monthly';

        if (period === 'Monthly') {
            var monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
            var monthlyRows = incomeTableData.monthlyRows || {};
            var monthlyTotals = incomeTableData.monthlyTotals || [];

            thead.innerHTML = '<tr><th class="ps-3">DEPARTMENT</th>' +
                monthNames.map(function (month) {
                    return '<th style="text-align:right;">' + month + '</th>';
                }).join('') +
                '<th style="text-align:right;" class="pe-3">YEARLY TOTAL</th></tr>';

            var rowNames = Object.keys(monthlyRows);
            tbody.innerHTML = rowNames.map(function (name) {
                var values = monthlyRows[name] || [];
                var rowTotal = values.reduce(function (sum, value) {
                    return sum + Number(value || 0);
                }, 0);

                return '<tr><td class="ps-3 fw-bold text-dark">' + escapeIncomeTableText(name) + '</td>' +
                    values.map(function (value) {
                        return '<td class="text-end">$' + formatIncomeAmount(value) + '</td>';
                    }).join('') +
                    '<td class="text-end pe-3 fw-bold">$' + formatIncomeAmount(rowTotal) + '</td></tr>';
            }).join('') || '<tr><td colspan="14" class="text-center text-muted py-4">No departmental income data available yet.</td></tr>';

            var monthlyGrandTotal = monthlyTotals.reduce(function (sum, value) {
                return sum + Number(value || 0);
            }, 0);

            tfoot.innerHTML = '<tr class="fw-bold"><td class="ps-3 text-dark">Combined Clinic Revenue</td>' +
                monthlyTotals.map(function (value) {
                    return '<td class="text-end text-primary">$' + formatIncomeAmount(value) + '</td>';
                }).join('') +
                '<td class="text-end pe-3 text-primary">$' + formatIncomeAmount(monthlyGrandTotal) + '</td></tr>';
            return;
        }

        var allLabels = incomeTableData.dailyCategories || [];
        var startIndex = period === 'Weekly' ? Math.max(0, allLabels.length - 7) : 0;
        var selectedLabels = allLabels.slice(startIndex);
        var departments = incomeTableData.dailySeries || {};
        var totalsByDay = selectedLabels.map(function () { return 0; });

        var totalLabel = period === 'Weekly' ? '7-DAY TOTAL' : '14-DAY TOTAL';
        thead.innerHTML = '<tr><th class="ps-3">DEPARTMENT</th>' +
            selectedLabels.map(function (label) {
                return '<th style="text-align:right;">' + escapeIncomeTableText(String(label).toUpperCase()) + '</th>';
            }).join('') +
            '<th style="text-align:right;" class="pe-3">' + totalLabel + '</th></tr>';

        var departmentNames = Object.keys(departments);
        tbody.innerHTML = departmentNames.map(function (name) {
            var values = (departments[name] || []).slice(startIndex);
            values.forEach(function (value, index) {
                totalsByDay[index] += Number(value || 0);
            });

            var rowTotal = values.reduce(function (sum, value) {
                return sum + Number(value || 0);
            }, 0);

            return '<tr><td class="ps-3 fw-bold text-dark">' + escapeIncomeTableText(name) + '</td>' +
                values.map(function (value) {
                    return '<td class="text-end">$' + formatIncomeAmount(value) + '</td>';
                }).join('') +
                '<td class="text-end pe-3 fw-bold">$' + formatIncomeAmount(rowTotal) + '</td></tr>';
        }).join('') || '<tr><td colspan="' + (selectedLabels.length + 2) + '" class="text-center text-muted py-4">No departmental income data available yet.</td></tr>';

        var dailyGrandTotal = totalsByDay.reduce(function (sum, value) {
            return sum + value;
        }, 0);

        tfoot.innerHTML = '<tr class="fw-bold"><td class="ps-3 text-dark">Combined Clinic Revenue</td>' +
            totalsByDay.map(function (value) {
                return '<td class="text-end text-primary">$' + formatIncomeAmount(value) + '</td>';
            }).join('') +
            '<td class="text-end pe-3 text-primary">$' + formatIncomeAmount(dailyGrandTotal) + '</td></tr>';
    }

    // Render ApexCharts using the selected chart period.
    function renderIncomeChart() {
        var chartElement = document.getElementById('dior-income-report-apex-chart');
        var panel = document.getElementById('tab-doc-accounts-income-report');
        if (!chartElement || !panel || window.getComputedStyle(panel).display === 'none') return;

        if (typeof window.ApexCharts === 'undefined') {
            if (!chartRetryTimer) {
                chartRetryTimer = window.setTimeout(function () {
                    chartRetryTimer = null;
                    renderIncomeChart();
                }, 300);
            }
            return;
        }

        if (incomeChartInstance) {
            incomeChartInstance.destroy();
            incomeChartInstance = null;
        }

        var filter = document.getElementById('dior-income-chart-filter');
        var period = filter ? filter.value : 'Monthly';
        var chartData = incomeChartData[period] || incomeChartData.Monthly;
        var yearlyView = period === 'Yearly';

        var options = {
            series: chartData.series,
            chart: {
                type: yearlyView ? 'bar' : 'area',
                height: 390,
                fontFamily: 'inherit',
                toolbar: {
                    show: true,
                    offsetX: -4,
                    offsetY: -6,
                    tools: {download:true, selection:true, zoom:true, zoomin:true, zoomout:true, pan:true, reset:true}
                },
                animations: {enabled:true, easing:'easeinout', speed:650}
            },
            colors: ['#0B3768', '#1A528E', '#3B82B8', '#62A6C8', '#8AB8D6'],
            plotOptions: {bar:{columnWidth:'38%', borderRadius:5}},
            dataLabels: {enabled:false},
            stroke: {curve:'smooth', width:2.5},
            fill: {
                type:'gradient',
                gradient:{shadeIntensity:1, opacityFrom:0.45, opacityTo:0.05, stops:[0,90,100]}
            },
            xaxis: {
                categories: chartData.categories,
                labels:{style:{colors:'#64748b', fontSize:'12px', fontWeight:500}},
                axisBorder:{color:'#e2e8f0'},
                axisTicks:{color:'#e2e8f0'}
            },
            yaxis: {
                labels:{
                    formatter:function(value){return '$' + Number(value || 0).toLocaleString();},
                    style:{colors:'#64748b', fontSize:'12px'}
                }
            },
            tooltip: {
                shared:true,
                intersect:false,
                y:{formatter:function(value){return '$' + Number(value || 0).toLocaleString() + '.00';}}
            },
            grid:{borderColor:'#E6EEF6', strokeDashArray:4},
            legend:{
                position:'bottom',
                horizontalAlign:'center',
                offsetY:8,
                fontSize:'13px',
                fontWeight:600,
                labels:{colors:'#334155'},
                markers:{width:10, height:10, radius:10}
            }
        };

        incomeChartInstance = new window.ApexCharts(chartElement, options);
        incomeChartInstance.render();
    }

    // Year selector reloads the report for the selected year.
    var yearSelector = document.getElementById('dior-income-table-year');
    if (yearSelector) {
        yearSelector.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('income_year', this.value);
            window.location.href = url.toString();
        });
    }

    var tablePeriodSelector = document.getElementById('dior-income-table-period');
    if (tablePeriodSelector) tablePeriodSelector.addEventListener('change', renderIncomeTablePeriod);

    var chartFilter = document.getElementById('dior-income-chart-filter');
    if (chartFilter) chartFilter.addEventListener('change', renderIncomeChart);

    var chartRefresh = document.getElementById('dior-income-chart-refresh');
    if (chartRefresh) chartRefresh.addEventListener('click', renderIncomeChart);

    // Re-render the chart when the hidden dashboard tab becomes visible.
    var incomePanel = document.getElementById('tab-doc-accounts-income-report');
    if (incomePanel && typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.attributeName === 'style' && window.getComputedStyle(incomePanel).display !== 'none') {
                    window.setTimeout(renderIncomeChart, 50);
                }
            });
        });
        observer.observe(incomePanel, {attributes:true, attributeFilter:['style']});
    }

    renderIncomeTablePeriod();
    window.diorRenderIncomeChart = renderIncomeChart;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderIncomeChart, {once:true});
    } else {
        renderIncomeChart();
    }
})();
</script>
