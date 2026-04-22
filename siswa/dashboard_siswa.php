<?php
session_start();
include "../config.php";
requireSiswa(); 

// 1. Pastikan ID adalah angka (integer) untuk mencegah "Array to string conversion"
$siswa_id = (int) $_SESSION['user']['id_user']; 

// 2. Gunakan pengecekan manual untuk melihat error SQL jika query gagal
$query_profil = "SELECT u.nama_depan, u.nama_belakang, u.email,
            ps.nis, ps.kelas, ps.jurusan, ps.foto_profil, ps.no_hp
     FROM users u
     LEFT JOIN profil_siswa ps ON ps.user_id = u.id
     WHERE u.id = $siswa_id";

$result_profil = mysqli_query($conn, $query_profil);

// Cek jika query gagal
if (!$result_profil) {
    die("Query Error: " . mysqli_error($conn));
}

$profil = mysqli_fetch_assoc($result_profil);
// ---- Status PKL (cek apakah sudah diajukan & disetujui) ----
$pkl = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.*, m.nama_perusahaan AS nama_mitra,
            CONCAT(pb.nama_depan,' ',pb.nama_belakang) AS nama_pembimbing
     FROM pkl_pengajuan p
     JOIN pkl_anggota a ON a.pengajuan_id = p.id
     LEFT JOIN mitra_industri m ON m.nama_perusahaan = p.nama_perusahaan
     LEFT JOIN users pb ON pb.id = p.pembimbing_id
     WHERE a.siswa_id = $siswa_id
     ORDER BY p.id DESC LIMIT 1"));

// ---- Statistik Jurnal ----
$total_jurnal = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM jurnal_harian WHERE siswa_id = $siswa_id"))['c'];

$jurnal_valid = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM jurnal_harian WHERE siswa_id = $siswa_id AND status_validasi='valid'"))['c'];

$jurnal_pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM jurnal_harian WHERE siswa_id = $siswa_id AND status_validasi='pending'"))['c'];

// ---- Statistik Laporan ----
$total_laporan = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM laporan_pkl WHERE siswa_id = $siswa_id"))['c'];

$laporan_disetujui = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM laporan_pkl WHERE siswa_id = $siswa_id AND status_pembimbing='disetujui'"))['c'];

// ---- Nilai PKL ----
// $nilai = mysqli_fetch_assoc(mysqli_query($conn,
//     "SELECT * FROM nilai_pkl WHERE siswa_id = $siswa_id ORDER BY id DESC LIMIT 1"));

// ---- Jurnal Terbaru (5) ----
$jurnal_terbaru = mysqli_query($conn,
    "SELECT * FROM jurnal_harian WHERE siswa_id = $siswa_id ORDER BY tanggal DESC LIMIT 5");

// ---- Laporan Terbaru (5) ----
$laporan_terbaru = mysqli_query($conn,
    "SELECT * FROM laporan_pkl WHERE siswa_id = $siswa_id ORDER BY created_at DESC LIMIT 5");

// ---- Hitung hari PKL (dari jurnal) ----
$hari_pkl = $total_jurnal; // setiap entry jurnal = 1 hari kerja

$active_page = 'dashboard';
$page_title  = 'Dashboard Siswa';
include '_header_siswa.php';
?>

<!-- Welcome Banner -->
<div class="welcome-section delay-1">
    <div>
        <h1>Halo, <?php echo htmlspecialchars($profil['nama_depan']); ?> 👋</h1>
        <p>
            <?php if ($profil['kelas'] && $profil['jurusan']): ?>
                Kelas <?php echo htmlspecialchars($profil['kelas']); ?> &bull;
                <?php echo htmlspecialchars($profil['jurusan']); ?>
            <?php else: ?>
                Selamat datang di SIMPKL — Sistem Informasi PKL
            <?php endif; ?>
        </p>
    </div>
    <div class="status-badge">
        <div class="status-dot"></div>
        <?php
        if ($pkl && $pkl['status_pembimbing'] === 'disetujui' && $pkl['status_wakasek'] === 'disetujui') {
            echo 'PKL Aktif';
        } elseif ($pkl && $pkl['status_pembimbing'] === 'pending') {
            echo 'Menunggu Persetujuan';
        } else {
            echo 'Belum Mengajukan PKL';
        }
        ?>
    </div>
</div>

<!-- Info PKL Aktif -->
<?php if ($pkl && $pkl['status_pembimbing'] === 'disetujui' && $pkl['status_wakasek'] === 'disetujui'): ?>
<div class="card delay-2" style="margin-bottom:24px; padding:20px 24px; background:rgba(74,222,128,0.05); border-color:rgba(74,222,128,0.2);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:46px;height:46px;border-radius:12px;background:rgba(74,222,128,0.1);display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-building" style="color:#4ade80; font-size:1.1rem;"></i>
            </div>
            <div>
                <div style="font-size:.72rem;color:#64748b;font-weight:600;letter-spacing:.5px;text-transform:uppercase;">Tempat PKL</div>
                <div style="font-size:1rem;font-weight:700;color:#f1f5f9;"><?php echo htmlspecialchars($pkl['nama_perusahaan']); ?></div>
                <div style="font-size:.78rem;color:#94a3b8;"><?php echo htmlspecialchars($pkl['alamat_perusahaan']); ?></div>
            </div>
        </div>
        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <div style="text-align:center;">
                <div style="font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Pembimbing</div>
                <div style="font-size:.85rem;font-weight:600;color:#e2e8f0;"><?php echo htmlspecialchars($pkl['nama_pembimbing'] ?? '-'); ?></div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Mulai</div>
                <div style="font-size:.85rem;font-weight:600;color:#e2e8f0;"><?php echo date('d M Y', strtotime($pkl['tanggal_pengajuan'])); ?></div>
            </div>
        </div>
    </div>
</div>
<?php elseif (!$pkl): ?>
<!-- Belum mengajukan PKL -->
<div class="card delay-2" style="margin-bottom:24px; padding:20px 24px; background:rgba(245,158,11,0.05); border-color:rgba(245,158,11,0.2);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:12px;">
            <i class="fas fa-exclamation-triangle" style="color:#f59e0b; font-size:1.2rem;"></i>
            <div>
                <div style="font-size:.9rem;font-weight:600;color:#f59e0b;">Belum Mengajukan PKL</div>
                <div style="font-size:.8rem;color:#94a3b8;">Segera ajukan PKL ke perusahaan mitra untuk memulai.</div>
            </div>
        </div>
        <a href="siswa-pengajuan.php" class="btn btn-warning btn-sm">
            <i class="fas fa-file-signature"></i> Ajukan Sekarang
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="section-title">Statistik Saya</div>
<div class="stat-grid">

    <div class="stat-card delay-1">
        <div class="card-icon">
            <i class="fas fa-book-open" style="color:#c4b5fd;"></i>
        </div>
        <h3>Total Jurnal</h3>
        <div class="number" style="color:#c4b5fd;"><?php echo $total_jurnal; ?></div>
        <div class="trend"><?php echo $jurnal_valid; ?> tervalidasi</div>
    </div>

    <div class="stat-card delay-2">
        <div class="card-icon">
            <i class="fas fa-clock" style="color:#f59e0b;"></i>
        </div>
        <h3>Pending Validasi</h3>
        <div class="number" style="color:#f59e0b;"><?php echo $jurnal_pending; ?></div>
        <div class="trend">Menunggu pembimbing</div>
    </div>

    <div class="stat-card delay-3">
        <div class="card-icon">
            <i class="fas fa-file-alt" style="color:#4ade80;"></i>
        </div>
        <h3>Laporan Dikirim</h3>
        <div class="number" style="color:#4ade80;"><?php echo $total_laporan; ?></div>
        <div class="trend"><?php echo $laporan_disetujui; ?> disetujui</div>
    </div>

    <!-- <div class="stat-card delay-4">
        <div class="card-icon">
            <i class="fas fa-star" style="color:#fbbf24;"></i>
        </div>
        <h3>Nilai Akhir</h3>
        <?php if ($nilai): ?>
        <div class="number" style="color:#fbbf24;"><?php echo $nilai['nilai_akhir'] ?? '-'; ?></div>
        <div class="trend">Predikat: <?php echo $nilai['predikat'] ?? '-'; ?></div>
        <?php else: ?>
        <div class="number" style="color:#475569;">—</div>
        <div class="trend">Belum dinilai</div>
        <?php endif; ?>
    </div> -->

    <div class="stat-card delay-5">
        <div class="card-icon">
            <i class="fas fa-calendar-check" style="color:#93c5fd;"></i>
        </div>
        <h3>Hari PKL</h3>
        <div class="number" style="color:#93c5fd;"><?php echo $hari_pkl; ?></div>
        <div class="trend">Hari kerja tercatat</div>
    </div>

    <div class="stat-card delay-6">
        <div class="card-icon">
            <i class="fas fa-percentage" style="color:#f1f5f9;"></i>
        </div>
        <h3>Progress Jurnal</h3>
        <?php
        $target = 80; // target hari PKL (bisa disesuaikan)
        $pct = $target > 0 ? min(100, round(($hari_pkl / $target) * 100)) : 0;
        ?>
        <div class="number"><?php echo $pct; ?>%</div>
        <div class="trend">dari target <?php echo $target; ?> hari</div>
    </div>

</div>

<!-- Quick Actions -->
<div class="section-title delay-2">Aksi Cepat</div>
<div class="quick-grid">
    <a href="siswa-jurnal.php?aksi=tambah" class="quick-card delay-1">
        <i class="fas fa-plus-circle"></i> Isi Jurnal Hari Ini
    </a>
    <a href="siswa-laporan.php?aksi=upload" class="quick-card delay-2">
        <i class="fas fa-upload"></i> Upload Laporan
    </a>
    <a href="siswa-jurnal.php" class="quick-card delay-3">
        <i class="fas fa-book-open"></i> Riwayat Jurnal
    </a>
    <a href="siswa-laporan.php" class="quick-card delay-4">
        <i class="fas fa-file-alt"></i> Laporan Saya
    </a>
    <!-- <a href="siswa-nilai.php" class="quick-card delay-5">
        <i class="fas fa-star"></i> Lihat Nilai
    </a> -->
    <a href="siswa-profil.php" class="quick-card delay-6">
        <i class="fas fa-user-edit"></i> Edit Profil
    </a>
</div>

<!-- Panel Bawah: Jurnal Terbaru + Laporan Terbaru -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:24px;">

    <!-- Jurnal Terbaru -->
    <div class="panel delay-3">
        <div class="panel-header">
            <h3><i class="fas fa-book-open" style="color:#c4b5fd;"></i> Jurnal Terbaru</h3>
            <a href="siswa-jurnal.php">Lihat semua</a>
        </div>
        <?php
        if (mysqli_num_rows($jurnal_terbaru) === 0): ?>
            <p style="text-align:center;padding:20px 0;color:#64748b;font-size:.82rem;">Belum ada jurnal.</p>
        <?php else:
            $status_color = ['pending'=>'#f59e0b','valid'=>'#4ade80','tolak'=>'#ef4444'];
            while ($j = mysqli_fetch_assoc($jurnal_terbaru)): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-book" style="color:#c4b5fd;"></i>
                </div>
                <div class="activity-text" style="flex:1;">
                    <p><?php echo htmlspecialchars(mb_strimwidth($j['kegiatan'], 0, 55, '...')); ?></p>
                    <span>
                        <?php echo date('d M Y', strtotime($j['tanggal'])); ?>
                        <?php if ($j['jam_masuk']): ?>
                            &bull; <?php echo substr($j['jam_masuk'],0,5); ?>–<?php echo substr($j['jam_keluar'],0,5); ?>
                        <?php endif; ?>
                        &bull; <span style="color:<?php echo $status_color[$j['status_validasi']]; ?>;">
                            <?php echo ucfirst($j['status_validasi']); ?>
                        </span>
                    </span>
                    <?php if ($j['komentar_pembimbing'] && $j['status_validasi'] === 'tolak'): ?>
                        <span style="color:#ef4444; font-size:.72rem; display:block; margin-top:2px;">
                            <i class="fas fa-comment-alt"></i>
                            <?php echo htmlspecialchars(mb_strimwidth($j['komentar_pembimbing'],0,50,'...')); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; endif; ?>
    </div>

    <!-- Laporan Terbaru -->
    <div class="panel delay-4">
        <div class="panel-header">
            <h3><i class="fas fa-file-alt" style="color:#4ade80;"></i> Laporan Saya</h3>
            <a href="siswa-laporan.php">Lihat semua</a>
        </div>
        <?php
        if (mysqli_num_rows($laporan_terbaru) === 0): ?>
            <p style="text-align:center;padding:20px 0;color:#64748b;font-size:.82rem;">Belum ada laporan.</p>
        <?php else: while ($l = mysqli_fetch_assoc($laporan_terbaru)): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-file-alt" style="color:#4ade80;"></i>
                </div>
                <div class="activity-text" style="flex:1;">
                    <p style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        <?php echo htmlspecialchars($l['judul_laporan'] ?? '(Tanpa judul)'); ?>
                        <span style="background:rgba(147,197,253,0.1);color:#93c5fd;border:1px solid rgba(147,197,253,0.2);padding:1px 7px;border-radius:99px;font-size:.68rem;">
                            <?php echo ucfirst($l['jenis_laporan']); ?>
                        </span>
                    </p>
                    <span>
                        <?php echo date('d M Y', strtotime($l['created_at'])); ?> &bull;
                        <?php
                        $sc = ['pending'=>'#f59e0b','revisi'=>'#ef4444','disetujui'=>'#4ade80'];
                        $sv = $l['status_pembimbing'];
                        echo "<span style='color:{$sc[$sv]};'>".ucfirst($sv)."</span>";
                        ?>
                    </span>
                    <?php if ($l['catatan_revisi']): ?>
                        <span style="color:#ef4444;font-size:.72rem;display:block;margin-top:2px;">
                            <i class="fas fa-redo"></i>
                            <?php echo htmlspecialchars(mb_strimwidth($l['catatan_revisi'],0,50,'...')); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; endif; ?>
    </div>

</div>

<!-- Progress PKL -->
<?php if ($nilai): ?>
<div class="panel delay-5" style="margin-top:24px; margin-bottom:0;">
    <div class="panel-header">
        <h3><i class="fas fa-chart-bar" style="color:#fbbf24;"></i> Rincian Nilai PKL</h3>
        <a href="siswa-nilai.php">Detail</a>
    </div>
    <div class="status-list">
        <div class="status-row">
            <span>Sikap</span>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?php echo $nilai['nilai_sikap']; ?>%;background:#c4b5fd;"></div>
            </div>
            <div class="val"><?php echo $nilai['nilai_sikap']; ?>/100</div>
        </div>
        <div class="status-row">
            <span>Keterampilan</span>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?php echo $nilai['nilai_keterampilan']; ?>%;background:#4ade80;"></div>
            </div>
            <div class="val"><?php echo $nilai['nilai_keterampilan']; ?>/100</div>
        </div>
        <div class="status-row">
            <span>Laporan</span>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?php echo $nilai['nilai_laporan']; ?>%;background:#93c5fd;"></div>
            </div>
            <div class="val"><?php echo $nilai['nilai_laporan']; ?>/100</div>
        </div>
        <div class="status-row">
            <span>Nilai Akhir</span>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?php echo $nilai['nilai_akhir']; ?>%;background:#fbbf24;"></div>
            </div>
            <div class="val"><strong style="color:#fbbf24;"><?php echo $nilai['nilai_akhir']; ?></strong> — <?php echo $nilai['predikat']; ?></div>
        </div>
    </div>
    <?php if ($nilai['catatan']): ?>
    <p style="margin-top:12px;padding:10px 14px;background:rgba(255,255,255,0.03);border-radius:8px;font-size:.8rem;">
        <i class="fas fa-comment-dots" style="color:#94a3b8;margin-right:6px;"></i>
        <?php echo htmlspecialchars($nilai['catatan']); ?>
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include '_footer_siswa.php'; ?>
