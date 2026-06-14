<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Notifikasi';
$conn = getDBConnection();

// Handle Mark All as Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    mysqli_query($conn, "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0");
    header('Location: notifications.php');
    exit;
}

// Handle Mark Single as Read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = $id");
    header('Location: notifications.php');
    exit;
}

// Handle Delete Notification
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM notifications WHERE id = $id");
    header('Location: notifications.php');
    exit;
}

// Get all notifications
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT * FROM notifications WHERE 1=1";

if ($filter === 'unread') {
    $query .= " AND is_read = 0";
} elseif ($filter === 'read') {
    $query .= " AND is_read = 1";
}

$query .= " ORDER BY created_at DESC";
$notifications = mysqli_query($conn, $query);

// Get counts
$total_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM notifications"))['c'] ?? 0;
$unread_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM notifications WHERE is_read = 0"))['c'] ?? 0;
$read_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM notifications WHERE is_read = 1"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin</title>
    
    <!-- CSS Eksternal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Admin Global -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        :root {
            --pink-coral: #FFB6C1;
            --pink-tua: #FF69B4;
            --tosca: #40E0D0;
            --tosca-muda: #7FFFD4;
            --kuning: #FFD700;
            --kuning-coral: #FFA500;
            --biru-dongker: #000080;
            --abu-abu: #808080;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
        }
        
        /* ===== STATS CARDS ===== */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(255, 182, 193, 0.15);
            border: 1px solid rgba(255, 182, 193, 0.2);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .stat-card.total::before { background: linear-gradient(180deg, var(--pink-coral), var(--tosca)); }
        .stat-card.unread::before { background: linear-gradient(180deg, var(--pink-tua), var(--pink-coral)); }
        .stat-card.read::before { background: linear-gradient(180deg, var(--success), #059669); }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(255, 182, 193, 0.25);
        }
        
        .stat-card i {
            font-size: 2.2rem;
            margin-bottom: 12px;
            display: block;
        }
        
        .stat-card.total i { color: var(--biru-dongker); }
        .stat-card.unread i { color: var(--pink-tua); }
        .stat-card.read i { color: var(--success); }
        
        .stat-number {
            font-size: 2.3rem;
            font-weight: 800;
            margin-bottom: 5px;
            color: var(--biru-dongker);
            font-family: 'Poppins', sans-serif;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--abu-abu);
            font-weight: 500;
        }
        
        /* ===== FILTER TABS ===== */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            padding: 5px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50px;
            width: fit-content;
        }
        
        .filter-tab {
            padding: 10px 22px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            color: var(--abu-abu);
            position: relative;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
            color: white;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
        }
        
        .filter-tab:not(.active):hover {
            background: rgba(255, 182, 193, 0.2);
            color: var(--pink-tua);
        }
        
        .filter-tab .count {
            margin-left: 6px;
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        /* ===== NOTIFICATIONS CONTAINER ===== */
        .notifications-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 30px rgba(255, 182, 193, 0.15);
            border: 1px solid rgba(255, 182, 193, 0.2);
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--biru-dongker);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px dashed var(--pink-coral);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title i { color: var(--pink-tua); }
        
        /* ===== NOTIFICATION ITEMS ===== */
        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 18px 20px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(255,182,193,0.06), rgba(64,224,208,0.04));
            border: 1px solid rgba(255, 182, 193, 0.15);
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
            animation: slideIn 0.4s ease forwards;
            opacity: 0;
            transform: translateY(15px);
        }
        
        .notif-item:nth-child(1) { animation-delay: 0.05s; }
        .notif-item:nth-child(2) { animation-delay: 0.1s; }
        .notif-item:nth-child(3) { animation-delay: 0.15s; }
        .notif-item:nth-child(4) { animation-delay: 0.2s; }
        .notif-item:nth-child(5) { animation-delay: 0.25s; }
        
        @keyframes slideIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .notif-item:hover {
            transform: translateX(5px);
            box-shadow: 0 6px 25px rgba(255, 182, 193, 0.2);
            background: linear-gradient(135deg, rgba(255,182,193,0.12), rgba(64,224,208,0.08));
        }
        
        .notif-item.is-unread {
            background: linear-gradient(135deg, rgba(255,182,193,0.15), rgba(64,224,208,0.1));
            border-left-color: var(--pink-tua);
        }
        
        .notif-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--pink-coral), var(--tosca));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(255, 105, 180, 0.25);
        }
        
        .notif-content {
            flex: 1;
            min-width: 0;
        }
        
        .notif-content strong {
            display: block;
            color: var(--biru-dongker);
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 4px;
            line-height: 1.4;
        }
        
        .notif-content p {
            color: var(--abu-abu);
            font-size: 0.9rem;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .notif-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .notif-time {
            color: var(--abu-abu);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .notif-time i { opacity: 0.7; }
        
        .notif-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-action.open {
            background: linear-gradient(135deg, var(--tosca), var(--tosca-muda));
            color: white;
        }
        
        .btn-action.read {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }
        
        .btn-action.delete {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }
        
        .btn-action:hover {
            transform: scale(1.05);
            filter: brightness(1.1);
        }
        
        .unread-marker {
            width: 10px;
            height: 10px;
            background: var(--pink-tua);
            border-radius: 50%;
            flex-shrink: 0;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 70px 30px;
            color: var(--abu-abu);
        }
        
        .empty-state i {
            font-size: 4.5rem;
            margin-bottom: 20px;
            opacity: 0.35;
            background: linear-gradient(135deg, var(--pink-coral), var(--tosca));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .empty-state h4 {
            color: var(--biru-dongker);
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 0.95rem;
        }
        
        /* ===== MARK ALL BUTTON ===== */
        .btn-mark-all-header {
            background: linear-gradient(135deg, var(--tosca), var(--tosca-muda));
            border: none;
            border-radius: 50px;
            padding: 10px 22px;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-mark-all-header:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(64, 224, 208, 0.35);
        }
        
        .btn-mark-all-header:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .filter-tabs {
                width: 100%;
                justify-content: center;
            }
            
            .notif-meta {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .notif-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .notif-item {
                padding: 16px 18px;
            }
        }
        
        @media (max-width: 480px) {
            .page-title { font-size: 1.4rem; }
            .stat-number { font-size: 2rem; }
            
            .notif-icon-wrapper {
                width: 42px;
                height: 42px;
                font-size: 1rem;
            }
            
            .notif-content strong { font-size: 0.95rem; }
            .notif-content p { font-size: 0.85rem; }
            
            .btn-action {
                padding: 5px 10px;
                font-size: 0.75rem;
            }
        }
        
        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="modern-main">
        <!-- Header -->
        <?php include 'includes/header.php'; ?>
        
        <div class="container-fluid px-4">
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card total">
                    <i class="fas fa-inbox"></i>
                    <div class="stat-number"><?php echo number_format($total_count); ?></div>
                    <div class="stat-label">Total Notifikasi</div>
                </div>
                <div class="stat-card unread">
                    <i class="fas fa-envelope-open"></i>
                    <div class="stat-number"><?php echo number_format($unread_count); ?></div>
                    <div class="stat-label">Belum Dibaca</div>
                </div>
                <div class="stat-card read">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-number"><?php echo number_format($read_count); ?></div>
                    <div class="stat-label">Sudah Dibaca</div>
                </div>
            </div>
            
            <!-- Notifications Container -->
            <div class="notifications-container">
                
                <!-- Filter & Actions -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <div class="filter-tabs mb-0">
                        <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            Semua <span class="count">(<?php echo $total_count; ?>)</span>
                        </a>
                        <a href="?filter=unread" class="filter-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                            Belum Dibaca <span class="count">(<?php echo $unread_count; ?>)</span>
                        </a>
                        <a href="?filter=read" class="filter-tab <?php echo $filter === 'read' ? 'active' : ''; ?>">
                            Sudah Dibaca <span class="count">(<?php echo $read_count; ?>)</span>
                        </a>
                    </div>
                    
                    <?php if ($unread_count > 0): ?>
                    <form method="POST">
                        <button type="submit" name="mark_all_read" class="btn-mark-all-header">
                            <i class="fas fa-check-double"></i> Tandai Semua Dibaca
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                
                <!-- Section Title -->
                <h3 class="section-title">
                    <i class="fas fa-list"></i>
                    <?php 
                    echo $filter === 'unread' ? 'Notifikasi Belum Dibaca' : 
                         ($filter === 'read' ? 'Notifikasi Sudah Dibaca' : 'Semua Notifikasi');
                    ?>
                </h3>
                
                <!-- Notification List -->
                <div class="notification-list">
                    <?php if (mysqli_num_rows($notifications) > 0): ?>
                        <?php while($notif = mysqli_fetch_assoc($notifications)): ?>
                        <div class="notif-item <?php echo !$notif['is_read'] ? 'is-unread' : ''; ?>">
                            <div class="notif-icon-wrapper">
                                <i class="<?php echo htmlspecialchars($notif['icon'] ?? 'fas fa-info-circle'); ?>"></i>
                            </div>
                            <div class="notif-content">
                                <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                                <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                <div class="notif-meta">
                                    <span class="notif-time">
                                        <i class="far fa-clock"></i>
                                        <?php 
                                        $timestamp = strtotime($notif['created_at']);
                                        $diff = time() - $timestamp;
                                        if ($diff < 60) echo 'Baru saja';
                                        elseif ($diff < 3600) echo floor($diff / 60) . ' menit yang lalu';
                                        elseif ($diff < 86400) echo floor($diff / 3600) . ' jam yang lalu';
                                        elseif ($diff < 604800) echo floor($diff / 86400) . ' hari yang lalu';
                                        else echo date('d M Y', $timestamp);
                                        ?>
                                    </span>
                                    <div class="notif-actions">
                                        <?php if ($notif['link']): ?>
                                        <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="btn-action open" title="Buka">
                                            <i class="fas fa-external-link-alt"></i> Buka
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!$notif['is_read']): ?>
                                        <a href="?mark_read=1&id=<?php echo $notif['id']; ?>" class="btn-action read" title="Tandai Dibaca">
                                            <i class="fas fa-check"></i> Dibaca
                                        </a>
                                        <?php endif; ?>
                                        <a href="?delete=1&id=<?php echo $notif['id']; ?>" class="btn-action delete" onclick="return confirm('Hapus notifikasi ini?');" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                            <span class="unread-marker" title="Belum dibaca"></span>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h4>Tidak Ada Notifikasi</h4>
                        <p>Tidak ada notifikasi yang ditemukan untuk filter ini.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
            
            <!-- Footer -->
            <footer class="text-center text-muted py-4 mt-4" style="font-size: 0.85rem;">
                <p class="mb-0">
                    © <?php echo date('Y'); ?> <strong>Vij Slimee & Aprpiejise</strong> • Panel Admin
                </p>
            </footer>
            
        </div>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-hide success message if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Add subtle animation to stat cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 200 + (index * 100));
            });
        });
    </script>
</body>
</html>