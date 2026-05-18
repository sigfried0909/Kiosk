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
<title>Verify OTP</title>
<link rel="icon" type="image" href="assets/images/logo.jpg">
<link rel="stylesheet" href="style/login.css">
<style>
.error { color: red; font-size: 0.9em; margin-bottom: 10px; text-align:center; }
.success { color: green; font-size: 0.9em; margin-bottom: 10px; text-align:center; }
.instruction { color: #555; font-size: 0.85em; margin-bottom: 15px; text-align:center; }
</style>
</head>
<body>
<div class="login-card">
    <h2>Verify OTP</h2>

    <?php if(isset($_GET['error'])): ?>
        <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php elseif(isset($_GET['success'])): ?>
        <div class="success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <form action="model/super_admin/verify_otp.php" method="POST">
        <input type="text" name="otp" placeholder="Enter OTP" maxlength="6" required>
        <!-- Instruction -->
        <div class="instruction">
            Please enter the OTP. Check your inbox or spam folder.
        </div>
        <button type="submit">Verify</button>
    </form>

    <a href="forgot_password" style="text-align:center; display:block; margin-top:10px;">Resend OTP</a>
</div>
</body>
</html>
