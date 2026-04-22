<?php
session_start();
include "../config.php";
requireAdmin();

// ---- Statistik dari tabel database webpkl ----
// users: semua pengguna terdaftar
$total_users      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];

// Siswa = users dengan role 'siswa'
$total_siswa      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='siswa'"))['c'];

// Pembimbing = users dengan role 'pembimbing'
$total_pembimbing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='pembimbing'"))['c'];

// Perusahaan mitra
$total_perusahaan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM mitra_industri"))['c'];

// Jurnal harian (menggantikan kegiatan)
$total_jurnal     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM jurnal_harian"))['c'];

// Pengajuan PKL
$total_pengajuan  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM pkl_pengajuan"))['c'];

// Laporan PKL
$total_laporan    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM laporan_pkl"))['c'];


// Laporan pending (menunggu review)
$total_laporan_pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM laporan_pkl WHERE status_pembimbing='pending'"))['c'];

$active_page = 'dashboard';
$page_title  = 'Dashboard Admin';
include '_header_admin.php';
?>

<!-- Welcome -->
<div class="welcome-section delay-1">
  <div>
    <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user']['nama']); ?> 👋</h1>
    <p>Pusat pengelolaan data Sistem Informasi PKL.</p>
  </div>
  <div class="status-badge">
    <div class="status-dot"></div>
    Online
  </div>
</div>

<!-- Stat Cards -->
<div class="section-title">Ringkasan Data</div>
<div class="stat-grid">

  <div class="stat-card delay-1">
    <div class="card-icon">
      <i class="fas fa-user-graduate" style="color:#f1f5f9;"></i>
    </div>
    <h3>Total Siswa</h3>
    <div class="number"><?php echo $total_siswa; ?></div>
    <div class="trend">Siswa terdaftar</div>
  </div>

  <div class="stat-card delay-2">
    <div class="card-icon">
      <i class="fas fa-chalkboard-teacher" style="color:#f59e0b;"></i>
    </div>
    <h3>Pembimbing</h3>
    <div class="number" style="color:#f59e0b;"><?php echo $total_pembimbing; ?></div>
    <div class="trend">Guru pembimbing PKL</div>
  </div>

  <div class="stat-card delay-3">
    <div class="card-icon">
      <i class="fas fa-building" style="color:#ef4444;"></i>
    </div>
    <h3>Perusahaan Mitra</h3>
    <div class="number" style="color:#ef4444;"><?php echo $total_perusahaan; ?></div>
    <div class="trend">Mitra industri PKL</div>
  </div>

  <div class="stat-card delay-4">
    <div class="card-icon">
      <i class="fas fa-file-signature" style="color:#93c5fd;"></i>
    </div>
    <h3>Pengajuan PKL</h3>
    <div class="number" style="color:#93c5fd;"><?php echo $total_pengajuan; ?></div>
    <div class="trend">Total pengajuan masuk</div>
  </div>

  <div class="stat-card delay-5">
    <div class="card-icon">
      <i class="fas fa-book-open" style="color:#c4b5fd;"></i>
    </div>
    <h3>Jurnal Harian</h3>
    <div class="number" style="color:#c4b5fd;"><?php echo $total_jurnal; ?></div>
    <div class="trend">Total entri jurnal</div>
  </div>

  <div class="stat-card delay-6">
    <div class="card-icon">
      <i class="fas fa-file-alt" style="color:#4ade80;"></i>
    </div>
    <h3>Laporan PKL</h3>
    <div class="number" style="color:#4ade80;"><?php echo $total_laporan; ?></div>
    <div class="trend"><?php echo $total_laporan_pending; ?> menunggu review</div>
  </div>

</div>

<!-- Quick Actions -->
<div class="section-title delay-2">Aksi Cepat</div>
<div class="quick-grid">
  <a href="admin-users.php"       class="quick-card delay-1"><i class="fas fa-users-cog"></i>  Kelola User</a>
  <a href="admin-mitra.php"       class="quick-card delay-2"><i class="fas fa-city"></i>        Tambah Mitra</a>
  <a href="pkl-pengajuan.php"     class="quick-card delay-3"><i class="fas fa-file-signature"></i> Cek Pengajuan</a>
  <a href="pkl-laporan.php"       class="quick-card delay-4"><i class="fas fa-file-alt"></i>   Lihat Laporan</a>
  <a href="pkl-jurnal.php"        class="quick-card delay-5"><i class="fas fa-book-open"></i>   Lihat Jurnal</a>
  <a href="pkl-nilai.php"         class="quick-card delay-6"><i class="fas fa-star"></i>        Input Nilai</a>
</div>

<!-- Jurnal Terbaru -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:24px;">

  <div class="panel delay-3">
    <div class="panel-header">
      <h3><i class="fas fa-book-open" style="color:#c4b5fd;"></i> Jurnal Terbaru</h3>
      <a href="pkl-jurnal.php">Lihat semua</a>
    </div>
    <?php
    // JOIN ke profil_siswa untuk nama, tapi cukup ambil dari users
    $res_jurnal = mysqli_query($conn,
        "SELECT j.kegiatan, j.tanggal, j.status_validasi,
                u.nama_depan, u.nama_belakang
         FROM jurnal_harian j
         LEFT JOIN users u ON j.siswa_id = u.id
         ORDER BY j.id DESC LIMIT 5");
    if (mysqli_num_rows($res_jurnal) === 0): ?>
      <p style="text-align:center;padding:20px 0;color:#64748b;font-size:.82rem;">Belum ada jurnal.</p>
    <?php else: while ($jrn = mysqli_fetch_assoc($res_jurnal)): ?>
    <div class="activity-item">
      <div class="activity-icon">
        <i class="fas fa-book" style="color:#c4b5fd;"></i>
      </div>
      <div class="activity-text">
        <p><?php echo htmlspecialchars(mb_strimwidth($jrn['kegiatan'], 0, 60, '...')); ?></p>
        <span>
          <?php echo htmlspecialchars($jrn['nama_depan'] . ' ' . $jrn['nama_belakang']); ?>
          &mdash; <?php echo date('d M Y', strtotime($jrn['tanggal'])); ?>
          &mdash;
          <?php
            $badge = ['pending'=>'#f59e0b','valid'=>'#4ade80','tolak'=>'#ef4444'];
            $st    = $jrn['status_validasi'];
            echo "<span style='color:{$badge[$st]};'>" . ucfirst($st) . "</span>";
          ?>
        </span>
      </div>
    </div>
    <?php endwhile; endif; ?>
  </div>

  <!-- Laporan Pending -->
  <div class="panel delay-4">
    <div class="panel-header">
      <h3><i class="fas fa-file-alt" style="color:#4ade80;"></i> Laporan Menunggu Review</h3>
      <a href="pkl-laporan.php">Lihat semua</a>
    </div>
    <?php
    $res_laporan = mysqli_query($conn,
        "SELECT l.judul_laporan, l.jenis_laporan, l.created_at,
                u.nama_depan, u.nama_belakang
         FROM laporan_pkl l
         LEFT JOIN users u ON l.siswa_id = u.id
         WHERE l.status_pembimbing = 'pending'
         ORDER BY l.id DESC LIMIT 5");
    if (mysqli_num_rows($res_laporan) === 0): ?>
      <p style="text-align:center;padding:20px 0;color:#64748b;font-size:.82rem;">Tidak ada laporan pending.</p>
    <?php else: while ($lpr = mysqli_fetch_assoc($res_laporan)): ?>
    <div class="activity-item">
      <div class="activity-icon">
        <i class="fas fa-file-alt" style="color:#4ade80;"></i>
      </div>
      <div class="activity-text">
        <p><?php echo htmlspecialchars($lpr['judul_laporan'] ?? '(Tanpa judul)'); ?>
           <span style="color:#94a3b8;font-size:.75rem;">[<?= ucfirst($lpr['jenis_laporan']) ?>]</span>
        </p>
        <span>
          <?php echo htmlspecialchars($lpr['nama_depan'] . ' ' . $lpr['nama_belakang']); ?>
          &mdash; <?php echo date('d M Y', strtotime($lpr['created_at'])); ?>
        </span>
      </div>
    </div>
    <?php endwhile; endif; ?>
  </div>

</div>

<!-- Status Sistem -->
<div class="panel delay-5" style="margin-top:24px; margin-bottom:0;">
  <div class="panel-header">
    <h3><i class="fas fa-server" style="color:#94a3b8;"></i> Status Sistem</h3>
    <span style="font-size:.75rem;color:#4ade80;">● Semua normal</span>
  </div>
  <div class="status-list">
    <div class="status-row">
      <span>Database</span>
      <div class="progress-bar"><div class="progress-fill" style="width:38%;background:#f1f5f9;"></div></div>
      <div class="val">38% digunakan</div>
    </div>
    <div class="status-row">
      <span>Penyimpanan</span>
      <div class="progress-bar"><div class="progress-fill" style="width:54%;background:#f59e0b;"></div></div>
      <div class="val">54% digunakan</div>
    </div>
    <div class="status-row">
      <span>Memori</span>
      <div class="progress-bar"><div class="progress-fill" style="width:25%;background:#4ade80;"></div></div>
      <div class="val">25% digunakan</div>
    </div>
    <div class="status-row">
      <span>Uptime</span>
      <div class="progress-bar"><div class="progress-fill" style="width:99%;background:#93c5fd;"></div></div>
      <div class="val">99.9%</div>
    </div>
  </div>
</div>

<?php include '_footer_admin.php'; ?>
