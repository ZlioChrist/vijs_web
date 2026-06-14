<?php
// ============================================
// HEADER COMPONENT - Vij Slimee & Aprpiejise
// Tema: Gradient Pink + Kuning-Coral
// Bahasa: Indonesia
// Fitur: Notifikasi, Profile Dropdown, Responsive
// Integrasi: Avatar sync dengan profile.php
// ============================================

// Pastikan functions.php terinclude untuk getDBConnection()
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/functions.php';
}

// Handle Mark All as Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $conn = getDBConnection();
    if ($conn) {
        mysqli_query($conn, "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0");
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get unread notifications count
$conn = getDBConnection();
$unread = 0;
if ($conn) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as c FROM notifications WHERE is_read = 0");
    if ($result) {
        $unread = (int) (mysqli_fetch_assoc($result)['c'] ?? 0);
    }
}

// ============================================
// AVATAR LOGIC - Terintegrasi dengan profile.php
// Prioritas: Session > Database > Default
// ============================================
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$sidebar_avatar = '';

// Prioritas 1: Session (paling cepat, sudah diupdate setelah upload di profile.php)
if (!empty($_SESSION['admin_avatar'])) {
    $avatar_path = '../uploads/avatars/' . $_SESSION['admin_avatar'];
    if (file_exists($avatar_path)) {
        // ⭐ Cache-busting: tambahkan timestamp file
        $sidebar_avatar = $avatar_path . '?v=' . filemtime($avatar_path);
    }
}

// Prioritas 2: Database (fallback jika session kosong)
if (empty($sidebar_avatar) && $conn && isset($_SESSION['admin_id'])) {
    $stmt = mysqli_prepare($conn, "SELECT avatar FROM admins WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['admin_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $db_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($db_data && !empty($db_data['avatar'])) {
            $avatar_path = '../uploads/avatars/' . $db_data['avatar'];
            if (file_exists($avatar_path)) {
                $sidebar_avatar = $avatar_path . '?v=' . filemtime($avatar_path);
                // ✅ Sync ke session agar next load lebih cepat
                $_SESSION['admin_avatar'] = $db_data['avatar'];
            }
        }
    }
}

// Prioritas 3: Default avatar dari ui-avatars.com
if (empty($sidebar_avatar)) {
    $sidebar_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($admin_name) . '&background=FFB6C1&color=000080&size=40';
}
?>

<header class="modern-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            
            <!-- Left: Title & Greeting -->
            <div class="col-lg-6 col-12 mb-3 mb-lg-0">
                <h1 class="page-title mb-1"><?php echo htmlspecialchars($page_title ?? 'Dashboard'); ?></h1>
                <p class="greeting mb-0">
                    <span class="wave">👋</span>
                    Selamat datang, <strong><?php echo htmlspecialchars($admin_name); ?></strong>
                </p>
            </div>
            
            <!-- Right: Notification + Profile -->
            <div class="col-lg-6 col-12">
                <div class="header-right d-flex align-items-center justify-content-lg-end gap-3">
                    
                    <!-- Notification Bell -->
                    <div class="notification-wrapper position-relative">
                        <div class="notification-bell" id="notificationBell" style="cursor: pointer;">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread > 0): ?>
                            <span class="notification-badge"><?php echo $unread; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Notification Dropdown -->
                        <div id="notificationDropdown" class="notification-dropdown" style="display: none;">
                            <div class="notification-dropdown-header">
                                <span><i class="fas fa-bell me-2"></i>Notifikasi</span>
                                <?php if ($unread > 0): ?>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="mark_all_read" class="btn btn-sm btn-light">Tandai semua</button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <div class="notification-list">
                                <?php if ($conn): 
                                    $notifs = mysqli_query($conn, "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
                                    if ($notifs && mysqli_num_rows($notifs) > 0):
                                        while($n = mysqli_fetch_assoc($notifs)):
                                ?>
                                <a class="notification-item <?php echo !$n['is_read'] ? 'is-unread' : ''; ?>" 
                                   href="<?php echo htmlspecialchars($n['link'] ?? '#'); ?>">
                                    <div class="notif-icon-wrapper">
                                        <i class="<?php echo htmlspecialchars($n['icon'] ?? 'fas fa-info-circle'); ?>"></i>
                                    </div>
                                    <div class="notif-text">
                                        <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                                        <small><?php echo htmlspecialchars(substr($n['message'], 0, 50)); ?><?php echo strlen($n['message']) > 50 ? '...' : ''; ?></small>
                                        <span class="time"><?php echo timeAgo($n['created_at']); ?></span>
                                    </div>
                                    <?php if (!$n['is_read']): ?>
                                    <span class="unread-marker"></span>
                                    <?php endif; ?>
                                </a>
                                <?php 
                                        endwhile;
                                    else:
                                ?>
                                <div class="text-center py-3 text-muted"><small>Tidak ada notifikasi</small></div>
                                <?php 
                                    endif;
                                endif; ?>
                            </div>
                            <div class="notification-dropdown-footer">
                                <a href="notifications.php">Lihat Semua <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Dropdown -->
                    <div class="profile-wrapper position-relative">
                        <div class="profile-card" id="profileCard" style="cursor: pointer;">
                            <div class="profile-avatar">
                                <img src="<?php echo htmlspecialchars($sidebar_avatar); ?>" 
                                     alt="Avatar" 
                                     class="sidebar-avatar"
                                     style="width: 40px; height: 40px; border-radius: 12px; object-fit: cover; border: 2px solid white;"
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=FFB6C1&color=000080&size=40'">
                            </div>
                            <div class="profile-info d-none d-md-block">
                                <div class="profile-name"><?php echo htmlspecialchars($admin_name); ?></div>
                                <div class="profile-role"><?php echo ucfirst($_SESSION['admin_role'] ?? 'Admin'); ?></div>
                            </div>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </div>
                        
                        <!-- Profile Dropdown Menu -->
                        <div id="profileDropdown" class="profile-dropdown" style="display: none;">
                            <a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profil Saya</a>
                            <hr class="dropdown-divider">
                            <a class="dropdown-item text-danger" href="logout.php" onclick="return confirm('Yakin logout?');"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                        </div>
                    </div>
                    
                </div>
            </div>
            
        </div>
    </div>
</header>

<style>
:root {
    --pink-coral: #FFB6C1;
    --pink-tua: #FF69B4;
    --tosca: #40E0D0;
    --kuning: #FFD700;
    --biru-dongker: #000080;
    --abu-abu: #808080;
}

.modern-header {
    background: white;
    padding: 20px 0;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(255, 182, 193, 0.15);
    margin-bottom: 30px;
    border: 1px solid rgba(255, 182, 193, 0.2);
}

.page-title {
    font-size: 1.8rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--biru-dongker), var(--pink-tua));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-family: 'Poppins', sans-serif;
}

.greeting {
    color: var(--abu-abu);
    font-size: 0.95rem;
}

.wave {
    display: inline-block;
    animation: wave 2s ease-in-out infinite;
}

@keyframes wave {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(20deg); }
    75% { transform: rotate(-20deg); }
}

.header-right {
    gap: 15px;
}

/* Notification Bell */
.notification-bell {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(255,182,193,0.2), rgba(64,224,208,0.2));
    border: 2px solid var(--pink-coral);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    position: relative;
}

.notification-bell:hover {
    background: linear-gradient(135deg, rgba(255,182,193,0.3), rgba(64,224,208,0.25));
    border-color: var(--tosca);
    transform: scale(1.1);
}

.notification-bell i {
    font-size: 1.2rem;
    color: var(--pink-tua);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: linear-gradient(135deg, var(--pink-tua), var(--kuning-coral));
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    border: 2px solid white;
}

/* Notification Dropdown */
.notification-dropdown {
    position: absolute;
    top: 60px;
    right: 0;
    min-width: 320px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(255, 182, 193, 0.3);
    border: 1px solid rgba(255, 182, 193, 0.2);
    z-index: 1000;
    animation: slideDown 0.25s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notification-dropdown-header {
    padding: 15px;
    background: linear-gradient(135deg, var(--pink-coral), var(--tosca));
    color: white;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
}

.notification-dropdown-header .btn {
    font-size: 0.75rem;
    padding: 3px 8px;
}

.notification-list {
    max-height: 280px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 15px;
    text-decoration: none;
    color: inherit;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}

.notification-item:hover {
    background: linear-gradient(135deg, rgba(255,182,193,0.1), rgba(64,224,208,0.08));
}

.notification-item.is-unread {
    background: rgba(255, 182, 193, 0.08);
    border-left-color: var(--pink-tua);
}

.notif-icon-wrapper {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--pink-coral), var(--tosca));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.notif-text {
    flex: 1;
    min-width: 0;
}

.notif-text strong {
    display: block;
    color: var(--biru-dongker);
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 2px;
}

.notif-text small {
    display: block;
    color: var(--abu-abu);
    font-size: 0.8rem;
    margin-bottom: 2px;
}

.notif-text .time {
    color: var(--abu-abu);
    font-size: 0.7rem;
    opacity: 0.8;
}

.unread-marker {
    width: 7px;
    height: 7px;
    background: var(--pink-tua);
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 15px;
}

.notification-dropdown-footer {
    padding: 12px;
    text-align: center;
    border-top: 1px dashed var(--pink-coral);
}

.notification-dropdown-footer a {
    color: var(--pink-tua);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Profile */
.profile-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 18px;
    background: linear-gradient(135deg, rgba(255,182,193,0.15), rgba(64,224,208,0.15));
    border: 2px solid var(--pink-coral);
    border-radius: 50px;
    transition: all 0.3s ease;
}

.profile-card:hover {
    background: linear-gradient(135deg, rgba(255,182,193,0.25), rgba(64,224,208,0.2));
    border-color: var(--tosca);
    transform: translateY(-2px);
}

.profile-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--pink-coral), var(--kuning));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

.profile-info {
    text-align: left;
}

.profile-name {
    font-weight: 700;
    color: var(--biru-dongker);
    font-size: 0.9rem;
    line-height: 1.1;
}

.profile-role {
    background: linear-gradient(135deg, var(--tosca), var(--tosca-muda));
    color: white;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 600;
    display: inline-block;
    margin-top: 2px;
}

.profile-dropdown {
    position: absolute;
    top: 60px;
    right: 0;
    min-width: 200px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(255, 182, 193, 0.3);
    border: 1px solid rgba(255, 182, 193, 0.2);
    z-index: 1000;
    padding: 8px;
    animation: slideDown 0.25s ease;
}

.profile-dropdown .dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    border-radius: 10px;
    text-decoration: none;
    color: var(--biru-dongker);
    font-weight: 500;
    transition: all 0.2s ease;
}

.profile-dropdown .dropdown-item:hover {
    background: linear-gradient(135deg, rgba(255,182,193,0.15), rgba(64,224,208,0.1));
}

.profile-dropdown .dropdown-divider {
    margin: 8px 0;
    border-top: 1px dashed var(--pink-coral);
}

/* Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: 1.5rem;
        text-align: center;
    }
    
    .greeting {
        text-align: center;
    }
    
    .header-right {
        justify-content: center !important;
    }
    
    .profile-info {
        display: none !important;
    }
    
    .notification-dropdown,
    .profile-dropdown {
        min-width: 90vw;
        right: -75vw;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Notification Dropdown
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const profileCard = document.getElementById('profileCard');
    const profileDropdown = document.getElementById('profileDropdown');
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
            notificationDropdown.style.display = 'none';
        }
        if (!profileCard.contains(e.target) && !profileDropdown.contains(e.target)) {
            profileDropdown.style.display = 'none';
        }
    });
    
    // Toggle notification dropdown
    if (notificationBell) {
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.style.display = notificationDropdown.style.display === 'none' ? 'block' : 'none';
            profileDropdown.style.display = 'none';
        });
    }
    
    // Toggle profile dropdown
    if (profileCard) {
        profileCard.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.style.display = profileDropdown.style.display === 'none' ? 'block' : 'none';
            notificationDropdown.style.display = 'none';
        });
    }
});
</script>