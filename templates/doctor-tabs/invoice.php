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
    
    .dior-company-info img {
        height: 42px;
        margin-bottom: 16px;
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

    <!-- Invoice details are shown only when a real invoice record is selected.
         Do not display fabricated clinic, patient, or payment information. -->
    <div class="dior-invoice-card">
        <div class="text-center py-5 px-3" role="status">
            <i class="fas fa-file-invoice-dollar" aria-hidden="true" style="font-size:36px;color:#94a3b8;margin-bottom:16px;"></i>
            <h3 style="font-size:18px;font-weight:700;color:#1e293b;">No Invoice Selected</h3>
            <p style="max-width:520px;margin:8px auto 0;color:#64748b;line-height:1.6;">
                Select an actual invoice from the Bill List to view its details. Invoice information will appear here when a real record is available.
            </p>
            <button type="button" class="dior-btn dior-btn-primary btn mt-3"
                onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-bill-list');">
                <i class="fas fa-receipt me-1"></i> Go to Bill List
            </button>
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
document.addEventListener('DOMContentLoaded', function() {
    const downloadBtn = document.getElementById('download-invoice-pdf');
    if(downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            const invoiceElement = document.getElementById('invoice-pdf-content');
            
            // Generate PDF
            const opt = {
                margin:       [10, 10, 10, 10], // top, left, bottom, right
                filename:     'Invoice_345766.pdf',
                image:        { type: 'jpeg', quality: 1.0 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(invoiceElement).save();
        });
    }
});
</script>
