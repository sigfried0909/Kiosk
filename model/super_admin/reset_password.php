<?php
header("Content-Type: application/json");
require_once("../db_connect.php");

$dept = $_POST['department'];
$username = $_POST['username'];
$new_pass = $_POST['new_password'];

$sql = "UPDATE teller_info SET Password='$new_pass' WHERE Username='$username' AND Department='$dept'";
if ($conn->query($sql) === TRUE) {
    echo "Password reset successfully!";
} else {
    echo "Error updating password: " . $conn->error;
}
$conn->close();
