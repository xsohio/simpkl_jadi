<?php
session_start();
include "../config.php";
requireSiswa();

$siswa_id = $_SESSION['user']['id_user'];
$active_page = 'jurnal';
$page_title  = 'Jurnal Harian';


// ---- Simpan Jurnal Baru ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_simpan'])) {
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam_masuk  = mysqli_real_escape_string($conn, $_POST['jam_masuk']);
    $jam_keluar = mysqli_real_escape_string($conn, $_POST['jam_keluar']);
    $kegiatan   = mysqli_real_escape_string($conn, $_POST['kegiatan']);

    // Cek duplikat tanggal
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM jurnal_harian WHERE siswa_id=$siswa_id AND tanggal='$tanggal'"));
    if ($cek) {
        setFlash('error', 'Jurnal untuk tanggal ini sudah ada.');
    } else {
        // Upload foto (opsional)
        $foto = NULL;
        if (!empty($_FILES['foto_kegiatan']['name'])) {
            $ext     = pathinfo($_FILES['foto_kegiatan']['name'], PATHINFO_EXTENSION);
            $nama    = "jurnal_{$siswa_id}_" . time() . ".$ext";
            $target  = "../assets/uploads/$nama";
            if (move_uploaded_file($_FILES['foto_kegiatan']['tmp_name'], $target)) {
                $foto = $nama;
            }
        }
        $foto_sql = $foto ? "'$foto'" : "NULL";
        mysqli_query($conn,
            "INSERT INTO jurnal_harian (siswa_id, tanggal, jam_masuk, jam_keluar, kegiatan, foto_kegiatan)
             VALUES ($siswa_id, '$tanggal', '$jam_masuk', '$jam_keluar', '$kegiatan', $foto_sql)");
        setFlash('success', 'Jurnal berhasil disimpan!');
    }
    redirect('siswa-jurnal.php');
}

// ---- Hapus Jurnal (hanya pending) ----
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM jurnal_harian WHERE id=$id AND siswa_id=$siswa_id AND status_validasi='pending'"));
    if ($cek) {
        mysqli_query($conn, "DELETE FROM jurnal_harian WHERE id=$id");
        setFlash('success', 'Jurnal berhasil dihapus.');
    } else {
        setFlash('error', 'Tidak dapat menghapus jurnal yang sudah divalidasi.');
    }
    redirect('siswa-jurnal.php');
}

// ---- Data jurnal ----
$result = mysqli_query($conn,
    "SELECT * FROM jurnal_harian WHERE siswa_id=$siswa_id ORDER BY tanggal DESC");

include '_header_siswa.php';
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h2><i class="fas fa-book-open"></i> Jurnal Harian PKL</h2>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalJurnal').classList.add('active')">
        <i class="fas fa-plus"></i> Isi Jurnal
    </button>
</div>

<?php getFlash(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Kegiatan</th>
                    <th>Status</th>
                    <th>Komentar</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            $status_pill = [
                'pending' => 'pill-yellow',
                'valid'   => 'pill-green',
                'tolak'   => 'pill-red'
            ];
            if ($result && mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td style="white-space:nowrap;"><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
                    <td><?= $row['jam_masuk'] ? substr($row['jam_masuk'],0,5) : '-'; ?></td>
                    <td><?= $row['jam_keluar'] ? substr($row['jam_keluar'],0,5) : '-'; ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($row['kegiatan'], 0, 70, '...')); ?></td>
                    <td>
                        <span class="pill <?= $status_pill[$row['status_validasi']]; ?>">
                            <?= ucfirst($row['status_validasi']); ?>
                        </span>
                    </td>
                    <td style="font-size:.78rem;color:#94a3b8;">
                        <?= $row['komentar_pembimbing'] ? htmlspecialchars(mb_strimwidth($row['komentar_pembimbing'],0,40,'...')) : '-'; ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($row['status_validasi'] === 'pending'): ?>
                        <a href="siswa-jurnal.php?hapus=<?= $row['id']; ?>"
                           onclick="return confirm('Hapus jurnal ini?')"
                           style="color:#ef4444;font-size:.78rem;text-decoration:none;">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                        <?php else: ?>
                        <span style="color:#334155;font-size:.75rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:30px;color:#64748b;">
                        Belum ada jurnal. Mulai mengisi jurnal hari ini!
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Jurnal -->
<div class="modal-overlay" id="modalJurnal">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <h3><i class="fas fa-book-open" style="color:#c4b5fd;"></i> Isi Jurnal Harian</h3>
            <button class="modal-close" onclick="document.getElementById('modalJurnal').classList.remove('active')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi_simpan" value="1">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Jam Masuk</label>
                    <input type="time" name="jam_masuk" class="form-control" value="07:00">
                </div>
                <div class="form-group">
                    <label>Jam Keluar</label>
                    <input type="time" name="jam_keluar" class="form-control" value="16:00">
                </div>
            </div>
            <div class="form-group">
                <label>Kegiatan Hari Ini</label>
                <textarea name="kegiatan" class="form-control" rows="5" placeholder="Deskripsikan kegiatan yang dilakukan hari ini..." required></textarea>
            </div>
            <div class="form-group">
                <label>Foto Kegiatan (opsional)</label>
                <input type="file" name="foto_kegiatan" class="form-control" accept="image/*">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <button type="button" class="btn btn-secondary btn-sm"
                    onclick="document.getElementById('modalJurnal').classList.remove('active')">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save"></i> Simpan Jurnal
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Buka modal langsung jika ada parameter ?aksi=tambah
if (isset($_GET['aksi']) && $_GET['aksi'] === 'tambah'):
?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('modalJurnal').classList.add('active');
});
</script>
<?php endif; ?>

<?php include '_footer_siswa.php'; ?>
