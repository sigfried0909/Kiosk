<?php
header("Content-Type: application/json");
require_once("../db_connect.php");
session_start();

if (!isset($_SESSION['reset_user'])) {
    die("Unauthorized");
}
$username = $_SESSION['reset_user'];
$otp = trim($_POST['otp'] ?? '');
$new_pass = trim($_POST['new_password'] ?? '');

if ($otp == '' || $new_pass == '') {
    die("Missing fields");
}

// Fetch OTP record
$stmt = $conn->prepare("SELECT otp_code, otp_expires FROM super_admin_info WHERE Username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) die("User not found");

// Validate OTP expiration
if ($otp !== $user['otp_code']) {
    die("Invalid OTP");
}
if (strtotime($user['otp_expires']) < time()) {
    die("OTP expired");
}

// Update password
$hashed = password_hash($new_pass, PASSWORD_DEFAULT);
$upd = $conn->prepare("UPDATE super_admin_info SET Password=?, otp_code=NULL, otp_expires=NULL WHERE Username=?");
$upd->bind_param("ss", $hashed, $username);
$upd->execute();
$upd->close();

// Cleanup session
unset($_SESSION['reset_user']);

echo "Password Reset Successful!";
echo "<script>setTimeout(()=>{ window.location.href='../'; },1500);</script>";
