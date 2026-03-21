<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.html");
    exit();
}

require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_department'])) {
        $name        = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $conn->query("INSERT INTO department (name, description) VALUES ('$name', '$description')");
        header("Location: department.php");
        exit();
    }
    if (isset($_POST['edit_department'])) {
        $dep_id      = $conn->real_escape_string($_POST['dep_id']);
        $name        = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $conn->query("UPDATE department SET name='$name', description='$description' WHERE dep_id=$dep_id");
        header("Location: department.php");
        exit();
    }
}

$result      = $conn->query("SELECT * FROM department");
$departments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$total       = count($departments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - Emplify</title>
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

        /* Department Cards */
        .dept-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            transition: box-shadow 0.2s, transform 0.2s;
            overflow: hidden;
        }
        .dept-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.09);
            transform: translateY(-2px);
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 100;
            background: rgba(15,23,42,0.5);
            backdrop-filter: blur(2px);
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 20px;
            width: 100%; max-width: 460px;
            margin: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.18);
            animation: modalIn 0.2s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Form inputs */
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            color: #334155;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }

        /* Employee list inside card */
        .employee-list-area {
            display: none;
            border-top: 1px solid #f1f5f9;
        }
        .employee-list-area.open { display: block; }

        /* Action buttons */
        .btn-icon {
            width: 30px; height: 30px;
            border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 11px;
            transition: background 0.15s, transform 0.15s;
            cursor: pointer; border: none;
        }
        .btn-icon:hover { transform: scale(1.08); }
    </style>
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-area">

    <!-- Navbar -->
    <div class="flex items-center justify-between px-6 py-4 bg-white"
         style="border-bottom: 1px solid #f1f5f9; min-height: 64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing: -0.2px;">Departments</h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage your organisation's departments</p>
        </div>
        <button onclick="openModal('addModal')"
                class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-xl text-white transition-all"
                style="background: linear-gradient(135deg,#6366f1,#8b5cf6); box-shadow: 0 4px 14px rgba(99,102,241,0.35);"
                onmouseover="this.style.boxShadow='0 6px 20px rgba(99,102,241,0.5)'"
                onmouseout="this.style.boxShadow='0 4px 14px rgba(99,102,241,0.35)'">
            <i class="fas fa-plus text-xs"></i> Add Department
        </button>
    </div>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <!-- Summary -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-xl bg-white"
                 style="border: 1px solid #e2e8f0;">
                <span class="w-2 h-2 rounded-full bg-indigo-400 inline-block"></span>
                <span class="text-slate-600"><?php echo $total; ?> department<?php echo $total !== 1 ? 's' : ''; ?></span>
            </div>
        </div>

        <?php if (empty($departments)): ?>
        <!-- Empty state -->
        <div class="flex flex-col items-center justify-center py-24">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                 style="background: #f1f5f9;">
                <i class="fas fa-building text-slate-300 text-2xl"></i>
            </div>
            <p class="text-slate-400 font-medium">No departments yet</p>
            <p class="text-slate-300 text-sm mt-1">Click "Add Department" to create your first one</p>
        </div>

        <?php else: ?>
        <!-- Department grid -->
        <?php
        $card_accents = [
            ['light'=>'#e0e7ff','color'=>'#6366f1','dark'=>'#4f46e5'],
            ['light'=>'#ede9fe','color'=>'#8b5cf6','dark'=>'#7c3aed'],
            ['light'=>'#fce7f3','color'=>'#ec4899','dark'=>'#db2777'],
            ['light'=>'#fef9c3','color'=>'#f59e0b','dark'=>'#d97706'],
            ['light'=>'#dcfce7','color'=>'#10b981','dark'=>'#059669'],
            ['light'=>'#dbeafe','color'=>'#3b82f6','dark'=>'#2563eb'],
            ['light'=>'#ffedd5','color'=>'#f97316','dark'=>'#ea580c'],
        ];
        ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($departments as $i => $dept):
                $ac = $card_accents[$i % count($card_accents)];
                $initial = strtoupper(substr($dept['name'], 0, 1));
            ?>
            <div class="dept-card">

                <!-- Card top bar accent -->
                <div class="h-1 w-full" style="background: linear-gradient(90deg, <?php echo $ac['color']; ?>, <?php echo $ac['dark']; ?>);"></div>

                <div class="p-5">
                    <!-- Header row -->
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3">
                            <!-- Icon badge -->
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                 style="background: <?php echo $ac['light']; ?>;">
                                <i class="fas fa-building text-sm" style="color: <?php echo $ac['color']; ?>;"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800 text-sm leading-tight">
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </h3>
                                <span class="text-xs" style="color: <?php echo $ac['color']; ?>;">Department</span>
                            </div>
                        </div>
                        <!-- Action buttons -->
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button class="btn-icon" style="background:#e0e7ff; color:#4f46e5;"
                                    onmouseover="this.style.background='#c7d2fe'"
                                    onmouseout="this.style.background='#e0e7ff'"
                                    onclick="openEditModal(<?php echo $dept['dep_id']; ?>, '<?php echo htmlspecialchars(addslashes($dept['name'])); ?>', '<?php echo htmlspecialchars(addslashes($dept['description'])); ?>')"
                                    title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon" style="background:#fee2e2; color:#dc2626;"
                                    onmouseover="this.style.background='#fecaca'"
                                    onmouseout="this.style.background='#fee2e2'"
                                    onclick="confirmDelete(<?php echo $dept['dep_id']; ?>)"
                                    title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-xs text-slate-500 leading-relaxed mb-4 line-clamp-2">
                        <?php echo htmlspecialchars($dept['description']) ?: '<span class="text-slate-300 italic">No description provided</span>'; ?>
                    </p>

                    <!-- Toggle employees button -->
                    <button onclick="toggleEmployees(<?php echo $dept['dep_id']; ?>, this)"
                            class="w-full flex items-center justify-center gap-2 text-xs font-semibold py-2 rounded-xl transition-all"
                            style="background: <?php echo $ac['light']; ?>; color: <?php echo $ac['color']; ?>;">
                        <i class="fas fa-users text-xs"></i>
                        <span>View Employees</span>
                        <i class="fas fa-chevron-down text-xs transition-transform" id="chevron-<?php echo $dept['dep_id']; ?>"></i>
                    </button>
                </div>

                <!-- Employee list (hidden by default) -->
                <div class="employee-list-area" id="employees-<?php echo $dept['dep_id']; ?>">
                    <div class="px-5 py-3 text-xs text-slate-400 flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin"></i> Loading employees...
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- ── ADD MODAL ─────────────────────────────────── -->
<div id="addModal" class="modal-overlay" onclick="handleOverlayClick(event,'addModal')">
    <div class="modal-box">
        <div class="flex items-center justify-between px-6 py-5" style="border-bottom:1px solid #f1f5f9;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <i class="fas fa-plus text-white text-xs"></i>
                </div>
                <h2 class="font-bold text-slate-800">Add Department</h2>
            </div>
            <button onclick="closeModal('addModal')"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form method="POST" class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Department Name</label>
                <input type="text" name="name" class="form-input" placeholder="e.g. Engineering" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Description</label>
                <textarea name="description" class="form-input" rows="3" placeholder="Brief description of this department..." required style="resize:none;"></textarea>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeModal('addModal')"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500 transition-colors"
                        style="background:#f1f5f9; border:1px solid #e2e8f0;"
                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    Cancel
                </button>
                <button type="submit" name="add_department"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6); box-shadow:0 4px 14px rgba(99,102,241,0.35);">
                    Add Department
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── EDIT MODAL ─────────────────────────────────── -->
<div id="editModal" class="modal-overlay" onclick="handleOverlayClick(event,'editModal')">
    <div class="modal-box">
        <div class="flex items-center justify-between px-6 py-5" style="border-bottom:1px solid #f1f5f9;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <i class="fas fa-edit text-white text-xs"></i>
                </div>
                <h2 class="font-bold text-slate-800">Edit Department</h2>
            </div>
            <button onclick="closeModal('editModal')"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form method="POST" class="px-6 py-5 space-y-4">
            <input type="hidden" name="dep_id" id="edit_dep_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Department Name</label>
                <input type="text" name="name" id="edit_name" class="form-input" placeholder="Department Name" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Description</label>
                <textarea name="description" id="edit_description" class="form-input" rows="3" required style="resize:none;"></textarea>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeModal('editModal')"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500 transition-colors"
                        style="background:#f1f5f9; border:1px solid #e2e8f0;"
                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    Cancel
                </button>
                <button type="submit" name="edit_department"
                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6); box-shadow:0 4px 14px rgba(99,102,241,0.35);">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── DELETE CONFIRM MODAL ───────────────────────── -->
<div id="deleteModal" class="modal-overlay" onclick="handleOverlayClick(event,'deleteModal')">
    <div class="modal-box" style="max-width:380px;">
        <div class="px-6 py-5 text-center">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
                 style="background:#fee2e2;">
                <i class="fas fa-trash text-red-500 text-xl"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-lg mb-1">Delete Department?</h3>
            <p class="text-sm text-slate-400">This action cannot be undone. All related data may be affected.</p>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button onclick="closeModal('deleteModal')"
                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500"
                    style="background:#f1f5f9; border:1px solid #e2e8f0;"
                    onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                Cancel
            </button>
            <button id="confirmDeleteBtn"
                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
                    style="background:linear-gradient(135deg,#ef4444,#dc2626); box-shadow:0 4px 14px rgba(239,68,68,0.35);">
                Yes, Delete
            </button>
        </div>
    </div>
</div>

<!-- ── MESSAGE MODAL ──────────────────────────────── -->
<div id="messageModal" class="modal-overlay" onclick="handleOverlayClick(event,'messageModal')">
    <div class="modal-box" style="max-width:360px;">
        <div class="px-6 py-8 text-center">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
                 style="background:#dcfce7;">
                <i class="fas fa-check text-emerald-500 text-xl" id="msgIcon"></i>
            </div>
            <p class="font-semibold text-slate-700" id="messageText"></p>
        </div>
        <div class="px-6 pb-6">
            <button onclick="closeModal('messageModal')"
                    class="w-full py-2.5 rounded-xl text-sm font-semibold text-white"
                    style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                OK
            </button>
        </div>
    </div>
</div>

<script>
/* ── Modal helpers ── */
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
function handleOverlayClick(e, id) {
    if (e.target === document.getElementById(id)) closeModal(id);
}

/* ── Edit modal ── */
function openEditModal(depId, name, description) {
    document.getElementById('edit_dep_id').value   = depId;
    document.getElementById('edit_name').value      = name;
    document.getElementById('edit_description').value = description;
    openModal('editModal');
}

/* ── Delete modal ── */
let deleteTargetId = null;
function confirmDelete(depId) {
    deleteTargetId = depId;
    openModal('deleteModal');
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    if (deleteTargetId !== null) {
        window.location.href = "delete_department.php?dep_id=" + deleteTargetId;
    }
});

/* ── Toggle employees ── */
function toggleEmployees(depId, btn) {
    const area     = document.getElementById('employees-' + depId);
    const chevron  = document.getElementById('chevron-' + depId);
    const isOpen   = area.classList.contains('open');

    if (isOpen) {
        area.classList.remove('open');
        chevron.style.transform = '';
        btn.querySelector('span').textContent = 'View Employees';
    } else {
        chevron.style.transform = 'rotate(180deg)';
        btn.querySelector('span').textContent = 'Hide Employees';

        if (!area.dataset.loaded) {
            fetch(`get_department_employees.php?dep_id=${depId}`)
                .then(r => r.json())
                .then(employees => {
                    if (employees.length === 0) {
                        area.innerHTML = `
                            <div class="px-5 py-4 text-xs text-slate-400 flex items-center gap-2">
                                <i class="fas fa-info-circle"></i> No employees in this department
                            </div>`;
                    } else {
                        let html = '<ul class="divide-y divide-slate-50">';
                        employees.forEach(emp => {
                            const init = emp.name.charAt(0).toUpperCase();
                            html += `
                            <li class="flex items-center gap-3 px-5 py-2.5">
                                <div style="width:28px;height:28px;border-radius:8px;background:#e0e7ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                                    ${init}
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-700">${emp.name}</p>
                                    <p class="text-xs text-slate-400">${emp.designation || 'No designation'}</p>
                                </div>
                            </li>`;
                        });
                        html += '</ul>';
                        area.innerHTML = html;
                    }
                    area.dataset.loaded = '1';
                    area.classList.add('open');
                })
                .catch(() => {
                    area.innerHTML = `<div class="px-5 py-4 text-xs text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>Failed to load employees</div>`;
                    area.classList.add('open');
                });
        } else {
            area.classList.add('open');
        }
    }
}

/* ── Message from redirect ── */
<?php if (isset($_GET['msg'])): ?>
window.addEventListener('DOMContentLoaded', () => {
    const msgMap = { deleted: 'Department deleted successfully.', fail: 'Failed to delete department.' };
    const msg    = msgMap["<?php echo htmlspecialchars($_GET['msg']); ?>"];
    const isFail = "<?php echo $_GET['msg']; ?>" === 'fail';
    if (msg) {
        document.getElementById('messageText').textContent = msg;
        const iconEl  = document.getElementById('msgIcon');
        const wrapEl  = iconEl.parentElement;
        if (isFail) {
            wrapEl.style.background  = '#fee2e2';
            iconEl.className         = 'fas fa-times text-red-500 text-xl';
        }
        openModal('messageModal');
    }
});
<?php endif; ?>
</script>

</body>
</html>