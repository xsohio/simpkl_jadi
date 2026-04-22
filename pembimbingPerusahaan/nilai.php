<?php
require_once 'config.php';
requireRole('wakasek');
$activePage = 'nilai';
$pageTitle  = 'Rekap Nilai PKL';
$wid = getWakasekId();
$pdo = getDB();

// Filter
$filterKelas   = trim($_GET['kelas']   ?? '');
$filterJurusan = trim($_GET['jurusan'] ?? '');

// Ambil semua nilai PKL
$where  = ['1=1'];
$params = [];
if ($filterKelas)   { $where[] = "ps.kelas=?";   $params[] = $filterKelas; }
if ($filterJurusan) { $where[] = "ps.jurusan=?";  $params[] = $filterJurusan; }
$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT n.*, u.nama_depan, u.nama_belakang,
           ps.kelas, ps.jurusan, ps.nis,
           pu.nama_depan AS pname, pu.nama_belakang AS plname
    FROM nilai_pkl n
    JOIN users u ON u.id = n.siswa_id
    LEFT JOIN profil_siswa ps ON ps.user_id = n.siswa_id
    LEFT JOIN users pu ON pu.id = n.pembimbing_id
    WHERE $whereSQL
    ORDER BY n.nilai_akhir DESC
");
$stmt->execute($params);
$nilaiList = $stmt->fetchAll();

// Statistik
$avgNilai    = count($nilaiList) ? round(array_sum(array_column($nilaiList,'nilai_akhir')) / count($nilaiList), 1) : 0;
$predCount   = array_count_values(array_column($nilaiList, 'predikat'));

$stmtKelas   = $pdo->query("SELECT DISTINCT kelas FROM profil_siswa WHERE kelas IS NOT NULL ORDER BY kelas");
$stmtJurusan = $pdo->query("SELECT DISTINCT jurusan FROM profil_siswa WHERE jurusan IS NOT NULL ORDER BY jurusan");

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Rekap Nilai PKL</span></div>

<!-- STAT CARDS -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card delay-1">
    <div class="card-icon" style="background:rgba(74,222,128,.15)"><i class="fas fa-star" style="color:#4ade80"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Rata-rata Nilai</div>
    <div class="number"><?= $avgNilai ?></div>
    <div class="trend"><i class="fas fa-chart-line" style="color:#4ade80;font-size:.5rem"></i> Seluruh siswa</div>
  </div>
  <?php foreach(['A'=>'#4ade80','B'=>'#818cf8','C'=>'#f59e0b','D'=>'#ef4444'] as $p=>$c): ?>
  <div class="stat-card delay-2">
    <div class="card-icon" style="background:<?=$c?>22"><i class="fas fa-award" style="color:<?=$c?>"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Predikat <?=$p?></div>
    <div class="number" style="color:<?=$c?>"><?= $predCount[$p] ?? 0 ?></div>
    <div class="trend" style="color:<?=$c?>">Nilai <?= $p==='A'?'≥ 90':($p==='B'?'80–89':($p==='C'?'70–79':'< 70')) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(74,222,128,.1);padding:10px;border-radius:10px"><i class="fas fa-star" style="color:#4ade80;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Rekap Nilai PKL Siswa</h3>
        <p style="margin:0;color:#64748b;font-size:.8rem">Nilai sikap, keterampilan, dan laporan seluruh siswa</p>
      </div>
    </div>
  </div>

  <div class="filter-bar">
    <select class="form-control" style="width:auto" onchange="location.href='nilai.php?kelas='+this.value+'&jurusan=<?= urlencode($filterJurusan) ?>'">
      <option value="">Semua Kelas</option>
      <?php foreach($stmtKelas->fetchAll() as $k): ?>
      <option value="<?= htmlspecialchars($k['kelas']) ?>" <?= $filterKelas===$k['kelas']?'selected':'' ?>><?= htmlspecialchars($k['kelas']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-control" style="width:auto" onchange="location.href='nilai.php?kelas=<?= urlencode($filterKelas) ?>&jurusan='+this.value">
      <option value="">Semua Jurusan</option>
      <?php foreach($stmtJurusan->fetchAll() as $j): ?>
      <option value="<?= htmlspecialchars($j['jurusan']) ?>" <?= $filterJurusan===$j['jurusan']?'selected':'' ?>><?= htmlspecialchars($j['jurusan']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if(empty($nilaiList)): ?>
  <div class="empty-state"><i class="fas fa-star"></i><p>Belum ada data nilai</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table>
      <thead><tr>
        <th><i class="fas fa-hashtag"></i> No</th>
        <th><i class="fas fa-user"></i> Nama Siswa</th>
        <th><i class="fas fa-chalkboard"></i> Kelas</th>
        <th><i class="fas fa-smile"></i> Sikap</th>
        <th><i class="fas fa-wrench"></i> Keterampilan</th>
        <th><i class="fas fa-file"></i> Laporan</th>
        <th><i class="fas fa-star"></i> Nilai Akhir</th>
        <th><i class="fas fa-award"></i> Predikat</th>
        <th><i class="fas fa-user-tie"></i> Pembimbing</th>
      </tr></thead>
      <tbody>
      <?php foreach($nilaiList as $i=>$n):
        $predColor = ['A'=>'#4ade80','B'=>'#818cf8','C'=>'#f59e0b','D'=>'#ef4444'][$n['predikat']] ?? '#94a3b8';
        $nama = htmlspecialchars($n['nama_depan'].' '.$n['nama_belakang']);
      ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td>
          <div style="font-weight:600;color:#f1f5f9"><?=$nama?></div>
          <div style="font-size:.72rem;color:#64748b"><?= htmlspecialchars($n['nis']??'-') ?></div>
        </td>
        <td>
          <div style="color:#cbd5e1"><?= htmlspecialchars($n['kelas']??'-') ?></div>
          <div style="font-size:.72rem;color:#64748b"><?= htmlspecialchars($n['jurusan']??'-') ?></div>
        </td>
        <td>
          <div style="color:#cbd5e1"><?= $n['nilai_sikap'] ?></div>
          <div class="progress-bar" style="margin-top:4px"><div class="progress-fill" style="width:<?=$n['nilai_sikap']?>%;background:#818cf8"></div></div>
        </td>
        <td>
          <div style="color:#cbd5e1"><?= $n['nilai_keterampilan'] ?></div>
          <div class="progress-bar" style="margin-top:4px"><div class="progress-fill" style="width:<?=$n['nilai_keterampilan']?>%;background:#f59e0b"></div></div>
        </td>
        <td>
          <div style="color:#cbd5e1"><?= $n['nilai_laporan'] ?></div>
          <div class="progress-bar" style="margin-top:4px"><div class="progress-fill" style="width:<?=$n['nilai_laporan']?>%;background:#2dd4bf"></div></div>
        </td>
        <td style="font-size:1.1rem;font-weight:700;color:#f1f5f9"><?= $n['nilai_akhir'] ?></td>
        <td><span style="font-weight:700;color:<?=$predColor?>;font-size:1rem"><?= $n['predikat'] ?></span></td>
        <td style="font-size:.83rem;color:#94a3b8"><?= htmlspecialchars(trim(($n['pname']??'').' '.($n['plname']??'')) ?: '-') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>
