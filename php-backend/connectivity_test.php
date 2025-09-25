<?php
require_once __DIR__ . '/config/db.php';

if (DatabaseConnection::testConnection()) {
    echo "Database connection successful.\n";
} else {
    echo "Database connection failed.\n";
}
?>