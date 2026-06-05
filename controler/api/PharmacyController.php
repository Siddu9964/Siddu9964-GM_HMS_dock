<?php
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\PharmacyModel;
use GM_HMS\Models\PharmacyBillingModel;

/**
 * PharmacyController
 * Handles all Pharmacy API endpoints
 *
 * Routes registered in /api/index.php:
 *   GET  /api/pharmacy/dashboard-summary
 *   GET  /api/pharmacy/low-stock-alerts
 *   GET  /api/pharmacy/expiry-alerts
 *   GET  /api/pharmacy/prescriptions
 *   GET  /api/pharmacy/patients/all
 *   GET  /api/pharmacy/search-patients?q=
 *   GET  /api/pharmacy/patient-prescription?patient_id=
 *   GET  /api/pharmacy/products/search?q=
 *   POST /api/pharmacy/billing/create
 */
class PharmacyController extends BaseController {

    private PharmacyModel        $model;
    private PharmacyBillingModel $billing;

    public function __construct() {
        parent::__construct();
        $this->model   = new PharmacyModel();
        $this->billing = new PharmacyBillingModel();
    }

    // ──────────────────────────────────────────────────────
    // DASHBOARD  GET /api/pharmacy/dashboard-summary
    // ──────────────────────────────────────────────────────
    public function getDashboardSummary(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess([
                'stats'          => $this->model->getDashboardStats(),
                'expiring_list'  => $this->model->getExpiringProductsList(5),
                'low_stock_list' => $this->model->getLowStockProductsList(5),
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    // ──────────────────────────────────────────────────────
    // LOW STOCK  GET /api/pharmacy/low-stock-alerts
    // ──────────────────────────────────────────────────────
    public function getLowStockAlerts(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->model->getAllProductsSortedByStock());
        } catch (Exception $e) { $this->handleException($e); }
    }

    // ──────────────────────────────────────────────────────
    // EXPIRY  GET /api/pharmacy/expiry-alerts
    // ──────────────────────────────────────────────────────
    public function getExpiryAlerts(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->model->getAllProductsSortedByExpiry());
        } catch (Exception $e) { $this->handleException($e); }
    }

    // ──────────────────────────────────────────────────────
    // PRESCRIPTIONS  GET /api/pharmacy/prescriptions
    // ──────────────────────────────────────────────────────
    public function getPrescriptions(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->model->getAllPrescriptions());
        } catch (Exception $e) { $this->handleException($e); }
    }

    // ══════════════════════════════════════════════════════
    // PATIENT ENDPOINTS
    // ══════════════════════════════════════════════════════

    /**
     * GET /api/pharmacy/patients/all
     * Returns all patients (id, name, phone, age, sex) for instant client-side search
     */
    public function getAllPatients(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->billing->getAllPatients());
        } catch (Exception $e) { $this->handleException($e); }
    }

    /**
     * GET /api/pharmacy/search-patients?q=
     * Search patients by ID, name, or phone
     */
    public function searchPatients(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { $this->respondSuccess([]); return; }
        try {
            $this->respondSuccess($this->billing->searchPatients($q));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /**
     * GET /api/pharmacy/patient-prescription?patient_id=
     * Returns full patient info + consultations with soap_plan medicines
     */
    public function getPatientPrescription(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        $pid = trim($_GET['patient_id'] ?? '');
        if (!$pid) { $this->respondBadRequest('patient_id is required'); return; }
        try {
            $patient = $this->billing->getPatientById($pid);
            if (!$patient) { $this->respondNotFound("Patient {$pid} not found"); return; }

            $consultations = $this->billing->getPatientConsultations($pid);

            $this->respondSuccess([
                'patient'       => $patient,
                'consultations' => $consultations,
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    // ══════════════════════════════════════════════════════
    // PRODUCT / MEDICINE SEARCH
    // ══════════════════════════════════════════════════════

    /**
     * GET /api/pharmacy/products/search?q=
     * Search medicines from ph_product (in-stock only)
     */
    public function searchProducts(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 1) { $this->respondSuccess([]); return; }
        try {
            $this->respondSuccess($this->billing->searchProducts($q));
        } catch (Exception $e) { $this->handleException($e); }
    }

    // ══════════════════════════════════════════════════════
    // SPONSOR ENDPOINTS
    // ══════════════════════════════════════════════════════
    public function getSponsors(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->billing->getAllSponsors());
        } catch (Exception $e) { $this->handleException($e); }
    }

    // ══════════════════════════════════════════════════════
    // BILLING  POST /api/pharmacy/billing/create
    // ══════════════════════════════════════════════════════

    /**
     * POST /api/pharmacy/billing/create
     * JSON Body:
     * {
     *   "cart": [{ product_id, product_name, batch_no, qty, rate, discount_pct, tax_pct }],
     *   "customer_name":   "Patient Name",
     *   "customer_phone":  "9876543210",
     *   "payment_method":  "cash|card|upi|insurance",
     *   "paid_amount":     500,
     *   "discount_amount": 0
     * }
     */
    public function createBill(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        $body = $this->getJsonInput();

        $cart          = $body['cart']             ?? [];
        $customerId    = trim($body['customer_id']     ?? '');
        $customerName  = trim($body['customer_name']   ?? 'Walk-in Customer');
        $customerPhone = trim($body['customer_phone']  ?? '');
        $payMethod     = $body['payment_method']   ?? 'cash';
        $sponsorId     = $body['sponsor_id']       ?? null;
        $paidAmt       = (float)($body['paid_amount']    ?? 0);
        $discountAmt   = (float)($body['discount_amount'] ?? 0);

        if (empty($cart)) { $this->respondBadRequest('Cart is empty'); return; }

        // ── Server-side recalculation ──────────────────────
        $subtotal = $taxTotal = $grandTotal = 0;
        $itemRows = [];

        foreach ($cart as $c) {
            $pid   = trim($c['product_id']   ?? '');
            $pnm   = trim($c['product_name'] ?? '');
            $qty   = (int)($c['qty']         ?? 0);
            $rate  = (float)($c['rate']      ?? 0);
            $disc  = (float)($c['discount_pct'] ?? 0);
            $tax   = (float)($c['tax_pct']   ?? 0);
            $batch = trim($c['batch_no']     ?? '');
            if (empty($pid) || $qty <= 0) continue;

            $gross   = $qty * $rate;
            $discAmt = $gross * $disc / 100;
            $taxAmt  = ($gross - $discAmt) * $tax / 100;
            $sub     = $gross - $discAmt + $taxAmt;

            $subtotal   += $gross;
            $taxTotal   += $taxAmt;
            $grandTotal += $sub;

            $itemRows[] = [
                'product_id'       => $pid,
                'product_name'     => $pnm,
                'batch_no'         => $batch,
                'qty'              => $qty,
                'rate'             => $rate,
                'discount_percent' => $disc,
                'tax_percent'      => $tax,
                'tax_amount'       => $taxAmt,
                'subtotal'         => $sub,
            ];
        }

        if (empty($itemRows)) { $this->respondBadRequest('No valid items in cart'); return; }

        $grandTotal   -= $discountAmt;
        $balanceFinal  = $paidAmt - $grandTotal;
        $conn          = $this->billing->getConnection();

        try {
            mysqli_begin_transaction($conn);

            // ── Stock check & deduct ───────────────────────
            foreach ($itemRows as $item) {
                $available = $this->billing->getProductStock($item['product_id']);
                if ($available < $item['qty']) {
                    mysqli_rollback($conn);
                    $this->respondBadRequest(
                        "Insufficient stock for {$item['product_name']}. Available: {$available}"
                    );
                    return;
                }
                $this->billing->deductStock($item['product_id'], $item['qty']);
            }

            // ── Save bill ─────────────────────────────────
            $invoice_no = $this->billing->generateInvoiceNo();

            $this->billing->insertSalesMaster([
                'invoice_no'      => $invoice_no,
                'customer_id'     => $customerId,
                'customer_name'   => $customerName,
                'customer_phone'  => $customerPhone,
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmt,
                'tax_total'       => $taxTotal,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmt,
                'balance'         => $balanceFinal,
                'payment_method'  => $payMethod,
                'sponsor_id'      => $sponsorId,
            ]);

            foreach ($itemRows as $item) {
                $this->billing->insertSalesItem($invoice_no, $customerId, $item);
            }

            mysqli_commit($conn);

            // ── Build invoice HTML ─────────────────────────
            $printedBy   = $this->currentUser['full_name'] ?? $this->currentUser['username'] ?? 'Pharmacist';
            $invoiceHtml = $this->billing->generateInvoiceHTML(
                [
                    'invoice_no'      => $invoice_no,
                    'customer_name'   => $customerName,
                    'customer_phone'  => $customerPhone,
                    'payment_method'  => $payMethod,
                    'subtotal'        => $subtotal,
                    'discount_amount' => $discountAmt,
                    'tax_total'       => $taxTotal,
                    'grand_total'     => $grandTotal,
                    'paid_amount'     => $paidAmt,
                    'balance'         => $balanceFinal
                ], 
                $itemRows, 
                $printedBy
            );

            $this->respondSuccess([
                'invoice_no'   => $invoice_no,
                'grand_total'  => $grandTotal,
                'invoice_html' => $invoiceHtml,
            ], 'Bill created successfully');

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $this->handleException($e);
        }
    }

    // ══════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════

}

