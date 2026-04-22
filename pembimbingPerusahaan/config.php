<?php
// ============================================================
//  config.php — Panel Wakasek SIMPKL
//  Disambungkan dengan sistem utama webpkl
//  Mendukung DUA format session:
//    Format Wakasek lama  : $_SESSION['user_id'] + $_SESSION['role']
//    Format Sistem Utama  : $_SESSION['user']['id_user'] + $_SESSION['user']['role']
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ——— Konfigurasi Database ———
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'webpkl_fixed');

// ——— Koneksi PDO ———
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            die('Koneksi database gagal: ' . $e->getMessage());
        }
    }
    return $pdo;
}

// ============================================================
//  BRIDGE SESSION — normalisasi session ke format wakasek
// ============================================================
function _bridgeSession() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        return;
    }
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        $u = $_SESSION['user'];
        $_SESSION['user_id'] = $u['id_user'] ?? ($u['id'] ?? null);
        $_SESSION['role']    = $u['role']    ?? '';
        $_SESSION['nama']    = $u['nama']    ?? (($u['nama_depan'] ?? '') . ' ' . ($u['nama_belakang'] ?? ''));
    }
}
_bridgeSession();

// ============================================================
//  Helper: Ambil ID wakasek dari session
// ============================================================
function getWakasekId() {
    return $_SESSION['user_id'] ?? null;
}

// ============================================================
//  Helper: Proteksi halaman — harus login + role wakasek
// ============================================================
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        _bridgeSession();
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }
}

function requireRole($role) {
    requireLogin();
    // Wakasek panel: terima role 'wakasek' atau 'admin'
    $allowed = ['wakasek', 'admin'];
    if (!in_array($_SESSION['role'] ?? '', $allowed)) {
        header('Location: login.php');
        exit;
    }
}

// ============================================================
//  Helper: Semua siswa PKL aktif (wakasek melihat semuanya)
// ============================================================
function getAllSiswaPKL($filter = []) {
    $pdo   = getDB();
    $where = ["pa.status_keanggotaan = 'aktif'"];
    $params = [];

    if (!empty($filter['kelas'])) {
        $where[] = "ps.kelas = ?";
        $params[] = $filter['kelas'];
    }
    if (!empty($filter['jurusan'])) {
        $where[] = "ps.jurusan = ?";
        $params[] = $filter['jurusan'];
    }

    $whereSQL = implode(' AND ', $where);
    $stmt = $pdo->prepare("
        SELECT u.id, u.nama_depan, u.nama_belakang,
               ps.nis, ps.kelas, ps.jurusan, ps.no_hp, ps.foto_profil,
               pp.nama_perusahaan, pp.alamat_perusahaan, pp.status_pembimbing,
               pp.pembimbing_id,
               pu.nama_depan AS pembimbing_depan, pu.nama_belakang AS pembimbing_belakang
        FROM pkl_pengajuan pp
        JOIN pkl_anggota pa ON pa.pengajuan_id = pp.id
        JOIN users u        ON u.id             = pa.siswa_id
        LEFT JOIN profil_siswa ps ON ps.user_id = u.id
        LEFT JOIN users pu ON pu.id = pp.pembimbing_id
        WHERE $whereSQL
        ORDER BY u.nama_depan
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ============================================================
//  Helper: JSON response (untuk AJAX)
// ============================================================
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ============================================================
//  Helper: Avatar warna & inisial
// ============================================================
function avatarColor($id) {
    $colors = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#8b5cf6','#ef4444','#2dd4bf','#f97316','#a855f7'];
    return $colors[$id % count($colors)];
}

function initials($nama_depan, $nama_belakang) {
    return strtoupper(substr($nama_depan, 0, 1) . substr($nama_belakang, 0, 1));
}

// ============================================================
//  Timezone
// ============================================================
date_default_timezone_set('Asia/Jakarta');
