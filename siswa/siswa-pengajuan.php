<?php
session_start();
include "../config.php";
requireSiswa();

if (!isset($_SESSION['user']['id_user'])) {
    // Jika tidak ada, paksa login ulang
    header("Location: ../login.php?pesan=session_habis");
    exit();
}

$siswa_id    = $_SESSION['user']['id_user'];
$active_page = 'pengajuan';
$page_title  = 'Pengajuan PKL';

// ---- Cek apakah sudah punya pengajuan ----
$existing = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.*, CONCAT(pb.nama_depan,' ',pb.nama_belakang) AS nama_pembimbing
     FROM pkl_pengajuan p
     JOIN pkl_anggota a ON a.pengajuan_id = p.id
     LEFT JOIN users pb ON pb.id = p.pembimbing_id
     WHERE a.siswa_id = $siswa_id
     ORDER BY p.id DESC LIMIT 1"));

// ---- Proses Pengajuan Baru ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    $nama_perusahaan  = mysqli_real_escape_string($conn, $_POST['nama_perusahaan']);
    $alamat_perusahaan= mysqli_real_escape_string($conn, $_POST['alamat_perusahaan']);
    $website          = mysqli_real_escape_string($conn, $_POST['website'] ?? '');

    $file_dok = NULL;
    if (!empty($_FILES['file_dokumen']['name'])) {
        $ext    = pathinfo($_FILES['file_dokumen']['name'], PATHINFO_EXTENSION);
        $nama   = time() . "_dok.$ext";
        $target = "../assets/uploads/$nama";
        if (move_uploaded_file($_FILES['file_dokumen']['tmp_name'], $target)) {
            $file_dok = "../assets/uploads/$nama";
        }
    }
    $fd_sql = $file_dok ? "'$file_dok'" : "NULL";
    $ws_sql = $website ? "'$website'" : "NULL";

    mysqli_query($conn,
        "INSERT INTO pkl_pengajuan (ketua_id, nama_perusahaan, alamat_perusahaan, website, file_dokumen)
         VALUES ($siswa_id, '$nama_perusahaan', '$alamat_perusahaan', $ws_sql, $fd_sql)");
    $pid = mysqli_insert_id($conn);

    // Tambahkan ke pkl_anggota sebagai ketua
    mysqli_query($conn,
        "INSERT INTO pkl_anggota (pengajuan_id, siswa_id) VALUES ($pid, $siswa_id)");

    setFlash('success', 'Pengajuan PKL berhasil dikirim! Menunggu persetujuan pembimbing.');
    redirect('siswa-pengajuan.php');
}

include '_header_siswa.php';
?>

<div class="page-header" style="margin-bottom:20px;">
    <h2><i class="fas fa-file-signature"></i> Pengajuan PKL</h2>
</div>

<?php getFlash(); ?>

<?php if ($existing): ?>
<!-- Tampilkan status pengajuan -->
<?php
$badge_status = [
    'pending'  => ['label'=>'Menunggu','color'=>'#f59e0b','pill'=>'pill-yellow'],
    'disetujui'=> ['label'=>'Disetujui','color'=>'#4ade80','pill'=>'pill-green'],
    'ditolak'  => ['label'=>'Ditolak','color'=>'#ef4444','pill'=>'pill-red'],
];
$sp = $badge_status[$existing['status_pembimbing']];
$sw = $badge_status[$existing['status_wakasek']];
?>
<div class="card" style="margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <div style="width:44px;height:44px;border-radius:12px;background:rgba(147,197,253,0.1);display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-building" style="color:#93c5fd;font-size:1.1rem;"></i>
        </div>
        <div>
            <div style="font-size:1.05rem;font-weight:700;color:#f1f5f9;">
                <?= htmlspecialchars($existing['nama_perusahaan']); ?>
            </div>
            <div style="font-size:.78rem;color:#64748b;">
                <?= htmlspecialchars($existing['alamat_perusahaan']); ?>
                <?php if ($existing['website']): ?>
                  &bull; <a href="<?= htmlspecialchars($existing['website']); ?>" target="_blank" style="color:#93c5fd;"><?= htmlspecialchars($existing['website']); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
        <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px;">
            <div style="font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Status Pembimbing</div>
            <span class="pill <?= $sp['pill']; ?>"><?= $sp['label']; ?></span>
        </div>
        <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px;">
            <div style="font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Status Wakasek</div>
            <span class="pill <?= $sw['pill']; ?>"><?= $sw['label']; ?></span>
        </div>
        <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px;">
            <div style="font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Pembimbing</div>
            <div style="font-size:.85rem;font-weight:600;color:#e2e8f0;"><?= htmlspecialchars($existing['nama_pembimbing'] ?? 'Belum ditentukan'); ?></div>
        </div>
        <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px;">
            <div style="font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Tanggal Pengajuan</div>
            <div style="font-size:.85rem;font-weight:600;color:#e2e8f0;"><?= date('d M Y', strtotime($existing['tanggal_pengajuan'])); ?></div>
        </div>
    </div>

    <?php if ($existing['status_pembimbing'] === 'disetujui' && $existing['status_wakasek'] === 'disetujui'): ?>
    <div class="alert alert-success" style="margin-top:16px;margin-bottom:0;">
        <i class="fas fa-check-circle"></i>
        <strong>Selamat!</strong> Pengajuan PKL Anda telah disetujui. Selamat menjalankan PKL!
    </div>
    <?php elseif ($existing['status_pembimbing'] === 'ditolak' || $existing['status_wakasek'] === 'ditolak'): ?>
    <div class="alert alert-error" style="margin-top:16px;margin-bottom:0;">
        <i class="fas fa-times-circle"></i>
        Pengajuan PKL ditolak. Silakan hubungi pembimbing untuk informasi lebih lanjut.
    </div>
    <?php else: ?>
    <div class="alert alert-warning" style="margin-top:16px;margin-bottom:0;">
        <i class="fas fa-hourglass-half"></i>
        Pengajuan PKL sedang dalam proses persetujuan. Harap tunggu konfirmasi.
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Form Pengajuan Baru -->
<div class="card" style="max-width:640px;">
    <div style="margin-bottom:20px;">
        <h3 style="color:#f1f5f9;">Formulir Pengajuan PKL</h3>
        <p style="margin-top:4px;">Isi data perusahaan tempat Anda akan melaksanakan PKL.</p>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Nama Perusahaan / Instansi <span style="color:#ef4444;">*</span></label>
            <input type="text" name="nama_perusahaan" class="form-control" placeholder="cth: PT Teknologi Maju" required>
        </div>
        <div class="form-group">
            <label>Alamat Lengkap <span style="color:#ef4444;">*</span></label>
            <textarea name="alamat_perusahaan" class="form-control" rows="3" placeholder="Jl. Contoh No. 1, Kota, Provinsi" required></textarea>
        </div>
        <div class="form-group">
            <label>Website Perusahaan (opsional)</label>
            <input type="url" name="website" class="form-control" placeholder="https://contoh.com">
        </div>
        <div class="form-group">
            <label>Dokumen Pengajuan (Surat, PDF, dll)</label>
            <input type="file" name="file_dokumen" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            <small style="color:#475569;font-size:.75rem;margin-top:4px;display:block;">
                Format: PDF, Word, atau gambar. Maks 5MB.
            </small>
        </div>
        <div style="display:flex;gap:10px;margin-top:8px;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Kirim Pengajuan
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php include '_footer_siswa.php'; ?>
