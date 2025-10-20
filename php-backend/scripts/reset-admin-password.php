<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/firebase.php';

// Admin account details from command line arguments or environment variables
$adminEmail = $argv[1] ?? getenv('ADMIN_EMAIL');
$newPassword = $argv[2] ?? getenv('ADMIN_PASSWORD');

if (!$adminEmail || !$newPassword) {
    echo "Usage: php reset-admin-password.php <email> <new_password>\n";
    echo "Or set environment variables: ADMIN_EMAIL, ADMIN_PASSWORD\n";
    exit(1);
}

try {
    $auth = get_firebase_auth();
    
    // Get user by email
    $user = $auth->getUserByEmail($adminEmail);
    echo "✓ Found user: {$user->uid}\n";
    echo "  Email: {$adminEmail}\n";
    
    // Update password
    $updatedUser = $auth->updateUser($user->uid, [
        'password' => $newPassword
    ]);
    
    echo "✓ Password updated successfully!\n";
    echo "   Email: {$adminEmail}\n";
    echo "   \n";
    echo "⚠️  IMPORTANT: Store your new password securely and change it after first login!\n";
    
} catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
    echo "✗ User not found: {$adminEmail}\n";
    echo "  Please run create-admin.php first\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
