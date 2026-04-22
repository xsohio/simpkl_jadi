<?php
session_start();
include "../config.php";
requireSiswa();

$siswa_id = (int) $_SESSION['user']['id_user'];
$active_page = 'log'; // Pastikan sesuaikan di sidebar jika ingin highlight
$page_title  = 'Log Aktivitas';

// Ambil log aktivitas
$query_log = "SELECT * FROM log_aktivitas WHERE id_users = $siswa_id ORDER BY waktu DESC LIMIT 50";
$result_log = mysqli_query($conn, $query_log);


include '_header_siswa.php';
?>

<div class="page-header" style="margin-bottom:20px;">
    <h2><i class="fas fa-history"></i> Riwayat Aktivitas</h2>
    <p style="color: #94a3b8; font-size: 0.9rem;">Menampilkan aktivitas terbaru akun Anda</p>
</div>

<div class="card">
    <div style="display: flex; flex-direction: column; gap: 5px;">
        <?php if (mysqli_num_rows($result_log) > 0): ?>
            <?php while ($log = mysqli_fetch_assoc($result_log)): ?>
                <div class="activity-item" style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 15px;">
                    <div class="activity-icon" style="background: rgba(147, 197, 253, 0.1); color: #93c5fd; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div class="activity-text">
                        <p style="margin: 0; font-size: 0.9rem; color: #e2e8f0;"><?= htmlspecialchars($log['aktivitas']); ?></p>
                        <span style="font-size: 0.75rem; color: #64748b;">
                            <i class="far fa-clock" style="margin-right: 4px;"></i><?= date('d M Y, H:i', strtotime($log['waktu'])); ?> WIB
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; padding: 40px; color: #64748b;">
                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                Belum ada catatan aktivitas.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '_footer_siswa.php'; ?>