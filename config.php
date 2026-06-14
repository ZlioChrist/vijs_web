<?php
// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/**
 * Vij Slimee & Aprpiejise - Configuration
 * Color Palette: Pink, Tosca, Kuning (NO Purple/Blue)
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vijs_db');

// Site Configuration
define('SITE_NAME', 'Vij Slimee & Aprpiejise');
define('SITE_TAGLINE', 'Your One-Stop Shop for Slime & Kpop Photocards');
define('SITE_URL', 'http://localhost/web_vijs');
define('WHATSAPP_NUMBER', '6281234567890');

define('SITE_DESCRIPTION', 'Toko slime & photocard K-Pop terpercaya');
define('SITE_WHATSAPP', '6281234567890');
define('SITE_SHIPPING_SAMARINDA', '10000');
define('SITE_SHIPPING_BALIKPAPAN', '15000');
define('SITE_SHIPPING_JAKARTA', '25000');
define('SITE_SHIPPING_DEFAULT', '20000');
define('SITE_FREE_SHIPPING_MIN', '100000');
define('SITE_BANK_NAME', 'BCA');
define('SITE_BANK_ACCOUNT', '1234567890');
define('SITE_BANK_ACCOUNT_NAME', 'Vij Slimee');
define('SITE_EWALLET_DANA', '081234567890');
define('SITE_EWALLET_OVO', '081234567890');
define('SITE_EWALLET_GOPAY', '081234567890');
define('SITE_MAINTENANCE_MODE', '1');
define('SITE_ORDER_PREFIX', 'VJS');
define('SITE_CURRENCY_SYMBOL', 'Rp');



// Instagram Accounts
define('INSTAGRAM_SLIME', 'https://www.instagram.com/vijslimee.smr/');
define('INSTAGRAM_KPOP', 'https://www.instagram.com/aprpiejise/');

// Product Types
define('TYPE_SLIME', 'slime');
define('TYPE_PHOTOCARD', 'photocard');

function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn) {
            die("KONEKSI DATABASE GAGAL: " . mysqli_connect_error());
        }
        mysqli_set_charset($conn, 'utf8mb4');
    }
    return $conn;
}




?>