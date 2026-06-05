<?php
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use Exception;

class ReceptionController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get dashboard summary statistics
     */
    public function getDashboardSummary() {
        try {
            $this->requireAuth();
            $today = date('Y-m-d');
            
            $summary = [];
            
            // Patients registered today
            $res = $this->db->fetchOne("SELECT COUNT(*) as count FROM patient WHERE DATE(date) = ?", [$today]);
            $summary['patients_today'] = $res['count'] ?? 0;
            
            // Appointments scheduled today
            $res = $this->db->fetchOne("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ?", [$today]);
            $summary['appointments_scheduled'] = $res['count'] ?? 0;
            
            // Pending payments (OPD)
            $res = $this->db->fetchOne("SELECT COUNT(*) as count FROM opd_billing_master WHERE payment_status = 'Pending'");
            $summary['pending_payments'] = $res['count'] ?? 0;
            
            // Doctors active
            $res = $this->db->fetchOne("SELECT COUNT(*) as count FROM doctors WHERE status = 'Active'");
            $summary['doctors_available'] = $res['count'] ?? 0;
            
            // Recent Appointments
            $summary['recent_appointments'] = $this->db->fetchAll(
                "SELECT a.*, p.first_name, p.last_name 
                 FROM appointments a 
                 JOIN patient p ON a.patient_id = p.patient_id 
                 ORDER BY a.created_at DESC LIMIT 5"
            );
            
            $this->respondSuccess($summary);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get today's appointments for the live queue
     */
    public function getTodayAppointments() {
        try {
            $this->requireAuth();
            $today = date('Y-m-d');
            
            $appointments = $this->db->fetchAll(
                "SELECT a.*, p.first_name, p.last_name, d.full_name as doctor_name
                 FROM appointments a
                 JOIN patient p ON a.patient_id = p.patient_id
                 LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                 WHERE a.appointment_date = ?
                 ORDER BY a.appointment_time ASC",
                [$today]
            );
            
            $this->respondSuccess($appointments);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get recent patients
     */
    public function getRecentPatients() {
        try {
            $this->requireAuth();
            $patients = $this->db->fetchAll(
                "SELECT patient_id, first_name, last_name, date as registration_date, time
                 FROM patient
                 ORDER BY sl_no DESC
                 LIMIT 10"
            );
            $this->respondSuccess($patients);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update receptionist profile
     */
    public function updateProfile() {
        try {
            $this->requireAuth();
            $data = $this->getJsonInput();
            
            // Update staff table as current users are in staff
            // Assuming current user ID is staff sl_no or username
            $userId = $this->currentUser['id'];
            
            $allowed = ['full_name', 'email', 'phone', 'address'];
            $updates = [];
            $params = [];
            
            foreach ($data as $k => $v) {
                if (in_array($k, $allowed)) {
                    $updates[] = "`$k` = ?";
                    $params[] = $v;
                }
            }
            
            if (empty($updates)) {
                $this->respondBadRequest('No fields to update');
            }
            
            $params[] = $userId;
            $this->db->execute("UPDATE staff SET " . implode(', ', $updates) . " WHERE sl_no = ?", $params);
            
            $this->respondSuccess(null, 'Profile updated successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
