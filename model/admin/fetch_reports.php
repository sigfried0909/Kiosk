<?php
header("Content-Type: application/json; charset=utf-8");
require_once("../db_connect.php");

$department = $_GET['department'] ?? '';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

if (!$department || !$start || !$end) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT Date, Ticket_ID, Name, Time_Queue, Time_Served, Status, Remarks
        FROM report
        WHERE Type = ?
          AND Date BETWEEN ? AND ?
          AND Status IN ('Served','Skipped')
        ORDER BY Time_Queue ASC, Date ASC, Ticket_ID ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $department, $start, $end);
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
