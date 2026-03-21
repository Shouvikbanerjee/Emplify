<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "emplify";

// Create connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Set connection timeout
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
    
    // Session settings - only set if session hasn't started
    if (session_status() == PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', 3600);
        ini_set('session.cookie_lifetime', 3600);
    }
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>