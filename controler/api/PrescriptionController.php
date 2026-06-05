<?php
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use GM_HMS\Database\SecureDatabase;
use Exception;

class PrescriptionController extends BaseController {
    
    protected $model;

    public function __construct() {
        parent::__construct();
        $this->model = new \GM_HMS\Models\PrescriptionModel();
    }

    /**
     * Get all prescriptions (for receptionist list)
     * Route: GET /api/prescriptions
     */
    public function listAll() {
        $this->requireAuth();
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $prescriptions = $this->model->getAllPrescriptions($limit);
            
            foreach ($prescriptions as &$p) {
                // Extract medicines from soap_plan (from consultation)
                $p['medicines'] = [];
                if (!empty($p['soap_plan'])) {
                    $plan = json_decode($p['soap_plan'], true);
                    if (isset($plan['medications'])) {
                        $p['medicines'] = $plan['medications'];
                    }
                }
                
                $p['age'] = $this->model->calculateAge($p['birth_date']);
            }

            $this->respondSuccess($prescriptions);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get detailed prescription view for receptionist
     * Route: GET /api/prescriptions/receptionist/view/{patient_id}
     */
    public function getReceptionistView($patientId) {
        $this->requireAuth();
        try {
            $prescriptions = $this->model->getPrescriptionsByPatient($patientId);
            
            foreach ($prescriptions as &$p) {
                // Extract medicines from soap_plan (from consultation)
                $p['medicines'] = [];
                if (!empty($p['soap_plan'])) {
                    $plan = json_decode($p['soap_plan'], true);
                    if (isset($plan['medications'])) {
                        $p['medicines'] = $plan['medications'];
                    }
                }
                
                $p['age'] = $this->model->calculateAge($p['birth_date']);
            }

            $this->respondSuccess([
                'patient_id' => $patientId,
                'prescriptions' => $prescriptions
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Log prescription print action
     * Route: POST /api/prescriptions/log-print
     */
    public function logPrint() {
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            if (empty($data['prescription_id'])) {
                $this->respondBadRequest('Prescription ID is required');
                return;
            }

            $userId = $this->currentUser['id'];
            $this->model->logPrintActivity($data['prescription_id'], $userId);
            
            $this->respondSuccess(null, 'Print action logged successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get all prescriptions for the logged-in doctor
     * Route: GET /api/prescriptions/doctor/{id}
     */
    /**
     * Get all prescriptions for the logged-in doctor (from consultations)
     * Route: GET /api/prescriptions/doctor/{id}
     */
    public function getDoctorPrescriptions($doctorId) {
        try {
            // Basic validation
            if (empty($doctorId)) {
                $this->respondBadRequest('Doctor ID is required');
            }

            // Fetch prescriptions from consultations
            $sql = "SELECT c.consultation_id as prescription_id,
                           c.consultation_date as prescription_date,
                           c.created_at,
                           c.patient_id, c.doctor_id, c.status,
                           c.soap_plan, 
                           c.final_diagnosis as diagnosis,
                           pat.first_name, pat.last_name, pat.sex as gender, pat.birth_date as dob, pat.age
                    FROM consultations c
                    LEFT JOIN patient pat ON c.patient_id = pat.patient_id
                    WHERE c.doctor_id = ?
                    ORDER BY c.consultation_date DESC, c.consultation_time DESC";
            
            $prescriptions = $this->db->fetchAll($sql, [$doctorId]);
            
            foreach ($prescriptions as &$p) {
                // Extract medicines from soap_plan
                $p['medicines'] = [];
                if (!empty($p['soap_plan'])) {
                    $plan = json_decode($p['soap_plan'], true);
                    if (isset($plan['medications'])) {
                        $p['medicines'] = $plan['medications'];
                    }
                }
            }

            $this->respondSuccess(['prescriptions' => $prescriptions]);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Create a new prescription (Saves as a Completed Consultation)
     * Route: POST /api/prescriptions
     */
    public function create() {
        try {
            $data = $this->getJsonInput();
            
            // Validation
            $required = ['patient_id', 'doctor_id', 'medicines'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->respondBadRequest("Field '$field' is required");
                }
            }

            // Generate Consultation ID usually handled by DB auto-increment or UUID, but here we generate manual ID to match pattern
            // Actually, consultations usually has auto-increment ID or UUID. 
            // Let's assume we generate one or let logic handle it. 
            // Based on ConsultationController, it seems to rely on auto-increment or manual? 
            // Wait, ConsultationController creates manual ID? No, it uses INSERT without ID usually? 
            // Let's check ConsultationController::create in snippet Step 90... no, it didn't show ID gen.
            // But previous PrescriptionController::create generated 'PRE-...'.
            // Let's generate 'CON-' ID to be safe if column isn't auto-increment.
            $consultationId = 'CON-' . date('Ymd') . '-' . rand(1000, 9999);
            
            // Prepare soap_plan with medications
            $medications = is_array($data['medicines']) ? $data['medicines'] : json_decode($data['medicines'], true);
            $soapPlan = json_encode([
                'medications' => $medications,
                'purpose' => $data['diagnosis'] ?? 'Prescription only',
                'warnings' => $data['dietary_advice'] ?? ''
            ]);

            $sql = "INSERT INTO consultations (
                consultation_id, patient_id, doctor_id, appointment_id,
                consultation_date, consultation_time, 
                soap_plan, 
                final_diagnosis, 
                clinical_notes, 
                follow_up_date, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $consultationId,
                $data['patient_id'],
                $data['doctor_id'],
                $data['appointment_id'] ?? null,
                date('Y-m-d'), // date
                date('H:i:s'), // time
                $soapPlan,
                $data['diagnosis'] ?? null,
                $data['general_instructions'] ?? null, // Stored in clinical_notes
                $data['follow_up_date'] ?? null,
                'Completed'
            ];

            $this->db->execute($sql, $params);
            
            $this->respondSuccess(['prescription_id' => $consultationId], 'Prescription created successfully');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get single prescription details
     * Route: GET /api/prescriptions/{id}
     */
    public function show($id) {
        try {
            // Fetch from consultations
            $sql = "SELECT c.consultation_id as prescription_id,
                           c.consultation_date as prescription_date,
                           c.consultation_time,
                           c.appointment_id,
                           c.patient_id, c.doctor_id, 
                           c.soap_plan, 
                           c.final_diagnosis as diagnosis,
                           c.clinical_notes,
                           c.follow_up_instructions,
                           c.status,
                           pat.first_name, pat.last_name, pat.sex as gender, pat.birth_date, pat.age, pat.phone,
                           doc.full_name as doctor_name, doc.specialization
                    FROM consultations c
                    LEFT JOIN patient pat ON c.patient_id = pat.patient_id
                    LEFT JOIN doctors doc ON c.doctor_id = doc.doctor_id
                    WHERE c.consultation_id = ?";
            
            $prescription = $this->db->fetchOne($sql, [$id]);

            if (!$prescription) {
                // FALLBACK: Try legacy prescriptions table if not found in consultations
                // This handles old data if any
                $sqlLegacy = "SELECT p.*, pat.first_name, pat.last_name, pat.phone, pat.age, pat.Sex as gender,
                               doc.full_name as doctor_name
                        FROM prescriptions p
                        LEFT JOIN patient pat ON p.patient_id = pat.patient_id
                        LEFT JOIN doctors doc ON p.doctor_id = doc.doctor_id
                        WHERE p.prescription_id = ?";
                $prescription = $this->db->fetchOne($sqlLegacy, [$id]);
                
                if (!$prescription) {
                    $this->respondNotFound('Prescription not found');
                }
                
                if (!empty($prescription['medicines'])) {
                    $prescription['medicines'] = json_decode($prescription['medicines'], true);
                }
            } else {
                // Process consultation data
                $prescription['medicines'] = [];
                if (!empty($prescription['soap_plan'])) {
                    $plan = json_decode($prescription['soap_plan'], true);
                    if (isset($plan['medications'])) {
                        $prescription['medicines'] = $plan['medications'];
                    }
                }
                
                // Construct general_instructions from valid columns
                $notes = [];
                if (!empty($prescription['clinical_notes'])) $notes[] = $prescription['clinical_notes'];
                if (!empty($prescription['follow_up_instructions'])) $notes[] = "Follow-up: " . $prescription['follow_up_instructions'];
                $prescription['general_instructions'] = implode("\n", $notes);
                
                $prescription['age'] = $this->model->calculateAge($prescription['birth_date'] ?? null) ?: ($prescription['age'] ?? 'N/A');
            }

            $this->respondSuccess($prescription);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get prescription history for a specific patient
     * Route: GET /api/prescriptions/patient/{id}
     */
    public function getPatientHistoryLog($patientId) {
        try {
            if (empty($patientId)) {
                $this->respondBadRequest('Patient ID is required');
            }

            // Fetch complete prescription history from consultations
            $sql = "SELECT 
                        c.consultation_id as prescription_id,
                        c.consultation_date as prescription_date,
                        c.final_diagnosis as diagnosis,
                        c.soap_plan,
                        c.clinical_notes,
                        c.follow_up_instructions,
                        c.follow_up_date,

                        c.doctor_id,
                        d.full_name as doctor_name,
                        d.specialization,
                        d.designation
                    FROM consultations c
                    LEFT JOIN doctors d ON c.doctor_id = d.doctor_id
                    WHERE c.patient_id = ? AND c.status = 'Completed'
                    ORDER BY c.consultation_date DESC, c.consultation_time DESC";
            
            $history = $this->db->fetchAll($sql, [$patientId]);
            
            foreach ($history as &$h) {
                // Extract medicines
                $h['medicines'] = [];
                if (!empty($h['soap_plan'])) {
                    $plan = json_decode($h['soap_plan'], true);
                    if (isset($plan['medications'])) {
                        $h['medicines'] = $plan['medications'];
                    }
                }
                
                // Construct general_instructions from valid columns
                $notes = [];
                if (!empty($h['clinical_notes'])) $notes[] = $h['clinical_notes'];
                if (!empty($h['follow_up_instructions'])) $notes[] = "Follow-up: " . $h['follow_up_instructions'];
                $h['general_instructions'] = implode("\n", $notes);
            }
            
            $this->respondSuccess([
                'history' => $history,
                'debug_info' => 'File: ' . __FILE__ . ' | Ver: 3.0'
            ]);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get the latest prescription for a patient
     * Route: GET /api/prescriptions/patient/{id}/latest
     */
    public function getLatestByPatient($patientId) {
        try {
            if (empty($patientId)) {
                $this->respondBadRequest('Patient ID is required');
            }

            $sql = "SELECT * FROM prescriptions 
                    WHERE patient_id = ? 
                    ORDER BY prescription_date DESC, created_at DESC 
                    LIMIT 1";
            
            $latest = $this->db->fetchOne($sql, [$patientId]);

            if ($latest) {
                if (!empty($latest['medicines'])) {
                   $latest['medicines'] = json_decode($latest['medicines'], true);
                }
                $this->respondSuccess($latest);
            } else {
                $this->respondSuccess([]); 
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
