<?php
// ============================================
// PROFIL ADMIN - Vij Slimee & Aprpiejise
// Tema: Gradient Pink + Kuning-Coral
// Bahasa: Indonesia
// Keamanan: Prepared Statements + Password Hashing + File Upload
// ============================================

// ✅ PENTING: Include dulu sebelum pakai variabel
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Profil Saya - Admin';

// ✅ Get database connection dengan error checking
$conn = getDBConnection();
if (!$conn) {
    error_log("Database connection failed in profile.php");
    die('❌ Koneksi database gagal. Silakan hubungi administrator.');
}

$admin_id = $_SESSION['admin_id'] ?? 0;
$message = '';
$message_type = '';

// ============================================
// KONFIGURASI UPLOAD AVATAR
// ============================================
$upload_dir = '../uploads/avatars/';
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
$max_size = 2 * 1024 * 1024; // 2MB

// Pastikan folder upload ada
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ============================================
// HANDLE UPDATE PROFIL
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = filter_var(sanitize($_POST['email']), FILTER_VALIDATE_EMAIL);
    $phone = preg_replace('/[^\d+]/', '', sanitize($_POST['phone']));
    
    // Handle avatar upload
    $avatar_filename = null;
    $old_avatar = sanitize($_POST['old_avatar'] ?? '');
    
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar_file'];
        $file_type = mime_content_type($file['tmp_name']);
        $file_size = $file['size'];
        
        // Validasi tipe file
        if (!in_array($file_type, $allowed_types)) {
            $message = '❌ Tipe file avatar tidak diizinkan. Hanya JPG, PNG, dan Webp.';
            $message_type = 'error';
        }
        // Validasi ukuran file
        elseif ($file_size > $max_size) {
            $message = '❌ Ukuran avatar terlalu besar. Maksimal 2MB.';
            $message_type = 'error';
        }
        else {
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $avatar_filename = 'avatar_' . $admin_id . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . $avatar_filename;
            
            // Upload file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Hapus avatar lama jika ada
                if (!empty($old_avatar) && file_exists($upload_dir . $old_avatar)) {
                    unlink($upload_dir . $old_avatar);
                }
            } else {
                $message = '❌ Gagal upload avatar.';
                $message_type = 'error';
            }
        }
    }
    
    // Jika tidak ada upload baru, gunakan avatar lama
    if (empty($avatar_filename) && empty($message)) {
        $avatar_filename = $old_avatar;
    }
    
    // Validasi data
    $errors = [];
    
    if (empty($name) || strlen($name) < 3) {
        $errors[] = 'Nama minimal 3 karakter';
    }
    
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }
    
    if ($phone && strlen(preg_replace('/\D/', '', $phone)) < 10) {
        $errors[] = 'Nomor telepon minimal 10 digit';
    }
    
    if (empty($errors) && empty($message)) {
        // ✅ Prepare statement dengan error checking
        $query = "UPDATE admins SET name = ?, email = ?, phone = ?, avatar = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            $message = '❌ Query error: ' . mysqli_error($conn);
            $message_type = 'error';
            error_log("Profile update prepare failed: " . mysqli_error($conn));
        } else {
            mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $phone, $avatar_filename, $admin_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // ✅ Update session dengan data terbaru (PENTING untuk header/sidebar!)
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_email'] = $email;
                $_SESSION['admin_phone'] = $phone;
                if (!empty($avatar_filename)) {
                    $_SESSION['admin_avatar'] = $avatar_filename;
                }
                
                $message = '✅ Profil berhasil diperbarui!';
                $message_type = 'success';
                
                // ✅ Redirect dengan timestamp untuk hindari cache browser
                header('Location: profile.php?updated=' . time());
                exit;
            } else {
                $message = '❌ Gagal update: ' . mysqli_stmt_error($stmt);
                $message_type = 'error';
                error_log("Profile update execute failed: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }
    } elseif (empty($message)) {
        $message = '❌ ' . implode(', ', $errors);
        $message_type = 'error';
    }
}

// ============================================
// HANDLE GANTI PASSWORD
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validasi password lama
    $stmt = mysqli_prepare($conn, "SELECT password FROM admins WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$admin || !password_verify($current_password, $admin['password'])) {
            $errors[] = 'Password saat ini tidak sesuai';
        }
    }
    
    // Validasi password baru
    if (strlen($new_password) < 8) {
        $errors[] = 'Password baru minimal 8 karakter';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak sama';
    }
    
    if ($new_password === $current_password) {
        $errors[] = 'Password baru tidak boleh sama dengan password lama';
    }
    
    if (empty($errors)) {
        // Hash & update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE admins SET password = ?, updated_at = NOW() WHERE id = ?");
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $admin_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = '✅ Password berhasil diubah! Silakan login ulang untuk keamanan.';
                $message_type = 'success';
                
                // Logout otomatis setelah ganti password
                session_destroy();
                header('Refresh: 3; URL=login.php');
                exit;
            } else {
                $message = '❌ Gagal mengubah password';
                $message_type = 'error';
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    if (!empty($errors)) {
        $message = '❌ ' . implode(', ', $errors);
        $message_type = 'error';
    }
}

// ============================================
// AMBIL DATA PROFIL ADMIN
// ============================================
$stmt = mysqli_prepare($conn, "SELECT id, username, name, email, phone, avatar, role, created_at, last_login FROM admins WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    mysqli_stmt_execute($stmt);
    $profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
} else {
    $profile = null;
}

// ✅ Default avatar dengan cache-busting
$avatar_url = '';
if (!empty($profile['avatar']) && file_exists($upload_dir . $profile['avatar'])) {
    $avatar_url = $upload_dir . $profile['avatar'] . '?v=' . filemtime($upload_dir . $profile['avatar']);
} else {
    $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($profile['name'] ?? 'A') . '&background=FFB6C1&color=000080&size=128';
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
    
    <!-- CSS Khusus Profile -->
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
        .profile-card {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .profile-card:nth-child(1) { animation-delay: 0.1s; }
        .profile-card:nth-child(2) { animation-delay: 0.2s; }
        
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ===== PROFILE HEADER CARD ===== */
        .profile-header {
            background: linear-gradient(135deg, var(--pink-coral), var(--pink-tua), var(--kuning-coral));
            background-size: 200% 200%;
            animation: gradientFlow 15s ease infinite;
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 25px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(255, 105, 180, 0.3);
        }
        
        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .profile-avatar-wrapper {
            position: relative;
            display: inline-block;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            background: white;
        }
        
        .profile-avatar-edit {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: white;
            color: var(--pink-tua);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .profile-avatar-edit:hover {
            transform: scale(1.1);
            background: var(--pink-tua);
            color: white;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 15px 0 5px 0;
            font-family: 'Poppins', sans-serif;
        }
        
        .profile-username {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .profile-role {
            display: inline-block;
            padding: 5px 14px;
            background: rgba(255,255,255,0.25);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .profile-stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .profile-stat {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            opacity: 0.95;
        }
        
        .profile-stat i {
            font-size: 1rem;
        }
        
        /* ===== PROFILE CARDS ===== */
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 8px 30px rgba(255, 182, 193, 0.15);
            border: 1px solid rgba(255, 182, 193, 0.2);
            margin-bottom: 25px;
        }
        
        .profile-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 2px dashed var(--pink-coral);
        }
        
        .profile-card-header i {
            color: var(--pink-tua);
            font-size: 1.3rem;
        }
        
        .profile-card-header h5 {
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
        
        .form-label .required {
            color: var(--danger);
        }
        
        .form-control {
            border: 2px solid var(--pink-coral);
            border-radius: 12px;
            padding: 11px 14px;
            transition: all 0.25s ease;
            font-size: 0.93rem;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--tosca);
            box-shadow: 0 0 0 4px rgba(64, 224, 208, 0.15);
            outline: none;
        }
        
        .form-control.is-valid {
            border-color: var(--success);
        }
        
        .form-control.is-invalid {
            border-color: var(--danger);
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
        
        /* ===== IMAGE UPLOAD ===== */
        .image-upload-wrapper {
            border: 2px dashed var(--pink-coral);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            background: rgba(255, 182, 193, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            display: block;
        }
        
        .image-upload-wrapper:hover {
            border-color: var(--tosca);
            background: rgba(64, 224, 208, 0.08);
        }
        
        .image-upload-wrapper i {
            font-size: 2.5rem;
            color: var(--pink-tua);
            margin-bottom: 10px;
        }
        
        .image-upload-wrapper input[type="file"] {
            display: none;
        }
        
        .image-preview-container {
            margin-top: 15px;
            text-align: center;
        }
        
        .image-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 12px;
            border: 2px solid var(--pink-coral);
            margin: 0 auto;
            display: none;
        }
        
        .image-preview.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .current-avatar {
            margin-top: 10px;
            text-align: center;
        }
        
        .current-avatar img {
            max-width: 120px;
            border-radius: 50%;
            border: 2px solid var(--pink-coral);
        }
        
        /* ===== PASSWORD STRENGTH ===== */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: #e0e0e0;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .password-strength-bar.weak { width: 33%; background: var(--danger); }
        .password-strength-bar.medium { width: 66%; background: var(--warning); }
        .password-strength-bar.strong { width: 100%; background: var(--success); }
        
        .password-hints {
            font-size: 0.75rem;
            color: var(--abu-abu);
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .password-hint {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .password-hint.valid {
            color: var(--success);
        }
        
        .password-hint.invalid {
            color: var(--danger);
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
        
        .btn-cancel {
            background: rgba(128, 128, 128, 0.12);
            border: none;
            border-radius: 50px;
            padding: 12px 28px;
            font-weight: 600;
            color: var(--abu-abu);
            transition: all 0.25s ease;
            font-size: 0.93rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-cancel:hover {
            background: rgba(128, 128, 128, 0.22);
            color: var(--biru-dongker);
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
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .profile-header {
                text-align: center;
                padding: 25px 20px;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .profile-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- ✅ Sidebar Included -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- ✅ Main Content -->
    <main class="modern-main">
        <!-- ✅ Header Included -->
        <?php include 'includes/header.php'; ?>
        
        <!-- Pesan Notifikasi -->
        <?php if ($message): ?>
        <div class="alert-custom <?php echo htmlspecialchars($message_type); ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.style.display='none'" style="background:none;border:none;font-size:1.2rem;color:inherit;opacity:0.7;"></button>
        </div>
        <?php endif; ?>
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-wrapper">
                <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar" class="profile-avatar" id="currentAvatar">
                <label for="avatar_file" class="profile-avatar-edit" title="Ubah avatar">
                    <i class="fas fa-camera"></i>
                </label>
            </div>
            <h2 class="profile-name"><?php echo htmlspecialchars($profile['name'] ?? $profile['username']); ?></h2>
            <p class="profile-username">@<?php echo htmlspecialchars($profile['username']); ?></p>
            <span class="profile-role">
                <i class="fas fa-crown me-1"></i>
                <?php echo ucfirst($profile['role'] ?? 'Admin'); ?>
            </span>
            <div class="profile-stats">
                <span class="profile-stat">
                    <i class="fas fa-calendar"></i>
                    Bergabung: <?php echo date('d M Y', strtotime($profile['created_at'] ?? 'now')); ?>
                </span>
                <span class="profile-stat">
                    <i class="fas fa-clock"></i>
                    Login terakhir: <?php echo $profile['last_login'] ? date('d M Y H:i', strtotime($profile['last_login'])) : '-'; ?>
                </span>
            </div>
        </div>
        
        <form method="POST" id="profileForm" enctype="multipart/form-data">
            
            <!-- ===== EDIT PROFIL ===== -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="fas fa-user-edit"></i>
                    <h5>Edit Informasi Pribadi</h5>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" 
                               placeholder="Nama lengkap Anda" required minlength="3">
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Minimal 3 karakter
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($profile['username']); ?>" readonly style="background: #f8f9fa; cursor: not-allowed;">
                        <small class="form-text">
                            <i class="fas fa-lock"></i> Username tidak dapat diubah
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" 
                               placeholder="email@contoh.com">
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Untuk notifikasi & recovery
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Nomor Telepon / WhatsApp</label>
                        <input type="tel" name="phone" id="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" 
                               placeholder="6281234567890" pattern="[\d+]{10,15}">
                        <small class="form-text">
                            <i class="fab fa-whatsapp"></i> Format: 628xxx (tanpa spasi)
                        </small>
                    </div>
                    
                    <!-- Upload Avatar -->
                    <div class="col-12">
                        <label class="form-label">Avatar Profil</label>
                        <label class="image-upload-wrapper" for="avatar_file">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <div>Klik untuk upload avatar</div>
                            <small class="text-muted">JPG, PNG, Webp (Max 2MB)</small>
                            <input type="file" name="avatar_file" id="avatar_file" accept="image/*" onchange="previewAvatar(this)">
                        </label>
                        
                        <!-- Preview gambar baru -->
                        <div class="image-preview-container">
                            <img id="avatarPreview" class="image-preview" alt="Preview Avatar">
                        </div>
                        
                        <!-- Avatar lama (jika ada) -->
                        <?php if (!empty($profile['avatar']) && file_exists($upload_dir . $profile['avatar'])): ?>
                        <div class="current-avatar">
                            <small class="text-muted d-block mb-2">Avatar saat ini:</small>
                            <img src="<?php echo htmlspecialchars($upload_dir . $profile['avatar'] . '?v=' . time()); ?>" 
                                 alt="Current Avatar" 
                                 class="img-thumbnail">
                        </div>
                        <?php endif; ?>
                        
                        <input type="hidden" name="old_avatar" id="old_avatar" value="<?php echo htmlspecialchars($profile['avatar'] ?? ''); ?>">
                        <small class="form-text">
                            <i class="fas fa-info-circle"></i> Avatar lama akan diganti jika upload baru
                        </small>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end gap-3 mt-4">
                    <button type="button" class="btn-cancel" onclick="history.back()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
            
        </form>
        
        <!-- ===== GANTI PASSWORD ===== -->
        <div class="profile-card">
            <div class="profile-card-header">
                <i class="fas fa-lock"></i>
                <h5>Ganti Password</h5>
            </div>
            
            <form method="POST" id="passwordForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="current_password">Password Saat Ini <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('current_password')" style="border-radius: 0 12px 12px 0;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-6"></div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="new_password">Password Baru <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8" oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password')" style="border-radius: 0 12px 12px 0;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-hints">
                            <span class="password-hint" id="hint-length">
                                <i class="fas fa-circle"></i> Minimal 8 karakter
                            </span>
                            <span class="password-hint" id="hint-upper">
                                <i class="fas fa-circle"></i> Huruf besar
                            </span>
                            <span class="password-hint" id="hint-number">
                                <i class="fas fa-circle"></i> Angka
                            </span>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="confirm_password">Konfirmasi Password Baru <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required oninput="checkPasswordMatch()">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')" style="border-radius: 0 12px 12px 0;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-text" id="matchHint"></small>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end gap-3 mt-4">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('passwordForm').reset()">
                        <i class="fas fa-rotate-left"></i> Reset
                    </button>
                    <button type="submit" name="change_password" class="btn-save" style="background: linear-gradient(135deg, var(--tosca), var(--tosca-muda));">
                        <i class="fas fa-key"></i> Ganti Password
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <footer class="text-center text-muted py-4" style="font-size: 0.85rem;">
            <p class="mb-0">
                © <?php echo date('Y'); ?> <strong>Vij Slimee & Aprpiejise</strong> • Panel Admin
            </p>
        </footer>
    </main>
    
    <!-- JS Eksternal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JS Khusus Profile -->
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Preview avatar dari file upload
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Preview di header
                    document.getElementById('currentAvatar').src = e.target.result;
                    // Preview di form
                    const preview = document.getElementById('avatarPreview');
                    preview.src = e.target.result;
                    preview.classList.add('show');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Check password strength
        function checkPasswordStrength(password) {
            const bar = document.getElementById('strengthBar');
            const hints = {
                length: document.getElementById('hint-length'),
                upper: document.getElementById('hint-upper'),
                number: document.getElementById('hint-number')
            };
            
            let score = 0;
            
            if (password.length >= 8) {
                hints.length.classList.add('valid');
                hints.length.classList.remove('invalid');
                score++;
            } else {
                hints.length.classList.remove('valid');
                hints.length.classList.add('invalid');
            }
            
            if (/[A-Z]/.test(password)) {
                hints.upper.classList.add('valid');
                hints.upper.classList.remove('invalid');
                score++;
            } else {
                hints.upper.classList.remove('valid');
                hints.upper.classList.add('invalid');
            }
            
            if (/\d/.test(password)) {
                hints.number.classList.add('valid');
                hints.number.classList.remove('invalid');
                score++;
            } else {
                hints.number.classList.remove('valid');
                hints.number.classList.add('invalid');
            }
            
            bar.className = 'password-strength-bar';
            if (score === 3) {
                bar.classList.add('strong');
            } else if (score === 2) {
                bar.classList.add('medium');
            } else if (score >= 1) {
                bar.classList.add('weak');
            }
        }
        
        // Check password match
        function checkPasswordMatch() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            const hint = document.getElementById('matchHint');
            
            if (confirmPass && newPass !== confirmPass) {
                hint.textContent = '❌ Password tidak sama';
                hint.style.color = 'var(--danger)';
                document.getElementById('confirm_password').classList.add('is-invalid');
            } else if (confirmPass && newPass === confirmPass) {
                hint.textContent = '✅ Password sama';
                hint.style.color = 'var(--success)';
                document.getElementById('confirm_password').classList.remove('is-invalid');
                document.getElementById('confirm_password').classList.add('is-valid');
            } else {
                hint.textContent = '';
                document.getElementById('confirm_password').classList.remove('is-valid', 'is-invalid');
            }
        }
        
        // Form validation
        document.getElementById('profileForm')?.addEventListener('submit', function(e) {
            const phone = document.getElementById('phone');
            if (phone.value && phone.value.replace(/\D/g, '').length < 10) {
                e.preventDefault();
                phone.classList.add('is-invalid');
                alert('⚠️ Nomor telepon minimal 10 digit');
            }
        });
        
        document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('⚠️ Password baru dan konfirmasi tidak sama');
            }
        });
        
        // Toggle sidebar untuk mobile
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.querySelector('.toggle-sidebar');
            const sidebar = document.querySelector('.modern-sidebar');
            
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('show');
                });
            }
            
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024 && 
                    sidebar?.classList.contains('show') && 
                    !sidebar.contains(e.target) && 
                    !toggleBtn?.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            });
            
            // Auto-hide alert
            const alerts = document.querySelectorAll('.alert-custom');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>