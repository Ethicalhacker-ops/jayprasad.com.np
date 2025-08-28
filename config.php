<?php
// web-account/config.php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mailserver_accounts');
define('DB_USER', 'mailadmin');
define('DB_PASS', 'your_secure_password');

// reCAPTCHA configuration (get from https://www.google.com/recaptcha)
define('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key');
define('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key');

// Mailserver configuration
define('MAILSERVER_CMD', 'docker exec mailserver setup email add');
define('DOMAIN', 'jayprasad.com.np');

// Rate limiting
define('MAX_ATTEMPTS_PER_HOUR', 5);
define('MAX_REGISTRATIONS_PER_IP', 3);

// Connect to database
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $db;
}
?>
