<?php
// ============================================
// PENGATURAN SISTEM - Vij Slimee & Aprpiejise
// Tema: Gradient Pink + Kuning-Coral
// Bahasa: Indonesia
// Keamanan: Prepared Statements + Config Sync
// ============================================

require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Pengaturan - Admin';
$conn = getDBConnection();

$message = '';
$message_type = '';

// ============================================
// HANDLE UPDATE SETTINGS
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $updated_count = 0;
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = str_replace('setting_', '', $key);
            
            // Validasi & sanitasi berdasarkan tipe setting
            switch($setting_key) {
                case 'whatsapp_number':
                    // Hanya angka dan +, minimal 10 digit
                    $value = preg_replace('/[^\d+]/', '', $value);
                    if (strlen(preg_replace('/\D/', '', $value)) < 10) {
                        $message = '❌ Nomor WhatsApp tidak valid (minimal 10 digit)';
                        $message_type = 'error';
                        break 2;
                    }
                    break;
                    
                case 'instagram_slime':
                case 'instagram_kpop':
                case 'tiktok_slime':
                case 'tiktok_kpop':
                    // Validasi URL media sosial
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        $message = '❌ Format URL media sosial tidak valid';
                        $message_type = 'error';
                        break 2;
                    }
                    break;
                    
                case 'shipping_samarinda':
                case 'shipping_balikpapan':
                case 'shipping_jakarta':
                case 'shipping_default':
                case 'free_shipping_min':
                    // Pastikan angka positif
                    $value = max(0, (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT));
                    break;
                    
                case 'bank_account':
                    // Hanya angka untuk nomor rekening
                    $value = preg_replace('/[^\d]/', '', $value);
                    break;
                    
                default:
                    $value = sanitize($value);
            }
            
            if ($message_type !== 'error') {
                // Update dengan prepared statement
                $stmt = mysqli_prepare($conn, "UPDATE settings SET value = ?, updated_by = ?, updated_at = NOW() WHERE key_name = ?");
               $admin_id = $_SESSION['admin_id'] ?? 1;
                mysqli_stmt_bind_param($stmt, "sis", $value, $admin_id, $setting_key);
                
                if (mysqli_stmt_execute($stmt)) {
                    $updated_count++;
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    if ($message_type !== 'error' && $updated_count > 0) {
        $message = "✅ {$updated_count} pengaturan berhasil diperbarui!";
        $message_type = 'success';
        
        // 🔗 SYNC KE CONFIG FILE WEBSITE (PENTING!)
        syncSettingsToConfig($conn);
    }
}

// ============================================
// FUNGSI: SYNC SETTINGS KE CONFIG WEBSITE
// ============================================
function syncSettingsToConfig($conn) {
    // Ambil semua settings dari database
    $settings = [];
    $result = mysqli_query($conn, "SELECT key_name, value FROM settings WHERE is_active = 1");
    while($row = mysqli_fetch_assoc($result)) {
        $settings[$row['key_name']] = $row['value'];
    }
    
    // Generate config.php content
    $config_content = "<?php\n";
    $config_content .= "// ============================================\n";
    $config_content .= "// AUTO-GENERATED CONFIG - JANGAN DIEDIT MANUAL\n";
    $config_content .= "// Diupdate: " . date('Y-m-d H:i:s') . "\n";
    $config_content .= "// ============================================\n\n";
    
    foreach ($settings as $key => $value) {
        $safe_value = addslashes($value);
        $config_content .= "define('SITE_" . strtoupper($key) . "', '{$safe_value}');\n";
    }
    
    $config_content .= "\n// Website URL (auto-detect)\n";
    $config_content .= "define('SITE_URL', 'http' . (isset(\$_SERVER['HTTPS']) ? 's' : '') . '://' . \$_SERVER['HTTP_HOST'] . rtrim(dirname(\$_SERVER['SCRIPT_NAME']), '/\\\\') . '/');\n";
    
    // Write to config file (pastikan folder writable)
    $config_path = realpath(__DIR__ . '/../config.php');
    if ($config_path && is_writable($config_path)) {
        file_put_contents($config_path, $config_content);
    }
}

// ============================================
// AMBIL DATA SETTINGS
// ============================================
$settings = [];
$result = mysqli_query($conn, "SELECT key_name, value, description FROM settings ORDER BY category, sort_order");
while($row = mysqli_fetch_assoc($result)) {
    $settings[$row['key_name']] = $row['value'];
}

// Group settings by category for better organization
$settings_by_category = [];
mysqli_data_seek($result, 0);
while($row = mysqli_fetch_assoc($result)) {
    $category = $row['category'] ?? 'general';
    if (!isset($settings_by_category[$category])) {
        $settings_by_category[$category] = [];
    }
    $settings_by_category[$category][] = $row;
}

// Helper: Format label dari key_name
function formatLabel($key) {
    $labels = [
        'site_name' => 'Nama Website',
        'site_description' => 'Deskripsi Website',
        'whatsapp_number' => 'Nomor WhatsApp',
        'instagram_slime' => 'Instagram Vij Slimee',
        'instagram_kpop' => 'Instagram Aprpiejise',
        'tiktok_slime' => 'TikTok Vij Slimee',
        'tiktok_kpop' => 'TikTok Aprpiejise',
        'shipping_samarinda' => 'Ongkir Samarinda',
        'shipping_balikpapan' => 'Ongkir Balikpapan',
        'shipping_jakarta' => 'Ongkir Jakarta',
        'shipping_default' => 'Ongkir Default',
        'free_shipping_min' => 'Minimal Free Shipping',
        'bank_name' => 'Nama Bank',
        'bank_account' => 'Nomor Rekening',
        'bank_account_name' => 'Atas Nama Rekening',
        'ewallet_dana' => 'DANA',
        'ewallet_ovo' => 'OVO',
        'ewallet_gopay' => 'GoPay',
        'maintenance_mode' => 'Mode Maintenance',
        'order_prefix' => 'Prefix Nomor Order',
        'currency_symbol' => 'Simbol Mata Uang',
    ];
    return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
}

// Helper: Get input type based on key
function getInputType($key) {
    $types = [
        'whatsapp_number' => 'tel',
        'instagram_slime' => 'url',
        'instagram_kpop' => 'url',
        'tiktok_slime' => 'url',
        'tiktok_kpop' => 'url',
        'shipping_samarinda' => 'number',
        'shipping_balikpapan' => 'number',
        'shipping_jakarta' => 'number',
        'shipping_default' => 'number',
        'free_shipping_min' => 'number',
        'bank_account' => 'text',
        'ewallet_dana' => 'tel',
        'ewallet_ovo' => 'tel',
        'ewallet_gopay' => 'tel',
    ];
    return $types[$key] ?? 'text';
}

// Helper: Get placeholder text
function getPlaceholder($key) {
    $placeholders = [
        'site_name' => 'Contoh: Vij Slimee & Aprpiejise',
        'site_description' => 'Deskripsi singkat tentang toko Anda...',
        'whatsapp_number' => '6281234567890',
        'instagram_slime' => 'https://instagram.com/vijslimee',
        'instagram_kpop' => 'https://instagram.com/aprpiejise',
        'shipping_samarinda' => '10000',
        'bank_account' => '1234567890',
        'ewallet_dana' => '081234567890',
    ];
    return $placeholders[$key] ?? '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin</title>
    
    <!-- CSS Eksternal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Admin Global -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- CSS Khusus Settings -->
    <style>
        :root {
            --pink-coral: #FFB6C1;
            --pink-tua: #FF69B4;
            --tosca: #40E0D0;
            --tosca-muda: #7FFFD4;
            --kuning: #FFD700;
            --kuning-coral: #FFA500;
            --biru-dongker: #000080;
            --abu-abu: #808080;
            --white: #ffffff;
            --success: #10B981;
            --danger: #EF4444;
        }
        
        /* ===== ANIMASI ===== */
        .settings-section {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .settings-section:nth-child(1) { animation-delay: 0.1s; }
        .settings-section:nth-child(2) { animation-delay: 0.2s; }
        .settings-section:nth-child(3) { animation-delay: 0.3s; }
        .settings-section:nth-child(4) { animation-delay: 0.4s; }
        
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ===== CARD SETTINGS ===== */
        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 8px 30px rgba(255, 182, 193, 0.15);
            border: 1px solid rgba(255, 182, 193, 0.2);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        .settings-card:hover {
            box-shadow: 0 12px 40px rgba(255, 182, 193, 0.25);
            transform: translateY(-3px);
        }
        
        .settings-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 2px dashed var(--pink-coral);
        }
        
        .settings-card-header i {
            color: var(--pink-tua);
            font-size: 1.3rem;
        }
        
        .settings-card-header h5 {
            color: var(--biru-dongker);
            font-weight: 700;
            font-size: 1.1rem;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }
        
        /* ===== FORM ELEMENTS ===== */
        .form-label {
            font-weight: 600;
            color: var(--biru-dongker);
            margin-bottom: 7px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-label .hint {
            font-weight: 400;
            color: var(--abu-abu);
            font-size: 0.8rem;
            margin-left: auto;
        }
        
        .form-control, .form-select {
            border: 2px solid var(--pink-coral);
            border-radius: 12px;
            padding: 11px 14px;
            transition: all 0.25s ease;
            font-size: 0.93rem;
            background: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--tosca);
            box-shadow: 0 0 0 4px rgba(64, 224, 208, 0.15);
            outline: none;
        }
        
        .form-control.is-valid {
            border-color: var(--success);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2310B981' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.5rem) center;
            background-size: calc(0.75em + 1rem) calc(0.75em + 1rem);
        }
        
        .form-control.is-invalid {
            border-color: var(--danger);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23EF4444'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23EF4444' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.5rem) center;
            background-size: calc(0.75em + 1rem) calc(0.75em + 1rem);
        }
        
        .form-text {
            font-size: 0.78rem;
            color: var(--abu-abu);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .form-text i {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        /* ===== TOGGLE SWITCH ===== */
        .form-check.form-switch .form-check-input {
            width: 3em;
            height: 1.6em;
            border: 2px solid var(--pink-coral);
        }
        
        .form-check.form-switch .form-check-input:checked {
            background-color: var(--pink-tua);
            border-color: var(--pink-tua);
        }
        
        .form-check.form-switch .form-check-label {
            font-weight: 600;
            color: var(--biru-dongker);
            cursor: pointer;
            padding-left: 5px;
        }
        
        /* ===== BUTTONS ===== */
        .btn-save {
            background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua));
            border: none;
            border-radius: 50px;
            padding: 12px 35px;
            font-weight: 700;
            color: white;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 20px rgba(255, 105, 180, 0.35);
        }
        
        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(255, 105, 180, 0.5);
            color: white;
        }
        
        .btn-save:active {
            transform: translateY(-1px);
        }
        
        .btn-test {
            background: linear-gradient(135deg, var(--tosca), var(--tosca-muda));
            border: none;
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 600;
            color: white;
            font-size: 0.85rem;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(64, 224, 208, 0.35);
            color: white;
        }
        
        /* ===== ALERT MESSAGE ===== */
        .alert-custom {
            border-radius: 14px;
            border: none;
            padding: 14px 20px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
            font-size: 0.93rem;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-custom.success {
            background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
            color: #059669;
            border-left: 4px solid var(--success);
        }
        
        .alert-custom.error {
            background: linear-gradient(135deg, #FEE2E2, #FECACA);
            color: #DC2626;
            border-left: 4px solid var(--danger);
        }
        
        .alert-custom.info {
            background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
            color: #2563EB;
            border-left: 4px solid #3B82F6;
        }
        
        /* ===== CONFIG SYNC BADGE ===== */
        .config-sync-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: linear-gradient(135deg, var(--kuning), var(--kuning-coral));
            color: var(--biru-dongker);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .config-sync-badge i {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        /* ===== SECTION ICONS ===== */
        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .section-icon.general { background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua)); }
        .section-icon.shipping { background: linear-gradient(135deg, var(--tosca), var(--tosca-muda)); }
        .section-icon.payment { background: linear-gradient(135deg, var(--kuning), var(--kuning-coral)); }
        .section-icon.social { background: linear-gradient(135deg, #A78BFA, #8B5CF6); }
        .section-icon.system { background: linear-gradient(135deg, #6B7280, #4B5563); }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .settings-card {
                padding: 20px;
            }
            
            .settings-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .config-sync-badge {
                margin-left: 0;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Konten Utama -->
    <main class="modern-main">
        <!-- Header -->
        <?php include 'includes/header.php'; ?>
        
        <!-- Pesan Notifikasi -->
        <?php if ($message): ?>
        <div class="alert-custom <?php echo htmlspecialchars($message_type); ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.style.display='none'" style="background:none;border:none;font-size:1.2rem;color:inherit;opacity:0.7;"></button>
        </div>
        <?php endif; ?>
        
        <!-- Header Halaman -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h1 class="page-title mb-0" style="color: var(--biru-dongker); font-weight: 800; font-size: 1.6rem; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-cog" style="color: var(--pink-tua);"></i>
                Pengaturan Sistem
            </h1>
            <span class="config-sync-badge">
                <i class="fas fa-sync-alt"></i>
                Auto-sync ke config.php
            </span>
        </div>
        
        <form method="POST" id="settingsForm">
            
            <!-- ===== GENERAL SETTINGS ===== -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="section-icon general">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h5>Informasi Website</h5>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="setting_site_name">
                            Nama Website <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="text" name="setting_site_name" id="setting_site_name" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Vij Slimee & Aprpiejise'); ?>" 
                               placeholder="<?php echo getPlaceholder('site_name'); ?>" required>
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Nama yang tampil di header website & title browser
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_site_description">Deskripsi Website</label>
                        <input type="text" name="setting_site_description" id="setting_site_description" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['site_description'] ?? ''); ?>" 
                               placeholder="Toko slime & photocard K-Pop terpercaya">
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Untuk meta description SEO & footer
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_order_prefix">Prefix Nomor Order</label>
                        <input type="text" name="setting_order_prefix" id="setting_order_prefix" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['order_prefix'] ?? 'VJS'); ?>" 
                               placeholder="VJS" maxlength="10">
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Contoh: <strong>VJS-20250101-001</strong>
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_currency_symbol">Simbol Mata Uang</label>
                        <input type="text" name="setting_currency_symbol" id="setting_currency_symbol" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'Rp'); ?>" 
                               placeholder="Rp" maxlength="5">
                    </div>
                </div>
            </div>
            
            <!-- ===== CONTACT & SOCIAL MEDIA ===== -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="section-icon social">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h5>Kontak & Media Sosial</h5>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="setting_whatsapp_number">
                            <i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #25D366; color: white; border: none; border-radius: 12px 0 0 12px;">
                                <i class="fab fa-whatsapp"></i>
                            </span>
                            <input type="tel" name="setting_whatsapp_number" id="setting_whatsapp_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? ''); ?>" 
                                   placeholder="<?php echo getPlaceholder('whatsapp_number'); ?>"
                                   pattern="[\d+]{10,15}" title="Minimal 10 digit angka">
                        </div>
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Format: 628xxx (tanpa spasi atau tanda -)
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_instagram_slime">
                            <i class="fab fa-instagram" style="color: #E1306C;"></i> Instagram Vij Slimee
                        </label>
                        <input type="url" name="setting_instagram_slime" id="setting_instagram_slime" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['instagram_slime'] ?? ''); ?>" 
                               placeholder="<?php echo getPlaceholder('instagram_slime'); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_instagram_kpop">
                            <i class="fab fa-instagram" style="color: #E1306C;"></i> Instagram Aprpiejise
                        </label>
                        <input type="url" name="setting_instagram_kpop" id="setting_instagram_kpop" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['instagram_kpop'] ?? ''); ?>" 
                               placeholder="<?php echo getPlaceholder('instagram_kpop'); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_tiktok_slime">
                            <i class="fab fa-tiktok"></i> TikTok Vij Slimee
                        </label>
                        <input type="url" name="setting_tiktok_slime" id="setting_tiktok_slime" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['tiktok_slime'] ?? ''); ?>" 
                               placeholder="https://tiktok.com/@vijslimee">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_tiktok_kpop">
                            <i class="fab fa-tiktok"></i> TikTok Aprpiejise
                        </label>
                        <input type="url" name="setting_tiktok_kpop" id="setting_tiktok_kpop" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['tiktok_kpop'] ?? ''); ?>" 
                               placeholder="https://tiktok.com/@aprpiejise">
                    </div>
                </div>
            </div>
            
            <!-- ===== SHIPPING SETTINGS ===== -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="section-icon shipping">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h5>Pengaturan Ongkos Kirim</h5>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="setting_shipping_samarinda">📍 Samarinda</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: var(--pink-coral); color: var(--biru-dongker); border: none; border-radius: 12px 0 0 12px; font-weight: 600;">Rp</span>
                            <input type="number" name="setting_shipping_samarinda" id="setting_shipping_samarinda" class="form-control" 
                                   value="<?php echo (int)($settings['shipping_samarinda'] ?? 10000); ?>" 
                                   min="0" step="1000" placeholder="<?php echo getPlaceholder('shipping_samarinda'); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label" for="setting_shipping_balikpapan">📍 Balikpapan</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: var(--pink-coral); color: var(--biru-dongker); border: none; border-radius: 12px 0 0 12px; font-weight: 600;">Rp</span>
                            <input type="number" name="setting_shipping_balikpapan" id="setting_shipping_balikpapan" class="form-control" 
                                   value="<?php echo (int)($settings['shipping_balikpapan'] ?? 15000); ?>" 
                                   min="0" step="1000">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label" for="setting_shipping_jakarta">📍 Jakarta</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: var(--pink-coral); color: var(--biru-dongker); border: none; border-radius: 12px 0 0 12px; font-weight: 600;">Rp</span>
                            <input type="number" name="setting_shipping_jakarta" id="setting_shipping_jakarta" class="form-control" 
                                   value="<?php echo (int)($settings['shipping_jakarta'] ?? 25000); ?>" 
                                   min="0" step="1000">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_shipping_default">🌍 Ongkir Default (Luar Kota)</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: var(--pink-coral); color: var(--biru-dongker); border: none; border-radius: 12px 0 0 12px; font-weight: 600;">Rp</span>
                            <input type="number" name="setting_shipping_default" id="setting_shipping_default" class="form-control" 
                                   value="<?php echo (int)($settings['shipping_default'] ?? 20000); ?>" 
                                   min="0" step="1000">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_free_shipping_min">🎁 Minimal Free Shipping</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: var(--kuning); color: var(--biru-dongker); border: none; border-radius: 12px 0 0 12px; font-weight: 600;">Rp</span>
                            <input type="number" name="setting_free_shipping_min" id="setting_free_shipping_min" class="form-control" 
                                   value="<?php echo (int)($settings['free_shipping_min'] ?? 100000); ?>" 
                                   min="0" step="10000">
                        </div>
                        <small class="form-text">
                            <i class="fas fa-gift"></i> Pembelian di atas ini gratis ongkir
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- ===== PAYMENT SETTINGS ===== -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="section-icon payment">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h5>Metode Pembayaran</h5>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="setting_bank_name">🏦 Nama Bank</label>
                        <input type="text" name="setting_bank_name" id="setting_bank_name" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['bank_name'] ?? 'BCA'); ?>" 
                               placeholder="Contoh: BCA, Mandiri, BNI">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_bank_account">🔢 Nomor Rekening</label>
                        <input type="text" name="setting_bank_account" id="setting_bank_account" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['bank_account'] ?? ''); ?>" 
                               placeholder="<?php echo getPlaceholder('bank_account'); ?>"
                               pattern="\d{8,20}" title="8-20 digit angka">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_bank_account_name">👤 Atas Nama Rekening</label>
                        <input type="text" name="setting_bank_account_name" id="setting_bank_account_name" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['bank_account_name'] ?? ''); ?>" 
                               placeholder="Nama pemilik rekening">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_ewallet_dana">
                            <i class="fas fa-wallet" style="color: #118EEA;"></i> DANA
                        </label>
                        <input type="tel" name="setting_ewallet_dana" id="setting_ewallet_dana" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['ewallet_dana'] ?? ''); ?>" 
                               placeholder="08xxxxxxxxxx">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_ewallet_ovo">
                            <i class="fas fa-wallet" style="color: #4C3494;"></i> OVO
                        </label>
                        <input type="tel" name="setting_ewallet_ovo" id="setting_ewallet_ovo" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['ewallet_ovo'] ?? ''); ?>" 
                               placeholder="08xxxxxxxxxx">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="setting_ewallet_gopay">
                            <i class="fas fa-wallet" style="color: #00AA13;"></i> GoPay
                        </label>
                        <input type="tel" name="setting_ewallet_gopay" id="setting_ewallet_gopay" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['ewallet_gopay'] ?? ''); ?>" 
                               placeholder="08xxxxxxxxxx">
                    </div>
                </div>
            </div>
            
            <!-- ===== SYSTEM SETTINGS ===== -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="section-icon system">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h5>Pengaturan Sistem</h5>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   name="setting_maintenance_mode" id="setting_maintenance_mode"
                                   <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="setting_maintenance_mode">
                                🔧 Mode Maintenance
                            </label>
                        </div>
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Website akan menampilkan halaman "Sedang dalam perbaikan"
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   name="setting_enable_whatsapp_order" id="setting_enable_whatsapp_order"
                                   <?php echo ($settings['enable_whatsapp_order'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="setting_enable_whatsapp_order">
                                💬 Order via WhatsApp
                            </label>
                        </div>
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Tampilkan tombol order WA di halaman produk
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   name="setting_show_stock" id="setting_show_stock"
                                   <?php echo ($settings['show_stock'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="setting_show_stock">
                                📦 Tampilkan Stok Produk
                            </label>
                        </div>
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Jika dimatikan, stok tidak tampil di frontend
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   name="setting_enable_reviews" id="setting_enable_reviews"
                                   <?php echo ($settings['enable_reviews'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="setting_enable_reviews">
                                ⭐ Ulasan Produk
                            </label>
                        </div>
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Izinkan pelanggan memberi rating & review
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- ===== SAVE BUTTON ===== -->
            <div class="d-flex justify-content-end gap-3 mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()" style="background: rgba(128,128,128,0.12); border: none; border-radius: 50px; padding: 12px 28px; font-weight: 600; color: var(--abu-abu); font-size: 0.93rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" name="update_settings" class="btn-save">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
        
        <!-- Footer -->
        <footer class="text-center text-muted py-4" style="font-size: 0.85rem;">
            <p class="mb-0">
                © <?php echo date('Y'); ?> <strong>Vij Slimee & Aprpiejise</strong> • Panel Admin
            </p>
        </footer>
    </main>
    
    <!-- JS Eksternal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JS Khusus Settings -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Real-time validation for WhatsApp number
            const whatsappInput = document.getElementById('setting_whatsapp_number');
            if (whatsappInput) {
                whatsappInput.addEventListener('blur', function() {
                    const digits = this.value.replace(/\D/g, '');
                    if (digits.length >= 10 && digits.length <= 15) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                });
            }
            
            // Format phone numbers with spaces for readability (visual only)
            document.querySelectorAll('input[type="tel"]').forEach(input => {
                input.addEventListener('input', function() {
                    // Only format for display, submit will remove non-digits
                    let value = this.value.replace(/\D/g, '');
                    if (value.startsWith('62')) {
                        // Format: 62 8xx xxxx xxxx
                        value = value.replace(/(\d{2})(\d{3})(\d{4})(\d{4})/, '$1 $2 $3 $4');
                    }
                    // Don't set value here to avoid cursor issues, just for reference
                });
            });
            
            // Auto-format currency inputs on blur
            document.querySelectorAll('input[type="number"][name^="setting_shipping"], input[name="setting_free_shipping_min"]').forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value) {
                        const num = parseInt(this.value) || 0;
                        // Store raw value in data attribute for form submit
                        this.dataset.rawValue = num;
                        // Show formatted for user
                        this.value = num.toLocaleString('id-ID');
                    }
                });
                
                // Convert back to raw number on focus
                input.addEventListener('focus', function() {
                    if (this.dataset.rawValue) {
                        this.value = this.dataset.rawValue;
                    }
                });
            });
            
            // Form submit: convert formatted values back to raw numbers
            document.getElementById('settingsForm')?.addEventListener('submit', function(e) {
                document.querySelectorAll('input[type="number"][name^="setting_shipping"], input[name="setting_free_shipping_min"]').forEach(input => {
                    if (input.dataset.rawValue) {
                        input.value = input.dataset.rawValue;
                    } else {
                        input.value = input.value.replace(/\D/g, '');
                    }
                });
                
                // Convert WhatsApp to digits only
                const whatsapp = document.getElementById('setting_whatsapp_number');
                if (whatsapp) {
                    whatsapp.value = whatsapp.value.replace(/\D/g, '');
                }
            });
            
            // Toggle sidebar untuk mobile
            const toggleBtn = document.querySelector('.toggle-sidebar');
            const sidebar = document.querySelector('.modern-sidebar');
            
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('show');
                });
            }
            
            // Tutup sidebar ketika klik di luar (mobile)
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024 && 
                    sidebar?.classList.contains('show') && 
                    !sidebar.contains(e.target) && 
                    !toggleBtn?.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            });
            
            // Auto-hide alert setelah 5 detik
            const alerts = document.querySelectorAll('.alert-custom');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
            
            // Visual feedback on save
            const saveBtn = document.querySelector('.btn-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function() {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    this.disabled = true;
                    
                    // Re-enable after 3 seconds (in case form doesn't submit)
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-save"></i> Simpan Perubahan';
                        this.disabled = false;
                    }, 3000);
                });
            }
        });
    </script>
</body>
</html>