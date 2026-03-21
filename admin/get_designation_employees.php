<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

require_once '../config/db.php';

header('Content-Type: application/json');

$desg_id = isset($_GET['desg_id']) ? (int)$_GET['desg_id'] : 0;

if ($desg_id <= 0) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("
    SELECT 
        e.emp_id,
        e.name,
        e.email,
        e.phone,
        d.name AS department
    FROM employee e
    LEFT JOIN department d ON e.dep_id = d.dep_id
    WHERE e.desg_id = ?
    ORDER BY e.name ASC
");

$stmt->bind_param("i", $desg_id);
$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = [
        'emp_id'     => $row['emp_id'],
        'name'       => $row['name'],
        'email'      => $row['email'],
        'phone'      => $row['phone'],
        'department' => $row['department'] ?? 'No Department',
    ];
}

echo json_encode($employees);