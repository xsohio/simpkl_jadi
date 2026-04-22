<?php
session_start();
include "../config.php";
requirePembimbing();

$pid = (int)$_SESSION['user']['id_user'];
$active_page = 'absensi';
$page_title = 'Monitoring Absensi Siswa';

function catat_log($conn, $user_id, $aktivitas) {
    $aktivitas = mysqli_real_escape_string($conn, $aktivitas);
    mysqli_query($conn, "INSERT INTO log_aktivitas (id_users, aktivitas) VALUES ($user_id, '$aktivitas')");
}

// -------- TAMBAH ABSENSI --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_absensi'])) {
    $siswa_id   = (int)$_POST['siswa_id'];
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam_masuk  = mysqli_real_escape_string($conn, $_POST['jam_masuk']);
    $jam_keluar = mysqli_real_escape_string($conn, $_POST['jam_keluar']);
    $status     = mysqli_real_escape_string($conn, $_POST['status']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');

    // Cek duplikat tanggal
    $cek = mysqli_query($conn, "SELECT id FROM absensi WHERE siswa_id=$siswa_id AND tanggal='$tanggal'");
    if (mysqli_num_rows($cek) > 0) {
        setFlash('warning', "<i class='fas fa-triangle-exclamation'></i> Absensi siswa ini pada tanggal tersebut sudah ada.");
    } else {
        mysqli_query($conn, "INSERT INTO absensi (siswa_id,tanggal,jam_masuk,jam_pulang,status,keterangan)
            VALUES ($siswa_id,'$tanggal','$jam_masuk','$jam_keluar','$status','$keterangan')");
        $nama = mysqli_fetch_assoc(mysqli_query($conn,"SELECT CONCAT(nama_depan,' ',nama_belakang) n FROM users WHERE id=$siswa_id"))['n'];
        catat_log($conn, $pid, "Menambah absensi $status untuk siswa $nama tanggal $tanggal");
        setFlash('success', "<i class='fas fa-circle-check'></i> Absensi berhasil ditambahkan.");
    }
    header("Location: absensi_pembimbing.php"); exit();
}

// -------- HAPUS ABSENSI --------
if (isset($_GET['hapus'])) {
    $aid = (int)$_GET['hapus'];
    $info = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT a.tanggal, a.status, u.nama_depan, u.nama_belakang
         FROM absensi a JOIN users u ON a.siswa_id=u.id WHERE a.id=$aid"));
    if ($info) {
        mysqli_query($conn, "DELETE FROM absensi WHERE id=$aid");
        catat_log($conn, $pid, "Menghapus absensi {$info['status']} siswa {$info['nama_depan']} {$info['nama_belakang']} tgl {$info['tanggal']}");
        setFlash('success', "<i class='fas fa-trash-can'></i> Data absensi dihapus.");
    }
    header("Location: absensi_pembimbing.php"); exit();
}

// -------- FILTER SISWA --------
$filter_siswa = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : 0;
$where_siswa  = $filter_siswa ? "AND a.siswa_id = $filter_siswa" : '';

// Ambil daftar siswa bimbingan untuk dropdown
$siswa_list = mysqli_query($conn,
    "SELECT u.id, u.nama_depan, u.nama_belakang FROM pkl_pengajuan p
     JOIN users u ON p.ketua_id=u.id WHERE p.pembimbing_id=$pid");

include '_header_pembimbing.php';
?>

<div class="panel">
    <div class="panel-header" style="flex-wrap:wrap;gap:12px;">
        <div style="display:flex;align-items:center;gap:15px;">
            <div style="background:rgba(99,102,241,0.12);padding:12px;border-radius:12px;">
                <i class="fas fa-calendar-check" style="color:#6366f1;font-size:1.4rem;"></i>
            </div>
            <div>
                <h3 style="margin:0;">Monitoring Absensi</h3>
                <p style="color:#64748b;font-size:.78rem;margin:0;">Rekap kehadiran harian siswa bimbingan Anda.</p>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <!-- Filter Siswa -->
            <form method="GET" style="display:flex;gap:6px;align-items:center;">
                <select name="siswa_id" onchange="this.form.submit()"
                  style="background:#1e293b;border:1px solid rgba(255,255,255,.1);color:#e2e8f0;
                  padding:7px 10px;border-radius:8px;font-size:.8rem;">
                    <option value="0">Semua Siswa</option>
                    <?php
                    mysqli_data_seek($siswa_list, 0);
                    while($s = mysqli_fetch_assoc($siswa_list)):
                    ?>
                    <option value="<?= $s['id'] ?>" <?= $filter_siswa==$s['id']?'selected':'' ?>>
                        <?= htmlspecialchars($s['nama_depan'].' '.$s['nama_belakang']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </form>
            <!-- Tombol Tambah -->
            <button onclick="document.getElementById('modal-tambah').style.display='flex'"
              style="display:inline-flex;align-items:center;gap:6px;background:#6366f1;color:#fff;
              border:none;padding:8px 14px;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;">
                <i class="fas fa-plus"></i> Tambah Absensi
            </button>
        </div>
    </div>

    <div class="table-responsive" style="margin-top:20px;">
        <table>
            <thead>
                <tr>
                    <th><i class="fas fa-user" style="color:#6366f1;"></i> Nama Siswa</th>
                    <th style="text-align:center;"><i class="fas fa-calendar-days" style="color:#f59e0b;"></i> Tanggal</th>
                    <th style="text-align:center;"><i class="fas fa-right-to-bracket" style="color:#4ade80;"></i> Jam Masuk</th>
                    <th style="text-align:center;"><i class="fas fa-right-from-bracket" style="color:#f87171;"></i> Jam Pulang</th>
                    <th style="text-align:center;"><i class="fas fa-circle-info" style="color:#93c5fd;"></i> Status</th>
                    <th style="text-align:center;"><i class="fas fa-sliders" style="color:#c084fc;"></i> Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $res = mysqli_query($conn,
                "SELECT a.*, u.nama_depan, u.nama_belakang
                 FROM absensi a
                 JOIN users u ON a.siswa_id = u.id
                 JOIN pkl_pengajuan p ON u.id = p.ketua_id
                 WHERE p.pembimbing_id = $pid $where_siswa
                 ORDER BY a.tanggal DESC, a.jam_masuk DESC");

            if (!$res || mysqli_num_rows($res) == 0):
            ?>
            <tr>
                <td colspan="6" style="text-align:center;padding:50px;color:#475569;">
                    <i class="fas fa-calendar-xmark" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.3;color:#6366f1;"></i>
                    Belum ada data absensi.
                </td>
            </tr>
            <?php else: while($d = mysqli_fetch_assoc($res)):
                $st = strtolower($d['status']);
                $pill = ($st=='hadir') ? 'pill-green' : (in_array($st,['izin','sakit']) ? 'pill-yellow' : 'pill-red');
                $st_icon = ($st=='hadir') ? 'fa-circle-check' : (in_array($st,['izin','sakit']) ? 'fa-notes-medical' : 'fa-circle-xmark');
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#7c3aed);
                            display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;color:#fff;flex-shrink:0;">
                            <?= strtoupper(substr($d['nama_depan'],0,1).substr($d['nama_belakang'],0,1)) ?>
                        </div>
                        <span style="font-weight:600;color:#f1f5f9;font-size:.85rem;">
                            <?= htmlspecialchars($d['nama_depan'].' '.$d['nama_belakang']) ?>
                        </span>
                    </div>
                </td>
                <td style="text-align:center;color:#94a3b8;font-size:.84rem;">
                    <?= date('d M Y', strtotime($d['tanggal'])) ?>
                </td>
                <td style="text-align:center;color:#4ade80;font-family:monospace;font-weight:700;">
                    <?= $d['jam_masuk'] ?: '--:--' ?>
                </td>
                <td style="text-align:center;color:#f87171;font-family:monospace;font-weight:700;">
                    <?= $d['jam_pulang'] ?: '--:--' ?>
                </td>
                <td style="text-align:center;">
                    <span class="pill <?= $pill ?>" style="display:inline-flex;align-items:center;gap:4px;">
                        <i class="fas <?= $st_icon ?>"></i>
                        <?= strtoupper($d['status']) ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <a href="?hapus=<?= $d['id'] ?>" class="btn-aksi btn-aksi-red"
                       onclick="return confirm('Hapus data absensi ini?')" title="Hapus">
                        <i class="fas fa-trash-can"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH ABSENSI -->
<div id="modal-tambah" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);
  z-index:999;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
  <div style="background:#1e293b;border:1px solid rgba(255,255,255,.1);border-radius:16px;
    padding:28px;width:100%;max-width:440px;animation:fadeUp .3s ease;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h3 style="margin:0;display:flex;align-items:center;gap:8px;font-size:.95rem;">
        <i class="fas fa-calendar-plus" style="color:#6366f1;"></i> Tambah Absensi
      </h3>
      <button onclick="document.getElementById('modal-tambah').style.display='none'"
        style="background:rgba(255,255,255,.08);border:none;color:#94a3b8;width:30px;height:30px;
        border-radius:6px;cursor:pointer;font-size:.9rem;"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" style="display:flex;flex-direction:column;gap:12px;">
      <div>
        <label class="form-label"><i class="fas fa-user-graduate" style="color:#6366f1;margin-right:5px;"></i>Siswa</label>
        <select name="siswa_id" required class="form-input">
          <option value="">— Pilih Siswa —</option>
          <?php
          mysqli_data_seek($siswa_list, 0);
          while($s = mysqli_fetch_assoc($siswa_list)):
          ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_depan'].' '.$s['nama_belakang']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label class="form-label"><i class="fas fa-calendar-days" style="color:#f59e0b;margin-right:5px;"></i>Tanggal</label>
        <input type="date" name="tanggal" required class="form-input" value="<?= date('Y-m-d') ?>">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div>
          <label class="form-label"><i class="fas fa-right-to-bracket" style="color:#4ade80;margin-right:5px;"></i>Jam Masuk</label>
          <input type="time" name="jam_masuk" class="form-input">
        </div>
        <div>
          <label class="form-label"><i class="fas fa-right-from-bracket" style="color:#f87171;margin-right:5px;"></i>Jam Pulang</label>
          <input type="time" name="jam_keluar" class="form-input">
        </div>
      </div>
      <div>
        <label class="form-label"><i class="fas fa-circle-dot" style="color:#93c5fd;margin-right:5px;"></i>Status</label>
        <select name="status" required class="form-input">
          <option value="Hadir">✅ Hadir</option>
          <option value="Izin">🟡 Izin</option>
          <option value="Sakit">🟠 Sakit</option>
          <option value="Alpa">🔴 Alpa</option>
        </select>
      </div>
      <div>
        <label class="form-label"><i class="fas fa-pen-line" style="color:#c084fc;margin-right:5px;"></i>Keterangan</label>
        <input type="text" name="keterangan" class="form-input" placeholder="Opsional...">
      </div>
      <button type="submit" name="tambah_absensi"
        style="background:#6366f1;color:#fff;border:none;border-radius:10px;padding:11px;
        font-size:.88rem;font-weight:600;cursor:pointer;margin-top:4px;display:flex;align-items:center;justify-content:center;gap:8px;">
        <i class="fas fa-floppy-disk"></i> Simpan Absensi
      </button>
    </form>
  </div>
</div>

<style>
.form-label { display:block;font-size:.76rem;color:#64748b;margin-bottom:4px;font-weight:600; }
.form-input {
    width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
    color:#e2e8f0;padding:9px 12px;border-radius:8px;font-size:.84rem;font-family:'Poppins',sans-serif;
}
.form-input:focus { outline:none;border-color:#6366f1;background:rgba(99,102,241,.08); }
.btn-aksi { display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;font-size:.82rem;border:1px solid;transition:.2s;cursor:pointer;text-decoration:none; }
.btn-aksi-red { color:#ef4444;border-color:rgba(239,68,68,.25);background:rgba(239,68,68,.08); }
.btn-aksi-red:hover { background:rgba(239,68,68,.2); }
</style>
