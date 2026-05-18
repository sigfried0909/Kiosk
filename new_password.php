<?php
session_start();
if (!isset($_SESSION['reset_user'])) {
    header("Location: /");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Set New Password</title>
<link rel="icon" type="image" href="assets/images/logo.jpg">
<link rel="stylesheet" href="style/login.css">
<style>
.error { color: red; font-size: 0.9em; margin-bottom: 10px; text-align:center; }
.success { color: green; font-size: 1em; margin-bottom: 10px; text-align:center; font-weight:bold; }
</style>
</head>
<body>
<div class="login-card">
    <h2>New Password</h2>

    <?php if(isset($_GET['error'])): ?>
        <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php elseif(isset($_GET['success'])): ?>
        <div class="success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <form action="model/super_admin/reset_password_superadmin.php" method="POST">
        <input type="password" name="new_password" placeholder="New Password" required minlength="6">
        <button type="submit">Reset Password</button>
    </form>
</div>
</body>
</html>
