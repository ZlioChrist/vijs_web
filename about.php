<?php
require_once 'config.php';
require_once 'includes/functions.php';

$page_title = "Tentang Kami - " . SITE_NAME;

if (!defined('INSTAGRAM_SLIME')) {
    define('INSTAGRAM_SLIME', 'https://instagram.com/vijslimee.smr');
}
if (!defined('INSTAGRAM_KPOP')) {
    define('INSTAGRAM_KPOP', 'https://instagram.com/aprpiejise');
}
?>
<?php require_once 'includes/header.php'; ?>

<style>
/* ========== VARIABLES & GLOBAL ========== */
:root {
    --pink-coral: #FFB6C1;
    --pink-tua: #FF69B4;
    --pink-muda: #FFC0CB;
    --tosca: #40E0D0;
    --tosca-muda: #7FFFD4;
    --kuning: #FFD700;
    --kuning-muda: #FFE4B5;
    --biru-dongker: #000080;
    --abu-abu: #6c757d;
    --cream: #FFF8DC;
    --shadow-sm: 0 10px 30px rgba(0,0,0,0.05);
    --shadow-md: 0 20px 40px rgba(0,0,0,0.1);
    --shadow-lg: 0 25px 50px rgba(0,0,0,0.15);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* About Header */
.about-header {
    background: linear-gradient(135deg, var(--pink-coral) 0%, var(--tosca) 100%);
    padding: 100px 0 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.about-header::before {
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
.about-header-content { position: relative; z-index: 2; }
.about-header h1 {
    font-size: clamp(2.8rem, 6vw, 4rem);
    font-weight: 800;
    color: white;
    margin-bottom: 15px;
    text-shadow: 2px 2px 8px rgba(0,0,0,0.15);
    font-family: 'Poppins', sans-serif;
}
.about-header p {
    font-size: 1.3rem;
    color: rgba(255,255,255,0.95);
    font-weight: 500;
    max-width: 650px;
    margin: 0 auto;
}

/* Story Section */
.about-section {
    padding: 80px 0;
    background: linear-gradient(135deg, #FFF5F7 0%, #F0FFFF 50%, #FFFBE6 100%);
}
.story-card {
    background: white;
    border-radius: 40px;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    transition: var(--transition);
    border: 1px solid rgba(255,182,193,0.3);
}
.story-card:hover { transform: translateY(-5px); box-shadow: 0 35px 60px rgba(0,0,0,0.12); }
.story-content { padding: 50px; }
.story-title {
    color: var(--biru-dongker);
    font-weight: 800;
    font-family: 'Poppins', sans-serif;
    font-size: 2.2rem;
    margin-bottom: 25px;
    position: relative;
    display: inline-block;
}
.story-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 0;
    width: 70px;
    height: 4px;
    background: linear-gradient(90deg, var(--pink-tua), var(--kuning));
    border-radius: 4px;
}
.story-text {
    color: var(--abu-abu);
    font-size: 1.05rem;
    line-height: 1.8;
    margin-bottom: 20px;
}
.story-image {
    overflow: hidden;
    height: 100%;
    min-height: 380px;
    background: linear-gradient(145deg, #f8f9fa, #e9ecef);
    display: flex;
    align-items: center;
    justify-content: center;
}
.story-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.story-image:hover img { transform: scale(1.03); }

/* Service Boxes inside story */
.service-box {
    background: white;
    border-radius: 24px;
    padding: 20px 12px;
    text-align: center;
    border: 1.5px solid var(--pink-coral);
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}
.service-box:hover {
    transform: translateY(-6px);
    border-color: var(--pink-tua);
    box-shadow: var(--shadow-md);
}
.service-box .service-icon {
    font-size: 2.2rem;
    color: var(--pink-tua);
    margin-bottom: 12px;
    display: inline-block;
}
.service-box .service-title {
    font-weight: 700;
    color: var(--biru-dongker);
    margin-bottom: 6px;
    font-size: 1rem;
}
.service-box .service-desc {
    font-size: 0.8rem;
    color: var(--abu-abu);
}

/* Services Section (Keunggulan) */
.services-section {
    padding: 80px 0;
    background: linear-gradient(135deg, var(--pink-coral), var(--tosca));
    position: relative;
}
.services-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.15)"/></svg>');
    background-size: 40px 40px;
    pointer-events: none;
}
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    position: relative;
    z-index: 2;
}
.service-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(8px);
    border-radius: 32px;
    padding: 35px 20px;
    text-align: center;
    transition: var(--transition);
    border: 1px solid rgba(255,255,255,0.4);
    box-shadow: var(--shadow-sm);
}
.service-card:hover {
    transform: translateY(-10px);
    background: white;
    border-color: var(--kuning);
    box-shadow: var(--shadow-lg);
}
.service-card .service-icon {
    font-size: 3rem;
    color: var(--pink-tua);
    margin-bottom: 20px;
    display: inline-block;
}
.service-card .service-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--biru-dongker);
    margin-bottom: 12px;
    font-family: 'Poppins', sans-serif;
}
.service-card .service-desc {
    color: var(--abu-abu);
    line-height: 1.5;
    font-size: 0.95rem;
}

/* Brands Section */
.brands-section {
    padding: 80px 0;
    background: white;
}
.section-title {
    text-align: center;
    color: var(--biru-dongker);
    font-weight: 800;
    font-family: 'Poppins', sans-serif;
    font-size: 2.5rem;
    margin-bottom: 12px;
}
.section-subtitle {
    text-align: center;
    color: var(--abu-abu);
    font-size: 1.15rem;
    margin-bottom: 60px;
    font-weight: 500;
}
.brand-card {
    background: white;
    border-radius: 32px;
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
    height: 100%;
    border: 1px solid transparent;
}
.brand-card:hover {
    transform: translateY(-12px);
    box-shadow: var(--shadow-lg);
    border-color: var(--pink-coral);
}
.brand-card.slime .brand-header { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); }
.brand-card.kpop .brand-header { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); }
.brand-header {
    padding: 40px 20px;
    text-align: center;
    color: white;
}
.brand-icon {
    font-size: 3.5rem;
    margin-bottom: 15px;
    display: inline-block;
    filter: drop-shadow(2px 4px 6px rgba(0,0,0,0.1));
}
.brand-header h3 {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 8px;
    font-family: 'Poppins', sans-serif;
}
.brand-header p { margin: 0; opacity: 0.95; font-size: 1rem; }
.brand-body { padding: 30px; }
.brand-body p {
    color: var(--abu-abu);
    line-height: 1.6;
    margin-bottom: 20px;
}
.brand-features {
    list-style: none;
    padding: 0;
    margin: 0 0 25px;
}
.brand-features li {
    padding: 8px 0;
    color: var(--abu-abu);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.95rem;
}
.brand-features li i {
    width: 22px;
    color: var(--pink-tua);
}
.brand-card.kpop .brand-features li i { color: var(--tosca); }
.brand-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
    color: white;
    padding: 12px 24px;
    border-radius: 40px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    width: 100%;
}
.brand-card.kpop .brand-btn {
    background: linear-gradient(135deg, var(--tosca), var(--tosca-muda));
    color: var(--biru-dongker);
}
.brand-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    color: white;
}

/* Values Section */
.values-section {
    padding: 80px 0;
    background: linear-gradient(135deg, #FFF5F7, #F0FFFF);
}
.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}
.value-card {
    background: white;
    border-radius: 32px;
    padding: 40px 25px;
    text-align: center;
    transition: var(--transition);
    border: 1px solid rgba(255,182,193,0.4);
    box-shadow: var(--shadow-sm);
}
.value-card:hover {
    transform: translateY(-8px);
    border-color: var(--pink-tua);
    box-shadow: var(--shadow-lg);
}
.value-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    font-size: 2.2rem;
    color: white;
}
.value-card:nth-child(1) .value-icon { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); }
.value-card:nth-child(2) .value-icon { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); }
.value-card:nth-child(3) .value-icon { background: linear-gradient(135deg, var(--kuning), var(--kuning-muda)); color: var(--biru-dongker); }
.value-card h4 {
    color: var(--biru-dongker);
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
    font-size: 1.3rem;
    margin-bottom: 12px;
}
.value-card p { color: var(--abu-abu); line-height: 1.6; font-size: 0.95rem; margin: 0; }

/* CTA Section */
.cta-section {
    padding: 80px 0;
    background: linear-gradient(135deg, var(--pink-tua), var(--kuning));
    text-align: center;
}
.cta-section h2 {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    color: white;
    margin-bottom: 15px;
    font-family: 'Poppins', sans-serif;
}
.cta-section p {
    font-size: 1.2rem;
    color: rgba(255,255,255,0.95);
    margin-bottom: 30px;
}
.btn-cta {
    background: white;
    color: var(--pink-tua);
    padding: 16px 45px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1.1rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    transition: var(--transition);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}
.btn-cta:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    color: var(--pink-tua);
}

/* Responsive */
@media (max-width: 992px) {
    .story-content { padding: 40px 30px; }
    .story-title { font-size: 1.8rem; }
    .story-image { min-height: 300px; }
}
@media (max-width: 768px) {
    .about-header { padding: 70px 0 50px; }
    .about-section, .services-section, .brands-section, .values-section, .cta-section { padding: 60px 0; }
    .section-title { font-size: 2rem; }
    .brand-header h3 { font-size: 1.5rem; }
    .service-card .service-title { font-size: 1.2rem; }
}
@media (max-width: 576px) {
    .story-content { padding: 30px 20px; }
    .story-image { min-height: 220px; }
    .services-grid { gap: 20px; }
    .service-card { padding: 25px 15px; }
    .brand-header { padding: 30px 15px; }
    .brand-body { padding: 25px; }
    .value-card { padding: 30px 20px; }
}
</style>

<!-- Header -->
<section class="about-header">
    <div class="container about-header-content">
        <h1>Tentang Kami</h1>
        <p>Toko satu atap untuk slime handmade dan photocard Kpop</p>
    </div>
</section>

<!-- Cerita Kami -->
<section class="about-section">
    <div class="container">
        <div class="story-card">
            <div class="row g-0 align-items-center">
                <div class="col-lg-6 order-lg-1 order-2">
                    <div class="story-content">
                        <h2 class="story-title">Cerita Kami</h2>
                        <p class="story-text">
                            Kami memulai dengan semangat menciptakan produk berkualitas yang membawa kebahagiaan bagi pelanggan kami. 
                            <strong style="color: var(--pink-tua);">Vij Slimee</strong> fokus pada slime handmade dengan cinta dan bahan aman, 
                            sementara <strong style="color: var(--tosca);">Aprpiejise</strong> menyediakan photocard Kpop terjangkau dan autentik.
                        </p>
                        <p class="story-text">
                            Setiap slime dibuat dengan bahan premium non-beracun. Kami percaya bermain slime tidak hanya menyenangkan, tapi juga terapi anti-stres.
                        </p>
                        <p class="story-text">
                            Untuk penggemar Kpop, kami menyediakan photocard berkualitas dari pemasok resmi, memastikan koleksi terbaik untuk idola favorit Anda.
                        </p>
                        <!-- Layanan cepat -->
                        <div class="row g-3 mt-4">
                            <div class="col-6">
                                <div class="service-box">
                                    <div class="service-icon"><i class="fas fa-truck"></i></div>
                                    <div class="service-title">Pengiriman Cepat</div>
                                    <div class="service-desc">Seluruh Indonesia 3-5 hari</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="service-box">
                                    <div class="service-icon"><i class="fas fa-lock"></i></div>
                                    <div class="service-title">Pembayaran Aman</div>
                                    <div class="service-desc">100% perlindungan data</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 order-lg-2 order-1">
                    <div class="story-image">
                        <img src="<?= SITE_URL ?>/uploads/owner.jpeg" alt="Cerita Kami" onerror="this.src='<?= SITE_URL ?>/assets/images/placeholder.jpg'">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Layanan & Keunggulan -->
<section class="services-section">
    <div class="container">
        <h2 class="section-title" style="color: white; text-shadow: 1px 1px 0 rgba(0,0,0,0.1);">Layanan & Keunggulan</h2>
        <p class="section-subtitle" style="color: rgba(255,255,255,0.9);">Mengapa memilih kami untuk kebutuhan slime dan Kpop Anda</p>
        <div class="services-grid">
            <div class="service-card" data-aos="fade-up">
                <div class="service-icon"><i class="fas fa-hand-sparkles"></i></div>
                <div class="service-title">Kualitas Handmade</div>
                <div class="service-desc">Slime dibuat dari bahan premium dan tekstur sempurna.</div>
            </div>
            <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                <div class="service-icon"><i class="fas fa-images"></i></div>
                <div class="service-title">Photocard Autentik</div>
                <div class="service-desc">Original & fanmade berkualitas dari berbagai grup Kpop.</div>
            </div>
            <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                <div class="service-icon"><i class="fas fa-comments"></i></div>
                <div class="service-title">Dukungan 24/7</div>
                <div class="service-desc">Respon cepat via Instagram</div>
            </div>
            <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                <div class="service-icon"><i class="fas fa-gift"></i></div>
                <div class="service-title">Gratis & Diskon</div>
                <div class="service-desc">Freebies eksklusif dan diskon spesial di setiap pembelian.</div>
            </div>
        </div>
    </div>
</section>

<!-- Brand Kami -->
<section class="brands-section">
    <div class="container">
        <h2 class="section-title">Brand Kami</h2>
        <p class="section-subtitle">Dua toko luar biasa, satu platform nyaman</p>
        <div class="row g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="brand-card slime">
                    <div class="brand-header">
                        <div class="brand-icon"><i class="fas fa-hand-sparkles"></i></div>
                        <h3>Vij Slimee</h3>
                        <p>Slime Buatan Tangan dengan Cinta</p>
                    </div>
                    <div class="brand-body">
                        <p>Mengkhususkan slime handmade berkualitas tinggi, aman, tidak beracun. Setiap batch dibuat cermat untuk tekstur sempurna.</p>
                        <ul class="brand-features">
                            <li><i class="fas fa-check-circle"></i> Bahan Aman & Non-Beracun</li>
                            <li><i class="fas fa-check-circle"></i> Fluffy, Butter & Clear</li>
                            <li><i class="fas fa-check-circle"></i> Pemesanan Kustom</li>
                            <li><i class="fas fa-check-circle"></i> Pengiriman Cepat</li>
                        </ul>
                        <a href="<?php echo INSTAGRAM_SLIME; ?>" target="_blank" class="brand-btn">
                            <i class="fab fa-instagram"></i> @vijslimee.smr
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="brand-card kpop">
                    <div class="brand-header">
                        <div class="brand-icon"><i class="fas fa-images"></i></div>
                        <h3>Aprpiejise</h3>
                        <p>Destinasi Photocard Kpop Anda</p>
                    </div>
                    <div class="brand-body">
                        <p>Sumber terpercaya photocard Kpop original & fanmade dari berbagai grup favorit dengan harga terjangkau.</p>
                        <ul class="brand-features">
                            <li><i class="fas fa-check-circle"></i> Original & Fanmade</li>
                            <li><i class="fas fa-check-circle"></i> Berbagai Grup Kpop</li>
                            <li><i class="fas fa-check-circle"></i> Harga Terjangkau</li>
                            <li><i class="fas fa-check-circle"></i> Pengemasan Aman</li>
                        </ul>
                        <a href="<?php echo INSTAGRAM_KPOP; ?>" target="_blank" class="brand-btn">
                            <i class="fab fa-instagram"></i> @aprpiejise
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Nilai Kami -->
<section class="values-section">
    <div class="container">
        <h2 class="section-title">Nilai Kami</h2>
        <p class="section-subtitle">Apa yang membuat kami berbeda</p>
        <div class="values-grid">
            <div class="value-card" data-aos="fade-up">
                <div class="value-icon"><i class="fas fa-gem"></i></div>
                <h4>Kualitas Utama</h4>
                <p>Tidak pernah berkompromi dengan kualitas. Setiap produk diperiksa cermat memenuhi standar tinggi.</p>
            </div>
            <div class="value-card" data-aos="fade-up" data-aos-delay="100">
                <div class="value-icon"><i class="fas fa-heart"></i></div>
                <h4>Dibuat dengan Cinta</h4>
                <p>Setiap slime handmade dengan detail penuh cinta, untuk kebahagiaan pelanggan.</p>
            </div>
            <div class="value-card" data-aos="fade-up" data-aos-delay="200">
                <div class="value-icon"><i class="fas fa-users"></i></div>
                <h4>Pelanggan Pertama</h4>
                <p>Kepuasan Anda prioritas utama. Kami siap membantu kapan pun.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container" data-aos="zoom-in">
        <h2>Siap Mulai Belanja?</h2>
        <p class="mb-3">Bergabung dengan ratusan pelanggan puas dan temukan produk sempurna Anda hari ini!</p>
        <a href="products.php" class="btn-cta">
            <i class="fas fa-shopping-bag"></i> Lihat Semua Produk
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ 
        duration: 800, 
        once: true, 
        offset: 80, 
        easing: 'ease-out-cubic' 
    });
</script>