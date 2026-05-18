<?php
require_once("../db_connect.php");
session_start();

if (!isset($_SESSION["Username"])) {
    echo "Session expired";
    exit;
}

$username = $_SESSION["Username"];
$new_username = trim($_POST["username"] ?? '');
$fname = trim($_POST["fname"] ?? '');
$lname = trim($_POST["lname"] ?? '');
$old_password = trim($_POST["old_password"] ?? '');
$new_password = trim($_POST["new_password"] ?? '');

// Fetch current data
$stmt = $conn->prepare("SELECT Username, FName, LName, Password FROM admin_info WHERE Username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current) {
    echo "Account not found";
    exit;
}

// Prepare updated values
$newUsername = $new_username !== '' ? $new_username : $current["Username"];
$newFname = $fname !== '' ? $fname : $current["FName"];
$newLname = $lname !== '' ? $lname : $current["LName"];
$hashed = $current["Password"];

// Track if changed
$changed = false;
if ($newUsername !== $current["Username"]) $changed = true;
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

// Check duplicate username
if ($newUsername !== $current["Username"]) {
    $check = $conn->prepare("SELECT Username FROM admin_info WHERE Username=?");
    $check->bind_param("s", $newUsername);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "username_exists";
        exit;
    }
    $check->close();
}

// Apply update
$update = $conn->prepare("UPDATE admin_info SET Username=?, FName=?, LName=?, Password=? WHERE Username=?");
$update->bind_param("sssss", $newUsername, $newFname, $newLname, $hashed, $username);

if ($update->execute()) {
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
