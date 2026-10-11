<!-- includes/header.php -->
<?php
$user_name = $_SESSION['user_name'] ?? 'Head Office';
$user_role = $_SESSION['user_role'] ?? 'head_office';
$role_config = getRoleConfig($user_role);
$role_title = $role_config['title'] ?? 'Staff';
$avatar = $_SESSION['user_photo'] ?? null;
$avatar_initial = strtoupper(substr($user_name, 0, 1));
$notification_count = getUnreadNotificationCount($_SESSION['user_id'] ?? 0);

// Display flash messages
if (isset($_SESSION['flash_message'])) {
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
    echo '<div class="flash-message flash-' . $flash_type . '">' . htmlspecialchars($flash_message) . '</div>';
}
?>
<header class="topbar">
    <div class="flex items-center gap-4">
        <button class="menu-btn mobile-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <span class="text-sm font-semibold text-gray-700" id="pageTitle">
            <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
        </span>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
        <button id="assistantToggleBtn" class="menu-btn" title="Open AI Assistant" aria-label="Open AI Assistant">
            <i class="fas fa-robot"></i>
        </button>
        <button id="themeToggleBtn" class="menu-btn" title="Toggle Dark Mode">
            <i class="fas fa-moon" id="themeToggleIcon"></i>
        </button>
        <label class="language-picker" title="Language">
            <i class="fas fa-language text-gray-500"></i>
            <select id="languageSelector" aria-label="Select language">
                <option value="en" <?php echo ($_SESSION['language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                <option value="am" <?php echo ($_SESSION['language'] ?? 'en') === 'am' ? 'selected' : ''; ?>>አማርኛ</option>
                <option value="om" <?php echo ($_SESSION['language'] ?? 'en') === 'om' ? 'selected' : ''; ?>>Afan Oromo</option>
            </select>
        </label>
        <a href="?page=notifications" class="relative" title="Notifications">
            <i class="fas fa-bell text-xl text-gray-500 cursor-pointer hover:text-gray-700"></i>
            <?php if ($notification_count > 0): ?><span class="notification-dot"></span><span class="absolute -top-2 -right-2 rounded-full bg-red-500 text-white text-[10px] px-1.5"><?php echo $notification_count; ?></span><?php endif; ?>
        </a>
        <div class="user-badge" id="profileTrigger" title="View Profile">
            <div class="avatar" id="userAvatar">
                <?php if ($avatar): ?>
                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" />
                <?php else: ?>
                    <?php echo $avatar_initial; ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="text-sm font-semibold text-gray-800" id="userDisplayName"><?php echo htmlspecialchars($user_name); ?></div>
                <div class="text-xs text-gray-400" id="userRoleDisplay"><?php echo htmlspecialchars($role_title); ?></div>
            </div>
        </div>
    </div>
</header>

<style>
.flash-message {
    padding: 10px 20px;
    margin: 10px 20px 0 20px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
}
.flash-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.flash-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}
</style>
