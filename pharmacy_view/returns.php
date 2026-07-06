<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Returns';
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
    <h1 class="ph-page-title">Returns Management</h1>
    <p class="ph-page-subtitle">Handle sales returns, purchase returns, and damage records</p>
  </div>
  <button class="ph-btn ph-btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> New Return</button>
</div>

<!-- Search & Filter -->
<div class="ph-searchbar">
  <div class="ph-search-input-wrap"><i class="fas fa-search"></i>
    <input type="text" id="searchInput" placeholder="Search return no, product, reference...">
  </div>
  <select class="ph-select" id="typeFilter" style="width:160px; padding:.55rem;">
    <option value="">All Types</option>
    <option value="sales">Sales Return</option>
    <option value="purchase">Purchase Return</option>
    <option value="damage">Damage Return</option>
  </select>
  <select class="ph-select" id="statusFilter" style="width:140px; padding:.55rem;">
    <option value="">All Statuses</option>
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
    <option value="processed">Processed</option>
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
          <th>Reference No</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Amount</th>
          <th>Status</th>
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
        <h5 class="modal-title" id="modalTitle">New Return</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="mainForm" onsubmit="save(event)">
        <div class="modal-body">
          <input type="hidden" name="id" id="id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="ph-label">Return Type <span class="req">*</span></label>
              <select class="ph-select" name="return_type" id="return_type" required onchange="updateRefLabel(this.value)">
                <option value="">-- Select --</option>
                <option value="sales">Sales Return</option>
                <option value="purchase">Purchase Return</option>
                <option value="damage">Damage / Expiry</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="ph-label" id="refLabel">Reference No (Invoice/PO)</label>
              <input type="text" class="ph-input" name="reference_no" id="reference_no" placeholder="e.g. INV-00001">
            </div>

            <div class="col-md-8">
              <label class="ph-label">Product <span class="req">*</span></label>
              <select class="ph-select" name="product_id" id="product_id" required onchange="fillProductName(this)">
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
              <input type="number" class="ph-input" name="qty" id="qty" min="1" value="1" required onchange="calcTotal()">
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

            <div class="col-md-6">
              <label class="ph-label">Status</label>
              <select class="ph-select" name="status" id="status">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="processed">Processed</option>
              </select>
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
const modal = new bootstrap.Modal(document.getElementById('mainModal'));

document.addEventListener('DOMContentLoaded', () => {
    load();
    document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; render(); });
    document.getElementById('typeFilter').addEventListener('change', () => { currentPage = 1; render(); });
    document.getElementById('statusFilter').addEventListener('change', () => { currentPage = 1; render(); });
});

async function load() {
    const res = await phGet(API_BASE + 'pharmacy/returns');
    if (res.success) { all = res.data; render(); } else PH.error(res.message);
}

function render() {
    const q  = document.getElementById('searchInput').value.toLowerCase();
    const tf = document.getElementById('typeFilter').value;
    const sf = document.getElementById('statusFilter').value;
    let filtered = all;
    if (q)  filtered = filtered.filter(x => (x.return_no||'').toLowerCase().includes(q) || (x.product_name||'').toLowerCase().includes(q) || (x.reference_no||'').toLowerCase().includes(q));
    if (tf) filtered = filtered.filter(x => x.return_type === tf);
    if (sf) filtered = filtered.filter(x => x.status === sf);
    const pager = phPaginate(filtered, currentPage, PER_PAGE);
    let html = '';
    if (!pager.items.length) html = `<tr><td colspan="10" class="text-center py-4 text-muted">No returns found.</td></tr>`;
    else pager.items.forEach(x => {
        const typeLabels = { sales:'Sales Return', purchase:'Purchase Return', damage:'Damage/Expiry' };
        const typeColors = { sales:'badge-warning', purchase:'badge-info', damage:'badge-danger' };
        
        let attachmentHtml = '';
        if (x.image) attachmentHtml += `<a href="../../${x.image}" target="_blank" class="ph-btn ph-btn-sm ph-btn-outline" style="padding:0.2rem 0.4rem; font-size:0.75rem" title="View Image"><i class="fas fa-image text-primary"></i> Image</a> `;
        if (x.doc) attachmentHtml += `<a href="../../${x.doc}" target="_blank" class="ph-btn ph-btn-sm ph-btn-outline" style="padding:0.2rem 0.4rem; font-size:0.75rem" title="View Document"><i class="fas fa-file-pdf text-info"></i> Doc</a>`;
        
        html += `<tr>
            <td><span class="ph-badge badge-muted">${x.return_no}</span></td>
            <td>${fmt.date(x.return_date)}</td>
            <td><span class="ph-badge ${typeColors[x.return_type]||'badge-muted'}">${typeLabels[x.return_type]||x.return_type}</span></td>
            <td>${x.reference_no||'—'}</td>
            <td>${x.product_name||'—'}</td>
            <td class="fw-bold">${x.qty}</td>
            <td class="fw-bold text-danger">-${fmt.currency(x.total_amount)}</td>
            <td>${statusBadge(x.status)}</td>
            <td>${attachmentHtml || '<span class="text-muted small">—</span>'}</td>
            <td class="text-end">
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon text-info me-1" onclick='printReturn(${JSON.stringify(x).replace(/'/g,"&apos;")})' title="Print"><i class="fas fa-print"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon me-1" onclick='edit(${JSON.stringify(x).replace(/'/g,"&apos;")})' title="Edit"><i class="fas fa-edit"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon text-danger" onclick="del(${x.id})" title="Delete"><i class="fas fa-trash"></i></button>
            </td></tr>`;
    });
    document.getElementById('tableBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; render(); });
}

function openModal() {
    document.getElementById('mainForm').reset();
    document.getElementById('id').value = '';
    document.getElementById('modalTitle').textContent = 'New Return';
    document.getElementById('existing_image').value = '';
    document.getElementById('existing_doc').value = '';
    document.getElementById('current_image_link').innerHTML = '';
    document.getElementById('current_doc_link').innerHTML = '';
    calcTotal();
    modal.show();
}

function edit(x) {
    document.getElementById('id').value = x.id;
    document.getElementById('modalTitle').textContent = 'Edit Return';
    ['return_type','reference_no','product_id','product_name','batch_no','qty','rate','total_amount','status','reason'].forEach(f => {
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
    
    PH.loading('Saving return...');
    try {
        const res = await fetch(API_BASE + 'pharmacy/returns', {
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
            const res = await fetch(API_BASE + 'pharmacy/returns/' + id, { method: 'DELETE' }).then(r => r.json());
            if (res.success) { PH.success('Deleted'); load(); } else PH.error(res.message);
        } catch(e) { PH.error('Failed to delete'); }
    });
}

function calcTotal() {
    const qty = parseFloat(document.getElementById('qty').value) || 0;
    const rate = parseFloat(document.getElementById('rate').value) || 0;
    const total = qty * rate;
    document.getElementById('total_amount').value = total.toFixed(2);
    document.getElementById('total_display').value = '₹' + total.toFixed(2);
}

function fillProductName(sel) {
    document.getElementById('product_name').value = sel.options[sel.selectedIndex].getAttribute('data-name') || '';
}

function updateRefLabel(type) {
    const labels = { sales:'Reference Invoice No', purchase:'Reference PO No', damage:'Batch / Reference No' };
    const el = document.getElementById('refLabel');
    if (el) el.textContent = labels[type] || 'Reference No';
}

function printReturn(x) {
    const typeLabels = { sales:'Sales Return', purchase:'Purchase Return', damage:'Damage/Expiry' };
    const typeName = typeLabels[x.return_type] || 'Return';
    
    let html = `
    <!DOCTYPE html>
    <html>
    <head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

        <title>Return Receipt - ${x.return_no}</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; color: #1e293b; line-height: 1.5; padding: 40px; max-width: 800px; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px dashed #e2e8f0; padding-bottom: 20px; }
            .header h1 { margin: 0; color: #1f6b4a; font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
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
            <h1>Pharmacy Return Receipt</h1>
            <p>${typeName}</p>
        </div>
        
        <div class="details">
            <div>
                <div class="detail-item"><div class="detail-label">Return No:</div><div class="detail-value">${x.return_no}</div></div>
                <div class="detail-item"><div class="detail-label">Date:</div><div class="detail-value">${fmt.date(x.return_date)}</div></div>
                <div class="detail-item"><div class="detail-label">Reference No:</div><div class="detail-value">${x.reference_no || '—'}</div></div>
            </div>
            <div>
                <div class="detail-item"><div class="detail-label">Status:</div><div class="detail-value" style="text-transform: uppercase;">${x.status || '—'}</div></div>
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
                    <th style="text-align:right;">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>${x.product_name || '—'}</td>
                    <td>${x.batch_no || '—'}</td>
                    <td style="text-align:center;">${x.qty}</td>
                    <td style="text-align:right;">${parseFloat(x.rate || 0).toFixed(2)}</td>
                    <td style="text-align:right; font-weight: 700;">${parseFloat(x.total_amount || 0).toFixed(2)}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="4" style="text-align:right;">Grand Total:</td>
                    <td style="text-align:right; color: #1f6b4a;">₹${parseFloat(x.total_amount || 0).toFixed(2)}</td>
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
