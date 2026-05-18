<?php
header("Content-Type: application/json");
require_once("../db_connect.php");
session_start();

if (!isset($_SESSION['reset_user'])) die("Unauthorized");

$username = $_SESSION['reset_user'];
$otp = trim($_POST['otp'] ?? '');

// Fetch OTP
$stmt = $conn->prepare("SELECT otp_code, otp_expires FROM super_admin_info WHERE Username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: ../../verify_otp?error=User not found");
    exit;
}
if ($otp !== $user['otp_code']) {
    header("Location: ../../verify_otp?error=Incorrect OTP");
    exit;
}
if (strtotime($user['otp_expires']) < time()) {
    header("Location: ../../verify_otp?error=OTP expired, request again");
    exit;
}

// OTP verified
$_SESSION['otp_verified'] = true;
header("Location: ../../new_password");
exit;
