<?php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get database connection
 */
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = @mysqli_connect('localhost', 'root', '', 'vijs_db');
        if (!$conn) {
            error_log("DB Error: " . mysqli_connect_error());
            return false;
        }
        mysqli_set_charset($conn, "utf8mb4");
    }
    return $conn;
}

/**
 * Check if admin is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id'], $_SESSION['admin_username']) 
        && $_SESSION['admin_id'] > 0;
}

/**
 * Redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Login function
 */
function login($username, $password) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $username = trim($username);
    if (empty($username) || empty($password)) return false;
    
    // Query admin
    $sql = "SELECT id, username, password, name, role FROM admins 
            WHERE username = ? AND is_active = 1 LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) return false;
    
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) === 1) {
        $admin = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $admin['password'])) {
            // Set session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
            
            // Update last login
            mysqli_query($conn, "UPDATE admins SET last_login = NOW() WHERE id = {$admin['id']}");
            
            mysqli_stmt_close($stmt);
            return true;
        }
    }
    
    mysqli_stmt_close($stmt);
    return false;
}

/**
 * Logout function
 */
function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        @setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"]);
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Get current admin
 */
function getCurrentAdmin() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'name' => $_SESSION['admin_name'],
        'role' => $_SESSION['admin_role'],
    ];
}

?>