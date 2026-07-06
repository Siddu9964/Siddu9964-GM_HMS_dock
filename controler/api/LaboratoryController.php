<?php
/**
 * ============================================================
 * LaboratoryController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * ------------------------------------------------------------
 *
 * 1. GET /api/laboratory/services
 *    Response: All lab test categories and services with rates
 *
 * 2. POST /api/laboratory/services
 *    Body: { "category":"Haematology", "test_name":"HbA1C", "rate":350, "description":"Glycated haemoglobin" }
 *
 * 3. PUT /api/laboratory/services/{category}/{id}
 *    Body: { "rate":400, "test_name":"HbA1C Updated" }
 *
 * 4. DELETE /api/laboratory/services/{category}/{id}
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\LaboratoryModel;
use Exception;

/**
 * Laboratory API Controller
 */
class LaboratoryController extends BaseController
{
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new LaboratoryModel();
    }

    /**
     * POST /api/laboratory/services
     * Creates a new service.
     */
    public function createService()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();

        try {
            $input = $this->getJsonInput();
            $category = $input['category'] ?? '';
            $result = false;

            switch ($category) {
                case 'lab':
                    $result = $this->model->createLabService($input);
                    break;
                case 'radiology':
                    $result = $this->model->createRadiologyService($input);
                    break;
                case 'other':
                    $result = $this->model->createOtherService($input);
                    break;
                default:
                    $this->respondBadRequest("Invalid service category: $category");
            }

            if ($result) {
                $this->respondSuccess(null, "Service created successfully");
            }
            else {
                $this->respondServerError("Failed to create service");
            }
        }
        catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/laboratory/services/{type}/{id}
     * Deletes a service.
     */
    public function deleteService($type, $id)
    {
        $this->restrictMethod('DELETE');
        $this->requireAuth();

        try {
            $result = false;
            switch ($type) {
                case 'lab':
                    $result = $this->model->deleteLabService($id);
                    break;
                case 'radiology':
                    $result = $this->model->deleteRadiologyService($id);
                    break;
                case 'other':
                    $result = $this->model->deleteOtherService($id);
                    break;
                default:
                    $this->respondBadRequest("Invalid service type: $type");
            }

            if ($result) {
                $this->respondSuccess(null, "Service deleted successfully");
            }
            else {
                $this->respondServerError("Failed to delete service");
            }
        }
        catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/laboratory/services
     * Returns all laboratory, radiology, and other services.
     */
    public function getServices()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $services = $this->model->getAllServices();
            $this->respondSuccess($services);
        }
        catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/laboratory/services/{type}/{id}
     * Updates a specific service.
     */
    public function updateService($type, $id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();

        try {
            $input = $this->getJsonInput();
            $result = false;

            switch ($type) {
                case 'lab':
                    $result = $this->model->updateLabService($id, $input);
                    break;
                case 'radiology':
                    $result = $this->model->updateRadiologyService($id, $input);
                    break;
                case 'other':
                    $result = $this->model->updateOtherService($id, $input);
                    break;
                default:
                    $this->respondBadRequest("Invalid service type: $type");
            }

            if ($result) {
                $this->respondSuccess(null, "Service updated successfully");
            }
            else {
                $this->respondServerError("Failed to update service");
            }
        }
        catch (Exception $e) {
            $this->handleException($e);
        }
    }
}

