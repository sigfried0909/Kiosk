<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
<title>Forgot Password</title>
<link rel="icon" type="image" href="assets/images/logo.jpg">
<link rel="stylesheet" href="style/login.css">
<style>
.error { color: red; font-size: 0.9em; margin-bottom: 10px; text-align:center; }
.success { color: green; font-size: 0.9em; margin-bottom: 10px; text-align:center; }
</style>
</head>
<body>
<div class="login-card">
    <h2>Reset Password</h2>

    <?php if(isset($_GET['error'])): ?>
        <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php elseif(isset($_GET['success'])): ?>
        <div class="success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <form action="model/super_admin/send_otp" method="POST" autocomplete="off">
        <input type="email" name="email" placeholder="Registered Email" required>
        <button type="submit">Send OTP</button>
    </form>

    <a href="/" style="text-align:center; display:block; margin-top:10px;">Back to Login</a>
</div>
</body>
</html>
