<?php
require 'config.php';

// Password baru yang akan kita set (misal: 123456)
$password_baru = '123456';
// Hash password menggunakan algoritma server saat ini
$password_hash = password_hash($password_baru, PASSWORD_DEFAULT);

// Email admin yang ingin direset/dibuat
$email_admin = 'admin@smk.id';

// Cek apakah akun admin sudah ada?
$cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_admin'");

if (mysqli_num_rows($cek) > 0) {
    // JIKA ADA: Update passwordnya saja
    $query = "UPDATE users SET password = '$password_hash', role = 'admin' WHERE email = '$email_admin'";
    echo "<h3>Update Akun Admin...</h3>";
} else {
    // JIKA BELUM ADA: Buat baru
    $query = "INSERT INTO users (nama_depan, nama_belakang, email, password, role) 
              VALUES ('Super', 'Admin', '$email_admin', '$password_hash', 'admin')";
    echo "<h3>Buat Akun Admin Baru...</h3>";
}

if (mysqli_query($conn, $query)) {
    echo "<div style='color: green; border: 1px solid green; padding: 10px;'>
            <b>BERHASIL!</b><br>
            Akun Admin siap digunakan.<br><br>
            Email: <b>$email_admin</b><br>
            Password: <b>$password_baru</b>
          </div>";
} else {
    echo "<div style='color: red;'>GAGAL: " . mysqli_error($conn) . "</div>";
}
?>
<br>
<a href="login.php">Klik disini untuk Login Admin >></a>