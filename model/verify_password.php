<?php
session_start();
require_once("db_connect.php");

// Must be logged in to attempt password check
if (!isset($_SESSION['Username'])) {
    echo "DENIED";
    exit();
}

$input = $_POST['password'] ?? '';

// Fetch hashed password from super_admin_info table
$stmt = $conn->prepare("SELECT Password FROM super_admin_info LIMIT 1");
$stmt->execute();
$stmt->bind_result($hashed_pass);
$stmt->fetch();
$stmt->close();

// Compare typed password with hashed password
if (password_verify($input, $hashed_pass)) {
    echo "OK";
} else {
    echo "NO";
}
?>
