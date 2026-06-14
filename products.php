<?php
require_once 'config.php';
require_once 'includes/functions.php';

$page_title = "Produk - " . SITE_NAME;

// ============================================
// FALLBACK jika fungsi helper belum ada
// ============================================
if (!function_exists('getProductTypeLabel')) {
    function getProductTypeLabel($type) {
        return $type == 'slime' ? 'Slime' : 'Photocard';
    }
}

// ============================================
// HELPER: Map Kategori K-Pop
// ============================================
function mapKpopCategory($categoryName) {
    if (empty($categoryName)) return 'Uncategorized';
    $kpop_keywords = ['bts', 'blackpink', 'newjeans', 'twice', 'ive', 
        'nct', 'aespa', 'enhypen', 'other', 'photocard', 'kpop', 
        'album', 'merchandise', 'lightstick', 'poster', 'standee', 
        'banner', 'lomo', 'polaroid'];
    $name_lower = strtolower($categoryName);
    foreach ($kpop_keywords as $keyword) {
        if (strpos($name_lower, $keyword) !== false) return 'K-Pop Merchandise';
    }
    return $categoryName;
}

// ============================================
// INITIALIZE FILTERS
// ============================================
$filters = [
    'type'      => $_GET['type'] ?? '',
    'category'  => $_GET['category'] ?? '',
    'search'    => $_GET['search'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'sort'      => $_GET['sort'] ?? 'newest'
];

// ============================================
// AMBIL SEMUA KATEGORI (untuk dropdown filter)
// ============================================
$conn = getDBConnection();
$all_categories = [];
if ($conn) {
    $cat_result = mysqli_query($conn, "SELECT name FROM categories WHERE is_active = 1 ORDER BY type, sort_order");
    if ($cat_result) {
        while ($row = mysqli_fetch_assoc($cat_result)) {
            $all_categories[] = $row['name'];
        }
        mysqli_free_result($cat_result);
    }
}

// ============================================
// PILL KATEGORI YANG DITAMPILKAN (hanya 5 slime + K-Pop)
// ============================================
$pill_categories = [
    'Clear Slime',
    'Fluffy Slime',
    'Cloud Slime',
    'Butter Slime',
    'Crunchy Slime',
    'K-Pop Merchandise'
];

// Ambil daftar nama kategori K-Pop asli (untuk keperluan filter query)
$kpop_original_names = [];
if ($conn) {
    $cat_result = mysqli_query($conn, "SELECT name FROM categories WHERE type = 'photocard' AND is_active = 1");
    if ($cat_result) {
        while ($row = mysqli_fetch_assoc($cat_result)) {
            $kpop_original_names[] = $row['name'];
        }
        mysqli_free_result($cat_result);
    }
}
$is_kpop_filter = ($filters['category'] === 'K-Pop Merchandise');

// ============================================
// GET PRODUCTS (query tetap sama, tidak dibatasi)
// ============================================
$where = "WHERE p.is_active = 1";
$params = [];
$types = "";

if (!empty($filters['search'])) {
    $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%" . $filters['search'] . "%";
    $params[] = $search_param; $params[] = $search_param;
    $types .= "ss";
}
if (!empty($filters['type'])) {
    $where .= " AND p.product_type = ?";
    $params[] = $filters['type'];
    $types .= "s";
}
if (!empty($filters['category']) && !$is_kpop_filter) {
    $where .= " AND c.name = ?";
    $params[] = $filters['category'];
    $types .= "s";
} elseif ($is_kpop_filter && !empty($kpop_original_names)) {
    $placeholders = implode(',', array_fill(0, count($kpop_original_names), '?'));
    $where .= " AND c.name IN ($placeholders)";
    foreach ($kpop_original_names as $kcat) { $params[] = $kcat; $types .= "s"; }
}
if (!empty($filters['min_price'])) {
    $where .= " AND p.price >= ?";
    $params[] = (float)$filters['min_price'];
    $types .= "d";
}
if (!empty($filters['max_price'])) {
    $where .= " AND p.price <= ?";
    $params[] = (float)$filters['max_price'];
    $types .= "d";
}

$sort_options = [
    'newest'     => 'p.created_at DESC',
    'price_low'  => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'popular'    => 'p.sold_count DESC, p.rating DESC'
];
$sort_by = $sort_options[$filters['sort']] ?? 'p.created_at DESC';

$query = "SELECT p.*, c.name as category_name, c.icon as category_icon
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          $where
          ORDER BY $sort_by";

$products_array = [];
if ($conn) {
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $products_result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($products_result)) {
            $row['display_category'] = mapKpopCategory($row['category_name'] ?? '');
            $products_array[] = $row;
        }
        mysqli_free_result($products_result);
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
$product_count = count($products_array);
?>

<!-- HEADER -->
<?php require_once 'includes/header.php'; ?>

<!-- CSS TAMBAHAN (spesifik halaman produk) -->
<style>
:root {
    --pink-coral: #FFB6C1;
    --pink-tua: #FF69B4;
    --tosca: #40E0D0;
    --tosca-muda: #7FFFD4;
    --kuning: #FFD700;
    --kuning-muda: #FFE4B5;
    --biru-dongker: #000080;
    --abu-abu: #808080;
}
.products-header {
    background: linear-gradient(135deg, var(--pink-coral) 0%, var(--tosca) 100%);
    padding: 80px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.products-header::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
    animation: rotate 30s linear infinite;
    pointer-events: none;
}
@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.products-header-content { position: relative; z-index: 2; }
.products-header h1 {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 800;
    color: white;
    margin-bottom: 15px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    font-family: 'Poppins', sans-serif;
}
.products-header p {
    font-size: 1.3rem;
    color: rgba(255,255,255,0.95);
    font-weight: 500;
    margin-bottom: 25px;
}
.products-badge {
    display: inline-block;
    background: rgba(255,255,255,0.25);
    backdrop-filter: blur(10px);
    padding: 10px 25px;
    border-radius: 50px;
    color: white;
    font-weight: 600;
    border: 2px solid rgba(255,255,255,0.4);
}
.products-section {
    padding: 80px 0;
    background: linear-gradient(135deg, #FFF5F7 0%, #F0FFFF 50%, #FFFBE6 100%);
}
.filter-sidebar {
    background: white;
    border-radius: 30px;
    padding: 35px;
    box-shadow: 0 20px 60px rgba(255,182,193,0.25);
    border: 2px solid var(--pink-coral);
    position: sticky;
    top: 160px;
}
.filter-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px dashed var(--pink-coral);
}
.filter-header h4 {
    color: var(--biru-dongker);
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
    font-size: 1.4rem;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.filter-group { margin-bottom: 25px; }
.filter-label {
    font-weight: 600;
    color: var(--biru-dongker);
    margin-bottom: 12px;
    display: block;
    font-size: 0.95rem;
    font-family: 'Poppins', sans-serif;
}
.filter-select, .filter-input {
    border: 2px solid var(--pink-coral);
    border-radius: 15px;
    padding: 12px 18px;
    font-size: 0.95rem;
    transition: all 0.3s;
    background: white;
    color: var(--biru-dongker);
    width: 100%;
}
.price-range { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.filter-btn {
    background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
    border: none;
    color: white;
    font-weight: 600;
    padding: 14px 25px;
    border-radius: 50px;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.filter-btn:hover { transform: translateY(-3px); box-shadow: 0 12px 35px rgba(255,105,180,0.4); }
.filter-reset {
    background: white;
    border: 2px solid var(--abu-abu);
    color: var(--abu-abu);
    font-weight: 600;
    padding: 14px 25px;
    border-radius: 50px;
    width: 100%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 12px;
    text-decoration: none;
}
.filter-reset:hover { background: var(--abu-abu); color: white; border-color: var(--abu-abu); }
.products-grid-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}
.products-count { color: var(--biru-dongker); font-weight: 600; }
.products-count span { color: var(--pink-tua); font-weight: 700; }
.products-sort {
    display: flex;
    align-items: center;
    gap: 12px;
}
.sort-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    background: white;
    padding: 8px 20px;
    border-radius: 50px;
    border: 2px solid var(--pink-coral);
    transition: all 0.3s ease;
}
.sort-wrapper:hover {
    border-color: var(--pink-tua);
    box-shadow: 0 4px 12px rgba(255,105,180,0.2);
}
.sort-icon {
    color: var(--pink-tua);
    font-size: 0.9rem;
}
.sort-wrapper label {
    color: var(--biru-dongker);
    font-weight: 600;
    font-size: 0.9rem;
    margin: 0;
}
.sort-select {
    border: none;
    background: transparent;
    padding: 6px 0;
    font-weight: 500;
    color: var(--biru-dongker);
    cursor: pointer;
    outline: none;
    font-size: 0.9rem;
}
.sort-select:focus { outline: none; }
.category-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 30px;
}
.category-pill {
    background: white;
    border: 2px solid var(--pink-coral);
    border-radius: 50px;
    padding: 8px 20px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--biru-dongker);
    text-decoration: none;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.category-pill:hover {
    background: var(--pink-coral);
    color: white;
    border-color: transparent;
}
.category-pill.active {
    background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
    color: white;
    border-color: transparent;
}
.category-pill.kpop {
    border-color: #eab033;
}
.category-pill.kpop.active {
    background: linear-gradient(135deg, #eab033, #EC4899);
}
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
}
.empty-state {
    text-align: center;
    padding: 80px 30px;
    background: white;
    border-radius: 30px;
    border: 2px solid var(--pink-coral);
    max-width: 600px;
    margin: 0 auto;
}
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}
.loading-overlay.active { opacity: 1; visibility: visible; }
.loading-spinner {
    width: 60px;
    height: 60px;
    border: 5px solid var(--pink-coral);
    border-top-color: var(--pink-tua);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 992px) { .filter-sidebar { position: static; margin-bottom: 40px; } }
@media (max-width: 768px) { .products-header { padding: 60px 0; } .products-section { padding: 60px 0; } }
@media (max-width: 576px) { .category-pills { justify-content: center; } }
</style>


<!-- KONTEN UTAMA -->
<section class="products-header">
    <div class="container products-header-content">
        <span class="products-badge"><i class="fas fa-store me-2"></i> <?php echo $product_count; ?> Produk Tersedia</span>
        <h1 class="fw-bold mb-3">
            <?php
            if (!empty($filters['type'])) {
                echo getProductTypeLabel($filters['type']) . ' Collection';
            } elseif (!empty($filters['category'])) {
                echo htmlspecialchars($filters['category']) . ' Collection';
            } else {
                echo 'Semua Produk';
            }
            ?>
        </h1>
        <p class="lead">
            <?php
            if (!empty($filters['type'])) {
                echo 'Temukan Koleksi ' . getProductTypeLabel($filters['type']);
            } elseif ($is_kpop_filter) {
                echo 'Temukan Photocard K-Pop Terbaik!';
            } else {
                echo 'Slime Cantik & Photocard K-Pop Terlengkap';
            }
            ?>
        </p>
    </div>
</section>

<section class="products-section">
    <div class="container">
        <div class="row g-5">
            <!-- Filter Sidebar -->
            <div class="col-lg-3">
                <div class="filter-sidebar" data-aos="fade-right">
                    <div class="filter-header"><h4><i class="fas fa-filter"></i> Filter</h4></div>
                    <form method="GET" id="filterForm">
                        <div class="filter-group">
                            <label class="filter-label">Toko</label>
                            <select name="type" class="filter-select">
                                <option value="">Semua Toko</option>
                                <option value="slime" <?php echo $filters['type'] == 'slime' ? 'selected' : ''; ?>>Vij Slimee</option>
                                <option value="photocard" <?php echo $filters['type'] == 'photocard' ? 'selected' : ''; ?>>Aprpiejise</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Cari</label>
                            <input type="text" name="search" class="filter-input" placeholder="Nama produk..." value="<?php echo htmlspecialchars($filters['search']); ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Kategori</label>
                            <select name="category" class="filter-select">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($all_categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($filters['category'] ?? '') == $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Rentang Harga</label>
                            <div class="price-range">
                                <input type="number" name="min_price" class="filter-input" placeholder="Min" value="<?php echo htmlspecialchars($filters['min_price']); ?>">
                                <input type="number" name="max_price" class="filter-input" placeholder="Maks" value="<?php echo htmlspecialchars($filters['max_price']); ?>">
                            </div>
                        </div>
                        <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Terapkan Filter</button>
                        <a href="products.php" class="filter-reset"><i class="fas fa-undo"></i> Reset Filter</a>
                    </form>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-lg-9">
                <!-- Category Pills (hanya 6) -->
                <div class="category-pills" data-aos="fade-up">
                    <a href="products.php" class="category-pill <?php echo empty($filters['category']) ? 'active' : ''; ?>">Semua</a>
                    <?php foreach ($pill_categories as $pill_cat): ?>
                        <a href="products.php?category=<?php echo urlencode($pill_cat); ?>" class="category-pill <?php echo $pill_cat === 'K-Pop Merchandise' ? 'kpop' : ''; ?> <?php echo ($filters['category'] ?? '') == $pill_cat ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($pill_cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="products-grid-header" data-aos="fade-up">
                    <div class="products-count">Menampilkan <span><?php echo $product_count; ?></span> produk</div>
                    <div class="products-sort">
                        <div class="sort-wrapper">
                            <i class="fas fa-arrow-down-wide-short sort-icon"></i>
                            <label for="sortSelect">Urutkan:</label>
                            <select id="sortSelect" class="sort-select">
                                <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                                <option value="price_low" <?php echo $filters['sort'] == 'price_low' ? 'selected' : ''; ?>>Harga: Rendah ke Tinggi</option>
                                <option value="price_high" <?php echo $filters['sort'] == 'price_high' ? 'selected' : ''; ?>>Harga: Tinggi ke Rendah</option>
                                <option value="popular" <?php echo $filters['sort'] == 'popular' ? 'selected' : ''; ?>>Paling Populer</option>
                            </select>
                        </div>
                    </div>
                </div>

                <?php if (!empty($products_array)): ?>
                <div class="products-grid">
                    <?php foreach ($products_array as $product): ?>
                        <?php include 'includes/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state" data-aos="fade-up">
                    <div class="empty-state-icon"><i class="fas fa-search"></i></div>
                    <h3>Tidak Ada Produk</h3>
                    <p>Coba ubah filter atau kata kunci pencarian.</p>
                    <a href="products.php" class="btn btn-primary rounded-pill px-5 py-3" style="background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); border: none;"><i class="fas fa-undo me-2"></i>Lihat Semua Produk</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="loading-overlay" id="loadingOverlay"><div class="loading-spinner"></div></div>

<!-- FOOTER -->
<?php require_once 'includes/footer.php'; ?>

<!-- Script tambahan (sama seperti sebelumnya) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration: 800, once: true, offset: 100 });

function addToCartWithRedirect(productId, productName, productPrice) {
    document.getElementById('loadingOverlay').classList.add('active');
    fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'add_to_cart=1&product_id=' + productId + '&qty=1'
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('loadingOverlay').classList.remove('active');
        if (data.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Produk ditambah ke Keranjang!', text: productName + ' telah ditambahkan ke keranjang Anda', timer: 1500, showConfirmButton: false }).then(() => { window.location.href = 'cart.php'; });
            } else {
                alert(productName + ' telah ditambahkan ke keranjang Anda!');
                window.location.href = 'cart.php';
            }
        } else {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Oops...', text: data.message || 'Gagal menambahkan ke keranjang' });
        }
    })
    .catch(() => { document.getElementById('loadingOverlay').classList.remove('active'); if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menambahkan ke keranjang. Silakan coba lagi.' }); });
}

function toggleWishlist(e, id) {
    e.preventDefault(); e.stopPropagation();
    const btn = e.currentTarget;
    const isActive = btn.classList.contains('active');
    fetch('wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${id}&${isActive ? 'remove_wishlist' : 'add_wishlist'}=1`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.classList.toggle('active');
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: isActive ? 'Dihapus dari Wishlist' : 'Ditambahkan ke Wishlist', timer: 1500, showConfirmButton: false });
        }
    });
}

document.querySelectorAll('.filter-select, input[name="min_price"], input[name="max_price"]').forEach(el => {
    el.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

document.getElementById('sortSelect')?.addEventListener('change', function() {
    const newSort = this.value;
    const url = new URL(window.location.href);
    url.searchParams.set('sort', newSort);
    window.location.href = url.toString();
});
</script>