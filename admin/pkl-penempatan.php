<?php
session_start();
include "../config.php";
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') redirect('login.php');

// --- PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if (mysqli_query($conn, "DELETE FROM pkl_pengajuan WHERE id=$id")) {
        catatLog($conn, "Menghapus penempatan PKL id: $id");
        setFlash('success', 'Data penempatan berhasil dihapus.');
    }
    header('Location: pkl-penempatan.php'); exit;
}

// --- PROSES UBAH STATUS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id                = (int)$_POST['id'];
    $status_pembimbing = mysqli_real_escape_string($conn, $_POST['status_pembimbing']);
    $status_wakasek    = mysqli_real_escape_string($conn, $_POST['status_wakasek']);
    $pembimbing_id     = (int)($_POST['pembimbing_id'] ?? 0);

    $set_pembimbing = $pembimbing_id > 0 ? "pembimbing_id=$pembimbing_id," : "";

    $sql = "UPDATE pkl_pengajuan SET 
                {$set_pembimbing}
                status_pembimbing='$status_pembimbing',
                status_wakasek='$status_wakasek'
            WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        catatLog($conn, "Mengubah status penempatan PKL id: $id");
        setFlash('success', 'Status penempatan berhasil diperbarui.');
    } else {
        setFlash('error', 'Gagal memperbarui: ' . mysqli_error($conn));
    }
    header('Location: pkl-penempatan.php'); exit;
}

// Ambil data penempatan dari pkl_pengajuan + info ketua (siswa) + pembimbing
$rows = mysqli_query($conn, "
    SELECT pp.*,
           CONCAT(uk.nama_depan, ' ', uk.nama_belakang) AS nama_ketua,
           CONCAT(up.nama_depan, ' ', up.nama_belakang) AS nama_pembimbing,
           ps.nis, ps.kelas,
           (SELECT GROUP_CONCAT(CONCAT(ua.nama_depan, ' ', ua.nama_belakang) SEPARATOR ', ')
            FROM pkl_anggota pa
            JOIN users ua ON pa.siswa_id = ua.id
            WHERE pa.pengajuan_id = pp.id) AS nama_anggota
    FROM pkl_pengajuan pp
    LEFT JOIN users uk ON pp.ketua_id = uk.id
    LEFT JOIN users up ON pp.pembimbing_id = up.id
    LEFT JOIN profil_siswa ps ON pp.ketua_id = ps.user_id
    ORDER BY pp.id DESC
");

// Dropdown pembimbing untuk form
$pembimbing_opt = mysqli_query($conn, "
    SELECT id, CONCAT(nama_depan, ' ', nama_belakang) AS nama
    FROM users
    WHERE role = 'pembimbing'
    ORDER BY nama_depan
");

$active_page = 'penempatan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Penempatan PKL — Admin PKL</title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <style>
    body{display:flex;flex-direction:column;}
    header{position:fixed;top:0;left:0;right:0;z-index:100;background:linear-gradient(to bottom, #19173a);backdrop-filter:blur(10px);border-bottom:1px solid rgba(45,212,191,.2);height:64px;}
    header nav{display:flex;align-items:center;justify-content:space-between;height:100%;padding:0 24px;}
    .logo{font-weight:500;font-size:1.1rem;letter-spacing:2px;}
    .nav-links{display:flex;gap:8px;align-items:center;}
    .nav-links a{color:#94a3b8;font-size:.85rem;padding:6px 14px;border-radius:8px;transition:all .2s;display:flex;align-items:center;gap:6px;}
    .nav-links a:hover,.nav-links a.active{color:#2dd4bf;background:rgba(234,234,234,.1);}
    .layout-wrapper{display:flex;margin-top:64px;min-height:calc(100vh - 64px);}
    .sidebar{width:260px;min-height:calc(100vh - 64px);background:linear-gradient(to bottom, #19173a);border-right:1px solid rgba(45,212,191,.15);padding:24px 0;position:fixed;top:64px;left:0;bottom:0;overflow-y:auto;transition:width .3s,transform .3s;z-index:90;}
    .sidebar.collapsed{width:68px;}
    .sidebar-section{padding:0 16px;margin-bottom:8px;}
    .sidebar-section-title{font-size:.65rem;font-weight:600;letter-spacing:2px;color:#475569;text-transform:uppercase;padding:12px 8px 6px;white-space:nowrap;overflow:hidden;}
    .sidebar.collapsed .sidebar-section-title{opacity:0;pointer-events:none;}
    .sidebar-item{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;text-decoration:none;color:#94a3b8;font-size:.875rem;font-weight:500;transition:all .2s;white-space:nowrap;overflow:hidden;position:relative;margin-bottom:2px;}
    .sidebar-item i{width:20px;text-align:center;font-size:1rem;flex-shrink:0;}
    .sidebar.collapsed .sidebar-item span{opacity:0;width:0;overflow:hidden;}
    .sidebar-item:hover{color:#e2e8f0;background:rgba(255,255,255,.06);}
    .sidebar-item.active{color:#2dd4bf;background:rgba(45,212,191,.12);border:1px solid rgba(45,212,191,.2);}
    .sidebar-item.danger:hover{color:#ef4444;background:rgba(239,68,68,.1);}
    .badge{margin-left:auto;background:#ef4444;color:#fff;font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:99px;flex-shrink:0;}
    .sidebar.collapsed .badge{opacity:0;}
    .sidebar.collapsed .sidebar-item:hover::after{content:attr(data-tooltip);position:absolute;left:68px;top:50%;transform:translateY(-50%);background:#1e293b;color:#e2e8f0;padding:6px 12px;border-radius:8px;font-size:.8rem;white-space:nowrap;border:1px solid rgba(45,212,191,.2);pointer-events:none;z-index:200;}
    .sidebar-toggle{position:fixed;top:74px;left:246px;z-index:110;background:#1e293b;border:1px solid rgba(45,212,191,.3);color:#2dd4bf;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.75rem;transition:left .3s,transform .3s;}
    .sidebar-toggle.collapsed{left:54px;transform:rotate(180deg);}
    .sidebar-profile .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#2dd4bf,#0ea5e9);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;color:#0f172a;flex-shrink:0;}
    .sidebar.collapsed .profile-info{opacity:0;width:0;}
    .profile-info .name{font-size:.8rem;font-weight:600;color:#e2e8f0;}
    .profile-info .role-label{font-size:.7rem;color:#2dd4bf;}
    .main-content{margin-left:260px;flex:1;padding:32px;transition:margin-left .3s;}
    .main-content.expanded{margin-left:68px;}
    .mobile-menu-btn{display:none;align-items:center;justify-content:center;background:none;border:1px solid rgba(45,212,191,.3);color:#2dd4bf;width:34px;height:34px;border-radius:8px;cursor:pointer;font-size:.9rem;}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:88;}
    @media(max-width:768px){.sidebar{transform:translateX(-100%);width:260px!important;}.sidebar.mobile-open{transform:translateX(0);}.sidebar-overlay.active{display:block;}.sidebar-toggle{display:none;}.main-content{margin-left:0!important;padding:20px 16px;}.mobile-menu-btn{display:flex!important;}}
  </style>
</head>
<body>
<header>
  <nav>
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
      <div class="logo">ADMIN PANEL</div>
    </div>
    <ul class="nav-links">
      <li><a href="dashboard_admin2.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
    </ul>
  </nav>
</header>
<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-chevron-left"></i></button>
<?php include 'sidebar.php'; ?>
<div class="layout-wrapper">
<main class="main-content" id="mainContent">
  <?php if (function_exists('getFlash')) getFlash(); ?>
  <div class="breadcrumb">
    <i class="fas fa-home"></i>
    <i class="fas fa-chevron-right" style="font-size:.6rem;"></i>
    <a href="dashboard_admin2.php" style="color:#94a3b8;">Dashboard</a>
    <i class="fas fa-chevron-right" style="font-size:.6rem;"></i>
    <span>Penempatan PKL</span>
  </div>

  <div class="page-header">
    <h2><i class="fas fa-map-marker-alt" style="color:#8b5cf6;"></i> Penempatan PKL</h2>
  </div>

  <div class="card">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Ketua / Siswa</th>
            <th>Perusahaan</th>
            <th>Alamat</th>
            <th>Pembimbing</th>
            <th>Anggota</th>
            <th>Tgl Pengajuan</th>
            <th>Status Pembimbing</th>
            <th>Status Wakasek</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($rows) === 0): ?>
          <tr><td colspan="10" style="text-align:center;color:#94a3b8;">Belum ada data penempatan PKL.</td></tr>
        <?php else:
        $no = 1; while ($row = mysqli_fetch_assoc($rows)):
          $sp = $row['status_pembimbing'];
          $sw = $row['status_wakasek'];

          $sp_class = match($sp) {
            'disetujui' => 'pill-green',
            'ditolak'   => 'pill-red',
            default     => 'pill-yellow',
          };
          $sw_class = match($sw) {
            'disetujui' => 'pill-green',
            'ditolak'   => 'pill-red',
            default     => 'pill-yellow',
          };
        ?>
          <tr>
            <td><?php echo $no++; ?></td>
            <td>
              <?php echo htmlspecialchars($row['nama_ketua'] ?? '-'); ?>
              <?php if (!empty($row['nis'])): ?>
                <br><small class="text-muted"><?php echo htmlspecialchars($row['nis']); ?> <?php echo $row['kelas'] ? '- '.$row['kelas'] : ''; ?></small>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row['nama_perusahaan'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($row['alamat_perusahaan'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($row['nama_pembimbing'] ?? '<em style="color:#94a3b8">Belum ditentukan</em>'); ?></td>
            <td>
              <?php 
              $anggota = $row['nama_anggota'];
              echo $anggota ? htmlspecialchars($anggota) : '<em style="color:#94a3b8">-</em>';
              ?>
            </td>
            <td><?php echo date('d M Y', strtotime($row['tanggal_pengajuan'])); ?></td>
            <td><span class="pill <?php echo $sp_class; ?>"><?php echo ucfirst($sp); ?></span></td>
            <td><span class="pill <?php echo $sw_class; ?>"><?php echo ucfirst($sw); ?></span></td>
            <td style="display:flex;gap:6px;">
              <button class="btn btn-warning btn-sm"
                onclick="editStatus(
                  <?php echo $row['id']; ?>,
                  '<?php echo addslashes($sp); ?>',
                  '<?php echo addslashes($sw); ?>',
                  <?php echo (int)($row['pembimbing_id'] ?? 0); ?>
                )">
                <i class="fas fa-edit"></i>
              </button>
              <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Hapus data penempatan ini?')">
                <i class="fas fa-trash"></i>
              </a>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</div>

<!-- MODAL EDIT STATUS -->
<div class="modal-overlay" id="modal_penempatan">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-map-marker-alt" style="margin-right:8px;color:#8b5cf6;"></i> Edit Status Penempatan</h3>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="id" id="field_id" value="0"/>

      <div class="form-group">
        <label>Pembimbing yang Ditugaskan</label>
        <select name="pembimbing_id" id="field_pembimbing_id" class="form-control">
          <option value="0">-- Belum Ditentukan --</option>
          <?php mysqli_data_seek($pembimbing_opt, 0); while ($rp = mysqli_fetch_assoc($pembimbing_opt)): ?>
          <option value="<?php echo $rp['id']; ?>"><?php echo htmlspecialchars($rp['nama']); ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Status Pembimbing</label>
        <select name="status_pembimbing" id="field_sp" class="form-control">
          <option value="pending">Pending</option>
          <option value="disetujui">Disetujui</option>
          <option value="ditolak">Ditolak</option>
        </select>
      </div>

      <div class="form-group">
        <label>Status Wakasek</label>
        <select name="status_wakasek" id="field_sw" class="form-control">
          <option value="pending">Pending</option>
          <option value="disetujui">Disetujui</option>
          <option value="ditolak">Ditolak</option>
        </select>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal()">Batal</button>
        <button type="submit" name="update_status" class="btn btn-primary btn-sm">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const sidebar=document.getElementById('sidebar'),main=document.getElementById('mainContent'),
      toggleBtn=document.getElementById('sidebarToggle'),mobileBtn=document.getElementById('mobileMenuBtn'),
      overlay=document.getElementById('sidebarOverlay');
toggleBtn.addEventListener('click',()=>{sidebar.classList.toggle('collapsed');main.classList.toggle('expanded');toggleBtn.classList.toggle('collapsed');});
mobileBtn.addEventListener('click',()=>{sidebar.classList.toggle('mobile-open');overlay.classList.toggle('active');});
overlay.addEventListener('click',()=>{sidebar.classList.remove('mobile-open');overlay.classList.remove('active');});

function editStatus(id, sp, sw, pembimbing_id) {
  document.getElementById('field_id').value = id;
  document.getElementById('field_sp').value = sp;
  document.getElementById('field_sw').value = sw;
  document.getElementById('field_pembimbing_id').value = pembimbing_id;
  document.getElementById('modal_penempatan').classList.add('active');
}

function closeModal() {
  document.getElementById('modal_penempatan').classList.remove('active');
}
</script>
</body>
</html>