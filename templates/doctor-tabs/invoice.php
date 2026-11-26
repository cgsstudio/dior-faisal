<section class="dior-tab-panel" id="tab-doc-accounts-invoice" style="display: none; background: #f8fafc; padding: 20px; position: relative; overflow: hidden;">
    
    <!-- Scoped CSS to prevent watermark & match design -->
    <style>
    #tab-doc-accounts-invoice .dior-page-watermark,
    #tab-doc-accounts-invoice .page-watermark,
    #tab-doc-accounts-invoice .dior-big-title,
    .dior-doctor-wrap #tab-doc-accounts-invoice .dior-page-watermark,
    .dior-doctor-wrap #tab-doc-accounts-invoice .page-watermark,
    .dior-doctor-wrap #tab-doc-accounts-invoice .dior-big-title {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
    }

    .dior-invoice-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 12px 40px rgba(15,23,42,.06);
        border: 1px solid #eef2f6;
        padding: 40px 50px;
        position: relative;
    }
    
    .dior-invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px dashed #e2e8f0;
        padding-bottom: 24px;
        margin-bottom: 30px;
    }
    
    .dior-invoice-header h2 {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .dior-status-paid {
        background: #ecfdf5;
        color: #059669;
        padding: 8px 18px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: 1px solid #a7f3d0;
        box-shadow: 0 4px 12px rgba(5,150,105,0.15);
    }
    
    .dior-invoice-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    /* Larger branded logo panel for the invoice and exported PDF. */
    .dior-company-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        min-width: 190px;
    }

    .dior-company-info img {
        display: block;
        width: 150px;
        height: 104px;
        max-width: 100%;
        padding: 12px 16px;
        margin: 0 0 16px;
        object-fit: contain;
        object-position: left center;
        background: #ffffff;
        border: 1px solid #e5edf5;
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .06);
    }
    
    .dior-company-info h3 {
        font-size: 18px;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 10px 0;
    }
    
    .dior-address-text {
        font-size: 14px;
        color: #64748b;
        line-height: 1.8;
        margin: 0;
    }
    
    .dior-client-info {
        text-align: right;
    }
    
    .dior-client-info .bill-to {
        font-size: 13px;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        margin: 0 0 8px 0;
        letter-spacing: 1px;
    }
    
    .dior-client-info h4 {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 10px 0;
    }
    
    .dior-invoice-meta {
        margin-top: 24px;
        font-size: 14px;
    }
    
    .dior-invoice-meta .meta-item {
        margin-bottom: 8px;
    }
    
    .dior-invoice-meta .meta-label {
        color: #64748b;
        margin-right: 8px;
        display: inline-block;
        width: 100px;
        text-align: right;
    }
    
    .dior-invoice-meta .meta-value {
        color: #1e293b;
        font-weight: 700;
    }
    
    .dior-invoice-table-section .section-title {
        font-size: 18px;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 20px;
    }
    
    .dior-invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 40px;
    }
    
    .dior-invoice-table th,
    .dior-invoice-table td {
        border-left: none !important;
        border-right: none !important;
        border-top: none !important;
    }
    
    .dior-invoice-table th {
        background: #f8fafc !important;
        color: #475569;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 16px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0 !important;
        letter-spacing: 1px;
    }
    
    .dior-invoice-table td {
        padding: 18px 16px;
        font-size: 14px;
        color: #475569;
        border-bottom: 1px solid #f1f5f9 !important;
        vertical-align: middle;
    }
    
    .dior-invoice-table th.text-end,
    .dior-invoice-table td.text-end { text-align: right; }
    
    .dior-invoice-table th.text-center,
    .dior-invoice-table td.text-center { text-align: center; }
    
    .dior-invoice-table tfoot td {
        padding: 24px 16px;
        font-size: 16px;
        border-bottom: none !important;
    }
    
    .dior-invoice-summary-section {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .dior-payment-info {
        flex: 1;
        min-width: 300px;
        background: linear-gradient(145deg, #f8fafc, #f1f5f9);
        padding: 24px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    
    .dior-payment-info .section-title {
        font-size: 16px;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 16px 0;
    }
    
    .dior-payment-details .detail-row {
        display: flex;
        margin-bottom: 12px;
        font-size: 14px;
    }
    
    .dior-payment-details .label {
        width: 140px;
        color: #64748b;
        font-weight: 500;
    }
    
    .dior-payment-details .value {
        color: #1e293b;
        font-weight: 700;
    }
    
    .dior-totals-box {
        flex: 0 0 350px;
    }
    
    .dior-totals-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        font-size: 15px;
        color: #475569;
        border-bottom: 1px dashed #e2e8f0;
    }
    
    .dior-totals-row:last-child {
        border-bottom: none;
    }
    
    .dior-totals-row.total {
        border-top: 2px solid #e2e8f0;
        border-bottom: 2px solid #e2e8f0;
        padding: 20px 0;
        margin-top: 12px;
        font-size: 20px;
        font-weight: 900;
        color: #1A528E;
    }
    
    .dior-invoice-actions {
        display: flex;
        justify-content: flex-end;
        gap: 16px;
        flex-wrap: wrap;
        border-top: 1px solid #f1f5f9;
        padding-top: 30px;
    }
    
    .dior-btn {
        padding: 12px 28px !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        border: none !important;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        line-height: normal !important;
        height: auto !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .dior-btn-primary { background: #1A528E !important; color: #ffffff !important; border: 1px solid #1A528E !important; box-shadow: 0 4px 12px rgba(26,82,142,0.18) !important; }
    .dior-btn-info { background: #1A528E !important; color: #ffffff !important; border: 1px solid #1A528E !important; box-shadow: 0 4px 12px rgba(26,82,142,0.18) !important; }
    .dior-btn-secondary { background: #1A528E !important; color: #ffffff !important; border: 1px solid #1A528E !important; box-shadow: 0 4px 12px rgba(26,82,142,0.18) !important; }
    
    .dior-btn:hover {
        transform: translateY(-2px);
        background: #0B3768 !important;
        border-color: #0B3768 !important;
        color: #ffffff !important;
        filter: none;
        box-shadow: 0 5px 14px rgba(11,55,104,0.22) !important;
    }
    </style>

    <!-- Header Breadcrumb without .breadcrumb-main -->
    <div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; position: relative; z-index: 5;">
        <div>
            <h4 class="mb-0" style="font-size: 20px; font-weight: 700; color: #1e293b;">Invoice</h4>
        </div>
        <div>
            <ul class="va-breadcrumb-list" style="display: flex; gap: 8px; list-style: none; padding: 0; margin: 0; align-items: center; font-size: 12px;">
                <li><a href="javascript:void(0)"><i class="fas fa-house" style="color: #2563eb;"></i></a></li>
                <li style="color: #cbd5e1;">/</li>
                <li><a href="javascript:void(0)" style="color: #64748b; text-decoration: none;">Accounts</a></li>
                <li style="color: #cbd5e1;">/</li>
                <li class="active"><span style="color: #1e293b; font-weight: 600;">Invoice</span></li>
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
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-expenses" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-expenses');">
            <i class="fas fa-arrow-trend-down"></i> Expenses
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-income-report" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-income-report');">
            <i class="fas fa-chart-pie"></i> Income Report
        </button>
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-accounts-invoice" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-invoice');">
            <i class="fas fa-file-invoice"></i> Invoice
        </button>
    </div>

    <!-- Main Card -->
    <div class="dior-invoice-card">
        
        <?php
        global $wpdb;
        $doctor_id = get_current_user_id();
        $doctor_ids = class_exists('Dior_Doctor_Resolver')
            ? Dior_Doctor_Resolver::get_accessible_doctor_ids($doctor_id)
            : [$doctor_id];
        $doctor_ids = array_values(array_unique(array_filter(array_map('absint', (array) $doctor_ids))));
        $invoice_records = [];
        $seen_invoice_ids = [];
        $inv_table = $wpdb->prefix . 'dior_invoices';
        $pay_table = $wpdb->prefix . 'dior_payments';
        $patients_table = $wpdb->prefix . 'dior_patients';
        $invoices_exist = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $inv_table)) === $inv_table;
        $payments_exist = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $pay_table)) === $pay_table;
        $doctor_placeholders = $doctor_ids ? implode(',', array_fill(0, count($doctor_ids), '%d')) : '';

        // Load real invoice records belonging to the signed-in doctor's scope.
        if ($doctor_ids && $invoices_exist) {
            $payment_join = $payments_exist
                ? "LEFT JOIN $pay_table pay ON pay.invoice_uid = i.invoice_uid"
                : "LEFT JOIN (SELECT NULL AS invoice_uid, NULL AS payment_method, NULL AS transaction_id, NULL AS amount, NULL AS currency, NULL AS status, NULL AS paid_at) pay ON 1=0";
            $sql = "SELECT i.*, p.first_name, p.last_name, p.email AS patient_email,
                           u.display_name AS patient_display, doc.display_name AS doctor_display,
                           pay.payment_method, pay.transaction_id, pay.amount AS paid_amount,
                           pay.currency AS paid_currency, pay.status AS payment_status, pay.paid_at
                    FROM $inv_table i
                    LEFT JOIN $patients_table p ON (p.user_id = i.patient_id OR p.id = i.patient_id)
                    LEFT JOIN {$wpdb->users} u ON u.ID = i.patient_id
                    LEFT JOIN {$wpdb->users} doc ON doc.ID = i.doctor_id
                    $payment_join
                    WHERE i.doctor_id IN ($doctor_placeholders)
                    ORDER BY i.created_at DESC, i.id DESC LIMIT 200";
            $rows = $wpdb->get_results($wpdb->prepare($sql, ...$doctor_ids), ARRAY_A);
            foreach ((array) $rows as $row) {
                $uid = (string) ($row['invoice_uid'] ?? '');
                if ($uid !== '') $seen_invoice_ids[$uid] = true;
                $invoice_records[] = $row;
            }
        }

        // Include real payment receipts that do not already have an invoice row.
        if ($doctor_ids && $payments_exist) {
            $sql = "SELECT pay.*, p.first_name, p.last_name, p.email AS patient_email,
                           u.display_name AS patient_display, doc.display_name AS doctor_display
                    FROM $pay_table pay
                    LEFT JOIN $patients_table p ON (p.user_id = pay.patient_id OR p.id = pay.patient_id)
                    LEFT JOIN {$wpdb->users} u ON u.ID = pay.patient_id
                    LEFT JOIN {$wpdb->users} doc ON doc.ID = pay.doctor_id
                    WHERE pay.doctor_id IN ($doctor_placeholders)
                    ORDER BY COALESCE(pay.paid_at, pay.created_at) DESC, pay.id DESC LIMIT 200";
            $rows = $wpdb->get_results($wpdb->prepare($sql, ...$doctor_ids), ARRAY_A);
            foreach ((array) $rows as $row) {
                $uid = (string) ($row['invoice_uid'] ?? $row['payment_uid'] ?? ('PAY-' . ($row['id'] ?? '')));
                if ($uid === '' || isset($seen_invoice_ids[$uid])) continue;
                $seen_invoice_ids[$uid] = true;
                $row['invoice_uid'] = $uid;
                $row['description'] = $row['description'] ?? 'Medical payment';
                $row['total_amount'] = $row['total_amount'] ?? $row['amount'] ?? 0;
                $invoice_records[] = $row;
            }
        }

        // Include doctor-created bills saved through the Add Bill form.
        $manual_table = $wpdb->prefix . 'dior_doctor_bills';
        if ($doctor_ids && $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $manual_table)) === $manual_table) {
            $sql = "SELECT b.*, u.display_name AS patient_display, u.user_email AS patient_email,
                           doc.display_name AS doctor_display, b.bill_uid AS invoice_uid,
                           b.service_type AS description, b.status AS payment_status,
                           b.amount AS paid_amount, b.created_at AS paid_at, 'USD' AS currency
                    FROM $manual_table b
                    LEFT JOIN {$wpdb->users} u ON u.ID = b.patient_id
                    LEFT JOIN {$wpdb->users} doc ON doc.ID = b.doctor_id
                    WHERE b.doctor_id IN ($doctor_placeholders)
                    ORDER BY b.created_at DESC LIMIT 200";
            $rows = $wpdb->get_results($wpdb->prepare($sql, ...$doctor_ids), ARRAY_A);
            foreach ((array) $rows as $row) {
                $uid = (string) ($row['invoice_uid'] ?? '');
                if ($uid === '' || isset($seen_invoice_ids[$uid])) continue;
                $seen_invoice_ids[$uid] = true;
                $invoice_records[] = $row;
            }
        }

        // Include Stripe receipts saved in patient payment metadata, scoped by doctor ID.
        if ($doctor_ids) {
            $meta_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT pm.user_id, pm.meta_value, u.display_name AS patient_display, u.user_email AS patient_email
                 FROM {$wpdb->usermeta} pm INNER JOIN {$wpdb->users} u ON u.ID = pm.user_id
                 WHERE pm.meta_key = %s ORDER BY pm.umeta_id DESC LIMIT 500",
                'dior_payments'
            ), ARRAY_A);
            foreach ((array) $meta_rows as $meta_row) {
                $payments = maybe_unserialize($meta_row['meta_value']);
                if (!is_array($payments)) continue;
                foreach ($payments as $payment) {
                    if (!is_array($payment) || !in_array(absint($payment['doctor_id'] ?? 0), $doctor_ids, true)) continue;
                    $uid = sanitize_text_field($payment['invoice_uid'] ?? $payment['id'] ?? '');
                    if ($uid === '' || isset($seen_invoice_ids[$uid])) continue;
                    $seen_invoice_ids[$uid] = true;
                    $doc = get_userdata(absint($payment['doctor_id']));
                    $invoice_records[] = [
                        'invoice_uid' => $uid, 'patient_id' => absint($meta_row['user_id']),
                        'patient_display' => $meta_row['patient_display'] ?? 'Patient',
                        'patient_email' => $meta_row['patient_email'] ?? '',
                        'doctor_display' => $payment['doctor_name'] ?? ($doc ? $doc->display_name : 'Doctor'),
                        'payment_method' => $payment['method'] ?? '—',
                        'status' => $payment['status'] ?? 'Paid', 'payment_status' => $payment['status'] ?? 'Paid',
                        'created_at' => $payment['date'] ?? '', 'paid_at' => $payment['date'] ?? '',
                        'amount' => $payment['amount'] ?? $payment['total'] ?? 0,
                        'total_amount' => $payment['total'] ?? $payment['amount'] ?? 0,
                        'tax' => $payment['tax'] ?? 0, 'discount' => $payment['discount'] ?? 0,
                        'description' => $payment['description'] ?? 'Medical payment',
                        'currency' => 'USD'
                    ];
                }
            }
        }

        usort($invoice_records, static function ($a, $b) {
            return (strtotime((string) ($b['created_at'] ?? $b['paid_at'] ?? '')) ?: 0)
                <=> (strtotime((string) ($a['created_at'] ?? $a['paid_at'] ?? '')) ?: 0);
        });
        $invoice_records = array_slice($invoice_records, 0, 200);
        $invoice_json = [];
        foreach ($invoice_records as $record) {
            $patient_name = trim((string) (($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? '')));
            $patient_name = $patient_name ?: ($record['patient_display'] ?? 'Patient');
            $currency = strtoupper((string) ($record['paid_currency'] ?? $record['currency'] ?? 'USD'));
            $amount = (float) ($record['amount'] ?? $record['total_amount'] ?? 0);
            $tax = (float) ($record['tax'] ?? 0);
            $discount = (float) ($record['discount'] ?? 0);
            $total = (float) ($record['total_amount'] ?? ($amount + $tax - $discount));
            $invoice_json[] = [
                'uid' => (string) ($record['invoice_uid'] ?? ('INV-' . ($record['id'] ?? ''))),
                'patient' => $patient_name,
                'patient_email' => (string) ($record['patient_email'] ?? ''),
                'doctor' => (string) ($record['doctor_display'] ?? 'Attending Physician'),
                'date' => (string) ($record['created_at'] ?? $record['paid_at'] ?? ''),
                'description' => (string) ($record['service_type'] ?? $record['description'] ?? 'Medical Consultation'),
                'method' => (string) ($record['payment_method'] ?? $record['method'] ?? '—'),
                'status' => ucfirst(strtolower((string) ($record['payment_status'] ?? $record['status'] ?? 'Pending'))),
                'currency' => $currency, 'amount' => $amount, 'tax' => $tax,
                'discount' => $discount, 'total' => $total,
                'transaction' => (string) ($record['transaction_id'] ?? $record['stripe_session_id'] ?? '')
            ];
        }
        $first_invoice = $invoice_json[0] ?? null;
        ?>
        <?php if (!$first_invoice): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-file-invoice fs-2 d-block mb-2 text-secondary opacity-50"></i>
                <p class="mb-0 fw-semibold small">No invoices, bills or payment receipts found for this doctor.</p>
                <button type="button" class="btn btn-primary mt-3" onclick="if(typeof diorDocSwitchTab==='function')diorDocSwitchTab('doc-accounts-add-bill');">Create Bill</button>
            </div>
        <?php else: ?>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <label for="dior-invoice-selector" class="form-label fw-semibold mb-1">Select Invoice / Bill</label>
                    <select id="dior-invoice-selector" class="form-select" style="min-width:260px;max-width:420px;">
                        <?php foreach ($invoice_json as $index => $invoice): ?>
                            <option value="<?php echo esc_attr($index); ?>"><?php echo esc_html($invoice['uid'] . ' — ' . $invoice['patient'] . ' — ' . $invoice['status']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <span class="badge bg-light text-primary border"><?php echo esc_html(count($invoice_json)); ?> record(s)</span>
            </div>
            <div id="invoice-pdf-content" style="background:#fff;padding-bottom:20px;">
                <div class="dior-invoice-header">
                    <h2>INVOICE #<span data-invoice-field="uid"><?php echo esc_html($first_invoice['uid']); ?></span></h2>
                    <span class="dior-status-paid" data-invoice-field="status"><?php echo esc_html($first_invoice['status']); ?></span>
                </div>
                <div class="dior-invoice-info">
                    <div class="dior-company-info">
                        <img src="https://diormedical.com/wp-content/uploads/2026/08/logo.png" alt="Dior Medical Logo">
                        <h3>Dior Medical</h3>
                        <p class="dior-address-text">HIPAA-Compliant Healthcare Platform</p>
                    </div>
                    <div class="dior-client-info">
                        <h3 class="bill-to">BILL TO</h3>
                        <h4 data-invoice-field="patient"><?php echo esc_html($first_invoice['patient']); ?></h4>
                        <div class="dior-invoice-meta">
                            <div class="meta-item"><span class="meta-label">Invoice Date:</span> <span class="meta-value" data-invoice-field="date"><?php echo esc_html($first_invoice['date'] ? wp_date('M j, Y', strtotime($first_invoice['date'])) : '—'); ?></span></div>
                            <div class="meta-item"><span class="meta-label">Doctor:</span> <span class="meta-value" data-invoice-field="doctor"><?php echo esc_html($first_invoice['doctor']); ?></span></div>
                        </div>
                    </div>
                </div>
                <div class="dior-invoice-table-section">
                    <div style="overflow-x:auto;">
                        <table class="dior-invoice-table table table-hover">
                            <thead><tr><th>#</th><th>Description</th><th class="text-center">Quantity</th><th class="text-center">Unit Price</th><th class="text-center">Charges</th><th class="text-center">Discount</th><th class="text-end">Total</th></tr></thead>
                            <tbody><tr>
                                <td>1</td><td style="font-weight:500;color:#1e293b;" data-invoice-field="description"><?php echo esc_html($first_invoice['description']); ?></td>
                                <td class="text-center">1</td>
                                <td class="text-center" data-invoice-field="unit">$<?php echo esc_html(number_format($first_invoice['amount'],2)); ?></td>
                                <td class="text-center" data-invoice-field="charges">$<?php echo esc_html(number_format($first_invoice['tax'],2)); ?></td>
                                <td class="text-center" data-invoice-field="discount">-$<?php echo esc_html(number_format($first_invoice['discount'],2)); ?></td>
                                <td class="text-end" style="font-weight:600;" data-invoice-field="total">$<?php echo esc_html(number_format($first_invoice['total'],2)); ?></td>
                            </tr></tbody>
                            <tfoot><tr><td colspan="6" class="text-end"><strong style="color:#1e293b;">Subtotal</strong></td><td class="text-end"><strong data-invoice-field="subtotal">$<?php echo esc_html(number_format($first_invoice['amount'],2)); ?></strong></td></tr></tfoot>
                        </table>
                    </div>
                </div>
                <div class="dior-invoice-summary-section">
                    <div class="dior-payment-info">
                        <h3 class="section-title">Payment Information</h3>
                        <div class="dior-payment-details">
                            <div class="detail-row"><span class="label">Payment Method:</span><span class="value" data-invoice-field="method"><?php echo esc_html($first_invoice['method']); ?></span></div>
                            <div class="detail-row"><span class="label">Transaction ID:</span><span class="value" data-invoice-field="transaction"><?php echo esc_html($first_invoice['transaction'] ?: '—'); ?></span></div>
                        </div>
                    </div>
                    <div class="dior-totals-box">
                        <div class="dior-totals-row"><span>Subtotal:</span><span data-invoice-field="subtotal-total">$<?php echo esc_html(number_format($first_invoice['amount'],2)); ?></span></div>
                        <div class="dior-totals-row"><span>Tax:</span><span data-invoice-field="tax">$<?php echo esc_html(number_format($first_invoice['tax'],2)); ?></span></div>
                        <div class="dior-totals-row"><span>Discount:</span><span data-invoice-field="discount-total">-$<?php echo esc_html(number_format($first_invoice['discount'],2)); ?></span></div>
                        <div class="dior-totals-row total"><span>Total:</span><span data-invoice-field="grand-total">$<?php echo esc_html(number_format($first_invoice['total'],2)); ?></span></div>
                    </div>
                </div>
            </div>
            <script type="application/json" id="dior-invoice-data"><?php echo wp_json_encode($invoice_json); ?></script>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="dior-invoice-actions">
            <button class="dior-btn dior-btn-primary btn" onclick="window.print()"><i class="fas fa-print me-1"></i> Print</button>
            <button class="dior-btn dior-btn-info btn" id="download-invoice-pdf"><i class="fas fa-file-pdf me-1"></i> Download PDF</button>
            <button type="button" class="dior-btn dior-btn-secondary btn" id="dior-email-invoice"><i class="fas fa-envelope me-1"></i> Email Invoice</button>
        </div>
        
    </div>

</section>
<style id="dior-accounts-mobile-invoice">
/* Accounts mobile responsive layout, aligned with Patient Records */

@media(max-width:767.98px){
#dior-doctor-app #dior-doc-content #tab-doc-accounts-invoice{width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;padding:14px 0 24px!important;overflow-x:clip!important;box-sizing:border-box!important}
#tab-doc-accounts-invoice>.mb-4:first-child{align-items:flex-start!important}
#tab-doc-accounts-invoice>.mb-4:first-child .va-breadcrumb-list{display:none!important}
#tab-doc-accounts-invoice .dior-subtab-nav-bar{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;padding:0!important;margin:0 0 16px!important}
#tab-doc-accounts-invoice .dior-subtab-pill{width:100%!important;min-width:0!important;min-height:40px!important;margin:0!important;padding:8px 5px!important;font-size:12px!important;white-space:normal!important}
#tab-doc-accounts-invoice .dior-invoice-card{width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;padding:12px!important;border-radius:14px!important;box-sizing:border-box!important;overflow:hidden!important}
#tab-doc-accounts-invoice #invoice-pdf-content{width:100%!important;max-width:100%!important;padding:0 0 14px!important;box-sizing:border-box!important}
#tab-doc-accounts-invoice .dior-invoice-header{flex-wrap:wrap!important;gap:8px!important}
#tab-doc-accounts-invoice .dior-invoice-info{display:flex!important;flex-direction:column!important;gap:18px!important}
#tab-doc-accounts-invoice .dior-client-info{text-align:left!important}
#tab-doc-accounts-invoice .dior-invoice-meta .meta-label{width:auto!important;text-align:left!important}
#tab-doc-accounts-invoice .dior-invoice-table-section>div{overflow:visible!important}
#tab-doc-accounts-invoice .dior-invoice-table{display:block!important;width:100%!important;min-width:0!important;table-layout:fixed!important;margin:0!important}
#tab-doc-accounts-invoice .dior-invoice-table thead{display:none!important}
#tab-doc-accounts-invoice .dior-invoice-table tbody{display:grid!important;gap:10px!important;width:100%!important}
#tab-doc-accounts-invoice .dior-invoice-table tbody tr{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:9px 12px!important;width:100%!important;min-width:0!important;margin:0!important;padding:12px!important;border:1px solid #E2E8F0!important;border-radius:12px!important;background:#fff!important;box-sizing:border-box!important}
#tab-doc-accounts-invoice .dior-invoice-table tbody td{display:flex!important;flex-direction:column!important;align-items:flex-start!important;gap:3px!important;min-width:0!important;padding:0!important;border:0!important;white-space:normal!important;overflow-wrap:anywhere!important;text-align:left!important;font-size:12px!important}
/* Prevent inherited desktop table column widths from squeezing mobile cells. */
#tab-doc-accounts-invoice .dior-invoice-table tbody tr{grid-template-columns:repeat(2,minmax(0,1fr))!important}
#tab-doc-accounts-invoice .dior-invoice-table tbody td{width:100%!important;max-width:100%!important;min-width:0!important;box-sizing:border-box!important}
#tab-doc-accounts-invoice .dior-invoice-table tbody td:first-child{display:none!important}
#tab-doc-accounts-invoice .dior-invoice-table tbody td:before{font-size:9px!important;font-weight:700!important;letter-spacing:.04em!important;text-transform:uppercase!important;color:#8493A7!important}
#tab-doc-accounts-invoice .dior-invoice-table tbody td:nth-child(1):before{content:"Item"}
#tab-doc-accounts-invoice .dior-invoice-table tbody td:nth-child(2){grid-column:1/-1!important;font-weight:700!important;color:#12345A!important}
#tab-doc-accounts-invoice .dior-invoice-table tbody td:nth-child(2):before{content:"Description"}
#tab-doc-accounts-invoice .dior-invoice-table tbody td:nth-child(3):before{content:"Quantity"}
#tab-doc-accounts-invoice .dior-invoice-table tbody td:nth-child(4):before{content:"Unit Price"}
#tab-doc-accounts-invoice .dior-invoice-table tbody td:nth-child(5):before{content:"Charges"}
#tab-doc-accounts-invoice .dior-invoice-table tbody td:nth-child(6):before{content:"Discount"}
#tab-doc-accounts-invoice .dior-invoice-table tbody td:nth-child(7):before{content:"Total"}
#tab-doc-accounts-invoice .dior-invoice-table tfoot{display:block!important;width:100%!important;margin-top:10px!important}
#tab-doc-accounts-invoice .dior-invoice-table tfoot tr{display:grid!important;grid-template-columns:1fr auto!important;gap:8px!important;padding:12px!important;border:1px solid #E2E8F0!important;border-radius:12px!important;background:#F8FAFC!important}
#tab-doc-accounts-invoice .dior-invoice-table tfoot td{display:block!important;padding:0!important;border:0!important;white-space:normal!important}
#tab-doc-accounts-invoice .dior-invoice-summary-section{display:flex!important;flex-direction:column!important;gap:14px!important;margin:16px 0!important}
#tab-doc-accounts-invoice .dior-payment-info{width:100%!important;min-width:0!important;padding:14px!important;box-sizing:border-box!important}
#tab-doc-accounts-invoice .dior-payment-details .detail-row{justify-content:space-between!important;gap:10px!important;flex-wrap:wrap!important}
#tab-doc-accounts-invoice .dior-payment-details .label{width:auto!important}
#tab-doc-accounts-invoice .dior-totals-box{width:100%!important;flex:1 1 auto!important}
#tab-doc-accounts-invoice .dior-invoice-actions{display:grid!important;grid-template-columns:1fr!important;gap:9px!important;padding-top:16px!important}
#tab-doc-accounts-invoice .dior-invoice-actions .dior-btn{width:100%!important;justify-content:center!important}
}

</style>


<!-- html2pdf.js library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
(function () {
    'use strict';
    function initDiorInvoice() {
        var panel = document.getElementById('tab-doc-accounts-invoice');
        if (!panel || panel.dataset.invoiceActionsReady === '1') return;
        panel.dataset.invoiceActionsReady = '1';

        var selector = panel.querySelector('#dior-invoice-selector');
        var dataNode = panel.querySelector('#dior-invoice-data');
        var records = [];
        try { records = dataNode ? JSON.parse(dataNode.textContent || '[]') : []; } catch (error) { records = []; }

        var currencySymbols = { USD: '$', EUR: '€', GBP: '£', INR: '₹', CAD: 'CA$', AUD: 'A$' };
        function money(value, currency) {
            var symbol = currencySymbols[String(currency || 'USD').toUpperCase()] || (String(currency || 'USD').toUpperCase() + ' ');
            return symbol + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function dateLabel(value) {
            if (!value) return '—';
            var date = new Date(String(value).replace(' ', 'T'));
            return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        }
        function setField(name, value) {
            panel.querySelectorAll('[data-invoice-field="' + name + '"]').forEach(function (node) {
                node.textContent = value == null || value === '' ? '—' : String(value);
            });
        }
        function renderInvoice(index) {
            var item = records[index];
            if (!item) return;
            var currency = item.currency || 'USD';
            var subtotal = Number(item.amount || 0);
            var tax = Number(item.tax || 0);
            var discount = Number(item.discount || 0);
            var total = Number(item.total || subtotal + tax - discount);
            setField('uid', item.uid);
            setField('status', item.status);
            setField('patient', item.patient);
            setField('date', dateLabel(item.date));
            setField('doctor', item.doctor);
            setField('description', item.description);
            setField('unit', money(subtotal, currency));
            setField('charges', money(tax, currency));
            setField('discount', '-' + money(discount, currency));
            setField('total', money(total, currency));
            setField('subtotal', money(subtotal, currency));
            setField('subtotal-total', money(subtotal, currency));
            setField('tax', money(tax, currency));
            setField('discount-total', '-' + money(discount, currency));
            setField('grand-total', money(total, currency));
            setField('method', item.method);
            setField('transaction', item.transaction || '—');
        }
        if (selector) selector.addEventListener('change', function () { renderInvoice(Number(selector.value)); });

        var printButton = panel.querySelector('.dior-invoice-actions .dior-btn-primary');
        if (printButton) printButton.addEventListener('click', function (event) {
            event.preventDefault();
            var content = panel.querySelector('#invoice-pdf-content');
            if (!content) return;
            var printWindow = window.open('', '_blank', 'width=900,height=700');
            if (!printWindow) { window.print(); return; }
            printWindow.document.write('<!doctype html><html><head><title>Invoice</title><style>body{font:14px Arial,sans-serif;padding:30px;color:#1e293b}table{width:100%;border-collapse:collapse}th,td{padding:12px;border-bottom:1px solid #ddd;text-align:left}.dior-invoice-header,.dior-invoice-info,.dior-invoice-summary-section{display:flex;justify-content:space-between;gap:20px;margin-bottom:24px}.dior-invoice-table{width:100%}.dior-totals-row{display:flex;justify-content:space-between;padding:8px}.dior-status-paid{padding:6px 12px;border:1px solid #ddd;border-radius:20px}</style></head><body>' + content.outerHTML + '</body></html>');
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        });

        var pdfButton = panel.querySelector('#download-invoice-pdf');
        if (pdfButton) pdfButton.addEventListener('click', async function () {
            var content = panel.querySelector('#invoice-pdf-content');
            if (!content) return;
            if (typeof window.html2pdf !== 'function') {
                window.alert('PDF generator could not load. Please refresh the page and try again.');
                return;
            }

            var selected = records[selector ? Number(selector.value) : 0];
            var originalLabel = pdfButton.innerHTML;
            pdfButton.disabled = true;
            pdfButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Preparing PDF...';

            try {
                // Wait for the invoice logo and other images before capturing the invoice.
                var images = Array.prototype.slice.call(content.querySelectorAll('img'));
                await Promise.all(images.map(function (img) {
                    if (img.complete && img.naturalWidth > 0) {
                        return typeof img.decode === 'function' ? img.decode().catch(function () {}) : Promise.resolve();
                    }
                    return new Promise(function (resolve) {
                        img.addEventListener('load', resolve, { once: true });
                        img.addEventListener('error', resolve, { once: true });
                        window.setTimeout(resolve, 5000);
                    });
                }));

                if (document.fonts && document.fonts.ready) await document.fonts.ready;

                var options = {
                    margin: [10, 10, 12, 10],
                    filename: ((selected && selected.uid) ? selected.uid : 'Dior-Medical-Invoice') + '.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        allowTaint: false,
                        backgroundColor: '#ffffff',
                        logging: false,
                        scrollX: 0,
                        scrollY: 0,
                        windowWidth: Math.max(content.scrollWidth, 794),
                        onclone: function (clonedDocument) {
                            var clonedContent = clonedDocument.getElementById('invoice-pdf-content');
                            if (!clonedContent) return;
                            clonedContent.style.display = 'block';
                            clonedContent.style.width = '100%';
                            clonedContent.style.maxWidth = '100%';
                            clonedContent.style.padding = '18px';
                            clonedContent.style.background = '#ffffff';
                            clonedContent.style.color = '#172033';
                            clonedContent.style.boxSizing = 'border-box';

                            var clonedLogo = clonedContent.querySelector('.dior-company-info img');
                            if (clonedLogo) {
                                clonedLogo.style.width = '150px';
                                clonedLogo.style.height = '104px';
                                clonedLogo.style.objectFit = 'contain';
                                clonedLogo.style.padding = '12px 16px';
                            }

                            clonedContent.querySelectorAll('table').forEach(function (table) {
                                table.style.width = '100%';
                                table.style.borderCollapse = 'collapse';
                                table.style.tableLayout = 'fixed';
                            });
                            clonedContent.querySelectorAll('th, td').forEach(function (cell) {
                                cell.style.padding = '10px 8px';
                                cell.style.fontSize = '11px';
                                cell.style.overflowWrap = 'anywhere';
                            });
                            clonedContent.querySelectorAll('tr, .dior-invoice-header, .dior-invoice-info, .dior-invoice-summary-section, .dior-company-info').forEach(function (node) {
                                node.style.breakInside = 'avoid';
                                node.style.pageBreakInside = 'avoid';
                            });
                        }
                    },
                    pagebreak: { mode: ['css', 'legacy'], avoid: ['tr', '.dior-invoice-header', '.dior-invoice-info', '.dior-invoice-summary-section'] },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
                };

                await window.html2pdf().set(options).from(content).save();
            } catch (error) {
                console.error('Dior invoice PDF export failed:', error);
                window.alert('PDF could not be generated. Please try again. If the issue continues, check the browser console.');
            } finally {
                pdfButton.disabled = false;
                pdfButton.innerHTML = originalLabel;
            }
        });

        var emailButton = panel.querySelector('#dior-email-invoice');
        if (emailButton) emailButton.addEventListener('click', function () {
            var item = records[selector ? Number(selector.value) : 0];
            if (!item) return;
            if (!item.patient_email) { window.alert('Patient email address is not available for this invoice.'); return; }
            var currency = item.currency || 'USD';
            var body = [
                'Dear ' + item.patient + ',',
                '',
                'Please find your invoice details below.',
                'Invoice: ' + item.uid,
                'Doctor: ' + item.doctor,
                'Date: ' + dateLabel(item.date),
                'Description: ' + item.description,
                'Payment method: ' + item.method,
                'Status: ' + item.status,
                'Total: ' + money(item.total, currency),
                '',
                'Regards,',
                'Dior Medical'
            ].join('\n');
            window.location.href = 'mailto:' + encodeURIComponent(item.patient_email) +
                '?subject=' + encodeURIComponent('Invoice ' + item.uid + ' - Dior Medical') +
                '&body=' + encodeURIComponent(body);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initDiorInvoice);
    else initDiorInvoice();
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-doc-tab="doc-accounts-invoice"]')) {
            window.setTimeout(initDiorInvoice, 0);
        }
    });
})();
</script>
