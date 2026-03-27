<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.html');
    exit();
}

require_once '../config/db.php';

$emp_id = $_SESSION['emp_id'];


$current_month = date('Y-m');

// Get last reset month
$res = mysqli_query($conn, "SELECT last_leave_reset FROM employee WHERE emp_id='$emp_id'");
$data = mysqli_fetch_assoc($res);

$last_reset = $data['last_leave_reset'] ?? '';

if ($last_reset != $current_month) {
    // Reset leave_balance to 5
    mysqli_query($conn, "
        UPDATE employee 
        SET leave_balance = 5,
            last_leave_reset = '$current_month'
        WHERE emp_id = '$emp_id'
    ");
}


// Employee data
$employee_result = $conn->query("SELECT * FROM employee WHERE emp_id = '$emp_id'");
$employee_data   = $employee_result->fetch_assoc();

// Hours today
$today = date('Y-m-d');
$hours_today_result = $conn->query("
    SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(check_out, check_in)))) AS hours_today
    FROM attendance WHERE emp_id = '$emp_id' AND date = '$today'
    AND check_in IS NOT NULL AND check_out IS NOT NULL
");
$hours_today = $hours_today_result->fetch_assoc()['hours_today'] ?? '00:00:00';

// Tasks due today (pending)
$tasks_result = $conn->query("SELECT * FROM task_assigned WHERE emp_id = '$emp_id' AND DATE(due_date) = '$today' AND status != 'Completed' ORDER BY due_date ASC LIMIT 3");

// All tasks today
$all_tasks_result = $conn->query("SELECT * FROM task_assigned WHERE emp_id = '$emp_id' AND DATE(due_date) = '$today' ORDER BY due_date ASC LIMIT 5");

// Clock in status
$stmt = $conn->prepare("SELECT * FROM attendance WHERE emp_id = ? AND date = ? AND check_out IS NULL LIMIT 1");
$stmt->bind_param("is", $emp_id, $today);
$stmt->execute();
$isClockedIn = $stmt->get_result()->num_rows > 0;

$tasks_due = $tasks_result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emplify - Employee Dashboard</title>
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

        .stat-card {
            background: #fff;
            border: 1px solid rgba(226,232,240,0.8);
            border-radius: 16px; padding: 20px 24px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.08); transform: translateY(-2px); }

        .clock-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            border-radius: 20px; padding: 28px 32px;
            position: relative; overflow: hidden;
        }
        .clock-hero::before {
            content: ''; position: absolute; top: -40px; right: -40px;
            width: 180px; height: 180px; border-radius: 50%;
            background: rgba(99,102,241,0.15);
        }
        .clock-hero::after {
            content: ''; position: absolute; bottom: -30px; left: 20px;
            width: 120px; height: 120px; border-radius: 50%;
            background: rgba(139,92,246,0.1);
        }

        .task-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            transition: box-shadow 0.15s, transform 0.15s;
        }
        .task-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.07); transform: translateY(-1px); }

        /* Notification modal */
        .notif-overlay {
            display: none; position: fixed; inset: 0; z-index: 200;
            background: rgba(15,23,42,0.5); backdrop-filter: blur(2px);
            align-items: center; justify-content: center;
        }
        .notif-overlay.open { display: flex; }
        .notif-box {
            background: #fff; border-radius: 20px; width: 100%;
            max-width: 380px; margin: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.2);
            animation: popIn 0.2s ease;
        }
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
</head>
<body>

<?php include 'employee_sidebar.php'; ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-white"
         style="border-bottom: 1px solid #f1f5f9; min-height: 64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing:-0.2px;">Dashboard</h1>
            <p class="text-xs text-slate-400 mt-0.5" id="dash-date">—</p>
        </div>
        <a href="my_profile.php?emp_id=<?php echo $_SESSION['emp_id']; ?>"
            class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all"
            style="background:#f8fafc; border:1px solid #e2e8f0;"
            onmouseover="this.style.background='#e0e7ff'" 
            onmouseout="this.style.background='#f8fafc'">

                <?php if (!empty($employee_data['image']) && file_exists('../uploads/' . $employee_data['image'])): ?>

                    <!-- Employee Image -->
                    <img src="../uploads/<?php echo htmlspecialchars($employee_data['image']); ?>"
                        class="w-8 h-8 rounded-xl object-cover border border-gray-200">

                <?php else: ?>

                    <!-- Default Initial -->
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold text-white"
                        style="background: linear-gradient(135deg,#6366f1,#8b5cf6);">
                        <?php echo strtoupper(substr($employee_data['name'], 0, 1)); ?>
                    </div>

                <?php endif; ?>

                <div class="hidden sm:block">
                    <p class="text-xs font-semibold text-slate-700">
                        <?php echo htmlspecialchars($employee_data['name']); ?>
                    </p>
                    <p class="text-xs text-slate-400">View Profile</p>
                </div>

                <i class="fas fa-chevron-right text-slate-300 text-xs ml-1"></i>
        </a>
    </div>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-6">

        <!-- Clock In/Out Hero + Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

            <!-- Clock Hero -->
            <div class="clock-hero lg:col-span-1">
                <div class="relative z-10">
                    <p class="text-xs font-semibold uppercase tracking-widest mb-2"
                       style="color:rgba(165,180,252,0.7);">Live Clock</p>
                    <div class="text-3xl font-bold text-white tracking-tight mb-1" id="live-clock">00:00:00</div>
                    <div class="text-xs mb-5" style="color:rgba(148,163,184,0.7);" id="live-date">Loading...</div>

                    <?php if (!$isClockedIn): ?>
                    <a href="clock_in.php"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                       style="background:linear-gradient(135deg,#10b981,#059669); box-shadow:0 4px 14px rgba(16,185,129,0.4);"
                       onmouseover="this.style.boxShadow='0 6px 20px rgba(16,185,129,0.55)'"
                       onmouseout="this.style.boxShadow='0 4px 14px rgba(16,185,129,0.4)'">
                        <i class="fas fa-sign-in-alt text-xs"></i> Clock In
                    </a>
                    <?php else: ?>
                    <a href="clock_out.php"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                       style="background:linear-gradient(135deg,#ef4444,#dc2626); box-shadow:0 4px 14px rgba(239,68,68,0.4);"
                       onmouseover="this.style.boxShadow='0 6px 20px rgba(239,68,68,0.55)'"
                       onmouseout="this.style.boxShadow='0 4px 14px rgba(239,68,68,0.4)'">
                        <i class="fas fa-sign-out-alt text-xs"></i> Clock Out
                    </a>
                    <div class="mt-3 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse inline-block"></span>
                        <span class="text-xs" style="color:rgba(148,163,184,0.7);">Currently clocked in</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stat: Hours Today -->
            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe);">
                    <i class="fas fa-hourglass-half text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Hours Today</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo substr($hours_today, 0, 5); ?></p>
                </div>
            </div>

            <!-- Stat: Leave Balance -->
            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                    <i class="fas fa-umbrella-beach text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Leave Balance</p>
                    <?php
                        $sql="select * from employee where emp_id='".$emp_id."'";
                        $res=mysqli_query($conn,$sql);
                        $row=mysqli_fetch_assoc($res);
                    ?>
                    <p class=" font-bold text-slate-800">
                        <?php echo isset($row['leave_balance']) ? htmlspecialchars($row['leave_balance']) . ' days left' : '0 days left'; ?>
                    </p>
                </div>
            </div>

            <!-- Stat: Tasks Due -->
            <div class="stat-card flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#fef9c3,#fde68a);">
                    <i class="fas fa-tasks text-amber-500"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Tasks Due</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $tasks_due; ?></p>
                </div>
            </div>

        </div>

        <!-- Today's Tasks -->
        <div class="bg-white rounded-2xl overflow-hidden"
             style="border:1px solid #e2e8f0; box-shadow:0 2px 12px rgba(0,0,0,0.04);">

            <div class="flex items-center justify-between px-5 py-4"
                 style="border-bottom:1px solid #f1f5f9;">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Today's Tasks</h2>
                    <p class="text-xs text-slate-400 mt-0.5"><?php echo date('l, M d Y'); ?></p>
                </div>
                <a href="my_tasks.php"
                   class="text-xs font-semibold px-3 py-1.5 rounded-xl transition-all"
                   style="background:#e0e7ff; color:#6366f1;"
                   onmouseover="this.style.background='#c7d2fe'" onmouseout="this.style.background='#e0e7ff'">
                    View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            </div>

            <div class="p-5 space-y-3">
                <?php
                $task_colors = [
                    'assigned'    => ['bg'=>'#dbeafe','color'=>'#1e40af','dot'=>'#3b82f6'],
                    'in progress' => ['bg'=>'#fef9c3','color'=>'#92400e','dot'=>'#f59e0b'],
                    'completed'   => ['bg'=>'#dcfce7','color'=>'#166534','dot'=>'#10b981'],
                ];
                $has_tasks = false;
                while ($task = $all_tasks_result->fetch_assoc()):
                    $has_tasks = true;
                    $status  = strtolower($task['status']);
                    $tcfg    = $task_colors[$status] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','dot'=>'#94a3b8'];
                    $is_site = $task['is_site_visit'] ?? 0;
                ?>
                <div class="task-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-0.5 rounded-full"
                                      style="background:<?php echo $tcfg['bg']; ?>; color:<?php echo $tcfg['color']; ?>;">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background:<?php echo $tcfg['dot']; ?>;"></span>
                                    <?php echo ucwords($status); ?>
                                </span>
                                <?php if ($is_site): ?>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                                      style="background:#ffedd5; color:#c2410c;">
                                    <i class="fas fa-map-marker-alt text-xs"></i> Site Visit
                                </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-semibold text-slate-800 text-sm">
                                <?php echo htmlspecialchars($task['title']); ?>
                            </h3>
                            <?php if (!empty($task['description'])): ?>
                            <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">
                                <?php echo htmlspecialchars($task['description']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-slate-400 flex-shrink-0">
                            <i class="fas fa-calendar text-slate-300"></i>
                            <?php echo date('M d', strtotime($task['due_date'])); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>

                <?php if (!$has_tasks): ?>
                <div class="flex flex-col items-center justify-center py-10">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-3"
                         style="background:#f1f5f9;">
                        <i class="fas fa-check-circle text-slate-300 text-lg"></i>
                    </div>
                    <p class="text-slate-400 font-medium text-sm">No tasks due today</p>
                    <p class="text-slate-300 text-xs mt-0.5">Enjoy your day!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /scrollable -->
</div><!-- /main-area -->

<!-- Notification Modal -->
<div id="notifOverlay" class="notif-overlay">
    <div class="notif-box">
        <div class="p-6 text-center">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" id="notifIconWrap"
                 style="background:#dcfce7;">
                <i class="fas fa-check text-emerald-500 text-xl" id="notifIcon"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-base mb-1" id="notifTitle">Success</h3>
            <p class="text-sm text-slate-500" id="notifMessage"></p>
        </div>
        <div class="px-6 pb-6">
            <button onclick="closeNotif()"
                    class="w-full py-2.5 rounded-xl text-sm font-semibold text-white"
                    style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">OK</button>
        </div>
    </div>
</div>

<script>
// Live clock
function updateClock() {
    const now = new Date();
    const c = document.getElementById('live-clock');
    const d = document.getElementById('live-date');
    const h = document.getElementById('dash-date');
    if (c) c.textContent = now.toLocaleTimeString('en-US', { hour12: false });
    if (d) d.textContent = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    if (h) h.textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}
updateClock();
setInterval(updateClock, 1000);

// Notification modal
function closeNotif() {
    document.getElementById('notifOverlay').classList.remove('open');
}

window.addEventListener('DOMContentLoaded', () => {
    const params  = new URLSearchParams(window.location.search);
    const type    = params.get('modal');
    const message = params.get('msg') || '';
    const action  = params.get('type');

    if (type && message.trim()) {
        const isSuccess = type === 'success';
        const wrap  = document.getElementById('notifIconWrap');
        const icon  = document.getElementById('notifIcon');
        const title = document.getElementById('notifTitle');
        const msg   = document.getElementById('notifMessage');

        wrap.style.background = isSuccess ? '#dcfce7' : '#fee2e2';
        icon.className = isSuccess ? 'fas fa-check text-emerald-500 text-xl' : 'fas fa-times text-red-500 text-xl';

        const labels = { clockin: ['Clock-In Successful','Clock-In Failed'], clockout: ['Clock-Out Successful','Clock-Out Failed'] };
        const pair   = labels[action] || ['Success','Error'];
        title.textContent   = isSuccess ? pair[0] : pair[1];
        msg.textContent     = decodeURIComponent(message);

        document.getElementById('notifOverlay').classList.add('open');
        setTimeout(closeNotif, 3000);

        if (history.replaceState) history.replaceState(null, '', window.location.pathname);
    }
});
</script>

</body>
</html>