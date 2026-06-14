<?php
require_once '../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required!';
    } elseif (login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username or password is incorrect!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Vij Slimee & Aprpiejise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Quicksand:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --pink-coral: #FFB6C1; --pink-tua: #FF69B4; --kuning: #FFD700;
            --tosca: #40E0D0; --biru-dongker: #000080; --abu-abu: #808080;
            --purple-primary: #ff71ba; --purple-dark: #ffd859;
        }
        body {
            background: linear-gradient(135deg, #FFF5F7 0%, #F0FFFF 50%, #FFFBE6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Quicksand', sans-serif;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(139, 92, 246, 0.2);
            padding: 45px 40px;
            max-width: 420px;
            width: 100%;
            border-left: 5px solid var(--purple-primary);
        }
        .brand-logo {
            font-size: 2rem;
            font-weight: 800;
            color: var(--biru-dongker);
            text-align: center;
            margin-bottom: 8px;
            font-family: 'Poppins', sans-serif;
        }
        .brand-subtitle {
            text-align: center;
            color: var(--abu-abu);
            margin-bottom: 25px;
            font-size: 0.9rem;
        }
        .form-label {
            font-weight: 600;
            color: var(--biru-dongker);
            font-size: 0.95rem;
        }
        .form-control {
            border: 2px solid var(--pink-coral);
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: var(--purple-primary);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, var(--purple-primary), var(--purple-dark));
            border: none;
            padding: 14px;
            font-weight: 700;
            border-radius: 50px;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
            color: white;
        }
        .alert-error {
            background: linear-gradient(135deg, #FEE2E2, #FECACA);
            border: 2px solid #DC2626;
            color: #DC2626;
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .default-cred {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
            color: var(--abu-abu);
        }
        .default-cred code {
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 5px;
            color: var(--pink-tua);
            font-weight: 600;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--pink-tua) !important;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover { color: var(--kuning-coral) !important; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-3">
            <div class="brand-logo">Admin Panel</div>
            <p class="brand-subtitle">Vij Slimee & Aprpiejise</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="form-label"><i class="fas fa-user me-2" style="color:var(--pink-tua)"></i>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label"><i class="fas fa-lock me-2" style="color:var(--tosca)"></i>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
            </button>
        </form>
        
        <div class="default-cred">
            Default: <code>admin</code> / <code>admin123</code>
        </div>
        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left me-1"></i>Back to Website
        </a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>