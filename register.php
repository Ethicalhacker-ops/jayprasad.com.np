<?php
// web-account/register.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $emailPrefix = trim($_POST['email_prefix']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    // Validate inputs
    $errors = [];
    
    // Check rate limiting
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM registration_attempts 
                         WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$ipAddress]);
    if ($stmt->fetchColumn() >= MAX_REGISTRATIONS_PER_IP) {
        $errors[] = "Too many registration attempts from your IP address. Please try again later.";
    }
    
    // Validate reCAPTCHA
    if (empty($recaptchaResponse)) {
        $errors[] = "Please complete the reCAPTCHA verification.";
    } else {
        $recaptcha = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".
            RECAPTCHA_SECRET_KEY."&response=".$recaptchaResponse."&remoteip=".$ipAddress);
        $recaptcha = json_decode($recaptcha);
        if (!$recaptcha->success) {
            $errors[] = "reCAPTCHA verification failed.";
        }
    }
    
    // Validate email prefix
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $emailPrefix)) {
        $errors[] = "Email prefix can only contain letters, numbers, dots, hyphens, and underscores.";
    }
    
    // Check password strength
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if email already exists
    $email = $emailPrefix . '@' . DOMAIN;
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "This email address is already registered.";
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Create user in database
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $verificationToken = bin2hex(random_bytes(32));
            
            $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, 
                                verification_token, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$email, $passwordHash, $fullName, $verificationToken, $ipAddress]);
            
            // Create email account in mailserver
            $command = MAILSERVER_CMD . " $email $password";
            $output = shell_exec($command);
            
            // Record registration attempt
            $stmt = $db->prepare("INSERT INTO registration_attempts (ip_address) VALUES (?)");
            $stmt->execute([$ipAddress]);
            
            $db->commit();
            
            // Send welcome email (optional)
            // mail($email, "Welcome to JayPrasad Mail", "Your account has been created successfully!");
            
            $_SESSION['success'] = "Account created successfully! You can now login.";
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
    
    // Store errors in session and redirect back
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: index.php#register');
    exit;
}

header('Location: index.php');
exit;
?>
