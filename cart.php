<?php
require_once 'config.php';
require_once 'includes/functions.php';

// ============================================
// AJAX HANDLER: ADD TO CART
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    header('Content-Type: application/json');
    
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    
    if ($product_id > 0) {
        if (addToCart($product_id, $qty)) {
            echo json_encode([
                'success' => true,
                'count' => getCartCount(),
                'message' => 'Produk berhasil ditambahkan ke keranjang!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Produk tidak ditemukan atau stok habis'
            ], 400);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ID produk tidak valid'
        ], 400);
    }
    exit;
}

// ============================================
// HALAMAN KERANJANG
// ============================================
$page_title = "Keranjang Belanja - " . SITE_NAME;

// Handle Actions Keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update quantities
    if (isset($_POST['update_cart']) && isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $id => $quantity) {
            updateCartQty($id, (int)$quantity);
        }
        setFlash('Keranjang berhasil diperbarui!', 'success');
        redirect('cart.php');
    }
    
    // Remove single item
    if (isset($_POST['remove_item']) && isset($_POST['product_id'])) {
        removeFromCart((int)$_POST['product_id']);
        setFlash('Item dihapus dari keranjang', 'success');
        redirect('cart.php');
    }
    
    // Clear all cart
    if (isset($_POST['clear_cart'])) {
        clearCart();
        setFlash('Keranjang dikosongkan', 'success');
        redirect('cart.php');
    }
}

$cart = getCart();
$cart_total = getCartTotal();
// HAPUS PERHITUNGAN ONGKIR
// $shipping = calculateShipping();
$grand_total = $cart_total; // total tanpa ongkir

$slime_count = 0;
$photocard_count = 0;
foreach ($cart as $item) {
    if ($item['type'] == 'slime') {
        $slime_count += $item['qty'];
    } else {
        $photocard_count += $item['qty'];
    }
}
$total_items = getCartCount();
?>
<?php require_once 'includes/header.php'; ?>

<style>
/* ============ VARIABLES (tema kuning) ============ */
:root {
    --cart-yellow: #FFD700;
    --cart-yellow-dark: #FFA500;
    --cart-yellow-light: #FFF9E6;
    --cart-yellow-coral: #f4ce6c;
}

/* Header - Kuning (mirip wishlist) */

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}


/* Stat Cards - Kuning (mirip wishlist) */
.cart-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}
.stat-card {
    background: white;
    border-radius: 25px;
    padding: 25px 20px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    border-left: 4px solid var(--cart-yellow);
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(255, 215, 0, 0.2);
}
.stat-icon { font-size: 2rem; margin-bottom: 10px; }
.stat-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--biru-dongker);
}
.stat-label {
    color: var(--abu-abu);
    font-size: 0.9rem;
    font-weight: 600;
}

/* Cart Items */
.cart-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: white;
    border-radius: 20px;
    margin-bottom: 15px;
    border: 1px solid #FFE4B5;
    transition: all 0.3s;
}
.cart-item:hover {
    transform: translateX(5px);
    border-color: var(--cart-yellow);
    box-shadow: 0 5px 20px rgba(255, 215, 0, 0.15);
}
.item-image {
    width: 90px;
    height: 90px;
    object-fit: cover;
    border-radius: 15px;
    border: 2px solid var(--cart-yellow);
}
.item-details { flex: 1; }
.item-name {
    font-weight: 700;
    color: var(--biru-dongker);
    margin-bottom: 5px;
    font-family: 'Poppins', sans-serif;
}
.item-type {
    font-size: 0.8rem;
    margin-bottom: 8px;
}
.item-type.slime { color: var(--pink-tua); }
.item-type.photocard { color: var(--tosca); }
.item-price {
    font-weight: 700;
    color: var(--cart-yellow-dark);
}
.quantity-control {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--cart-yellow-light);
    padding: 5px 10px;
    border-radius: 30px;
}
.qty-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid var(--cart-yellow);
    background: white;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}
.qty-btn:hover {
    background: var(--cart-yellow);
    color: var(--biru-dongker);
    transform: scale(1.05);
}
.qty-input {
    width: 50px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 20px;
    padding: 6px;
}
.item-subtotal {
    font-weight: 800;
    min-width: 100px;
    text-align: right;
    color: var(--cart-yellow-dark);
}
.remove-btn {
    background: none;
    border: none;
    color: #ff6b6b;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.2s;
}
.remove-btn:hover {
    background: #ffe0e0;
    transform: scale(1.1);
}

.cart-header {
    background: linear-gradient(135deg, var(--cart-yellow-dark) 0%, var(--cart-yellow-coral) 100%);
    padding: 80px 0 60px;
    position: relative;
    overflow: hidden;
}
.cart-header::before {
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

/* Cards */
.card-cart {
    background: white;
    border-radius: 25px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    border: 1px solid var(--cart-yellow-light);
}
.card-cart.yellow-summary {
    background: linear-gradient(145deg, #FFF9E6, #FFFEF5);
    border-left: 4px solid var(--cart-yellow);
}
.card-header-cart {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 1.3rem;
    color: var(--biru-dongker);
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px dashed var(--cart-yellow);
}

/* Buttons */
.btn-yellow {
    background: linear-gradient(135deg, var(--cart-yellow), var(--cart-yellow-dark));
    color: var(--biru-dongker);
    border: none;
    font-weight: 700;
    transition: all 0.3s;
}
.btn-yellow:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 165, 0, 0.4);
    color: var(--biru-dongker);
}
.btn-outline-yellow {
    background: white;
    border: 2px solid var(--cart-yellow);
    color: var(--cart-yellow-dark);
}
.btn-outline-yellow:hover {
    background: var(--cart-yellow-light);
    border-color: var(--cart-yellow-dark);
}
.cart-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
    flex-wrap: wrap;
}

/* Empty Cart (mirip wishlist) */
.empty-cart {
    text-align: center;
    padding: 60px 30px;
    background: white;
    border-radius: 30px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.08);
    border: 2px dashed var(--cart-yellow);
    margin: 40px 0;
}
.empty-cart i {
    color: var(--cart-yellow);
    opacity: 0.7;
}
.empty-cart h3 {
    color: var(--biru-dongker);
}
.empty-cart .btn {
    background: linear-gradient(135deg, var(--cart-yellow), var(--cart-yellow-dark));
    border: none;
}

/* Summary Row */
.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px dashed var(--cart-yellow);
}
.summary-row.total {
    font-size: 1.3rem;
    font-weight: 800;
    border-top: 2px solid var(--cart-yellow);
    border-bottom: none;
    padding-top: 15px;
    margin-top: 10px;
    color: var(--cart-yellow-dark);
}
.security-note {
    text-align: center;
    margin-top: 20px;
    font-size: 0.85rem;
    color: var(--abu-abu);
}

/* Responsive */
@media (max-width: 768px) {
    .cart-item {
        flex-wrap: wrap;
        text-align: center;
        justify-content: center;
    }
    .item-subtotal {
        text-align: center;
        width: 100%;
    }
    .quantity-control {
        margin: 10px 0;
    }
}
</style>

<!-- Header Keranjang -->
<section class="cart-header">
    <div class="container text-center position-relative z-2">
        <i class="fas fa-shopping-cart fa-3x text-white mb-3" style="filter: drop-shadow(0 5px 15px rgba(0,0,0,0.2));"></i>
        <h1 class="display-4 fw-bold text-white mb-3">Keranjang Belanja</h1>
        <p class="lead text-white mb-0">
            <span class="cart-count">
                <i class="fas fa-star me-1"></i> <?php echo $total_items; ?> item di keranjang
            </span>
        </p>
    </div>
</section>

<!-- Main Content (background seperti wishlist) -->
<section class="py-5" style="background: linear-gradient(135deg, rgba(255,248,220,0.3) 0%, rgba(255,228,181,0.2) 100%);">
    <div class="container">
        <?php if (empty($cart)): ?>
            <!-- Keranjang Kosong -->
            <div class="empty-cart text-center" data-aos="fade-up">
                <i class="fas fa-shopping-basket fa-5x mb-3" style="color: var(--cart-yellow); opacity: 0.6;"></i>
                <h3 class="fw-bold mt-4 mb-3" style="color: var(--biru-dongker);">Keranjang masih kosong</h3>
                <p class="text-muted mb-4">Yuk mulai belanja dan tambahkan item favoritmu!</p>
                <a href="products.php" class="btn btn-primary rounded-pill px-5 py-3" style="background: linear-gradient(135deg, var(--cart-yellow), var(--cart-yellow-dark)); border: none; font-weight: 600;">
                    <i class="fas fa-shopping-bag me-2"></i> Lanjut Belanja
                </a>
            </div>
        <?php else: ?>
            <!-- Statistik Ringkas -->
            <div class="cart-stats">
                <div class="stat-card" data-aos="fade-up">
                    <div class="stat-icon"><i class="fas fa-hand-peace"></i></div>
                    <div class="stat-value"><?php echo $slime_count; ?></div>
                    <div class="stat-label">Item Slime</div>
                </div>
                <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-icon"><i class="fas fa-images"></i></div>
                    <div class="stat-value"><?php echo $photocard_count; ?></div>
                    <div class="stat-label">Item Photocard</div>
                </div>
                <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-value"><?php echo formatRupiah($cart_total); ?></div>
                    <div class="stat-label">Total Belanja</div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Daftar Item -->
                <div class="col-lg-8">
                    <div class="card-cart">
                        <div class="card-header-cart">
                            <i class="fas fa-list me-2" style="color: var(--cart-yellow);"></i> Item di Keranjang
                        </div>
                        <form method="POST" id="cartForm">
                            <?php foreach ($cart as $item): 
                                $item_type_label = ($item['type'] == 'slime') ? 'Vij Slimee' : 'Aprpiejise';
                                $item_type_class = ($item['type'] == 'slime') ? 'slime' : 'photocard';
                            ?>
                            <div class="cart-item">
                                <img src="<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                     class="item-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-type <?php echo $item_type_class; ?>"><?php echo $item_type_label; ?></div>
                                    <div class="item-price"><?php echo formatRupiah($item['price']); ?></div>
                                </div>
                                <div class="quantity-control">
                                    <button type="button" class="qty-btn" onclick="updateQty(<?php echo $item['id']; ?>, -1)">-</button>
                                    <input type="number" name="qty[<?php echo $item['id']; ?>]" value="<?php echo $item['qty']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="qty-input">
                                    <button type="button" class="qty-btn" onclick="updateQty(<?php echo $item['id']; ?>, 1)">+</button>
                                </div>
                                <div class="item-subtotal"><?php echo formatRupiah($item['price'] * $item['qty']); ?></div>
                                <button type="submit" name="remove_item" value="1" class="remove-btn" onclick="return confirm('Hapus item ini dari keranjang?')">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                            <div class="cart-actions">
                                <a href="products.php" class="btn btn-outline-secondary rounded-pill px-4">
                                    <i class="fas fa-arrow-left me-1"></i> Lanjut Belanja
                                </a>
                                <button type="submit" name="update_cart" class="btn btn-outline-yellow rounded-pill px-4">
                                    <i class="fas fa-sync-alt me-1"></i> Perbarui Keranjang
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ringkasan Pesanan -->
                <div class="col-lg-4">
                    <div class="card-cart yellow-summary">
                        <div class="card-header-cart">
                            <i class="fas fa-receipt me-2" style="color: var(--cart-yellow);"></i> Ringkasan Pesanan
                        </div>
                        <div class="summary-row">
                            <span>Subtotal (<?php echo $total_items; ?> item)</span>
                            <span><?php echo formatRupiah($cart_total); ?></span>
                        </div>
                        <!-- HAPUS BARIS ONGKOS KIRIM -->
                        <div class="summary-row total">
                            <span>Total</span>
                            <span><?php echo formatRupiah($cart_total); ?></span>
                        </div>
                        <a href="checkout.php" class="btn btn-yellow rounded-pill w-100 mt-4 py-3">
                            <i class="fas fa-credit-card me-2"></i> Checkout Sekarang
                        </a>
                        <form method="POST" onsubmit="return confirm('Yakin ingin mengosongkan seluruh keranjang?')" class="mt-2">
                            <button type="submit" name="clear_cart" class="btn btn-outline-danger rounded-pill w-100 py-2">
                                <i class="fas fa-trash-alt me-2"></i> Kosongkan Keranjang
                            </button>
                        </form>
                        <div class="security-note">
                            <i class="fas fa-shield-alt me-1" style="color: var(--cart-yellow);"></i> Checkout aman dengan data terenkripsi
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<script>
function updateQty(productId, change) {
    const input = document.querySelector(`input[name="qty[${productId}]"]`);
    if (input) {
        let newVal = parseInt(input.value) + change;
        const max = parseInt(input.max) || 999;
        if (newVal >= 1 && newVal <= max) {
            input.value = newVal;
            // Tambahkan hidden input 'update_cart'
            const form = document.getElementById('cartForm');
            let hidden = form.querySelector('input[name="update_cart"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'update_cart';
                hidden.value = '1';
                form.appendChild(hidden);
            } else {
                hidden.value = '1';
            }
            form.submit();
        } else if (newVal < 1) {
            // Notifikasi minimal jumlah
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Minimal Jumlah',
                    text: 'Jumlah minimal adalah 1',
                    confirmButtonColor: '#FFA500',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert('Minimal jumlah adalah 1');
            }
        } else {
            // Notifikasi stok maksimal (mirip "Keranjang diperbarui" tetapi info)
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'Stok Terbatas',
                    text: `Stok maksimal ${max} item`,
                    confirmButtonColor: '#FFA500',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert(`Stok maksimal ${max} item`);
            }
        }
    }
}

document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('change', function() {
        document.getElementById('cartForm').submit();
    });
});

if (typeof AOS !== 'undefined') {
    AOS.init({ duration: 800, once: true });
}
</script>