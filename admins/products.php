<?php
// ============================================
// MANAJEMEN PRODUK - VIJARIE
// Tema: Gradient Pink + Kuning-Coral
// Bahasa: Indonesia
// Keamanan: Prepared Statements + File Upload
// ============================================

require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Produk - VIJARIE';
$conn = getDBConnection();

$message = '';
$message_type = '';

// ============================================
// KONFIGURASI UPLOAD
// ============================================
$upload_dir = '../uploads/products/';
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

/**
 * Generate unique slug untuk produk
 */
function generateUniqueSlug($name, $conn, $exclude_id = 0) {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $name), '-'));
    $original = $slug;
    $counter = 1;
    while (true) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM products WHERE slug = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, "si", $slug, $exclude_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 0) {
            mysqli_stmt_close($stmt);
            break;
        }
        mysqli_stmt_close($stmt);
        $slug = $original . '-' . $counter++;
    }
    return $slug;
}

// ============================================
// HANDLE FORM SUBMISSION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $name = sanitize($_POST['name']);
    $raw_slug = sanitize($_POST['slug'] ?? '');
    $type = sanitize($_POST['product_type']);
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $stock = (int)$_POST['stock'];
    $description = sanitize($_POST['description']);
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    
    if (empty($raw_slug)) {
        $slug = generateUniqueSlug($name, $conn);
    } else {
        $exclude_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $slug = generateUniqueSlug($raw_slug, $conn, $exclude_id);
    }
    
    $image_filename = null;
    $old_image = sanitize($_POST['old_image'] ?? '');
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_image'];
        $file_type = mime_content_type($file['tmp_name']);
        $file_size = $file['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $message = 'Tipe file tidak diizinkan. Hanya JPG, PNG, dan Webp yang diperbolehkan.';
            $message_type = 'error';
        } elseif ($file_size > $max_size) {
            $message = 'Ukuran file terlalu besar. Maksimal 5MB.';
            $message_type = 'error';
        } else {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $image_filename = uniqid('product_') . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . $image_filename;
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                if (!empty($old_image) && file_exists($upload_dir . $old_image)) {
                    unlink($upload_dir . $old_image);
                }
            } else {
                $message = 'Gagal upload gambar.';
                $message_type = 'error';
            }
        }
    }
    
    if (empty($image_filename) && empty($message)) {
        $image_filename = $old_image;
    }
    
    if (empty($message)) {
        if (isset($_POST['product_id']) && $_POST['product_id']) {
            $id = (int)$_POST['product_id'];
            $stmt = mysqli_prepare($conn, "UPDATE products SET 
                name=?, slug=?, category_id=?, price=?, old_price=?, 
                stock=?, description=?, image=?, is_bestseller=?, is_new=?, updated_at=NOW() 
                WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssiiddssiii", 
                $name, $slug, $category_id, $price, $old_price, 
                $stock, $description, $image_filename, $is_bestseller, $is_new, $id);
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Produk berhasil diperbarui!';
                $message_type = 'success';
            } else {
                $message = 'Gagal memperbarui produk: ' . mysqli_stmt_error($stmt);
                $message_type = 'error';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO products (
                name, slug, product_type, category_id, price, old_price, 
                stock, description, image, is_bestseller, is_new, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, "sssiddssiii", 
                $name, $slug, $type, $category_id, $price, $old_price, 
                $stock, $description, $image_filename, $is_bestseller, $is_new);
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Produk berhasil ditambahkan!';
                $message_type = 'success';
            } else {
                $message = 'Gagal menambahkan produk: ' . mysqli_stmt_error($stmt);
                $message_type = 'error';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Nonaktifkan Produk
if (isset($_POST['delete_product'])) {
    $id = (int)$_POST['product_id'];
    $stmt = mysqli_prepare($conn, "SELECT image FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($product && !empty($product['image']) && file_exists($upload_dir . $product['image'])) {
        unlink($upload_dir . $product['image']);
    }
    $stmt = mysqli_prepare($conn, "UPDATE products SET is_active=0 WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $message = 'Produk dinonaktifkan';
        $message_type = 'info';
    }
    mysqli_stmt_close($stmt);
}

// Aktifkan Produk
if (isset($_POST['activate_product'])) {
    $id = (int)$_POST['product_id'];
    $stmt = mysqli_prepare($conn, "UPDATE products SET is_active=1 WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $message = 'Produk diaktifkan';
        $message_type = 'success';
    }
    mysqli_stmt_close($stmt);
}

// ============================================
// FILTER & PENCARIAN
// ============================================
$search = $_GET['search'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_category = $_GET['category'] ?? '';

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}
if ($filter_type) {
    $where .= " AND p.product_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}
if ($filter_category) {
    $where .= " AND p.category_id = ?";
    $params[] = (int)$filter_category;
    $types .= "i";
}

$stmt = mysqli_prepare($conn, "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    $where 
    ORDER BY p.created_at DESC
");
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);

// Ambil kategori untuk dropdown & filter
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY type, sort_order");

// Data produk untuk edit
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt_edit = mysqli_prepare($conn, "SELECT * FROM products WHERE id=?");
    mysqli_stmt_bind_param($stmt_edit, "i", $edit_id);
    mysqli_stmt_execute($stmt_edit);
    $edit_result = mysqli_stmt_get_result($stmt_edit);
    $edit_product = mysqli_fetch_assoc($edit_result);
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
        }
        body { font-family: 'Quicksand', sans-serif; background: linear-gradient(135deg, #FFF5F7 0%, #F0FFFF 100%); }
        .product-row {
            animation: slideIn 0.4s ease forwards;
            opacity: 0;
            transform: translateY(15px);
        }
        .product-row:nth-child(1) { animation-delay: 0.05s; }
        .product-row:nth-child(2) { animation-delay: 0.1s; }
        .product-row:nth-child(3) { animation-delay: 0.15s; }
        .product-row:nth-child(4) { animation-delay: 0.2s; }
        .product-row:nth-child(5) { animation-delay: 0.25s; }
        @keyframes slideIn {
            to { opacity: 1; transform: translateY(0); }
        }
        .product-row:hover {
            background: linear-gradient(135deg, rgba(255,182,193,0.08), rgba(64,224,208,0.08));
            transform: translateX(4px);
            box-shadow: 0 4px 20px rgba(255,182,193,0.15);
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-title {
            color: var(--biru-dongker);
            font-weight: 800;
            font-size: 1.6rem;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }
        .page-title i { color: var(--pink-tua); font-size: 1.4rem; }
        .btn-add-product {
            background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
            border: none;
            border-radius: 60px;
            padding: 12px 28px;
            font-weight: 700;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            box-shadow: 0 6px 20px rgba(255,105,180,0.35);
        }
        .btn-add-product:hover { transform: translateY(-3px); box-shadow: 0 12px 35px rgba(255,105,180,0.5); color: white; }
        .filter-section {
            background: white;
            border-radius: 18px;
            padding: 16px 20px;
            margin-bottom: 25px;
            box-shadow: 0 6px 25px rgba(255,182,193,0.12);
            border: 1px solid rgba(255,182,193,0.2);
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-section .form-control, .filter-section .form-select {
            border: 2px solid var(--pink-coral);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 0.9rem;
            background: white;
        }
        .filter-section .form-control:focus, .filter-section .form-select:focus {
            border-color: var(--tosca);
            box-shadow: 0 0 0 4px rgba(64,224,208,0.15);
        }
        .btn-filter {
            background: linear-gradient(135deg, var(--tosca), var(--tosca-muda));
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            color: white;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(64,224,208,0.35); color: white; }
        .btn-reset {
            background: rgba(128,128,128,0.12);
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            color: var(--abu-abu);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-reset:hover { background: rgba(128,128,128,0.22); color: var(--biru-dongker); }
        .products-table {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 8px 30px rgba(255,182,193,0.15);
            border: 1px solid rgba(255,182,193,0.2);
            overflow-x: auto;
        }
        .products-table thead th {
            background: linear-gradient(135deg, rgba(255,182,193,0.12), rgba(64,224,208,0.12));
            color: var(--biru-dongker);
            font-weight: 700;
            border: none;
            padding: 14px 12px;
            font-size: 0.82rem;
            text-transform: uppercase;
            border-bottom: 2px solid var(--pink-coral);
            white-space: nowrap;
        }
        .product-image-wrapper {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid rgba(255,182,193,0.35);
            background: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-thumb { width: 100%; height: 100%; object-fit: cover; }
        .product-name { display: flex; flex-direction: column; gap: 4px; max-width: 200px; }
        .product-name strong { color: var(--biru-dongker); font-size: 0.93rem; font-weight: 600; }
        .badge-new, .badge-bestseller {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            width: fit-content;
        }
        .badge-new { background: linear-gradient(135deg, #FFD700, #FFA500); color: var(--biru-dongker); }
        .badge-bestseller { background: linear-gradient(135deg, var(--pink-tua), var(--pink-coral)); color: white; }
        .badge-type {
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .badge-type.slime { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); color: white; }
        .badge-type.photocard { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); color: white; }
        .price-wrapper { display: flex; flex-direction: column; gap: 2px; min-width: 90px; }
        .price-main { color: var(--biru-dongker); font-weight: 700; font-size: 0.9rem; }
        .price-old { color: var(--abu-abu); text-decoration: line-through; font-size: 0.75rem; }
        .stock-badge {
            padding: 5px 11px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
            min-width: 45px;
            text-align: center;
        }
        .stock-badge.low-stock { background: #FEE2E2; color: var(--danger); }
        .stock-badge:not(.low-stock) { background: #D1FAE5; color: var(--success); }
        .status-badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status-badge.active { background: linear-gradient(135deg, var(--success), #059669); color: white; }
        .status-badge.inactive { background: rgba(128,128,128,0.12); color: var(--abu-abu); }
        .action-buttons { display: flex; gap: 8px; }
        .btn-action {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            color: white;
            transition: 0.2s;
        }
        .btn-edit { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); }
        .btn-hide { background: linear-gradient(135deg, #FCA5A5, #EF4444); }
        .btn-show { background: linear-gradient(135deg, #86EFAC, var(--success)); }
        .btn-action:hover { transform: scale(1.1); }
        .modal-content { border-radius: 24px; border: none; box-shadow: 0 30px 80px rgba(255,182,193,0.35); }
        .modal-header { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); color: white; border-radius: 24px 24px 0 0; border: none; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .form-label { font-weight: 600; color: var(--biru-dongker); margin-bottom: 7px; font-size: 0.88rem; display: flex; align-items: center; gap: 5px; }
        .form-control, .form-select { border: 2px solid var(--pink-coral); border-radius: 12px; padding: 11px 14px; transition: 0.25s; background: white; }
        .form-control:focus, .form-select:focus { border-color: var(--tosca); box-shadow: 0 0 0 4px rgba(64,224,208,0.15); }
        .image-upload-wrapper {
            border: 2px dashed var(--pink-coral);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            background: rgba(255,182,193,0.05);
            cursor: pointer;
            transition: 0.3s;
        }
        .image-upload-wrapper:hover { border-color: var(--tosca); background: rgba(64,224,208,0.08); }
        .image-upload-wrapper i { font-size: 2.5rem; color: var(--pink-tua); margin-bottom: 10px; }
        .image-upload-wrapper input[type="file"] { display: none; }
        .image-preview { max-width: 150px; border-radius: 12px; border: 2px solid var(--pink-coral); margin-top: 15px; display: none; }
        .image-preview.show { display: block; }
        .btn-save {
            background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
            border: none;
            border-radius: 60px;
            padding: 12px 32px;
            font-weight: 700;
            color: white;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(255,105,180,0.45); }
        .btn-cancel {
            background: rgba(128,128,128,0.12);
            border: none;
            border-radius: 60px;
            padding: 12px 28px;
            font-weight: 600;
            color: var(--abu-abu);
            transition: 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-cancel:hover { background: rgba(128,128,128,0.22); color: var(--biru-dongker); }
        .alert-custom {
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        .alert-custom.success { background: #D1FAE5; color: #059669; border-left: 4px solid var(--success); }
        .alert-custom.info { background: #DBEAFE; color: #2563EB; border-left: 4px solid #3B82F6; }
        .alert-custom.error { background: #FEE2E2; color: #DC2626; border-left: 4px solid var(--danger); }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--abu-abu); }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.4; }
        footer { text-align: center; padding: 20px; color: var(--abu-abu); font-size: 0.8rem; border-top: 1px solid rgba(255,182,193,0.2); margin-top: 30px; }
        @media (max-width: 992px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .filter-section { flex-direction: column; align-items: stretch; }
            .filter-section .form-control, .filter-section .form-select, .filter-section .btn-filter, .filter-section .btn-reset { width: 100%; }
        }
        @media (max-width: 768px) {
            .products-table { overflow-x: auto; }
            .products-table table { min-width: 800px; }
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
        
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-box"></i> Manajemen Produk</h1>
            <button class="btn-add-product" onclick="openProductModal()"><i class="fas fa-plus-circle"></i> Tambah Produk</button>
        </div>
        
        <form method="GET" class="filter-section">
            <input type="text" class="form-control" placeholder="Cari produk..." name="search" value="<?php echo htmlspecialchars($search); ?>">
            <select class="form-select" name="type">
                <option value="">Semua Tipe</option>
                <option value="slime" <?php echo $filter_type === 'slime' ? 'selected' : ''; ?>>Slime</option>
                <option value="photocard" <?php echo $filter_type === 'photocard' ? 'selected' : ''; ?>>Photocard</option>
            </select>
            <select class="form-select" name="category">
                <option value="">Semua Kategori</option>
                <?php 
                mysqli_data_seek($categories, 0);
                while($cat = mysqli_fetch_assoc($categories)): 
                ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endwhile; ?>
            </select>
            <button class="btn-filter" type="submit"><i class="fas fa-filter"></i> Filter</button>
            <a href="products.php" class="btn-reset"><i class="fas fa-undo-alt"></i> Reset</a>
        </form>
        
        <div class="products-table">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th style="width:70px">Gambar</th><th>Nama Produk</th><th>Kategori</th><th>Tipe</th><th>Harga</th><th>Stok</th><th>Status</th><th style="width:90px">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($products_result) > 0): ?>
                            <?php while($p = mysqli_fetch_assoc($products_result)): 
                                $product_image = !empty($p['image']) ? $upload_dir . $p['image'] : 'https://placehold.co/60x60?text=No+Image';
                            ?>
                            <tr class="product-row">
                                <td><div class="product-image-wrapper"><img src="<?php echo htmlspecialchars($product_image); ?>" class="product-thumb" alt="<?php echo htmlspecialchars($p['name']); ?>" onerror="this.src='https://placehold.co/60x60?text=No+Image'"></div></div></td>
                                <td><div class="product-name"><strong><?php echo htmlspecialchars($p['name']); ?></strong><?php if ($p['is_new']): ?><span class="badge-new"><i class="fas fa-star"></i> Baru</span><?php endif; ?><?php if ($p['is_bestseller']): ?><span class="badge-bestseller"><i class="fas fa-fire"></i> Terlaris</span><?php endif; ?></div></div></td>
                                <td><?php echo htmlspecialchars($p['category_name'] ?? '-'); ?></td>
                                <td><span class="badge-type <?php echo htmlspecialchars($p['product_type']); ?>"><i class="fas <?php echo $p['product_type'] == 'slime' ? 'fa-bezier-curve' : 'fa-camera'; ?>"></i> <?php echo ucfirst($p['product_type']); ?></span></div></td>
                                <td><div class="price-wrapper"><strong class="price-main"><?php echo formatRupiah($p['price']); ?></strong><?php if ($p['old_price']): ?><div class="price-old"><?php echo formatRupiah($p['old_price']); ?></div><?php endif; ?></div></div></td>
                                <td><span class="stock-badge <?php echo $p['stock'] < 5 ? 'low-stock' : ''; ?>"><i class="fas fa-boxes"></i> <?php echo $p['stock']; ?></span></div></td>
                                <td><span class="status-badge <?php echo $p['is_active'] ? 'active' : 'inactive'; ?>"><i class="fas <?php echo $p['is_active'] ? 'fa-check-circle' : 'fa-ban'; ?>"></i> <?php echo $p['is_active'] ? 'Aktif' : 'Nonaktif'; ?></span></div></td>
                                <td><div class="action-buttons">
                                    <button class="btn-action btn-edit" onclick='editProduct(<?php echo json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                        <?php if ($p['is_active']): ?>
                                        <button type="submit" name="delete_product" class="btn-action btn-hide" title="Nonaktifkan" onclick="return confirm('Nonaktifkan produk ini?');"><i class="fas fa-eye-slash"></i></button>
                                        <?php else: ?>
                                        <button type="submit" name="activate_product" class="btn-action btn-show" title="Aktifkan"><i class="fas fa-eye"></i></button>
                                        <?php endif; ?>
                                    </form>
                                </div></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8"><div class="empty-state"><i class="fas fa-box-open"></i><p>Belum ada produk</p><small>Klik "Tambah Produk" untuk memulai</small></div></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <footer>
            <p>© <?php echo date('Y'); ?> <strong>VIJARIE</strong> — Panel Admin. Hak Cipta Dilindungi.</p>
        </footer>
    </main>
    
    <!-- Modal Tambah/Edit Produk -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-box"></i> <span id="modalTitle">Tambah Produk</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="productForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="product_id">
                        <input type="hidden" name="old_image" id="old_image">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-tag"></i> Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-link"></i> Slug</label>
                                <input type="text" name="slug" id="slug" class="form-control" placeholder="otomatis">
                                <small class="text-muted">Kosongkan untuk otomatis</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-boxes"></i> Tipe Produk <span class="text-danger">*</span></label>
                                <select name="product_type" id="product_type" class="form-select" required>
                                    <option value="slime">Slime</option>
                                    <option value="photocard">Photocard</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label"><i class="fas fa-list"></i> Kategori <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php 
                                    mysqli_data_seek($categories, 0);
                                    while($cat = mysqli_fetch_assoc($categories)): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-money-bill-wave"></i> Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" name="price" id="price" class="form-control" min="0" step="100" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-tags"></i> Harga Coret (Rp)</label>
                                <input type="number" name="old_price" id="old_price" class="form-control" min="0" step="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-cubes"></i> Stok <span class="text-danger">*</span></label>
                                <input type="number" name="stock" id="stock" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-image"></i> Gambar Produk</label>
                                <label class="image-upload-wrapper" for="product_image">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>Klik untuk upload</div>
                                    <small class="text-muted">JPG, PNG, Webp (Max 5MB)</small>
                                    <input type="file" name="product_image" id="product_image" accept="image/*" onchange="previewImage(this)">
                                </label>
                                <div class="image-preview-container"><img id="imagePreview" class="image-preview" alt="Preview"></div>
                                <div id="currentImage" class="mt-2"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label"><i class="fas fa-align-left"></i> Deskripsi</label>
                                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_bestseller" id="is_bestseller">
                                    <label class="form-check-label"><i class="fas fa-fire"></i> Tandai sebagai <strong>Terlaris</strong></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_new" id="is_new">
                                    <label class="form-check-label"><i class="fas fa-star"></i> Tandai sebagai <strong>Produk Baru</strong></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
                        <button type="submit" name="save_product" class="btn-save"><i class="fas fa-save"></i> Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const productModal = new bootstrap.Modal(document.getElementById('productModal'));
        function openProductModal() {
            document.getElementById('modalTitle').innerText = 'Tambah Produk';
            document.getElementById('productForm').reset();
            document.getElementById('product_id').value = '';
            document.getElementById('old_image').value = '';
            document.getElementById('slug').readOnly = false;
            document.getElementById('imagePreview').classList.remove('show');
            document.getElementById('currentImage').innerHTML = '';
            productModal.show();
        }
        function editProduct(product) {
            document.getElementById('modalTitle').innerText = 'Edit Produk';
            document.getElementById('product_id').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('slug').value = product.slug;
            document.getElementById('slug').readOnly = true;
            document.getElementById('product_type').value = product.product_type;
            document.getElementById('category_id').value = product.category_id;
            document.getElementById('price').value = product.price;
            document.getElementById('old_price').value = product.old_price;
            document.getElementById('stock').value = product.stock;
            document.getElementById('description').value = product.description;
            document.getElementById('old_image').value = product.image;
            document.getElementById('is_bestseller').checked = product.is_bestseller == 1;
            document.getElementById('is_new').checked = product.is_new == 1;
            if (product.image) {
                document.getElementById('currentImage').innerHTML = '<small class="text-muted">Gambar saat ini:</small><br><img src="../uploads/products/' + product.image + '" width="100" class="rounded border mt-1">';
            } else {
                document.getElementById('currentImage').innerHTML = '';
            }
            productModal.show();
        }
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.classList.add('show');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        document.getElementById('name')?.addEventListener('input', function() {
            const slug = document.getElementById('slug');
            if (slug && !slug.readOnly && !slug.dataset.userEdited) {
                slug.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            }
        });
        document.getElementById('slug')?.addEventListener('input', function() { this.dataset.userEdited = 'true'; });
        document.getElementById('productForm')?.addEventListener('submit', function() {
            let price = document.getElementById('price');
            let oldPrice = document.getElementById('old_price');
            if (price) price.value = price.value.replace(/\D/g, '');
            if (oldPrice) oldPrice.value = oldPrice.value.replace(/\D/g, '');
        });
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
        });
    </script>
</body>
</html>