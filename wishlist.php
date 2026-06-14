<?php
require_once 'config.php';
require_once 'includes/functions.php';

$page_title = "Wishlist - " . SITE_NAME;

// Handle AJAX requests for add/remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    header('Content-Type: application/json');
    if (isset($_POST['add_wishlist'])) {
        addToWishlist($_POST['product_id']);
        echo json_encode(['success' => true, 'message' => 'Ditambahkan ke wishlist!']);
        exit;
    }
    if (isset($_POST['remove_wishlist'])) {
        removeFromWishlist($_POST['product_id']);
        echo json_encode(['success' => true, 'message' => 'Dihapus dari wishlist']);
        exit;
    }
}

// Ambil data produk dari wishlist
$wishlist_products = [];
if (!empty($_SESSION['wishlist'])) {
    $ids = implode(',', array_map('intval', $_SESSION['wishlist']));
    $conn = getDBConnection();
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id IN ($ids) AND p.is_active = 1";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $wishlist_products[] = $row;
    }
    mysqli_close($conn);
}
?>
<?php require_once 'includes/header.php'; ?>

<style>
/* Tambahan style untuk halaman wishlist */
.wishlist-header {
    background: linear-gradient(135deg, var(--pink-tua) 0%, var(--pink-coral) 100%);
    padding: 80px 0 60px;
    position: relative;
    overflow: hidden;
}
.wishlist-header::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
    animation: rotate 30s linear infinite;
    pointer-events: none;
}
@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.wishlist-count {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    display: inline-block;
    padding: 8px 20px;
    border-radius: 50px;
    font-weight: 600;
}
.empty-wishlist {
    background: white;
    border-radius: 30px;
    padding: 60px 30px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.08);
    border: 2px dashed var(--pink-coral);
}
.btn-remove-wishlist {
    background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
    border: none;
    padding: 8px 20px;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s;
}
.btn-remove-wishlist:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(238, 90, 90, 0.4);
    background: linear-gradient(135deg, #ee5a5a, #ff4444);
}
.product-card-wrapper {
    transition: all 0.3s ease;
}
.product-card-wrapper.removing {
    opacity: 0;
    transform: scale(0.8);
}
</style>

<!-- Header Wishlist -->
<section class="wishlist-header">
    <div class="container text-center position-relative z-2">
        <i class="fas fa-heart fa-3x text-white mb-3" style="filter: drop-shadow(0 5px 15px rgba(0,0,0,0.2));"></i>
        <h1 class="display-4 fw-bold text-white mb-3">Produk Favorit</h1>
        <p class="lead text-white mb-0">
            <span class="wishlist-count">
                <i class="fas fa-star me-1"></i> <?php echo count($wishlist_products); ?> produk tersimpan
            </span>
        </p>
    </div>
</section>

<!-- Main Content -->
<section class="py-5" style="background: linear-gradient(135deg, rgba(255,248,220,0.3) 0%, rgba(255,228,181,0.2) 100%);">
    <div class="container">
        <?php if (empty($wishlist_products)): ?>
            <div class="empty-wishlist text-center" data-aos="fade-up">
                <i class="far fa-heart fa-5x" style="color: var(--pink-coral); opacity: 0.6;"></i>
                <h3 class="fw-bold mt-4 mb-3" style="color: var(--biru-dongker);">Produk Favoritmu Kosong</h3>
                <p class="text-muted mb-4">Tambahkan produk yang kamu sukai ke dalam wishlist dan mereka akan muncul di sini.</p>
                <a href="products.php" class="btn btn-primary rounded-pill px-5 py-3" style="background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); border: none; font-weight: 600;">
                    <i class="fas fa-shopping-bag me-2"></i> Cari Produk Favoritmu
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4 product-list">
                <?php foreach ($wishlist_products as $product): ?>
                    <div class="col-md-6 col-lg-3 product-card-wrapper" data-product-id="<?php echo $product['id']; ?>" data-aos="fade-up">
                        <?php 
                            $hide_wishlist_button = true; 
                            include 'includes/product-card.php'; 
                        ?>
                        <div class="text-center mt-3">
                            <button class="btn btn-remove-wishlist btn-sm remove-wishlist-btn" data-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-trash-alt me-1"></i> Hapus
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration: 800, once: true, offset: 100 });

// Handle remove from wishlist (AJAX)
document.querySelectorAll('.remove-wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const productId = this.dataset.id;
        const cardWrapper = this.closest('.product-card-wrapper');
        
        Swal.fire({
            title: 'Hapus dari wishlist?',
            text: 'Produk ini akan dihapus dari wishlist Anda.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff6b6b',
            cancelButtonColor: '#808080',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        'remove_wishlist': 1,
                        'product_id': productId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cardWrapper.classList.add('removing');
                        setTimeout(() => {
                            cardWrapper.remove();
                            const countSpan = document.querySelector('.wishlist-count');
                            const remaining = document.querySelectorAll('.product-card-wrapper').length;
                            countSpan.innerHTML = `<i class="fas fa-star me-1"></i> ${remaining} produk tersimpan`;
                            if (remaining === 0) {
                                location.reload();
                            }
                        }, 300);
                        Swal.fire('Terhapus!', data.message, 'success');
                    } else {
                        Swal.fire('Error!', 'Gagal menghapus item.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error!', 'Terjadi kesalahan.', 'error'));
            }
        });
    });
});
</script>