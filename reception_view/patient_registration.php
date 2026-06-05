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
    <title>Registered Patients - GM HMS</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Main Dashboard CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">

    <!-- Patient Module CSS -->
    <link rel="stylesheet" href="assets/css/patient.css">

    <style>
        /* Additional page-specific overrides if needed */
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
$pageTitle = 'Registered Patients';
include 'includes/reception_navbar.php';
?>

            <!-- Page Content -->
            <main class="reception-content">

                <!-- Page Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800" style="color: #056674;">Patient Records</h1>
                            <p class="text-gray-600 mt-1">Manage all patient records and information</p>
                        </div>
                        <button onclick="openAddPatientModal()" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            Patient Registration
                        </button>
                    </div>
                </div>

                <!-- Search and Filter Bar -->
                <div class="bg-white rounded-xl p-4 shadow-sm mb-6">
                    <div class="filter-bar" style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <input type="text" id="searchInput" class="search-input"
                            placeholder="Search by phone number or patient ID..."
                            style="flex: 1; min-width: 250px; padding: 10px 14px 10px 40px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">

                        <select id="genderFilter" class="filter-select"
                            style="padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="">All Genders</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>

                        <select id="statusFilter" class="filter-select"
                            style="padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>

                        <select id="pageSizeSelect" class="filter-select"
                            style="padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>
                </div>

                <!-- Patient Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div id="tableContainer">
                        <!-- Loading skeleton -->
                        <div id="loadingSkeleton" class="p-6">
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12"></div>
                        </div>

                        <!-- Actual table -->
                        <div id="patientTableWrapper" class="hidden">
                            <div style="overflow-x: auto;">
                                <table class="patient-table">
                                    <thead>
                                        <tr>
                                            <th>Patient ID</th>
                                            <th>Full Name</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Phone</th>
                                            <th>Aadhar</th>
                                            <th>City</th>
                                            <th>Registration Date</th>
                                            <th>Status</th>
                                            <th style="text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="patientTableBody">
                                        <!-- Rows will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="pagination"
                                style="display: flex; align-items: center; justify-content: space-between; padding: 20px; background: white; border-top: 1px solid #e5e7eb;">
                                <div class="pagination-info" style="color: #6b7280; font-size: 14px;">
                                    Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span
                                        id="totalRecords">0</span> patients
                                </div>
                                <div class="pagination-controls" style="display: flex; gap: 8px;">
                                    <button id="prevBtn" class="pagination-btn" onclick="changePage(-1)"
                                        style="padding: 8px 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; transition: all 0.2s ease; font-size: 14px; font-weight: 500;">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button id="nextBtn" class="pagination-btn" onclick="changePage(1)"
                                        style="padding: 8px 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; transition: all 0.2s ease; font-size: 14px; font-weight: 500;">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>

        </div>
    </div>

    <!-- Patient Form Modal -->
    <div id="patientModal" class="modal-overlay hidden" onclick="closeModalOnBackdrop(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div>
                    <h2 id="modalTitle">Add New Patient</h2>
                    <p class="text-white text-sm mt-1 opacity-90">Fill in the patient information below</p>
                </div>
                <button onclick="closePatientModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <form id="patientForm">
                    <input type="hidden" id="editPatientId" name="patient_id">

                    <!-- Personal Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-user-circle" style="color: #0FA4AF;"></i>
                            Personal Information
                        </h3>

                        <div class="form-grid cols-3">
                            <div class="input-group">
                                <label>Title</label>
                                <select name="title">
                                    <option value="">Select</option>
                                    <option value="Mr">Mr</option>
                                    <option value="Mrs">Mrs</option>
                                    <option value="Miss">Miss</option>
                                    <option value="Dr">Dr</option>
                                    <option value="Master">Mast</option>
                                    <option value="B/O">B/O</option>
                                    <option value="Baby Boy">Baby Boy</option>
                                    <option value="Baby Girl">Baby Girl</option>
                                    <option value="NA">N/A</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="input-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" placeholder="Enter first name">
                            </div>

                            <div class="input-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" placeholder="Enter last name">
                            </div>
                        </div>

                        <div class="form-grid cols-2">
                            <div class="input-group">
                                <label>Aadhar</label>
                                <input type="text" id="patientAadhar" name="aadhar" placeholder="XXXX XXXX XXXX"
                                    maxlength="14">
                            </div>

                            <div class="input-group">
                                <label>Phone <span class="required">*</span></label>
                                <input type="tel" id="patientPhone" name="phone" required
                                    placeholder="Enter Phone Number" maxlength="10">
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Gender</label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Male" id="male">
                                    <label for="male">
                                        <i class="fas fa-mars"></i> Male
                                    </label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Female" id="female">
                                    <label for="female">
                                        <i class="fas fa-venus"></i> Female
                                    </label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Other" id="other">
                                    <label for="other">
                                        <i class="fas fa-genderless"></i> Other
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-heartbeat text-blue-600"></i>
                            Medical Information
                        </h3>

                        <div class="form-grid cols-3">
                            <div class="input-group">
                                <label>Birth Date</label>
                                <input type="date" name="birth_date" id="birthDate">
                            </div>

                            <div class="input-group">
                                <label>Age</label>
                                <input type="number" name="age" id="age" min="0" max="150"
                                    placeholder="Auto-calculated or enter manually">
                            </div>

                            <div class="input-group">
                                <label>Blood Group</label>
                                <select name="blood_group">
                                    <option value="">Select</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-grid cols-2">
                            <div class="input-group">
                                <label>Occupation</label>
                                <input type="text" name="occupation" placeholder="Enter occupation">
                            </div>

                            <div class="input-group">
                                <label>Vaccine Status</label>
                                <select name="vaccine_status">
                                    <option value="">Select Status</option>
                                    <option value="Not Vaccinated">Not Vaccinated</option>
                                    <option value="Partially Vaccinated">Partially Vaccinated</option>
                                    <option value="Fully Vaccinated">Fully Vaccinated</option>
                                    <option value="Booster Taken">Booster Taken</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-orange-600"></i>
                            Address Information
                        </h3>

                        <div class="form-grid cols-3">
                            <!-- Pincode first — drives all address fields below -->
                            <div class="input-group">
                                <label>Pincode</label>
                                <div style="position:relative;">
                                    <input type="text" name="pincode" id="patientPincode"
                                        placeholder="Enter 6-digit pincode" maxlength="6"
                                        style="padding-right: 38px;">
                                    <span id="pincodeStatus" style="
                                        position:absolute; right:10px; top:50%;
                                        transform:translateY(-50%);
                                        font-size:16px; pointer-events:none;">
                                    </span>
                                </div>
                                <span id="pincodeMessage" style="font-size:12px; margin-top:4px; display:block;"></span>
                            </div>

                            <div class="input-group">
                                <label>Country <span style="font-size:11px;color:#9ca3af;font-weight:400;">✎ editable</span></label>
                                <input type="text" name="country" id="patientCountry"
                                    placeholder="Auto-filled or type manually">
                            </div>

                            <div class="input-group">
                                <label>State <span style="font-size:11px;color:#9ca3af;font-weight:400;">✎ editable</span></label>
                                <input type="text" name="state" id="patientState"
                                    placeholder="Auto-filled or type manually">
                            </div>

                            <div class="input-group">
                                <label>Region <span style="font-size:11px;color:#9ca3af;font-weight:400;">✎ editable</span></label>
                                <input type="text" name="region" id="patientRegion"
                                    placeholder="Auto-filled or type manually">
                            </div>

                            <div class="input-group">
                                <label>Division <span style="font-size:11px;color:#9ca3af;font-weight:400;">✎ editable</span></label>
                                <input type="text" name="division" id="patientDivision"
                                    placeholder="Auto-filled or type manually">
                            </div>

                            <div class="input-group">
                                <label>District <span style="font-size:11px;color:#9ca3af;font-weight:400;">✎ editable</span></label>
                                <input type="text" name="district" id="patientDistrict"
                                    list="districtDatalist"
                                    placeholder="Auto-filled or type manually"
                                    autocomplete="off">
                                <datalist id="districtDatalist"></datalist>
                            </div>

                            <div class="input-group">
                                <label>City / Taluk <span style="font-size:11px;color:#9ca3af;font-weight:400;">✎ editable</span></label>
                                <input type="text" name="city" id="patientCity"
                                    list="cityDatalist"
                                    placeholder="Auto-filled or type manually"
                                    autocomplete="off">
                                <datalist id="cityDatalist"></datalist>
                            </div>

                            <div class="input-group" style="position:relative;">
                                <label>Area / Post Office</label>
                                <!-- Hidden input that carries the actual value -->
                                <input type="hidden" name="area" id="patientAreaValue">
                                <!-- Visible search input -->
                                <div style="position:relative;">
                                    <input type="text" id="patientAreaSearch"
                                        placeholder="Type to search or enter manually"
                                        autocomplete="off"
                                        style="padding-right:36px; width:100%;">
                                    <span id="patientAreaClear"
                                        onclick="window._clearAreaSearch()"
                                        title="Clear"
                                        style="display:none; position:absolute; right:10px; top:50%;
                                               transform:translateY(-50%); cursor:pointer;
                                               font-size:14px; color:#9ca3af;">✕</span>
                                </div>
                                <!-- Suggestion dropdown -->
                                <div id="patientAreaDropdown" style="
                                    display:none;
                                    position:absolute;
                                    top:calc(100% - 2px);
                                    left:0; right:0;
                                    background:#fff;
                                    border:1px solid #0FA4AF;
                                    border-top:none;
                                    border-radius:0 0 10px 10px;
                                    max-height:200px;
                                    overflow-y:auto;
                                    z-index:999;
                                    box-shadow:0 8px 20px rgba(15,164,175,0.15);">
                                </div>
                            </div>

                        </div>

                        <!-- Address textarea at the bottom -->
                        <div class="input-group" style="margin-top: 16px;">
                            <label>Address</label>
                            <textarea name="address" rows="2" placeholder="Enter full address"></textarea>
                        </div>
                    </div>




                    <!-- Form Actions -->
                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" onclick="closePatientModal()" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Patient
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Duplicate Alert Modal -->
    <div id="duplicateModal" class="modal-overlay hidden">
        <div class="modal-content alert-modal">
            <div class="modal-body" style="padding: 40px 30px;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-id-card"></i>
                </div>
                <h2 class="alert-title">Patient Already Exists</h2>
                <div id="duplicateInfo" class="alert-message">
                    Patient details already exist. Please proceed to appointment booking.
                </div>
                <div class="alert-footer">
                    <button id="proceedToBookingBtn" class="btn btn-primary btn-full">
                        <i class="fas fa-calendar-check"></i> Proceed to Booking
                    </button>
                    <button onclick="document.getElementById('duplicateModal').classList.add('hidden')"
                        class="btn btn-secondary btn-full">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/patient.js"></script>
    <script>
        // Initialize patient manager
        window.patientManager = new PatientManager();
        window.patientManager.init();
    </script>
</body>

</html>