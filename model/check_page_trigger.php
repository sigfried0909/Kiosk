<?php
header("Content-Type: application/json; charset=utf-8");
require_once("db_connect.php");

// Get all recent triggers in last 10 seconds
$sql = "SELECT Department, Ticket_Num, Triggered_At
        FROM page_trigger
        WHERE Triggered_At >= NOW() - INTERVAL 10 SECOND";
$result = $conn->query($sql);

$triggers = [];
while ($row = $result->fetch_assoc()) {
    $triggers[] = $row;
}

echo json_encode($triggers);
?>