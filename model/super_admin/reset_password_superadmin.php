<?php
header("Content-Type: application/json");
require_once("../db_connect.php");
session_start();

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_user'])) {
    header("Location: ../../");
    exit;
}

$username = $_SESSION['reset_user'];
$new = trim($_POST['new_password'] ?? '');

if ($new == '') {
    header("Location: ../../new_password?error=Password cannot be empty");
    exit;
}

$hashed = password_hash($new, PASSWORD_DEFAULT);

$upd = $conn->prepare("UPDATE super_admin_info SET Password=?, otp_code=NULL, otp_expires=NULL WHERE Username=?");
$upd->bind_param("ss", $hashed, $username);
$upd->execute();
$upd->close();

// Clear session
unset($_SESSION['reset_user']);
unset($_SESSION['otp_verified']);

// Redirect with success message
header("Location: ../../?success=Password reset successfully! You can now login.");
exit;
