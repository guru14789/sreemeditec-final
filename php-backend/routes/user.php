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
    sendJsonResponse([
        'success' => true,
        'user' => $user
    ]);
}

function handleUpdateUserProfile(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['error' => 'Invalid JSON input'], 400);
    }
    
    $userModel = new User();
    $result = $userModel->updateProfile($user->uid, $input);
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}

function handleChangeUserPassword(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['newPassword'])) {
        sendJsonResponse(['error' => 'New password is required'], 400);
    }
    
    $userModel = new User();
    $result = $userModel->changePassword($user->uid, $input['newPassword']);
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}
?>