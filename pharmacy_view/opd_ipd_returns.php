<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Patient Returns (OPD/IPD)';
$db = getDB();
$products = $db->query("SELECT product_id, product_name FROM ph_product ORDER BY product_name")->fetchAll();
include 'includes/ph_head.php';
?>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">Patient Returns</h1>
    <p class="ph-page-subtitle">Handle medicine returns for OPD and IPD Patients</p>
  </div>
  <button class="ph-btn ph-btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> New Return</button>
</div>

<!-- Search & Filter -->
<div class="ph-searchbar">
  <div class="ph-search-input-wrap"><i class="fas fa-search"></i>
    <input type="text" id="searchInput" placeholder="Search return no, patient id, receipt no...">
  </div>
  <select class="ph-select" id="typeFilter" style="width:160px; padding:.55rem;">
    <option value="">All Types</option>
    <option value="OPD">OPD Patient</option>
    <option value="IPD">IPD Patient</option>
  </select>
  <button class="ph-btn ph-btn-outline" onclick="load()"><i class="fas fa-sync-alt"></i></button>
</div>

<!-- Returns Table -->
<div class="ph-card">
  <div class="ph-table-wrap">
    <table class="ph-table">
      <thead>
        <tr>
          <th>Return No</th>
          <th>Date</th>
          <th>Type</th>
          <th>Patient ID</th>
          <th>Receipt No</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Amount</th>
          <th>Attachments</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <tr><td colspan="10" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="ph-card-body pt-0 pb-3">
    <div id="pager" class="ph-pagination justify-content-end"></div>
  </div>
</div>

</div></div></div>

<!-- Modal -->
<div class="modal fade" id="mainModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">New Patient Return</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="mainForm" onsubmit="save(event)">
        <div class="modal-body">
          <input type="hidden" name="id" id="id">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="ph-label">Patient Type <span class="req">*</span></label>
              <select class="ph-select" name="patient_type" id="patient_type" required>
                <option value="">-- Select --</option>
                <option value="OPD">OPD</option>
                <option value="IPD">IPD</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="ph-label">Patient ID</label>
              <input type="text" class="ph-input" name="patient_id" id="patient_id" placeholder="e.g. PAT-1234">
            </div>
            <div class="col-md-3">
              <label class="ph-label">Patient Name</label>
              <input type="text" class="ph-input" name="patient_name" id="patient_name" placeholder="John Doe">
            </div>
            <div class="col-md-3">
              <label class="ph-label">Receipt No</label>
              <div class="input-group">
                <input type="text" class="ph-input form-control" name="receipt_no" id="receipt_no" placeholder="e.g. REC-5678">
                <button type="button" class="btn btn-primary" onclick="fetchReceiptData()"><i class="fas fa-search"></i> Fetch</button>
              </div>
            </div>

            <!-- BULK UI MODE (Shown on New Return) -->
            <div id="bulkItemMode" style="display:none; width: 100%;">
                <div class="col-12" id="receiptInfoContainer" style="display:none; margin-top:15px;">
                    <div class="p-3 mb-3 rounded" style="background:#f8f9fa; border:1px solid #dee2e6;">
                        <h6 class="mb-3 text-primary"><i class="fas fa-file-invoice-dollar"></i> Receipt Summary</h6>
                        <div class="row text-sm mb-2">
                            <div class="col-12"><strong>Patient Name:</strong> <span id="rs_patient_name" class="fw-bold"></span></div>
                        </div>
                        <div class="row text-sm">
                            <div class="col-md-3"><strong>Subtotal:</strong> <br><span id="rs_subtotal">₹0.00</span></div>
                            <div class="col-md-3"><strong>Discount:</strong> <br><span id="rs_discount" class="text-success">₹0.00</span></div>
                            <div class="col-md-3"><strong>Tax:</strong> <br><span id="rs_tax">₹0.00</span></div>
                            <div class="col-md-3"><strong>Grand Total:</strong> <br><span id="rs_total" class="fw-bold">₹0.00</span><br><small id="rs_method" class="text-muted"></small></div>
                        </div>
                    </div>
                    
                    <h6 class="mb-2">Select Items to Return</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;"><input type="checkbox" id="checkAllItems" onclick="toggleAllItems(this)"></th>
                                    <th>Product</th>
                                    <th>Batch</th>
                                    <th>Rate</th>
                                    <th>Purchased</th>
                                    <th style="width:100px;">Return Qty</th>
                                    <th>Refund</th>
                                </tr>
                            </thead>
                            <tbody id="receiptItemsBody">
                                <tr><td colspan="7" class="text-center text-muted">Fetch a receipt to load items.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mb-3">
                        <h5 class="m-0">Total Refund: <span id="bulkTotalDisplay" class="text-danger fw-bold">₹0.00</span></h5>
                    </div>
                </div>
            </div>

            <!-- SINGLE UI MODE (Shown on Edit) -->
            <div id="singleItemMode" class="row g-3" style="display:none; margin-top:0;">
                <div class="col-md-8">
                  <label class="ph-label">Product <span class="req">*</span></label>
                  <select class="ph-select" name="product_id" id="product_id" onchange="fillProductName(this)">
                    <option value="">-- Select Product --</option>
                    <?php foreach($products as $p): ?>
                    <option value="<?= $p['product_id'] ?>" data-name="<?= htmlspecialchars($p['product_name']) ?>"><?= htmlspecialchars($p['product_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="hidden" name="product_name" id="product_name">
                </div>
                <div class="col-md-4">
                  <label class="ph-label">Batch No</label>
                  <input type="text" class="ph-input" name="batch_no" id="batch_no">
                </div>

                <div class="col-md-4">
                  <label class="ph-label">Return Quantity <span class="req">*</span></label>
                  <input type="number" class="ph-input" name="qty" id="qty" min="1" value="1" onchange="calcTotal()">
                </div>
                <div class="col-md-4">
                  <label class="ph-label">Rate (₹)</label>
                  <input type="number" class="ph-input" name="rate" id="rate" step="0.01" value="0" onchange="calcTotal()">
                </div>
                <div class="col-md-4">
                  <label class="ph-label">Total Amount (₹)</label>
                  <input type="text" class="ph-input" id="total_display" readonly style="background:#F8FAFC;font-weight:700;">
                  <input type="hidden" name="total_amount" id="total_amount">
                </div>
            </div>

            <div class="col-12">
              <label class="ph-label">Reason / Notes <span class="req">*</span></label>
              <textarea class="ph-textarea" name="reason" id="reason" rows="2" required placeholder="Describe the return reason..."></textarea>
            </div>

            <!-- Attachments -->
            <div class="col-md-6">
              <label class="ph-label"><i class="fas fa-image me-1"></i> Upload Image</label>
              <input type="file" class="ph-input" name="image" accept="image/*">
              <input type="hidden" name="existing_image" id="existing_image">
              <div id="current_image_link" class="small mt-1"></div>
            </div>
            <div class="col-md-6">
              <label class="ph-label"><i class="fas fa-file-pdf me-1"></i> Upload Document</label>
              <input type="file" class="ph-input" name="doc" accept=".pdf,.doc,.docx">
              <input type="hidden" name="existing_doc" id="existing_doc">
              <div id="current_doc_link" class="small mt-1"></div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ph-btn ph-btn-outline" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="ph-btn ph-btn-primary"><i class="fas fa-save"></i> Save Return</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>
<script>
let all = [], currentPage = 1, PER_PAGE = 10;
let originalProductHtml = '';
const modal = new bootstrap.Modal(document.getElementById('mainModal'));

document.addEventListener('DOMContentLoaded', () => {
    originalProductHtml = document.getElementById('product_id').innerHTML;
    load();
    document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; render(); });
    document.getElementById('typeFilter').addEventListener('change', () => { currentPage = 1; render(); });
});

async function load() {
    const res = await phGet(API_BASE + 'pharmacy/patient-returns');
    if (res.success) { 
        let grouped = {};
        res.data.forEach(x => {
            if (!grouped[x.return_no]) {
                grouped[x.return_no] = { ...x, _items: [] };
            }
            grouped[x.return_no]._items.push(x);
        });
        
        all = Object.values(grouped).map(g => {
            if (g._items.length > 1) {
                g.product_name = g._items.map(i => `<div class="mb-1">${i.product_name || '—'}</div>`).join('');
                g.qty = g._items.map(i => `<div class="mb-1">${i.qty}</div>`).join('');
                g.total_amount_sum = g._items.reduce((sum, i) => sum + parseFloat(i.total_amount || 0), 0);
                g.is_grouped = true;
            } else {
                g.product_name = `<div>${g.product_name || '—'}</div>`;
                g.qty = `<div>${g.qty}</div>`;
                g.total_amount_sum = parseFloat(g.total_amount || 0);
                g.is_grouped = false;
            }
            return g;
        });
        render(); 
    } else PH.error(res.message);
}

function render() {
    const q  = document.getElementById('searchInput').value.toLowerCase();
    const tf = document.getElementById('typeFilter').value;
    let filtered = all;
    if (q)  filtered = filtered.filter(x => (x.return_no||'').toLowerCase().includes(q) || (x.patient_id||'').toLowerCase().includes(q) || (x.receipt_no||'').toLowerCase().includes(q) || (x.product_name||'').toLowerCase().includes(q));
    if (tf) filtered = filtered.filter(x => x.patient_type === tf);
    
    const pager = phPaginate(filtered, currentPage, PER_PAGE);
    let html = '';
    if (!pager.items.length) html = `<tr><td colspan="10" class="text-center py-4 text-muted">No returns found.</td></tr>`;
    else pager.items.forEach(x => {
        const typeColors = { 'OPD':'badge-info', 'IPD':'badge-primary' };

        let attachmentHtml = '';
        if (x.image) attachmentHtml += `<a href="../../${x.image}" target="_blank" class="ph-btn ph-btn-sm ph-btn-outline" style="padding:0.2rem 0.4rem; font-size:0.75rem" title="View Image"><i class="fas fa-image text-primary"></i> Image</a> `;
        if (x.doc) attachmentHtml += `<a href="../../${x.doc}" target="_blank" class="ph-btn ph-btn-sm ph-btn-outline" style="padding:0.2rem 0.4rem; font-size:0.75rem" title="View Document"><i class="fas fa-file-pdf text-info"></i> Doc</a>`;

        html += `<tr>
            <td><span class="ph-badge badge-muted">${x.return_no}</span></td>
            <td>${fmt.date(x.return_date)}</td>
            <td><span class="ph-badge ${typeColors[x.patient_type]||'badge-muted'}">${x.patient_type}</span></td>
            <td>${x.patient_id||'—'}</td>
            <td>${x.receipt_no||'—'}</td>
            <td>${x.product_name||'—'}</td>
            <td class="fw-bold">${x.qty}</td>
            <td class="fw-bold text-danger">-${fmt.currency(x.total_amount_sum)}</td>
            <td>${attachmentHtml || '<span class="text-muted small">—</span>'}</td>
            <td class="text-end">
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon text-info me-1" onclick='printReturn(${JSON.stringify(x).replace(/'/g,"&apos;")})' title="Print"><i class="fas fa-print"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon me-1" onclick='edit(${JSON.stringify(x._items[0]).replace(/'/g,"&apos;")})' title="Edit" ${x.is_grouped ? 'disabled' : ''}><i class="fas fa-edit"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon text-danger" onclick="del('${x.return_no}')" title="Delete"><i class="fas fa-trash"></i></button>
            </td></tr>`;
    });
    document.getElementById('tableBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; render(); });
}

function openModal() {
    document.getElementById('mainForm').reset();
    document.getElementById('id').value = '';
    document.getElementById('modalTitle').textContent = 'New Patient Return';
    document.getElementById('product_id').innerHTML = originalProductHtml;
    
    // Reset bulk UI
    document.getElementById('bulkItemMode').style.display = 'block';
    document.getElementById('singleItemMode').style.display = 'none';
    document.getElementById('receiptInfoContainer').style.display = 'none';
    document.getElementById('receiptItemsBody').innerHTML = '<tr><td colspan="7" class="text-center text-muted">Fetch a receipt to load items.</td></tr>';
    document.getElementById('bulkTotalDisplay').textContent = '₹0.00';
    document.getElementById('checkAllItems').checked = false;
    
    document.getElementById('existing_image').value = '';
    document.getElementById('existing_doc').value = '';
    document.getElementById('current_image_link').innerHTML = '';
    document.getElementById('current_doc_link').innerHTML = '';

    calcTotal();
    modal.show();
}

function edit(x) {
    document.getElementById('id').value = x.id;
    document.getElementById('modalTitle').textContent = 'Edit Patient Return';
    
    document.getElementById('bulkItemMode').style.display = 'none';
    document.getElementById('singleItemMode').style.display = 'flex';
    
    ['patient_type','patient_id','patient_name','receipt_no','product_id','product_name','batch_no','qty','rate','total_amount','reason'].forEach(f => {
        const el = document.getElementById(f); if (el) el.value = x[f]||'';
    });
    
    document.querySelector('input[name="image"]').value = '';
    document.querySelector('input[name="doc"]').value = '';
    document.getElementById('existing_image').value = x.image || '';
    document.getElementById('existing_doc').value = x.doc || '';
    
    document.getElementById('current_image_link').innerHTML = x.image ? `<a href="../../${x.image}" target="_blank" class="text-primary"><i class="fas fa-link"></i> Current Image</a>` : '';
    document.getElementById('current_doc_link').innerHTML = x.doc ? `<a href="../../${x.doc}" target="_blank" class="text-info"><i class="fas fa-link"></i> Current Doc</a>` : '';

    calcTotal();
    modal.show();
}

async function save(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    
    // If in Bulk Mode, gather checked items
    if (document.getElementById('bulkItemMode').style.display === 'block') {
        const items = [];
        document.querySelectorAll('.return-item-row').forEach(row => {
            const cb = row.querySelector('.item-checkbox');
            if (cb && cb.checked) {
                const rQty = parseInt(row.querySelector('.item-return-qty').value) || 0;
                if (rQty > 0) {
                    items.push({
                        product_id: cb.value,
                        product_name: cb.getAttribute('data-name'),
                        batch_no: cb.getAttribute('data-batch'),
                        rate: parseFloat(cb.getAttribute('data-rate')),
                        return_qty: rQty
                    });
                }
            }
        });
        if (items.length === 0) {
            PH.error('Please select at least one item to return.');
            return;
        }
        fd.append('items', JSON.stringify(items));
    }
    
    PH.loading('Saving return...');
    try {
        const res = await fetch(API_BASE + 'pharmacy/patient-returns', {
            method: 'POST',
            body: fd
        }).then(r => r.json());
        if (res.success) { PH.success(res.message); modal.hide(); load(); } else PH.error(res.message);
    } catch(e) { PH.error('Failed to save return'); }
}

function del(id) {
    PH.confirm('Delete Return?', '', async () => {
        PH.loading('Deleting...');
        try {
            const res = await fetch(API_BASE + 'pharmacy/patient-returns/' + id, { method: 'DELETE' }).then(r => r.json());
            if (res.success) { PH.success('Deleted'); load(); } else PH.error(res.message);
        } catch(e) { PH.error('Failed to delete'); }
    });
}

function calcTotal() {
    const qtyInput = document.getElementById('qty');
    let qty = parseFloat(qtyInput.value) || 0;
    
    // Enforce max quantity if set by fetch
    if (qtyInput.hasAttribute('max')) {
        const max = parseFloat(qtyInput.getAttribute('max'));
        if (qty > max) {
            qty = max;
            qtyInput.value = max;
            PH.error('Cannot return more than purchased quantity (' + max + ')');
        }
    }
    
    const rate = parseFloat(document.getElementById('rate').value) || 0;
    const total = qty * rate;
    document.getElementById('total_amount').value = total.toFixed(2);
    document.getElementById('total_display').value = '₹' + total.toFixed(2);
}

function fillProductName(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('product_name').value = opt.getAttribute('data-name') || '';
    
    // Auto-fill rate, qty, and batch if available from receipt
    if (opt.hasAttribute('data-rate')) {
        document.getElementById('rate').value = opt.getAttribute('data-rate');
        document.getElementById('qty').value = 1;
        document.getElementById('qty').setAttribute('max', opt.getAttribute('data-qty'));
        document.getElementById('batch_no').value = opt.getAttribute('data-batch') || '';
        calcTotal();
    } else {
        document.getElementById('qty').removeAttribute('max');
    }
}

async function fetchReceiptData() {
    const receiptNo = document.getElementById('receipt_no').value.trim();
    if (!receiptNo) {
        PH.error('Please enter a Receipt No first.');
        return;
    }
    PH.loading('Fetching receipt...');
    try {
        const res = await fetch(API_BASE + 'pharmacy/patient-returns/receipt/' + receiptNo).then(r => r.json());
        if (res.success) {
            PH.success('Receipt loaded');
            document.getElementById('patient_id').value = res.data.patient_id || '';
            document.getElementById('patient_name').value = res.data.patient_name || '';
            
            // Show summary
            document.getElementById('receiptInfoContainer').style.display = 'block';
            document.getElementById('rs_patient_name').textContent = res.data.patient_name || 'N/A';
            document.getElementById('rs_subtotal').textContent = fmt.currency(res.data.payment.subtotal);
            document.getElementById('rs_discount').textContent = fmt.currency(res.data.payment.discount_amount);
            document.getElementById('rs_tax').textContent = fmt.currency(res.data.payment.tax_total);
            document.getElementById('rs_total').textContent = fmt.currency(res.data.payment.grand_total);
            document.getElementById('rs_method').textContent = 'Paid via: ' + res.data.payment.payment_method;
            
            // Build items table
            const tbody = document.getElementById('receiptItemsBody');
            tbody.innerHTML = '';
            document.getElementById('bulkTotalDisplay').textContent = '₹0.00';
            document.getElementById('checkAllItems').checked = false;
            
            if (!res.data.items || res.data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No items found on this receipt.</td></tr>';
                return;
            }
            
            res.data.items.forEach((item, i) => {
                const tr = document.createElement('tr');
                tr.className = 'return-item-row';
                tr.innerHTML = `
                    <td><input type="checkbox" class="item-checkbox form-check-input" value="${item.product_id}" data-name="${item.product_name}" data-batch="${item.batch_no||''}" data-rate="${item.rate}" onchange="calcBulkTotal()"></td>
                    <td>${item.product_name}</td>
                    <td>${item.batch_no || '-'}</td>
                    <td>${fmt.currency(item.rate)}</td>
                    <td>${item.qty}</td>
                    <td><input type="number" class="form-control form-control-sm item-return-qty" value="1" min="1" max="${item.qty}" onchange="validateBulkRow(this, ${item.qty}); calcBulkTotal()"></td>
                    <td class="item-row-total text-danger fw-bold">₹0.00</td>
                `;
                tbody.appendChild(tr);
            });
            
        } else {
            PH.error(res.message || 'Receipt not found');
            document.getElementById('receiptInfoContainer').style.display = 'none';
        }
    } catch(e) {
        PH.error('Failed to fetch receipt');
    }
}

function toggleAllItems(cb) {
    document.querySelectorAll('.item-checkbox').forEach(c => c.checked = cb.checked);
    calcBulkTotal();
}

function validateBulkRow(input, maxQty) {
    let val = parseInt(input.value) || 0;
    if (val > maxQty) {
        input.value = maxQty;
        PH.error('Cannot return more than purchased (' + maxQty + ')');
    } else if (val < 1) {
        input.value = 1;
    }
}

function calcBulkTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.return-item-row').forEach(row => {
        const cb = row.querySelector('.item-checkbox');
        const qtyInput = row.querySelector('.item-return-qty');
        const totalDisplay = row.querySelector('.item-row-total');
        
        let rowTotal = 0;
        if (cb && cb.checked) {
            const rate = parseFloat(cb.getAttribute('data-rate')) || 0;
            const qty = parseInt(qtyInput.value) || 0;
            rowTotal = rate * qty;
            grandTotal += rowTotal;
        }
        totalDisplay.textContent = '₹' + rowTotal.toFixed(2);
    });
    document.getElementById('bulkTotalDisplay').textContent = '₹' + grandTotal.toFixed(2);
}

function printReturn(x) {
    const typeName = x.patient_type === 'OPD' ? 'OPD Return' : (x.patient_type === 'IPD' ? 'IPD Return' : 'Patient Return');
    
    let html = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>Patient Return Receipt - ${x.return_no}</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; color: #1e293b; line-height: 1.5; padding: 40px; max-width: 800px; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px dashed #e2e8f0; padding-bottom: 20px; }
            .header h1 { margin: 0; color: #0FA4AF; font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
            .header p { margin: 5px 0 0; color: #64748b; font-weight: 600; font-size: 14px; }
            .details { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
            .detail-item { margin-bottom: 12px; display: flex; align-items: center; }
            .detail-label { font-weight: 700; color: #64748b; font-size: 11px; text-transform: uppercase; width: 120px; }
            .detail-value { font-size: 13px; font-weight: 600; color: #1e293b; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 30px; border: 1px solid #cbd5e1; }
            th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #cbd5e1; font-size: 12px; }
            th { background-color: #f1f5f9; font-weight: 800; color: #1e293b; text-transform: uppercase; }
            td { font-weight: 500; }
            .total-row { background-color: #f8fafc; }
            .total-row td { font-weight: 800; font-size: 14px; border-top: 2px solid #cbd5e1; }
            .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #64748b; font-weight: 500; border-top: 2px dashed #e2e8f0; padding-top: 20px; }
            @media print { body { padding: 0; } }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Patient Return Receipt</h1>
            <p>${typeName}</p>
        </div>
        
        <div class="details">
            <div>
                <div class="detail-item"><div class="detail-label">Return No:</div><div class="detail-value">${x.return_no}</div></div>
                <div class="detail-item"><div class="detail-label">Date:</div><div class="detail-value">${fmt.date(x.return_date)}</div></div>
                <div class="detail-item"><div class="detail-label">Receipt No:</div><div class="detail-value">${x.receipt_no || '—'}</div></div>
            </div>
            <div>
                <div class="detail-item"><div class="detail-label">Patient ID:</div><div class="detail-value">${x.patient_id || '—'}</div></div>
                <div class="detail-item"><div class="detail-label">Patient Name:</div><div class="detail-value">${x.patient_name || '—'}</div></div>
                <div class="detail-item"><div class="detail-label">Reason:</div><div class="detail-value">${x.reason || '—'}</div></div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Batch No</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Rate (₹)</th>
                    <th style="text-align:right;">Refund (₹)</th>
                </tr>
            </thead>
            <tbody>
                ${x._items.map(i => `
                <tr>
                    <td>${i.product_name || '—'}</td>
                    <td>${i.batch_no || '—'}</td>
                    <td style="text-align:center;">${i.qty}</td>
                    <td style="text-align:right;">${parseFloat(i.rate || 0).toFixed(2)}</td>
                    <td style="text-align:right; font-weight: 700;">${parseFloat(i.total_amount || 0).toFixed(2)}</td>
                </tr>
                `).join('')}
                <tr class="total-row">
                    <td colspan="4" style="text-align:right;">Grand Total Refund:</td>
                    <td style="text-align:right; color: #e11d48;">₹${parseFloat(x.total_amount_sum || 0).toFixed(2)}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="footer">
            <p>This is a computer-generated receipt.</p>
        </div>
        
        <script>
            setTimeout(() => { window.print(); }, 500);
        <\/script>
    </body>
    </html>
    `;
    
    let win = window.open('', '_blank');
    win.document.write(html);
    win.document.close();
}
</script>
