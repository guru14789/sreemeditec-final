<?php

// Firebase (now using environment variables for security)
define('FIREBASE_API_KEY', getenv('FIREBASE_API_KEY') ?: 'AIzaSyDGim4IkNRi9DKlr5KwmcRmagJUXLmVzfc');
define('FIREBASE_AUTH_DOMAIN', getenv('FIREBASE_AUTH_DOMAIN') ?: 'sreemeditec-final.firebaseapp.com');
define('FIREBASE_PROJECT_ID', getenv('FIREBASE_PROJECT_ID') ?: 'sreemeditec-final');
define('FIREBASE_STORAGE_BUCKET', getenv('FIREBASE_STORAGE_BUCKET') ?: 'sreemeditec-final.appspot.com');
define('FIREBASE_MESSAGING_SENDER_ID', getenv('FIREBASE_MESSAGING_SENDER_ID') ?: '236444837209');
define('FIREBASE_APP_ID', getenv('FIREBASE_APP_ID') ?: '1:236444837209:web:16d3497b8b8c5566eb9848');
define('FIREBASE_MEASUREMENT_ID', getenv('FIREBASE_MEASUREMENT_ID') ?: 'G-M9RDRTWRR6');

// Database
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'sreemeditec');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'root');

/**
 * Send JSON response
 * @param array $data
 * @param int $statusCode
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate required fields
 * @param array $data
 * @param array $requiredFields
 * @return array
 */
function validateRequiredFields($data, $requiredFields) {
    $errors = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        } elseif (is_string($data[$field]) && empty(trim($data[$field]))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        } elseif (is_array($data[$field]) && empty($data[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    return $errors;
}

?>
