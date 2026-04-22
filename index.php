<?php
require 'config.php';

// Ambil Laporan yang Sudah Dipublikasi
$query_laporan = "
    SELECT l.*, u.nama_depan, u.nama_belakang, ps.jurusan 
    FROM laporan_pkl l
    JOIN users u ON l.siswa_id = u.id
    LEFT JOIN profil_siswa ps ON u.id = ps.user_id 
    WHERE l.tampil_di_publik = 1
    ORDER BY l.created_at DESC LIMIT 6
";
$result_laporan = mysqli_query($conn, $query_laporan) or die(mysqli_error($conn));

// Query: Ambil Daftar Mitra PKL
$query_mitra = mysqli_query($conn, "
    SELECT * FROM mitra_industri 
    ORDER BY id DESC LIMIT 12
") or die(mysqli_error($conn));

// Cek login status
if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = isset($_SESSION['user']);
$user_role = $_SESSION['user']['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIMPKL — Sistem Informasi PKL</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:       #090913;
      --bg2:      #0e0e1a;
      --bg3:      #12121f;
      --card:     rgba(255,255,255,.04);
      --border:   rgba(255,255,255,.09);
      --text:     #f1f5f9;
      --muted:    #64748b;
      --accent:   #ffffff;
      --blue:     #93c5fd;
      --purple:   #c4b5fd;
      --green:    #4ade80;
      --yellow:   #f59e0b;
      --red:      #ef4444;
    }

    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      font-family: 'Poppins', sans-serif;
      color: var(--text);
      line-height: 1.6;
    }

    /* ── NAVBAR ─────────────────────────────── */
    .navbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 5%;
      background: rgba(9,9,19,.85);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--border);
    }
    .navbar-brand {
      display: flex; align-items: center; gap: 12px;
      text-decoration: none; color: var(--text);
    }
    .brand-icon {
      width: 38px; height: 38px;
      background: #fff; color: #090913;
      display: flex; align-items: center; justify-content: center;
      border-radius: 10px; font-size: .95rem;
    }
    .brand-name { font-weight: 700; font-size: 1.1rem; }
    .brand-sub  { font-size: .68rem; color: var(--muted); }

    .nav-links { display: flex; align-items: center; gap: 6px; }
    .nav-links a {
      padding: 8px 14px; border-radius: 10px;
      font-size: .82rem; color: #94a3b8;
      text-decoration: none; transition: .2s;
    }
    .nav-links a:hover { color: #fff; background: rgba(255,255,255,.07); }
    .nav-links .btn-nav {
      background: #fff; color: #090913 !important;
      font-weight: 600; padding: 8px 18px;
    }
    .nav-links .btn-nav:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(255,255,255,.12); }

    /* ── HERO ─────────────────────────────── */
    .hero {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      text-align: center;
      padding: 120px 5% 80px;
      background: radial-gradient(ellipse at 20% 50%, #1c1c52 0%, transparent 60%),
                  radial-gradient(ellipse at 80% 20%, #0f1a3d 0%, transparent 55%),
                  var(--bg);
      position: relative; overflow: hidden;
    }
    .hero::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        radial-gradient(circle, rgba(255,255,255,.04) 1px, transparent 1px);
      background-size: 40px 40px;
    }
    .hero-content { position: relative; max-width: 760px; }
    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,.07); border: 1px solid var(--border);
      padding: 6px 16px; border-radius: 50px;
      font-size: .75rem; color: #94a3b8; margin-bottom: 28px;
    }
    .hero-badge span { width: 6px; height: 6px; background: var(--green); border-radius: 50%; display: block; animation: pulse 2s infinite; }
    @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.4;} }

    .hero h1 {
      font-size: clamp(2rem, 5vw, 3.4rem);
      font-weight: 800; line-height: 1.15;
      margin-bottom: 20px;
    }
    .hero h1 span { color: #93c5fd; }
    .hero p {
      font-size: 1rem; color: #94a3b8;
      max-width: 580px; margin: 0 auto 36px;
    }
    .hero-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
    .btn-primary {
      padding: 14px 28px; background: #fff; color: #090913;
      border-radius: 12px; font-weight: 700; font-size: .9rem;
      text-decoration: none; transition: .3s; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(255,255,255,.15); }
    .btn-outline {
      padding: 14px 28px; background: transparent;
      border: 1px solid var(--border); color: #cbd5e1;
      border-radius: 12px; font-weight: 500; font-size: .9rem;
      text-decoration: none; transition: .3s; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-outline:hover { border-color: rgba(255,255,255,.3); color: #fff; background: rgba(255,255,255,.05); }

    /* ── STATS BAR ─────────────────────────── */
    .stats-bar {
      background: var(--bg2);
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      padding: 36px 5%;
    }
    .stats-grid {
      display: grid; grid-template-columns: repeat(4, 1fr);
      gap: 20px; max-width: 900px; margin: 0 auto; text-align: center;
    }
    .stat-num { font-size: 2rem; font-weight: 800; color: #fff; }
    .stat-label { font-size: .78rem; color: var(--muted); margin-top: 4px; }

    /* ── SECTION WRAPPER ─────────────────────── */
    .section { padding: 80px 5%; }
    .section-alt { background: var(--bg2); }
    .section-container { max-width: 1100px; margin: 0 auto; }
    .section-header { text-align: center; margin-bottom: 48px; }
    .section-header h2 { font-size: 1.7rem; font-weight: 700; margin-bottom: 10px; }
    .section-header p { color: var(--muted); font-size: .9rem; }

    /* ── BENEFIT CARDS ─────────────────────── */
    .benefit-grid {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
    }
    .benefit-card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 18px; padding: 30px 24px;
      transition: .3s;
    }
    .benefit-card:hover { border-color: rgba(255,255,255,.2); transform: translateY(-4px); }
    .benefit-icon {
      width: 48px; height: 48px;
      background: rgba(255,255,255,.08); border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; margin-bottom: 18px; color: var(--blue);
    }
    .benefit-card h4 { font-size: .95rem; font-weight: 600; margin-bottom: 10px; }
    .benefit-card p  { font-size: .82rem; color: var(--muted); line-height: 1.7; }

    /* ── TIMELINE ─────────────────────────── */
    .timeline { display: flex; flex-direction: column; gap: 0; position: relative; max-width: 700px; margin: 0 auto; }
    .timeline::before {
      content: ''; position: absolute; left: 24px; top: 0; bottom: 0;
      width: 1px; background: var(--border);
    }
    .timeline-item {
      display: flex; gap: 24px; align-items: flex-start;
      padding: 0 0 28px 0; position: relative;
    }
    .tl-num {
      width: 48px; height: 48px; min-width: 48px;
      background: var(--bg2); border: 1px solid var(--border);
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: .85rem; color: var(--blue); z-index: 1;
    }
    .tl-body { padding-top: 8px; }
    .tl-body h4 { font-size: .95rem; font-weight: 600; margin-bottom: 6px; }
    .tl-body p  { font-size: .82rem; color: var(--muted); line-height: 1.7; }

    /* ── MITRA GRID ─────────────────────────── */
    .mitra-grid {
      display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
    }
    .mitra-card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 16px; padding: 24px 18px; text-align: center; transition: .3s;
    }
    .mitra-card:hover { border-color: rgba(255,255,255,.2); transform: translateY(-3px); }
    .mitra-icon {
      width: 44px; height: 44px;
      background: rgba(239,68,68,.1); color: var(--red);
      border-radius: 12px; display: flex; align-items: center; justify-content: center;
      font-size: 1rem; margin: 0 auto 14px;
    }
    .mitra-card h4 { font-size: .85rem; font-weight: 600; margin-bottom: 8px; }
    .mitra-info { font-size: .75rem; color: var(--muted); margin-bottom: 12px; }
    .btn-sm {
      padding: 6px 14px; background: rgba(255,255,255,.08);
      border: 1px solid var(--border); color: #cbd5e1;
      border-radius: 8px; font-size: .75rem; text-decoration: none; transition: .2s;
      display: inline-block;
    }
    .btn-sm:hover { background: rgba(255,255,255,.14); color: #fff; }

    /* ── LAPORAN GRID ─────────────────────────── */
    .laporan-grid {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
    }
    .laporan-card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 18px; padding: 26px 22px;
      display: flex; flex-direction: column; gap: 10px; transition: .3s;
    }
    .laporan-card:hover { border-color: rgba(255,255,255,.2); transform: translateY(-3px); }
    .laporan-meta { font-size: .73rem; color: var(--muted); }
    .laporan-card h4 { font-size: .9rem; font-weight: 600; line-height: 1.5; flex-grow: 1; }
    .laporan-author { font-size: .8rem; color: var(--blue); }
    .btn-laporan {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 9px 16px; background: rgba(255,255,255,.07);
      border: 1px solid var(--border); border-radius: 10px;
      color: #cbd5e1; font-size: .8rem; text-decoration: none; transition: .2s;
      margin-top: 4px; width: fit-content;
    }
    .btn-laporan:hover { background: rgba(255,255,255,.14); color: #fff; }

    /* ── FAQ ─────────────────────────────────── */
    .faq-list { max-width: 720px; margin: 0 auto; display: flex; flex-direction: column; gap: 10px; }
    .faq-item {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 14px; overflow: hidden; transition: .2s;
    }
    .faq-item.active { border-color: rgba(255,255,255,.2); }
    .faq-q {
      display: flex; justify-content: space-between; align-items: center;
      padding: 18px 22px; cursor: pointer; gap: 16px;
    }
    .faq-q span { font-size: .88rem; font-weight: 500; }
    .faq-q i { color: var(--muted); transition: .3s; font-size: .8rem; flex-shrink: 0; }
    .faq-item.active .faq-q i { transform: rotate(180deg); }
    .faq-a { display: none; padding: 0 22px 18px; font-size: .83rem; color: var(--muted); line-height: 1.7; }
    .faq-item.active .faq-a { display: block; }

    /* ── CTA ─────────────────────────────────── */
    .cta-section {
      padding: 80px 5%; text-align: center;
      background: radial-gradient(ellipse at center, #1c1c52 0%, var(--bg) 70%);
      border-top: 1px solid var(--border);
    }
    .cta-section h2 { font-size: 1.8rem; font-weight: 700; margin-bottom: 14px; }
    .cta-section p  { color: var(--muted); margin-bottom: 32px; }

    /* ── FOOTER ─────────────────────────────── */
    footer {
      background: var(--bg2);
      border-top: 1px solid var(--border);
      padding: 50px 5% 28px;
    }
    .footer-grid {
      display: grid; grid-template-columns: 2fr 1fr 1fr;
      gap: 40px; max-width: 1000px; margin: 0 auto 40px;
    }
    .footer-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .footer-brand .brand-icon { width: 34px; height: 34px; font-size: .85rem; }
    footer h4 { font-size: .9rem; font-weight: 600; margin-bottom: 14px; }
    footer p, footer a { font-size: .82rem; color: var(--muted); }
    .footer-links { display: flex; flex-direction: column; gap: 8px; }
    .footer-links a { text-decoration: none; transition: .2s; }
    .footer-links a:hover { color: #fff; }
    .footer-contact p { margin-bottom: 8px; display: flex; align-items: flex-start; gap: 8px; }
    .footer-contact i { margin-top: 3px; color: #475569; flex-shrink: 0; }
    .footer-bottom {
      border-top: 1px solid var(--border); padding-top: 20px;
      text-align: center; font-size: .77rem; color: var(--muted);
      max-width: 1000px; margin: 0 auto;
    }

    /* ── RESPONSIVE ─────────────────────────── */
    @media (max-width: 900px) {
      .benefit-grid, .laporan-grid { grid-template-columns: 1fr 1fr; }
      .mitra-grid { grid-template-columns: 1fr 1fr; }
      .stats-grid { grid-template-columns: 1fr 1fr; }
      .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 600px) {
      .benefit-grid, .laporan-grid, .mitra-grid { grid-template-columns: 1fr; }
      .stats-grid { grid-template-columns: 1fr 1fr; }
      .footer-grid { grid-template-columns: 1fr; }
      .nav-links a:not(.btn-nav) { display: none; }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="index.php" class="navbar-brand">
    <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
    <div>
      <div class="brand-name">SIMPKL</div>
      <div class="brand-sub">Sistem Informasi PKL</div>
    </div>
  </a>
  <div class="nav-links">
    <a href="index.php"><i class="fas fa-house"></i> Beranda</a>
    <a href="#about-section">Tentang Kami</a>
    <a href="#alur-section">Alur Sistem</a>
    <a href="#mitra-section">Mitra PKL</a>
    <a href="#laporan-section">Kumpulan Laporan</a>
    <?php if ($is_logged_in): ?>
      <a href="<?= $user_role ?>/dashboard.php" class="btn-nav"><i class="fas fa-gauge"></i> Dashboard</a>
    <?php else: ?>
      <a href="login.php" class="btn-nav"><i class="fas fa-right-to-bracket"></i> Login</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-badge">
      <span></span> Sistem Aktif &amp; Terintegrasi
    </div>
    <h1>Sistem Monitoring <span>Praktik Kerja Lapangan</span></h1>
    <p>Platform terintegrasi untuk mengelola, memonitor, dan mempublikasikan seluruh kegiatan PKL siswa SMK Hebat.</p>
    <div class="hero-btns">
      <a href="registrasi.php" class="btn-primary"><i class="fas fa-user-plus"></i> Daftar PKL Sekarang</a>
      <a href="#about-section" class="btn-outline"><i class="fas fa-circle-info"></i> Pelajari Lebih Lanjut</a>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats-bar">
  <div class="stats-grid">
    <div><div class="stat-num">350+</div><div class="stat-label">Siswa Aktif PKL</div></div>
    <div><div class="stat-num">42</div><div class="stat-label">Mitra Perusahaan</div></div>
    <div><div class="stat-num">15</div><div class="stat-label">Guru Pembimbing</div></div>
    <div><div class="stat-num">99%</div><div class="stat-label">Laporan Tervalidasi</div></div>
  </div>
</div>

<!-- TENTANG -->
<section class="section" id="about-section">
  <div class="section-container">
    <div class="section-header">
      <h2><i class="fas fa-graduation-cap" style="color:#93c5fd;"></i> Tentang PKL Digital &amp; Manfaat Platform</h2>
      <p>Platform terintegrasi yang memastikan transparansi dan kemudahan dalam seluruh proses Praktik Kerja Lapangan.</p>
    </div>
    <div class="benefit-grid">
      <div class="benefit-card">
        <div class="benefit-icon"><i class="fas fa-check-double"></i></div>
        <h4>Validasi Berjenjang</h4>
        <p>Memastikan setiap tahapan divalidasi oleh Pembimbing dan Wakasek Hubin secara terstruktur.</p>
      </div>
      <div class="benefit-card">
        <div class="benefit-icon" style="color:#c4b5fd; background:rgba(196,181,253,.1);"><i class="fas fa-chart-line"></i></div>
        <h4>Monitoring Real-time</h4>
        <p>Guru Pembimbing dapat memonitor absensi dan jurnal harian siswa secara langsung.</p>
      </div>
      <div class="benefit-card">
        <div class="benefit-icon" style="color:#4ade80; background:rgba(74,222,128,.1);"><i class="fas fa-cloud-arrow-up"></i></div>
        <h4>Arsip Digital Terpusat</h4>
        <p>Seluruh laporan dan dokumen PKL tersimpan aman, mudah dicari, dan siap dipublikasikan.</p>
      </div>
    </div>
  </div>
</section>

<!-- ALUR -->
<section class="section section-alt" id="alur-section">
  <div class="section-container">
    <div class="section-header">
      <h2><i class="fas fa-route" style="color:#93c5fd;"></i> Alur Lengkap Sistem PKL Digital</h2>
      <p>Ikuti langkah-langkah sederhana dari registrasi hingga publikasi laporan.</p>
    </div>
    <div class="timeline">
      <div class="timeline-item">
        <div class="tl-num">1</div>
        <div class="tl-body">
          <h4>Registrasi &amp; Profil</h4>
          <p>Siswa melakukan registrasi sederhana, kemudian login dan wajib melengkapi profil pribadi.</p>
        </div>
      </div>
      <div class="timeline-item">
        <div class="tl-num">2</div>
        <div class="tl-body">
          <h4>Pendaftaran Kelompok</h4>
          <p>Siswa mendaftar PKL, bisa perorangan atau membentuk kelompok (1–6 siswa) dengan form dinamis.</p>
        </div>
      </div>
      <div class="timeline-item">
        <div class="tl-num">3</div>
        <div class="tl-body">
          <h4>Validasi Pendaftaran</h4>
          <p>Pembimbing meninjau dan Wakasek Hubin memberikan validasi akhir pada pendaftaran kelompok.</p>
        </div>
      </div>
      <div class="timeline-item">
        <div class="tl-num">4</div>
        <div class="tl-body">
          <h4>Periode PKL — Jurnal &amp; Absensi</h4>
          <p>Siswa mengisi absensi dan jurnal harian secara online, dimonitor langsung oleh Pembimbing.</p>
        </div>
      </div>
      <div class="timeline-item">
        <div class="tl-num">5</div>
        <div class="tl-body">
          <h4>Upload dan Validasi Laporan</h4>
          <p>Siswa mengunggah laporan PKL, kemudian divalidasi oleh Pembimbing dan Wakasek.</p>
        </div>
      </div>
      <div class="timeline-item" style="padding-bottom:0;">
        <div class="tl-num" style="color:#4ade80; border-color:#4ade80;">6</div>
        <div class="tl-body">
          <h4>Publikasi Laporan</h4>
          <p>Laporan yang sudah disetujui Wakasek dapat dipublikasikan ke halaman umum website.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- MITRA -->
<section class="section" id="mitra-section">
  <div class="section-container">
    <div class="section-header">
      <h2><i class="fas fa-building" style="color:#ef4444;"></i> Mitra Perusahaan Resmi Kami</h2>
      <p>Penempatan siswa dengan perusahaan terkemuka yang telah divalidasi oleh Hubin Sekolah.</p>
    </div>
    <div class="mitra-grid">
      <?php while($mitra = mysqli_fetch_assoc($query_mitra)): ?>
      <div class="mitra-card">
        <div class="mitra-icon"><i class="fas fa-industry"></i></div>
        <h4><?= htmlspecialchars($mitra['nama_perusahaan']) ?></h4>
        <div class="mitra-info"><i class="fas fa-location-dot"></i> <?= htmlspecialchars(substr($mitra['alamat_perusahaan'], 0, 30)) ?>...</div>
        <?php if(!empty($mitra['website'])): ?>
          <a href="<?= $mitra['website'] ?>" target="_blank" class="btn-sm"><i class="fas fa-arrow-up-right-from-square"></i> Website</a>
        <?php endif; ?>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- LAPORAN -->
<section class="section section-alt" id="laporan-section">
  <div class="section-container">
    <div class="section-header">
      <h2><i class="fas fa-book-bookmark" style="color:#4ade80;"></i> Laporan PKL Terbaik</h2>
      <p>Karya terbaik siswa yang telah menyelesaikan dan mempublikasikan laporan PKL mereka.</p>
    </div>
    <?php if(mysqli_num_rows($result_laporan) > 0): ?>
    <div class="laporan-grid">
      <?php while($row = mysqli_fetch_assoc($result_laporan)): ?>
      <div class="laporan-card">
        <div class="laporan-meta"><?= htmlspecialchars($row['jurusan'] ?? 'Jurusan') ?> &bull; <?= date('Y', strtotime($row['created_at'])) ?></div>
        <h4><?= htmlspecialchars($row['judul_laporan']) ?></h4>
        <div class="laporan-author"><i class="fas fa-user-graduate"></i> <?= htmlspecialchars($row['nama_depan'] . ' ' . $row['nama_belakang']) ?></div>
        <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn-laporan">
          <i class="fas fa-eye"></i> Lihat Laporan
        </a>
      </div>
      <?php endwhile; ?>
    </div>
    <?php else: ?>
      <div style="text-align:center; padding:40px; color:var(--muted);">
        <i class="fas fa-search" style="font-size:2.5rem; margin-bottom:14px; display:block;"></i>
        Belum ada laporan yang dipublikasikan.
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- FAQ -->
<section class="section" id="faq-section">
  <div class="section-container">
    <div class="section-header">
      <h2><i class="fas fa-circle-question" style="color:#c4b5fd;"></i> Pertanyaan Umum (FAQ)</h2>
      <p>Temukan jawaban cepat mengenai penggunaan dan fitur utama sistem PKL Digital.</p>
    </div>
    <div class="faq-list">
      <div class="faq-item">
        <div class="faq-q"><span>Siapa saja pengguna platform PKL Digital ini?</span><i class="fas fa-chevron-down"></i></div>
        <div class="faq-a">Pengguna utama adalah Siswa, Guru Pembimbing, Admin, dan Wakasek Hubin.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q"><span>Apakah siswa bisa mendaftar PKL secara berkelompok?</span><i class="fas fa-chevron-down"></i></div>
        <div class="faq-a">Ya, siswa dapat mendaftar sendiri atau membentuk kelompok yang terdiri dari 1 hingga 6 siswa. Form pendaftaran kelompok bersifat dinamis.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q"><span>Apa yang harus saya lakukan setelah registrasi akun?</span><i class="fas fa-chevron-down"></i></div>
        <div class="faq-a">Setelah login pertama kali, siswa wajib melengkapi data profil (NIS, Jurusan, dll.) sebelum dapat mengakses fitur pendaftaran PKL.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q"><span>Bagaimana alur validasi laporan PKL?</span><i class="fas fa-chevron-down"></i></div>
        <div class="faq-a">Laporan yang diunggah siswa akan divalidasi oleh Guru Pembimbing, kemudian dikirim ke Wakasek Hubin untuk validasi akhir.</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div>
    <h2>Siap Mengelola PKL Lebih Profesional?</h2>
    <p>Bergabunglah dengan ratusan siswa dan guru yang sudah menggunakan SIMPKL.</p>
    <a href="login.php" class="btn-primary" style="display:inline-flex; margin: 0 auto;">
      <i class="fas fa-arrow-right-to-bracket"></i> Masuk ke Sistem
    </a>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-grid">
    <div>
      <div class="footer-brand">
        <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
        <div>
          <div class="brand-name" style="font-size:.95rem; font-weight:700;">SIMPKL</div>
          <div style="font-size:.68rem; color:var(--muted);">PKL Digital SMK Hebat</div>
        </div>
      </div>
      <p style="max-width:280px; line-height:1.7;">Platform manajemen PKL terintegrasi yang memastikan transparansi dan efisiensi untuk siswa, guru, dan manajemen sekolah.</p>
    </div>
    <div>
      <h4>Akses Cepat</h4>
      <div class="footer-links">
        <a href="#about-section">Tentang Kami</a>
        <a href="#alur-section">Alur Sistem</a>
        <a href="#mitra-section">Mitra PKL Resmi</a>
        <a href="#laporan-section">Kumpulan Laporan</a>
        <a href="#faq-section">Pertanyaan Umum</a>
      </div>
    </div>
    <div>
      <h4>Hubungi Kami</h4>
      <div class="footer-contact">
        <p><i class="fas fa-map-location-dot"></i> Jl. Raya Digital No. 10, Kota Aplikasi</p>
        <p><i class="fas fa-envelope"></i> hubin@smkhebat.sch.id</p>
        <p><i class="fas fa-tty"></i> (022) 123-456</p>
        <p><i class="fas fa-mobile-screen"></i> +62 812-3456-7890</p>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    &copy; <?= date('Y') ?>  <i"> Kita Hebat Kita Pasti Bisa</i> 
  </div>
</footer>

<script>
  // FAQ Accordion
  document.querySelectorAll('.faq-q').forEach(el => {
    el.addEventListener('click', () => {
      const item = el.closest('.faq-item');
      document.querySelectorAll('.faq-item').forEach(i => { if (i !== item) i.classList.remove('active'); });
      item.classList.toggle('active');
    });
  });

  // Smooth navbar scroll effect
  window.addEventListener('scroll', () => {
    document.querySelector('.navbar').style.background =
      window.scrollY > 40 ? 'rgba(9,9,19,.97)' : 'rgba(9,9,19,.85)';
  });
</script>
</body>
</html>