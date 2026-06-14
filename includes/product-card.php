<?php
// ============================================
// SAFETY CHECK & DEFAULT VALUES
// ============================================
if (!isset($product) || !is_array($product)) {
    $product = [];
}

$defaults = [
    'id'            => 0,
    'name'          => 'Produk Tidak Dikenal',
    'price'         => 0,
    'old_price'     => null,
    'image'         => '',
    'description'   => '',
    'category'      => 'Uncategorized',
    'category_name' => 'Uncategorized',
    'product_type'  => 'slime',
    'rating'        => 4.5,
    'review_count'  => 0,
    'stock'         => 0,
    'is_bestseller' => false,
    'is_new'        => false,
    'is_limited'    => false
];
$product = array_merge($defaults, $product);

// Product type styling
$type_label = $product['product_type'] == 'slime' ? 'Vij Slimee' : 'Aprpiejise';
$type_icon   = $product['product_type'] == 'slime'
    ? '<i class="fas fa-hand-peace"></i>'
    : '<i class="fas fa-album-collection"></i>';
$type_gradient = $product['product_type'] == 'slime'
    ? 'linear-gradient(135deg, #FFB6C1, #FF69B4)'
    : 'linear-gradient(135deg, #40E0D0, #7FFFD4)';

// Wishlist & discount
$in_wishlist = function_exists('isInWishlist') ? isInWishlist($product['id']) : false;
$discount = 0;
if (!empty($product['old_price']) && $product['old_price'] > $product['price']) {
    $discount = round((($product['old_price'] - $product['price']) / $product['old_price']) * 100);
}

$stock = max(0, (int)($product['stock'] ?? 0));
$image_path = !empty($product['image'])
    ? htmlspecialchars($product['image'])
    : SITE_URL . '/assets/images/placeholder.jpg';
?>

<div class="product-card" data-product-id="<?= $product['id'] ?>">
    <!-- Image section -->
    <div class="product-card__image">
        <a href="product-detail.php?id=<?= $product['id'] ?>">
            <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
        </a>

        <!-- Badges -->
        <div class="product-card__badges">
            <span class="badge badge--type" style="background: <?= $type_gradient ?>">
                <?= $type_icon ?> <?= $type_label ?>
            </span>
            <?php if ($product['is_bestseller']): ?>
                <span class="badge badge--bestseller"><i class="fas fa-star"></i> Terlaris</span>
            <?php endif; ?>
            <?php if ($product['is_new']): ?>
                <span class="badge badge--new"><i class="fas fa-sparkles"></i> Baru</span>
            <?php endif; ?>
            <?php if ($discount > 0): ?>
                <span class="badge badge--discount">-<?= $discount ?>%</span>
            <?php endif; ?>
            <?php if ($stock > 0 && $stock <= 5): ?>
                <span class="badge badge--lowstock"><i class="fas fa-fire"></i> Hampir Habis</span>
            <?php endif; ?>
        </div>

        <!-- Action buttons -->
        <div class="product-card__actions">
            <button class="action-btn wishlist-btn <?= $in_wishlist ? 'active' : '' ?>"
                    data-id="<?= $product['id'] ?>"
                    onclick="toggleWishlist(event, <?= $product['id'] ?>)"
                    title="<?= $in_wishlist ? 'Hapus dari Wishlist' : 'Tambah ke Wishlist' ?>">
                <i class="fas fa-heart"></i>
            </button>
            <button class="action-btn quick-view"
                    onclick="quickView(<?= $product['id'] ?>)"
                    title="Lihat Cepat">
                <i class="fas fa-eye"></i>
            </button>
            <button class="action-btn share-btn"
                    onclick="shareProduct(event, <?= $product['id'] ?>, '<?= addslashes($product['name']) ?>')"
                    title="Bagikan">
                <i class="fas fa-share-alt"></i>
            </button>
        </div>
    </div>

    <!-- Info section -->
    <div class="product-card__info">
        <div class="product-category">
            <i class="fas fa-tag"></i> 
            <?= htmlspecialchars($product['category_name'] ?? $product['category'] ?? 'Tidak Berkategori') ?>
        </div>
        <h3 class="product-name">
            <a href="product-detail.php?id=<?= $product['id'] ?>">
                <?= htmlspecialchars($product['name']) ?>
            </a>
        </h3>

        <!-- Price -->
        <div class="product-price">
            <?php if ($discount > 0): ?>
                <span class="old-price"><?= formatRupiah($product['old_price']) ?></span>
                <span class="current-price"><?= formatRupiah($product['price']) ?></span>
                <span class="discount-badge">Hemat <?= $discount ?>%</span>
            <?php else: ?>
                <span class="current-price"><?= formatRupiah($product['price']) ?></span>
            <?php endif; ?>
        </div>

        <!-- Stock status -->
        <div class="stock-status">
            <?php if ($stock <= 0): ?>
                <span class="stock-badge stock-out"><i class="fas fa-times-circle"></i> Stok Habis</span>
            <?php elseif ($stock < 5): ?>
                <span class="stock-badge stock-low"><i class="fas fa-exclamation-triangle"></i> Stok Terbatas (<?= $stock ?>)</span>
            <?php elseif ($stock < 10): ?>
                <span class="stock-badge stock-medium"><i class="fas fa-info-circle"></i> Tersisa <?= $stock ?> item</span>
            <?php else: ?>
                <span class="stock-badge stock-in"><i class="fas fa-check-circle"></i> Tersedia</span>
            <?php endif; ?>
        </div>

        <!-- Add to cart button -->
        <button class="btn-add-cart"
                onclick="addToCartWithRedirect(<?= $product['id'] ?>, '<?= addslashes($product['name']) ?>', <?= $product['price'] ?>)"
                <?= $stock <= 0 ? 'disabled' : '' ?>>
            <i class="fas fa-shopping-cart"></i> <?= $stock <= 0 ? 'Stok Habis' : 'Tambah ke Keranjang' ?>
        </button>
    </div>
</div>

<style>
/* PRODUCT CARD - MODERN & SIMPLE */
.product-card {
    background: #fff;
    border-radius: 24px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    height: 100%;
    display: flex;
    flex-direction: column;
    border: 1px solid #f0e6e9;
}

.product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 35px rgba(0,0,0,0.1);
}

/* Image section */
.product-card__image {
    position: relative;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: #faf9fc;
}

.product-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.product-card:hover .product-card__image img {
    transform: scale(1.05);
}

/* Badges */
.product-card__badges {
    position: absolute;
    top: 12px;
    left: 12px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    z-index: 2;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 40px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    backdrop-filter: blur(4px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.badge--type { color: white; }
.badge--bestseller { background: linear-gradient(135deg, #FFD700, #FFA500); color: #1e2a5e; }
.badge--new { background: linear-gradient(135deg, #40E0D0, #7FFFD4); color: #1e2a5e; }
.badge--discount { background: #10B981; color: white; }
.badge--lowstock { background: #F97316; color: white; }

/* Action buttons */
.product-card__actions {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    opacity: 0;
    transform: translateX(12px);
    transition: opacity 0.25s ease, transform 0.25s ease;
    z-index: 2;
}

.product-card:hover .product-card__actions {
    opacity: 1;
    transform: translateX(0);
}

.action-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FF69B4;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.action-btn:hover {
    background: #FF69B4;
    color: white;
    transform: scale(1.08);
}

.action-btn.active {
    background: #FF69B4;
    color: white;
}

/* Info section */
.product-card__info {
    padding: 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.product-category {
    font-size: 0.7rem;
    font-weight: 600;
    color: #FF69B4;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.product-name {
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 8px;
    line-height: 1.4;
    font-family: 'Poppins', sans-serif;
}

.product-name a {
    color: #1a2a6c;
    text-decoration: none;
    transition: color 0.2s;
}

.product-name a:hover {
    color: #FF69B4;
}

.product-price {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.old-price {
    font-size: 0.8rem;
    color: #adb5bd;
    text-decoration: line-through;
}

.current-price {
    font-size: 1.2rem;
    font-weight: 800;
    color: #FF69B4;
}

.discount-badge {
    background: #10B981;
    color: white;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 700;
}

.stock-status {
    margin-bottom: 14px;
}

.stock-badge {
    font-size: 0.7rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 30px;
}

.stock-out { background: #FEE2E2; color: #DC2626; }
.stock-low { background: #FEF3C7; color: #D97706; }
.stock-medium { background: #DBEAFE; color: #2563EB; }
.stock-in { background: #D1FAE5; color: #059669; }

.btn-add-cart {
    background: linear-gradient(135deg, #FFB6C1, #FF69B4);
    border: none;
    color: white;
    padding: 10px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.25s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}

.btn-add-cart:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(255,105,180,0.3);
}

.btn-add-cart:disabled {
    background: #adb5bd;
    cursor: not-allowed;
}

/* Responsive for all devices */
@media (max-width: 1024px) {
    .product-card__info {
        padding: 14px;
    }
    .product-name {
        font-size: 0.95rem;
    }
    .current-price {
        font-size: 1.1rem;
    }
    .badge {
        padding: 3px 10px;
        font-size: 0.65rem;
    }
    .action-btn {
        width: 32px;
        height: 32px;
    }
}

@media (max-width: 768px) {
    .product-card__info {
        padding: 12px;
    }
    .product-name {
        font-size: 0.9rem;
    }
    .current-price {
        font-size: 1rem;
    }
    .product-category {
        font-size: 0.65rem;
    }
    .btn-add-cart {
        font-size: 0.8rem;
        padding: 8px;
    }
    .badge {
        padding: 2px 8px;
        font-size: 0.6rem;
    }
    .action-btn {
        width: 30px;
        height: 30px;
    }
}

@media (max-width: 576px) {
    .product-card__actions {
        opacity: 1;
        transform: translateX(0);
    }
    .product-name {
        font-size: 0.85rem;
    }
    .current-price {
        font-size: 0.95rem;
    }
    .stock-badge {
        font-size: 0.65rem;
        padding: 2px 8px;
    }
    .badge {
        padding: 2px 6px;
        font-size: 0.55rem;
    }
    .action-btn {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
}
</style>

<script>
function shareProduct(e, productId, productName) {
    e.preventDefault();
    e.stopPropagation();
    const url = window.location.origin + '/product-detail.php?id=' + productId;
    if (navigator.share) {
        navigator.share({ title: productName, text: `Lihat ${productName}`, url: url }).catch(() => {});
    } else {
        navigator.clipboard.writeText(url).then(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Link Disalin!', text: 'Link produk telah disalin', timer: 1500, showConfirmButton: false });
            } else {
                alert('Link produk disalin');
            }
        });
    }
}

function addToCartWithRedirect(productId, productName, productPrice) {
    const btn = event.currentTarget;
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
    btn.disabled = true;

    fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `add_to_cart=1&product_id=${productId}&qty=1`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.cart-count, #cart-count');
            if (badge) badge.innerText = data.count;
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: `${productName} ditambahkan ke keranjang`, timer: 1500, showConfirmButton: false });
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Stok tidak mencukupi' });
            }
        }
    })
    .catch(() => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menambah ke keranjang' });
        }
    })
    .finally(() => {
        btn.innerHTML = original;
        btn.disabled = false;
    });
}

function toggleWishlist(e, id) {
    e.preventDefault();
    e.stopPropagation();
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
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: isActive ? 'Dihapus dari Wishlist' : 'Ditambahkan ke Wishlist', timer: 1500, showConfirmButton: false });
            }
        }
    });
}

function quickView(productId) {
    window.location.href = 'product-detail.php?id=' + productId;
}
</script>