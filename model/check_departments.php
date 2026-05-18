<?php
header("Content-Type: application/json; charset=utf-8");
require_once("db_connect.php");

$dept = $_GET['department'] ?? '';
if (!$dept) {
    echo json_encode(["error" => "Missing department"]);
    exit();
}

$sql = "SELECT Ticket_ID, Sequence_Num FROM queue_info WHERE Department=? AND Status='Serving' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
if (!$current) {
    echo json_encode(["hasMultiple" => false, "isLast" => false]);
    exit();
}

$ticketID = $current['Ticket_ID'];
$currentSeq = (int) $current['Sequence_Num'];

$sqlSeq = "SELECT COUNT(DISTINCT Sequence_Num) AS seqCount FROM queue_info WHERE Ticket_ID=?";
$stmt = $conn->prepare($sqlSeq);
$stmt->bind_param("s", $ticketID);
$stmt->execute();
$seqCount = (int) $stmt->get_result()->fetch_assoc()['seqCount'];

$sql = "SELECT Department FROM queue_info WHERE Ticket_ID=? AND Sequence_Num>? ORDER BY Sequence_Num ASC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $ticketID, $currentSeq);
$stmt->execute();
$next = $stmt->get_result()->fetch_assoc();
$nextDept = $next['Department'] ?? null;

echo json_encode([
    "hasMultiple" => $seqCount > 1,
    "isLast" => $seqCount > 1 && !$nextDept
]);
?>