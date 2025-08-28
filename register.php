<?php
// web-account/index.php
session_start();
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Process registration form if submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
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
    
    // Store errors in session
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: index.php#register');
    exit;
}

// Process login form if submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    // Add your login processing code here
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate login credentials
    $db = getDB();
    $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        $_SESSION['errors'] = ["Invalid email or password."];
        header('Location: index.php#login');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JayPrasad Mail - Account Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .header {
            background: #4a6fc3;
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 15px;
            font-size: 40px;
            color: #4a6fc3;
        }
        
        h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .auth-container {
            padding: 25px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #eaeaea;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px;
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: #777;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            color: #4a6fc3;
            border-bottom: 3px solid #4a6fc3;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        
        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        input:focus {
            border-color: #4a6fc3;
            outline: none;
        }
        
        .input-group {
            display: flex;
        }
        
        .input-group input {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .input-group-text {
            padding: 14px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-left: none;
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
            color: #666;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-primary {
            background: #4a6fc3;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3b5aa6;
        }
        
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .help-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .help-link a {
            color: #4a6fc3;
            text-decoration: none;
            margin: 0 5px;
        }
        
        .help-link a:hover {
            text-decoration: underline;
        }
        
        .g-recaptcha {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .alert-icon {
            margin-right: 10px;
            font-size: 20px;
        }
        
        @media (max-width: 480px) {
            .container {
                border-radius: 8px;
            }
            
            .header {
                padding: 20px;
            }
            
            .auth-container {
                padding: 20px;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .input-group-text {
                border: 1px solid #ddd;
                border-top: none;
                border-radius: 0 0 6px 6px;
            }
            
            .input-group input {
                border-radius: 6px 6px 0 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-envelope"></i>
            </div>
            <h1>JayPrasad Mail Service</h1>
        </div>
        
        <div class="auth-container">
            <!-- Error/Success Messages -->
            <div id="message-container">
                <?php
                if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])) {
                    foreach ($_SESSION['errors'] as $error) {
                        echo '<div class="alert alert-error">';
                        echo '<span class="alert-icon"><i class="fas fa-exclamation-circle"></i></span> ' . htmlspecialchars($error);
                        echo '</div>';
                    }
                    unset($_SESSION['errors']);
                }
                
                if (isset($_SESSION['success'])) {
                    echo '<div class="alert alert-success">';
                    echo '<span class="alert-icon"><i class="fas fa-check-circle"></i></span> ' . htmlspecialchars($_SESSION['success']);
                    echo '</div>';
                    unset($_SESSION['success']);
                }
                ?>
            </div>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('login')">Login</button>
                <button class="tab-btn" onclick="showTab('register')">Create Account</button>
            </div>
            
            <div id="login-tab" class="tab-content active">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="login-email">Email Address</label>
                        <input type="email" id="login-email" name="email" required 
                               placeholder="yourname@jayprasad.com.np" value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Sign In</button>
                </form>
                
                <p class="help-link">
                    <a href="/webmail/">Access Webmail directly</a> | 
                    <a href="/email-help">Setup Help</a>
                </p>
            </div>
            
            <div id="register-tab" class="tab-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label for="register-fullname">Full Name</label>
                        <input type="text" id="register-fullname" name="full_name" required 
                               value="<?php echo isset($_SESSION['form_data']['full_name']) ? htmlspecialchars($_SESSION['form_data']['full_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Desired Email Address</label>
                        <div class="input-group">
                            <input type="text" id="register-email-prefix" name="email_prefix" 
                                   placeholder="yourname" required
                                   value="<?php echo isset($_SESSION['form_data']['email_prefix']) ? htmlspecialchars($_SESSION['form_data']['email_prefix']) : ''; ?>">
                            <span class="input-group-text">@jayprasad.com.np</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input type="password" id="register-password" name="password" required 
                               pattern=".{8,}" title="8 characters minimum">
                        <p class="password-requirements">Minimum 8 characters</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-confirm">Confirm Password</label>
                        <input type="password" id="register-confirm" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Create Account</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Show appropriate tab based on URL hash or errors
    document.addEventListener('DOMContentLoaded', function() {
        const urlHash = window.location.hash;
        const hasErrors = document.querySelector('.alert-error') !== null;
        
        if (urlHash === '#register' || hasErrors) {
            showTab('register');
        } else {
            showTab('login');
        }
        
        // Clear form data from session after displaying it
        <?php unset($_SESSION['form_data']); ?>
    });
    
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName + '-tab').classList.add('active');
        document.querySelector(`.tab-btn:nth-child(${tabName === 'login' ? 1 : 2})`).classList.add('active');
    }
    
    // Simple password confirmation validation
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.querySelector('form[action=""][method="POST"]');
        const password = document.getElementById('register-password');
        const confirmPassword = document.getElementById('register-confirm');
        const emailPrefix = document.getElementById('register-email-prefix');
        
        if (registerForm && password && confirmPassword) {
            registerForm.onsubmit = function() {
                // Validate email prefix format
                const emailRegex = /^[a-zA-Z0-9._-]+$/;
                if (!emailRegex.test(emailPrefix.value)) {
                    alert("Email prefix can only contain letters, numbers, dots, hyphens, and underscores.");
                    return false;
                }
                
                // Validate password length
                if (password.value.length < 8) {
                    alert("Password must be at least 8 characters long.");
                    return false;
                }
                
                // Validate password match
                if (password.value !== confirmPassword.value) {
                    alert("Passwords don't match");
                    return false;
                }
                
                return true;
            };
        }
    });
    </script>
</body>
</html>
