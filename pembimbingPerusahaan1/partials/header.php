<?php
// partials/header.php
// Expects: $activePage (string), $pageTitle (string)
$pembimbing_id = getPembimbingId();
$pdo = getDB();
$stmt = $pdo->prepare("SELECT u.nama_depan, u.nama_belakang, pg.foto_profil FROM users u LEFT JOIN profil_guru pg ON pg.user_id=u.id WHERE u.id=?");
$stmt->execute([$pembimbing_id]);
$guru = $stmt->fetch();
$namaGuru = ($guru['nama_depan'] ?? '') . ' ' . ($guru['nama_belakang'] ?? '');
$inisialGuru = strtoupper(substr($guru['nama_depan']??'',0,1) . substr($guru['nama_belakang']??'',0,1));

// Badge counts
$stmtJurnal  = $pdo->prepare("SELECT COUNT(*) FROM jurnal_harian jh JOIN pkl_anggota pa ON pa.siswa_id=jh.siswa_id JOIN pkl_pengajuan pp ON pp.id=pa.pengajuan_id WHERE pp.pembimbing_id=? AND jh.status_validasi='pending'");
$stmtJurnal->execute([$pembimbing_id]);
$badgeJurnal = $stmtJurnal->fetchColumn();

$stmtLaporan = $pdo->prepare("SELECT COUNT(*) FROM laporan_pkl lp JOIN pkl_anggota pa ON pa.siswa_id=lp.siswa_id JOIN pkl_pengajuan pp ON pp.id=pa.pengajuan_id WHERE pp.pembimbing_id=? AND lp.status_pembimbing='pending'");
$stmtLaporan->execute([$pembimbing_id]);
$badgeLaporan = $stmtLaporan->fetchColumn();

$pages = [
    ['id'=>'dashboard',   'icon'=>'fa-th-large',         'label'=>'Dashboard',         'section'=>'Utama'],
    ['id'=>'siswa',       'icon'=>'fa-user-graduate',     'label'=>'Daftar Siswa',      'section'=>'Siswa Bimbingan'],
    ['id'=>'penempatan',  'icon'=>'fa-map-marker-alt',    'label'=>'Penempatan PKL',    'section'=>'Siswa Bimbingan'],
    ['id'=>'jurnal',      'icon'=>'fa-book-open',         'label'=>'Jurnal Harian',     'section'=>'Monitoring PKL', 'badge'=>$badgeJurnal],
    ['id'=>'absensi',     'icon'=>'fa-calendar-check',    'label'=>'Absensi PKL',       'section'=>'Monitoring PKL'],
    ['id'=>'laporan',     'icon'=>'fa-file-alt',          'label'=>'Laporan PKL',       'section'=>'Monitoring PKL', 'badge'=>$badgeLaporan],
    // ['id'=>'bimbingan',   'icon'=>'fa-comments',          'label'=>'Bimbingan',         'section'=>'Monitoring PKL'],
    ['id'=>'nilai',       'icon'=>'fa-star',              'label'=>'Input Nilai',       'section'=>'Penilaian'],
    // ['id'=>'kompetensi',  'icon'=>'fa-tasks',             'label'=>'Kompetensi',        'section'=>'Penilaian'],
    // ── Tambahan: Log Aktivitas ──────────────────────────────────────────────
    ['id'=>'log',         'icon'=>'fa-history',           'label'=>'Log Aktivitas',     'section'=>'Akun Saya'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIMPKL – <?= htmlspecialchars($pageTitle ?? 'Panel Pembimbing') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <nav>
    <div style="display:flex;align-items:center;gap:10px">
      <button class="sidebar-hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
      <a href="dashboard.php" class="logo">
        <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
        SIMPKL
      </a>
    </div>
    <div class="header-right">
      <div class="notif-btn" title="Notifikasi">
        <i class="fas fa-bell" style="font-size:.85rem"></i>
        <?php if($badgeJurnal + $badgeLaporan > 0): ?>
        <div class="notif-dot"></div>
        <?php endif; ?>
      </div>
      <div style="text-align:right">
        <div class="header-name"><?= htmlspecialchars($namaGuru) ?></div>
        <div class="header-role">Pembimbing PKL</div>
      </div>
      <div class="header-avatar"><?= $inisialGuru ?></div>
    </div>
  </nav>
</header>

<button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
  <i class="fas fa-chevron-left" id="toggleIcon"></i>
</button>

<div class="layout-wrapper">
<aside class="sidebar" id="sidebar">
<?php
$currentSection = '';
foreach ($pages as $p):
  if ($p['section'] !== $currentSection):
    if ($currentSection !== '') echo '</div><div class="sidebar-divider"></div>';
    $currentSection = $p['section'];
    echo '<div class="sidebar-section"><div class="sidebar-section-title">' . htmlspecialchars($p['section']) . '</div>';
  endif;
  $isActive = ($activePage === $p['id']) ? ' active' : '';
  $badge = isset($p['badge']) && $p['badge'] > 0 ? '<span class="badge-sidebar">' . $p['badge'] . '</span>' : '';
  // Map halaman log ke filename yang benar
  $href = ($p['id'] === 'log') ? 'log-aktivitas.php' : $p['id'] . '.php';
  echo '<a href="' . $href . '" class="sidebar-item' . $isActive . '"><i class="fas ' . $p['icon'] . '"></i><span class="label">' . htmlspecialchars($p['label']) . '</span>' . $badge . '</a>';
endforeach;
?>
  </div>
  <div class="sidebar-divider"></div>
  <div class="sidebar-section">
    <a href="logout.php" class="sidebar-item danger">
      <i class="fas fa-sign-out-alt"></i><span class="label">Logout</span>
    </a>
  </div>
</aside>

<main class="main-content" id="mainContent">
<div class="toast hidden" id="toast">
  <div class="toast-icon" id="toast-icon"></div>
  <span id="toast-msg">Pesan</span>
</div>
