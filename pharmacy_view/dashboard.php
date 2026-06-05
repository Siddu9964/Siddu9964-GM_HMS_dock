<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Dashboard';
include 'includes/ph_head.php';
?>

<style>
/* ── Dashboard Command Bar ── */
.cmd-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 1.75rem;
    background: #fff;
    border-bottom: 1px solid var(--ph-border);
    position: sticky;
    top: var(--ph-navbar-h);
    z-index: 40;
    box-shadow: 0 1px 0 rgba(15,23,42,0.04);
}
.cmd-left { display: flex; align-items: center; gap: 14px; }
.cmd-logo {
    width: 42px; height: 42px; border-radius: 13px;
    background: linear-gradient(135deg, #0FA4AF, #0d8a94);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: #fff;
    box-shadow: 0 4px 12px rgba(15,164,175,0.3);
}
.cmd-title { font-size: 1rem; font-weight: 900; color: var(--ph-text); letter-spacing: -0.5px; }
.cmd-sub   { font-size: 0.7rem; color: var(--ph-muted); font-weight: 500; margin-top: 1px; }
.cmd-right { display: flex; align-items: center; gap: 12px; }
.live-chip {
    display: flex; align-items: center; gap: 6px;
    background: #ecfdf5; border: 1px solid #a7f3d0;
    padding: 5px 12px; border-radius: 20px;
    font-size: 0.62rem; font-weight: 800; color: #065f46; letter-spacing: 0.5px;
}
.live-dot {
    width: 7px; height: 7px; border-radius: 50%; background: #10b981;
    animation: blink 1.4s infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
.cmd-clock { font-size: 0.78rem; font-weight: 700; color: var(--ph-muted); }

/* ── Page Body ── */
.dash-body { padding: 1.75rem; display: flex; flex-direction: column; gap: 20px; background: var(--ph-bg); min-height: calc(100vh - 64px - 77px); }

/* ── KPI Strip ── */
.kpi-strip {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 1px;
    background: var(--ph-border);
    border: 1px solid var(--ph-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--ph-shadow);
}
.kpi-cell {
    background: #fff;
    padding: 18px 16px;
    display: flex; flex-direction: column; gap: 4px;
    transition: 0.2s; position: relative; cursor: default;
}
.kpi-cell::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
    background: var(--kpi-color, var(--ph-primary));
    transform: scaleX(0); transition: transform 0.25s ease;
    transform-origin: left;
}
.kpi-cell:hover::after { transform: scaleX(1); }
.kpi-cell:hover { background: #fafcff; }
.kpi-icon {
    width: 32px; height: 32px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.82rem; margin-bottom: 4px;
    background: var(--kpi-bg, rgba(15,164,175,0.1));
    color: var(--kpi-color, var(--ph-primary));
}
.kpi-val {
    font-size: 1.4rem; font-weight: 900;
    color: var(--ph-text); letter-spacing: -1px; line-height: 1;
}
.kpi-label { font-size: 0.58rem; font-weight: 700; color: var(--ph-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.kpi-link { font-size: 0.58rem; font-weight: 700; color: var(--kpi-color, var(--ph-primary)); text-decoration: none; margin-top: 1px; }
.kpi-link:hover { text-decoration: underline; }

/* ── Mid Row ── */
.mid-row { display: grid; grid-template-columns: 1fr 320px; gap: 16px; }

/* ── Chart Panel ── */
.panel {
    background: #fff;
    border: 1px solid var(--ph-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--ph-shadow);
}
.panel-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid var(--ph-border);
    background: #fafbfc;
}
.panel-title {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.82rem; font-weight: 800; color: var(--ph-text);
}
.panel-title-dot { width: 8px; height: 8px; border-radius: 50%; }
.panel-action {
    font-size: 0.65rem; font-weight: 700; color: var(--ph-muted);
    background: #F1F5F9; border: 1px solid var(--ph-border);
    padding: 4px 10px; border-radius: 6px; text-decoration: none;
    transition: 0.2s;
}
.panel-action:hover { color: var(--ph-primary); border-color: var(--ph-primary); background: rgba(15,164,175,0.05); }
.panel-body { padding: 16px 20px; }

/* ── Stock Panel ── */
.stock-panel { display: flex; flex-direction: column; }
.stock-legend { padding: 0 20px 16px; display: flex; flex-direction: column; gap: 8px; }
.legend-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; border-radius: 10px;
    background: var(--ph-bg); border: 1px solid var(--ph-border);
}
.legend-label { font-size: 0.72rem; font-weight: 700; color: var(--ph-text); display: flex; align-items: center; gap: 8px; }
.legend-pip { width: 10px; height: 10px; border-radius: 3px; }
.legend-val { font-size: 0.9rem; font-weight: 900; color: var(--ph-text); }

/* ── Bottom Row ── */
.bottom-row { display: grid; grid-template-columns: 1fr 1fr 300px; gap: 16px; }

/* ── Alert Feed ── */
.alert-feed { max-height: 330px; overflow-y: auto; padding: 4px 0; }
.alert-feed::-webkit-scrollbar { width: 3px; }
.alert-feed::-webkit-scrollbar-thumb { background: var(--ph-border); border-radius: 4px; }
.alert-row {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 20px; transition: 0.15s;
    border-left: 3px solid transparent;
}
.alert-row:hover { background: var(--ph-bg); }
.alert-row.low    { border-left-color: var(--ph-danger); }
.alert-row.expiry { border-left-color: var(--ph-warning); }
.alert-icon-box {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 0.75rem;
}
.alert-name { font-size: 0.75rem; font-weight: 700; color: var(--ph-text); }
.alert-desc { font-size: 0.65rem; color: var(--ph-muted); margin-top: 1px; }
.alert-badge {
    margin-left: auto; flex-shrink: 0;
    font-size: 0.58rem; font-weight: 800; padding: 3px 8px; border-radius: 6px;
    text-transform: uppercase; letter-spacing: 0.3px;
}

/* ── Top Products ── */
.product-row {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 20px; transition: 0.15s;
}
.product-row:hover { background: var(--ph-bg); }
.rank-num {
    width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.65rem; font-weight: 900;
}
.r1 { background: rgba(245,158,11,0.12); color: var(--ph-warning); }
.r2 { background: rgba(100,116,139,0.1);  color: #64748b; }
.r3 { background: rgba(239,68,68,0.1);   color: var(--ph-danger); }
.rn { background: var(--ph-bg);           color: var(--ph-muted); }
.prod-bar-wrap { flex: 1; min-width: 0; }
.prod-name { font-size: 0.75rem; font-weight: 700; color: var(--ph-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prod-track { height: 3px; background: var(--ph-border); border-radius: 4px; margin-top: 4px; }
.prod-fill  { height: 3px; border-radius: 4px; background: linear-gradient(90deg, var(--ph-primary), #5DE8F0); }
.prod-rev   { font-size: 0.75rem; font-weight: 800; color: var(--ph-primary); white-space: nowrap; }

/* ── Quick Actions ── */
.qa-grid { padding: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.qa-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 8px; padding: 16px 8px; border-radius: 12px;
    background: var(--ph-bg); border: 1.5px solid var(--ph-border);
    text-decoration: none; transition: 0.2s;
}
.qa-btn:hover {
    background: #fff;
    border-color: var(--qa-c, var(--ph-primary));
    box-shadow: 0 4px 12px rgba(15,23,42,0.08);
    transform: translateY(-2px);
}
.qa-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
}
.qa-label { font-size: 0.62rem; font-weight: 800; color: var(--ph-muted); text-align: center; text-transform: uppercase; letter-spacing: 0.3px; }

/* ── Responsive ── */
@media (max-width: 1280px) {
    .kpi-strip { grid-template-columns: repeat(4, 1fr); }
    .mid-row, .bottom-row { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .kpi-strip { grid-template-columns: repeat(2, 1fr); }
    .dash-body  { padding: 1rem; }
}
</style>

<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>

<!-- ── Command Bar ── -->
<div class="cmd-bar">
    <div class="cmd-left">
        <div class="cmd-logo"><i class="fas fa-capsules"></i></div>
        <div>
            <div class="cmd-title">Pharmacy Command Center</div>
            <div class="cmd-sub">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Pharmacist') ?> &nbsp;·&nbsp; <?= date('l, d M Y') ?></div>
        </div>
    </div>
    <div class="cmd-right">
        <div class="live-chip"><div class="live-dot"></div>LIVE</div>
        <div class="cmd-clock" id="liveTime"></div>
        <a href="billing_pos.php" class="ph-btn ph-btn-primary">
            <i class="fas fa-cash-register"></i> New Sale
        </a>
    </div>
</div>

<!-- ── Dashboard Body ── -->
<div class="dash-body">

    <!-- ── KPI Strip ── -->
    <div class="kpi-strip">
        <div class="kpi-cell" style="--kpi-color:#0FA4AF;--kpi-bg:rgba(15,164,175,0.1);">
            <div class="kpi-icon"><i class="fas fa-pills"></i></div>
            <div class="kpi-val" id="stat-total-products">—</div>
            <div class="kpi-label">Total Products</div>
        </div>
        <div class="kpi-cell" style="--kpi-color:#F59E0B;--kpi-bg:rgba(245,158,11,0.1);">
            <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kpi-val" id="stat-low-stock">—</div>
            <div class="kpi-label">Low Stock</div>
            <a href="inventory_alerts.php" class="kpi-link">View All →</a>
        </div>
        <div class="kpi-cell" style="--kpi-color:#EF4444;--kpi-bg:rgba(239,68,68,0.1);">
            <div class="kpi-icon"><i class="fas fa-calendar-times"></i></div>
            <div class="kpi-val" id="stat-expiry-soon">—</div>
            <div class="kpi-label">Expiring Soon</div>
            <a href="inventory_alerts.php" class="kpi-link">View All →</a>
        </div>
        <div class="kpi-cell" style="--kpi-color:#22C55E;--kpi-bg:rgba(34,197,94,0.1);">
            <div class="kpi-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="kpi-val" id="stat-today-sales">—</div>
            <div class="kpi-label">Today's Sales</div>
        </div>
        <div class="kpi-cell" style="--kpi-color:#3B82F6;--kpi-bg:rgba(59,130,246,0.1);">
            <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
            <div class="kpi-val" id="stat-month-sales">—</div>
            <div class="kpi-label">Month Sales</div>
        </div>
        <div class="kpi-cell" style="--kpi-color:#8B5CF6;--kpi-bg:rgba(139,92,246,0.1);">
            <div class="kpi-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="kpi-val" id="stat-pending-indents">—</div>
            <div class="kpi-label">Pending Indents</div>
        </div>
        <div class="kpi-cell" style="--kpi-color:#0FA4AF;--kpi-bg:rgba(15,164,175,0.1);">
            <div class="kpi-icon"><i class="fas fa-truck"></i></div>
            <div class="kpi-val" id="stat-total-suppliers">—</div>
            <div class="kpi-label">Active Suppliers</div>
        </div>
        <div class="kpi-cell" style="--kpi-color:#EC4899;--kpi-bg:rgba(236,72,153,0.1);">
            <div class="kpi-icon"><i class="fas fa-users"></i></div>
            <div class="kpi-val" id="stat-total-customers">—</div>
            <div class="kpi-label">Customers</div>
        </div>
    </div>

    <!-- ── Mid Row ── -->
    <div class="mid-row">
        <!-- Sales Chart -->
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">
                    <div class="panel-title-dot" style="background:#0FA4AF;"></div>
                    Sales Revenue — Last 7 Days
                </div>
                <a href="reports.php" class="panel-action">FULL REPORT →</a>
            </div>
            <div class="panel-body" style="height:220px;position:relative;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Stock Donut -->
        <div class="panel stock-panel">
            <div class="panel-head">
                <div class="panel-title">
                    <div class="panel-title-dot" style="background:#22C55E;"></div>
                    Inventory Health
                </div>
            </div>
            <div class="panel-body" style="height:160px;position:relative;">
                <canvas id="stockChart"></canvas>
            </div>
            <div class="stock-legend">
                <div class="legend-row">
                    <div class="legend-label"><div class="legend-pip" style="background:#22C55E;"></div>In Stock</div>
                    <div class="legend-val" id="stock-in">—</div>
                </div>
                <div class="legend-row">
                    <div class="legend-label"><div class="legend-pip" style="background:#F59E0B;"></div>Low Stock</div>
                    <div class="legend-val" id="stock-low">—</div>
                </div>
                <div class="legend-row">
                    <div class="legend-label"><div class="legend-pip" style="background:#EF4444;"></div>Out of Stock</div>
                    <div class="legend-val" id="stock-out">—</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Bottom Row ── -->
    <div class="bottom-row">
        <!-- Critical Alerts -->
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">
                    <div class="panel-title-dot" style="background:#EF4444;box-shadow:0 0 6px #EF4444;"></div>
                    Critical Alerts
                </div>
                <a href="inventory_alerts.php" class="panel-action">VIEW ALL →</a>
            </div>
            <div class="alert-feed" id="alertsList">
                <div style="text-align:center;padding:40px;color:var(--ph-muted);font-size:0.8rem;">
                    <i class="fas fa-circle-notch fa-spin" style="font-size:1.3rem;color:var(--ph-primary);margin-bottom:10px;display:block;"></i>
                    Loading alerts...
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">
                    <div class="panel-title-dot" style="background:#F59E0B;"></div>
                    Top Selling — This Month
                </div>
            </div>
            <div id="topProductsList" style="padding:4px 0;">
                <div style="text-align:center;padding:40px;color:var(--ph-muted);font-size:0.8rem;">
                    <i class="fas fa-circle-notch fa-spin" style="font-size:1.3rem;color:var(--ph-primary);margin-bottom:10px;display:block;"></i>
                    Loading...
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">
                    <div class="panel-title-dot" style="background:#8B5CF6;"></div>
                    Quick Actions
                </div>
            </div>
            <div class="qa-grid">
                <?php $actions = [
                    ['billing_pos.php',   'fa-cash-register',  'New Sale',     '#0FA4AF'],
                    ['products.php',      'fa-plus-circle',    'Add Product',  '#22C55E'],
                    ['indent_request.php','fa-clipboard-list', 'Indent',       '#8B5CF6'],
                    ['purchase_order.php','fa-shopping-cart',  'Purchase',     '#F59E0B'],
                    ['stock_receive.php', 'fa-boxes',          'Receive Stock','#3B82F6'],
                    ['reports.php',       'fa-chart-pie',      'Reports',      '#EC4899'],
                ];
                foreach ($actions as $a): ?>
                <a href="<?= $a[0] ?>" class="qa-btn" style="--qa-c:<?= $a[3] ?>;">
                    <div class="qa-icon" style="background:<?= $a[3] ?>18;color:<?= $a[3] ?>;"><i class="fas <?= $a[1] ?>"></i></div>
                    <div class="qa-label"><?= $a[2] ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div><!-- /.dash-body -->
</div><!-- /#ph-content -->
</div><!-- /.ph-wrap -->

<?php include 'includes/ph_foot.php'; ?>
<script>
/* ── Live Clock ── */
(function tick() {
    document.getElementById('liveTime').textContent =
        new Date().toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    setTimeout(tick, 1000);
})();

/* ── Count-Up Animation ── */
function animateCount(el, to) {
    if (!el) return;
    const dur = 1000, start = performance.now();
    (function step(now) {
        const p = Math.min((now - start) / dur, 1);
        const ease = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(to * ease).toLocaleString('en-IN');
        if (p < 1) requestAnimationFrame(step);
    })(start);
}

/* ── Load Dashboard Data ── */
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const json = await fetch('../api/pharmacy/dashboard').then(r => r.json());
        if (!json.success) throw new Error(json.error || 'Failed');
        const { stats, charts, recent_alerts, top_products } = json.data;

        /* KPI strip */
        animateCount(document.getElementById('stat-total-products'), +stats.total_products);
        animateCount(document.getElementById('stat-low-stock'),      +stats.low_stock);
        animateCount(document.getElementById('stat-expiry-soon'),    +stats.expiry_soon);
        document.getElementById('stat-today-sales').textContent  = stats.today_sales_formatted;
        document.getElementById('stat-month-sales').textContent  = stats.month_sales_formatted;
        animateCount(document.getElementById('stat-pending-indents'),  +stats.pending_indents);
        animateCount(document.getElementById('stat-total-suppliers'),  +stats.total_suppliers);
        animateCount(document.getElementById('stat-total-customers'),  +stats.total_customers);

        /* Stock Donut */
        const sd = charts.stock_distribution;
        animateCount(document.getElementById('stock-in'),  +sd.in_stock);
        animateCount(document.getElementById('stock-low'), +sd.low_stock);
        animateCount(document.getElementById('stock-out'), +sd.out_of_stock);

        new Chart(document.getElementById('stockChart'), {
            type: 'doughnut',
            data: {
                labels: ['In Stock','Low Stock','Out of Stock'],
                datasets: [{ data: [sd.in_stock, sd.low_stock, sd.out_of_stock],
                    backgroundColor: ['#22C55E','#F59E0B','#EF4444'],
                    borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
            },
            options: { responsive:true, maintainAspectRatio:false, cutout:'78%',
                plugins: { legend:{display:false},
                    tooltip: { callbacks:{ label: c => ` ${c.label}: ${c.parsed.toLocaleString('en-IN')}` }}}}
        });

        /* Sales Line Chart */
        const map = {};
        charts.sales_history.forEach(h => map[h.date] = h.total);
        const labels = [], vals = [];
        for (let i = 6; i >= 0; i--) {
            const d = new Date(); d.setDate(d.getDate() - i);
            labels.push(d.toLocaleDateString('en-IN', { weekday:'short', day:'2-digit' }));
            vals.push(parseFloat(map[d.toISOString().split('T')[0]] || 0));
        }

        const ctx = document.getElementById('salesChart').getContext('2d');
        const grad = ctx.createLinearGradient(0,0,0,200);
        grad.addColorStop(0,'rgba(15,164,175,0.18)');
        grad.addColorStop(1,'rgba(15,164,175,0.01)');

        new Chart(ctx, {
            type: 'line',
            data: { labels, datasets: [{ label:'Sales (₹)', data:vals,
                borderColor:'#0FA4AF', backgroundColor: grad,
                fill:true, tension:0.45, borderWidth:2.5,
                pointBackgroundColor:'#0FA4AF', pointBorderColor:'#fff',
                pointBorderWidth:2.5, pointRadius:5, pointHoverRadius:7 }]},
            options: { responsive:true, maintainAspectRatio:false,
                plugins: { legend:{display:false}, tooltip:{
                    backgroundColor:'#0F172A', borderColor:'rgba(15,164,175,0.3)',
                    borderWidth:1, titleColor:'#94a3b8', bodyColor:'#f1f5f9',
                    bodyFont:{weight:700, size:13}, padding:12,
                    callbacks:{ label: c=>'  ₹'+c.parsed.y.toLocaleString('en-IN',{minimumFractionDigits:2}) }}},
                scales: {
                    y:{ 
                        beginAtZero:true, 
                        suggestedMax: 100, // Forces the chart to scale up to 20k even if sales are low
                        grid:{color:'rgba(226,232,240,0.8)', drawBorder:false},
                        ticks:{ 
                            color:'#94a3b8', 
                            font:{size:10}, 
                            maxTicksLimit: 7, // Prevents overcrowding of numbers on the axis
                            callback:v=>'₹'+(v>=1000?(v/1000).toFixed(0)+'k':v) 
                        }
                    },
                    x:{ grid:{display:false}, ticks:{color:'#94a3b8', font:{size:10}} }
                }}
        });

        /* Alerts Feed */
        const alertsEl = document.getElementById('alertsList');
        if (!recent_alerts.length) {
            alertsEl.innerHTML = `<div style="text-align:center;padding:48px 20px;">
                <i class="fas fa-shield-alt" style="font-size:2rem;color:#22C55E;margin-bottom:10px;display:block;"></i>
                <div style="font-weight:700;font-size:0.8rem;color:var(--ph-muted);">All Clear — No Critical Alerts</div>
            </div>`;
        } else {
            alertsEl.innerHTML = recent_alerts.map(a => {
                const isLow = a.alert_type === 'low';
                const color = isLow ? '#EF4444' : '#F59E0B';
                const bg    = isLow ? 'rgba(239,68,68,0.1)' : 'rgba(245,158,11,0.1)';
                const icon  = isLow ? 'fa-exclamation-circle' : 'fa-calendar-times';
                const badge = isLow ? 'LOW STOCK' : 'EXPIRING';
                const msg   = isLow
                    ? `Only ${a.quantity} units remaining`
                    : `Expires in ${Math.ceil((new Date(a.expiry_date)-new Date())/86400000)} days`;
                return `<div class="alert-row ${isLow?'low':'expiry'}">
                    <div class="alert-icon-box" style="background:${bg};color:${color};">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="alert-name">${a.product_name}</div>
                        <div class="alert-desc">${msg}</div>
                    </div>
                    <div class="alert-badge" style="background:${bg};color:${color};">${badge}</div>
                </div>`;
            }).join('');
        }

        /* Top Products */
        const topEl = document.getElementById('topProductsList');
        const maxQ  = top_products[0] ? +top_products[0].total_qty : 1;
        if (!top_products.length) {
            topEl.innerHTML = `<div style="text-align:center;padding:48px;color:var(--ph-muted);font-size:0.78rem;">No sales data this month yet.</div>`;
        } else {
            topEl.innerHTML = top_products.map((p,i) => {
                const pct  = (+p.total_qty / maxQ * 100).toFixed(1);
                const rc   = ['r1','r2','r3'][i] ?? 'rn';
                const rev  = parseFloat(p.total_revenue ?? p.total_amt ?? 0);
                return `<div class="product-row">
                    <div class="rank-num ${rc}">#${i+1}</div>
                    <div class="prod-bar-wrap">
                        <div class="prod-name">${p.product_name}</div>
                        <div class="prod-track"><div class="prod-fill" style="width:${pct}%;"></div></div>
                    </div>
                    <div class="prod-rev">₹${rev.toLocaleString('en-IN',{minimumFractionDigits:0})}</div>
                </div>`;
            }).join('');
        }

    } catch (e) { console.error('Dashboard Error:', e.message); }
});
</script>
