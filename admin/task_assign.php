<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['add_task'])) {
        $emp_id        = $conn->real_escape_string($_POST['emp_id']);
        $title         = $conn->real_escape_string($_POST['title']);
        $description   = $conn->real_escape_string($_POST['description']);
        $assign_date   = date('Y-m-d');
        $due_date      = $conn->real_escape_string($_POST['due_date']);
        $is_site_visit = isset($_POST['is_site_visit']) ? 1 : 0;
        $location      = $is_site_visit ? $conn->real_escape_string($_POST['location']) : '';
        $sql = "INSERT INTO task_assigned (emp_id, title, description, assign_date, due_date, status, is_site_visit, location)
                VALUES ('$emp_id','$title','$description','$assign_date','$due_date','Assigned',$is_site_visit,'$location')";
        if ($conn->query($sql)) {
            $_SESSION['success_message'] = "Task assigned successfully!";
        } else {
            $_SESSION['error_message'] = "Error assigning task: " . $conn->error;
        }
        header("Location: task_assign.php"); exit();
    }

    if (isset($_POST['edit_task'])) {
        $task_id     = $conn->real_escape_string($_POST['task_id']);
        $title       = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $due_date    = $conn->real_escape_string($_POST['due_date']);
        $status      = $conn->real_escape_string($_POST['status']);
        $conn->query("UPDATE task_assigned SET title='$title', description='$description', due_date='$due_date', status='$status' WHERE task_id=$task_id");
        header("Location: task_assign.php"); exit();
    }

    if (isset($_POST['delete_task'])) {
        $task_id = $conn->real_escape_string($_POST['task_id']);
        $conn->query("DELETE FROM task_assigned WHERE task_id=$task_id");
        header("Location: task_assign.php"); exit();
    }
}

// GET: fetch single task for edit modal
if (isset($_GET['task_id']) && $_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['json'])) {
    header('Content-Type: application/json');
    $task_id = intval($_GET['task_id']);
    $r = $conn->query("SELECT title, description, due_date, status FROM task_assigned WHERE task_id=$task_id");
    echo $r && $r->num_rows > 0 ? json_encode($r->fetch_assoc()) : json_encode(['error' => 'Not found']);
    exit();
}

$tasks     = $conn->query("SELECT t.*, e.name as employee_name FROM task_assigned t JOIN employee e ON t.emp_id = e.emp_id ORDER BY t.due_date DESC")->fetch_all(MYSQLI_ASSOC);
$employees = $conn->query("SELECT emp_id, name FROM employee ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$total      = count($tasks);
$assigned   = count(array_filter($tasks, fn($t) => strtolower($t['status']) === 'assigned'));
$inprogress = count(array_filter($tasks, fn($t) => strtolower($t['status']) === 'in progress'));
$completed  = count(array_filter($tasks, fn($t) => strtolower($t['status']) === 'completed'));
$sitevisits = count(array_filter($tasks, fn($t) => $t['is_site_visit'] == 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - Emplify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
        body { background: #f1f5f9; height: 100vh; overflow: hidden; }
        .main-area {
            display: flex; flex-direction: column;
            height: 100vh; overflow: hidden; margin-left: 0;
        }
        @media (min-width: 1024px) { .main-area { margin-left: 256px; } }

        /* Stat cards */
        .stat-card {
            background: #fff;
            border: 1px solid rgba(226,232,240,0.8);
            border-radius: 16px; padding: 18px 22px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.08); transform: translateY(-2px); }

        /* Task cards */
        .task-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
            display: flex; flex-direction: column;
        }
        .task-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,0.09); transform: translateY(-2px); }

        /* Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 100;
            background: rgba(15,23,42,0.5);
            backdrop-filter: blur(2px);
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff; border-radius: 20px;
            width: 100%; max-width: 480px; margin: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.18);
            animation: modalIn 0.2s ease;
            max-height: 90vh; overflow-y: auto;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Form inputs */
        .form-input {
            width: 100%; padding: 10px 14px;
            border: 1px solid #e2e8f0; border-radius: 12px;
            font-size: 14px; color: #334155; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }

        /* Filter tabs */
        .filter-tab {
            padding: 6px 14px; border-radius: 10px;
            font-size: 12px; font-weight: 600; cursor: pointer;
            transition: background 0.15s, color 0.15s;
            border: 1px solid transparent;
        }
        .filter-tab.active { background: #6366f1; color: #fff; border-color: #6366f1; }
        .filter-tab:not(.active) { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
        .filter-tab:not(.active):hover { background: #e0e7ff; color: #6366f1; border-color: #c7d2fe; }

        /* Date filter buttons */
        .date-filter-btn {
            padding: 6px 14px; border-radius: 10px;
            font-size: 12px; font-weight: 600; cursor: pointer;
            transition: all 0.15s;
            border: 1px solid #e2e8f0;
        }
        .date-filter-btn.active {
            background: #6366f1 !important;
            color: #fff !important;
            border-color: #6366f1 !important;
        }

        /* Search */
        .search-bar {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 10px 16px; font-size: 14px; color: #334155; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-bar:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
        
        .hidden { display: none !important; }
    </style>
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-white"
         style="border-bottom: 1px solid #f1f5f9; min-height: 64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing: -0.2px;">Task Management</h1>
            <p class="text-xs text-slate-400 mt-0.5">Assign and track employee tasks</p>
        </div>
        <button onclick="openModal('addModal')"
                class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-xl text-white transition-all"
                style="background: linear-gradient(135deg,#6366f1,#8b5cf6); box-shadow: 0 4px 14px rgba(99,102,241,0.35);"
                onmouseover="this.style.boxShadow='0 6px 20px rgba(99,102,241,0.5)'"
                onmouseout="this.style.boxShadow='0 4px 14px rgba(99,102,241,0.35)'">
            <i class="fas fa-plus text-xs"></i> Add Task
        </button>
    </div>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
             style="background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0;">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
             style="background:#fee2e2; color:#dc2626; border:1px solid #fecaca;">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe);">
                    <i class="fas fa-tasks text-indigo-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total Tasks</p>
                    <p class="text-2xl font-bold text-slate-800" id="statTotal"><?php echo $total; ?></p>
                </div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);">
                    <i class="fas fa-clipboard-list text-blue-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Assigned</p>
                    <p class="text-2xl font-bold text-slate-800" id="statAssigned"><?php echo $assigned; ?></p>
                </div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#fef9c3,#fde68a);">
                    <i class="fas fa-spinner text-amber-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">In Progress</p>
                    <p class="text-2xl font-bold text-slate-800" id="statProgress"><?php echo $inprogress; ?></p>
                </div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                    <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Completed</p>
                    <p class="text-2xl font-bold text-slate-800" id="statCompleted"><?php echo $completed; ?></p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white px-5 py-4 rounded-2xl space-y-4" style="border:1px solid #e2e8f0;">
            
            <!-- Date Filters -->
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-wide">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Date Filter</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button class="date-filter-btn active" onclick="setDateFilter('all', this)">All Dates</button>
                    <button class="date-filter-btn" onclick="setDateFilter('today', this)">Today</button>
                    <button class="date-filter-btn" onclick="setDateFilter('tomorrow', this)">Tomorrow</button>
                    <button class="date-filter-btn" onclick="setDateFilter('week', this)">This Week</button>
                    <button class="date-filter-btn" onclick="setDateFilter('nextweek', this)">Next Week</button>
                    <button class="date-filter-btn" onclick="setDateFilter('month', this)">This Month</button>
                    <button class="date-filter-btn" onclick="setDateFilter('nextmonth', this)">Next Month</button>
                    <button class="date-filter-btn" onclick="setDateFilter('overdue', this)">Overdue</button>
                </div>
            </div>
            
            <!-- Custom Date Range -->
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-wide">
                    <i class="fas fa-calendar-range"></i>
                    <span>Custom Range</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <input type="date" id="dateFrom" class="search-bar" style="width: auto; padding:7px 12px;" onchange="applyCustomDateRange()">
                    <span class="text-xs text-slate-400">to</span>
                    <input type="date" id="dateTo" class="search-bar" style="width: auto; padding:7px 12px;" onchange="applyCustomDateRange()">
                    <button onclick="clearDateFilter()" id="clearDateBtn" class="hidden text-xs font-semibold px-3 py-1.5 rounded-lg" style="background:#fee2e2; color:#dc2626;">
                        <i class="fas fa-times mr-1"></i>Clear
                    </button>
                </div>
            </div>
            
            <!-- Status Filter -->
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-wide">
                    <i class="fas fa-filter"></i>
                    <span>Status Filter</span>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button class="filter-tab active" onclick="filterCards('all',this)">All</button>
                    <button class="filter-tab" onclick="filterCards('assigned',this)">Assigned</button>
                    <button class="filter-tab" onclick="filterCards('in progress',this)">In Progress</button>
                    <button class="filter-tab" onclick="filterCards('completed',this)">Completed</button>
                    <button class="filter-tab" onclick="filterCards('sitevisit',this)">
                        <i class="fas fa-map-marker-alt text-xs mr-1"></i>Site Visits
                    </button>
                </div>
            </div>
            
            <!-- Search -->
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                <input type="text" id="searchInput" placeholder="Search tasks or employees..."
                       class="search-bar pl-9 w-full" oninput="filterCards(null,null)">
            </div>
        </div>

        <!-- Task Cards Grid -->
        <?php if (empty($tasks)): ?>
        <div class="flex flex-col items-center justify-center py-20">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                 style="background:#f1f5f9;">
                <i class="fas fa-tasks text-slate-300 text-2xl"></i>
            </div>
            <p class="text-slate-400 font-medium">No tasks yet</p>
            <p class="text-slate-300 text-sm mt-1">Click "Add Task" to assign a task to an employee</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5" id="taskGrid">
            <?php
            $status_cfg = [
                'assigned'    => ['bg'=>'#dbeafe','color'=>'#1e40af','dot'=>'#3b82f6'],
                'in progress' => ['bg'=>'#fef9c3','color'=>'#92400e','dot'=>'#f59e0b'],
                'completed'   => ['bg'=>'#dcfce7','color'=>'#166534','dot'=>'#10b981'],
            ];
            $avatar_colors = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#f97316'];
            foreach ($tasks as $i => $task):
                $status  = strtolower(trim($task['status']));
                $scfg    = $status_cfg[$status] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','dot'=>'#94a3b8'];
                $acolor  = $avatar_colors[$i % count($avatar_colors)];
                $initial = strtoupper(substr($task['employee_name'], 0, 1));
                $is_over = strtotime($task['due_date']) < strtotime('today') && $status !== 'completed';
                $due_date = $task['due_date'];
            ?>
            <div class="task-card"
                 data-status="<?php echo $status; ?>"
                 data-sitevisit="<?php echo $task['is_site_visit']; ?>"
                 data-due-date="<?php echo $due_date; ?>"
                 data-search="<?php echo strtolower(htmlspecialchars($task['title'].' '.$task['employee_name'].' '.$task['description'])); ?>">

                <!-- Top accent -->
                <div class="h-1 w-full" style="background:<?php echo $scfg['dot']; ?>;"></div>

                <div class="p-5 flex flex-col flex-1 gap-3">

                    <!-- Status + Site Visit badges -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                              style="background:<?php echo $scfg['bg']; ?>; color:<?php echo $scfg['color']; ?>;">
                            <span class="w-1.5 h-1.5 rounded-full" style="background:<?php echo $scfg['dot']; ?>;<?php echo $status==='in progress'?'animation:pulse 1.5s infinite;':'' ?>"></span>
                            <?php echo ucwords($status); ?>
                        </span>
                        <?php if ($task['is_site_visit']): ?>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full"
                              style="background:#ffedd5; color:#c2410c;">
                            <i class="fas fa-map-marker-alt text-xs"></i> Site Visit
                        </span>
                        <?php endif; ?>
                        <?php if ($is_over): ?>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full"
                              style="background:#fee2e2; color:#dc2626;">
                            <i class="fas fa-exclamation-circle text-xs"></i> Overdue
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Title -->
                    <h3 class="font-bold text-slate-800 text-sm leading-snug">
                        <?php echo htmlspecialchars($task['title']); ?>
                    </h3>

                    <!-- Description -->
                    <?php if ($task['description']): ?>
                    <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">
                        <?php echo htmlspecialchars($task['description']); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Meta info -->
                    <div class="space-y-1.5 mt-auto">
                        <!-- Assignee -->
                        <div class="flex items-center gap-2">
                            <div style="width:22px;height:22px;border-radius:6px;background:<?php echo $acolor; ?>20;color:<?php echo $acolor; ?>;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;">
                                <?php echo $initial; ?>
                            </div>
                            <span class="text-xs text-slate-600 font-medium"><?php echo htmlspecialchars($task['employee_name']); ?></span>
                        </div>

                        <!-- Due date -->
                        <div class="flex items-center gap-2">
                            <div style="width:22px;height:22px;border-radius:6px;background:<?php echo $is_over?'#fee2e2':'#f1f5f9'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-calendar text-xs" style="color:<?php echo $is_over?'#dc2626':'#94a3b8'; ?>;"></i>
                            </div>
                            <span class="text-xs font-medium due-date-text" style="color:<?php echo $is_over?'#dc2626':'#64748b'; ?>;">
                                <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                            </span>
                        </div>

                        <!-- Location -->
                        <?php if ($task['is_site_visit'] && $task['location']): ?>
                        <div class="flex items-center gap-2">
                            <div style="width:22px;height:22px;border-radius:6px;background:#ffedd5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-map-pin text-xs" style="color:#c2410c;"></i>
                            </div>
                            <span class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($task['location']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex gap-2 pt-1">
                        <button onclick="openEditModal(<?php echo $task['task_id']; ?>)"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 text-xs font-semibold py-2 rounded-xl transition-all"
                                style="background:#e0e7ff; color:#4f46e5;"
                                onmouseover="this.style.background='#c7d2fe'"
                                onmouseout="this.style.background='#e0e7ff'">
                            <i class="fas fa-edit text-xs"></i> Edit
                        </button>
                        <button onclick="deleteTask(<?php echo $task['task_id']; ?>)"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 text-xs font-semibold py-2 rounded-xl transition-all"
                                style="background:#fee2e2; color:#dc2626;"
                                onmouseover="this.style.background='#fecaca'"
                                onmouseout="this.style.background='#fee2e2'">
                            <i class="fas fa-trash text-xs"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /scrollable -->
</div><!-- /main-area -->

<!-- ── ADD MODAL ──────────────────────────────────── -->
<div id="addModal" class="modal-overlay" onclick="handleOverlayClick(event,'addModal')">
    <div class="modal-box">
        <div class="flex items-center justify-between px-6 py-5" style="border-bottom:1px solid #f1f5f9;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <i class="fas fa-plus text-white text-xs"></i>
                </div>
                <h2 class="font-bold text-slate-800">Add New Task</h2>
            </div>
            <button onclick="closeModal('addModal')"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form method="POST" onsubmit="return validateDueDate()" class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Assign To</label>
                <select name="emp_id" required class="form-input">
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?php echo $emp['emp_id']; ?>"><?php echo htmlspecialchars($emp['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Task Title</label>
                <input type="text" name="title" required class="form-input" placeholder="e.g. Monthly report review">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Description</label>
                <textarea name="description" rows="3" class="form-input" style="resize:none;" placeholder="Brief task description..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Due Date</label>
                <input type="date" name="due_date" required class="form-input">
            </div>
            <!-- Site visit toggle -->
            <div class="flex items-center gap-3 p-3 rounded-xl" style="background:#f8fafc; border:1px solid #e2e8f0;">
                <input type="checkbox" name="is_site_visit" id="is_site_visit" onchange="toggleLocationField()"
                       class="w-4 h-4 cursor-pointer" style="accent-color:#6366f1;">
                <label for="is_site_visit" class="text-sm font-medium text-slate-700 cursor-pointer flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-orange-400"></i> This is a site visit task
                </label>
            </div>
            <div id="location_group" class="hidden">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Visit Location</label>
                <input type="text" name="location" id="location_input" placeholder="Enter visit location" class="form-input">
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeModal('addModal')"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500"
                        style="background:#f1f5f9; border:1px solid #e2e8f0;"
                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    Cancel
                </button>
                <button type="submit" name="add_task"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6); box-shadow:0 4px 14px rgba(99,102,241,0.35);">
                    Assign Task
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── EDIT MODAL ─────────────────────────────────── -->
<div id="editModal" class="modal-overlay" onclick="handleOverlayClick(event,'editModal')">
    <div class="modal-box">
        <div class="flex items-center justify-between px-6 py-5" style="border-bottom:1px solid #f1f5f9;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <i class="fas fa-edit text-white text-xs"></i>
                </div>
                <h2 class="font-bold text-slate-800">Edit Task</h2>
            </div>
            <button onclick="closeModal('editModal')"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form method="POST" class="px-6 py-5 space-y-4">
            <input type="hidden" name="task_id" id="edit_task_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Task Title</label>
                <input type="text" name="title" id="edit_title" required class="form-input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Description</label>
                <textarea name="description" id="edit_description" rows="3" class="form-input" style="resize:none;"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Due Date</label>
                <input type="date" name="due_date" id="edit_due_date" required class="form-input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Status</label>
                <select name="status" id="edit_status" required class="form-input">
                    <option value="Assigned">Assigned</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeModal('editModal')"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500"
                        style="background:#f1f5f9; border:1px solid #e2e8f0;"
                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    Cancel
                </button>
                <button type="submit" name="edit_task"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6); box-shadow:0 4px 14px rgba(99,102,241,0.35);">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── DELETE CONFIRM MODAL ───────────────────────── -->
<div id="deleteModal" class="modal-overlay" onclick="handleOverlayClick(event,'deleteModal')">
    <div class="modal-box" style="max-width:380px;">
        <div class="px-6 py-6 text-center">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
                 style="background:#fee2e2;">
                <i class="fas fa-trash text-red-500 text-xl"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-lg mb-1">Delete Task?</h3>
            <p class="text-sm text-slate-400">This action cannot be undone.</p>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button onclick="closeModal('deleteModal')"
                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500"
                    style="background:#f1f5f9; border:1px solid #e2e8f0;"
                    onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                Cancel
            </button>
            <button id="confirmDeleteBtn"
                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
                    style="background:linear-gradient(135deg,#ef4444,#dc2626); box-shadow:0 4px 14px rgba(239,68,68,0.35);">
                Yes, Delete
            </button>
        </div>
    </div>
</div>

<script>
/* ── Modal helpers ── */
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function handleOverlayClick(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }

/* ── Edit modal ── */
function openEditModal(taskId) {
    document.getElementById('edit_task_id').value = taskId;
    openModal('editModal');
    fetch(`task_assign.php?task_id=${taskId}&json=1`)
        .then(r => r.json())
        .then(d => {
            document.getElementById('edit_title').value       = d.title;
            document.getElementById('edit_description').value = d.description;
            document.getElementById('edit_due_date').value    = d.due_date;
            document.getElementById('edit_status').value      = d.status;
        });
}

/* ── Delete with confirm modal ── */
let deleteTargetId = null;
function deleteTask(taskId) {
    deleteTargetId = taskId;
    openModal('deleteModal');
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    if (deleteTargetId !== null) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="task_id" value="${deleteTargetId}"><input type="hidden" name="delete_task" value="1">`;
        document.body.appendChild(form);
        form.submit();
    }
});

/* ── Site visit toggle ── */
function toggleLocationField() {
    const checked = document.getElementById('is_site_visit').checked;
    const group   = document.getElementById('location_group');
    group.classList.toggle('hidden', !checked);
    document.getElementById('location_input').required = checked;
}

/* ── Due date validation ── */
function validateDueDate() {
    const val   = document.querySelector('#addModal input[name="due_date"]').value;
    const today = new Date().toISOString().split('T')[0];
    if (val < today) { alert('Due date cannot be in the past!'); return false; }
    return true;
}

/* ── Date Filter Variables ── */
let activeDateFilter = 'all';
let activeStatus = 'all';
let customDateFrom = '';
let customDateTo = '';

/* ── Set date filter ── */
function setDateFilter(filter, btn) {
    activeDateFilter = filter;
    customDateFrom = '';
    customDateTo = '';
    
    // Update button styles
    document.querySelectorAll('.date-filter-btn').forEach(b => {
        b.classList.remove('active');
    });
    if (btn) btn.classList.add('active');
    
    // Clear custom date inputs
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('clearDateBtn').classList.add('hidden');
    
    filterCards();
}

/* ── Apply custom date range ── */
function applyCustomDateRange() {
    customDateFrom = document.getElementById('dateFrom').value;
    customDateTo = document.getElementById('dateTo').value;
    
    if (customDateFrom || customDateTo) {
        activeDateFilter = 'custom';
        
        // Reset quick date buttons
        document.querySelectorAll('.date-filter-btn').forEach(b => {
            b.classList.remove('active');
        });
        
        // Show clear button
        document.getElementById('clearDateBtn').classList.remove('hidden');
    } else {
        document.getElementById('clearDateBtn').classList.add('hidden');
    }
    
    filterCards();
}

/* ── Clear date filter ── */
function clearDateFilter() {
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('clearDateBtn').classList.add('hidden');
    
    customDateFrom = '';
    customDateTo = '';
    activeDateFilter = 'all';
    
    // Update buttons
    document.querySelectorAll('.date-filter-btn').forEach(b => {
        if (b.textContent.trim() === 'All Dates') {
            b.classList.add('active');
        } else {
            b.classList.remove('active');
        }
    });
    
    filterCards();
}

/* ── Check if date matches filter ── */
function dateMatchesFilter(dueDate) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const date = new Date(dueDate);
    date.setHours(0, 0, 0, 0);
    
    // Get tomorrow
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    // Get next week
    const nextWeek = new Date(today);
    nextWeek.setDate(nextWeek.getDate() + 7);
    
    // Get next month
    const nextMonth = new Date(today);
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    
    switch(activeDateFilter) {
        case 'today':
            return date.getTime() === today.getTime();
            
        case 'tomorrow':
            return date.getTime() === tomorrow.getTime();
            
        case 'week':
            const weekEnd = new Date(today);
            weekEnd.setDate(weekEnd.getDate() + (7 - weekEnd.getDay()));
            return date >= today && date <= weekEnd;
            
        case 'nextweek':
            const nextWeekStart = new Date(today);
            nextWeekStart.setDate(nextWeekStart.getDate() + (7 - nextWeekStart.getDay() + 1));
            const nextWeekEnd = new Date(nextWeekStart);
            nextWeekEnd.setDate(nextWeekEnd.getDate() + 6);
            return date >= nextWeekStart && date <= nextWeekEnd;
            
        case 'month':
            const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            return date >= today && date <= monthEnd;
            
        case 'nextmonth':
            const nextMonthStart = new Date(today.getFullYear(), today.getMonth() + 1, 1);
            const nextMonthEnd = new Date(today.getFullYear(), today.getMonth() + 2, 0);
            return date >= nextMonthStart && date <= nextMonthEnd;
            
        case 'overdue':
            return date < today;
            
        case 'custom':
            if (customDateFrom && customDateTo) {
                return dueDate >= customDateFrom && dueDate <= customDateTo;
            } else if (customDateFrom) {
                return dueDate >= customDateFrom;
            } else if (customDateTo) {
                return dueDate <= customDateTo;
            }
            return true;
            
        case 'all':
        default:
            return true;
    }
}

/* ── Filter cards ── */
function filterCards(status = null, btn = null) {
    // Update status filter if provided
    if (status !== null) {
        activeStatus = status;
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        if (btn) btn.classList.add('active');
    }
    
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('#taskGrid .task-card');
    
    let total = 0;
    let assigned = 0;
    let inProgress = 0;
    let completed = 0;
    
    cards.forEach(card => {
        const cardStatus = card.dataset.status;
        const dueDate = card.dataset.dueDate;
        const isSiteVisit = card.dataset.sitevisit === '1';
        const searchText = card.dataset.search;
        
        // Check status filter
        let statusMatch = activeStatus === 'all' || 
                         (activeStatus === 'sitevisit' && isSiteVisit) ||
                         (!['all', 'sitevisit'].includes(activeStatus) && cardStatus === activeStatus);
        
        // Check date filter
        const dateMatch = dateMatchesFilter(dueDate);
        
        // Check search
        const searchMatch = searchText.includes(searchQuery);
        
        const visible = statusMatch && dateMatch && searchMatch;
        
        card.style.display = visible ? '' : 'none';
        
        if (visible) {
            total++;
            if (cardStatus === 'assigned') assigned++;
            else if (cardStatus === 'in progress') inProgress++;
            else if (cardStatus === 'completed') completed++;
        }
    });
    
    // Update stat cards
    document.getElementById('statTotal').textContent = total;
    document.getElementById('statAssigned').textContent = assigned;
    document.getElementById('statProgress').textContent = inProgress;
    document.getElementById('statCompleted').textContent = completed;
}

/* ── Initialize with all dates ── */
window.addEventListener('DOMContentLoaded', () => {
    const allDatesBtn = document.querySelector('.date-filter-btn');
    if (allDatesBtn) {
        setDateFilter('all', allDatesBtn);
    }
});
</script>

</body>
</html>