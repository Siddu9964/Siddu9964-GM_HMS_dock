<?php
namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

/**
 * Laboratory Model
 * Handles data retrieval for laboratory, radiology, and other services.
 */
class LaboratoryModel
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get all laboratory services
     * @return array
     */
    public function getLabServices()
    {
        return $this->db->fetchAll("SELECT * FROM lab_services ORDER BY test_name ASC");
    }

    /**
     * Get all radiology services
     * @return array
     */
    public function getRadiologyServices()
    {
        return $this->db->fetchAll("SELECT * FROM radiology_services ORDER BY billing_name ASC");
    }

    /**
     * Get all other services
     * @return array
     */
    public function getOtherServices()
    {
        return $this->db->fetchAll("SELECT * FROM other_services ORDER BY billing_name ASC");
    }

    /**
     * Get all services combined for a unified view
     * @return array
     */
    public function getAllServices()
    {
        return [
            'lab' => $this->getLabServices(),
            'radiology' => $this->getRadiologyServices(),
            'other' => $this->getOtherServices()
        ];
    }

    /**
     * Delete a laboratory service
     * @param string $id Service ID
     * @return bool
     */
    public function deleteLabService($id)
    {
        return $this->db->execute("DELETE FROM lab_services WHERE service_id = ?", [$id]);
    }

    /**
     * Delete a radiology service
     * @param string $id Service ID
     * @return bool
     */
    public function deleteRadiologyService($id)
    {
        return $this->db->execute("DELETE FROM radiology_services WHERE service_id = ?", [$id]);
    }

    /**
     * Delete an other service
     * @param string $id Service ID
     * @return bool
     */
    public function deleteOtherService($id)
    {
        return $this->db->execute("DELETE FROM other_services WHERE service_id = ?", [$id]);
    }

    /**
     * Create a new laboratory service
     * @param array $data Service data
     * @return bool
     */
    public function createLabService($data)
    {
        return $this->db->execute(
            "INSERT INTO lab_services (service_id, test_name, opd_rate, gw_rate, spvt_rate, pvt_ccu_rate, suite_rate) VALUES (?, ?, ?, ?, ?, ?, ?)",
        [
            $data['service_id'],
            $data['test_name'],
            $data['opd_rate'],
            $data['gw_rate'],
            $data['spvt_rate'],
            $data['pvt_ccu_rate'],
            $data['suite_rate']
        ]
        );
    }

    /**
     * Create a new radiology service
     * @param array $data Service data
     * @return bool
     */
    public function createRadiologyService($data)
    {
        return $this->db->execute(
            "INSERT INTO radiology_services (service_id, billing_name, modality_name, opd_price, general_ward_price, semi_private_price, private_icu_price, suite_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $data['service_id'],
            $data['billing_name'],
            $data['modality_name'],
            $data['opd_price'],
            $data['general_ward_price'],
            $data['semi_private_price'],
            $data['private_icu_price'],
            $data['suite_price']
        ]
        );
    }

    /**
     * Create a new other service
     * @param array $data Service data
     * @return bool
     */
    public function createOtherService($data)
    {
        return $this->db->execute(
            "INSERT INTO other_services (service_id, billing_name, op_gw_price, semi_private_price, private_icu_price, suite_price) VALUES (?, ?, ?, ?, ?, ?)",
        [
            $data['service_id'],
            $data['billing_name'],
            $data['op_gw_price'],
            $data['semi_private_price'],
            $data['private_icu_price'],
            $data['suite_price']
        ]
        );
    }

    /**
     * Update a laboratory service
     * @param string $id Service ID
     * @param array $data Update data
     * @return bool
     */
    public function updateLabService($id, $data)
    {
        return $this->db->execute(
            "UPDATE lab_services SET test_name = ?, opd_rate = ?, gw_rate = ?, spvt_rate = ?, pvt_ccu_rate = ?, suite_rate = ? WHERE service_id = ?",
        [
            $data['test_name'],
            $data['opd_rate'],
            $data['gw_rate'],
            $data['spvt_rate'],
            $data['pvt_ccu_rate'],
            $data['suite_rate'],
            $id
        ]
        );
    }

    /**
     * Update a radiology service
     * @param string $id Service ID
     * @param array $data Update data
     * @return bool
     */
    public function updateRadiologyService($id, $data)
    {
        return $this->db->execute(
            "UPDATE radiology_services SET billing_name = ?, modality_name = ?, opd_price = ?, general_ward_price = ?, semi_private_price = ?, private_icu_price = ?, suite_price = ? WHERE service_id = ?",
        [
            $data['billing_name'],
            $data['modality_name'],
            $data['opd_price'],
            $data['general_ward_price'],
            $data['semi_private_price'],
            $data['private_icu_price'],
            $data['suite_price'],
            $id
        ]
        );
    }

    /**
     * Update an other service
     * @param string $id Service ID
     * @param array $data Update data
     * @return bool
     */
    public function updateOtherService($id, $data)
    {
        return $this->db->execute(
            "UPDATE other_services SET billing_name = ?, op_gw_price = ?, semi_private_price = ?, private_icu_price = ?, suite_price = ? WHERE service_id = ?",
        [
            $data['billing_name'],
            $data['op_gw_price'],
            $data['semi_private_price'],
            $data['private_icu_price'],
            $data['suite_price'],
            $id
        ]
        );
    }
}
