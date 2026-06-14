<?php
/**
 * ============================================
 * PENGATURAN SISTEM - VIJARIE
 * Tema: Gradient Pink + Kuning-Coral
 * ============================================
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$page_title  = 'Pengaturan - VIJARIE';
$conn        = getDBConnection();
$message     = '';
$message_type = '';

// ----------------------------------------------------------------------
// PROSES UPDATE SETTINGS
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    // Default untuk checkbox yang tidak terkirim
    $checkbox_keys = [
        'maintenance_mode', 'show_stock',
        'enable_whatsapp_order', 'enable_reviews'
    ];
    foreach ($checkbox_keys as $ck) {
        $post_key = 'setting_' . $ck;
        if (!isset($_POST[$post_key])) {
            $_POST[$post_key] = '0';
        }
    }

    $updated_count = 0;
    $error_msg     = '';
    $admin_id      = $_SESSION['admin_id'] ?? 1; // simpan ke variabel

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') !== 0) {
            continue;
        }

        $setting_key = str_replace('setting_', '', $key);

        // Validasi dan sanitasi berdasarkan key
        switch ($setting_key) {
            case 'whatsapp_number':
                $value = preg_replace('/[^\d+]/', '', $value);
                if (strlen(preg_replace('/\D/', '', $value)) < 10) {
                    $error_msg = 'Nomor WhatsApp tidak valid (minimal 10 digit)';
                    break 2;
                }
                break;

            case 'instagram_slime':
            case 'instagram_kpop':
            case 'tiktok_slime':
            case 'tiktok_kpop':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $error_msg = 'Format URL media sosial tidak valid';
                    break 2;
                }
                break;

            case 'shipping_samarinda':
            case 'shipping_balikpapan':
            case 'shipping_jakarta':
            case 'shipping_default':
            case 'free_shipping_min':
                $value = max(0, (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT));
                break;

            case 'bank_account':
                $value = preg_replace('/[^\d]/', '', $value);
                break;

            case 'maintenance_mode':
            case 'show_stock':
            case 'enable_whatsapp_order':
            case 'enable_reviews':
                $value = ($value === '1') ? '1' : '0';
                break;

            default:
                $value = sanitize($value);
                break;
        }

        if ($error_msg) {
            break;
        }

        $stmt = mysqli_prepare($conn, "UPDATE settings SET value = ?, updated_by = ?, updated_at = NOW() WHERE key_name = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sis', $value, $admin_id, $setting_key);
            if (mysqli_stmt_execute($stmt)) {
                $updated_count++;
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($error_msg) {
        $message      = $error_msg;
        $message_type = 'error';
    } elseif ($updated_count > 0) {
        $message      = "{$updated_count} pengaturan berhasil diperbarui!";
        $message_type = 'success';

        // Informasi tambahan untuk maintenance mode
        $new_maintenance = isset($_POST['setting_maintenance_mode']) ? $_POST['setting_maintenance_mode'] : '0';
        if ($new_maintenance == '1') {
            $message .= ' Mode maintenance AKTIF. Website akan menampilkan halaman maintenance.';
        } else {
            $message .= ' Mode maintenance NONAKTIF. Website kembali normal.';
        }
    } else {
        $message      = 'Tidak ada perubahan yang disimpan';
        $message_type = 'info';
    }
}

// ----------------------------------------------------------------------
// AMBIL DATA SETTINGS DARI DATABASE
// ----------------------------------------------------------------------
$settings = [];
$result   = mysqli_query($conn, "SELECT key_name, value FROM settings");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['key_name']] = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --pink-coral: #FFB6C1;
            --pink-tua: #FF69B4;
            --tosca: #40E0D0;
            --tosca-muda: #7FFFD4;
            --kuning: #FFD700;
            --kuning-coral: #FFA500;
            --biru-dongker: #000080;
            --abu-abu: #6B7280;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
        }
        body { font-family: 'Quicksand', sans-serif; background: linear-gradient(135deg, #FFF5F7 0%, #F0FFFF 100%); }
        .settings-card { background: white; border-radius: 20px; padding: 24px; margin-bottom: 25px; border: 1px solid rgba(255,182,193,0.2); transition: 0.3s; }
        .settings-card:hover { transform: translateY(-3px); box-shadow: 0 12px 40px rgba(255,182,193,0.25); }
        .settings-card-header { display: flex; align-items: center; gap: 10px; padding-bottom: 15px; margin-bottom: 20px; border-bottom: 2px dashed var(--pink-coral); }
        .section-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; margin-right: 12px; }
        .section-icon.general { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); }
        .section-icon.shipping { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); }
        .section-icon.payment { background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); }
        .section-icon.social { background: linear-gradient(135deg, #A78BFA, #8B5CF6); }
        .section-icon.system { background: linear-gradient(135deg, #6B7280, #4B5563); }
        .form-label { font-weight: 600; color: var(--biru-dongker); margin-bottom: 7px; font-size: 0.88rem; display: flex; align-items: center; gap: 6px; }
        .form-control, .form-select { border: 2px solid var(--pink-coral); border-radius: 12px; padding: 10px 14px; background: white; }
        .form-check.form-switch .form-check-input { width: 3em; height: 1.6em; border: 2px solid var(--pink-coral); }
        .form-check.form-switch .form-check-input:checked { background-color: var(--pink-tua); border-color: var(--pink-tua); }
        .btn-save { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); border: none; border-radius: 60px; padding: 12px 35px; color: white; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 6px 20px rgba(255,105,180,0.35); }
        .btn-save:hover { transform: translateY(-3px); box-shadow: 0 12px 35px rgba(255,105,180,0.5); }
        .btn-cancel { background: rgba(128,128,128,0.12); border: none; border-radius: 60px; padding: 12px 28px; color: var(--abu-abu); font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .alert-custom { border-radius: 14px; padding: 12px 16px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s; }
        .alert-custom.success { background: #D1FAE5; color: #059669; border-left: 4px solid var(--success); }
        .alert-custom.error { background: #FEE2E2; color: #DC2626; border-left: 4px solid var(--danger); }
        .alert-custom.warning { background: #FEF3C7; color: #92400E; border-left: 4px solid var(--warning); }
        .alert-custom.info { background: #DBEAFE; color: #2563EB; border-left: 4px solid #3B82F6; }
        @keyframes slideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
        .config-sync-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); color: var(--biru-dongker); border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        footer { text-align: center; padding: 20px; color: var(--abu-abu); font-size: 0.8rem; border-top: 1px solid rgba(255,182,193,0.2); margin-top: 30px; }
        @media (max-width: 768px) { .settings-card-header { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <main class="modern-main">
        <?php include 'includes/header.php'; ?>

        <?php if ($message): ?>
        <div class="alert-custom <?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'error' ? 'exclamation-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'info-circle')); ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.style.display='none'"></button>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h1 class="page-title" style="font-size:1.6rem; font-weight:800;">
                <i class="fas fa-cog" style="color:var(--pink-tua);"></i> Pengaturan Sistem
            </h1>
            <span class="config-sync-badge"><i class="fas fa-sync-alt"></i> Simpan otomatis ke database</span>
        </div>

        <form method="POST" id="settingsForm">
            <!-- Informasi Website -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="section-icon general"><i class="fas fa-globe"></i></div>
                    <h5>Informasi Website</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Website <span class="text-danger">*</span></label>
                        <input type="text" name="setting_site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'VIJARIE'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Deskripsi Website</label>
                        <input type="text" name="setting_site_description" class="form-control" value="<?php echo htmlspecialchars($settings['site_description'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prefix Nomor Order</label>
                        <input type="text" name="setting_order_prefix" class="form-control" value="<?php echo htmlspecialchars($settings['order_prefix'] ?? 'VJR'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Simbol Mata Uang</label>
                        <input type="text" name="setting_currency_symbol" class="form-control" value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'Rp'); ?>">
                    </div>
                </div>
            </div>

            <!-- Kontak & Media Sosial -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="section-icon social"><i class="fas fa-share-alt"></i></div>
                    <h5>Kontak & Media Sosial</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label"><i class="fab fa-whatsapp"></i> WhatsApp</label><input type="tel" name="setting_whatsapp_number" class="form-control" value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? ''); ?>"></div>
                    <div class="col-md-6"><label class="form-label"><i class="fab fa-instagram"></i> Instagram Slime</label><input type="url" name="setting_instagram_slime" class="form-control" value="<?php echo htmlspecialchars($settings['instagram_slime'] ?? ''); ?>"></div>
                    <div class="col-md-6"><label class="form-label"><i class="fab fa-instagram"></i> Instagram K-Pop</label><input type="url" name="setting_instagram_kpop" class="form-control" value="<?php echo htmlspecialchars($settings['instagram_kpop'] ?? ''); ?>"></div>
                    <div class="col-md-6"><label class="form-label"><i class="fab fa-tiktok"></i> TikTok Slime</label><input type="url" name="setting_tiktok_slime" class="form-control" value="<?php echo htmlspecialchars($settings['tiktok_slime'] ?? ''); ?>"></div>
                    <div class="col-md-6"><label class="form-label"><i class="fab fa-tiktok"></i> TikTok K-Pop</label><input type="url" name="setting_tiktok_kpop" class="form-control" value="<?php echo htmlspecialchars($settings['tiktok_kpop'] ?? ''); ?>"></div>
                </div>
            </div>

            <!-- Ongkos Kirim -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="section-icon shipping"><i class="fas fa-truck"></i></div>
                    <h5>Ongkos Kirim</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Samarinda</label><input type="number" name="setting_shipping_samarinda" class="form-control" value="<?php echo (int)($settings['shipping_samarinda'] ?? 10000); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Balikpapan</label><input type="number" name="setting_shipping_balikpapan" class="form-control" value="<?php echo (int)($settings['shipping_balikpapan'] ?? 15000); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Jakarta</label><input type="number" name="setting_shipping_jakarta" class="form-control" value="<?php echo (int)($settings['shipping_jakarta'] ?? 25000); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Default (Luar Kota)</label><input type="number" name="setting_shipping_default" class="form-control" value="<?php echo (int)($settings['shipping_default'] ?? 20000); ?>"></div>
                    <div class="col-md-6"><label class="form-label">Minimal Free Shipping</label><input type="number" name="setting_free_shipping_min" class="form-control" value="<?php echo (int)($settings['free_shipping_min'] ?? 100000); ?>"></div>
                </div>
            </div>

            <!-- Metode Pembayaran -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="section-icon payment"><i class="fas fa-credit-card"></i></div>
                    <h5>Metode Pembayaran</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Nama Bank</label><input type="text" name="setting_bank_name" class="form-control" value="<?php echo htmlspecialchars($settings['bank_name'] ?? 'BCA'); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Nomor Rekening</label><input type="text" name="setting_bank_account" class="form-control" value="<?php echo htmlspecialchars($settings['bank_account'] ?? ''); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Atas Nama Rekening</label><input type="text" name="setting_bank_account_name" class="form-control" value="<?php echo htmlspecialchars($settings['bank_account_name'] ?? ''); ?>"></div>
                    <div class="col-md-4"><label class="form-label">DANA</label><input type="tel" name="setting_ewallet_dana" class="form-control" value="<?php echo htmlspecialchars($settings['ewallet_dana'] ?? ''); ?>"></div>
                    <div class="col-md-4"><label class="form-label">OVO</label><input type="tel" name="setting_ewallet_ovo" class="form-control" value="<?php echo htmlspecialchars($settings['ewallet_ovo'] ?? ''); ?>"></div>
                    <div class="col-md-4"><label class="form-label">GoPay</label><input type="tel" name="setting_ewallet_gopay" class="form-control" value="<?php echo htmlspecialchars($settings['ewallet_gopay'] ?? ''); ?>"></div>
                </div>
            </div>

            <!-- Pengaturan Sistem (termasuk Maintenance Mode) -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="section-icon system"><i class="fas fa-cogs"></i></div>
                    <h5>Pengaturan Sistem</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="setting_maintenance_mode" id="setting_maintenance_mode" value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="setting_maintenance_mode">Mode Maintenance</label>
                        </div>
                        <small class="form-text text-muted">Aktifkan untuk menampilkan halaman maintenance ke pengunjung</small>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="setting_show_stock" id="setting_show_stock" value="1" <?php echo ($settings['show_stock'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="setting_show_stock">Tampilkan Stok Produk</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="setting_enable_whatsapp_order" id="setting_enable_whatsapp_order" value="1" <?php echo ($settings['enable_whatsapp_order'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="setting_enable_whatsapp_order">Order via WhatsApp</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="setting_enable_reviews" id="setting_enable_reviews" value="1" <?php echo ($settings['enable_reviews'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="setting_enable_reviews">Ulasan Produk</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-3">
                <button type="button" class="btn-cancel" onclick="history.back()"><i class="fas fa-times"></i> Batal</button>
                <button type="submit" name="update_settings" class="btn-save"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </div>
        </form>

        <footer>
            <p>© <?php echo date('Y'); ?> <strong>VIJARIE</strong> — Panel Admin. Hak Cipta Dilindungi.</p>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toggleBtn = document.querySelector('.toggle-sidebar');
            var sidebar = document.querySelector('.modern-sidebar');
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function(e) { e.stopPropagation(); sidebar.classList.toggle('show'); });
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 1024 && sidebar.classList.contains('show') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                });
            }
            document.querySelectorAll('.alert-custom').forEach(function(el) {
                setTimeout(function() { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 300); }, 5000);
            });
            var btnSave = document.querySelector('.btn-save');
            if (btnSave) {
                btnSave.addEventListener('click', function() {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    this.disabled = true;
                    setTimeout(function() {
                        if (btnSave) {
                            btnSave.innerHTML = '<i class="fas fa-save"></i> Simpan Perubahan';
                            btnSave.disabled = false;
                        }
                    }, 3000);
                });
            }
        });
    </script>
</body>
</html>