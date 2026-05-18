<?php
header("Content-Type: application/json");
require_once("../db_connect.php");
session_start();

if (!isset($_SESSION['Username'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$username = $_SESSION['Username'];

$stmt = $conn->prepare("SELECT FName, LName, Username, Email FROM super_admin_info WHERE Username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(["error" => "Not found"]);
}

$stmt->close();
$conn->close();
?>
