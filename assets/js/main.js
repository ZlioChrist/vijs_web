// Initialize AOS Animation
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    }
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Add to Cart - Global Handler (Bahasa Indonesia)
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            fetch('cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `add_to_cart=1&product_id=${id}&qty=1`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    updateCartCount(data.count);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: `${name} Ditambah ke Keranjang`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: data.message || 'Stok tidak mencukupi',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            })
            .catch(() => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Terjadi Kesalahan',
                        text: 'Gagal menambahkan ke keranjang. Silakan coba lagi.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        });
    });
    
    // Wishlist Toggle (Bahasa Indonesia)
    window.toggleWishlist = function(e, id) {
        e.preventDefault();
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
                const icon = btn.querySelector('i');
                if (icon) icon.classList.toggle('text-danger');
                updateWishlistCount();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: isActive ? 'Dihapus dari Wishlist' : 'Ditambahkan ke Wishlist',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            }
        });
    };
    
    // Update cart count in header
    window.updateCartCount = function(count) {
        let badge = document.getElementById('cart-count');
        if (!badge) {
            const cartBtn = document.querySelector('a[href="cart.php"] .cart-icon');
            if (cartBtn) {
                badge = document.createElement('span');
                badge.id = 'cart-count';
                badge.className = 'cart-count';
                cartBtn.appendChild(badge);
            }
        }
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    };
    
    // Update wishlist count
    window.updateWishlistCount = function() {
        const count = document.querySelectorAll('.wishlist-btn.active').length;
        const badge = document.querySelector('.wishlist-count');
        if (badge) badge.textContent = count;
    };
    
    // Auto hide flash messages
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});