<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header("Location: ../doctor_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions Management - GM HMS</title>
    <!-- CSS Dependencies -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css">
    <link rel="stylesheet" href="assets/css/notebook.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Herr+Von+Muellerhoff&display=swap" rel="stylesheet">
    
    <style>
        .medicine-row {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>

    <!-- Notebook View Overlay -->
    <div id="notebook-view" class="notebook-overlay">
        <div class="notebook-container">
            <!-- Timeline Sidebar -->
            <div class="notebook-sidebar">
                <h5><i class="fas fa-history mr-2"></i>History</h5>
                <div id="notebook-timeline">
                    <!-- Loaded dynamically -->
                </div>
            </div>

            <!-- Notebook Page (Book Spread) -->
            <div class="book-spread">
                <i class="fas fa-times close-notebook" onclick="closeNotebookView()"></i>
                
                <!-- Left Page (Cover/Title) -->
                <div class="book-page left-page" id="book-left">
                    <!-- Dynamic Title Content -->
                </div>

                <!-- Right Page (Details) -->
                <div class="book-page right-page" id="book-right">
                    <!-- Dynamic Detailed Content -->
                </div>
            </div>
        </div>
    </div>

    <div class="doctor-layout">
        <!-- Sidebar -->
        <?php include 'includes/doctor_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="doctor-main-content">
            <!-- Top Navbar -->
            <?php include 'includes/doctor_navbar.php'; ?>
            
            <div class="container-fluid mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="main-page-title"><i class="fas fa-prescription"></i>Prescription Management</h2>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addPrescriptionModal" onclick="addMedicineRow()" style="background: var(--primary-gradient); border: none;">
                        <i class="fas fa-plus mr-1"></i> New Prescription
                    </button>
                </div>

                <!-- Prescriptions Table -->
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="prescriptions-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Prescription ID</th>
                            <th>Patient</th>
                            <th>Date</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="prescriptions-table-body">
                        <!-- Loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Prescription Modal -->
<div class="modal fade" id="addPrescriptionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header text-white" style="background: var(--primary-gradient); border: none;">
                <h5 class="modal-title" style="font-weight: 700;">Create New Prescription</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="add-prescription-form">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Patient</label>
                            <select class="form-control" id="patient_select" required>
                                <option value="">Select Patient...</option>
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Diagnosis</label>
                            <input type="text" class="form-control" id="diagnosis" placeholder="e.g. Fever, Infection">
                        </div>
                    </div>

                    <h6 class="mt-3">Medicines & Dosage <button type="button" class="btn btn-sm btn-outline-primary ml-2" id="add-medicine-row"><i class="fas fa-plus"></i> Add Row</button></h6>
                    <div id="medicine-rows-container">
                        <!-- Medicine rows go here -->
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6 form-group">
                            <label>General Instructions</label>
                            <textarea class="form-control" id="general_instructions" rows="3" placeholder="Rest instructions..."></textarea>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Dietary Advice</label>
                            <textarea class="form-control" id="dietary_advice" rows="3" placeholder="e.g. Avoid spicy food"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Follow-up Date</label>
                        <input type="date" class="form-control" id="follow_up_date" style="max-width: 200px;">
                    </div>

                    <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save & Issue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- View Prescription Modal -->
    <div class="modal fade" id="viewPrescriptionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Prescription Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="viewPrescriptionModalBody">
                    <!-- Content loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Print</button>
                </div>
            </div>
        </div>
    </div>

    </div> <!-- End .doctor-main-content -->
</div> <!-- End .doctor-layout -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>
<!-- Moment.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<!-- Custom Logic -->
<script src="assets/js/doctor_utils.js"></script>
<script src="assets/js/prescription.js"></script>

</body>
</html>
