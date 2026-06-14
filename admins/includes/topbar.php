<?php
if (!isset($admin)) {
    $admin = getCurrentAdmin();
}
if (!isset($conn)) {
    global $conn;
}
if (!isset($unread_count)) {
    $unread_count = function_exists('getUnreadNotificationCount') ? getUnreadNotificationCount('admin') ?? 0 : 0;
}
?>

<div class="modern-header">
    <div>
        <h2>👋 Hey, <?php echo isset($admin['name']) ? explode(' ', $admin['name'])[0] : 'Admin'; ?>!</h2>
        <p class="greeting">Selamat datang di dashboard Vij Slimee 💜</p>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <div class="notification-bell" onclick="toggleNotifications()">
            <i class="fas fa-bell"></i>
            <?php if($unread_count > 0): ?>
            <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-light rounded-circle p-2" data-bs-toggle="dropdown" style="border: 3px solid #FFB6C1;">
                <img src="https://ui-avatars.com/api/?name=<?php echo isset($admin['name']) ? urlencode($admin['name']) : 'Admin'; ?>&background=FFB6C1&color=000080" 
                     class="rounded-circle" width="45" height="45">
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2 text-pink-tua"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</div>