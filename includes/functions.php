<?php

// ============================================
//  DATABASE CONNECTION - AUTO SAFE
// ============================================
function getSafeDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = @mysqli_connect('localhost', 'root', '', 'vijs_db');
        if (!$conn) {
            error_log("DB Connection failed: " . mysqli_connect_error());
            return false;
        }
        mysqli_set_charset($conn, "utf8mb4");
    }
    return $conn;
}

function getProductTypeLabel($type) {
    $labels = [
        'slime' => 'Vij Slimee',
        'photocard' => 'Aprpiejise',
        'both' => 'Vij Slimee & Aprpiejise'
    ];
    return $labels[$type] ?? 'Semua Produk';
}

// ============================================
//  CART FUNCTIONS (SESSION BASED)
// ============================================
function getCart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function getCartCount() {
    $count = 0;
    foreach (getCart() as $item) {
        $count += $item['qty'];
    }
    return $count;
}

function getCartTotal() {
    $total = 0;
    foreach (getCart() as $item) {
        $total += $item['price'] * $item['qty'];
    }
    return $total;
}

function addToCart($product_id, $qty = 1) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    $product_id = (int)$product_id;
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? AND is_active = 1 AND stock >= ?");
    mysqli_stmt_bind_param($stmt, "ii", $product_id, $qty);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$product_id] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'type' => $product['product_type'],
            'qty' => $qty,
            'stock' => $product['stock'],
            'sku' => $product['sku']
        ];
    }
    return true;
}

function updateCartQty($product_id, $qty) {
    if ($qty <= 0) {
        removeFromCart($product_id);
        return;
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['qty'] = min($qty, $_SESSION['cart'][$product_id]['stock']);
    }
}

function removeFromCart($product_id) {
    unset($_SESSION['cart'][$product_id]);
}

function clearCart() {
    $_SESSION['cart'] = [];
}

// ============================================
//  WISHLIST FUNCTIONS
// ============================================
function getWishlist() {
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    return $_SESSION['wishlist'];
}

function addToWishlist($product_id) {
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    if (!in_array($product_id, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = (int)$product_id;
        return true;
    }
    return false;
}

function removeFromWishlist($product_id) {
    $key = array_search((int)$product_id, $_SESSION['wishlist'] ?? []);
    if ($key !== false) {
        unset($_SESSION['wishlist'][$key]);
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
    }
}

function isInWishlist($product_id) {
    return in_array((int)$product_id, $_SESSION['wishlist'] ?? []);
}

// ============================================
//  PRODUCT FUNCTIONS - FIXED SAFE
// ============================================
function getProduct($id) {
   $conn = getDBConnection();
    if (!$conn) return [];
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? AND is_active = 1");
    if (!$stmt) return [];
    
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result) ?: [];
    mysqli_stmt_close($stmt);
    return $product;
}

function getProducts($filters = []) {
   $conn = getDBConnection();
    if (!$conn) return false;
    
    $query = "SELECT * FROM products WHERE is_active = 1";
    
    if (!empty($filters['type'])) {
        $query .= " AND product_type = '" . mysqli_real_escape_string($conn, $filters['type']) . "'";
    }
    
    if (!empty($filters['search'])) {
        $search = mysqli_real_escape_string($conn, $filters['search']);
        $query .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
    }
    
    $query .= " ORDER BY created_at DESC";
    if (!empty($filters['limit'])) {
        $query .= " LIMIT " . (int)$filters['limit'];
    }
    
    return mysqli_query($conn, $query);
}

function registerCustomer($name, $phone, $email, $address, $city = null) {
    $conn = getDBConnection();
    if (!$conn) {
        echo " ERROR: Database connection failed";
        return 0;
    }
    
    // Cek customer existing
    $stmt = mysqli_prepare($conn, "SELECT id FROM customers WHERE phone = ?");
    mysqli_stmt_bind_param($stmt, "s", $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($customer = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return $customer['id'];
    }
    mysqli_stmt_close($stmt);
    
    //  BENAR: Tambah is_active = 1 (karena kolom NOT NULL tanpa default)
    $stmt = mysqli_prepare($conn, "
        INSERT INTO customers (name, phone, email, address, city, is_active) 
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    mysqli_stmt_bind_param($stmt, "sssss", $name, $phone, $email, $address, $city);
    
    if (!mysqli_stmt_execute($stmt)) {
        echo " ERROR: " . mysqli_stmt_error($stmt);
        return 0;
    }
    
    $customer_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $customer_id;
}


function getCustomerByPhone($phone) {
   $conn = getDBConnection();
    if (!$conn) return null;
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM customers WHERE phone = ?");
    mysqli_stmt_bind_param($stmt, "s", $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $customer = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);
    return $customer;
}

// ============================================
//  TRANSACTION & UTILITY FUNCTIONS
// ============================================
function recordTransaction($type, $category, $description, $amount, $store_type, $ref_type = null, $ref_id = null) {
   $conn = getDBConnection();
    if (!$conn) return false;
    
    $trx_number = 'TRX-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO transactions (transaction_number, type, store_type, category, description, amount, reference_type, reference_id, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssdsi", $trx_number, $type, $store_type, $category, $description, $amount, $ref_type, $ref_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    return false;
}

function calculateShipping($city = 'Samarinda') {
    $rates = [
    'Samarinda'            => 10000,
    'Balikpapan'           => 25000,
    'Bontang'              => 20000,
    'Penajam Paser Utara'  => 35000,
    'Berau'                => 40000,
    'Tenggarong'           => 15000,
    'Bandung'              => 70000,
    'Jakarta'              => 80000,
    'Surabaya'             => 60000,
    'Mahakam Ulu'          => 50000,
    'Malang'               => 60000,
    'Batam'                => 90000,
    'Tanjung Selor'        => 45000,
    'Lainnya'              => 80000
    ];
    return $rates[$city] ?? 80000;
}

function getCityList() {
    $rates = [
    'Samarinda'            => 10000,
    'Balikpapan'           => 25000,
    'Bontang'              => 20000,
    'Penajam Paser Utara'  => 35000,
    'Berau'                => 40000,
    'Tenggarong'           => 15000,
    'Bandung'              => 70000,
    'Jakarta'              => 80000,
    'Surabaya'             => 60000,
    'Mahakam Ulu'          => 50000,
    'Malang'               => 60000,
    'Batam'                => 90000,
    'Tanjung Selor'        => 45000,
    ];
    return array_keys($rates);
}

function getSetting($key) {
   $conn = getDBConnection();
    if (!$conn) return null;
    
    $stmt = mysqli_prepare($conn, "SELECT value FROM settings WHERE key_name = ?");
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $setting = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $setting['value'] ?? null;
}

function generateOrderNumber() {
    return 'VJS-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

function sanitize($data) {
    $conn = getSafeDBConnection();
    return $conn ? mysqli_real_escape_string($conn, trim($data)) : htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatRupiah($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    return date('d M Y', $timestamp);
}

// Flash message functions
function setFlash($message, $type = 'info') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function redirect($url) {
    if (!headers_sent($file, $line)) {
        header("Location: $url");
        exit;
    } else {
        // Fallback JavaScript jika redirect gagal
        echo "<script>window.location.href='$url';</script>";
        exit;
    }
}

function getRelatedProducts($category_name, $product_type, $exclude_id, $limit = 4) {
   $conn = getDBConnection();
    if (!$conn) return false;
    
    $stmt = mysqli_prepare($conn, "
        SELECT p.* 
        FROM products p 
        INNER JOIN categories c ON p.category_id = c.id
        WHERE c.name = ? 
        AND p.product_type = ? 
        AND p.id != ? 
        AND p.is_active = 1
        ORDER BY p.created_at DESC 
        LIMIT ?
    ");
    
    if (!$stmt) return false;
    
    mysqli_stmt_bind_param($stmt, "ssii", $category_name, $product_type, $exclude_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

if (!function_exists('array_some')) {
    function array_some(array $array, callable $callback): bool {
        foreach ($array as $item) {
            if ($callback($item)) {
                return true;
            }
        }
        return false;
    }
}
?>
