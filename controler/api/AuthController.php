<?php
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use Exception;

class AuthController extends BaseController {
    
    /**
     * Handle login request
     * POST /api/auth/login
     */
    public function login() {
        error_log("[DEBUG] AuthController::login hit");
        $this->restrictMethod('POST');
        
        // Get and validate JSON input
        $schema = [
            'required' => ['username', 'password'],
            'properties' => [
                'username' => ['type' => 'string', 'minLength' => 1],
                'password' => ['type' => 'string', 'minLength' => 1],
                'role' => ['type' => 'string'] // Optional now
            ],
            'additionalProperties' => true // Allow additional for flexibility
        ];
        
        try {
            $data = $this->getJsonInput($schema);
            
            // Sanitize identifier (can be username or ID)
            $identifier = $this->sanitizer->sanitizeString($data['username']);
            $password = $data['password'];
            
            // Attempt login using unified logic
            $result = $this->auth->login($identifier, $password);
            
            if ($result['success']) {
                $user = $result['user'];
                $detectedRole = $result['role'];
                
                // Create secure session
                $this->auth->createSession($user, $detectedRole);
                
                // Determine redirect URL
                $redirectUrl = '';
                // Standardize role for redirection check
                $checkRole = strtolower($detectedRole);
                
                if ($checkRole === 'doctor') {
                    $redirectUrl = 'doctors_view/dashboard.php';
                } elseif ($checkRole === 'receptionist') {
                    $redirectUrl = 'reception_view/index.php';
                } elseif ($checkRole === 'nurse') {
                    $redirectUrl = 'nurse_view/dashboard.php';
                } elseif ($checkRole === 'pharmacist') {
                    $redirectUrl = 'pharmacy_view/dashboard.php';
                } elseif ($checkRole === 'admin') {
                    $redirectUrl = 'view/admin_dashboard.php';
                } else {
                    // Default fallback based on common designations
                    if (strpos($checkRole, 'reception') !== false) {
                        $redirectUrl = 'reception_view/index.php';
                    } elseif (strpos($checkRole, 'nurse') !== false) {
                        $redirectUrl = 'nurse_view/dashboard.php';
                    } elseif (strpos($checkRole, 'pharmacy') !== false || strpos($checkRole, 'pharmacist') !== false) {
                        $redirectUrl = 'pharmacy_view/dashboard.php';
                    } else {
                        $redirectUrl = 'view/admin_dashboard.php'; // Default for other staff
                    }
                }
                
                $this->respond([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'role' => $detectedRole,
                    'user' => $result['user'],
                    'redirect_url' => $redirectUrl
                ]);
            } else {
                $this->respond([
                    'status' => 'error',
                    'message' => $result['error']
                ], 401);
            }
            
        } catch (Exception $e) {
            $this->respond([
                'status' => 'error',
                'message' => 'Invalid request format: ' . $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Handle logout request
     * POST /api/auth/logout
     */
    public function logout() {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        try {
            $token = $this->getBearerToken();
            $this->auth->logout($token);
            
            $this->respondSuccess(null, 'Logged out successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Handle token refresh request
     * POST /api/auth/refresh
     */
    public function refresh() {
        $this->restrictMethod('POST');
        
        $data = $this->getJsonInput([
            'required' => ['refresh_token'],
            'properties' => [
                'refresh_token' => ['type' => 'string']
            ]
        ]);
        
        try {
            require_once __DIR__ . '/../../security/TokenManager.php';
            $tokenManager = TokenManager::getInstance();
            
            $result = $tokenManager->refreshAccessToken($data['refresh_token']);
            
            if ($result) {
                $this->respondSuccess($result, 'Token refreshed');
            } else {
                $this->respondUnauthorized('Invalid refresh token');
            }
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get current user info
     * GET /api/auth/me
     */
    public function me() {
        $this->restrictMethod('GET');
        $user = $this->requireAuth();
        
        try {
            // Get user permissions
            $permissions = $this->authz->getUserPermissions($user['id']);
            $role = $this->authz->getUserRole($user['id']);
            
            $this->respondSuccess([
                'user' => $user,
                'role' => $role,
                'permissions' => $permissions
            ]);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Change password
     * POST /api/auth/change-password
     */
    public function changePassword() {
        $this->restrictMethod('POST');
        
        try {
            $user = $this->requireAuth();
            
            $schema = [
                'required' => ['current_password', 'new_password'],
                'properties' => [
                    'current_password' => ['type' => 'string'],
                    'new_password' => [
                        'type' => 'string',
                        'minLength' => 8
                    ]
                ]
            ];
            
            // $data = $this->getJsonInput($schema); is inside try block in previous edit, need to be careful with overlaps.
            // Actually, I can replace the whole function to be safe.
            $data = $this->getJsonInput($schema);
            
            // Validate password strength
            $policy = $this->config->getPasswordPolicy();
            if (!$this->validator->password($data['new_password'], 'New password', $policy)) {
                $this->respondBadRequest(implode(', ', $this->validator->getErrors()));
            }
            
            $result = $this->auth->changePassword(
                $user['id'],
                $data['current_password'],
                $data['new_password']
            );
            
            if ($result['success']) {
                $this->respondSuccess(null, 'Password changed successfully. Please login again.');
            } else {
                $this->respondBadRequest($result['error']);
            }
            
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get bearer token helper
     */
    private function getBearerToken() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
