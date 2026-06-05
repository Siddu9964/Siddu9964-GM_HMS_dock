<?php
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * Notification API Controller
 * 
 * Handles doctor notifications, alerts, and real-time updates
 * Database: hmsci, Table: notifications
 * 
 * @package GM_HMS\Controllers\API
 * @version 1.1.0
 */
class NotificationController extends BaseController {
    
    /**
     * Route the request to appropriate method
     */
    public function route() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Check for special endpoints
        if (strpos($path, '/unread-count') !== false) {
            $this->getUnreadCount();
            return;
        }
        
        if (strpos($path, '/mark-read') !== false) {
            $this->markAsRead();
            return;
        }
        
        // Extract ID from path if present
        $pathParts = explode('/', trim($path, '/'));
        $id = end($pathParts);
        
        switch ($method) {
            case 'GET':
                if ($id && $id !== 'notifications') {
                    $this->show($id);
                } else {
                    $this->index();
                }
                break;
            case 'POST':
                $this->create();
                break;
            case 'DELETE':
                $this->delete($id);
                break;
            default:
                $this->respondMethodNotAllowed();
        }
    }
    
    /**
     * GET /api/notifications
     * Get all notifications for doctor
     */
    public function index() {
        $this->restrictMethod('GET');
        
        try {
            $doctorId = $_GET['doctor_id'] ?? null;
            $category = $_GET['category'] ?? null;
            $unreadOnly = $_GET['unread_only'] ?? false;
            $limit = $_GET['limit'] ?? 50;
            
            $query = 'SELECT * FROM notifications WHERE 1=1';
            $params = [];
            
            if ($doctorId) {
                $query .= ' AND recipient_id = ?';
                $params[] = $doctorId;
            }
            
            if ($category) {
                $query .= ' AND category = ?';
                $params[] = $category;
            }
            
            if ($unreadOnly) {
                $query .= ' AND is_read = 0';
            }
            
            $query .= ' ORDER BY priority DESC, created_at DESC LIMIT ?';
            $params[] = (int)$limit;
            
            $notifications = $this->db->fetchAll($query, $params);
            $this->respondSuccess($notifications);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/notifications/unread-count
     * Get unread notification count
     */
    public function getUnreadCount() {
        try {
            $doctorId = $_GET['doctor_id'] ?? null;
            
            $query = 'SELECT COUNT(*) as count FROM notifications WHERE is_read = 0';
            $params = [];
            
            if ($doctorId) {
                $query .= ' AND recipient_id = ?';
                $params[] = $doctorId;
            }
            
            $result = $this->db->fetchOne($query, $params);
            $this->respondSuccess(['count' => (int)$result['count']]);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * POST /api/notifications/mark-read
     * Mark notifications as read
     */
    public function markAsRead() {
        $this->restrictMethod('POST');
        
        try {
            $data = $this->getJsonInput([
                'required' => ['notification_ids'],
                'properties' => [
                    'notification_ids' => ['type' => 'array']
                ]
            ]);
            
            $ids = $data['notification_ids'];
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $this->db->execute(
                "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id IN ($placeholders)",
                $ids
            );
            
            $this->respondSuccess(null, 'Notifications marked as read');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * POST /api/notifications
     * Create new notification
     */
    public function create() {
        $this->restrictMethod('POST');
        
        $schema = [
            'required' => ['recipient_id', 'title', 'message', 'category'],
            'properties' => [
                'recipient_id' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'message' => ['type' => 'string'],
                'category' => ['type' => 'string'],
                'priority' => ['type' => 'string'],
                'action_url' => ['type' => 'string']
            ],
            'additionalProperties' => false
        ];
        
        $data = $this->getJsonInput($schema);
        
        // Generate notification_id
        $data['notification_id'] = 'NOTIF-' . time() . '-' . rand(1000, 9999);
        $data['is_read'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        if (!isset($data['priority'])) {
            $data['priority'] = 'normal';
        }
        
        try {
            $this->db->insert('notifications', $data);
            
            $notification = $this->db->fetchOne(
                'SELECT * FROM notifications WHERE notification_id = ?',
                [$data['notification_id']]
            );
            
            $this->respondCreated($notification);
            
        } catch (Exception $e) {
            error_log("Notification creation error: " . $e->getMessage());
            $this->respondError('Database operation failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * DELETE /api/notifications/{id}
     * Delete notification
     */
    public function delete($id) {
        $this->restrictMethod('DELETE');
        
        try {
            $existing = $this->db->fetchOne(
                'SELECT notification_id FROM notifications WHERE notification_id = ?',
                [$id]
            );
            
            if (!$existing) {
                $this->respondNotFound('Notification not found');
            }
            
            $this->db->delete('notifications', 'notification_id = ?', [$id]);
            $this->respondSuccess(null, 'Notification deleted successfully');
            
        } catch (Exception $e) {
            error_log("Notification delete error: " . $e->getMessage());
            $this->respondError('Database operation failed: ' . $e->getMessage(), 500);
        }
    }
}
