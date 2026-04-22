<?php
session_start();
include '../config.php';
requireAdmin();
$active_page = 'siswa';
$page_title  = 'Data Siswa';

// ============================================================
// Database webpkl TIDAK punya tabel: siswa, kelas, perusahaan,
// pembimbing_pkl, walikelas.
//
// Yang ada:
//   users         → id, nama_depan, nama_belakang, email, password, role, created_at
//   profil_siswa  → id, user_id, nis, kelas, jurusan, no_hp, foto_profil,
//                   jenis_kelamin, agama, tempat_lahir, tanggal_lahir, alamat, golongan_darah
//   mitra_industri → id, nama_perusahaan, alamat_perusahaan, website, logo, created_at
//   pkl_pengajuan  → digunakan untuk relasi siswa ↔ perusahaan ↔ pembimbing
// ============================================================

// --- HAPUS SISWA ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus']; // ini adalah users.id

    $res  = mysqli_query($conn, "SELECT nama_depan, nama_belakang FROM users WHERE id = $id AND role = 'siswa'");
    $data = mysqli_fetch_assoc($res);
    $nama = $data ? ($data['nama_depan'] . ' ' . $data['nama_belakang']) : 'Unknown';

    // profil_siswa akan terhapus otomatis via ON DELETE CASCADE
    if (mysqli_query($conn, "DELETE FROM users WHERE id = $id AND role = 'siswa'")) {
        catatLog($conn, "Menghapus siswa: $nama");
        setFlash('success', "Data siswa '$nama' berhasil dihapus.");
    } else {
        setFlash('error', 'Gagal menghapus: ' . mysqli_error($conn));
    }
    redirect('admin-siswa.php');
}

// --- SIMPAN (TAMBAH / EDIT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_siswa'])) {
    $user_id      = (int)($_POST['user_id'] ?? 0);   // users.id
    $profil_id    = (int)($_POST['profil_id'] ?? 0); // profil_siswa.id
    $nama_depan   = mysqli_real_escape_string($conn, trim($_POST['nama_depan']));
    $nama_belakang = mysqli_real_escape_string($conn, trim($_POST['nama_belakang']));
    $email        = mysqli_real_escape_string($conn, trim($_POST['email']));
    $nis          = mysqli_real_escape_string($conn, trim($_POST['nis']));
    $kelas        = mysqli_real_escape_string($conn, trim($_POST['kelas']));
    $jurusan      = mysqli_real_escape_string($conn, trim($_POST['jurusan']));
    $jk           = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $agama        = mysqli_real_escape_string($conn, $_POST['agama']);
    $tempat_lahir = mysqli_real_escape_string($conn, trim($_POST['tempat_lahir']));
    $tgl_lahir    = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $no_hp        = mysqli_real_escape_string($conn, trim($_POST['no_hp']));
    $alamat       = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $goldar       = mysqli_real_escape_string($conn, $_POST['golongan_darah']);
    $password     = $_POST['password'] ?? '';

    if ($user_id == 0) {
        // --- TAMBAH ---
        // Cek email unik
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($cek) > 0) {
            setFlash('error', 'Email sudah digunakan!');
            redirect('admin-siswa.php');
        }
        // Cek NIS unik
        $cek_nis = mysqli_query($conn, "SELECT id FROM profil_siswa WHERE nis = '$nis'");
        if (mysqli_num_rows($cek_nis) > 0) {
            setFlash('error', 'NIS sudah terdaftar!');
            redirect('admin-siswa.php');
        }

        $pass_default = !empty($password) ? $password : 'siswa123';
        $hashed = mysqli_real_escape_string($conn, password_hash($pass_default, PASSWORD_BCRYPT));

        // Insert ke tabel users
        mysqli_query($conn, "INSERT INTO users (nama_depan, nama_belakang, email, password, role)
                              VALUES ('$nama_depan', '$nama_belakang', '$email', '$hashed', 'siswa')");
        $new_user_id = mysqli_insert_id($conn);

        // Insert ke profil_siswa
        mysqli_query($conn, "INSERT INTO profil_siswa
                              (user_id, nis, kelas, jurusan, jenis_kelamin, agama,
                               tempat_lahir, tanggal_lahir, no_hp, alamat, golongan_darah)
                              VALUES
                              ($new_user_id, '$nis', '$kelas', '$jurusan', '$jk', '$agama',
                               '$tempat_lahir', '$tgl_lahir', '$no_hp', '$alamat', '$goldar')");

        catatLog($conn, "Menambah siswa baru: $nama_depan $nama_belakang");
        setFlash('success', "Siswa '$nama_depan $nama_belakang' berhasil ditambahkan.");

    } else {
        // --- EDIT ---
        // Cek email tidak bentrok dengan user lain
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
        if (mysqli_num_rows($cek) > 0) {
            setFlash('error', 'Email sudah digunakan user lain!');
            redirect('admin-siswa.php');
        }

        // Update users
        $q_user = "UPDATE users SET nama_depan='$nama_depan', nama_belakang='$nama_belakang', email='$email'";
        if (!empty($password)) {
            $hashed = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_BCRYPT));
            $q_user .= ", password='$hashed'";
        }
        $q_user .= " WHERE id = $user_id";
        mysqli_query($conn, $q_user);

        // Update profil_siswa
        mysqli_query($conn, "UPDATE profil_siswa SET
                              nis='$nis', kelas='$kelas', jurusan='$jurusan',
                              jenis_kelamin='$jk', agama='$agama',
                              tempat_lahir='$tempat_lahir', tanggal_lahir='$tgl_lahir',
                              no_hp='$no_hp', alamat='$alamat', golongan_darah='$goldar'
                              WHERE user_id = $user_id");

        catatLog($conn, "Mengubah data siswa: $nama_depan $nama_belakang");
        setFlash('success', "Data siswa '$nama_depan $nama_belakang' berhasil diperbarui.");
    }
    redirect('admin-siswa.php');
}

// --- AMBIL DATA ---
$search = isset($_GET['s']) ? mysqli_real_escape_string($conn, trim($_GET['s'])) : '';
$where  = "WHERE u.role = 'siswa'";
if ($search) $where .= " AND (u.nama_depan LIKE '%$search%' OR u.nama_belakang LIKE '%$search%' OR ps.nis LIKE '%$search%')";

$rows = mysqli_query($conn,
    "SELECT u.id AS user_id, u.nama_depan, u.nama_belakang, u.email, u.created_at,
            ps.id AS profil_id, ps.nis, ps.kelas, ps.jurusan,
            ps.jenis_kelamin, ps.agama, ps.tempat_lahir, ps.tanggal_lahir,
            ps.no_hp, ps.alamat, ps.golongan_darah
     FROM users u
     LEFT JOIN profil_siswa ps ON u.id = ps.user_id
     $where
     ORDER BY u.id DESC"
);

if (!$rows) die("Query error: " . mysqli_error($conn));

// Dropdown mitra industri untuk referensi perusahaan PKL
$mitraList = mysqli_query($conn, "SELECT id, nama_perusahaan FROM mitra_industri ORDER BY nama_perusahaan");

include '_header_admin.php';
?>

<div class="page-header">
  <h2><i class="fas fa-user-graduate"></i> Data Siswa</h2>
  <button class="btn btn-primary btn-sm" onclick="openModal('modalSiswa')">
    <i class="fas fa-user-plus"></i> Tambah Siswa
  </button>
</div>

<?php getFlash(); ?>

<!-- Search -->
<div class="card" style="margin-bottom:16px;">
  <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input type="text" name="s" placeholder="Cari nama / NIS..."
             value="<?php echo htmlspecialchars($search); ?>"/>
    </div>
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Cari</button>
    <?php if ($search): ?>
      <a href="admin-siswa.php" class="btn btn-sm" style="background:rgba(255,255,255,.06);color:#64748b;">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Tabel -->
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>NIS</th>
          <th>Nama Siswa</th>
          <th>Email</th>
          <th>Kelas / Jurusan</th>
          <th>JK</th>
          <th>No. HP</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $no = 1;
      if (mysqli_num_rows($rows) === 0): ?>
        <tr><td colspan="8" style="text-align:center;padding:20px;color:#64748b;">Belum ada data siswa.</td></tr>
      <?php else:
      while ($r = mysqli_fetch_assoc($rows)): ?>
      <tr>
        <td><?php echo $no++; ?></td>
        <td><?php echo htmlspecialchars($r['nis'] ?? '-'); ?></td>
        <td>
          <i class="fas fa-user-graduate" style="color:#94a3b8;margin-right:7px;"></i>
          <?php echo htmlspecialchars($r['nama_depan'] . ' ' . $r['nama_belakang']); ?>
        </td>
        <td><?php echo htmlspecialchars($r['email']); ?></td>
        <td><?php echo htmlspecialchars(($r['kelas'] ?? '-') . ' / ' . ($r['jurusan'] ?? '-')); ?></td>
        <td><?php
          $jk = $r['jenis_kelamin'] ?? '';
          if ($jk === 'Laki-laki')  echo "<span class='pill pill-blue'>L</span>";
          elseif ($jk === 'Perempuan') echo "<span class='pill pill-purple'>P</span>";
          else echo '-';
        ?></td>
        <td><?php echo htmlspecialchars($r['no_hp'] ?? '-'); ?></td>
        <td style="display:flex;gap:6px;">
          <button class="btn btn-warning btn-sm"
            onclick="editSiswa(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES); ?>)">
            <i class="fas fa-edit"></i>
          </button>
          <a href="?hapus=<?php echo $r['user_id']; ?>"
             class="btn btn-danger btn-sm"
             onclick="return confirm('Hapus siswa ini? Data tidak bisa dikembalikan.')">
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
<div class="modal-overlay" id="modalSiswa">
  <div class="modal" style="max-width:640px;">
    <div class="modal-header">
      <h3 id="modalSiswaTitle">
        <i class="fas fa-user-plus" style="margin-right:8px;color:#94a3b8;"></i> Tambah Siswa
      </h3>
      <button class="modal-close" onclick="closeModal('modalSiswa')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" style="max-height:70vh;overflow-y:auto;padding-right:4px;">
      <input type="hidden" name="user_id"   id="s_user_id"   value="0"/>
      <input type="hidden" name="profil_id" id="s_profil_id" value="0"/>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

        <div class="form-group">
          <label>Nama Depan</label>
          <input type="text" name="nama_depan" id="s_nama_depan" class="form-control" required placeholder="Budi"/>
        </div>
        <div class="form-group">
          <label>Nama Belakang</label>
          <input type="text" name="nama_belakang" id="s_nama_belakang" class="form-control" placeholder="Santoso"/>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" id="s_email" class="form-control" required placeholder="email@contoh.com"/>
        </div>
        <div class="form-group">
          <label>NIS</label>
          <input type="text" name="nis" id="s_nis" class="form-control" required placeholder="NIS Siswa"/>
        </div>

        <div class="form-group">
          <label>Kelas</label>
          <input type="text" name="kelas" id="s_kelas" class="form-control" placeholder="Contoh: XI"/>
        </div>
        <div class="form-group">
          <label>Jurusan</label>
          <input type="text" name="jurusan" id="s_jurusan" class="form-control" placeholder="Contoh: RPL"/>
        </div>

        <div class="form-group">
          <label>Jenis Kelamin</label>
          <select name="jenis_kelamin" id="s_jk" class="form-control">
            <option value="Laki-laki">Laki-laki</option>
            <option value="Perempuan">Perempuan</option>
          </select>
        </div>
        <div class="form-group">
          <label>Agama</label>
          <select name="agama" id="s_agama" class="form-control">
            <option>Islam</option>
            <option>Kristen</option>
            <option>Katolik</option>
            <option>Hindu</option>
            <option>Buddha</option>
          </select>
        </div>

        <div class="form-group">
          <label>Tempat Lahir</label>
          <input type="text" name="tempat_lahir" id="s_tempat_lahir" class="form-control" placeholder="Kota lahir"/>
        </div>
        <div class="form-group">
          <label>Tanggal Lahir</label>
          <input type="date" name="tanggal_lahir" id="s_tgl_lahir" class="form-control"/>
        </div>

        <div class="form-group">
          <label>No. HP</label>
          <input type="text" name="no_hp" id="s_hp" class="form-control" placeholder="08xx"/>
        </div>
        <div class="form-group">
          <label>Golongan Darah</label>
          <select name="golongan_darah" id="s_goldar" class="form-control">
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="AB">AB</option>
            <option value="O">O</option>
          </select>
        </div>

      </div>

      <div class="form-group">
        <label>Alamat</label>
        <textarea name="alamat" id="s_alamat" class="form-control" rows="2" placeholder="Alamat lengkap"></textarea>
      </div>

      <div class="form-group">
        <label>Password
          <span style="color:#64748b;font-size:.78rem;">(Default: <code>siswa123</code> — kosongkan saat edit jika tidak ingin ganti)</span>
        </label>
        <input type="password" name="password" id="s_password" class="form-control" placeholder="••••••••"/>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalSiswa')">Batal</button>
        <button type="submit" name="simpan_siswa" class="btn btn-primary btn-sm">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
  document.getElementById(id).classList.add('active');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('active');
}

function editSiswa(d) {
  document.getElementById('s_user_id').value      = d.user_id   || 0;
  document.getElementById('s_profil_id').value    = d.profil_id || 0;
  document.getElementById('s_nama_depan').value   = d.nama_depan   || '';
  document.getElementById('s_nama_belakang').value = d.nama_belakang || '';
  document.getElementById('s_email').value        = d.email    || '';
  document.getElementById('s_nis').value          = d.nis      || '';
  document.getElementById('s_kelas').value        = d.kelas    || '';
  document.getElementById('s_jurusan').value      = d.jurusan  || '';
  document.getElementById('s_jk').value           = d.jenis_kelamin || 'Laki-laki';
  document.getElementById('s_agama').value        = d.agama    || 'Islam';
  document.getElementById('s_tempat_lahir').value = d.tempat_lahir || '';
  document.getElementById('s_tgl_lahir').value    = d.tanggal_lahir || '';
  document.getElementById('s_hp').value           = d.no_hp    || '';
  document.getElementById('s_goldar').value       = d.golongan_darah || 'A';
  document.getElementById('s_alamat').value       = d.alamat   || '';
  document.getElementById('s_password').value     = '';
  document.getElementById('modalSiswaTitle').innerHTML =
    '<i class="fas fa-edit" style="margin-right:8px;color:#94a3b8;"></i> Edit Siswa';
  openModal('modalSiswa');
}
</script>

<?php include '_footer_admin.php'; ?>