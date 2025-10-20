<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/firebase.php';

// Email to make admin
$email = 'mrgokkul@gmail.com';

try {
    // Get Firestore instance
    $firestore = get_firebase_firestore();
    
    // Query for user by email
    $documents = $firestore->collection('users')
        ->where('email', '=', $email)
        ->documents();
    
    $updated = false;
    foreach ($documents as $document) {
        if ($document->exists()) {
            $userData = $document->data();
            
            // Update role to admin
            $firestore->collection('users')->document($document->id())->update([
                ['path' => 'role', 'value' => 'admin']
            ]);
            
            echo "✓ Successfully updated {$userData['username']} ({$email}) to admin role\n";
            echo "  User ID: {$document->id()}\n";
            echo "  Previous role: {$userData['role']}\n";
            echo "  New role: admin\n";
            $updated = true;
            break;
        }
    }
    
    if (!$updated) {
        echo "✗ User with email {$email} not found\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ Error updating user: " . $e->getMessage() . "\n";
    exit(1);
}
