<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['emp_id'])) {
    header('Location: login.html');
    exit();
}

$emp_id        = $_SESSION['emp_id'];
$current_month = date('Y-m');
$month_label   = date('F Y');

// Get attendance for current month
$stmt = $conn->prepare("
    SELECT date, check_in, check_out,
           TIMEDIFF(check_out, check_in) as hours_worked
    FROM attendance
    WHERE emp_id = ?
    AND DATE_FORMAT(date, '%Y-%m') = ?
    ORDER BY date DESC
");
$stmt->bind_param('is', $emp_id, $current_month);
$stmt->execute();
$result = $stmt->get_result();
$records = $result->fetch_all(MYSQLI_ASSOC);

$total_days   = count($records);
$present_days = count(array_filter($records, fn($r) => !empty($r['check_out'])));
$incomplete   = $total_days - $present_days;

// Calculate total hours
$total_seconds = 0;
foreach ($records as $r) {
    if ($r['hours_worked'] && $r['hours_worked'] !== '00:00:00') {
        list($h, $m, $s) = explode(':', $r['hours_worked']);
        $total_seconds += ($h * 3600) + ($m * 60) + $s;
    }
}
$total_hours = sprintf('%02d:%02d', floor($total_seconds / 3600), floor(($total_seconds % 3600) / 60));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Emplify</title>
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
            border-radius: 16px; padding: 18px 22px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.08); transform: translateY(-2px); }

        .table-row { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
        .table-row:hover { background: #f8fafc; }
        .table-row:last-child { border-bottom: none; }

        .search-bar {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 10px 16px; font-size: 14px; color: #334155; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-bar:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
    </style>
</head>
<body>

<?php include 'employee_sidebar.php'; ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-white"
         style="border-bottom: 1px solid #f1f5f9; min-height: 64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing:-0.2px;">My Attendance</h1>
            <p class="text-xs text-slate-400 mt-0.5"><?php echo $month_label; ?></p>
        </div>
        <div class="flex items-center gap-2 text-sm text-slate-500 px-4 py-2 rounded-xl bg-white"
             style="border:1px solid #e2e8f0;">
            <i class="fas fa-calendar text-indigo-400"></i>
            <span><?php echo $month_label; ?></span>
        </div>
    </div>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">

            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe);">
                    <i class="fas fa-calendar-check text-indigo-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total Days</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $total_days; ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                    <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Full Days</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $present_days; ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#fef9c3,#fde68a);">
                    <i class="fas fa-exclamation-circle text-amber-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Incomplete</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $incomplete; ?></p>
                </div>
            </div>

            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);">
                    <i class="fas fa-hourglass-half text-violet-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total Hours</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $total_hours; ?></p>
                </div>
            </div>

        </div>

        <!-- Attendance Table -->
        <div class="bg-white rounded-2xl overflow-hidden"
             style="border:1px solid #e2e8f0; box-shadow:0 2px 12px rgba(0,0,0,0.04);">

            <!-- Table toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-5"
                 style="border-bottom:1px solid #f1f5f9;">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Attendance Records</h2>
                    <p class="text-xs text-slate-400 mt-0.5" id="recordCount"><?php echo $total_days; ?> records this month</p>
                </div>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Search by date..."
                           class="search-bar pl-9 w-52" oninput="filterTable()">
                </div>
            </div>

            <!-- Table -->
            <?php if (empty($records)): ?>
            <div class="flex flex-col items-center justify-center py-20">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4"
                     style="background:#f1f5f9;">
                    <i class="fas fa-calendar-times text-slate-300 text-xl"></i>
                </div>
                <p class="text-slate-400 font-medium">No records for <?php echo $month_label; ?></p>
                <p class="text-slate-300 text-sm mt-1">Your attendance will appear here once you clock in</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full" id="attendanceTable">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Date</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Day</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Check In</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Check Out</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Hours</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $row):
                            $complete = !empty($row['check_out']);
                            $hours    = $row['hours_worked'] ?? null;
                            $isToday  = $row['date'] === date('Y-m-d');
                        ?>
                        <tr class="table-row" data-search="<?php echo strtolower(date('d M Y', strtotime($row['date']))); ?>">

                            <!-- Date -->
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <?php if ($isToday): ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse inline-block"></span>
                                    <?php endif; ?>
                                    <span class="text-sm font-semibold <?php echo $isToday ? 'text-indigo-600' : 'text-slate-700'; ?>">
                                        <?php echo date('d M Y', strtotime($row['date'])); ?>
                                    </span>
                                </div>
                            </td>

                            <!-- Day -->
                            <td class="p-4">
                                <span class="text-xs font-medium text-slate-400">
                                    <?php echo date('l', strtotime($row['date'])); ?>
                                </span>
                            </td>

                            <!-- Check In -->
                            <td class="p-4">
                                <span class="text-sm text-slate-600">
                                    <i class="fas fa-arrow-right-to-bracket text-emerald-400 mr-1.5 text-xs"></i>
                                    <?php echo date('h:i A', strtotime($row['check_in'])); ?>
                                </span>
                            </td>

                            <!-- Check Out -->
                            <td class="p-4">
                                <?php if ($complete): ?>
                                <span class="text-sm text-slate-600">
                                    <i class="fas fa-arrow-right-from-bracket text-red-400 mr-1.5 text-xs"></i>
                                    <?php echo date('h:i A', strtotime($row['check_out'])); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full"
                                      style="background:#fef9c3; color:#d97706;">Not checked out</span>
                                <?php endif; ?>
                            </td>

                            <!-- Hours -->
                            <td class="p-4">
                                <?php if ($hours && $hours !== '00:00:00'): ?>
                                <span class="text-sm font-semibold text-slate-700">
                                    <?php echo substr($hours, 0, 5); ?>h
                                </span>
                                <?php else: ?>
                                <span class="text-slate-300 text-sm">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status -->
                            <td class="p-4">
                                <?php if ($complete): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                      style="background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0;">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    Complete
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                      style="background:#fef9c3; color:#d97706; border:1px solid #fde68a;">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                    In Progress
                                </span>
                                <?php endif; ?>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /scrollable -->
</div><!-- /main-area -->

<script>
function filterTable() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const rows  = document.querySelectorAll('#attendanceTable tbody tr');
    let visible = 0;
    rows.forEach(row => {
        const show = row.dataset.search.includes(query) || row.textContent.toLowerCase().includes(query);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const c = document.getElementById('recordCount');
    if (c) c.textContent = visible + ' record' + (visible === 1 ? '' : 's') + ' this month';
}
</script>

</body>
</html>