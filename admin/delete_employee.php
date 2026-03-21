<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}

require_once '../config/db.php';

$emp_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($emp_id > 0) {
    // Start transaction
    $conn->begin_transaction();

    try {
        // First check if employee exists
        $check_query = "SELECT emp_id FROM employee WHERE emp_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $emp_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Employee not found");
        }

        // Delete attendance records first
        $delete_attendance = "DELETE FROM attendance WHERE emp_id = ?";
        $stmt = $conn->prepare($delete_attendance);
        $stmt->bind_param("i", $emp_id);
        $stmt->execute();

        // Delete from login table
        $delete_login = "DELETE FROM login WHERE emp_id = ?";
        $stmt = $conn->prepare($delete_login);
        $stmt->bind_param("i", $emp_id);
        $stmt->execute();

        // Finally delete from employee table
        $delete_employee = "DELETE FROM employee WHERE emp_id = ?";
        $stmt = $conn->prepare($delete_employee);
        $stmt->bind_param("i", $emp_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete employee");
        }

        $conn->commit();
        header('Location: employees.php?message=' . urlencode('Employee deleted successfully'));
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header('Location: employees.php?error=' . urlencode('Delete failed: ' . $e->getMessage()));
        exit();
    }
} else {
    header('Location: employees.php?error=' . urlencode('Invalid employee ID'));
    exit();
}
?>