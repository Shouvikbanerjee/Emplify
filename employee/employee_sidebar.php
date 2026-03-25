<!-- Mobile Topbar -->
<div class="lg:hidden flex items-center justify-between px-5 py-4"
     style="background: #0f172a; border-bottom: 1px solid rgba(99,102,241,0.2);">
    <div class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-lg flex items-center justify-center"
             style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <i class="fas fa-bolt text-white text-xs"></i>
        </div>
        <span class="text-white font-bold text-base" style="letter-spacing: 0.05em;">Emplify</span>
    </div>
    <button onclick="toggleSidebar()"
            class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-white transition-colors"
            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
        <i class="fas fa-bars text-sm"></i>
    </button>
</div>

<!-- Sidebar -->
<div id="sidebar"
     class="fixed top-0 left-0 h-screen w-64 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 flex flex-col overflow-hidden"
     style="background: #0f172a; border-right: 1px solid rgba(99,102,241,0.15);">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-6"
         style="border-bottom: 1px solid rgba(255,255,255,0.06);">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 0 20px rgba(99,102,241,0.4);">
            <i class="fas fa-bolt text-white text-sm"></i>
        </div>
        <span class="text-white font-bold text-xl" style="letter-spacing: 0.03em;">Emplify</span>
    </div>

    <!-- Nav Label -->
    

    <!-- Navigation -->
    <nav class="flex flex-col px-3 space-y-1 flex-1"
         style="overflow-y: auto; scrollbar-width: none; -ms-overflow-style: none;">

        <?php
        $nav_items = [
            ['href' => 'employee_dashboard.php', 'icon' => 'fa-home',         'label' => 'Dashboard'],
            ['href' => 'my_attendance.php',      'icon' => 'fa-clock',        'label' => 'My Attendance'],
            ['href' => 'my_tasks.php',           'icon' => 'fa-tasks',        'label' => 'My Tasks'],
            ['href' => 'submit_report.php',      'icon' => 'fa-file-alt',     'label' => 'Submit Report'],
            ['href' => 'my_leaves.php',          'icon' => 'fa-calendar-alt', 'label' => 'Leave Requests'],
            ['href' => 'feedback.php',           'icon' => 'fa-comment',      'label' => 'Feedback'],
        ];
        $current = basename($_SERVER['PHP_SELF']);
        foreach ($nav_items as $item):
            $active = $current === $item['href'];
        ?>
        <a href="<?php echo $item['href']; ?>"
           class="sidebar-nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 relative"
           data-active="<?php echo $active ? 'true' : 'false'; ?>"
           style="
               <?php if ($active): ?>
                   background: linear-gradient(135deg, rgba(99,102,241,0.2), rgba(139,92,246,0.1));
                   color: #a5b4fc;
                   border: 1px solid rgba(99,102,241,0.3);
               <?php else: ?>
                   color: rgba(148,163,184,0.8);
                   border: 1px solid transparent;
               <?php endif; ?>
           ">

            <?php if ($active): ?>
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 rounded-r-full"
                 style="background: #6366f1;"></div>
            <?php endif; ?>

            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background: <?php echo $active ? 'rgba(99,102,241,0.2)' : 'rgba(255,255,255,0.05)'; ?>;">
                <i class="fas <?php echo $item['icon']; ?> text-xs"
                   style="color: <?php echo $active ? '#a5b4fc' : 'rgba(148,163,184,0.6)'; ?>;"></i>
            </div>

            <span><?php echo $item['label']; ?></span>

            <?php if ($active): ?>
            <div class="ml-auto w-1.5 h-1.5 rounded-full" style="background: #6366f1;"></div>
            <?php endif; ?>

        </a>
        <?php endforeach; ?>

    </nav>

    <!-- Bottom: User + Logout -->
    <div class="mx-3 mb-4 rounded-xl overflow-hidden"
     style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);">

        <!-- User info -->
        <a href="my_profile.php"
        class="flex items-center gap-3 px-3 py-3 text-sm font-medium transition-colors"
        style="color: rgba(125, 225, 250, 0.7); border-bottom: 1px solid rgba(175, 204, 209, 0.88);"
        onmouseover="this.style.color='#8d9ab6'; this.style.background='rgba(9, 42, 61, 0.67)'"
        onmouseout="this.style.color='rgba(148,163,184,0.7)'; this.style.background=''">

            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                <?php echo isset($_SESSION['name']) ? strtoupper(substr($_SESSION['name'], 0, 1)) : 'E'; ?>
            </div>

            <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-white truncate">
                    <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Employee'; ?>
                </div>
                <div class="text-xs truncate" style="color: rgba(148,163,184,0.5);">
                    Employee
                </div>
            </div>

        </a>

        <!-- Logout -->
        <a href="logout.php"
        class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium transition-colors"
        style="color: rgba(148,163,184,0.7);"
        onmouseover="this.style.color='#f87171'; this.style.background='rgba(239,68,68,0.06)'"
        onmouseout="this.style.color='rgba(148,163,184,0.7)'; this.style.background=''">

            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                style="background: rgba(255,255,255,0.05);">
                <i class="fas fa-sign-out-alt text-xs"></i>
            </div>

            <span>Log Out</span>
        </a>

    </div>

</div>

<style>
.sidebar-nav-link:not([data-active="true"]):hover {
    background: rgba(255,255,255,0.05) !important;
    color: #e2e8f0 !important;
}
nav::-webkit-scrollbar { display: none; }
</style>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("-translate-x-full");
}
</script>