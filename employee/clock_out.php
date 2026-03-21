<?php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['emp_id'])) {
    http_response_code(401);
    exit("Not logged in");
}

$employee_id = $_SESSION['emp_id'];
date_default_timezone_set('Asia/Kolkata');
$current_time = date("Y-m-d H:i:s");
$today_date = date("Y-m-d");

$stmt = $conn->prepare("SELECT att_id, check_in FROM attendance WHERE emp_id = ? AND check_out IS NULL ORDER BY check_in DESC LIMIT 1");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $attendance_id = $row['att_id'];
    $check_in_time = $row['check_in'];

    // Calculate how long the employee has been clocked in
    $check_in_timestamp = strtotime($check_in_time);
    $current_timestamp = strtotime($current_time);
    $hours_diff = ($current_timestamp - $check_in_timestamp) / 3600;

    if ($hours_diff >= 8) {
        // Auto clock-out after exactly 8 hours
        $clock_out_time = date("Y-m-d H:i:s", strtotime($check_in_time . ' +8 hours'));
        $auto_msg = "Auto clocked out after 8 hours at: $clock_out_time";
    } else {
        // Regular clock-out
        $clock_out_time = $current_time;
        $auto_msg = "Clocked out at: $clock_out_time";
    }

    $update = $conn->prepare("UPDATE attendance SET check_out = ? WHERE att_id = ?");
    $update->bind_param("si", $clock_out_time, $attendance_id);
    $update->execute();

    $msg = urlencode($auto_msg);
    header("Location: employee_dashboard.php?modal=success&msg=$msg&type=clockout");
    exit();

} else {
    $msg = urlencode("No active clock-in record found.");
    header("Location: employee_dashboard.php?modal=error&msg=$msg&type=clockout");
    exit();
}
