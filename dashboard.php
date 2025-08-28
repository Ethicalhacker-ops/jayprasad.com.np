<?php
// web-account/dashboard.php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Get user info
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - JayPrasad Mail</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h1>
        </div>
        
        <div class="auth-container">
            <div class="alert alert-success">
                <h3>Your Account Information</h3>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Status:</strong> <?php echo ucfirst($user['status']); ?></p>
                <p><strong>Last Login:</strong> <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></p>
            </div>
            
            <div class="action-buttons">
                <a href="/webmail/" class="btn btn-primary">Open Webmail</a>
                <a href="logout.php" class="btn">Logout</a>
            </div>
            
            <div class="help-section">
                <h3>Setup Instructions</h3>
                <p><strong>IMAP:</strong> mail.jayprasad.com.np (SSL, port 993)</p>
                <p><strong>SMTP:</strong> mail.jayprasad.com.np (TLS, port 587)</p>
                <p><strong>Username:</strong> Your full email address</p>
            </div>
        </div>
    </div>
</body>
</html>
