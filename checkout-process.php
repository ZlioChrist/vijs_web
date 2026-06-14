<?php
//  DEBUG MODE - ERROR LANGSUNG MUNCUL
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'includes/functions.php';

//  Validasi POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo " ERROR: Harus via POST (submit form)";
    exit;
}

if (!isset($_POST['place_order'])) {
    echo " ERROR: place_order tidak ada";
    echo "<pre>POST: " . print_r($_POST, true) . "</pre>";
    exit;
}

//  Sanitize input
$name = sanitize($_POST['name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$city = sanitize($_POST['city'] ?? '');
$note = sanitize($_POST['note'] ?? '');
$payment_method = sanitize($_POST['payment_method'] ?? 'transfer');

echo "<pre style='background:#f0f0f0;padding:10px'>";
echo " Data:\nName: $name\nPhone: $phone\nCity: $city\n";
echo "</pre>";

//  Validasi
$errors = [];
if (empty($name)) $errors[] = 'Nama wajib diisi';
if (empty($phone) || !preg_match('/^62[0-9]{9,12}$/', $phone)) $errors[] = 'Format WhatsApp tidak valid';
if (empty($address)) $errors[] = 'Alamat wajib diisi';
if (empty($city)) $errors[] = 'Kota wajib dipilih';

$cart = getCart();
if (empty($cart)) {
    echo " ERROR: Keranjang kosong";
    exit;
}

if (!empty($errors)) {
    echo "<div style='background:#fee;padding:15px;border:1px solid #f00'>";
    echo " <strong>ERROR:</strong><br>";
    foreach ($errors as $e) echo "• $e<br>";
    echo "</div>";
    $_SESSION['form_data'] = $_POST;
    $_SESSION['form_errors'] = $errors;
    redirect('checkout.php');
}

//  Hitung total
$subtotal = getCartTotal();
$shipping = calculateShipping($city);
$total = $subtotal + $shipping;

//  DB Connection
$conn = getDBConnection();
if (!$conn) {
    echo " ERROR: Database connection failed";
    exit;
}

echo " Database connected<br>";

//  Cek struktur tabel orders
$columns = [];
$result = mysqli_query($conn, "DESCRIBE orders");
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
}

echo " Orders table has " . count($columns) . " columns<br>";

mysqli_begin_transaction($conn);

try {
    // 1️ Customer
    $customer_id = registerCustomer($name, $phone, $email, $address, $city);
    if ($customer_id == 0) {
        throw new Exception("Gagal register customer");
    }
    echo " Customer ID: $customer_id<br>";
    
    // 2️ Order
    $order_number = generateOrderNumber();
    $types = array_column($cart, 'type');
    $store_type = (count(array_unique($types)) > 1) ? 'both' : $types[0];
    
    //  Build INSERT dinamis sesuai kolom yang ADA
    $insert_cols = [];
    $placeholders = [];
    $types_str = '';
    $values = [];
    
    // Kolom wajib
    $required = [
        'customer_id' => [$customer_id, 'i'],
        'order_number' => [$order_number, 's'],
        'customer_name' => [$name, 's'],
        'customer_phone' => [$phone, 's'],
        'customer_address' => [$address, 's'],
        'customer_city' => [$city, 's'],
        'subtotal' => [$subtotal, 'd'],
        'shipping_cost' => [$shipping, 'd'],
        'total' => [$total, 'd'],
        'payment_method' => [$payment_method, 's'],
        'store_type' => [$store_type, 's'],
    ];
    
    // Kolom opsional (cek ada/tidak)
    $optional = [
        'customer_email' => [$email, 's'],
        'customer_note' => [$note, 's'],
        'items' => [json_encode($cart, JSON_UNESCAPED_UNICODE), 's'],
        'payment_status' => ['pending', 's'],
        'order_status' => ['pending', 's'],
    ];
    
    // Gabungkan kolom yang ada di database
    foreach ($required as $col => $data) {
        if (in_array($col, $columns)) {
            $insert_cols[] = $col;
            $placeholders[] = '?';
            $types_str .= $data[1];
            $values[] = $data[0];
        }
    }
    
    foreach ($optional as $col => $data) {
        if (in_array($col, $columns)) {
            $insert_cols[] = $col;
            $placeholders[] = '?';
            $types_str .= $data[1];
            $values[] = $data[0];
        }
    }
    
    $sql = "INSERT INTO orders (" . implode(', ', $insert_cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    echo " SQL: $sql<br>";
    echo " Types: $types_str (" . strlen($types_str) . " chars)<br>";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    // Dynamic bind
    $refs = [$types_str];
    for ($i = 0; $i < count($values); $i++) {
        $refs[] = &$values[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Insert order failed: " . mysqli_stmt_error($stmt));
    }
    
    $order_id = mysqli_insert_id($conn);
    echo " Order inserted, ID: $order_id<br>";
    mysqli_stmt_close($stmt);
    
    // 3️ Order items
    foreach ($cart as $item) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO order_items 
            (order_id, product_id, product_name, product_sku, product_type, price, quantity, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $subtotal_item = $item['price'] * $item['qty'];
        mysqli_stmt_bind_param($stmt, "iisssddd", 
            $order_id, $item['id'], $item['name'], $item['sku'], 
            $item['type'], $item['price'], $item['qty'], $subtotal_item
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Kurangi stok
        $stmt = mysqli_prepare($conn, "UPDATE products SET stock = stock - ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $item['qty'], $item['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    echo " Order items inserted<br>";
    
    // 4️ Transaction
    recordTransaction('income', 'Penjualan', "Income from order $order_number", $total, $store_type, 'order', $order_id);
    
    //  Commit
    mysqli_commit($conn);
    
    //  Clear cart
    clearCart();
    
    echo "<div style='background:#efe;padding:20px;border:2px solid #0a0;text-align:center'>";
    echo " <strong>SUCCESS!</strong> Order #$order_number<br>";
    echo "Redirecting...";
    echo "</div>";
    
    setFlash(" Order #$order_number berhasil!", 'success');
    redirect('order-success.php?order=' . $order_number);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    
    echo "<div style='background:#fee;padding:20px;border:2px solid #f00'>";
    echo " <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . htmlspecialchars($e->getFile()) . "<br>";
    echo "Line: " . $e->getLine();
    echo "</div>";
    
    $_SESSION['form_data'] = $_POST;
    $_SESSION['form_errors'] = [$e->getMessage()];
    redirect('checkout.php');
}
?>