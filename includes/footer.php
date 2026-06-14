<!-- Footer -->
<footer>
    <div class="container">
        <!-- Main Footer: 2 kolom sederhana -->
        <div class="row align-items-center">
            <!-- Kiri: Brand Info -->
            <div class="col-md-7 mb-4 mb-md-0">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fas fa-gem fa-lg" style="color: var(--pink-tua);"></i>
                    <h4 class="fw-bold mb-0" style="font-family: 'Poppins', sans-serif; font-size: 1.3rem;">
                        <span style="color: var(--pink-tua);">Vij Slimee</span>
                        <span style="color: #6c757d;">&</span>
                        <span style="color: var(--tosca);">Aprpiejise</span>
                    </h4>
                </div>
                <p class="mb-0" style="color: #4a5568; max-width: 500px; line-height: 1.5; font-size: 0.9rem;">
                    Toko slime & photocard K-Pop terpercaya. Kami menyediakan berbagai macam slime unik dan photocard K-Pop eksklusif untuk para penggemar. Belanja sekarang dan temukan koleksi favoritmu!
                </p>
            </div>

            <!-- Kanan: Quick Links (horizontal) -->
            <div class="col-md-5">
                <ul class="list-unstyled d-flex flex-wrap gap-3 justify-content-md-end mb-0">
                    <li><a href="<?php echo SITE_URL; ?>" class="text-decoration-none" style="color: #4a5568; font-size: 0.9rem;"><i class="fas fa-angle-right me-1" style="color: var(--kuning);"></i>Home</a></li>
                    <li><a href="products.php" class="text-decoration-none" style="color: #4a5568; font-size: 0.9rem;"><i class="fas fa-angle-right me-1" style="color: var(--kuning);"></i>Products</a></li>
                    <li><a href="about.php" class="text-decoration-none" style="color: #4a5568; font-size: 0.9rem;"><i class="fas fa-angle-right me-1" style="color: var(--kuning);"></i>About Us</a></li>
                </ul>
            </div>
        </div>

        <!-- Garis pemisah tipis dengan gradasi warna -->
        <hr class="my-4" style="border: 0; height: 1px; background: linear-gradient(90deg, var(--pink-coral), var(--tosca), var(--kuning));">

        <!-- Bottom Bar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <p class="mb-2 mb-md-0" style="color: #4a5568; font-size: 0.8rem;">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
            </p>
            <div class="d-flex flex-column align-items-center align-items-md-end">
                <img src="<?php echo SITE_URL; ?>/uploads/logo.png" alt="Logo" class="footer-logo mb-1">
                <p class="mb-0" style="color: #4a5568; font-size: 0.75rem;">
                    Made with <i class="fas fa-heart" style="color: var(--pink-tua); animation: heartbeat 1.2s infinite;"></i> for slime & kpop lovers
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
/* Variabel warna (sinkron dengan halaman utama) */
:root {
    --pink-coral: #FFB6C1;
    --pink-tua: #FF69B4;
    --pink-muda: #FFC0CB;
    --kuning: #FFD700;
    --kuning-coral: #FFA500;
    --tosca: #40E0D0;
    --tosca-muda: #7FFFD4;
    --cream: #FFF8DC;
    --biru-dongker: #000080;
    --cream-light: rgba(236, 174, 107, 0.81);
    --white: #FFFFFF;
}

footer {
    /* Gradien lembut dari krem ke pink muda, selaras dengan hero & produk */
    background: linear-gradient(135deg, var(--white) 30%, var(--tosca) 50%);
    padding: 40px 0 30px;
    position: relative;
    border-top: 1px solid rgba(255, 182, 193, 0.5);
    /* Optional: efek shadow di atas */
    box-shadow: 0 -5px 20px rgba(255, 105, 180, 0.1);
}

/* Logo footer */
.footer-logo {
    width: 55px;
    height: auto;
    filter: drop-shadow(0 2px 6px rgba(0,0,0,0.1));
    transition: transform 0.2s;
}
.footer-logo:hover {
    transform: scale(1.05);
}

/* Hover pada link */
footer a:hover {
    color: var(--pink-tua) !important;
    transition: color 0.2s;
}
footer a:hover i {
    transform: translateX(2px);
}

/* Animasi heartbeat */
@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    25% { transform: scale(1.15); }
    50% { transform: scale(1); }
    75% { transform: scale(1.05); }
}

/* Responsive */
@media (max-width: 768px) {
    footer {
        padding: 30px 0 20px;
        text-align: center;
    }
    .footer-logo {
        width: 50px;
    }
    ul.list-unstyled {
        justify-content: center !important;
        margin-top: 10px;
    }
}
</style>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/cart.js"></script>

<script>
// Initialize AOS
AOS.init({
    duration: 800,
    once: true,
    offset: 100,
    easing: 'ease-out-cubic'
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
</script>

</body>
</html>