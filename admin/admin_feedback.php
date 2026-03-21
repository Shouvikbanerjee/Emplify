<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_id   = (int)$_POST['feedback_id'];
    $admin_comment = $conn->real_escape_string($_POST['admin_comment']);
    $stmt = $conn->prepare("UPDATE feedback SET admin_comment = ?, status = 'read' WHERE feedback_id = ?");
    $stmt->bind_param('si', $admin_comment, $feedback_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Comment added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding comment.";
    }
    header('Location: admin_feedback.php');
    exit();
}

$result = $conn->query("SELECT f.*, e.name FROM feedback f JOIN employee e ON f.emp_id = e.emp_id ORDER BY f.submitted_on DESC");
if ($result === false) die("Error: " . $conn->error);

$feedbacks = $result->fetch_all(MYSQLI_ASSOC);
$total     = count($feedbacks);
$unread    = count(array_filter($feedbacks, fn($f) => $f['status'] === 'unread'));
$read      = $total - $unread;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Feedback - Emplify</title>
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

        .feedback-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .feedback-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,0.07); transform: translateY(-1px); }

        .form-input {
            width: 100%; padding: 10px 14px;
            border: 1px solid #e2e8f0; border-radius: 12px;
            font-size: 14px; color: #334155; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: 'DM Sans', sans-serif;
            resize: none;
        }
        .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }

        .search-bar {
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 10px 16px;
            font-size: 14px; color: #334155; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-bar:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }

        .filter-tab {
            padding: 6px 14px; border-radius: 10px;
            font-size: 12px; font-weight: 600; cursor: pointer;
            transition: background 0.15s, color 0.15s;
            border: 1px solid transparent;
        }
        .filter-tab.active { background: #6366f1; color: #fff; border-color: #6366f1; }
        .filter-tab:not(.active) { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
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
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing: -0.2px;">Employee Feedback</h1>
            <p class="text-xs text-slate-400 mt-0.5">Review and respond to employee submissions</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-slate-500 px-4 py-2 rounded-xl bg-white"
             style="border: 1px solid #e2e8f0;">
            <i class="fas fa-calendar-day text-indigo-400"></i>
            <span><?php echo date('D, M d Y'); ?></span>
        </div>
    </div>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <!-- Flash messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
             style="background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0;">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
             style="background:#fee2e2; color:#dc2626; border:1px solid #fecaca;">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>

        <!-- Stat cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe);">
                    <i class="fas fa-comments text-indigo-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $total; ?></p>
                </div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#fef9c3,#fde68a);">
                    <i class="fas fa-envelope text-amber-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Unread</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $unread; ?></p>
                </div>
            </div>
            <div class="stat-card flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                    <i class="fas fa-envelope-open text-emerald-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Read</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo $read; ?></p>
                </div>
            </div>
        </div>

        <!-- Filter + Search -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-5 py-4 rounded-2xl"
             style="border:1px solid #e2e8f0;">
            <div class="flex items-center gap-2 flex-wrap">
                <button class="filter-tab active" onclick="filterFeedback('all',this)">All</button>
                <button class="filter-tab" onclick="filterFeedback('unread',this)">
                    <span class="inline-flex items-center gap-1">
                        Unread
                        <?php if ($unread > 0): ?>
                        <span class="w-4 h-4 rounded-full text-white flex items-center justify-center"
                              style="background:#f59e0b; font-size:10px;"><?php echo $unread; ?></span>
                        <?php endif; ?>
                    </span>
                </button>
                <button class="filter-tab" onclick="filterFeedback('read',this)">Read</button>
            </div>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                <input type="text" id="searchInput" placeholder="Search employee or subject..."
                       class="search-bar pl-9 w-56" oninput="filterFeedback(null,null)">
            </div>
        </div>

        <!-- Feedback Cards -->
        <?php if (empty($feedbacks)): ?>
        <div class="flex flex-col items-center justify-center py-24">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                 style="background:#f1f5f9;">
                <i class="fas fa-comment-slash text-slate-300 text-2xl"></i>
            </div>
            <p class="text-slate-400 font-medium">No feedback yet</p>
            <p class="text-slate-300 text-sm mt-1">Employee feedback will appear here once submitted</p>
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

        <div class="space-y-4" id="feedbackList">
            <?php foreach ($feedbacks as $i => $fb):
                $ac      = $avatar_colors[$i % count($avatar_colors)];
                $initial = strtoupper(substr($fb['name'], 0, 1));
                $isUnread = $fb['status'] === 'unread';
                $hasComment = !empty($fb['admin_comment']);
            ?>
            <div class="feedback-card"
                 data-status="<?php echo $fb['status']; ?>"
                 data-search="<?php echo strtolower(htmlspecialchars($fb['name'].' '.$fb['subject'].' '.$fb['message'])); ?>">

                <!-- Top accent -->
                <div class="h-1 w-full" style="background: <?php echo $isUnread ? 'linear-gradient(90deg,#f59e0b,#fbbf24)' : 'linear-gradient(90deg,'.$ac['text'].','.$ac['text'].'88)'; ?>;"></div>

                <div class="p-5 space-y-4">

                    <!-- Header -->
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div style="width:36px;height:36px;border-radius:10px;background:<?php echo $ac['bg']; ?>;color:<?php echo $ac['text']; ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                                <?php echo $initial; ?>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($fb['name']); ?></p>
                                <p class="text-xs text-slate-400">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php echo date('M d, Y · h:i A', strtotime($fb['submitted_on'])); ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($isUnread): ?>
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                                  style="background:#fef9c3; color:#d97706; border:1px solid #fde68a;">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                Unread
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                                  style="background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0;">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Read
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Subject -->
                    <div class="flex items-start gap-3 p-3 rounded-xl" style="background:#f8fafc; border:1px solid #f1f5f9;">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                             style="background:<?php echo $ac['bg']; ?>;">
                            <i class="fas fa-tag text-xs" style="color:<?php echo $ac['text']; ?>;"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Subject</p>
                            <p class="text-sm font-semibold text-slate-700 mt-0.5"><?php echo htmlspecialchars($fb['subject']); ?></p>
                        </div>
                    </div>

                    <!-- Message -->
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Message</p>
                        <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                            <?php echo htmlspecialchars($fb['message']); ?>
                        </p>
                    </div>

                    <!-- Existing admin comment -->
                    <?php if ($hasComment): ?>
                    <div class="p-4 rounded-xl" style="background:linear-gradient(135deg,rgba(99,102,241,0.06),rgba(139,92,246,0.04)); border:1px solid rgba(99,102,241,0.15);">
                        <p class="text-xs font-semibold mb-2 flex items-center gap-2" style="color:#6366f1;">
                            <i class="fas fa-reply"></i> Admin Reply
                        </p>
                        <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                            <?php echo htmlspecialchars($fb['admin_comment']); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Reply form -->
                    <div style="border-top:1px solid #f1f5f9; padding-top:16px;">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">
                            <?php echo $hasComment ? 'Update Reply' : 'Add Reply'; ?>
                        </p>
                        <form method="POST" action="admin_feedback.php">
                            <input type="hidden" name="feedback_id" value="<?php echo $fb['feedback_id']; ?>">
                            <textarea name="admin_comment" rows="3" required
                                      class="form-input mb-3"
                                      placeholder="Type your reply here..."><?php echo htmlspecialchars($fb['admin_comment'] ?? ''); ?></textarea>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-xl text-white transition-all"
                                    style="background:linear-gradient(135deg,#6366f1,#8b5cf6); box-shadow:0 4px 14px rgba(99,102,241,0.3);"
                                    onmouseover="this.style.boxShadow='0 6px 20px rgba(99,102,241,0.45)'"
                                    onmouseout="this.style.boxShadow='0 4px 14px rgba(99,102,241,0.3)'">
                                <i class="fas <?php echo $hasComment ? 'fa-sync-alt' : 'fa-paper-plane'; ?> text-xs"></i>
                                <?php echo $hasComment ? 'Update Reply' : 'Send Reply'; ?>
                            </button>
                        </form>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /scrollable -->
</div><!-- /main-area -->

<script>
let activeFilter = 'all';

function filterFeedback(status, btn) {
    if (status !== null) {
        activeFilter = status;
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        if (btn) btn.classList.add('active');
    }
    const query = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#feedbackList .feedback-card').forEach(card => {
        const matchStatus = activeFilter === 'all' || card.dataset.status === activeFilter;
        const matchSearch = card.dataset.search.includes(query);
        card.style.display = matchStatus && matchSearch ? '' : 'none';
    });
}
</script>

</body>
</html>