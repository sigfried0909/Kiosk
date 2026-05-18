<?php
header("Content-Type: application/json");
require_once("../db_connect.php");
session_start();

$email = trim($_POST['email'] ?? '');
if ($email == '') {
    header("Location: ../../forgot_password?error=Please enter your email");
    exit;
}

// Check email
$stmt = $conn->prepare("SELECT Username FROM super_admin_info WHERE Email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    header("Location: ../../forgot_password?error=Email not found");
    exit;
}

$username = $data['Username'];
$_SESSION['reset_user'] = $username;

// Generate OTP
$otp = rand(100000, 999999);
$expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

$upd = $conn->prepare("UPDATE super_admin_info SET otp_code=?, otp_expires=? WHERE Username=?");
$upd->bind_param("sss", $otp, $expiry, $username);
$upd->execute();
$upd->close();

// send email
$subject = "Your One-Time Password (OTP) Code";

$message = "Hello,\n\n";
$message .= "You requested a One-Time Password (OTP) to verify your identity.\n\n";
$message .= "Your OTP Code: $otp\n\n";
$message .= "This code is valid for 15 minutes from the time this email was sent.\n\n";
$message .= "For your security, do NOT share this code with anyone. Our support team will never ask for your OTP.\n\n";
$message .= "If you did not request this OTP, you may safely ignore this email.\n\n";
$message .= "This is an Auto Generated Email, please do not reply,\n";
$message .= "Z-KioskPro Support Team";

$headers = "From: noreply@z-kioskpro.com\r\n";
$headers .= "Reply-To: noreply@z-kioskpro.com\r\n";

mail($email, $subject, $message, $headers);

// Redirect to OTP page
header("Location: ../../verify_otp?success=OTP sent to your email");
exit;
