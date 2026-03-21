<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}

require_once '../config/db.php';

// Fetch all employees
$query = "SELECT e.*, d.name as department_name, ds.name as designation_name 
          FROM employee e 
          LEFT JOIN department d ON e.dep_id = d.dep_id 
          LEFT JOIN designation ds ON e.desg_id = ds.desg_id";
$result = $conn->query($query);
$employees = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$total = count($employees);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emplify - Employees</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
        body { background: #f1f5f9; height: 100vh; overflow: hidden; }
        .main-area {
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            margin-left: 0;
        }
        @media (min-width: 1024px) {
            .main-area { margin-left: 256px; }
        }
        .table-row {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }
        .table-row:hover { background: #f8fafc; }
        .table-row:last-child { border-bottom: none; }
        .avatar {
            width: 34px; height: 34px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 13px;
            flex-shrink: 0;
        }
        .search-bar {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 14px;
            color: #334155;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-bar:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }
        .btn-action {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            transition: all 0.15s;
            font-size: 12px;
        }
        .btn-action:hover { transform: scale(1.08); }
        
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 100;
            background: rgba(15,23,42,0.5);
            backdrop-filter: blur(2px);
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 24px;
            width: 100%;
            max-width: 500px;
            margin: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.18);
            animation: modalIn 0.2s ease;
            max-height: 90vh;
            overflow-y: auto;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        /* Info card styles */
        .info-row {
            display: flex;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            width: 110px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .info-value {
            flex: 1;
            font-size: 14px;
            color: #0f172a;
            font-weight: 500;
        }
        
        /* Utility classes */
        .opacity-50 { opacity: 0.5; }
        .pointer-events-none { pointer-events: none; }
    </style>
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-white"
         style="border-bottom: 1px solid #f1f5f9; min-height: 64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing: -0.2px;">Employees</h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage your workforce</p>
        </div>
        <a href="register.php"
           class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-xl text-white transition-all"
           style="background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 4px 14px rgba(99,102,241,0.35);"
           onmouseover="this.style.boxShadow='0 6px 20px rgba(99,102,241,0.5)'"
           onmouseout="this.style.boxShadow='0 4px 14px rgba(99,102,241,0.35)'">
            <i class="fas fa-plus text-xs"></i> Add Employee
        </a>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <!-- Summary strip -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-xl bg-white"
                 style="border: 1px solid #e2e8f0;">
                <span class="w-2 h-2 rounded-full bg-indigo-400 inline-block"></span>
                <span class="text-slate-600"><?php echo $total; ?> employees total</span>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-2xl overflow-hidden"
             style="border: 1px solid #e2e8f0; box-shadow: 0 2px 12px rgba(0,0,0,0.04);">

            <!-- Table toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-5"
                 style="border-bottom: 1px solid #f1f5f9;">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">All Employees</h2>
                    <p class="text-xs text-slate-400 mt-0.5" id="recordCount"><?php echo $total; ?> records found</p>
                </div>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Search employees..."
                           class="search-bar pl-9 w-56" oninput="filterTable()">
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full" id="employeeTable">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">#</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Employee</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Email</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Phone</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Department</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Designation</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3"
                                     style="background: #f1f5f9;">
                                    <i class="fas fa-users text-slate-300 text-xl"></i>
                                </div>
                                <p class="text-slate-400 font-medium">No employees found</p>
                                <p class="text-slate-300 text-sm mt-1">Add your first employee to get started</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php
                        $avatar_colors = [
                            ['bg' => '#e0e7ff20', 'text' => '#6366f1', 'border' => '#6366f130'],
                            ['bg' => '#ede9fe20', 'text' => '#8b5cf6', 'border' => '#8b5cf630'],
                            ['bg' => '#fce7f320', 'text' => '#ec4899', 'border' => '#ec489930'],
                            ['bg' => '#fef9c320', 'text' => '#f59e0b', 'border' => '#f59e0b30'],
                            ['bg' => '#dcfce720', 'text' => '#10b981', 'border' => '#10b98130'],
                            ['bg' => '#dbeafe20', 'text' => '#3b82f6', 'border' => '#3b82f630'],
                            ['bg' => '#ffedd520', 'text' => '#f97316', 'border' => '#f9731630'],
                        ];
                        foreach ($employees as $i => $row):
                            $ac = $avatar_colors[$i % count($avatar_colors)];
                            $initials = strtoupper(substr($row['name'], 0, 1));
                        ?>
                        <tr class="table-row" data-employee-id="<?php echo $row['emp_id']; ?>">
                            <td class="p-4">
                                <span class="text-xs font-semibold text-slate-400"><?php echo $i + 1; ?></span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="avatar"
                                         style="background: <?php echo $ac['bg']; ?>; color: <?php echo $ac['text']; ?>; border: 1.5px solid <?php echo $ac['border']; ?>;">
                                        <?php echo $initials; ?>
                                    </div>
                                    <span class="font-medium text-slate-700 text-sm">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="text-sm text-slate-500">
                                    <?php echo htmlspecialchars($row['email']); ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="text-sm text-slate-500">
                                    <?php echo htmlspecialchars($row['phone']); ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <?php if ($row['department_name']): ?>
                                <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full"
                                      style="background: #e0e7ff; color: #4f46e5;">
                                    <?php echo htmlspecialchars($row['department_name']); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-300 text-sm">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <span class="text-sm text-slate-500">
                                    <?php echo htmlspecialchars($row['designation_name'] ?? '—'); ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <button onclick='viewEmployee(<?php echo json_encode($row); ?>)'
                                            class="btn-action"
                                            style="background: #dbeafe; color: #3b82f6;"
                                            title="View employee details"
                                            onmouseover="this.style.background='#bfdbfe'"
                                            onmouseout="this.style.background='#dbeafe'">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="edit_employee.php?id=<?php echo $row['emp_id']; ?>"
                                       class="btn-action"
                                       style="background: #e0e7ff; color: #4f46e5;"
                                       title="Edit employee"
                                       onmouseover="this.style.background='#c7d2fe'"
                                       onmouseout="this.style.background='#e0e7ff'">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_employee.php?id=<?php echo $row['emp_id']; ?>"
                                       class="btn-action"
                                       style="background: #fee2e2; color: #dc2626;"
                                       title="Delete employee"
                                       onclick="return confirm('Are you sure you want to delete this employee?')"
                                       onmouseover="this.style.background='#fecaca'"
                                       onmouseout="this.style.background='#fee2e2'">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div><!-- /scrollable -->
</div><!-- /main-area -->

<!-- View Employee Modal -->
<div id="viewModal" class="modal-overlay" onclick="handleOverlayClick(event)">
    <div class="modal-box">
        <!-- Modal Header -->
        
        
        <!-- Modal Body -->
        <div class="px-6 py-5">
            <!-- Avatar and Basic Info -->
            <div class="flex items-center gap-4 mb-6 pb-4" style="border-bottom: 1px solid #f1f5f9;">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl font-bold"
                     style="background: #e0e7ff; color: #4f46e5;" id="modalAvatar">
                    ID
                </div>
                <div>
                    <p class="text-sm text-slate-400">Employee ID</p>
                    <p class="text-lg font-bold text-slate-800" id="modalEmpId">EMP001</p>
                </div>
            </div>
            
            <!-- Details Grid -->
            <div class="space-y-1">
                <!-- Personal Information -->
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value" id="modalName">John Doe</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Email Address</div>
                    <div class="info-value" id="modalEmail">john@example.com</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value" id="modalPhone">+1 234 567 8900</div>
                </div>
                
                <!-- Work Information -->
                <div class="info-row">
                    <div class="info-label">Department</div>
                    <div class="info-value">
                        <span class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-full" 
                              style="background: #e0e7ff; color: #4f46e5;" id="modalDepartment">
                            Engineering
                        </span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Designation</div>
                    <div class="info-value" id="modalDesignation">Senior Developer</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value" id="modalDob">1990-01-01</div>
                </div>
                
                <!-- Address Information -->
                <div class="info-row">
                    <div class="info-label">Address</div>
                    <div class="info-value" id="modalAddress">123 Main St, City, State 12345</div>
                </div>
                
                
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="flex gap-3 px-6 py-4" style="border-top: 1px solid #f1f5f9;">
            <button onclick="closeModal()"
                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500 transition-all"
                    style="background: #f1f5f9; border: 1px solid #e2e8f0;"
                    onmouseover="this.style.background='#e2e8f0'"
                    onmouseout="this.style.background='#f1f5f9'">
                Close
            </button>
            <a href="#" id="modalCallBtn"
               class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold text-white transition-all opacity-50 pointer-events-none"
               style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16,185,129,0.35);"
               onmouseover="this.style.boxShadow='0 6px 20px rgba(16,185,129,0.5)'"
               onmouseout="this.style.boxShadow='0 4px 14px rgba(16,185,129,0.35)'">
                <i class="fas fa-phone-alt"></i> Call Employee
            </a>
        </div>
    </div>
</div>

<script>
// Modal functions
function openModal() {
    document.getElementById('viewModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('viewModal').classList.remove('open');
    document.body.style.overflow = '';
}

function handleOverlayClick(event) {
    if (event.target === document.getElementById('viewModal')) {
        closeModal();
    }
}

// View employee details
function viewEmployee(employee) {
    // Update modal content with employee data
    
    document.getElementById('modalEmpId').textContent = 'EMP' + String(employee.emp_id).padStart(3, '0');
    document.getElementById('modalName').textContent = employee.name || '—';
    document.getElementById('modalEmail').textContent = employee.email || '—';
    document.getElementById('modalPhone').textContent = employee.phone || '—';
    document.getElementById('modalDepartment').textContent = employee.department_name || '—';
    document.getElementById('modalDesignation').textContent = employee.designation_name || '—';
    document.getElementById('modalDob').textContent = employee.dob ? new Date(employee.dob).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '—';
    document.getElementById('modalAddress').textContent = employee.address || '—';
    
    // Update call button with phone number
    const callBtn = document.getElementById('modalCallBtn');
    if (employee.phone) {
        callBtn.href = 'tel:' + employee.phone;
        callBtn.classList.remove('opacity-50', 'pointer-events-none');
        callBtn.title = 'Call ' + employee.phone;
    } else {
        callBtn.href = '#';
        callBtn.classList.add('opacity-50', 'pointer-events-none');
        callBtn.title = 'No phone number available';
    }
    
    // Open modal
    openModal();
}

// Table filter function
function filterTable() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#employeeTable tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const visible = text.includes(query);
        row.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
    });
    
    // Update record count
    document.getElementById('recordCount').textContent = visibleCount + ' record' + (visibleCount === 1 ? '' : 's') + ' found';
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});
</script>

</body>
</html>