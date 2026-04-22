<?php
// ============================================================
//  config.php — Konfigurasi Koneksi Database
//  Sistem Informasi PKL — webpkl_fixed
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', getenv('BASE_URL') ?: 'coba/webpkl/siswa2/');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'webpkl_fixed');

// ── mysqli connection (dipakai login.php & bagian lama) ──────────────────
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

// ── PDO connection (dipakai dashboard.php, log-aktivitas.php, dll) ───────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

// ── Ambil ID user wakasek yang sedang login ───────────────────────────────
function getWakasekId(): int {
    if (!isset($_SESSION['user']['id_user'])) {
        redirect('login.php');
    }
    return (int) $_SESSION['user']['id_user'];
}

// ── Timezone ──────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Jakarta');

// ============================================================
//  Helper: Bersihkan input
// ============================================================
function clean($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim(htmlspecialchars($data)));
}

// ============================================================
//  Helper: Redirect
// ============================================================
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

// ============================================================
//  Helper: Flash message (session)
// ============================================================
function setFlash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f     = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $color = $f['type'] === 'success' ? '#2dd4bf' : '#ef4444';
        $bg    = $f['type'] === 'success' ? '45,212,191' : '239,68,68';
        echo "<div style='background:rgba({$bg},0.12);
              border:1px solid {$color};border-radius:10px;padding:12px 20px;
              color:{$color};font-size:.85rem;margin-bottom:20px;'>
              {$f['msg']}</div>";
    }
}

// ============================================================
//  Helper: Proteksi halaman berdasarkan role
//  Session structure: $_SESSION['user']['id_user'], ['nama'], ['email'], ['role']
// ============================================================
function requireLogin() {
    if (!isset($_SESSION['user'])) {
        redirect('login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user']['role'] !== 'admin') redirect('login.php');
}

function requirePembimbing() {
    requireLogin();
    if ($_SESSION['user']['role'] !== 'pembimbing') redirect('login.php');
}

function requireWakasek() {
    requireLogin();
    if ($_SESSION['user']['role'] !== 'wakasek') redirect('login.php');
}

function requireSiswa() {
    requireLogin();
    if ($_SESSION['user']['role'] !== 'siswa') redirect('login.php');
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user']['role'] !== $role) redirect('login.php');
}

// ============================================================
//  Helper: Catat log aktivitas
// ============================================================
function catatLog($conn, $aktivitas) {
    $cek = mysqli_query($conn, "SHOW TABLES LIKE 'log_aktivitas'");
    if (!$cek || mysqli_num_rows($cek) === 0) return;

    $id_users  = isset($_SESSION['user']['id_user'])
                 ? (int)$_SESSION['user']['id_user']
                 : 'NULL';
    $aktivitas = mysqli_real_escape_string($conn, $aktivitas);
    mysqli_query($conn, "INSERT INTO log_aktivitas (id_users, aktivitas)
                         VALUES ($id_users, '$aktivitas')");
}

// ============================================================
//  Helper: Log aktivitas via PDO (dipakai panel wakasek)
// ============================================================
function logAktivitas(PDO $pdo, int $userId, string $aktivitas, string $role = ''): void {
    try {
        $pdo->prepare("INSERT INTO log_aktivitas (id_users, aktivitas) VALUES (?, ?)")
            ->execute([$userId, $aktivitas]);
    } catch (Exception $e) {
        // Diam saja jika tabel belum ada
    }
}
