<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}

// Handle approve/reject actions
if (isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $status     = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $stmt = $conn->prepare("UPDATE leave_management SET status = ? WHERE request_id = ?");
    $stmt->bind_param('si', $status, $request_id);
    $stmt->execute();
    header("Location: leave_approval.php");
    exit();
}

// Fetch all leave requests
$sql = "SELECT lr.*, e.name as employee_name, e.emp_id 
        FROM leave_management lr 
        JOIN employee e ON lr.emp_id = e.emp_id 
        ORDER BY lr.start_date DESC";
$result   = $conn->query($sql);
$requests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$total    = count($requests);
$pending  = count(array_filter($requests, fn($r) => strtolower(trim($r['status'])) === 'pending'));
$approved = count(array_filter($requests, fn($r) => strtolower(trim($r['status'])) === 'approved'));
$rejected = count(array_filter($requests, fn($r) => strtolower(trim($r['status'])) === 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Approval - Emplify</title>
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
            border-radius: 16px;
            padding: 18px 22px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            transform: translateY(-2px);
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
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px; font-weight: 600;
            transition: background 0.15s, transform 0.15s;
            text-decoration: none;
        }
        .btn-action:hover { transform: scale(1.04); }

        /* Filter tabs */
        .filter-tab {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 12px; font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            border: 1px solid transparent;
        }
        .filter-tab.active {
            background: #6366f1;
            color: #fff;
            border-color: #6366f1;
        }
        .filter-tab:not(.active) {
            background: #f8fafc;
            color: #64748b;
            border-color: #e2e8f0;
        }
        .filter-tab:not(.active):hover { background: #e0e7ff; color: #6366f1; border-color: #c7d2fe; }
    </style>
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-white"
         style="border-bottom: 1px solid #f1f5f9; min-height: 64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing: -0.2px;">Leave Requests</h1>
            <p class="text-xs text-slate-400 mt-0.5">Review and manage employee leave applications</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-slate-500 px-4 py-2 rounded-xl bg-white"
             style="border: 1px solid #e2e8f0;">
            <i class="fas fa-calendar-day text-indigo-400"></i>
            <span><?php echo date('D, M d Y'); ?></span>
        </div>
    </div>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">

            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg,#e0e7ff,#c7d2fe);">
                    <i class="fas fa-list text-indigo-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $total; ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg,#fef9c3,#fde68a);">
                    <i class="fas fa-hourglass-half text-amber-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Pending</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $pending; ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg,#dcfce7,#bbf7d0);">
                    <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Approved</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $approved; ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg,#fee2e2,#fecaca);">
                    <i class="fas fa-times-circle text-red-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Rejected</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $rejected; ?></p>
                </div>
            </div>

        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-2xl overflow-hidden"
             style="border: 1px solid #e2e8f0; box-shadow: 0 2px 12px rgba(0,0,0,0.04);">

            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-5"
                 style="border-bottom: 1px solid #f1f5f9;">
                <div class="flex items-center gap-2 flex-wrap">
                    <button class="filter-tab active" onclick="filterByStatus('all', this)">All</button>
                    <button class="filter-tab" onclick="filterByStatus('pending', this)">Pending</button>
                    <button class="filter-tab" onclick="filterByStatus('approved', this)">Approved</button>
                    <button class="filter-tab" onclick="filterByStatus('rejected', this)">Rejected</button>
                </div>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Search employees..."
                           class="search-bar pl-9 w-52" oninput="filterTable()">
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full" id="leaveTable">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Employee</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Duration</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Days</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Reason</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leaveTableBody">
                        <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3"
                                     style="background: #f1f5f9;">
                                    <i class="fas fa-calendar-check text-slate-300 text-xl"></i>
                                </div>
                                <p class="text-slate-400 font-medium">No leave requests found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php
                        $avatar_colors = [
                            ['bg'=>'#e0e7ff20','text'=>'#6366f1','border'=>'#6366f130'],
                            ['bg'=>'#ede9fe20','text'=>'#8b5cf6','border'=>'#8b5cf630'],
                            ['bg'=>'#fce7f320','text'=>'#ec4899','border'=>'#ec489930'],
                            ['bg'=>'#fef9c320','text'=>'#f59e0b','border'=>'#f59e0b30'],
                            ['bg'=>'#dcfce720','text'=>'#10b981','border'=>'#10b98130'],
                            ['bg'=>'#dbeafe20','text'=>'#3b82f6','border'=>'#3b82f630'],
                            ['bg'=>'#ffedd520','text'=>'#f97316','border'=>'#f9731630'],
                        ];
                        foreach ($requests as $i => $row):
                            $status  = strtolower(trim($row['status']));
                            $ac      = $avatar_colors[$i % count($avatar_colors)];
                            $initial = strtoupper(substr($row['employee_name'], 0, 1));
                            $start   = new DateTime($row['start_date']);
                            $end     = new DateTime($row['end_date']);
                            $days    = $start->diff($end)->days + 1;
                        ?>
                        <tr class="table-row" data-status="<?php echo $status; ?>">

                            <!-- Employee -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="avatar"
                                         style="background:<?php echo $ac['bg']; ?>;color:<?php echo $ac['text']; ?>;border:1.5px solid <?php echo $ac['border']; ?>;">
                                        <?php echo $initial; ?>
                                    </div>
                                    <span class="font-medium text-slate-700 text-sm">
                                        <?php echo htmlspecialchars($row['employee_name']); ?>
                                    </span>
                                </div>
                            </td>

                            <!-- Duration -->
                            <td class="p-4">
                                <div class="text-sm text-slate-700 font-medium">
                                    <?php echo date('M d', strtotime($row['start_date'])); ?>
                                    <span class="text-slate-400 mx-1">→</span>
                                    <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                </div>
                            </td>

                            <!-- Days -->
                            <td class="p-4">
                                <span class="text-sm font-semibold px-2.5 py-1 rounded-full"
                                      style="background:#f1f5f9; color:#64748b;">
                                    <?php echo $days; ?>d
                                </span>
                            </td>

                            <!-- Reason -->
                            <td class="p-4" style="max-width: 200px;">
                                <p class="text-sm text-slate-500 truncate" title="<?php echo htmlspecialchars($row['reason']); ?>">
                                    <?php echo htmlspecialchars($row['reason']); ?>
                                </p>
                            </td>

                            <!-- Status badge -->
                            <td class="p-4">
                                <?php if ($status === 'pending'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                          style="background:#fef9c3; color:#d97706; border:1px solid #fde68a;">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                        Pending
                                    </span>
                                <?php elseif ($status === 'approved'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                          style="background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0;">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        Approved
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                          style="background:#fee2e2; color:#dc2626; border:1px solid #fecaca;">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                        Rejected
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="p-4">
                                <?php if ($status === 'pending'): ?>
                                <div class="flex items-center gap-2">
                                    <a href="accept_leave.php?leave_id=<?php echo $row['leave_id']; ?>"
                                       class="btn-action"
                                       style="background:#dcfce7; color:#16a34a;"
                                       onmouseover="this.style.background='#bbf7d0'"
                                       onmouseout="this.style.background='#dcfce7'">
                                        <i class="fas fa-check text-xs"></i> Accept
                                    </a>
                                    <a href="reject_leave.php?leave_id=<?php echo $row['leave_id']; ?>"
                                       class="btn-action"
                                       style="background:#fee2e2; color:#dc2626;"
                                       onmouseover="this.style.background='#fecaca'"
                                       onmouseout="this.style.background='#fee2e2'">
                                        <i class="fas fa-times text-xs"></i> Reject
                                    </a>
                                </div>
                                <?php else: ?>
                                    <span class="text-slate-300 text-sm">—</span>
                                <?php endif; ?>
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

<script>
/* Search filter */
function filterTable() {
    const query  = document.getElementById('searchInput').value.toLowerCase();
    const rows   = document.querySelectorAll('#leaveTableBody tr[data-status]');
    const active = document.querySelector('.filter-tab.active')?.dataset.status || 'all';
    rows.forEach(row => {
        const matchSearch = row.textContent.toLowerCase().includes(query);
        const matchStatus = active === 'all' || row.dataset.status === active;
        row.style.display = matchSearch && matchStatus ? '' : 'none';
    });
}

/* Status filter tabs */
function filterByStatus(status, btn) {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    btn.dataset.status = status;
    filterTable();
}
</script>

</body>
</html>