<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

$conn = getDBConnection();
echo "<pre>";
echo " Connected to: " . DB_NAME . "\n\n";

// Cek struktur tabel orders
$result = mysqli_query($conn, "DESCRIBE orders");
echo " Tabel 'orders' columns:\n";
while($row = mysqli_fetch_assoc($result)) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}

echo "\n";

// Cek struktur tabel customers
$result = mysqli_query($conn, "DESCRIBE customers");
echo " Tabel 'customers' columns:\n";
while($row = mysqli_fetch_assoc($result)) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}
echo "</pre>";
?>