<?php
require_once 'config.php';
requireRole('pembimbing');
$activePage = 'siswa';
$pageTitle  = 'Daftar Siswa';
$pid = getPembimbingId();
$pdo = getDB();
$siswaList = getSiswaBimbingan($pid);
include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Daftar Siswa Bimbingan</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(99,102,241,.1);padding:10px;border-radius:10px">
        <i class="fas fa-user-graduate" style="color:#818cf8;font-size:1.5rem"></i>
      </div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Daftar Siswa Bimbingan</h3>
        <p style="color:#64748b;font-size:.8rem;margin:0">Total <?= count($siswaList) ?> siswa aktif PKL di bawah bimbingan Anda.</p>
      </div>
    </div>
  </div>

  <div class="filter-bar">
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Cari siswa..." id="search-siswa" oninput="filterSiswa()">
    </div>
    <select class="form-control" style="width:auto" id="filter-kelas" onchange="filterSiswa()">
      <option value="">Semua Kelas</option>
      <?php
      $kelasList = array_unique(array_column($siswaList, 'kelas'));
      foreach($kelasList as $k) if($k) echo '<option value="'.htmlspecialchars($k).'">'.htmlspecialchars($k).'</option>';
      ?>
    </select>
  </div>

  <?php if(empty($siswaList)): ?>
  <div class="empty-state"><i class="fas fa-user-graduate"></i><p>Belum ada siswa yang ditugaskan kepada Anda</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table>
      <thead><tr>
        <th><i class="fas fa-hashtag"></i> No</th>
        <th><i class="fas fa-user"></i> Nama Siswa</th>
        <th><i class="fas fa-id-card"></i> NIS</th>
        <th><i class="fas fa-chalkboard"></i> Kelas</th>
        <th><i class="fas fa-building"></i> Tempat PKL</th>
        <th><i class="fas fa-circle"></i> Status</th>
        <th style="text-align:center"><i class="fas fa-cog"></i> Aksi</th>
      </tr></thead>
      <tbody id="siswa-tbody">
      <?php foreach($siswaList as $i=>$s):
        $inisial = initials($s['nama_depan'], $s['nama_belakang']);
        $color   = avatarColor($s['id']);
        $nama    = htmlspecialchars($s['nama_depan'].' '.$s['nama_belakang']);
        $nis     = htmlspecialchars($s['nis'] ?? '-');
        $kelas   = htmlspecialchars($s['kelas'] ?? '-');
        $tempat  = htmlspecialchars($s['nama_perusahaan'] ?? '-');
        $statusPill = $s['status_pembimbing'] === 'disetujui'
            ? '<span class="pill pill-green">AKTIF</span>'
            : '<span class="pill pill-yellow">'.strtoupper($s['status_pembimbing']).'</span>';
      ?>
      <tr data-nama="<?=$nama?>" data-kelas="<?=$kelas?>">
        <td><?= $i+1 ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:34px;height:34px;border-radius:50%;background:<?=$color?>22;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:<?=$color?>;flex-shrink:0"><?=$inisial?></div>
            <div>
              <div style="font-weight:600;color:#f1f5f9"><?=$nama?></div>
              <div style="font-size:.72rem;color:#64748b"><?= htmlspecialchars($s['no_hp'] ?? '') ?></div>
            </div>
          </div>
        </td>
        <td><?=$nis?></td>
        <td><?=$kelas?></td>
        <td><?=$tempat?></td>
        <td><?=$statusPill?></td>
        <td style="text-align:center">
          <button class="btn-action btn-view" onclick="openDetail(<?=$s['id']?>)" title="Detail"><i class="fas fa-eye"></i></button>
          <a href="absensi.php?siswa_id=<?=$s['id']?>" class="btn-action btn-approve" title="Absensi"><i class="fas fa-calendar-check"></i></a>
          <a href="nilai.php?siswa_id=<?=$s['id']?>" class="btn-action btn-edit" title="Nilai"><i class="fas fa-star"></i></a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- MODAL DETAIL SISWA -->
<div class="modal-overlay" id="modal-siswa-detail">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modal-siswa-nama">Detail Siswa</h3>
      <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fas fa-times"></i></button>
    </div>
    <div id="modal-siswa-content"><div style="text-align:center;padding:40px;color:#64748b"><i class="fas fa-spinner fa-spin"></i></div></div>
  </div>
</div>

<script>
function filterSiswa() {
  const q  = document.getElementById('search-siswa').value.toLowerCase();
  const kl = document.getElementById('filter-kelas').value.toLowerCase();
  document.querySelectorAll('#siswa-tbody tr').forEach(tr => {
    const nama  = tr.dataset.nama.toLowerCase();
    const kelas = tr.dataset.kelas.toLowerCase();
    tr.style.display = (nama.includes(q) && kelas.includes(kl)) ? '' : 'none';
  });
}

function openDetail(id) {
  document.getElementById('modal-siswa-detail').classList.add('active');
  fetch('ajax/siswa_detail.php?id=' + id)
    .then(r=>r.text()).then(h=>{document.getElementById('modal-siswa-content').innerHTML=h;});
}
</script>

<?php include 'partials/footer.php'; ?>
