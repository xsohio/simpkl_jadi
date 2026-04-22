<?php
require_once 'config.php';
requireRole('wakasek');
$activePage = 'siswa';
$pageTitle  = 'Data Siswa PKL';
$wid = getWakasekId();
$pdo = getDB();

// Filter
$filterKelas   = trim($_GET['kelas']   ?? '');
$filterJurusan = trim($_GET['jurusan'] ?? '');
$siswaList = getAllSiswaPKL(['kelas' => $filterKelas, 'jurusan' => $filterJurusan]);

// Daftar kelas & jurusan untuk dropdown
$stmtKelas   = $pdo->query("SELECT DISTINCT kelas FROM profil_siswa WHERE kelas IS NOT NULL ORDER BY kelas");
$stmtJurusan = $pdo->query("SELECT DISTINCT jurusan FROM profil_siswa WHERE jurusan IS NOT NULL ORDER BY jurusan");

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Data Siswa PKL</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(99,102,241,.1);padding:10px;border-radius:10px">
        <i class="fas fa-user-graduate" style="color:#818cf8;font-size:1.5rem"></i>
      </div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Data Siswa PKL Aktif</h3>
        <p style="color:#64748b;font-size:.8rem;margin:0">Total <?= count($siswaList) ?> siswa sedang melaksanakan PKL.</p>
      </div>
    </div>
  </div>

  <div class="filter-bar">
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Cari nama / NIS siswa..." id="search-siswa" oninput="filterSiswa()">
    </div>
    <select class="form-control" style="width:auto" id="filter-kelas" onchange="filterSiswa()">
      <option value="">Semua Kelas</option>
      <?php foreach($stmtKelas->fetchAll() as $k): ?>
      <option value="<?= htmlspecialchars($k['kelas']) ?>" <?= $filterKelas===$k['kelas']?'selected':'' ?>><?= htmlspecialchars($k['kelas']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-control" style="width:auto" id="filter-jurusan" onchange="filterSiswa()">
      <option value="">Semua Jurusan</option>
      <?php foreach($stmtJurusan->fetchAll() as $j): ?>
      <option value="<?= htmlspecialchars($j['jurusan']) ?>" <?= $filterJurusan===$j['jurusan']?'selected':'' ?>><?= htmlspecialchars($j['jurusan']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if(empty($siswaList)): ?>
  <div class="empty-state"><i class="fas fa-user-graduate"></i><p>Tidak ada siswa PKL aktif</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table>
      <thead><tr>
        <th><i class="fas fa-hashtag"></i> No</th>
        <th><i class="fas fa-user"></i> Nama Siswa</th>
        <th><i class="fas fa-id-card"></i> NIS</th>
        <th><i class="fas fa-chalkboard"></i> Kelas / Jurusan</th>
        <th><i class="fas fa-building"></i> Tempat PKL</th>
        <th><i class="fas fa-user-tie"></i> Pembimbing</th>
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
        $jurusan = htmlspecialchars($s['jurusan'] ?? '-');
        $tempat  = htmlspecialchars($s['nama_perusahaan'] ?? '-');
        $pembimbing = htmlspecialchars(($s['pembimbing_depan']??'').' '.($s['pembimbing_belakang']??''));
        $statusPill = $s['status_pembimbing'] === 'disetujui'
            ? '<span class="pill pill-green">AKTIF</span>'
            : '<span class="pill pill-yellow">'.strtoupper($s['status_pembimbing']).'</span>';
      ?>
      <tr data-nama="<?= strtolower($nama) ?>" data-nis="<?= strtolower($nis) ?>" data-kelas="<?= strtolower($kelas) ?>" data-jurusan="<?= strtolower($jurusan) ?>">
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
        <td>
          <div style="color:#cbd5e1"><?=$kelas?></div>
          <div style="font-size:.72rem;color:#64748b"><?=$jurusan?></div>
        </td>
        <td><?=$tempat?></td>
        <td>
          <div style="font-size:.83rem;color:#94a3b8"><?= trim($pembimbing) ?: '-' ?></div>
        </td>
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
  const jr = document.getElementById('filter-jurusan').value.toLowerCase();
  document.querySelectorAll('#siswa-tbody tr').forEach(tr => {
    const nama    = tr.dataset.nama;
    const nis     = tr.dataset.nis;
    const kelas   = tr.dataset.kelas;
    const jurusan = tr.dataset.jurusan;
    const matchQ  = (nama.includes(q) || nis.includes(q));
    const matchK  = kelas.includes(kl);
    const matchJ  = jurusan.includes(jr);
    tr.style.display = (matchQ && matchK && matchJ) ? '' : 'none';
  });
}

function openDetail(id) {
  document.getElementById('modal-siswa-detail').classList.add('active');
  fetch('ajax/siswa_detail.php?id=' + id)
    .then(r => r.text()).then(h => { document.getElementById('modal-siswa-content').innerHTML = h; });
}
</script>

<?php include 'partials/footer.php'; ?>
