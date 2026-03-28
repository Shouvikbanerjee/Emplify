<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.html');
    exit();
}
require_once '../config/db.php';

// ── Handle ADD ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name=$_POST['name']; $email=$_POST['email']; $phone=$_POST['phone'];
    $gender=$_POST['gender']; $dob=$_POST['dob']; $address=$_POST['address'];
    $dep_id=(int)$_POST['dep_id']; $desg_id=(int)$_POST['desg_id'];
    $username=$_POST['username'];
    $password=password_hash($_POST['password'], PASSWORD_DEFAULT);
    $conn->begin_transaction();
    try {
        $status = 1; // define variable first

        $s = $conn->prepare("INSERT INTO employee (name,email,phone,gender,dob,address,dep_id,desg_id,status) VALUES (?,?,?,?,?,?,?,?,?)");

        $s->bind_param("ssssssiii", $name, $email, $phone, $gender, $dob, $address, $dep_id, $desg_id, $status);
        $s->execute(); $eid=$conn->insert_id;
        $s2=$conn->prepare("INSERT INTO login (username,password,emp_id) VALUES (?,?,?)");
        $s2->bind_param("ssi",$username,$password,$eid);
        $s2->execute();
        $conn->commit();
        $_SESSION['flash']=['type'=>'success','msg'=>"Employee <strong>".htmlspecialchars($name)."</strong> added successfully!"];
    } catch(Exception $e){ $conn->rollback(); $_SESSION['flash']=['type'=>'error','msg'=>"Failed: ".$e->getMessage()]; }
    header('Location: employees.php'); exit();
}

// ── Handle EDIT ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $eid=(int)$_POST['emp_id']; $name=$_POST['name']; $email=$_POST['email'];
    $phone=$_POST['phone']; $gender=$_POST['gender']; $dob=$_POST['dob'];
    $address=$_POST['address']; $dep_id=(int)$_POST['dep_id']; $desg_id=(int)$_POST['desg_id'];
    $s=$conn->prepare("UPDATE employee SET name=?,email=?,phone=?,gender=?,dob=?,address=?,dep_id=?,desg_id=? WHERE emp_id=?");
    $s->bind_param("ssssssiii",$name,$email,$phone,$gender,$dob,$address,$dep_id,$desg_id,$eid);
    if($s->execute()) $_SESSION['flash']=['type'=>'success','msg'=>"Employee <strong>".htmlspecialchars($name)."</strong> updated!"];
    else $_SESSION['flash']=['type'=>'error','msg'=>"Update failed: ".$conn->error];
    header('Location: employees.php'); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {

    $emp_id = (int)$_POST['emp_id'];
    $current_status = (int)$_POST['status'];

    $new_status = $current_status ? 0 : 1;

    $stmt = $conn->prepare("UPDATE employee SET status=? WHERE emp_id=?");
    $stmt->bind_param("ii", $new_status, $emp_id);
    $stmt->execute();

    $redirect_filter = $_POST['current_filter'] ?? 'active';
    header("Location: employees.php?filter=" . urlencode($redirect_filter));
    exit();
}

// ── Handle DELETE ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $eid = (int)$_POST['emp_id'];
    $conn->begin_transaction();
    try {
        $s = $conn->prepare("DELETE FROM login WHERE emp_id=?");
        $s->bind_param("i",$eid); $s->execute();
        $s2 = $conn->prepare("DELETE FROM employee WHERE emp_id=?");
        $s2->bind_param("i",$eid); $s2->execute();
        $conn->commit();
        $_SESSION['flash']=['type'=>'success','msg'=>"Employee deleted successfully."];
    } catch(Exception $e){ $conn->rollback(); $_SESSION['flash']=['type'=>'error','msg'=>"Delete failed: ".$e->getMessage()]; }
    $redirect_filter = $_POST['current_filter'] ?? 'active';
    header('Location: employees.php?filter='.urlencode($redirect_filter)); exit();
}


$filter = $_GET['filter'] ?? 'active';

$sql = "SELECT e.*, d.name as department_name, ds.name as designation_name 
        FROM employee e 
        LEFT JOIN department d ON e.dep_id = d.dep_id 
        LEFT JOIN designation ds ON e.desg_id = ds.desg_id";

if ($filter === 'active') {
    $sql .= " WHERE e.status = 1";
} elseif ($filter === 'inactive') {
    $sql .= " WHERE e.status = 0";
}

$employees = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$total = count($employees);
$departments=$conn->query("SELECT dep_id,name FROM department ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$designations=$conn->query("SELECT desg_id,name FROM designation ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emplify - Employees</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        *{font-family:'DM Sans',sans-serif}
        body{background:#f1f5f9;height:100vh;overflow:hidden}
        .main-area{display:flex;flex-direction:column;height:100vh;overflow:hidden;margin-left:0}
        @media(min-width:1024px){.main-area{margin-left:256px}}
        .table-row{border-bottom:1px solid #f1f5f9;transition:background .15s}
        .table-row:hover{background:#f8fafc}
        .table-row:last-child{border-bottom:none}
        .avatar{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:13px;flex-shrink:0}
        .search-bar{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:10px 16px;font-size:14px;color:#334155;outline:none;transition:border-color .2s,box-shadow .2s}
        .search-bar:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
        .btn-action{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;transition:all .15s;font-size:12px;border:none;cursor:pointer}
        .btn-action:hover{transform:scale(1.08)}
        .modal-overlay{display:none;position:fixed;inset:0;z-index:100;background:rgba(15,23,42,.5);backdrop-filter:blur(2px);align-items:center;justify-content:center}
        .modal-overlay.open{display:flex}
        .modal-box{background:#fff;border-radius:20px;width:100%;max-width:540px;margin:16px;box-shadow:0 24px 60px rgba(0,0,0,.18);animation:modalIn .2s ease;max-height:90vh;overflow-y:auto}
        .modal-sm{max-width:400px}
        @keyframes modalIn{from{opacity:0;transform:scale(.96) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}
        .form-input{width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:12px;font-size:14px;color:#334155;outline:none;transition:border-color .2s,box-shadow .2s;font-family:'DM Sans',sans-serif;background:#fff}
        .form-input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
        .info-row{display:flex;padding:11px 0;border-bottom:1px solid #f8fafc}
        .info-row:last-child{border-bottom:none}
        .info-label{width:105px;font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;padding-top:1px;flex-shrink:0}
        .info-value{flex:1;font-size:14px;color:#0f172a;font-weight:500}
    </style>
</head>
<body>

<?php include('../includes/sidebar.php'); ?>

<div class="main-area">
    <div class="flex items-center justify-between px-6 py-4 bg-white" style="border-bottom:1px solid #f1f5f9;min-height:64px;">
        <div>
            <h1 class="text-lg font-bold text-slate-800" style="letter-spacing:-0.2px">Employees</h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage your workforce</p>
        </div>
        <button onclick="openModal('addModal')"
                class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-xl text-white"
                style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 4px 14px rgba(99,102,241,.35);"
                onmouseover="this.style.boxShadow='0 6px 20px rgba(99,102,241,.5)'"
                onmouseout="this.style.boxShadow='0 4px 14px rgba(99,102,241,.35)'">
            <i class="fas fa-plus text-xs"></i> Add Employee
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-5">

        <?php if(isset($_SESSION['flash'])): $f=$_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium"
             style="background:<?php echo $f['type']==='success'?'#dcfce7':'#fee2e2';?>;color:<?php echo $f['type']==='success'?'#16a34a':'#dc2626';?>;border:1px solid <?php echo $f['type']==='success'?'#bbf7d0':'#fecaca';?>">
            <i class="fas fa-<?php echo $f['type']==='success'?'check-circle':'exclamation-circle';?> flex-shrink-0"></i>
            <span><?php echo $f['msg'];?></span>
        </div>
        <?php endif;?>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-xl bg-white" style="border:1px solid #e2e8f0">
                <span class="w-2 h-2 rounded-full bg-indigo-400 inline-block"></span>
                <span class="text-slate-600"><?php echo $total;?> employees total</span>
            </div>

            <div class="flex items-center gap-2">
    <?php
    $filters = [
        'active'   => ['label'=>'Active',   'icon'=>'fa-circle-check',  'active_bg'=>'linear-gradient(135deg,#22c55e,#16a34a)', 'active_shadow'=>'rgba(34,197,94,.35)',  'inactive_bg'=>'#f0fdf4', 'inactive_color'=>'#16a34a', 'inactive_border'=>'#bbf7d0'],
        'inactive' => ['label'=>'Inactive',  'icon'=>'fa-circle-xmark',  'active_bg'=>'linear-gradient(135deg,#ef4444,#dc2626)', 'active_shadow'=>'rgba(239,68,68,.35)',   'inactive_bg'=>'#fff1f2', 'inactive_color'=>'#dc2626', 'inactive_border'=>'#fecdd3'],
        'all'      => ['label'=>'All',       'icon'=>'fa-users',          'active_bg'=>'linear-gradient(135deg,#6366f1,#8b5cf6)', 'active_shadow'=>'rgba(99,102,241,.35)', 'inactive_bg'=>'#f5f3ff', 'inactive_color'=>'#6366f1', 'inactive_border'=>'#ddd6fe'],
    ];
    foreach($filters as $key=>$cfg):
        $isActive = ($filter === $key);
        if($isActive):
    ?>
        <a href="employees.php?filter=<?php echo $key;?>"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:50px;font-size:13px;font-weight:600;color:#fff;text-decoration:none;background:<?php echo $cfg['active_bg'];?>;box-shadow:0 4px 12px <?php echo $cfg['active_shadow'];?>;transition:all .2s;"
           onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 18px <?php echo $cfg['active_shadow'];?>'"
           onmouseout="this.style.transform='';this.style.boxShadow='0 4px 12px <?php echo $cfg['active_shadow'];?>'">
            <i class="fas <?php echo $cfg['icon'];?>" style="font-size:11px"></i> <?php echo $cfg['label'];?>
        </a>
    <?php else: ?>
        <a href="employees.php?filter=<?php echo $key;?>"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:50px;font-size:13px;font-weight:600;color:<?php echo $cfg['inactive_color'];?>;text-decoration:none;background:<?php echo $cfg['inactive_bg'];?>;border:1.5px solid <?php echo $cfg['inactive_border'];?>;transition:all .2s;"
           onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 3px 10px <?php echo $cfg['active_shadow'];?>'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
            <i class="fas <?php echo $cfg['icon'];?>" style="font-size:11px"></i> <?php echo $cfg['label'];?>
        </a>
    <?php endif; endforeach; ?>
</div>

        </div>

        <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #e2e8f0;box-shadow:0 2px 12px rgba(0,0,0,.04)">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-5" style="border-bottom:1px solid #f1f5f9">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">All Employees</h2>
                    <p class="text-xs text-slate-400 mt-0.5" id="recordCount"><?php echo $total;?> records found</p>
                </div>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Search employees..." class="search-bar pl-9 w-56" oninput="filterTable()">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full" id="employeeTable">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9">
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">#</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Employee</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Email</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Phone</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Department</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Designation</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($employees)):?>
                        <tr><td colspan="8" class="py-16 text-center">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background:#f1f5f9">
                                <i class="fas fa-users text-slate-300 text-xl"></i>
                            </div>
                            <p class="text-slate-400 font-medium">No employees found</p>
                        </td></tr>
                        <?php else:
                        $ac_pool=[
                            ['bg'=>'#e0e7ff20','text'=>'#6366f1','border'=>'#6366f130'],
                            ['bg'=>'#ede9fe20','text'=>'#8b5cf6','border'=>'#8b5cf630'],
                            ['bg'=>'#fce7f320','text'=>'#ec4899','border'=>'#ec489930'],
                            ['bg'=>'#fef9c320','text'=>'#f59e0b','border'=>'#f59e0b30'],
                            ['bg'=>'#dcfce720','text'=>'#10b981','border'=>'#10b98130'],
                            ['bg'=>'#dbeafe20','text'=>'#3b82f6','border'=>'#3b82f630'],
                            ['bg'=>'#ffedd520','text'=>'#f97316','border'=>'#f9731630'],
                        ];
                        foreach($employees as $i=>$row):
                            $ac=$ac_pool[$i%count($ac_pool)];
                        ?>
                        <tr class="table-row">
                            <td class="p-4"><span class="text-xs font-semibold text-slate-400"><?php echo $i+1;?></span></td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="avatar" style="background:<?php echo $ac['bg'];?>;color:<?php echo $ac['text'];?>;border:1.5px solid <?php echo $ac['border'];?>">
                                        <?php echo strtoupper(substr($row['name'],0,1));?>
                                    </div>
                                    <span class="font-medium text-slate-700 text-sm"><?php echo htmlspecialchars($row['name']);?></span>
                                </div>
                            </td>
                            <td class="p-4"><span class="text-sm text-slate-500"><?php echo htmlspecialchars($row['email']);?></span></td>
                            <td class="p-4"><span class="text-sm text-slate-500"><?php echo htmlspecialchars($row['phone']);?></span></td>
                            <td class="p-4">
                                <?php if($row['department_name']):?>
                                <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#e0e7ff;color:#4f46e5"><?php echo htmlspecialchars($row['department_name']);?></span>
                                <?php else:?><span class="text-slate-300 text-sm">—</span><?php endif;?>
                            </td>
                            <td class="p-4"><span class="text-sm text-slate-500"><?php echo htmlspecialchars($row['designation_name']??'—');?></span></td>
                            <td class="p-4">
                                <?php if($row['status']==1):?>
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:50px;font-size:11px;font-weight:700;background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0">
                                    <i class="fas fa-circle" style="font-size:5px"></i> Active
                                </span>
                                <?php else:?>
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:50px;font-size:11px;font-weight:700;background:#fee2e2;color:#dc2626;border:1px solid #fecaca">
                                    <i class="fas fa-circle" style="font-size:5px"></i> Inactive
                                </span>
                                <?php endif;?>
                            </td>
                            <td class="p-4">
                                
                                    <div class="flex items-center gap-2">

                                        <!-- View -->
                                        <button class="btn-action"
                                            style="background:#e0f2fe;color:#0284c7"
                                            title="View"
                                            onclick='viewEmployee(<?php echo json_encode($row);?>)'
                                            onmouseover="this.style.background='#bae6fd'"
                                            onmouseout="this.style.background='#e0f2fe'">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <!-- Edit -->
                                        <button class="btn-action"
                                            style="background:#e0e7ff;color:#4f46e5"
                                            title="Edit"
                                            onclick='openEditModal(<?php echo json_encode($row);?>)'
                                            onmouseover="this.style.background='#c7d2fe'"
                                            onmouseout="this.style.background='#e0e7ff'">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- Delete -->
                                        <button class="btn-action"
                                            style="background:#fee2e2;color:#dc2626"
                                            title="Delete"
                                            onclick='openDeleteModal(<?php echo $row["emp_id"]; ?>, <?php echo json_encode(htmlspecialchars($row["name"])); ?>, "<?php echo htmlspecialchars($filter); ?>")'
                                            onmouseover="this.style.background='#fecaca'"
                                            onmouseout="this.style.background='#fee2e2'">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>

                                        <!-- Toggle Status Pill -->
                                        <form method="POST" action="employees.php" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="emp_id" value="<?php echo $row['emp_id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $row['status']; ?>">
                                            <input type="hidden" name="current_filter" value="<?php echo htmlspecialchars($filter); ?>">
                                            <?php if($row['status'] == 1): ?>
                                                
                                                <button type="submit"
                                                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border:none;border-radius:50px;cursor:pointer;font-size:11px;font-weight:700;background:linear-gradient(135deg,#f87171,#ef4444);color:#fff;box-shadow:0 3px 8px rgba(239,68,68,.35);transition:all .2s;letter-spacing:.2px;"
                                                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 5px 14px rgba(239,68,68,.5)'"
                                                    onmouseout="this.style.transform='';this.style.boxShadow='0 3px 8px rgba(239,68,68,.35)'">
                                                    <i class="fas fa-circle" style="font-size:6px"></i> Inactive
                                                </button>
                                            <?php else: ?>
                                                <button type="submit"
                                                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border:none;border-radius:50px;cursor:pointer;font-size:11px;font-weight:700;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;box-shadow:0 3px 8px rgba(34,197,94,.35);transition:all .2s;letter-spacing:.2px;"
                                                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 5px 14px rgba(34,197,94,.5)'"
                                                    onmouseout="this.style.transform='';this.style.boxShadow='0 3px 8px rgba(34,197,94,.35)'">
                                                    <i class="fas fa-circle" style="font-size:6px"></i> Active
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                    </div>
                            
                            </td>
                        </tr>
                        <?php endforeach; endif;?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Pre-render dropdown options once
ob_start();
foreach($departments as $d) echo "<option value='{$d['dep_id']}'>".htmlspecialchars($d['name'])."</option>";
$dept_opts=ob_get_clean();
ob_start();
foreach($designations as $d) echo "<option value='{$d['desg_id']}'>".htmlspecialchars($d['name'])."</option>";
$desg_opts=ob_get_clean();

function modal_header($id,$icon,$title,$subtitle){
    echo "<div class='flex items-center justify-between px-6 py-5' style='border-bottom:1px solid #f1f5f9'>
        <div class='flex items-center gap-3'>
            <div class='w-9 h-9 rounded-xl flex items-center justify-center' style='background:linear-gradient(135deg,#6366f1,#8b5cf6)'>
                <i class='fas $icon text-white text-xs'></i></div>
            <div><h2 class='font-bold text-slate-800'>$title</h2><p class='text-xs text-slate-400' id='{$id}Sub'>$subtitle</p></div>
        </div>
        <button onclick=\"closeModal('$id')\" class='w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100'>
            <i class='fas fa-times text-sm'></i></button></div>";
}
function btn_row($cancel_modal){
    echo "<div class='flex gap-3 pt-1'>
        <button type='button' onclick=\"closeModal('$cancel_modal')\" class='flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500' style='background:#f1f5f9;border:1px solid #e2e8f0' onmouseover=\"this.style.background='#e2e8f0'\" onmouseout=\"this.style.background='#f1f5f9'\">Cancel</button>
        <button type='submit' class='flex-1 py-2.5 rounded-xl text-sm font-semibold text-white' style='background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 4px 14px rgba(99,102,241,.35)'>Save</button>
    </div>";
}
?>

<!-- ── ADD MODAL ── -->
<div id="addModal" class="modal-overlay" onclick="handleOverlay(event,'addModal')">
<div class="modal-box">
<?php modal_header('addModal','fa-user-plus','Add Employee','Fill in the new employee details'); ?>
<form method="POST" action="employees.php" class="px-6 py-5 space-y-4">
    <input type="hidden" name="action" value="add">
    <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Full Name *</label>
        <input type="text" name="name" class="form-input" placeholder="e.g. Rahul Sharma" required>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Email *</label>
            <input type="email" name="email" class="form-input" required></div>
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
            <input type="tel" name="phone" class="form-input"></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Date of Birth</label>
            <input type="date" name="dob" class="form-input"></div>
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Gender</label>
            <select name="gender" class="form-input"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Department *</label>
            <select name="dep_id" class="form-input" required><option value="">Select</option><?php echo $dept_opts;?></select></div>
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Designation *</label>
            <select name="desg_id" class="form-input" required><option value="">Select</option><?php echo $desg_opts;?></select></div>
    </div>
    <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Address</label>
        <textarea name="address" rows="2" class="form-input" style="resize:none"></textarea></div>
    <div style="border-top:1px solid #f1f5f9;padding-top:14px">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Login Credentials</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Username *</label>
                <input type="text" name="username" class="form-input" required minlength="4" placeholder="Enter Username"></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Password *</label>
                <input type="password" name="password" class="form-input" required minlength="6" placeholder="Enter Password"></div>
        </div>
    </div>
    <?php btn_row('addModal');?>
</form>
</div>
</div>

<!-- ── EDIT MODAL ── -->
<div id="editModal" class="modal-overlay" onclick="handleOverlay(event,'editModal')">
<div class="modal-box">
<?php modal_header('editModal','fa-user-edit','Edit Employee','Update employee details'); ?>
<form method="POST" action="employees.php" class="px-6 py-5 space-y-4">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="emp_id" id="editEmpId">
    <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Full Name *</label>
        <input type="text" name="name" id="editName" class="form-input" required></div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Email *</label>
            <input type="email" name="email" id="editEmail" class="form-input" required></div>
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
            <input type="tel" name="phone" id="editPhone" class="form-input"></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Date of Birth</label>
            <input type="date" name="dob" id="editDob" class="form-input"></div>
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Gender</label>
            <select name="gender" id="editGender" class="form-input"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Department *</label>
            <select name="dep_id" id="editDepId" class="form-input" required><option value="">Select</option><?php echo $dept_opts;?></select></div>
        <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Designation *</label>
            <select name="desg_id" id="editDesgId" class="form-input" required><option value="">Select</option><?php echo $desg_opts;?></select></div>
    </div>
    <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Address</label>
        <textarea name="address" id="editAddress" rows="2" class="form-input" style="resize:none"></textarea></div>
    <?php btn_row('editModal');?>
</form>
</div>
</div>

<!-- ── VIEW MODAL ── -->
<div id="viewModal" class="modal-overlay" onclick="handleOverlay(event,'viewModal')">
<div class="modal-box">
    <div class="px-6 py-5" style="border-bottom:1px solid #f1f5f9">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div id="viewAvatar" class="w-12 h-12 rounded-xl flex items-center justify-center text-lg font-bold text-white flex-shrink-0"
                     style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">A</div>
                <div>
                    <h2 class="font-bold text-slate-800" id="viewName">—</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="px-6 py-4">
        <div class="info-row"><div class="info-label">Email</div><div class="info-value" id="vEmail">—</div></div>
        <div class="info-row"><div class="info-label">Phone</div><div class="info-value" id="vPhone">—</div></div>
        <div class="info-row"><div class="info-label">Department</div>
            <div class="info-value"><span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#e0e7ff;color:#4f46e5" id="vDept">—</span></div>
        </div>
        <div class="info-row"><div class="info-label">Designation</div><div class="info-value" id="vDesg">—</div></div>
        <div class="info-row"><div class="info-label">Date of Birth</div><div class="info-value" id="vDob">—</div></div>
        <div class="info-row"><div class="info-label">Gender</div><div class="info-value" id="vGender">—</div></div>
        <div class="info-row"><div class="info-label">Address</div><div class="info-value" id="vAddress">—</div></div>
    </div>
    <div class="flex gap-3 px-6 py-4" style="border-top:1px solid #f1f5f9">
        <button onclick="closeModal('viewModal')" class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500"
                style="background:#f1f5f9;border:1px solid #e2e8f0" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Close</button>
        <a id="viewCallBtn" href="#" class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold text-white"
           style="background:linear-gradient(135deg,#10b981,#059669)"><i class="fas fa-phone-alt text-xs"></i> Call</a>
    </div>
</div>
</div>





<!-- ── DELETE CONFIRM MODAL ── -->
<div id="deleteModal" class="modal-overlay" onclick="handleOverlay(event,'deleteModal')">
<div class="modal-box modal-sm" style="max-width:400px">
    <div class="px-6 pt-6 pb-2 text-center">
        <!-- Icon -->
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
             style="background:linear-gradient(135deg,#fee2e2,#fecaca)">
            <i class="fas fa-trash-alt text-2xl" style="color:#dc2626"></i>
        </div>
        <h2 class="text-lg font-bold text-slate-800 mb-1">Delete Employee?</h2>
        <p class="text-sm text-slate-400 mb-1">You are about to permanently delete</p>
        <p class="text-sm font-semibold text-slate-700 mb-1" id="deleteEmpName">—</p>
        <p class="text-xs text-slate-400">This action <span class="font-semibold text-red-500">cannot be undone</span>.</p>
    </div>
    <form method="POST" action="employees.php" id="deleteForm" class="px-6 pb-6 pt-4">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="emp_id" id="deleteEmpId">
        <input type="hidden" name="current_filter" id="deleteCurrentFilter">
        <div class="flex gap-3 mt-2">
            <button type="button" onclick="closeModal('deleteModal')"
                class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-slate-500"
                style="background:#f1f5f9;border:1px solid #e2e8f0"
                onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                Cancel
            </button>
            <button type="submit"
                class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
                style="background:linear-gradient(135deg,#ef4444,#dc2626);box-shadow:0 4px 14px rgba(239,68,68,.35)"
                onmouseover="this.style.boxShadow='0 6px 20px rgba(239,68,68,.5)'"
                onmouseout="this.style.boxShadow='0 4px 14px rgba(239,68,68,.35)'">
                <i class="fas fa-trash-alt text-xs mr-1"></i> Yes, Delete
            </button>
        </div>
    </form>
</div>
</div>

<script>

// ==========================
// 🔹 OPEN MODAL (FIXED)
// ==========================
function openDeleteModal(empId, empName, currentFilter) {
    document.getElementById('deleteEmpId').value = empId;
    document.getElementById('deleteEmpName').textContent = empName;
    document.getElementById('deleteCurrentFilter').value = currentFilter;
    openModal('deleteModal');
}


// ==========================
// 🔹 OPEN MODAL (FIXED)
// ==========================
function openModal(id){
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.classList.remove('open');
    });

    setTimeout(() => {
        const modal = document.getElementById(id);
        if(modal){
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    }, 30);
}


// ==========================
// 🔹 CLOSE MODAL
// ==========================
function closeModal(id){
    const modal = document.getElementById(id);
    if(modal){
        modal.classList.remove('open');
    }
    document.body.style.overflow = '';
}


// ==========================
// 🔹 CLICK OUTSIDE CLOSE
// ==========================
function handleOverlay(e,id){
    if(e.target === document.getElementById(id)){
        closeModal(id);
    }
}


// ==========================
// 🔹 VIEW EMPLOYEE (SAFE)
// ==========================
function viewEmployee(e){

    if(!e) return;

    document.getElementById('viewName').textContent = e.name || '—';
    document.getElementById('viewAvatar').textContent = (e.name || '?').charAt(0).toUpperCase();

    document.getElementById('vEmail').textContent = e.email || '—';
    document.getElementById('vPhone').textContent = e.phone || '—';
    document.getElementById('vDept').textContent = e.department_name || '—';
    document.getElementById('vDesg').textContent = e.designation_name || '—';
    document.getElementById('vGender').textContent = e.gender || '—';
    document.getElementById('vAddress').textContent = e.address || '—';

    document.getElementById('vDob').textContent =
        e.dob
        ? new Date(e.dob).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        })
        : '—';

    document.getElementById('viewCallBtn').href =
        e.phone ? 'tel:' + e.phone : '#';

    openModal('viewModal');
}


// ==========================
// 🔹 EDIT EMPLOYEE
// ==========================
function openEditModal(e){

    if(!e) return;

    document.getElementById('editEmpId').value = e.emp_id || '';
    document.getElementById('editName').value = e.name || '';
    document.getElementById('editEmail').value = e.email || '';
    document.getElementById('editPhone').value = e.phone || '';
    document.getElementById('editDob').value = e.dob || '';
    document.getElementById('editGender').value = e.gender || '';
    document.getElementById('editAddress').value = e.address || '';
    document.getElementById('editDepId').value = e.dep_id || '';
    document.getElementById('editDesgId').value = e.desg_id || '';

    document.getElementById('editModalSub').textContent =
        'Editing: ' + (e.name || '');

    openModal('editModal');
}






// ==========================
// 🔹 SEARCH FILTER
// ==========================
function filterTable(){

    const q = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#employeeTable tbody tr');

    let visibleCount = 0;

    rows.forEach(row => {
        const show = row.textContent.toLowerCase().includes(q);
        row.style.display = show ? '' : 'none';

        if(show) visibleCount++;
    });

    document.getElementById('recordCount').textContent =
        visibleCount + ' record' + (visibleCount === 1 ? '' : 's') + ' found';
}

</script>
</body>
</html>