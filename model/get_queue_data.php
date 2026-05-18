<?php
header("Content-Type: application/json; charset=utf-8");
require_once("db_connect.php");

$department = $_GET['department'] ?? '';
if (!$department) {
    echo json_encode(["error" => "Missing department"]);
    exit();
}

/* --- NOW SERVING --- */
$sql = "SELECT q.Ticket_ID, t.Customer_Name, t.Contact_Num, q.Sequence_Num
        FROM queue_info q
        JOIN tickets t ON q.Ticket_ID = t.Ticket_ID
        WHERE q.Department=? AND q.Status='Serving'
        ORDER BY q.Queue_Num ASC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$nowServing = $row['Ticket_ID'] ?? '--';
$name = $row['Customer_Name'] ?? null;
$contact = $row['Contact_Num'] ?? null;

/* --- CURRENT SERVING REMARKS --- */
$lastRemark = null;

if ($nowServing && $nowServing !== '--') {
    $stmt = $conn->prepare("
        SELECT Remarks
        FROM queue_info
        WHERE Ticket_ID = ?
          AND Department = ?
          AND Status = 'Serving'
        ORDER BY Updated_At DESC, ID DESC
        LIMIT 1
    ");
    $stmt->bind_param("ss", $nowServing, $department);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $lastRemark = $res['Remarks'] ?? '';
}


/* --- HELPERS --- */
function getTickets($conn, $dept, $status)
{
    $stmt = $conn->prepare("SELECT Ticket_ID FROM queue_info WHERE Department=? AND Status=? ORDER BY Queue_Num ASC");
    $stmt->bind_param("ss", $dept, $status);
    $stmt->execute();
    $r = $stmt->get_result();
    $out = [];
    while ($row = $r->fetch_assoc())
        $out[] = $row['Ticket_ID'];
    return $out;
}
function getCount($conn, $dept, $status)
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM queue_info WHERE Department=? AND Status=?");
    $stmt->bind_param("ss", $dept, $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['c'] ?? 0;
}
function getCountToday($conn, $dept, $status)
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS c 
        FROM queue_info 
        WHERE Department=? 
          AND Status=? 
          AND DATE(Updated_At) = CURDATE()
    ");
    $stmt->bind_param("ss", $dept, $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['c'] ?? 0;
}

function getSkippedTickets($conn, $dept)
{
    $stmt = $conn->prepare("
        SELECT 
            q.ID,
            q.Ticket_ID,
            q.Remarks,
            t.Customer_Name,
            t.Contact_Num
        FROM queue_info q
        JOIN tickets t ON q.Ticket_ID = t.Ticket_ID
        WHERE q.Department=? 
          AND q.Status='Skipped'
          AND DATE(q.Updated_At) = CURDATE()  -- Only today's skipped tickets
        ORDER BY q.Updated_At DESC, q.Queue_Num ASC
    ");
    $stmt->bind_param("s", $dept);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            "id"      => (int)$row['ID'],
            "ticket"  => $row['Ticket_ID'],
            "name"    => $row['Customer_Name'],
            "contact" => $row['Contact_Num'],
            "remarks" => $row['Remarks'] ?? ''
        ];
    }
    return $out;
}

/* --- JSON OUTPUT --- */
echo json_encode([
    "nowServing" => $nowServing,
    "name" => $name,
    "contact" => $contact,
    "remarks" => $lastRemark,
    "waitingTickets" => getTickets($conn, $department, 'Waiting'),
    "pendingCount" => getCount($conn, $department, 'Pending'),
    "servedCount"  => getCountToday($conn, $department, 'Served'),
    "skippedCount" => getCountToday($conn, $department, 'Skipped'),
    "skippedTickets" => getSkippedTickets($conn, $department)
]);
?>