<?php
require_once __DIR__ . '/config.php';
use Kreait\Firebase\Factory;

function get_firebase_auth() {
    static $auth;
    if (!$auth) {
        try {
            $factory = (new Factory)
                ->withServiceAccount(__DIR__ . '/sreemeditec-final-firebase-adminsdk-fbsvc-6184119249.json');
            $auth = $factory->createAuth();
        } catch (\Exception $e) {
            error_log("Firebase Auth creation failed: " . $e->getMessage());
            sendJsonResponse(['error' => 'Could not initialize Firebase Authentication.'], 500);
        }
    }
    return $auth;
}

function get_firebase_firestore() {
    static $firestore;
    if (!$firestore) {
        try {
            $factory = (new Factory)
                ->withServiceAccount(__DIR__ . '/sreemeditec-final-firebase-adminsdk-fbsvc-6184119249.json');
            $firestore = $factory->createFirestore();
        } catch (\Exception $e) {
            error_log("Firebase Firestore creation failed: " . $e->getMessage());
            sendJsonResponse(['error' => 'Could not initialize Firebase Firestore.'], 500);
        }
    }
    return $firestore;
}
?>