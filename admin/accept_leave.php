<?php
session_start();
require_once '../config/db.php'; 

// Check admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}

if (!isset($_GET['leave_id'])) {
    header("location:leave_approval.php");
    exit();
}

$leave_id = intval($_GET['leave_id']);

// 🔹 Get leave details
$stmt = $conn->prepare("SELECT emp_id, start_date, end_date, status FROM leave_management WHERE leave_id=?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$result = $stmt->get_result();
$leave = $result->fetch_assoc();

// ❗ Prevent double deduction
if ($leave['status'] === 'approved') {
    header("location:leave_approval.php");
    exit();
}

// 🔹 Calculate days
$start = new DateTime($leave['start_date']);
$end   = new DateTime($leave['end_date']);
$end->modify('+1 day');
$interval = $start->diff($end);
$days = $interval->days;

// 🔹 Deduct leave balance
$stmt2 = $conn->prepare("UPDATE employee SET leave_balance = leave_balance - ? WHERE emp_id=?");
$stmt2->bind_param("ii", $days, $leave['emp_id']);
$stmt2->execute();

// 🔹 Approve leave
$stmt3 = $conn->prepare("UPDATE leave_management SET status='approved' WHERE leave_id=?");
$stmt3->bind_param("i", $leave_id);
$stmt3->execute();

header("location:leave_approval.php");
exit();
?>