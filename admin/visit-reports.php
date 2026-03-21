<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}

$query = "
    SELECT
        employee.name        AS employee_name,
        task_assigned.title  AS task_title,
        reports.title        AS project_title,
        reports.description  AS description,
        reports.created_at   AS submitted_on,
        GROUP_CONCAT(report_images.image_path ORDER BY report_images.image_id SEPARATOR ',') AS image_paths
    FROM task_assigned
    INNER JOIN employee ON employee.emp_id = task_assigned.emp_id
    INNER JOIN reports  ON reports.emp_id  = task_assigned.emp_id
    LEFT  JOIN report_images ON report_images.report_id = reports.report_id
    GROUP BY reports.report_id, task_assigned.task_id
    ORDER BY reports.created_at DESC
";

$result  = mysqli_query($conn, $query);
if (!$result) die("Query failed: " . mysqli_error($conn));

$reports = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reports[] = $row;
}
$total = count($reports);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visit Reports - Emplify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
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

        /* Report card */
        .report-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .report-card:hover {
            box-shadow: 0 10px 36px rgba(0,0,0,0.09);
            transform: translateY(-2px);
        }

        /* Image grid */
        .img-thumb {
            width: 100%; aspect-ratio: 4/3;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e2e8f0;
        }
        .img-thumb:hover {
            transform: scale(1.03);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }

        /* Search and filter */
        .search-bar {
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 10px 16px;
            font-size: 14px; color: #334155; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-bar:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }

        /* Date filter buttons */
        .date-filter-btn {
            padding: 6px 14px; border-radius: 10px;
            font-size: 12px; font-weight: 600; cursor: pointer;
            transition: all 0.15s;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
        }
        .date-filter-btn.active {
            background: #6366f1 !important;
            color: #fff !important;
            border-color: #6366f1 !important;
        }
        .date-filter-btn:not(.active):hover {
            background: #e0e7ff;
            color: #6366f1;
            border-color: #c7d2fe;
        }

        /* Avatar colors cycling */
        .avatar {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px; flex-shrink: 0;
        }

        /* Lightbox override */
        .fancybox__container { z-index: 9999 !important; }
        
        .hidden { display: none !important; }
        
        /* Month picker styling */
        .month-picker {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 7px 12px;
            font-size: 12px;
            color: #334155;
            outline: none;
        }
        .month-picker:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
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
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing: -0.2px;">Visit Reports</h1>
            <p class="text-xs text-slate-400 mt-0.5">Employee field visit submissions</p>
        </div>
        <div class="flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-xl bg-white"
             style="border: 1px solid #e2e8f0;">
            <span class="w-2 h-2 rounded-full bg-indigo-400 inline-block"></span>
            <span class="text-slate-600" id="totalReports"><?php echo $total; ?></span>
            <span id="reportLabel">report<?php echo $total !== 1 ? 's' : ''; ?></span>
        </div>
    </div>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <!-- Filter Section -->
        <div class="bg-white px-5 py-4 rounded-2xl space-y-4" style="border: 1px solid #e2e8f0;">
            
            <!-- Date Filters -->
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-wide">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Date Filter</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button class="date-filter-btn active" onclick="setDateFilter('all', this)">All Reports</button>
                    <button class="date-filter-btn" onclick="setDateFilter('today', this)">Today</button>
                    <button class="date-filter-btn" onclick="setDateFilter('yesterday', this)">Yesterday</button>
                    <button class="date-filter-btn" onclick="setDateFilter('thisWeek', this)">This Week</button>
                    <button class="date-filter-btn" onclick="setDateFilter('lastWeek', this)">Last Week</button>
                    <button class="date-filter-btn" onclick="setDateFilter('thisMonth', this)">This Month</button>
                    <button class="date-filter-btn" onclick="setDateFilter('lastMonth', this)">Last Month</button>
                    <button class="date-filter-btn" onclick="setDateFilter('thisYear', this)">This Year</button>
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
            
            <!-- Month/Year Picker -->
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-xs font-medium text-slate-400 uppercase tracking-wide">
                    <i class="fas fa-calendar"></i>
                    <span>Quick Month/Year</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select id="monthSelect" class="month-picker" onchange="applyMonthYearFilter()">
                        <option value="">Select Month</option>
                        <option value="01">January</option>
                        <option value="02">February</option>
                        <option value="03">March</option>
                        <option value="04">April</option>
                        <option value="05">May</option>
                        <option value="06">June</option>
                        <option value="07">July</option>
                        <option value="08">August</option>
                        <option value="09">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                    <select id="yearSelect" class="month-picker" onchange="applyMonthYearFilter()">
                        <option value="">Select Year</option>
                        <?php
                        $currentYear = date('Y');
                        for ($year = $currentYear; $year >= $currentYear - 5; $year--):
                        ?>
                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <!-- Search -->
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                <input type="text" id="searchInput" placeholder="Search employee, project, or task..."
                       class="search-bar pl-9 w-full" oninput="filterReports()">
            </div>
        </div>

        <?php if (empty($reports)): ?>
        <!-- Empty state -->
        <div class="flex flex-col items-center justify-center py-24">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                 style="background: #f1f5f9;">
                <i class="fas fa-chart-bar text-slate-300 text-2xl"></i>
            </div>
            <p class="text-slate-400 font-medium">No visit reports found</p>
            <p class="text-slate-300 text-sm mt-1">Reports will appear here once employees submit them</p>
        </div>

        <?php else: ?>
        <?php
        $avatar_colors = [
            ['bg'=>'#e0e7ff','text'=>'#6366f1'],
            ['bg'=>'#ede9fe','text'=>'#8b5cf6'],
            ['bg'=>'#fce7f3','text'=>'#ec4899'],
            ['bg'=>'#fef9c3','text'=>'#d97706'],
            ['bg'=>'#dcfce7','text'=>'#10b981'],
            ['bg'=>'#dbeafe','text'=>'#3b82f6'],
            ['bg'=>'#ffedd5','text'=>'#f97316'],
        ];
        ?>

        <!-- Report grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5" id="reportGrid">
            <?php foreach ($reports as $i => $row):
                $ac      = $avatar_colors[$i % count($avatar_colors)];
                $initial = strtoupper(substr($row['employee_name'], 0, 1));
                $images  = array_filter(array_map('trim', explode(',', $row['image_paths'] ?? '')));
                $date    = !empty($row['submitted_on']) ? date('M d, Y', strtotime($row['submitted_on'])) : 'Unknown date';
                $fullDate = $row['submitted_on'] ?? '';
            ?>
            <div class="report-card"
                 data-search="<?php echo strtolower(htmlspecialchars($row['employee_name'].' '.$row['project_title'].' '.$row['task_title'])); ?>"
                 data-date="<?php echo $fullDate; ?>">

                <!-- Top accent -->
                <div class="h-1 w-full" style="background: linear-gradient(90deg, <?php echo $ac['text']; ?>, <?php echo $ac['text']; ?>88);"></div>

                <div class="p-5 space-y-4">

                    <!-- Header: employee + date -->
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="avatar" style="background:<?php echo $ac['bg']; ?>; color:<?php echo $ac['text']; ?>;">
                                <?php echo $initial; ?>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">
                                    <?php echo htmlspecialchars($row['employee_name']); ?>
                                </p>
                                <p class="text-xs text-slate-400">
                                    <i class="fas fa-clock mr-1"></i><?php echo $date; ?>
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                              style="background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0;">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Submitted
                        </span>
                    </div>

                    <!-- Divider -->
                    <div style="border-top: 1px solid #f1f5f9;"></div>

                    <!-- Project & Task info -->
                    <div class="space-y-2">
                        <div class="flex items-start gap-2">
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                 style="background:<?php echo $ac['bg']; ?>;">
                                <i class="fas fa-folder text-xs" style="color:<?php echo $ac['text']; ?>;"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Project</p>
                                <p class="text-sm font-semibold text-slate-700">
                                    <?php echo htmlspecialchars($row['project_title']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                 style="background:#f1f5f9;">
                                <i class="fas fa-tasks text-xs text-slate-400"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Task</p>
                                <p class="text-sm text-slate-600"><?php echo htmlspecialchars($row['task_title']); ?></p>
                            </div>
                        </div>

                        <?php if (!empty($row['description'])): ?>
                        <div class="flex items-start gap-2">
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                 style="background:#f1f5f9;">
                                <i class="fas fa-align-left text-xs text-slate-400"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Description</p>
                                <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Images -->
                    <?php if (!empty($images)): ?>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">
                            <i class="fas fa-images mr-1"></i>Photos (<?php echo count($images); ?>)
                        </p>
                        <div class="grid <?php echo count($images) === 1 ? 'grid-cols-1' : (count($images) === 2 ? 'grid-cols-2' : 'grid-cols-3'); ?> gap-2">
                            <?php foreach (array_slice($images, 0, 6) as $j => $img):
                                $imgPath = '../employee/' . ltrim($img, '/');
                                $imgSrc  = htmlspecialchars($imgPath);
                            ?>
                            <?php if ($j === 5 && count($images) > 6): ?>
                            <!-- +more overlay on last visible -->
                            <a href="<?php echo $imgSrc; ?>"
                               data-fancybox="gallery-<?php echo $i; ?>"
                               class="relative block">
                                <img src="<?php echo $imgSrc; ?>" alt="Report image" class="img-thumb">
                                <div class="absolute inset-0 rounded-xl flex items-center justify-center text-white font-bold text-sm"
                                     style="background:rgba(0,0,0,0.55);">
                                    +<?php echo count($images) - 5; ?>
                                </div>
                            </a>
                            <?php else: ?>
                            <a href="<?php echo $imgSrc; ?>"
                               data-fancybox="gallery-<?php echo $i; ?>"
                               data-caption="<?php echo htmlspecialchars($row['project_title']); ?>">
                                <img src="<?php echo $imgSrc; ?>" alt="Report image" class="img-thumb">
                            </a>
                            <?php endif; ?>
                            <?php endforeach; ?>
                            <!-- Hidden remaining images for lightbox -->
                            <?php foreach (array_slice($images, 6) as $img):
                                $imgPath = '../employee/' . ltrim($img, '/');
                                $imgSrc  = htmlspecialchars($imgPath);
                            ?>
                            <a href="<?php echo $imgSrc; ?>" data-fancybox="gallery-<?php echo $i; ?>" class="hidden"
                               data-caption="<?php echo htmlspecialchars($row['project_title']); ?>"></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-xs text-slate-400"
                         style="background:#f8fafc; border:1px dashed #e2e8f0;">
                        <i class="fas fa-image text-slate-300"></i>
                        No photos attached to this report
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /scrollable -->
</div><!-- /main-area -->

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script>
Fancybox.bind('[data-fancybox]', {
    Toolbar: { display: { left: ['infobar'], middle: [], right: ['close'] } }
});

// Date filter variables
let activeDateFilter = 'all';
let customDateFrom = '';
let customDateTo = '';
let selectedMonth = '';
let selectedYear = '';

// Set date filter
function setDateFilter(filter, btn) {
    activeDateFilter = filter;
    customDateFrom = '';
    customDateTo = '';
    selectedMonth = '';
    selectedYear = '';
    
    // Reset month/year selects
    document.getElementById('monthSelect').value = '';
    document.getElementById('yearSelect').value = '';
    
    // Update button styles
    document.querySelectorAll('.date-filter-btn').forEach(b => {
        b.classList.remove('active');
    });
    if (btn) btn.classList.add('active');
    
    // Clear custom date inputs
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('clearDateBtn').classList.add('hidden');
    
    filterReports();
}

// Apply custom date range
function applyCustomDateRange() {
    customDateFrom = document.getElementById('dateFrom').value;
    customDateTo = document.getElementById('dateTo').value;
    selectedMonth = '';
    selectedYear = '';
    
    // Reset month/year selects
    document.getElementById('monthSelect').value = '';
    document.getElementById('yearSelect').value = '';
    
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
    
    filterReports();
}

// Apply month/year filter
function applyMonthYearFilter() {
    selectedMonth = document.getElementById('monthSelect').value;
    selectedYear = document.getElementById('yearSelect').value;
    
    if (selectedMonth || selectedYear) {
        activeDateFilter = 'monthYear';
        
        // Reset quick date buttons
        document.querySelectorAll('.date-filter-btn').forEach(b => {
            b.classList.remove('active');
        });
        
        // Clear custom date inputs
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value = '';
        document.getElementById('clearDateBtn').classList.remove('hidden');
    } else {
        if (!customDateFrom && !customDateTo) {
            document.getElementById('clearDateBtn').classList.add('hidden');
        }
    }
    
    filterReports();
}

// Clear date filter
function clearDateFilter() {
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('monthSelect').value = '';
    document.getElementById('yearSelect').value = '';
    document.getElementById('clearDateBtn').classList.add('hidden');
    
    customDateFrom = '';
    customDateTo = '';
    selectedMonth = '';
    selectedYear = '';
    activeDateFilter = 'all';
    
    // Update buttons
    document.querySelectorAll('.date-filter-btn').forEach(b => {
        if (b.textContent.trim() === 'All Reports') {
            b.classList.add('active');
        } else {
            b.classList.remove('active');
        }
    });
    
    filterReports();
}

// Check if date matches filter
function dateMatchesFilter(dateStr) {
    if (!dateStr) return false;
    
    const date = new Date(dateStr);
    date.setHours(0, 0, 0, 0);
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Yesterday
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    // This week start (Monday)
    const weekStart = new Date(today);
    const day = today.getDay();
    const diff = today.getDate() - day + (day === 0 ? -6 : 1);
    weekStart.setDate(diff);
    weekStart.setHours(0, 0, 0, 0);
    
    // Last week
    const lastWeekStart = new Date(weekStart);
    lastWeekStart.setDate(lastWeekStart.getDate() - 7);
    const lastWeekEnd = new Date(weekStart);
    lastWeekEnd.setDate(lastWeekEnd.getDate() - 1);
    
    // This month start
    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
    
    // Last month
    const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
    
    // This year start
    const yearStart = new Date(today.getFullYear(), 0, 1);
    
    switch(activeDateFilter) {
        case 'today':
            return date.getTime() === today.getTime();
            
        case 'yesterday':
            return date.getTime() === yesterday.getTime();
            
        case 'thisWeek':
            return date >= weekStart && date <= today;
            
        case 'lastWeek':
            return date >= lastWeekStart && date <= lastWeekEnd;
            
        case 'thisMonth':
            return date >= monthStart && date <= today;
            
        case 'lastMonth':
            return date >= lastMonthStart && date <= lastMonthEnd;
            
        case 'thisYear':
            return date >= yearStart && date <= today;
            
        case 'custom':
            if (customDateFrom && customDateTo) {
                return dateStr >= customDateFrom && dateStr <= customDateTo;
            } else if (customDateFrom) {
                return dateStr >= customDateFrom;
            } else if (customDateTo) {
                return dateStr <= customDateTo;
            }
            return true;
            
        case 'monthYear':
            const reportDate = new Date(dateStr);
            const reportMonth = String(reportDate.getMonth() + 1).padStart(2, '0');
            const reportYear = reportDate.getFullYear().toString();
            
            let monthMatch = !selectedMonth || reportMonth === selectedMonth;
            let yearMatch = !selectedYear || reportYear === selectedYear;
            
            return monthMatch && yearMatch;
            
        case 'all':
        default:
            return true;
    }
}

// Filter reports
function filterReports() {
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('#reportGrid .report-card');
    
    let visibleCount = 0;
    
    cards.forEach(card => {
        const searchText = card.dataset.search;
        const dateStr = card.dataset.date;
        
        const searchMatch = searchText.includes(searchQuery);
        const dateMatch = dateMatchesFilter(dateStr);
        
        const visible = searchMatch && dateMatch;
        
        card.style.display = visible ? '' : 'none';
        
        if (visible) visibleCount++;
    });
    
    // Update total count
    document.getElementById('totalReports').textContent = visibleCount;
    document.getElementById('reportLabel').textContent = visibleCount === 1 ? 'report' : 'reports';
    
    // Show empty state message if no cards visible
    const reportGrid = document.getElementById('reportGrid');
    let emptyMessage = document.getElementById('emptyFilterMessage');
    
    if (visibleCount === 0 && cards.length > 0) {
        if (!emptyMessage) {
            emptyMessage = document.createElement('div');
            emptyMessage.id = 'emptyFilterMessage';
            emptyMessage.className = 'flex flex-col items-center justify-center py-16 col-span-full';
            emptyMessage.innerHTML = `
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background:#f1f5f9;">
                    <i class="fas fa-filter text-slate-300 text-2xl"></i>
                </div>
                <p class="text-slate-400 font-medium">No reports match your filters</p>
                <p class="text-slate-300 text-sm mt-1">Try adjusting your date range or search criteria</p>
            `;
            reportGrid.parentNode.insertBefore(emptyMessage, reportGrid.nextSibling);
        }
        emptyMessage.style.display = 'flex';
    } else {
        if (emptyMessage) emptyMessage.style.display = 'none';
    }
}

// Initialize with all reports
window.addEventListener('DOMContentLoaded', () => {
    const allReportsBtn = document.querySelector('.date-filter-btn');
    if (allReportsBtn) {
        setDateFilter('all', allReportsBtn);
    }
});
</script>

</body>
</html>