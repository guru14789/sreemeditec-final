<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/FirestoreRestClient.php';
use Kreait\Firebase\Factory;

function getServiceAccountCredentials() {
    $serviceAccountJson = getenv('FIREBASE_SERVICE_ACCOUNT');
    
    if ($serviceAccountJson) {
        $decoded = json_decode($serviceAccountJson, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $tempFile = sys_get_temp_dir() . '/firebase-credentials-' . md5($serviceAccountJson) . '.json';
            if (!file_exists($tempFile)) {
                file_put_contents($tempFile, $serviceAccountJson);
            }
            return $tempFile;
        }
        return $serviceAccountJson;
    }
    
    $fallbackPath = __DIR__ . '/sreemeditec-final-firebase-adminsdk-fbsvc-6184119249.json';
    if (file_exists($fallbackPath)) {
        return $fallbackPath;
    }
    
    throw new \Exception('Firebase service account credentials not found. Please set FIREBASE_SERVICE_ACCOUNT environment variable.');
}

function get_firebase_auth() {
    static $auth;
    if (!$auth) {
        try {
            $credentials = getServiceAccountCredentials();
            error_log("Loading Firebase with credentials: " . (is_string($credentials) ? $credentials : 'array'));
            
            $serviceAccount = is_string($credentials) && file_exists($credentials) 
                ? json_decode(file_get_contents($credentials), true)
                : (is_array($credentials) ? $credentials : json_decode($credentials, true));
            
            $projectId = $serviceAccount['project_id'] ?? 'unknown';
            $databaseUrl = "https://{$projectId}.firebaseio.com";
            
            error_log("Project ID: {$projectId}, Database URL: {$databaseUrl}");
            
            $factory = (new Factory)
                ->withServiceAccount($credentials)
                ->withDatabaseUri($databaseUrl);
                
            $auth = $factory->createAuth();
            error_log("Firebase Auth created successfully!");
        } catch (\Exception $e) {
            error_log("Firebase Auth creation failed: " . $e->getMessage());
            error_log("Exception class: " . get_class($e));
            sendJsonResponse(['error' => 'Could not initialize Firebase Authentication.'], 500);
        }
    }
    return $auth;
}

function get_firebase_firestore() {
    static $firestore;
    if (!$firestore) {
        try {
            $credentials = getServiceAccountCredentials();
            $firestore = new FirestoreRestClient($credentials);
        } catch (\Exception $e) {
            error_log("Firebase Firestore creation failed: " . $e->getMessage());
            sendJsonResponse(['error' => 'Could not initialize Firebase Firestore.'], 500);
        }
    }
    return $firestore;
}
?>
