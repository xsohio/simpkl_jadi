<?php
require_once 'config.php';
requireRole('pembimbing');
$activePage = 'laporan';
$pageTitle  = 'Laporan PKL';
$pid = getPembimbingId();
$pdo = getDB();

// Handle action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $lid    = (int)$_POST['laporan_id'];
    $action = $_POST['action'];
    $catatan = trim($_POST['catatan'] ?? '');
    $newStatus = $action === 'setujui' ? 'disetujui' : 'revisi';
    $stmt = $pdo->prepare("UPDATE laporan_pkl SET status_pembimbing=?, catatan_revisi=? WHERE id=?");
    $stmt->execute([$newStatus, $catatan ?: null, $lid]);
    $_SESSION['toast'] = ['msg'=>'Laporan berhasil '.($newStatus==='disetujui'?'disetujui':'dikirim revisi').'!','type'=>'success'];
    header('Location: laporan.php'); exit;
}

$filter = $_GET['filter'] ?? 'all';
$sql = "
    SELECT lp.*, u.nama_depan, u.nama_belakang, ps.kelas
    FROM laporan_pkl lp
    JOIN users u ON u.id = lp.siswa_id
    LEFT JOIN profil_siswa ps ON ps.user_id = lp.siswa_id
    JOIN pkl_anggota pa ON pa.siswa_id = lp.siswa_id
    JOIN pkl_pengajuan pp ON pp.id = pa.pengajuan_id
    WHERE pp.pembimbing_id = ?
";
$params = [$pid];
if ($filter !== 'all') { $sql .= " AND lp.status_pembimbing=?"; $params[] = $filter; }
$sql .= " ORDER BY lp.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$laporanList = $stmt->fetchAll();

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Laporan PKL</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(239,68,68,.1);padding:10px;border-radius:10px"><i class="fas fa-file-alt" style="color:#ef4444;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Review Laporan PKL</h3>
        <p style="margin:0;color:#64748b;font-size:.8rem">Periksa dan validasi laporan siswa</p>
      </div>
    </div>
  </div>

  <div class="filter-bar">
    <div class="tab-bar" style="margin-bottom:0">
      <?php foreach(['all'=>'Semua','pending'=>'Pending','disetujui'=>'Disetujui','revisi'=>'Revisi'] as $val=>$lbl): ?>
      <a href="laporan.php?filter=<?=$val?>" class="tab-btn <?=$filter===$val?'active':''?>"><?=$lbl?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if(empty($laporanList)): ?>
  <div class="empty-state"><i class="fas fa-file-alt"></i><p>Tidak ada laporan</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table>
      <thead><tr>
        <th><i class="fas fa-user"></i> Siswa</th>
        <th><i class="fas fa-file-pdf"></i> Judul Laporan</th>
        <th><i class="fas fa-tag"></i> Jenis</th>
        <th><i class="fas fa-calendar"></i> Tgl Submit</th>
        <th><i class="fas fa-info-circle"></i> Status</th>
        <th style="text-align:center"><i class="fas fa-cog"></i> Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach($laporanList as $l):
        $nama = htmlspecialchars($l['nama_depan'].' '.$l['nama_belakang']);
        $statusMap = ['pending'=>'pill-yellow','disetujui'=>'pill-green','revisi'=>'pill-red'];
        $pillClass = $statusMap[$l['status_pembimbing']] ?? 'pill-gray';
      ?>
      <tr>
        <td>
          <div style="font-weight:600;color:#f1f5f9"><?=$nama?></div>
          <div style="font-size:.72rem;color:#64748b"><?= htmlspecialchars($l['kelas']??'-') ?></div>
        </td>
        <td style="max-width:260px">
          <div style="color:#cbd5e1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($l['judul_laporan']??'-') ?></div>
          <?php if($l['catatan_revisi']): ?>
          <div style="font-size:.72rem;color:#ef4444;margin-top:4px"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($l['catatan_revisi']) ?></div>
          <?php endif; ?>
        </td>
        <td><span class="pill pill-blue"><?= ucfirst($l['jenis_laporan']) ?></span></td>
        <td style="color:#94a3b8"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
        <td><span class="pill <?=$pillClass?>"><?= strtoupper($l['status_pembimbing']) ?></span></td>
        <td style="text-align:center">
          <button class="btn-action btn-view" onclick="openLaporanModal(<?=$l['id']?>, <?=json_encode($nama)?>, <?=json_encode($l['judul_laporan'])?>, '<?=$l['status_pembimbing']?>', <?=json_encode($l['catatan_revisi']??'')?>, '<?=$l['file_path']??''?>')" title="Lihat & Review">
            <i class="fas fa-eye"></i>
          </button>
          <?php if($l['file_path']): ?>
          <a href="../<?= htmlspecialchars($l['file_path']) ?>" target="_blank" class="btn-action btn-approve" title="Download"><i class="fas fa-download"></i></a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- MODAL LAPORAN -->
<div class="modal-overlay" id="modal-laporan">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modal-laporan-title">Review Laporan</h3>
      <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fas fa-times"></i></button>
    </div>
    <div id="modal-laporan-body"></div>
    <form method="POST" id="form-laporan" style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.06)">
      <input type="hidden" name="laporan_id" id="f-lap-id">
      <div class="form-group">
        <label class="form-label">Catatan / Revisi</label>
        <textarea name="catatan" class="form-control" id="f-lap-catatan" placeholder="Catatan untuk siswa..."></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" name="action" value="setujui" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Setujui</button>
        <button type="submit" name="action" value="revisi" class="btn btn-warning btn-sm"><i class="fas fa-redo"></i> Minta Revisi</button>
      </div>
    </form>
  </div>
</div>

<script>
function openLaporanModal(id, nama, judul, status, catatan, file) {
  document.getElementById('modal-laporan').classList.add('active');
  document.getElementById('modal-laporan-title').textContent = 'Review – ' + nama;
  const pillMap = {pending:'pill-yellow',disetujui:'pill-green',revisi:'pill-red'};
  document.getElementById('modal-laporan-body').innerHTML = `
    <div style="margin-bottom:12px">
      <div style="font-size:.75rem;color:#64748b;margin-bottom:4px">Judul Laporan</div>
      <div style="color:#f1f5f9;font-weight:600">${judul||'-'}</div>
    </div>
    <div style="margin-bottom:12px"><span class="pill ${pillMap[status]||'pill-gray'}">${status.toUpperCase()}</span></div>
    ${catatan ? `<div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:12px;font-size:.83rem;color:#ef4444;margin-bottom:12px"><i class="fas fa-exclamation-triangle"></i> ${catatan}</div>` : ''}
    ${file ? `<a href="../${file}" target="_blank" class="btn btn-indigo btn-sm" style="margin-bottom:8px"><i class="fas fa-download"></i> Download File</a>` : '<p style="font-size:.8rem;color:#64748b"><i class="fas fa-times-circle"></i> Tidak ada file</p>'}
  `;
  document.getElementById('f-lap-id').value = id;
  document.getElementById('f-lap-catatan').value = catatan || '';
  document.getElementById('form-laporan').style.display = status !== 'disetujui' ? 'block' : 'none';
}
</script>

<?php include 'partials/footer.php'; ?>
