<?php
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyIndentController
 * Routes:
 *   GET    /api/pharmacy/indents                  → index()      list all indents
 *   POST   /api/pharmacy/indents                  → save()       create / update
 *   POST   /api/pharmacy/indents/auto-generate    → autoGenerate()
 *   POST   /api/pharmacy/indents/update-qty       → updateQty()
 *   POST   /api/pharmacy/indents/bulk-status      → bulkStatus()
 *   POST   /api/pharmacy/indents/bulk-delete      → bulkDelete()
 *   DELETE /api/pharmacy/indents/{id}             → delete()
 */
class PharmacyIndentController extends BaseController {
    public function __construct() { parent::__construct(); }

    // ──────────────────────────────────────────────────────────────────────────
    /** GET /api/pharmacy/indents — Fetch all indent requests */
    // ──────────────────────────────────────────────────────────────────────────
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $data = $this->db->fetchAll(
                "SELECT * FROM ph_indent_requests WHERE status != 'ordered' ORDER BY id DESC"
            );
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    /** POST /api/pharmacy/indents — Create or update an indent request */
    // ──────────────────────────────────────────────────────────────────────────
    public function save(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d = $this->getJsonInput();

            $id           = !empty($d['id']) ? (int)$d['id'] : null;
            $department   = trim($d['department']   ?? '');
            $requested_by = trim($d['requested_by'] ?? '');
            $product_id   = trim($d['product_id']   ?? '');
            $item_name    = trim($d['item_name']    ?? '');
            $qty          = (int)($d['qty']         ?? 1);
            $priority     = in_array($d['priority'] ?? '', ['low','medium','high','urgent'])
                                ? $d['priority'] : 'medium';
            $status       = in_array($d['status']   ?? '', ['pending','approved','ordered','cancelled'])
                                ? $d['status'] : 'pending';
            $remarks      = trim($d['remarks']      ?? '');
            $supplier_id  = trim($d['supplier_id']  ?? '');
            $company_name = trim($d['company_name'] ?? '');
            $email        = trim($d['email']        ?? '');

            if (empty($item_name) || $qty < 1) {
                $this->respondBadRequest('Item name and quantity (>0) are required.');
                return;
            }

            if (empty($id)) {
                // Auto-generate IND-XXXXX
                $row = $this->db->fetchOne(
                    "SELECT MAX(CAST(SUBSTRING(indent_no, 5) AS UNSIGNED)) AS max_id
                     FROM ph_indent_requests"
                );
                $indent_no = 'IND-' . str_pad(($row['max_id'] ?? 0) + 1, 5, '0', STR_PAD_LEFT);

                $this->db->execute(
                    "INSERT INTO ph_indent_requests
                        (indent_no, request_date, request_time, requested_by, department,
                         product_id, item_name, qty, priority, remarks, status,
                         supplier_id, company_name, email)
                     VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$indent_no, $requested_by, $department, $product_id, $item_name,
                     $qty, $priority, $remarks, $status, $supplier_id, $company_name, $email]
                );
                $this->respondCreated(['indent_no' => $indent_no]);
            } else {
                $this->db->execute(
                    "UPDATE ph_indent_requests
                     SET requested_by=?, department=?, product_id=?, item_name=?,
                         qty=?, priority=?, status=?, remarks=?,
                         supplier_id=?, company_name=?, email=?
                     WHERE id=?",
                    [$requested_by, $department, $product_id, $item_name,
                     $qty, $priority, $status, $remarks,
                     $supplier_id, $company_name, $email, $id]
                );
                $this->respondSuccess(null, 'Indent request updated successfully.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    /** POST /api/pharmacy/indents/update-qty — Update qty of a single indent */
    // ──────────────────────────────────────────────────────────────────────────
    public function updateQty(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d   = $this->getJsonInput();
            $id  = (int)($d['id']  ?? 0);
            $qty = (int)($d['qty'] ?? 0);

            if ($id < 1 || $qty < 1) {
                $this->respondBadRequest('Valid id and qty (>0) are required.');
                return;
            }

            $this->db->execute(
                "UPDATE ph_indent_requests SET qty = ? WHERE id = ?",
                [$qty, $id]
            );
            $this->respondSuccess(null, 'Quantity updated.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    /** POST /api/pharmacy/indents/bulk-status — Batch update status */
    // ──────────────────────────────────────────────────────────────────────────
    public function bulkStatus(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d      = $this->getJsonInput();
            $ids    = array_filter(array_map('intval', $d['ids'] ?? []), fn($v) => $v > 0);
            $status = $d['status'] ?? '';

            if (empty($ids)) {
                $this->respondBadRequest('No valid IDs provided.');
                return;
            }
            $allowed = ['pending', 'approved', 'ordered', 'cancelled'];
            if (!in_array($status, $allowed)) {
                $this->respondBadRequest('Invalid status. Allowed: ' . implode(', ', $allowed));
                return;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $this->db->execute(
                "UPDATE ph_indent_requests SET status = ? WHERE id IN ($placeholders)",
                array_merge([$status], array_values($ids))
            );
            $this->respondSuccess(null, count($ids) . ' indent(s) updated to "' . $status . '".');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    /** POST /api/pharmacy/indents/bulk-delete — Batch delete */
    // ──────────────────────────────────────────────────────────────────────────
    public function bulkDelete(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d   = $this->getJsonInput();
            $ids = array_filter(array_map('intval', $d['ids'] ?? []), fn($v) => $v > 0);

            if (empty($ids)) {
                $this->respondBadRequest('No valid IDs provided.');
                return;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $this->db->execute(
                "DELETE FROM ph_indent_requests WHERE id IN ($placeholders)",
                array_values($ids)
            );
            $this->respondSuccess(null, count($ids) . ' indent(s) deleted.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    /** POST /api/pharmacy/indents/auto-generate — Draft indents for low-stock items */
    // ──────────────────────────────────────────────────────────────────────────
    public function autoGenerate(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $row_t     = $this->db->fetchOne("SELECT setting_value FROM ph_settings WHERE setting_key='low_stock_threshold'");
            $threshold = (int)($row_t['setting_value'] ?? 20);

            // Only pick items below threshold that don't already have a pending/approved indent
            $items = $this->db->fetchAll(
                "SELECT product_id, product_name, quantity FROM ph_product
                 WHERE quantity <= ?
                   AND product_id NOT IN (
                       SELECT product_id FROM ph_indent_requests
                       WHERE status IN ('pending','approved')
                         AND product_id IS NOT NULL AND product_id != ''
                   )",
                [$threshold]
            );

            if (empty($items)) {
                $this->respondSuccess(null, 'No new low-stock items found, or indents already exist for them.');
                return;
            }

            // Use SecureDatabase transaction methods (not raw connection)
            $this->db->beginTransaction();
            $count = 0;

            foreach ($items as $item) {
                // Order qty: enough to reach 50, minimum 10
                $orderQty  = max(50 - (int)$item['quantity'], 10);

                // Generate next IND-XXXXX
                $row_m     = $this->db->fetchOne(
                    "SELECT MAX(CAST(SUBSTRING(indent_no, 5) AS UNSIGNED)) AS max_id FROM ph_indent_requests"
                );
                $indent_no = 'IND-' . str_pad(($row_m['max_id'] ?? 0) + 1, 5, '0', STR_PAD_LEFT);

                $this->db->execute(
                    "INSERT INTO ph_indent_requests
                        (indent_no, request_date, request_time, requested_by, department,
                         product_id, item_name, qty, priority, remarks, status, supplier_id, company_name, email)
                     VALUES (?, CURDATE(), CURTIME(), 'System Auto', 'Pharmacy Store', ?, ?, ?, 'high', 'Auto-generated: low stock', 'pending', '', '', '')",
                    [$indent_no, $item['product_id'], $item['product_name'], $orderQty]
                );
                $count++;
            }

            $this->db->commit();
            $this->respondSuccess(null, "$count indent request(s) generated successfully.");

        } catch (Exception $e) {
            try { $this->db->rollback(); } catch (Exception $re) {}
            $this->handleException($e);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    /** DELETE /api/pharmacy/indents/{id} — Delete single indent */
    // ──────────────────────────────────────────────────────────────────────────
    public function delete(string $id): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $this->db->execute("DELETE FROM ph_indent_requests WHERE id = ?", [(int)$id]);
            $this->respondSuccess(null, 'Indent deleted.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
