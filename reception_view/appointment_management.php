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
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

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
            border-color: #1f6b4a !important;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1);
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

        /* Premium Modal Form Styles */
        .modal-body {
            padding: 2rem;
            background: #FDFBF7;
            border-radius: 0 0 1.25rem 1.25rem;
        }

        .modal-body label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            display: block;
        }

        .modal-body .required {
            color: #ef4444;
            font-weight: bold;
        }

        .modal-body input[type="text"],
        .modal-body input[type="date"],
        .modal-body input[type="time"],
        .modal-body select,
        .modal-body textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            background: #ffffff;
            color: #1e293b;
            transition: all 0.3s ease;
        }

        .modal-body input:focus,
        .modal-body select:focus,
        .modal-body textarea:focus {
            border-color: #1f6b4a;
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1);
            outline: none;
        }

        .modal-body .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 640px) {
            .modal-body .form-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Advanced Button Animations */
        .adv-btn-save {
            background: #1f6b4a !important;
            color: #ffffff !important;
            border: none !important;
            padding: 0.875rem 2rem !important;
            border-radius: 0.75rem !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
            box-shadow: 0 4px 15px rgba(31, 107, 74, 0.3) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 1 !important;
        }
        .adv-btn-save:disabled {
            background: #1f6b4a !important;
            opacity: 1 !important;
            cursor: not-allowed;
            box-shadow: none !important;
            filter: none !important;
        }
        .adv-btn-save:hover {
            transform: translateY(-3px) scale(1.02) !important;
            box-shadow: 0 8px 25px rgba(31, 107, 74, 0.4) !important;
        }
        
        .adv-btn-cancel {
            background: #ffffff !important;
            color: #ef4444 !important;
            border: 2px solid #fee2e2 !important;
            padding: 0.875rem 2rem !important;
            border-radius: 0.75rem !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            transition: all 0.3s ease !important;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .adv-btn-cancel:hover {
            background: #fef2f2 !important;
            border-color: #ef4444 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15) !important;
        }
        
        .adv-btn-back {
            background: transparent;
            border: none;
            color: #64748b;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.5rem 0;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
        }
        .adv-btn-back:hover {
            color: #1f6b4a;
            transform: translateX(-5px);
        }
        
        .adv-btn-next {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%) !important;
            color: white !important;
            border: none !important;
            padding: 0.875rem 2.5rem !important;
            border-radius: 0.75rem !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
            box-shadow: 0 4px 15px rgba(31, 107, 74, 0.3) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            width: 100%;
        }
        .adv-btn-next:hover {
            transform: translateY(-3px) scale(1.01) !important;
            box-shadow: 0 8px 25px rgba(31, 107, 74, 0.4) !important;
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
                            <h1 class="text-3xl font-bold text-gray-800" style="color: #144d34;">Appointment Management
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
        <div class="modal-content" onclick="event.stopPropagation()" style="background: #ffffff; border-radius: 1.25rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 800px; width: 95%; animation: slideUp 0.3s ease-out; border: 1px solid rgba(0,0,0,0.05);">
            <div class="modal-header" style="background: #ffffff; color: #1f6b4a; padding: 1.5rem 2rem; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; border-radius: 1.25rem 1.25rem 0 0;">
                <h2 id="modalTitle" style="margin: 0; font-size: 1.75rem; font-weight: 800; display: flex; align-items: center; gap: 0.75rem; color: #1f6b4a !important;">
                    <i class="fas fa-calendar-plus" style="color: #f59e0b;"></i> New Appointment
                </h2>
                <button onclick="appointmentManager.closeModal()" class="modal-close"
                    style="background: #f1f5f9; border: none; color: #64748b; padding: 0.5rem 0.75rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s; font-size: 1.25rem;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#ef4444';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b';">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="appointmentForm">
                    <input type="hidden" id="editAppointmentId" name="appointment_id">
                    <input type="hidden" id="patientPhone" name="phone">

                    <h6 style="color: #1f6b4a; font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; margin-top: 0; display: flex; align-items: center; gap: 8px; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem;"><i class="fas fa-user-circle"></i> Patient Details</h6>
                    
                    <div class="form-grid cols-2" style="margin-bottom: 2rem;">
                        <div class="input-group" style="grid-column: span 2;">
                            <label>Patient <span class="required">*</span></label>
                            <select id="patientSelect" name="patient_id" style="width: 100%;" required tabindex="1">
                                <option value="">Search by ID or Name...</option>
                            </select>
                        </div>
                        <div class="input-group" style="grid-column: span 2;">
                            <label>Reason</label>
                            <input type="text" name="reason" placeholder="Main complaint or reason for visit" tabindex="2">
                        </div>
                    </div>

                    <h6 style="color: #1f6b4a; font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem;"><i class="fas fa-calendar-alt"></i> Schedule & Doctor</h6>
                    
                    <div class="form-grid cols-2">
                        <div class="input-group">
                            <label>Department <span class="required">*</span></label>
                            <select id="departmentSelect" name="department_id" required tabindex="3">
                                <option value="">Select Department</option>
                                <!-- Populated via JS -->
                            </select>
                        </div>

                        <div class="input-group">
                            <label>Doctor <span class="required">*</span></label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <select id="doctorSelect" name="doctor_id" required disabled style="flex: 1;" tabindex="4">
                                    <option value="">Select Department First</option>
                                    <!-- Populated via JS -->
                                </select>
                                <div id="doctorAvailabilityStatus" style="font-weight: bold; white-space: nowrap;">
                                    <!-- Status Icon/Text via JS -->
                                </div>
                            </div>
                            <div id="doctorAvailabilityMsg" class="hidden"></div>
                        </div>

                        <div class="input-group">
                            <label>Date <span class="required">*</span></label>
                            <input type="date" name="appointment_date" required tabindex="5">
                        </div>

                        <div class="input-group">
                            <label>Time <span class="required">*</span></label>
                            <input type="time" name="appointment_time" required tabindex="6">
                        </div>

                        <div class="input-group" style="grid-column: span 2;">
                            <label>Notes</label>
                            <textarea name="notes" rows="2" placeholder="Additional notes..." tabindex="7"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 pt-4 border-t mt-6">
                        <button type="button" onclick="appointmentManager.closeModal()" class="adv-btn-cancel" tabindex="8"><i class="fas fa-times"></i> Cancel</button>
                        <button type="submit" id="btnSaveOnly" class="adv-btn-save" tabindex="9"><i class="fas fa-save"></i> Save Appointment</button>
                    </div>
                </form>

                <script>
                    // Keyboard navigation and Focus Trap
                    document.addEventListener('DOMContentLoaded', function() {
                        const modal = document.getElementById('appointmentModal');
                        if (modal) {
                            modal.addEventListener('keydown', function(e) {
                                const focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
                                const elements = Array.from(modal.querySelectorAll(focusableElements)).filter(el => !el.disabled && el.offsetParent !== null);
                                
                                // Tab key focus trap
                                if (e.key === 'Tab') {
                                    if(elements.length > 0) {
                                        const firstElement = elements[0];
                                        const lastElement = elements[elements.length - 1];
                                        
                                        if (e.shiftKey) { // Shift + Tab
                                            if (document.activeElement === firstElement) {
                                                lastElement.focus();
                                                e.preventDefault();
                                            }
                                        } else { // Tab
                                            if (document.activeElement === lastElement) {
                                                firstElement.focus();
                                                e.preventDefault();
                                            }
                                        }
                                    }
                                }
                                
                                // Enter key acts like Tab (skip textareas and buttons)
                                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'BUTTON') {
                                    e.preventDefault();
                                    const index = elements.indexOf(document.activeElement);
                                    if (index > -1 && index < elements.length - 1) {
                                        elements[index + 1].focus();
                                    }
                                }
                            });
                        }
                    });
                </script>
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