<?php
require_once '../config.php';
requireRole('wakasek');
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo '<p style="color:#ef4444">ID tidak valid.</p>'; exit; }

$stmt = $pdo->prepare("
    SELECT u.*, ps.*,
           pp.nama_perusahaan, pp.alamat_perusahaan, pp.tanggal_pengajuan, pp.status_pembimbing,
           pu.nama_depan AS pname, pu.nama_belakang AS plname,
           n.nilai_akhir, n.predikat
    FROM users u
    LEFT JOIN profil_siswa ps ON ps.user_id = u.id
    LEFT JOIN pkl_anggota pa ON pa.siswa_id = u.id
    LEFT JOIN pkl_pengajuan pp ON pp.id = pa.pengajuan_id
    LEFT JOIN users pu ON pu.id = pp.pembimbing_id
    LEFT JOIN nilai_pkl n ON n.siswa_id = u.id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) { echo '<p style="color:#ef4444">Data tidak ditemukan.</p>'; exit; }

$inisial = strtoupper(substr($s['nama_depan'],0,1).substr($s['nama_belakang']??'',0,1));
$color   = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#8b5cf6','#ef4444','#2dd4bf','#f97316','#a855f7'][$id % 10];

// Absensi rekap
$stmtA = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM absensi WHERE siswa_id=? GROUP BY status");
$stmtA->execute([$id]);
$absensi = [];
foreach ($stmtA->fetchAll() as $r) $absensi[$r['status']] = $r['cnt'];
?>

<div class="profile-card" style="margin-bottom:16px">
  <div class="avatar-lg" style="background:<?=$color?>22;color:<?=$color?>;font-size:1.2rem"><?=$inisial?></div>
  <div>
    <div style="font-size:1rem;font-weight:700;color:#f1f5f9"><?= htmlspecialchars($s['nama_depan'].' '.$s['nama_belakang']) ?></div>
    <div style="font-size:.78rem;color:#64748b;margin-top:2px"><?= htmlspecialchars($s['nis']??'-') ?> · <?= htmlspecialchars($s['kelas']??'-') ?></div>
    <div style="margin-top:8px">
      <span class="pill <?= $s['status_pembimbing']==='disetujui'?'pill-green':'pill-yellow' ?>"><?= strtoupper($s['status_pembimbing']??'pending') ?></span>
    </div>
  </div>
</div>

<div class="detail-row"><span class="detail-label">Jurusan</span><span class="detail-value"><?= htmlspecialchars($s['jurusan']??'-') ?></span></div>
<div class="detail-row"><span class="detail-label">No HP</span><span class="detail-value"><?= htmlspecialchars($s['no_hp']??'-') ?></span></div>
<div class="detail-row"><span class="detail-label">Email</span><span class="detail-value"><?= htmlspecialchars($s['email']??'-') ?></span></div>
<div class="detail-row"><span class="detail-label">Tempat PKL</span><span class="detail-value"><?= htmlspecialchars($s['nama_perusahaan']??'-') ?></span></div>
<div class="detail-row"><span class="detail-label">Pembimbing</span><span class="detail-value"><?= htmlspecialchars(trim(($s['pname']??'').' '.($s['plname']??'')) ?: '-') ?></span></div>
<?php if ($s['nilai_akhir']): ?>
<div class="detail-row">
  <span class="detail-label">Nilai Akhir</span>
  <span class="detail-value" style="font-weight:700;font-size:1.1rem;color:<?= ['A'=>'#4ade80','B'=>'#818cf8','C'=>'#f59e0b','D'=>'#ef4444'][$s['predikat']]??'#f1f5f9' ?>">
    <?= $s['nilai_akhir'] ?> (<?= $s['predikat'] ?>)
  </span>
</div>
<?php endif; ?>

<div style="margin-top:14px;padding-top:14px;border-top:1px solid rgba(255,255,255,.06)">
  <div style="font-size:.72rem;color:#64748b;margin-bottom:8px;text-transform:uppercase;letter-spacing:1px">Rekap Absensi</div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php foreach(['Hadir'=>'#4ade80','Izin'=>'#f59e0b','Sakit'=>'#818cf8','Alpa'=>'#ef4444'] as $s2=>$c2): ?>
    <div style="background:<?=$c2?>1a;border:1px solid <?=$c2?>44;border-radius:8px;padding:6px 12px;text-align:center;min-width:60px">
      <div style="font-size:1rem;font-weight:700;color:<?=$c2?>"><?= $absensi[$s2]??0 ?></div>
      <div style="font-size:.65rem;color:#64748b"><?=$s2?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
  <a href="../absensi.php?siswa_id=<?=$id?>" class="btn btn-indigo btn-sm"><i class="fas fa-calendar-check"></i> Absensi</a>
  <a href="../nilai.php?siswa_id=<?=$id?>" class="btn btn-warning btn-sm"><i class="fas fa-star"></i> Nilai</a>
</div>
