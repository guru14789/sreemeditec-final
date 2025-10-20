<?php
namespace Models;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/firebase.php';

class User 
{
    private $auth;
    private $firestore;
    private $usersCollection;

    public function __construct()
    {
        $this->auth = \get_firebase_auth();
        $this->firestore = \get_firebase_firestore();
        $this->usersCollection = $this->firestore->collection('users');
    }
    
    public function register(array $userData): array
    {
        $requiredFields = ['name', 'email', 'password', 'phone'];
        $errors = \validateRequiredFields($userData, $requiredFields);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $userProperties = [
                'email' => strtolower(trim($userData['email'])),
                'emailVerified' => false,
                'password' => $userData['password'],
                'displayName' => \sanitizeInput($userData['name']),
                'disabled' => false,
            ];
            $createdUser = $this->auth->createUser($userProperties);
            $uid = $createdUser->uid;

            // Store additional user data in Firestore
            $this->usersCollection->document($uid)->set([
                'username' => \sanitizeInput($userData['name']),
                'email' => strtolower(trim($userData['email'])),
                'phone' => \sanitizeInput($userData['phone']),
                'address' => \sanitizeInput($userData['address'] ?? ''),
                'role' => 'user',
                'createdAt' => new \Google\Cloud\Core\Timestamp(new \DateTime())
            ]);

            $signInResult = $this->auth->signInWithEmailAndPassword(strtolower(trim($userData['email'])), $userData['password']);
            $idToken = $signInResult->idToken();
            $refreshToken = $signInResult->refreshToken();

            return [
                'success' => true,
                'user_id' => $uid,
                'message' => 'User registered successfully',
                'idToken' => $idToken,
                'refreshToken' => $refreshToken
            ];
        } catch (\Kreait\Firebase\Exception\Auth\EmailExists $e) {
            error_log("User registration error: Email already exists - " . $e->getMessage());
            return ['success' => false, 'errors' => ['The email address is already in use by another account.']];
        } catch (\Exception $e) {
            error_log("User registration error: " . $e->getMessage());
            error_log("Exception class: " . get_class($e));
            error_log("Exception code: " . $e->getCode());
            if (method_exists($e, 'errors')) {
                error_log("Firebase errors: " . json_encode($e->errors()));
            }
            // Generic error message for the user
            return ['success' => false, 'errors' => ['An unexpected error occurred during registration. Please try again later.']];
        }
    }
    
    public function login(string $email, string $password): array
    {
        try {
            $signInResult = $this->auth->signInWithEmailAndPassword(strtolower(trim($email)), $password);
            $uid = $signInResult->firebaseUserId();
            $idToken = $signInResult->idToken();
            $refreshToken = $signInResult->refreshToken();

            $userDoc = $this->usersCollection->document($uid)->snapshot();

            return [
                'success' => true,
                'user' => $userDoc->data(),
                'idToken' => $idToken,
                'refreshToken' => $refreshToken
            ];
        } catch (\Exception $e) {
            error_log("User login error: " . $e->getMessage());
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    public function getUserById(string $uid): ?array
    {
        try {
            $userDoc = $this->usersCollection->document($uid)->snapshot();
            if ($userDoc->exists()) {
                return $userDoc->data();
            }
            return null;
        } catch (\Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    public function updateProfile(string $uid, array $updateData): array
    {
        try {
            $updates = [];
            foreach ($updateData as $key => $value) {
                $updates[] = ['path' => $key, 'value' => \sanitizeInput($value)];
            }
            $this->usersCollection->document($uid)->update($updates);
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } catch (\Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Profile update failed']];
        }
    }

    public function changePassword(string $uid, string $email, string $currentPassword, string $newPassword): array
    {
        try {
            // First verify the current password by attempting to sign in
            try {
                $this->auth->signInWithEmailAndPassword(strtolower(trim($email)), $currentPassword);
            } catch (\Exception $e) {
                error_log("Current password verification failed: " . $e->getMessage());
                return ['success' => false, 'errors' => ['Current password is incorrect']];
            }
            
            // If current password is correct, change to new password
            $this->auth->changeUserPassword($uid, $newPassword);
            return ['success' => true, 'message' => 'Password changed successfully'];
        } catch (\Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Password change failed']];
        }
    }

    public function deleteUser(string $uid): array
    {
        try {
            $this->auth->deleteUser($uid);
            $this->usersCollection->document($uid)->delete();
            return ['success' => true, 'message' => 'User deleted successfully'];
        } catch (\Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['User deletion failed']];
        }
    }

    public function getAllUsers(): array
    {
        try {
            $users = [];
            $documents = $this->usersCollection->documents();
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $userData = $document->data();
                    // Add uid from document ID for role updates
                    $userData['uid'] = $userData['user_id'] ?? $document->id();
                    $users[] = $userData;
                }
            }
            return $users;
        } catch (\Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }

    public function getUsersByIds(array $userIds): array
    {
        try {
            if (empty($userIds)) {
                return [];
            }

            $users = [];
            
            // Firestore doesn't support batched document reads directly,
            // but we can fetch individual documents in a loop (still better than N queries scattered across the code)
            foreach ($userIds as $userId) {
                $userDoc = $this->usersCollection->document($userId)->snapshot();
                if ($userDoc->exists()) {
                    $userData = $userDoc->data();
                    $userData['uid'] = $userId;
                    $users[$userId] = $userData;
                }
            }
            
            return $users;
        } catch (\Exception $e) {
            error_log("Get users by IDs error: " . $e->getMessage());
            return [];
        }
    }
}
