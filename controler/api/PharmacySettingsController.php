<?php
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacySettingsController
 * Routes:
 *   GET  /api/pharmacy/settings       → get all settings
 *   POST /api/pharmacy/settings       → save settings
 */
class PharmacySettingsController extends BaseController {
    public function __construct() { parent::__construct(); }

    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $rows = $this->db->fetchAll("SELECT setting_key, setting_value FROM ph_settings");
            $settings = [];
            foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];
            $this->respondSuccess($settings);
        } catch (Exception $e) { $this->handleException($e); }
    }

    public function save(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d = $this->getJsonInput();
            foreach ($d as $key => $value) {
                $existing = $this->db->fetchOne(
                    "SELECT setting_key FROM ph_settings WHERE setting_key = ?", [$key]
                );
                if ($existing) {
                    $this->db->execute(
                        "UPDATE ph_settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]
                    );
                } else {
                    $this->db->execute(
                        "INSERT INTO ph_settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]
                    );
                }
            }
            $this->respondSuccess(null, 'Settings saved');
        } catch (Exception $e) { $this->handleException($e); }
    }
}
