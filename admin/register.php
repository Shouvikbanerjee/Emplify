<?php
require_once '../config/db.php';

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
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert employee data
        $emp_query = "INSERT INTO employee (name, email, phone, gender, dob, address, dep_id, desg_id) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($emp_query);
        $stmt->bind_param("ssssssii", $name, $email, $phone, $gender, $dob, $address, $dep_id, $desg_id);
        $stmt->execute();
        $emp_id = $conn->insert_id;

        // Insert login credentials with emp_id reference
        $login_query = "INSERT INTO login (username, password, emp_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($login_query);
        $stmt->bind_param("ssi", $username, $password, $emp_id);
        $stmt->execute();

        $conn->commit();
        $success_message = "Registration successful!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emplify - Employee Registration</title>
    <style>
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

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
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

        textarea {
            height: 100px;
            resize: vertical;
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

        .submit-btn:hover {
            background: #0083b0;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        </style>
    <style>
        .back-btn-container {
            background: #f8f9fa;
            padding: 5px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        }

        .back-btn:hover {
            color: white;
            text-decoration: none;
        }

        .back-btn i {
            font-size: 14px;
        }

        h2 {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>Employee Registration</h2>
            <div class="back-btn-container">
                <a href="employees.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob">
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
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
                                echo "<option value='".$dep['dep_id']."'>".htmlspecialchars($dep['name'])."</option>";
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
                                echo "<option value='".$desg['desg_id']."'>".htmlspecialchars($desg['name'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
            </div>

            <button type="submit" class="submit-btn">Register Employee</button>
        </form>
    </div>

    <script>
    // Add form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const username = document.getElementById('username').value;
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long');
        }
        
        if (username.length < 4) {
            e.preventDefault();
            alert('Username must be at least 4 characters long');
        }
    });
    </script>
</body>
</html>