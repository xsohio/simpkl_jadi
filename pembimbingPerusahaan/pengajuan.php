<?php
require_once 'config.php';
requireRole('wakasek');
$activePage = 'pengajuan';
$pageTitle  = 'Pengajuan PKL';
$wid = getWakasekId();
$pdo = getDB();

// Handle aksi setujui / tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pid    = (int)$_POST['pengajuan_id'];
    $action = $_POST['action'];
    $catatan = trim($_POST['catatan'] ?? '');
    $newStatus = $action === 'setujui' ? 'disetujui' : 'ditolak';
    $stmt = $pdo->prepare("UPDATE pkl_pengajuan SET status_admin=?, catatan_admin=? WHERE id=?");
    $stmt->execute([$newStatus, $catatan ?: null, $pid]);

    require_once 'log_helper.php';
    logAktivitas($pdo, $wid, ($newStatus==='disetujui'?'Menyetujui':'Menolak').' pengajuan PKL ID '.$pid, 'wakasek');

    $_SESSION['toast'] = ['msg'=>'Pengajuan berhasil '.($newStatus==='disetujui'?'disetujui':'ditolak').'!','type'=>'success'];
    header('Location: pengajuan.php'); exit;
}

$filter = $_GET['filter'] ?? 'all';
$sql = "
    SELECT pp.*, u.nama_depan, u.nama_belakang,
           COUNT(pa.id) as jumlah_siswa,
           pu.nama_depan AS pname, pu.nama_belakang AS plname
    FROM pkl_pengajuan pp
    JOIN users u ON u.id = pp.ketua_id
    LEFT JOIN pkl_anggota pa ON pa.pengajuan_id = pp.id
    LEFT JOIN users pu ON pu.id = pp.pembimbing_id
    WHERE 1=1
";
$params = [];
if ($filter !== 'all') { $sql .= " AND pp.status_admin=?"; $params[] = $filter; }
$sql .= " GROUP BY pp.id ORDER BY pp.tanggal_pengajuan DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$pengajuanList = $stmt->fetchAll();

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Pengajuan PKL</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(245,158,11,.1);padding:10px;border-radius:10px"><i class="fas fa-file-signature" style="color:#f59e0b;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Verifikasi Pengajuan PKL</h3>
        <p style="margin:0;color:#64748b;font-size:.8rem">Setujui atau tolak pengajuan tempat PKL siswa</p>
      </div>
    </div>
  </div>

  <div class="filter-bar">
    <div class="tab-bar" style="margin-bottom:0">
      <?php foreach(['all'=>'Semua','pending'=>'Pending','disetujui'=>'Disetujui','ditolak'=>'Ditolak'] as $val=>$lbl): ?>
      <a href="pengajuan.php?filter=<?=$val?>" class="tab-btn <?=$filter===$val?'active':''?>"><?=$lbl?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if(empty($pengajuanList)): ?>
  <div class="empty-state"><i class="fas fa-file-signature"></i><p>Tidak ada pengajuan</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table>
      <thead><tr>
        <th><i class="fas fa-user"></i> Ketua / Siswa</th>
        <th><i class="fas fa-building"></i> Perusahaan</th>
        <th><i class="fas fa-users"></i> Anggota</th>
        <th><i class="fas fa-user-tie"></i> Pembimbing</th>
        <th><i class="fas fa-calendar"></i> Tgl Pengajuan</th>
        <th><i class="fas fa-info-circle"></i> Status</th>
        <th style="text-align:center"><i class="fas fa-cog"></i> Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach($pengajuanList as $p):
        $nama = htmlspecialchars($p['nama_depan'].' '.$p['nama_belakang']);
        $statusMap = ['pending'=>'pill-yellow','disetujui'=>'pill-green','ditolak'=>'pill-red'];
        $pillClass = $statusMap[$p['status_admin']] ?? 'pill-gray';
      ?>
      <tr>
        <td>
          <div style="font-weight:600;color:#f1f5f9"><?=$nama?></div>
          <div style="font-size:.72rem;color:#64748b">Ketua kelompok</div>
        </td>
        <td>
          <div style="color:#cbd5e1"><?= htmlspecialchars($p['nama_perusahaan']) ?></div>
          <div style="font-size:.72rem;color:#64748b"><?= htmlspecialchars($p['alamat_perusahaan']) ?></div>
        </td>
        <td><span class="pill pill-blue"><?= $p['jumlah_siswa'] ?> siswa</span></td>
        <td style="color:#94a3b8;font-size:.83rem"><?= htmlspecialchars(trim(($p['pname']??'').' '.($p['plname']??'')) ?: '-') ?></td>
        <td style="color:#94a3b8"><?= date('d M Y', strtotime($p['tanggal_pengajuan'])) ?></td>
        <td><span class="pill <?=$pillClass?>"><?= strtoupper($p['status_admin']) ?></span></td>
        <td style="text-align:center">
          <button class="btn-action btn-view"
            onclick="openPengajuanModal(<?=$p['id']?>, <?=json_encode($nama)?>, <?=json_encode($p['nama_perusahaan'])?>, '<?=$p['status_admin']?>', <?=json_encode($p['catatan_admin']??'')?>, <?=$p['jumlah_siswa']?>)"
            title="Review">
            <i class="fas fa-eye"></i>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- MODAL PENGAJUAN -->
<div class="modal-overlay" id="modal-pengajuan">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modal-peng-title">Review Pengajuan</h3>
      <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fas fa-times"></i></button>
    </div>
    <div id="modal-peng-body"></div>
    <form method="POST" id="form-pengajuan" style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.06)">
      <input type="hidden" name="pengajuan_id" id="f-peng-id">
      <div class="form-group">
        <label class="form-label">Catatan (opsional)</label>
        <textarea name="catatan" class="form-control" id="f-peng-catatan" placeholder="Catatan untuk siswa / pembimbing..."></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" name="action" value="setujui" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Setujui</button>
        <button type="submit" name="action" value="tolak"   class="btn btn-danger  btn-sm"><i class="fas fa-times"></i> Tolak</button>
      </div>
    </form>
  </div>
</div>

<script>
function openPengajuanModal(id, nama, perusahaan, status, catatan, jumlah) {
  document.getElementById('modal-pengajuan').classList.add('active');
  document.getElementById('modal-peng-title').textContent = 'Review – ' + nama;
  const pillMap = {pending:'pill-yellow',disetujui:'pill-green',ditolak:'pill-red'};
  document.getElementById('modal-peng-body').innerHTML = `
    <div style="margin-bottom:12px">
      <div style="font-size:.75rem;color:#64748b;margin-bottom:4px">Perusahaan / DU-DI</div>
      <div style="color:#f1f5f9;font-weight:600">${perusahaan}</div>
    </div>
    <div style="margin-bottom:12px">
      <div style="font-size:.75rem;color:#64748b;margin-bottom:4px">Jumlah Siswa</div>
      <span class="pill pill-blue">${jumlah} siswa</span>
    </div>
    <div style="margin-bottom:12px"><span class="pill ${pillMap[status]||'pill-gray'}">${status.toUpperCase()}</span></div>
    ${catatan ? `<div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:12px;font-size:.83rem;color:#ef4444;margin-bottom:12px"><i class="fas fa-exclamation-triangle"></i> ${catatan}</div>` : ''}
  `;
  document.getElementById('f-peng-id').value = id;
  document.getElementById('f-peng-catatan').value = catatan || '';
  document.getElementById('form-pengajuan').style.display = status !== 'disetujui' ? 'block' : 'none';
}
</script>

<?php include 'partials/footer.php'; ?>
