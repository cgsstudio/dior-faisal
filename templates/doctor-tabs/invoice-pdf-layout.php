<?php
/**
 * Standalone table-based A4 invoice PDF template.
 * Dynamic values are populated from the selected invoice record in invoice.php.
 */
?>
<style id="dior-invoice-pdf-layout-styles">
.dior-pdf-layout{width:734px!important;min-width:734px!important;max-width:734px!important;margin:0 auto!important;padding:24px 26px!important;box-sizing:border-box!important;background:#fff!important;color:#17324f!important;font-family:Arial,Helvetica,sans-serif!important;font-size:12px!important;line-height:1.45!important}
.dior-pdf-layout table{width:100%!important;max-width:100%!important;border-collapse:collapse!important;table-layout:fixed!important;margin:0!important}
.dior-pdf-layout td,.dior-pdf-layout th{box-sizing:border-box!important;vertical-align:top!important}
.dior-pdf-layout .pdf-header td{padding:0 0 18px!important}
.dior-pdf-layout .pdf-logo{width:128px!important;height:90px!important;object-fit:contain!important;object-position:left center!important;display:block!important}
.dior-pdf-layout .pdf-brand{font-size:21px!important;font-weight:800!important;color:#063b70!important;padding-left:12px!important;vertical-align:middle!important}
.dior-pdf-layout .pdf-brand small{display:block!important;font-size:11px!important;font-weight:400!important;color:#718096!important;margin-top:5px!important}
.dior-pdf-layout .pdf-heading{text-align:right!important;font-size:10px!important;font-weight:700!important;letter-spacing:2px!important;color:#718096!important}
.dior-pdf-layout .pdf-heading strong{display:block!important;font-size:32px!important;letter-spacing:1px!important;color:#063b70!important;line-height:1.2!important;margin:5px 0!important}
.dior-pdf-layout .pdf-heading span{display:block!important;font-size:12px!important;letter-spacing:0!important;color:#334e68!important}
.dior-pdf-layout .pdf-status{display:inline-block!important;margin-top:8px!important;padding:5px 13px!important;border-radius:20px!important;background:#e8f7ef!important;border:1px solid #b9e8ce!important;color:#087443!important;font-size:10px!important;font-weight:800!important;letter-spacing:1px!important}
.dior-pdf-layout .pdf-rule td{height:3px!important;background:#0b5594!important;padding:0!important;font-size:0!important}
.dior-pdf-layout .pdf-parties td{padding:20px 0 24px!important}
.dior-pdf-layout .pdf-label{font-size:10px!important;font-weight:800!important;letter-spacing:1.4px!important;color:#718096!important}
.dior-pdf-layout .pdf-patient{font-size:19px!important;font-weight:700!important;color:#172b4d!important;padding:9px 0!important}
.dior-pdf-layout .pdf-muted{font-size:11px!important;color:#718096!important}
.dior-pdf-layout .pdf-meta{border-left:1px solid #e4ebf2!important;padding-left:18px!important}
.dior-pdf-layout .pdf-meta td{padding:6px 0!important;border-bottom:1px solid #edf1f5!important;font-size:11px!important}
.dior-pdf-layout .pdf-meta td:first-child{color:#718096!important;width:42%!important}
.dior-pdf-layout .pdf-meta td:last-child{text-align:right!important;font-weight:700!important;color:#243b53!important;overflow-wrap:anywhere!important}
.dior-pdf-layout .pdf-items th{background:#f1f5f9!important;color:#52677f!important;padding:11px 6px!important;text-align:left!important;font-size:9px!important;font-weight:800!important;text-transform:uppercase!important;border:1px solid #dce5ef!important}
.dior-pdf-layout .pdf-items td{padding:14px 6px!important;border:1px solid #e2e8f0!important;font-size:10px!important;color:#334e68!important;overflow-wrap:anywhere!important}
.dior-pdf-layout .pdf-items .right{text-align:right!important}
.dior-pdf-layout .pdf-items .center{text-align:center!important}
.dior-pdf-layout .pdf-items .description{font-weight:700!important;color:#243b53!important}
.dior-pdf-layout .pdf-bottom td{padding-top:24px!important;vertical-align:top!important}
.dior-pdf-layout .pdf-payment{background:#f7f9fc!important;border:1px solid #e4ebf2!important;padding:16px!important}
.dior-pdf-layout .pdf-payment td{padding:7px 0!important;border-bottom:1px solid #e7edf3!important;font-size:10px!important}
.dior-pdf-layout .pdf-payment td:first-child{color:#718096!important;width:48%!important}
.dior-pdf-layout .pdf-payment td:last-child{text-align:right!important;font-weight:700!important;overflow-wrap:anywhere!important}
.dior-pdf-layout .pdf-thanks{padding-top:12px!important;color:#0b5594!important;font-size:10px!important;font-weight:700!important}
.dior-pdf-layout .pdf-totals td{padding:9px 0!important;border-bottom:1px solid #e8eef4!important;font-size:11px!important}
.dior-pdf-layout .pdf-totals td:last-child{text-align:right!important;font-weight:700!important;color:#243b53!important}
.dior-pdf-layout .pdf-grand td{background:#063b70!important;color:#fff!important;padding:14px 12px!important;border:0!important;font-size:13px!important;font-weight:800!important}
.dior-pdf-layout .pdf-grand td:last-child{font-size:16px!important;text-align:right!important}
.dior-pdf-layout .pdf-footer td{padding-top:24px!important;border-top:1px solid #e5edf5!important;color:#91a0b1!important;font-size:9px!important}
.dior-pdf-layout .pdf-footer td:last-child{text-align:right!important}
</style>
<template id="dior-invoice-pdf-layout">
  <div class="dior-pdf-layout">
    <table class="pdf-header"><tr>
      <td style="width:145px"><img class="pdf-logo" src="https://diormedical.com/wp-content/uploads/2026/08/logo.png" alt="Dior Medical"></td>
      <td class="pdf-brand">Dior Medical<small>Healthcare Services</small></td>
      <td class="pdf-heading" style="width:190px">PAYMENT DOCUMENT<strong>INVOICE</strong><span data-pdf-field="uid"></span><span class="pdf-status" data-pdf-field="status"></span></td>
    </tr></table>
    <table class="pdf-rule"><tr><td></td></tr></table>
    <table class="pdf-parties"><tr>
      <td style="width:50%">
        <div class="pdf-label">BILL TO</div>
        <div class="pdf-patient" data-pdf-field="patient"></div>
        <div class="pdf-muted" data-pdf-field="patient_email"></div>
      </td>
      <td style="width:50%">
        <table class="pdf-meta">
          <tr><td>Invoice date</td><td data-pdf-field="date"></td></tr>
          <tr><td>Attending doctor</td><td data-pdf-field="doctor"></td></tr>
          <tr><td>Payment method</td><td data-pdf-field="method"></td></tr>
        </table>
      </td>
    </tr></table>
    <table class="pdf-items">
      <colgroup><col style="width:5%"><col style="width:39%"><col style="width:7%"><col style="width:13%"><col style="width:13%"><col style="width:11%"><col style="width:12%"></colgroup>
      <thead><tr><th>#</th><th>Description</th><th class="center">Qty</th><th class="right">Unit price</th><th class="right">Tax / charges</th><th class="right">Discount</th><th class="right">Amount</th></tr></thead>
      <tbody><tr>
        <td>01</td><td class="description" data-pdf-field="description"></td><td class="center">1</td>
        <td class="right" data-pdf-field="amount"></td><td class="right" data-pdf-field="tax"></td>
        <td class="right" data-pdf-field="discount"></td><td class="right" data-pdf-field="total"></td>
      </tr></tbody>
    </table>
    <table class="pdf-bottom"><tr>
      <td style="width:54%;padding-right:20px">
        <table class="pdf-payment">
          <tr><td colspan="2" style="font-weight:800;letter-spacing:1px;color:#52677f">PAYMENT DETAILS</td></tr>
          <tr><td>Payment method</td><td data-pdf-field="method"></td></tr>
          <tr><td>Transaction reference</td><td data-pdf-field="transaction"></td></tr>
          <tr><td colspan="2" class="pdf-thanks">Thank you for choosing Dior Medical.</td></tr>
        </table>
      </td>
      <td style="width:46%">
        <table class="pdf-totals">
          <tr><td>Subtotal</td><td data-pdf-field="subtotal"></td></tr>
          <tr><td>Tax / charges</td><td data-pdf-field="tax"></td></tr>
          <tr><td>Discount</td><td data-pdf-field="discount"></td></tr>
          <tr class="pdf-grand"><td>Total due</td><td data-pdf-field="total"></td></tr>
        </table>
      </td>
    </tr></table>
    <table class="pdf-footer"><tr><td>Dior Medical · Healthcare Services</td><td>Computer-generated invoice</td></tr></table>
  </div>
</template>
