<?php
session_start();
include "../config.php";
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') redirect('login.php');

// --- PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if (mysqli_query($conn, "DELETE FROM absensi WHERE id=$id")) {
        catatLog($conn, "Menghapus data absensi id: $id");
        setFlash('success', 'Data absensi berhasil dihapus.');
    }
    header('Location: pkl-absensi.php'); exit;
}

// --- PROSES SIMPAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $siswa_id   = (int)$_POST['siswa_id'];
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam_masuk  = mysqli_real_escape_string($conn, $_POST['jam_masuk'] ?? '');
    $jam_pulang = mysqli_real_escape_string($conn, $_POST['jam_pulang'] ?? '');
    $status     = mysqli_real_escape_string($conn, $_POST['status']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');

    $jam_masuk_val  = $jam_masuk  ? "'$jam_masuk'"  : 'NULL';
    $jam_pulang_val = $jam_pulang ? "'$jam_pulang'" : 'NULL';

    if ($id == 0) {
        $sql = "INSERT INTO absensi (siswa_id, tanggal, jam_masuk, jam_pulang, status, keterangan)
                VALUES ($siswa_id, '$tanggal', $jam_masuk_val, $jam_pulang_val, '$status', '$keterangan')";
        $msg = "Menambah data absensi siswa id: $siswa_id tanggal: $tanggal";
    } else {
        $sql = "UPDATE absensi SET
                    siswa_id=$siswa_id,
                    tanggal='$tanggal',
                    jam_masuk=$jam_masuk_val,
                    jam_pulang=$jam_pulang_val,
                    status='$status',
                    keterangan='$keterangan'
                WHERE id=$id";
        $msg = "Mengubah data absensi id: $id";
    }

    if (mysqli_query($conn, $sql)) {
        catatLog($conn, $msg);
        setFlash('success', 'Data absensi berhasil disimpan.');
    } else {
        setFlash('error', 'Gagal menyimpan: ' . mysqli_error($conn));
    }
    header('Location: pkl-absensi.php'); exit;
}

// --- FILTER ---
$filter_tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($conn, $_GET['tanggal']) : '';
$filter_siswa   = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : 0;

$where = [];
if ($filter_tanggal) $where[] = "a.tanggal = '$filter_tanggal'";
if ($filter_siswa)   $where[] = "a.siswa_id = $filter_siswa";
$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Ambil data absensi dari database
$rows = mysqli_query($conn, "
    SELECT a.*,
           CONCAT(u.nama_depan, ' ', u.nama_belakang) AS nama_siswa,
           ps.nis, ps.kelas
    FROM absensi a
    LEFT JOIN users u ON a.siswa_id = u.id
    LEFT JOIN profil_siswa ps ON a.siswa_id = ps.user_id
    $where_sql
    ORDER BY a.tanggal DESC, a.id DESC
");

// Dropdown siswa untuk filter & modal
$siswa_opt = mysqli_query($conn, "
    SELECT u.id, CONCAT(u.nama_depan, ' ', u.nama_belakang) AS nama, ps.nis
    FROM users u
    LEFT JOIN profil_siswa ps ON u.id = ps.user_id
    WHERE u.role = 'siswa'
    ORDER BY u.nama_depan
");

$active_page = 'absensi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Absensi Harian — Admin PKL</title>
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
    .nav-links a:hover,.nav-links a.active{color:#2dd4bf;background:rgba(45,212,191,.1);}
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
    <span>Absensi Harian</span>
  </div>

  <div class="page-header">
    <h2><i class="fas fa-calendar-check" style="color:#10b981;"></i> Absensi Harian</h2>
    <button class="btn btn-primary btn-sm" onclick="openModal()">
      <i class="fas fa-plus"></i> Tambah Absensi
    </button>
  </div>

  <!-- FILTER -->
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
    <input type="date" name="tanggal" class="form-control" style="width:auto;"
           value="<?php echo htmlspecialchars($filter_tanggal); ?>"/>
    <select name="siswa_id" class="form-control" style="width:auto;padding:9px 14px;">
      <option value="0">-- Semua Siswa --</option>
      <?php
      mysqli_data_seek($siswa_opt, 0);
      while ($rs = mysqli_fetch_assoc($siswa_opt)):
        $sel = ($filter_siswa == $rs['id']) ? 'selected' : '';
      ?>
      <option value="<?php echo $rs['id']; ?>" <?php echo $sel; ?>>
        <?php echo htmlspecialchars($rs['nama']); ?>
        <?php if (!empty($rs['nis'])) echo ' (' . htmlspecialchars($rs['nis']) . ')'; ?>
      </option>
      <?php endwhile; ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filter</button>
    <?php if ($filter_tanggal || $filter_siswa): ?>
      <a href="pkl-absensi.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Reset</a>
    <?php endif; ?>
  </form>

  <div class="card">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Nama Siswa</th>
            <th>Tanggal</th>
            <th>Jam Masuk</th>
            <th>Jam Pulang</th>
            <th>Status</th>
            <th>Keterangan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($rows) === 0): ?>
          <tr><td colspan="8" style="text-align:center;color:#94a3b8;">Belum ada data absensi.</td></tr>
        <?php else:
        $no = 1; while ($row = mysqli_fetch_assoc($rows)):
          $status = $row['status'];
          $pill_class = match($status) {
            'Hadir' => 'pill-green',
            'Izin'  => 'pill-yellow',
            'Sakit' => 'pill-yellow',
            'Alpa'  => 'pill-red',
            default => 'pill-yellow',
          };
        ?>
          <tr>
            <td><?php echo $no++; ?></td>
            <td>
              <?php echo htmlspecialchars($row['nama_siswa'] ?? '-'); ?>
              <?php if (!empty($row['nis'])): ?>
                <br><small class="text-muted"><?php echo htmlspecialchars($row['nis']); ?><?php echo $row['kelas'] ? ' - ' . htmlspecialchars($row['kelas']) : ''; ?></small>
              <?php endif; ?>
            </td>
            <td><?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
            <td><?php echo $row['jam_masuk'] ? substr($row['jam_masuk'], 0, 5) : '-'; ?></td>
            <td><?php echo $row['jam_pulang'] ? substr($row['jam_pulang'], 0, 5) : '-'; ?></td>
            <td><span class="pill <?php echo $pill_class; ?>"><?php echo htmlspecialchars($status); ?></span></td>
            <td><?php echo htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
            <td style="display:flex;gap:6px;">
              <button class="btn btn-warning btn-sm"
                onclick="editData(
                  <?php echo $row['id']; ?>,
                  <?php echo $row['siswa_id']; ?>,
                  '<?php echo addslashes($row['tanggal']); ?>',
                  '<?php echo addslashes(substr($row['jam_masuk'] ?? '', 0, 5)); ?>',
                  '<?php echo addslashes(substr($row['jam_pulang'] ?? '', 0, 5)); ?>',
                  '<?php echo addslashes($status); ?>',
                  '<?php echo addslashes($row['keterangan'] ?? ''); ?>'
                )">
                <i class="fas fa-edit"></i>
              </button>
              <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Hapus data absensi ini?')">
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

<!-- MODAL TAMBAH / EDIT -->
<div class="modal-overlay" id="modal_absensi">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-calendar-check" style="margin-right:8px;color:#10b981;"></i>
        <span id="modal_title">Tambah Absensi</span>
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
            <?php echo htmlspecialchars($rs['nama']); ?>
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
          <label>Jam Pulang</label>
          <input type="time" name="jam_pulang" id="field_jam_pulang" class="form-control"/>
        </div>
      </div>

      <div class="form-group">
        <label>Status Kehadiran</label>
        <select name="status" id="field_status" class="form-control" required>
          <option value="Hadir">Hadir</option>
          <option value="Izin">Izin</option>
          <option value="Sakit">Sakit</option>
          <option value="Alpa">Alpa</option>
        </select>
      </div>

      <div class="form-group">
        <label>Keterangan</label>
        <textarea name="keterangan" id="field_keterangan" class="form-control" rows="2"
                  placeholder="Keterangan tambahan..."></textarea>
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
const sidebar=document.getElementById('sidebar'),main=document.getElementById('mainContent'),
      toggleBtn=document.getElementById('sidebarToggle'),mobileBtn=document.getElementById('mobileMenuBtn'),
      overlay=document.getElementById('sidebarOverlay');
toggleBtn.addEventListener('click',()=>{sidebar.classList.toggle('collapsed');main.classList.toggle('expanded');toggleBtn.classList.toggle('collapsed');});
mobileBtn.addEventListener('click',()=>{sidebar.classList.toggle('mobile-open');overlay.classList.toggle('active');});
overlay.addEventListener('click',()=>{sidebar.classList.remove('mobile-open');overlay.classList.remove('active');});

function openModal() {
  document.getElementById('modal_title').textContent = 'Tambah Absensi';
  document.getElementById('field_id').value = '0';
  document.getElementById('field_siswa_id').value = '';
  document.getElementById('field_tanggal').value = '';
  document.getElementById('field_jam_masuk').value = '';
  document.getElementById('field_jam_pulang').value = '';
  document.getElementById('field_status').value = 'Hadir';
  document.getElementById('field_keterangan').value = '';
  document.getElementById('modal_absensi').classList.add('active');
}

function editData(id, siswa_id, tanggal, jam_masuk, jam_pulang, status, keterangan) {
  document.getElementById('modal_title').textContent = 'Edit Absensi';
  document.getElementById('field_id').value = id;
  document.getElementById('field_siswa_id').value = siswa_id;
  document.getElementById('field_tanggal').value = tanggal;
  document.getElementById('field_jam_masuk').value = jam_masuk;
  document.getElementById('field_jam_pulang').value = jam_pulang;
  document.getElementById('field_status').value = status;
  document.getElementById('field_keterangan').value = keterangan;
  document.getElementById('modal_absensi').classList.add('active');
}

function closeModal() {
  document.getElementById('modal_absensi').classList.remove('active');
}
</script>
</body>
</html>