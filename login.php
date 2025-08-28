<?php
// web-account/login.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    // Check rate limiting
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts 
                         WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                         AND success = 0");
    $stmt->execute([$ipAddress]);
    if ($stmt->fetchColumn() >= MAX_ATTEMPTS_PER_HOUR) {
        $_SESSION['error'] = "Too many failed login attempts. Please try again later.";
        header('Location: index.php');
        exit;
    }
    
    // Verify user credentials
    $stmt = $db->prepare("SELECT id, email, password_hash, full_name, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] === 'active') {
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // Update last login
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Record successful attempt
            $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 1)");
            $stmt->execute([$email, $ipAddress]);
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Your account is not active. Please contact administrator.";
        }
    } else {
        $error = "Invalid email or password.";
        
        // Record failed attempt
        $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)");
        $stmt->execute([$email, $ipAddress]);
    }
    
    $_SESSION['error'] = $error;
    header('Location: index.php');
    exit;
}

header('Location: index.php');
exit;
?>
