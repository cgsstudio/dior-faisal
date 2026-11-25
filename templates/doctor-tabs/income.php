<section class="dior-tab-panel" id="tab-doc-accounts-income" style="display: none;">
    <!-- Breadcrumb Header -->
    <div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; padding: 0 5px;">
        <div>
            <h4 class="mb-0 text-dark" style="font-size: 20px; font-weight: 700;">Income</h4>
        </div>
        <div>
            <ul class="va-breadcrumb-list">
                <li><a href="#"><i class="fas fa-house"></i></a></li>
                <li>/</li>
                <li><a href="#">Accounts</a></li>
                <li>/</li>
                <li class="active"><span>Income</span></li>
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
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-accounts-income" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-income');">
            <i class="fas fa-arrow-trend-up"></i> Income
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-expenses" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-expenses');">
            <i class="fas fa-arrow-trend-down"></i> Expenses
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-income-report" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-income-report');">
            <i class="fas fa-chart-pie"></i> Income Report
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-invoice" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-invoice');">
            <i class="fas fa-file-invoice"></i> Invoice
        </button>
    </div>

    <div class="view-appointment-card">
        <div class="va-header-container">
            <div class="va-title-box">
                <h2><i class="fas fa-arrow-trend-up text-primary me-2"></i>Income</h2>
                <div class="va-title-line"></div>
            </div>

            <div class="va-actions-box">
                <div class="input-group input-group-sm dior-bs-search-group" style="width: 240px;">

                    <input type="text" class="va-search-input form-control border-start-0 ps-0"
                        placeholder="Search records...">
                </div>

                <button class="va-action-btn va-btn-primary btn" aria-label="Add new record">
                    <i class="fas fa-plus"></i>
                </button>
                <button class="va-action-btn va-btn-success btn" aria-label="Export to Excel">
                    <i class="fas fa-file-arrow-down"></i>
                </button>
                <button class="va-action-btn va-btn-info btn" aria-label="Refresh data">
                    <i class="fas fa-rotate-right"></i>
                </button>
            </div>
        </div>

        <div class="va-table-wrapper">
            <table class="va-table table table-hover">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;"><input type="checkbox"
                                class="va-checkbox form-check-input"></th>
                        <th>INCOME ID <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>PATIENT NAME <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>SERVICE TYPE <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>BILLED <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>PAID <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>METHOD <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>DATE <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>INVOICE # <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>STATUS <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    global $wpdb;
                    $inv_table = $wpdb->prefix . 'dior_invoices';
                    $inc_rows = [];
                    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $inv_table)) === $inv_table) {
                        $inc_rows = $wpdb->get_results(
                            "SELECT i.*, p.first_name, p.last_name, u.display_name AS patient_display
                             FROM $inv_table i
                             LEFT JOIN {$wpdb->prefix}dior_patients p ON (p.user_id = i.patient_id OR p.id = i.patient_id)
                             LEFT JOIN {$wpdb->users} u ON u.ID = i.patient_id
                             ORDER BY i.created_at DESC, i.id DESC
                             LIMIT 100",
                            ARRAY_A
                        );
                    }
                    if (empty($inc_rows)): ?>
                        <tr>
                            <td colspan="11" class="dior-empty-state text-center text-muted py-5">
                                <i class="fas fa-arrow-trend-up fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                <span class="small fw-semibold">No income records found.</span>
                            </td>
                        </tr>
                    <?php else:
                        foreach ($inc_rows as $ir):
                            $p_name  = trim((string) (($ir['first_name'] ?? '') . ' ' . ($ir['last_name'] ?? ''))) ?: ($ir['patient_display'] ?? 'Patient');
                            $billed  = number_format((float) ($ir['total_amount'] ?? $ir['amount'] ?? 0), 2);
                            $paid    = strtolower((string) ($ir['status'] ?? '')) === 'paid' ? $billed : '0.00';
                            $st_raw  = ucfirst(strtolower((string) ($ir['status'] ?? 'Pending')));
                            $st_cls  = strtolower($st_raw) === 'paid' || strtolower($st_raw) === 'completed' ? 'va-badge-completed' : 'va-badge-pending';
                            $i_date  = !empty($ir['created_at']) ? wp_date('M j, Y', strtotime($ir['created_at'])) : '—';
                    ?>
                        <tr>
                            <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input"></td>
                            <td><?php echo esc_html($ir['id'] ?? '—'); ?></td>
                            <td><?php echo esc_html($p_name); ?></td>
                            <td><?php echo esc_html($ir['service_type'] ?? ($ir['description'] ?? 'Consultation')); ?></td>
                            <td>$<?php echo esc_html($billed); ?></td>
                            <td>$<?php echo esc_html($paid); ?></td>
                            <td><?php echo esc_html($ir['payment_method'] ?? 'Card'); ?></td>
                            <td><i class="far fa-calendar va-icon-blue"></i> <?php echo esc_html($i_date); ?></td>
                            <td><?php echo esc_html($ir['invoice_uid'] ?? '—'); ?></td>
                            <td><span class="va-badge <?php echo esc_attr($st_cls); ?>"><?php echo esc_html($st_raw); ?></span></td>
                            <td>
                                <div class="va-row-actions" style="justify-content: center;">
                                    <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                    <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</section>
<style id="dior-accounts-mobile-income">
/* Accounts mobile responsive layout, aligned with Patient Records */

@media(max-width:767.98px){
#dior-doctor-app #dior-doc-content #tab-doc-accounts-income{width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;padding:14px 0 24px!important;overflow-x:clip;box-sizing:border-box!important}
#tab-doc-accounts-income>.mb-4:first-child{align-items:flex-start!important}
#tab-doc-accounts-income>.mb-4:first-child .va-breadcrumb-list{display:none!important}
#tab-doc-accounts-income .dior-subtab-nav-bar{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;padding:0!important;margin:0 0 16px!important}
#tab-doc-accounts-income .dior-subtab-pill{width:100%!important;min-width:0!important;min-height:40px!important;margin:0!important;padding:8px 5px!important;font-size:12px!important;white-space:normal!important}
#tab-doc-accounts-income .view-appointment-card{width:100%!important;max-width:100%!important;margin:0!important;padding:0!important;overflow:hidden!important;box-sizing:border-box!important}
#tab-doc-accounts-income .va-header-container{display:flex!important;flex-direction:column!important;align-items:stretch!important;gap:12px!important;padding:12px!important}
#tab-doc-accounts-income .va-header-actions{width:100%!important}
#tab-doc-accounts-income .va-actions-group{display:flex!important;gap:8px!important}
#tab-doc-accounts-income .va-actions-group .va-icon-btn{flex:1!important}
#tab-doc-accounts-income .va-table-wrapper{width:100%!important;max-width:100%!important;overflow:hidden!important;padding:0 10px 12px!important;box-sizing:border-box!important}
#tab-doc-accounts-income .va-table{display:block!important;width:100%!important;min-width:0!important;table-layout:fixed!important}
#tab-doc-accounts-income .va-table thead{display:none!important}
#tab-doc-accounts-income .va-table tbody{display:grid!important;gap:10px!important;width:100%!important}
#tab-doc-accounts-income .va-table tbody tr{position:relative!important;display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:10px 12px!important;width:100%!important;min-width:0!important;margin:0!important;padding:14px 12px 12px 42px!important;border:1px solid #E2E8F0!important;border-radius:14px!important;background:#fff!important;box-shadow:0 1px 3px rgba(15,23,42,.06)!important;box-sizing:border-box!important}
#tab-doc-accounts-income .va-table tbody td{display:flex!important;flex-direction:column!important;align-items:flex-start!important;gap:3px!important;min-width:0!important;padding:0!important;border:0!important;background:transparent!important;white-space:normal!important;overflow-wrap:anywhere!important;font-size:12px!important}
#tab-doc-accounts-income .va-table tbody td:before{font-size:9px!important;font-weight:700!important;letter-spacing:.04em!important;text-transform:uppercase!important;color:#8493A7!important}
#tab-doc-accounts-income .va-table tbody td:nth-child(1){position:absolute!important;left:12px!important;top:15px!important;display:block!important}
#tab-doc-accounts-income .va-table tbody td:nth-child(2):before{content:"Income ID"}
#tab-doc-accounts-income .va-table tbody td:nth-child(3):before{content:"Patient"}
#tab-doc-accounts-income .va-table tbody td:nth-child(4):before{content:"Service"}
#tab-doc-accounts-income .va-table tbody td:nth-child(5):before{content:"Billed"}
#tab-doc-accounts-income .va-table tbody td:nth-child(6):before{content:"Paid"}
#tab-doc-accounts-income .va-table tbody td:nth-child(7):before{content:"Method"}
#tab-doc-accounts-income .va-table tbody td:nth-child(8):before{content:"Date"}
#tab-doc-accounts-income .va-table tbody td:nth-child(9):before{content:"Invoice"}
#tab-doc-accounts-income .va-table tbody td:nth-child(10):before{content:"Status"}
#tab-doc-accounts-income .va-table tbody td:nth-child(3){font-weight:700!important;color:#12345A!important}
#tab-doc-accounts-income .va-table tbody td:nth-child(11){grid-column:1/-1;align-items:flex-end!important;padding-top:8px!important;border-top:1px solid #EDF1F6!important}
}

</style>
