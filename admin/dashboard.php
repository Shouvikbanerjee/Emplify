<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}

$today = date('Y-m-d');

// Employees present today
$attendance_result = $conn->query("SELECT COUNT(DISTINCT emp_id) as present_count FROM attendance WHERE date = '$today'");
$present_count = $attendance_result->fetch_assoc()['present_count'];

// Tasks due today
$status = 'assigned';
$stmt = $conn->prepare("SELECT COUNT(*) as due_tasks FROM task_assigned WHERE status = ? AND due_date = ?");
$stmt->bind_param("ss", $status, $today);
$stmt->execute();
$due_tasks = $stmt->get_result()->fetch_assoc()['due_tasks'];

// Pending leave requests
$leave_result = $conn->query("SELECT COUNT(*) as pending_leaves FROM leave_management WHERE status = 'Pending'");
$pending_leaves = $leave_result ? $leave_result->fetch_assoc()['pending_leaves'] : 0;

// Site visits today
$reports_result = $conn->query("SELECT COUNT(*) as unread_reports FROM task_assigned WHERE is_site_visit=1 AND due_date = '$today'");
$unread_reports = $reports_result ? $reports_result->fetch_assoc()['unread_reports'] : 0;

// Recent activities
$recent_activities_sql = "
(SELECT 'attendance' AS type, e.name AS employee_name, a.check_in AS activity_time, 'checked in' AS action
 FROM attendance a JOIN employee e ON a.emp_id = e.emp_id WHERE DATE(a.date) = CURDATE())
UNION
(SELECT 'leave' AS type, e.name AS employee_name, l.start_date AS activity_time, 'requested leave' AS action
 FROM leave_management l JOIN employee e ON l.emp_id = e.emp_id WHERE DATE(l.start_date) = CURDATE() AND l.status = 'Pending')
UNION
(SELECT 'task' AS type, e.name AS employee_name, t.assign_date AS activity_time,
 CASE WHEN t.status='Completed' THEN 'completed task' WHEN t.status='In Progress' THEN 'started task' ELSE 'was assigned task' END AS action
 FROM task_assigned t JOIN employee e ON t.emp_id = e.emp_id WHERE DATE(t.assign_date) = CURDATE())
UNION
(SELECT 'report' AS type, e.name AS employee_name, r.created_at AS activity_time, 'created report' AS action
 FROM reports r JOIN employee e ON r.emp_id = e.emp_id WHERE DATE(r.created_at) = CURDATE())
UNION
(SELECT 'feedback' AS type, e.name AS employee_name, f.submitted_on AS activity_time, 'created feedback' AS action
 FROM feedback f JOIN employee e ON f.emp_id = e.emp_id WHERE DATE(f.submitted_on) = CURDATE())
ORDER BY activity_time DESC LIMIT 5";

$activities_result = $conn->query($recent_activities_sql);
$recent_activities = $activities_result ? $activities_result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emplify - Admin Dashboard</title>
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
        .stat-card {
            background: #fff;
            border: 1px solid rgba(226,232,240,0.8);
            border-radius: 16px;
            padding: 20px 24px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .activity-icon-wrap {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-white"
         style="border-bottom: 1px solid #f1f5f9; min-height: 64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing: -0.2px;">Dashboard Overview</h1>
            <p class="text-xs text-slate-400 mt-0.5" id="dash-date">—</p>
        </div>
        <a href="logout.php"
           class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-xl transition-all"
           style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;"
           onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">
            <i class="fas fa-sign-out-alt text-xs"></i> Logout
        </a>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-6">

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

            <!-- Present Today -->
            <div class="stat-card flex items-center gap-4">
                <div class="activity-icon-wrap" style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe);">
                    <i class="fas fa-user-check text-indigo-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Present Today</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $present_count; ?></p>
                </div>
            </div>

            <!-- Tasks Due -->
            <div class="stat-card flex items-center gap-4">
                <div class="activity-icon-wrap" style="background: linear-gradient(135deg, #fef9c3, #fde68a);">
                    <i class="fas fa-tasks text-amber-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Tasks Due Today</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $due_tasks; ?></p>
                </div>
            </div>

            <!-- Pending Leaves -->
            <div class="stat-card flex items-center gap-4">
                <div class="activity-icon-wrap" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                    <i class="fas fa-calendar-alt text-red-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Pending Leaves</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $pending_leaves; ?></p>
                </div>
            </div>

            <!-- Site Visits -->
            <div class="stat-card flex items-center gap-4">
                <div class="activity-icon-wrap" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0);">
                    <i class="fas fa-map-marker-alt text-emerald-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Site Visits Today</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $unread_reports; ?></p>
                </div>
            </div>

        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-2xl overflow-hidden" style="border: 1px solid #e2e8f0; box-shadow: 0 2px 12px rgba(0,0,0,0.04);">

            <div class="flex items-center justify-between p-5" style="border-bottom: 1px solid #f1f5f9;">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Recent Activity</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Today's activity log</p>
                </div>
                <span class="text-xs font-semibold px-3 py-1 rounded-full"
                      style="background: #f1f5f9; color: #64748b;">Today</span>
            </div>

            <div class="divide-y divide-slate-50">
                <?php if (empty($recent_activities)): ?>
                <div class="flex flex-col items-center justify-center py-14">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-3"
                         style="background: #f1f5f9;">
                        <i class="fas fa-inbox text-slate-300 text-lg"></i>
                    </div>
                    <p class="text-slate-400 font-medium text-sm">No activity today</p>
                    <p class="text-slate-300 text-xs mt-1">Activity will appear here throughout the day</p>
                </div>
                <?php else: ?>
                <?php
                $type_config = [
                    'attendance' => ['icon' => 'fa-user-clock',   'bg' => '#e0e7ff', 'color' => '#4f46e5'],
                    'leave'      => ['icon' => 'fa-calendar-alt', 'bg' => '#fee2e2', 'color' => '#dc2626'],
                    'task'       => ['icon' => 'fa-tasks',        'bg' => '#fef9c3', 'color' => '#d97706'],
                    'report'     => ['icon' => 'fa-file-alt',     'bg' => '#dcfce7', 'color' => '#16a34a'],
                    'feedback'   => ['icon' => 'fa-comments',     'bg' => '#ede9fe', 'color' => '#7c3aed'],
                ];
                foreach ($recent_activities as $activity):
                    $cfg = $type_config[$activity['type']] ?? ['icon' => 'fa-info-circle', 'bg' => '#f1f5f9', 'color' => '#64748b'];
                    $initials = strtoupper(substr($activity['employee_name'], 0, 1));
                ?>
                <div class="flex items-start gap-4 px-5 py-4 hover:bg-slate-50 transition-colors">

                    <!-- Type icon -->
                    <div class="activity-icon-wrap flex-shrink-0 mt-0.5"
                         style="background: <?php echo $cfg['bg']; ?>;">
                        <i class="fas <?php echo $cfg['icon']; ?> text-xs" style="color: <?php echo $cfg['color']; ?>;"></i>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-700">
                            <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($activity['employee_name']); ?></span>
                            <span class="text-slate-500"> <?php echo htmlspecialchars($activity['action']); ?></span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <i class="fas fa-clock mr-1 text-slate-300"></i>
                            <?php echo date('d M Y, h:i A', strtotime($activity['activity_time'])); ?>
                        </p>
                    </div>

                    <!-- Type badge -->
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize flex-shrink-0"
                          style="background: <?php echo $cfg['bg']; ?>; color: <?php echo $cfg['color']; ?>;">
                        <?php echo $activity['type']; ?>
                    </span>

                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

    </div><!-- /scrollable -->
</div><!-- /main-area -->

<script>
const d = new Date();
const dateEl = document.getElementById('dash-date');
if (dateEl) dateEl.textContent = d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
</script>

</body>
</html>