<?php
// ============================================================
//  pembimbingPerusahaan/dashboard.php — Dashboard Wakasek
// ============================================================
require_once '../config.php';
requireRole('wakasek');

$activePage = 'dashboard';
$pageTitle  = 'Dashboard Wakasek';
$wid        = getWakasekId();
$pdo        = getDB();

// Gunakan $_SESSION['user']['nama'] (bukan $_SESSION['nama'])
$namaUser = $_SESSION['user']['nama'] ?? 'Wakasek';

// ── Log: buka dashboard ───────────────────────────────────────────────────
logAktivitas($pdo, $wid, 'Membuka dashboard wakasek');

// ── Stats: total siswa PKL aktif ──────────────────────────────────────────
$totalSiswa = (int) $pdo->query("
    SELECT COUNT(DISTINCT pa.siswa_id)
    FROM pkl_anggota pa
    JOIN pkl_pengajuan pp ON pp.id = pa.pengajuan_id
    WHERE pa.status_keanggotaan = 'aktif'
")->fetchColumn();

// ── Pengajuan pending untuk wakasek (status_wakasek) ─────────────────────
$pendingPengajuan = (int) $pdo->query("
    SELECT COUNT(*) FROM pkl_pengajuan WHERE status_wakasek = 'pending'
")->fetchColumn();

// ── Laporan pending pembimbing ────────────────────────────────────────────
$pendingLaporan = (int) $pdo->query("
    SELECT COUNT(*) FROM laporan_pkl WHERE status_pembimbing = 'pending'
")->fetchColumn();

// ── Persentase kehadiran ──────────────────────────────────────────────────
$totalAbsensi = (int) $pdo->query("SELECT COUNT(*) FROM absensi")->fetchColumn();
$totalHadir   = (int) $pdo->query("SELECT COUNT(*) FROM absensi WHERE status='Hadir'")->fetchColumn();
$pctHadir     = $totalAbsensi > 0 ? round($totalHadir / $totalAbsensi * 100) : 0;

// ── Pengajuan terbaru ─────────────────────────────────────────────────────
$recentPengajuan = $pdo->query("
    SELECT pp.*, u.nama_depan, u.nama_belakang,
           COUNT(pa.id) AS jumlah_siswa
    FROM pkl_pengajuan pp
    JOIN users u ON u.id = pp.ketua_id
    LEFT JOIN pkl_anggota pa ON pa.pengajuan_id = pp.id
    GROUP BY pp.id
    ORDER BY pp.tanggal_pengajuan DESC
    LIMIT 5
")->fetchAll();

// ── Rekap absensi ─────────────────────────────────────────────────────────
$absensiMap = [];
foreach ($pdo->query("SELECT status, COUNT(*) as cnt FROM absensi GROUP BY status")->fetchAll() as $r) {
    $absensiMap[$r['status']] = $r['cnt'];
}
$totalAbs = array_sum($absensiMap) ?: 1;

// ── Nilai ─────────────────────────────────────────────────────────────────
$sudahDinilai = (int) $pdo->query("SELECT COUNT(DISTINCT siswa_id) FROM nilai_pkl")->fetchColumn();

// ── Total DU/DI ───────────────────────────────────────────────────────────
$totalDU = (int) $pdo->query("
    SELECT COUNT(DISTINCT nama_perusahaan)
    FROM pkl_pengajuan
    WHERE status_pembimbing = 'disetujui'
")->fetchColumn();

// ── Rekap per jurusan ─────────────────────────────────────────────────────
$jurusanRekap = $pdo->query("
    SELECT ps.jurusan, COUNT(DISTINCT pa.siswa_id) AS total
    FROM pkl_anggota pa
    JOIN pkl_pengajuan pp ON pp.id = pa.pengajuan_id
    LEFT JOIN profil_siswa ps ON ps.user_id = pa.siswa_id
    WHERE pa.status_keanggotaan = 'aktif'
    GROUP BY ps.jurusan
    ORDER BY total DESC
")->fetchAll();

include 'partials/header.php';
?>

<div class="breadcrumb">
  <i class="fas fa-home"></i>
  <i class="fas fa-chevron-right" style="font-size:.6rem"></i>
  <span>Dashboard</span>
</div>

<div class="welcome-section">
  <div>
    <h1>Selamat Datang, <?= htmlspecialchars(explode(' ', $namaUser)[0]) ?> 👋</h1>
    <p style="margin-top:6px">Wakil Kepala Sekolah · Bidang Hubungan Industri &amp; PKL</p>
    <div class="status-badge" style="margin-top:14px;display:inline-flex">
      <div class="status-dot"></div>
      Monitoring PKL · <?= $totalSiswa ?> Siswa Aktif
    </div>
  </div>
  <div style="text-align:right">
    <div style="font-size:.75rem;color:#64748b">Hari ini</div>
    <div style="font-size:1.5rem;font-weight:700;color:#f1f5f9" id="clock-display">--:--</div>
    <div style="font-size:.78rem;color:#94a3b8" id="date-display">--</div>
  </div>
</div>

<div class="section-title">Statistik PKL Sekolah</div>
<div class="stat-grid">
  <a href="siswa.php" class="stat-card delay-1" style="display:block">
    <div class="card-icon" style="background:rgba(99,102,241,.15)"><i class="fas fa-user-graduate" style="color:#818cf8"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Siswa PKL Aktif</div>
    <div class="number"><?= $totalSiswa ?></div>
    <div class="trend"><i class="fas fa-circle" style="color:#4ade80;font-size:.5rem"></i> Sedang melaksanakan PKL</div>
  </a>
  <a href="pengajuan.php" class="stat-card delay-2" style="display:block">
    <div class="card-icon" style="background:rgba(245,158,11,.15)"><i class="fas fa-file-signature" style="color:#f59e0b"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Pengajuan Pending</div>
    <div class="number"><?= $pendingPengajuan ?></div>
    <div class="trend"><i class="fas fa-clock" style="color:#f59e0b;font-size:.5rem"></i> Menunggu persetujuan</div>
  </a>
  <a href="laporan.php" class="stat-card delay-3" style="display:block">
    <div class="card-icon" style="background:rgba(239,68,68,.15)"><i class="fas fa-file-alt" style="color:#ef4444"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Laporan Pending</div>
    <div class="number"><?= $pendingLaporan ?></div>
    <div class="trend"><i class="fas fa-exclamation-circle" style="color:#ef4444;font-size:.5rem"></i> Perlu ditindaklanjuti</div>
  </a>
  <a href="absensi.php" class="stat-card delay-4" style="display:block">
    <div class="card-icon" style="background:rgba(74,222,128,.15)"><i class="fas fa-calendar-check" style="color:#4ade80"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Kehadiran Rata-rata</div>
    <div class="number"><?= $pctHadir ?>%</div>
    <div class="trend"><i class="fas fa-chart-line" style="color:#4ade80;font-size:.5rem"></i> Seluruh siswa PKL</div>
  </a>
  <a href="nilai.php" class="stat-card delay-5" style="display:block">
    <div class="card-icon" style="background:rgba(20,184,166,.15)"><i class="fas fa-star" style="color:#2dd4bf"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Sudah Dinilai</div>
    <div class="number"><?= $sudahDinilai ?></div>
    <div class="trend"><i class="fas fa-check" style="color:#2dd4bf;font-size:.5rem"></i> Dari <?= $totalSiswa ?> siswa</div>
  </a>
  <a href="penempatan.php" class="stat-card delay-6" style="display:block">
    <div class="card-icon" style="background:rgba(168,85,247,.15)"><i class="fas fa-building" style="color:#c084fc"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">DU/DI Mitra</div>
    <div class="number"><?= $totalDU ?></div>
    <div class="trend"><i class="fas fa-handshake" style="color:#c084fc;font-size:.5rem"></i> Perusahaan aktif</div>
  </a>
</div>

<div class="two-col-panels">
  <!-- PENGAJUAN TERBARU -->
  <div class="panel">
    <div class="panel-header">
      <h3><i class="fas fa-file-signature" style="color:#f59e0b"></i> Pengajuan PKL Terbaru</h3>
      <a href="pengajuan.php" class="btn btn-warning btn-sm">Lihat Semua</a>
    </div>
    <?php if (empty($recentPengajuan)): ?>
    <div class="empty-state"><i class="fas fa-file"></i><p>Belum ada pengajuan</p></div>
    <?php else:
      foreach ($recentPengajuan as $p):
        $sc = [
          'disetujui' => ['pill-green','check','rgba(74,222,128,.12)','#4ade80'],
          'pending'   => ['pill-yellow','clock','rgba(245,158,11,.12)','#f59e0b'],
          'ditolak'   => ['pill-red','times','rgba(239,68,68,.12)','#ef4444'],
        ][$p['status_pembimbing']] ?? ['pill-gray','minus','rgba(100,116,139,.12)','#94a3b8'];
    ?>
    <div class="activity-item">
      <div class="activity-icon" style="background:<?=$sc[2]?>;color:<?=$sc[3]?>">
        <i class="fas fa-<?=$sc[1]?>" style="font-size:.7rem"></i>
      </div>
      <div style="flex:1">
        <div style="font-size:.83rem;font-weight:600;color:#f1f5f9"><?= htmlspecialchars($p['nama_perusahaan']) ?></div>
        <div style="font-size:.75rem;color:#64748b">
          <?= htmlspecialchars($p['nama_depan'].' '.$p['nama_belakang']) ?> ·
          <?= $p['jumlah_siswa'] ?> siswa ·
          <?= date('d M Y', strtotime($p['tanggal_pengajuan'])) ?>
        </div>
      </div>
      <span class="pill <?=$sc[0]?>"><?= strtoupper($p['status_pembimbing']) ?></span>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- ABSENSI SUMMARY -->
  <div class="panel">
    <div class="panel-header">
      <h3><i class="fas fa-chart-pie" style="color:#818cf8"></i> Ringkasan Absensi Seluruh Siswa</h3>
      <a href="absensi.php" class="btn btn-sm btn-indigo">Detail</a>
    </div>
    <?php
    $abs = [
      ['Hadir','#4ade80','fa-check-circle'],
      ['Izin','#f59e0b','fa-envelope'],
      ['Sakit','#818cf8','fa-procedures'],
      ['Alpa','#ef4444','fa-times-circle'],
    ];
    foreach ($abs as [$lbl,$color,$icon]):
      $cnt = $absensiMap[$lbl] ?? 0;
      $pct = round($cnt / $totalAbs * 100);
    ?>
    <div style="margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;margin-bottom:6px">
        <span style="font-size:.8rem;color:<?=$color?>"><i class="fas <?=$icon?>"></i> <?=$lbl?></span>
        <span style="font-size:.8rem;font-weight:600;color:#f1f5f9"><?=$cnt?> hari</span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" style="width:<?=$pct?>%;background:<?=$color?>"></div>
      </div>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.06);display:flex;justify-content:space-between">
      <span style="font-size:.78rem;color:#64748b">Total Hari Kerja</span>
      <span style="font-size:.85rem;font-weight:600;color:#f1f5f9"><?= array_sum($absensiMap) ?> hari</span>
    </div>
  </div>
</div>

<!-- REKAP PER JURUSAN -->
<?php if (!empty($jurusanRekap)): ?>
<div class="section-title" style="margin-top:28px">Rekap PKL per Jurusan</div>
<div class="panel">
  <div class="table-responsive">
    <table>
      <thead><tr>
        <th><i class="fas fa-book"></i> Jurusan</th>
        <th><i class="fas fa-users"></i> Jumlah Siswa PKL</th>
        <th><i class="fas fa-chart-bar"></i> Proporsi</th>
      </tr></thead>
      <tbody>
      <?php
      $ci = 0;
      $colors = ['#818cf8','#4ade80','#f59e0b','#2dd4bf','#ef4444','#c084fc'];
      foreach ($jurusanRekap as $jr):
        $jPct = round($jr['total'] / max($totalSiswa, 1) * 100);
        $c    = $colors[$ci++ % count($colors)];
      ?>
      <tr>
        <td style="font-weight:600;color:#f1f5f9"><?= htmlspecialchars($jr['jurusan'] ?? '-') ?></td>
        <td><?= $jr['total'] ?> siswa</td>
        <td style="width:200px">
          <div style="display:flex;align-items:center;gap:8px">
            <div class="progress-bar" style="flex:1">
              <div class="progress-fill" style="width:<?=$jPct?>%;background:<?=$c?>"></div>
            </div>
            <span style="font-size:.75rem;color:#94a3b8;width:36px;text-align:right"><?=$jPct?>%</span>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="section-title" style="margin-top:28px">Akses Cepat</div>
<div class="quick-grid">
<?php
$qlinks = [
  ['pengajuan.php','fa-file-signature','rgba(245,158,11,.12)','#f59e0b','Pengajuan PKL'],
  ['siswa.php','fa-user-graduate','rgba(99,102,241,.12)','#818cf8','Data Siswa PKL'],
  ['laporan.php','fa-file-alt','rgba(239,68,68,.12)','#ef4444','Monitor Laporan'],
  ['nilai.php','fa-star','rgba(74,222,128,.12)','#4ade80','Rekap Nilai'],
  ['absensi.php','fa-calendar-check','rgba(20,184,166,.12)','#2dd4bf','Absensi Siswa'],
  ['penempatan.php','fa-map-marker-alt','rgba(168,85,247,.12)','#c084fc','Penempatan DU/DI'],
];
foreach ($qlinks as $i => [$href,$ico,$bg,$col,$lbl]):
?>
<a href="<?=$href?>" class="quick-card delay-<?=$i+1?>">
  <div style="width:36px;height:36px;border-radius:8px;background:<?=$bg?>;display:flex;align-items:center;justify-content:center">
    <i class="fas <?=$ico?>" style="color:<?=$col?>"></i>
  </div>
  <?=$lbl?>
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