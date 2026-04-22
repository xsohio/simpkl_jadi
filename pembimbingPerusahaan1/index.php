<?php
// ============================================================
//  index.php — Panel Pembimbing SIMPKL
//  Entry point: cek session lalu arahkan
// ============================================================
require_once 'config.php';   // sudah bridge session

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'pembimbing') {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
