<?php
// ============================================================
//  _header_siswa.php — Header + Sidebar + CSS siswa SIMPKL
//  Include di setiap halaman siswa SEBELUM konten:
//    $active_page = 'jurnal';
//    include '_header_siswa.php';
// ============================================================
if (!isset($active_page)) $active_page = '';
if (!isset($page_title))  $page_title  = 'Siswa SIMPKL';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($page_title); ?> — SIMPKL</title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
</head>
<body style="display:flex;flex-direction:column;">

<!-- ===== TOPBAR ===== -->
<header style="position:fixed;top:0;left:0;right:0;z-index:100;
  background:rgba(28, 26, 62, 0.97);backdrop-filter:blur(10px);
  border-bottom:1px solid rgba(36, 38, 151, 0.07);height:64px;">
  <nav style="display:flex;align-items:center;justify-content:space-between;height:100%;padding:0 24px;">
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
      <div style="font-weight:700;font-size:1rem;color:#f1f5f9;letter-spacing:2px;">
        PORTAL SISWA
      </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <ul>
        <li><a href="dashboard_siswa.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
      </ul>
    </div>
  </nav>
</header>

<?php include 'sidebar_siswa.php'; ?>

<div class="layout-wrapper">
<main class="main-content" id="mainContent">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <i class="fas fa-home"></i>
    <i class="fas fa-chevron-right" style="font-size:.6rem;"></i>
    <a href="dashboard_siswa.php">Dashboard</a>
    <?php if ($page_title !== 'Dashboard Siswa'): ?>
    <i class="fas fa-chevron-right" style="font-size:.6rem;"></i>
    <span><?php echo htmlspecialchars($page_title); ?></span>
    <?php endif; ?>
  </div>

  <?php getFlash(); ?>
  <!-- ============ KONTEN HALAMAN DI BAWAH INI ============ -->
