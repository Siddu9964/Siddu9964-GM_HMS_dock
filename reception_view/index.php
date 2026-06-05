<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Receptionist') {
    header("Location: /GM_HMS/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reception Dashboard - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">
</head>
<body>
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="reception-main-content">
            <!-- Top Navbar -->
            <?php include 'includes/reception_navbar.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="reception-content">
                <!-- New Layout: Left Column (Greeting + KPI Cards) | Right Column (Available Doctors) -->
                <div class="dashboard-main-grid">
                    <!-- Left Column: Greeting + KPI Cards -->
                    <div class="dashboard-left-column">
                        <!-- Greeting Card -->
                        <div class="greeting-card-compact">
                            <h1 class="greeting-title-compact">
                                Good <span id="greeting-time">Afternoon</span>, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! 👋
                            </h1>
                            <p class="greeting-date-compact">
                                Today is <span id="current-date">Monday, January 26, 2026</span>
                            </p>
                        </div>
                        
                        <!-- KPI Cards Grid (2x2) -->
                        <div class="kpi-cards-grid">
                            <!-- New Registrations -->
                            <div class="kpi-card card" style="background: linear-gradient(135deg, #0FA4AF 0%, #056674 100%);">
                                <div style="position: relative; z-index: 1;">
                                    <div class="kpi-card-label">New Registrations</div>
                                    <div class="kpi-card-value" id="kpi-registrations">0</div>
                                </div>
                                <i class="fas fa-user-plus kpi-card-icon"></i>
                            </div>
                            
                            <!-- Waiting Patients -->
                            <div class="kpi-card card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                <div style="position: relative; z-index: 1;">
                                    <div class="kpi-card-label">OPD Waiting</div>
                                    <div class="kpi-card-value" id="kpi-waiting">0</div>
                                </div>
                                <i class="fas fa-clock kpi-card-icon"></i>
                            </div>
                            
                            <!-- Active IPD Patients -->
                            <div class="kpi-card card" onclick="window.location.href='ipd_management/public/index.php'" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); cursor: pointer;">
                                <div style="position: relative; z-index: 1;">
                                    <div class="kpi-card-label">Active IPD Patients</div>
                                    <div class="kpi-card-value" id="kpi-ipd">0</div>
                                </div>
                                <i class="fas fa-procedures kpi-card-icon"></i>
                            </div>
                            
                            <!-- Available Doctors -->
                            <div class="kpi-card card" style="background: linear-gradient(135deg, #0FA4AF 0%, #2EAFB9 100%);">
                                <div style="position: relative; z-index: 1;">
                                    <div class="kpi-card-label">Doctors Available</div>
                                    <div class="kpi-card-value" id="kpi-doctors">0</div>
                                </div>
                                <i class="fas fa-user-md kpi-card-icon"></i>
                            </div>
                        </div>
                        
                        <!-- Quick Actions Section -->
                        <div class="quick-actions-card card">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="fas fa-bolt"></i>
                                    Quick Actions
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions-grid">
                                    <button onclick="window.location.href='patient_registration.php'" class="btn btn-outline quick-action-btn">
                                        <i class="fas fa-user-plus"></i>
                                        Register Patient
                                    </button>
                                    <button onclick="window.location.href='appointment_management.php'" class="btn btn-outline quick-action-btn">
                                        <i class="fas fa-calendar-check"></i>
                                        Book Appointment
                                    </button>
                                    <button onclick="window.location.href='prescriptions.php'" class="btn btn-outline quick-action-btn">
                                        <i class="fas fa-prescription"></i>
                                        View Prescription
                                    </button>
                                    <button onclick="window.location.href='billing.php'" class="btn btn-outline quick-action-btn">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                        Create Invoice
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column: Available Doctors Panel + Patient Flow Chart -->
                    <div class="dashboard-right-column">
                        <!-- Available Doctors Panel -->
                        <div class="doctors-panel-compact">
                            <div class="doctors-header">
                                <i class="fas fa-user-md"></i>
                                <h3>Available Doctors</h3>
                            </div>
                            <div class="doctors-list" id="available-doctors-list">
                                <div class="loading-doctors">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading doctors...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom Row: Patient Flow & Recent Activity (Horizontal Layout) -->
                <div class="bottom-row-grid">
                    <!-- Patient Flow Chart -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-chart-line"></i>
                                Patient Flow (Weekly)
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="patientFlowChart" height="250"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recent Front Desk Activity -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-history"></i>
                                Recent Front Desk Activity
                            </div>
                            <a href="#" style="font-size: 0.875rem; color: var(--primary-blue);">View All</a>
                        </div>
                        <div class="card-body">
                            <div id="recent-activity-list">
                                <div style="text-align: center; padding: 2rem; color: var(--gray-400);">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                    <p>Loading activity...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="assets/js/reception_utils.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
