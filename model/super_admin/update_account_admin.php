<?php
require_once("../db_connect.php");

$id = $_POST['idAdmin'] ?? '';
$fname = trim($_POST['first_nameAdmin'] ?? '');
$lname = trim($_POST['last_nameAdmin'] ?? '');
$username = trim($_POST['usernameAdmin'] ?? '');
$password = trim($_POST['passwordAdmin'] ?? '');

if (!$id) {
    echo "Invalid Admin ID";
    exit;
}

// Fetch current data
$stmt = $conn->prepare("SELECT FName, Lname, Username, Password FROM admin_info WHERE ID = ?");
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
$newLname = $lname !== '' ? $lname : $current['Lname'];
$newUsername = $username !== '' ? $username : $current['Username'];
$newPassword = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : $current['Password'];

// Detect if actual change exists
$changed = false;
if ($newFname !== $current['FName'])
    $changed = true;
if ($newLname !== $current['Lname'])
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
    UPDATE admin_info
    SET FName=?, Lname=?, Username=?, Password=?
    WHERE ID=?
");
$update->bind_param("ssssi", $newFname, $newLname, $newUsername, $newPassword, $id);

echo $update->execute() ? "Success" : "Error updating teller";

$update->close();
$conn->close();
?>