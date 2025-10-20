<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// API routing for auth
switch (true) {
    case str_starts_with($route, '/auth/register') && $method === 'POST':
        handleUserRegistration();
        break;
        
    case str_starts_with($route, '/auth/login') && $method === 'POST':
        handleUserLogin();
        break;
        
    case str_starts_with($route, '/auth/logout') && $method === 'POST':
        handleUserLogout();
        break;
        
    case str_starts_with($route, '/auth/me') && $method === 'GET':
        handleGetCurrentUser();
        break;
        
    case str_starts_with($route, '/auth/refresh') && $method === 'POST':
        handleTokenRefresh();
        break;
}

// Authentication handlers
function handleUserRegistration(): void
{
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendJsonResponse(['success' => false, 'errors' => ['Invalid JSON input']], 400);
            return;
        }

        $userModel = new \Models\User();
        $result = $userModel->register($input);
        
        sendJsonResponse($result, $result['success'] ? 201 : 400);
    } catch (\Exception $e) {
        error_log("Unhandled error in handleUserRegistration: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'errors' => ['An unexpected server error occurred.']], 500);
    }
}

function handleUserLogin(): void
{
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($input['email']) || !isset($input['password'])) {
            sendJsonResponse(['success' => false, 'errors' => ['Email and password are required']], 400);
            return;
        }
        
        $userModel = new \Models\User();
        $result = $userModel->login($input['email'], $input['password']);
        
        sendJsonResponse($result, $result['success'] ? 200 : 401);
    } catch (\Exception $e) {
        error_log("Unhandled error in handleUserLogin: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'errors' => ['An unexpected server error occurred.']], 500);
    }
}

function handleUserLogout(): void
{
    session_destroy();
    sendJsonResponse(['success' => true, 'message' => 'Logged out successfully']);
}

function handleGetCurrentUser(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    sendJsonResponse([
        'success' => true,
        'user' => $user
    ]);
}

function handleTokenRefresh(): void
{
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($input['refreshToken'])) {
            sendJsonResponse(['success' => false, 'errors' => ['Refresh token is required']], 400);
            return;
        }
        
        $auth = \get_firebase_auth();
        $signInResult = $auth->signInWithRefreshToken($input['refreshToken']);
        
        sendJsonResponse([
            'success' => true,
            'idToken' => $signInResult->idToken(),
            'refreshToken' => $signInResult->refreshToken()
        ]);
    } catch (\Exception $e) {
        error_log("Token refresh error: " . $e->getMessage());
        sendJsonResponse([
            'success' => false, 
            'errors' => ['Token refresh failed. Please log in again.']
        ], 401);
    }
}
?>