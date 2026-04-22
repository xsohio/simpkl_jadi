<?php
session_start();
include "../config.php";
requireAdmin();

$active_page = 'kopetensi';
$page_title  = 'Data Laporan PKL';

// --- PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Ambil file_path sebelum hapus agar bisa hapus file fisik jika ada
    $res_del = mysqli_query($conn, "SELECT file_path, judul_laporan FROM laporan_pkl WHERE id=$id");
    $row_del = mysqli_fetch_assoc($res_del);
    $judul   = $row_del['judul_laporan'] ?? 'Unknown';

    if (mysqli_query($conn, "DELETE FROM laporan_pkl WHERE id=$id")) {
        catatLog($conn, "Menghapus laporan PKL: $judul (id=$id)");
        setFlash('success', 'Data berhasil dihapus.');
    }
    redirect('pkl-kopetensi.php');
}

// --- PROSES SIMPAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id                = (int)($_POST['id'] ?? 0);
    $siswa_id          = (int)$_POST['siswa_id'];
    $judul_laporan     = clean($_POST['judul_laporan']);
    $jenis_laporan     = clean($_POST['jenis_laporan']);
    $status_pembimbing = clean($_POST['status_pembimbing']);
    $status_wakasek    = clean($_POST['status_wakasek']);
    $catatan_revisi    = clean($_POST['catatan_revisi']);
    $tampil            = isset($_POST['tampil_di_publik']) ? 1 : 0;

    if ($id == 0) {
        // file_path kosong dulu jika tidak ada upload (bisa dikembangkan)
        $file_path = '';
        $sql = "INSERT INTO laporan_pkl 
                    (siswa_id, judul_laporan, file_path, jenis_laporan, status_pembimbing, status_wakasek, catatan_revisi, tampil_di_publik)
                VALUES
                    ($siswa_id, '$judul_laporan', '$file_path', '$jenis_laporan', '$status_pembimbing', '$status_wakasek', '$catatan_revisi', $tampil)";
        $msg = "Menambah laporan PKL: $judul_laporan";
    } else {
        $sql = "UPDATE laporan_pkl SET
                    siswa_id=$siswa_id,
                    judul_laporan='$judul_laporan',
                    jenis_laporan='$jenis_laporan',
                    status_pembimbing='$status_pembimbing',
                    status_wakasek='$status_wakasek',
                    catatan_revisi='$catatan_revisi',
                    tampil_di_publik=$tampil
                WHERE id=$id";
        $msg = "Mengubah laporan PKL: $judul_laporan (id=$id)";
    }

    if (mysqli_query($conn, $sql)) {
        catatLog($conn, $msg);
        setFlash('success', 'Data berhasil disimpan.');
    } else {
        setFlash('error', 'Gagal menyimpan: ' . mysqli_error($conn));
    }
    redirect('pkl-kopetensi.php');
}

// Ambil data laporan + nama siswa
$rows = mysqli_query($conn, "
    SELECT lp.*,
           CONCAT(u.nama_depan, ' ', u.nama_belakang) AS nama_siswa,
           ps.nis, ps.kelas
    FROM laporan_pkl lp
    LEFT JOIN users u ON lp.siswa_id = u.id
    LEFT JOIN profil_siswa ps ON lp.siswa_id = ps.user_id
    ORDER BY lp.id DESC
");

// Dropdown siswa
$siswa_opt = mysqli_query($conn, "
    SELECT u.id, CONCAT(u.nama_depan, ' ', u.nama_belakang) AS nama_siswa, ps.nis, ps.kelas
    FROM users u
    LEFT JOIN profil_siswa ps ON u.id = ps.user_id
    WHERE u.role = 'siswa'
    ORDER BY u.nama_depan
");

include '_header_admin.php';
?>

<div class="page-header">
  <h2><i class="fas fa-file-alt"></i> Data Laporan PKL</h2>
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
          <th>Judul Laporan</th>
          <th>Jenis</th>
          <th>Status Pembimbing</th>
          <th>Status Wakasek</th>
          <th>Catatan Revisi</th>
          <th>Publik</th>
          <th>Dibuat</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php $no = 1; while ($row = mysqli_fetch_assoc($rows)): ?>
      <tr>
        <td><?php echo $no++; ?></td>
        <td>
          <?php echo htmlspecialchars($row['nama_siswa'] ?? '-'); ?>
          <?php if (!empty($row['nis'])): ?>
            <br><small class="text-muted"><?php echo htmlspecialchars($row['nis']); ?></small>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($row['judul_laporan'] ?? '-'); ?></td>
        <td>
          <span class="badge badge-info"><?php echo ucfirst($row['jenis_laporan'] ?? '-'); ?></span>
        </td>
        <td>
          <?php
            $sp = $row['status_pembimbing'] ?? 'pending';
            $sp_class = match($sp) { 'disetujui' => 'badge-success', 'revisi' => 'badge-warning', default => 'badge-secondary' };
          ?>
          <span class="badge <?php echo $sp_class; ?>"><?php echo ucfirst($sp); ?></span>
        </td>
        <td>
          <?php
            $sw = $row['status_wakasek'] ?? 'pending';
            $sw_class = ($sw === 'disetujui') ? 'badge-success' : 'badge-secondary';
          ?>
          <span class="badge <?php echo $sw_class; ?>"><?php echo ucfirst($sw); ?></span>
        </td>
        <td><?php echo htmlspecialchars($row['catatan_revisi'] ?? '-'); ?></td>
        <td><?php echo $row['tampil_di_publik'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($row['created_at'] ?? 'now'))); ?></td>
        <td style="display:flex;gap:6px;">
          <?php if (!empty($row['file_path'])): ?>
          <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank"
             class="btn btn-info btn-sm" title="Lihat File">
            <i class="fas fa-eye"></i>
          </a>
          <?php endif; ?>
          <button class="btn btn-warning btn-sm" title="Edit"
            onclick="editData(
              <?php echo $row['id']; ?>,
              <?php echo $row['siswa_id']; ?>,
              '<?php echo addslashes($row['judul_laporan'] ?? ''); ?>',
              '<?php echo addslashes($row['jenis_laporan'] ?? 'mingguan'); ?>',
              '<?php echo addslashes($row['status_pembimbing'] ?? 'pending'); ?>',
              '<?php echo addslashes($row['status_wakasek'] ?? 'pending'); ?>',
              '<?php echo addslashes($row['catatan_revisi'] ?? ''); ?>',
              <?php echo (int)$row['tampil_di_publik']; ?>
            )">
            <i class="fas fa-edit"></i>
          </button>
          <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Hapus laporan ini?')">
            <i class="fas fa-trash"></i>
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL TAMBAH / EDIT -->
<div class="modal-overlay" id="modal_kopetensi">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-file-alt" style="margin-right:8px;color:#94a3b8;"></i>
        <span id="modal_title">Tambah Laporan PKL</span>
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
        <label>Judul Laporan</label>
        <input type="text" name="judul_laporan" id="field_judul" class="form-control"
               placeholder="Judul laporan PKL" required/>
      </div>

      <div class="form-group">
        <label>Jenis Laporan</label>
        <select name="jenis_laporan" id="field_jenis" class="form-control">
          <option value="mingguan">Mingguan</option>
          <option value="akhir">Akhir</option>
        </select>
      </div>

      <div class="form-group" style="display:flex;gap:12px;">
        <div style="flex:1">
          <label>Status Pembimbing</label>
          <select name="status_pembimbing" id="field_status_pmb" class="form-control">
            <option value="pending">Pending</option>
            <option value="revisi">Revisi</option>
            <option value="disetujui">Disetujui</option>
          </select>
        </div>
        <div style="flex:1">
          <label>Status Wakasek</label>
          <select name="status_wakasek" id="field_status_wks" class="form-control">
            <option value="pending">Pending</option>
            <option value="disetujui">Disetujui</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Catatan Revisi</label>
        <textarea name="catatan_revisi" id="field_catatan" class="form-control" rows="2"
                  placeholder="Catatan revisi jika ada..."></textarea>
      </div>

      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" name="tampil_di_publik" id="field_publik" value="1"/>
          Tampilkan di halaman publik
        </label>
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
  document.getElementById('modal_title').textContent = 'Tambah Laporan PKL';
  document.getElementById('field_id').value = '0';
  document.getElementById('field_siswa_id').value = '';
  document.getElementById('field_judul').value = '';
  document.getElementById('field_jenis').value = 'mingguan';
  document.getElementById('field_status_pmb').value = 'pending';
  document.getElementById('field_status_wks').value = 'pending';
  document.getElementById('field_catatan').value = '';
  document.getElementById('field_publik').checked = false;
  document.getElementById('modal_kopetensi').classList.add('active');
}

function editData(id, siswa_id, judul, jenis, status_pmb, status_wks, catatan, publik) {
  document.getElementById('modal_title').textContent = 'Edit Laporan PKL';
  document.getElementById('field_id').value = id;
  document.getElementById('field_siswa_id').value = siswa_id;
  document.getElementById('field_judul').value = judul;
  document.getElementById('field_jenis').value = jenis;
  document.getElementById('field_status_pmb').value = status_pmb;
  document.getElementById('field_status_wks').value = status_wks;
  document.getElementById('field_catatan').value = catatan;
  document.getElementById('field_publik').checked = publik == 1;
  document.getElementById('modal_kopetensi').classList.add('active');
}

function closeModal() {
  document.getElementById('modal_kopetensi').classList.remove('active');
}
</script>

<?php include '_footer_admin.php'; ?>