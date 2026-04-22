<?php
require 'config.php';

// Password yang akan kita pakai untuk semua akun di bawah
$password_asli = '123456';
$password_hash = password_hash($password_asli, PASSWORD_DEFAULT);

// 1. Buat/Reset Akun PEMBIMBING
$email_guru = 'pembimbing@smk.id';
$cek_guru = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_guru'");

if (mysqli_num_rows($cek_guru) > 0) {
    // Jika sudah ada, UPDATE passwordnya saja
    $query = "UPDATE users SET password = '$password_hash', role = 'pembimbing' WHERE email = '$email_guru'";
    echo "Update Pembimbing: ";
} else {
    // Jika belum ada, BUAT BARU
    $query = "INSERT INTO users (nama_depan, nama_belakang, email, password, role) 
              VALUES ('Ibu', 'Guru', '$email_guru', '$password_hash', 'pembimbing')";
    echo "Buat Pembimbing Baru: ";
}

if (mysqli_query($conn, $query)) {
    echo "BERHASIL! <br>Email: <b>$email_guru</b> <br>Pass: <b>$password_asli</b><br><br>";
} else {
    echo "GAGAL: " . mysqli_error($conn) . "<br><br>";
}


// 2. Buat/Reset Akun WAKASEK (Sekalian)
$email_wakasek = 'wakasek@smk.id';
$cek_wakasek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_wakasek'");

if (mysqli_num_rows($cek_wakasek) > 0) {
    $query = "UPDATE users SET password = '$password_hash', role = 'wakasek' WHERE email = '$email_wakasek'";
    echo "Update Wakasek: ";
} else {
    $query = "INSERT INTO users (nama_depan, nama_belakang, email, password, role) 
              VALUES ('Bapak', 'Wakasek', '$email_wakasek', '$password_hash', 'wakasek')";
    echo "Buat Wakasek Baru: ";
}

if (mysqli_query($conn, $query)) {
    echo "BERHASIL! <br>Email: <b>$email_wakasek</b> <br>Pass: <b>$password_asli</b><br><br>";
} else {
    echo "GAGAL: " . mysqli_error($conn) . "<br><br>";
}

?>
<hr>
<a href="login.php">Ke Halaman Login >></a>