<?php
header("Content-Type: application/json; charset=utf-8");
require_once("db_connect.php");

$data = json_decode(file_get_contents("php://input"), true);
$dept = $data['department'] ?? '';
$id   = (int)($data['id'] ?? 0);

if (!$dept || !$id) {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

/* ==========================
   GET SKIPPED TICKET
========================== */
$row = $conn->query("
    SELECT q.ID, q.Queue_Num, q.Status, q.Recalled, t.Priority
    FROM queue_info q
    JOIN tickets t ON q.Ticket_ID = t.Ticket_ID
    WHERE q.ID=$id AND q.Department='$dept' AND q.Status='Skipped'
")->fetch_assoc();

if (!$row) {
    echo json_encode(["error" => "Skipped ticket not found"]);
    exit;
}

$newLevel = ((int)$row['Priority'] === 1) ? 3 : 2;

/* ==========================
   IF NO SERVING → SERVING
========================== */
$serving = $conn->query("
    SELECT ID FROM queue_info
    WHERE Department='$dept' AND Status='Serving'
")->fetch_assoc();

if (!$serving) {
    $conn->query("
        UPDATE queue_info
        SET Status='Serving', Queue_Num=1, Recalled=1
        WHERE ID=$id
    ");
    echo json_encode(["success"=>true,"newStatus"=>"Serving"]);
    exit;
}

/* ==========================
   BUILD ACTIVE QUEUE
========================== */
$queue = $conn->query("
    SELECT q.ID, q.Queue_Num,
           IF(t.Priority=1,3,IF(q.Recalled=1,2,1)) AS level
    FROM queue_info q
    JOIN tickets t ON q.Ticket_ID = t.Ticket_ID
    WHERE q.Department='$dept'
      AND q.Status IN ('Waiting','Pending')
    ORDER BY q.Queue_Num ASC
")->fetch_all(MYSQLI_ASSOC);

/* ==========================
   FIND FCFS INSERT POINT
========================== */
$insertAt = null;

foreach ($queue as $q) {
    if ($newLevel > $q['level']) {
        $insertAt = $q['Queue_Num'];
        break;
    }
}

/* ==========================
   APPEND IF NO SLOT
========================== */
if ($insertAt === null) {
    $next = $conn->query("
        SELECT COALESCE(MAX(Queue_Num),0)+1 AS q
        FROM queue_info WHERE Department='$dept'
    ")->fetch_assoc()['q'];

    $conn->query("
        UPDATE queue_info
        SET Status='Pending', Queue_Num=$next, Recalled=1
        WHERE ID=$id
    ");

    echo json_encode(["success"=>true,"newStatus"=>"Pending"]);
    exit;
}

/* ==========================
   SHIFT DOWN (ONE STEP)
========================== */
$conn->query("
    UPDATE queue_info
    SET Queue_Num = Queue_Num + 1
    WHERE Department='$dept'
      AND Queue_Num >= $insertAt
");

/* ==========================
   INSERT RECALLED TICKET
========================== */
$conn->query("
    UPDATE queue_info
    SET Status='Waiting', Queue_Num=$insertAt, Recalled=1
    WHERE ID=$id
");

echo json_encode([
    "success" => true,
    "newStatus" => "Waiting",
    "nowServing" => [
        "ticket"   => $servingInfo['Ticket_ID'] ?? null,
        "remarks"  => $servingInfo['Remarks'] ?? '',
        "name"     => $servingInfo['Customer_Name'] ?? '',
        "contact"  => $servingInfo['Contact_Num'] ?? ''
    ]
]);
?>
