<?php
header("Content-Type: application/json; charset=utf-8");
require_once("db_connect.php");

$data = json_decode(file_get_contents("php://input"), true);
$department = $data['department'] ?? '';

if (!$department) {
    echo json_encode(["error" => "Missing department"]);
    exit();
}

$sql = "SELECT Ticket_ID FROM queue_info WHERE Department=? AND Status='Serving' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    echo json_encode(["error" => "No active ticket to page"]);
    exit();
}
$ticket = $row['Ticket_ID'];

$conn->query("DELETE FROM page_trigger WHERE Department='$department'");
$conn->query("INSERT INTO page_trigger (Department, Ticket_Num, Triggered_At)
              VALUES ('$department', '$ticket', NOW())");

echo json_encode(["success" => true, "message" => "Paged ticket $ticket for $department"]);
?>