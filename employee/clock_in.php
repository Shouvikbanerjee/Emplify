<?php
session_start();
require_once '../config/db.php';  // Make sure this path is correct

if (!isset($_SESSION['emp_id'])) {
    header('Location: login.html');
    exit();
}

$employee_id = $_SESSION['emp_id'];
date_default_timezone_set('Asia/Kolkata');

$today_date = date("Y-m-d");
$clock_in_time = date("Y-m-d H:i:s");

// Count today's clock-ins (using DATE() to extract date part from check_in)
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE emp_id = ? AND DATE(check_in) = ?");
$count_stmt->bind_param("is", $employee_id, $today_date);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$row = $count_result->fetch_assoc();
$clock_in_count = $row['total'];

if ($clock_in_count >= 3) {
    header("Location: employee_dashboard.php?modal=error&msg=" . urlencode("You have already clocked in 3 times today.") . "&type=clockin");
    exit();
} else {
    $stmt = $conn->prepare("INSERT INTO attendance (emp_id, date, check_in) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $employee_id, $today_date, $clock_in_time);
    
    if ($stmt->execute()) {
        header("Location: employee_dashboard.php?modal=success&msg=" . urlencode("Clocked in at: $clock_in_time") . "&type=clockin");
    } else {
        header("Location: employee_dashboard.php?modal=error&msg=" . urlencode("Failed to clock in. Please try again.") . "&type=clockin");
    }
    exit();
}
?>