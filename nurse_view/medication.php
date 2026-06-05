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
    <title>Medication Administration - GM HMS</title>
    
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 24px;
            color: var(--dark);
            font-weight: 700;
        }

        .tab-navigation {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            border-bottom: 1px solid #DEE2E6;
        }

        .tab-btn {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            color: #6C757D;
            border-bottom: 3px solid transparent;
            transition: 0.3s;
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .med-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            align-items: center;
            gap: 20px;
        }

        .med-time {
            background: #E3F2FD;
            color: var(--primary);
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            width: 80px;
        }

        .med-time .time {
            display: block;
            font-weight: 700;
            font-size: 16px;
        }

        .med-time .date {
            font-size: 11px;
            opacity: 0.8;
        }

        .med-info h4 {
            font-size: 17px;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .med-info p {
            font-size: 13px;
            color: #6C757D;
        }

        .patient-tag {
            background: #F1F3F5;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #495057;
        }

        .btn-give {
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-give:hover {
            background: #218838;
            transform: scale(1.05);
        }

        .med-overdue {
            border-left: 5px solid var(--danger);
        }

        .overdue-tag {
            color: var(--danger);
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }

        .loading, .empty-state {
            text-align: center;
            padding: 80px;
            color: #6C757D;
            background: white;
            border-radius: 12px;
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
                    <div class="page-header">
                        <h1>MAR: Medication Administration</h1>
                        <button class="btn-give" style="background: var(--primary);">
                            <i class="fas fa-plus"></i> New Admin
                        </button>
                    </div>

                    <div class="tab-navigation">
                        <div class="tab-btn active">Scheduled Today</div>
                        <div class="tab-btn">Overdue</div>
                        <div class="tab-btn">Administered</div>
                        <div class="tab-btn">Patient Search</div>
                    </div>

                    <div id="medicationList">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Loading medications schedule...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadMedications() {
            try {
                // Get patient from URL or SessionStorage
                const urlParams = new URLSearchParams(window.location.search);
                let targetId = urlParams.get('patient_id') || sessionStorage.getItem('selected_patient_id');
                
                // Clear URL param if it exists to keep URL clean
                if (urlParams.has('patient_id')) {
                    sessionStorage.setItem('selected_patient_id', urlParams.get('patient_id'));
                    window.history.replaceState({}, document.title, window.location.pathname);
                }

                const response = await fetch('api/dashboard.php');
                const result = await response.json();

                if (result.success) {
                    const overdue = result.data.overdue_medications;
                    const container = document.getElementById('medicationList');
                    
                    // Note: In real app we'd fetch actual scheduled meds. 
                    // Using overdue as a placeholder or empty state
                    
                    if (overdue && overdue.length > 0) {
                        container.innerHTML = overdue.map(m => `
                            <div class="med-card med-overdue">
                                <div class="med-time">
                                    <span class="time">${new Date(m.scheduled_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                    <span class="date">OVERDUE</span>
                                </div>
                                <div class="med-info">
                                    <h4>${m.medicine_name}</h4>
                                    <p>${m.dosage} | ${m.route} | ${m.frequency}</p>
                                    <div class="overdue-tag">
                                        <i class="fas fa-exclamation-circle"></i> Overdue by ${m.minutes_overdue} mins
                                    </div>
                                </div>
                                <div>
                                    <span class="patient-tag">
                                        <i class="fas fa-user"></i> ${m.patient_name}
                                    </span>
                                </div>
                                <button class="btn-give">GIVE NOW</button>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-check-circle" style="color: var(--success); font-size: 64px; margin-bottom: 20px;"></i>
                                <h3>All Caught Up!</h3>
                                <p>No medications are currently pending for administration.</p>
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        loadMedications();
    </script>
</body>
</html>
