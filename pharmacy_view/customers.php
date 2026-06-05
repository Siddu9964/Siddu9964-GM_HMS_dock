<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Customers';
include 'includes/ph_head.php';
?>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">Customer Management</h1>
    <p class="ph-page-subtitle">Manage retail customers and store accounts</p>
  </div>
  <button class="ph-btn ph-btn-primary" onclick="openCustomerModal()"><i class="fas fa-plus"></i> Add Customer</button>
</div>

<!-- Search & Filter -->
<div class="ph-searchbar">
  <div class="ph-search-input-wrap">
    <i class="fas fa-search"></i>
    <input type="text" id="searchInput" placeholder="Search customer name, phone or email...">
  </div>
  <button class="ph-btn ph-btn-outline" onclick="loadCustomers()"><i class="fas fa-sync-alt"></i></button>
</div>

<!-- Customers Table -->
<div class="ph-card">
  <div class="ph-table-wrap">
    <table class="ph-table" id="customersTable">
      <thead>
        <tr>
          <th>Customer ID</th>
          <th>Customer Name</th>
          <th>Contact Number</th>
          <th>Email Address</th>
          <th>Credit Limit</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="customersBody">
        <tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="ph-card-body pt-0 pb-3">
    <div id="pager" class="ph-pagination justify-content-end"></div>
  </div>
</div>

</div><!-- body -->
</div><!-- content -->
</div><!-- wrap -->

<!-- Add/Edit Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="customerForm" onsubmit="saveCustomer(event)">
        <div class="modal-body">
          <input type="hidden" name="id" id="id">
          <input type="hidden" name="action" id="formAction" value="save">
          
          <div class="row g-3">
            <div class="col-12">
              <label class="ph-label">Customer Name <span class="req">*</span></label>
              <input type="text" class="ph-input" name="customer_name" id="customer_name" required>
            </div>
            
            <div class="col-md-6">
              <label class="ph-label">Phone Number <span class="req">*</span></label>
              <input type="text" class="ph-input" name="phone" id="phone" required>
            </div>
            <div class="col-md-6">
              <label class="ph-label">Email Address</label>
              <input type="email" class="ph-input" name="email" id="email">
            </div>

            <div class="col-md-6">
              <label class="ph-label">Credit Limit (<?= CURRENCY ?>)</label>
              <input type="number" class="ph-input" name="credit_limit" id="credit_limit" value="0.00" step="0.01">
            </div>

            <div class="col-12">
              <label class="ph-label">Address</label>
              <textarea class="ph-textarea" name="address" id="address" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ph-btn ph-btn-outline" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="ph-btn ph-btn-primary"><i class="fas fa-save"></i> Save Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>
<script>
let allCustomers = [];
let currentPage = 1;
const PER_PAGE = 10;
const modal = new bootstrap.Modal(document.getElementById('customerModal'));

document.addEventListener('DOMContentLoaded', () => {
    loadCustomers();
    document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; renderTable(); });
});

async function loadCustomers() {
    try {
        const res = await phGet(API_BASE + 'pharmacy/customers');
        if (res.success) {
            allCustomers = res.data;
            renderTable();
        } else {
            PH.error(res.message);
        }
    } catch (e) {
        PH.error('Network error loading customers');
    }
}

function renderTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    
    let filtered = allCustomers;
    if (q) {
        filtered = filtered.filter(c => 
            (c.customer_name||'').toLowerCase().includes(q) || 
            (c.phone||'').toLowerCase().includes(q) ||
            (c.email||'').toLowerCase().includes(q)
        );
    }
    
    const pager = phPaginate(filtered, currentPage, PER_PAGE);
    
    let html = '';
    if (pager.items.length === 0) {
        html = `<tr><td colspan="6" class="text-center py-4 text-muted">No customers found.</td></tr>`;
    } else {
        pager.items.forEach(c => {
            html += `
            <tr>
                <td><span class="badge-muted ph-badge fw-bold" style="font-family:monospace;">${c.customer_id}</span></td>
                <td><div class="fw-bold">${c.customer_name}</div></td>
                <td>${c.phone || '—'}</td>
                <td>${c.email || '—'}</td>
                <td class="text-success fw-bold">${fmt.currency(c.credit_limit)}</td>
                <td class="text-end">
                    <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon me-1" onclick='editCustomer(${JSON.stringify(c).replace(/'/g, "&apos;")})' title="Edit"><i class="fas fa-edit"></i></button>
                    <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon text-danger" onclick="del(${c.id})" title="Delete"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });
    }
    
    document.getElementById('customersBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, (p) => {
        currentPage = p;
        renderTable();
    });
}

function openCustomerModal() {
    document.getElementById('customerForm').reset();
    document.getElementById('id').value = '';
    document.getElementById('formAction').value = 'save';
    document.getElementById('modalTitle').textContent = 'Add New Customer';
    modal.show();
}

function editCustomer(c) {
    document.getElementById('customerForm').reset();
    document.getElementById('id').value = c.id;
    document.getElementById('formAction').value = 'save';
    document.getElementById('modalTitle').textContent = 'Edit Customer';
    
    ['customer_name', 'phone', 'email', 'credit_limit', 'address'].forEach(f => {
        if(document.getElementById(f)) document.getElementById(f).value = c[f] || '';
    });
    
    modal.show();
}

async function saveCustomer(e) {
    e.preventDefault();
    const form = e.target;
    const fd = new FormData(form);
    const data = Object.fromEntries(fd.entries());
    
    PH.loading('Saving customer...');
    try {
        const res = await fetch(API_BASE + 'pharmacy/customers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());

        if (res.success) {
            PH.success(res.message);
            modal.hide();
            loadCustomers();
        } else {
            PH.error(res.message);
        }
    } catch (err) {
        PH.error('Failed to save customer');
    }
}

function del(id) {
    PH.confirm('Delete Customer?', 'This will permanently remove the record.', async () => {
        try {
            const res = await fetch(API_BASE + 'pharmacy/customers/' + id, { method: 'DELETE' }).then(r => r.json());
            if (res.success) {
                PH.success('Customer deleted');
                loadCustomers();
            } else {
                PH.error(res.message);
            }
        } catch (e) {
            PH.error('Failed to delete');
        }
    });
}
</script>
