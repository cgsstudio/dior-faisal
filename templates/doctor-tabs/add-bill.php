<section class="dior-tab-panel" id="tab-doc-accounts-add-bill" style="display: none;">
    <!-- Breadcrumb Header -->
    <div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; padding: 0 5px;">
        <div>
            <h4 class="mb-0 text-dark" style="font-size: 20px; font-weight: 700;">Add Bill</h4>
        </div>
        <div>
            <ul class="va-breadcrumb-list">
                <li><a href="#"><i class="fas fa-house"></i></a></li>
                <li>/</li>
                <li><a href="#">Accounts</a></li>
                <li>/</li>
                <li class="active"><span>Add Bill</span></li>
            </ul>
        </div>
    </div>

    <!-- Subtab Navigation Bar -->
    <div class="dior-subtab-nav-bar px-1 mb-4">
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-bill-list" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-bill-list');">
            <i class="fas fa-receipt"></i> Bill List
        </button>
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-accounts-add-bill" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-add-bill');">
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
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-invoice" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-invoice');">
            <i class="fas fa-file-invoice"></i> Invoice
        </button>
    </div>

    <div class="view-appointment-card">
        <div class="va-header-container" style="border-bottom: 0;">
            <div class="va-title-box">
                <h2><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Add Bill</h2>
            </div>
        </div>

        <div style="padding: 20px;">
            <?php
            $account_patients = class_exists('Dior_Doctor_Dynamic') ? Dior_Doctor_Dynamic::patients(get_current_user_id()) : [];
            ?>
            <form id="dior-doctor-add-bill-form">
                <div class="row">
                    <div class="col-xl-6 col-lg-6 col-md-12 mb-3">
                        <label class="form-label fw-semibold">Patient *</label>
                        <select name="patient_id" class="va-form-control form-select" required>
                            <option value="">Select patient</option>
                            <?php foreach ($account_patients as $account_patient):
                                $account_pid = absint($account_patient['user_id'] ?? 0);
                                if (!$account_pid || !Dior_Doctor_Dynamic::patient_belongs_to_doctor($account_pid, get_current_user_id())) continue;
                                $account_name = trim(($account_patient['first_name'] ?? '') . ' ' . ($account_patient['last_name'] ?? ''));
                            ?>
                            <option value="<?php echo esc_attr($account_pid); ?>"><?php echo esc_html($account_name ?: ($account_patient['email'] ?? 'Patient')); ?> — <?php echo esc_html($account_patient['patient_uid'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-12 mb-3">
                        <label class="form-label fw-semibold">Service *</label>
                        <input name="service_type" type="text" class="va-form-control form-control" placeholder="Consultation / Service" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-3"><label class="form-label fw-semibold">Bill Amount *</label><input name="amount" type="number" min="0.01" step="0.01" class="va-form-control form-control" required></div>
                    <div class="col-xl-3 col-md-6 mb-3"><label class="form-label fw-semibold">Tax</label><input name="tax" type="number" min="0" step="0.01" value="0" class="va-form-control form-control"></div>
                    <div class="col-xl-3 col-md-6 mb-3"><label class="form-label fw-semibold">Discount</label><input name="discount" type="number" min="0" step="0.01" value="0" class="va-form-control form-control"></div>
                    <div class="col-xl-3 col-md-6 mb-3"><label class="form-label fw-semibold">Due Date</label><input name="due_date" type="date" class="va-form-control form-control"></div>
                </div>
                <div class="row">
                    <div class="col-xl-4 col-md-6 mb-3"><label class="form-label fw-semibold">Payment Method *</label><select name="payment_method" class="va-form-control form-select" required><option value="">Select method</option><option>Cash</option><option>Card</option><option>Insurance</option><option>Online</option><option>Stripe</option><option>Other</option></select></div>
                    <div class="col-xl-4 col-md-6 mb-3"><label class="form-label fw-semibold">Status *</label><select name="status" class="va-form-control form-select"><option>Pending</option><option>Paid</option><option>Overdue</option></select></div>
                    <div class="col-xl-4 col-md-12 mb-3"><label class="form-label fw-semibold">Total Amount</label><input id="dior-bill-total-preview" type="text" class="va-form-control form-control" readonly value="0.00"></div>
                </div>
                <div class="row"><div class="col-12 mb-4"><label class="form-label fw-semibold">Notes</label><textarea name="notes" rows="3" class="va-form-control form-control" placeholder="Additional notes"></textarea></div></div>
                <div class="row"><div class="col-12 d-flex flex-wrap gap-2"><button type="submit" class="va-btn-primary btn">Save Bill</button><button type="reset" class="btn btn-light">Clear Form</button></div></div>
                <p id="dior-bill-form-message" class="mt-3 mb-0" role="status" aria-live="polite"></p>
            </form>
            <script>
            (function(){
                const form=document.getElementById('dior-doctor-add-bill-form'); if(!form||form.dataset.bound)return; form.dataset.bound='1';
                const amount=form.elements.amount,tax=form.elements.tax,discount=form.elements.discount,total=document.getElementById('dior-bill-total-preview'),msg=document.getElementById('dior-bill-form-message');
                const calc=()=>{total.value=Math.max(0,(Number(amount.value)||0)+(Number(tax.value)||0)-(Number(discount.value)||0)).toFixed(2);};
                [amount,tax,discount].forEach(el=>el.addEventListener('input',calc));
                form.addEventListener('submit',async e=>{e.preventDefault();msg.textContent='Saving…';const fd=new FormData(form);fd.append('action','dior_doc_bill_save');fd.append('nonce',window.diorDocNonce||window.dior_doctor_vars?.nonce||window.dior_vars?.nonce||'');try{const res=await fetch(window.diorDocAjax||window.dior_doctor_vars?.ajax_url||window.dior_vars?.ajax_url||'/wp-admin/admin-ajax.php',{method:'POST',body:fd,credentials:'same-origin'});const json=await res.json();if(!json.success)throw new Error(json.data?.message||'Bill could not be saved.');msg.textContent=json.data.message+' Total: '+Number(json.data.total).toFixed(2);form.reset();calc();}catch(err){msg.textContent=err.message;}});
            })();
            </script>     </form>
        </div>
    </div>
</section>
<style id="dior-accounts-mobile-add-bill">
/* Accounts mobile responsive layout, aligned with Patient Records */

@media(max-width:767.98px){
#dior-doctor-app #dior-doc-content #tab-doc-accounts-add-bill{width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;padding:14px 0 24px!important;overflow-x:clip;box-sizing:border-box!important}
#tab-doc-accounts-add-bill>.mb-4:first-child{align-items:flex-start!important}
#tab-doc-accounts-add-bill>.mb-4:first-child .va-breadcrumb-list{display:none!important}
#tab-doc-accounts-add-bill .dior-subtab-nav-bar{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;padding:0!important;margin:0 0 16px!important}
#tab-doc-accounts-add-bill .dior-subtab-pill{width:100%!important;min-width:0!important;min-height:40px!important;margin:0!important;padding:8px 5px!important;font-size:12px!important;white-space:normal!important}
#tab-doc-accounts-add-bill .view-appointment-card{width:100%!important;max-width:100%!important;margin:0!important;padding:0!important;overflow:hidden!important;box-sizing:border-box!important}
#tab-doc-accounts-add-bill .va-header-container{padding:12px!important}
#tab-doc-accounts-add-bill .view-appointment-card>div[style*="padding: 20px"]{padding:12px!important}
#tab-doc-accounts-add-bill form .row{margin-left:0!important;margin-right:0!important}
#tab-doc-accounts-add-bill form .row>[class*="col-"]{padding-left:0!important;padding-right:0!important;width:100%!important;max-width:100%!important;flex:0 0 100%!important}
#tab-doc-accounts-add-bill .va-form-control{width:100%!important;max-width:100%!important;min-width:0!important;min-height:42px!important;box-sizing:border-box!important}
#tab-doc-accounts-add-bill textarea.va-form-control{min-height:90px!important}
#tab-doc-accounts-add-bill form .row:last-child .col-12{flex-wrap:wrap!important}
#tab-doc-accounts-add-bill form .row:last-child button{flex:1 1 120px!important;min-height:42px!important}
}

</style>

