<?php
session_start();
include "../config.php";
requireSiswa();

catatLog($conn, "Mengirim laporan mingguan baru");

$siswa_id = $_SESSION['user']['id_user'];
$active_page = 'laporan';
$page_title  = 'Laporan PKL';


// ---- Upload Laporan ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_upload'])) {
    $judul  = mysqli_real_escape_string($conn, $_POST['judul_laporan']);
    $jenis  = in_array($_POST['jenis_laporan'], ['mingguan','akhir']) ? $_POST['jenis_laporan'] : 'mingguan';

    $file_path = NULL;
    if (!empty($_FILES['file_laporan']['name'])) {
        $ext    = pathinfo($_FILES['file_laporan']['name'], PATHINFO_EXTENSION);
        $nama   = "laporan_{$siswa_id}_" . time() . ".$ext";
        $target = "../assets/uploads/$nama";
        if (move_uploaded_file($_FILES['file_laporan']['tmp_name'], $target)) {
            $file_path = $nama;
        }
    }
    $fp_sql = $file_path ? "'$file_path'" : "NULL";
    mysqli_query($conn,
        "INSERT INTO laporan_pkl (siswa_id, judul_laporan, file_path, jenis_laporan)
         VALUES ($siswa_id, '$judul', $fp_sql, '$jenis')");
    // Log aktivitas
    mysqli_query($conn,
        "INSERT INTO log_aktivitas (id_users, aktivitas) VALUES ($siswa_id, 'Menambah laporan PKL: $judul')");
    setFlash('success', 'Laporan berhasil diunggah!');
    redirect('siswa-laporan.php');
}

// Cari bagian setelah mysqli_query INSERT laporan_pkl:
if (mysqli_query($conn, "INSERT INTO laporan_pkl ...")) {
    // LOGIKA LOG:
    $judul_log = mysqli_real_escape_string($conn, $_POST['judul_laporan']);
    // $msg_log = "Mengunggah laporan PKL: " . $judul_log;
    mysqli_query($conn, "INSERT INTO log_aktivitas (id_users, aktivitas) VALUES ($siswa_id, )");
    
    setFlash('success', 'Laporan berhasil diunggah.');
}
// ---- Hapus (hanya jika pending) ----
if (isset($_GET['hapus'])) {
    $id  = (int)$_GET['hapus'];
    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM laporan_pkl WHERE id=$id AND siswa_id=$siswa_id AND status_pembimbing='pending'"));
    if ($row) {
        mysqli_query($conn, "DELETE FROM laporan_pkl WHERE id=$id");
        setFlash('success', 'Laporan berhasil dihapus.');
    } else {
        setFlash('error', 'Laporan tidak dapat dihapus (sudah diproses).');
    }
    redirect('siswa-laporan.php');
}

$result = mysqli_query($conn,
    "SELECT * FROM laporan_pkl WHERE siswa_id=$siswa_id ORDER BY created_at DESC");

include '_header_siswa.php';
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h2><i class="fas fa-file-alt"></i> Laporan PKL Saya</h2>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalLaporan').classList.add('active')">
        <i class="fas fa-upload"></i> Upload Laporan
    </button>
</div>

<?php getFlash(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul</th>
                    <th>Jenis</th>
                    <th>Tgl Upload</th>
                    <th>Status Pembimbing</th>
                    <th>Status Wakasek</th>
                    <th>Catatan Revisi</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            $sp_pill = ['pending'=>'pill-yellow','revisi'=>'pill-red','disetujui'=>'pill-green'];
            $sw_pill = ['pending'=>'pill-yellow','disetujui'=>'pill-green'];
            if ($result && mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['judul_laporan'] ?? '(Tanpa judul)'); ?></td>
                    <td>
                        <span class="pill pill-blue"><?= ucfirst($row['jenis_laporan']); ?></span>
                    </td>
                    <td style="white-space:nowrap;"><?= date('d M Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <span class="pill <?= $sp_pill[$row['status_pembimbing']]; ?>">
                            <?= ucfirst($row['status_pembimbing']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="pill <?= $sw_pill[$row['status_wakasek']]; ?>">
                            <?= ucfirst($row['status_wakasek']); ?>
                        </span>
                    </td>
                    <td style="font-size:.78rem;color:<?= $row['catatan_revisi'] ? '#ef4444' : '#475569'; ?>;">
                        <?= $row['catatan_revisi'] ? htmlspecialchars(mb_strimwidth($row['catatan_revisi'],0,50,'...')) : '-'; ?>
                    </td>
                    <td style="text-align:center;white-space:nowrap;">
                        <?php if ($row['file_path']): ?>
                        <a href="../assets/uploads/<?= $row['file_path']; ?>" target="_blank"
                           style="color:#93c5fd;font-size:.78rem;text-decoration:none;margin-right:8px;">
                            <i class="fas fa-download"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($row['status_pembimbing'] === 'pending'): ?>
                        <a href="siswa-laporan.php?hapus=<?= $row['id']; ?>"
                           onclick="return confirm('Hapus laporan ini?')"
                           style="color:#ef4444;font-size:.78rem;text-decoration:none;">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:30px;color:#64748b;">
                        Belum ada laporan. Segera upload laporan PKL Anda!
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Upload -->
<div class="modal-overlay" id="modalLaporan">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <h3><i class="fas fa-upload" style="color:#4ade80;"></i> Upload Laporan PKL</h3>
            <button class="modal-close" onclick="document.getElementById('modalLaporan').classList.remove('active')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi_upload" value="1">
            <div class="form-group">
                <label>Judul Laporan</label>
                <input type="text" name="judul_laporan" class="form-control" placeholder="cth: Laporan Mingguan 1" required>
            </div>
            <div class="form-group">
                <label>Jenis Laporan</label>
                <select name="jenis_laporan" class="form-control">
                    <option value="mingguan">Mingguan</option>
                    <option value="akhir">Akhir</option>
                </select>
            </div>
            <div class="form-group">
                <label>File Laporan (PDF/Word)</label>
                <input type="file" name="file_laporan" class="form-control" accept=".pdf,.doc,.docx">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <button type="button" class="btn btn-secondary btn-sm"
                    onclick="document.getElementById('modalLaporan').classList.remove('active')">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-cloud-upload-alt"></i> Upload
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_GET['aksi']) && $_GET['aksi'] === 'upload'): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('modalLaporan').classList.add('active');
});
</script>
<?php endif; ?>

<?php include '_footer_siswa.php'; ?>
