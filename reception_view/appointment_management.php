<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'Admin'])) {
    header("Location: /GM_HMS/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - GM HMS</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Main Dashboard CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">

    <!-- Shared Styles (Modals, Tables) -->
    <link rel="stylesheet" href="assets/css/patient.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Appointment specific styles override */
        .status-scheduled {
            background: #E0F2F1;
            color: #00796B;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .timeline-view {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 20px;
        }

        /* Professional Filter Styles */
        .premium-filter-container {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #fff;
            padding: 10px 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            flex-wrap: wrap;
        }

        .search-wrapper,
        .select-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-wrapper i,
        .select-wrapper i {
            position: absolute;
            left: 15px;
            color: #94a3b8;
            font-size: 0.9rem;
            pointer-events: none;
        }

        .professional-input {
            padding: 10px 15px 10px 40px !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            font-size: 0.9rem;
            color: #1e293b;
            transition: all 0.3s ease;
            min-width: 250px;
            background: #f8fafc;
        }

        .professional-input:focus {
            border-color: #0fa4af !important;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(15, 164, 175, 0.1);
        }

        .professional-select {
            padding: 10px 35px 10px 40px !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            appearance: none;
            background: #f8fafc;
            font-size: 0.9rem;
            color: #1e293b;
            cursor: pointer;
            min-width: 180px;
        }

        .select-wrapper::after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            color: #94a3b8;
            font-size: 0.7rem;
            pointer-events: none;
        }
    </style>
</head>

<body>

    <div class="reception-layout">

        <!-- Include Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="reception-main-content">

            <!-- Include Navbar -->
            <?php
            $pageTitle = 'Appointment Management';
            include 'includes/reception_navbar.php';
            ?>

            <!-- Page Content -->
            <main class="reception-content">

                <!-- Page Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800" style="color: #056674;">Appointment Management
                            </h1>
                            <p class="text-gray-600 mt-1">Schedule and manage doctor appointments</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="appointmentManager.openModal('create')" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i>
                                New Appointment
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6">
                    <div class="premium-filter-container">
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="professional-input"
                                placeholder="Search by patient, ID or doctor...">
                        </div>

                        <div class="select-wrapper">
                            <i class="fas fa-user-md"></i>
                            <select id="doctorFilter" class="professional-select">
                                <option value="">Filter By Doctor</option>
                                <!-- Populated via JS -->
                            </select>
                        </div>

                        <div class="select-wrapper">
                            <i class="fas fa-filter"></i>
                            <select id="statusFilter" class="professional-select">
                                <option value="">Filter By Status</option>
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>

                        <div style="margin-left: auto;">
                            <button onclick="appointmentManager.loadAppointments()" class="btn btn-outline"
                                style="padding: 8px 15px; border-radius: 10px;" title="Refresh List">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div id="tableContainer">
                        <!-- Loading skeleton -->
                        <div id="loadingSkeleton" class="p-6 hidden">
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                        </div>

                        <!-- Actual table -->
                        <div id="appointmentTableWrapper">
                            <div style="overflow-x: auto;">
                                <table class="patient-table">
                                    <thead>
                                        <tr>
                                            <th>Patient ID</th>
                                            <th>Apt ID</th>
                                            <th>Patient</th>
                                            <th>Phone</th>
                                            <th>Doctor</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th style="text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="appointmentTableBody">
                                        <!-- Rows inserted via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </main>

        </div>
    </div>

    <!-- Appointment Modal -->
    <div id="appointmentModal" class="modal-overlay hidden">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header" style="background: var(--primary-gradient); color: white;">
                <h2 id="modalTitle">New Appointment</h2>
                <button onclick="appointmentManager.closeModal()" class="modal-close"
                    style="color: white; opacity: 0.8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="appointmentForm">
                    <input type="hidden" id="editAppointmentId" name="appointment_id">
                    <input type="hidden" id="patientPhone" name="phone">

                    <div class="form-grid cols-2">
                        <!-- Patient ID -->
                        <div class="input-group" style="grid-column: span 2;">
                            <label>Patient <span class="required">*</span></label>
                            <select id="patientSelect" name="patient_id" style="width: 100%;" required>
                                <option value="">Search by ID or Name...</option>
                            </select>
                        </div>

                        <!-- Department -->
                        <div class="input-group">
                            <label>Department <span class="required">*</span></label>
                            <select id="departmentSelect" name="department_id" required>
                                <option value="">Select Department</option>
                                <!-- Populated via JS -->
                            </select>
                        </div>

                        <!-- Doctor & Availability -->
                        <div class="input-group">
                            <label>Doctor <span class="required">*</span></label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <select id="doctorSelect" name="doctor_id" required disabled style="flex: 1;">
                                    <option value="">Select Department First</option>
                                    <!-- Populated via JS -->
                                </select>
                                <div id="doctorAvailabilityStatus" style="font-weight: bold; white-space: nowrap;">
                                    <!-- Status Icon/Text via JS -->
                                </div>
                            </div>
                            <div id="doctorAvailabilityMsg" class="hidden"></div>
                            <!-- Legacy msg container, keeping hidden or removing -->
                        </div>

                        <!-- Date -->
                        <div class="input-group">
                            <label>Date <span class="required">*</span></label>
                            <input type="date" name="appointment_date" required>
                        </div>

                        <!-- Time -->
                        <div class="input-group">
                            <label>Time <span class="required">*</span></label>
                            <input type="time" name="appointment_time" required>
                        </div>

                        <!-- Status -->




                        <!-- Reason -->
                        <div class="input-group" style="grid-column: span 2;">
                            <label>Reason</label>
                            <input type="text" name="reason" placeholder="Main complaint or reason for visit">
                        </div>

                        <!-- Notes -->
                        <div class="input-group" style="grid-column: span 2;">
                            <label>Notes</label>
                            <textarea name="notes" rows="2" placeholder="Additional notes..."></textarea>
                        </div>


                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t mt-4">
                        <button type="button" onclick="appointmentManager.closeModal()" class="btn btn-secondary"
                            style="background: white; border: 1px solid #ccc;">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSaveOnly">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- JavaScript -->
    <script src="assets/js/appointment.js?v=<?= time() ?>"></script>
</body>

</html>