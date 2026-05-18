<?php

// error_reporting(0);
// ini_set('display_errors', 0);
// ob_start();
// header("Content-Type: application/json; charset=utf-8");

// // Safe session start
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// // === Default: local dev ===
// $servername = "localhost";
// $username = "root";
// $password = "";
// $dbname = "qms";

// // === Live fallback: Hostinger ===
// $liveServer = "srv2045.hstgr.io";
// $liveUser = "u772079577_qms";
// $livePass = "Qms@123123";
// $liveDB = "u772079577_qms";

// $conn = @new mysqli($servername, $username, $password, $dbname);

// // If local fails, auto-switch to Hostinger
// if ($conn->connect_error) {
//     $conn = @new mysqli($liveServer, $liveUser, $livePass, $liveDB);
// }

// // Final check
// if ($conn->connect_error) {
//     echo json_encode(["error" => "Database connection failed"]);
//     ob_end_flush();
//     exit;
// }

// $conn->set_charset("utf8mb4");

// =============================================

// $servername = "localhost";
// $username = "root";
// $password = ""; 
// $dbname = "qms";

$servername = "srv2045.hstgr.io";
$username = "u772079577_qms";
$password = "Qms@123123";
$dbname = "u772079577_qms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("❌ Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>