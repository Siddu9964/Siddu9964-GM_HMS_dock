<?php
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\AppointmentModel;

class AppointmentController extends BaseController
{
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new AppointmentModel();
    }

    /**
     * GET /api/appointments
     * Get all appointments with optional filters
     */
    public function index()
    {
        $this->restrictMethod('GET');

        try {
            $this->requireAuth();
            $filters = [];

            // --- SECURITY RESTRICTION ---
            // If logged-in user is a Doctor, force the doctor_id filter
            if (isset($this->currentUser['role']) && $this->currentUser['role'] === 'Doctor') {
                $filters['doctor_id'] = $this->currentUser['id'];
            }
            // ----------------------------

            if (isset($_GET['status']) && $_GET['status'] !== '') {
                $filters['status'] = $_GET['status'];
            }
            if (isset($_GET['doctor_id']) && $_GET['doctor_id'] !== '') {
                $filters['doctor_id'] = $_GET['doctor_id'];
            }
            if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
                $filters['date_from'] = $_GET['date_from'];
            }
            if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
                $filters['date_to'] = $_GET['date_to'];
            }
            if (isset($_GET['date']) && $_GET['date'] !== '') {
                $filters['date'] = $_GET['date'];
            }
            if (isset($_GET['type']) && $_GET['type'] !== '') {
                $filters['type'] = $_GET['type'];
            }
            if (isset($_GET['limit']) && $_GET['limit'] !== '') {
                $filters['limit'] = $_GET['limit'];
            }
            if (isset($_GET['search']) && $_GET['search'] !== '') {
                $filters['search'] = $_GET['search'];
            }

            $appointments = $this->model->getAllAppointments($filters);
            $this->respondSuccess($appointments);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/appointments/{id}
     * Get single appointment by ID
     */
    public function show($id)
    {
        $this->restrictMethod('GET');

        try {
            $appointment = $this->model->getAppointmentById($id);

            if (!$appointment) {
                $this->respondNotFound('Appointment not found');
            }

            $this->respondSuccess($appointment);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/appointments
     * Create new appointment
     */
    public function create()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();

        // Validation Schema
        $schema = [
            'required' => ['patient_id', 'doctor_id', 'appointment_date', 'appointment_time'],
            'properties' => [
                'patient_id' => ['type' => 'string'],
                'doctor_id' => ['type' => 'string'],
                'phone' => ['type' => 'string'],
                'appointment_date' => ['type' => 'string'], // format: YYYY-MM-DD
                'appointment_time' => ['type' => 'string'],
                'reason' => ['type' => 'string'],
                'notes' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'consultation_fee' => ['type' => 'string'], // or number, but input often string
                'discount' => ['type' => 'string'],
                'total_amount' => ['type' => 'string'],
                'payment_status' => ['type' => 'string'],
                'payment_mode' => ['type' => 'string']
            ]
        ];

        $data = $this->getJsonInput($schema);

        try {
            $appointmentId = $this->model->createAppointment($data);
            $appointment = $this->model->getAppointmentById($appointmentId);

            $this->respondSuccess($appointment, 'Appointment scheduled successfully');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/appointments/{id}
     * Update appointment
     */
    public function update($id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();

        $data = $this->getJsonInput();

        try {
            $existing = $this->model->getAppointmentById($id);
            if (!$existing) {
                $this->respondNotFound('Appointment not found');
            }

            // SECURITY: Ensure doctor can only update their own appointments
            if (isset($this->currentUser['role']) && $this->currentUser['role'] === 'Doctor') {
                if ($existing['doctor_id'] !== $this->currentUser['id']) {
                    $this->respondForbidden('Unauthorized: You can only update your own appointments');
                    return;
                }
            }

            $this->model->updateAppointment($id, $data);
            $updated = $this->model->getAppointmentById($id);

            $this->respondSuccess($updated, 'Appointment updated successfully');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/appointments/{id}
     * Delete/Cancel appointment
     */
    public function delete($id)
    {
        $this->restrictMethod('DELETE');
        $this->requireAuth();

        try {
            $existing = $this->model->getAppointmentById($id);
            if (!$existing) {
                $this->respondNotFound('Appointment not found');
            }

            // SECURITY: Ensure doctor can only delete their own appointments
            if (isset($this->currentUser['role']) && $this->currentUser['role'] === 'Doctor') {
                if ($existing['doctor_id'] !== $this->currentUser['id']) {
                    $this->respondForbidden('Unauthorized: You can only delete your own appointments');
                    return;
                }
            }

            $this->model->deleteAppointment($id);
            $this->respondSuccess(null, 'Appointment deleted successfully');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/appointments/stats
     * Get appointments statistics
     */
    public function getStats()
    {
        $this->restrictMethod('GET');

        try {
            $stats = $this->model->getStatistics();
            $this->respondSuccess($stats);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    /**
     * GET /api/appointments/departments
     */
    public function getDepartments()
    {
        $this->restrictMethod('GET');
        try {
            $departments = $this->model->getAllDepartments();
            $this->respondSuccess($departments);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/appointments/doctors
     */
    public function getDoctors()
    {
        $this->restrictMethod('GET');
        try {
            $deptId = $_GET['department_id'] ?? null;
            if (!$deptId) {
                $this->respondBadRequest('Department ID is required');
            }

            $doctors = $this->model->getDoctorsByDepartment($deptId);
            $this->respondSuccess($doctors);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/appointments/check-availability
     */
    public function checkAvailability()
    {
        $this->restrictMethod('GET');
        try {
            $doctorId = $_GET['doctor_id'] ?? null;
            $date = $_GET['date'] ?? null;
            $time = $_GET['time'] ?? null;

            if (!$doctorId || !$date || !$time) {
                $this->respondBadRequest('Missing required parameters');
            }

            $isAvailable = $this->model->checkAvailability($doctorId, $date, $time);

            if ($isAvailable) {
                $this->respondSuccess(['available' => true], 'Doctor is available');
            } else {
                // Return 200 OK but with available=false (application logic) OR 409 Conflict?
                // The prompt says "Block the next process and display a message".
                // Client side will handle logic. 
                // I'll return success: false in data or just clear JSON.
                $this->respondSuccess(['available' => false], 'Doctor is unavailable at this time');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
