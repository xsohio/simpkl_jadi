<?php
session_start();
include "../config.php";
requireAdmin();
$active_page = 'walikelas';
$page_title  = 'Pembimbing';

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM users WHERE id=$id AND role='pembimbing'");
    setFlash('success','Data berhasil dihapus.'); redirect('admin-walikelas.php');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['simpan'])) {
    $id           = (int)($_POST['id'] ?? 0);
    $nama_depan   = clean($_POST['nama_depan']   ?? '');
    $nama_belakang= clean($_POST['nama_belakang'] ?? '');
    $email        = clean($_POST['email']         ?? '');
    $password     = $_POST['password'] ?? '';

    if ($id) {
        $sql = "UPDATE users SET nama_depan='$nama_depan', nama_belakang='$nama_belakang', email='$email'";
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password='$hashed'";
        }
        $sql .= " WHERE id=$id AND role='pembimbing'";
        mysqli_query($conn, $sql);
        setFlash('success','Data berhasil diperbarui.');
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (nama_depan, nama_belakang, email, password, role)
                             VALUES ('$nama_depan','$nama_belakang','$email','$hashed','pembimbing')");
        setFlash('success','Data baru berhasil ditambahkan.');
    }
    redirect('admin-walikelas.php');
}

$rows = mysqli_query($conn, "SELECT * FROM users WHERE role='pembimbing' ORDER BY id DESC");
if (!$rows) die("Query error: " . mysqli_error($conn));

include '_header_admin.php';
?>
<div class="page-header">
  <h2><i class="fas fa-chalkboard-teacher"></i> Pembimbing</h2>
  <button class="btn btn-primary btn-sm" onclick="openModal()">
    <i class="fas fa-plus"></i> Tambah
  </button>
</div>

<?php getFlash(); ?>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nama Pembimbing</th>
          <th>Email</th>
          <th>Terdaftar</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php $no = 1; if (mysqli_num_rows($rows) === 0): ?>
        <tr><td colspan="5" style="text-align:center;padding:20px;color:#64748b;">Tidak ada data pembimbing.</td></tr>
      <?php else: while ($row = mysqli_fetch_assoc($rows)): ?>
      <tr>
        <td><?php echo $no++; ?></td>
        <td>
          <i class="fas fa-user" style="color:#94a3b8;margin-right:8px;"></i>
          <?php echo htmlspecialchars($row['nama_depan'] . ' ' . $row['nama_belakang']); ?>
        </td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
        <td style="display:flex;gap:6px;">
          <button class="btn btn-warning btn-sm"
            onclick="editModal(
              <?php echo $row['id']; ?>,
              '<?php echo htmlspecialchars(addslashes($row['nama_depan'])); ?>',
              '<?php echo htmlspecialchars(addslashes($row['nama_belakang'])); ?>',
              '<?php echo htmlspecialchars(addslashes($row['email'])); ?>'
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

<!-- Modal Tambah / Edit -->
<div class="modal-overlay" id="modal_pembimbing">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modalTitle"><i class="fas fa-chalkboard-teacher" style="margin-right:8px;color:#94a3b8;"></i> Tambah Pembimbing</h3>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="id" id="p_id" value="0"/>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label>Nama Depan</label>
          <input type="text" name="nama_depan" id="p_nama_depan" class="form-control" required placeholder="Budi"/>
        </div>
        <div class="form-group">
          <label>Nama Belakang</label>
          <input type="text" name="nama_belakang" id="p_nama_belakang" class="form-control" placeholder="Santoso"/>
        </div>
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" id="p_email" class="form-control" required placeholder="email@contoh.com"/>
      </div>

      <div class="form-group">
        <label>Password <span style="color:#64748b;font-size:.78rem;">(Kosongkan jika tidak ingin ganti)</span></label>
        <input type="password" name="password" id="p_password" class="form-control" placeholder="••••••••"/>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal()">Batal</button>
        <button type="submit" name="simpan" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal() {
  document.getElementById('p_id').value           = '0';
  document.getElementById('p_nama_depan').value   = '';
  document.getElementById('p_nama_belakang').value = '';
  document.getElementById('p_email').value        = '';
  document.getElementById('p_password').value     = '';
  document.getElementById('modalTitle').innerHTML = '<i class="fas fa-chalkboard-teacher" style="margin-right:8px;color:#94a3b8;"></i> Tambah Pembimbing';
  document.getElementById('modal_pembimbing').classList.add('active');
}

function closeModal() {
  document.getElementById('modal_pembimbing').classList.remove('active');
}

function editModal(id, nama_depan, nama_belakang, email) {
  document.getElementById('p_id').value            = id;
  document.getElementById('p_nama_depan').value    = nama_depan;
  document.getElementById('p_nama_belakang').value = nama_belakang;
  document.getElementById('p_email').value         = email;
  document.getElementById('p_password').value      = '';
  document.getElementById('modalTitle').innerHTML  = '<i class="fas fa-edit" style="margin-right:8px;color:#94a3b8;"></i> Edit Pembimbing';
  document.getElementById('modal_pembimbing').classList.add('active');
}
</script>

<?php include '_footer_admin.php'; ?>