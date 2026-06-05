<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header("Location: /GM_HMS/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
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
            
            <!-- Dashboard Content -->
            <div class="doctor-content">
                <!-- Welcome Banner -->
                <div class="card" style="background: #1e293b; color: white; margin-bottom: 1rem; border: none; overflow: hidden; position: relative; padding: 1.0625rem 1.25rem;">
                    <div style="position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h1 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; letter-spacing: -0.5px;">
                                Good <span id="greeting-time">Morning</span>, <span id="doctor-greeting-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>! 👋
                            </h1>
                            <p style="font-size: 0.95rem; opacity: 0.9; font-weight: 400;">
                                Today is <span id="current-date" style="font-weight: 600;"></span>. You have <span id="today-appointments-count" style="font-weight: 700; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 6px;">0</span> appointments scheduled today.
                            </p>
                        </div>
                        <div style="font-size: 3.5rem; opacity: 0.15; position: absolute; right: -10px; bottom: -15px; transform: rotate(-10deg);">
                            <i class="fas fa-user-md"></i>
                        </div>
                    </div>
                </div>
                
                <!-- KPI Cards Grid -->
                <div class="d-grid grid-cols-4 gap-3 mb-4">
                    <!-- Today's Appointments -->
                    <div class="stat-card gradient-bg-1">
                        <h3>Today's Appointments</h3>
                        <div class="value" id="kpi-appointments">0</div>
                        <i class="fas fa-calendar-check stat-icon"></i>
                    </div>
                    
                    <!-- Waiting Patients -->
                    <div class="stat-card gradient-bg-4">
                        <h3>Waiting Patients</h3>
                        <div class="value" id="kpi-waiting">0</div>
                        <i class="fas fa-clock stat-icon"></i>
                    </div>
                    
                    <!-- Completed Consultations -->
                    <div class="stat-card gradient-bg-5">
                        <h3>Completed Today</h3>
                        <div class="value" id="kpi-completed">0</div>
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                    
                    <!-- Pending Lab Reports -->
                    <div class="stat-card gradient-bg-6">
                        <h3>Pending Lab Reports</h3>
                        <div class="value" id="kpi-pending-labs">0</div>
                        <i class="fas fa-flask stat-icon"></i>
                    </div>
                </div>
                
                <!-- Secondary KPI Cards -->
                <div class="d-grid grid-cols-3 gap-3 mb-4">
                    <!-- Emergency Alerts -->
                    <div class="card" style="border-top: 4px solid var(--status-danger); background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <div class="card-header" style="border-bottom: 1px solid var(--gray-100); margin-bottom: 1rem; padding-bottom: 0.75rem;">
                            <div class="card-title" style="color: var(--status-danger); font-weight: 700; font-size: 1.1rem;">
                                <i class="fas fa-exclamation-circle"></i>
                                Emergency Alerts
                            </div>
                            <span class="badge" id="emergency-count" style="background: var(--status-danger); color: white; border-radius: 50px; padding: 2px 10px;">0</span>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div id="emergency-list" style="padding: 0.5rem 0;">
                                <div style="text-align: center; padding: 1.5rem; color: var(--gray-400);">
                                    <i class="fas fa-shield-alt" style="font-size: 1.5rem; opacity: 0.3; margin-bottom: 0.5rem; display: block;"></i>
                                    No emergency alerts
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- AI Risk Alerts -->
                    <div class="card" style="border-top: 4px solid var(--status-warning); background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <div class="card-header" style="border-bottom: 1px solid var(--gray-100); margin-bottom: 1rem; padding-bottom: 0.75rem;">
                            <div class="card-title" style="color: var(--status-warning); font-weight: 700; font-size: 1.1rem;">
                                <i class="fas fa-brain"></i>
                                AI Risk Alerts
                            </div>
                            <span class="badge" id="ai-risk-count" style="background: var(--status-warning); color: white; border-radius: 50px; padding: 2px 10px;">0</span>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <div id="ai-risk-list" style="padding: 0.5rem 0;">
                                <div style="text-align: center; padding: 1.5rem; color: var(--gray-400);">
                                    <i class="fas fa-robot" style="font-size: 1.5rem; opacity: 0.3; margin-bottom: 0.5rem; display: block;"></i>
                                    Analyzing risk levels...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Average Consultation Time -->
                    <div class="card" style="border-top: 4px solid var(--primary-blue); background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <div class="card-header" style="border-bottom: 1px solid var(--gray-100); margin-bottom: 1rem; padding-bottom: 0.75rem;">
                            <div class="card-title" style="color: var(--primary-blue); font-weight: 700; font-size: 1.1rem;">
                                <i class="fas fa-stopwatch"></i>
                                Performance Sync
                            </div>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                                <div style="font-size: 2.25rem; font-weight: 800; color: var(--primary-blue-dark);" id="avg-consultation-time">--</div>
                                <div style="font-size: 0.875rem; color: var(--gray-500); font-weight: 600;">min/pt</div>
                            </div>
                            <div style="margin-top: 1rem; height: 4px; background: var(--gray-100); border-radius: 2px; overflow: hidden;">
                                <div style="width: 75%; height: 100%; background: var(--primary-blue);"></div>
                            </div>
                            <p style="font-size: 0.75rem; color: var(--gray-500); margin-top: 0.5rem;">75% efficiency vs target</p>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="d-grid grid-cols-2 gap-3 mb-4">
                    <!-- Patient Flow Chart -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-chart-line"></i>
                                Patient Flow (Last 7 Days)
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="patientFlowChart" height="200"></canvas>
                        </div>
                    </div>
                    
                    <!-- Consultation Duration Chart -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-chart-bar"></i>
                                Consultation Duration Trends
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="consultationDurationChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom Row: Upcoming Appointments + Recent Activity -->
                <div class="d-grid grid-cols-2 gap-3">
                    <!-- Upcoming Appointments -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-calendar-alt"></i>
                                Upcoming Appointments
                            </div>
                            <a href="mypatient.php" style="font-size: 0.875rem; color: var(--primary-blue);">View All</a>
                        </div>
                        <div class="card-body">
                            <div id="upcoming-appointments-list">
                                <div style="text-align: center; padding: 2rem; color: var(--gray-400);">
                                    <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                    <p>No upcoming appointments</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity Feed -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-history"></i>
                                Recent Activity
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="recent-activity-list">
                                <div style="text-align: center; padding: 2rem; color: var(--gray-400);">
                                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                    <p>No recent activity</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="assets/js/doctor_utils.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
