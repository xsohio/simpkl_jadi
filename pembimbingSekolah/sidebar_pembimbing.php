<?php
require_once '../config.php';
// ============================================================
//  sidebar_pembimbing.php — Komponen Sidebar Pembimbing SIMPKL
// ============================================================
if (!isset($active_page)) $active_page = '';

function si_p($href, $icon, $label, $key, $active, $badge = 0, $danger = false) {
    $ac = ($key === $active) ? 'active' : '';
    $dg = $danger ? 'danger' : '';
    echo "<a href='{$href}' class='sidebar-item {$ac} {$dg}' data-tooltip='{$label}'>
            <i class='{$icon}'></i><span>{$label}</span>";
    if ($badge > 0) echo "<span class='badge'>{$badge}</span>";
    echo "</a>";
}

global $conn;
$badge_jurnal  = 0;
$badge_laporan = 0;
if (isset($conn) && isset($_SESSION['user'])) {
    $pid = (int)$_SESSION['user']['id_user'];

    $rj = mysqli_query($conn,
        "SELECT COUNT(*) c FROM jurnal_harian j
         INNER JOIN pkl_pengajuan p ON j.siswa_id = p.ketua_id
         WHERE p.pembimbing_id = $pid AND j.status_validasi = 'pending'");
    if ($rj) $badge_jurnal = (int)mysqli_fetch_assoc($rj)['c'];

    $rl = mysqli_query($conn,
        "SELECT COUNT(*) c FROM laporan_pkl l
         INNER JOIN pkl_pengajuan p ON l.siswa_id = p.ketua_id
         WHERE p.pembimbing_id = $pid AND l.status_pembimbing = 'pending'");
    if ($rl) $badge_laporan = (int)mysqli_fetch_assoc($rl)['c'];
}
?>

<button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
  <i class="fas fa-chevron-left"></i>
</button>

<aside class="sidebar" id="sidebar">

  <div class="sidebar-section">
    <div class="sidebar-section-title">Utama</div>
    <?php si_p('dashboard_pembimbing.php', 'fas fa-gauge-high', 'Dashboard', 'dashboard', $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <div class="sidebar-section-title">Siswa Bimbingan</div>
    <?php si_p('daftar_siswa_pembimbing.php', 'fas fa-user-graduate', 'Daftar Siswa', 'siswa', $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <div class="sidebar-section-title">Monitoring PKL</div>
    <?php si_p('jurnal_pembimbing.php',  'fas fa-book-open-reader', 'Jurnal Harian', 'jurnal',  $active_page, $badge_jurnal); ?>
    <?php si_p('absensi_pembimbing.php', 'fas fa-calendar-check',  'Absensi PKL',   'absensi', $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <div class="sidebar-section-title">Penilaian</div>
    <?php si_p('input_nilai.php',  'fas fa-star-half-stroke', 'Input Nilai',  'nilai',      $active_page); ?>
    <?php si_p('kompetensi.php',   'fas fa-list-check',       'Kompetensi',   'kompetensi', $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <div class="sidebar-section-title">Sistem</div>
    <?php si_p('log_aktivitas_pembimbing.php', 'fas fa-clock-rotate-left', 'Log Aktivitas', 'log', $active_page); ?>
  </div>

  <div class="sidebar-divider"></div>

  <div class="sidebar-section">
    <?php si_p('../logout.php', 'fas fa-right-from-bracket', 'Logout', 'logout', $active_page, 0, true); ?>
  </div>

</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
