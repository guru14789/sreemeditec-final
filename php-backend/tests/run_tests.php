<?php
// Start the built-in web server
$command = 'php -S localhost:8080 -t ' . escapeshellarg(__DIR__ . '/../public');
exec($command);

// Run the tests
require_once __DIR__ . '/api_test.php';
?>