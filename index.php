<?php
// --- SESSION SECURITY ---
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true,
    'cookie_samesite' => 'Strict'
]);

// --- REDIRECT IF ALREADY LOGGED IN ---
if (isset($_SESSION['Username']) && isset($_SESSION['UserType'])) {
    switch ($_SESSION['UserType']) {
        case 'super_admin':
            header("Location: /superadmin");
            break;
        case 'admin':
            header("Location: /admin");
            break;
        case 'teller':
            header("Location: /teller");
            break;
    }
    exit();
}

// --- CSRF TOKEN SETUP ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- SECURITY HEADERS ---
header("X-Frame-Options: DENY");            // Prevent clickjacking
header("X-Content-Type-Options: nosniff");  // Prevent MIME sniffing
header("X-XSS-Protection: 1; mode=block");  // Legacy XSS filter
header("Referrer-Policy: no-referrer");     // Hide referrer info
header("Permissions-Policy: geolocation=(), microphone=()"); // Disable unwanted APIs
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com;
        style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
        font-src https://fonts.gstatic.com;
        script-src 'self';
        img-src 'self' data:;
        form-action 'self';
        frame-ancestors 'none';
    ">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image" href="assets/images/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/login.css">
</head>

<body>
    <div class="login-card">
        <h2>Login</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message"><?= htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form action="model/login_process.php" method="POST" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

            <input type="text" name="username" placeholder="Username" required pattern="[A-Za-z0-9_]{3,20}"
                title="Only letters, numbers, and underscores (3–20 chars)">

            <input type="password" name="password" placeholder="Password" required minlength="6">

            <button type="submit" name="login">Login</button>
        </form>
        <a href="forgot_password" style="display:block; margin-top:10px; text-align:center;">Forgot Password?</a>


        <div class="footer">
            Powered By: <span>ZionTech</span>
        </div>
    </div>
</body>

</html>