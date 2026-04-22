<?php
session_start();
include "../config.php";
requireAdmin();

// ============================================================
// log-aktivitas.php
// CATATAN: Database webpkl TIDAK memiliki tabel log_aktivitas.
// Halaman ini menampilkan "aktivitas tersirat" dari tabel yang ada:
// - Jurnal harian terbaru (jurnal_harian)
// - Laporan masuk terbaru (laporan_pkl)
// - Pengajuan PKL terbaru (pkl_pengajuan)
// - Registrasi user terbaru (users)
//
// Jika Anda ingin log aktivitas permanen, jalankan SQL di bawah
// untuk membuat tabelnya terlebih dahulu.
// ============================================================
// Query untuk mengambil log aktivitas beserta nama usernya
$query = "SELECT log_aktivitas.*, users.nama_depan, users.nama_belakang 
          FROM log_aktivitas 
          JOIN users ON log_aktivitas.id_users = users.id 
          ORDER BY log_aktivitas.waktu DESC";

$result = mysqli_query($conn, $query);

$active_page = 'log';
$page_title  = 'Log Aktivitas';
include '_header_admin.php';

// Cek apakah tabel log_aktivitas sudah dibuat
$tbl_exists = mysqli_query($conn, "SHOW TABLES LIKE 'log_aktivitas'");
$has_log_table = (mysqli_num_rows($tbl_exists) > 0);
?>

<div class="page-header">
    <h2><i class="fas fa-history"></i> Log Aktivitas Sistem</h2>
</div>

<?php getFlash(); ?>

<?php if (!$has_log_table): ?>
<!-- ============================================================
     Tabel log_aktivitas belum ada — tampilkan aktivitas dari tabel yang ada
     ============================================================ -->
<div class="card" style="margin-bottom:16px; padding:16px; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.3); border-radius:12px;">
    <p style="color:#f59e0b; margin:0; font-size:.85rem;">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Tabel <code>log_aktivitas</code> belum ada di database.</strong>
        Menampilkan aktivitas dari tabel yang tersedia. Untuk mengaktifkan log permanen,
        jalankan perintah SQL berikut di phpMyAdmin:
    </p>
    <pre style="background:rgba(0,0,0,0.3); padding:12px; border-radius:8px; font-size:.78rem; color:#94a3b8; margin-top:10px; overflow-x:auto;">
CREATE TABLE `log_aktivitas` (
  `id_log`    INT AUTO_INCREMENT PRIMARY KEY,
  `id_users`  INT DEFAULT NULL,
  `aktivitas` TEXT NOT NULL,
  `waktu`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_users`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
</div>

<!-- Aktivitas tersirat dari tabel jurnal_harian, laporan_pkl, pkl_pengajuan, users -->
<div class="card">
    <div style="padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.06); font-size:.85rem; color:#94a3b8;">
        <i class="fas fa-info-circle"></i> Menampilkan aktivitas terbaru dari data yang ada
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Waktu</th>
                    <th>Aktivitas</th>
                    <th>Pengguna</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Gabungkan aktivitas dari berbagai tabel menggunakan UNION
            $query_log = "
                SELECT 
                    j.tanggal AS waktu,
                    CONCAT('Mengisi jurnal harian: ', LEFT(j.kegiatan, 60), '...') AS aktivitas,
                    CONCAT(u.nama_depan, ' ', u.nama_belakang) AS pengguna
                FROM jurnal_harian j
                LEFT JOIN users u ON j.siswa_id = u.id

                UNION ALL

                SELECT
                    l.created_at AS waktu,
                    CONCAT('Upload laporan PKL [', l.jenis_laporan, ']: ', COALESCE(l.judul_laporan, '(Tanpa judul)')) AS aktivitas,
                    CONCAT(u.nama_depan, ' ', u.nama_belakang) AS pengguna
                FROM laporan_pkl l
                LEFT JOIN users u ON l.siswa_id = u.id

                UNION ALL

                SELECT
                    p.tanggal_pengajuan AS waktu,
                    CONCAT('Mengajukan PKL ke: ', p.nama_perusahaan) AS aktivitas,
                    CONCAT(u.nama_depan, ' ', u.nama_belakang) AS pengguna
                FROM pkl_pengajuan p
                LEFT JOIN users u ON p.ketua_id = u.id

                UNION ALL

                SELECT
                    u.created_at AS waktu,
                    CONCAT('Registrasi akun baru sebagai: ', u.role) AS aktivitas,
                    CONCAT(u.nama_depan, ' ', u.nama_belakang) AS pengguna
                FROM users u

                ORDER BY waktu DESC
                LIMIT 50
            ";

            $result = mysqli_query($conn, $query_log);
            $no = 1;
            if ($result && mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td style="white-space:nowrap;"><?= date('d M Y, H:i', strtotime($row['waktu'])); ?></td>
                    <td><?= htmlspecialchars($row['aktivitas']); ?></td>
                    <td><?= htmlspecialchars($row['pengguna'] ?? '-'); ?></td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:20px; color:#64748b;">
                        Belum ada aktivitas yang tercatat.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- ============================================================
     Tabel log_aktivitas ADA — tampilkan data log asli
     ============================================================ -->
<div class="page-header" style="margin-top:0; justify-content:flex-end;">
    <a href="log-aktivitas.php?bersihkan_semua=1"
       class="btn btn-danger btn-sm"
       onclick="return confirm('Apakah Anda yakin ingin menghapus SEMUA log?')">
        <i class="fas fa-trash-alt"></i> Bersihkan Semua Log
    </a>
</div>

<?php
// Proses hapus satu log
if (isset($_GET['hapus'])) {
    $id_log = (int)$_GET['hapus'];
    if (mysqli_query($conn, "DELETE FROM log_aktivitas WHERE id_log = $id_log")) {
        setFlash('success', 'Catatan log berhasil dihapus.');
    } else {
        setFlash('error', 'Gagal menghapus log.');
    }
    redirect('log-aktivitas.php');
}

// Bersihkan semua
if (isset($_GET['bersihkan_semua'])) {
    if (mysqli_query($conn, "TRUNCATE TABLE log_aktivitas")) {
        setFlash('success', 'Semua log telah dibersihkan.');
    }
    redirect('log-aktivitas.php');
}

$query = "SELECT la.*, u.nama_depan, u.nama_belakang
          FROM log_aktivitas la
          LEFT JOIN users u ON la.id_users = u.id
          ORDER BY la.waktu DESC";
$result = mysqli_query($conn, $query);
?>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aktivitas</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            if ($result && mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td style="white-space:nowrap;"><?= date('d M Y, H:i', strtotime($row['waktu'])); ?></td>
                    <td><?= htmlspecialchars(($row['nama_depan'] ?? '') . ' ' . ($row['nama_belakang'] ?? '') ?: 'System'); ?></td>
                    <td><?= htmlspecialchars($row['aktivitas']); ?></td>
                    <td style="text-align:center;">
                        <a href="log-aktivitas.php?hapus=<?= $row['id_log']; ?>"
                           onclick="return confirm('Hapus log ini?')"
                           style="color:#ef4444; font-size:.8rem; text-decoration:none;">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    </td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:20px; color:#64748b;">
                        Belum ada aktivitas yang tercatat.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include '_footer_admin.php'; ?>
