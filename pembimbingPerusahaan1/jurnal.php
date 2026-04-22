<?php
require_once 'config.php';
requireRole('pembimbing');
$activePage = 'jurnal';
$pageTitle  = 'Jurnal Harian';
$pid = getPembimbingId();
$pdo = getDB();

// Handle POST validasi jurnal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $jid    = (int)$_POST['jurnal_id'];
    $action = $_POST['action'];
    $komentar = trim($_POST['komentar'] ?? '');
    $newStatus = $action === 'setujui' ? 'valid' : 'tolak';
    $stmt = $pdo->prepare("UPDATE jurnal_harian SET status_validasi=?, komentar_pembimbing=? WHERE id=?");
    $stmt->execute([$newStatus, $komentar ?: null, $jid]);
    $_SESSION['toast'] = ['msg'=>'Jurnal berhasil '.($newStatus==='valid'?'divalidasi':'ditolak').'!','type'=>$newStatus==='valid'?'success':'error'];
    header('Location: jurnal.php'); exit;
}

// Fetch jurnal
$filter = $_GET['filter'] ?? 'all';
$sql = "
    SELECT jh.*, u.nama_depan, u.nama_belakang, ps.kelas
    FROM jurnal_harian jh
    JOIN users u ON u.id = jh.siswa_id
    LEFT JOIN profil_siswa ps ON ps.user_id = jh.siswa_id
    JOIN pkl_anggota pa ON pa.siswa_id = jh.siswa_id
    JOIN pkl_pengajuan pp ON pp.id = pa.pengajuan_id
    WHERE pp.pembimbing_id = ?
";
$params = [$pid];
if ($filter !== 'all') { $sql .= " AND jh.status_validasi=?"; $params[] = $filter; }
$sql .= " ORDER BY jh.tanggal DESC, jh.id DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$jurnalList = $stmt->fetchAll();

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Jurnal Harian</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(245,158,11,.1);padding:10px;border-radius:10px"><i class="fas fa-book-open" style="color:#f59e0b;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Validasi Jurnal Harian</h3>
        <p style="color:#64748b;font-size:.8rem;margin:0">Review dan validasi kegiatan harian siswa bimbingan.</p>
      </div>
    </div>
  </div>

  <div class="filter-bar">
    <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Cari siswa atau kegiatan..." id="search-jurnal" oninput="filterJurnal()"></div>
    <div class="tab-bar" style="margin-bottom:0">
      <?php foreach(['all'=>'Semua','pending'=>'Pending','valid'=>'Valid','tolak'=>'Tolak'] as $val=>$lbl): ?>
      <a href="jurnal.php?filter=<?=$val?>" class="tab-btn <?=$filter===$val?'active':''?>"><?=$lbl?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if(empty($jurnalList)): ?>
  <div class="empty-state"><i class="fas fa-book"></i><p>Tidak ada jurnal <?= $filter !== 'all' ? "dengan status '$filter'" : '' ?></p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table>
      <thead><tr>
        <th><i class="fas fa-user"></i> Siswa</th>
        <th><i class="fas fa-calendar-alt"></i> Tanggal &amp; Waktu</th>
        <th><i class="fas fa-tasks"></i> Kegiatan</th>
        <th><i class="fas fa-info-circle"></i> Status</th>
        <th style="text-align:center"><i class="fas fa-cog"></i> Aksi</th>
      </tr></thead>
      <tbody id="jurnal-tbody">
      <?php foreach($jurnalList as $j):
        $nama  = htmlspecialchars($j['nama_depan'].' '.$j['nama_belakang']);
        $kelas = htmlspecialchars($j['kelas'] ?? '-');
        $statusMap = ['valid'=>['pill-green','VALID'],'pending'=>['pill-yellow','PENDING'],'tolak'=>['pill-red','TOLAK']];
        [$pillClass,$pillText] = $statusMap[$j['status_validasi']] ?? ['pill-gray','–'];
      ?>
      <tr data-search="<?= strtolower($nama.' '.$j['kegiatan']) ?>">
        <td>
          <div style="font-weight:600;color:#f1f5f9"><?=$nama?></div>
          <div style="font-size:.72rem;color:#64748b"><?=$kelas?></div>
        </td>
        <td>
          <div style="color:#cbd5e1"><?= date('d M Y', strtotime($j['tanggal'])) ?></div>
          <div style="font-size:.72rem;color:#64748b"><?= substr($j['jam_masuk']??'',0,5) ?> – <?= substr($j['jam_keluar']??'',0,5) ?></div>
        </td>
        <td style="max-width:300px">
          <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px"><?= htmlspecialchars($j['kegiatan']) ?></div>
          <?php if($j['komentar_pembimbing']): ?>
          <div style="font-size:.72rem;color:#818cf8;margin-top:4px"><i class="fas fa-comment"></i> <?= htmlspecialchars($j['komentar_pembimbing']) ?></div>
          <?php endif; ?>
        </td>
        <td><span class="pill <?=$pillClass?>"><?=$pillText?></span></td>
        <td style="text-align:center">
          <button class="btn-action btn-view" onclick="openJurnalModal(<?=$j['id']?>, <?=json_encode($nama)?>, <?=json_encode($j['kegiatan'])?>, '<?=$j['status_validasi']?>')" title="Lihat & Validasi"><i class="fas fa-eye"></i></button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- MODAL DETAIL JURNAL -->
<div class="modal-overlay" id="modal-jurnal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modal-jurnal-title">Detail Jurnal</h3>
      <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fas fa-times"></i></button>
    </div>
    <div id="modal-jurnal-body"></div>
    <form method="POST" id="form-validasi" style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.06)">
      <input type="hidden" name="jurnal_id" id="f-jurnal-id">
      <div class="form-group">
        <label class="form-label">Komentar (opsional)</label>
        <textarea name="komentar" class="form-control" placeholder="Catatan untuk siswa..."></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" name="action" value="setujui" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Validasi</button>
        <button type="submit" name="action" value="tolak" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Tolak</button>
      </div>
    </form>
  </div>
</div>

<script>
function filterJurnal() {
  const q = document.getElementById('search-jurnal').value.toLowerCase();
  document.querySelectorAll('#jurnal-tbody tr').forEach(tr=>{
    tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
  });
}
function openJurnalModal(id, nama, kegiatan, status) {
  document.getElementById('modal-jurnal').classList.add('active');
  document.getElementById('modal-jurnal-title').textContent = 'Jurnal – ' + nama;
  document.getElementById('modal-jurnal-body').innerHTML = `
    <div style="background:rgba(0,0,0,.2);border-radius:10px;padding:14px;font-size:.875rem;color:#cbd5e1;line-height:1.7">${kegiatan}</div>
    <div style="margin-top:10px"><span style="font-size:.75rem;color:#64748b">Status saat ini:</span> <span class="pill ${{'valid':'pill-green','pending':'pill-yellow','tolak':'pill-red'}[status]||'pill-gray'}">${status.toUpperCase()}</span></div>
  `;
  document.getElementById('f-jurnal-id').value = id;
  const showForm = status === 'pending';
  document.getElementById('form-validasi').style.display = showForm ? 'block' : 'none';
}
</script>

<?php include 'partials/footer.php'; ?>
