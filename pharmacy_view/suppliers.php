<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Supplier Directory — GM HMS';
$db = getDB();

// Fetch Next Supplier ID
$lastId = $db->query("SELECT supplier_id FROM ph_suppliers WHERE supplier_id LIKE 'SUP-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
$nextIdNum = $lastId ? (int)substr($lastId, 4) + 1 : 1;
$nextSupplierId = 'SUP-' . str_pad($nextIdNum, 5, '0', STR_PAD_LEFT);

// Bento Stats
$totalSuppliers = (int)$db->query("SELECT COUNT(*) FROM ph_suppliers")->fetchColumn();
$activeSuppliers = (int)$db->query("SELECT COUNT(*) FROM ph_suppliers WHERE status='active'")->fetchColumn();
$cityCount = (int)$db->query("SELECT COUNT(DISTINCT city) FROM ph_suppliers WHERE city != ''")->fetchColumn();

include 'includes/ph_head.php';
?>

<style>
/* ==========================================
   PREMIUM MEDICAL DESIGN SYSTEM (v2.0)
   ========================================== */
:root {
  --med-primary: #0D9488;
  --med-primary-dark: #0F766E;
  --med-primary-light: #CCFBF1;
  --med-bg: #F8FAFC;
  --med-surface: rgba(255, 255, 255, 0.8);
  --med-border: rgba(226, 232, 240, 0.8);
  --med-text-main: #1E293B;
  --med-text-muted: #64748B;
  --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
  --glass-border: 1px solid rgba(255, 255, 255, 0.4);
}

.ph-page-body { background: var(--med-bg); font-family: 'Plus Jakarta Sans', sans-serif; padding: 2rem !important; }

/* ===== BENTO KPI GRID ===== */
.bento-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2.5rem; }
.bento-card {
  background: var(--med-surface); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
  border: var(--glass-border); border-radius: 24px; padding: 1.75rem; box-shadow: var(--glass-shadow);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column;
}
.bento-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
.bento-icon { width: 52px; height: 52px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem; }
.bento-val { font-size: 2.5rem; font-weight: 800; color: var(--med-text-main); line-height: 1; }
.bento-lbl { font-size: 0.85rem; font-weight: 700; color: var(--med-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.5rem; }

/* ===== SMART TABLE ===== */
.smart-table-container { background: white; border-radius: 28px; box-shadow: var(--glass-shadow); border: var(--glass-border); overflow: hidden; }
.ph-table { border-collapse: separate; border-spacing: 0; width: 100%; }
.ph-table thead th { background: #F8FAFC; padding: 1.25rem 1.5rem; font-weight: 800; color: var(--med-text-muted); font-size: 0.75rem; text-transform: uppercase; border-bottom: 1px solid #F1F5F9; }
.ph-table tbody tr { transition: background 0.2s; border-bottom: 1px solid #F1F5F9; }
.ph-table tbody tr:hover { background: #F0FDFA; }
.ph-table td { padding: 1.25rem 1.5rem; vertical-align: middle; color: #334155; font-size: 0.9rem; }

.status-pill { padding: 6px 12px; border-radius: 50px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.02em; display: inline-flex; align-items: center; gap: 6px; }
.status-active { background: #DCFCE7; color: #15803D; }
.status-inactive { background: #F1F5F9; color: #64748B; }

/* Form Controls */
.ph-input { width: 100%; padding: 0.85rem 1rem; border-radius: 14px; border: 1px solid #E2E8F0; background: #F8FAFC; font-weight: 600; transition: all 0.2s ease; }
.ph-input:focus { border-color: var(--med-primary); box-shadow: 0 0 0 4px var(--med-primary-light); background: white; outline: none; }

.input-icon-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94A3B8; transition: color 0.2s; }
.input-icon-btn:hover { color: var(--med-primary); }

/* Error Message Visibility Fix */
.alert, .toast { 
    border-radius: 16px !important; 
    box-shadow: 0 10px 40px rgba(0,0,0,0.2) !important; 
    border: 2px solid rgba(0,0,0,0.1) !important;
    font-size: 1rem !important;
}
.alert-danger, .toast.bg-danger { 
    background: #FF3333 !important; 
    color: #FFFFFF !important; 
    font-weight: 800 !important; 
}
.alert-success, .toast.bg-success { 
    background: #00C853 !important; 
    color: #FFFFFF !important; 
    font-weight: 800 !important; 
}
.toast-body, .alert-body {
    padding: 1.25rem !important;
    color: #FFFFFF !important;
}
</style>

<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-5">
  <div>
    <h1 class="ph-page-title" style="font-weight: 900; letter-spacing: -1px; color: var(--med-text-main);">Supplier Ecosystem</h1>
    <p class="ph-page-subtitle" style="font-weight: 600; color: var(--med-text-muted);">Manage pharmaceutical distribution partners</p>
  </div>
  <button class="ph-btn" style="background: var(--med-primary); color: white; border-radius: 16px; padding: 0.85rem 1.75rem; font-weight: 700; box-shadow: 0 10px 20px rgba(13, 148, 136, 0.2);" onclick="openSupplierModal()">
    <i class="fas fa-plus-circle me-2"></i> Onboard Supplier
  </button>
</div>

<!-- Bento KPI Grid -->
<div class="bento-grid">
  <div class="bento-card">
    <div class="bento-icon" style="background: var(--med-primary-light); color: var(--med-primary-dark);"><i class="fas fa-handshake"></i></div>
    <div class="bento-val"><?= $totalSuppliers ?></div>
    <div class="bento-lbl">Total Partners</div>
  </div>
  <div class="bento-card">
    <div class="bento-icon" style="background: #DCFCE7; color: #15803D;"><i class="fas fa-check-shield"></i></div>
    <div class="bento-val"><?= $activeSuppliers ?></div>
    <div class="bento-lbl">Active & Verified</div>
  </div>
  <div class="bento-card">
    <div class="bento-icon" style="background: #E0F2FE; color: #0369A1;"><i class="fas fa-map-marker-alt"></i></div>
    <div class="bento-val"><?= $cityCount ?></div>
    <div class="bento-lbl">Cities Covered</div>
  </div>
</div>

<!-- Search Bar -->
<div class="d-flex gap-3 mb-4">
  <div class="flex-grow-1 position-relative">
    <i class="fas fa-search" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: #94A3B8;"></i>
    <input type="text" id="searchInput" class="ph-input" style="padding-left: 3.25rem; height: 56px; border-radius: 18px; border-color: transparent; box-shadow: var(--glass-shadow);" placeholder="Search by name, company, GST, or city...">
  </div>
  <select class="ph-select" id="statusFilter" style="width:180px; height: 56px; border-radius: 18px; border-color: transparent; box-shadow: var(--glass-shadow);">
    <option value="">All Status</option>
    <option value="active">Active</option>
    <option value="inactive">Inactive</option>
  </select>
  <button class="ph-btn ph-btn-outline" style="height: 56px; width: 56px; border-radius: 18px; box-shadow: var(--glass-shadow); border-color: transparent; background: white;" onclick="loadSuppliers()">
    <i class="fas fa-sync-alt"></i>
  </button>
</div>

<!-- Table -->
<div class="smart-table-container">
  <table class="ph-table" id="suppliersTable">
    <thead>
      <tr>
        <th>Partner Profile</th>
        <th>Corporate Details</th>
        <th>Contact Info</th>
        <th>Tax Identification</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody id="suppliersBody">
      <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-teal" role="status"></div></td></tr>
    </tbody>
  </table>
  <div class="p-4 d-flex justify-content-between align-items-center bg-light border-top">
    <span id="tableInfo" style="font-weight: 700; color: #94A3B8; font-size: 0.85rem;"></span>
    <div id="pager" class="ph-pagination"></div>
  </div>
</div>

</div><!-- ph-page-body -->
</div><!-- ph-content -->
</div><!-- ph-wrap -->

<!-- Add/Edit Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 28px; border: none; overflow: hidden;">
      <div class="modal-header p-4 border-0" style="background: #F8FAFC;">
        <h5 class="modal-title fw-bold" id="modalTitle">Supplier Registration</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="supplierForm" onsubmit="saveSupplier(event)">
        <div class="modal-body p-4">
          <input type="hidden" name="id" id="id">
          <input type="hidden" name="action" id="formAction" value="save">
          
          <h6 class="fw-bold mb-3" style="color: var(--med-primary-dark);"><i class="fas fa-building me-2"></i>Primary Details</h6>
          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <label class="ph-label">Supplier ID / Code <span class="text-danger">*</span></label>
              <div class="position-relative">
                <input type="text" class="ph-input" name="supplier_id" id="supplier_id" required readonly value="<?= $nextSupplierId ?>" style="background:#F1F5F9; cursor:not-allowed;">
                <button type="button" class="input-icon-btn" onclick="copyValue('supplier_id')" title="Copy ID"><i class="fas fa-copy"></i></button>
              </div>
              <div class="small text-muted mt-1 px-1">Auto-generated based on last sequence.</div>
            </div>
            <div class="col-md-6">
              <label class="ph-label">Account Password <span class="text-danger">*</span></label>
              <div class="position-relative">
                <input type="password" class="ph-input" name="password" id="password" required placeholder="Vendor portal access">
                <div class="d-flex gap-2" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">
                  <button type="button" class="input-icon-btn position-static" onclick="togglePass()" id="passBtn"><i class="fas fa-eye"></i></button>
                  <button type="button" class="input-icon-btn position-static" onclick="genPass()" title="Generate Password"><i class="fas fa-magic"></i></button>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <label class="ph-label">Supplier Name (Contact Person) <span class="text-danger">*</span></label>
              <input type="text" class="ph-input" name="supplier_name" id="supplier_name" required placeholder="Full name">
            </div>
            <div class="col-md-6">
              <label class="ph-label">Company Name <span class="text-danger">*</span></label>
              <input type="text" class="ph-input" name="company_name" id="company_name" required placeholder="Legal entity name">
            </div>
            
            <div class="col-md-6">
              <label class="ph-label">Phone Number <span class="text-danger">*</span></label>
              <div class="position-relative">
                <input type="text" class="ph-input" name="phone" id="phone" required maxlength="10" placeholder="10-digit mobile number" oninput="this.value = this.value.replace(/[^0-9]/g, ''); validatePhone(this)">
                <i id="phoneValid" class="fas fa-check-circle position-absolute" style="right: 15px; top: 50%; transform: translateY(-50%); color: #10B981; display: none;"></i>
              </div>
            </div>
            <div class="col-md-6">
              <label class="ph-label">Email Address</label>
              <input type="email" class="ph-input" name="email" id="email" placeholder="vendor@example.com">
            </div>
            <div class="col-md-6">
              <label class="ph-label">Account Status</label>
              <select class="ph-select" name="status" id="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="ph-label">Supplier Type</label>
              <select class="ph-select" name="supplier_type" id="supplier_type">
                <option value="">Select Type</option>
                <option value="Distributor">Distributor</option>
                <option value="Manufacturer">Manufacturer</option>
                <option value="Wholesaler">Wholesaler</option>
                <option value="Retailer">Retailer</option>
              </select>
            </div>
          </div>

          <h6 class="fw-bold mb-3" style="color: var(--med-primary-dark); border-top: 1px solid #E2E8F0; padding-top: 1.5rem;"><i class="fas fa-map-marked-alt me-2"></i>Address & Tax Details</h6>
          <div class="row g-4 mb-4">
            <div class="col-12">
              <label class="ph-label">Corporate Address</label>
              <textarea class="ph-textarea" name="address" id="address" rows="2" placeholder="Full street address, building, state..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="ph-label">City</label>
              <input type="text" class="ph-input" name="city" id="city" placeholder="City name">
            </div>
            <div class="col-md-6">
              <label class="ph-label">GST Number</label>
              <input type="text" class="ph-input" name="gst_no" id="gst_no" style="text-transform:uppercase;" placeholder="29XXXXX0000X1Z5">
            </div>
            <div class="col-md-6">
              <label class="ph-label">Company PAN</label>
              <input type="text" class="ph-input" name="company_pan" id="company_pan" style="text-transform:uppercase;" placeholder="ABCDE1234F">
            </div>
            <div class="col-md-6">
              <label class="ph-label">S.T No (Sales/Service Tax No)</label>
              <input type="text" class="ph-input" name="st_no" id="st_no" style="text-transform:uppercase;" placeholder="ST Number">
            </div>
            <div class="col-md-6 d-flex align-items-center mt-4">
              <div class="form-check form-switch" style="font-size: 1.1rem;">
                <input class="form-check-input" type="checkbox" role="switch" id="is_msme" name="is_msme" value="1">
                <label class="form-check-label fw-bold ms-2" for="is_msme" style="color: var(--med-text-main);">MSME Registered</label>
              </div>
            </div>
          </div>

          <h6 class="fw-bold mb-3" style="color: var(--med-primary-dark); border-top: 1px solid #E2E8F0; padding-top: 1.5rem;"><i class="fas fa-university me-2"></i>Bank & Account Details</h6>
          <div class="row g-4">
            <div class="col-md-6">
              <label class="ph-label">A/C Holder Name</label>
              <input type="text" class="ph-input" name="account_holder" id="account_holder" placeholder="Name on bank account">
            </div>
            <div class="col-md-6">
              <label class="ph-label">A/C Number</label>
              <input type="text" class="ph-input" name="account_number" id="account_number" placeholder="Account Number">
            </div>
            <div class="col-md-6">
              <label class="ph-label">Bank Name</label>
              <input type="text" class="ph-input" name="bank_name" id="bank_name" placeholder="e.g. HDFC Bank">
            </div>
            <div class="col-md-6">
              <label class="ph-label">Branch Name</label>
              <input type="text" class="ph-input" name="branch_name" id="branch_name" placeholder="Branch location">
            </div>
            <div class="col-md-6">
              <label class="ph-label">IFSC Code</label>
              <input type="text" class="ph-input" name="ifsc_code" id="ifsc_code" style="text-transform:uppercase;" placeholder="HDFC0001234">
            </div>
            <div class="col-md-6">
              <label class="ph-label">Credit Unit (Days / Limit)</label>
              <input type="text" class="ph-input" name="credit_unit" id="credit_unit" placeholder="e.g. 30 Days or 1,00,000">
            </div>
            <div class="col-12">
              <label class="ph-label">Bank Address</label>
              <textarea class="ph-textarea" name="bank_address" id="bank_address" rows="2" placeholder="Bank branch address"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer p-4 border-0" style="background: #F8FAFC;">
          <button type="button" class="ph-btn ph-btn-outline border-0" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="ph-btn px-5" style="background: var(--med-primary); color: white; border-radius: 14px; font-weight: 700; box-shadow: 0 10px 20px rgba(13, 148, 136, 0.2);">
            <i class="fas fa-check-circle me-2"></i> Save Partner
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

</div>

<!-- Advanced Details Center Card Modal -->
<div class="modal fade" id="supplierViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" style="border-radius: 28px; border: none; overflow: hidden; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 20px 50px rgba(0,0,0,0.15);">
      <div class="modal-header p-4 border-0" style="background: linear-gradient(135deg, #F8FAFC, #F1F5F9);">
        <div>
          <h4 class="fw-bold mb-1" id="ocSupplierName" style="color: var(--med-text-main); font-size: 1.5rem; letter-spacing: -0.5px;">Supplier Name</h4>
          <div class="d-flex align-items-center gap-2 mt-2">
            <span id="ocSupplierId" class="badge px-3 py-2" style="background: #E2E8F0; color: #475569; font-weight: 800; border-radius: 8px;">ID</span>
            <span id="ocStatus" class="status-pill px-3 py-2" style="border-radius: 8px;">Status</span>
            <span id="ocType" class="badge px-3 py-2" style="background: var(--med-primary-light); color: var(--med-primary-dark); font-weight: 800; border-radius: 8px;">Type</span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color: white; border-radius: 50%; padding: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05);"></button>
      </div>
      
      <div class="modal-body p-4 p-md-5">
        <div class="row g-4">
          
          <!-- Column 1: Contact -->
          <div class="col-md-4">
            <div class="p-4 h-100" style="background: white; border-radius: 20px; box-shadow: var(--glass-shadow); border: 1px solid #F1F5F9;">
              <h6 class="fw-bold mb-4" style="color: var(--med-primary-dark); font-size: 0.95rem; letter-spacing: 0.5px; text-transform: uppercase;"><i class="fas fa-id-card me-2"></i>Contact Info</h6>
              <div class="mb-3">
                <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">CONTACT PERSON</div>
                <div class="fw-semibold fs-5" id="ocContactName" style="color: var(--med-text-main);">—</div>
              </div>
              <div class="mb-3">
                <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">PHONE</div>
                <div class="fw-semibold"><i class="fas fa-phone-alt me-2 text-teal-500"></i><span id="ocPhone">—</span></div>
              </div>
              <div>
                <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">EMAIL</div>
                <div class="fw-semibold"><i class="fas fa-envelope me-2 text-slate-400"></i><span id="ocEmail">—</span></div>
              </div>
            </div>
          </div>

          <!-- Column 2: Tax & Location -->
          <div class="col-md-4">
            <div class="p-4 h-100" style="background: white; border-radius: 20px; box-shadow: var(--glass-shadow); border: 1px solid #F1F5F9;">
              <h6 class="fw-bold mb-4" style="color: var(--med-primary-dark); font-size: 0.95rem; letter-spacing: 0.5px; text-transform: uppercase;"><i class="fas fa-map-marked-alt me-2"></i>Tax & Location</h6>
              <div class="row g-3 mb-3">
                <div class="col-6">
                  <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">GST NUMBER</div>
                  <div class="fw-bold text-slate-700" id="ocGst" style="font-family: monospace;">—</div>
                </div>
                <div class="col-6">
                  <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">COMPANY PAN</div>
                  <div class="fw-bold text-slate-700" id="ocPan" style="font-family: monospace;">—</div>
                </div>
                <div class="col-6">
                  <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">S.T NO</div>
                  <div class="fw-bold text-slate-700" id="ocStNo" style="font-family: monospace;">—</div>
                </div>
                <div class="col-6" id="ocMsmeContainer" style="display:none;">
                  <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">MSME</div>
                  <div class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Registered</div>
                </div>
              </div>
              <div class="pt-3 border-top">
                <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">CORPORATE ADDRESS</div>
                <div class="fw-semibold" id="ocAddress" style="color: var(--med-text-main); font-size: 0.9rem;">—</div>
                <div class="small text-muted mt-1 fw-bold"><i class="fas fa-map-marker-alt me-1 text-teal-500"></i><span id="ocCity">—</span></div>
              </div>
            </div>
          </div>

          <!-- Column 3: Banking Details -->
          <div class="col-md-4">
            <div class="p-4 h-100" style="background: white; border-radius: 20px; box-shadow: var(--glass-shadow); border: 1px solid #F1F5F9;">
              <h6 class="fw-bold mb-4" style="color: var(--med-primary-dark); font-size: 0.95rem; letter-spacing: 0.5px; text-transform: uppercase;"><i class="fas fa-university me-2"></i>Banking Info</h6>
              <div class="row g-3">
                <div class="col-12 mb-1">
                  <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">A/C HOLDER & NUMBER</div>
                  <div class="fw-bold" id="ocAcName" style="color: var(--med-text-main);">—</div>
                  <div class="fw-bold text-slate-600" id="ocAcNo" style="font-family: monospace; letter-spacing: 1px;">—</div>
                </div>
                <div class="col-6">
                  <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">BANK</div>
                  <div class="fw-semibold" id="ocBankName" style="font-size: 0.9rem;">—</div>
                </div>
                <div class="col-6">
                  <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">BRANCH</div>
                  <div class="fw-semibold" id="ocBranch" style="font-size: 0.9rem;">—</div>
                </div>
                <div class="col-12">
                  <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">IFSC & ADDRESS</div>
                  <div class="fw-bold text-teal-600 mb-1" id="ocIfsc" style="font-family: monospace;">—</div>
                  <div class="fw-semibold text-slate-500" id="ocBankAddress" style="font-size: 0.85rem;">—</div>
                </div>
              </div>
              <div class="p-3 mt-3" style="background: #F8FAFC; border-radius: 12px; border: 1px dashed #CBD5E1;">
                <div class="small text-muted fw-bold mb-1" style="font-size: 0.75rem;">CREDIT UNIT / LIMIT</div>
                <div class="fw-bold" id="ocCreditUnit" style="color: var(--med-primary-dark); font-size: 1.1rem;">—</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>

<script>
const NEXT_ID = '<?= $nextSupplierId ?>';
let allSuppliers = [];
let currentPage = 1;
const PER_PAGE = 10;
const modal = new bootstrap.Modal(document.getElementById('supplierModal'));

document.addEventListener('DOMContentLoaded', () => {
    loadSuppliers();
    document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; renderTable(); });
    document.getElementById('statusFilter').addEventListener('change', () => { currentPage = 1; renderTable(); });
});

function togglePass() {
    const p = document.getElementById('password');
    const btn = document.getElementById('passBtn').querySelector('i');
    if (p.type === 'password') { p.type = 'text'; btn.classList.replace('fa-eye', 'fa-eye-slash'); }
    else { p.type = 'password'; btn.classList.replace('fa-eye-slash', 'fa-eye'); }
}

function genPass() {
    const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%";
    let pass = "";
    for (let i = 0; i < 10; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('password').value = pass;
    document.getElementById('password').type = 'text';
    document.getElementById('passBtn').querySelector('i').classList.replace('fa-eye', 'fa-eye-slash');
}

function copyValue(id) {
    const val = document.getElementById(id).value;
    navigator.clipboard.writeText(val);
    PH.toast('Copied: ' + val, 'success');
}

function validatePhone(input) {
    const check = document.getElementById('phoneValid');
    if (input.value.length === 10) {
        check.style.display = 'block';
        input.style.borderColor = '#10B981';
    } else {
        check.style.display = 'none';
        input.style.borderColor = '';
    }
}

async function loadSuppliers() {
    try {
        const res = await phGet(API_BASE + 'pharmacy/suppliers');
        if (res.success) { 
            allSuppliers = res.data; 
            renderTable(); 
        } else {
            PH.error(res.message);
        }
    } catch (e) { 
        PH.error('Network error loading suppliers'); 
        console.error(e);
    }
}

function renderTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const sf = document.getElementById('statusFilter').value;
    
    let filtered = allSuppliers.filter(s => {
        if (q && !((s.supplier_name||'').toLowerCase().includes(q) || (s.company_name||'').toLowerCase().includes(q) || (s.gst_no||'').toLowerCase().includes(q) || (s.city||'').toLowerCase().includes(q))) return false;
        if (sf && s.status !== sf) return false;
        return true;
    });

    const pager = phPaginate(filtered, currentPage, PER_PAGE);
    document.getElementById('tableInfo').textContent = `Showing ${pager.items.length} of ${filtered.length} partners`;
    
    let html = '';
    if (!pager.items.length) {
        html = `<tr><td colspan="6" class="text-center py-5 text-muted">No partners found.</td></tr>`;
    } else {
        pager.items.forEach(s => {
            html += `
            <tr onclick='viewSupplier(${JSON.stringify(s).replace(/'/g, "&apos;")})' style="cursor: pointer;">
                <td>
                    <div class="fw-bold" style="color:var(--med-primary-dark); font-size:1rem;">${s.company_name}</div>
                    <div style="font-size:0.75rem; color:#94A3B8; font-weight:700;">ID: ${s.supplier_id}</div>
                </td>
                <td>
                    <div class="fw-semibold text-slate-700">${s.supplier_name}</div>
                    <div class="small text-muted"><i class="fas fa-map-marker-alt me-1"></i>${s.city || '—'}</div>
                </td>
                <td>
                    <div class="small fw-bold text-slate-600"><i class="fas fa-phone-alt me-2 text-teal-500"></i>${s.phone}</div>
                    <div class="small text-muted mt-1"><i class="fas fa-envelope me-2"></i>${s.email || '—'}</div>
                </td>
                <td>
                    <div class="small fw-bold text-slate-700">GST: ${s.gst_no || '—'}</div>
                    <div class="small text-muted">PAN: ${s.company_pan || '—'}</div>
                </td>
                <td>
                    <span class="status-pill ${s.status === 'active' ? 'status-active' : 'status-inactive'}">
                        <i class="fas ${s.status === 'active' ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                        ${s.status}
                    </span>
                </td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="ph-btn ph-btn-sm" style="background:#F1F5F9; color:#475569; width:40px; height:40px; border-radius:12px;" onclick='event.stopPropagation(); editSupplier(${JSON.stringify(s).replace(/'/g, "&apos;")})' title="Edit Profile"><i class="fas fa-edit"></i></button>
                        <button class="ph-btn ph-btn-sm" style="background:#FEF2F2; color:#EF4444; width:40px; height:40px; border-radius:12px;" onclick="event.stopPropagation(); deleteSupplier(${s.id})" title="Remove"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </td>
            </tr>`;
        });
    }
    document.getElementById('suppliersBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; renderTable(); });
}

function openSupplierModal() {
    document.getElementById('supplierForm').reset();
    document.getElementById('id').value = '';
    document.getElementById('supplier_id').value = NEXT_ID;
    document.getElementById('phoneValid').style.display = 'none';
    document.getElementById('phone').style.borderColor = '';
    document.getElementById('formAction').value = 'save';
    document.getElementById('modalTitle').textContent = 'Supplier Registration';
    modal.show();
}

function editSupplier(s) {
    document.getElementById('supplierForm').reset();
    document.getElementById('id').value = s.id;
    document.getElementById('formAction').value = 'save';
    document.getElementById('modalTitle').textContent = 'Update Profile';
    ['supplier_id', 'supplier_name', 'company_name', 'phone', 'email', 'gst_no', 'company_pan', 'address', 'city', 'status', 'password', 'account_number', 'account_holder', 'bank_name', 'branch_name', 'ifsc_code', 'bank_address', 'credit_unit', 'supplier_type', 'st_no'].forEach(f => {
        if(document.getElementById(f)) document.getElementById(f).value = s[f] || '';
    });
    if(document.getElementById('is_msme')) document.getElementById('is_msme').checked = (s.is_msme == 1 || s.is_msme == '1');
    validatePhone(document.getElementById('phone'));
    modal.show();
}

function viewSupplier(s) {
    document.getElementById('ocSupplierName').textContent = s.company_name || '—';
    document.getElementById('ocSupplierId').textContent = s.supplier_id || '—';
    
    // Status
    const statusEl = document.getElementById('ocStatus');
    statusEl.className = 'status-pill ' + (s.status === 'active' ? 'status-active' : 'status-inactive');
    statusEl.innerHTML = `<i class="fas ${s.status === 'active' ? 'fa-check-circle' : 'fa-times-circle'}"></i> ${s.status}`;
    
    // Type
    const typeEl = document.getElementById('ocType');
    if(s.supplier_type) {
        typeEl.style.display = 'inline-block';
        typeEl.textContent = s.supplier_type;
    } else {
        typeEl.style.display = 'none';
    }

    // Contact
    document.getElementById('ocContactName').textContent = s.supplier_name || '—';
    document.getElementById('ocPhone').textContent = s.phone || '—';
    document.getElementById('ocEmail').textContent = s.email || '—';

    // Address & Tax
    document.getElementById('ocAddress').textContent = s.address || '—';
    document.getElementById('ocCity').textContent = s.city || '—';
    document.getElementById('ocGst').textContent = s.gst_no || '—';
    document.getElementById('ocPan').textContent = s.company_pan || '—';
    document.getElementById('ocStNo').textContent = s.st_no || '—';
    document.getElementById('ocMsmeContainer').style.display = (s.is_msme == 1 || s.is_msme == '1') ? 'block' : 'none';

    // Bank
    document.getElementById('ocAcName').textContent = s.account_holder || '—';
    document.getElementById('ocAcNo').textContent = s.account_number || '—';
    document.getElementById('ocBankName').textContent = s.bank_name || '—';
    document.getElementById('ocBranch').textContent = s.branch_name || '—';
    document.getElementById('ocIfsc').textContent = s.ifsc_code || '—';
    document.getElementById('ocBankAddress').textContent = s.bank_address || '—';
    document.getElementById('ocCreditUnit').textContent = s.credit_unit || '—';

    // Show Center Modal
    const viewModal = new bootstrap.Modal(document.getElementById('supplierViewModal'));
    viewModal.show();
}

async function saveSupplier(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    PH.loading('Processing...');
    try {
        const response = await fetch(API_BASE + 'pharmacy/suppliers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const res = await response.json();
        if (res.success) { 
            PH.success(res.message); 
            modal.hide(); 
            location.reload(); 
        } else {
            PH.error(res.error || res.message || 'Server error');
            console.error('API Error:', res);
        }
    } catch (err) { 
        PH.error('Failed to save supplier'); 
        console.error(err);
    }
}

function deleteSupplier(id) {
    PH.confirm('Remove Partner?', 'This action cannot be undone.', async () => {
        try {
            const res = await fetch(API_BASE + 'pharmacy/suppliers/' + id, { method: 'DELETE' }).then(r => r.json());
            if (res.success) { 
                PH.success('Partner removed'); 
                loadSuppliers(); 
            } else {
                PH.error(res.message);
            }
        } catch (e) { 
            PH.error('Delete failed'); 
            console.error(e);
        }
    });
}
</script>
