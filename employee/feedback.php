<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.html');
    exit();
}

$emp_id = $_SESSION['emp_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $conn->real_escape_string($_POST['subject']);
    $message = $conn->real_escape_string($_POST['message']);
    $stmt = $conn->prepare("INSERT INTO feedback (emp_id, subject, message) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $emp_id, $subject, $message);
    if ($stmt->execute()) $_SESSION['success_message'] = "Feedback submitted successfully!";
    else $_SESSION['error_message'] = "Error submitting feedback.";
    header('Location: feedback.php'); exit();
}

$stmt = $conn->prepare("SELECT * FROM feedback WHERE emp_id = ? ORDER BY submitted_on DESC");
$stmt->bind_param('i', $emp_id);
$stmt->execute();
$feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total  = count($feedbacks);
$unread = count(array_filter($feedbacks, fn($f) => $f['status'] === 'unread'));
$read   = $total - $unread;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Emplify</title>
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
        .feedback-card { background:#fff; border:1px solid #e2e8f0; border-radius:20px; overflow:hidden; transition:box-shadow .2s, transform .2s; }
        .feedback-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.07); transform:translateY(-1px); }
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
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing:-0.2px;">Feedback</h1>
            <p class="text-xs text-slate-400 mt-0.5">Share your thoughts with management</p>
        </div>
        <button onclick="openModal('feedbackModal')"
                class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-xl text-white transition-all"
                style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 4px 14px rgba(99,102,241,.35);"
                onmouseover="this.style.boxShadow='0 6px 20px rgba(99,102,241,.5)'"
                onmouseout="this.style.boxShadow='0 4px 14px rgba(99,102,241,.35)'">
            <i class="fas fa-plus text-xs"></i> Submit Feedback
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
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe);">
                    <i class="fas fa-comments text-indigo-600 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total</p><p class="text-2xl font-bold text-slate-800"><?php echo $total; ?></p></div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#fef9c3,#fde68a);">
                    <i class="fas fa-envelope text-amber-500 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Awaiting Reply</p><p class="text-2xl font-bold text-slate-800"><?php echo $unread; ?></p></div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                    <i class="fas fa-envelope-open text-emerald-600 text-sm"></i>
                </div>
                <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Replied</p><p class="text-2xl font-bold text-slate-800"><?php echo $read; ?></p></div>
            </div>
        </div>

        <!-- Feedback History -->
        <?php if (empty($feedbacks)): ?>
        <div class="flex flex-col items-center justify-center py-24">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background:#f1f5f9;">
                <i class="fas fa-comment-slash text-slate-300 text-2xl"></i>
            </div>
            <p class="text-slate-400 font-medium">No feedback submitted yet</p>
            <p class="text-slate-300 text-sm mt-1">Click "Submit Feedback" to share your thoughts</p>
        </div>
        <?php else: ?>
        <?php
        $accent_colors = [
            ['bg'=>'#e0e7ff','color'=>'#6366f1'],
            ['bg'=>'#ede9fe','color'=>'#8b5cf6'],
            ['bg'=>'#fce7f3','color'=>'#ec4899'],
            ['bg'=>'#fef9c3','color'=>'#d97706'],
            ['bg'=>'#dcfce7','color'=>'#10b981'],
            ['bg'=>'#dbeafe','color'=>'#3b82f6'],
        ];
        ?>
        <div class="space-y-4">
            <?php foreach ($feedbacks as $i => $fb):
                $ac     = $accent_colors[$i % count($accent_colors)];
                $status = strtolower($fb['status'] ?? 'unread');
                $hasReply = !empty($fb['admin_comment']);
            ?>
            <div class="feedback-card">
                <div class="h-1 w-full" style="background:<?php echo $hasReply ? $ac['color'] : '#f59e0b'; ?>;"></div>
                <div class="p-5 space-y-3">

                    <!-- Header -->
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-xs font-bold"
                                 style="background:<?php echo $ac['bg']; ?>;color:<?php echo $ac['color']; ?>;">
                                <?php echo strtoupper(substr($fb['subject'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($fb['subject']); ?></p>
                                <p class="text-xs text-slate-400"><i class="fas fa-clock mr-1"></i><?php echo date('M d, Y · h:i A', strtotime($fb['submitted_on'])); ?></p>
                            </div>
                        </div>
                        <?php if ($hasReply): ?>
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Replied
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#fef9c3;color:#d97706;border:1px solid #fde68a;">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>Pending
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Message -->
                    <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($fb['message']); ?></p>

                    <!-- Admin reply -->
                    <?php if ($hasReply): ?>
                    <div class="p-4 rounded-xl" style="background:linear-gradient(135deg,rgba(99,102,241,0.06),rgba(139,92,246,0.04));border:1px solid rgba(99,102,241,0.15);">
                        <p class="text-xs font-semibold mb-1 flex items-center gap-2" style="color:#6366f1;">
                            <i class="fas fa-reply"></i> Admin Reply
                        </p>
                        <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($fb['admin_comment']); ?></p>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Submit Feedback Modal -->
<div id="feedbackModal" class="modal-overlay" onclick="handleOverlay(event,'feedbackModal')">
    <div class="modal-box">
        <div class="flex items-center justify-between px-6 py-5" style="border-bottom:1px solid #f1f5f9;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <i class="fas fa-comment text-white text-xs"></i>
                </div>
                <h2 class="font-bold text-slate-800">Submit Feedback</h2>
            </div>
            <button onclick="closeModal('feedbackModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form method="POST" action="feedback.php" class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Subject</label>
                <input type="text" name="subject" class="form-input" placeholder="Brief subject..." required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Message</label>
                <textarea name="message" rows="4" class="form-input" style="resize:none;" placeholder="Share your thoughts or concerns..." required></textarea>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeModal('feedbackModal')"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500"
                        style="background:#f1f5f9;border:1px solid #e2e8f0;"
                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Cancel</button>
                <button type="submit"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 4px 14px rgba(99,102,241,.35);">Send Feedback</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function handleOverlay(e,id) { if (e.target===document.getElementById(id)) closeModal(id); }
</script>

</body>
</html>