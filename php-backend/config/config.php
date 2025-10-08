<?php
// Firebase
define('FIREBASE_API_KEY', 'AIzaSyDGim4IkNRi9DKlr5KwmcRmagJUXLmVzfc');
define('FIREBASE_AUTH_DOMAIN', 'sreemeditec-final.firebaseapp.com');
define('FIREBASE_PROJECT_ID', 'sreemeditec-final');
define('FIREBASE_STORAGE_BUCKET', 'sreemeditec-final.appspot.com');
define('FIREBASE_MESSAGING_SENDER_ID', '236444837209');
define('FIREBASE_APP_ID', '1:236444837209:web:16d3497b8b8c5566eb9848');
define('FIREBASE_MEASUREMENT_ID', 'G-M9RDRTWRR6');

// Database
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'sreemeditec');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'root');
?>