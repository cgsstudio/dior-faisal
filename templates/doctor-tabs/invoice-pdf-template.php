<?php
/**
 * Dior Medical invoice document template.
 * Uses the selected invoice data prepared by invoice.php.
 * Keep the screen invoice and downloaded PDF design in this single template.
 */
if (!isset($first_invoice) || !is_array($first_invoice)) {
    return;
}
?>
<style id="dior-standard-invoice-template">
.dior-standard-invoice{background:#fff;color:#172b4d;width:100%;max-width:100%;padding:42px 48px 28px;border:1px solid #e5edf5;border-radius:14px;box-sizing:border-box;font-family:Arial,Helvetica,sans-serif}
.dior-standard-invoice__top{display:flex;justify-content:space-between;align-items:flex-start;gap:28px}
.dior-standard-invoice__brand{display:flex;align-items:center;gap:18px;min-width:0}
.dior-standard-invoice__brand img{display:block;width:150px;height:105px;max-width:100%;object-fit:contain;object-position:left center;padding:0!important;background:transparent!important;border:0!important;border-radius:0!important;box-shadow:none!important;box-sizing:border-box}
.dior-standard-invoice__brand-name{font-size:22px;font-weight:800;color:#063b70;letter-spacing:.2px}
.dior-standard-invoice__brand-caption{font-size:12px;color:#718096;margin-top:5px}
.dior-standard-invoice__heading{text-align:right;min-width:190px}
.dior-standard-invoice__eyebrow{font-size:10px;font-weight:700;letter-spacing:2px;color:#718096}
.dior-standard-invoice__heading h1{font-size:34px;line-height:1.1;font-weight:800;letter-spacing:2px;color:#063b70;margin:7px 0}
.dior-standard-invoice__number{font-size:13px;font-weight:700;color:#334e68}
.dior-standard-invoice__status{display:inline-block;margin-top:10px;padding:6px 14px;border-radius:30px;background:#e8f7ef;color:#087443;border:1px solid #b9e8ce;font-size:10px;font-weight:800;letter-spacing:1px;text-transform:uppercase}
.dior-standard-invoice__rule{height:3px;background:#0b5594;margin:28px 0 26px;border-radius:4px}
.dior-standard-invoice__parties{display:grid;grid-template-columns:1.1fr .9fr;gap:32px;margin-bottom:34px}
.dior-standard-invoice__label{font-size:10px;font-weight:800;letter-spacing:1.4px;color:#6b7f96;margin-bottom:12px}
.dior-standard-invoice__billto h2{font-size:19px;font-weight:700;color:#172b4d;margin:0 0 6px}
.dior-standard-invoice__muted{font-size:12px;color:#718096;overflow-wrap:anywhere}
.dior-standard-invoice__details{border-left:1px solid #e4ebf2;padding-left:24px}
.dior-standard-invoice__details>div{display:flex;justify-content:space-between;gap:18px;padding:7px 0;border-bottom:1px solid #edf1f5;font-size:12px}
.dior-standard-invoice__details span{color:#718096}
.dior-standard-invoice__details strong{color:#243b53;text-align:right;font-weight:700;overflow-wrap:anywhere}
.dior-standard-invoice__items{margin:0 0 28px}
.dior-standard-invoice__table{width:100%;border-collapse:collapse;table-layout:fixed;margin:0}
.dior-standard-invoice__table th{background:#f1f5f9;color:#52677f;padding:13px 9px;text-align:left;font-size:9px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;border-bottom:1px solid #dce5ef}
.dior-standard-invoice__table td{padding:16px 9px;border-bottom:1px solid #e8eef4;font-size:11px;color:#334e68;vertical-align:middle;overflow-wrap:anywhere}
.dior-standard-invoice__table td strong{display:block;color:#243b53;font-size:11px}
.dior-standard-invoice__table td small{display:block;margin-top:4px;color:#8a9aab;font-size:9px}
.dior-standard-invoice__table .col-no{width:5%}
.dior-standard-invoice__table .col-qty{width:7%;text-align:center}
.dior-standard-invoice__table .col-money{width:14%;text-align:right}
.dior-standard-invoice__table .col-total{width:15%;text-align:right}
.dior-standard-invoice__bottom{display:grid;grid-template-columns:1.15fr .85fr;gap:34px;align-items:start}
.dior-standard-invoice__payment{background:#f7f9fc;border:1px solid #e4ebf2;border-radius:10px;padding:20px}
.dior-standard-invoice__payment-row{display:flex;justify-content:space-between;gap:14px;padding:8px 0;border-bottom:1px solid #e7edf3;font-size:11px}
.dior-standard-invoice__payment-row span{color:#718096}
.dior-standard-invoice__payment-row strong{color:#243b53;text-align:right;overflow-wrap:anywhere}
.dior-standard-invoice__payment p{margin:16px 0 0;color:#0b5594;font-size:11px;font-weight:700}
.dior-standard-invoice__totals>div{display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #e8eef4;font-size:12px;color:#52677f}
.dior-standard-invoice__totals strong{color:#243b53;font-weight:700}
.dior-standard-invoice__totals .grand-total{margin-top:8px;padding:15px;background:#063b70;border:0;border-radius:8px;color:#fff;font-size:14px;font-weight:800}
.dior-standard-invoice__totals .grand-total strong{color:#fff;font-size:17px}
.dior-standard-invoice__footer{display:flex;justify-content:space-between;gap:20px;margin-top:34px;padding-top:14px;border-top:1px solid #e5edf5;color:#91a0b1;font-size:9px}
@media(max-width:767.98px){
.dior-standard-invoice{padding:20px 15px;border-radius:10px}
.dior-standard-invoice__top{gap:18px;flex-direction:column}
.dior-standard-invoice__heading{text-align:left}
.dior-standard-invoice__brand img{width:84px;height:76px}
.dior-standard-invoice__parties,.dior-standard-invoice__bottom{grid-template-columns:1fr;gap:18px}
.dior-standard-invoice__details{border-left:0;padding-left:0}
.dior-standard-invoice__items{overflow-x:auto}
.dior-standard-invoice__table{min-width:680px}
.dior-standard-invoice__footer{flex-direction:column;gap:6px}
}
</style>
<div id="invoice-pdf-content" class="dior-standard-invoice">
                <header class="dior-standard-invoice__top">
                    <div class="dior-standard-invoice__brand">
                        <img src="https://diormedical.com/wp-content/uploads/2026/08/logo.png" alt="Dior Medical Logo">
                        <div>
                            <div class="dior-standard-invoice__brand-name">Dior Medical</div>
                            <div class="dior-standard-invoice__brand-caption">Healthcare Services</div>
                        </div>
                    </div>
                    <div class="dior-standard-invoice__heading">
                        <div class="dior-standard-invoice__eyebrow">PAYMENT DOCUMENT</div>
                        <h1>INVOICE</h1>
                        <div class="dior-standard-invoice__number" data-invoice-field="uid"><?php echo esc_html($first_invoice['uid']); ?></div>
                        <span class="dior-standard-invoice__status" data-invoice-field="status"><?php echo esc_html($first_invoice['status']); ?></span>
                    </div>
                </header>

                <div class="dior-standard-invoice__rule"></div>

                <section class="dior-standard-invoice__parties">
                    <div class="dior-standard-invoice__billto">
                        <div class="dior-standard-invoice__label">BILL TO</div>
                        <h2 data-invoice-field="patient"><?php echo esc_html($first_invoice['patient']); ?></h2>
                        <div class="dior-standard-invoice__muted" data-invoice-field="patient-email"><?php echo esc_html($first_invoice['patient_email'] ?: ''); ?></div>
                    </div>
                    <div class="dior-standard-invoice__details">
                        <div><span>Invoice date</span><strong data-invoice-field="date"><?php echo esc_html($first_invoice['date'] ? wp_date('M j, Y', strtotime($first_invoice['date'])) : '—'); ?></strong></div>
                        <div><span>Attending doctor</span><strong data-invoice-field="doctor"><?php echo esc_html($first_invoice['doctor']); ?></strong></div>
                        <div><span>Payment method</span><strong data-invoice-field="method"><?php echo esc_html($first_invoice['method']); ?></strong></div>
                    </div>
                </section>

                <section class="dior-standard-invoice__items">
                    <table class="dior-standard-invoice__table dior-no-global-pagination">
                        <thead>
                            <tr><th class="col-no">#</th><th>Description</th><th class="col-qty">Qty</th><th class="col-money">Unit price</th><th class="col-money">Tax / charges</th><th class="col-money">Discount</th><th class="col-total">Amount</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>01</td>
                                <td><strong data-invoice-field="description"><?php echo esc_html($first_invoice['description']); ?></strong><small>Medical service</small></td>
                                <td class="text-center">1</td>
                                <td class="text-end" data-invoice-field="unit">$<?php echo esc_html(number_format($first_invoice['amount'],2)); ?></td>
                                <td class="text-end" data-invoice-field="charges">$<?php echo esc_html(number_format($first_invoice['tax'],2)); ?></td>
                                <td class="text-end" data-invoice-field="discount">-$<?php echo esc_html(number_format($first_invoice['discount'],2)); ?></td>
                                <td class="text-end" data-invoice-field="total">$<?php echo esc_html(number_format($first_invoice['total'],2)); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="dior-standard-invoice__bottom">
                    <div class="dior-standard-invoice__payment">
                        <div class="dior-standard-invoice__label">PAYMENT DETAILS</div>
                        <div class="dior-standard-invoice__payment-row"><span>Payment method</span><strong data-invoice-field="method"><?php echo esc_html($first_invoice['method']); ?></strong></div>
                        <div class="dior-standard-invoice__payment-row"><span>Transaction reference</span><strong data-invoice-field="transaction"><?php echo esc_html($first_invoice['transaction'] ?: '—'); ?></strong></div>
                        <p>Thank you for choosing Dior Medical.</p>
                    </div>
                    <div class="dior-standard-invoice__totals">
                        <div><span>Subtotal</span><strong data-invoice-field="subtotal-total">$<?php echo esc_html(number_format($first_invoice['amount'],2)); ?></strong></div>
                        <div><span>Tax / charges</span><strong data-invoice-field="tax">$<?php echo esc_html(number_format($first_invoice['tax'],2)); ?></strong></div>
                        <div><span>Discount</span><strong data-invoice-field="discount-total">-$<?php echo esc_html(number_format($first_invoice['discount'],2)); ?></strong></div>
                        <div class="grand-total"><span>Total due</span><strong data-invoice-field="grand-total">$<?php echo esc_html(number_format($first_invoice['total'],2)); ?></strong></div>
                    </div>
                </section>

                <footer class="dior-standard-invoice__footer">
                    <span>Dior Medical · Healthcare Services</span>
                    <span>Computer-generated invoice</span>
                </footer>
            </div>
