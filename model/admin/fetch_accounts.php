<?php
header("Content-Type: application/json; charset=utf-8");
require_once("../db_connect.php");

session_start();
$department = $_SESSION['Department'] ?? '';

if (!$department) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT Teller_ID, FName, LName, Username 
        FROM teller_info 
        WHERE Department = ?
        ORDER BY FName ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();

$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}

echo json_encode($accounts);

$stmt->close();
$conn->close();
?>