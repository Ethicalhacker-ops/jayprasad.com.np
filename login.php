<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'test_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('MAX_ATTEMPTS_PER_HOUR', 5);

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

// Initialize variables
$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Process login form if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .header {
            background: #4a6fd0;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .form-container {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #4a6fd0;
            outline: none;
            box-shadow: 0 0 0 2px rgba(74, 111, 208, 0.2);
        }
        
        .btn {
            background: #4a6fd0;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #3a5fc0;
        }
        
        .message {
            padding: 12px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        
        .error {
            background: #ffebee;
            color: #d32f2f;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c3e6cb;
        }
        
        .footer {
            text-align: center;
            padding: 15px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #777;
        }
        
        .footer a {
            color: #4a6fd0;
            text-decoration: none;
        }
        
        @media (max-width: 480px) {
            .container {
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Account Login</h1>
            <p>Enter your credentials to access your account</p>
        </div>
        
        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
        
        <div class="footer">
            <p>Don't have an account? <a href="#">Sign up</a></p>
            <p><a href="#">Forgot your password?</a></p>
        </div>
    </div>
</body>
</html>
