<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header("Location: ../doctor_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css">
</head>
<body>
    <div class="doctor-layout">
        <!-- Sidebar -->
        <?php include 'includes/doctor_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="doctor-main-content">
            <!-- Top Navbar -->
            <?php include 'includes/doctor_navbar.php'; ?>
            
            <!-- Page Content -->
            <div class="doctor-content-elite" style="padding: 2rem; background: #f8fafc; min-height: 100vh;">
                <!-- Page Header: Elite Style -->
                <div class="header-glass-mini" style="background: transparent; padding: 2rem; color: #1e293b; display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h1 class="main-page-title">
                            <i class="fas fa-id-card-clip"></i>
                            Patient Master List
                        </h1>
                        <p style="font-size: 0.9rem; opacity: 0.7; margin-top: 4px; display: flex; align-items: center; gap: 8px;">
                            <span style="width: 8px; height: 8px; background: #10B981; border-radius: 50%; display: inline-block;"></span>
                            Manage your clinical follow-ups and records
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button onclick="toggleView('table')" id="btn-table-view" class="btn btn-primary" style="background: #0FA4AF; border: none; border-radius: 10px;">
                            <i class="fas fa-table"></i>
                        </button>
                        <button onclick="toggleView('cards')" id="btn-cards-view" class="btn btn-outline" style="border-radius: 10px; border-color: rgba(255,255,255,0.2); color: white;">
                            <i class="fas fa-th-large"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Filters Card: Elite Design -->
                <div class="card mb-4 elite-filter-card" style="border: none; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden;">
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="filter-group-elite" style="display: flex; gap: 1.5rem; align-items: flex-end;">
                            <div style="flex: 2;">
                                <label style="font-size: 0.65rem; font-weight: 800; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Quick Search</label>
                                <div style="position: relative;">
                                    <i class="fas fa-search" style="position: absolute; left: 15px; top: 12px; color: #94A3B8;"></i>
                                    <input type="text" id="search-patient" class="form-control" placeholder="Name, ID, or Phone..." style="padding-left: 40px; border-radius: 12px; height: 44px; border: 2px solid #F1F5F9;">
                                </div>
                            </div>
                            <div style="flex: 1;">
                                <label style="font-size: 0.65rem; font-weight: 800; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Filter Status</label>
                                <select id="filter-status" class="form-control form-select" style="border-radius: 12px; height: 44px; border: 2px solid #F1F5F9;">
                                    <option value="">All Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Discharged">Discharged</option>
                                    <option value="Follow-up">Follow-up</option>
                                </select>
                            </div>
                            <div style="flex: 1; display: flex; gap: 0.5rem;">
                                <button onclick="applyFilters()" class="btn btn-primary" style="flex: 2; height: 44px; border-radius: 12px; background: #056674; border: none; font-weight: 700;">
                                    <i class="fas fa-filter"></i> Apply
                                </button>
                                <button onclick="Modal.show('advanced-search-modal')" class="btn btn-outline" style="flex: 1; height: 44px; border-radius: 12px; border: 2px solid #E2E8F0; color: #64748B;" title="Advanced Search">
                                    <i class="fas fa-sliders-h"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="d-grid grid-cols-4 gap-3 mb-4">
                    <div class="stat-card-elite" style="background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #F1F5F9; display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #F0F9FF; color: #0EA5E9; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <p style="font-size: 0.75rem; color: #64748B; font-weight: 700; text-transform: uppercase;">Total</p>
                            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800; color: #1E293B;" id="stat-total">0</h2>
                        </div>
                    </div>
                    <div class="stat-card-elite" style="background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #F1F5F9; display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #F0FDF4; color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <p style="font-size: 0.75rem; color: #64748B; font-weight: 700; text-transform: uppercase;">Active</p>
                            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800; color: #1E293B;" id="stat-active">0</h2>
                        </div>
                    </div>
                    <div class="stat-card-elite" style="background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #F1F5F9; display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #FFFBEB; color: #F59E0B; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                            <i class="fas fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <p style="font-size: 0.75rem; color: #64748B; font-weight: 700; text-transform: uppercase;">Follow-up</p>
                            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800; color: #1E293B;" id="stat-followup">0</h2>
                        </div>
                    </div>
                    <div class="stat-card-elite" style="background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #F1F5F9; display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #FEF2F2; color: #EF4444; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                            <i class="fas fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <p style="font-size: 0.75rem; color: #64748B; font-weight: 700; text-transform: uppercase;">Review</p>
                            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800; color: #1E293B;" id="stat-critical">0</h2>
                        </div>
                    </div>
                </div>
                
                <!-- Table View -->
                <div id="table-view" class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i>
                            Patient List
                        </div>
                        <button onclick="exportPatients()" class="btn btn-sm btn-outline">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="patients-table" class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Name</th>
                                        <th>Age/Gender</th>
                                        <th>Blood Group</th>
                                        <th>Last Visit</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="patients-table-body">
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Cards View -->
                <div id="cards-view" class="d-grid grid-cols-3 gap-3" style="display: none;">
                    <!-- Cards loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Patient Details Modal -->
    <div id="patient-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2 id="modal-patient-name">Patient Details</h2>
                <button onclick="Modal.hide('patient-modal')" class="btn btn-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modal-patient-details">
                <!-- Patient details loaded here -->
            </div>
        </div>
    </div>

    <!-- Advanced Search Modal -->
    <div id="advanced-search-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2><i class="fas fa-search-plus" style="color: #0FA4AF; margin-right: 10px;"></i>Advanced Search</h2>
                <button onclick="Modal.hide('advanced-search-modal')" class="btn btn-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="advanced-search-form" onsubmit="event.preventDefault(); performAdvancedSearch();">
                    <div class="d-grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Patient ID</label>
                            <input type="text" id="adv-patient-id" class="form-control" placeholder="e.g. PID-2023..." style="border-radius: 8px;">
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Phone Number</label>
                            <input type="text" id="adv-phone" class="form-control" placeholder="10-digit number" style="border-radius: 8px;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Patient Name</label>
                        <input type="text" id="adv-name" class="form-control" placeholder="First or Last Name" style="border-radius: 8px;">
                    </div>
                    
                    <div class="d-grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">City</label>
                            <input type="text" id="adv-city" class="form-control" placeholder="City Name" style="border-radius: 8px;">
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Gender</label>
                            <select id="adv-gender" class="form-control form-select" style="border-radius: 8px;">
                                <option value="">Any</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Registration Date Range</label>
                        <div class="d-flex gap-2">
                            <input type="date" id="adv-date-from" class="form-control" style="border-radius: 8px;">
                            <span style="align-self: center; color: #94A3B8;">to</span>
                            <input type="date" id="adv-date-to" class="form-control" style="border-radius: 8px;">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Status</label>
                        <div class="d-flex gap-3">
                            <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                                <input type="radio" name="adv-status" value="" checked> Any
                            </label>
                            <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                                <input type="radio" name="adv-status" value="Active"> Active
                            </label>
                            <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                                <input type="radio" name="adv-status" value="Inactive"> Inactive
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" onclick="Modal.hide('advanced-search-modal')" class="btn btn-outline" style="flex: 1; border-radius: 10px;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="flex: 2; border-radius: 10px; background: #0FA4AF; border: none;">
                            <i class="fas fa-search"></i> Search Patients
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/doctor_utils.js"></script>
    <script>
        const CURRENT_DOCTOR_ID = "<?php echo $_SESSION['user_id']; ?>";
    </script>
    <script src="assets/js/mypatient.js"></script>
</body>
</html>
