<?php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}

// Fetch attendance records
$sql = "SELECT a.*, e.name as employee_name, d.name as department_name 
        FROM attendance a 
        JOIN employee e ON a.emp_id = e.emp_id
        JOIN department d ON e.dep_id = d.dep_id
        ORDER BY a.date DESC, a.check_in DESC";
$result = $conn->query($sql);
$attendance_records = [];
if ($result) {
    $attendance_records = $result->fetch_all(MYSQLI_ASSOC);
}

// Summary counts
$total      = count($attendance_records);
$clocked_in = count(array_filter($attendance_records, fn($r) => !$r['check_out']));
$clocked_out= $total - $clocked_in;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Emplify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
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

        .clock-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            border-radius: 20px;
            padding: 28px 32px;
            position: relative;
            overflow: hidden;
        }
        .clock-card::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(99,102,241,0.15);
        }
        .clock-card::after {
            content: '';
            position: absolute;
            bottom: -30px; left: 30px;
            width: 100px; height: 100px;
            border-radius: 50%;
            background: rgba(139,92,246,0.1);
        }

        .table-row {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }
        .table-row:hover { background: #f8fafc; }
        .table-row:last-child { border-bottom: none; }

        .badge-in {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .badge-out {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .avatar {
            width: 34px; height: 34px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 13px; color: #fff;
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

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        
        .date-filter-btn {
            transition: all 0.2s ease;
        }
        .date-filter-btn.active {
            background: #6366f1 !important;
            color: #fff !important;
            border-color: #6366f1 !important;
        }
        
        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-area">

    <?php include '../includes/navbar.php'; ?>

    <div class="flex-1 overflow-y-auto p-6 space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="page-title">Attendance</h1>
            <p class="text-sm text-slate-400 mt-0.5">Track and manage employee attendance records</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-slate-500"
             style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:8px 16px;">
            <i class="fas fa-calendar-day text-indigo-400"></i>
            <span id="header-date">—</span>
        </div>
    </div>

    <!-- Top Row: Clock + Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <!-- Clock Card -->
        <div class="clock-card md:col-span-1">
            <div class="relative z-10">
                <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color: rgba(165,180,252,0.7);">Live Clock</p>
                <div class="text-4xl font-bold text-white tracking-tight mb-1" id="current-time">00:00:00</div>
                <div class="text-sm" style="color: rgba(148,163,184,0.8);" id="current-date">Loading...</div>
                <div class="mt-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse inline-block"></span>
                    <span class="text-xs" style="color: rgba(148,163,184,0.7);">Live tracking active</span>
                </div>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="stat-card flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                 style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe);">
                <i class="fas fa-users text-indigo-600"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total Records</p>
                <p class="text-2xl font-bold text-slate-800" id="statTotal"><?php echo $total; ?></p>
            </div>
        </div>

        <div class="stat-card flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                 style="background: linear-gradient(135deg, #dcfce7, #bbf7d0);">
                <i class="fas fa-sign-in-alt text-emerald-600"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Clocked In</p>
                <p class="text-2xl font-bold text-slate-800" id="statIn"><?php echo $clocked_in; ?></p>
            </div>
        </div>

        <div class="stat-card flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                 style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                <i class="fas fa-sign-out-alt text-red-500"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Clocked Out</p>
                <p class="text-2xl font-bold text-slate-800" id="statOut"><?php echo $clocked_out; ?></p>
            </div>
        </div>

    </div>

    <!-- Attendance Table Card -->
    <div class="bg-white rounded-2xl overflow-hidden" style="border: 1px solid #e2e8f0; box-shadow: 0 2px 12px rgba(0,0,0,0.04);">

        <!-- Table Header -->
        <div class="flex flex-col gap-3 p-5" style="border-bottom: 1px solid #f1f5f9;">

            <!-- Row 1: title + search -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Attendance Records</h2>
                    <p class="text-xs text-slate-400 mt-0.5" id="recordCount"><?php echo $total; ?> entries found</p>
                </div>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Search employees..."
                           class="search-bar pl-9 w-56" oninput="filterTable()">
                </div>
            </div>

            <!-- Row 2: quick date filters -->
            <div class="flex flex-wrap items-center gap-2 pt-2">
                <span class="text-xs font-medium text-slate-400">Quick Date:</span>
                <button onclick="setDateFilter('today', this)" class="date-filter-btn text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all active" style="background:#6366f1; color:#fff; border-color:#6366f1;">Today</button>
                <button onclick="setDateFilter('yesterday', this)" class="date-filter-btn text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all" style="background:#f8fafc; color:#64748b; border-color:#e2e8f0;">Yesterday</button>
                <button onclick="setDateFilter('thisWeek', this)" class="date-filter-btn text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all" style="background:#f8fafc; color:#64748b; border-color:#e2e8f0;">This Week</button>
                <button onclick="setDateFilter('thisMonth', this)" class="date-filter-btn text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all" style="background:#f8fafc; color:#64748b; border-color:#e2e8f0;">This Month</button>
                <button onclick="setDateFilter('lastMonth', this)" class="date-filter-btn text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all" style="background:#f8fafc; color:#64748b; border-color:#e2e8f0;">Last Month</button>
                <button onclick="setDateFilter('all', this)" class="date-filter-btn text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all" style="background:#f8fafc; color:#64748b; border-color:#e2e8f0;">All</button>
            </div>

            <!-- Row 3: custom date range -->
            <div class="flex flex-wrap items-center gap-2 pt-2">
                <span class="text-xs font-medium text-slate-400">Custom Range:</span>
                <input type="date" id="dateFrom" class="search-bar" style="width: auto; padding:7px 12px; border-radius:10px; font-size:12px;" onchange="applyCustomDateRange()">
                <span class="text-xs text-slate-400">to</span>
                <input type="date" id="dateTo" class="search-bar" style="width: auto; padding:7px 12px; border-radius:10px; font-size:12px;" onchange="applyCustomDateRange()">
                <button onclick="clearDateFilter()" id="clearDateBtn" class="hidden text-xs font-semibold px-2.5 py-1.5 rounded-lg" style="background:#fee2e2; color:#dc2626;">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
            </div>

            <!-- Row 4: status filter -->
            <div class="flex items-center gap-2 flex-wrap pt-2">
                <span class="text-xs font-medium text-slate-400">Status:</span>
                <button onclick="setStatusFilter('all', this)" class="status-filter-btn text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all active" style="background:#6366f1; color:#fff; border-color:#6366f1;">All</button>
                <button onclick="setStatusFilter('in', this)" class="status-filter-btn text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all" style="background:#f8fafc; color:#64748b; border-color:#e2e8f0;">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Clocked In
                    </span>
                </button>
                <button onclick="setStatusFilter('out', this)" class="status-filter-btn text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all" style="background:#f8fafc; color:#64748b; border-color:#e2e8f0;">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                        Clocked Out
                    </span>
                </button>
            </div>

        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full" id="attendanceTable">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                        <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Employee</th>
                        <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Department</th>
                        <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Date</th>
                        <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Time In</th>
                        <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Time Out</th>
                        <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $avatar_colors = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#f97316'];
                    foreach ($attendance_records as $i => $record):
                        $color = $avatar_colors[$i % count($avatar_colors)];
                        $initials = strtoupper(substr($record['employee_name'], 0, 1));
                    ?>
                    <tr class="table-row" 
                        data-date="<?php echo $record['date']; ?>"
                        data-status="<?php echo $record['check_out'] ? 'out' : 'in'; ?>">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="avatar" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>; border: 1.5px solid <?php echo $color; ?>30;">
                                    <?php echo $initials; ?>
                                </div>
                                <span class="font-medium text-slate-700 text-sm">
                                    <?php echo htmlspecialchars($record['employee_name']); ?>
                                </span>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="text-sm text-slate-500">
                                <?php echo htmlspecialchars($record['department_name']); ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="text-sm text-slate-600 font-medium">
                                <?php echo date('M d, Y', strtotime($record['date'])); ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="text-sm text-slate-600">
                                <i class="fas fa-arrow-right-to-bracket text-emerald-400 mr-1.5 text-xs"></i>
                                <?php echo date('h:i A', strtotime($record['check_in'])); ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="text-sm text-slate-600">
                                <?php if ($record['check_out']): ?>
                                    <i class="fas fa-arrow-right-from-bracket text-red-400 mr-1.5 text-xs"></i>
                                    <?php echo date('h:i A', strtotime($record['check_out'])); ?>
                                <?php else: ?>
                                    <span class="text-slate-300">—</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <?php if ($record['check_out']): ?>
                                <span class="badge-out inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                    Clocked Out
                                </span>
                            <?php else: ?>
                                <span class="badge-in inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Clocked In
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($attendance_records)): ?>
                    <tr>
                        <td colspan="6" class="p-16 text-center">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3"
                                 style="background: #f1f5f9;">
                                <i class="fas fa-clock text-slate-300 text-xl"></i>
                            </div>
                            <p class="text-slate-400 font-medium">No attendance records found</p>
                            <p class="text-slate-300 text-sm mt-1">Records will appear here once employees clock in</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div><!-- /scrollable content -->
</div><!-- /main-area -->

<script>
// Live clock
function updateClock() {
    const now = new Date();
    const timeEl = document.getElementById('current-time');
    const dateEl = document.getElementById('current-date');
    const headerDate = document.getElementById('header-date');

    if (timeEl) {
        timeEl.textContent = now.toLocaleTimeString('en-US', { hour12: false });
    }
    const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    if (dateEl) dateEl.textContent = dateStr;
    if (headerDate) headerDate.textContent = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
}
updateClock();
setInterval(updateClock, 1000);

// Helper function to format date as YYYY-MM-DD
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Active filters
let activeDateFilter = 'today';
let activeStatusFilter = 'all';

// Set date filter
function setDateFilter(filter, btn) {
    activeDateFilter = filter;
    
    // Update button styles
    document.querySelectorAll('.date-filter-btn').forEach(b => {
        b.style.background = '#f8fafc';
        b.style.color = '#64748b';
        b.style.borderColor = '#e2e8f0';
    });
    btn.style.background = '#6366f1';
    btn.style.color = '#fff';
    btn.style.borderColor = '#6366f1';
    
    // Clear custom date inputs
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('clearDateBtn').classList.add('hidden');
    
    filterTable();
}

// Apply custom date range
function applyCustomDateRange() {
    const fromDate = document.getElementById('dateFrom').value;
    const toDate = document.getElementById('dateTo').value;
    
    if (fromDate || toDate) {
        activeDateFilter = 'custom';
        document.getElementById('clearDateBtn').classList.remove('hidden');
        
        // Reset quick date buttons
        document.querySelectorAll('.date-filter-btn').forEach(b => {
            b.style.background = '#f8fafc';
            b.style.color = '#64748b';
            b.style.borderColor = '#e2e8f0';
        });
    } else {
        document.getElementById('clearDateBtn').classList.add('hidden');
    }
    
    filterTable();
}

// Clear date filter
function clearDateFilter() {
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('clearDateBtn').classList.add('hidden');
    
    // Reset to 'all' filter
    activeDateFilter = 'all';
    
    // Update buttons
    document.querySelectorAll('.date-filter-btn').forEach(b => {
        if (b.textContent.trim() === 'All') {
            b.style.background = '#6366f1';
            b.style.color = '#fff';
            b.style.borderColor = '#6366f1';
        } else {
            b.style.background = '#f8fafc';
            b.style.color = '#64748b';
            b.style.borderColor = '#e2e8f0';
        }
    });
    
    filterTable();
}

// Set status filter
function setStatusFilter(filter, btn) {
    activeStatusFilter = filter;
    
    // Update button styles
    document.querySelectorAll('.status-filter-btn').forEach(b => {
        b.style.background = '#f8fafc';
        b.style.color = '#64748b';
        b.style.borderColor = '#e2e8f0';
    });
    btn.style.background = '#6366f1';
    btn.style.color = '#fff';
    btn.style.borderColor = '#6366f1';
    
    filterTable();
}

// Check if date matches filter
function dateMatchesFilter(dateStr) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const date = new Date(dateStr);
    date.setHours(0, 0, 0, 0);
    
    switch(activeDateFilter) {
        case 'today':
            return date.getTime() === today.getTime();
            
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            return date.getTime() === yesterday.getTime();
            
        case 'thisWeek':
            const weekStart = new Date(today);
            const day = today.getDay();
            const diff = today.getDate() - day + (day === 0 ? -6 : 1);
            weekStart.setDate(diff);
            weekStart.setHours(0, 0, 0, 0);
            return date >= weekStart && date <= today;
            
        case 'thisMonth':
            const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
            return date >= monthStart && date <= today;
            
        case 'lastMonth':
            const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
            return date >= lastMonthStart && date <= lastMonthEnd;
            
        case 'custom':
            const fromDate = document.getElementById('dateFrom').value;
            const toDate = document.getElementById('dateTo').value;
            
            if (fromDate && toDate) {
                return dateStr >= fromDate && dateStr <= toDate;
            } else if (fromDate) {
                return dateStr >= fromDate;
            } else if (toDate) {
                return dateStr <= toDate;
            }
            return true;
            
        case 'all':
        default:
            return true;
    }
}

// Animate number changes
function animateNumber(element, newValue) {
    if (!element) return;
    
    const startValue = parseInt(element.textContent) || 0;
    if (startValue === newValue) return;
    
    const duration = 300;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const currentValue = Math.round(startValue + (newValue - startValue) * progress);
        element.textContent = currentValue;
        
        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.textContent = newValue;
        }
    }
    
    requestAnimationFrame(update);
}

// Master filter function
function filterTable() {
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#attendanceTable tbody tr[data-date]');
    
    let total = 0;
    let clockedIn = 0;
    let clockedOut = 0;
    
    rows.forEach(row => {
        const date = row.getAttribute('data-date');
        const status = row.getAttribute('data-status');
        const text = row.textContent.toLowerCase();
        
        const matchesSearch = text.includes(searchQuery);
        const matchesDate = dateMatchesFilter(date);
        const matchesStatus = activeStatusFilter === 'all' || status === activeStatusFilter;
        
        const visible = matchesSearch && matchesDate && matchesStatus;
        
        row.style.display = visible ? '' : 'none';
        
        if (visible) {
            total++;
            if (status === 'in') clockedIn++;
            else clockedOut++;
        }
    });
    
    // Update record count
    const recordCountEl = document.getElementById('recordCount');
    if (recordCountEl) {
        recordCountEl.textContent = total + ' entr' + (total === 1 ? 'y' : 'ies') + ' found';
    }
    
    // Update stat cards with animation
    animateNumber(document.getElementById('statTotal'), total);
    animateNumber(document.getElementById('statIn'), clockedIn);
    animateNumber(document.getElementById('statOut'), clockedOut);
}

// Initialize with today's filter
window.addEventListener('DOMContentLoaded', () => {
    const todayBtn = document.querySelector('.date-filter-btn');
    if (todayBtn) {
        setDateFilter('today', todayBtn);
    }
});
</script>

</body>
</html>