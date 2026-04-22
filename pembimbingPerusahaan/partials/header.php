<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Panel Wakasek') ?> — SIMPKL Wakasek</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php
// Ambil nama dari session yang benar
$_namaHeader = $_SESSION['user']['nama'] ?? 'Wakasek';
?>

<!-- ======= HEADER ======= -->
<header>
  <nav>
    <div style="display:flex;align-items:center;gap:12px">
      <button class="sidebar-hamburger" onclick="toggleMobileSidebar()"><i class="fas fa-bars"></i></button>
      <a href="dashboard.php" class="logo">
        <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <span>SIMPKL <span style="color:#818cf8;font-size:.8em">Wakasek</span></span>
      </a>
    </div>
    <div class="header-right">
      <div class="notif-btn"><i class="fas fa-bell"></i><div class="notif-dot"></div></div>
      <div class="header-avatar"><?= strtoupper(substr($_namaHeader, 0, 1)) ?></div>
      <div>
        <div class="header-name"><?= htmlspecialchars(explode(' ', $_namaHeader)[0]) ?></div>
        <div class="header-role">Wakil Kepala Sekolah</div>
      </div>
      <a href="logout.php" style="color:#64748b;padding:6px 10px;border-radius:6px;transition:.2s;font-size:.8rem" title="Keluar">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </nav>
</header>

<!-- ======= SIDEBAR TOGGLE ======= -->
<button class="sidebar-toggle" id="sidebar-toggle" onclick="toggleSidebar()">
  <i class="fas fa-chevron-left"></i>
</button>
<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleMobileSidebar()"></div>

<!-- ======= SIDEBAR ======= -->
<aside class="sidebar" id="sidebar">
  <!-- MAIN MENU -->
  <div class="sidebar-section">
    <div class="sidebar-section-title">Menu Utama</div>
    <a href="dashboard.php" class="sidebar-item <?= ($activePage??'')==='dashboard'?'active':'' ?>">
      <i class="fas fa-tachometer-alt"></i><span class="label">Dashboard</span>
    </a>
    <a href="siswa.php" class="sidebar-item <?= ($activePage??'')==='siswa'?'active':'' ?>">
      <i class="fas fa-user-graduate"></i><span class="label">Data Siswa PKL</span>
    </a>
    <a href="penempatan.php" class="sidebar-item <?= ($activePage??'')==='penempatan'?'active':'' ?>">
      <i class="fas fa-map-marker-alt"></i><span class="label">Penempatan PKL</span>
    </a>
  </div>

  <div class="sidebar-divider"></div>

  <!-- VERIFIKASI -->
  <div class="sidebar-section">
    <div class="sidebar-section-title">Verifikasi &amp; Monitor</div>
    <a href="pengajuan.php" class="sidebar-item <?= ($activePage??'')==='pengajuan'?'active':'' ?>">
      <i class="fas fa-file-signature"></i><span class="label">Pengajuan PKL</span>
      <?php
      try {
          $pdo2 = getDB();
          $cPending = $pdo2->query("SELECT COUNT(*) FROM pkl_pengajuan WHERE status_wakasek='pending'")->fetchColumn();
          if ($cPending > 0): ?>
          <span class="badge-sidebar"><?= $cPending ?></span>
          <?php endif;
      } catch (Exception $e) {}
      ?>
    </a>
    <a href="jurnal.php" class="sidebar-item <?= ($activePage??'')==='jurnal'?'active':'' ?>">
      <i class="fas fa-book-open"></i><span class="label">Monitor Jurnal</span>
    </a>
    <a href="laporan.php" class="sidebar-item <?= ($activePage??'')==='laporan'?'active':'' ?>">
      <i class="fas fa-file-alt"></i><span class="label">Monitor Laporan</span>
    </a>
    <a href="absensi.php" class="sidebar-item <?= ($activePage??'')==='absensi'?'active':'' ?>">
      <i class="fas fa-calendar-check"></i><span class="label">Rekap Absensi</span>
    </a>
    <a href="nilai.php" class="sidebar-item <?= ($activePage??'')==='nilai'?'active':'' ?>">
      <i class="fas fa-star"></i><span class="label">Rekap Nilai PKL</span>
    </a>
  </div>

  <div class="sidebar-divider"></div>

  <!-- AKUN -->
  <div class="sidebar-section">
    <div class="sidebar-section-title">Akun</div>
    <a href="log-aktivitas.php" class="sidebar-item <?= ($activePage??'')==='log'?'active':'' ?>">
      <i class="fas fa-history"></i><span class="label">Log Aktivitas</span>
    </a>
    <a href="../logout.php" class="sidebar-item danger">
      <i class="fas fa-sign-out-alt"></i><span class="label">Keluar</span>
    </a>
  </div>
</aside>

<!-- ======= MAIN CONTENT ======= -->
<div class="layout-wrapper">
  <main class="main-content" id="main-content">

<?php
// Toast notification
if (!empty($_SESSION['toast'])):
  $t = $_SESSION['toast'];
  unset($_SESSION['toast']);
?>
<div class="toast <?= $t['type'] ?>" id="toast-msg">
  <div class="toast-icon" style="background:<?=$t['type']==='success'?'rgba(74,222,128,.15)':'rgba(239,68,68,.15)'?>">
    <i class="fas <?=$t['type']==='success'?'fa-check':'fa-times'?>" style="color:<?=$t['type']==='success'?'#4ade80':'#ef4444'?>"></i>
  </div>
  <?= htmlspecialchars($t['msg']) ?>
</div>
<script>setTimeout(()=>{const t=document.getElementById('toast-msg');if(t)t.style.display='none'},3500);</script>
<?php endif; ?>