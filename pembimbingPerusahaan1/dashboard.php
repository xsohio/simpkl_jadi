<?php
require_once 'config.php';
requireRole('pembimbing');
$activePage = 'dashboard';
$pageTitle  = 'Dashboard';
$pid = getPembimbingId();
$pdo = getDB();

// Stats
$siswaList = getSiswaBimbingan($pid);
$totalSiswa = count($siswaList);

$stmtJ = $pdo->prepare("SELECT COUNT(*) FROM jurnal_harian jh JOIN pkl_anggota pa ON pa.siswa_id=jh.siswa_id JOIN pkl_pengajuan pp ON pp.id=pa.pengajuan_id WHERE pp.pembimbing_id=? AND jh.status_validasi='pending'");
$stmtJ->execute([$pid]); $pendingJurnal = $stmtJ->fetchColumn();

$stmtL = $pdo->prepare("SELECT COUNT(*) FROM laporan_pkl lp JOIN pkl_anggota pa ON pa.siswa_id=lp.siswa_id JOIN pkl_pengajuan pp ON pp.id=pa.pengajuan_id WHERE pp.pembimbing_id=? AND lp.status_pembimbing='pending'");
$stmtL->execute([$pid]); $pendingLaporan = $stmtL->fetchColumn();

// Kehadiran rata-rata
$stmtA = $pdo->prepare("SELECT COUNT(*) FROM absensi ab JOIN pkl_anggota pa ON pa.siswa_id=ab.siswa_id JOIN pkl_pengajuan pp ON pp.id=pa.pengajuan_id WHERE pp.pembimbing_id=?");
$stmtA->execute([$pid]); $totalAbsensi = $stmtA->fetchColumn();
$stmtH = $pdo->prepare("SELECT COUNT(*) FROM absensi ab JOIN pkl_anggota pa ON pa.siswa_id=ab.siswa_id JOIN pkl_pengajuan pp ON pp.id=pa.pengajuan_id WHERE pp.pembimbing_id=? AND ab.status='Hadir'");
$stmtH->execute([$pid]); $totalHadir = $stmtH->fetchColumn();
$pctHadir = $totalAbsensi > 0 ? round($totalHadir/$totalAbsensi*100) : 0;

// Recent Jurnal
$stmtRJ = $pdo->prepare("
    SELECT jh.*, u.nama_depan, u.nama_belakang
    FROM jurnal_harian jh
    JOIN users u ON u.id = jh.siswa_id
    JOIN pkl_anggota pa ON pa.siswa_id=jh.siswa_id
    JOIN pkl_pengajuan pp ON pp.id=pa.pengajuan_id
    WHERE pp.pembimbing_id=?
    ORDER BY jh.tanggal DESC LIMIT 5
");
$stmtRJ->execute([$pid]); $recentJurnal = $stmtRJ->fetchAll();

// Absensi summary
$stmtAS = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM absensi ab JOIN pkl_anggota pa ON pa.siswa_id=ab.siswa_id JOIN pkl_pengajuan pp ON pp.id=pa.pengajuan_id WHERE pp.pembimbing_id=? GROUP BY status");
$stmtAS->execute([$pid]); $absensiRaw = $stmtAS->fetchAll();
$absensiMap = []; foreach($absensiRaw as $r) $absensiMap[$r['status']] = $r['cnt'];
$totalAbs = array_sum($absensiMap) ?: 1;

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Dashboard</span></div>

<div class="welcome-section">
  <div>
    <h1>Selamat Datang, <?= htmlspecialchars(explode(' ',$_SESSION['nama'])[0]) ?> 👋</h1>
    <p style="margin-top:6px">Pembimbing PKL · Tahun Ajaran 2024/2025</p>
    <div class="status-badge" style="margin-top:14px;display:inline-flex">
      <div class="status-dot"></div>
      Aktif Membimbing · <?= $totalSiswa ?> Siswa
    </div>
  </div>
  <div style="text-align:right">
    <div style="font-size:.75rem;color:#64748b">Hari ini</div>
    <div style="font-size:1.5rem;font-weight:700;color:#f1f5f9" id="clock-display">--:--</div>
    <div style="font-size:.78rem;color:#94a3b8" id="date-display">--</div>
  </div>
</div>

<div class="section-title">Statistik Bimbingan</div>
<div class="stat-grid">
  <a href="siswa.php" class="stat-card delay-1" style="display:block">
    <div class="card-icon" style="background:rgba(99,102,241,.15)"><i class="fas fa-user-graduate" style="color:#818cf8"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Siswa Bimbingan</div>
    <div class="number"><?= $totalSiswa ?></div>
    <div class="trend"><i class="fas fa-circle" style="color:#4ade80;font-size:.5rem"></i> Semua aktif PKL</div>
  </a>
  <a href="jurnal.php" class="stat-card delay-2" style="display:block">
    <div class="card-icon" style="background:rgba(245,158,11,.15)"><i class="fas fa-book-open" style="color:#f59e0b"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Jurnal Pending</div>
    <div class="number"><?= $pendingJurnal ?></div>
    <div class="trend"><i class="fas fa-clock" style="color:#f59e0b;font-size:.5rem"></i> Menunggu validasi</div>
  </a>
  <a href="laporan.php" class="stat-card delay-3" style="display:block">
    <div class="card-icon" style="background:rgba(239,68,68,.15)"><i class="fas fa-file-alt" style="color:#ef4444"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Laporan Pending</div>
    <div class="number"><?= $pendingLaporan ?></div>
    <div class="trend"><i class="fas fa-exclamation-circle" style="color:#ef4444;font-size:.5rem"></i> Perlu direview</div>
  </a>
  <a href="absensi.php" class="stat-card delay-4" style="display:block">
    <div class="card-icon" style="background:rgba(74,222,128,.15)"><i class="fas fa-calendar-check" style="color:#4ade80"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Kehadiran Rata-rata</div>
    <div class="number"><?= $pctHadir ?>%</div>
    <div class="trend"><i class="fas fa-chart-line" style="color:#4ade80;font-size:.5rem"></i> Semua periode</div>
  </a>
</div>

<div class="two-col-panels">
  <!-- RECENT JURNAL -->
  <div class="panel">
    <div class="panel-header">
      <h3><i class="fas fa-book-open" style="color:#f59e0b"></i> Jurnal Terbaru</h3>
      <a href="jurnal.php" class="btn btn-warning btn-sm">Lihat Semua</a>
    </div>
    <?php if (empty($recentJurnal)): ?>
    <div class="empty-state"><i class="fas fa-book"></i><p>Belum ada jurnal</p></div>
    <?php else: foreach($recentJurnal as $j):
      $sc = ['valid'=>['pill-green','check','rgba(74,222,128,.12)','#4ade80'],'pending'=>['pill-yellow','clock','rgba(245,158,11,.12)','#f59e0b'],'tolak'=>['pill-red','times','rgba(239,68,68,.12)','#ef4444']][$j['status_validasi']] ?? ['pill-gray','minus','rgba(100,116,139,.12)','#94a3b8'];
    ?>
    <div class="activity-item">
      <div class="activity-icon" style="background:<?=$sc[2]?>;color:<?=$sc[3]?>"><i class="fas fa-<?=$sc[1]?>" style="font-size:.7rem"></i></div>
      <div style="flex:1">
        <div style="font-size:.83rem;font-weight:600;color:#f1f5f9"><?= htmlspecialchars($j['nama_depan'].' '.$j['nama_belakang']) ?></div>
        <div style="font-size:.75rem;color:#64748b"><?= mb_strimwidth(htmlspecialchars($j['kegiatan']),0,40,'…') ?> · <?= $j['tanggal'] ?></div>
      </div>
      <span class="pill <?=$sc[0]?>"><?= strtoupper($j['status_validasi']) ?></span>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- ABSENSI SUMMARY -->
  <div class="panel">
    <div class="panel-header">
      <h3><i class="fas fa-chart-pie" style="color:#818cf8"></i> Ringkasan Absensi</h3>
      <a href="absensi.php" class="btn btn-sm btn-indigo">Detail</a>
    </div>
    <?php
    $abs = [
      ['Hadir','#4ade80','fa-check-circle'],
      ['Izin','#f59e0b','fa-envelope'],
      ['Sakit','#818cf8','fa-procedures'],
      ['Alpa','#ef4444','fa-times-circle'],
    ];
    foreach($abs as [$lbl,$color,$icon]):
      $cnt = $absensiMap[$lbl] ?? 0;
      $pct = round($cnt/$totalAbs*100);
    ?>
    <div style="margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;margin-bottom:6px">
        <span style="font-size:.8rem;color:<?=$color?>"><i class="fas <?=$icon?>"></i> <?=$lbl?></span>
        <span style="font-size:.8rem;font-weight:600;color:#f1f5f9"><?=$cnt?> hari</span>
      </div>
      <div class="progress-bar"><div class="progress-fill" style="width:<?=$pct?>%;background:<?=$color?>"></div></div>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.06);display:flex;justify-content:space-between">
      <span style="font-size:.78rem;color:#64748b">Total Hari Kerja</span>
      <span style="font-size:.85rem;font-weight:600;color:#f1f5f9"><?= array_sum($absensiMap) ?> hari</span>
    </div>
  </div>
</div>

<div class="section-title" style="margin-top:28px">Akses Cepat</div>
<div class="quick-grid">
  <?php $qlinks = [
    ['jurnal.php','fa-book-open','rgba(245,158,11,.12)','#f59e0b','Validasi Jurnal'],
    ['laporan.php','fa-file-alt','rgba(239,68,68,.12)','#ef4444','Review Laporan'],
    ['nilai.php','fa-star','rgba(74,222,128,.12)','#4ade80','Input Nilai'],
    ['bimbingan.php','fa-comments','rgba(99,102,241,.12)','#818cf8','Bimbingan'],
    ['absensi.php','fa-calendar-check','rgba(20,184,166,.12)','#2dd4bf','Cek Absensi'],
    ['kompetensi.php','fa-tasks','rgba(168,85,247,.12)','#c084fc','Kompetensi'],
  ]; foreach($qlinks as $i=>[$href,$ico,$bg,$col,$lbl]): ?>
  <a href="<?=$href?>" class="quick-card delay-<?=$i+1?>">
    <div style="width:36px;height:36px;border-radius:8px;background:<?=$bg?>;display:flex;align-items:center;justify-content:center"><i class="fas <?=$ico?>" style="color:<?=$col?>"></i></div>
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<script>
function updateClock(){
  const now=new Date();
  document.getElementById('clock-display').textContent=now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
  document.getElementById('date-display').textContent=now.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long'});
}
updateClock(); setInterval(updateClock,1000);
</script>

<?php include 'partials/footer.php'; ?>
