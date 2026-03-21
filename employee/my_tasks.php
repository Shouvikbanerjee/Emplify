<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['emp_id'])) {
    header('Location: login.html');
    exit();
}

$emp_id = $_SESSION['emp_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $task_id  = $conn->real_escape_string($_POST['task_id']);
    $status   = $conn->real_escape_string($_POST['status']);
    $feedback = $conn->real_escape_string($_POST['feedback']);
    $stmt = $conn->prepare("UPDATE task_assigned SET status=?, feedback=?, report_submitted_on=CASE WHEN ?='Completed' THEN CURRENT_TIMESTAMP ELSE NULL END WHERE task_id=? AND emp_id=?");
    $stmt->bind_param('ssssi', $status, $feedback, $status, $task_id, $emp_id);
    if ($stmt->execute()) $_SESSION['success_message'] = "Task updated successfully!";
    else $_SESSION['error_message'] = "Error updating task.";
    header('Location: my_tasks.php'); exit();
}

$stmt = $conn->prepare("SELECT t.*, CASE WHEN t.due_date < CURDATE() AND t.status != 'Completed' THEN 1 ELSE 0 END as is_overdue FROM task_assigned t WHERE t.emp_id=? ORDER BY t.assign_date DESC, t.is_site_visit DESC, t.due_date ASC");
$stmt->bind_param('i', $emp_id);
$stmt->execute();
$tasks_result = $stmt->get_result();
$tasks = $tasks_result->fetch_all(MYSQLI_ASSOC);

$total     = count($tasks);
$completed = count(array_filter($tasks, fn($t) => strtolower($t['status']) === 'completed'));
$inprog    = count(array_filter($tasks, fn($t) => strtolower($t['status']) === 'in progress'));
$overdue   = count(array_filter($tasks, fn($t) => $t['is_overdue'] == 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - Emplify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
        body { background: #f1f5f9; height: 100vh; overflow: hidden; }
        .main-area { display: flex; flex-direction: column; height: 100vh; overflow: hidden; margin-left: 0; }
        @media (min-width: 1024px) { .main-area { margin-left: 256px; } }

        .stat-card { background:#fff; border:1px solid rgba(226,232,240,0.8); border-radius:16px; padding:18px 22px; transition:all .2s; }
        .stat-card:hover { box-shadow:0 8px 30px rgba(0,0,0,0.08); transform:translateY(-2px); }

        .task-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; overflow:hidden; transition:box-shadow .2s, transform .2s; }
        .task-card:hover { box-shadow:0 8px 28px rgba(0,0,0,0.09); transform:translateY(-2px); }

        .modal-overlay { display:none; position:fixed; inset:0; z-index:100; background:rgba(15,23,42,0.5); backdrop-filter:blur(2px); align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:20px; width:100%; max-width:480px; margin:16px; box-shadow:0 24px 60px rgba(0,0,0,0.18); animation:modalIn .2s ease; max-height:90vh; overflow-y:auto; }
        @keyframes modalIn { from{opacity:0;transform:scale(.96) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }

        .form-input { width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:12px; font-size:14px; color:#334155; outline:none; transition:border-color .2s,box-shadow .2s; font-family:'DM Sans',sans-serif; }
        .form-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12); }

        .filter-tab { padding:6px 14px; border-radius:10px; font-size:12px; font-weight:600; cursor:pointer; transition:all .15s; border:1px solid transparent; }
        .filter-tab.active { background:#6366f1; color:#fff; border-color:#6366f1; }
        .filter-tab:not(.active) { background:#f8fafc; color:#64748b; border-color:#e2e8f0; }
        .filter-tab:not(.active):hover { background:#e0e7ff; color:#6366f1; border-color:#c7d2fe; }
    </style>
</head>
<body>

<?php include 'employee_sidebar.php'; ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-white" style="border-bottom:1px solid #f1f5f9; min-height:64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing:-0.2px;">My Tasks</h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage and update your assigned tasks</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-slate-500 px-4 py-2 rounded-xl bg-white" style="border:1px solid #e2e8f0;">
            <i class="fas fa-calendar-day text-indigo-400"></i>
            <span><?php echo date('D, M d Y'); ?></span>
        </div>
    </div>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <!-- Flash messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium" style="background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;">
            <i class="fas fa-check-circle"></i><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium" style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;">
            <i class="fas fa-exclamation-circle"></i><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe);">
                    <i class="fas fa-tasks text-indigo-600 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total</p><p class="text-2xl font-bold text-slate-800"><?php echo $total; ?></p></div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#fef9c3,#fde68a);">
                    <i class="fas fa-spinner text-amber-500 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">In Progress</p><p class="text-2xl font-bold text-slate-800"><?php echo $inprog; ?></p></div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                    <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Completed</p><p class="text-2xl font-bold text-slate-800"><?php echo $completed; ?></p></div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#fee2e2,#fecaca);">
                    <i class="fas fa-exclamation-triangle text-red-500 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Overdue</p><p class="text-2xl font-bold text-slate-800"><?php echo $overdue; ?></p></div>
            </div>
        </div>

        <!-- Filter tabs -->
        <div class="flex items-center gap-2 flex-wrap">
            <button class="filter-tab active" onclick="filterCards('all',this)">All</button>
            <button class="filter-tab" onclick="filterCards('assigned',this)">Assigned</button>
            <button class="filter-tab" onclick="filterCards('in progress',this)">In Progress</button>
            <button class="filter-tab" onclick="filterCards('completed',this)">Completed</button>
            <button class="filter-tab" onclick="filterCards('overdue',this)"><i class="fas fa-exclamation-triangle text-xs mr-1"></i>Overdue</button>
            <button class="filter-tab" onclick="filterCards('sitevisit',this)"><i class="fas fa-map-marker-alt text-xs mr-1"></i>Site Visits</button>
        </div>

        <!-- Task Cards Grid -->
        <?php if (empty($tasks)): ?>
        <div class="flex flex-col items-center justify-center py-24">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background:#f1f5f9;">
                <i class="fas fa-tasks text-slate-300 text-2xl"></i>
            </div>
            <p class="text-slate-400 font-medium">No tasks assigned yet</p>
        </div>
        <?php else: ?>
        <?php
        $status_cfg = [
            'assigned'    => ['bg'=>'#dbeafe','color'=>'#1e40af','dot'=>'#3b82f6'],
            'in progress' => ['bg'=>'#fef9c3','color'=>'#92400e','dot'=>'#f59e0b'],
            'completed'   => ['bg'=>'#dcfce7','color'=>'#166534','dot'=>'#10b981'],
            'pending'     => ['bg'=>'#f1f5f9','color'=>'#64748b','dot'=>'#94a3b8'],
        ];
        ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5" id="taskGrid">
            <?php foreach ($tasks as $task):
                $status  = strtolower(trim($task['status']));
                $scfg    = $status_cfg[$status] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','dot'=>'#94a3b8'];
                $overdue = $task['is_overdue'] == 1;
                $is_site = $task['is_site_visit'] ?? 0;
                $done    = $status === 'completed';
            ?>
            <div class="task-card"
                 data-status="<?php echo $status; ?>"
                 data-overdue="<?php echo $overdue ? '1' : '0'; ?>"
                 data-sitevisit="<?php echo $is_site; ?>">

                <!-- Top accent -->
                <div class="h-1 w-full" style="background:<?php echo $overdue && !$done ? '#ef4444' : $scfg['dot']; ?>;"></div>

                <div class="p-5 flex flex-col gap-3">
                    <!-- Badges -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                              style="background:<?php echo $scfg['bg']; ?>;color:<?php echo $scfg['color']; ?>;">
                            <span class="w-1.5 h-1.5 rounded-full" style="background:<?php echo $scfg['dot']; ?>;<?php echo $status==='in progress'?'animation:pulse 1.5s infinite;':''; ?>"></span>
                            <?php echo ucwords($status); ?>
                        </span>
                        <?php if ($is_site): ?>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#ffedd5;color:#c2410c;">
                            <i class="fas fa-map-marker-alt text-xs"></i> Site Visit
                        </span>
                        <?php endif; ?>
                        <?php if ($overdue && !$done): ?>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#fee2e2;color:#dc2626;">
                            <i class="fas fa-exclamation-circle text-xs"></i> Overdue
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Title + desc -->
                    <h3 class="font-bold text-slate-800 text-sm leading-snug"><?php echo htmlspecialchars($task['title']); ?></h3>
                    <?php if (!empty($task['description'])): ?>
                    <p class="text-xs text-slate-500 leading-relaxed line-clamp-2"><?php echo htmlspecialchars($task['description']); ?></p>
                    <?php endif; ?>

                    <!-- Due date -->
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0"
                             style="background:<?php echo $overdue&&!$done?'#fee2e2':'#f1f5f9'; ?>;">
                            <i class="fas fa-calendar text-xs" style="color:<?php echo $overdue&&!$done?'#dc2626':'#94a3b8'; ?>;"></i>
                        </div>
                        <span class="text-xs font-medium" style="color:<?php echo $overdue&&!$done?'#dc2626':'#64748b'; ?>;">
                            Due <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                        </span>
                    </div>

                    <!-- Location (site visit) -->
                    <?php if ($is_site && !empty($task['location'])): ?>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#ffedd5;">
                            <i class="fas fa-map-pin text-xs" style="color:#c2410c;"></i>
                        </div>
                        <span class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($task['location']); ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="flex gap-2 mt-1">
                        <?php if (!$done): ?>
                        <button onclick="openUpdateModal(<?php echo $task['task_id']; ?>, '<?php echo addslashes($task['title']); ?>', '<?php echo htmlspecialchars(addslashes($task['status'])); ?>', '<?php echo htmlspecialchars(addslashes($task['feedback'] ?? '')); ?>')"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 text-xs font-semibold py-2 rounded-xl transition-all"
                                style="background:#e0e7ff;color:#4f46e5;"
                                onmouseover="this.style.background='#c7d2fe'" onmouseout="this.style.background='#e0e7ff'">
                            <i class="fas fa-edit text-xs"></i> Update Status
                        </button>
                        <?php else: ?>
                        <div class="flex-1 inline-flex items-center justify-center gap-1.5 text-xs font-semibold py-2 rounded-xl"
                             style="background:#dcfce7;color:#16a34a;">
                            <i class="fas fa-check text-xs"></i> Completed
                        </div>
                        <?php endif; ?>
                        <a href="submit_report.php?task_id=<?php echo $task['task_id']; ?>"
                           class="flex-1 inline-flex items-center justify-center gap-1.5 text-xs font-semibold py-2 rounded-xl transition-all"
                           style="background:#f1f5f9;color:#64748b;"
                           onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fas fa-file-alt text-xs"></i> Report
                        </a>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /scrollable -->
</div><!-- /main-area -->

<!-- Update Status Modal -->
<div id="updateModal" class="modal-overlay" onclick="handleOverlay(event,'updateModal')">
    <div class="modal-box">
        <div class="flex items-center justify-between px-6 py-5" style="border-bottom:1px solid #f1f5f9;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <i class="fas fa-edit text-white text-xs"></i>
                </div>
                <div>
                    <h2 class="font-bold text-slate-800">Update Task</h2>
                    <p class="text-xs text-slate-400" id="modalTaskTitle"></p>
                </div>
            </div>
            <button onclick="closeModal('updateModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form method="POST" action="my_tasks.php" class="px-6 py-5 space-y-4">
            <input type="hidden" name="task_id" id="modal_task_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Status</label>
                <select name="status" id="modal_status" required class="form-input">
                    <option value="Assigned">Assigned</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Feedback (optional)</label>
                <textarea name="feedback" id="modal_feedback" rows="3" class="form-input" style="resize:none;" placeholder="Add any notes or feedback..."></textarea>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeModal('updateModal')"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500"
                        style="background:#f1f5f9;border:1px solid #e2e8f0;"
                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Cancel</button>
                <button type="submit"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 4px 14px rgba(99,102,241,.35);">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function handleOverlay(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }

function openUpdateModal(taskId, title, status, feedback) {
    document.getElementById('modal_task_id').value  = taskId;
    document.getElementById('modal_status').value   = status;
    document.getElementById('modal_feedback').value = feedback;
    document.getElementById('modalTaskTitle').textContent = title;
    openModal('updateModal');
}

let activeFilter = 'all';
function filterCards(filter, btn) {
    activeFilter = filter;
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#taskGrid .task-card').forEach(card => {
        const match =
            filter === 'all' ||
            (filter === 'sitevisit' && card.dataset.sitevisit === '1') ||
            (filter === 'overdue'   && card.dataset.overdue === '1') ||
            (!['all','sitevisit','overdue'].includes(filter) && card.dataset.status === filter);
        card.style.display = match ? '' : 'none';
    });
}
</script>

</body>
</html>