<?php
require_once 'config.php';
requireRole('wakasek');
$activePage = 'penempatan';
$pageTitle  = 'Penempatan PKL';
$wid = getWakasekId();
$pdo = getDB();

// Wakasek dapat approve/reject pengajuan (status_admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pid    = (int)$_POST['id'];
    $action = $_POST['action'];
    $newStatus = $action === 'setujui' ? 'disetujui' : 'ditolak';
    $stmt = $pdo->prepare("UPDATE pkl_pengajuan SET status_admin=? WHERE id=?");
    $stmt->execute([$newStatus, $pid]);
    require_once 'log_helper.php';
    logAktivitas($pdo, $wid, ($newStatus==='disetujui'?'Menyetujui':'Menolak').' penempatan PKL ID '.$pid, 'wakasek');
    $_SESSION['toast'] = ['msg'=>'Penempatan berhasil '.($newStatus==='disetujui'?'disetujui':'ditolak').'!','type'=>'success'];
    header('Location: penempatan.php'); exit;
}

// Ambil semua penempatan
$stmt = $pdo->query("
    SELECT pp.*, u.nama_depan, u.nama_belakang,
           COUNT(pa.id) as jumlah_siswa,
           GROUP_CONCAT(CONCAT(u2.nama_depan,' ',u2.nama_belakang) SEPARATOR ', ') as nama_siswa,
           pu.nama_depan AS pname, pu.nama_belakang AS plname
    FROM pkl_pengajuan pp
    JOIN users u ON u.id = pp.ketua_id
    JOIN pkl_anggota pa ON pa.pengajuan_id = pp.id
    JOIN users u2 ON u2.id = pa.siswa_id
    LEFT JOIN users pu ON pu.id = pp.pembimbing_id
    GROUP BY pp.id
    ORDER BY pp.tanggal_pengajuan DESC
");
$penempatanList = $stmt->fetchAll();

$filterStatus = $_GET['status'] ?? 'all';
include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Penempatan PKL</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(20,184,166,.1);padding:10px;border-radius:10px"><i class="fas fa-map-marker-alt" style="color:#2dd4bf;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Penempatan PKL Siswa</h3>
        <p style="margin:0;color:#64748b;font-size:.8rem">Seluruh lokasi &amp; tempat PKL siswa — semua jurusan</p>
      </div>
    </div>
    <div class="tab-bar" style="margin-bottom:0">
      <?php foreach(['all'=>'Semua','pending'=>'Pending','disetujui'=>'Disetujui','ditolak'=>'Ditolak'] as $v=>$l): ?>
      <a href="penempatan.php?status=<?=$v?>" class="tab-btn <?=$filterStatus===$v?'active':''?>"><?=$l?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php
  $filtered = array_filter($penempatanList, function($p) use ($filterStatus) {
    return $filterStatus === 'all' || $p['status_admin'] === $filterStatus;
  });
  if(empty($filtered)): ?>
  <div class="empty-state"><i class="fas fa-map-marker-alt"></i><p>Tidak ada data penempatan</p></div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
    <?php foreach($filtered as $p):
      $statusBadge = match($p['status_admin']) {
        'disetujui' => '<span class="pill pill-green">DISETUJUI</span>',
        'ditolak'   => '<span class="pill pill-red">DITOLAK</span>',
        default     => '<span class="pill pill-yellow">PENDING</span>'
      };
      $pembimbing = trim(($p['pname']??'').' '.($p['plname']??''));
    ?>
    <div class="location-card">
      <div class="location-icon" style="background:rgba(99,102,241,.12)"><i class="fas fa-building" style="color:#818cf8"></i></div>
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
        <div style="font-weight:600;color:#f1f5f9;font-size:.95rem"><?= htmlspecialchars($p['nama_perusahaan']) ?></div>
        <?= $statusBadge ?>
      </div>
      <div style="font-size:.78rem;color:#64748b;margin-bottom:6px"><i class="fas fa-map-marker-alt" style="margin-right:4px"></i><?= htmlspecialchars($p['alamat_perusahaan']) ?></div>
      <?php if($p['website']): ?>
      <div style="font-size:.78rem;color:#818cf8;margin-bottom:6px"><i class="fas fa-globe" style="margin-right:4px"></i><?= htmlspecialchars($p['website']) ?></div>
      <?php endif; ?>
      <?php if($pembimbing): ?>
      <div style="font-size:.78rem;color:#94a3b8;margin-bottom:6px"><i class="fas fa-user-tie" style="margin-right:4px"></i><?= htmlspecialchars($pembimbing) ?></div>
      <?php endif; ?>
      <div style="padding-top:10px;border-top:1px solid rgba(255,255,255,.06);margin-top:8px">
        <div style="font-size:.72rem;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Siswa (<?= $p['jumlah_siswa'] ?>)</div>
        <div style="font-size:.8rem;color:#cbd5e1"><?= htmlspecialchars($p['nama_siswa']) ?></div>
      </div>
      <div style="margin-top:8px;font-size:.72rem;color:#334155">
        <i class="fas fa-calendar" style="margin-right:4px"></i>
        Diajukan: <?= date('d M Y', strtotime($p['tanggal_pengajuan'])) ?>
      </div>
      <?php if($p['status_admin'] === 'pending'): ?>
      <div style="display:flex;gap:8px;margin-top:12px">
        <form method="POST" style="flex:1">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <input type="hidden" name="action" value="setujui">
          <button type="submit" class="btn btn-success btn-sm" style="width:100%;justify-content:center"><i class="fas fa-check"></i> Setujui</button>
        </form>
        <form method="POST" style="flex:1">
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
