<?php
session_start();
include "../config.php";
requirePembimbing();

$pid = (int)$_SESSION['user']['id_user'];

$total_siswa_bimbing = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT ketua_id) c FROM pkl_pengajuan WHERE pembimbing_id = $pid"))['c'];

$total_jurnal_pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM jurnal_harian j
     INNER JOIN pkl_pengajuan p ON j.siswa_id = p.ketua_id
     WHERE p.pembimbing_id = $pid AND j.status_validasi = 'pending'"))['c'];

$total_jurnal_valid = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM jurnal_harian j
     INNER JOIN pkl_pengajuan p ON j.siswa_id = p.ketua_id
     WHERE p.pembimbing_id = $pid AND j.status_validasi = 'valid'"))['c'];

$total_laporan_pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM laporan_pkl l
     INNER JOIN pkl_pengajuan p ON l.siswa_id = p.ketua_id
     WHERE p.pembimbing_id = $pid AND l.status_pembimbing = 'pending'"))['c'];

$total_laporan_approved = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM laporan_pkl l
     INNER JOIN pkl_pengajuan p ON l.siswa_id = p.ketua_id
     WHERE p.pembimbing_id = $pid AND l.status_pembimbing = 'disetujui'"))['c'];

$total_nilai = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM nilai_pkl WHERE pembimbing_id=$pid"))['c'];

$active_page = 'dashboard';
$page_title  = 'Dashboard Pembimbing';
include '_header_pembimbing.php';
?>

<div class="welcome-section delay-1">
  <div>
    <h1><i class="fas fa-hand-wave" style="color:#f59e0b;margin-right:8px;"></i>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user']['nama']); ?>!</h1>
    <p>Panel monitoring & bimbingan PKL — pantau perkembangan siswa Anda hari ini.</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <div class="status-badge">
      <div class="status-dot"></div> Online
    </div>
    <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:99px;
      padding:8px 16px;font-size:.78rem;color:#64748b;">
      <i class="fas fa-calendar-day" style="color:#6366f1;margin-right:5px;"></i>
      <?= date('l, d F Y') ?>
    </div>
  </div>
</div>

<div class="section-title"><i class="fas fa-chart-pie" style="margin-right:6px;color:#6366f1;"></i> Ringkasan Bimbingan</div>
<div class="stat-grid">

  <div class="stat-card delay-1">
    <div class="card-icon" style="background:rgba(99,102,241,.12);">
      <i class="fas fa-user-graduate" style="color:#6366f1;"></i>
    </div>
    <h3>Siswa Bimbingan</h3>
    <div class="number"><?= $total_siswa_bimbing ?></div>
    <div class="trend"><i class="fas fa-users" style="margin-right:3px;color:#6366f1;"></i>Total siswa terdaftar</div>
  </div>

  <div class="stat-card delay-2">
    <div class="card-icon" style="background:rgba(245,158,11,.12);">
      <i class="fas fa-hourglass-half" style="color:#f59e0b;"></i>
    </div>
    <h3>Jurnal Menunggu</h3>
    <div class="number" style="color:#f59e0b;"><?= $total_jurnal_pending ?></div>
    <div class="trend"><i class="fas fa-circle-check" style="color:#4ade80;margin-right:3px;"></i><?= $total_jurnal_valid ?> sudah divalidasi</div>
  </div>

  <div class="stat-card delay-3">
    <div class="card-icon" style="background:rgba(239,68,68,.12);">
      <i class="fas fa-file-pen" style="color:#ef4444;"></i>
    </div>
    <h3>Laporan Pending</h3>
    <div class="number" style="color:#ef4444;"><?= $total_laporan_pending ?></div>
    <div class="trend"><i class="fas fa-circle-check" style="color:#4ade80;margin-right:3px;"></i><?= $total_laporan_approved ?> sudah disetujui</div>
  </div>

  <div class="stat-card delay-4">
    <div class="card-icon" style="background:rgba(74,222,128,.12);">
      <i class="fas fa-star-half-stroke" style="color:#4ade80;"></i>
    </div>
    <h3>Nilai Diinput</h3>
    <div class="number" style="color:#4ade80;"><?= $total_nilai ?></div>
    <div class="trend"><i class="fas fa-list-check" style="color:#4ade80;margin-right:3px;"></i>Siswa sudah dinilai</div>
  </div>

</div>

<div class="section-title delay-2"><i class="fas fa-bolt" style="margin-right:6px;color:#f59e0b;"></i> Aksi Cepat</div>
<div class="quick-grid">
  <a href="jurnal_pembimbing.php" class="quick-card delay-1">
    <i class="fas fa-book-open-reader" style="color:#f59e0b;"></i> Validasi Jurnal
    <?php if ($total_jurnal_pending > 0): ?>
    <span style="margin-left:auto;background:rgba(245,158,11,.15);color:#f59e0b;border:1px solid rgba(245,158,11,.3);border-radius:99px;font-size:.7rem;padding:2px 8px;font-weight:600;">
      <?= $total_jurnal_pending ?> baru
    </span>
    <?php endif; ?>
  </a>
  <a href="absensi_pembimbing.php" class="quick-card delay-2">
    <i class="fas fa-calendar-check" style="color:#4ade80;"></i> Cek Absensi
  </a>
  <a href="daftar_siswa_pembimbing.php" class="quick-card delay-3">
    <i class="fas fa-users" style="color:#6366f1;"></i> Daftar Siswa
  </a>
  <a href="input_nilai.php" class="quick-card delay-4">
    <i class="fas fa-star-half-stroke" style="color:#f59e0b;"></i> Input Nilai
  </a>
  <a href="kompetensi.php" class="quick-card delay-5">
    <i class="fas fa-list-check" style="color:#a78bfa;"></i> Kompetensi
  </a>
  <a href="log_aktivitas_pembimbing.php" class="quick-card delay-6">
    <i class="fas fa-clock-rotate-left" style="color:#93c5fd;"></i> Log Aktivitas
  </a>
</div>

<div class="two-col-panels" style="margin-top:24px;">

  <div class="panel delay-3">
    <div class="panel-header">
      <h3><i class="fas fa-hourglass-half" style="color:#f59e0b;"></i> Jurnal Menunggu Validasi</h3>
      <a href="jurnal_pembimbing.php" style="font-size:.78rem;color:#64748b;display:flex;align-items:center;gap:4px;">
        Lihat semua <i class="fas fa-arrow-right" style="font-size:.7rem;"></i>
      </a>
    </div>
    <?php
    $res_jurnal = mysqli_query($conn,
        "SELECT j.id, j.kegiatan, j.tanggal, u.nama_depan, u.nama_belakang
         FROM jurnal_harian j
         INNER JOIN pkl_pengajuan p ON j.siswa_id = p.ketua_id
         LEFT JOIN users u ON j.siswa_id = u.id
         WHERE p.pembimbing_id = $pid AND j.status_validasi = 'pending'
         ORDER BY j.id DESC LIMIT 5");
    if (!$res_jurnal || mysqli_num_rows($res_jurnal) === 0): ?>
      <p style="text-align:center;padding:20px 0;color:#334155;font-size:.82rem;">
        <i class="fas fa-check-circle" style="color:#4ade80;"></i>&nbsp; Semua jurnal sudah tervalidasi.
      </p>
    <?php else: while ($jrn = mysqli_fetch_assoc($res_jurnal)): ?>
    <div class="activity-item">
      <div class="activity-icon" style="background:rgba(245,158,11,.1);">
        <i class="fas fa-book-open" style="color:#f59e0b;font-size:.85rem;"></i>
      </div>
      <div style="flex:1;">
        <p style="margin-bottom:2px;font-size:.82rem;">
          <?= htmlspecialchars(mb_strimwidth($jrn['kegiatan'],0,55,'…')) ?>
        </p>
        <span style="font-size:.72rem;color:#475569;">
          <i class="fas fa-user" style="margin-right:2px;"></i>
          <?= htmlspecialchars($jrn['nama_depan'].' '.$jrn['nama_belakang']) ?>
          &mdash; <?= date('d M Y', strtotime($jrn['tanggal'])) ?>
        </span>
      </div>
      <a href="jurnal_pembimbing.php?aksi=valid&id=<?= $jrn['id'] ?>"
         class="btn btn-warning btn-sm" style="white-space:nowrap;">
        <i class="fas fa-check"></i> Validasi
      </a>
    </div>
    <?php endwhile; endif; ?>
  </div>

  <div class="panel delay-4">
    <div class="panel-header">
      <h3><i class="fas fa-file-circle-exclamation" style="color:#ef4444;"></i> Laporan Menunggu Review</h3>
      <a href="#" style="font-size:.78rem;color:#64748b;display:flex;align-items:center;gap:4px;">
        Lihat semua <i class="fas fa-arrow-right" style="font-size:.7rem;"></i>
      </a>
    </div>
    <?php
    $res_laporan = mysqli_query($conn,
        "SELECT l.id, l.judul_laporan, l.jenis_laporan, l.created_at, u.nama_depan, u.nama_belakang
         FROM laporan_pkl l
         INNER JOIN pkl_pengajuan p ON l.siswa_id = p.ketua_id
         LEFT JOIN users u ON l.siswa_id = u.id
         WHERE p.pembimbing_id = $pid AND l.status_pembimbing = 'pending'
         ORDER BY l.id DESC LIMIT 5");
    if (!$res_laporan || mysqli_num_rows($res_laporan) === 0): ?>
      <p style="text-align:center;padding:20px 0;color:#334155;font-size:.82rem;">
        <i class="fas fa-check-circle" style="color:#4ade80;"></i>&nbsp; Tidak ada laporan pending.
      </p>
    <?php else: while ($lpr = mysqli_fetch_assoc($res_laporan)): ?>
    <div class="activity-item">
      <div class="activity-icon" style="background:rgba(239,68,68,.1);">
        <i class="fas fa-file-lines" style="color:#ef4444;font-size:.85rem;"></i>
      </div>
      <div style="flex:1;">
        <p style="margin-bottom:2px;font-size:.82rem;">
          <?= htmlspecialchars($lpr['judul_laporan'] ?? '(Tanpa judul)') ?>
          <span style="color:#475569;font-size:.72rem;">[<?= ucfirst($lpr['jenis_laporan']) ?>]</span>
        </p>
        <span style="font-size:.72rem;color:#475569;">
          <i class="fas fa-user" style="margin-right:2px;"></i>
          <?= htmlspecialchars($lpr['nama_depan'].' '.$lpr['nama_belakang']) ?>
          &mdash; <?= date('d M Y', strtotime($lpr['created_at'])) ?>
        </span>
      </div>
    </div>
    <?php endwhile; endif; ?>
  </div>

</div>

<div class="two-col-panels" style="margin-top:20px;">

  <div class="panel delay-5">
    <div class="panel-header">
      <h3><i class="fas fa-chart-line" style="color:#c4b5fd;"></i> Progress Jurnal Siswa</h3>
      <a href="daftar_siswa_pembimbing.php" style="font-size:.78rem;color:#64748b;display:flex;align-items:center;gap:4px;">
        <i class="fas fa-circle-info"></i> Detail
      </a>
    </div>
    <?php
    $res_progress = mysqli_query($conn,
        "SELECT u.nama_depan, u.nama_belakang,
                SUM(CASE WHEN j.status_validasi='valid' THEN 1 ELSE 0 END) AS valid,
                SUM(CASE WHEN j.status_validasi='pending' THEN 1 ELSE 0 END) AS pending,
                COUNT(j.id) AS total
         FROM pkl_pengajuan p
         LEFT JOIN users u ON p.ketua_id = u.id
         LEFT JOIN jurnal_harian j ON j.siswa_id = p.ketua_id
         WHERE p.pembimbing_id = $pid
         GROUP BY p.ketua_id, u.nama_depan, u.nama_belakang LIMIT 5");
    if (!$res_progress || mysqli_num_rows($res_progress) === 0): ?>
      <p style="text-align:center;padding:20px 0;color:#334155;font-size:.82rem;">Belum ada siswa bimbingan.</p>
    <?php else: while ($prg = mysqli_fetch_assoc($res_progress)):
        $total  = max((int)$prg['total'],1);
        $pct    = round(($prg['valid']/$total)*100);
        $bcol   = $pct>=70?'#4ade80':($pct>=40?'#f59e0b':'#ef4444');
    ?>
    <div style="margin-bottom:14px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
        <span style="font-size:.8rem;color:#cbd5e1;display:flex;align-items:center;gap:5px;">
          <i class="fas fa-user-graduate" style="color:#6366f1;font-size:.72rem;"></i>
          <?= htmlspecialchars($prg['nama_depan'].' '.$prg['nama_belakang']) ?>
        </span>
        <span style="font-size:.72rem;color:#475569;">
          <?= $prg['valid'] ?>/<?= $total ?> jurnal
          <?php if($prg['pending']>0): ?>
          <span style="color:#f59e0b;">(<?= $prg['pending'] ?> pending)</span>
          <?php endif; ?>
        </span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $bcol ?>;"></div>
      </div>
    </div>
    <?php endwhile; endif; ?>
  </div>

  <!-- Log Aktivitas Terbaru -->
  <div class="panel delay-6">
    <div class="panel-header">
      <h3><i class="fas fa-clock-rotate-left" style="color:#93c5fd;"></i> Aktivitas Terbaru</h3>
      <a href="log_aktivitas_pembimbing.php" style="font-size:.78rem;color:#64748b;display:flex;align-items:center;gap:4px;">
        Lihat semua <i class="fas fa-arrow-right" style="font-size:.7rem;"></i>
      </a>
    </div>
    <?php
    $res_log = mysqli_query($conn,
        "SELECT l.aktivitas, l.waktu, CONCAT(u.nama_depan,' ',u.nama_belakang) AS nama, u.peran
         FROM log_aktivitas l LEFT JOIN users u ON l.id_users = u.id
         ORDER BY l.waktu DESC LIMIT 6");
    if (!$res_log || mysqli_num_rows($res_log) === 0): ?>
      <p style="text-align:center;padding:20px 0;color:#334155;font-size:.82rem;">Belum ada log aktivitas.</p>
    <?php else: while($lg = mysqli_fetch_assoc($res_log)):
        $akt = strtolower($lg['aktivitas']);
        if (str_contains($akt,'hapus') || str_contains($akt,'tolak')) { $lc='#ef4444';$li='fa-circle-xmark'; }
        elseif (str_contains($akt,'validasi') || str_contains($akt,'setujui') || str_contains($akt,'simpan') || str_contains($akt,'tambah') || str_contains($akt,'input') || str_contains($akt,'mengirim')) { $lc='#4ade80';$li='fa-circle-check'; }
        else { $lc='#93c5fd';$li='fa-circle-dot'; }
        $peran_icons = ['admin'=>'fa-crown','pembimbing'=>'fa-chalkboard-user','siswa'=>'fa-user-graduate'];
        $pi = $peran_icons[$lg['peran']??''] ?? 'fa-user';
    ?>
    <div class="activity-item">
      <div class="activity-icon" style="background:<?= $lc ?>18;">
        <i class="fas <?= $li ?>" style="color:<?= $lc ?>;font-size:.8rem;"></i>
      </div>
      <div style="flex:1;min-width:0;">
        <p style="font-size:.8rem;margin-bottom:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?= htmlspecialchars(mb_strimwidth($lg['aktivitas'],0,50,'…')) ?>
        </p>
        <span style="font-size:.7rem;color:#334155;">
          <i class="fas <?= $pi ?>" style="margin-right:2px;"></i>
          <?= htmlspecialchars($lg['nama'] ?? 'Sistem') ?>
          &mdash; <?= date('d M H:i', strtotime($lg['waktu'])) ?>
        </span>
      </div>
    </div>
    <?php endwhile; endif; ?>
  </div>

</div>

</body>
</html>
