<?php
// config.php - Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'test_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('MAX_ATTEMPTS_PER_HOUR', 5);
define('MAX_REGISTRATIONS_PER_IP', 3);
define('DOMAIN', 'jayprasad.com.np');
define('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key');
define('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key');
define('MAILSERVER_CMD', '/usr/sbin/add_email_account');

session_start();

// Database connection function
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $db;
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Initialize variables
$error = '';
$success = '';
$form_data = [];

// Process login form if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            
            // Validate input
            if (empty($email) || empty($password)) {
                $error = "Please fill in all fields.";
            } else {
                try {
                    // Check rate limiting
                    $db = getDB();
                    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts 
                                         WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                                         AND success = 0");
                    $stmt->execute([$ipAddress]);
                    
                    if ($stmt->fetchColumn() >= MAX_ATTEMPTS_PER_HOUR) {
                        $error = "Too many failed login attempts. Please try again later.";
                    } else {
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
                                
                                $success = "Login successful! Redirecting to dashboard...";
                                header("refresh:2;url=dashboard.php");
                            } else {
                                $error = "Your account is not active. Please contact administrator.";
                            }
                        } else {
                            $error = "Invalid email or password.";
                            
                            // Record failed attempt
                            $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)");
                            $stmt->execute([$email, $ipAddress]);
                        }
                    }
                } catch(PDOException $e) {
                    $error = "A system error occurred. Please try again later.";
                }
            }
        } elseif ($_POST['action'] === 'register') {
            $fullName = trim($_POST['full_name']);
            $emailPrefix = trim($_POST['email_prefix']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            
            // Store form data for repopulation
            $form_data = [
                'full_name' => $fullName,
                'email_prefix' => $emailPrefix
            ];
            
            // Validate inputs
            $errors = [];
            
            // Check rate limiting
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT COUNT(*) FROM registration_attempts 
                                     WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                $stmt->execute([$ipAddress]);
                if ($stmt->fetchColumn() >= MAX_REGISTRATIONS_PER_IP) {
                    $errors[] = "Too many registration attempts from your IP address. Please try again later.";
                }
            } catch(PDOException $e) {
                $errors[] = "A system error occurred. Please try again later.";
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
            try {
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = "This email address is already registered.";
                }
            } catch(PDOException $e) {
                $errors[] = "A system error occurred. Please try again later.";
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
                    
                    // Record registration attempt
                    $stmt = $db->prepare("INSERT INTO registration_attempts (ip_address) VALUES (?)");
                    $stmt->execute([$ipAddress]);
                    
                    $db->commit();
                    
                    $success = "Account created successfully! You can now login.";
                    $form_data = []; // Clear form data
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $errors[] = "Registration failed: " . $e->getMessage();
                }
            }
            
            if (!empty($errors)) {
                $error = implode("<br>", $errors);
            }
        }
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
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon"><i class="fas fa-exclamation-circle"></i></span> 
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon"><i class="fas fa-check-circle"></i></span> 
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
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
                               placeholder="yourname@jayprasad.com.np">
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Sign In</button>
                </form>
                
                <p class="help-link">
                    <a href="#">Access Webmail directly</a> | 
                    <a href="#">Setup Help</a>
                </p>
            </div>
            
            <div id="register-tab" class="tab-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label for="register-fullname">Full Name</label>
                        <input type="text" id="register-fullname" name="full_name" required 
                               value="<?php echo isset($form_data['full_name']) ? htmlspecialchars($form_data['full_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Desired Email Address</label>
                        <div class="input-group">
                            <input type="text" id="register-email-prefix" name="email_prefix" 
                                   placeholder="yourname" required
                                   value="<?php echo isset($form_data['email_prefix']) ? htmlspecialchars($form_data['email_prefix']) : ''; ?>">
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
                    
                    <button type="submit" class="btn btn-success">Create Account</button>
                </form>
            </div>
        </div>
    </div>

    <script>
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
        const registerForm = document.querySelector('#register-tab form');
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
                } // Validate password length if (password.value.length
                < 8) { 
                alert("Password must be at least 8 characters long.”); return false; 
                             }
            // Validate password match if
            (password.value !== confirmPassword.value) {
                alert("Passwords don't match"); return false;
            } return true;
        }; 
    } 
 // Show register tab if there was an error with registration 
<?php if (!empty(Serror) && isset($_POST['‘action']) && $_POST[‘action'] s== 'register'): 
>
showTab('register');
<
?php endif; ?
>5 </script>
</body> 
</html> 

                                                                                                                                                                                                                                                                                                                                                                          
