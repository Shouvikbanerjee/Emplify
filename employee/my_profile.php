<?php
session_start();
require_once("../config/db.php");

// Check if employee is logged in
if (!isset($_SESSION['emp_id'])) {
    header('Location: login.html');
    exit();
}

$employee_id = $_SESSION['emp_id'];

// Fetch employee details
$stmt = $conn->prepare("SELECT * FROM employee WHERE emp_id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Emplify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'employee_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-64 p-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Profile</h1>
            <p class="text-gray-600 mt-1">Manage your personal information</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <!-- Profile Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-400 px-6 py-8 text-center">
                        <div class="relative inline-block">
                            <div class="w-28 h-28 rounded-full bg-white/20 backdrop-blur-sm mx-auto flex items-center justify-center border-4 border-white">
                                <span class="text-5xl text-white font-semibold">
                                    <?= strtoupper(substr($employee['name'], 0, 1)) ?>
                                </span>
                            </div>
                        </div>
                        <h2 class="text-2xl font-bold text-white mt-4"><?= htmlspecialchars($employee['name']) ?></h2>
                        <p class="text-blue-100 mt-1">Employee ID: #<?= str_pad($employee['emp_id'], 4, '0', STR_PAD_LEFT) ?></p>
                    </div>
                    
                    <!-- Profile Stats -->
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-envelope w-6 text-blue-500"></i>
                            <span class="ml-3"><?= htmlspecialchars($employee['email']) ?></span>
                        </div>
                        <div class="flex items-center text-gray-600 mt-3">
                            <i class="fas fa-phone w-6 text-blue-500"></i>
                            <span class="ml-3"><?= htmlspecialchars($employee['phone']) ?></span>
                        </div>
                    </div>
                    
                    <!-- Account Status -->
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Account Status</span>
                            <span class="px-3 py-1 bg-green-100 text-green-600 rounded-full text-sm font-medium">Active</span>
                        </div>
                        <div class="flex items-center justify-between mt-3">
                            <span class="text-gray-600">Member Since</span>
                            <span class="text-gray-800 font-medium">2024</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Information & Edit Form -->
            <div class="lg:col-span-2">
                <!-- Personal Information Card -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-user-circle mr-2 text-blue-500"></i>
                            Personal Information
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Full Name</label>
                                <p class="text-gray-800 bg-gray-50 px-4 py-2 rounded-lg"><?= htmlspecialchars($employee['name']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Date of Birth</label>
                                <p class="text-gray-800 bg-gray-50 px-4 py-2 rounded-lg">
                                    <?= date('d F, Y', strtotime($employee['dob'])) ?>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Gender</label>
                                <p class="text-gray-800 bg-gray-50 px-4 py-2 rounded-lg">
                                    <?= htmlspecialchars($employee['gender']) ?>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Email Address</label>
                                <p class="text-gray-800 bg-gray-50 px-4 py-2 rounded-lg"><?= htmlspecialchars($employee['email']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Phone Number</label>
                                <p class="text-gray-800 bg-gray-50 px-4 py-2 rounded-lg"><?= htmlspecialchars($employee['phone']) ?></p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-500 mb-1">Address</label>
                                <p class="text-gray-800 bg-gray-50 px-4 py-2 rounded-lg"><?= htmlspecialchars($employee['address']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Card -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-edit mr-2 text-blue-500"></i>
                            Edit Profile
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Update your contact information</p>
                    </div>
                    <div class="p-6">
                        <form method="post" action="update_emp_profile.php" class="space-y-5">
                            <input type="hidden" name="id" value="<?= $employee['emp_id'] ?>">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-2 text-blue-500"></i>Email Address
                                </label>
                                <input type="email" name="email" 
                                       value="<?= htmlspecialchars($employee['email']) ?>" 
                                       required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                       placeholder="Enter your email">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-phone mr-2 text-blue-500"></i>Phone Number
                                </label>
                                <input type="text" name="phone" 
                                       value="<?= htmlspecialchars($employee['phone']) ?>" 
                                       required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                       placeholder="Enter your phone number">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>Address
                                </label>
                                <textarea name="address" 
                                          required
                                          rows="3"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                          placeholder="Enter your address"><?= htmlspecialchars($employee['address']) ?></textarea>
                            </div>

                            <div class="flex items-center space-x-4 pt-4">
                                <button type="submit" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-200 flex items-center">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Changes
                                </button>
                                <button type="reset" 
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-lg transition duration-200 flex items-center">
                                    <i class="fas fa-undo mr-2"></i>
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Alert Messages (Optional - Add if you want) -->
                <?php if(isset($_SESSION['success_message'])): ?>
                    <div class="mt-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <p><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['error_message'])): ?>
                    <div class="mt-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <p><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        /* Custom scrollbar for better UX */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Smooth transitions */
        * {
            transition: all 0.2s ease-in-out;
        }
    </style>
</body>
</html>