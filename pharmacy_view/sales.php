<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Sales History';
include 'includes/ph_head.php';
?>
<style>
/* Compress table to prevent horizontal scrolling */
.ph-table th, .ph-table td {
    padding: 0.5rem 0.5rem !important;
    font-size: 0.78rem !important;
}
.ph-table th {
    font-size: 0.65rem !important;
}
.ph-table-wrap {
    overflow-x: hidden !important; /* Hide scrollbar if it just barely overshoots */
}
.actions-cell {
    white-space: nowrap;
    width: 80px;
}
</style>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">Sales History</h1>
    <p class="ph-page-subtitle">All invoices and sales records</p>
  </div>
  <a href="billing_pos.php" class="ph-btn ph-btn-primary"><i class="fas fa-cash-register"></i> New Sale</a>
</div>

<!-- Filters -->
<div class="ph-searchbar">
  <div class="ph-search-input-wrap"><i class="fas fa-search"></i>
    <input type="text" id="searchInput" placeholder="Search invoice no, customer name...">
  </div>
  <input type="date" class="ph-input" id="dateFrom" style="width:auto;" value="<?= date('Y-m-01') ?>">
  <input type="date" class="ph-input" id="dateTo"   style="width:auto;" value="<?= date('Y-m-d') ?>">
  <select class="ph-select" id="pharmacistFilter" style="width:140px; padding:.55rem;">
    <option value="">All Pharmacists</option>
  </select>
  <select class="ph-select" id="payFilter" style="width:140px; padding:.55rem;">
    <option value="">All Payments</option>
    <option value="cash">Cash</option>
    <option value="upi">UPI</option>
    <option value="offered_plan">Offered Plan</option>
    <option value="card">Card</option>
    <option value="dd">DD</option>
    <option value="credit">Credit (Sponsor)</option>
  </select>
  <button class="ph-btn ph-btn-primary" onclick="load()"><i class="fas fa-filter"></i> Filter</button>
</div>

<!-- Summary Stats -->
<div class="ph-stat-grid mb-4" id="statRow"></div>

<!-- Sales Table -->
<div class="ph-card">
  <div class="ph-table-wrap">
    <table class="ph-table">
      <thead>
        <tr>
          <th>Invoice No</th>
          <th>Date & Time</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Subtotal</th>
          <th>Discount</th>
          <th>Tax</th>
          <th>Grand Total</th>
          <th>Payment</th>
          <th>Pharmacist</th>
          <th>Status</th>
          <th class="text-end actions-cell">Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <tr><td colspan="11" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="ph-card-body pt-0 pb-3">
    <div id="pager" class="ph-pagination justify-content-end"></div>
  </div>
</div>

</div></div></div>

<!-- Sale Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailTitle">Invoice Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailBody">Loading...</div>
      <div class="modal-footer">
        <button class="ph-btn ph-btn-outline" data-bs-dismiss="modal">Close</button>
        <button class="ph-btn ph-btn-primary" onclick="reprintInvoice()"><i class="fas fa-print"></i> Reprint</button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>
<script>
let all = [], currentPage = 1, PER_PAGE = 15, currentSaleId = null;
const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

document.addEventListener('DOMContentLoaded', () => {
    try {
        if (typeof phGet !== 'function') {
            console.error('phGet not defined');
            return;
        }
        load();
        const s = document.getElementById('searchInput');
        const p = document.getElementById('payFilter');
        const pf = document.getElementById('pharmacistFilter');
        if (s) s.addEventListener('input', () => { currentPage = 1; render(); });
        if (p) p.addEventListener('change', () => { currentPage = 1; render(); });
        if (pf) pf.addEventListener('change', () => { currentPage = 1; load(); });
    } catch (e) { console.error('Init error:', e); }
});

async function load() {
    try {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo   = document.getElementById('dateTo').value;
        const pharmacist = document.getElementById('pharmacistFilter').value;
        const res = await phGet(API_BASE + `pharmacy/sales?date_from=${dateFrom}&date_to=${dateTo}&pharmacist=${pharmacist}`);
        if (res.success) { 
            all = res.data.data || []; 
            
            
            // Populate Pharmacist Dropdown dynamically
            if (res.data.pharmacists) {
                const pSelect = document.getElementById('pharmacistFilter');
                const currVal = pSelect.value;
                pSelect.innerHTML = '<option value="">All Pharmacists</option>' + 
                    res.data.pharmacists.map(p => `<option value="${p.created_by}" ${p.created_by===currVal?'selected':''}>${p.created_by}</option>`).join('');
            }
            
            render(); 
        } else {
            PH.error(res.message || res.error || "Failed to load sales");
        }
    } catch (e) {
        console.error('Load error:', e);
        PH.error("Network or server error: " + e.message);
    }
}

function renderStats(s) {
    const data = [
        { label:'Total Sales', val: fmt.currency(s.total_sales), icon:'fa-rupee-sign', color:'#0FA4AF' },
        { label:'Total Bills',  val: fmt.number(s.total_bills),  icon:'fa-receipt',    color:'#22C55E' },
        { label:'Total Tax',    val: fmt.currency(s.total_tax),  icon:'fa-percent',    color:'#F59E0B' },
        { label:'Total Discount',val:fmt.currency(s.total_disc), icon:'fa-tag',        color:'#8B5CF6' },
    ];
    document.getElementById('statRow').innerHTML = data.map(d => `
        <div class="ph-stat" style="border-left:4px solid ${d.color};">
            <div class="ph-stat-icon" style="background:${d.color}20;color:${d.color};"><i class="fas ${d.icon}"></i></div>
            <div class="ph-stat-val">${d.val}</div>
            <div class="ph-stat-lbl">${d.label}</div>
        </div>`).join('');
}

function render() {
    const q  = document.getElementById('searchInput').value.toLowerCase();
    const pf = document.getElementById('payFilter').value;
    let filtered = all;
    if (q)  filtered = filtered.filter(x => (x.invoice_no||'').toLowerCase().includes(q) || (x.customer_name||'').toLowerCase().includes(q));
    if (pf) filtered = filtered.filter(x => x.payment_method === pf);
    
    const dStats = {
        total_bills: filtered.length,
        total_sales: filtered.reduce((acc, cur) => acc + parseFloat(cur.grand_total || 0), 0),
        total_tax: filtered.reduce((acc, cur) => acc + parseFloat(cur.tax_total || 0), 0),
        total_disc: filtered.reduce((acc, cur) => acc + parseFloat(cur.discount_amount || 0), 0),
    };
    renderStats(dStats);
    
    const pager = phPaginate(filtered, currentPage, PER_PAGE);
    let html = '';
    if (!pager.items.length) { html = `<tr><td colspan="11" class="text-center py-4 text-muted">No sales records found for the selected period.</td></tr>`; }
    else pager.items.forEach(x => {
        const payIcon = {cash:'💵', card:'💳', upi:'📱', credit:'📋', offered_plan:'📋', dd:'🏦'}[x.payment_method] || '';
        html += `<tr>
            <td><span class="ph-badge badge-primary">${x.invoice_no}</span></td>
            <td><div>${fmt.date(x.invoice_date)}</div><div class="fs-xs text-muted">${x.invoice_time||''}</div></td>
            <td>${x.customer_name||'Walk-in'}</td>
            <td class="fw-bold text-center">${x.item_count||0}</td>
            <td>${fmt.currency(x.subtotal)}</td>
            <td class="text-danger">-${fmt.currency(x.discount_amount)}</td>
            <td>${fmt.currency(x.tax_total)}</td>
            <td class="fw-bold text-primary">${fmt.currency(x.grand_total)}</td>
            <td>${payIcon} ${x.payment_method}</td>
            <td>${x.created_by || ''}</td>
            <td>${statusBadge(x.status)}</td>
            <td class="text-end actions-cell">
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon me-1" onclick="viewSale(${x.id})" title="View"><i class="fas fa-eye"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon" onclick="printSale(${x.id})" title="Print Invoice"><i class="fas fa-print"></i></button>
            </td></tr>`;
    });
    document.getElementById('tableBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; render(); });
}

async function viewSale(id) {
    currentSaleId = id;
    document.getElementById('detailBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    detailModal.show();
    const res = await phGet(API_BASE + 'pharmacy/sales/' + id);
    if (!res.success) { document.getElementById('detailBody').innerHTML = '<p class="text-danger">Error loading details</p>'; return; }
    const s = res.data.sale, items = res.data.items;
    document.getElementById('detailTitle').textContent = 'Invoice — ' + s.invoice_no;
    let itemsHtml = items.map(i => `<tr>
        <td>${i.product_name}</td><td>${i.batch_no||'—'}</td><td>${i.qty}</td>
        <td>${fmt.currency(i.rate)}</td><td>${i.discount_percent}%</td><td>${i.tax_percent}%</td><td>${fmt.currency(i.subtotal)}</td></tr>`).join('');
    document.getElementById('detailBody').innerHTML = `
        <div class="row g-2 mb-3">
            <div class="col-md-4"><div class="ph-label">Invoice No</div><strong>${s.invoice_no}</strong></div>
            <div class="col-md-4"><div class="ph-label">Date</div>${fmt.date(s.invoice_date)}</div>
            <div class="col-md-4"><div class="ph-label">Customer</div>${s.customer_name}</div>
            <div class="col-md-4"><div class="ph-label">Phone</div>${s.customer_phone||'—'}</div>
            <div class="col-md-4"><div class="ph-label">Payment</div>${s.payment_method === 'split' ? '<span style="color:#4f46e5;font-weight:700;">SPLIT</span>' : s.payment_method}</div>
            <div class="col-md-4"><div class="ph-label">Status</div>${statusBadge(s.status)}</div>
        </div>
        <table class="ph-table"><thead><tr><th>Product</th><th>Batch</th><th>Qty</th><th>Rate</th><th>Disc%</th><th>Tax%</th><th>Subtotal</th></tr></thead><tbody>${itemsHtml}</tbody></table>
        <div class="row g-2 mt-3">
            <div class="col-md-6">
                ${s.payment_method === 'split' && s.split_payments ? `
                    <div class="mb-3 p-3" style="background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
                        <div style="font-weight:700; font-size:0.85rem; text-transform:uppercase; color:var(--ph-muted); margin-bottom:8px;">Split Breakdown</div>
                        ${s.split_payments.map(p => `
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; padding:4px 0; border-bottom:1px dashed #cbd5e1;">
                                <span style="text-transform:capitalize; font-weight:600; color:var(--ph-text);">${p.payment_method}</span>
                                <span style="font-weight:700;">${fmt.currency(p.amount)}</span>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
            <div class="col-md-6">
                <div class="cart-total-row"><span>Subtotal</span><strong>${fmt.currency(s.subtotal)}</strong></div>
                <div class="cart-total-row"><span>Discount</span><strong class="text-danger">-${fmt.currency(s.discount_amount)}</strong></div>
                <div class="cart-total-row"><span>Tax</span><strong>${fmt.currency(s.tax_total)}</strong></div>
                <div class="cart-total-row" style="font-size:1.1rem;"><span>Grand Total</span><strong class="text-primary">${fmt.currency(s.grand_total)}</strong></div>
            </div>
        </div>`;
}

async function printSale(id) {
    PH.loading('Generating invoice...');
    phGet(API_BASE + `pharmacy/sales/${id}/reprint`).then(res => {
        PH.close();
        if (res.success) printInvoice(res.data.html); else PH.error('Failed to generate invoice');
    }).catch(e => {
        PH.close();
        PH.error('Network error');
    });
}

async function reprintInvoice() {
    if (!currentSaleId) return;
    printSale(currentSaleId);
}

function printInvoice(html) {
    const w = window.open('', '_blank', 'width=900,height=800');
    if (!w) {
        PH.error('Popup blocked! Please allow popups for this site.');
        return;
    }
    w.document.write(html);
    w.document.close();
}
</script>
