<?php
require_once __DIR__ . '/../models/Contact.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// API routing for contacts
switch (true) {
    case str_starts_with($route, '/contacts') && $method === 'POST':
        handleCreateContact();
        break;
        
    case str_starts_with($route, '/contacts/all') && $method === 'GET':
        AuthMiddleware::handle();
        AuthMiddleware::requireAdmin();
        handleGetAllContacts();
        break;
        
    case preg_match('#^/contacts/([^/]+)$#', $route, $matches) && $method === 'GET':
        AuthMiddleware::handle();
        AuthMiddleware::requireAdmin();
        handleGetContactById($matches[1]);
        break;
        
    case preg_match('#^/contacts/([^/]+)/status$#', $route, $matches) && $method === 'PUT':
        AuthMiddleware::handle();
        AuthMiddleware::requireAdmin();
        handleUpdateContactStatus($matches[1]);
        break;
        
    case preg_match('#^/contacts/([^/]+)$#', $route, $matches) && $method === 'DELETE':
        AuthMiddleware::handle();
        AuthMiddleware::requireAdmin();
        handleDeleteContact($matches[1]);
        break;
}

function handleCreateContact(): void
{
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendJsonResponse(['success' => false, 'errors' => ['Invalid JSON input']], 400);
            return;
        }

        // Add user ID if authenticated
        $token = getBearerToken();
        if ($token) {
            try {
                $auth = \get_firebase_auth();
                $verifiedToken = $auth->verifyIdToken($token);
                $input['userId'] = $verifiedToken->claims()->get('sub');
            } catch (\Exception $e) {
                // Guest user - no userId
            }
        }

        $contactModel = new \Models\Contact();
        $result = $contactModel->create($input);
        
        sendJsonResponse($result, $result['success'] ? 201 : 400);
    } catch (\Exception $e) {
        error_log("Create contact error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'errors' => ['An unexpected error occurred.']], 500);
    }
}

function handleGetAllContacts(): void
{
    try {
        $contactModel = new \Models\Contact();
        $result = $contactModel->getAll();
        
        sendJsonResponse($result, $result['success'] ? 200 : 400);
    } catch (\Exception $e) {
        error_log("Get all contacts error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'errors' => ['An unexpected error occurred.']], 500);
    }
}

function handleGetContactById(string $contactId): void
{
    try {
        $contactModel = new \Models\Contact();
        $result = $contactModel->getById($contactId);
        
        sendJsonResponse($result, $result['success'] ? 200 : 404);
    } catch (\Exception $e) {
        error_log("Get contact error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'errors' => ['An unexpected error occurred.']], 500);
    }
}

function handleUpdateContactStatus(string $contactId): void
{
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($input['status'])) {
            sendJsonResponse(['success' => false, 'errors' => ['Status is required']], 400);
            return;
        }

        $contactModel = new \Models\Contact();
        $result = $contactModel->updateStatus($contactId, $input['status']);
        
        sendJsonResponse($result, $result['success'] ? 200 : 400);
    } catch (\Exception $e) {
        error_log("Update contact status error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'errors' => ['An unexpected error occurred.']], 500);
    }
}

function handleDeleteContact(string $contactId): void
{
    try {
        $contactModel = new \Models\Contact();
        $result = $contactModel->delete($contactId);
        
        sendJsonResponse($result, $result['success'] ? 200 : 400);
    } catch (\Exception $e) {
        error_log("Delete contact error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'errors' => ['An unexpected error occurred.']], 500);
    }
}
