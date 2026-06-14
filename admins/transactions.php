<?php
// ============================================
// PEMBUKUAN & TRANSAKSI - VIJARIE
// Tema: Gradient Pink + Kuning-Coral
// Bahasa: Indonesia
// Keamanan: Prepared Statements
// Fitur: Add, Edit, Delete
// ============================================

require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Pembukuan - VIJARIE';
$conn = getDBConnection();

$message = '';
$message_type = '';

// ============================================
// HANDLE TAMBAH/EDIT TRANSAKSI
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_transaction'])) {
    $id = isset($_POST['transaction_id']) && $_POST['transaction_id'] ? (int)$_POST['transaction_id'] : null;
    $type = sanitize($_POST['type']);
    $category = sanitize($_POST['category']);
    $description = sanitize($_POST['description']);
    $amount = (float)$_POST['amount'];
    $store_type = sanitize($_POST['store_type'] ?? 'both');
    $date = $_POST['transaction_date'] ?? date('Y-m-d');
    $admin_id = $_SESSION['admin_id'] ?? 1;
    
    if ($id) {
        $stmt = mysqli_prepare($conn, "UPDATE transactions 
            SET type=?, category=?, description=?, amount=?, store_type=?, transaction_date=?, updated_at=NOW() 
            WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssdsisi", $type, $category, $description, $amount, $store_type, $date, $id);
        if (mysqli_stmt_execute($stmt)) {
            $message = 'Transaksi berhasil diperbarui!';
            $message_type = 'success';
        }
        mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO transactions 
            (type, category, description, amount, store_type, transaction_date, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "sssdsii", $type, $category, $description, $amount, $store_type, $date, $admin_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = 'Transaksi berhasil ditambahkan!';
            $message_type = 'success';
        }
        mysqli_stmt_close($stmt);
    }
}

// ============================================
// HANDLE HAPUS TRANSAKSI
// ============================================
if (isset($_POST['delete_transaction'])) {
    $id = (int)$_POST['transaction_id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM transactions WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $message = 'Transaksi berhasil dihapus';
        $message_type = 'info';
    }
    mysqli_stmt_close($stmt);
}

// ============================================
// FILTER & PENCARIAN
// ============================================
$search = $_GET['search'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_store = $_GET['store'] ?? '';
$filter_date_from = $_GET['date_from'] ?? date('Y-m-01');
$filter_date_to = $_GET['date_to'] ?? date('Y-m-d');

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $where .= " AND (t.description LIKE ? OR t.category LIKE ?)";
    $sp = "%$search%";
    $params[] = $sp; $params[] = $sp;
    $types .= "ss";
}
if ($filter_type) { $where .= " AND t.type = ?"; $params[] = $filter_type; $types .= "s"; }
if ($filter_store) { $where .= " AND t.store_type = ?"; $params[] = $filter_store; $types .= "s"; }
if ($filter_date_from) { $where .= " AND DATE(t.transaction_date) >= ?"; $params[] = $filter_date_from; $types .= "s"; }
if ($filter_date_to) { $where .= " AND DATE(t.transaction_date) <= ?"; $params[] = $filter_date_to; $types .= "s"; }

// Ringkasan keuangan
$summary_stmt = mysqli_prepare($conn, "
    SELECT 
        COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) as total_expense,
        COUNT(*) as total_transactions
    FROM transactions 
    WHERE 1=1
    " . ($filter_date_from ? "AND DATE(transaction_date) >= ?" : "") . "
    " . ($filter_date_to ? "AND DATE(transaction_date) <= ?" : "") . "
    " . ($filter_type ? "AND type = ?" : "") . "
    " . ($filter_store ? "AND store_type = ?" : "")
);
$summary_params = [];
$summary_types = "";
if ($filter_date_from) { $summary_params[] = $filter_date_from; $summary_types .= "s"; }
if ($filter_date_to) { $summary_params[] = $filter_date_to; $summary_types .= "s"; }
if ($filter_type) { $summary_params[] = $filter_type; $summary_types .= "s"; }
if ($filter_store) { $summary_params[] = $filter_store; $summary_types .= "s"; }
if (!empty($summary_params)) mysqli_stmt_bind_param($summary_stmt, $summary_types, ...$summary_params);
mysqli_stmt_execute($summary_stmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($summary_stmt));
mysqli_stmt_close($summary_stmt);
$net_profit = ($summary['total_income'] ?? 0) - ($summary['total_expense'] ?? 0);

// Data transaksi
$stmt = mysqli_prepare($conn, "
    SELECT t.*, a.name as created_by_name 
    FROM transactions t 
    LEFT JOIN admins a ON t.created_by = a.id 
    $where 
    ORDER BY t.transaction_date DESC, t.created_at DESC 
    LIMIT 100
");
if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$transactions = mysqli_stmt_get_result($stmt);

$categories = ['Penjualan', 'Bahan Baku', 'Operasional', 'Marketing', 'Packaging', 'Shipping', 'Lain-lain'];

$edit_transaction = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_edit = mysqli_prepare($conn, "SELECT * FROM transactions WHERE id = ?");
    mysqli_stmt_bind_param($stmt_edit, "i", $edit_id);
    mysqli_stmt_execute($stmt_edit);
    $edit_transaction = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_edit));
    mysqli_stmt_close($stmt_edit);
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
        }
        body { font-family: 'Quicksand', sans-serif; background: linear-gradient(135deg, #FFF5F7 0%, #F0FFFF 100%); }
        .transaction-row { animation: slideIn 0.4s ease forwards; opacity: 0; transform: translateY(12px); }
        .transaction-row:nth-child(1) { animation-delay: 0.05s; }
        .transaction-row:nth-child(2) { animation-delay: 0.1s; }
        .transaction-row:nth-child(3) { animation-delay: 0.15s; }
        .transaction-row:nth-child(4) { animation-delay: 0.2s; }
        .transaction-row:nth-child(5) { animation-delay: 0.25s; }
        @keyframes slideIn { to { opacity: 1; transform: translateY(0); } }
        .transaction-row:hover { background: linear-gradient(135deg, rgba(255,182,193,0.08), rgba(64,224,208,0.08)); transform: translateX(3px); }
        .summary-wrapper { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .summary-card { background: white; border-radius: 20px; padding: 24px; box-shadow: 0 8px 30px rgba(255,182,193,0.15); border: 1px solid rgba(255,182,193,0.2); text-align: center; transition: 0.3s; position: relative; overflow: hidden; }
        .summary-card::before { content: ''; position: absolute; top: 0; left: 0; width: 5px; height: 100%; }
        .summary-card.income::before { background: linear-gradient(180deg, var(--success), #059669); }
        .summary-card.expense::before { background: linear-gradient(180deg, var(--danger), #DC2626); }
        .summary-card.profit::before { background: linear-gradient(180deg, var(--kuning), var(--kuning-coral)); }
        .summary-card:hover { transform: translateY(-5px); box-shadow: 0 15px 45px rgba(255,182,193,0.25); }
        .summary-icon { width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 1.5rem; color: white; }
        .summary-card.income .summary-icon { background: linear-gradient(135deg, var(--success), #059669); }
        .summary-card.expense .summary-icon { background: linear-gradient(135deg, var(--danger), #DC2626); }
        .summary-card.profit .summary-icon { background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); }
        .summary-value { font-size: 2rem; font-weight: 800; color: var(--biru-dongker); font-family: 'Poppins', sans-serif; margin-bottom: 5px; }
        .summary-card.income .summary-value { color: var(--success); }
        .summary-card.expense .summary-value { color: var(--danger); }
        .summary-label { color: var(--abu-abu); font-size: 0.9rem; font-weight: 600; }
        .summary-trend { display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; margin-top: 10px; padding: 4px 10px; border-radius: 8px; font-weight: 600; }
        .summary-trend.up { background: rgba(16,185,129,0.12); color: var(--success); }
        .summary-trend.down { background: rgba(239,68,68,0.12); color: var(--danger); }
        .filter-section { background: white; border-radius: 18px; padding: 18px 20px; margin-bottom: 25px; box-shadow: 0 6px 25px rgba(255,182,193,0.12); border: 1px solid rgba(255,182,193,0.2); display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; min-width: 150px; flex: 1; }
        .filter-group label { font-size: 0.78rem; font-weight: 600; color: var(--biru-dongker); }
        .filter-section .form-control, .filter-section .form-select { border: 2px solid var(--pink-coral); border-radius: 12px; padding: 10px 14px; font-size: 0.9rem; background: white; }
        .btn-filter { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); border: none; border-radius: 12px; padding: 10px 20px; color: white; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; height: 42px; }
        .btn-reset { background: rgba(128,128,128,0.12); border: none; border-radius: 12px; padding: 10px 20px; color: var(--abu-abu); font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; height: 42px; }
        .btn-add-transaction { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); border: none; border-radius: 60px; padding: 12px 28px; font-weight: 700; color: white; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; box-shadow: 0 6px 20px rgba(255,105,180,0.35); }
        .btn-add-transaction:hover { transform: translateY(-3px); box-shadow: 0 12px 35px rgba(255,105,180,0.5); }
        .transactions-table { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 8px 30px rgba(255,182,193,0.15); border: 1px solid rgba(255,182,193,0.2); overflow-x: auto; }
        .transactions-table thead th { background: linear-gradient(135deg, rgba(255,182,193,0.12), rgba(64,224,208,0.12)); color: var(--biru-dongker); font-weight: 700; border: none; padding: 14px 12px; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid var(--pink-coral); white-space: nowrap; }
        .badge-type { padding: 6px 14px; border-radius: 30px; font-weight: 600; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 6px; }
        .badge-type.income { background: #D1FAE5; color: #065F46; }
        .badge-type.expense { background: #FEE2E2; color: #991B1B; }
        .badge-store { padding: 5px 12px; border-radius: 30px; font-weight: 600; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px; }
        .badge-store.slime { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); color: white; }
        .badge-store.photocard { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); color: white; }
        .badge-store.both { background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); color: var(--biru-dongker); }
        .btn-action-group { display: flex; gap: 5px; }
        .btn-edit { width: 32px; height: 32px; border-radius: 10px; background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); color: white; border: none; cursor: pointer; transition: 0.2s; }
        .btn-delete { width: 32px; height: 32px; border-radius: 10px; background: rgba(239,68,68,0.15); color: var(--danger); border: none; cursor: pointer; transition: 0.2s; }
        .btn-edit:hover, .btn-delete:hover { transform: scale(1.1); }
        .modal-content { border-radius: 24px; border: none; box-shadow: 0 30px 80px rgba(255,182,193,0.35); }
        .modal-header { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); color: white; border-radius: 24px 24px 0 0; border: none; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .alert-custom { border-radius: 14px; padding: 12px 16px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease; }
        .alert-custom.success { background: #D1FAE5; color: #059669; border-left: 4px solid var(--success); }
        .alert-custom.info { background: #DBEAFE; color: #2563EB; border-left: 4px solid #3B82F6; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--abu-abu); }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.4; }
        footer { text-align: center; padding: 20px; color: var(--abu-abu); font-size: 0.8rem; border-top: 1px solid rgba(255,182,193,0.2); margin-top: 30px; }
        @media (max-width: 992px) {
            .filter-section { flex-direction: column; align-items: stretch; }
            .filter-group { width: 100%; }
            .btn-filter, .btn-reset { justify-content: center; }
        }
        @media (max-width: 768px) {
            .transactions-table { overflow-x: auto; }
            .transactions-table table { min-width: 750px; }
            .summary-wrapper { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <main class="modern-main">
        <?php include 'includes/header.php'; ?>
        
        <?php if ($message): ?>
        <div class="alert-custom <?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.style.display='none'" style="background:none;border:none;font-size:1.2rem;opacity:0.7;"></button>
        </div>
        <?php endif; ?>
        
        <!-- Ringkasan Keuangan -->
        <div class="summary-wrapper">
            <div class="summary-card income">
                <div class="summary-icon"><i class="fas fa-arrow-down"></i></div>
                <div class="summary-value">+ <?php echo formatRupiah($summary['total_income'] ?? 0); ?></div>
                <div class="summary-label">Total Pemasukan</div>
                <div class="summary-trend up"><i class="fas fa-arrow-up"></i> Bulan ini</div>
            </div>
            <div class="summary-card expense">
                <div class="summary-icon"><i class="fas fa-arrow-up"></i></div>
                <div class="summary-value">- <?php echo formatRupiah($summary['total_expense'] ?? 0); ?></div>
                <div class="summary-label">Total Pengeluaran</div>
                <div class="summary-trend down"><i class="fas fa-arrow-down"></i> Bulan ini</div>
            </div>
            <div class="summary-card profit">
                <div class="summary-icon"><i class="fas fa-chart-line"></i></div>
                <div class="summary-value"><?php echo ($net_profit >= 0 ? '+' : '-') . ' ' . formatRupiah(abs($net_profit)); ?></div>
                <div class="summary-label">Laba Bersih</div>
                <div class="summary-trend <?php echo $net_profit >= 0 ? 'up' : 'down'; ?>"><i class="fas fa-arrow-<?php echo $net_profit >= 0 ? 'up' : 'down'; ?>"></i> <?php echo $net_profit >= 0 ? 'Profit' : 'Defisit'; ?></div>
            </div>
        </div>
        
        <!-- Filter -->
        <form method="GET" class="filter-section">
            <div class="filter-group"><label><i class="fas fa-search"></i> Cari</label><input type="text" class="form-control" name="search" placeholder="Deskripsi / Kategori" value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="filter-group"><label><i class="fas fa-chart-simple"></i> Tipe</label><select class="form-select" name="type"><option value="">Semua</option><option value="income" <?php echo $filter_type=='income'?'selected':''; ?>>Pemasukan</option><option value="expense" <?php echo $filter_type=='expense'?'selected':''; ?>>Pengeluaran</option></select></div>
            <div class="filter-group"><label><i class="fas fa-store"></i> Toko</label><select class="form-select" name="store"><option value="">Semua Toko</option><option value="slime" <?php echo $filter_store=='slime'?'selected':''; ?>>Slime</option><option value="photocard" <?php echo $filter_store=='photocard'?'selected':''; ?>>Photocard</option><option value="both" <?php echo $filter_store=='both'?'selected':''; ?>>Kedua</option></select></div>
            <div class="filter-group"><label><i class="fas fa-calendar-alt"></i> Dari</label><input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>"></div>
            <div class="filter-group"><label><i class="fas fa-calendar-alt"></i> Sampai</label><input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>"></div>
            <div style="display: flex; gap: 8px;"><button class="btn-filter" type="submit"><i class="fas fa-filter"></i> Filter</button><a href="transactions.php" class="btn-reset"><i class="fas fa-undo-alt"></i> Reset</a></div>
        </form>
        
        <div class="d-flex justify-content-end mb-4">
            <button class="btn-add-transaction" onclick="openTransactionModal()"><i class="fas fa-plus-circle"></i> Tambah Transaksi</button>
        </div>
        
        <div class="transactions-table">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Tipe</th><th>Kategori</th><th>Toko</th><th>Deskripsi</th><th>Jumlah</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if (mysqli_num_rows($transactions) > 0): while($t = mysqli_fetch_assoc($transactions)): ?>
                        <tr class="transaction-row">
                            <td><small><?php echo date('d M Y', strtotime($t['transaction_date'])); ?></small><br><small class="text-muted"><?php echo date('H:i', strtotime($t['created_at'])); ?></small></td>
                            <td><span class="badge-type <?php echo $t['type']; ?>"><i class="fas <?php echo $t['type']=='income'?'fa-arrow-down':'fa-arrow-up'; ?>"></i> <?php echo $t['type']=='income'?'Pemasukan':'Pengeluaran'; ?></span></td>
                            <td><?php echo htmlspecialchars($t['category']); ?></td>
                            <td><span class="badge-store <?php echo htmlspecialchars($t['store_type']??'both'); ?>"><i class="fas <?php echo $t['store_type']=='slime'?'fa-bezier-curve':($t['store_type']=='photocard'?'fa-camera':'fa-retweet'); ?>"></i></span></td>
                            <td><?php echo htmlspecialchars($t['description']); ?></td>
                            <td><strong class="<?php echo $t['type']=='income'?'text-success':'text-danger'; ?>"><?php echo ($t['type']=='income'?'+ ':'- ') . formatRupiah($t['amount']); ?></strong></td>
                            <td><div class="btn-action-group"><button class="btn-edit" onclick='editTransaction(<?php echo json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button><form method="POST" style="display:inline;" onsubmit="return confirm('Hapus transaksi ini?');"><input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>"><button type="submit" name="delete_transaction" class="btn-delete"><i class="fas fa-trash"></i></button></form></div></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-wallet"></i><p>Belum ada transaksi</p><small>Klik "Tambah Transaksi" untuk memulai</small></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <footer><p>© <?php echo date('Y'); ?> <strong>VIJARIE</strong> — Panel Admin. Hak Cipta Dilindungi.</p></footer>
    </main>
    
    <!-- Modal Tambah/Edit Transaksi -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> <span id="modalTitle">Tambah Transaksi Baru</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="transactionForm">
                    <div class="modal-body">
                        <input type="hidden" name="transaction_id" id="transaction_id">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label"><i class="fas fa-chart-simple"></i> Tipe Transaksi <span class="text-danger">*</span></label><select name="type" id="type" class="form-select" required><option value="income">Pemasukan</option><option value="expense">Pengeluaran</option></select></div>
                            <div class="col-md-6"><label class="form-label"><i class="fas fa-store"></i> Toko <span class="text-danger">*</span></label><select name="store_type" id="store_type" class="form-select" required><option value="both">Kedua Toko</option><option value="slime">Vij Slimee</option><option value="photocard">Aprpiejise</option></select></div>
                            <div class="col-12"><label class="form-label"><i class="fas fa-tags"></i> Kategori <span class="text-danger">*</span></label><select name="category" id="category" class="form-select" required><option value="">Pilih Kategori</option><?php foreach($categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?></select></div>
                            <div class="col-12"><label class="form-label"><i class="fas fa-money-bill-wave"></i> Jumlah (Rp) <span class="text-danger">*</span></label><input type="number" name="amount" id="amount" class="form-control" placeholder="0" min="0" step="100" required><small class="text-muted">Masukkan angka tanpa titik/koma</small></div>
                            <div class="col-12"><label class="form-label"><i class="fas fa-align-left"></i> Deskripsi</label><textarea name="description" id="description" class="form-control" rows="2" placeholder="Contoh: Pembelian bahan baku..."></textarea></div>
                            <div class="col-12"><label class="form-label"><i class="fas fa-calendar-alt"></i> Tanggal Transaksi</label><input type="date" name="transaction_date" id="transaction_date" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Batal</button><button type="submit" name="save_transaction" class="btn-save"><i class="fas fa-save"></i> Simpan Transaksi</button></div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const transactionModal = new bootstrap.Modal(document.getElementById('transactionModal'));
        function openTransactionModal() {
            document.getElementById('modalTitle').innerText = 'Tambah Transaksi Baru';
            document.getElementById('transactionForm').reset();
            document.getElementById('transaction_id').value = '';
            document.getElementById('transaction_date').value = '<?php echo date('Y-m-d'); ?>';
            transactionModal.show();
        }
        function editTransaction(t) {
            document.getElementById('modalTitle').innerText = 'Edit Transaksi';
            document.getElementById('transaction_id').value = t.id;
            document.getElementById('type').value = t.type;
            document.getElementById('store_type').value = t.store_type;
            document.getElementById('category').value = t.category;
            document.getElementById('amount').value = t.amount;
            document.getElementById('description').value = t.description;
            document.getElementById('transaction_date').value = t.transaction_date;
            transactionModal.show();
        }
        document.getElementById('amount')?.addEventListener('blur', function() { if(this.value) this.value = parseInt(this.value.replace(/\D/g,'')) || 0; });
        document.getElementById('transactionForm')?.addEventListener('submit', function() { let a = document.getElementById('amount'); if(a) a.value = a.value.replace(/\D/g,''); });
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.querySelector('.toggle-sidebar'), sidebar = document.querySelector('.modern-sidebar');
            if(toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', (e) => { e.stopPropagation(); sidebar.classList.toggle('show'); });
                document.addEventListener('click', (e) => { if(window.innerWidth<=1024 && sidebar.classList.contains('show') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) sidebar.classList.remove('show'); });
            }
            document.querySelectorAll('.alert-custom').forEach(alert => { setTimeout(() => { alert.style.opacity='0'; setTimeout(()=>alert.remove(),300); },5000); });
            <?php if($edit_transaction): ?>editTransaction(<?php echo json_encode($edit_transaction, JSON_HEX_APOS | JSON_HEX_QUOT); ?>);<?php endif; ?>
        });
    </script>
</body>
</html>