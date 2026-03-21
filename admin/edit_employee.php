<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}
require_once '../config/db.php';

$emp_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $dep_id = $_POST['dep_id'];
    $desg_id = $_POST['desg_id'];

    // Update employee data
    $update_query = "UPDATE employee SET 
                    name = ?, 
                    email = ?, 
                    phone = ?, 
                    gender = ?, 
                    dob = ?, 
                    address = ?, 
                    dep_id = ?, 
                    desg_id = ? 
                    WHERE emp_id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssssiis", $name, $email, $phone, $gender, $dob, $address, $dep_id, $desg_id, $emp_id);
    
    if ($stmt->execute()) {
        header('Location: employees.php?message=Employee updated successfully');
        exit();
    } else {
        $error_message = "Update failed: " . $conn->error;
    }
}

// Fetch employee data
$query = "SELECT * FROM employee WHERE emp_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    header('Location: employees.php?error=Employee not found');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - Emplify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Copy the same styles from register.php */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .submit-btn {
            background: #00b4db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
        }

        .back-btn {
            background: #666;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #555;
            color: white;
        }

        .back-btn i {
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Edit Employee</h2>
            <a href="employees.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        
        <form action="" method="POST">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($employee['dob']); ?>">
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo $employee['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $employee['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $employee['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="dep_id">Department *</label>
                    <select id="dep_id" name="dep_id" required>
                        <option value="">Select Department</option>
                        <?php
                        $dep_query = "SELECT dep_id, name FROM department";
                        $dep_result = $conn->query($dep_query);
                        if ($dep_result) {
                            while($dep = $dep_result->fetch_assoc()) {
                                $selected = $dep['dep_id'] == $employee['dep_id'] ? 'selected' : '';
                                echo "<option value='".$dep['dep_id']."' ".$selected.">".htmlspecialchars($dep['name'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="desg_id">Designation *</label>
                    <select id="desg_id" name="desg_id" required>
                        <option value="">Select Designation</option>
                        <?php
                        $desg_query = "SELECT desg_id, name FROM designation";
                        $desg_result = $conn->query($desg_query);
                        if ($desg_result) {
                            while($desg = $desg_result->fetch_assoc()) {
                                $selected = $desg['desg_id'] == $employee['desg_id'] ? 'selected' : '';
                                echo "<option value='".$desg['desg_id']."' ".$selected.">".htmlspecialchars($desg['name'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address"><?php echo htmlspecialchars($employee['address']); ?></textarea>
            </div>

            <button type="submit" class="submit-btn">Update Employee</button>
        </form>
    </div>
</body>
</html>