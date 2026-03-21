<?php
session_start();
require_once 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // First check for admin
    if ($username === 'bikas123' && $password === 'bikas@123') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = true;
        
        header("Location: http://".$_SERVER['HTTP_HOST']."/emplify/admin/dashboard.php");
        exit();
    }
    
    // Check employee credentials from login table
    $stmt = $conn->prepare("SELECT * FROM login WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['emp_id'] = $user['emp_id'];
            
            // Get employee name from employee table
            $emp_stmt = $conn->prepare("SELECT name FROM employee WHERE emp_id = ?");
            $emp_stmt->bind_param("s", $user['emp_id']);
            $emp_stmt->execute();
            $emp_result = $emp_stmt->get_result();
            if ($emp_result->num_rows === 1) {
                $emp_data = $emp_result->fetch_assoc();
               
                $_SESSION['name'] = $emp_data['name'];
            }
            
            $_SESSION['is_admin'] = false;
            
            header("Location: http://".$_SERVER['HTTP_HOST']."/emplify/employee/employee_dashboard.php");
            exit();
        }
    }
    
    // If credentials are invalid
    header("Location: http://".$_SERVER['HTTP_HOST']."/emplify/login.html?error=1");
    exit();
}
?>