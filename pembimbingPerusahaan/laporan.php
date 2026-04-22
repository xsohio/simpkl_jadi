<?php
require_once 'config.php';
requireRole('wakasek');
$activePage = 'laporan';
$pageTitle  = 'Monitor Laporan PKL';
$wid = getWakasekId();
$pdo = getDB();

$filter = $_GET['filter'] ?? 'all';
$sql = "
    SELECT lp.*, u.nama_depan, u.nama_belakang, ps.kelas, ps.jurusan,
           pu.nama_depan AS pname, pu.nama_belakang AS plname
    FROM laporan_pkl lp
    JOIN users u ON u.id = lp.siswa_id
    LEFT JOIN profil_siswa ps ON ps.user_id = lp.siswa_id
    LEFT JOIN pkl_anggota pa ON pa.siswa_id = lp.siswa_id
    LEFT JOIN pkl_pengajuan pp ON pp.id = pa.pengajuan_id
    LEFT JOIN users pu ON pu.id = pp.pembimbing_id
    WHERE 1=1
";
$params = [];
if ($filter !== 'all') { $sql .= " AND lp.status_pembimbing=?"; $params[] = $filter; }
$sql .= " ORDER BY lp.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$laporanList = $stmt->fetchAll();

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Monitor Laporan PKL</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(239,68,68,.1);padding:10px;border-radius:10px"><i class="fas fa-file-alt" style="color:#ef4444;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Monitor Laporan PKL</h3>
        <p style="margin:0;color:#64748b;font-size:.8rem">Pantau status laporan seluruh siswa PKL</p>
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
        <th><i class="fas fa-user-tie"></i> Pembimbing</th>
        <th><i class="fas fa-calendar"></i> Tgl Submit</th>
        <th><i class="fas fa-info-circle"></i> Status</th>
        <th style="text-align:center"><i class="fas fa-cog"></i> Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach($laporanList as $l):
        $nama = htmlspecialchars($l['nama_depan'].' '.$l['nama_belakang']);
        $pembimbing = htmlspecialchars(trim(($l['pname']??'').' '.($l['plname']??'')));
        $statusMap = ['pending'=>'pill-yellow','disetujui'=>'pill-green','revisi'=>'pill-red'];
        $pillClass = $statusMap[$l['status_pembimbing']] ?? 'pill-gray';
      ?>
      <tr>
        <td>
          <div style="font-weight:600;color:#f1f5f9"><?=$nama?></div>
          <div style="font-size:.72rem;color:#64748b"><?= htmlspecialchars($l['kelas']??'-') ?> · <?= htmlspecialchars($l['jurusan']??'-') ?></div>
        </td>
        <td style="max-width:240px">
          <div style="color:#cbd5e1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($l['judul_laporan']??'-') ?></div>
          <?php if($l['catatan_revisi']): ?>
          <div style="font-size:.72rem;color:#ef4444;margin-top:4px"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($l['catatan_revisi']) ?></div>
          <?php endif; ?>
        </td>
        <td><span class="pill pill-blue"><?= ucfirst($l['jenis_laporan']) ?></span></td>
        <td style="font-size:.83rem;color:#94a3b8"><?= $pembimbing ?: '-' ?></td>
        <td style="color:#94a3b8"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
        <td><span class="pill <?=$pillClass?>"><?= strtoupper($l['status_pembimbing']) ?></span></td>
        <td style="text-align:center">
          <?php if($l['file_path']): ?>
          <a href="../<?= htmlspecialchars($l['file_path']) ?>" target="_blank" class="btn-action btn-approve" title="Download"><i class="fas fa-download"></i></a>
          <?php endif; ?>
          <button class="btn-action btn-view"
            onclick="openLaporanModal(<?=json_encode($nama)?>, <?=json_encode($l['judul_laporan']??'-')?>, '<?=$l['status_pembimbing']?>', <?=json_encode($l['catatan_revisi']??'')?>, '<?=$l['file_path']??''?>')"
            title="Detail">
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

<!-- MODAL DETAIL LAPORAN (read-only untuk wakasek) -->
<div class="modal-overlay" id="modal-laporan">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modal-laporan-title">Detail Laporan</h3>
      <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fas fa-times"></i></button>
    </div>
    <div id="modal-laporan-body"></div>
  </div>
</div>

<script>
function openLaporanModal(nama, judul, status, catatan, file) {
  document.getElementById('modal-laporan').classList.add('active');
  document.getElementById('modal-laporan-title').textContent = 'Laporan – ' + nama;
  const pillMap = {pending:'pill-yellow',disetujui:'pill-green',revisi:'pill-red'};
  document.getElementById('modal-laporan-body').innerHTML = `
    <div style="margin-bottom:12px">
      <div style="font-size:.75rem;color:#64748b;margin-bottom:4px">Judul Laporan</div>
      <div style="color:#f1f5f9;font-weight:600">${judul||'-'}</div>
    </div>
    <div style="margin-bottom:12px"><span class="pill ${pillMap[status]||'pill-gray'}">${status.toUpperCase()}</span></div>
    ${catatan ? `<div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:12px;font-size:.83rem;color:#ef4444;margin-bottom:12px"><i class="fas fa-exclamation-triangle"></i> ${catatan}</div>` : ''}
    ${file ? `<a href="../${file}" target="_blank" class="btn btn-indigo btn-sm"><i class="fas fa-download"></i> Download File</a>` : '<p style="font-size:.8rem;color:#64748b"><i class="fas fa-times-circle"></i> Tidak ada file</p>'}
  `;
}
</script>

<?php include 'partials/footer.php'; ?>
