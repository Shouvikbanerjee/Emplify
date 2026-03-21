<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.html");
    exit();
}

if (isset($_GET['dep_id'])) {
    $dep_id = $_GET['dep_id'];

    $stmt = $conn->prepare("DELETE FROM designation WHERE desg_id = ?");
    $stmt->bind_param("i", $dep_id);

    if ($stmt->execute()) {
        header("Location: designation.php?msg=deleted");
    } else {
        header("Location: designation.php?msg=fail");
    }

    $stmt->close();
    exit();
} else {
    header("Location: designation.php?msg=fail");
    exit();
}
?>