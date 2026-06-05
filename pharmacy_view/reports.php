<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Reports & Analytics';
include 'includes/ph_head.php';
?>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">Reports & Analytics</h1>
    <p class="ph-page-subtitle">Business insights, exports, and performance analysis</p>
  </div>
</div>

<!-- Report Type Tabs -->
<div class="ph-searchbar" style="flex-wrap:wrap; gap:.5rem;">
  <div class="d-flex flex-wrap gap-2">
    <?php
    $reports = [
        'sales'     => ['fa-receipt',         'Sales Report'],
        'purchase'  => ['fa-shopping-cart',   'Purchase Report'],
        'expiry'    => ['fa-calendar-times',  'Expiry Report'],
        'low_stock' => ['fa-exclamation-triangle', 'Low Stock Report'],
        'top_products'=> ['fa-star',          'Top Products'],
        'supplier'  => ['fa-truck',           'Supplier Report'],
        'customer'  => ['fa-users',           'Customer Report'],
        'tax'       => ['fa-percent',         'Tax Report'],
    ];
    foreach ($reports as $key => [$icon, $label]):
    ?>
      <button class="ph-btn report-tab <?= $key === 'sales' ? 'ph-btn-primary' : 'ph-btn-outline' ?>" data-report="<?= $key ?>">
        <i class="fas <?= $icon ?>"></i> <?= $label ?>
      </button>
    <?php endforeach; ?>
  </div>
</div>

<!-- Date Filters -->
<div class="ph-card mb-4">
  <div class="ph-card-body">
    <div class="row g-3 align-items-end">
      <div class="col-md-2" id="paymentFilterWrapper">
        <label class="ph-label">Payment Method</label>
        <select class="ph-select" id="paymentMethod" style="padding: 0.5rem;">
          <option value="">All Payments</option>
          <option value="cash">Cash</option>
          <option value="card">Card</option>
          <option value="upi">UPI / Online</option>
          <option value="credit">Credit</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="ph-label">From Date</label>
        <input type="date" class="ph-input" id="dateFrom" value="<?= date('Y-m-01') ?>">
      </div>
      <div class="col-md-2">
        <label class="ph-label">To Date</label>
        <input type="date" class="ph-input" id="dateTo" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="col-md-2">
        <button class="ph-btn ph-btn-primary w-100" onclick="generateReport()"><i class="fas fa-chart-bar"></i> Generate</button>
      </div>
      <div class="col-md-2">
        <button class="ph-btn ph-btn-outline w-100" onclick="exportCSV()"><i class="fas fa-file-csv"></i> Export CSV</button>
      </div>
      <div class="col-md-2">
        <button class="ph-btn ph-btn-outline w-100" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
      </div>
    </div>
  </div>
</div>

<!-- Summary Stats -->
<div id="statRow" class="ph-stat-grid mb-4"></div>

<!-- Chart -->
<div class="row g-4 mb-4" id="chartRow" style="display:none!important;">
  <div class="col-12">
    <div class="ph-card">
      <div class="ph-card-header"><span id="chartTitle">Sales Trend</span></div>
      <div class="ph-card-body"><canvas id="reportChart" height="80"></canvas></div>
    </div>
  </div>
</div>

<!-- Table -->
<div class="ph-card no-print">
  <div class="ph-card-header">
    <span id="tableTitle">Report Results</span>
    <span id="resultCount" class="ph-badge badge-muted">0 records</span>
  </div>
  <div class="ph-table-wrap">
    <table class="ph-table" id="reportTable">
      <thead id="tableHead"></thead>
      <tbody id="tableBody">
        <tr><td colspan="10" class="text-center py-4 text-muted">Select a report type and click Generate</td></tr>
      </tbody>
    </table>
  </div>
</div>

</div></div></div>
<?php include 'includes/ph_foot.php'; ?>
<style>
.report-tab.active, .report-tab.ph-btn-primary { background: var(--ph-primary) !important; color: #fff !important; border-color: var(--ph-primary) !important; }
@media print { .ph-sidebar, .ph-navbar, .ph-searchbar, .no-print { display: none !important; } #ph-content { margin-left: 0 !important; } }
</style>
<script>
let currentReport = 'sales';
let currentData   = [];
let reportChart   = null;

document.querySelectorAll('.report-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.report-tab').forEach(b => { b.classList.remove('ph-btn-primary'); b.classList.add('ph-btn-outline'); });
        this.classList.add('ph-btn-primary'); this.classList.remove('ph-btn-outline');
        currentReport = this.dataset.report;
        generateReport();
    });
});

const REPORT_CONFIGS = {
    sales:       { title: 'Sales Report',      headers: ['Invoice No','Date','Customer','Items','Subtotal','Discount','Tax','Grand Total','Payment','Status'] },
    purchase:    { title: 'Purchase Report',   headers: ['PO No','Date','Supplier','Expected Date','Subtotal','Tax','Grand Total','Status'] },
    expiry:      { title: 'Expiry Report',     headers: ['Product ID','Product Name','Strength','Form','Batch No','Expiry Date','Quantity','Status'] },
    low_stock:   { title: 'Low Stock Report',  headers: ['Product ID','Product Name','Form','Therapeutic','Batch No','Current Qty','Expiry Date'] },
    top_products:{ title: 'Top Selling Products', headers: ['Product Name','Total Qty Sold','Total Revenue','Avg. Rate'] },
    supplier:    { title: 'Supplier Report',   headers: ['Supplier ID','Company','Contact','City','GST No','Status','POs','Total Value'] },
    customer:    { title: 'Customer Report',   headers: ['Customer ID','Name','Phone','Email','Total Bills','Total Spent','Credit Limit'] },
    tax:         { title: 'Tax Summary Report',headers: ['Invoice No','Date','Customer','Taxable Amount','Tax Amount','Grand Total','Tax %'] },
};

async function generateReport() {
    document.getElementById('tableBody').innerHTML = '<tr><td colspan="10" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>';
    document.getElementById('statRow').innerHTML = '';

    const dateFrom  = document.getElementById('dateFrom').value;
    const dateTo    = document.getElementById('dateTo').value;
    const payMethod = document.getElementById('paymentMethod')?.value || '';
    const url = API_BASE + `pharmacy/reports?type=${currentReport}&date_from=${dateFrom}&date_to=${dateTo}&payment_method=${encodeURIComponent(payMethod)}`;

    const res = await phGet(url);
    if (!res.success) { PH.error(res.message); return; }

    // API wraps result as: { success: true, data: { data: [...rows...], stats: {...} } }
    // For some report types (expiry/low_stock) it may return data directly as array
    const payload = res.data;
    if (Array.isArray(payload)) {
        currentData = payload;
        renderStats({});
    } else {
        currentData = payload?.data || [];
        renderStats(payload?.stats || {});
    }
    renderTable(currentData);
    if (res.chart_data) renderChart(res.chart_data);
}


function renderStats(stats) {
    if (!Object.keys(stats).length) { document.getElementById('statRow').innerHTML = ''; return; }
    const statColors = ['#0FA4AF','#22C55E','#F59E0B','#8B5CF6'];
    const icons      = ['fa-chart-line','fa-receipt','fa-percent','fa-tag'];
    const els = Object.entries(stats).map(([k, v], i) => `
        <div class="ph-stat" style="border-left:4px solid ${statColors[i%4]};">
            <div class="ph-stat-icon" style="background:${statColors[i%4]}20;color:${statColors[i%4]};"><i class="fas ${icons[i%4]}"></i></div>
            <div class="ph-stat-val">${typeof v === 'number' && !Number.isInteger(v) ? '₹'+v.toLocaleString('en-IN',{minimumFractionDigits:2}) : v.toLocaleString('en-IN')}</div>
            <div class="ph-stat-lbl">${k.replace(/_/g,' ').replace(/\b\w/g, l => l.toUpperCase())}</div>
        </div>`);
    document.getElementById('statRow').innerHTML = els.join('');
}

function renderTable(data) {
    const cfg = REPORT_CONFIGS[currentReport] || { title: 'Report', headers: [] };
    document.getElementById('tableTitle').textContent = cfg.title;
    document.getElementById('tableHead').innerHTML = '<tr>' + cfg.headers.map(h => `<th>${h}</th>`).join('') + '</tr>';
    document.getElementById('resultCount').textContent = data.length + ' records';

    if (!data.length) {
        document.getElementById('tableBody').innerHTML = `<tr><td colspan="${cfg.headers.length}" class="text-center py-4 text-muted">No records found for the selected period.</td></tr>`;
        return;
    }

    const rows = {
        sales:       d => `<td><span class="ph-badge badge-primary">${d.invoice_no}</span></td><td>${fmt.date(d.invoice_date)}</td><td>${d.customer_name||'Walk-in'}</td><td>${d.item_count||0}</td><td>${fmt.currency(d.subtotal)}</td><td class="text-danger">-${fmt.currency(d.discount_amount)}</td><td>${fmt.currency(d.tax_total)}</td><td class="fw-bold text-primary">${fmt.currency(d.grand_total)}</td><td>${d.payment_method}</td><td>${statusBadge(d.status)}</td>`,
        purchase:    d => `<td><span class="ph-badge badge-muted">${d.po_no}</span></td><td>${fmt.date(d.po_date)}</td><td>${d.supplier_name}</td><td>${fmt.date(d.expected_date)}</td><td>${fmt.currency(d.subtotal)}</td><td>${fmt.currency(d.tax_total)}</td><td class="fw-bold text-primary">${fmt.currency(d.grand_total)}</td><td>${statusBadge(d.status)}</td>`,
        expiry:      d => `<td>${d.product_id}</td><td>${d.product_name}</td><td>${d.strength||'—'}</td><td>${d.form||'—'}</td><td>${d.batch_number||'—'}</td><td>${expiryBadge(d.expiry_date)}</td><td class="fw-bold">${d.quantity}</td><td>${d.quantity<=0?'<span class="ph-badge badge-danger">Out</span>':d.quantity<=20?'<span class="ph-badge badge-warning">Low</span>':'<span class="ph-badge badge-success">OK</span>'}</td>`,
        low_stock:   d => `<td>${d.product_id}</td><td>${d.product_name}</td><td>${d.form||'—'}</td><td>${d.therapeutic||'—'}</td><td>${d.batch_number||'—'}</td><td class="text-danger fw-bold">${d.quantity}</td><td>${expiryBadge(d.expiry_date)}</td>`,
        top_products:d => `<td class="fw-bold">${d.product_name}</td><td class="fw-bold text-primary">${fmt.number(d.total_qty)}</td><td class="text-success fw-bold">${fmt.currency(d.total_revenue)}</td><td>${fmt.currency(d.avg_rate)}</td>`,
        supplier:    d => `<td>${d.supplier_id}</td><td>${d.company_name}</td><td>${d.supplier_name}<br><small class="text-muted">${d.phone}</small></td><td>${d.city||'—'}</td><td>${d.gst_no||'—'}</td><td>${statusBadge(d.status)}</td><td>${d.po_count||0}</td><td>${fmt.currency(d.total_value)}</td>`,
        customer:    d => `<td>${d.customer_id}</td><td>${d.customer_name}</td><td>${d.phone}</td><td>${d.email||'—'}</td><td>${d.total_bills||0}</td><td class="fw-bold text-primary">${fmt.currency(d.total_spent)}</td><td>${fmt.currency(d.credit_limit)}</td>`,
        tax:         d => `<td><span class="ph-badge badge-primary">${d.invoice_no}</span></td><td>${fmt.date(d.invoice_date)}</td><td>${d.customer_name||'Walk-in'}</td><td>${fmt.currency(d.subtotal)}</td><td class="text-warning fw-bold">${fmt.currency(d.tax_total)}</td><td>${fmt.currency(d.grand_total)}</td><td>${d.avg_tax_pct||'—'}%</td>`,
    };

    const rowFn = rows[currentReport] || (() => '');
    document.getElementById('tableBody').innerHTML = data.map(d => `<tr>${rowFn(d)}</tr>`).join('');
}

function renderChart(chartData) {
    document.getElementById('chartRow').style.display = '';
    if (reportChart) reportChart.destroy();
    reportChart = new Chart(document.getElementById('reportChart'), {
        type: chartData.type || 'bar',
        data: {
            labels: chartData.labels,
            datasets: chartData.datasets.map((ds, i) => ({
                ...ds,
                backgroundColor: ds.type === 'line' ? 'rgba(15,164,175,.15)' : ['#0FA4AF','#22C55E','#F59E0B','#8B5CF6','#EF4444'][i % 5],
                borderColor: ['#0FA4AF','#22C55E','#F59E0B','#8B5CF6','#EF4444'][i % 5],
                borderWidth: 2
            }))
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: chartData.datasets.length > 1 } },
            scales: { y: { beginAtZero: true, grid: { color: '#E2E8F0' } }, x: { grid: { display: false } } }
        }
    });
}

function exportCSV() {
    if (!currentData.length) { PH.warning('No data to export'); return; }
    const cfg = REPORT_CONFIGS[currentReport] || { title: 'report', headers: Object.keys(currentData[0]) };
    const headers = cfg.headers;
    const keys    = Object.keys(currentData[0]);
    const csvRows = [headers.join(',')];
    currentData.forEach(row => {
        csvRows.push(keys.map(k => `"${String(row[k]??'').replace(/"/g,'""')}"`).join(','));
    });
    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${currentReport}_report_${document.getElementById('dateFrom').value}_to_${document.getElementById('dateTo').value}.csv`;
    a.click();
}

// Auto-load sales report on page load
document.addEventListener('DOMContentLoaded', generateReport);
</script>
