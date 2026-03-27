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

// Step 1: Check active session
$active_stmt = $conn->prepare("SELECT emp_id FROM attendance WHERE emp_id = ? AND date = ? AND check_out IS NULL");
$active_stmt->bind_param("is", $employee_id, $today_date);
$active_stmt->execute();

if ($active_stmt->get_result()->num_rows > 0) {
    header("Location: employee_dashboard.php?modal=error&msg=" . urlencode("Already clocked in!") . "&type=clockin");
    exit();
}

// Step 2: Count today's entries
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE emp_id = ? AND date = ?");
$count_stmt->bind_param("is", $employee_id, $today_date);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$row = $count_result->fetch_assoc();

if ($row['total'] >= 3) {
    header("Location: employee_dashboard.php?modal=error&msg=" . urlencode("Max 3 clock-in reached!") . "&type=clockin");
    exit();
}

// Step 3: Insert
$clock_in_time = date("Y-m-d H:i:s");

$stmt = $conn->prepare("INSERT INTO attendance (emp_id, date, check_in) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $employee_id, $today_date, $clock_in_time);

if ($stmt->execute()) {
    header("Location: employee_dashboard.php?modal=success&msg=" . urlencode("Clocked in successfully!") . "&type=clockin");
} else {
    header("Location: employee_dashboard.php?modal=error&msg=" . urlencode("Failed!") . "&type=clockin");
}
exit();
?>