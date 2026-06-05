<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Receptionist') {
    header("Location: ../receptionist_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Management - GM HMS</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Base CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">
    <!-- Module CSS -->
    <link rel="stylesheet" href="assets/css/opd_management.css">
</head>

<body>

    <div class="reception-layout">

        <!-- Include Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="reception-main-content">

            <!-- Include Navbar -->
            <?php
            $pageTitle = 'Out-patients department';
            include 'includes/reception_navbar.php';
            ?>

            <!-- Page Content -->
            <main class="reception-content">

                <!-- 1. Stats Overview -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="stat-opd-total">0</h3>
                            <p>Today's OPD</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="stat-doctors-active">0</h3>
                            <p>Doctors Available</p>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon warning">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="stat-revenue">0</h3>
                            <p>Today's Revenue</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Live Queue -->
                <div class="card mb-4">
                    <div class="card-header queue-header">
                        <div class="card-title">
                            <i class="fas fa-procedures text-primary"></i>
                            <span>Live Patient Queue</span>
                        </div>
                        <div class="d-flex gap-2">

                            <div class="queue-filters">
                                <button class="filter-btn active" data-filter="all">All</button>
                                <button class="filter-btn" data-filter="Pending" id="tab-pending">Pending (0)</button>
                                <button class="filter-btn" data-filter="Completed" id="tab-completed">Completed
                                    (0)</button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Loading State -->
                        <div id="queue-loading" class="text-center p-4">
                            <div class="spinner"></div>
                            <p class="mt-2 text-gray-500">Loading queue...</p>
                        </div>

                        <!-- Queue Grid -->
                        <div class="queue-grid" id="queue-list" style="display: none;">
                            <!-- Queue Items injected via JS -->
                        </div>

                        <!-- Empty State -->
                        <div id="queue-empty" class="empty-state" style="display: none;">
                            <i class="fas fa-clipboard-check"></i>
                            <h3>No patients in queue</h3>
                            <p>All clear for now.</p>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Encounter Modal -->
    <div id="encounterModal" class="modal-overlay hidden" onclick="if(event.target === this) closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="modal-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="text-2xl font-bold mb-1" id="modal-patient-name">Patient Name</h2>
                        <div class="modal-patient-info">
                            <span class="info-badge"><i class="fas fa-id-card-alt mr-2"></i><span
                                    id="modal-patient-id">PID-000</span></span>
                            <span class="info-badge"><i class="fas fa-user mr-2"></i><span id="modal-patient-details">25
                                    Y / Male</span></span>
                            <span class="info-badge"><i class="fas fa-user-md mr-2"></i><span id="modal-doctor-name">Dr.
                                    Name</span></span>
                        </div>
                    </div>
                    <button class="btn btn-link text-white" onclick="closeModal()">
                        <i class="fas fa-times fa-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tab-nav">
                <button class="tab-btn active" onclick="switchTab('clinical')">
                    <i class="fas fa-heartbeat mr-2"></i> Clinical & Vitals
                </button>
                <button class="tab-btn" onclick="switchTab('rx')">
                    <i class="fas fa-prescription mr-2"></i> Prescriptions
                </button>
                <button class="tab-btn" onclick="switchTab('labs')">
                    <i class="fas fa-microscope mr-2"></i> Lab Reports
                </button>
                <button class="tab-btn" onclick="switchTab('followup')">
                    <i class="fas fa-calendar-alt mr-2"></i> Follow-up
                </button>
            </div>

            <div class="p-0">

                <!-- Tab: Clinical -->
                <div id="tab-clinical" class="tab-content active">
                    <form id="vitals-form">
                        <input type="hidden" name="appointment_id" id="vitals-appt-id">
                        <input type="hidden" name="patient_id" id="vitals-patient-id">
                        <input type="hidden" name="doctor_id" id="vitals-doctor-id">

                        <h4 class="form-label mb-3">Vital Signs</h4>
                        <div class="vitals-grid">
                            <div class="vital-input">
                                <label>Blood Pressure</label>
                                <input type="text" name="bp" placeholder="---/---">
                                <div class="vital-unit">mmHg</div>
                            </div>
                            <div class="vital-input">
                                <label>Pulse Rate</label>
                                <input type="text" name="pulse" placeholder="---">
                                <div class="vital-unit">bpm</div>
                            </div>
                            <div class="vital-input">
                                <label>Temperature</label>
                                <input type="text" name="temp" placeholder="---">
                                <div class="vital-unit">°F</div>
                            </div>
                            <div class="vital-input">
                                <label>Weight</label>
                                <input type="text" name="weight" placeholder="---">
                                <div class="vital-unit">kg</div>
                            </div>
                            <div class="vital-input">
                                <label>SpO2</label>
                                <input type="text" name="spo2" placeholder="---">
                                <div class="vital-unit">%</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Chief Complaint</label>
                            <textarea name="chief_complaint" class="form-control" rows="3"
                                placeholder="Enter patient's main problem or symptoms..."></textarea>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Vitals
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Rx -->
                <div id="tab-rx" class="tab-content">
                    <div class="d-flex justify-content-between mb-3">
                        <h4 class="form-label">Active Prescriptions</h4>
                        <button class="btn btn-outline btn-sm" onclick="printPrescription()">
                            <i class="fas fa-print"></i> Print Rx
                        </button>
                    </div>
                    <div id="rx-list">
                        <!-- Loaded dynamically -->
                        <p class="text-center text-gray-500 py-4">No prescriptions found for this visit.</p>
                    </div>
                </div>

                <!-- Tab: Labs -->
                <div id="tab-labs" class="tab-content">
                    <div class="row">
                        <div class="col-md-7 border-right">
                            <div class="d-flex justify-content-between mb-3">
                                <h4 class="form-label">Lab Tests Requests</h4>
                            </div>
                            <div id="lab-list">
                                <!-- Loaded dynamically -->
                                <p class="text-center text-gray-500 py-4">No lab orders found.</p>
                            </div>
                        </div>
                        <div class="col-md-5 pl-3">
                            <h4 class="form-label mb-3">New Lab Request</h4>
                            <form id="lab-form">
                                <div class="form-group">
                                    <label class="form-label">Test Name</label>
                                    <select name="test_name" class="form-control" required>
                                        <option value="">Select Test</option>
                                        <option value="CBC">Complete Blood Count (CBC)</option>
                                        <option value="Lipid Profile">Lipid Profile</option>
                                        <option value="Liver Function">Liver Function Test</option>
                                        <option value="Kidney Function">Kidney Function Test</option>
                                        <option value="Thyroid Profile">Thyroid Profile</option>
                                        <option value="Blood Sugar Fasting">Blood Sugar (Fasting)</option>
                                        <option value="Urine Routine">Urine Routine</option>
                                        <option value="X-Ray Chest">X-Ray Chest PA</option>
                                        <option value="USG Abdomen">USG Abdomen</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Priority</label>
                                    <select name="priority" class="form-control">
                                        <option value="Normal">Normal</option>
                                        <option value="Urgent">Urgent</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Clinical Notes</label>
                                    <textarea name="notes" class="form-control" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i> Add Request
                                </button>
                            </form>
                        </div>
                    </div>
                </div>



                <!-- Tab: Follow-up -->
                <div id="tab-followup" class="tab-content">
                    <form id="followup-form">
                        <h4 class="form-label mb-3">Schedule Follow-up</h4>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="form-label">Date</label>
                                <input type="date" name="follow_up_date" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Notes for Next Visit</label>
                                <textarea name="notes" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> Schedule Follow-up
                        </button>
                    </form>
                    <div class="mt-4 pt-4 border-top">
                        <div class="alert alert-info" id="current-followup" style="display:none;">
                            <strong>Scheduled:</strong> <span id="followup-display"></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>



    <!-- Scripts -->
    <script src="assets/js/reception_utils.js"></script>
    <script src="assets/js/opd_management.js"></script>
</body>

</html>