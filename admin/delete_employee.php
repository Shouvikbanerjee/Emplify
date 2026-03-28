<?php
session_start();
require_once '../config/db.php';

if (!isset($_GET['id'])) {
    header('Location: employees.php');
    exit();
}

$id = (int)$_GET['id'];

try {
    // delete login first (if exists)
    $conn->query("DELETE FROM login WHERE emp_id = $id");

    // delete employee
    $conn->query("DELETE FROM employee WHERE emp_id = $id");

    $_SESSION['flash'] = [
        'type' => 'success',
        'msg' => 'Employee deleted successfully!'
    ];

} catch (Exception $e) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'msg' => 'Delete failed!'
    ];
}

header('Location: employees.php');
exit();