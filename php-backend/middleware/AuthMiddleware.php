<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/firebase.php';

class AuthMiddleware
{
    public static function handle(string $role = 'user'): void
    {
        $auth = get_firebase_auth();
        $firestore = get_firebase_firestore();

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

            // Fetch user role from Firestore
            $userDoc = $firestore->collection('users')->document($uid)->snapshot();
            $userRole = 'user';
            if ($userDoc->exists()) {
                $userData = $userDoc->data();
                $userRole = $userData['role'] ?? 'user';
            }

            // Attach user and role to GLOBALS for later use in controllers
            $GLOBALS['user'] = (object)[
                'uid' => $user->uid,
                'email' => $user->email,
                'displayName' => $user->displayName ?? '',
                'role' => $userRole
            ];

            // Role-based access control
            if ($role === 'admin' && $userRole !== 'admin') {
                sendJsonResponse(['error' => 'Forbidden: Admins only'], 403);
            }
        } catch (Exception $e) {
            sendJsonResponse(['error' => 'Invalid or expired token', 'details' => $e->getMessage()], 401);
        }
    }

    public static function requireAdmin(): void
    {
        self::handle('admin');
    }
}
?>