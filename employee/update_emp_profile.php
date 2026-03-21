<?php
session_start();
require_once("../config/db.php");


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE employee SET  email = ?, address = ?, phone = ? WHERE emp_id = ?");
    $stmt->bind_param("sssi",  $email, $address, $phone, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating profile.";
    }

    header("Location: my_profile.php"); // or wherever the profile page is
    exit;
}
?>
