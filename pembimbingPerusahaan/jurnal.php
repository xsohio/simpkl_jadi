<?php
require_once 'config.php';
requireRole('wakasek');
$activePage = 'jurnal';
$pageTitle  = 'Monitor Jurnal Harian';
$wid = getWakasekId();
$pdo = getDB();

// Fetch jurnal — wakasek melihat semua
$filter = $_GET['filter'] ?? 'all';
$sql = "
    SELECT jh.*, u.nama_depan, u.nama_belakang, ps.kelas, ps.jurusan
    FROM jurnal_harian jh
    JOIN users u ON u.id = jh.siswa_id
    LEFT JOIN profil_siswa ps ON ps.user_id = jh.siswa_id
    WHERE 1=1
";
$params = [];
if ($filter !== 'all') { $sql .= " AND jh.status_validasi=?"; $params[] = $filter; }
$sql .= " ORDER BY jh.tanggal DESC, jh.id DESC LIMIT 200";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$jurnalList = $stmt->fetchAll();

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Monitor Jurnal Harian</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(245,158,11,.1);padding:10px;border-radius:10px"><i class="fas fa-book-open" style="color:#f59e0b;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Monitor Jurnal Harian PKL</h3>
        <p style="color:#64748b;font-size:.8rem;margin:0">Pantau aktivitas jurnal harian seluruh siswa PKL.</p>
      </div>
    </div>
  </div>

  <div class="filter-bar">
    <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Cari nama siswa atau kegiatan..." id="search-jurnal" oninput="filterJurnal()"></div>
    <div class="tab-bar" style="margin-bottom:0">
      <?php foreach(['all'=>'Semua','pending'=>'Pending','valid'=>'Valid','tolak'=>'Ditolak'] as $val=>$lbl): ?>
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
        <th><i class="fas fa-chalkboard"></i> Kelas</th>
        <th><i class="fas fa-calendar-alt"></i> Tanggal</th>
        <th><i class="fas fa-tasks"></i> Kegiatan</th>
        <th><i class="fas fa-info-circle"></i> Status</th>
        <th><i class="fas fa-comment"></i> Komentar Pembimbing</th>
      </tr></thead>
      <tbody id="jurnal-tbody">
      <?php foreach($jurnalList as $j):
        $nama  = htmlspecialchars($j['nama_depan'].' '.$j['nama_belakang']);
        $kelas = htmlspecialchars(($j['kelas']??'-').' · '.($j['jurusan']??'-'));
        $statusMap = ['valid'=>['pill-green','VALID'],'pending'=>['pill-yellow','PENDING'],'tolak'=>['pill-red','DITOLAK']];
        [$pillClass,$pillText] = $statusMap[$j['status_validasi']] ?? ['pill-gray','–'];
      ?>
      <tr data-search="<?= strtolower($nama.' '.$j['kegiatan']) ?>">
        <td>
          <div style="font-weight:600;color:#f1f5f9"><?=$nama?></div>
        </td>
        <td style="font-size:.75rem;color:#64748b"><?=$kelas?></td>
        <td>
          <div style="color:#cbd5e1"><?= date('d M Y', strtotime($j['tanggal'])) ?></div>
          <div style="font-size:.72rem;color:#64748b"><?= substr($j['jam_masuk']??'',0,5) ?> – <?= substr($j['jam_keluar']??'',0,5) ?></div>
        </td>
        <td style="max-width:280px">
          <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;color:#cbd5e1"><?= htmlspecialchars($j['kegiatan']) ?></div>
        </td>
        <td><span class="pill <?=$pillClass?>"><?=$pillText?></span></td>
        <td style="font-size:.78rem;color:#818cf8"><?= $j['komentar_pembimbing'] ? htmlspecialchars($j['komentar_pembimbing']) : '<span style="color:#334155">-</span>' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
function filterJurnal() {
  const q = document.getElementById('search-jurnal').value.toLowerCase();
  document.querySelectorAll('#jurnal-tbody tr').forEach(tr=>{
    tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
  });
}
</script>

<?php include 'partials/footer.php'; ?>
