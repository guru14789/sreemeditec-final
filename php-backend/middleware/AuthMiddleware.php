<?php
require_once __DIR__ . '/../config/config.php';

class AuthMiddleware
{
    public static function handle(string $role = 'user'): void
    {
        $auth = get_firebase_auth();

        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            sendJsonResponse(['error' => 'Authorization header not found'], 401);
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        $matches = [];
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            sendJsonResponse(['error' => 'Invalid token format'], 401);
        }

        $token = $matches[1];

        try {
            $verifiedToken = $auth->verifyIdToken($token);
            $uid = $verifiedToken->claims()->get('sub');
            $user = $auth->getUser($uid);

            // Attach user to the request for later use in controllers
            $GLOBALS['user'] = $user;

            // Role-based access control
            if ($role === 'admin' && !($user->customClaims['admin'] ?? false)) {
                sendJsonResponse(['error' => 'Forbidden: Admins only'], 403);
            }
        } catch (Exception $e) {
            sendJsonResponse(['error' => 'Invalid or expired token', 'details' => $e->getMessage()], 401);
        }
    }
}
?>