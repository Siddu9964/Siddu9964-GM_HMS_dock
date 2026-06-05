<?php
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use Exception;

class VendorController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new \GM_HMS\Models\VendorModel();
    }
    
    /**
     * Get pending indents for the logged-in vendor
     * GET /api/vendor/indents
     */
    public function getIndents() {
        $this->restrictMethod('GET');
        
        // Ensure vendor session exists
        if (!isset($_SESSION['vendor_id'])) {
            $this->respondUnauthorized("Please log in to your vendor account.");
        }
        
        try {
            $indents = $this->model->getPendingIndents($_SESSION['vendor_id']);
            $this->respondSuccess($indents);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Submit bulk quotations
     * POST /api/vendor/quotations
     */
    public function submitQuotation() {
        $this->restrictMethod('POST');
        
        if (!isset($_SESSION['vendor_id'])) {
            $this->respondUnauthorized("Please log in to your vendor account.");
        }
        
        try {
            $data = $this->getJsonInput();
            if (empty($data['items'])) {
                $this->respondBadRequest("No items selected for submission.");
            }
            
            $results = $this->model->submitBulkQuotation(
                $_SESSION['vendor_id'],
                $_SESSION['vendor_name'],
                $data['items']
            );
            
            $this->respondSuccess([
                'count' => count($results),
                'quotations' => $results
            ], "Successfully submitted " . count($results) . " quotations.");
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
