<?php
// ============================================================
//  config.php — Panel Pembimbing SIMPKL
//  Disambungkan dengan sistem utama webpkl (config.php root)
//  Mendukung DUA format session:
//    Format Pembimbing lama : $_SESSION['user_id'] + $_SESSION['role']
//    Format Sistem Utama    : $_SESSION['user']['id_user'] + $_SESSION['user']['role']
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ——— Konfigurasi Database (sama dengan sistem utama) ———
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'webpkl_fixed');

// ——— Koneksi PDO (untuk panel pembimbing) ———
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
//  BRIDGE SESSION
//  Normalkan session ke format pembimbing ($_SESSION['user_id'])
//  dari dua kemungkinan sumber:
//    1. Login lewat panel pembimbing ini (sudah set user_id)
//    2. Login lewat sistem utama (set $_SESSION['user'][...])
// ============================================================
function _bridgeSession() {
    // Sudah ada format pembimbing — tidak perlu apa-apa
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        return;
    }
    // Ada session sistem utama — salin ke format pembimbing
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        $u = $_SESSION['user'];
        $_SESSION['user_id'] = $u['id_user'] ?? ($u['id'] ?? null);
        $_SESSION['role']    = $u['role']    ?? '';
        $_SESSION['nama']    = $u['nama']    ?? (($u['nama_depan'] ?? '') . ' ' . ($u['nama_belakang'] ?? ''));
    }
}
_bridgeSession();

// ============================================================
//  Helper: Ambil ID pembimbing dari session
// ============================================================
function getPembimbingId() {
    return $_SESSION['user_id'] ?? null;
}

// ============================================================
//  Helper: Proteksi halaman — harus login + role pembimbing
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
    if (($_SESSION['role'] ?? '') !== $role) {
        header('Location: login.php');
        exit;
    }
}

// ============================================================
//  Helper: Data siswa bimbingan
// ============================================================
function getSiswaBimbingan($pembimbing_id) {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT u.id, u.nama_depan, u.nama_belakang,
               ps.nis, ps.kelas, ps.jurusan, ps.no_hp, ps.foto_profil,
               pp.nama_perusahaan, pp.alamat_perusahaan, pp.status_pembimbing
        FROM pkl_pengajuan pp
        JOIN pkl_anggota pa ON pa.pengajuan_id = pp.id
        JOIN users u        ON u.id             = pa.siswa_id
        LEFT JOIN profil_siswa ps ON ps.user_id = u.id
        WHERE pp.pembimbing_id      = ?
          AND pa.status_keanggotaan = 'aktif'
        ORDER BY u.nama_depan
    ");
    $stmt->execute([$pembimbing_id]);
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
