<?php
session_start();
include "../config.php";
requireSiswa();

$siswa_id = $_SESSION['user']['id_user'];
$active_page = 'profil';
$page_title  = 'Profil Saya';


// ---- Proses Update Profil ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_depan   = mysqli_real_escape_string($conn, $_POST['nama_depan']);
    $nama_belakang= mysqli_real_escape_string($conn, $_POST['nama_belakang']);
    $nis          = mysqli_real_escape_string($conn, $_POST['nis']);
    $kelas        = mysqli_real_escape_string($conn, $_POST['kelas']);
    $jurusan      = mysqli_real_escape_string($conn, $_POST['jurusan']);
    $no_hp        = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $jk           = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $agama        = mysqli_real_escape_string($conn, $_POST['agama']);
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tgl_lahir    = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $alamat       = mysqli_real_escape_string($conn, $_POST['alamat']);

    // Update users
    mysqli_query($conn,
        "UPDATE users SET nama_depan='$nama_depan', nama_belakang='$nama_belakang' WHERE id=$siswa_id");

    // Cek profil_siswa exist
    $ps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM profil_siswa WHERE user_id=$siswa_id"));

    // Upload foto
    $foto_sql = '';
    if (!empty($_FILES['foto_profil']['name'])) {
        $ext  = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $nama = "SISWA_{$siswa_id}_" . time() . ".$ext";
        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], "../assets/uploads/$nama")) {
            $foto_sql = ", foto_profil='$nama'";
        }
    }

    if ($ps) {
        mysqli_query($conn,
            "UPDATE profil_siswa SET
                nis='$nis', kelas='$kelas', jurusan='$jurusan', no_hp='$no_hp',
                jenis_kelamin='$jk', agama='$agama', tempat_lahir='$tempat_lahir',
                tanggal_lahir='$tgl_lahir', alamat='$alamat'
                $foto_sql
             WHERE user_id=$siswa_id");
    } else {
        mysqli_query($conn,
            "INSERT INTO profil_siswa (user_id, nis, kelas, jurusan, no_hp, jenis_kelamin, agama, tempat_lahir, tanggal_lahir, alamat)
             VALUES ($siswa_id, '$nis', '$kelas', '$jurusan', '$no_hp', '$jk', '$agama', '$tempat_lahir', '$tgl_lahir', '$alamat')");
    }

    setFlash('success', 'Profil berhasil diperbarui!');
    redirect('siswa-profil.php');
}

// ---- Ambil data profil ----
$data = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT u.nama_depan, u.nama_belakang, u.email,
            ps.nis, ps.kelas, ps.jurusan, ps.no_hp, ps.foto_profil,
            ps.jenis_kelamin, ps.agama, ps.tempat_lahir, ps.tanggal_lahir,
            ps.alamat, ps.golongan_darah
     FROM users u
     LEFT JOIN profil_siswa ps ON ps.user_id = u.id
     WHERE u.id = $siswa_id"));

include '_header_siswa.php';
?>

<div class="page-header" style="margin-bottom:24px;">
    <h2><i class="fas fa-user-edit"></i> Profil Saya</h2>
</div>

<?php getFlash(); ?>

<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start;">

    <!-- Foto & Info Singkat -->
    <div class="card" style="text-align:center;">
        <?php $foto = $data['foto_profil'] ?? null; ?>
        <?php if ($foto): ?>
        <img src="../assets/uploads/<?= htmlspecialchars($foto); ?>" alt="Foto Profil"
             style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,0.15);margin:0 auto 16px;">
        <?php else: ?>
        <div style="width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,0.1);
                    border:3px solid rgba(255,255,255,0.15);display:flex;align-items:center;
                    justify-content:center;margin:0 auto 16px;font-size:2rem;font-weight:700;color:#f1f5f9;">
            <?= strtoupper(substr($data['nama_depan'],0,1)); ?>
        </div>
        <?php endif; ?>

        <div style="font-size:1rem;font-weight:700;color:#f1f5f9;">
            <?= htmlspecialchars($data['nama_depan'].' '.$data['nama_belakang']); ?>
        </div>
        <div style="font-size:.78rem;color:#64748b;margin-top:4px;"><?= htmlspecialchars($data['email']); ?></div>

        <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px;text-align:left;">
            <?php
            $info_list = [
                ['fas fa-id-card','NIS', $data['nis'] ?? '-'],
                ['fas fa-school','Kelas', ($data['kelas'] ?? '-') . ' ' . ($data['jurusan'] ?? '')],
                ['fas fa-phone','No. HP', $data['no_hp'] ?? '-'],
            ];
            foreach ($info_list as [$icon, $label, $val]): ?>
            <div style="display:flex;align-items:center;gap:8px;padding:8px;background:rgba(255,255,255,0.03);border-radius:8px;">
                <i class="<?= $icon; ?>" style="color:#64748b;width:16px;text-align:center;font-size:.8rem;"></i>
                <div>
                    <div style="font-size:.65rem;color:#475569;text-transform:uppercase;letter-spacing:.5px;"><?= $label; ?></div>
                    <div style="font-size:.82rem;color:#cbd5e1;font-weight:500;"><?= htmlspecialchars($val); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Form Edit -->
    <div class="card">
        <h3 style="margin-bottom:20px;color:#e2e8f0;">Edit Informasi Profil</h3>
        <form method="POST" enctype="multipart/form-data">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Nama Depan</label>
                    <input type="text" name="nama_depan" class="form-control"
                           value="<?= htmlspecialchars($data['nama_depan']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Nama Belakang</label>
                    <input type="text" name="nama_belakang" class="form-control"
                           value="<?= htmlspecialchars($data['nama_belakang']); ?>" required>
                </div>
                <div class="form-group">
                    <label>NIS</label>
                    <input type="text" name="nis" class="form-control"
                           value="<?= htmlspecialchars($data['nis'] ?? ''); ?>" placeholder="Nomor Induk Siswa">
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <input type="text" name="kelas" class="form-control"
                           value="<?= htmlspecialchars($data['kelas'] ?? ''); ?>" placeholder="cth: XI">
                </div>
                <div class="form-group">
                    <label>Jurusan</label>
                    <input type="text" name="jurusan" class="form-control"
                           value="<?= htmlspecialchars($data['jurusan'] ?? ''); ?>" placeholder="cth: RPL, TKJ">
                </div>
                <div class="form-group">
                    <label>No. HP / WA</label>
                    <input type="text" name="no_hp" class="form-control"
                           value="<?= htmlspecialchars($data['no_hp'] ?? ''); ?>" placeholder="08xxxxxxxxxx">
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-control">
                        <option value="">— Pilih —</option>
                        <option value="Laki-laki" <?= ($data['jenis_kelamin']==='Laki-laki')?'selected':''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?= ($data['jenis_kelamin']==='Perempuan')?'selected':''; ?>>Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Agama</label>
                    <select name="agama" class="form-control">
                        <?php
                        $agamas = ['Islam','Kristen Protestan','Kristen Katolik','Hindu','Buddha','Konghucu'];
                        foreach ($agamas as $ag):
                        ?>
                        <option value="<?= $ag; ?>" <?= ($data['agama']===$ag)?'selected':''; ?>><?= $ag; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" class="form-control"
                           value="<?= htmlspecialchars($data['tempat_lahir'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" class="form-control"
                           value="<?= $data['tanggal_lahir'] ?? ''; ?>">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Alamat Lengkap</label>
                    <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($data['alamat'] ?? ''); ?></textarea>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Foto Profil (opsional)</label>
                    <input type="file" name="foto_profil" class="form-control" accept="image/*">
                    <?php if ($data['foto_profil']): ?>
                    <small style="color:#475569;font-size:.75rem;margin-top:4px;display:block;">
                        Kosongkan jika tidak ingin mengganti foto.
                    </small>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top:4px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '_footer_siswa.php'; ?>
