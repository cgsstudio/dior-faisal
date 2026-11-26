<?php
global $wpdb;
$expense_table = $wpdb->prefix . 'dior_doctor_expenses';
$expense_doctor_ids = class_exists('Dior_Doctor_Resolver') ? Dior_Doctor_Resolver::get_accessible_doctor_ids(get_current_user_id()) : [get_current_user_id()];
$expense_doctor_ids = array_values(array_unique(array_filter(array_map('absint', (array) $expense_doctor_ids))));
$expense_rows = [];
if ($expense_doctor_ids && $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $expense_table)) === $expense_table) {
    $expense_in = implode(',', array_fill(0, count($expense_doctor_ids), '%d'));
    $expense_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $expense_table WHERE doctor_id IN ($expense_in) ORDER BY expense_date DESC, id DESC LIMIT 500", ...$expense_doctor_ids), ARRAY_A);
}
?>
<section class="dior-tab-panel" id="tab-doc-accounts-expenses" style="display: none;">
    <!-- Breadcrumb Header -->
    <div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; padding: 0 5px;">
        <div>
            <h4 class="mb-0 text-dark" style="font-size: 20px; font-weight: 700;">Expenses</h4>
        </div>
        <div>
            <ul class="va-breadcrumb-list">
                <li><a href="#"><i class="fas fa-house"></i></a></li>
                <li>/</li>
                <li><a href="#">Accounts</a></li>
                <li>/</li>
                <li class="active"><span>Expenses</span></li>
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
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-accounts-expenses" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-expenses');">
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
                <h2><i class="fas fa-arrow-trend-down text-primary me-2"></i>Expenses</h2>
                <div class="va-title-line"></div>
            </div>

            <div class="va-actions-box">
                <div class="input-group input-group-sm dior-bs-search-group" style="width:240px;"><input id="dior-expense-search" type="text" class="va-search-input form-control" placeholder="Search records..."></div>
                <button type="button" id="dior-expense-add-toggle" class="va-action-btn va-btn-primary btn" aria-label="Add expense"><i class="fas fa-plus"></i></button>
                <button type="button" id="dior-expense-export" class="va-action-btn va-btn-success btn" aria-label="Export expenses"><i class="fas fa-file-arrow-down"></i></button>
                <button type="button" class="va-action-btn va-btn-info btn" aria-label="Refresh expenses" onclick="window.location.reload()"><i class="fas fa-rotate-right"></i></button>
            </div>
        </div>

        <div id="dior-expense-form-wrap" class="p-3 border-bottom" hidden>
            <form id="dior-expense-form" class="row g-3">
                <div class="col-md-4"><label class="form-label">Category *</label><input name="category" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Amount *</label><input name="amount" type="number" min="0.01" step="0.01" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Date *</label><input name="expense_date" type="date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Vendor</label><input name="vendor" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Payment Method</label><input name="payment_method" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Department</label><input name="department" class="form-control"></div>
                <div class="col-md-8"><label class="form-label">Description</label><input name="description" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Payment Status</label><select name="payment_status" class="form-select"><option>Paid</option><option>Pending</option></select></div>
                <div class="col-12"><button class="btn va-btn-primary" type="submit">Save Expense</button><button class="btn btn-light ms-2" type="button" id="dior-expense-cancel">Cancel</button><span id="dior-expense-message" class="ms-3" role="status"></span></div>
            </form>
        </div>
        <div class="va-table-wrapper">
            <table class="va-table table table-hover">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;"><input type="checkbox"
                                class="va-checkbox form-check-input"></th>
                        <th>EXPENSE ID <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>CATEGORY <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>DESCRIPTION <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>AMOUNT <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>DATE <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>VENDOR <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>PAYMENT METHOD <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>DEPARTMENT <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>APPROVAL <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>PAYMENT <i class="fas fa-sort" style="color: #ccc; margin-left:5px;"></i></th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="dior-expense-rows">
                    <?php if (empty($expense_rows)): ?>
                    <tr><td colspan="12" class="dior-empty-state text-center text-muted py-5"><i class="fas fa-arrow-trend-down fs-3 d-block mb-2"></i><span class="small fw-semibold">No expense records found.</span></td></tr>
                    <?php else: foreach ($expense_rows as $expense): ?>
                    <tr data-expense-row>
                        <td class="text-center"><input type="checkbox" class="va-checkbox form-check-input" value="<?php echo esc_attr($expense['id']); ?>"></td>
                        <td><?php echo esc_html($expense['expense_uid']); ?></td>
                        <td><?php echo esc_html($expense['category']); ?></td>
                        <td><?php echo esc_html($expense['description']); ?></td>
                        <td><?php echo esc_html(number_format((float)$expense['amount'], 2)); ?></td>
                        <td><?php echo esc_html(wp_date('M j, Y', strtotime($expense['expense_date']))); ?></td>
                        <td><?php echo esc_html($expense['vendor']); ?></td>
                        <td><?php echo esc_html($expense['payment_method']); ?></td>
                        <td><?php echo esc_html($expense['department']); ?></td>
                        <td><?php echo esc_html($expense['approval_status']); ?></td>
                        <td><?php echo esc_html($expense['payment_status']); ?></td>
                        <td><button type="button" class="va-action-btn-sm va-btn-delete btn dior-expense-delete" data-id="<?php echo esc_attr($expense['id']); ?>" aria-label="Delete expense"><i class="fas fa-trash"></i></button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>        </tbody>
            </table>
        </div>

    </div>
</section>
<script>
(function(){
 const panel=document.getElementById('tab-doc-accounts-expenses');if(!panel||panel.dataset.accountsBound)return;panel.dataset.accountsBound='1';
 const ajax=window.diorDocAjax||window.dior_doctor_vars?.ajax_url||window.dior_vars?.ajax_url||'/wp-admin/admin-ajax.php';
 const nonce=window.diorDocNonce||window.dior_doctor_vars?.nonce||window.dior_vars?.nonce||'';
 const wrap=document.getElementById('dior-expense-form-wrap'),form=document.getElementById('dior-expense-form'),msg=document.getElementById('dior-expense-message');
 document.getElementById('dior-expense-add-toggle')?.addEventListener('click',()=>{wrap.hidden=!wrap.hidden;});
 document.getElementById('dior-expense-cancel')?.addEventListener('click',()=>{form.reset();wrap.hidden=true;});
 document.getElementById('dior-expense-search')?.addEventListener('input',e=>{const q=e.target.value.toLowerCase();panel.querySelectorAll('[data-expense-row]').forEach(row=>row.hidden=!row.textContent.toLowerCase().includes(q));});
 document.getElementById('dior-expense-export')?.addEventListener('click',()=>{const rows=[...panel.querySelectorAll('table tr')].filter(r=>!r.hidden);const csv=rows.map(r=>[...r.children].map(td=>'"'+td.innerText.replace(/"/g,'""').trim()+'"').join(',')).join('\n');const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([csv],{type:'text/csv'}));a.download='doctor-expenses.csv';a.click();URL.revokeObjectURL(a.href);});
 form?.addEventListener('submit',async e=>{e.preventDefault();msg.textContent='Saving…';const fd=new FormData(form);fd.append('action','dior_doc_expense_save');fd.append('nonce',nonce);try{const r=await fetch(ajax,{method:'POST',body:fd,credentials:'same-origin'});const j=await r.json();if(!j.success)throw new Error(j.data?.message||'Save failed');window.location.reload();}catch(err){msg.textContent=err.message;}});
 panel.addEventListener('click',async e=>{const btn=e.target.closest('.dior-expense-delete');if(!btn)return;if(!window.confirm('Delete this expense record?'))return;const fd=new FormData();fd.append('action','dior_doc_expense_delete');fd.append('nonce',nonce);fd.append('id',btn.dataset.id);try{const r=await fetch(ajax,{method:'POST',body:fd,credentials:'same-origin'});const j=await r.json();if(!j.success)throw new Error(j.data?.message||'Delete failed');btn.closest('tr').remove();}catch(err){window.alert(err.message);}});
})();
</script>

<style id="dior-accounts-mobile-expenses">
/* Accounts mobile responsive layout, aligned with Patient Records */

@media(max-width:767.98px){
#dior-doctor-app #dior-doc-content #tab-doc-accounts-expenses{width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;padding:14px 0 24px!important;overflow-x:clip;box-sizing:border-box!important}
#tab-doc-accounts-expenses>.mb-4:first-child{align-items:flex-start!important}
#tab-doc-accounts-expenses>.mb-4:first-child .va-breadcrumb-list{display:none!important}
#tab-doc-accounts-expenses .dior-subtab-nav-bar{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;padding:0!important;margin:0 0 16px!important}
#tab-doc-accounts-expenses .dior-subtab-pill{width:100%!important;min-width:0!important;min-height:40px!important;margin:0!important;padding:8px 5px!important;font-size:12px!important;white-space:normal!important}
#tab-doc-accounts-expenses .view-appointment-card{width:100%!important;max-width:100%!important;margin:0!important;padding:0!important;overflow:hidden!important;box-sizing:border-box!important}
#tab-doc-accounts-expenses .va-header-container{display:flex!important;flex-direction:column!important;align-items:stretch!important;gap:12px!important;padding:12px!important}
#tab-doc-accounts-expenses .va-header-actions{width:100%!important}
#tab-doc-accounts-expenses .va-actions-group{display:flex!important;gap:8px!important}
#tab-doc-accounts-expenses .va-actions-group .va-icon-btn{flex:1!important}
#tab-doc-accounts-expenses .va-table-wrapper{width:100%!important;max-width:100%!important;overflow:hidden!important;padding:0 10px 12px!important;box-sizing:border-box!important}
#tab-doc-accounts-expenses .va-table{display:block!important;width:100%!important;min-width:0!important;table-layout:fixed!important}
#tab-doc-accounts-expenses .va-table thead{display:none!important}
#tab-doc-accounts-expenses .va-table tbody{display:grid!important;gap:10px!important;width:100%!important}
#tab-doc-accounts-expenses .va-table tbody tr{position:relative!important;display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:10px 12px!important;width:100%!important;min-width:0!important;margin:0!important;padding:14px 12px 12px 42px!important;border:1px solid #E2E8F0!important;border-radius:14px!important;background:#fff!important;box-shadow:0 1px 3px rgba(15,23,42,.06)!important;box-sizing:border-box!important}
#tab-doc-accounts-expenses .va-table tbody td{display:flex!important;flex-direction:column!important;align-items:flex-start!important;gap:3px!important;min-width:0!important;padding:0!important;border:0!important;background:transparent!important;white-space:normal!important;overflow-wrap:anywhere!important;font-size:12px!important}
#tab-doc-accounts-expenses .va-table tbody td:before{font-size:9px!important;font-weight:700!important;letter-spacing:.04em!important;text-transform:uppercase!important;color:#8493A7!important}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(1){position:absolute!important;left:12px!important;top:15px!important;display:block!important}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(2):before{content:"Expense ID"}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(3):before{content:"Category"}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(4):before{content:"Description"}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(5):before{content:"Amount"}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(6):before{content:"Date"}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(7):before{content:"Vendor"}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(8):before{content:"Payment Method"}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(9):before{content:"Department"}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(10):before{content:"Approval"}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(11):before{content:"Payment"}
#tab-doc-accounts-expenses .va-table tbody td:nth-child(12){grid-column:1/-1;align-items:flex-end!important;padding-top:8px!important;border-top:1px solid #EDF1F6!important}
}

</style>
