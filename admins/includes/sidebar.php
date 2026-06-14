<?php
// ============================================
// KOMPONEN SIDEBAR - Vij Slimee & Aprpiejise
// Tema: Gradient Pink + Kuning-Coral
// Bahasa: Indonesia
// Fitur: Avatar Sync dengan Profile.php
// ============================================

// Pastikan functions.php terinclude untuk getDBConnection()
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/functions.php';
}

// Ambil jumlah order pending untuk badge
$conn = getDBConnection();
$pending_orders = 0;
if ($conn) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE order_status = 'pending'");
    if ($result) {
        $pending_orders = (int) (mysqli_fetch_assoc($result)['c'] ?? 0);
    }
}

// Ambil halaman saat ini untuk status aktif
$current_page = basename($_SERVER['PHP_SELF']);
$current_query = $_SERVER['QUERY_STRING'] ?? '';

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
        //  Cache-busting: tambahkan timestamp file
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
                //  Sync ke session agar next load lebih cepat
                $_SESSION['admin_avatar'] = $db_data['avatar'];
            }
        }
    }
}

// Prioritas 3: Default avatar dari ui-avatars.com
if (empty($sidebar_avatar)) {
    $sidebar_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($admin_name) . '&background=FFFFFF&color=FF69B4&size=40';
}
?>

<aside class="modern-sidebar" id="sidebar">
    
    <!-- Brand dengan Logo -->
    <div class="sidebar-brand">
        <div class="brand-icon" style="background: transparent; box-shadow: none;">
    <?php if (file_exists('../uploads/logo.png')): ?>
        <img src="../uploads/logo.png" 
             alt="Logo" 
             class="brand-logo"
             style="background: transparent; border-radius: 50%;">
    <?php else: ?>
        <i class="fas fa-sparkles"></i>
    <?php endif; ?>
</div>
        <div class="brand-text">
            <span class="brand-name">Vij Slimee</span>
            <span class="brand-tagline">& Aprpiejise</span>
        </div>
    </div>
    
    <!-- Navigasi -->
    <nav class="sidebar-nav">
        
        <!-- Menu Utama -->
        <div class="nav-section">
            <span class="nav-label">Utama</span>
            <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'is-active' : ''; ?>">
                <span class="nav-icon icon-pink">
                    <i class="fas fa-home"></i>
                </span>
                <span class="nav-text">Dashboard</span>
            </a>
        </div>
        
        <!-- Produk -->
        <div class="nav-section">
            <span class="nav-label">Produk</span>
            <a href="products.php" class="nav-link <?php echo $current_page === 'products.php' && empty($current_query) ? 'is-active' : ''; ?>">
                <span class="nav-icon icon-tosca">
                    <i class="fas fa-box"></i>
                </span>
                <span class="nav-text">Semua Produk</span>
            </a>
        </div>
        
        <!-- Orders -->
        <div class="nav-section">
            <span class="nav-label">Pesanan</span>
            <a href="orders.php" class="nav-link <?php echo $current_page === 'orders.php' ? 'is-active' : ''; ?>">
                <span class="nav-icon icon-kuning">
                    <i class="fas fa-shopping-cart"></i>
                </span>
                <span class="nav-text">Semua Pesanan</span>
                <?php if ($pending_orders > 0): ?>
                <span class="nav-badge"><?php echo $pending_orders; ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <!-- Pembukuan -->
        <div class="nav-section">
            <span class="nav-label">Pembukuan</span>
            <a href="transactions.php" class="nav-link <?php echo $current_page === 'transactions.php' && empty($current_query) ? 'is-active' : ''; ?>">
                <span class="nav-icon icon-gradient">
                    <i class="fas fa-wallet"></i>
                </span>
                <span class="nav-text">Transaksi</span>
            </a>
        </div>
        
        <!-- Pelanggan -->
        <div class="nav-section">
            <span class="nav-label">Pelanggan</span>
            <a href="customers.php" class="nav-link <?php echo $current_page === 'customers.php' ? 'is-active' : ''; ?>">
                <span class="nav-icon icon-purple">
                    <i class="fas fa-users"></i>
                </span>
                <span class="nav-text">Daftar Pelanggan</span>
            </a>
        </div>
        
        
        <!-- Logout (Bawah) -->
        <div class="nav-section nav-section-bottom">
            <a href="logout.php" class="nav-link nav-logout" onclick="return confirm('Yakin ingin logout?');">
                <span class="nav-icon icon-red">
                    <i class="fas fa-sign-out-alt"></i>
                </span>
                <span class="nav-text">Logout</span>
            </a>
        </div>
        
    </nav>
    
    <!-- Profil User (Bawah) - Dengan Avatar yang Sync -->
    <div class="sidebar-footer">
        <a href="profile.php" class="user-profile-link" title="Edit Profil">
            <div class="user-profile">
                <div class="user-avatar-wrapper">
                    <img src="<?php echo htmlspecialchars($sidebar_avatar); ?>" 
                         alt="Avatar" 
                         class="user-avatar"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=FFFFFF&color=FF69B4&size=40'">
                    <span class="avatar-edit-indicator">
                        <i class="fas fa-pen"></i>
                    </span>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="user-role"><?php echo ucfirst($_SESSION['admin_role'] ?? 'Admin'); ?></span>
                </div>
            </div>
        </a>
    </div>
    
</aside>

<style>
/* ============================================
   STYLE SIDEBAR - Gradient Pink + Kuning Coral
   Warna: Pink-Coral, Pink-Tua, Kuning-Coral
   ============================================ */

:root {
    --pink-coral: #FFB6C1;
    --pink-tua: #FF69B4;
    --tosca: #40E0D0;
    --tosca-muda: #7FFFD4;
    --kuning: #FFD700;
    --kuning-coral: #FFA500;
    --biru-dongker: #000080;
    --abu-abu: #808080;
    --white: #ffffff;
    
    /* ✅ GRADIENT PINK + KUNING CORAL */
    --sidebar-gradient: linear-gradient(180deg, 
        #FFB6C1 0%,
        #FF69B4 35%,
        #FFA500 75%,
        #FFD700 100%
    );
    
    --sidebar-hover: rgba(255, 255, 255, 0.25);
    --sidebar-active: rgba(255, 255, 255, 0.4);
    --text-primary: #000080;
    --text-secondary: #404080;
    --text-muted: #606090;
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
}

/* Container Sidebar - BACKGROUND GRADIENT */
.modern-sidebar {
    width: 260px;
    height: 100vh;
    background: var(--sidebar-gradient);
    background-size: 200% 200%;
    animation: gradientFlow 18s ease infinite;
    position: fixed;
    left: 0;
    top: 0;
    padding: 20px 16px;
    display: flex;
    flex-direction: column;
    z-index: 1000;
    border-right: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 4px 0 35px rgba(255, 105, 180, 0.35);
    transition: transform 0.3s ease;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Section Brand - Efek Glass */
.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    margin-bottom: 24px;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(12px);
    border-radius: var(--radius-md);
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 6px 20px rgba(255, 255, 255, 0.25);
}

.brand-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-sm);
    background: transparent !important; /* Hilangkan background */
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--pink-tua);
    animation: float 3s ease-in-out infinite;
    box-shadow: none !important; /* Hilangkan shadow */
    overflow: visible; /* Agar logo tidak terpotong */
}

.brand-logo {
    width: 100%;
    height: 100%;
    object-fit: contain; /* Logo tetap proporsional */
    border-radius: 50%; /* Pastikan bulat */
    background: transparent;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

.brand-text {
    display: flex;
    flex-direction: column;
}

.brand-name {
    color: var(--biru-dongker);
    font-weight: 800;
    font-size: 1.05rem;
    font-family: 'Poppins', sans-serif;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.6);
}

.brand-tagline {
    color: var(--text-primary);
    font-size: 0.8rem;
    font-weight: 600;
}

/* Navigasi */
.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding-right: 4px;
}

.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.25);
    border-radius: 2px;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.6);
    border-radius: 2px;
}

.nav-section {
    margin-bottom: 8px;
}

.nav-label {
    display: block;
    color: var(--biru-dongker);
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 12px 14px 6px;
    opacity: 0.85;
}

.nav-section-bottom {
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.5);
}

/* Link Navigasi */
.nav-link {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 14px;
    color: var(--text-primary);
    text-decoration: none;
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 0.92rem;
    transition: all 0.25s ease;
    position: relative;
    background: rgba(255, 255, 255, 0.2);
}

.nav-link:hover {
    background: var(--sidebar-hover);
    transform: translateX(4px);
    box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
}

.nav-link.is-active {
    background: var(--sidebar-active);
    color: var(--biru-dongker);
    border-left: 3px solid var(--biru-dongker);
    padding-left: 11px;
    box-shadow: 0 4px 20px rgba(255, 255, 255, 0.4);
}

.nav-link.is-active .nav-icon {
    transform: scale(1.08);
    box-shadow: 0 4px 15px rgba(255, 255, 255, 0.5);
}

/* Pertahankan warna gradient untuk setiap icon saat active */
.nav-link.is-active .icon-pink { 
    background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); 
}
.nav-link.is-active .icon-tosca { 
    background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); 
}
.nav-link.is-active .icon-kuning { 
    background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); 
}
.nav-link.is-active .icon-gradient { 
    background: linear-gradient(135deg, var(--tosca), var(--pink-coral)); 
}
.nav-link.is-active .icon-purple { 
    background: linear-gradient(135deg, #A78BFA, #8B5CF6); 
}
.nav-link.is-active .icon-orange { 
    background: linear-gradient(135deg, var(--kuning-coral), var(--kuning)); 
}
.nav-link.is-active .icon-red { 
    background: linear-gradient(135deg, #EF4444, #DC2626); 
}
.nav-link.is-active .icon-success { 
    background: linear-gradient(135deg, #10B981, #059669); 
}

/* Ikon Navigasi */
.nav-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: var(--white);
    transition: all 0.25s ease;
    flex-shrink: 0;
}

.nav-link:hover .nav-icon {
    transform: scale(1.1) rotate(5deg);
}

/* Warna Ikon */
.icon-pink { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); }
.icon-tosca { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); }
.icon-kuning { background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); }
.icon-gradient { background: linear-gradient(135deg, var(--tosca), var(--pink-coral)); }
.icon-success { background: linear-gradient(135deg, #10B981, #059669); }
.icon-danger { background: linear-gradient(135deg, #EF4444, #DC2626); }
.icon-purple { background: linear-gradient(135deg, #A78BFA, #8B5CF6); }
.icon-orange { background: linear-gradient(135deg, var(--kuning-coral), var(--kuning)); }
.icon-red { background: linear-gradient(135deg, #EF4444, #DC2626); }

/* Badge */
.nav-badge {
    margin-left: auto;
    background: var(--biru-dongker);
    color: var(--white);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
    animation: pulse 2s infinite;
    box-shadow: 0 2px 8px rgba(0, 0, 128, 0.35);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.08); }
}

/* Link Logout */
.nav-logout {
    color: var(--biru-dongker);
    font-weight: 700;
}

.nav-logout:hover {
    background: rgba(239, 68, 68, 0.25);
    color: #DC2626;
}

.nav-logout .nav-icon {
    background: rgba(239, 68, 68, 0.3);
}

/* Footer / Profil User - Efek Glass */
.sidebar-footer {
    padding-top: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.5);
}

.user-profile-link {
    text-decoration: none;
    display: block;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(12px);
    border-radius: var(--radius-md);
    border: 1px solid rgba(255, 255, 255, 0.5);
    transition: all 0.25s ease;
}

.user-profile-link:hover .user-profile {
    background: rgba(255, 255, 255, 0.45);
    transform: translateX(3px);
    box-shadow: 0 4px 15px rgba(255, 255, 255, 0.4);
}

.user-avatar-wrapper {
    position: relative;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    object-fit: cover;
    border: 2px solid var(--white);
    box-shadow: 0 2px 10px rgba(255, 255, 255, 0.4);
    transition: all 0.25s ease;
}

.user-profile-link:hover .user-avatar {
    transform: scale(1.08);
}

.avatar-edit-indicator {
    position: absolute;
    bottom: -3px;
    right: -3px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--pink-tua);
    color: white;
    font-size: 0.6rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.user-profile-link:hover .avatar-edit-indicator {
    opacity: 1;
}

.user-info {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
    flex: 1;
}

.user-info .user-name {
    color: var(--biru-dongker);
    font-weight: 700;
    font-size: 0.9rem;
}

.user-info .user-role {
    color: var(--text-primary);
    font-size: 0.75rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.5);
    padding: 2px 8px;
    border-radius: 6px;
    display: inline-block;
    margin-top: 3px;
}

/* Responsive - Mobile */
@media (max-width: 1024px) {
    .modern-sidebar {
        transform: translateX(-100%);
    }
    
    .modern-sidebar.show {
        transform: translateX(0);
    }
}

@media (max-width: 480px) {
    .modern-sidebar {
        width: 240px;
    }
    
    .brand-name { font-size: 0.95rem; }
    .brand-tagline { font-size: 0.75rem; }
    .nav-text { font-size: 0.9rem; }
}
</style>

<script>
// Fungsi toggle sidebar (untuk mobile)
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
            
            // Toggle ikon
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });
        
        // Tutup sidebar ketika klik di luar
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024 && 
                sidebar.classList.contains('show') && 
                !sidebar.contains(e.target) && 
                !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
                const icon = toggleBtn.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }
});
</script>