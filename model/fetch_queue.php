<?php
header("Content-Type: application/json");

// --- DATABASE CONNECTION ---
require_once("db_connect.php");

if ($conn->connect_error) {
  echo json_encode(["error" => "DB Connection failed"]);
  exit;
}

// --- FETCH DATA ORDERED BY Department + Queue_Num (FCFS) ---
$query = "
  SELECT 
      q.Department,
      q.Queue_Num,
      q.Ticket_ID,
      q.Status,
      t.Priority,
      q.Updated_At
  FROM queue_info q
  JOIN tickets t ON q.Ticket_ID = t.Ticket_ID
  WHERE q.Status IS NOT NULL
  ORDER BY q.Department ASC, q.Queue_Num ASC, q.Updated_At ASC
";

$result = $conn->query($query);
if (!$result) {
  echo json_encode(["error" => $conn->error]);
  exit;
}

$data = [];

// --- BUILD DATA PER DEPARTMENT ---
while ($row = $result->fetch_assoc()) {
  $dept = $row['Department'];

  // Initialize if not set
  if (!isset($data[$dept])) {
    $data[$dept] = [
      "code" => $dept,
      "serving" => "---",
      "waiting" => "---",
      "pending_priority" => "---"
    ];
  }

  // ===== SERVING =====
  if ($row['Status'] === 'Serving' || $row['Status'] === 'Done') {
    $data[$dept]['serving'] = $row['Ticket_ID'];
  }

  // ===== WAITING =====
  elseif ($row['Status'] === 'Waiting') {
    // Only fill if no current waiting shown
    if ($data[$dept]['waiting'] === "---") {
      $data[$dept]['waiting'] = $row['Ticket_ID'];
    }
  }

  // ===== PENDING (SHOW PRIORITY FIRST) =====
  elseif ($row['Status'] === 'Pending') {
    // Priority Pending always gets priority display if not yet filled
    if (strtolower($row['Priority']) === 'yes' && $data[$dept]['pending_priority'] === "---") {
      $data[$dept]['pending_priority'] = $row['Ticket_ID'];
    }
  }
}

// --- FORMAT OUTPUT AS SORTED ARRAY BY DEPARTMENT CODE ---
$finalData = [];
foreach ($data as $dept => $info) {
  $finalData[] = [
    "department" => $dept,
    "code" => $info['code'],
    "serving" => $info['serving'],
    "waiting" => $info['waiting'],
    "pending_priority" => $info['pending_priority']
  ];
}

$conn->close();
echo json_encode($finalData);
?>