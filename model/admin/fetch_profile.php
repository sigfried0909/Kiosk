<?php
header("Content-Type: application/json; charset=utf-8");
require_once("../db_connect.php");

session_start();
$username = $_SESSION['Username'] ?? '';

if (!$username) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$sql = "SELECT FName, LName, Username FROM admin_info WHERE Username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(["error" => "Profile not found"]);
}

$stmt->close();
$conn->close();
?>