<?php
require_once __DIR__ . '/../config/firebase.php'; // Include the Firebase connection file

use Kreait\Firebase\Exception\Auth\InvalidToken;

class AuthMiddleware 
{
    public static function authenticate(): ?array 
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $idToken = str_replace('Bearer ', '', $authHeader);

        if (empty($idToken)) {
            return null; // No token provided
        }
        
        try {
            $verifiedIdToken = $GLOBALS['auth']->verifyIdToken($idToken);
            return $verifiedIdToken->claims()->all();
        } catch (InvalidToken $e) {
            return null; // Token is invalid
        } catch (Exception $e) {
            error_log("Firebase Auth Error: " . $e->getMessage());
            return null; // Other authentication errors
        }
    }
    
    public static function requireAuth(): array 
    {
        $user = self::authenticate();
        
        if (!$user) {
            sendJsonResponse(['error' => 'Authentication required'], 401);
        }
        
        return $user;
    }
    
    public static function requireAdmin(): array 
    {
        $user = self::requireAuth();
        
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            sendJsonResponse(['error' => 'Admin access required'], 403);
        }
        
        return $user;
    }
    
    public static function requireRole(string $role): array 
    {
        $user = self::requireAuth();
        
        if (!isset($user['role']) || $user['role'] !== $role) {
            sendJsonResponse(['error' => "Role '$role' required"], 403);
        }
        
        return $user;
    }
    
    public static function setUserContext(array $user): void 
    {
        // In Firebase Auth, user context is derived from the token, not session
        // Set global user context for request
        $GLOBALS['current_user'] = $user;
    }
}
?>