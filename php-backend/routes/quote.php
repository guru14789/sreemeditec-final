<?php
require_once __DIR__ . '/../models/Quote.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// API routing for quotes
switch (true) {
    case str_starts_with($route, '/quotes') && $method === 'POST':
        handleCreateQuote();
        break;
        
    case str_starts_with($route, '/quotes/user') && $method === 'GET':
        handleGetUserQuotes();
        break;
        
    case str_starts_with($route, '/quotes/all') && $method === 'GET':
        handleGetAllQuotes();
        break;
        
    case preg_match('/^\/quotes\/([a-zA-Z0-9\-]+)$/', $route, $matches) && $method === 'GET':
        handleGetQuoteById($matches[1]);
        break;
        
    case preg_match('/^\/quotes\/([a-zA-Z0-9\-]+)\/status$/', $route, $matches) && $method === 'PUT':
        handleUpdateQuoteStatus($matches[1]);
        break;
        
    case preg_match('/^\/quotes\/([a-zA-Z0-9\-]+)$/', $route, $matches) && $method === 'DELETE':
        handleDeleteQuote($matches[1]);
        break;
}

function handleCreateQuote(): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['error' => 'Invalid JSON input'], 400);
        return;
    }
    
    // Check if user is authenticated (optional)
    $userId = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        try {
            AuthMiddleware::handle();
            $user = $GLOBALS['user'];
            $userId = $user->uid;
            $input['userId'] = $userId;
        } catch (Exception $e) {
            // Guest quote request - continue without user ID
        }
    }
    
    $quoteModel = new \Models\Quote();
    $result = $quoteModel->createQuote($input);
    
    sendJsonResponse($result, $result['success'] ? 201 : 400);
}

function handleGetUserQuotes(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $quoteModel = new \Models\Quote();
    $quotes = $quoteModel->getUserQuotes($user->uid);
    
    sendJsonResponse([
        'success' => true,
        'quotes' => $quotes,
        'total' => count($quotes)
    ]);
}

function handleGetAllQuotes(): void
{
    AuthMiddleware::handle('admin');
    
    $quoteModel = new \Models\Quote();
    $quotes = $quoteModel->getAllQuotes();
    
    sendJsonResponse([
        'success' => true,
        'quotes' => $quotes,
        'total' => count($quotes)
    ]);
}

function handleGetQuoteById(string $quoteId): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $quoteModel = new \Models\Quote();
    $quote = $quoteModel->getQuoteById($quoteId);
    
    if (!$quote) {
        sendJsonResponse(['error' => 'Quote not found'], 404);
        return;
    }
    
    // Check if user owns this quote or is admin
    if ($quote['userId'] !== $user->uid && $user->role !== 'admin') {
        sendJsonResponse(['error' => 'Access denied'], 403);
        return;
    }
    
    sendJsonResponse([
        'success' => true,
        'quote' => $quote
    ]);
}

function handleUpdateQuoteStatus(string $quoteId): void
{
    AuthMiddleware::handle('admin');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['status'])) {
        sendJsonResponse(['error' => 'Status is required'], 400);
        return;
    }
    
    $quoteModel = new \Models\Quote();
    $result = $quoteModel->updateQuoteStatus($quoteId, $input['status']);
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}

function handleDeleteQuote(string $quoteId): void
{
    AuthMiddleware::handle('admin');
    
    $quoteModel = new \Models\Quote();
    $result = $quoteModel->deleteQuote($quoteId);
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}
?>
