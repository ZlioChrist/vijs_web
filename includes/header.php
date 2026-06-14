<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions
if (!file_exists(__DIR__ . '/functions.php')) {
    die('Error: functions.php file not found!');
}
require_once __DIR__ . '/functions.php';

// Include config if not already included
if (!defined('SITE_NAME')) {
    if (!file_exists(__DIR__ . '/../config.php')) {
        die('Error: config.php file not found!');
    }
    require_once __DIR__ . '/../config.php';
}

$maintenance_mode = SITE_MAINTENANCE_MODE;
// Jika maintenance aktif, tampilkan halaman maintenance
if ($maintenance_mode == '1') {
    http_response_code(503);
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maintenance - VIJARIE</title>
        <style>
            *{margin:0;padding:0;box-sizing:border-box;}
            body{
                font-family:"Poppins","Quicksand",sans-serif;
                background:linear-gradient(135deg,#FFF5F7 0%,#F0FFFF 100%);
                min-height:100vh;
                display:flex;
                align-items:center;
                justify-content:center;
                text-align:center;
                padding:20px;
            }
            .maintenance-card{
                background:white;
                border-radius:40px;
                padding:50px 40px;
                max-width:550px;
                box-shadow:0 30px 60px rgba(255,182,193,0.25);
                border:1px solid rgba(255,182,193,0.3);
                animation:fadeInUp 0.6s ease;
            }
            @keyframes fadeInUp{
                from{opacity:0;transform:translateY(30px);}
                to{opacity:1;transform:translateY(0);}
            }
            .icon-wrapper{
                width:100px;
                height:100px;
                background:linear-gradient(135deg,#FFB6C1,#FF69B4);
                border-radius:50%;
                display:flex;
                align-items:center;
                justify-content:center;
                margin:0 auto 25px;
            }
            .icon-wrapper i{
                font-size:3rem;
                color:white;
            }
            h1{
                color:#000080;
                font-weight:800;
                margin-bottom:15px;
            }
            p{
                color:#6B7280;
                line-height:1.6;
                margin-bottom:10px;
            }
            .logo-text{
                margin-top:30px;
                font-weight:700;
                background:linear-gradient(135deg,#FF69B4,#40E0D0);
                -webkit-background-clip:text;
                background-clip:text;
                -webkit-text-fill-color:transparent;
            }
        </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    </head>
    <body>
        <div class="maintenance-card">
            <div class="icon-wrapper"><i class="fas fa-tools"></i></div>
            <h1>Sedang dalam perbaikan</h1>
            <p>Website kami sedang dalam proses maintenance untuk memberikan pengalaman terbaik.</p>
            <p>Harap kembali beberapa saat lagi.</p>
            <p class="logo-text">— VIJARIE —</p>
        </div>
    </body>
    </html>';
}

// Determine current page for active class
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require_once 'includes/header-meta.php'; ?>
</head>
<body class="font-quicksand">

<!-- Main Navbar (selaras dengan tema index.php) -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
        <!-- Brand Logo (VIJARIE) -->
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
            <div class="brand-logo-wrapper">
                <span class="brand-icon"></span>
                <div class="brand-text-wrapper">
                    <span class="brand-text">VIJARIE</span>
                </div>
            </div>
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto gap-2">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>">
                        <i class="fas fa-home me-1"></i> Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>" 
                       href="products.php">
                        <i class="fas fa-store me-1"></i> Produk
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" 
                       href="about.php">
                        <i class="fas fa-info-circle me-1"></i> Tentang
                    </a>
                </li>
            </ul>
            
            <!-- Right Actions -->
            <div class="navbar-actions">
                <!-- Wishlist -->
                <a href="wishlist.php" class="action-icon-wrapper" title="Wishlist">
                    <div class="action-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <?php if(function_exists('getWishlist') && count(getWishlist()) > 0): ?>
                    <span class="action-count wishlist-count"><?php echo count(getWishlist()); ?></span>
                    <?php endif; ?>
                </a>
                
                <!-- Cart -->
                <a href="cart.php" class="action-icon-wrapper" title="Shopping Cart">
                    <div class="action-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <?php if(function_exists('getCartCount') && getCartCount() > 0): ?>
                    <span class="action-count cart-count"><?php echo getCartCount(); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Spacer for fixed navbar -->
<div style="height: 100px;"></div>

<!-- Flash Message -->
<?php if($flash = getFlash()): ?>
<div class="position-fixed top-0 end-0 p-4" style="margin-top: 110px; z-index: 9999;">
    <div class="alert alert-<?php echo $flash['type'] == 'error' ? 'danger' : ($flash['type'] == 'warning' ? 'warning' : 'success'); ?> 
                    alert-dismissible fade show shadow-lg rounded-4 border-0 flash-message" 
         role="alert" 
         style="background: linear-gradient(135deg, var(--pink-coral), var(--tosca)); color: white;">
        <i class="fas fa-<?php echo $flash['type'] == 'error' ? 'exclamation-circle' : 'check-circle'; ?> me-2"></i>
        <?php echo $flash['message']; ?>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<style>
/* ========== SESUAIKAN DENGAN VARIABEL INDEX.PHP ========== */
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
    --shadow-sm: 0 2px 15px rgba(0,0,0,0.05);
    --shadow-md: 0 8px 25px rgba(0,0,0,0.1);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Navbar */
.navbar {
    background: white;
    box-shadow: var(--shadow-sm);
    padding: 18px 0;
    transition: var(--transition);
    top: 0;
}
.navbar.scrolled {
    padding: 12px 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    box-shadow: var(--shadow-md);
}

/* Brand Logo - VIJARIE (gradien gabungan pink + tosca + kuning) */
.brand-logo-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}
.brand-icon {
    font-size: 1.8rem;
    color: var(--pink-tua);
    animation: bounce 2.5s ease-in-out infinite;
    display: inline-flex;
    align-items: center;
}
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}
.brand-text-wrapper {
    display: flex;
    align-items: center;
}
.brand-text {
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: 1.6rem;
    background: linear-gradient(135deg, var(--pink-coral), var(--tosca-tua));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}

/* Navigation Links */
.nav-link {
    color: var(--biru-dongker) !important;
    font-weight: 600;
    padding: 8px 18px !important;
    border-radius: 40px;
    transition: var(--transition);
    font-family: 'Poppins', sans-serif;
    font-size: 0.95rem;
    position: relative;
}
.nav-link::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--pink-coral), var(--pink-tua));
    transition: width 0.3s ease;
    transform: translateX(-50%);
    border-radius: 2px;
}
.nav-link:hover::after,
.nav-link.active::after {
    width: 60%;
}
.nav-link:hover {
    background: rgba(255, 182, 193, 0.1);
    color: var(--pink-tua) !important;
}
.nav-link.active {
    background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
    color: white !important;
}
.nav-link.active::after {
    display: none;
}

/* Navbar Actions */
.navbar-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}
.action-icon-wrapper {
    position: relative;
    text-decoration: none;
}
.action-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #FFF5F7, #F0FFFF);
    border: 2px solid var(--pink-coral);
    transition: var(--transition);
    color: var(--pink-tua);
    font-size: 1.2rem;
}
.action-icon-wrapper:hover .action-icon {
    background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
    border-color: transparent;
    color: white;
    transform: scale(1.08);
    box-shadow: 0 8px 20px rgba(255, 105, 180, 0.3);
}
.action-count {
    position: absolute;
    top: -6px;
    right: -6px;
    background: linear-gradient(135deg, var(--kuning), var(--kuning-muda));
    color: var(--biru-dongker);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 20px;
    min-width: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Flash Message (sesuai gradien index) */
.flash-message {
    animation: slideInRight 0.5s ease;
}
@keyframes slideInRight {
    from { opacity: 0; transform: translateX(100%); }
    to { opacity: 1; transform: translateX(0); }
}

/* ========== RESPONSIVE ========== */
@media (max-width: 992px) {
    .navbar-collapse {
        background: white;
        padding: 20px;
        border-radius: 28px;
        margin-top: 15px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--pink-coral);
    }
    .navbar-actions {
        justify-content: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px dashed var(--pink-coral);
    }
    .brand-text {
        font-size: 1.4rem;
    }
    .brand-icon {
        font-size: 1.6rem;
    }
    .nav-link::after {
        display: none;
    }
}
@media (max-width: 576px) {
    .brand-logo-wrapper {
        gap: 8px;
    }
    .brand-text {
        font-size: 1.2rem;
    }
    .brand-icon {
        font-size: 1.4rem;
    }
    .action-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}
</style>

<script>
// Navbar scroll effect
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Auto hide flash messages
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Close mobile menu on link click
document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
    link.addEventListener('click', function() {
        const navbarCollapse = document.querySelector('.navbar-collapse');
        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
            const bsCollapse = new bootstrap.Collapse(navbarCollapse, {toggle: false});
            bsCollapse.hide();
        }
    });
});

// Add loaded class for animations
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('loaded');
});
</script>