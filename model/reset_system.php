<?php
header("Content-Type: application/json");
require_once("db_connect.php");

try {
    // Start Transaction
    $conn->begin_transaction();

    // Delete tables
    $conn->query("DELETE FROM daily_ticket_counter");
    $conn->query("DELETE FROM page_trigger");
    $conn->query("DELETE FROM report");
    $conn->query("DELETE FROM tickets");
    $conn->query("DELETE FROM queue_info");

    // Note:
    // queue_info is automatically cleared because of CASCADE on tickets

    $conn->commit();

    echo json_encode("System successfully reset!");

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode("Reset failed: " . $e->getMessage());
}

$conn->close();
?>
