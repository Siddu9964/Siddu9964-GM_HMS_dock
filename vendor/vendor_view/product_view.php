<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['vendor_id'])) {
    header("Location: login.php");
    exit();
}

$db          = getDB();
$vendor_id   = $_SESSION['vendor_id'];
$vendorName  = $_SESSION['vendor_name'] ?? 'Vendor';

// Get filters
$indent_no = $_GET['indent_no'] ?? '';
$is_global = empty($indent_no);

// Build Query — always restricted to approved quotations only
$query  = "SELECT q.*, 
            p.content, p.strength, p.form, p.therapeutic, 
            p.purchase_rate, p.pack_rate, p.individual_rate, p.mrp, 
            p.pack, p.unit, p.pack_size 
           FROM ph_quotations q 
           LEFT JOIN ph_product p ON q.product_id = p.product_id 
           WHERE q.supplier_id = ? AND q.status = 'approved'";
$params = [$vendor_id];

if (!$is_global) {
    $query   .= " AND q.indent_no = ?";
    $params[] = $indent_no;
}
$query .= " ORDER BY q.quotation_date DESC";

$quotations = $db->fetchAll($query, $params);

// Stats — null-safe total, approved only
$total_all = $is_global
    ? $db->fetchOne("SELECT COUNT(*) as cnt, SUM(total_amount) as total FROM ph_quotations WHERE supplier_id=? AND status='approved'", [$vendor_id])
    : $db->fetchOne("SELECT COUNT(*) as cnt, SUM(total_amount) as total FROM ph_quotations WHERE indent_no=? AND supplier_id=? AND status='approved'", [$indent_no, $vendor_id]);

$stat_count = $total_all['cnt']   ?? 0;
$stat_total = $total_all['total'] ?? 0;

function statusClass(string $s): string {
    return match(strtolower($s)) {
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        default    => 'status-pending'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission History | MediVend Nexus</title>
    <link rel="stylesheet" href="assets/css/vendor.css">
    <link rel="stylesheet" href="assets/css/sidebar_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; }
        .pv-container { padding: 32px; overflow-y: auto; flex: 1; scroll-behavior: smooth; }

        /* ── Quotation Cards ── */
        .pv-card {
            background: #fff; border-radius: 20px; padding: 20px; margin-bottom: 12px;
            border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 16px;
            transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .pv-card-top {
            display: flex; align-items: center; gap: 20px; width: 100%;
        }
        .pv-card:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(6,182,212,0.08); border-color: rgba(6,182,212,0.2); }
        .pv-card.selected { border-color: #06b6d4; background: rgba(6,182,212,0.02); }
        .pv-card.non-selectable { opacity: 0.6; }

        /* ── Card Column Layout ── */
        .check-col   { width: 30px; display: flex; justify-content: center; }
        .meta-col    { width: 140px; }
        .info-col    { flex: 1; min-width: 0; }
        .metrics-col { display: flex; gap: 32px; padding: 0 32px; border-left: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; }
        .action-col  { width: 200px; text-align: right; }

        /* ── Typography ── */
        .q-badge  { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 8px; font-size: 0.65rem; font-weight: 800; }
        .q-title  { font-size: 1rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
        .q-sub    { font-size: 0.7rem; color: #94a3b8; font-weight: 600; }
        .m-label  { font-size: 0.6rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 2px; }
        .m-value  { font-size: 0.85rem; font-weight: 700; color: #1e293b; }
        .q-total  { font-size: 1.2rem; font-weight: 900; color: #06b6d4; }

        /* ── Status Pills ── */
        .status-pill     { padding: 4px 10px; border-radius: 8px; font-size: 0.65rem; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
        .status-pending  { background: #fffbeb; color: #d97706; }
        .status-approved { background: #ecfdf5; color: #059669; }
        .status-rejected { background: #fef2f2; color: #dc2626; }
        .dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }

        /* ── Stat Cards ── */
        .pv-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .pv-stat-card  { background: #fff; padding: 20px; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px; }

        /* ── Advanced Editable Inputs ── */
        .adv-input {
            width: 100%; background: transparent; border: none;
            border-bottom: 1px dashed #cbd5e1; font-size: 0.75rem; font-weight: 700;
            color: #1e293b; padding: 3px 0; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 0; outline: none; font-family: inherit;
        }
        .adv-input:hover { border-bottom-color: #94a3b8; }
        .adv-input:focus {
            border-bottom: 1.5px solid #06b6d4; background: #fff;
            padding: 3px 6px; border-radius: 4px; box-shadow: 0 4px 12px rgba(6,182,212,0.1);
        }
        .adv-input::placeholder { color: #cbd5e1; font-weight: 500; }
        
        .adv-input-price { color: #064e3b; }
        .adv-input-price:focus {
            border-bottom-color: #10b981;
            box-shadow: 0 4px 12px rgba(16,185,129,0.1);
        }
    </style>
</head>
<body>

<div class="nexus-layout">
    <?php $current_page = 'product'; include 'includes/sidebar.php'; ?>

    <div class="nexus-main">
        <?php $page_title = 'Submission History'; include 'includes/topbar.php'; ?>

        <div class="pv-container">

            <!-- ── Page Header ── -->
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:32px;">
                <div>
                    <h2 style="font-size:1.5rem; font-weight:900; color:#1e293b; letter-spacing:-0.5px;">Approved Quotations</h2>
                    <p style="color:#94a3b8; font-weight:600; font-size:0.85rem; margin-top:2px;">Only approved quotations are listed here. Select items to place an order.</p>
                </div>
                <label style="display:flex; align-items:center; gap:10px; background:#fff; padding:10px 20px; border-radius:12px; border:1px solid #f1f5f9; cursor:pointer; font-weight:700; font-size:0.85rem;">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="width:16px; height:16px; accent-color:#06b6d4;"> Select All
                </label>
            </div>

            <!-- ── Stat Cards ── -->
            <div class="pv-stats-grid">
                <div class="pv-stat-card">
                    <div style="width:48px;height:48px;background:#f0f9ff;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#06b6d4;font-size:1.2rem;">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <div class="m-label">Total Quotes</div>
                        <div class="m-value" style="font-size:1.2rem;"><?= $stat_count ?></div>
                    </div>
                </div>
                <div class="pv-stat-card">
                    <div style="width:48px;height:48px;background:#ecfdf5;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#10b981;font-size:1.2rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="m-label">History Value</div>
                        <div class="m-value" style="font-size:1.2rem;">₹<?= number_format($stat_total / 1000, 1) ?>k</div>
                    </div>
                </div>
            </div>

            <!-- ── Quotation List ── -->
            <?php foreach ($quotations as $q):
                $sClass = statusClass($q['status']);
            ?>
            <div class="pv-card" id="card-<?= $q['id'] ?>">
                <div class="pv-card-top">
                    <div class="check-col">
                        <input type="checkbox" class="row-check"
                            data-id="<?= $q['id'] ?>"
                            data-qty="<?= $q['qty'] ?>"
                            data-rate="<?= $q['rate'] ?>"
                            data-tax="<?= $q['tax_amount'] ?>"
                            data-total="<?= $q['total_amount'] ?>"
                            onchange="updateSelection(this)">
                    </div>
                    <div class="meta-col"><span class="q-badge"><?= htmlspecialchars($q['quotation_no']) ?></span></div>
                    <div class="info-col">
                        <div class="q-title"><?= htmlspecialchars($q['item_name'] ?? 'Item') ?></div>
                        <div class="q-sub">Indent: <?= htmlspecialchars($q['indent_no']) ?></div>
                    </div>
                    <div class="metrics-col">
                        <div class="metric-item"><div class="m-label">Qty</div><div class="m-value"><?= number_format($q['qty']) ?></div></div>
                        <div class="metric-item"><div class="m-label">Rate</div><div class="m-value">₹<?= number_format($q['rate'], 2) ?></div></div>
                        <div class="metric-item">
                            <div class="m-label">Batch No</div>
                            <input type="text" class="batch-input" id="batch-<?= $q['id'] ?>" placeholder="Enter Batch" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:6px 10px; font-size:0.75rem; width:110px; font-weight:700; color:#1e293b; transition: all 0.2s;">
                        </div>
                    </div>
                    <div class="action-col">
                        <div class="q-total">₹<?= number_format($q['total_amount'], 2) ?></div>
                        <div class="status-pill <?= $sClass ?>"><div class="dot"></div><?= ucfirst($q['status']) ?></div>
                    </div>
                </div>
                
                <div style="width: 100%; padding-top: 16px; border-top: 1px dashed #e2e8f0; display: flex; gap: 24px; flex-wrap: wrap;">
                    
                    <!-- Specs Block -->
                    <div style="flex: 1; min-width: 280px; background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; display:flex; justify-content:space-between; align-items:center;">
                            <span><i class="fas fa-pills" style="margin-right:4px;"></i> Product Specifications</span>
                            <span style="font-size:0.55rem; color:#0ea5e9; font-weight:800; background:rgba(14,165,233,0.1); padding:2px 6px; border-radius:4px;"><i class="fas fa-edit"></i> EDITABLE</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 16px;">
                            <div class="metric-item"><div class="m-label">Content</div><input type="text" class="adv-input input-content-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['content'] ?? '') ?>" placeholder="-"></div>
                            <div class="metric-item"><div class="m-label">Strength</div><input type="text" class="adv-input input-strength-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['strength'] ?? '') ?>" placeholder="-"></div>
                            <div class="metric-item"><div class="m-label">Form</div><input type="text" class="adv-input input-form-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['form'] ?? '') ?>" placeholder="-"></div>
                            <div class="metric-item"><div class="m-label">Therapeutic</div><input type="text" class="adv-input input-therapeutic-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['therapeutic'] ?? '') ?>" placeholder="-"></div>
                            <div class="metric-item"><div class="m-label">Pack</div><input type="text" class="adv-input input-pack-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['pack'] ?? '') ?>" placeholder="-"></div>
                            <div class="metric-item"><div class="m-label">Unit</div><input type="text" class="adv-input input-unit-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['unit'] ?? '') ?>" placeholder="-"></div>
                            <div class="metric-item"><div class="m-label">Size</div><input type="text" class="adv-input input-pack_size-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['pack_size'] ?? '') ?>" placeholder="-"></div>
                        </div>
                    </div>
                    
                    <!-- Pricing Block -->
                    <div style="flex: 1; min-width: 280px; background: #f0fdf4; padding: 16px; border-radius: 12px; border: 1px solid #dcfce3; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="font-size:0.65rem; font-weight:800; color:#10b981; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; border-bottom: 1px solid #bbf7d0; padding-bottom: 6px; display:flex; justify-content:space-between; align-items:center;">
                            <span><i class="fas fa-tags" style="margin-right:4px;"></i> Pricing & Taxation</span>
                            <span style="font-size:0.55rem; color:#10b981; font-weight:800; background:rgba(16,185,129,0.1); padding:2px 6px; border-radius:4px;"><i class="fas fa-edit"></i> EDITABLE</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 16px;">
                            <div class="metric-item"><div class="m-label" style="color:#059669;">Purchase Rate (₹)</div><input type="number" step="0.01" class="adv-input adv-input-price input-purchase_rate-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['purchase_rate'] ?? '') ?>" placeholder="0.00"></div>
                            <div class="metric-item"><div class="m-label" style="color:#059669;">Pack Rate (₹)</div><input type="number" step="0.01" class="adv-input adv-input-price input-pack_rate-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['pack_rate'] ?? '') ?>" placeholder="0.00"></div>
                            <div class="metric-item"><div class="m-label" style="color:#059669;">Ind. Rate (₹)</div><input type="number" step="0.01" class="adv-input adv-input-price input-individual_rate-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['individual_rate'] ?? '') ?>" placeholder="0.00"></div>
                            <div class="metric-item"><div class="m-label" style="color:#059669;">MRP (₹)</div><input type="number" step="0.01" class="adv-input adv-input-price input-mrp-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['mrp'] ?? '') ?>" placeholder="0.00"></div>
                            <div class="metric-item"><div class="m-label" style="color:#059669;">Tax %</div><input type="number" step="0.01" class="adv-input adv-input-price input-tax_percent-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['tax_percent'] ?? '') ?>" placeholder="0.00"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($quotations)): ?>
            <div style="text-align:center; padding:80px 0;">
                <div style="width:64px;height:64px;border-radius:20px;background:rgba(6,182,212,0.08);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.6rem;color:#06b6d4;">
                    <i class="fas fa-inbox"></i>
                </div>
                <div style="font-weight:700;font-size:1rem;color:#1e293b;">No Approved Quotations</div>
                <div style="font-size:0.82rem;color:#94a3b8;margin-top:6px;">Your quotations are under review. Approved ones will appear here.</div>
            </div>
            <?php endif; ?>

        </div><!-- /.pv-container -->
    </div><!-- /.nexus-main -->
</div><!-- /.nexus-layout -->

<!-- ── Floating Order Bar ── -->
<div class="floating-bar" id="floatingBar">
    <div style="display:flex; gap:32px; align-items:center;">
        <div><div class="f-stat-label">Selected</div><div class="f-stat-value" id="selectedCount">0</div></div>
        <div style="width:1px; height:30px; background:rgba(255,255,255,0.1);"></div>
        <div><div class="f-stat-label">Payable</div><div class="f-stat-value" id="estTotal" style="color:#06b6d4;">₹ 0.00</div></div>
    </div>
    <form id="orderForm" style="display:flex; gap:12px; margin:0; align-items:center;">
        <input type="text" name="invoice_no" id="invoice_no" placeholder="Invoice No" required style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); border-radius:8px; padding:8px 12px; font-size:0.8rem; width:130px; color:#fff; font-weight:600; outline:none; transition:0.3s; font-family:inherit;">
        <input type="hidden" name="subtotal"    id="h_sub">
        <input type="hidden" name="tax_total"   id="h_tax">
        <input type="hidden" name="grand_total" id="h_grand">
        <input type="hidden" name="po_no"       id="h_po">
        <input type="file"   id="fileInput" name="attachment" style="display:none;" accept=".pdf" onchange="updateFileUI(this)">
        <button type="button" class="btn-attach" id="attachBtn" onclick="document.getElementById('fileInput').click()">
            <i class="fas fa-paperclip"></i> <span id="attachText">PDF</span>
        </button>
        <button type="button" class="btn-submit" id="submitBtn" onclick="submitOrder()">
            <i class="fas fa-paper-plane"></i> Submit Order
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let state = { count: 0, subtotal: 0, tax: 0, total: 0 };

function toggleSelectAll(master) {
    document.querySelectorAll('.row-check').forEach(c => {
        c.checked = master.checked;
        const card = document.getElementById('card-' + c.dataset.id);
        if (master.checked) card.classList.add('selected');
        else card.classList.remove('selected');
    });
    updateSelection();
}

function updateSelection(chk) {
    if (chk) {
        const card = document.getElementById('card-' + chk.dataset.id);
        if (chk.checked) card.classList.add('selected');
        else card.classList.remove('selected');
    }
    const selected = document.querySelectorAll('.row-check:checked');
    state = { count: selected.length, subtotal: 0, tax: 0, total: 0 };
    selected.forEach(c => {
        state.subtotal += parseFloat(c.dataset.qty)   * parseFloat(c.dataset.rate);
        state.tax      += parseFloat(c.dataset.tax);
        state.total    += parseFloat(c.dataset.total);
    });
    document.getElementById('selectedCount').textContent = state.count;
    document.getElementById('estTotal').textContent = '₹ ' + state.total.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    const bar = document.getElementById('floatingBar');
    if (state.count > 0) bar.classList.add('active');
    else bar.classList.remove('active');
}

function updateFileUI(input) {
    const btn = document.getElementById('attachBtn');
    const txt = document.getElementById('attachText');
    if (input.files && input.files[0]) {
        btn.classList.add('done');
        txt.textContent = 'Attached';
    } else {
        btn.classList.remove('done');
        txt.textContent = 'PDF';
    }
}

async function submitOrder() {
    const selected = document.querySelectorAll('.row-check:checked');
    if (selected.length === 0) {
        return Swal.fire('No Selection', 'Please select at least one pending quotation.', 'warning');
    }

    const invoiceNo = document.getElementById('invoice_no').value.trim();
    if (!invoiceNo) {
        document.getElementById('invoice_no').style.borderColor = '#ef4444';
        return Swal.fire('Invoice Required', 'Please enter your Invoice Number.', 'warning');
    } else {
        document.getElementById('invoice_no').style.borderColor = '';
    }

    const file = document.getElementById('fileInput').files[0];
    if (!file) {
        return Swal.fire('PDF Required', 'Please attach a PDF before submitting.', 'warning');
    }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    // Auto-generate PO number
    const po_no = 'PO-' + Date.now();
    document.getElementById('h_po').value    = po_no;
    document.getElementById('h_sub').value   = state.subtotal.toFixed(2);
    document.getElementById('h_tax').value   = state.tax.toFixed(2);
    document.getElementById('h_grand').value = state.total.toFixed(2);

    const fd = new FormData(document.getElementById('orderForm'));

    // Collect selected quotation items and their batch numbers
    const items = [];
    let validBatch = true;
    selected.forEach(c => {
        const batchEl = document.getElementById('batch-' + c.dataset.id);
        const batchNo = batchEl ? batchEl.value.trim() : '';
        const card = document.getElementById('card-' + c.dataset.id);
        
        if (!batchNo) {
            validBatch = false;
            if (card) {
                card.style.borderColor = '#ef4444';
                card.style.background = 'rgba(239, 68, 68, 0.02)';
            }
        } else {
            if (card) {
                card.style.borderColor = '';
                card.style.background = '';
            }
        }

        items.push({
            id:       c.dataset.id,
            qty:      c.dataset.qty,
            rate:     c.dataset.rate,
            total:    c.dataset.total,
            batch_no: batchNo,
            content: document.querySelector('.input-content-'+c.dataset.id)?.value || '',
            strength: document.querySelector('.input-strength-'+c.dataset.id)?.value || '',
            form: document.querySelector('.input-form-'+c.dataset.id)?.value || '',
            therapeutic: document.querySelector('.input-therapeutic-'+c.dataset.id)?.value || '',
            pack: document.querySelector('.input-pack-'+c.dataset.id)?.value || '',
            unit: document.querySelector('.input-unit-'+c.dataset.id)?.value || '',
            pack_size: document.querySelector('.input-pack_size-'+c.dataset.id)?.value || '',
            purchase_rate: document.querySelector('.input-purchase_rate-'+c.dataset.id)?.value || 0,
            pack_rate: document.querySelector('.input-pack_rate-'+c.dataset.id)?.value || 0,
            individual_rate: document.querySelector('.input-individual_rate-'+c.dataset.id)?.value || 0,
            mrp: document.querySelector('.input-mrp-'+c.dataset.id)?.value || 0,
            tax_percent: document.querySelector('.input-tax_percent-'+c.dataset.id)?.value || 0
        });
    });

    if (!validBatch) {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Order';
        return Swal.fire('Batch Required', 'Please enter a Batch No for all selected items.', 'warning');
    }

    fd.append('items', JSON.stringify(items));

    try {
        const r   = await fetch('api.php?action=submitOrder', { method: 'POST', body: fd });
        const res = await r.json();
        if (res.success) {
            Swal.fire('Order Placed!', res.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Submission Failed', res.message, 'error');
        }
    } catch (e) {
        Swal.fire('Connection Error', 'Could not reach the server. Please try again.', 'error');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Order';
    }
}
</script>
</body>
</html>
