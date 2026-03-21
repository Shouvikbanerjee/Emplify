<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

require_once '../config/db.php';

header('Content-Type: application/json');

$dep_id = isset($_GET['dep_id']) ? (int)$_GET['dep_id'] : 0;

if ($dep_id <= 0) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("
    SELECT 
        e.emp_id,
        e.name,
        e.email,
        e.phone,
        ds.name AS designation
    FROM employee e
    LEFT JOIN designation ds ON e.desg_id = ds.desg_id
    WHERE e.dep_id = ?
    ORDER BY e.name ASC
");

$stmt->bind_param("i", $dep_id);
$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = [
        'emp_id'      => $row['emp_id'],
        'name'        => $row['name'],
        'email'       => $row['email'],
        'phone'       => $row['phone'],
        'designation' => $row['designation'] ?? 'No Designation',
    ];
}

echo json_encode($employees);