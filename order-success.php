<?php
require_once 'config.php';
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$order_number = $_GET['order'] ?? '';

if (empty($order_number)) {
    redirect('checkout.php');
}

$conn = getDBConnection();
$stmt = mysqli_prepare($conn, "
    SELECT o.*, 
           GROUP_CONCAT(
               CONCAT(oi.product_name,'|',oi.quantity,'|',oi.price,'|',oi.subtotal)
               SEPARATOR ';;'
           ) as items_detail
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.order_number = ?
    GROUP BY o.id
");
mysqli_stmt_bind_param($stmt, "s", $order_number);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order_db = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$order_db) {
    echo "<div style='background:#fee;padding:20px;margin:20px;border:2px solid #f00;text-align:center;border-radius:15px'>";
    echo "Order tidak ditemukan: <strong>" . htmlspecialchars($order_number) . "</strong><br>";
    echo "<a href='checkout.php' style='color:#FF9AA2;text-decoration:none'>← Kembali</a>";
    echo "</div>";
    exit;
}

$items = [];
if (!empty($order_db['items_detail'])) {
    foreach (explode(';;', $order_db['items_detail']) as $item_str) {
        $parts = explode('|', $item_str);
        if (count($parts) == 4) {
            $items[] = [
                'name' => $parts[0],
                'qty' => (int)$parts[1],
                'price' => (float)$parts[2],
                'subtotal' => (float)$parts[3]
            ];
        }
    }
}

$order = [
    'order_number' => $order_db['order_number'],
    'name' => $order_db['customer_name'],
    'phone' => $order_db['customer_phone'],
    'address' => $order_db['customer_address'],
    'city' => $order_db['customer_city'],
    'payment' => $order_db['payment_method'],
    'subtotal' => (float)$order_db['subtotal'],
    'shipping' => (float)$order_db['shipping_cost'],
    'total' => (float)$order_db['total'],
    'store_type' => $order_db['store_type'],
    'items' => $items
];

$store_name = $order['store_type'] == 'slime' ? 'Vij Slimee' : ($order['store_type'] == 'photocard' ? 'Aprpiejise' : 'Vij Slimee & Aprpiejise');
$store_instagram = $order['store_type'] == 'slime' ? 'vijslimee.smr' : ($order['store_type'] == 'photocard' ? 'aprpiejise' : 'vijslimee.smr');

$items_summary = array_map(fn($i) => "• {$i['name']} ({$i['qty']}x) — " . formatRupiah($i['subtotal']), $order['items']);

// Hapus emoji di pesan Instagram
$ig_message = "Halo Kak {$order['name']}!\n\n" .
    "Terima kasih sudah berbelanja di {$store_name}.\n\n" .
    "────────────────────\n" .
    "No. Order: {$order['order_number']}\n" .
    "Nama: {$order['name']}\n" .
    "No. HP: {$order['phone']}\n" .
    "────────────────────\n" .
    "Pesanan:\n" .
    implode("\n", $items_summary) . "\n" .
    "────────────────────\n" .
    "Subtotal: " . formatRupiah($order['subtotal']) . "\n" .
    "Ongkir: " . formatRupiah($order['shipping']) . "\n" .
    "Total: " . formatRupiah($order['total']) . "\n" .
    "────────────────────\n" .
    "Alamat: {$order['address']}, {$order['city']}\n" .
    "Pembayaran: " . strtoupper($order['payment']) . "\n\n" .
    "Mohon konfirmasi pesanan ini melalui DM ya. Terima kasih 🩷 ";

$page_title = "Pesanan Berhasil - " . SITE_NAME;
?>
<?php require_once 'includes/header.php'; ?>

<style>
:root {
    --pink-light: #fbcddc;
    --pink-soft: #FFB6C1;
    --pink-medium: #FF9AA2;
    --pink-dark: #FF6B9D;
    --tosca-light: #E0F7FA;
    --tosca: #40E0D0;
    --tosca-dark: #20B2AA;
    --yellow-light: #FFF9E6;
    --yellow: #FFD700;
    --yellow-soft: #f9ec86;
    --coral: #FFA500;
    --white: #ffffff;
    --gray-light: #f8f9fa;
    --gray: #6c757d;
    --navy: #1a2a6c;
    --shadow-sm: 0 10px 30px rgba(0,0,0,0.05);
    --shadow-md: 0 20px 40px rgba(0,0,0,0.1);
    --shadow-lg: 0 25px 50px rgba(255,182,193,0.2);
    --transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
}

/* Background lembut (user modified) */
body {
    background: linear-gradient(135deg, #fff0bb 0%, #ffc2db 100%);
}
.stars {
    position: fixed; inset: 0; pointer-events: none; z-index: 0;
    background-image: radial-gradient(2px 2px at 20px 30px, #fec4cd, transparent),
                      radial-gradient(2px 2px at 80px 60px, #b2fbf3, transparent);
    background-size: 200px 200px;
    opacity: 0.3;
    animation: twinkle 8s infinite;
}
@keyframes twinkle { 0%,100%{opacity:0.2;}50%{opacity:0.4;} }

.success-wrapper {
    position: relative; z-index: 1;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 80px 20px;
}
.success-card {
    background: var(--white);
    border-radius: 48px;
    padding: 50px 45px;
    max-width: 750px;
    width: 100%;
    box-shadow: var(--shadow-lg);
    border: 1px solid rgba(255,182,193,0.4);
    animation: slideUp 0.6s ease-out;
    position: relative;
    backdrop-filter: blur(2px);
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(60px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.success-header {
    text-align: center;
    padding-bottom: 35px;
    border-bottom: 2px dashed var(--pink-soft);
    margin-bottom: 35px;
}
.success-icon {
    width: 110px; height: 110px;
    margin: 0 auto 25px;
    background: linear-gradient(135deg, var(--yellow-soft), var(--pink-medium));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    color: white;
    box-shadow: 0 15px 30px rgba(255,182,193,0.4);
    animation: gentleBounce 2s infinite;
}
@keyframes gentleBounce {
    0%,100%{transform:translateY(0);}
    50%{transform:translateY(-8px);}
}
.success-header h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 2.2rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--pink-medium), var(--yellow-soft));
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.3px;
}
.success-header p {
    color: var(--gray);
    font-size: 1rem;
    margin-top: 8px;
}
.order-number {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--pink-light);
    padding: 10px 28px;
    border-radius: 60px;
    font-weight: 700;
    color: var(--pink-dark);
    border: 1px solid var(--pink-soft);
    margin-top: 20px;
    font-size: 0.95rem;
}
.order-number i {
    font-size: 1.2rem;
}

/* Next steps */
.next-steps {
    background: linear-gradient(145deg, var(--pink-soft), var(--tosca-light));
    border-radius: 32px;
    padding: 24px 30px;
    margin-bottom: 35px;
    border-left: 6px solid var(--pink-dark);
}
.next-steps h4 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 15px;
    font-size: 1.1rem;
}
.next-steps ol {
    padding-left: 24px;
    margin: 0;
    counter-reset: step;
}
.next-steps li {
    margin-bottom: 10px;
    color: var(--navy);
    font-weight: 500;
    position: relative;
    padding-left: 5px;
}
.next-steps li::before {
    content: counter(step);
    counter-increment: step;
    position: absolute;
    left: -22px;
    width: 20px; height: 20px;
    background: linear-gradient(135deg, var(--pink-light), var(--tosca));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
}
.confirmation-title {
    text-align: center;
    font-weight: 700;
    font-size: 1.5rem;
    margin-bottom: 25px;
    color: var(--navy);
    font-family: 'Poppins', sans-serif;
    letter-spacing: -0.2px;
}
.ig-card {
    background: var(--white);
    border-radius: 36px;
    padding: 30px 25px;
    text-align: center;
    border: 1px solid var(--pink-soft);
    margin-bottom: 30px;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}
.ig-card:hover {
    transform: translateY(-6px);
    border-color: var(--tosca);
    box-shadow: var(--shadow-lg);
}
.ig-icon {
    width: 80px; height: 80px;
    margin: 0 auto 18px;
    background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    color: white;
    box-shadow: 0 8px 20px rgba(220,39,67,0.3);
}
.message-box {
    background: #FEFAF5;
    border: 1px dashed var(--pink-soft);
    border-radius: 24px;
    padding: 18px;
    margin: 20px 0;
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    max-height: 280px;
    overflow-y: auto;
    white-space: pre-wrap;
    text-align: left;
    color: #4a5568;
    line-height: 1.5;
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px 28px;
    border-radius: 60px;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    width: 100%;
    margin-bottom: 12px;
}
.btn-copy {
    background: linear-gradient(135deg, var(--pink-medium), var(--pink-dark));
    color: white;
    box-shadow: 0 6px 15px rgba(255,107,157,0.3);
}
.btn-copy:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(255,107,157,0.4); }
.btn-instagram {
    background: linear-gradient(135deg, #f09433, #dc2743, #bc1888);
    color: white;
}
.btn-instagram:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(220,39,67,0.4); }
.btn-continue {
    background: white;
    border: 2px solid var(--pink-soft);
    color: var(--pink-dark);
    width: auto;
    padding: 12px 32px;
}
.btn-continue:hover {
    background: linear-gradient(135deg, var(--pink-soft), var(--pink-medium));
    color: white;
    border-color: transparent;
    transform: translateY(-3px);
}
.ig-tip {
    background: rgba(64,224,208,0.08);
    border-radius: 20px;
    padding: 12px 18px;
    font-size: 0.85rem;
    color: var(--gray);
    border-left: 4px solid var(--tosca);
    margin-top: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    text-align: left;
}
.ig-tip i {
    color: var(--tosca-dark);
    font-size: 1.2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .success-card { padding: 35px 25px; border-radius: 36px; }
    .success-header h1 { font-size: 1.8rem; }
    .success-icon { width: 85px; height: 85px; font-size: 2.8rem; }
    .order-number { font-size: 0.85rem; padding: 8px 20px; }
    .confirmation-title { font-size: 1.3rem; }
}
@media (max-width: 480px) {
    .success-wrapper { padding: 40px 15px; }
    .success-card { padding: 25px 18px; }
    .success-header h1 { font-size: 1.5rem; }
    .next-steps { padding: 18px 20px; }
    .btn { padding: 10px 20px; font-size: 0.85rem; }
    .message-box { font-size: 0.7rem; }
}
</style>

<div class="stars"></div>
<div class="success-wrapper">
    <div class="success-card">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Pesanan Berhasil</h1>
            <p>Terima kasih telah berbelanja di <?= htmlspecialchars(SITE_NAME) ?></p>
            <div class="order-number">
                <i class="fas fa-receipt"></i> 
                <span><?= htmlspecialchars($order['order_number']) ?></span>
            </div>
        </div>

        <div class="next-steps">
            <h4><i class="fas fa-info-circle"></i> Langkah Selanjutnya</h4>
            <ol>
                <li>Salin pesan konfirmasi di bawah ini</li>
                <li>Buka Instagram <strong>@<?= htmlspecialchars($store_instagram) ?></strong></li>
                <li>Kirim pesan melalui Direct Message (DM)</li>
                <li>Kami akan segera memproses pesananmu</li>
            </ol>
        </div>

        <h4 class="confirmation-title">Konfirmasi via Instagram DM</h4>

        <div class="ig-card">
            <div class="ig-icon">
                <i class="fab fa-instagram"></i>
            </div>
            <h5>@<?= htmlspecialchars($store_instagram) ?></h5>
            <p>Kirim pesan ini untuk mengonfirmasi pesanan</p>

            <div class="message-box" id="igMessage"><?= htmlspecialchars($ig_message) ?></div>

            <button onclick="copyMessage('igMessage', this)" class="btn btn-copy">
                <i class="fas fa-copy"></i> Salin Pesan
            </button>

            <a href="https://instagram.com/<?= htmlspecialchars($store_instagram) ?>" target="_blank" class="btn btn-instagram">
                <i class="fab fa-instagram"></i> Buka Instagram
            </a>

            <div class="ig-tip">
                <i class="fas fa-lightbulb"></i>
                <span><strong>Tips:</strong> Setelah menyalin, buka Instagram, buka profil @<?= htmlspecialchars($store_instagram) ?>, lalu klik "Message" dan tempel pesan di atas.</span>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="products.php" class="btn btn-continue">
                <i class="fas fa-shopping-bag"></i> Lanjut Belanja
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function copyMessage(id, btn) {
    const box = document.getElementById(id);
    if (!box) return;
    const text = box.innerText;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyalin...';
    btn.disabled = true;

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showSuccess(btn, originalHtml);
        }).catch(() => fallbackCopy(text, btn, originalHtml));
    } else {
        fallbackCopy(text, btn, originalHtml);
    }
}

function fallbackCopy(text, btn, originalHtml) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        showSuccess(btn, originalHtml);
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Gagal menyalin', text: 'Silakan salin manual dengan Ctrl+C', confirmButtonColor: '#FF9AA2' });
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }
    document.body.removeChild(textarea);
}

function showSuccess(btn, originalHtml) {
    btn.innerHTML = '<i class="fas fa-check"></i> Tersalin!';
    btn.style.background = '#10B981';
    Swal.fire({
        icon: 'success',
        title: 'Pesan tersalin',
        text: 'Sekarang buka Instagram dan kirim pesan ke @' + '<?= htmlspecialchars($store_instagram) ?>',
        timer: 2500,
        showConfirmButton: false,
        position: 'top-end',
        toast: true,
        background: '#FFF0F3',
        color: '#1a2a6c'
    });
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.style.background = '';
        btn.disabled = false;
    }, 2000);
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        Swal.fire({
            icon: 'success',
            title: 'Pesanan Diterima',
            html: 'Jangan lupa konfirmasi via Instagram DM ya.<br>Kami akan segera memproses pesanan Anda.',
            timer: 4000,
            showConfirmButton: false,
            background: 'linear-gradient(145deg, #FFF0F5, #E0F7FA)',
            color: '#1a2a6c',
            iconColor: '#FF6B9D'
        });
    }, 800);
});
</script>