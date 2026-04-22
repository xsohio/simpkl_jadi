<?php
require_once 'config.php';
requireRole('wakasek');
$activePage = 'absensi';
$pageTitle  = 'Absensi PKL';
$wid = getWakasekId();
$pdo = getDB();

$bulan = (int)($_GET['bulan'] ?? date('m'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$filterKelas = trim($_GET['kelas'] ?? '');

// Rekap absensi per siswa untuk bulan terpilih
$whereKelas = $filterKelas ? "AND ps.kelas = ?" : "";
$params = [$bulan, $tahun];
if ($filterKelas) $params[] = $filterKelas;

$stmt = $pdo->prepare("
    SELECT u.id, u.nama_depan, u.nama_belakang,
           ps.kelas, ps.nis,
           pp.nama_perusahaan,
           SUM(CASE WHEN ab.status='Hadir' THEN 1 ELSE 0 END) as hadir,
           SUM(CASE WHEN ab.status='Izin'  THEN 1 ELSE 0 END) as izin,
           SUM(CASE WHEN ab.status='Sakit' THEN 1 ELSE 0 END) as sakit,
           SUM(CASE WHEN ab.status='Alpa'  THEN 1 ELSE 0 END) as alpa,
           COUNT(ab.id) as total
    FROM users u
    JOIN pkl_anggota pa ON pa.siswa_id = u.id
    JOIN pkl_pengajuan pp ON pp.id = pa.pengajuan_id
    LEFT JOIN profil_siswa ps ON ps.user_id = u.id
    LEFT JOIN absensi ab ON ab.siswa_id = u.id
        AND MONTH(ab.tanggal) = ?
        AND YEAR(ab.tanggal) = ?
    WHERE pa.status_keanggotaan = 'aktif'
    $whereKelas
    GROUP BY u.id
    ORDER BY u.nama_depan
");
$stmt->execute($params);
$siswaAbsensi = $stmt->fetchAll();

// Rekap global bulan ini
$stmtGlobal = $pdo->prepare("
    SELECT
        SUM(CASE WHEN ab.status='Hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN ab.status='Izin'  THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN ab.status='Sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN ab.status='Alpa'  THEN 1 ELSE 0 END) as alpa
    FROM absensi ab
    WHERE MONTH(ab.tanggal)=? AND YEAR(ab.tanggal)=?
");
$stmtGlobal->execute([$bulan, $tahun]);
$globalRekap = $stmtGlobal->fetch();
$totalGlobal = array_sum($globalRekap) ?: 1;
$pctHadirGlobal = round(($globalRekap['hadir'] ?? 0) / $totalGlobal * 100);

$bulanNama = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$stmtKelas = $pdo->query("SELECT DISTINCT kelas FROM profil_siswa WHERE kelas IS NOT NULL ORDER BY kelas");

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Absensi PKL</span></div>

<!-- HEADER PANEL -->
<div class="panel-full">
  <div class="panel-header" style="flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(74,222,128,.1);padding:10px;border-radius:10px"><i class="fas fa-calendar-check" style="color:#4ade80;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Rekap Absensi PKL</h3>
        <p style="margin:0;color:#64748b;font-size:.8rem"><?= $bulanNama[$bulan] ?> <?= $tahun ?> — seluruh siswa aktif PKL</p>
      </div>
    </div>
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <select name="kelas" class="form-control" style="width:auto" onchange="this.form.submit()">
        <option value="">Semua Kelas</option>
        <?php foreach($stmtKelas->fetchAll() as $k): ?>
        <option value="<?= htmlspecialchars($k['kelas']) ?>" <?= $filterKelas===$k['kelas']?'selected':'' ?>><?= htmlspecialchars($k['kelas']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="bulan" class="form-control" style="width:auto" onchange="this.form.submit()">
        <?php for($m=1;$m<=12;$m++): ?>
        <option value="<?=$m?>" <?=$m===$bulan?'selected':''?>><?=$bulanNama[$m]?></option>
        <?php endfor; ?>
      </select>
      <select name="tahun" class="form-control" style="width:auto" onchange="this.form.submit()">
        <?php for($y=2024;$y<=2026;$y++): ?>
        <option value="<?=$y?>" <?=$y===$tahun?'selected':''?>><?=$y?></option>
        <?php endfor; ?>
      </select>
      <?php if($filterKelas): ?>
      <a href="absensi.php?bulan=<?=$bulan?>&tahun=<?=$tahun?>" class="btn btn-sm" style="background:rgba(255,255,255,.05);color:#94a3b8;border:1px solid rgba(255,255,255,.08)"><i class="fas fa-times"></i></a>
      <?php endif; ?>
    </form>
  </div>

  <!-- GLOBAL STATS — minimalis -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin:20px 0 24px">
    <?php
    $gStats = [
      ['Hadir',  $globalRekap['hadir']??0, '#4ade80', 'fa-check'],
      ['Izin',   $globalRekap['izin'] ??0, '#f59e0b', 'fa-envelope'],
      ['Sakit',  $globalRekap['sakit']??0, '#818cf8', 'fa-procedures'],
      ['Alpa',   $globalRekap['alpa'] ??0, '#ef4444', 'fa-times'],
    ];
    foreach($gStats as [$lbl,$cnt,$col,$ic]):
    ?>
    <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px">
      <div style="width:32px;height:32px;border-radius:8px;background:<?=$col?>1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="fas <?=$ic?>" style="color:<?=$col?>;font-size:.8rem"></i>
      </div>
      <div>
        <div style="font-size:1.2rem;font-weight:700;color:<?=$col?>;line-height:1"><?=$cnt?></div>
        <div style="font-size:.7rem;color:#64748b"><?=$lbl?></div>
      </div>
    </div>
    <?php endforeach; ?>
    <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px">
      <div style="width:32px;height:32px;border-radius:8px;background:rgba(99,102,241,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="fas fa-chart-bar" style="color:#818cf8;font-size:.8rem"></i>
      </div>
      <div>
        <div style="font-size:1.2rem;font-weight:700;color:#f1f5f9;line-height:1"><?=$pctHadirGlobal?>%</div>
        <div style="font-size:.7rem;color:#64748b">Kehadiran</div>
      </div>
    </div>
  </div>

  <!-- TABEL REKAP PER SISWA -->
  <?php if(empty($siswaAbsensi)): ?>
  <div class="empty-state"><i class="fas fa-calendar"></i><p>Tidak ada data absensi bulan ini</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table>
      <thead><tr>
        <th><i class="fas fa-user"></i> Siswa</th>
        <th><i class="fas fa-chalkboard"></i> Kelas</th>
        <th><i class="fas fa-building"></i> Tempat PKL</th>
        <th style="color:#4ade80"><i class="fas fa-check"></i> Hadir</th>
        <th style="color:#f59e0b"><i class="fas fa-envelope"></i> Izin</th>
        <th style="color:#818cf8"><i class="fas fa-procedures"></i> Sakit</th>
        <th style="color:#ef4444"><i class="fas fa-times"></i> Alpa</th>
        <th><i class="fas fa-chart-bar"></i> Kehadiran</th>
      </tr></thead>
      <tbody>
      <?php foreach($siswaAbsensi as $s):
        $total   = $s['total'] ?: 0;
        $pct     = $total > 0 ? round($s['hadir'] / $total * 100) : 0;
        $barColor = $pct >= 80 ? '#4ade80' : ($pct >= 60 ? '#f59e0b' : '#ef4444');
        $nama = htmlspecialchars($s['nama_depan'].' '.$s['nama_belakang']);
      ?>
      <tr>
        <td>
          <div style="font-weight:600;color:#f1f5f9"><?=$nama?></div>
          <div style="font-size:.72rem;color:#64748b"><?= htmlspecialchars($s['nis']??'-') ?></div>
        </td>
        <td style="color:#94a3b8"><?= htmlspecialchars($s['kelas']??'-') ?></td>
        <td style="color:#94a3b8;font-size:.83rem"><?= htmlspecialchars($s['nama_perusahaan']??'-') ?></td>
        <td style="color:#4ade80;font-weight:600"><?= $s['hadir'] ?></td>
        <td style="color:#f59e0b;font-weight:600"><?= $s['izin'] ?></td>
        <td style="color:#818cf8;font-weight:600"><?= $s['sakit'] ?></td>
        <td style="color:#ef4444;font-weight:600"><?= $s['alpa'] ?></td>
        <td style="width:140px">
          <div style="display:flex;align-items:center;gap:8px">
            <div class="progress-bar" style="flex:1"><div class="progress-fill" style="width:<?=$pct?>%;background:<?=$barColor?>"></div></div>
            <span style="font-size:.75rem;font-weight:600;color:<?=$barColor?>;width:36px"><?=$pct?>%</span>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>
