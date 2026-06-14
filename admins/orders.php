<?php
// ============================================
// MANAJEMEN PESANAN - VIJARIE
// Tema: Gradient Pink + Kuning-Coral
// Bahasa: Indonesia
// Keamanan: Prepared Statements
// ============================================
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();
$page_title = 'Pesanan - VIJARIE';
$conn = getDBConnection();
$message = '';
$message_type = '';

// ============================================
// HANDLE UPDATE STATUS PESANAN
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $id = (int)$_POST['order_id'];
    $order_status = sanitize($_POST['order_status'] ?? '');
    $payment_status = sanitize($_POST['payment_status'] ?? '');
    $resi = sanitize($_POST['shipping_resi'] ?? '');
    $carrier = sanitize($_POST['shipping_carrier'] ?? '');
    
    $fields = [];
    $params = [];
    $types = "";
    
    if ($order_status) {
        $fields[] = "order_status = ?";
        $params[] = $order_status;
        $types .= "s";
        if ($order_status == 'shipped') $fields[] = "shipped_at = NOW()";
        if ($order_status == 'delivered' || $order_status == 'completed') $fields[] = "completed_at = NOW()";
    }
    if ($payment_status) {
        $fields[] = "payment_status = ?";
        $params[] = $payment_status;
        $types .= "s";
        if ($payment_status == 'paid') $fields[] = "paid_at = NOW()";
    }
    if ($resi) { $fields[] = "shipping_resi = ?"; $params[] = $resi; $types .= "s"; }
    if ($carrier) { $fields[] = "shipping_carrier = ?"; $params[] = $carrier; $types .= "s"; }
    
    if (empty($fields)) {
        $message = 'Tidak ada perubahan status';
        $message_type = 'warning';
    } else {
        $params[] = $id;
        $types .= "i";
        $query = "UPDATE orders SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Status pesanan berhasil diperbarui!';
                $message_type = 'success';
                header("Location: orders.php?view=$id&updated=1");
                exit;
            } else {
                $message = 'Gagal update: ' . mysqli_stmt_error($stmt);
                $message_type = 'error';
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = 'Prepare failed: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

if (isset($_GET['updated']) && $_GET['updated'] == 1) {
    $message = 'Status pesanan berhasil diperbarui!';
    $message_type = 'success';
}

// ============================================
// FILTER & PENCARIAN
// ============================================
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_store = $_GET['store'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $where .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
    $sp = "%$search%";
    $params = array_merge($params, [$sp, $sp, $sp]);
    $types .= "sss";
}
if ($filter_status) { $where .= " AND o.order_status = ?"; $params[] = $filter_status; $types .= "s"; }
if ($filter_store) { $where .= " AND o.store_type = ?"; $params[] = $filter_store; $types .= "s"; }
if ($date_from) { $where .= " AND DATE(o.created_at) >= ?"; $params[] = $date_from; $types .= "s"; }
if ($date_to) { $where .= " AND DATE(o.created_at) <= ?"; $params[] = $date_to; $types .= "s"; }

$count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM orders o $where");
if (!empty($params)) mysqli_stmt_bind_param($count_stmt, $types, ...$params);
mysqli_stmt_execute($count_stmt);
$total_orders = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
mysqli_stmt_close($count_stmt);

$stmt = mysqli_prepare($conn, "SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id $where ORDER BY o.created_at DESC LIMIT 50");
if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);

// ============================================
// DETAIL PESANAN
// ============================================
$view_order = null;
$order_items = null;
if (isset($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    $stmt_detail = mysqli_prepare($conn, "SELECT o.*, c.name as customer_name, c.email as customer_email, c.address as customer_address, c.city, c.province, c.postal_code FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
    mysqli_stmt_bind_param($stmt_detail, "i", $view_id);
    mysqli_stmt_execute($stmt_detail);
    $view_order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_detail));
    mysqli_stmt_close($stmt_detail);
    if ($view_order) {
        $stmt_items = mysqli_prepare($conn, "SELECT * FROM order_items WHERE order_id = ?");
        mysqli_stmt_bind_param($stmt_items, "i", $view_id);
        mysqli_stmt_execute($stmt_items);
        $order_items = mysqli_stmt_get_result($stmt_items);
        mysqli_stmt_close($stmt_items);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
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
            --white: #ffffff;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
        }
        body { font-family: 'Quicksand', sans-serif; background: linear-gradient(135deg, #FFF5F7 0%, #F0FFFF 100%); }
        .order-row { animation: slideIn 0.4s ease forwards; opacity: 0; transform: translateY(12px); }
        .order-row:nth-child(1) { animation-delay: 0.05s; }
        .order-row:nth-child(2) { animation-delay: 0.1s; }
        .order-row:nth-child(3) { animation-delay: 0.15s; }
        .order-row:nth-child(4) { animation-delay: 0.2s; }
        .order-row:nth-child(5) { animation-delay: 0.25s; }
        @keyframes slideIn { to { opacity: 1; transform: translateY(0); } }
        .order-row:hover { background: linear-gradient(135deg, rgba(255,182,193,0.08), rgba(64,224,208,0.08)); transform: translateX(3px); box-shadow: 0 3px 15px rgba(255,182,193,0.12); }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { color: var(--biru-dongker); font-weight: 800; font-size: 1.6rem; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 12px; margin: 0; }
        .page-title i { color: var(--pink-tua); font-size: 1.4rem; }
        .stats-summary { display: flex; gap: 12px; flex-wrap: wrap; }
        .stat-pill { background: white; padding: 8px 16px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; box-shadow: 0 3px 12px rgba(255,182,193,0.15); border: 1px solid rgba(255,182,193,0.2); }
        .stat-pill.pending { color: var(--warning); }
        .stat-pill.processing { color: var(--info); }
        .stat-pill.shipped { color: var(--pink-tua); }
        .filter-section { background: white; border-radius: 18px; padding: 18px 20px; margin-bottom: 25px; box-shadow: 0 6px 25px rgba(255,182,193,0.12); border: 1px solid rgba(255,182,193,0.2); display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; min-width: 150px; flex: 1; }
        .filter-group label { font-size: 0.78rem; font-weight: 600; color: var(--biru-dongker); }
        .filter-section .form-control, .filter-section .form-select { border: 2px solid var(--pink-coral); border-radius: 12px; padding: 10px 14px; font-size: 0.9rem; background: white; }
        .btn-filter { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); border: none; border-radius: 12px; padding: 10px 20px; color: white; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; height: 42px; }
        .btn-reset { background: rgba(128,128,128,0.12); border: none; border-radius: 12px; padding: 10px 20px; color: var(--abu-abu); font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; height: 42px; }
        .orders-table { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 8px 30px rgba(255,182,193,0.15); border: 1px solid rgba(255,182,193,0.2); overflow-x: auto; }
        .orders-table thead th { background: linear-gradient(135deg, rgba(255,182,193,0.12), rgba(64,224,208,0.12)); color: var(--biru-dongker); font-weight: 700; border: none; padding: 14px 12px; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid var(--pink-coral); white-space: nowrap; }
        .badge-order { padding: 6px 14px; border-radius: 30px; font-weight: 600; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .badge-order.pending { background: #FFEDD5; color: #C2410C; }
        .badge-order.processing { background: #DBEAFE; color: #1E40AF; }
        .badge-order.packed { background: #E0E7FF; color: #3730A3; }
        .badge-order.shipped { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); color: white; }
        .badge-order.completed { background: #D1FAE5; color: #065F46; }
        .badge-order.cancelled { background: #FEE2E2; color: #991B1B; }
        .badge-payment { padding: 6px 14px; border-radius: 30px; font-weight: 600; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 6px; }
        .badge-payment.pending { background: #FFEDD5; color: #C2410C; }
        .badge-payment.paid { background: #D1FAE5; color: #065F46; }
        .badge-payment.failed { background: #FEE2E2; color: #991B1B; }
        .badge-store { padding: 5px 12px; border-radius: 30px; font-weight: 600; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px; }
        .badge-store.slime { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); color: white; }
        .badge-store.photocard { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); color: white; }
        .badge-store.both { background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); color: var(--biru-dongker); }
        .btn-view-order { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); border: none; border-radius: 30px; padding: 7px 16px; color: white; font-weight: 600; font-size: 0.8rem; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-view-order:hover { transform: translateY(-2px); box-shadow: 0 5px 18px rgba(255,105,180,0.4); color: white; }
        .order-detail-card { background: white; border-radius: 24px; padding: 28px; box-shadow: 0 10px 40px rgba(255,182,193,0.18); border: 1px solid rgba(255,182,193,0.2); margin-bottom: 30px; }
        .order-number { font-size: 1.4rem; font-weight: 800; color: var(--biru-dongker); display: flex; align-items: center; gap: 10px; }
        .info-box { background: rgba(255,182,193,0.08); border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(255,182,193,0.2); }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed rgba(0,0,0,0.05); flex-wrap: wrap; }
        .status-form { background: rgba(255,182,193,0.06); border-radius: 16px; padding: 24px; margin-top: 25px; border: 1px solid rgba(255,182,193,0.2); }
        .btn-update { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); border: none; border-radius: 60px; padding: 12px 32px; font-weight: 700; color: white; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; width: 100%; justify-content: center; }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(255,105,180,0.45); }
        .alert-custom { border-radius: 14px; padding: 12px 16px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease; }
        .alert-custom.success { background: #D1FAE5; color: #059669; border-left: 4px solid var(--success); }
        .alert-custom.warning { background: #FEF3C7; color: #92400E; border-left: 4px solid var(--warning); }
        .alert-custom.error { background: #FEE2E2; color: #DC2626; border-left: 4px solid var(--danger); }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--abu-abu); }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.4; }
        footer { text-align: center; padding: 20px; color: var(--abu-abu); font-size: 0.8rem; border-top: 1px solid rgba(255,182,193,0.2); margin-top: 30px; }
        @media (max-width: 992px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .filter-section { flex-direction: column; align-items: stretch; }
            .filter-group { width: 100%; }
            .btn-filter, .btn-reset { justify-content: center; }
        }
        @media (max-width: 768px) {
            .orders-table { overflow-x: auto; }
            .orders-table table { min-width: 850px; }
            .info-row { flex-direction: column; gap: 5px; }
            .info-value { text-align: left; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <main class="modern-main">
        <?php include 'includes/header.php'; ?>
        
        <?php if ($message): ?>
        <div class="alert-custom <?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.style.display='none'" style="background:none;border:none;font-size:1.2rem;opacity:0.7;"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($view_order): ?>
        <!-- Detail Pesanan -->
        <div class="order-detail-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <h2 class="order-number"><i class="fas fa-receipt"></i> <?php echo htmlspecialchars($view_order['order_number']); ?></h2>
                <a href="orders.php" class="btn btn-secondary btn-sm rounded-pill"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="info-box">
                        <h6 class="fw-bold mb-3"><i class="fas fa-user"></i> Data Pelanggan</h6>
                        <div class="info-row"><span class="text-muted">Nama</span><span><?php echo htmlspecialchars($view_order['customer_name']); ?></span></div>
                        <div class="info-row"><span class="text-muted">Telepon</span><span><?php echo htmlspecialchars($view_order['customer_phone']); ?></span></div>
                        <div class="info-row"><span class="text-muted">Email</span><span><?php echo htmlspecialchars($view_order['customer_email']??'-'); ?></span></div>
                        <div class="info-row"><span class="text-muted">Alamat</span><span><?php echo nl2br(htmlspecialchars($view_order['customer_address']??'')); ?><br><?php echo htmlspecialchars($view_order['city']??''); ?>, <?php echo htmlspecialchars($view_order['province']??''); ?> <?php echo htmlspecialchars($view_order['postal_code']??''); ?></span></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <h6 class="fw-bold mb-3"><i class="fas fa-clipboard-list"></i> Detail Pesanan</h6>
                        <div class="info-row"><span class="text-muted">Tanggal</span><span><?php echo date('d M Y, H:i', strtotime($view_order['created_at'])); ?></span></div>
                        <div class="info-row"><span class="text-muted">Toko</span><span class="badge-store <?php echo htmlspecialchars($view_order['store_type']); ?>"><i class="fas <?php echo $view_order['store_type']=='slime'?'fa-bezier-curve':'fa-camera'; ?>"></i> <?php echo ucfirst($view_order['store_type']); ?></span></div>
                        <div class="info-row"><span class="text-muted">Pembayaran</span><span class="badge-payment <?php echo htmlspecialchars($view_order['payment_status']); ?>"><i class="fas <?php echo $view_order['payment_status']=='paid'?'fa-check-circle':'fa-clock'; ?>"></i> <?php echo ucfirst($view_order['payment_status']); ?></span></div>
                        <div class="info-row"><span class="text-muted">Status</span><span class="badge-order <?php echo htmlspecialchars($view_order['order_status']); ?>"><i class="fas <?php echo $view_order['order_status']=='shipped'?'fa-truck':($view_order['order_status']=='completed'?'fa-check-circle':'fa-clock'); ?>"></i> <?php echo ucfirst($view_order['order_status']); ?></span></div>
                        <?php if ($view_order['shipping_resi']): ?>
                        <div class="info-row"><span class="text-muted">No. Resi</span><span><?php echo htmlspecialchars($view_order['shipping_resi']); ?></span></div>
                        <?php endif; ?>
                        <?php if ($view_order['shipping_carrier']): ?>
                        <div class="info-row"><span class="text-muted">Kurir</span><span><?php echo htmlspecialchars($view_order['shipping_carrier']); ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <h5 class="mt-4"><i class="fas fa-box-open"></i> Item Pesanan</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light"><tr><th>Produk</th><th>SKU</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
                    <tbody><?php if($order_items && mysqli_num_rows($order_items)>0): while($item=mysqli_fetch_assoc($order_items)): ?><tr><td><?php echo htmlspecialchars($item['product_name']); ?></td><td><?php echo htmlspecialchars($item['product_sku']??'-'); ?></td><td><?php echo formatRupiah($item['price']); ?></td><td><?php echo $item['quantity']; ?></td><td><?php echo formatRupiah($item['subtotal']); ?></td></tr><?php endwhile; else: ?><tr><td colspan="5" class="text-center">Tidak ada item</td></tr><?php endif; ?></tbody>
                    <tfoot><tr><td colspan="4" class="text-end">Subtotal</td><td><?php echo formatRupiah($view_order['subtotal']); ?></td></tr><tr><td colspan="4" class="text-end">Ongkir</td><td><?php echo formatRupiah($view_order['shipping_cost']??0); ?></td></tr><tr><td colspan="4" class="text-end"><strong>Total</strong></td><td><strong class="text-pink"><?php echo formatRupiah($view_order['total']); ?></strong></td></tr></tfoot>
                </table>
            </div>
            
            <div class="status-form">
                <h6 class="fw-bold mb-3"><i class="fas fa-sync-alt"></i> Update Status Pesanan</h6>
                <form method="POST">
                    <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label"><i class="fas fa-credit-card"></i> Status Pembayaran</label><select name="payment_status" class="form-select"><option value="">-- Pilih --</option><option value="pending" <?php echo $view_order['payment_status']=='pending'?'selected':''; ?>>Menunggu</option><option value="paid" <?php echo $view_order['payment_status']=='paid'?'selected':''; ?>>Lunas</option><option value="failed" <?php echo $view_order['payment_status']=='failed'?'selected':''; ?>>Gagal</option></select></div>
                        <div class="col-md-4"><label class="form-label"><i class="fas fa-box"></i> Status Pesanan</label><select name="order_status" class="form-select" required><option value="">-- Pilih --</option><option value="pending" <?php echo $view_order['order_status']=='pending'?'selected':''; ?>>Menunggu</option><option value="processing" <?php echo $view_order['order_status']=='processing'?'selected':''; ?>>Diproses</option><option value="packed" <?php echo $view_order['order_status']=='packed'?'selected':''; ?>>Dikemas</option><option value="shipped" <?php echo $view_order['order_status']=='shipped'?'selected':''; ?>>Dikirim</option><option value="delivered" <?php echo $view_order['order_status']=='delivered'?'selected':''; ?>>Sampai</option><option value="completed" <?php echo $view_order['order_status']=='completed'?'selected':''; ?>>Selesai</option><option value="cancelled" <?php echo $view_order['order_status']=='cancelled'?'selected':''; ?>>Batal</option><option value="refunded" <?php echo $view_order['order_status']=='refunded'?'selected':''; ?>>Refund</option></select></div>
                        <div class="col-md-4"><label class="form-label"><i class="fas fa-truck"></i> No. Resi</label><input type="text" name="shipping_resi" class="form-control" placeholder="JNE123456789" value="<?php echo htmlspecialchars($view_order['shipping_resi']??''); ?>"></div>
                        <div class="col-md-6"><label class="form-label"><i class="fas fa-shipping-fast"></i> Kurir</label><input type="text" name="shipping_carrier" class="form-control" placeholder="JNE, J&T, SiCepat" value="<?php echo htmlspecialchars($view_order['shipping_carrier']??''); ?>"></div>
                        <div class="col-md-6 d-flex align-items-end"><button type="submit" name="update_order" class="btn-update"><i class="fas fa-save"></i> Update Status</button></div>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Daftar Pesanan -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-shopping-cart"></i> Daftar Pesanan</h1>
            <div class="stats-summary">
                <span class="stat-pill pending"><i class="fas fa-clock"></i> <?php echo mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM orders WHERE order_status='pending'"))['c']??0; ?> Menunggu</span>
                <span class="stat-pill processing"><i class="fas fa-sync-alt"></i> <?php echo mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM orders WHERE order_status='processing'"))['c']??0; ?> Diproses</span>
                <span class="stat-pill shipped"><i class="fas fa-truck"></i> <?php echo mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM orders WHERE order_status='shipped'"))['c']??0; ?> Dikirim</span>
            </div>
        </div>
        
        <form method="GET" class="filter-section">
            <div class="filter-group"><label><i class="fas fa-search"></i> Cari</label><input type="text" class="form-control" name="search" placeholder="No. Order / Nama / Telp" value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="filter-group"><label><i class="fas fa-chart-simple"></i> Status</label><select class="form-select" name="status"><option value="">Semua Status</option><option value="pending" <?php echo $filter_status=='pending'?'selected':''; ?>>Menunggu</option><option value="processing" <?php echo $filter_status=='processing'?'selected':''; ?>>Diproses</option><option value="shipped" <?php echo $filter_status=='shipped'?'selected':''; ?>>Dikirim</option><option value="completed" <?php echo $filter_status=='completed'?'selected':''; ?>>Selesai</option><option value="cancelled" <?php echo $filter_status=='cancelled'?'selected':''; ?>>Batal</option></select></div>
            <div class="filter-group"><label><i class="fas fa-store"></i> Toko</label><select class="form-select" name="store"><option value="">Semua Toko</option><option value="slime" <?php echo $filter_store=='slime'?'selected':''; ?>>Slime</option><option value="photocard" <?php echo $filter_store=='photocard'?'selected':''; ?>>Photocard</option><option value="both" <?php echo $filter_store=='both'?'selected':''; ?>>Kedua</option></select></div>
            <div class="filter-group"><label><i class="fas fa-calendar-alt"></i> Dari</label><input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"></div>
            <div class="filter-group"><label><i class="fas fa-calendar-alt"></i> Sampai</label><input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"></div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;"><button class="btn-filter" type="submit"><i class="fas fa-filter"></i> Filter</button><a href="orders.php" class="btn-reset"><i class="fas fa-undo-alt"></i> Reset</a></div>
        </form>
        
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <small class="text-muted">Menampilkan <strong><?php echo mysqli_num_rows($orders_result); ?></strong> dari <strong><?php echo $total_orders; ?></strong> pesanan</small>
            <?php if($search || $filter_status || $filter_store || $date_from || $date_to): ?><small><a href="orders.php" class="text-pink text-decoration-none"><i class="fas fa-times"></i> Hapus Filter</a></small><?php endif; ?>
        </div>
        
        <div class="orders-table">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>No. Order</th><th>Pelanggan</th><th>Toko</th><th>Tanggal</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(mysqli_num_rows($orders_result)>0): while($o=mysqli_fetch_assoc($orders_result)): ?>
                        <tr class="order-row">
                            <td><strong class="text-pink"><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                            <td><div class="fw-bold"><?php echo htmlspecialchars($o['customer_name']??'Tamu'); ?></div><small><?php echo htmlspecialchars($o['customer_phone']??''); ?></small></td>
                            <td><span class="badge-store <?php echo htmlspecialchars($o['store_type']??'both'); ?>"><i class="fas <?php echo $o['store_type']=='slime'?'fa-bezier-curve':'fa-camera'; ?>"></i> <?php echo ucfirst($o['store_type']??'both'); ?></span></td>
                            <td><small><?php echo date('d M', strtotime($o['created_at'])); ?><br><strong><?php echo date('H:i', strtotime($o['created_at'])); ?></strong></small></td>
                            <td class="fw-bold text-primary"><?php echo formatRupiah($o['total']); ?></td>
                            <td><span class="badge-payment <?php echo htmlspecialchars($o['payment_status']); ?>"><i class="fas <?php echo $o['payment_status']=='paid'?'fa-check-circle':'fa-clock'; ?>"></i> <?php echo ucfirst($o['payment_status']); ?></span></td>
                            <td><span class="badge-order <?php echo htmlspecialchars($o['order_status']); ?>"><i class="fas <?php echo $o['order_status']=='shipped'?'fa-truck':($o['order_status']=='completed'?'fa-check-circle':'fa-clock'); ?>"></i> <?php echo ucfirst($o['order_status']); ?></span></td>
                            <td><a href="orders.php?view=<?php echo $o['id']; ?>" class="btn-view-order"><i class="fas fa-eye"></i> Detail</a></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-shopping-cart"></i><p>Belum ada pesanan</p><small>Pesanan yang masuk akan muncul di sini</small></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <footer><p>© <?php echo date('Y'); ?> <strong>VIJARIE</strong> — Panel Admin. Hak Cipta Dilindungi.</p></footer>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.querySelector('.toggle-sidebar');
            const sidebar = document.querySelector('.modern-sidebar');
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', (e) => { e.stopPropagation(); sidebar.classList.toggle('show'); });
                document.addEventListener('click', (e) => {
                    if (window.innerWidth <= 1024 && sidebar.classList.contains('show') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                });
            }
            document.querySelectorAll('.alert-custom').forEach(alert => {
                setTimeout(() => { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 300); }, 5000);
            });
            const statusSelect = document.getElementById('order_status');
            const statusForm = document.querySelector('.status-form form');
            if (statusSelect && statusForm) {
                statusForm.addEventListener('submit', function(e) {
                    const status = statusSelect.value;
                    if (status === 'cancelled' || status === 'refunded') {
                        if (!confirm(status === 'cancelled' ? 'Yakin ingin membatalkan pesanan ini?' : 'Yakin ingin melakukan refund untuk pesanan ini?')) {
                            e.preventDefault();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>