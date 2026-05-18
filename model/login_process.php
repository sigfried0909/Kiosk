<?php
ob_start();
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once("db_connect.php");

// Handle Login Request
if (isset($_POST['login'])) {
    $inputUsername = trim($_POST['username']);
    $inputPassword = trim($_POST['password']);

    if (empty($inputUsername) || empty($inputPassword)) {
        header("Location: /?error=Please enter username and password");
        exit();
    }

    /* =============================
       CHECK 1: SUPER ADMIN LOGIN
    ============================== */
    $sql = "SELECT * FROM super_admin_info WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $inputUsername);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $super = $result->fetch_assoc();

            // Works with both hashed and plain passwords
            if (password_verify($inputPassword, $super['Password']) || $inputPassword === $super['Password']) {
                $_SESSION['Username'] = $super['Username'];
                $_SESSION['FullName'] = $super['FName'] . " " . $super['LName'];
                $_SESSION['UserType'] = "super_admin";
                header("Location: /superadmin");
                ob_end_flush();
                exit();
            } else {
                header("Location: /?error=Invalid password");
                ob_end_flush();
                exit();
            }
        }
        $stmt->close();
    }

    /* =============================
       CHECK 2: ADMIN LOGIN
    ============================== */
    $sql = "SELECT * FROM admin_info WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $inputUsername);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();

            if (password_verify($inputPassword, $admin['Password']) || $inputPassword === $admin['Password']) {
                $_SESSION['Username'] = $admin['Username'];
                $_SESSION['FullName'] = $admin['FName'] . " " . $admin['Lname'];
                $_SESSION['Department'] = $admin['Department'];
                $_SESSION['UserType'] = "admin";
                header("Location: /admin");
                ob_end_flush();
                exit();
            } else {
                header("Location: /?error=Invalid password");
                ob_end_flush();
                exit();
            }
        }
        $stmt->close();
    }

    /* =============================
       CHECK 3: TELLER LOGIN
    ============================== */
    $sql = "SELECT * FROM teller_info WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $inputUsername);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $teller = $result->fetch_assoc();

            if (password_verify($inputPassword, $teller['Password']) || $inputPassword === $teller['Password']) {
                $_SESSION['Username'] = $teller['Username'];
                $_SESSION['FullName'] = $teller['FName'] . " " . $teller['LName'];
                $_SESSION['Department'] = $teller['Department'];
                $_SESSION['UserType'] = "teller";
                header("Location: /teller");
                ob_end_flush();
                exit();
            } else {
                header("Location: /?error=Invalid password");
                ob_end_flush();
                exit();
            }
        }
        $stmt->close();
    }

    // --- If user not found ---
    header("Location: /?error=User not found");
    ob_end_flush();
    exit();
}

$conn->close();
ob_end_flush();
?>