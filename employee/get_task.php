<?php
require_once '../config/db.php'; 

if (isset($_GET['task_id'])) {
    $task_id = $conn->real_escape_string($_GET['task_id']);
    $sql = "SELECT * FROM task_assigned WHERE task_id = $task_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $task = $result->fetch_assoc();
        echo json_encode($task);
    } else {
        echo json_encode(['error' => 'Task not found']);
    }
} else {
    echo json_encode(['error' => 'No task ID provided']);
}