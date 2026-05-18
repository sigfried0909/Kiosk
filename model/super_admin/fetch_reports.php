<?php
header("Content-Type: application/json");
require_once("../db_connect.php");

$dept = $_GET['dept'] ?? '';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$status = $_GET['status'] ?? 'All';

$where = [];

if ($dept !== '') {
    $where[] = "Type = '$dept'";
}

if ($start && $end) {
    $where[] = "Date BETWEEN '$start' AND '$end'";
}

/* ✔ Only show Served & Skipped */
$where[] = "Status IN ('Served', 'Skipped')";

/* ✔ Apply dropdown filter */
if ($status !== "All") {
    $where[] = "Status = '$status'";
}

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

/* ORDER BY time_queue ASC -> first queued to latest */
$query = "SELECT * FROM report $whereSQL ORDER BY time_queue ASC";

$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc())
    $data[] = $row;

echo json_encode($data);
$conn->close();
