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
    <title>IPD Patients - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css">
    <style>
        .ward-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .days-badge { padding: 2px 8px; border-radius: 4px; background: #E6FAFA; color: #056674; font-weight: 700; font-size: 0.75rem; }
        .patient-avatar-mini { width: 32px; height: 32px; border-radius: 50%; background: #10b981; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; }
    </style>
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
            <div class="doctor-content">
                <!-- Page Header -->
                <div class="d-flex" style="justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h1 class="main-page-title">
                            <i class="fas fa-procedures"></i>
                            In-Patient Management (IPD)
                        </h1>
                        <p style="color: var(--gray-500);">Monitor admitted patients, bed status, and recovery progress</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button onclick="refreshData()" class="btn btn-outline">
                            <i class="fas fa-sync-alt"></i> Sync Data
                        </button>
                    </div>
                </div>
                
                <!-- KPI Section -->
                <div class="d-grid grid-cols-4 gap-3 mb-4">
                    <div class="card card-success">
                        <div class="card-body">
                            <div class="d-flex" style="justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem;">Active Admissions</div>
                                    <div style="font-size: 2rem; font-weight: 700; color: #10b981;" id="kpi-active">0</div>
                                </div>
                                <i class="fas fa-hospital-user" style="font-size: 2.5rem; color: #10b981; opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card card-info">
                        <div class="card-body">
                            <div class="d-flex" style="justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem;">Total Today</div>
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--primary-blue);" id="kpi-total">0</div>
                                </div>
                                <i class="fas fa-bed" style="font-size: 2.5rem; color: var(--primary-blue); opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Empty slots for alignment or future KPIs -->
                    <div class="card" style="visibility: hidden;"></div>
                    <div class="card" style="visibility: hidden;"></div>
                </div>

                <!-- Admissions Table -->
                <div class="card">
                    <div class="card-header d-flex" style="justify-content: space-between; align-items: center;">
                        <div class="card-title">
                            <i class="fas fa-user-shield"></i>
                            Admitted Patients List
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" id="search-patients" class="form-control" placeholder="Search admitted patients...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="ipd-table" class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Patient Details</th>
                                        <th>Bed / Ward</th>
                                        <th>Admission Date</th>
                                        <th>Duration</th>
                                        <th>Financial Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ipd-table-body">
                                    <!-- Data loaded via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/doctor_utils.js"></script>
    <script src="assets/js/ipd_patients.js"></script>
</body>
</html>
