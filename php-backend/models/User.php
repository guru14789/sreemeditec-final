<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/firebase.php';

use Kreait\Firebase\Auth;

class User 
{
    private Auth $auth;
    
    public function __construct()
    {
        global $auth;
        $this->auth = $auth;
    }
    
    public function register(array $userData): array
    {
        // Validate required fields
        $requiredFields = ['username', 'email', 'password', 'phone'];
        $errors = validateRequiredFields($userData, $requiredFields);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $userProperties = [
                'email' => strtolower(trim($userData['email'])),
                'emailVerified' => false,
                'password' => $userData['password'],
                'displayName' => sanitizeInput($userData['username']),
                'disabled' => false,
            ];
            $createdUser = $this->auth->createUser($userProperties);

            // You might want to store additional user data (phone, address, etc.) in Firestore
            // For now, we'll just return the Firebase User UID
            return [
                'success' => true,
                'user_id' => $createdUser->uid,
                'message' => 'User registered successfully'
            ];
        } catch (\Kreait\Firebase\Exception\AuthException | \Kreait\Firebase\Exception\FirebaseException $e) {
            error_log("User registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    public function login(string $email, string $password): array
    {
        try {
            $signInResult = $this->auth->signInWithEmailAndPassword(strtolower(trim($email)), $password);
            $user = $signInResult->firebaseUserId();
            $idToken = $signInResult->idToken();

            // Optionally, you can retrieve more user data from Firebase Auth or Firestore
            $firebaseUser = $this->auth->getUser($user);

            return [
                'success' => true,
                'user' => [
                    'id' => $firebaseUser->uid,
                    'email' => $firebaseUser->email,
                    'displayName' => $firebaseUser->displayName,
                    // Add other relevant fields from FirebaseUser object
                ],
                'idToken' => $idToken,
            ];
        } catch (\Kreait\Firebase\Exception\AuthException | \Kreait\Firebase\Exception\FirebaseException $e) {
            error_log("User login error: " . $e->getMessage());
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    public function getUserByUid(string $uid): ?array
    {
        try {
            $user = $this->auth->getUser($uid);

            return [
                'id' => $user->uid,
                'email' => $user->email,
                'displayName' => $user->displayName,
                'emailVerified' => $user->emailVerified,
                'disabled' => $user->disabled,
                'createdAt' => $user->metadata->createdAt()->format('Y-m-d H:i:s'),
                'lastLoginAt' => $user->metadata->lastLoginAt()->format('Y-m-d H:i:s'),
                // Add other relevant fields from FirebaseUser object
            ];
        } catch (\Kreait\Firebase\Exception\AuthException | \Kreait\Firebase\Exception\FirebaseException $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    // The following methods will need to be adapted to use Firestore if you
    // store additional user profile data beyond what Firebase Auth provides.
    // For this task, we are focusing only on Authentication.
    // If you need to implement these, please provide further instructions.

    /*
    public function updateProfile(string $userId, array $updateData): array
    {
        // This method needs to be adapted for Firestore if you store profile data there.
    }
    
    public function changePassword(string $userId, string $currentPassword, string $newPassword): array
    {
        // Firebase Authentication handles password changes differently.
        // You would typically use methods provided by the Firebase client SDKs
        // on the frontend for the authenticated user to change their password.
        // If you need a backend process for this (e.g., admin functionality),
        // you would use the Admin SDK's updateUser method, but you wouldn't
        // verify the current password this way.
    }
    
    public function emailExists(string $email): bool
    {
         try {
             $user = $this->auth->getUserByEmail(strtolower(trim($email)));
             return $user !== null;
         } catch (\Kreait\Firebase\Exception\AuthException $e) {
             // User not found is an expected scenario, so we return false
             if ($e->getCode() === 404) {
                 return false;
             }
             // Other errors should be logged
             error_log("Email check error: " . $e->getMessage());
             return false;
         } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
            error_log("Email check error: " . $e->getMessage());
            return false;
         }
    }
    
    public function getAllUsers(): array
    {
        // This method would require fetching all users from Firebase Auth
        // which might have performance implications for large numbers of users.
        // Alternatively, if you store user data in Firestore, you would query Firestore.
    }

    public function deleteUser(string $userId): array
    {
         try {
             $this->auth->deleteUser($userId);
             return ['success' => true, 'message' => 'User deleted successfully'];
         } catch (\Kreait\Firebase\Exception\AuthException | \Kreait\Firebase\Exception\FirebaseException $e) {
             error_log("Delete user error: " . $e->getMessage());
             return ['success' => false, 'errors' => [$e->getMessage()]];
         }
    }
    */
}











































































































































































































































































































































































































































































































































































































































































































































































































































































































































































































































































































































































































































































































 the/home/user/.firebase/credentials\n--- /dev/null
++ b/home/user/.firebase/credentials
new file mode/home/user/.firebase/credentials\ncomposer update` has been successfully executed and the Firebase SDK has been installed. The next step is to configure the User model to use Firebase for authentication.
            'username' => sanitizeInput($userData['username']),
            'email' => strtolower(trim($userData['email'])),
            'password' => $hashedPassword,
            'phone' => sanitizeInput($userData['phone']),
            'address' => sanitizeInput($userData['address'] ?? ''),
            'profile_picture' => null,
            'role' => 'user',
            'email_verified' => false,
            'is_active' => true,
            'created_at' => new UTCDateTime(),
            'updated_at' => new UTCDateTime()
        ];
        
        try {
            $result = $this->collection->insertOne($user);
            return [
                'success' => true,
                'user_id' => (string)$result->getInsertedId(),
                'message' => 'User registered successfully'
            ];
        } catch (Exception $e) {
            error_log("User registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed']];
        }
    }
    
    public function login(string $email, string $password): array
    {
        try {
            error_log("Attempting login for: " . $email);
            $user = $this->collection->findOne(['email' => strtolower(trim($email))]);
            
            if (!$user) {
                error_log("Login failed: User not found.");
                return ['success' => false, 'errors' => ['Invalid credentials']];
            }
            
            error_log("User found: " . print_r($user, true));

            if (!isset($user->password)) {
                error_log("Login failed: Password field does not exist for user.");
                return ['success' => false, 'errors' => ['Login failed']];
            }
            
            $isPasswordCorrect = password_verify($password, $user->password);
            error_log("Password verification result: " . ($isPasswordCorrect ? 'true' : 'false'));

            if (!$isPasswordCorrect) {
                error_log("Login failed: Invalid password.");
                return ['success' => false, 'errors' => ['Invalid credentials']];
            }
            
            if (!$user->is_active) {
                return ['success' => false, 'errors' => ['Account is deactivated']];
            }
            
            // Update last login
            $this->collection->updateOne(
                ['_id' => $user->_id],
                ['$set' => ['last_login' => new UTCDateTime()]]
            );
            
            error_log("Login successful for: " . $email);
            return [
                'success' => true,
                'user' => [
                    'id' => (string)$user->_id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address ?? '',
                    'role' => $user->role,
                    'profile_picture' => $user->profile_picture
                ]
            ];
        } catch (Exception $e) {
            error_log("User login error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Login failed']];
        }
    }
    
    public function getUserById(string $userId): ?array
    {
        try {
            $user = $this->collection->findOne(['_id' => new ObjectId($userId)]);
            
            if (!$user) {
                return null;
            }
            
            return [
                'id' => (string)$user->_id,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address ?? '',
                'role' => $user->role,
                'profile_picture' => $user->profile_picture,
                'email_verified' => $user->email_verified ?? false,
                'created_at' => $user->created_at->toDateTime()->format('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    public function updateProfile(string $userId, array $updateData): array
    {
        try {
            $allowedFields = ['username', 'phone', 'address', 'profile_picture'];
            $updateFields = [];
            
            foreach ($updateData as $field => $value) {
                if (in_array($field, $allowedFields) && !empty($value)) {
                    $updateFields[$field] = sanitizeInput($value);
                }
            }
            
            if (empty($updateFields)) {
                return ['success' => false, 'errors' => ['No valid fields to update']];
            }
            
            $updateFields['updated_at'] = new UTCDateTime();
            
            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($userId)],
                ['$set' => $updateFields]
            );
            
            if ($result->getModifiedCount() > 0) {
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'errors' => ['No changes made']];
            }
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Profile update failed']];
        }
    }
    
    public function changePassword(string $userId, string $currentPassword, string $newPassword): array
    {
        try {
            $user = $this->collection->findOne(['_id' => new ObjectId($userId)]);
            
            if (!$user) {
                return ['success' => false, 'errors' => ['User not found']];
            }
            
            if (!password_verify($currentPassword, $user->password)) {
                return ['success' => false, 'errors' => ['Current password is incorrect']];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
            
            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($userId)],
                ['$set' => ['password' => $hashedPassword, 'updated_at' => new UTCDateTime()]]
            );
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Password change failed']];
        }
    }
    
    public function emailExists(string $email): bool
    {
        try {
            $user = $this->collection->findOne(['email' => strtolower(trim($email))]);
            return $user !== null;
        } catch (Exception $e) {
            error_log("Email check error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllUsers(): array
    {
        try {
            $users = $this->collection->find([], [
                'projection' => ['password' => 0],
                'sort' => ['created_at' => -1]
            ])->toArray();
            
            return array_map(function($user) {
                return [
                    'id' => (string)$user->_id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'is_active' => $user->is_active ?? true,
                    'created_at' => $user->created_at->toDateTime()->format('Y-m-d H:i:s')
                ];
            }, $users);
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }

    public function deleteUser(string $userId): array
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($userId)]);

            if ($result->getDeletedCount() > 0) {
                return ['success' => true, 'message' => 'User deleted successfully'];
            } else {
                return ['success' => false, 'errors' => ['User not found']];
            }
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['User deletion failed']];
        }
    }
}
?>