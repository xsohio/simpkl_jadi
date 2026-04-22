<?php
// ============================================================
//  index.php — Panel Wakasek SIMPKL
//  Entry point: cek session lalu arahkan
// ============================================================
require_once 'config.php';   // sudah bridge session

if (isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['wakasek', 'admin'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
