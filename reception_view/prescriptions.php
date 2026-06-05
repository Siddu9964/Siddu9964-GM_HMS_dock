<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Receptionist') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Prescriptions - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">
    <link rel="stylesheet" href="assets/css/prescription_view.css">
    
    <style>
        /* Modern Search Bar Styling */
        .search-container {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .search-wrapper {
            display: flex;
            gap: 1rem;
        }
        
        .search-input-group {
            flex: 1;
            position: relative;
        }
        
        .search-input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #0FA4AF;
            box-shadow: 0 0 0 3px rgba(15, 164, 175, 0.1);
        }

        /* History Timeline */
        .history-list {
            margin-top: 1.5rem;
        }

        .prescription-item {
            background: white;
            padding: 1.25rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .prescription-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .no-records {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="reception-main-content">
            <!-- Top Navbar -->
            <?php include 'includes/reception_navbar.php'; ?>
            
            <div class="reception-content">
                <div class="page-header mb-4">
                    <h1 class="page-title">
                        <i class="fas fa-prescription" style="color: #0FA4AF;"></i>
                        Patient Prescriptions
                    </h1>
                    <p class="page-subtitle">Search and view professional prescriptions for patients</p>
                </div>

                <!-- Search Section -->
                <div class="search-container">
                    <div class="search-wrapper">
                        <div class="search-input-group">
                            <i class="fas fa-search"></i>
                            <input type="text" id="patient-id-input" class="search-input" placeholder="Enter Patient ID or Mobile Number">
                        </div>
                        <button onclick="searchPrescription()" class="btn btn-primary" id="search-btn">
                            <i class="fas fa-search mr-2"></i>
                            Search
                        </button>
                    </div>
                </div>

                <!-- Results Section -->
                <div id="results-section" style="display: none; margin-bottom: 2rem;">
                    <div class="card mb-4" id="patient-summary-card">
                        <div class="card-body" style="display: flex; gap: 2rem; align-items: center;">
                            <div style="width: 60px; height: 60px; border-radius: 50%; background: #0FA4AF; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700;">
                                <span id="pat-initials">--</span>
                            </div>
                            <div>
                                <h2 id="pat-name" style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #1e293b;">Patient Name</h2>
                                <p id="pat-details" style="margin: 0; color: #64748b;">Age: -- | Gender: -- | ID: --</p>
                            </div>
                        </div>
                    </div>

                    <h3 class="section-title mb-3">Prescription History</h3>
                    <div id="prescription-history-list" class="history-list">
                        <!-- Items loaded dynamically -->
                    </div>
                </div>

                <!-- Global Recent Prescriptions -->
                <div id="all-prescriptions-section">
                    <h3 class="section-title mb-3" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Recent Global Prescriptions</span>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                           <span style="font-size: 0.8rem; font-weight: 400; color: #64748b;">Latest 50 records</span>
                           <button onclick="loadAllPrescriptions()" class="btn btn-sm btn-outline" style="padding: 4px 8px; font-size: 0.75rem;">
                              <i class="fas fa-sync-alt"></i> Refresh
                           </button>
                        </div>
                    </h3>
                    <div id="all-prescriptions-list" class="history-list">
                        <!-- Items loaded dynamically -->
                        <div class="no-records">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Loading recent records...</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div id="empty-state" class="no-records">
                    <div style="font-size: 4rem; opacity: 0.1; margin-bottom: 1rem;">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <h3>Start searching by Patient ID</h3>
                    <p>Enter a valid Patient ID above to view history and print prescriptions.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Professional View -->
    <div id="prescription-modal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header no-print">
                <h3><i class="fas fa-print"></i> Professional Preview</h3>
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="closePrescriptionModal()" class="btn btn-outline" style="background: white;">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
            
            <div class="modal-body">
                <!-- A4 Prescription Layout -->
                <div id="professional-prescription-a4" class="prescription-a4">
                    <!-- Loaded dynamically via JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/reception_utils.js"></script>
    <script src="assets/js/prescriptions.js"></script>
    
    <!-- Dynamic Page Title Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set navbar title based on current page
        const currentPath = window.location.pathname;
        
        if (currentPath.includes('prescriptions.php')) {
            setPageTitle('Prescriptions', 'Welcome back, Anita Sharma');
        }
    });
    </script>
</body>
</html>
