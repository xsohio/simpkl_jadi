<?php
// Pastikan sesi sudah dimulai
session_start();

// Hancurkan semua variabel sesi
session_unset();

// Hancurkan sesi
session_destroy();

// Redirect user ke halaman login
// Atau Anda bisa redirect ke index.php jika ingin kembali ke halaman publik
header("Location: login.php");
exit();
?>