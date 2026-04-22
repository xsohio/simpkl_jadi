<?php
session_start();
include "../config.php";
requireAdmin();
$active_page = 'users';
$page_title  = 'Manajemen User';

// ============================================================
// Kolom tabel users di webpkl:
// id, nama_depan, nama_belakang, email, password, role, created_at
// TIDAK ADA: username, is_deleted, nis_nik, id_users
// ============================================================

$search = isset($_GET['s'])    ? mysqli_real_escape_string($conn, trim($_GET['s']))    : '';
$filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, trim($_GET['role'])) : '';

$where = "WHERE 1=1";
if ($search) $where .= " AND (nama_depan LIKE '%$search%' OR nama_belakang LIKE '%$search%' OR email LIKE '%$search%')";
if ($filter) $where .= " AND role = '$filter'";

$rows = mysqli_query($conn, "SELECT * FROM users $where ORDER BY id DESC");

// Tangkap error query
if (!$rows) {
    die("Query error: " . mysqli_error($conn));
}
  

include '_header_admin.php';
?>

<div class="page-header">
  <h2><i class="fas fa-users-cog"></i> Manajemen User</h2>
  <button class="btn btn-primary btn-sm" onclick="openModal()">
    <i class="fas fa-user-plus"></i> Tambah User
  </button>
</div>

<?php getFlash(); ?>

<!-- Filter & Search -->
<div class="card" style="margin-bottom:16px;">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input type="text" name="s" placeholder="Cari nama / email..."
             value="<?php echo htmlspecialchars($search); ?>"/>
    </div>
    <select name="role" class="form-control" style="width:auto;padding:9px 14px;">
      <option value="">Semua Role</option>
      <option value="admin"      <?php if($filter==='admin')      echo 'selected';?>>Admin</option>
      <option value="siswa"      <?php if($filter==='siswa')      echo 'selected';?>>Siswa</option>
      <option value="pembimbing" <?php if($filter==='pembimbing') echo 'selected';?>>Pembimbing</option>
      <option value="wakasek"    <?php if($filter==='wakasek')    echo 'selected';?>>Wakasek</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filter</button>
    <a href="admin-users.php" class="btn btn-sm" style="background:rgba(255,255,255,.06);color:#64748b;">Reset</a>
  </form>
</div>

<!-- Tabel -->
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nama</th>
          <th>Email</th>
          <th>Role</th>
          <th>Terdaftar</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $no = 1;
      if (mysqli_num_rows($rows) === 0): ?>
        <tr><td colspan="6" style="text-align:center;padding:20px;color:#64748b;">Tidak ada data user.</td></tr>
      <?php else:
      while ($r = mysqli_fetch_assoc($rows)): ?>
      <tr>
        <td><?php echo $no++; ?></td>
        <td>
          <i class="fas fa-user" style="color:#94a3b8;margin-right:8px;"></i>
          <?php echo htmlspecialchars($r['nama_depan'] . ' ' . $r['nama_belakang']); ?>
        </td>
        <td><?php echo htmlspecialchars($r['email']); ?></td>
        <td><?php
          $map = ['admin'=>'pill-white','siswa'=>'pill-blue','pembimbing'=>'pill-yellow','wakasek'=>'pill-purple'];
          $cls = $map[$r['role']] ?? 'pill-white';
          echo "<span class='pill {$cls}'>".ucfirst($r['role'])."</span>";
        ?></td>
        <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
        <td style="display:flex;gap:6px;">
          <button class="btn btn-warning btn-sm"
            onclick="editUser(
              <?php echo $r['id']; ?>,
              '<?php echo htmlspecialchars(addslashes($r['nama_depan'])); ?>',
              '<?php echo htmlspecialchars(addslashes($r['nama_belakang'])); ?>',
              '<?php echo htmlspecialchars(addslashes($r['email'])); ?>',
              '<?php echo $r['role']; ?>'
            )">
            <i class="fas fa-edit"></i> Edit
          </button>
          <?php if ($r['id'] != $_SESSION['user']['id_user']): ?>
          <a href="user_aksi.php?hapus=<?php echo $r['id']; ?>"
             class="btn btn-danger btn-sm"
             onclick="return confirm('Yakin hapus user ini? Tindakan tidak bisa dibatalkan.')">
            <i class="fas fa-trash"></i>
          </a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah / Edit -->
<div class="modal-overlay" id="modalUser">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <h3 id="modalTitle"><i class="fas fa-user-plus" style="margin-right:8px;color:#94a3b8;"></i> Tambah User</h3>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <form action="user_aksi.php" method="POST">
      <input type="hidden" name="id" id="u_id" value="0"/>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label>Nama Depan</label>
          <input type="text" name="nama_depan" id="u_nama_depan" class="form-control" required placeholder="Budi"/>
        </div>
        <div class="form-group">
          <label>Nama Belakang</label>
          <input type="text" name="nama_belakang" id="u_nama_belakang" class="form-control" placeholder="Santoso"/>
        </div>
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" id="u_email" class="form-control" required placeholder="email@contoh.com"/>
      </div>

      <div class="form-group">
        <label>Role</label>
        <select name="role" id="u_role" class="form-control">
          <option value="siswa">Siswa</option>
          <option value="pembimbing">Pembimbing</option>
          <option value="wakasek">Wakasek</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <div class="form-group">
        <label>Password <span style="color:#64748b;font-size:.78rem;">(Kosongkan jika tidak ingin ganti)</span></label>
        <input type="password" name="password" id="u_password" class="form-control" placeholder="••••••••"/>
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
  // Reset form untuk mode tambah
  document.getElementById('u_id').value = '0';
  document.getElementById('u_nama_depan').value = '';
  document.getElementById('u_nama_belakang').value = '';
  document.getElementById('u_email').value = '';
  document.getElementById('u_role').value = 'siswa';
  document.getElementById('u_password').value = '';
  document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus" style="margin-right:8px;color:#94a3b8;"></i> Tambah User';
  document.getElementById('modalUser').classList.add('active');
}

function closeModal() {
  document.getElementById('modalUser').classList.remove('active');
}

function editUser(id, nama_depan, nama_belakang, email, role) {
  document.getElementById('u_id').value           = id;
  document.getElementById('u_nama_depan').value   = nama_depan;
  document.getElementById('u_nama_belakang').value = nama_belakang;
  document.getElementById('u_email').value        = email;
  document.getElementById('u_role').value         = role;
  document.getElementById('u_password').value     = '';
  document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit" style="margin-right:8px;color:#94a3b8;"></i> Edit User';
  document.getElementById('modalUser').classList.add('active');
}
</script>

<?php include '_footer_admin.php'; ?>