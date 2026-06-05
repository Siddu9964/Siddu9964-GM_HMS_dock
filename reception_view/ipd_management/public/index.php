<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Receptionist') {
    header("Location: ../../../receptionist_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inpatients - GM HMS</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Reception Dashboard CSS -->
    <link rel="stylesheet" href="../../assets/css/reception_dashboard.css">

    <!-- Custom IPD CSS -->
    <link rel="stylesheet" href="assets/css/ipd_main.css">

    <style>
        .quick-action-btn {
            width: 100%;
            padding: 20px;
            margin-bottom: 15px;
            font-size: 1.1rem;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>

<body>
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include '../../includes/reception_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="reception-main-content">
            <!-- Top Navbar -->
            <?php
            $pageTitle = 'Inpatients';
            include '../../includes/reception_navbar.php';
            ?>

            <!-- Dashboard Content -->
            <div class="reception-content">
                <!-- IPD Dashboard Header -->
                <div style="margin-bottom: 1.5rem;">
                    <h1 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem;">
                        <i class="fas fa-hospital-user"></i> Inpatient Services
                    </h1>
                    <p style="color: #6b7280; font-size: 0.875rem;">Admissions, bed occupancy and payments overview
                    </p>
                </div>

                <!-- Stats Cards - Horizontal Layout -->
                <div class="row g-3 mb-4" id="statsGrid">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                            <i class="fas fa-bed text-primary fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-0 fw-bold" id="activeAdmissions">-</h3>
                                        <p class="text-muted mb-0 small">Active Admissions</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                            <i class="fas fa-procedures text-success fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-0 fw-bold" id="bedOccupancy">-</h3>
                                        <p class="text-muted mb-0 small">Bed Occupancy</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                            <i class="fas fa-user-plus text-warning fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-0 fw-bold" id="admissionsToday">-</h3>
                                        <p class="text-muted mb-0 small">Admissions Today</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                            <i class="fas fa-rupee-sign text-info fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-0 fw-bold" id="paymentsToday">-</h3>
                                        <p class="text-muted mb-0 small">Payments Today</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <button class="btn btn-primary quick-action-btn"
                                            onclick="window.location.href='../views/admissions/'">
                                            <i class="fas fa-user-plus fa-2x"></i>
                                            <span>New Admission</span>
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-success quick-action-btn"
                                            onclick="window.location.href='../views/beds/'">
                                            <i class="fas fa-bed fa-2x"></i>
                                            <span>Manage Beds</span>
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-info quick-action-btn"
                                            onclick="window.location.href='../views/payments/'">
                                            <i class="fas fa-money-bill-wave fa-2x"></i>
                                            <span>Record Payment</span>
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-warning quick-action-btn"
                                            onclick="window.location.href='../views/discharge/'">
                                            <i class="fas fa-sign-out-alt fa-2x"></i>
                                            <span>Discharge Patient</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Modules -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title"><i class="fas fa-th-large"></i> All Modules</h2>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-hospital-user fa-3x text-primary mb-3"></i>
                                                <h5 class="card-title">IPD Admissions</h5>
                                                <p class="card-text">Manage patient admissions, bed assignments, and
                                                    discharge</p>
                                                <a href="../views/admissions/" class="btn btn-primary">Open Module</a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-bed fa-3x text-success mb-3"></i>
                                                <h5 class="card-title">Hospital Beds</h5>
                                                <p class="card-text">View bed status, allocate and release beds</p>
                                                <a href="../views/beds/" class="btn btn-success">Open Module</a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-procedures fa-3x text-info mb-3"></i>
                                                <h5 class="card-title">Procedures</h5>
                                                <p class="card-text">Record medical procedures performed during
                                                    admission</p>
                                                <a href="../views/procedures/" class="btn btn-info">Open Module</a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-file-medical fa-3x text-warning mb-3"></i>
                                                <h5 class="card-title">Discharge Details</h5>
                                                <p class="card-text">Manage discharge summaries and follow-up
                                                    instructions</p>
                                                <a href="../views/discharge/" class="btn btn-warning">Open Module</a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-users fa-3x text-secondary mb-3"></i>
                                                <h5 class="card-title">Visitor Log</h5>
                                                <p class="card-text">Track visitors for admitted patients</p>
                                                <a href="../views/visitors/" class="btn btn-secondary">Open Module</a>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- End Dashboard Content -->
                    </div>
                    <!-- End Reception Content -->
                </div>
                <!-- End Reception Main Content -->
            </div>
            <!-- End Reception Layout -->

            <!-- jQuery -->
            <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

            <!-- Bootstrap 5 JS -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

            <!-- DataTables JS -->
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

            <!-- Select2 JS -->
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

            <!-- Toastify JS -->
            <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

            <!-- Custom JS -->
            <script src="assets/js/ipd_main.js"></script>

            <script>
                // Load dashboard statistics
                function loadDashboardStats() {
                    IPD.ajax('dashboard', 'GET')
                        .then(response => {
                            const data = response.data;

                            // Update active admissions
                            $('#activeAdmissions').text(data.admissions.active || 0);

                            // Update bed occupancy
                            const beds = data.beds;
                            $('#bedOccupancy').html(`${beds.occupied_beds}/${beds.total_beds}`);

                            // Update admissions today
                            $('#admissionsToday').text(data.admissions.today.total_admissions || 0);

                            // Update payments today
                            $('#paymentsToday').text(IPD.formatCurrency(data.payments.today.total_amount || 0));
                        })
                        .catch(error => {
                            console.error('Failed to load dashboard stats:', error);
                        });
                }

                // Load stats on page load
                $(document).ready(function () {
                    loadDashboardStats();

                    // Refresh stats every 30 seconds
                    setInterval(loadDashboardStats, 30000);
                });
            </script>
</body>

</html>