<?php
// Database connection
$conn = mysqli_connect('localhost', 'root', '', 'vijslimee_db');

// Generate new password hash
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update database
$query = "UPDATE admins SET password = '$hashed_password' WHERE username = 'admin'";

if (mysqli_query($conn, $query)) {
    echo "✅ Password berhasil direset!<br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>admin123</strong><br>";
    echo "Hash baru: <strong>$hashed_password</strong>";
} else {
    echo "❌ Error: " . mysqli_error($conn);
}
?>