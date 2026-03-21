<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['emp_id'])) {
    header('Location: login.html');
    exit();
}

$emp_id     = $_SESSION['emp_id'];
$upload_dir = 'uploads/reports/';

// Get task if provided
$task = null;
if (isset($_GET['task_id'])) {
    $task_id = $conn->real_escape_string($_GET['task_id']);
    $stmt    = $conn->prepare("SELECT * FROM task_assigned WHERE task_id = ? AND emp_id = ?");
    $stmt->bind_param('ii', $task_id, $emp_id);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();
    if (!$task) { $_SESSION['error_message'] = "Invalid task ID"; header('Location: my_tasks.php'); exit(); }
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = $conn->real_escape_string($_POST['title']);
    $description  = $conn->real_escape_string($_POST['description']);
    $is_site_visit = isset($_POST['is_site_visit']) ? 1 : 0;
    $task_id      = isset($_POST['task_id']) ? $conn->real_escape_string($_POST['task_id']) : null;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO reports (emp_id, task_id, title, description, is_site_visit) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iissi', $emp_id, $task_id, $title, $description, $is_site_visit);
        $stmt->execute();
        $report_id = $conn->insert_id;

        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === 0) {
                    $filename = uniqid() . '_' . $_FILES['images']['name'][$key];
                    $filepath = $upload_dir . $filename;
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $stmt = $conn->prepare("INSERT INTO report_images (report_id, image_path) VALUES (?, ?)");
                        $stmt->bind_param('is', $report_id, $filepath);
                        $stmt->execute();
                    }
                }
            }
        } elseif ($is_site_visit) {
            throw new Exception("Images are required for site visit reports");
        }

        $conn->commit();
        $_SESSION['success_message'] = "Report submitted successfully!";
        header('Location: submit_report.php'); exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Fetch reports
$stmt = $conn->prepare("SELECT r.*, COUNT(ri.image_id) as image_count FROM reports r LEFT JOIN report_images ri ON r.report_id = ri.report_id WHERE r.emp_id = ? GROUP BY r.report_id ORDER BY r.created_at DESC");
$stmt->bind_param('i', $emp_id);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total = count($reports);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Report - Emplify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
        body { background: #f1f5f9; height: 100vh; overflow: hidden; }
        .main-area { display: flex; flex-direction: column; height: 100vh; overflow: hidden; margin-left: 0; }
        @media (min-width: 1024px) { .main-area { margin-left: 256px; } }
        .table-row { border-bottom:1px solid #f1f5f9; transition:background .15s; }
        .table-row:hover { background:#f8fafc; }
        .table-row:last-child { border-bottom:none; }
        .modal-overlay { display:none; position:fixed; inset:0; z-index:100; background:rgba(15,23,42,0.5); backdrop-filter:blur(2px); align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:20px; width:100%; max-width:520px; margin:16px; box-shadow:0 24px 60px rgba(0,0,0,0.18); animation:modalIn .2s ease; max-height:90vh; overflow-y:auto; }
        @keyframes modalIn { from{opacity:0;transform:scale(.96) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }
        .form-input { width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:12px; font-size:14px; color:#334155; outline:none; transition:border-color .2s,box-shadow .2s; font-family:'DM Sans',sans-serif; }
        .form-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
        .img-preview { width:80px; height:80px; object-fit:cover; border-radius:10px; border:1px solid #e2e8f0; }
    </style>
</head>
<body>

<?php include 'employee_sidebar.php'; ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-white" style="border-bottom:1px solid #f1f5f9; min-height:64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing:-0.2px;">Reports</h1>
            <p class="text-xs text-slate-400 mt-0.5">Submit and track your work reports</p>
        </div>
        <button onclick="openModal('reportModal')"
                class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-xl text-white transition-all"
                style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 4px 14px rgba(99,102,241,.35);"
                onmouseover="this.style.boxShadow='0 6px 20px rgba(99,102,241,.5)'"
                onmouseout="this.style.boxShadow='0 4px 14px rgba(99,102,241,.35)'">
            <i class="fas fa-plus text-xs"></i> Submit Report
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

        <!-- Reports Table -->
        <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #e2e8f0;box-shadow:0 2px 12px rgba(0,0,0,.04);">
            <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid #f1f5f9;">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Report History</h2>
                    <p class="text-xs text-slate-400 mt-0.5"><?php echo $total; ?> report<?php echo $total !== 1 ? 's' : ''; ?> submitted</p>
                </div>
            </div>

            <?php if (empty($reports)): ?>
            <div class="flex flex-col items-center justify-center py-20">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-3" style="background:#f1f5f9;">
                    <i class="fas fa-file-alt text-slate-300 text-xl"></i>
                </div>
                <p class="text-slate-400 font-medium">No reports yet</p>
                <p class="text-slate-300 text-sm mt-1">Click "Submit Report" to add your first one</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Title</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Description</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Type</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Images</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $row): ?>
                        <tr class="table-row">
                            <td class="p-4">
                                <span class="text-sm font-semibold text-slate-700"><?php echo htmlspecialchars($row['title']); ?></span>
                            </td>
                            <td class="p-4" style="max-width:200px;">
                                <p class="text-sm text-slate-500 truncate"><?php echo htmlspecialchars($row['description']); ?></p>
                            </td>
                            <td class="p-4">
                                <?php if ($row['is_site_visit']): ?>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#ffedd5;color:#c2410c;">
                                    <i class="fas fa-map-marker-alt text-xs"></i> Site Visit
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#f1f5f9;color:#64748b;">
                                    <i class="fas fa-file text-xs"></i> General
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#e0e7ff;color:#6366f1;">
                                    <?php echo $row['image_count']; ?> photo<?php echo $row['image_count'] != 1 ? 's' : ''; ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="text-xs text-slate-500"><?php echo date('M d, Y · h:i A', strtotime($row['created_at'])); ?></span>
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

<!-- Submit Report Modal -->
<div id="reportModal" class="modal-overlay" onclick="handleOverlay(event,'reportModal')">
    <div class="modal-box">
        <div class="flex items-center justify-between px-6 py-5" style="border-bottom:1px solid #f1f5f9;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <i class="fas fa-file-alt text-white text-xs"></i>
                </div>
                <h2 class="font-bold text-slate-800">Submit Report</h2>
            </div>
            <button onclick="closeModal('reportModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form method="POST" action="submit_report.php" enctype="multipart/form-data" class="px-6 py-5 space-y-4" id="reportForm">
            <?php if ($task): ?>
            <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
            <div class="flex items-center gap-3 p-3 rounded-xl" style="background:#e0e7ff;border:1px solid #c7d2fe;">
                <i class="fas fa-tasks text-indigo-600 text-sm"></i>
                <p class="text-sm font-medium text-indigo-800">Task: <?php echo htmlspecialchars($task['title']); ?></p>
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Report Title</label>
                <input type="text" name="title" class="form-input" required
                       value="<?php echo $task ? htmlspecialchars($task['title']) . ' - Report' : ''; ?>"
                       placeholder="Enter report title...">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Description</label>
                <textarea name="description" rows="4" class="form-input" style="resize:none;" required placeholder="Describe the work done..."></textarea>
            </div>
            <div class="flex items-center gap-3 p-3 rounded-xl" style="background:#f8fafc;border:1px solid #e2e8f0;">
                <input type="checkbox" name="is_site_visit" id="is_site_visit"
                       class="w-4 h-4 cursor-pointer" style="accent-color:#6366f1;"
                       onchange="toggleSiteVisit()"
                       <?php echo $task && $task['is_site_visit'] ? 'checked disabled' : ''; ?>>
                <label for="is_site_visit" class="text-sm font-medium text-slate-700 cursor-pointer flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-orange-400"></i> This is a site visit report
                </label>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                    Upload Images <span id="img_required" class="text-red-400 hidden">*required for site visit</span>
                </label>
                <div class="border-2 border-dashed rounded-xl p-4 text-center cursor-pointer transition-all"
                     style="border-color:#e2e8f0;"
                     onclick="document.getElementById('images').click()"
                     onmouseover="this.style.borderColor='#6366f1';this.style.background='#f8faff'"
                     onmouseout="this.style.borderColor='#e2e8f0';this.style.background=''">
                    <i class="fas fa-cloud-upload-alt text-slate-300 text-2xl mb-2"></i>
                    <p class="text-sm text-slate-400">Click to upload images</p>
                    <p class="text-xs text-slate-300 mt-0.5">PNG, JPG, JPEG supported</p>
                    <input type="file" id="images" name="images[]" multiple accept="image/*" class="hidden" onchange="previewImages(this)">
                </div>
                <div id="imagePreview" class="flex flex-wrap gap-2 mt-3"></div>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeModal('reportModal')"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500"
                        style="background:#f1f5f9;border:1px solid #e2e8f0;"
                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Cancel</button>
                <button type="submit"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 4px 14px rgba(99,102,241,.35);">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function handleOverlay(e,id) { if (e.target===document.getElementById(id)) closeModal(id); }

function toggleSiteVisit() {
    const checked = document.getElementById('is_site_visit').checked;
    document.getElementById('img_required').classList.toggle('hidden', !checked);
}

function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    for (const file of input.files) {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => {
                const wrap = document.createElement('div');
                wrap.style.cssText = 'position:relative;';
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'img-preview';
                wrap.appendChild(img);
                preview.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        }
    }
}

document.getElementById('reportForm').addEventListener('submit', function(e) {
    const isSite  = document.getElementById('is_site_visit').checked;
    const images  = document.getElementById('images').files;
    if (isSite && images.length === 0) {
        e.preventDefault();
        alert('Please upload at least one image for site visit reports.');
    }
});

// Auto-open modal if coming from task
<?php if ($task): ?>
window.addEventListener('DOMContentLoaded', () => openModal('reportModal'));
<?php endif; ?>
</script>

</body>
</html>