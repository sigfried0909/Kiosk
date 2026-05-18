<?php
require_once("../db_connect.php");
session_start();

if (!isset($_SESSION["Username"])) {
    echo "Session expired";
    exit;
}

$username = $_SESSION["Username"];
$new_username = trim($_POST["username"] ?? '');
$new_email = trim($_POST["email"] ?? '');
$fname = trim($_POST["fname"] ?? '');
$lname = trim($_POST["lname"] ?? '');
$old_password = trim($_POST["old_password"] ?? '');
$new_password = trim($_POST["new_password"] ?? '');

// Fetch current record
$stmt = $conn->prepare("SELECT Username, Email, FName, LName, Password FROM super_admin_info WHERE Username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current) {
    echo "Account not found";
    exit;
}

// Build updated values
$newUsername = $new_username !== '' ? $new_username : $current["Username"];
$newEmail = $new_email !== '' ? $new_email : $current["Email"];
$newFname = $fname !== '' ? $fname : $current["FName"];
$newLname = $lname !== '' ? $lname : $current["LName"];
$hashed = $current["Password"];

// Track if changed
$changed = false;
if ($newUsername !== $current["Username"]) $changed = true;
if ($newEmail !== $current["Email"]) $changed = true;
if ($newFname !== $current["FName"]) $changed = true;
if ($newLname !== $current["LName"]) $changed = true;

// Handle password change
if ($new_password !== '') {
    if (!password_verify($old_password, $current["Password"])) {
        echo "incorrect";
        exit;
    }
    if (password_verify($new_password, $current["Password"])) {
        echo "same";
        exit;
    }
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $changed = true;
}

if (!$changed) {
    echo "nochange";
    exit;
}

// Check if username is taken
if ($newUsername !== $current["Username"]) {
    $checkUser = $conn->prepare("SELECT Username FROM super_admin_info WHERE Username=?");
    $checkUser->bind_param("s", $newUsername);
    $checkUser->execute();
    if ($checkUser->get_result()->num_rows > 0) {
        echo "username_exists";
        exit;
    }
    $checkUser->close();
}

// Check if email is taken
if ($newEmail !== $current["Email"]) {
    $checkEmail = $conn->prepare("SELECT Email FROM super_admin_info WHERE Email=?");
    $checkEmail->bind_param("s", $newEmail);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        echo "email_exists";
        exit;
    }
    $checkEmail->close();
}

// Apply update
$update = $conn->prepare("UPDATE super_admin_info 
    SET FName=?, LName=?, Email=?, Username=?, Password=? 
    WHERE Username=?");

$update->bind_param("ssssss", $newFname, $newLname, $newEmail, $newUsername, $hashed, $username);

if ($update->execute()) {
    // Update session username if changed
    if ($newUsername !== $current["Username"]) {
        $_SESSION["Username"] = $newUsername;
    }
    echo "Success";
} else {
    echo "Error updating profile";
}

$update->close();
$conn->close();
?>
