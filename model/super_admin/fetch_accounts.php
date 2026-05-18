<?php
header("Content-Type: application/json");
require_once("../db_connect.php");

$dept = $_GET['dept'] ?? '';

$sql = "SELECT Teller_ID, FName, LName, Username, Department 
        FROM teller_info";
if (!empty($dept)) {
    $sql .= " WHERE Department = ?";
}

$stmt = $conn->prepare($sql);
if (!empty($dept)) {
    $stmt->bind_param("s", $dept);
}
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($r = $result->fetch_assoc()) {
    $data[] = $r;
}

echo json_encode($data);
$stmt->close();
$conn->close();
?>