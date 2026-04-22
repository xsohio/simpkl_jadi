<?php
// ============================================================
//  sidebar_siswa.php — Komponen Sidebar Siswa SIMPKL
//  Disesuaikan dengan fitur database webpkl untuk role siswa:
//  dashboard, jurnal_harian, laporan_pkl, pkl_pengajuan,
//  nilai_pkl, profil_siswa
// ============================================================
if (!isset($active_page)) $active_page = '';

function sis($href, $icon, $label, $key, $active, $badge = 0, $danger = false) {
    $ac = ($key === $active) ? 'active' : '';
    $dg = $danger ? 'danger' : '';
    echo "<a href='{$href}' class='sidebar-item {$ac} {$dg}' data-tooltip='{$label}'>
            <i class='{$icon}'></i><span>{$label}</span>";
    if ($badge > 0) echo "<span class='badge'>{$badge}</span>";
    echo "</a>";
}

// Hitung badge: jurnal pending validasi milik siswa ini
global $conn;
$badge_jurnal = 0;
$siswa_id_s   = $_SESSION['user']['id_user'] ?? 0;
if (isset($conn) && $siswa_id_s) {
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM jurnal_harian WHERE siswa_id = $siswa_id_s AND status_validasi='pending'");
    if ($r) $badge_jurnal = (int)mysqli_fetch_assoc($r)['c'];
}

// Badge laporan direvisi (perlu diupload ulang)
$badge_laporan = 0;
if (isset($conn) && $siswa_id_s) {
    $r2 = mysqli_query($conn, "SELECT COUNT(*) c FROM laporan_pkl WHERE siswa_id = $siswa_id_s AND status_pembimbing='revisi'");
    if ($r2) $badge_laporan = (int)mysqli_fetch_assoc($r2)['c'];
}
?>

<button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
  <i class="fas fa-chevron-left"></i>
</button>

<aside class="sidebar" id="sidebar">

  <div class="sidebar-section">
    <div class="sidebar-section-title">Utama</div>
    <?php sis('dashboard_siswa.php', 'fas fa-th-large', 'Dashboard', 'dashboard', $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <div class="sidebar-section-title">PKL Saya</div>
    <?php sis('siswa-pengajuan.php',  'fas fa-file-signature', 'Pengajuan PKL',  'pengajuan', $active_page); ?>
    <?php sis('siswa-jurnal.php',     'fas fa-book-open',      'Jurnal Harian',  'jurnal',    $active_page, $badge_jurnal); ?>
    <?php sis('siswa-laporan.php',    'fas fa-file-alt',       'Laporan PKL',    'laporan',   $active_page, $badge_laporan); ?>
    <?php sis('siswa-absensi.php',    'fas fa-calendar-check', 'Rekap Absensi',  'absensi',   $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <div class="sidebar-section-title">Hasil</div>
    <!-- <?php sis('siswa-nilai.php',      'fas fa-star',           'Nilai PKL',      'nilai',     $active_page); ?> -->
    <?php sis('siswa-log-crud.php', 'fas fa-certificate',    'log aktivitas',     'log',$active_page); ?>
  </div>

  
  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <div class="sidebar-section-title">Akun</div>
    <?php sis('siswa-profil.php',     'fas fa-user-edit',      'Profil Saya',    'profil',    $active_page); ?>
    <?php sis('../logout.php',        'fas fa-sign-out-alt',   'Logout',         'logout',    $active_page, 0, true); ?>
  </div>

</aside>

<div class="sidebar-overlay" id="sidebarOverlay">
  
</div>
