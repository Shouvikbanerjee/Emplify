<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['emp_id'])) {
    header('Location: login.html');
    exit();
}

$emp_id = $_SESSION['emp_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];
    $leave_type = $_POST['leave_type'];
    $reason     = $_POST['reason'];

    // 🔹 Validate dates
    if (empty($start_date) || empty($end_date)) {
        $_SESSION['error_message'] = "Please select valid dates!";
        header('Location: my_leaves.php');
        exit();
    }

    // 🔹 Calculate total leave days
    $start = new DateTime($start_date);
    $end   = new DateTime($end_date);
    $end->modify('+1 day'); // include end date
    $interval = $start->diff($end);
    $days = $interval->days;

    if ($days <= 0) {
        $_SESSION['error_message'] = "Invalid date range!";
        header('Location: my_leaves.php');
        exit();
    }

    // 🔹 Get current leave balance
    $stmt = $conn->prepare("SELECT leave_balance FROM employee WHERE emp_id=?");
    $stmt->bind_param("i", $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $emp = $result->fetch_assoc();

    if ($emp['leave_balance'] < $days) {
        $_SESSION['error_message'] = "Not enough leave balance!";
        header('Location: my_leaves.php');
        exit();
    }

    // 🔹 Insert leave request
    $stmt = $conn->prepare("INSERT INTO leave_management 
        (emp_id, start_date, end_date, leave_type, reason, status) 
        VALUES (?, ?, ?, ?, ?, 'Pending')");
        
    $stmt->bind_param('issss', $emp_id, $start_date, $end_date, $leave_type, $reason);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Leave request submitted successfully!";
    } else {
        $_SESSION['error_message'] = "Error submitting leave request!";
    }

    header('Location: my_leaves.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM leave_management WHERE emp_id = ? ORDER BY start_date DESC");
$stmt->bind_param('i', $emp_id);
$stmt->execute();
$result   = $stmt->get_result();
$leaves   = $result->fetch_all(MYSQLI_ASSOC);
$total    = count($leaves);
$pending  = count(array_filter($leaves, fn($l) => strtolower($l['status']) === 'pending'));
$approved = count(array_filter($leaves, fn($l) => strtolower($l['status']) === 'approved'));
$rejected = count(array_filter($leaves, fn($l) => strtolower($l['status']) === 'rejected'));

$stmt = $conn->prepare("SELECT leave_balance FROM employee WHERE emp_id = ?");
$stmt->bind_param('i', $emp_id);
$stmt->execute();
$balRow  = $stmt->get_result()->fetch_assoc();
$balance = $balRow['leave_balance'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests - Emplify</title>
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
        .table-row { border-bottom:1px solid #f1f5f9; transition:background .15s; }
        .table-row:hover { background:#f8fafc; }
        .table-row:last-child { border-bottom:none; }
        .modal-overlay { display:none; position:fixed; inset:0; z-index:100; background:rgba(15,23,42,0.5); backdrop-filter:blur(2px); align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:20px; width:100%; max-width:480px; margin:16px; box-shadow:0 24px 60px rgba(0,0,0,0.18); animation:modalIn .2s ease; }
        @keyframes modalIn { from{opacity:0;transform:scale(.96) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }
        .form-input { width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:12px; font-size:14px; color:#334155; outline:none; transition:border-color .2s,box-shadow .2s; font-family:'DM Sans',sans-serif; }
        .form-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
    </style>
</head>
<body>

<?php include 'employee_sidebar.php'; ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-white" style="border-bottom:1px solid #f1f5f9; min-height:64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing:-0.2px;">Leave Requests</h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage your leave applications</p>
        </div>
        <button onclick="openModal('leaveModal')"
                class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-xl text-white transition-all"
                style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 4px 14px rgba(99,102,241,.35);"
                onmouseover="this.style.boxShadow='0 6px 20px rgba(99,102,241,.5)'"
                onmouseout="this.style.boxShadow='0 4px 14px rgba(99,102,241,.35)'">
            <i class="fas fa-plus text-xs"></i> Request Leave
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <!-- Flash -->
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

        <!-- Stats -->
        <div class="grid grid-cols-2 xl:grid-cols-5 gap-4">
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe);">
                    <i class="fas fa-list text-indigo-600 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total</p><p class="text-2xl font-bold text-slate-800"><?php echo $total; ?></p></div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#fef9c3,#fde68a);">
                    <i class="fas fa-hourglass-half text-amber-500 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Pending</p><p class="text-2xl font-bold text-slate-800"><?php echo $pending; ?></p></div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                    <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Approved</p><p class="text-2xl font-bold text-slate-800"><?php echo $approved; ?></p></div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#fee2e2,#fecaca);">
                    <i class="fas fa-times-circle text-red-500 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Rejected</p><p class="text-2xl font-bold text-slate-800"><?php echo $rejected; ?></p></div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#e0f2fe,#bae6fd);">
                    <i class="fas fa-wallet text-sky-500 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Leave Balance</p><p class="text-2xl font-bold text-slate-800"><?php echo $balance; ?></p></div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #e2e8f0;box-shadow:0 2px 12px rgba(0,0,0,.04);">
            <div class="px-5 py-4" style="border-bottom:1px solid #f1f5f9;">
                <h2 class="text-base font-semibold text-slate-800">Leave History</h2>
                <p class="text-xs text-slate-400 mt-0.5"><?php echo $total; ?> request<?php echo $total !== 1 ? 's' : ''; ?> total</p>
            </div>

            <?php if (empty($leaves)): ?>
            <div class="flex flex-col items-center justify-center py-20">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-3" style="background:#f1f5f9;">
                    <i class="fas fa-calendar-times text-slate-300 text-xl"></i>
                </div>
                <p class="text-slate-400 font-medium">No leave requests yet</p>
                <p class="text-slate-300 text-sm mt-1">Click "Request Leave" to submit your first one</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Duration</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Days</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Type</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Reason</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaves as $row):
                            $status = strtolower($row['status']);
                            $start  = new DateTime($row['start_date']);
                            $end    = new DateTime($row['end_date']);
                            $days   = $start->diff($end)->days + 1;
                        ?>
                        <tr class="table-row">
                            <td class="p-4">
                                <span class="text-sm font-medium text-slate-700">
                                    <?php echo date('M d', strtotime($row['start_date'])); ?>
                                    <span class="text-slate-400 mx-1">→</span>
                                    <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#f1f5f9;color:#64748b;"><?php echo $days; ?>d</span>
                            </td>
                            <td class="p-4">
                                <span class="text-sm text-slate-600"><?php echo htmlspecialchars($row['leave_type']); ?></span>
                            </td>
                            <td class="p-4" style="max-width:200px;">
                                <p class="text-sm text-slate-500 truncate" title="<?php echo htmlspecialchars($row['reason']); ?>"><?php echo htmlspecialchars($row['reason']); ?></p>
                            </td>
                            <td class="p-4">
                                <?php if ($status === 'pending'): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold" style="background:#fef9c3;color:#d97706;border:1px solid #fde68a;">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>Pending
                                </span>
                                <?php elseif ($status === 'approved'): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold" style="background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Approved
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold" style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>Rejected
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

    </div>
</div>

<!-- Leave Request Modal -->
<div id="leaveModal" class="modal-overlay" onclick="handleOverlay(event,'leaveModal')">
    <div class="modal-box">
        <div class="flex items-center justify-between px-6 py-5" style="border-bottom:1px solid #f1f5f9;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <i class="fas fa-calendar-plus text-white text-xs"></i>
                </div>
                <h2 class="font-bold text-slate-800">Request Leave</h2>
            </div>
            <button onclick="closeModal('leaveModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form method="POST" action="my_leaves.php" class="px-6 py-5 space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-input" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-input" required>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Leave Type</label>
                <select name="leave_type" class="form-input" required>
                    <option value="">Select Leave Type</option>
                    <option value="Sick">Sick Leave</option>
                    <option value="Casual">Casual Leave</option>
                    <option value="Emergency">Emergency Leave</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Reason</label>
                <textarea name="reason" rows="3" class="form-input" style="resize:none;" placeholder="Briefly describe your reason..." required></textarea>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeModal('leaveModal')"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500"
                        style="background:#f1f5f9;border:1px solid #e2e8f0;"
                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Cancel</button>
                <button type="submit"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 4px 14px rgba(99,102,241,.35);">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function handleOverlay(e,id) { if (e.target===document.getElementById(id)) closeModal(id); }

const today = new Date().toISOString().split('T')[0];
document.getElementById('start_date').min = today;
document.getElementById('end_date').min = today;
document.getElementById('start_date').addEventListener('change', function () {
    document.getElementById('end_date').min = this.value;
    const e = document.getElementById('end_date');
    if (e.value && e.value < this.value) e.value = this.value;
});
document.getElementById('end_date').addEventListener('change', function () {
    const s = document.getElementById('start_date').value;
    if (s && this.value < s) { alert('End date cannot be before start date'); this.value = ''; }
});
</script>

</body>
</html>