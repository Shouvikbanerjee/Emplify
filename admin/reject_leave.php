<?php

    session_start();
 require_once '../config/db.php'; 

    // Check if user is logged in and is admin
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header('Location: login.html');
        exit();
    }

   
    
    $value='rejected';

    $res=mysqli_query($conn,"update leave_management set status='$value'where leave_id='".$_GET['leave_id']."'");

    header("location:leave_approval.php");
    exit;

?>