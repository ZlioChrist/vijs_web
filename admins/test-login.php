<?php
// test-db.php - Test database connection & admin
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== DATABASE TEST FOR vijs_db ===\n\n";

// 1. Connect to vijs_db
$conn = @mysqli_connect('localhost', 'root', '', 'vijs_db');
if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error() . "\n");
}
echo "✅ Connected to vijs_db\n";

// 2. Check if admins table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'admins'");
if (mysqli_num_rows($result) === 0) {
    echo "❌ Table 'admins' NOT FOUND in vijs_db!\n";
    echo "💡 Creating table...\n";
    
    mysqli_query($conn, "
        CREATE TABLE admins (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            role ENUM('super_admin','admin','staff') DEFAULT 'admin',
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Table created\n";
} else {
    echo "✅ Table 'admins' exists\n";
}

// 3. Check admin user
echo "\n📋 Checking admin user:\n";
$result = mysqli_query($conn, "SELECT id, username, password, is_active FROM admins WHERE username = 'admin'");

if ($result && mysqli_num_rows($result) > 0) {
    $admin = mysqli_fetch_assoc($result);
    echo "   ID: {$admin['id']}\n";
    echo "   Username: {$admin['username']}\n";
    echo "   Password length: " . strlen($admin['password']) . " chars\n";
    echo "   Is active: " . ($admin['is_active'] ? 'Yes' : 'No') . "\n";
    
    // 4. Test password_verify
    echo "\n🔐 Testing password 'admin123':\n";
    $test = password_verify('admin123', $admin['password']);
    echo "   Result: " . ($test ? "✅ MATCH - Login should work!" : "❌ NO MATCH - Hash wrong!") . "\n";
    
    if (!$test) {
        echo "\n🔧 AUTO-FIX: Updating password hash...\n";
        $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE admins SET password = '$new_hash', is_active = 1 WHERE username = 'admin'");
        
        // Verify again
        $result2 = mysqli_query($conn, "SELECT password FROM admins WHERE username = 'admin'");
        $admin2 = mysqli_fetch_assoc($result2);
        $test2 = password_verify('admin123', $admin2['password']);
        echo "   Re-test: " . ($test2 ? "✅ SUCCESS!" : "❌ Still failed") . "\n";
        
        if ($test2) {
            echo "\n🎉 FIX COMPLETE! Login with:\n";
            echo "   Username: admin\n";
            echo "   Password: admin123\n";
            echo "   👉 <a href='admin/login.php'>Click here to login</a>\n";
        }
    }
} else {
    echo "\n⚠️ Admin user 'admin' NOT FOUND! Creating...\n";
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($conn, "
        INSERT INTO admins (username, password, name, email, phone, role, is_active) 
        VALUES ('admin', '$hash', 'Administrator', 'admin@vijslimee.com', '6281234567890', 'super_admin', 1)
    ");
    echo "✅ Admin created!\n";
    echo "\n🎉 Login with:\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n";
    echo "   👉 <a href='admin/login.php'>Click here to login</a>\n";
}

echo "\n=== END TEST ===";
echo "</pre>";
?>