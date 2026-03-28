<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['emp_id'])) {
    header('Location: login.html');
    exit();
}

$employee_id = $_SESSION['emp_id'];
$stmt = $conn->prepare("SELECT * FROM employee WHERE emp_id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();


// ================= IMAGE UPLOAD FIX =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['employee_image'])) {

    if (!empty($_FILES['employee_image']['name'])) {

        $targetDir = "../uploads/";

        // Create folder if not exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["employee_image"]["name"]);
        $targetFile = $targetDir . $fileName;

        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowedTypes)) {

            if (move_uploaded_file($_FILES["employee_image"]["tmp_name"], $targetFile)) {

                // Delete old image
                if (!empty($employee['image']) && file_exists($targetDir . $employee['image'])) {
                    unlink($targetDir . $employee['image']);
                }

                // Update DB
                $stmt = $conn->prepare("UPDATE employee SET image=? WHERE emp_id=?");
                $stmt->bind_param("si", $fileName, $employee_id);
                $stmt->execute();

                // Reload page
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "Upload failed!";
            }
        } else {
            echo "Invalid file type!";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Emplify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }

        body { background: #f1f5f9; }

        /* ── Layout ── */
        .main-area {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin-left: 0;
        }
        @media (min-width: 1024px) {
            .main-area { margin-left: 256px; }
        }

        /* ── Profile hero ── */
        .profile-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            position: relative;
            overflow: hidden;
        }
        .profile-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 240px; height: 240px;
            border-radius: 50%;
            background: rgba(99,102,241,0.15);
        }
        .profile-hero::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -20px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(139,92,246,0.1);
        }

        /* ── Info fields ── */
        .info-field {
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 12px 16px;
        }

        /* ── Form inputs ── */
        .form-input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            color: #334155;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: 'DM Sans', sans-serif;
            background: #fff;
        }
        .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }

        /* ── Stat pill ── */
        .stat-pill {
            background: #fff;
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 12px;
            padding: 12px 16px;
            text-align: center;
        }

        /* ── Card ── */
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>

<?php include 'employee_sidebar.php'; ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-4 sm:px-6 py-4 bg-white"
         style="border-bottom: 1px solid #f1f5f9; min-height: 64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing:-0.2px;">My Profile</h1>
            <p class="text-xs text-slate-400 mt-0.5">View and update your personal information</p>
        </div>
        <div class="flex items-center gap-2 text-sm px-3 sm:px-4 py-2 rounded-xl"
             style="border:1px solid <?= $employee['status'] ? '#bbf7d0' : '#fecaca' ?>;background:<?= $employee['status'] ? '#f0fdf4' : '#fff1f2' ?>;color:<?= $employee['status'] ? '#16a34a' : '#dc2626' ?>;">
            <span class="w-2 h-2 rounded-full inline-block <?= $employee['status'] ? 'animate-pulse' : '' ?>"
                  style="background:<?= $employee['status'] ? '#34d399' : '#f87171' ?>"></span>
            <span class="hidden sm:inline font-semibold"><?= $employee['status'] ? 'Active' : 'Inactive' ?></span>
        </div>
    </div>

    <!-- Page content (scrollable) -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5">

        <!-- Flash messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
             style="background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;">
            <i class="fas fa-check-circle flex-shrink-0"></i>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
             style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;">
            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>

        <!-- Main grid: profile card + details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- ── Left: Profile Card ── -->
            <div class="lg:col-span-1 flex flex-col gap-5">

                <!-- Profile Hero Card -->
                <div class="card">
                    <div class="profile-hero px-6 py-10 text-center relative z-10">
                        <!-- Avatar -->
                        <form method="POST" enctype="multipart/form-data">
    
    <!-- Hidden File Input -->
    <input type="file" name="employee_image" id="imageInput" class="hidden" accept="image/*">

    <!-- Clickable Avatar -->
    <label for="imageInput" class="cursor-pointer block w-fit mx-auto">

        <?php if (!empty($employee['image']) && file_exists('../uploads/' . $employee['image'])): ?>
            
            <img id="previewImage"
                 src="../uploads/<?= htmlspecialchars($employee['image']) ?>" 
                 class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl object-cover mb-4 border-2 border-white/20">

        <?php else: ?>

            <div id="previewImage"
                 class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl flex items-center justify-center text-4xl sm:text-5xl font-bold text-white mb-4"
                 style="background: rgba(255,255,255,0.12); border: 2px solid rgba(255,255,255,0.2); backdrop-filter: blur(8px);">
                <?= strtoupper(substr($employee['name'], 0, 1)) ?>
            </div>

        <?php endif; ?>

        
        

    </label>

</form>
                        <h2 class="text-xl sm:text-2xl font-bold text-white"><?= htmlspecialchars($employee['name']) ?></h2>
                        
                        <!-- Quick stats row -->
                        <div class="flex gap-3 mt-5 justify-center flex-wrap">
                            <div class="stat-pill min-w-[80px]">
                                <p class="text-xs font-semibold" style="color:#a5b4fc;">Status</p>
                                <p class="text-xs font-bold mt-0.5" style="color:<?= $employee['status'] ? '#16a34a' : '#dc2626' ?>">
                                    <?= $employee['status'] ? 'Active' : 'Inactive' ?>
                                </p>
                            </div>
                           
                        </div>
                    </div>

                    <!-- Contact quick-view -->
                    <div class="p-5 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                 style="background:#e0e7ff;">
                                <i class="fas fa-envelope text-xs" style="color:#6366f1;"></i>
                            </div>
                            <span class="text-sm text-slate-600 truncate"><?= htmlspecialchars($employee['email']) ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                 style="background:#dcfce7;">
                                <i class="fas fa-phone text-xs" style="color:#10b981;"></i>
                            </div>
                            <span class="text-sm text-slate-600"><?= htmlspecialchars($employee['phone']) ?></span>
                        </div>
                        <?php if (!empty($employee['address'])): ?>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                 style="background:#fef9c3;">
                                <i class="fas fa-map-marker-alt text-xs" style="color:#d97706;"></i>
                            </div>
                            <span class="text-sm text-slate-600 leading-relaxed"><?= htmlspecialchars($employee['address']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Account info card -->
                <div class="card p-5 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Account Details</p>
                    <div class="flex items-center justify-between py-2" style="border-bottom:1px solid #f8fafc;">
                        <span class="text-sm text-slate-500">Employee ID</span>
                        <span class="text-sm font-semibold text-slate-700">#<?= str_pad($employee['emp_id'], 4, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="flex items-center justify-between py-2" style="border-bottom:1px solid #f8fafc;">
                        <span class="text-sm text-slate-500">Account Status</span>
                        <?php if($employee['status']): ?>
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                              style="background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>Active
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                              style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>Inactive
                        </span>
                        <?php endif; ?>
                    </div>
                    
                </div>

            </div>

            <!-- ── Right: Info + Edit ── -->
            <div class="lg:col-span-2 flex flex-col gap-5">

                <!-- Personal Information -->
                <div class="card">
                    <div class="card-header">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                             style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                            <i class="fas fa-user text-white text-xs"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm">Personal Information</h3>
                            <p class="text-xs text-slate-400">Your recorded details</p>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Full Name</label>
                                <div class="info-field">
                                    <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($employee['name']) ?></p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Date of Birth</label>
                                <div class="info-field">
                                    <p class="text-sm font-medium text-slate-700">
                                        <?= !empty($employee['dob']) ? date('d F, Y', strtotime($employee['dob'])) : '—' ?>
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Gender</label>
                                <div class="info-field">
                                    <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($employee['gender'] ?? '—') ?></p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Email Address</label>
                                <div class="info-field">
                                    <p class="text-sm font-medium text-slate-700 truncate"><?= htmlspecialchars($employee['email']) ?></p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Phone Number</label>
                                <div class="info-field">
                                    <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($employee['phone']) ?></p>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Address</label>
                                <div class="info-field">
                                    <p class="text-sm font-medium text-slate-700 leading-relaxed"><?= htmlspecialchars($employee['address'] ?? '—') ?></p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Edit Profile -->
                <div class="card">
                    <div class="card-header">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                             style="background:linear-gradient(135deg,#10b981,#059669);">
                            <i class="fas fa-edit text-white text-xs"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm">Edit Profile</h3>
                            <p class="text-xs text-slate-400">Update your contact information</p>
                        </div>
                    </div>
                    <div class="p-5">
                        <form method="post" action="update_emp_profile.php" class="space-y-4">
                            <input type="hidden" name="id" value="<?= $employee['emp_id'] ?>">

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                    <i class="fas fa-envelope text-indigo-400 mr-1"></i>Email Address
                                </label>
                                <input type="email" name="email"
                                       value="<?= htmlspecialchars($employee['email']) ?>"
                                       required class="form-input"
                                       placeholder="your@email.com">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                    <i class="fas fa-phone text-emerald-400 mr-1"></i>Phone Number
                                </label>
                                <input type="text" name="phone"
                                       value="<?= htmlspecialchars($employee['phone']) ?>"
                                       required class="form-input"
                                       placeholder="+91 XXXXX XXXXX">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                                    <i class="fas fa-map-marker-alt text-amber-400 mr-1"></i>Address
                                </label>
                                <textarea name="address" rows="3" required class="form-input"
                                          style="resize:none;"
                                          placeholder="Enter your full address..."><?= htmlspecialchars($employee['address'] ?? '') ?></textarea>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                <button type="submit"
                                        class="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-xl text-white transition-all"
                                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 4px 14px rgba(99,102,241,0.35);"
                                        onmouseover="this.style.boxShadow='0 6px 20px rgba(99,102,241,0.5)'"
                                        onmouseout="this.style.boxShadow='0 4px 14px rgba(99,102,241,0.35)'">
                                    <i class="fas fa-save text-xs"></i> Save Changes
                                </button>
                                <button type="reset"
                                        class="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-xl text-slate-600 transition-all"
                                        style="background:#f1f5f9;border:1px solid #e2e8f0;"
                                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                    <i class="fas fa-undo text-xs"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div><!-- /scrollable -->
</div><!-- /main-area -->

<script>
document.getElementById('imageInput').addEventListener('change', function() {
    if (this.files.length > 0) {
        this.form.submit(); // submit form automatically
    }
});
</script>
</body>

</html>