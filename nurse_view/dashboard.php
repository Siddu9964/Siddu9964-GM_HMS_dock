<?php
session_start();

// Check authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Nurse') {
    header('Location: ../login.php');
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
$nurseName = $_SESSION['username'] ?? 'Nurse';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary: #4A90E2;
            --primary-dark: #357ABD;
            --success: #28A745;
            --warning: #FFC107;
            --danger: #DC3545;
            --info: #17A2B8;
            --light: #F8F9FA;
            --dark: #343A40;
        }

        body {
            background: #F5F7FA;
            min-height: 100vh;
            display: flex;
        }

        .main-layout {
            display: flex;
            width: 100%;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
        }

        .welcome-card h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .welcome-card p {
            font-size: 16px;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.blue { background: var(--primary); }
        .stat-icon.green { background: var(--success); }
        .stat-icon.orange { background: var(--warning); }
        .stat-icon.red { background: var(--danger); }

        .stat-content h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-content p {
            color: #6C757D;
            font-size: 14px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6C757D;
        }

        .loading i {
            font-size: 48px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }

        .action-btn {
            background: white;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <div class="main-layout">
        <!-- Sidebar -->
        <?php include 'includes/nurse_sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="content-wrapper">
            <!-- Navbar -->
            <?php include 'includes/nurse_navbar.php'; ?>
            
            <!-- Page Content -->
            <div class="main-content">
                <div class="container">
                    <div class="welcome-card">
                        <h2>Welcome, <?php echo htmlspecialchars($nurseName); ?>! 👋</h2>
                        <p>Your nurse dashboard is ready. Here's your overview for today.</p>
                    </div>

                    <div class="stats-grid" id="statsGrid">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="totalPatients">--</h3>
                                <p>Assigned Patients</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-pills"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="pendingMeds">--</h3>
                                <p>Pending Medications</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="pendingTasks">--</h3>
                                <p>Pending Tasks</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="vitalsRecorded">--</h3>
                                <p>Vitals Recorded Today</p>
                            </div>
                        </div>
                    </div>

                    <div class="quick-actions">
                        <a href="vitals.php" class="action-btn">
                            <i class="fas fa-heartbeat"></i> Record Vitals
                        </a>
                        <a href="medication.php" class="action-btn">
                            <i class="fas fa-pills"></i> Medications
                        </a>
                        <a href="nurse_notes.php" class="action-btn">
                            <i class="fas fa-notes-medical"></i> Nurse Notes
                        </a>
                        <a href="tasks.php" class="action-btn">
                            <i class="fas fa-tasks"></i> My Tasks
                        </a>
                        <a href="patient_care.php" class="action-btn">
                            <i class="fas fa-user-injured"></i> My Patients
                        </a>
                        <a href="my_shift.php" class="action-btn">
                            <i class="fas fa-clock"></i> My Shift
                        </a>
                    </div>

                    <div class="loading" id="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Loading dashboard data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load dashboard data
        async function loadDashboard() {
            try {
                const response = await fetch('api/dashboard.php');
                const result = await response.json();

                if (result.success) {
                    const stats = result.data.statistics;
                    
                    document.getElementById('totalPatients').textContent = stats.shift.total_patients || 0;
                    document.getElementById('pendingMeds').textContent = stats.medications.pending || 0;
                    document.getElementById('pendingTasks').textContent = stats.tasks.pending || 0;
                    document.getElementById('vitalsRecorded').textContent = stats.vitals.total_recorded || 0;
                    
                    document.getElementById('loading').style.display = 'none';
                } else {
                    console.error('Failed to load dashboard:', result.message);
                    document.getElementById('loading').innerHTML = '<p style="color: var(--danger);">Failed to load dashboard data</p>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loading').innerHTML = '<p style="color: var(--danger);">Error loading dashboard</p>';
            }
        }

        // Load data on page load
        loadDashboard();
    </script>
</body>
</html>
