<?php
// ============================================
// DASHBOARD - VIJARIE (Dinamis)
// Tema: Gradient Pink + Kuning-Coral
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Dashboard - VIJARIE';
$conn = getDBConnection();

// ------------------------------------------------------------------
// STATISTIK DARI DATABASE
// ------------------------------------------------------------------
$stats = [
    'orders'        => 0,
    'pending'       => 0,
    'revenue'       => 0,
    'products'      => 0,
    'customers'     => 0,
    'percent_orders'   => 0,   // persen perubahan jumlah pesanan
    'percent_revenue'   => 0,   // persen perubahan pendapatan
    'percent_customers' => 0,   // persen perubahan pelanggan
];

$recent_orders = [];

if ($conn) {
    // ---------- Jumlah total ----------
    $stats['orders']    = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'] ?? 0);
    $stats['pending']   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE order_status='pending'"))['c'] ?? 0);
    $stats['revenue']   = (float)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as t FROM orders WHERE payment_status='paid'"))['t'] ?? 0);
    $stats['products']  = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products WHERE is_active=1"))['c'] ?? 0);
    $stats['customers'] = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM customers WHERE is_active=1"))['c'] ?? 0);

    // ---------- Perubahan persentase (bulan ini vs bulan lalu) ----------
    // 1. Pendapatan
    $current_month_rev = (float)(mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT SUM(total) as t FROM orders 
        WHERE payment_status='paid' 
        AND MONTH(created_at) = MONTH(CURDATE()) 
        AND YEAR(created_at) = YEAR(CURDATE())
    "))['t'] ?? 0);
    $last_month_rev = (float)(mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT SUM(total) as t FROM orders 
        WHERE payment_status='paid' 
        AND MONTH(created_at) = MONTH(CURDATE())-1 
        AND YEAR(created_at) = YEAR(CURDATE())
    "))['t'] ?? 0);
    if ($last_month_rev > 0) {
        $stats['percent_revenue'] = round((($current_month_rev - $last_month_rev) / $last_month_rev) * 100);
    } elseif ($current_month_rev > 0) {
        $stats['percent_revenue'] = 100;
    } else {
        $stats['percent_revenue'] = 0;
    }

    // 2. Jumlah pesanan
    $current_month_ord = (int)(mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as c FROM orders 
        WHERE MONTH(created_at) = MONTH(CURDATE()) 
        AND YEAR(created_at) = YEAR(CURDATE())
    "))['c'] ?? 0);
    $last_month_ord = (int)(mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as c FROM orders 
        WHERE MONTH(created_at) = MONTH(CURDATE())-1 
        AND YEAR(created_at) = YEAR(CURDATE())
    "))['c'] ?? 0);
    if ($last_month_ord > 0) {
        $stats['percent_orders'] = round((($current_month_ord - $last_month_ord) / $last_month_ord) * 100);
    } elseif ($current_month_ord > 0) {
        $stats['percent_orders'] = 100;
    } else {
        $stats['percent_orders'] = 0;
    }

    // 3. Pelanggan baru
    $current_month_cust = (int)(mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as c FROM customers 
        WHERE is_active=1 
        AND MONTH(created_at) = MONTH(CURDATE()) 
        AND YEAR(created_at) = YEAR(CURDATE())
    "))['c'] ?? 0);
    $last_month_cust = (int)(mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as c FROM customers 
        WHERE is_active=1 
        AND MONTH(created_at) = MONTH(CURDATE())-1 
        AND YEAR(created_at) = YEAR(CURDATE())
    "))['c'] ?? 0);
    if ($last_month_cust > 0) {
        $stats['percent_customers'] = round((($current_month_cust - $last_month_cust) / $last_month_cust) * 100);
    } elseif ($current_month_cust > 0) {
        $stats['percent_customers'] = 100;
    } else {
        $stats['percent_customers'] = 0;
    }

    // 4. Pesanan terbaru (5)
    $recent_query = mysqli_query($conn, "
        SELECT id, order_number, customer_name, customer_phone, store_type, total, order_status, created_at 
        FROM orders 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    if ($recent_query) {
        while ($row = mysqli_fetch_assoc($recent_query)) {
            $recent_orders[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            --abu-abu: #6B7280;
            --success: #10B981;
            --danger: #EF4444;
        }
        body { font-family: 'Quicksand', sans-serif; background: linear-gradient(135deg, #FFF5F7 0%, #F0FFFF 100%); }
        .quick-stats { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .quick-stat { background: white; padding: 12px 20px; border-radius: 14px; box-shadow: 0 5px 20px rgba(255,182,193,0.1); display: flex; align-items: center; gap: 10px; font-weight: 600; color: var(--biru-dongker); }
        .quick-stat i { font-size: 1.2rem; color: var(--pink-tua); }
        .stat-card {
            background: white; border-radius: 20px; padding: 24px; box-shadow: 0 8px 30px rgba(255,182,193,0.15);
            border: 1px solid rgba(255,182,193,0.2); transition: 0.3s; height: 100%;
        }
        .stat-card:hover { transform: translateY(-6px); }
        .stat-card.pink { border-left: 4px solid var(--pink-coral); }
        .stat-card.tosca { border-left: 4px solid var(--tosca); }
        .stat-card.kuning { border-left: 4px solid var(--kuning); }
        .stat-card.biru { border-left: 4px solid var(--biru-dongker); }
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .stat-value { font-size: 2.2rem; font-weight: 800; color: var(--biru-dongker); line-height: 1; }
        .stat-label { color: var(--abu-abu); font-size: 0.9rem; font-weight: 600; }
        .stat-icon { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: white; }
        .stat-card.pink .stat-icon { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); }
        .stat-card.tosca .stat-icon { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); }
        .stat-card.kuning .stat-icon { background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); }
        .stat-card.biru .stat-icon { background: linear-gradient(135deg, var(--biru-dongker), var(--tosca)); }
        .stat-change { display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; font-weight: 600; padding: 4px 10px; border-radius: 8px; }
        .stat-change.positive { background: rgba(16,185,129,0.12); color: var(--success); }
        .stat-change.negative { background: rgba(239,68,68,0.12); color: var(--danger); }
        .action-section { background: white; border-radius: 20px; padding: 24px; margin-bottom: 30px; border: 1px solid rgba(255,182,193,0.2); }
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; }
        .action-btn { padding: 20px 16px; border-radius: 16px; text-align: center; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 10px; color: white; font-weight: 600; transition: 0.3s; }
        .action-btn.pink { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); }
        .action-btn.tosca { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); }
        .action-btn.kuning { background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); color: var(--biru-dongker); }
        .action-btn.biru { background: linear-gradient(135deg, var(--biru-dongker), var(--tosca)); }
        .action-btn:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
        .action-btn i { font-size: 1.8rem; transition: 0.3s; }
        .action-btn:hover i { transform: scale(1.1) rotate(3deg); }
        .orders-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid rgba(255,182,193,0.2); }
        .table thead th { background: linear-gradient(135deg, rgba(255,182,193,0.1), rgba(64,224,208,0.1)); color: var(--biru-dongker); font-weight: 700; border: none; padding: 14px; font-size: 0.85rem; text-transform: uppercase; border-bottom: 2px solid var(--pink-coral); }
        .badge-pink { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); color: white; }
        .badge-tosca { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); color: white; }
        .badge-status { padding: 6px 14px; border-radius: 30px; font-weight: 600; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 6px; }
        .badge-status.pending { background: #FFEDD5; color: #C2410C; }
        .badge-status.processing { background: #DBEAFE; color: #1E40AF; }
        .badge-status.shipped { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); color: white; }
        .badge-status.completed { background: #D1FAE5; color: #065F46; }
        .badge-status.cancelled { background: #FEE2E2; color: #991B1B; }
        .empty-state { text-align: center; padding: 50px 20px; color: var(--abu-abu); }
        .dashboard-footer { text-align: center; padding: 30px 20px; color: var(--abu-abu); font-size: 0.85rem; border-top: 1px solid rgba(255,182,193,0.2); margin-top: 30px; }
        @media (max-width: 768px) { .quick-stats { flex-direction: column; } .action-grid { grid-template-columns: repeat(2, 1fr); } .stat-value { font-size: 1.8rem; } }
        @media (max-width: 480px) { .action-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <main class="modern-main">
        <?php include 'includes/header.php'; ?>

        <!-- Quick Stats (3 badge) -->
        <div class="quick-stats">
            <div class="quick-stat"><i class="fas fa-clock"></i> <span><?php echo $stats['pending']; ?> Menunggu</span></div>
            <div class="quick-stat"><i class="fas fa-check-circle"></i> <span><?php echo max(0, $stats['orders'] - $stats['pending']); ?> Selesai</span></div>
            <div class="quick-stat"><i class="fas fa-chart-line"></i> <span><?php echo ($stats['percent_revenue'] >= 0 ? '+' : '') . $stats['percent_revenue']; ?>% Pendapatan</span></div>
        </div>

        <!-- Stat Cards (4 kartu) -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="stat-card pink">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo number_format($stats['orders']); ?></div>
                            <div class="stat-label">Total Pesanan</div>
                            <div class="stat-change <?php echo $stats['percent_orders'] >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-arrow-<?php echo $stats['percent_orders'] >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($stats['percent_orders']); ?>% vs bulan lalu
                            </div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card tosca">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo formatRupiah($stats['revenue']); ?></div>
                            <div class="stat-label">Total Pendapatan</div>
                            <div class="stat-change <?php echo $stats['percent_revenue'] >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-arrow-<?php echo $stats['percent_revenue'] >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($stats['percent_revenue']); ?>% vs bulan lalu
                            </div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card kuning">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo number_format($stats['products']); ?></div>
                            <div class="stat-label">Produk Aktif</div>
                            <div class="stat-change"><i class="fas fa-minus"></i> Stabil</div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-box"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card biru">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo number_format($stats['customers']); ?></div>
                            <div class="stat-label">Total Pelanggan</div>
                            <div class="stat-change <?php echo $stats['percent_customers'] >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-arrow-<?php echo $stats['percent_customers'] >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($stats['percent_customers']); ?>% vs bulan lalu
                            </div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aksi Cepat -->
        <div class="action-section">
            <h4 class="mb-3"><i class="fas fa-bolt"></i> Aksi Cepat</h4>
            <div class="action-grid">
                <a href="products.php?action=add" class="action-btn pink"><i class="fas fa-plus"></i><span>Tambah Produk</span></a>
                <a href="transactions.php?action=add" class="action-btn tosca"><i class="fas fa-plus"></i><span>Tambah Transaksi</span></a>
                <a href="orders.php?status=pending" class="action-btn kuning"><i class="fas fa-clock"></i><span>Pesanan Menunggu</span>
                    <?php if ($stats['pending'] > 0): ?>
                        <span class="badge bg-danger position-absolute top-0 end-0 translate-middle badge rounded-pill" style="font-size: 0.7rem;"><?php echo $stats['pending']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="settings.php" class="action-btn biru"><i class="fas fa-cog"></i><span>Pengaturan</span></a>
            </div>
        </div>

        <!-- Pesanan Terbaru -->
        <div class="orders-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-clock"></i> Pesanan Terbaru</h4>
                <a href="orders.php" class="btn btn-sm" style="background: linear-gradient(135deg, var(--pink-coral), var(--tosca)); color: white; border-radius: 50px; padding: 10px 25px;">Lihat Semua <i class="fas fa-arrow-right ms-2"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>No. Order</th><th>Pelanggan</th><th>Toko</th><th>Total</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_orders)): ?>
                            <?php foreach ($recent_orders as $o): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                                <td><div><?php echo htmlspecialchars($o['customer_name'] ?? 'Tamu'); ?></div><small><?php echo htmlspecialchars($o['customer_phone'] ?? ''); ?></small></td>
                                <td><span class="badge <?php echo ($o['store_type'] ?? '') == 'slime' ? 'badge-pink' : 'badge-tosca'; ?>"><?php echo ucfirst($o['store_type'] ?? 'both'); ?></span></td>
                                <td><?php echo formatRupiah($o['total']); ?></td>
                                <td><span class="badge-status <?php echo strtolower($o['order_status'] ?? 'pending'); ?>">
                                    <i class="fas <?php echo $o['order_status'] == 'shipped' ? 'fa-truck' : ($o['order_status'] == 'completed' ? 'fa-check-circle' : 'fa-clock'); ?>"></i>
                                    <?php echo ucfirst($o['order_status'] ?? 'pending'); ?>
                                </span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5"><div class="empty-state"><i class="fas fa-inbox"></i><p>Belum ada pesanan</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="dashboard-footer">
            <p>&copy; <?php echo date('Y'); ?> <strong>VIJARIE</strong> — Panel Admin. Hak Cipta Dilindungi.</p>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.querySelector('.toggle-sidebar');
            const sidebar = document.querySelector('.modern-sidebar');
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', e => { e.stopPropagation(); sidebar.classList.toggle('show'); });
                document.addEventListener('click', e => {
                    if (window.innerWidth <= 1024 && sidebar.classList.contains('show') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>
</html>