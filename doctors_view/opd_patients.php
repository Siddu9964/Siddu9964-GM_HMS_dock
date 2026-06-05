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
    <title>OPD Patients - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css">
    <style>
        .opd-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-scheduled { background: #e0f2fe; color: #0369a1; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-cancelled { background: #fee2e2; color: #b91c1c; }
        .patient-avatar-mini { width: 32px; height: 32px; border-radius: 50%; background: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; }
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
                            <i class="fas fa-calendar-check"></i>
                            OPD Appointments
                        </h1>
                        <p style="color: var(--gray-500);">Manage your outpatient visits and clinical encounters</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button onclick="refreshData()" class="btn btn-outline">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- KPI Section -->
                <div class="d-grid grid-cols-4 gap-3 mb-4">
                    <div class="card card-info">
                        <div class="card-body">
                            <div class="d-flex" style="justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem;">Total Today</div>
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--primary-blue);" id="kpi-total">0</div>
                                </div>
                                <i class="fas fa-user-clock" style="font-size: 2.5rem; color: var(--primary-blue); opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card card-warning">
                        <div class="card-body">
                            <div class="d-flex" style="justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem;">Scheduled</div>
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--status-warning);" id="kpi-scheduled">0</div>
                                </div>
                                <i class="fas fa-clock" style="font-size: 2.5rem; color: var(--status-warning); opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card card-success">
                        <div class="card-body">
                            <div class="d-flex" style="justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem;">Completed</div>
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--status-success);" id="kpi-completed">0</div>
                                </div>
                                <i class="fas fa-check-circle" style="font-size: 2.5rem; color: var(--status-success); opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card card-danger">
                        <div class="card-body">
                            <div class="d-flex" style="justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem;">Cancelled</div>
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--status-danger);" id="kpi-cancelled">0</div>
                                </div>
                                <i class="fas fa-times-circle" style="font-size: 2.5rem; color: var(--status-danger); opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="card">
                    <div class="card-header d-flex" style="justify-content: space-between; align-items: center;">
                        <div class="card-title">
                            <i class="fas fa-list"></i>
                            Encounters List
                        </div>
                        <div class="d-flex gap-2">
                            <select id="status-filter" class="form-control" onchange="applyFilters()">
                                <option value="">All Status</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                            <input type="text" id="search-patients" class="form-control" placeholder="Search patients...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="opd-table" class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>ID</th>
                                        <th>Time</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="opd-table-body">
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
    <script>
        const CURRENT_DOCTOR_ID = "<?php echo $_SESSION['user_id']; ?>";
    </script>
    <script src="assets/js/opd_patients.js"></script>
</body>
</html>
