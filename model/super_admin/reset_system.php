<?php
session_start();
require_once("db_connect.php");

if (!isset($_SESSION['Username']) || $_SESSION['UserType'] !== 'super_admin') {
    exit("Unauthorized");
}

// Disable FK checks to avoid constraints
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Delete data only (not drop tables)
$conn->query("TRUNCATE TABLE daily_ticket_counter");
$conn->query("TRUNCATE TABLE tickets");
$conn->query("TRUNCATE TABLE report");
$conn->query("TRUNCATE TABLE page_trigger");

// If you store daily counters in another table, reset here
// Example: $conn->query("UPDATE daily_counter SET counter = 0");

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "System reset successfully!";
