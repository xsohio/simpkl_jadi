<?php
session_start();
include "../config.php";
requireAdmin();

// --- PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $res  = mysqli_query($conn, "SELECT nama_perusahaan FROM mitra_industri WHERE id=$id");
    $data = mysqli_fetch_assoc($res);
    $nama = $data['nama_perusahaan'] ?? 'Unknown';

    if (mysqli_query($conn, "DELETE FROM mitra_industri WHERE id=$id")) {
        catatLog($conn, "Menghapus mitra industri: $nama");
        setFlash('success', 'Data mitra berhasil dihapus.');
    } else {
        setFlash('error', 'Gagal menghapus: ' . mysqli_error($conn));
    }
    redirect('admin-perusahaan.php');
}

// --- PROSES SIMPAN (TAMBAH & EDIT) ---
if (isset($_POST['simpan'])) {
    $id      = (int)$_POST['id'];
    $nama    = clean($_POST['nama_perusahaan']);
    $alamat  = clean($_POST['alamat_perusahaan']);
    $website = clean($_POST['website']);

    if ($id == 0) {
        $sql  = "INSERT INTO mitra_industri (nama_perusahaan, alamat_perusahaan, website) 
                 VALUES ('$nama', '$alamat', '$website')";
        $aksi = "Menambah mitra industri: $nama";
    } else {
        $sql  = "UPDATE mitra_industri SET 
                    nama_perusahaan='$nama',
                    alamat_perusahaan='$alamat',
                    website='$website'
                 WHERE id=$id";
        $aksi = "Mengubah data mitra industri: $nama";
    }

    if (mysqli_query($conn, $sql)) {
        catatLog($conn, $aksi);
        setFlash('success', 'Data berhasil disimpan.');
    } else {
        setFlash('error', 'Gagal menyimpan: ' . mysqli_error($conn));
    }
    redirect('admin-perusahaan.php');
}

$rows = mysqli_query($conn, "SELECT * FROM mitra_industri ORDER BY id DESC");

$active_page = 'mitra';
$page_title  = 'Data Mitra Industri';
include '_header_admin.php';
?>

<div class="page-header">
  <h2><i class="fas fa-building"></i> Data Mitra Industri</h2>
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
          <th>Nama Perusahaan</th>
          <th>Alamat</th>
          <th>Website</th>
          <th>Ditambahkan</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php $no = 1; while ($row = mysqli_fetch_assoc($rows)): ?>
      <tr>
        <td><?php echo $no++; ?></td>
        <td><?php echo htmlspecialchars($row['nama_perusahaan'] ?? '-'); ?></td>
        <td><?php echo htmlspecialchars($row['alamat_perusahaan'] ?? '-'); ?></td>
        <td>
          <?php if (!empty($row['website'])): ?>
            <a href="<?php echo htmlspecialchars($row['website']); ?>" target="_blank">
              <?php echo htmlspecialchars($row['website']); ?>
            </a>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($row['created_at'] ?? 'now'))); ?></td>
        <td style="display:flex;gap:6px;">
          <button class="btn btn-warning btn-sm"
            onclick="editData(
              <?php echo $row['id']; ?>,
              '<?php echo addslashes($row['nama_perusahaan'] ?? ''); ?>',
              '<?php echo addslashes($row['alamat_perusahaan'] ?? ''); ?>',
              '<?php echo addslashes($row['website'] ?? ''); ?>'
            )">
            <i class="fas fa-edit"></i>
          </button>
          <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Hapus mitra ini?')">
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
<div class="modal-overlay" id="modal_perusahaan">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-building" style="margin-right:8px;color:#94a3b8;"></i>
        <span id="modal_title">Tambah Mitra Industri</span>
      </h3>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="id" id="field_id" value="0"/>

      <div class="form-group">
        <label>Nama Perusahaan</label>
        <input type="text" name="nama_perusahaan" id="field_nama" class="form-control"
               placeholder="Nama perusahaan mitra" required/>
      </div>

      <div class="form-group">
        <label>Alamat</label>
        <textarea name="alamat_perusahaan" id="field_alamat" class="form-control" rows="2"
                  placeholder="Alamat lengkap perusahaan"></textarea>
      </div>

      <div class="form-group">
        <label>Website <small class="text-muted">(opsional)</small></label>
        <input type="text" name="website" id="field_website" class="form-control"
               placeholder="https://example.com"/>
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
  document.getElementById('modal_title').textContent = 'Tambah Mitra Industri';
  document.getElementById('field_id').value = '0';
  document.getElementById('field_nama').value = '';
  document.getElementById('field_alamat').value = '';
  document.getElementById('field_website').value = '';
  document.getElementById('modal_perusahaan').classList.add('active');
}

function editData(id, nama, alamat, website) {
  document.getElementById('modal_title').textContent = 'Edit Mitra Industri';
  document.getElementById('field_id').value = id;
  document.getElementById('field_nama').value = nama;
  document.getElementById('field_alamat').value = alamat;
  document.getElementById('field_website').value = website;
  document.getElementById('modal_perusahaan').classList.add('active');
}

function closeModal() {
  document.getElementById('modal_perusahaan').classList.remove('active');
}
</script>

<?php include '_footer_admin.php'; ?>