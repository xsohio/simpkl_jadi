<?php
session_start();
include "../config.php";
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') redirect('login.php');

// --- PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if (mysqli_query($conn, "DELETE FROM nilai_pkl WHERE id=$id")) {
        catatLog($conn, "Menghapus nilai PKL id: $id");
        setFlash('success', 'Data nilai berhasil dihapus.');
    }
    header('Location: pkl-nilai.php'); exit;
}

// --- PROSES SIMPAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id                = (int)($_POST['id'] ?? 0);
    $siswa_id          = (int)$_POST['siswa_id'];
    $pembimbing_id     = (int)$_POST['pembimbing_id'];
    $nilai_sikap       = (int)$_POST['nilai_sikap'];
    $nilai_keterampilan = (int)$_POST['nilai_keterampilan'];
    $nilai_laporan     = (int)$_POST['nilai_laporan'];
    $catatan           = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');

    // Hitung nilai akhir: rata-rata dari 3 komponen
    $nilai_akhir = round(($nilai_sikap + $nilai_keterampilan + $nilai_laporan) / 3, 2);

    // Tentukan predikat
    if ($nilai_akhir >= 90)      $predikat = 'A';
    elseif ($nilai_akhir >= 80)  $predikat = 'B';
    elseif ($nilai_akhir >= 70)  $predikat = 'C';
    elseif ($nilai_akhir >= 60)  $predikat = 'D';
    else                         $predikat = 'E';

    if ($id == 0) {
        $sql = "INSERT INTO nilai_pkl (siswa_id, pembimbing_id, nilai_sikap, nilai_keterampilan, nilai_laporan, nilai_akhir, predikat, catatan)
                VALUES ($siswa_id, $pembimbing_id, $nilai_sikap, $nilai_keterampilan, $nilai_laporan, $nilai_akhir, '$predikat', '$catatan')";
        $msg = "Menambah nilai PKL siswa id: $siswa_id";
    } else {
        $sql = "UPDATE nilai_pkl SET
                    siswa_id=$siswa_id,
                    pembimbing_id=$pembimbing_id,
                    nilai_sikap=$nilai_sikap,
                    nilai_keterampilan=$nilai_keterampilan,
                    nilai_laporan=$nilai_laporan,
                    nilai_akhir=$nilai_akhir,
                    predikat='$predikat',
                    catatan='$catatan'
                WHERE id=$id";
        $msg = "Mengubah nilai PKL id: $id";
    }

    if (mysqli_query($conn, $sql)) {
        catatLog($conn, $msg);
        setFlash('success', 'Data nilai berhasil disimpan.');
    } else {
        setFlash('error', 'Gagal menyimpan: ' . mysqli_error($conn));
    }
    header('Location: pkl-nilai.php'); exit;
}

// Ambil semua data nilai dari database (join users untuk nama siswa & pembimbing)
$rows = mysqli_query($conn, "
    SELECT np.*,
           CONCAT(us.nama_depan, ' ', us.nama_belakang) AS nama_siswa,
           CONCAT(up.nama_depan, ' ', up.nama_belakang) AS nama_pembimbing,
           ps.nis, ps.kelas
    FROM nilai_pkl np
    LEFT JOIN users us ON np.siswa_id = us.id
    LEFT JOIN users up ON np.pembimbing_id = up.id
    LEFT JOIN profil_siswa ps ON np.siswa_id = ps.user_id
    ORDER BY np.id DESC
");

// Dropdown siswa
$siswa_opt = mysqli_query($conn, "
    SELECT u.id, CONCAT(u.nama_depan, ' ', u.nama_belakang) AS nama, ps.nis
    FROM users u
    LEFT JOIN profil_siswa ps ON u.id = ps.user_id
    WHERE u.role = 'siswa'
    ORDER BY u.nama_depan
");

// Dropdown pembimbing
$pembimbing_opt = mysqli_query($conn, "
    SELECT id, CONCAT(nama_depan, ' ', nama_belakang) AS nama
    FROM users
    WHERE role = 'pembimbing'
    ORDER BY nama_depan
");

$active_page = 'nilai';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Penilaian PKL — Admin PKL</title>
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
    <span>Penilaian PKL</span>
  </div>

  <div class="page-header">
    <h2><i class="fas fa-star" style="color:#f59e0b;"></i> Penilaian PKL</h2>
    <button class="btn btn-primary btn-sm" onclick="openModal()">
      <i class="fas fa-plus"></i> Input Nilai
    </button>
  </div>

  <div class="card">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Nama Siswa</th>
            <th>Pembimbing</th>
            <th>Sikap</th>
            <th>Keterampilan</th>
            <th>Laporan</th>
            <th>Nilai Akhir</th>
            <th>Predikat</th>
            <th>Catatan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($rows) === 0): ?>
          <tr><td colspan="10" style="text-align:center;color:#94a3b8;">Belum ada data nilai PKL.</td></tr>
        <?php else:
        $no = 1; while ($row = mysqli_fetch_assoc($rows)): 
          $na = (float)$row['nilai_akhir'];
          $warna = $na >= 85 ? '#2dd4bf' : ($na >= 75 ? '#f59e0b' : '#ef4444');
          $predikat = $row['predikat'] ?? '-';
          $pill_class = match($predikat) {
            'A' => 'pill-green',
            'B' => 'pill-yellow',
            default => 'pill-red',
          };
        ?>
          <tr>
            <td><?php echo $no++; ?></td>
            <td>
              <?php echo htmlspecialchars($row['nama_siswa'] ?? '-'); ?>
              <?php if (!empty($row['nis'])): ?>
                <br><small class="text-muted"><?php echo htmlspecialchars($row['nis']); ?> <?php echo $row['kelas'] ? '- '.$row['kelas'] : ''; ?></small>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row['nama_pembimbing'] ?? '-'); ?></td>
            <td><?php echo $row['nilai_sikap']; ?></td>
            <td><?php echo $row['nilai_keterampilan']; ?></td>
            <td><?php echo $row['nilai_laporan']; ?></td>
            <td><strong style="color:<?php echo $warna; ?>"><?php echo number_format($na, 2); ?></strong></td>
            <td><span class="pill <?php echo $pill_class; ?>"><?php echo htmlspecialchars($predikat); ?></span></td>
            <td><?php echo htmlspecialchars($row['catatan'] ?? '-'); ?></td>
            <td style="display:flex;gap:6px;">
              <button class="btn btn-warning btn-sm"
                onclick="editData(
                  <?php echo $row['id']; ?>,
                  <?php echo $row['siswa_id']; ?>,
                  <?php echo $row['pembimbing_id']; ?>,
                  <?php echo $row['nilai_sikap']; ?>,
                  <?php echo $row['nilai_keterampilan']; ?>,
                  <?php echo $row['nilai_laporan']; ?>,
                  '<?php echo addslashes($row['catatan'] ?? ''); ?>'
                )">
                <i class="fas fa-edit"></i>
              </button>
              <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Hapus data nilai ini?')">
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

<!-- MODAL INPUT / EDIT NILAI -->
<div class="modal-overlay" id="modal_nilai">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-star" style="margin-right:8px;color:#f59e0b;"></i>
        <span id="modal_title">Input Nilai PKL</span>
      </h3>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="id" id="field_id" value="0"/>

      <div class="form-group">
        <label>Siswa</label>
        <select name="siswa_id" id="field_siswa_id" class="form-control" required>
          <option value="">-- Pilih Siswa --</option>
          <?php mysqli_data_seek($siswa_opt, 0); while ($rs = mysqli_fetch_assoc($siswa_opt)): ?>
          <option value="<?php echo $rs['id']; ?>">
            <?php echo htmlspecialchars($rs['nama']); ?>
            <?php if (!empty($rs['nis'])) echo ' (' . htmlspecialchars($rs['nis']) . ')'; ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Pembimbing</label>
        <select name="pembimbing_id" id="field_pembimbing_id" class="form-control" required>
          <option value="">-- Pilih Pembimbing --</option>
          <?php mysqli_data_seek($pembimbing_opt, 0); while ($rp = mysqli_fetch_assoc($pembimbing_opt)): ?>
          <option value="<?php echo $rp['id']; ?>"><?php echo htmlspecialchars($rp['nama']); ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group" style="display:flex;gap:12px;">
        <div style="flex:1">
          <label>Nilai Sikap (0-100)</label>
          <input type="number" name="nilai_sikap" id="field_sikap" class="form-control" min="0" max="100" value="0" required/>
        </div>
        <div style="flex:1">
          <label>Nilai Keterampilan (0-100)</label>
          <input type="number" name="nilai_keterampilan" id="field_keterampilan" class="form-control" min="0" max="100" value="0" required/>
        </div>
        <div style="flex:1">
          <label>Nilai Laporan (0-100)</label>
          <input type="number" name="nilai_laporan" id="field_laporan" class="form-control" min="0" max="100" value="0" required/>
        </div>
      </div>

      <div class="form-group">
        <label>Catatan</label>
        <textarea name="catatan" id="field_catatan" class="form-control" rows="2"
                  placeholder="Catatan tambahan..."></textarea>
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
  document.getElementById('modal_title').textContent = 'Input Nilai PKL';
  document.getElementById('field_id').value = '0';
  document.getElementById('field_siswa_id').value = '';
  document.getElementById('field_pembimbing_id').value = '';
  document.getElementById('field_sikap').value = '0';
  document.getElementById('field_keterampilan').value = '0';
  document.getElementById('field_laporan').value = '0';
  document.getElementById('field_catatan').value = '';
  document.getElementById('modal_nilai').classList.add('active');
}

function editData(id, siswa_id, pembimbing_id, sikap, keterampilan, laporan, catatan) {
  document.getElementById('modal_title').textContent = 'Edit Nilai PKL';
  document.getElementById('field_id').value = id;
  document.getElementById('field_siswa_id').value = siswa_id;
  document.getElementById('field_pembimbing_id').value = pembimbing_id;
  document.getElementById('field_sikap').value = sikap;
  document.getElementById('field_keterampilan').value = keterampilan;
  document.getElementById('field_laporan').value = laporan;
  document.getElementById('field_catatan').value = catatan;
  document.getElementById('modal_nilai').classList.add('active');
}

function closeModal() {
  document.getElementById('modal_nilai').classList.remove('active');
}
</script>
</body>
</html>