<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// API routing for user
switch (true) {
    case str_starts_with($route, '/user/profile') && $method === 'GET':
        handleGetCurrentUserProfile();
        break;
        
    case str_starts_with($route, '/user/profile') && $method === 'PUT':
        handleUpdateUserProfile();
        break;

    case str_starts_with($route, '/user/change-password') && $method === 'PUT':
        handleChangeUserPassword();
        break;
}

// User handlers
function handleGetCurrentUserProfile(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $userModel = new \Models\User();
    $userData = $userModel->getUserById($user->uid);
    
    if ($userData) {
        sendJsonResponse([
            'success' => true,
            'user' => $userData
        ]);
    } else {
        sendJsonResponse([
            'error' => 'User not found'
        ], 404);
    }
}

function handleUpdateUserProfile(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['error' => 'Invalid JSON input'], 400);
    }
    
    $userModel = new \Models\User();
    $result = $userModel->updateProfile($user->uid, $input);
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}

function handleChangeUserPassword(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['currentPassword']) || !isset($input['newPassword'])) {
        sendJsonResponse(['success' => false, 'errors' => ['Current password and new password are required']], 400);
        return;
    }
    
    $userModel = new \Models\User();
    $result = $userModel->changePassword($user->uid, $user->email, $input['currentPassword'], $input['newPassword']);
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}
?>