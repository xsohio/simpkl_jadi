<?php
// ============================================================
//  _header_pembimbing.php — Header + Sidebar + CSS bawaan pembimbing
//  Include di setiap halaman pembimbing SEBELUM konten:
//    $active_page = 'jurnal';
//    include '_header_pembimbing.php';
// ============================================================
if (!isset($active_page)) $active_page = '';
if (!isset($page_title))  $page_title  = 'Pembimbing SIMPKL';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($page_title); ?> — SIMPKL</title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link rel="stylesheet" href="pembimbing.css"/>
</head>
<body style="display:flex;flex-direction:column;">

<!-- ===== TOPBAR ===== -->
<header>
  <nav>
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="mobile-menu-btn" id="mobileMenuBtn" style="
        background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);
        color:#e2e8f0;padding:8px 10px;border-radius:8px;cursor:pointer;font-size:1rem;">
        <i class="fas fa-bars"></i>
      </button>
      <div style="display:flex;align-items:center;gap:8px;">
        <div style="width:32px;height:32px;background:linear-gradient(135deg,#6366f1,#8b5cf6);
          border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.85rem;">
          <i class="fas fa-chalkboard-user" style="color:#fff;"></i>
        </div>
        <div style="font-weight:700;font-size:.9rem;color:#f1f5f9;letter-spacing:1.5px;">
          PEMBIMBING <span style="color:#6366f1;">PKL</span>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <!-- Notif Jurnal Pending -->
      <?php
      global $conn;
      $notif_jurnal = 0;
      if (isset($conn) && isset($_SESSION['user'])) {
          $pid_hdr = (int)$_SESSION['user']['id_user'];
          $rn = mysqli_query($conn,
              "SELECT COUNT(*) c FROM jurnal_harian j
               INNER JOIN pkl_pengajuan p ON j.siswa_id = p.ketua_id
               WHERE p.pembimbing_id = $pid_hdr AND j.status_validasi = 'pending'");
          if ($rn) $notif_jurnal = (int)mysqli_fetch_assoc($rn)['c'];
      }
      ?>
      <a href="jurnal_pembimbing.php" style="position:relative;color:#94a3b8;font-size:1.1rem;padding:6px 10px;
         background:rgba(255,255,255,0.05);border-radius:8px;border:1px solid rgba(255,255,255,0.08);
         display:flex;align-items:center;" title="Jurnal Pending">
        <i class="fas fa-bell"></i>
        <?php if($notif_jurnal > 0): ?>
        <span style="position:absolute;top:-5px;right:-5px;background:#ef4444;color:#fff;
          font-size:.6rem;font-weight:700;padding:2px 5px;border-radius:99px;min-width:16px;text-align:center;">
          <?= $notif_jurnal ?>
        </span>
        <?php endif; ?>
      </a>
      <div style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.05);
        border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:6px 12px;font-size:.82rem;color:#e2e8f0;">
        <i class="fas fa-circle-user" style="color:#6366f1;font-size:1rem;"></i>
        <?php echo htmlspecialchars($_SESSION['user']['nama'] ?? 'Pembimbing'); ?>
      </div>
    </div>
  </nav>
</header>

<?php include 'sidebar_pembimbing.php'; ?>

<div class="layout-wrapper">
<main class="main-content" id="mainContent">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <i class="fas fa-house" style="font-size:.75rem;"></i>
    <i class="fas fa-chevron-right" style="font-size:.55rem;"></i>
    <a href="dashboard_pembimbing.php">Dashboard</a>
    <?php if ($page_title !== 'Dashboard Pembimbing'): ?>
    <i class="fas fa-chevron-right" style="font-size:.55rem;"></i>
    <span><?php echo htmlspecialchars($page_title); ?></span>
    <?php endif; ?>
  </div>

  <?php if(function_exists('getFlash')) getFlash(); ?>
  <!-- ============ KONTEN HALAMAN DI BAWAH INI ============ -->
