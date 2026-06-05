<?php
namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class PrescriptionModel
{
    protected $db;
    protected $table = 'prescriptions';

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get all prescriptions with patient and doctor details
     */
    /**
     * Get all prescriptions (from completed consultations)
     */
    public function getAllPrescriptions($limit = 50)
    {
        $sql = "SELECT c.consultation_id as prescription_id,
                       c.consultation_date as prescription_date,
                       c.patient_id, c.doctor_id, c.status,
                       c.soap_plan, 
                       c.final_diagnosis as diagnosis,
                       pat.first_name, pat.last_name, pat.sex as gender, pat.birth_date, pat.phone as patient_phone,
                       doc.full_name as doctor_name, doc.specialization
                FROM consultations c
                LEFT JOIN patient pat ON c.patient_id = pat.patient_id
                LEFT JOIN doctors doc ON c.doctor_id = doc.doctor_id
                WHERE c.status = 'Completed'
                ORDER BY c.consultation_date DESC, c.consultation_time DESC LIMIT ?";

        $prescriptions = $this->db->fetchAll($sql, [$limit]);

        // Inject settings into each prescription (for printing)
        $settings = $this->getSystemSettings();
        foreach ($prescriptions as &$p) {
            $p['hospital_name'] = $settings['system_name'] ?? 'GM HMS Multispeciality';
            $p['hospital_logo'] = $settings['institution_logo'] ?? null;
            $p['hospital_address'] = $settings['address'] ?? 'Main Road, Health City';
            $p['hospital_phone'] = $settings['phone'] ?? '+91 99999 88888';
            $p['hospital_email'] = $settings['email'] ?? 'contact@gmhms.com';
        }

        return $prescriptions;
    }

    /**
     * Get prescription details for a patient (from consultations)
     */
    public function getPrescriptionsByPatient($patientId)
    {
        $sql = "SELECT c.consultation_id as prescription_id,
                       c.consultation_date as prescription_date,
                       c.patient_id, c.doctor_id, c.status,
                       c.soap_plan, 
                       c.final_diagnosis as diagnosis,
                       pat.first_name, pat.last_name, pat.sex as gender, pat.birth_date, pat.phone as patient_phone,
                       doc.full_name as doctor_name, doc.specialization
                FROM consultations c
                LEFT JOIN patient pat ON c.patient_id = pat.patient_id
                LEFT JOIN doctors doc ON c.doctor_id = doc.doctor_id
                WHERE c.patient_id = ? AND c.status = 'Completed'
                ORDER BY c.consultation_date DESC, c.consultation_time DESC";

        $prescriptions = $this->db->fetchAll($sql, [$patientId]);

        // Inject settings into each prescription (for printing)
        $settings = $this->getSystemSettings();
        foreach ($prescriptions as &$p) {
            $p['hospital_name'] = $settings['system_name'] ?? 'GM HMS Multispeciality';
            $p['hospital_logo'] = $settings['institution_logo'] ?? null;
            $p['hospital_address'] = $settings['address'] ?? 'Main Road, Health City';
            $p['hospital_phone'] = $settings['phone'] ?? '+91 99999 88888';
            $p['hospital_email'] = $settings['email'] ?? 'contact@gmhms.com';
        }

        return $prescriptions;
    }

    /**
     * Get a specific prescription by ID
     */
    public function getPrescriptionById($prescriptionId)
    {
        $sql = "SELECT p.*, 
                       pat.first_name, pat.last_name, pat.sex as gender, pat.birth_date, pat.phone as patient_phone,
                       doc.full_name as doctor_name, doc.specialization, doc.signature as signature_path
                FROM prescriptions p
                LEFT JOIN patient pat ON p.patient_id = pat.patient_id
                LEFT JOIN doctors doc ON p.doctor_id = doc.doctor_id
                WHERE p.prescription_id = ?";

        $p = $this->db->fetchOne($sql, [$prescriptionId]);
        if ($p) {
            $settings = $this->getSystemSettings();
            $p['hospital_name'] = $settings['system_name'] ?? 'GM HMS Multispeciality';
            $p['hospital_logo'] = $settings['institution_logo'] ?? null;
            $p['hospital_address'] = $settings['address'] ?? 'Main Road, Health City';
            $p['hospital_phone'] = $settings['phone'] ?? '+91 99999 88888';
            $p['hospital_email'] = $settings['email'] ?? 'contact@gmhms.com';
        }
        return $p;
    }

    /**
     * Fetch settings as key-value pairs
     */
    public function getSystemSettings()
    {
        $sql = "SELECT type, description FROM settings";
        $results = $this->db->fetchAll($sql);
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['type']] = $row['description'];
        }
        return $settings;
    }

    /**
     * Log prescription print activity
     */
    public function logPrintActivity($prescriptionId, $userId)
    {
        $sql = "INSERT INTO audit_logs (event_type, event_category, severity, resource, action, user_id, ip_address, request_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $requestData = json_encode([
            'prescription_id' => $prescriptionId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        return $this->db->execute($sql, [
            'PRINT',                // event_type
            'Clinical',             // event_category
            'Info',                 // severity
            'Prescriptions',        // resource
            'PRINT_PRESCRIPTION',   // action
            $userId,                // user_id
            $_SERVER['REMOTE_ADDR'] ?? 'unknown', // ip_address
            $requestData            // request_data
        ]);
    }

    /**
     * Calculate Age from birth_date
     */
    public function calculateAge($birthDate)
    {
        if (!$birthDate)
            return 'N/A';
        $birthDate = new \DateTime($birthDate);
        $today = new \DateTime();
        $age = $today->diff($birthDate);
        return $age->y;
    }
}
