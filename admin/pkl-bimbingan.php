<?php
session_start();
include "../config.php";
requireAdmin();

// --- 1. PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $res = mysqli_query($conn, "SELECT komentar_pembimbing FROM jurnal_harian WHERE id=$id");
    $row_del = mysqli_fetch_assoc($res);
    $info = $row_del['komentar_pembimbing'] ?? 'Unknown';

    if (mysqli_query($conn, "DELETE FROM jurnal_harian WHERE id=$id")) {
        catatLog($conn, "Menghapus data bimbingan/jurnal id: $id");
        setFlash('success', 'Data berhasil dihapus.');
    }
    redirect('pkl-bimbingan.php');
}

// --- 2. PROSES SIMPAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id              = (int)($_POST['id'] ?? 0);
    $siswa_id        = (int)$_POST['siswa_id'];
    $tanggal         = clean($_POST['tanggal']);
    $jam_masuk       = clean($_POST['jam_masuk']);
    $jam_keluar      = clean($_POST['jam_keluar']);
    $kegiatan        = clean($_POST['kegiatan']);
    $status_validasi = clean($_POST['status_validasi']);
    $komentar        = clean($_POST['komentar_pembimbing']);

    if ($id == 0) {
        $sql = "INSERT INTO jurnal_harian 
                    (siswa_id, tanggal, jam_masuk, jam_keluar, kegiatan, status_validasi, komentar_pembimbing) 
                VALUES 
                    ($siswa_id, '$tanggal', '$jam_masuk', '$jam_keluar', '$kegiatan', '$status_validasi', '$komentar')";
        $msg = "Menambah data jurnal/bimbingan siswa id: $siswa_id";
    } else {
        $sql = "UPDATE jurnal_harian SET 
                    siswa_id=$siswa_id,
                    tanggal='$tanggal',
                    jam_masuk='$jam_masuk',
                    jam_keluar='$jam_keluar',
                    kegiatan='$kegiatan',
                    status_validasi='$status_validasi',
                    komentar_pembimbing='$komentar'
                WHERE id=$id";
        $msg = "Mengubah data jurnal/bimbingan id: $id";
    }

    if (mysqli_query($conn, $sql)) {
        catatLog($conn, $msg);
        setFlash('success', 'Data berhasil disimpan.');
    } else {
        setFlash('error', 'Gagal menyimpan: ' . mysqli_error($conn));
    }
    redirect('pkl-bimbingan.php');
}

// Ambil data jurnal dengan nama siswa (join users + profil_siswa)
$rows = mysqli_query($conn, "
    SELECT jh.*, 
           CONCAT(u.nama_depan, ' ', u.nama_belakang) AS nama_siswa,
           ps.nis, ps.kelas
    FROM jurnal_harian jh
    LEFT JOIN users u ON jh.siswa_id = u.id
    LEFT JOIN profil_siswa ps ON jh.siswa_id = ps.user_id
    ORDER BY jh.id DESC
");

// Dropdown siswa (role = siswa)
$siswa_opt = mysqli_query($conn, "
    SELECT u.id, CONCAT(u.nama_depan, ' ', u.nama_belakang) AS nama_siswa, ps.nis, ps.kelas
    FROM users u
    LEFT JOIN profil_siswa ps ON u.id = ps.user_id
    WHERE u.role = 'siswa'
    ORDER BY u.nama_depan
");

$active_page = 'bimbingan';
$page_title  = 'Data Bimbingan / Jurnal';
include '_header_admin.php';
?>

<div class="page-header">
  <h2><i class="fas fa-comments"></i> Data Bimbingan / Jurnal Harian</h2>
  <button class="btn btn-primary btn-sm" onclick="openModal()">
    <i class="fas fa-plus"></i> Tambah
  </button>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Siswa</th>
          <th>Tanggal</th>
          <th>Jam Masuk</th>
          <th>Jam Keluar</th>
          <th>Kegiatan</th>
          <th>Status</th>
          <th>Komentar Pembimbing</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php 
      if (mysqli_num_rows($rows) === 0): ?>
        <tr><td colspan="9" style="text-align:center;color:#94a3b8;">Belum ada data jurnal.</td></tr>
      <?php else:
      $no = 1; while ($row = mysqli_fetch_assoc($rows)): ?>
      <tr>
        <td><?php echo $no++; ?></td>
        <td>
          <?php echo htmlspecialchars($row['nama_siswa'] ?? '-'); ?>
          <?php if (!empty($row['nis'])): ?>
            <br><small class="text-muted"><?php echo htmlspecialchars($row['nis']); ?> - <?php echo htmlspecialchars($row['kelas'] ?? ''); ?></small>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($row['tanggal'] ?? '-'); ?></td>
        <td><?php echo htmlspecialchars($row['jam_masuk'] ?? '-'); ?></td>
        <td><?php echo htmlspecialchars($row['jam_keluar'] ?? '-'); ?></td>
        <td><?php echo htmlspecialchars($row['kegiatan'] ?? '-'); ?></td>
        <td>
          <?php
            $status = $row['status_validasi'] ?? 'pending';
            $badge_class = match($status) {
                'valid'  => 'badge-success',
                'tolak'  => 'badge-danger',
                default  => 'badge-warning',
            };
          ?>
          <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span>
        </td>
        <td><?php echo htmlspecialchars($row['komentar_pembimbing'] ?? '-'); ?></td>
        <td style="display:flex;gap:6px;">
          <button class="btn btn-warning btn-sm"
            onclick="editData(
              <?php echo $row['id']; ?>,
              <?php echo $row['siswa_id']; ?>,
              '<?php echo addslashes($row['tanggal'] ?? ''); ?>',
              '<?php echo addslashes(substr($row['jam_masuk'] ?? '', 0, 5)); ?>',
              '<?php echo addslashes(substr($row['jam_keluar'] ?? '', 0, 5)); ?>',
              '<?php echo addslashes($row['kegiatan'] ?? ''); ?>',
              '<?php echo addslashes($row['status_validasi'] ?? 'pending'); ?>',
              '<?php echo addslashes($row['komentar_pembimbing'] ?? ''); ?>'
            )">
            <i class="fas fa-edit"></i>
          </button>
          <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Hapus data ini?')">
            <i class="fas fa-trash"></i>
          </a>
        </td>
      </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL TAMBAH / EDIT -->
<div class="modal-overlay" id="modal_bimbingan">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-comments" style="margin-right:8px;color:#94a3b8;"></i>
        <span id="modal_title">Tambah Data Jurnal</span>
      </h3>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="id" id="field_id" value="0"/>

      <div class="form-group">
        <label>Siswa</label>
        <select name="siswa_id" id="field_siswa_id" class="form-control" required>
          <option value="">-- Pilih Siswa --</option>
          <?php
          mysqli_data_seek($siswa_opt, 0);
          while ($rs = mysqli_fetch_assoc($siswa_opt)):
          ?>
          <option value="<?php echo $rs['id']; ?>">
            <?php echo htmlspecialchars($rs['nama_siswa']); ?>
            <?php if (!empty($rs['nis'])) echo ' (' . htmlspecialchars($rs['nis']) . ')'; ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Tanggal</label>
        <input type="date" name="tanggal" id="field_tanggal" class="form-control" required/>
      </div>

      <div class="form-group" style="display:flex;gap:12px;">
        <div style="flex:1">
          <label>Jam Masuk</label>
          <input type="time" name="jam_masuk" id="field_jam_masuk" class="form-control"/>
        </div>
        <div style="flex:1">
          <label>Jam Keluar</label>
          <input type="time" name="jam_keluar" id="field_jam_keluar" class="form-control"/>
        </div>
      </div>

      <div class="form-group">
        <label>Kegiatan</label>
        <textarea name="kegiatan" id="field_kegiatan" class="form-control" rows="3"
                  placeholder="Deskripsi kegiatan..."></textarea>
      </div>

      <div class="form-group">
        <label>Status Validasi</label>
        <select name="status_validasi" id="field_status" class="form-control">
          <option value="pending">Pending</option>
          <option value="valid">Valid</option>
          <option value="tolak">Tolak</option>
        </select>
      </div>

      <div class="form-group">
        <label>Komentar Pembimbing</label>
        <textarea name="komentar_pembimbing" id="field_komentar" class="form-control" rows="2"
                  placeholder="Komentar / catatan pembimbing..."></textarea>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal()">Batal</button>
        <button type="submit" name="simpan" class="btn btn-primary btn-sm">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal() {
  document.getElementById('modal_title').textContent = 'Tambah Data Jurnal';
  document.getElementById('field_id').value = '0';
  document.getElementById('field_siswa_id').value = '';
  document.getElementById('field_tanggal').value = '';
  document.getElementById('field_jam_masuk').value = '';
  document.getElementById('field_jam_keluar').value = '';
  document.getElementById('field_kegiatan').value = '';
  document.getElementById('field_status').value = 'pending';
  document.getElementById('field_komentar').value = '';
  document.getElementById('modal_bimbingan').classList.add('active');
}

function editData(id, siswa_id, tanggal, jam_masuk, jam_keluar, kegiatan, status, komentar) {
  document.getElementById('modal_title').textContent = 'Edit Data Jurnal';
  document.getElementById('field_id').value = id;
  document.getElementById('field_siswa_id').value = siswa_id;
  document.getElementById('field_tanggal').value = tanggal;
  document.getElementById('field_jam_masuk').value = jam_masuk;
  document.getElementById('field_jam_keluar').value = jam_keluar;
  document.getElementById('field_kegiatan').value = kegiatan;
  document.getElementById('field_status').value = status;
  document.getElementById('field_komentar').value = komentar;
  document.getElementById('modal_bimbingan').classList.add('active');
}

function closeModal() {
  document.getElementById('modal_bimbingan').classList.remove('active');
}
</script>

<?php include '_footer_admin.php'; ?>