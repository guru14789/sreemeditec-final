<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/firebase.php';

// Admin account details from command line arguments or environment variables
$adminEmail = $argv[1] ?? getenv('ADMIN_EMAIL');
$adminPassword = $argv[2] ?? getenv('ADMIN_PASSWORD');
$adminName = $argv[3] ?? getenv('ADMIN_NAME');
$adminPhone = $argv[4] ?? getenv('ADMIN_PHONE');

if (!$adminEmail || !$adminPassword || !$adminName || !$adminPhone) {
    echo "Usage: php create-admin.php <email> <password> <name> <phone>\n";
    echo "Or set environment variables: ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NAME, ADMIN_PHONE\n";
    exit(1);
}

try {
    $auth = get_firebase_auth();
    $firestore = get_firebase_firestore();
    
    // Check if user already exists in Firebase Auth
    try {
        $user = $auth->getUserByEmail($adminEmail);
        echo "ℹ User already exists in Firebase Auth\n";
        echo "  UID: {$user->uid}\n";
        $userId = $user->uid;
    } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
        // Create user in Firebase Auth
        $userProperties = [
            'email' => $adminEmail,
            'emailVerified' => false,
            'password' => $adminPassword,
            'displayName' => $adminName,
            'disabled' => false,
        ];
        
        $createdUser = $auth->createUser($userProperties);
        echo "✓ Created Firebase Auth user\n";
        echo "  UID: {$createdUser->uid}\n";
        echo "  Email: {$adminEmail}\n";
        $userId = $createdUser->uid;
    }
    
    // Create/Update Firestore document
    $userData = [
        'user_id' => $userId,
        'email' => $adminEmail,
        'username' => $adminName,
        'phone' => $adminPhone,
        'role' => 'admin',
        'created_at' => new \DateTime(),
        'updated_at' => new \DateTime(),
    ];
    
    $firestore->collection('users')->document($userId)->set($userData, ['merge' => true]);
    
    echo "✓ Created/Updated Firestore user document\n";
    echo "  Role: admin\n";
    echo "\n";
    echo "🎉 Admin account ready!\n";
    echo "   Email: {$adminEmail}\n";
    echo "   \n";
    echo "⚠️  IMPORTANT: Store your password securely and change it after first login!\n";
    
} catch (Exception $e) {
    echo "✗ Error creating admin account: " . $e->getMessage() . "\n";
    exit(1);
}
