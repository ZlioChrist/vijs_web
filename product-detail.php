<?php
require_once 'config.php';
require_once 'includes/functions.php';

$id = $_GET['id'] ?? 0;
$product = getProduct($id);
if (!$product) {
    setFlash('❌ Produk tidak ditemukan', 'error');
    redirect('products.php');
}

$page_title = $product['name'] . " - " . SITE_NAME;
$is_slime = $product['product_type'] == 'slime';
$type_label = $is_slime ? 'Vij Slimee' : 'Aprpiejise';
$type_icon = $is_slime ? '<i class="fas fa-hand-peace"></i>' : '<i class="fas fa-album-collection"></i>';
$type_gradient = $is_slime 
    ? 'linear-gradient(135deg, #FFB6C1, #FF9AA2)' 
    : 'linear-gradient(135deg, #40E0D0, #7FFFD4)';

$category_id = $product['category_id'] ?? 0;
$related = getRelatedProducts($category_id, $product['product_type'], $product['id']);

$rating = floatval($product['rating'] ?? 4.5);
$review_count = intval($product['review_count'] ?? rand(10, 150));
$stock = $product['stock'] ?? 0;
?>
<?php require_once 'includes/header.php'; ?>

<!-- CSS Khusus Halaman Detail Produk -->
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
:root {
    --tosca: #40E0D0;
    --tosca-gelap: #20B2AA;
    --pink-soft: #FFB6C1;
    --pink-medium: #FF9AA2;
    --pink-dark: #FF6B9D;
    --yellow-coral: #FFD700;
    --yellow-coral-dark: #FFA500;
    --navy: #1a2a6c;
    --gray: #6c757d;
    --white: #ffffff;
    --shadow: 0 20px 35px rgba(0,0,0,0.08);
    --transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
}
body {
    font-family: 'Quicksand', sans-serif;
    background: linear-gradient(145deg, #FFF5F7 0%, #FFFFFF 100%);
    color: #2d3e50;
    line-height: 1.6;
}
.bg-stars {
    position: fixed; inset: 0; pointer-events: none; z-index: 0;
    background-image: radial-gradient(2px 2px at 20px 30px, #FFB6C1, transparent),
                      radial-gradient(2px 2px at 80px 60px, #FFD700, transparent),
                      radial-gradient(2px 2px at 150px 100px, #40E0D0, transparent);
    background-size: 200px 200px;
    opacity: 0.4;
    animation: twinkle 8s infinite;
}
@keyframes twinkle { 0%,100%{opacity:0.3;}50%{opacity:0.5;} }

/* Header produk */
.product-header {
    background: linear-gradient(135deg, var(--pink-soft), var(--pink-medium));
    padding: 12px 0;
    position: relative;
    z-index: 1;
}
.back-wrapper {
    display: flex; justify-content: space-between; align-items: center;
}
.btn-back {
    background: rgba(255,255,255,0.2); backdrop-filter: blur(12px);
    padding: 8px 20px; border-radius: 50px; color: white;
    text-decoration: none; font-weight: 600; transition: var(--transition);
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-back:hover { background: white; color: var(--pink-dark); transform: translateX(-4px); }

/* Main */
.product-main { padding: 60px 0; position: relative; z-index: 1; }

/* Gallery & Image */
.gallery-card {
    background: white; border-radius: 32px; overflow: hidden;
    box-shadow: var(--shadow); border: 2px solid var(--tosca);
    cursor: zoom-in;
    transition: var(--transition);
    min-height: 480px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.gallery-card:hover { transform: translateY(-8px); box-shadow: 0 30px 45px rgba(64,224,208,0.25); }
.main-img {
    width: 100%; height: 480px; object-fit: cover; display: block;
    pointer-events: none;
}
.zoom-hint {
    position: absolute; bottom: 18px; right: 18px;
    background: rgba(0,0,0,0.55); backdrop-filter: blur(8px);
    padding: 6px 14px; border-radius: 40px; font-size: 0.75rem;
    color: white; display: inline-flex; align-items: center; gap: 8px;
    pointer-events: none;
}

/* Info Card */
.info-card {
    background: white; border-radius: 32px; padding: 35px;
    box-shadow: var(--shadow); border: 1px solid #FFE4B5;
    position: sticky; top: 100px;
    min-height: 600px;
    display: flex;
    flex-direction: column;
}
.type-badge {
    display: inline-flex; align-items: center; gap: 10px;
    background: <?= $type_gradient ?>;
    padding: 6px 18px; border-radius: 50px; color: white; font-weight: 600;
    margin-bottom: 20px;
}
.product-title {
    font-family: 'Poppins', sans-serif; font-size: 2rem; font-weight: 800;
    color: var(--navy); margin-bottom: 15px; line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 3.2rem;
}
.rating {
    display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
    flex-wrap: wrap;
}
.stars { color: var(--yellow-coral); font-size: 1rem; letter-spacing: 3px; }
.price {
    font-size: 2rem; font-weight: 800;
    background: linear-gradient(135deg, var(--yellow-coral), var(--yellow-coral-dark));
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    margin: 20px 0; display: flex; align-items: baseline; gap: 12px;
    flex-shrink: 0;
    flex-wrap: wrap;
}
.old-price { font-size: 1.2rem; color: var(--gray); text-decoration: line-through; -webkit-text-fill-color: var(--gray); }
.desc-box {
    background: <?= $is_slime ? '#FFF5F8' : '#F0FFFE' ?>;
    padding: 22px; border-radius: 24px;
    border-left: 5px solid <?= $is_slime ? 'var(--pink-medium)' : 'var(--tosca)' ?>;
    margin: 25px 0;
    max-height: 150px;
    overflow-y: auto;
}
.stock {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 18px; border-radius: 50px; font-weight: 600;
    margin-bottom: 20px;
    flex-shrink: 0;
}
.stock.in { background: #E0FCE8; color: #0B5E2E; }
.stock.low { background: #FEF7E0; color: #B45309; }
.stock.out { background: #FEE2E2; color: #B91C1C; }

.quantity-wrapper {
    background: #F8FAFF;
    border-radius: 60px;
    padding: 12px 20px;
    margin: 25px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    flex-shrink: 0;
}
.quantity-label {
    font-weight: 600;
    color: var(--navy);
    font-size: 1rem;
}
.quantity-control {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    border-radius: 50px;
    padding: 4px 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.qty-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid var(--tosca);
    background: white;
    font-size: 1.3rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--tosca-gelap);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.qty-btn:hover:not(:disabled) {
    background: var(--tosca);
    color: white;
    border-color: transparent;
    transform: scale(1.05);
}
.qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.qty-input {
    width: 60px;
    text-align: center;
    border: none;
    background: transparent;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--navy);
    padding: 8px 0;
}
.qty-input:focus { outline: none; }

.action-buttons {
    display: flex;
    gap: 15px;
    margin: 30px 0;
    flex-shrink: 0;
}
.btn-cart, .btn-share {
    flex: 1;
    padding: 14px;
    border-radius: 50px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    transition: var(--transition);
    cursor: pointer;
    border: none;
}
.btn-cart {
    background: linear-gradient(135deg, var(--pink-dark), var(--pink-medium));
    color: white;
    box-shadow: 0 8px 20px rgba(255,107,157,0.25);
}
.btn-cart:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(255,107,157,0.35); }
.btn-share {
    background: white; border: 2px solid var(--tosca); color: var(--tosca-gelap);
}
.btn-share:hover { background: var(--tosca); color: white; border-color: transparent; transform: translateY(-3px); }

/* Related */
.related-section { padding: 60px 0; background: #FFFBF5; }
.related-title {
    text-align: center; font-size: 2rem; font-weight: 800; margin-bottom: 40px;
    color: var(--navy); display: flex; align-items: center; justify-content: center; gap: 12px;
}

/* Zoom Modal */
.zoom-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
    z-index: 10000;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.zoom-modal.active {
    display: flex;
    opacity: 1;
}
.zoom-modal-content {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    justify-content: center;
    align-items: center;
    transform: scale(0.95);
    transition: transform 0.3s ease;
}
.zoom-modal.active .zoom-modal-content {
    transform: scale(1);
}
.zoom-img {
    max-width: 100%;
    max-height: 90vh;
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 12px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    cursor: zoom-out;
}
.zoom-close {
    position: absolute;
    top: -40px;
    right: 0;
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s, background 0.2s;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.zoom-close:hover {
    transform: rotate(90deg);
    background: var(--pink-dark);
    color: white;
}

/* Responsive improvements */
@media (max-width: 992px) {
    .info-card { position: static; margin-top: 30px; min-height: auto; }
    .gallery-card, .main-img { min-height: 380px; height: 380px; }
    .product-title { font-size: 1.8rem; }
}
@media (max-width: 768px) {
    .product-main { padding: 40px 0; }
    .info-card { padding: 25px; }
    .product-title { font-size: 1.6rem; min-height: auto; }
    .price { font-size: 1.8rem; }
    .action-buttons { flex-direction: column; }
    .btn-cart, .btn-share { width: 100%; }
    .quantity-wrapper { flex-direction: column; align-items: stretch; text-align: center; }
    .quantity-control { justify-content: center; }
    .zoom-hint { font-size: 0.7rem; padding: 4px 10px; bottom: 12px; right: 12px; }
    .zoom-close { top: -30px; width: 32px; height: 32px; }
}
@media (max-width: 576px) {
    .product-title { font-size: 1.4rem; }
    .price { font-size: 1.6rem; }
    .old-price { font-size: 1rem; }
    .type-badge { font-size: 0.8rem; padding: 4px 12px; }
    .qty-btn { width: 36px; height: 36px; font-size: 1.2rem; }
    .qty-input { width: 50px; font-size: 1rem; }
}
</style>

<!-- Header produk (kembali) -->
<section class="product-header">
    <div class="container">
        <div class="back-wrapper">
            <a href="javascript:history.back()" class="btn-back"><i class="fas fa-chevron-left"></i> Kembali</a>
        </div>
    </div>
</section>

<!-- Detail Produk -->
<section class="product-main">
    <div class="container">
        <div class="row g-5">
            <!-- Gambar dengan zoom -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="gallery-card" id="zoomTrigger">
                    <img src="<?= $product['image'] ?: 'assets/images/placeholder.jpg' ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="main-img" id="mainImg">
                    <div class="zoom-hint"><i class="fas fa-search-plus"></i> Zoom</div>
                </div>
            </div>
            
            <!-- Info Produk -->
            <div class="col-lg-6" data-aos="fade-left">
                <div class="info-card">
                    <div class="type-badge"><?= $type_icon ?> <?= $type_label ?></div>
                    <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    
                    <!-- Harga -->
                    <div class="price">
                        <?php if($product['old_price']): ?>
                            <span class="old-price"><?= formatRupiah($product['old_price']) ?></span>
                        <?php endif; ?>
                        <?= formatRupiah($product['price']) ?>
                    </div>
                    
                    <!-- Deskripsi -->
                    <div class="desc-box">
                        <h4 style="font-weight:700; margin-bottom:12px;"><i class="fas fa-file-alt"></i> Deskripsi</h4>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                    
                    <!-- Stok -->
                    <div class="stock <?= $stock < 1 ? 'out' : ($stock < 10 ? 'low' : 'in') ?>">
                        <i class="fas fa-<?= $stock < 1 ? 'times-circle' : ($stock < 10 ? 'exclamation-triangle' : 'check-circle') ?>"></i>
                        <?php
                        if($stock < 1) echo "Stok Habis";
                        elseif($stock < 10) echo "Stok Terbatas: {$stock} tersisa!";
                        else echo "Tersedia";
                        ?>
                    </div>
                    
                    <!-- Quantity -->
                    <div class="quantity-wrapper">
                        <span class="quantity-label">Jumlah:</span>
                        <div class="quantity-control">
                            <button class="qty-btn" onclick="changeQty(-1)" <?= $stock < 1 ? 'disabled' : '' ?>>−</button>
                            <input type="number" id="qty" class="qty-input" value="1" min="1" max="<?= $stock ?>" <?= $stock < 1 ? 'disabled' : '' ?>>
                            <button class="qty-btn" onclick="changeQty(1)" <?= $stock < 1 ? 'disabled' : '' ?>>+</button>
                        </div>
                    </div>
                    
                    <!-- Tombol Aksi -->
                    <div class="action-buttons">
                        <button class="btn-cart" onclick="addToCart(<?= $product['id'] ?>)" <?= $stock < 1 ? 'disabled' : '' ?>>
                            <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                        </button>
                        <button class="btn-share" onclick="shareProduct()">
                            <i class="fas fa-share-alt"></i> Bagikan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Zoom -->
<div class="zoom-modal" id="zoomModal">
    <div class="zoom-modal-content">
        <div class="zoom-close" id="zoomCloseBtn"><i class="fas fa-times"></i></div>
        <img src="" alt="Zoom Image" class="zoom-img" id="zoomImg">
    </div>
</div>

<!-- Produk Terkait -->
<?php if($related && mysqli_num_rows($related) > 0): ?>
<section class="related-section">
    <div class="container">
        <h3 class="related-title"><i class="fas fa-magic"></i> Kamu Mungkin Juga Suka</h3>
        <div class="row g-4">
            <?php while($rel = mysqli_fetch_assoc($related)): ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up">
                <?php include 'includes/product-card.php'; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration: 600, once: true, offset: 50 });

function changeQty(delta) {
    const input = document.getElementById('qty');
    let val = parseInt(input.value) + delta;
    const max = parseInt(input.max);
    if (val >= 1 && val <= max) {
        input.value = val;
        input.style.borderColor = '#40E0D0';
        input.style.boxShadow = '0 0 0 3px rgba(64,224,208,0.2)';
        setTimeout(() => { input.style.borderColor = ''; input.style.boxShadow = ''; }, 200);
    }
}

function addToCart(productId) {
    const qty = document.getElementById('qty').value;
    const btn = document.querySelector('.btn-cart');
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
    btn.disabled = true;
    
    fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `add_to_cart=1&product_id=${productId}&qty=${qty}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.cart-count, #cart-count');
            if (badge) badge.innerText = data.count;
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: `Produk ditambahkan ke keranjang (${qty}x)`, confirmButtonColor: '#FF6B9D', timer: 2000, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Stok tidak mencukupi', confirmButtonColor: '#FF6B9D' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menambah ke keranjang', confirmButtonColor: '#FF6B9D' }))
    .finally(() => { btn.innerHTML = original; btn.disabled = false; });
}

function shareProduct() {
    const url = window.location.href;
    const title = document.querySelector('.product-title')?.innerText || 'Produk';
    if (navigator.share) {
        navigator.share({ title: title, text: `Lihat ${title}`, url: url }).catch(() => {});
    } else {
        navigator.clipboard.writeText(url).then(() => {
            Swal.fire({ icon: 'success', title: 'Link Disalin!', text: 'Link produk telah disalin', confirmButtonColor: '#40E0D0', timer: 1500, showConfirmButton: false });
        });
    }
}

// ========== ZOOM (TIDAK DOBEL) ==========
const zoomTrigger = document.getElementById('zoomTrigger');
const zoomModal = document.getElementById('zoomModal');
const zoomImg = document.getElementById('zoomImg');
const zoomCloseBtn = document.getElementById('zoomCloseBtn');
const mainImg = document.getElementById('mainImg');

function openZoom() {
    if (!mainImg || !mainImg.src) return;
    const imgSrc = mainImg.src;
    zoomImg.src = ''; // reset dulu
    zoomModal.classList.add('active');
    document.body.style.overflow = 'hidden';
    // Preload gambar
    const tempImg = new Image();
    tempImg.onload = function() { zoomImg.src = imgSrc; };
    tempImg.src = imgSrc;
}

function closeZoom() {
    zoomModal.classList.remove('active');
    document.body.style.overflow = '';
    setTimeout(() => {
        if (!zoomModal.classList.contains('active')) zoomImg.src = '';
    }, 300);
}

// Event listener untuk container gambar (tanpa onclick di HTML)
if (zoomTrigger) {
    zoomTrigger.addEventListener('click', openZoom);
}
if (zoomCloseBtn) {
    zoomCloseBtn.addEventListener('click', closeZoom);
}
// Tutup jika klik background
zoomModal.addEventListener('click', function(e) {
    if (e.target === zoomModal) closeZoom();
});
// Tutup dengan ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeZoom();
});
</script>