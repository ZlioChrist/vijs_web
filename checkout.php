<?php
// DEBUG MODE - ERROR LANGSUNG MUNCUL, JANGAN DISEMBUNYIKAN!
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// 1. Load config & functions DULU (session_start() ada di config.php)
require_once 'config.php';
require_once 'includes/functions.php';

// ============================================
// AJAX HANDLER: Get shipping cost by city
// ============================================
if (isset($_GET['get_shipping']) && isset($_GET['city'])) {
    header('Content-Type: application/json');
    $city = $_GET['city'];
    $shipping = calculateShipping($city);
    echo json_encode(['shipping' => $shipping]);
    exit;
}

// 2. Double-check session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Tampilkan error dari checkout-process.php (SEBELUM output HTML apapun)
if (isset($_SESSION['form_errors']) && !empty($_SESSION['form_errors'])) {
    echo '<div style="background:#fff3cd; border-left:5px solid #ffc107; padding:15px; margin:20px auto; max-width:1200px; font-family:monospace;">';
    echo '<strong>ERROR dari checkout-process.php:</strong><br><br>';
    foreach ($_SESSION['form_errors'] as $err) {
        echo '• ' . htmlspecialchars($err) . '<br>';
    }
    echo '</div>';
    unset($_SESSION['form_errors']);
}

// 4. Restore form data agar user tidak isi ulang
if (isset($_SESSION['form_data'])) {
    $_POST = $_SESSION['form_data'];
}

// Redirect jika keranjang kosong
$cart = getCart();
if (empty($cart)) {
    setFlash('Keranjang kosong! Tambahkan produk dulu, yuk!', 'warning');
    redirect('cart.php');
}

// Hitung total
$subtotal = getCartTotal();
$shipping = calculateShipping($_POST['city'] ?? 'Samarinda');
$total = $subtotal + $shipping;

$page_title = "Checkout - " . SITE_NAME;
$flash = getFlash();
?>
<?php require_once 'includes/header.php'; ?>

<style>
:root {
    --pink-soft: #FFB6C1;
    --pink-medium: #FF9AA2;
    --pink-dark: #FF6B9D;
    --pink-light: #FFF0F3;
    --tosca: #40E0D0;
    --tosca-light: #7FFFD4;
    --tosca-dark: #20B2AA;
    --tosca-glow: rgba(64, 224, 208, 0.3);
    --yellow-coral: #FFD700;
    --yellow-coral-dark: #FFA500;
    --yellow-coral-light: #FFF9E6;
    --yellow-coral-glow: rgba(255, 215, 0, 0.3);
    --white: #ffffff;
    --gray-soft: #f8f9fa;
    --gray-text: #666666;
    --gray-dark: #333333;
    --navy: #000080;
    --gradient-main: linear-gradient(135deg, #FFF0F3 0%, #E0F7FA 50%, #FFF0F3 100%);
    --gradient-card: linear-gradient(145deg, #FFF9E6, #FFFEF5);
    --gradient-pink: linear-gradient(135deg, var(--pink-soft), var(--pink-medium));
    --gradient-coral: linear-gradient(135deg, var(--yellow-coral), var(--yellow-coral-dark));
    --gradient-tosca: linear-gradient(135deg, var(--tosca), var(--tosca-light));
    --gradient-mixed: linear-gradient(135deg, var(--pink-soft), var(--tosca), var(--yellow-coral));
    --shadow-soft: 0 10px 40px rgba(255, 215, 0, 0.2);
    --shadow-card: 0 20px 60px rgba(255, 215, 0, 0.25);
    --shadow-glow: 0 0 30px var(--tosca-glow);
    --transition-smooth: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-bounce: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Quicksand', sans-serif;
    background: var(--gradient-main);
    background-attachment: fixed;
    min-height: 100vh;
    color: var(--gray-text);
    line-height: 1.6;
    overflow-x: hidden;
}

.stars {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    pointer-events: none; z-index: 0;
    background-image: 
        radial-gradient(2px 2px at 20px 30px, var(--pink-soft), transparent),
        radial-gradient(2px 2px at 80px 60px, var(--tosca), transparent),
        radial-gradient(2px 2px at 150px 100px, var(--pink-medium), transparent);
    background-size: 200px 200px;
    opacity: 0.4;
    animation: twinkle 8s ease-in-out infinite;
}

@keyframes twinkle {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.5; }
}

.floating { animation: float 6s ease-in-out infinite; }
.floating-delay { animation: float 6s ease-in-out 2s infinite; }
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.checkout-wrapper {
    position: relative; z-index: 1;
    padding: 40px 20px;
    min-height: 100vh;
}

.checkout-header {
    text-align: center; padding: 60px 20px 40px;
    animation: slideDown 0.6s ease-out;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
}

.checkout-header h1 {
    font-family: 'Poppins', sans-serif;
    font-size: clamp(2rem, 4vw, 2.5rem);
    font-weight: 800;
    margin-bottom: 15px;
    background: var(--gradient-pink);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: inline-flex;
    align-items: center;
    gap: 12px;
}

.checkout-header h1 i {
    animation: bounce 2s ease-in-out infinite;
    color: var(--tosca) !important;
    -webkit-text-fill-color: var(--tosca) !important;
}

@keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.checkout-header p {
    font-size: 1.1rem;
    color: var(--gray-text);
    margin: 0;
}

.card {
    background: var(--gradient-card);
    border-radius: 30px;
    padding: 35px;
    box-shadow: var(--shadow-card);
    border: 2px solid var(--yellow-coral-light);
    transition: var(--transition-smooth);
    animation: slideUp 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.card::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(255,215,0,0.08) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
    pointer-events: none;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 70px var(--yellow-coral-glow);
}

.card-title {
    display: flex; align-items: center; gap: 12px;
    font-family: 'Poppins', sans-serif;
    font-weight: 700; font-size: 1.4rem;
    color: var(--navy);
    padding-bottom: 20px; margin-bottom: 25px;
    border-bottom: 3px dashed var(--yellow-coral);
}

.card-title i {
    background: var(--gradient-tosca);
    width: 40px; height: 40px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: white;
    font-size: 1.1rem;
}

.form-group { margin-bottom: 22px; }

.form-label {
    display: block; font-weight: 600; margin-bottom: 8px;
    color: var(--navy); font-size: 0.95rem;
}

.form-label .required { color: var(--pink-dark); margin-left: 3px; }

.form-control {
    width: 100%; padding: 14px 18px;
    border: 2px solid var(--yellow-coral);
    border-radius: 15px; font-size: 1rem;
    background: var(--white); color: var(--gray-dark);
    transition: var(--transition-smooth);
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: var(--tosca);
    box-shadow: 0 0 0 4px var(--tosca-glow);
    transform: translateY(-2px);
}

.form-control::placeholder { color: #aaa; }

textarea.form-control { resize: vertical; min-height: 80px; }

.payment-options { display: grid; gap: 15px; margin-bottom: 25px; }

.payment-option {
    border: 2px solid var(--yellow-coral);
    border-radius: 20px; padding: 18px 22px;
    cursor: pointer; transition: var(--transition-bounce);
    background: var(--white);
    display: flex; align-items: center; gap: 15px;
    position: relative; overflow: hidden;
}

.payment-option::before {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s;
}

.payment-option:hover::before { left: 100%; }

.payment-option:hover {
    border-color: var(--yellow-coral-dark);
    transform: translateX(5px);
    box-shadow: var(--shadow-soft);
}

.payment-option input { display: none; }

.payment-option input:checked + .payment-content + .payment-check {
    background: var(--gradient-coral);
    border-color: transparent;
    color: var(--navy);
    transform: scale(1.1);
}

.payment-icon {
    width: 45px; height: 45px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: white;
    flex-shrink: 0;
}

.payment-icon.bank { background: var(--gradient-coral); color: var(--navy); }
.payment-icon.ewallet { background: var(--gradient-tosca); }
.payment-icon.cod { background: var(--gradient-pink); color: var(--navy); }

.payment-content { flex: 1; }
.payment-content h6 {
    margin: 0 0 3px; font-weight: 700;
    color: var(--navy); font-size: 1rem;
}
.payment-content small {
    color: var(--gray-text); font-size: 0.85rem;
}

.payment-check {
    width: 26px; height: 26px;
    border: 2px solid var(--yellow-coral);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--yellow-coral-dark);
    transition: var(--transition-smooth);
    flex-shrink: 0;
}

.summary-card {
    position: sticky; top: 20px;
    background: var(--gradient-card);
    border-radius: 30px; padding: 30px;
    box-shadow: var(--shadow-card);
    border-left: 5px solid var(--pink-dark);
    animation: slideUp 0.6s ease-out 0.2s both;
}

.summary-item {
    display: flex; justify-content: space-between;
    padding: 12px 0; border-bottom: 1px dashed var(--yellow-coral);
}

.summary-item:last-of-type { border-bottom: none; }

.summary-total {
    display: flex; justify-content: space-between;
    font-size: 1.5rem; font-weight: 800;
    color: var(--navy);
    padding: 20px 0; margin-top: 15px;
    border-top: 3px solid var(--yellow-coral-dark);
}

.btn-submit {
    width: 100%; padding: 18px;
    background: var(--gradient-pink);
    color: white; border: none;
    border-radius: 50px;
    font-weight: 700; font-size: 1.1rem;
    cursor: pointer; transition: var(--transition-bounce);
    display: flex; align-items: center; justify-content: center;
    gap: 10px; margin-top: 25px;
    position: relative; overflow: hidden;
}

.btn-submit::before {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.btn-submit:hover::before { left: 100%; }

.btn-submit:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 40px rgba(255, 154, 162, 0.5);
}

.btn-submit:active { transform: translateY(-1px); }

.btn-submit:disabled {
    opacity: 0.7; cursor: not-allowed;
    transform: none !important;
}

.security-note {
    text-align: center; margin-top: 20px;
    color: var(--gray-text); font-size: 0.9rem;
    display: flex; align-items: center; justify-content: center;
    gap: 8px;
}

.security-note i { color: var(--tosca); }

.flash-message {
    background: linear-gradient(135deg, #FFF0F3, #FFF5F7);
    border-left: 4px solid var(--pink-medium);
    border-radius: 12px; padding: 15px 20px;
    color: var(--gray-dark); font-weight: 500;
    display: flex; align-items: center; gap: 10px;
    margin: 20px auto 0; max-width: 1200px;
    animation: slideDown 0.3s ease;
}

.flash-message.success {
    background: linear-gradient(135deg, #E6FFFA, #D1FAE5);
    border-left-color: var(--tosca);
    color: #065F46;
}

.flash-message.warning {
    background: linear-gradient(135deg, #FFF9E6, #FFF3CD);
    border-left-color: var(--yellow-coral-dark);
    color: #856404;
}

.flash-message.error {
    background: linear-gradient(135deg, #FFEBEE, #FEE2E2);
    border-left-color: var(--pink-dark);
    color: #991B1B;
}

.checkout-container {
    max-width: 1200px; margin: 0 auto;
    padding: 0 20px 60px;
    display: grid; grid-template-columns: 1fr 400px;
    gap: 30px;
}

@media (max-width: 992px) {
    .checkout-container { grid-template-columns: 1fr; }
    .summary-card { position: static; margin-top: 20px; }
}

@media (max-width: 576px) {
    .checkout-header { padding: 40px 20px 30px; }
    .card { padding: 25px 20px; }
    .form-group { margin-bottom: 18px; }
    .payment-option { padding: 15px 18px; }
    .btn-submit { padding: 16px; font-size: 1rem; }
}

.decoration {
    position: absolute; width: 60px; height: 60px;
    border-radius: 50%; opacity: 0.12;
    pointer-events: none;
}
.decoration-1 { top: 20px; right: 20px; background: var(--pink-medium); animation: float 4s ease-in-out infinite; }
.decoration-2 { bottom: 20px; left: 20px; background: var(--tosca); animation: float 5s ease-in-out 1s infinite; }
.decoration-3 { top: 50%; right: -30px; background: var(--pink-soft); animation: float 6s ease-in-out 2s infinite; }

::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--gray-soft); border-radius: 10px; }
::-webkit-scrollbar-thumb { background: var(--pink-soft); border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: var(--pink-medium); }

/* ========== TAMBAHAN UNTUK SUB-METODE PEMBAYARAN ========== */
.payment-sub {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px dashed var(--yellow-coral);
    display: none;
}
.payment-sub.active {
    display: block;
    animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="stars"></div>
<div class="checkout-wrapper">
    <div class="decoration decoration-1 floating"></div>
    <div class="decoration decoration-2 floating-delay"></div>
    <div class="decoration decoration-3 floating"></div>
    
    <?php if ($flash): ?>
    <div class="flash-message <?= htmlspecialchars($flash['type']) ?>">
        <i class="fas fa-<?= $flash['type']=='error'?'exclamation-circle':($flash['type']=='warning'?'bell':'circle-check') ?>"></i>
        <span><?= nl2br(htmlspecialchars($flash['message'])) ?></span>
    </div>
    <?php endif; ?>
    
    <header class="checkout-header">
        <h1><i class="fas fa-credit-card"></i> Checkout Aman</h1>
        <p>Selesaikan pesanan Anda dengan aman dan cepat</p>
    </header>
    
    <div class="checkout-container">
        <!-- Form Section -->
        <div class="card">
            <h3 class="card-title"><i class="fas fa-user"></i> Informasi Pelanggan</h3>
            <form method="POST" action="checkout-process.php" id="checkoutForm" novalidate>
                <input type="hidden" name="place_order" value="1">
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                    <div class="form-group">
                        <label class="form-label" for="name">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required 
                               placeholder="Masukkan nama lengkap" 
                               value="<?=htmlspecialchars($_POST['name']??'')?>" autocomplete="name">
                        <small class="error-msg" style="color:#DC2626;display:none">Nama wajib diisi</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">WhatsApp <span class="required">*</span></label>
                        <input type="tel" name="phone" id="phone" class="form-control" required 
                               placeholder="08123456789" 
                               pattern="^[0+]?6?2?8[0-9]{9,11}$" 
                               value="<?=htmlspecialchars($_POST['phone']??'')?>" autocomplete="tel">
                        <small class="error-msg" style="color:#DC2626;display:none">Format: 0812xxxx atau 62812xxxx</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email (Opsional)</label>
                    <input type="email" name="email" id="email" class="form-control" 
                           placeholder="email@contoh.com" 
                           value="<?=htmlspecialchars($_POST['email']??'')?>" autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="address">Alamat Lengkap <span class="required">*</span></label>
                    <textarea name="address" id="address" class="form-control" required rows="3" 
                              placeholder="Jalan, nomor, gedung, RT/RW, dll"
                              autocomplete="street-address"><?=htmlspecialchars($_POST['address']??'')?></textarea>
                    <small class="error-msg" style="color:#DC2626;display:none">Alamat wajib diisi</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="city">Kota <span class="required">*</span></label>
                    <select name="city" id="city" class="form-control" required autocomplete="address-level2">
                        <option value="">Pilih Kota</option>
                       <?php 
                        $city_list = getCityList();
                        $selected_city = $_POST['city'] ?? '';
                        foreach ($city_list as $city_name): 
                        ?>
                        <option value="<?= htmlspecialchars($city_name) ?>" <?= $selected_city == $city_name ? 'selected' : '' ?>>
                            <?= htmlspecialchars($city_name) ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="Other" <?=($_POST['city']??'')=='Other'?'selected':''?>>Lainnya</option>
                    </select>
                    <small class="error-msg" style="color:#DC2626;display:none">Kota wajib dipilih</small>
                </div>
                
                <h3 class="card-title" style="margin-top:35px"><i class="fas fa-wallet"></i> Pembayaran</h3>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="transfer" checked autocomplete="off">
                        <div class="payment-content">
                            <div class="payment-icon bank"><i class="fas fa-university"></i></div>
                            <h6>Transfer Bank</h6>
                            <small>BCA, BRI, Mandiri, BNI</small>
                        </div>
                        <div class="payment-check"><i class="fas fa-check"></i></div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="ewallet" autocomplete="off">
                        <div class="payment-content">
                            <div class="payment-icon ewallet"><i class="fas fa-wallet"></i></div>
                            <h6>E-Wallet</h6>
                            <small>DANA, OVO, GoPay, ShopeePay</small>
                        </div>
                        <div class="payment-check"><i class="fas fa-check"></i></div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cod" autocomplete="off">
                        <div class="payment-content">
                            <div class="payment-icon cod"><i class="fas fa-hand-holding-usd"></i></div>
                            <h6>COD (Bayar di Tempat)</h6>
                            <small>Hanya untuk Samarinda</small>
                        </div>
                        <div class="payment-check"><i class="fas fa-check"></i></div>
                    </label>
                </div>
                
                <!-- Sub-metode Transfer Bank -->
                <div id="sub-transfer" class="payment-sub">
                    <div class="form-group">
                        <label class="form-label" for="bank_name">Pilih Bank <span class="required">*</span></label>
                        <select name="bank_name" id="bank_name" class="form-control">
                            <option value="">Pilih Bank</option>
                            <option value="BCA">BCA</option>
                            <option value="BRI">BRI</option>
                            <option value="Mandiri">Mandiri</option>
                            <option value="BNI">BNI</option>
                            <option value="Other">Bank Lainnya</option>
                        </select>
                    </div>
                </div>
                
                <!-- Sub-metode E-Wallet -->
                <div id="sub-ewallet" class="payment-sub">
                    <div class="form-group">
                        <label class="form-label" for="ewallet_name">Pilih E-Wallet <span class="required">*</span></label>
                        <select name="ewallet_name" id="ewallet_name" class="form-control">
                            <option value="">Pilih E-Wallet</option>
                            <option value="DANA">DANA</option>
                            <option value="OVO">OVO</option>
                            <option value="GoPay">GoPay</option>
                            <option value="ShopeePay">ShopeePay</option>
                            <option value="Other">Lainnya</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="note">Catatan Pesanan (Opsional)</label>
                    <textarea name="note" id="note" class="form-control" rows="2" 
                              placeholder="Instruksi khusus, contoh: 'Tolong dikemas kado'"
                              autocomplete="off"><?=htmlspecialchars($_POST['note']??'')?></textarea>
                </div>
                
                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-lock"></i> Bayar Sekarang - <?=formatRupiah($total)?>
                </button>
                
                <div class="security-note">
                    <i class="fas fa-shield-alt"></i> Data Anda aman & terenkripsi
                </div>
            </form>
        </div>
        
        <!-- Ringkasan Pesanan -->
        <div class="summary-card">
            <h3 class="card-title" style="border-bottom-color:var(--yellow-coral)"><i class="fas fa-receipt"></i> Ringkasan</h3>
            
            <div style="max-height:280px; overflow-y:auto; margin-bottom:20px; padding-right:8px;">
                <?php foreach($cart as $item): 
                    $item_subtotal = $item['price'] * $item['qty'];
                ?>
                <div class="summary-item" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="flex:2;">
                        <strong style="color:var(--navy); display:block;"><?= htmlspecialchars($item['name']) ?></strong>
                        <small style="color:var(--gray-text);">
                            <?= formatRupiah($item['price']) ?> × <?= $item['qty'] ?>
                        </small>
                    </div>
                    <div style="flex:1; text-align:right;">
                        <strong style="color:var(--yellow-coral-dark);"><?= formatRupiah($item_subtotal) ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="summary-item" style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px dashed var(--yellow-coral);">
                <span>Subtotal (<?= count($cart) ?> item)</span>
                <span><?= formatRupiah($subtotal) ?></span>
            </div>
            
            <div class="summary-item" id="shipping-item" style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px dashed var(--yellow-coral);">
                <span>Ongkos Kirim</span>
                <span id="shipping-amount"><?= formatRupiah($shipping) ?></span>
            </div>
            
            <div class="summary-total" id="total-item" style="display:flex; justify-content:space-between; font-size:1.4rem; font-weight:800; color:var(--navy); padding-top:15px; margin-top:10px; border-top:3px solid var(--yellow-coral-dark);">
                <span>Total</span>
                <span id="total-amount"><?= formatRupiah($total) ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
    const required = this.querySelectorAll('[required]');
    let valid = true;
    
    required.forEach(f => {
        if (!f.value.trim()) {
            f.style.borderColor = '#DC2626';
            f.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
            valid = false;
        } else {
            f.style.borderColor = '';
            f.style.boxShadow = '';
        }
    });
    
    const phone = this.querySelector('input[name="phone"]');
    if (phone.value && !/^62[0-9]{9,12}$/.test(phone.value)) {
        phone.style.borderColor = '#DC2626';
        phone.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
        valid = false;
    }
    
    if (!valid) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Lengkapi Data',
            text: 'Mohon isi semua field wajib',
            confirmButtonColor: '#FFA500',
            background: '#FFF9E6',
            color: '#333'
        });
        return;
    }
    
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    btn.disabled = true;
    btn.style.opacity = '0.8';
});

document.querySelector('input[name="phone"]')?.addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    if (v.startsWith('08')) v = '62' + v.substring(1);
    if (v.length > 13) v = v.substring(0, 13);
    e.target.value = v;
});

document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.querySelector('.form-label')?.style.setProperty('color', 'var(--tosca-dark)');
    });
    input.addEventListener('blur', function() {
        this.parentElement.querySelector('.form-label')?.style.setProperty('color', 'var(--navy)');
    });
});

document.querySelectorAll('.flash-message').forEach(msg => {
    setTimeout(() => {
        msg.style.opacity = '0';
        msg.style.transform = 'translateY(-10px)';
        setTimeout(() => msg.remove(), 300);
    }, 5000);
});

// ========== TOGGLE SUB-METODE PEMBAYARAN ==========
const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
const subTransfer = document.getElementById('sub-transfer');
const subEwallet = document.getElementById('sub-ewallet');
const bankSelect = document.getElementById('bank_name');
const ewalletSelect = document.getElementById('ewallet_name');

function togglePaymentSub() {
    const selected = document.querySelector('input[name="payment_method"]:checked').value;
    subTransfer.classList.remove('active');
    subEwallet.classList.remove('active');
    if (selected === 'transfer') {
        subTransfer.classList.add('active');
        bankSelect.setAttribute('required', 'required');
        ewalletSelect.removeAttribute('required');
    } else if (selected === 'ewallet') {
        subEwallet.classList.add('active');
        ewalletSelect.setAttribute('required', 'required');
        bankSelect.removeAttribute('required');
    } else {
        bankSelect.removeAttribute('required');
        ewalletSelect.removeAttribute('required');
    }
}

paymentRadios.forEach(radio => radio.addEventListener('change', togglePaymentSub));
togglePaymentSub(); // Inisialisasi

// ========== UPDATE ONGKIR VIA AJAX ==========
function formatRupiah(amount) {
    return 'Rp ' + amount.toLocaleString('id-ID');
}

const citySelect = document.getElementById('city');
if (citySelect) {
    citySelect.addEventListener('change', function() {
        const city = this.value;
        if (!city) return;
        
        const subtotal = <?php echo json_encode($subtotal); ?>;
        
        fetch(window.location.pathname + '?get_shipping=1&city=' + encodeURIComponent(city))
            .then(response => response.json())
            .then(data => {
                const shippingAmount = data.shipping;
                const totalAmount = subtotal + shippingAmount;
                
                const shippingSpan = document.getElementById('shipping-amount');
                const totalSpan = document.getElementById('total-amount');
                if (shippingSpan) shippingSpan.innerText = formatRupiah(shippingAmount);
                if (totalSpan) totalSpan.innerText = formatRupiah(totalAmount);
            })
            .catch(err => console.error('Error fetching shipping:', err));
    });
}
</script>