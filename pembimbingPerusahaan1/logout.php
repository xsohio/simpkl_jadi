<?php
// ============================================================
//  logout.php — Panel Pembimbing SIMPKL
//  Menghancurkan KEDUA format session sekaligus:
//    - $_SESSION['user_id'] / $_SESSION['role']  (format pembimbing)
//    - $_SESSION['user']                          (format sistem utama)
//  Lalu redirect ke login panel pembimbing
// ============================================================
require_once 'config.php';   // sudah panggil session_start()

// Hapus semua data session
session_unset();
session_destroy();

// Arahkan ke login pembimbing (bukan login utama)
header('Location: ../login.php');
exit;
