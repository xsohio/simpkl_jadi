<?php
require_once 'config.php';
requireRole('pembimbing');
$activePage = 'penempatan';
$pageTitle  = 'Penempatan PKL';
$pid = getPembimbingId();
$pdo = getDB();

// Ambil penempatan/pengajuan yang disetujui pembimbing ini
$stmt = $pdo->prepare("
    SELECT pp.*, u.nama_depan, u.nama_belakang,
           COUNT(pa.id) as jumlah_siswa,
           GROUP_CONCAT(CONCAT(u2.nama_depan,' ',u2.nama_belakang) SEPARATOR ', ') as nama_siswa
    FROM pkl_pengajuan pp
    JOIN users u ON u.id = pp.ketua_id
    JOIN pkl_anggota pa ON pa.pengajuan_id = pp.id
    JOIN users u2 ON u2.id = pa.siswa_id
    WHERE pp.pembimbing_id = ?
    GROUP BY pp.id
    ORDER BY pp.tanggal_pengajuan DESC
");
$stmt->execute([$pid]);
$penempatanList = $stmt->fetchAll();

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Penempatan PKL</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(20,184,166,.1);padding:10px;border-radius:10px"><i class="fas fa-map-marker-alt" style="color:#2dd4bf;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Penempatan PKL</h3>
        <p style="margin:0;color:#64748b;font-size:.8rem">Informasi lokasi &amp; tempat siswa melaksanakan PKL</p>
      </div>
    </div>
  </div>

  <?php if(empty($penempatanList)): ?>
  <div class="empty-state"><i class="fas fa-map-marker-alt"></i><p>Belum ada data penempatan</p></div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
    <?php foreach($penempatanList as $p):
      $statusBadge = match($p['status_pembimbing']) {
        'disetujui' => '<span class="pill pill-green">DISETUJUI</span>',
        'ditolak'   => '<span class="pill pill-red">DITOLAK</span>',
        default     => '<span class="pill pill-yellow">PENDING</span>'
      };
    ?>
    <div class="location-card">
      <div class="location-icon" style="background:rgba(99,102,241,.12)"><i class="fas fa-building" style="color:#818cf8"></i></div>
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
        <div style="font-weight:600;color:#f1f5f9;font-size:.95rem"><?= htmlspecialchars($p['nama_perusahaan']) ?></div>
        <?= $statusBadge ?>
      </div>
      <div style="font-size:.78rem;color:#64748b;margin-bottom:10px"><i class="fas fa-map-marker-alt" style="margin-right:4px"></i><?= htmlspecialchars($p['alamat_perusahaan']) ?></div>
      <?php if($p['website']): ?>
      <div style="font-size:.78rem;color:#818cf8;margin-bottom:10px"><i class="fas fa-globe" style="margin-right:4px"></i><?= htmlspecialchars($p['website']) ?></div>
      <?php endif; ?>
      <div style="padding-top:10px;border-top:1px solid rgba(255,255,255,.06)">
        <div style="font-size:.72rem;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Siswa (<?= $p['jumlah_siswa'] ?>)</div>
        <div style="font-size:.8rem;color:#cbd5e1"><?= htmlspecialchars($p['nama_siswa']) ?></div>
      </div>
      <div style="margin-top:10px;font-size:.72rem;color:#334155">
        <i class="fas fa-calendar" style="margin-right:4px"></i>
        Diajukan: <?= date('d M Y', strtotime($p['tanggal_pengajuan'])) ?>
      </div>
      <?php if($p['status_pembimbing'] === 'pending'): ?>
      <div style="display:flex;gap:8px;margin-top:12px">
        <form method="POST" action="ajax/penempatan_action.php" style="flex:1">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <input type="hidden" name="action" value="setujui">
          <button type="submit" class="btn btn-success btn-sm" style="width:100%;justify-content:center"><i class="fas fa-check"></i> Setujui</button>
        </form>
        <form method="POST" action="ajax/penempatan_action.php" style="flex:1">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <input type="hidden" name="action" value="tolak">
          <button type="submit" class="btn btn-danger btn-sm" style="width:100%;justify-content:center"><i class="fas fa-times"></i> Tolak</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>
