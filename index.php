<?php
// web-account/index.php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JayPrasad Mail - Account Management</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="/path/to/logo.png" alt="JayPrasad Mail" class="logo">
            <h1>JayPrasad Mail Service</h1>
        </div>
        
        <div class="auth-container">
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('login')">Login</button>
                <button class="tab-btn" onclick="showTab('register')">Create Account</button>
            </div>
            
            <div id="login-tab" class="tab-content active">
                <form action="login.php" method="POST">
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
                    <a href="/webmail/">Access Webmail directly</a> | 
                    <a href="/email-help">Setup Help</a>
                </p>
            </div>
            
            <div id="register-tab" class="tab-content">
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label for="register-fullname">Full Name</label>
                        <input type="text" id="register-fullname" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Desired Email Address</label>
                        <div class="input-group">
                            <input type="text" id="register-email-prefix" name="email_prefix" 
                                   placeholder="yourname" required>
                            <span class="input-group-text">@jayprasad.com.np</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input type="password" id="register-password" name="password" required 
                               pattern=".{8,}" title="8 characters minimum">
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
        event.currentTarget.classList.add('active');
    }
    </script>
</body>
</html>
