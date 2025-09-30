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
}

// Authentication handlers
function handleUserRegistration(): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['error' => 'Invalid JSON input'], 400);
    }

    $userModel = new User();
    $result = $userModel->register($input);
    
    sendJsonResponse($result, $result['success'] ? 201 : 400);
}

function handleUserLogin(): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        sendJsonResponse(['error' => 'Email and password are required'], 400);
    }
    
    $userModel = new User();
    $result = $userModel->login($input['email'], $input['password']);
    
    sendJsonResponse($result, $result['success'] ? 200 : 401);
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
?>