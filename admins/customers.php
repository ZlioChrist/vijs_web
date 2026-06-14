<?php
// ============================================
// MANAJEMEN PELANGGAN - VIJARIE
// Tema: Gradient Pink + Kuning-Coral
// Bahasa: Indonesia
// Fitur: Detail Modal, Export CSV, Responsif
// ============================================

require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Pelanggan - VIJARIE';
$conn = getDBConnection();

$message = '';
$message_type = '';

// Handle Delete Customer (Soft Delete)
if (isset($_POST['delete_customer'])) {
    $id = (int)$_POST['customer_id'];
    $stmt = mysqli_prepare($conn, "UPDATE customers SET is_active = 0 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $message = 'Data pelanggan berhasil dinonaktifkan';
        $message_type = 'info';
    }
    mysqli_stmt_close($stmt);
}

// ============================================
// EXPORT CSV
// ============================================
if (isset($_GET['export'])) {
    $search = $_GET['search'] ?? '';
    $filter_city = $_GET['city'] ?? '';
    
    $where = "WHERE c.is_active = 1";
    $params = [];
    $types = "";
    
    if ($search) {
        $where .= " AND (c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
        $types .= "sss";
    }
    if ($filter_city) {
        $where .= " AND c.city = ?";
        $params[] = $filter_city;
        $types .= "s";
    }
    
    $query = "
        SELECT c.id, c.name, c.phone, c.email, c.address, c.city, c.province, c.postal_code, c.created_at,
            (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) as total_orders,
            (SELECT COALESCE(SUM(total), 0) FROM orders WHERE customer_id = c.id AND payment_status = 'paid') as total_spent,
            (SELECT MAX(created_at) FROM orders WHERE customer_id = c.id) as last_order
        FROM customers c $where ORDER BY c.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data_pelanggan_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Nama Lengkap', 'Nomor Telepon', 'Email', 'Alamat', 'Kota', 'Provinsi', 'Kode Pos', 'Total Pesanan', 'Total Belanja', 'Pesanan Terakhir', 'Tanggal Bergabung']);
    while($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'], $row['name'], $row['phone'], $row['email'] ?? '-', $row['address'] ?? '-',
            $row['city'] ?? '-', $row['province'] ?? '-', $row['postal_code'] ?? '-', $row['total_orders'],
            'Rp ' . number_format($row['total_spent'], 0, ',', '.'),
            $row['last_order'] ? date('d/m/Y', strtotime($row['last_order'])) : '-',
            date('d/m/Y', strtotime($row['created_at']))
        ]);
    }
    fclose($output);
    mysqli_stmt_close($stmt);
    exit;
}

// ============================================
// GET CUSTOMER DETAIL (AJAX)
// ============================================
if (isset($_GET['get_detail'])) {
    $id = (int)$_GET['get_detail'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM customers WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    
    if ($customer) {
        $stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total_orders, COALESCE(SUM(total),0) as total_spent, MAX(created_at) as last_order FROM orders WHERE customer_id = $id"));
        $orders = mysqli_query($conn, "SELECT * FROM orders WHERE customer_id = $id ORDER BY created_at DESC LIMIT 10");
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'customer' => $customer, 'stats' => $stats, 'orders' => mysqli_fetch_all($orders, MYSQLI_ASSOC)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pelanggan tidak ditemukan']);
    }
    exit;
}

// ============================================
// FILTER & PENCARIAN
// ============================================
$search = $_GET['search'] ?? '';
$filter_city = $_GET['city'] ?? '';

$where = "WHERE c.is_active = 1";
$params = [];
$types = "";

if ($search) {
    $where .= " AND (c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
    $sp = "%$search%";
    $params = [$sp, $sp, $sp];
    $types = "sss";
}
if ($filter_city) {
    $where .= " AND c.city = ?";
    $params[] = $filter_city;
    $types .= "s";
}

// Ambil daftar kota unik untuk filter
$cities_result = mysqli_query($conn, "SELECT DISTINCT city FROM customers WHERE city IS NOT NULL AND city != '' ORDER BY city ASC");
$cities = [];
while($row = mysqli_fetch_assoc($cities_result)) $cities[] = $row['city'];

// Data pelanggan
$stmt = mysqli_prepare($conn, "
    SELECT c.*,
        (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) as total_orders,
        (SELECT COALESCE(SUM(total), 0) FROM orders WHERE customer_id = c.id AND payment_status = 'paid') as total_spent
    FROM customers c $where ORDER BY c.created_at DESC");
if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$customers = mysqli_stmt_get_result($stmt);

// Statistik
$total_customers = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM customers WHERE is_active = 1"))['c'] ?? 0);
$new_this_month = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM customers WHERE is_active = 1 AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"))['c'] ?? 0);
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
        }
        body { font-family: 'Quicksand', sans-serif; background: linear-gradient(135deg, #FFF5F7 0%, #F0FFFF 100%); }
        .stats-wrapper { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 8px 30px rgba(255,182,193,0.15); border: 1px solid rgba(255,182,193,0.2); display: flex; align-items: center; gap: 16px; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; flex-shrink: 0; }
        .stat-icon.total { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); }
        .stat-icon.new { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); }
        .stat-value { font-size: 1.8rem; font-weight: 800; color: var(--biru-dongker); line-height: 1; }
        .stat-label { color: var(--abu-abu); font-size: 0.9rem; font-weight: 600; }
        .stat-trend { font-size: 0.8rem; color: var(--success); font-weight: 600; margin-top: 6px; }
        
        .filter-section { background: white; border-radius: 18px; padding: 18px 20px; margin-bottom: 25px; box-shadow: 0 6px 25px rgba(255,182,193,0.12); border: 1px solid rgba(255,182,193,0.2); display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; min-width: 160px; flex: 1; }
        .filter-group label { font-size: 0.78rem; font-weight: 600; color: var(--biru-dongker); }
        .filter-section .form-control, .filter-section .form-select { border: 2px solid var(--pink-coral); border-radius: 12px; padding: 10px 14px; font-size: 0.9rem; background: white; }
        .btn-filter { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); border: none; border-radius: 12px; padding: 10px 20px; color: white; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; height: 42px; }
        .btn-reset { background: rgba(128,128,128,0.12); border: none; border-radius: 12px; padding: 10px 20px; color: var(--abu-abu); font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; height: 42px; }
        .btn-export { background: linear-gradient(135deg, var(--success), #059669); border: none; border-radius: 12px; padding: 10px 20px; color: white; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; height: 42px; }
        .customers-table { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 8px 30px rgba(255,182,193,0.15); border: 1px solid rgba(255,182,193,0.2); overflow-x: auto; }
        .customers-table thead th { background: linear-gradient(135deg, rgba(255,182,193,0.12), rgba(64,224,208,0.12)); color: var(--biru-dongker); font-weight: 700; border: none; padding: 14px 12px; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid var(--pink-coral); white-space: nowrap; }
        .customer-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--pink-coral), var(--kuning)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; margin-right: 10px; }
        .customer-name { display: flex; align-items: center; font-weight: 600; color: var(--biru-dongker); }
        .badge-orders { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); color: white; padding: 5px 12px; border-radius: 10px; font-weight: 600; font-size: 0.8rem; display: inline-block; }
        .badge-city { background: rgba(64,224,208,0.15); color: var(--biru-dongker); padding: 4px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 500; display: inline-block; }
        .btn-view { width: 32px; height: 32px; border-radius: 10px; background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); color: white; border: none; cursor: pointer; transition: 0.2s; }
        .btn-delete { width: 32px; height: 32px; border-radius: 10px; background: rgba(239,68,68,0.15); color: var(--danger); border: none; cursor: pointer; transition: 0.2s; }
        .btn-view:hover, .btn-delete:hover { transform: scale(1.1); }
        .modal-content { border-radius: 24px; }
        .modal-header { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); color: white; border-radius: 24px 24px 0 0; border: none; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .customer-detail-header { display: flex; align-items: center; gap: 16px; padding: 20px; background: linear-gradient(135deg, rgba(255,182,193,0.1), rgba(64,224,208,0.1)); border-radius: 16px; margin-bottom: 20px; flex-wrap: wrap; }
        .customer-detail-avatar { width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, var(--pink-coral), var(--kuning)); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.8rem; font-weight: 700; }
        .detail-section-title { font-weight: 700; color: var(--biru-dongker); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px dashed var(--pink-coral); display: flex; align-items: center; gap: 8px; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed rgba(0,0,0,0.05); flex-wrap: wrap; }
        .order-item { padding: 12px; background: rgba(255,182,193,0.05); border-radius: 10px; margin-bottom: 8px; border-left: 3px solid var(--pink-coral); }
        .order-status { padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
        .order-status.pending { background: #FFEDD5; color: #C2410C; }
        .order-status.completed { background: #D1FAE5; color: #065F46; }
        .alert-custom { border-radius: 14px; padding: 12px 16px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; background: #DBEAFE; color: #2563EB; border-left: 4px solid #3B82F6; }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--abu-abu); }
        .admin-footer { text-align: center; padding: 20px; color: var(--abu-abu); font-size: 0.8rem; border-top: 1px solid rgba(255,182,193,0.2); margin-top: 30px; }
        @media (max-width: 768px) {
            .stats-wrapper { grid-template-columns: 1fr; }
            .filter-section { flex-direction: column; align-items: stretch; }
            .filter-group { width: 100%; }
            .btn-filter, .btn-reset, .btn-export { justify-content: center; }
            .customer-name { flex-wrap: wrap; gap: 5px; }
            .customer-detail-header { flex-direction: column; text-align: center; }
            .detail-row { flex-direction: column; gap: 5px; }
            .detail-value { text-align: left; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <main class="modern-main">
        <?php include 'includes/header.php'; ?>
        
        <?php if ($message): ?>
        <div class="alert-custom"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?><button type="button" class="btn-close ms-auto" onclick="this.parentElement.style.display='none'" style="background:none;border:none;"></button></div>
        <?php endif; ?>
        
        <div class="stats-wrapper">
            <div class="stat-card">
                <div class="stat-icon total"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($total_customers); ?></div>
                    <div class="stat-label">Total Pelanggan</div>
                    <div class="stat-trend"><i class="fas fa-arrow-up"></i> <?php echo $new_this_month; ?> baru bulan ini</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon new"><i class="fas fa-user-plus"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($new_this_month); ?></div>
                    <div class="stat-label">Pelanggan Baru</div>
                    <div class="stat-trend"><i class="fas fa-calendar-alt"></i> Bulan ini</div>
                </div>
            </div>
        </div>
        
        <form method="GET" class="filter-section">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Cari Pelanggan</label>
                <input type="text" class="form-control" name="search" placeholder="Nama / Telepon / Email" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-city"></i> Kota</label>
                <select class="form-select" name="city">
                    <option value="">Semua Kota</option>
                    <?php foreach($cities as $city): ?>
                    <option value="<?php echo htmlspecialchars($city); ?>" <?php echo $filter_city===$city?'selected':''; ?>><?php echo htmlspecialchars($city); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button class="btn-filter" type="submit"><i class="fas fa-filter"></i> Filter</button>
                <a href="customers.php" class="btn-reset"><i class="fas fa-undo-alt"></i> Reset</a>
                <a href="?export=1&search=<?php echo urlencode($search); ?>&city=<?php echo urlencode($filter_city); ?>" class="btn-export"><i class="fas fa-file-csv"></i> Export CSV</a>
            </div>
        </form>
        
        <div class="customers-table">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Pelanggan</th><th>Kontak</th><th>Kota</th><th>Pesanan</th><th>Total Belanja</th><th>Bergabung</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($customers) > 0): ?>
                            <?php while($c = mysqli_fetch_assoc($customers)): ?>
                            <tr class="customer-row">
                                <td><div class="customer-name"><div class="customer-avatar"><?php echo strtoupper(substr($c['name'],0,1)); ?></div> <?php echo htmlspecialchars($c['name']); ?></div></td>
                                <td><div><i class="fas fa-phone-alt text-muted me-1"></i> <?php echo htmlspecialchars($c['phone']); ?></div><small class="text-muted"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($c['email']??'-'); ?></small></td>
                                <td><?php if($c['city']): ?><span class="badge-city"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($c['city']); ?></span><?php else: ?>-<?php endif; ?></td>
                                <td><span class="badge-orders"><i class="fas fa-shopping-cart"></i> <?php echo number_format($c['total_orders']); ?></span></td>
                                <td class="fw-bold text-primary"><?php echo formatRupiah($c['total_spent']); ?></td>
                                <td><small><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($c['created_at'])); ?></small></td>
                                <td><div class="d-flex gap-2"><button class="btn-view" onclick="viewCustomerDetail(<?php echo $c['id']; ?>)" title="Lihat Detail"><i class="fas fa-eye"></i></button><form method="POST" style="display:inline;" onsubmit="return confirm('Nonaktifkan pelanggan ini?');"><input type="hidden" name="customer_id" value="<?php echo $c['id']; ?>"><button type="submit" name="delete_customer" class="btn-delete" title="Nonaktifkan"><i class="fas fa-user-slash"></i></button></form></div></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-users fa-3x mb-3 opacity-25"></i><p>Belum ada pelanggan</p><small>Data pelanggan akan muncul di sini</small></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <footer class="admin-footer">
            <p>© <?php echo date('Y'); ?> <strong>VIJARIE</strong> — Panel Admin. Hak Cipta Dilindungi.</p>
        </footer>
    </main>
    
    <!-- Modal Detail Pelanggan -->
    <div class="modal fade" id="customerDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-circle me-2"></i>Detail Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="customerDetailContent">
                    <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Memuat data...</p></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewCustomerDetail(id) {
            const modal = new bootstrap.Modal(document.getElementById('customerDetailModal'));
            const content = document.getElementById('customerDetailContent');
            content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Memuat data...</p></div>';
            modal.show();
            fetch('?get_detail=' + id).then(r=>r.json()).then(data=>{
                if(data.success){
                    let ordersHtml = '';
                    if(data.orders && data.orders.length){
                        data.orders.forEach(o=>{
                            let statusClass = o.order_status || 'pending';
                            let statusText = {pending:'Menunggu',processing:'Diproses',shipped:'Dikirim',completed:'Selesai',cancelled:'Batal'}[statusClass] || statusClass;
                            ordersHtml += `<div class="order-item"><div class="d-flex justify-content-between"><span class="fw-bold">#${o.order_number}</span><span class="order-status ${statusClass}"><i class="fas ${statusClass=='completed'?'fa-check-circle':'fa-clock'}"></i> ${statusText}</span></div><div class="text-muted small mt-1"><i class="far fa-calendar-alt"></i> ${new Date(o.created_at).toLocaleDateString('id-ID')} &nbsp; <i class="fas fa-shopping-cart"></i> Rp ${parseInt(o.total).toLocaleString('id-ID')}</div></div>`;
                        });
                    } else ordersHtml = '<p class="text-center text-muted py-3">Belum ada pesanan</p>';
                    content.innerHTML = `
                        <div class="customer-detail-header">
                            <div class="customer-detail-avatar">${data.customer.name.charAt(0).toUpperCase()}</div>
                            <div class="flex-grow-1"><h5>${data.customer.name}</h5><p><i class="fas fa-phone-alt me-2"></i> ${data.customer.phone}<br><i class="fas fa-envelope me-2"></i> ${data.customer.email || '-'}</p></div>
                        </div>
                        <div class="detail-section">
                            <h6 class="detail-section-title"><i class="fas fa-chart-simple"></i> Statistik</h6>
                            <div class="detail-row"><span class="detail-label">Total Pesanan</span><span class="detail-value fw-bold">${data.stats.total_orders || 0}</span></div>
                            <div class="detail-row"><span class="detail-label">Total Belanja</span><span class="detail-value fw-bold text-primary">Rp ${parseInt(data.stats.total_spent || 0).toLocaleString('id-ID')}</span></div>
                            <div class="detail-row"><span class="detail-label">Pesanan Terakhir</span><span class="detail-value">${data.stats.last_order ? new Date(data.stats.last_order).toLocaleDateString('id-ID') : '-'}</span></div>
                            <div class="detail-row"><span class="detail-label">Bergabung</span><span class="detail-value">${new Date(data.customer.created_at).toLocaleDateString('id-ID')}</span></div>
                        </div>
                        <div class="detail-section">
                            <h6 class="detail-section-title"><i class="fas fa-location-dot"></i> Alamat</h6>
                            <div class="detail-row"><span class="detail-label">Alamat Lengkap</span><span class="detail-value">${data.customer.address || '-'}</span></div>
                            <div class="detail-row"><span class="detail-label">Kota</span><span class="detail-value">${data.customer.city || '-'}</span></div>
                            <div class="detail-row"><span class="detail-label">Provinsi</span><span class="detail-value">${data.customer.province || '-'}</span></div>
                            <div class="detail-row"><span class="detail-label">Kode Pos</span><span class="detail-value">${data.customer.postal_code || '-'}</span></div>
                        </div>
                        <div class="detail-section">
                            <h6 class="detail-section-title"><i class="fas fa-receipt"></i> Riwayat Pesanan (${data.orders.length})</h6>
                            <div class="order-list">${ordersHtml}</div>
                        </div>
                    `;
                } else content.innerHTML = '<div class="alert alert-danger">Gagal memuat data pelanggan</div>';
            }).catch(()=>content.innerHTML='<div class="alert alert-danger">Terjadi kesalahan</div>');
        }
    </script>
</body>
</html>