<?php
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use Exception;

class PatientController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new \GM_HMS\Models\PatientModel();
    }
    
    /**
     * List patients with search
     * GET /api/patients
     */
    public function index() {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $term = $_GET['term'] ?? $_GET['search'] ?? '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            
            // Use model for standardized searching and pagination
            $filters = [
                'search' => $term,
                'city' => $_GET['city'] ?? null,
                'phone' => $_GET['phone'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'status' => $_GET['status'] ?? null
            ];
            
            // If logged-in user is a Doctor, restrict to their patients
            if (isset($this->currentUser['role']) && $this->currentUser['role'] === 'Doctor') {
                $filters['doctor_id'] = $this->currentUser['id'];
            } elseif (isset($_GET['doctor_id'])) {
                $filters['doctor_id'] = $_GET['doctor_id'];
            }
            
            $result = $this->model->getAllPatients($page, $limit, $filters);
            
            // Format: { success: true, data: { data: [...], pagination: {...} } }
            $this->respondSuccess($result);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get single patient
     * GET /api/patients/{id}
     */
    public function show($id) {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            // Base query to get patient and their last visit date from consultations
            $patient = $this->db->fetchOne(
                "SELECT p.*, CONCAT(p.first_name, ' ', p.last_name) as full_name,
                 (SELECT MAX(consultation_date) FROM consultations WHERE patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci) as last_visit
                 FROM patient p 
                 WHERE p.patient_id = ?",
                [$id]
            );
            
            if (!$patient) {
                $this->respondNotFound("Patient $id not found");
            }
            
            // If user is a Doctor, verify patient is allocated to them
            if (isset($this->currentUser['role']) && $this->currentUser['role'] === 'Doctor') {
                $doctorId = $this->currentUser['id'];
                
                // Check if patient has any appointments with this doctor
                $allocation = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM appointments 
                     WHERE patient_id COLLATE utf8mb4_general_ci = ? 
                     AND doctor_id = ?",
                    [$id, $doctorId]
                );
                
                if (!$allocation || $allocation['count'] == 0) {
                    $this->respond([
                        'success' => false,
                        'status' => 'error',
                        'error' => 'Access Denied',
                        'message' => "This patient is not allocated to you. Please contact reception to schedule an appointment with this patient.",
                        'error_code' => 'PATIENT_NOT_ALLOCATED'
                    ], 403);
                }
            }
            
            $this->respondSuccess($patient);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Create new patient
     * POST /api/patients
     */
    public function create() {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        $schema = [
            'required' => ['phone'],
            'properties' => [
                'title' => ['type' => 'string'],
                'first_name' => ['type' => 'string'],
                'last_name' => ['type' => 'string'],
                'sex' => ['type' => 'string', 'enum' => ['', 'Male', 'Female', 'Other']],
                'aadhar' => ['type' => 'string'],
                'phone' => ['type' => 'string', 'minLength' => 10],
                'birth_date' => ['type' => 'string'],
                'address' => ['type' => 'string'],
                'blood_group' => ['type' => 'string'],
                'occupation' => ['type' => 'string'],
                'vaccine_status' => ['type' => 'string'],
                'country' => ['type' => 'string'],
                'state' => ['type' => 'string'],
                'district' => ['type' => 'string'],
                'city' => ['type' => 'string'],
                'area' => ['type' => 'string'],
                'pincode' => ['type' => 'string']
            ]
        ];
        
        try {
            $data = $this->getJsonInput($schema);
            
            // Check if Aadhar already exists (if provided)
            $aadhar = $data['aadhar'] ?? null;
            if (!empty($aadhar)) {
                $existing = $this->db->fetchOne("SELECT patient_id FROM patient WHERE aadhar = ?", [$aadhar]);
                if ($existing) {
                    $this->respondBadRequest("Patient with Aadhar " . $aadhar . " already exists as " . $existing['patient_id']);
                }
            }
            
            // Use model to create patient - this handles ID generation and formatting
            $patientId = $this->model->createPatient($data);
            
            if ($patientId) {
                $this->respondCreated(['patient_id' => $patientId]);
            } else {
                $this->respondServerError("Failed to register patient");
            }
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Update patient info
     * PUT /api/patients/{id}
     */
    public function update($id) {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        
        try {
            $data = $this->getJsonInput();
            
            // All fields that can be updated via the edit form
            $allowedFields = [
                'title', 'first_name', 'last_name', 'sex', 'phone',
                'aadhar', 'birth_date', 'age', 'blood_group',
                'occupation', 'vaccine_status',
                'address', 'country', 'state', 'district', 'city', 'area', 'pincode',
                'status'
            ];

            $updates = [];
            $params = [];

            foreach ($allowedFields as $field) {
                // Use array_key_exists so we also allow setting a field to NULL/empty
                if (array_key_exists($field, $data)) {
                    $updates[] = "`$field` = ?";
                    // Store null for empty strings so DB is properly cleared
                    $params[] = ($data[$field] === '') ? null : $data[$field];
                }
            }

            // Recalculate age whenever birth_date is provided
            if (array_key_exists('birth_date', $data) && !empty($data['birth_date'])) {
                $birthDate = new \DateTime($data['birth_date']);
                $age = (new \DateTime())->diff($birthDate)->y;
                // Only add age if not already explicitly sent
                if (!array_key_exists('age', $data)) {
                    $updates[] = "`age` = ?";
                    $params[] = $age;
                }
            }
            
            if (empty($updates)) {
                $this->respondBadRequest("No valid fields to update");
            }
            
            $params[] = $id;
            $sql = "UPDATE patient SET " . implode(', ', $updates) . " WHERE patient_id = ?";
            
            $this->db->execute($sql, $params);
            $this->respondSuccess(null, "Patient $id updated");
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get patient issues
     */
    public function getIssues($id) {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $issues = $this->db->fetchAll(
                "SELECT * FROM patient_issue_description WHERE patient_id = ? ORDER BY created_at DESC",
                [$id]
            );
            $this->respondSuccess($issues);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Delete patient
     * DELETE /api/patients/{id}
     */
    public function delete($id) {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        
        try {
            // Check if patient exists
            $patient = $this->db->fetchOne("SELECT patient_id FROM patient WHERE patient_id = ?", [$id]);
            
            if (!$patient) {
                $this->respondNotFound("Patient $id not found");
            }
            
            // Hard delete - permanently remove the patient record from the database
            $sql = "DELETE FROM patient WHERE patient_id = ?";
            $result = $this->db->execute($sql, [$id]);

            if ($result) {
                $this->respondSuccess(null, "Patient $id deleted successfully");
            } else {
                $this->respondServerError("Failed to delete patient");
            }
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Check for duplicate patient by Aadhaar or Phone
     * GET /api/patients/check-duplicate
     */
    public function checkDuplicate() {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $aadhar = $_GET['aadhar'] ?? null;
            $phone = $_GET['phone'] ?? null;

            if (!$aadhar && !$phone) {
                $this->respondBadRequest("Aadhaar or Phone number required");
            }

            $sql = "SELECT patient_id, first_name, last_name FROM patient WHERE 1=0";
            $params = [];

            if ($aadhar) {
                $sql .= " OR aadhar = ?";
                $params[] = $aadhar;
            }

            if ($phone) {
                $sql .= " OR phone = ?";
                $params[] = $phone;
            }

            $existing = $this->db->fetchOne($sql, $params);

            if ($existing) {
                $this->respondSuccess([
                    'exists' => true,
                    'patient_id' => $existing['patient_id'],
                    'name' => $existing['first_name'] . ' ' . $existing['last_name']
                ], "Duplicate check completed");
            } else {
                $this->respondSuccess(['exists' => false], "No duplicate found");
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
