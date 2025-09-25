<?php
require_once __DIR__ . '/config/db.php';

$database = DatabaseConnection::getDatabase();
$collection = $database->selectCollection('users');

$email = 'admin@sreemeditec.com';
$password = 'admin123';

// Check if user already exists
$existingUser = $collection->findOne(['email' => $email]);

if ($existingUser) {
    echo "Admin user already exists.\n";
} else {
    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
    
    $result = $collection->insertOne([
        'username' => 'Admin',
        'email' => $email,
        'password' => $hashedPassword,
        'role' => 'admin',
        'is_active' => true,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ]);

    if ($result->getInsertedCount() > 0) {
        echo "Admin user created successfully.\n";
    } else {
        echo "Failed to create admin user.\n";
    }
}
?>