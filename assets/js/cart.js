
// Update keranjang via AJAX
function updateCartItem(productId, qty) {
    return fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `update_cart=1&qty[${productId}]=${qty}`
    })
    .then(r => r.text())
    .then(() => location.reload());
}

// Hapus item dari keranjang
function removeFromCart(productId) {
    const confirmAction = () => {
        fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `remove_item=1&product_id=${productId}`
        })
        .then(() => location.reload());
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus item?',
            text: 'Item ini akan dihapus dari keranjang Anda',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) confirmAction();
        });
    } else {
        if (confirm('Hapus item ini?')) confirmAction();
    }
}

// Kosongkan seluruh keranjang
function clearCart() {
    const confirmAction = () => {
        fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'clear_cart=1'
        })
        .then(() => location.reload());
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Kosongkan keranjang?',
            text: 'Semua item akan dihapus!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, kosongkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) confirmAction();
        });
    } else {
        if (confirm('Kosongkan keranjang?')) confirmAction();
    }
}

// Perbarui jumlah item di badge keranjang
function updateCartCount(count) {
    let badge = document.getElementById('cart-count');
    if (!badge) {
        const cartLink = document.querySelector('a[href="cart.php"]');
        if (cartLink) {
            badge = document.createElement('span');
            badge.id = 'cart-count';
            badge.className = 'cart-count';
            cartLink.appendChild(badge);
        }
    }
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Ubah jumlah pada halaman detail produk
function changeQty(delta) {
    const input = document.getElementById('qty');
    if (!input) return;
    let newVal = parseInt(input.value) + delta;
    const max = parseInt(input.max) || 99;
    if (newVal >= 1 && newVal <= max) {
        input.value = newVal;
    }
}

// Proses checkout (redirect jika keranjang tidak kosong)
function proceedToCheckout() {
    const cartCount = (typeof window.VijSlimee !== 'undefined' && window.VijSlimee.cartCount) ? window.VijSlimee.cartCount : 0;
    if (cartCount <= 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Keranjang Kosong',
                text: 'Tambahkan produk terlebih dahulu sebelum checkout'
            });
        } else {
            alert('Keranjang kosong! Tambahkan produk dulu.');
        }
        return false;
    }
    window.location.href = 'checkout.php';
}

// Tambah ke keranjang dengan redirect ke halaman keranjang (opsional)
function addToCartWithRedirect(productId, productName, productPrice) {
    fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `add_to_cart=1&product_id=${productId}&qty=1`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.count);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: `${productName} ditambahkan ke keranjang`,
                    timer: 1500,
                    showConfirmButton: false,
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = 'cart.php';
                });
            } else {
                alert(`${productName} ditambahkan ke keranjang!`);
                window.location.href = 'cart.php';
            }
        } else {
            const errorMsg = data.message || 'Gagal menambahkan ke keranjang';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: errorMsg
                });
            } else {
                alert(errorMsg);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Gagal menambahkan ke keranjang. Silakan coba lagi.'
            });
        } else {
            alert('Gagal menambahkan ke keranjang. Silakan coba lagi.');
        }
    });
}

// Inisialisasi saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.VijSlimee !== 'undefined' && window.VijSlimee.cartCount !== undefined) {
        updateCartCount(window.VijSlimee.cartCount);
    }
});