<?php
session_start();
include "../config.php";
requireSiswa();

$siswa_id = (int) $_SESSION['user']['id_user'];
$active_page = 'absensi';
$page_title  = 'Rekap Absensi';

// Ambil data absensi dari database
$query_absensi = "SELECT * FROM absensi WHERE siswa_id = $siswa_id ORDER BY tanggal DESC";
$result_absensi = mysqli_query($conn, $query_absensi);

include '_header_siswa.php';
?>

<div class="page-header" style="margin-bottom:20px;">
    <h2><i class="fas fa-calendar-check"></i> Kehadiran Saya</h2>
</div>

<?php getFlash(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result_absensi) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_absensi)): 
                        $status_color = ($row['status'] == 'Hadir') ? '#4ade80' : (($row['status'] == 'Izin') ? '#fbbf24' : '#ef4444');
                    ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
                            <td><?= $row['jam_masuk'] ?? '-'; ?></td>
                            <td><?= $row['jam_pulang'] ?? '-'; ?></td>
                            <td>
                                <span class="badge" style="background: <?= $status_color; ?>22; color: <?= $status_color; ?>; border: 1px solid <?= $status_color; ?>44;">
                                    <?= $row['status']; ?>
                                </span>
                            </td>
                            <td style="font-size: 0.85rem; color: #94a3b8;"><?= $row['keterangan'] ?? '-'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 30px; color: #64748b;">Belum ada data absensi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '_footer_siswa.php'; ?>