<?php
require_once '../config.php';
requireRole('pembimbing');
$pid = getPembimbingId();
$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { echo '<p style="color:#ef4444">ID tidak valid</p>'; exit; }

// Pastikan siswa ini memang di bawah pembimbing ini
$stmt = $pdo->prepare("
    SELECT u.*, ps.nis, ps.kelas, ps.jurusan, ps.no_hp, ps.jenis_kelamin, ps.agama, ps.tempat_lahir, ps.tanggal_lahir, ps.alamat, ps.golongan_darah,
           pp.nama_perusahaan, pp.alamat_perusahaan
    FROM users u
    LEFT JOIN profil_siswa ps ON ps.user_id = u.id
    JOIN pkl_anggota pa ON pa.siswa_id = u.id
    JOIN pkl_pengajuan pp ON pp.id = pa.pengajuan_id
    WHERE u.id=? AND pp.pembimbing_id=?
    LIMIT 1
");
$stmt->execute([$id, $pid]);
$s = $stmt->fetch();
if (!$s) { echo '<p style="color:#ef4444">Data tidak ditemukan atau akses ditolak</p>'; exit; }

$inisial = initials($s['nama_depan'], $s['nama_belakang']);
$color   = avatarColor($s['id']);
$nama    = htmlspecialchars($s['nama_depan'].' '.$s['nama_belakang']);

// Nilai
$nStmt = $pdo->prepare("SELECT * FROM nilai_pkl WHERE siswa_id=? AND pembimbing_id=?");
$nStmt->execute([$id,$pid]);
$nilai = $nStmt->fetch();

// Absensi ringkasan
$aStmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM absensi WHERE siswa_id=? GROUP BY status");
$aStmt->execute([$id]);
$absensiMap = [];
foreach($aStmt->fetchAll() as $r) $absensiMap[$r['status']] = $r['cnt'];
?>
<div class="profile-card" style="margin-bottom:16px">
  <div style="width:54px;height:54px;border-radius:50%;background:<?=$color?>22;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;color:<?=$color?>"><?=$inisial?></div>
  <div>
    <div style="font-weight:700;color:#f1f5f9;font-size:1rem"><?=$nama?></div>
    <div style="font-size:.8rem;color:#818cf8"><?= htmlspecialchars($s['kelas']??'-') ?> · <?= htmlspecialchars($s['jurusan']??'-') ?></div>
    <div style="font-size:.78rem;color:#64748b"><?= htmlspecialchars($s['email']) ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
  <?php
  $details = [
    ['NIS', $s['nis']??'-'],
    ['No HP', $s['no_hp']??'-'],
    ['Jenis Kelamin', $s['jenis_kelamin']??'-'],
    ['Agama', $s['agama']??'-'],
    ['Tempat Lahir', $s['tempat_lahir']??'-'],
    ['Tanggal Lahir', $s['tanggal_lahir'] ? date('d M Y', strtotime($s['tanggal_lahir'])) : '-'],
    ['Gol. Darah', $s['golongan_darah']??'-'],
    ['Tempat PKL', $s['nama_perusahaan']??'-'],
  ];
  foreach($details as [$lbl,$val]):
  ?>
  <div class="detail-row" style="padding:6px 0">
    <span class="detail-label"><?=$lbl?></span>
    <span class="detail-value"><?= htmlspecialchars($val) ?></span>
  </div>
  <?php endforeach; ?>
</div>

<?php if($nilai): ?>
<div style="background:#162032;border-radius:10px;padding:14px;margin-bottom:12px">
  <div style="font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px">Nilai PKL</div>
  <div style="display:flex;align-items:center;justify-content:space-between">
    <div>
      <div style="font-size:.8rem;color:#94a3b8">Nilai Akhir</div>
      <div style="font-size:2rem;font-weight:700;color:#f1f5f9"><?= $nilai['nilai_akhir'] ?></div>
    </div>
    <div style="font-size:1.5rem;font-weight:700;padding:10px 18px;border-radius:10px;background:<?=['A'=>'rgba(74,222,128,.15)','B'=>'rgba(129,140,248,.15)','C'=>'rgba(245,158,11,.15)','D'=>'rgba(239,68,68,.15)'][$nilai['predikat']]??'rgba(100,116,139,.15)?>';color:<?=['A'=>'#4ade80','B'=>'#818cf8','C'=>'#f59e0b','D'=>'#ef4444'][$nilai['predikat']]??'#94a3b8'>">
      <?= $nilai['predikat'] ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div style="display:flex;gap:8px;flex-wrap:wrap">
  <a href="absensi.php?siswa_id=<?=$id?>" class="btn btn-indigo btn-sm"><i class="fas fa-calendar-check"></i> Lihat Absensi</a>
  <a href="nilai.php?siswa_id=<?=$id?>" class="btn btn-warning btn-sm"><i class="fas fa-star"></i> Input Nilai</a>
  <a href="bimbingan.php?siswa_id=<?=$id?>" class="btn btn-success btn-sm"><i class="fas fa-comments"></i> Bimbingan</a>
</div>
