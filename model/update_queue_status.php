<?php
header("Content-Type: application/json; charset=utf-8");
require_once("db_connect.php");

$data = json_decode(file_get_contents("php://input"), true);

$dept = $data['department'] ?? '';
$action = $data['action'] ?? '';
$remarks = $data['remarks'] ?? '';
$targetDept = $data['targetDept'] ?? '';

if (!$dept || !$action) {
    echo json_encode(["error" => "Missing parameters"]);
    exit();
}

/* ==========================================================
   GET CURRENT SERVING
========================================================== */
$sql = "SELECT * FROM queue_info WHERE Department=? AND Status='Serving' ORDER BY Queue_Num ASC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();

if (!$current) {
    echo json_encode(["message" => "No active ticket"]);
    exit();
}

$ticketID = $current['Ticket_ID'];
$currentSeq = (int)$current['Sequence_Num'];

/* ==========================================================
   FUNCTION: Reorder Pending by Level FCFS
========================================================== */
function reorderPendingFCFS($conn, $dept) {
    // Get all pending tickets in queue order
    $rows = $conn->query("
        SELECT q.ID, q.Queue_Num, t.Priority, q.Recalled
        FROM queue_info q
        JOIN tickets t ON q.Ticket_ID = t.Ticket_ID
        WHERE q.Department='$dept' AND q.Status='Pending'
        ORDER BY q.Queue_Num ASC, q.Updated_At ASC
    ");

    if (!$rows || $rows->num_rows == 0) return;

    $priority = [];
    $recalled = [];
    $normal = [];

    while ($r = $rows->fetch_assoc()) {
        if ((int)$r['Priority'] === 1) $priority[] = $r;
        else if ((int)$r['Recalled'] === 1) $recalled[] = $r;
        else $normal[] = $r;
    }

    $queueNum = 3; // After Serving=1 and Waiting=2
    foreach (array_merge($priority, $recalled, $normal) as $r) {
        $conn->query("UPDATE queue_info SET Queue_Num=$queueNum WHERE ID='{$r['ID']}'");
        $queueNum++;
    }
}

/* ==========================================================
   FUNCTION: Promote Next Waiting → Serving
========================================================== */
function promoteNext($conn, $dept)
{
    // Close current serving
    $conn->query("
        UPDATE queue_info
        SET Status='Served'
        WHERE Department='$dept' AND Status='Serving'
    ");

    // Promote FIRST FCFS ticket (Waiting + Pending)
    $next = $conn->query("
        SELECT ID FROM queue_info
        WHERE Department='$dept'
          AND Status IN ('Waiting','Pending')
        ORDER BY
          IF((SELECT Priority FROM tickets WHERE Ticket_ID=queue_info.Ticket_ID)=1,3,
             IF(Recalled=1,2,1)) DESC,
          Queue_Num ASC
        LIMIT 1
    ")->fetch_assoc();

    if ($next) {
        $conn->query("
            UPDATE queue_info
            SET Status='Serving'
            WHERE ID='{$next['ID']}'
        ");
    }

    // Set next Waiting
    $conn->query("
        UPDATE queue_info
        SET Status='Waiting'
        WHERE ID = (
            SELECT ID FROM (
                SELECT ID FROM queue_info
                WHERE Department='$dept'
                  AND Status='Pending'
                ORDER BY Queue_Num ASC
                LIMIT 1
            ) x
        )
    ");
}

/* ==========================================================
   FUNCTION: Forward Ticket to Next Department
========================================================== */
function forwardTicket($conn, $current, $targetDept, $remarks) {
    $ticketID = $current['Ticket_ID'];
    $currentSeq = (int)$current['Sequence_Num'];

    // Determine next department
    if ($targetDept) {
        $nextDept = $targetDept;
    } else {
        $stmt = $conn->prepare("SELECT Department FROM queue_info WHERE Ticket_ID=? AND Sequence_Num=? LIMIT 1");
        $stmt->bind_param("si", $ticketID, $currentSeq+1);
        $stmt->execute();
        $next = $stmt->get_result()->fetch_assoc();
        $nextDept = $next['Department'] ?? null;
    }

    if (!$nextDept) return false;

    // Determine queue numbers and status
    $isPriority = $conn->query("SELECT Priority FROM tickets WHERE Ticket_ID='$ticketID'")->fetch_assoc()['Priority'] == 1;

    $servingCount = $conn->query("SELECT COUNT(*) AS c FROM queue_info WHERE Department='$nextDept' AND Status='Serving'")->fetch_assoc()['c'];
    $waitingCount = $conn->query("SELECT COUNT(*) AS c FROM queue_info WHERE Department='$nextDept' AND Status='Waiting'")->fetch_assoc()['c'];
    $pendingCount = $conn->query("SELECT COUNT(*) AS c FROM queue_info WHERE Department='$nextDept' AND Status='Pending'")->fetch_assoc()['c'];

    $status = ($servingCount == 0) ? 'Serving' : 'Waiting';

    if ($isPriority && ($servingCount > 0 || $waitingCount > 0 || $pendingCount > 0)) {
        $status = 'Pending';

        // Insert before first non-priority pending
        $nonPriorityPending = $conn->query("
            SELECT q.ID, q.Queue_Num
            FROM queue_info q
            JOIN tickets t ON q.Ticket_ID = t.Ticket_ID
            WHERE q.Department='$nextDept' AND q.Status='Pending' AND t.Priority=0
            ORDER BY q.Queue_Num ASC LIMIT 1
        ")->fetch_assoc();

        $nextQueueNum = $pendingCount + 3;
        if ($nonPriorityPending) {
            $insertBeforeNum = $nonPriorityPending['Queue_Num'];
            $conn->query("UPDATE queue_info SET Queue_Num = Queue_Num + 1 WHERE Department='$nextDept' AND Queue_Num >= '$insertBeforeNum'");
            $nextQueueNum = $insertBeforeNum;
        }
    } else {
        // Normal Waiting / Serving
        $qRes = $conn->query("SELECT COALESCE(MAX(Queue_Num),0)+1 AS nextQ FROM queue_info WHERE Department='$nextDept'");
        $nextQueueNum = $qRes->fetch_assoc()['nextQ'] ?? 1;
    }

    // Insert or update ticket in next department
    $exists = $conn->query("SELECT ID FROM queue_info WHERE Ticket_ID='$ticketID' AND Department='$nextDept' LIMIT 1")->fetch_assoc();

    if ($exists) {
        $conn->query("UPDATE queue_info SET Status='$status', Queue_Num='$nextQueueNum', Switch=1, Updated_At=NOW() WHERE Ticket_ID='$ticketID' AND Department='$nextDept'");
    } else {
        $conn->query("
            INSERT INTO queue_info (Ticket_ID, Department, Status, Sequence_Num, Queue_Num, Switch, Updated_At)
            VALUES ('$ticketID', '$nextDept', '$status', 1, '$nextQueueNum', 1, NOW())
        ");
    }

    // Copy remarks if exists
    if (!empty($remarks)) {
        $stmtCopy = $conn->prepare("UPDATE queue_info SET Remarks=? WHERE Ticket_ID=? AND Department=?");
        $stmtCopy->bind_param("sss", $remarks, $ticketID, $nextDept);
        $stmtCopy->execute();
    }

    reorderPendingFCFS($conn, $nextDept);
    return true;
}

/* ==========================================================
   ACTION HANDLER
========================================================== */
switch ($action) {
    case 'Page':
        echo json_encode(["message" => "Paged ticket $ticketID"]);
        exit();

    case 'Skip':
        // Mark current ticket as skipped
        $stmt = $conn->prepare("UPDATE queue_info SET Status='Skipped', Remarks=? WHERE ID=?");
        $stmt->bind_param("si", $remarks, $current['ID']);
        $stmt->execute();

        // Promote next waiting → serving
        promoteNext($conn, $dept);
        break;

    case 'Forward':
        // Step 1: Mark current as served
        $stmt = $conn->prepare("UPDATE queue_info SET Status='Served', Remarks=? WHERE ID=?");
        $stmt->bind_param("si", $remarks, $current['ID']);
        $stmt->execute();

        // Step 2: Forward to next department
        if (!forwardTicket($conn, $current, $targetDept, $remarks)) {
            echo json_encode(["manual" => true]);
            exit();
        }

        // Step 3: Promote next waiting → serving
        promoteNext($conn, $dept);
        break;

    case 'Next':
        // Mark current as served
        $stmt = $conn->prepare("UPDATE queue_info SET Status='Served', Remarks=? WHERE ID=?");
        $stmt->bind_param("si", $remarks, $current['ID']);
        $stmt->execute();

        // Promote next waiting → serving
        promoteNext($conn, $dept);
        break;
}

echo json_encode(["success" => true, "message" => "$action completed successfully."]);
?>
