<?php
// ============================================================
//  sidebar.php — Komponen Sidebar Admin SIMPKL
//  Disesuaikan dengan fitur database webpkl:
//  users, profil_siswa, profil_guru, mitra_industri,
//  pkl_pengajuan, pkl_anggota, jurnal_harian,
//  laporan_pkl, nilai_pkl
// ============================================================
if (!isset($active_page)) $active_page = '';

function si($href, $icon, $label, $key, $active, $badge = 0, $danger = false) {
    $ac = ($key === $active) ? 'active' : '';
    $dg = $danger ? 'danger' : '';
    echo "<a href='{$href}' class='sidebar-item {$ac} {$dg}' data-tooltip='{$label}'>
            <i class='{$icon}'></i><span>{$label}</span>";
    if ($badge > 0) echo "<span class='badge'>{$badge}</span>";
    echo "</a>";
}

// Hitung badge laporan pending dari tabel laporan_pkl
global $conn;
$badge_laporan = 0;
if (isset($conn)) {
    $res_badge = mysqli_query($conn, "SELECT COUNT(*) c FROM laporan_pkl WHERE status_pembimbing = 'pending'");
    if ($res_badge) $badge_laporan = (int)mysqli_fetch_assoc($res_badge)['c'];
}
?>

<button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
  <i class="fas fa-chevron-left"></i>
</button>

<aside class="sidebar" id="sidebar">

  <div class="sidebar-section">
    <div class="sidebar-section-title">Utama</div>
    <?php si('dashboard_admin2.php', 'fas fa-th-large', 'Dashboard', 'dashboard', $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <div class="sidebar-section-title">Data Master</div>
    <?php si('admin-users.php',  'fas fa-users-cog',          'Manajemen User',  'users',      $active_page); ?>
    <?php si('admin-siswa.php',  'fas fa-user-graduate',      'Data Siswa',      'siswa',      $active_page); ?>
    <?php si('admin-walikelas.php',   'fas fa-chalkboard-teacher', 'Data Guru pembimbing',       'guru',       $active_page); ?>
    <?php si('admin-perusahaan.php',  'fas fa-building',           'Mitra Industri',  'mitra',      $active_page); ?>
    <?php si('pkl-penempatan.php',  'fas fa-building',           'Siswa PKL',  'pkl',      $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <div class="sidebar-section-title">Aktivitas PKL</div>
    <?php si('pkl-absensi.php', 'fas fa-file-signature', 'Absensi PKL',  'absensi', $active_page); ?>
    <?php si('pkl-bimbingan.php',    'fas fa-book-open',      'Bimbingan PKL',  'bimbingan',    $active_page); ?>
    <?php si('pkl-kopetensi.php',   'fas fa-file-alt',       'Kopetensi',    'kopetensi',   $active_page, $badge_laporan); ?>
    <?php si('pkl-nilai.php',     'fas fa-star',           'Nilai PKL',      'nilai',     $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <div class="sidebar-section-title">Sistem</div>
    <?php si('log-aktivitas.php', 'fas fa-history', 'Log Aktivitas', 'log', $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <?php si('../logout.php', 'fas fa-sign-out-alt', 'Logout', 'logout', $active_page, 0, true); ?>
  </div>

</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
