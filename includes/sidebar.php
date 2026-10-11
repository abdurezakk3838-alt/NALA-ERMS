<!-- includes/sidebar.php -->
<?php
$current_page = $_GET['page'] ?? 'dashboard';
$role = $_SESSION['user_role'] ?? 'staff';
?>
<aside class="sidebar" id="sidebar">
    <div class="p-5 flex items-center gap-3 border-b border-white/10">
        <img src="images/logo.png" alt="NALA Logo" class="h-12 mx-auto mb-2"
             onerror="this.src='https://via.placeholder.com/150x50?text=NALA+ERMS'" />
        <div class="text-white font-bold text-2xl leading-tight tracking-tight">ERMS</div>
        <div><div class="text-white/50 text-[0.5rem] uppercase tracking-wider">NALA · ERMS</div></div>
    </div>

    <nav class="mt-4 pb-20" id="sidebarNav">
        <div class="px-4 py-2 text-white/30 text-[0.6rem] font-semibold uppercase tracking-wider">Main</div>
        <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
            <i class="fas fa-th-large"></i> <span>Dashboard</span>
        </a>
        <?php if ($role === 'archivist' && hasPermission('archive')): ?>
        <a class="nav-link <?php echo $current_page === 'archive' ? 'active' : ''; ?>" href="?page=archive">
            <i class="fas fa-archive"></i> <span>Archive</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('incoming')): ?>
        <a class="nav-link <?php echo $current_page === 'incoming' ? 'active' : ''; ?>" href="?page=incoming">
            <i class="fas fa-inbox"></i> <span>Incoming Letters</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('outgoing')): ?>
        <a class="nav-link <?php echo $current_page === 'outgoing' ? 'active' : ''; ?>" href="?page=outgoing">
            <i class="fas fa-paper-plane"></i> <span>Outgoing Letters</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('internal')): ?>
        <a class="nav-link <?php echo $current_page === 'internal' ? 'active' : ''; ?>" href="?page=internal">
            <i class="fas fa-envelope"></i> <span>Internal Memos</span>
        </a>
        <?php endif; ?>

        <div class="px-4 py-2 mt-4 text-white/30 text-[0.6rem] font-semibold uppercase tracking-wider">Management</div>
        <?php if (hasPermission('records')): ?>
        <a class="nav-link <?php echo $current_page === 'records' ? 'active' : ''; ?>" href="?page=records">
            <i class="fas fa-folder-open"></i> <span>Records</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('workflow')): ?>
        <a class="nav-link <?php echo $current_page === 'workflow' ? 'active' : ''; ?>" href="?page=workflow">
            <i class="fas fa-diagram-project"></i> <span>Workflow</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('archive') && $role !== 'archivist'): ?>
        <a class="nav-link <?php echo $current_page === 'archive' ? 'active' : ''; ?>" href="?page=archive">
            <i class="fas fa-archive"></i> <span>Archive</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('search')): ?>
        <a class="nav-link <?php echo $current_page === 'search' ? 'active' : ''; ?>" href="?page=search">
            <i class="fas fa-search"></i> <span>Search</span>
        </a>
        <?php if (hasPermission('notifications')): ?>
        <a class="nav-link <?php echo $current_page === 'notifications' ? 'active' : ''; ?>" href="?page=notifications">
            <i class="fas fa-bell"></i> <span>Notifications</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($role === 'archivist' && hasPermission('notifications')): ?>
        <a class="nav-link <?php echo $current_page === 'notifications' ? 'active' : ''; ?>" href="?page=notifications">
            <i class="fas fa-bell"></i> <span>Notifications</span>
        </a>
        <?php endif; ?>

        <div class="px-4 py-2 mt-4 text-white/30 text-[0.6rem] font-semibold uppercase tracking-wider">Admin</div>
        <?php if (hasPermission('users')): ?>
        <a class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>" href="?page=users">
            <i class="fas fa-users-cog"></i> <span>User Management</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('audit')): ?>
        <a class="nav-link <?php echo $current_page === 'audit' ? 'active' : ''; ?>" href="?page=audit">
            <i class="fas fa-clipboard-list"></i> <span>Audit Trail</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('reports')): ?>
        <a class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>" href="?page=reports">
            <i class="fas fa-chart-bar"></i> <span>Reports</span>
        </a>
        <?php endif; ?>

        <div class="px-4 py-2 mt-4 text-white/30 text-[0.6rem] font-semibold uppercase tracking-wider">System</div>
        <?php if (hasPermission('settings')): ?>
        <a class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>" href="?page=settings">
            <i class="fas fa-cog"></i> <span>Settings</span>
        </a>
        <?php endif; ?>
        <a class="nav-link" href="logout.php" style="color:#f87171;">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </nav>
</aside>
