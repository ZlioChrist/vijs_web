<?php
require_once 'config.php';
require_once 'includes/functions.php';

$page_title = "Beranda - " . SITE_NAME;

// ============================================
// AMBIL PRODUK DENGAN JOIN KATEGORI (untuk product-card)
// ============================================
$conn = getDBConnection();

function getProductsWithCategory($filters = []) {
    global $conn;
    if (!$conn) return false;
    
    $where = "WHERE p.is_active = 1";
    $params = [];
    $types = "";
    
    if (!empty($filters['type'])) {
        $where .= " AND p.product_type = ?";
        $params[] = $filters['type'];
        $types .= "s";
    }
    
    $order = "ORDER BY p.created_at DESC";
    if (!empty($filters['sort']) && $filters['sort'] === 'newest') {
        $order = "ORDER BY p.created_at DESC";
    }
    
    $limit = "";
    if (!empty($filters['limit'])) {
        $limit = "LIMIT " . (int)$filters['limit'];
    }
    
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              $where 
              $order 
              $limit";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// Get featured products (8 produk terbaru)
$featured_products = getProductsWithCategory(['sort' => 'newest', 'limit' => 8]);

// Count total products per category
$total_slime = 0;
$total_photocard = 0;

$slime_all = getProductsWithCategory(['type' => TYPE_SLIME]);
if ($slime_all instanceof mysqli_result) {
    $total_slime = mysqli_num_rows($slime_all);
    mysqli_free_result($slime_all);
}

$photocard_all = getProductsWithCategory(['type' => TYPE_PHOTOCARD]);
if ($photocard_all instanceof mysqli_result) {
    $total_photocard = mysqli_num_rows($photocard_all);
    mysqli_free_result($photocard_all);
}

$total_products = $total_slime + $total_photocard;
$show_category_badge = false;
?>

<!-- HEADER SUDAH ADA DI SINI -->
<?php require_once 'includes/header.php'; ?>

<!-- CSS TAMBAHAN (jika tidak ingin menggabungkan ke file terpisah) -->
<style>
    /* Semua style yang sebelumnya ada di head index.php, pindahkan ke sini */
    /* ... (salin semua style dari kode sebelumnya) ... */
    /* Untuk hemat tempat, saya tulis ulang style yang diperlukan */
    :root {
        --pink-coral: #FFB6C1;
        --pink-tua: #FF69B4;
        --pink-muda: #FFC0CB;
        --kuning: #FFD700;
        --kuning-coral: #FFA500;
        --kuning-muda: #FFE4B5;
        --tosca: #40E0D0;
        --tosca-muda: #7FFFD4;
        --tosca-tua: #20B2AA;
        --cream: #FFF8DC;
        --biru-dongker: #000080;
        --abu-abu: #808080;
        --cream-light: rgba(236, 174, 107, 0.81);
    }
    
    html { scroll-behavior: smooth; }
    .min-vh-75 { min-height: 75vh; }
    
    /* ============ HERO SECTION ============ */
    .hero-section {
        min-height: 100vh;
        background: linear-gradient(135deg, var(--pink-muda) 0%, var(--tosca) 100%);
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
        padding: 120px 0 80px;
    }
    .hero-section::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%;
        width: 200%; height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
        animation: rotate 40s linear infinite;
        pointer-events: none;
    }
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .hero-content {
        position: relative;
        z-index: 2;
        animation: fadeInUp 1s ease;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.25);
        backdrop-filter: blur(10px);
        padding: 10px 25px;
        border-radius: 50px;
        color: white;
        font-weight: 600;
        margin-bottom: 25px;
        border: 2px solid rgba(255,255,255,0.4);
    }
    .hero-title {
        font-size: clamp(2.5rem, 6vw, 4.5rem);
        font-weight: 800;
        color: white;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        font-family: 'Poppins', sans-serif;
        line-height: 1.2;
    }
    .hero-title span {
        background: linear-gradient(135deg, var(--kuning), var(--kuning-coral));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .hero-subtitle {
        font-size: clamp(1rem, 2vw, 1.3rem);
        color: rgba(255,255,255,0.95);
        margin-bottom: 40px;
        font-weight: 500;
        line-height: 1.6;
        max-width: 650px;
    }
    .hero-buttons {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .btn-hero-primary {
        background: linear-gradient(135deg, var(--pink-tua), var(--kuning-coral));
        color: white;
        padding: 16px 40px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 1rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 15px 40px rgba(255,105,180,0.4);
        border: none;
    }
    .btn-hero-primary:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 60px rgba(255,105,180,0.55);
        color: white;
    }
    .btn-hero-secondary {
        background: rgba(255,255,255,0.95);
        color: var(--tosca-tua);
        padding: 16px 40px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 1rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        border: 2px solid var(--pink-coral);
    }
    .btn-hero-secondary:hover {
        transform: translateY(-5px);
        background: white;
        color: var(--pink-tua);
    }
    .hero-scroll {
        position: absolute;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 2;
        animation: bounce 2s ease-in-out infinite;
    }
    .hero-scroll a {
        color: white;
        font-size: 2rem;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 55px;
        height: 55px;
        text-decoration: none;
        transition: all 0.3s;
    }
    .hero-scroll a:hover {
        background: rgba(255,255,255,0.4);
        transform: scale(1.1);
    }
    @keyframes bounce {
        0%,100% { transform: translateX(-50%) translateY(0); }
        50% { transform: translateX(-50%) translateY(-15px); }
    }
    
    /* ============ KATEGORI ============ */
    .category-section {
        padding: 80px 0;
        background: white;
    }
    .section-header {
        text-align: center;
        margin-bottom: 60px;
    }
    .section-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, var(--pink-coral), var(--tosca));
        color: white;
        padding: 8px 24px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(255,182,193,0.3);
    }
    .section-title {
        font-size: clamp(1.8rem, 4vw, 2.8rem);
        font-weight: 800;
        color: var(--biru-dongker);
        margin-bottom: 15px;
        font-family: 'Poppins', sans-serif;
        position: relative;
        display: inline-block;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 70px;
        height: 3px;
        background: linear-gradient(90deg, var(--pink-coral), var(--kuning), var(--tosca));
        border-radius: 2px;
    }
    .section-subtitle {
        font-size: 1rem;
        color: var(--abu-abu);
        font-weight: 500;
        margin-top: 20px;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }
    .category-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
    }
    @media (max-width: 768px) {
        .category-grid { grid-template-columns: 1fr; }
        .category-section { padding: 60px 0; }
    }
    .category-card {
        background: white;
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(0,0,0,0.08);
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .category-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 50px rgba(0,0,0,0.15);
    }
    .category-visual {
        height: 200px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .category-card.slime .category-visual {
        background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
    }
    .category-card.photocard .category-visual {
        background: linear-gradient(135deg, var(--tosca), var(--tosca-muda));
    }
    .category-visual::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%;
        width: 200%; height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        animation: rotate 30s linear infinite;
    }
    .category-icon {
        font-size: 5rem;
        position: relative;
        z-index: 2;
        filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));
        transition: transform 0.5s ease;
        color: white;
    }
    .category-card:hover .category-icon {
        transform: scale(1.1) rotate(-5deg);
    }
    .category-count-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255,255,255,0.95);
        color: var(--biru-dongker);
        padding: 6px 14px;
        border-radius: 40px;
        font-weight: 700;
        font-size: 0.8rem;
        z-index: 3;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .category-count-badge i { color: var(--kuning-coral); }
    .category-body {
        padding: 25px;
    }
    .category-body h3 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--biru-dongker);
        margin-bottom: 8px;
        font-family: 'Poppins', sans-serif;
    }
    .category-tagline {
        color: var(--pink-tua);
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .category-card.photocard .category-tagline {
        color: var(--tosca-tua);
    }
    .category-body p {
        color: var(--abu-abu);
        line-height: 1.6;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }
    .category-features {
        list-style: none;
        padding: 0;
        margin-bottom: 20px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .category-features li {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--abu-abu);
        font-size: 0.85rem;
    }
    .category-card.slime .category-features li i { color: var(--pink-tua); }
    .category-card.photocard .category-features li i { color: var(--tosca-tua); }
    .category-explore {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 40px;
        font-weight: 700;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
        width: 100%;
        font-size: 0.9rem;
    }
    .category-card.slime .category-explore {
        background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
        box-shadow: 0 8px 20px rgba(255,105,180,0.25);
    }
    .category-card.photocard .category-explore {
        background: linear-gradient(135deg, var(--tosca), var(--tosca-muda));
        color: var(--biru-dongker);
        box-shadow: 0 8px 20px rgba(64,224,208,0.25);
    }
    .category-explore:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.2);
        color: white;
    }
    .category-card.photocard .category-explore:hover {
        color: var(--biru-dongker);
    }
    
    /* ============ PRODUK ============ */
    .products-section {
        padding: 80px 0;
        background: linear-gradient(135deg, rgba(255,248,220,0.3) 50%, rgba(255,228,181,0.2) 100%);
        position: relative;
    }
    .products-section::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--tosca), var(--kuning), var(--pink-coral), transparent);
    }
    .product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 25px;
    }
    @media (max-width: 1200px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 992px) { .product-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .product-grid { grid-template-columns: 1fr; } }
    .empty-state {
        grid-column: 1 / -1;
        padding: 50px 20px;
        text-align: center;
        background: rgba(255,248,220,0.3);
        border-radius: 25px;
        border: 2px dashed var(--pink-coral);
    }
    .empty-state i { color: var(--abu-abu); opacity: 0.5; }
    .empty-state p { color: var(--abu-abu); font-size: 1rem; margin-top: 20px; }
    
    /* ============ MENGAPA MEMILIH KAMI ============ */
    .why-choose-section {
        padding: 80px 0;
        background: linear-gradient(135deg, var(--pink-tua), var(--kuning-coral));
        position: relative;
        overflow: hidden;
    }
    .why-choose-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
        background-size: 50px 50px;
        pointer-events: none;
    }
    .why-choose-section .section-badge {
        background: rgba(255,255,255,0.25);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255,255,255,0.3);
    }
    .why-choose-section .section-title { color: white; }
    .why-choose-section .section-title::after {
        background: linear-gradient(90deg, white, var(--kuning), white);
    }
    .why-choose-section .section-subtitle { color: rgba(255,255,255,0.9); }
    .why-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 25px;
        position: relative;
        z-index: 2;
    }
    .why-card {
        background: rgba(255,255,255,0.98);
        border-radius: 24px;
        padding: 30px 20px;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .why-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    .why-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        color: white;
        transition: transform 0.4s ease;
    }
    .why-card:hover .why-icon {
        transform: scale(1.05) rotate(5deg);
    }
    .why-card:nth-child(1) .why-icon { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); }
    .why-card:nth-child(2) .why-icon { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); }
    .why-card:nth-child(3) .why-icon { background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); color: var(--biru-dongker); }
    .why-card:nth-child(4) .why-icon { background: linear-gradient(135deg, var(--pink-tua), var(--kuning)); }
    .why-card h4 {
        color: var(--biru-dongker);
        font-weight: 700;
        font-family: 'Poppins', sans-serif;
        font-size: 1.1rem;
        margin-bottom: 10px;
    }
    .why-card p {
        color: var(--abu-abu);
        line-height: 1.6;
        margin: 0;
        font-size: 0.9rem;
    }
    
    /* ============ CTA ============ */
    .cta-section {
        padding: 80px 0;
        background: linear-gradient(135deg, rgba(255,248,220,0.8), rgba(255,228,181,0.6));
        text-align: center;
    }
    .cta-card {
        background: linear-gradient(35deg, var(--pink-coral), var(--kuning));
        border-radius: 36px;
        padding: 60px 40px;
        box-shadow: 0 30px 70px rgba(255,182,193,0.3);
        max-width: 800px;
        margin: 0 auto;
    }
    .cta-title {
        font-size: clamp(1.8rem, 4vw, 3rem);
        font-weight: 800;
        color: white;
        margin-bottom: 20px;
        font-family: 'Poppins', sans-serif;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    }
    .cta-subtitle {
        font-size: 1.1rem;
        color: rgba(255,255,255,0.95);
        margin-bottom: 40px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    .btn-cta {
        background: white;
        color: var(--pink-tua);
        padding: 18px 50px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 1rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        border: none;
    }
    .btn-cta:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        color: var(--pink-tua);
    }
    
    @media (max-width: 992px) {
        .hero-buttons { justify-content: center; }
        .hero-section { padding: 100px 0 60px; text-align: center; }
        .products-section, .why-choose-section, .cta-section { padding: 60px 0; }
        .cta-card { padding: 50px 30px; }
    }
    @media (max-width: 768px) {
        .hero-title { font-size: 2rem; }
        .hero-badge { font-size: 0.8rem; padding: 6px 18px; }
        .btn-hero-primary, .btn-hero-secondary {
            padding: 14px 30px;
            font-size: 0.9rem;
            width: 100%;
        }
        .section-title { font-size: 1.8rem; }
        .section-subtitle { font-size: 0.9rem; }
        .category-features { grid-template-columns: 1fr; }
        .why-card { padding: 25px 15px; }
        .why-icon { width: 70px; height: 70px; font-size: 1.8rem; }
    }
    @media (max-width: 576px) {
        .hero-title { font-size: 1.8rem; }
        .category-body { padding: 20px; }
        .category-body h3 { font-size: 1.3rem; }
        .category-icon { font-size: 4rem; }
        .products-section { padding: 50px 0; }
    }
</style>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-8 hero-content">
                <div class="hero-badge">
                    <i class="fas fa-store"></i> Toko Resmi
                </div>
                <h1 class="hero-title">
                    Temukan Produk<br>
                    <span>Unik & Berkualitas</span>
                </h1>
                <p class="hero-subtitle">
                    Dari slime warna-warni hingga photocard Kpop – semua kebutuhan Anda dalam satu tempat.
                    Produk berkualitas dengan layanan pelanggan terbaik.
                </p>
                <div class="hero-buttons">
                    <a href="products.php" class="btn-hero-primary">
                        <i class="fas fa-shopping-bag"></i> Belanja Sekarang
                    </a>
                    <a href="#categories" class="btn-hero-secondary">
                        <i class="fas fa-th-large"></i> Lihat Kategori
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-scroll">
        <a href="#categories" aria-label="Gulir ke bawah">
            <i class="fas fa-chevron-down"></i>
        </a>
    </div>
</section>

<!-- KATEGORI -->
<section id="categories" class="category-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge">
                <i class="fas fa-tags"></i> Koleksi
            </span>
            <h2 class="section-title">Pilih Koleksi Favoritmu</h2>
            <p class="section-subtitle">Jelajahi dua kategori produk unggulan kami, dipilih khusus untukmu</p>
        </div>
        <div class="category-grid">
            <!-- Slime -->
            <a href="products.php?type=slime" class="category-card slime" data-aos="fade-right">
                <div class="category-visual">
                    <span class="category-count-badge">
                        <i class="fas fa-box"></i> <?php echo $total_slime; ?> Produk
                    </span>
                    <i class="fas fa-hand-peace category-icon"></i>
                </div>
                <div class="category-body">
                    <h3>Vij Slimee</h3>
                    <div class="category-tagline">
                        <i class="fas fa-sparkles"></i> Slime Buatan Tangan dengan Cinta
                    </div>
                    <p>Slime handmade berkualitas tinggi menggunakan bahan aman dan tidak beracun. Setiap batch dibuat dengan cermat untuk memastikan tekstur yang sempurna.</p>
                    <ul class="category-features">
                        <li><i class="fas fa-check-circle"></i> Bahan Aman</li>
                        <li><i class="fas fa-check-circle"></i> Fluffy & Butter</li>
                        <li><i class="fas fa-check-circle"></i> Pesanan Kustom</li>
                        <li><i class="fas fa-check-circle"></i> Pengiriman Cepat</li>
                    </ul>
                    <span class="category-explore">
                        Lihat Koleksi <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            <!-- Photocard -->
            <a href="products.php?type=photocard" class="category-card photocard" data-aos="fade-left">
                <div class="category-visual">
                    <span class="category-count-badge">
                        <i class="fas fa-box"></i> <?php echo $total_photocard; ?> Produk
                    </span>
                    <i class="fas fa-images category-icon"></i>
                </div>
                <div class="category-body">
                    <h3>Aprpiejise</h3>
                    <div class="category-tagline">
                        <i class="fas fa-star"></i> Destinasi Photocard Kpop Kamu
                    </div>
                    <p>Sumber terpercaya untuk photocard Kpop. Kami menawarkan photocard original maupun fanmade berkualitas tinggi dengan harga terjangkau.</p>
                    <ul class="category-features">
                        <li><i class="fas fa-check-circle"></i> Original & Fanmade</li>
                        <li><i class="fas fa-check-circle"></i> Banyak Grup</li>
                        <li><i class="fas fa-check-circle"></i> Harga Terjangkau</li>
                        <li><i class="fas fa-check-circle"></i> Pengemasan Aman</li>
                    </ul>
                    <span class="category-explore">
                        Lihat Koleksi <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- PRODUK UNGGULAN -->
<section class="products-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge">
                <i class="fas fa-fire"></i> Produk Unggulan
            </span>
            <h2 class="section-title">Produk Terbaru Kami</h2>
            <p class="section-subtitle">Temukan koleksi terbaru dari slime handmade dan photocard Kpop</p>
        </div>
        <div class="product-grid">
            <?php if($featured_products && mysqli_num_rows($featured_products) > 0):
                $delay = 0;
                while($product = mysqli_fetch_assoc($featured_products)): ?>
            <div data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                <?php include 'includes/product-card.php'; ?>
            </div>
            <?php 
                    $delay += 100;
                    if ($delay > 700) $delay = 0;
                endwhile;
            else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open fa-4x mb-4"></i>
                <p>Produk segera hadir! Nantikan 🌟</p>
            </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="products.php" class="btn btn-primary rounded-pill px-5 py-3" style="background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); border: none; font-weight: 600;">
                <i class="fas fa-shopping-bag me-2"></i> Lihat Semua Produk
            </a>
        </div>
    </div>
</section>

<!-- MENGAPA MEMILIH KAMI -->
<section class="why-choose-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge">
                <i class="fas fa-award"></i> Janji Kami
            </span>
            <h2 class="section-title">Kenapa Memilih Kami?</h2>
            <p class="section-subtitle">Kami berkomitmen memberikan pengalaman belanja terbaik untukmu</p>
        </div>
        <div class="why-grid">
            <div class="why-card" data-aos="fade-up">
                <div class="why-icon"><i class="fas fa-certificate"></i></div>
                <h4>100% Original</h4>
                <p>Semua produk dijamin asli dengan pengecekan kualitas sebelum dikirim</p>
            </div>
            <div class="why-card" data-aos="fade-up" data-aos-delay="100">
                <div class="why-icon"><i class="fas fa-shield-halved"></i></div>
                <h4>Garansi Keamanan</h4>
                <p>Garansi uang kembali jika produk rusak atau tidak sesuai deskripsi</p>
            </div>
            <div class="why-card" data-aos="fade-up" data-aos-delay="200">
                <div class="why-icon"><i class="fas fa-headset"></i></div>
                <h4>Respon Cepat</h4>
                <p>Admin kami siap membantu Anda 24/7 via WhatsApp dan Instagram DM</p>
            </div>
            <div class="why-card" data-aos="fade-up" data-aos-delay="300">
                <div class="why-icon"><i class="fas fa-gift"></i></div>
                <h4>Gratis & Diskon</h4>
                <p>Dapatkan freebie eksklusif dan diskon spesial untuk setiap pembelian</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <div class="cta-card" data-aos="zoom-in">
            <div class="cta-content">
                <h2 class="cta-title">Siap Mulai Belanja?</h2>
                <p class="cta-subtitle">Bergabunglah dengan ratusan pelanggan puas dan temukan produk sempurnamu hari ini!</p>
                <a href="products.php" class="btn-cta">
                    <i class="fas fa-shopping-bag"></i> Lihat Semua Produk
                </a>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<?php require_once 'includes/footer.php'; ?>

<!-- Script tambahan (jika belum ada di footer) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true,
        offset: 100,
        easing: 'ease-out-cubic'
    });

    window.addToCartWithRedirect = function(productId, productName, productPrice) {
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
        btn.disabled = true;

        fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'add_to_cart=1&product_id=' + productId + '&qty=1'
        })
        .then(r => r.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            if (data.success) {
                const badge = document.querySelector('.cart-count, #cart-count');
                if (badge) badge.innerText = data.count;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: productName + ' ditambahkan ke keranjang',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'cart.php';
                    });
                } else {
                    alert(productName + ' ditambahkan ke keranjang');
                    window.location.href = 'cart.php';
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message || 'Stok tidak mencukupi'
                    });
                } else {
                    alert('Gagal menambahkan ke keranjang');
                }
            }
        })
        .catch(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal menambahkan ke keranjang. Silakan coba lagi.'
                });
            } else {
                alert('Terjadi kesalahan, coba lagi');
            }
        });
    };

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1 && href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });
</script>