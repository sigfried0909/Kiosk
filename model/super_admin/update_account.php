<?php
require_once("../db_connect.php");

$id = $_POST['id'] ?? '';
$fname = trim($_POST['first_name'] ?? '');
$lname = trim($_POST['last_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$id) {
    echo "Invalid Teller ID";
    exit;
}

// Fetch current data
$stmt = $conn->prepare("SELECT FName, LName, Username, Password FROM teller_info WHERE Teller_ID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current) {
    echo "Account not found";
    exit;
}

// Assign new values (keep old if blank)
$newFname = $fname !== '' ? $fname : $current['FName'];
$newLname = $lname !== '' ? $lname : $current['LName'];
$newUsername = $username !== '' ? $username : $current['Username'];
$newPassword = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : $current['Password'];

// Detect if actual change exists
$changed = false;
if ($newFname !== $current['FName'])
    $changed = true;
if ($newLname !== $current['LName'])
    $changed = true;
if ($newUsername !== $current['Username'])
    $changed = true;
if ($password !== '')
    $changed = true;

if (!$changed) {
    echo "nochange";
    exit;
}

// Perform update
$update = $conn->prepare("
    UPDATE teller_info
    SET FName=?, LName=?, Username=?, Password=?
    WHERE Teller_ID=?
");
$update->bind_param("ssssi", $newFname, $newLname, $newUsername, $newPassword, $id);

echo $update->execute() ? "Success" : "Error updating teller";

$update->close();
$conn->close();
?>