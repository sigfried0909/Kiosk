<?php
header("Content-Type: application/json");
require_once("../db_connect.php");

// ===== TOTAL SERVED =====
$totalServed = $conn->query("
    SELECT COUNT(*) AS c FROM report 
    WHERE Status='Served' OR Status='Done'
")->fetch_assoc()['c'];

// ===== TOTAL SKIPPED =====
$totalSkipped = $conn->query("
    SELECT COUNT(*) AS c FROM report 
    WHERE Status='Skipped'
")->fetch_assoc()['c'];

// ===== TOTAL QUEUE (SERVED + SKIPPED) =====
$totalQueue = $totalServed + $totalSkipped;

// ===== GET DISTINCT DEPARTMENTS (Type column) =====
$deptCounts = [];
$resDept = $conn->query("SELECT DISTINCT Type FROM report WHERE Type IS NOT NULL");
while ($row = $resDept->fetch_assoc()) {
    $dept = $row['Type'];
    $count = $conn->query("
        SELECT COUNT(*) AS c FROM report 
        WHERE Type='$dept' AND (Status='Served' OR Status='Done' OR Status='Skipped')
    ")->fetch_assoc()['c'];
    $deptCounts[$dept] = (int)$count;
}

// ===== MONTHLY SERVED & SKIPPED =====
$monthlyServed = array_fill(0, 12, 0);
$monthlySkipped = array_fill(0, 12, 0);

$res = $conn->query("
    SELECT MONTH(Date) AS m,
        SUM(CASE WHEN Status='Served' OR Status='Done' THEN 1 ELSE 0 END) AS served,
        SUM(CASE WHEN Status='Skipped' THEN 1 ELSE 0 END) AS skipped
    FROM report
    WHERE Status IS NOT NULL
    GROUP BY MONTH(Date)
");

while ($row = $res->fetch_assoc()) {
    $monthIndex = $row['m'] - 1; // Jan = 0
    $monthlyServed[$monthIndex] = (int)$row['served'];
    $monthlySkipped[$monthIndex] = (int)$row['skipped'];
}

// ===== PACKAGE DATA =====
$data = [
    "total" => $totalQueue,
    "served" => $totalServed,
    "skipped" => $totalSkipped,
    "departments" => $deptCounts,
    "monthly" => [
        "served" => $monthlyServed,
        "skipped" => $monthlySkipped
    ]
];

echo json_encode($data);
$conn->close();
?>
