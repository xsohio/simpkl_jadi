<?php
session_start();
include "../config.php";
requireSiswa();

$siswa_id = $_SESSION['user']['id_user'];
$active_page = 'nilai';
$page_title  = 'Nilai PKL';

$nilai = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT n.*, CONCAT(u.nama_depan,' ',u.nama_belakang) AS nama_pembimbing
     FROM nilai_pkl n
     LEFT JOIN users u ON u.id = n.pembimbing_id
     WHERE n.siswa_id = $siswa_id
     ORDER BY n.id DESC LIMIT 1"));

include '_header_siswa.php';
?>

<div class="page-header" style="margin-bottom:20px;">
    <h2><i class="fas fa-star"></i> Nilai PKL Saya</h2>
</div>

<?php getFlash(); ?>

<?php if (!$nilai): ?>
<div class="card" style="text-align:center;padding:48px 24px;">
    <i class="fas fa-star" style="font-size:2.5rem;color:#334155;margin-bottom:16px;display:block;"></i>
    <h3 style="color:#64748b;margin-bottom:8px;">Nilai Belum Tersedia</h3>
    <p>Nilai PKL akan ditampilkan setelah pembimbing menginputkan penilaian Anda.</p>
</div>
<?php else: ?>

<!-- Nilai Card Utama -->
<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;margin-bottom:24px;">

    <!-- Nilai Akhir -->
    <div class="card" style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:36px 24px;">
        <div style="font-size:.75rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">
            Nilai Akhir PKL
        </div>
        <div style="font-size:4rem;font-weight:700;line-height:1;color:#fbbf24;">
            <?= $nilai['nilai_akhir'] ?? '—'; ?>
        </div>
        <?php if ($nilai['predikat']): ?>
        <div style="margin-top:12px;">
            <span style="padding:6px 20px;border-radius:99px;font-size:.9rem;font-weight:700;
              background:rgba(251,191,36,0.1);color:#fbbf24;border:1px solid rgba(251,191,36,0.3);">
                Predikat <?= $nilai['predikat']; ?>
            </span>
        </div>
        <?php endif; ?>
        <div style="font-size:.78rem;color:#64748b;margin-top:16px;">
            Dinilai oleh: <?= htmlspecialchars($nilai['nama_pembimbing']); ?>
        </div>
    </div>

    <!-- Rincian Nilai -->
    <div class="card">
        <h3 style="margin-bottom:18px;color:#e2e8f0;">Rincian Komponen Nilai</h3>
        <div class="status-list">
            <div class="status-row">
                <span>Sikap & Perilaku</span>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $nilai['nilai_sikap']; ?>%;background:#c4b5fd;"></div>
                </div>
                <div class="val" style="color:#c4b5fd;font-weight:600;"><?= $nilai['nilai_sikap']; ?></div>
            </div>
            <div class="status-row">
                <span>Keterampilan</span>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $nilai['nilai_keterampilan']; ?>%;background:#4ade80;"></div>
                </div>
                <div class="val" style="color:#4ade80;font-weight:600;"><?= $nilai['nilai_keterampilan']; ?></div>
            </div>
            <div class="status-row">
                <span>Kualitas Laporan</span>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $nilai['nilai_laporan']; ?>%;background:#93c5fd;"></div>
                </div>
                <div class="val" style="color:#93c5fd;font-weight:600;"><?= $nilai['nilai_laporan']; ?></div>
            </div>
            <div class="status-row" style="padding-top:8px;border-top:1px solid rgba(255,255,255,0.06);margin-top:4px;">
                <span style="font-weight:700;color:#f1f5f9;">Nilai Akhir</span>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $nilai['nilai_akhir']; ?>%;background:#fbbf24;"></div>
                </div>
                <div class="val" style="color:#fbbf24;font-weight:700;"><?= $nilai['nilai_akhir']; ?></div>
            </div>
        </div>

        <?php
        // Konversi predikat
        $predikat_info = [
            'A'  => ['label'=>'Sangat Baik', 'color'=>'#4ade80'],
            'B'  => ['label'=>'Baik',        'color'=>'#93c5fd'],
            'C'  => ['label'=>'Cukup',       'color'=>'#f59e0b'],
            'D'  => ['label'=>'Kurang',      'color'=>'#ef4444'],
        ];
        $pi = $predikat_info[$nilai['predikat']] ?? null;
        if ($pi): ?>
        <div style="margin-top:18px;padding:12px;background:rgba(255,255,255,0.03);border-radius:10px;text-align:center;">
            <span style="color:<?= $pi['color']; ?>;font-size:.85rem;font-weight:600;">
                <i class="fas fa-award"></i> <?= $pi['label']; ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($nilai['catatan']): ?>
<!-- Catatan Pembimbing -->
<div class="card" style="background:rgba(147,197,253,0.04);border-color:rgba(147,197,253,0.15);">
    <h3 style="margin-bottom:12px;color:#93c5fd;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">
        <i class="fas fa-comment-dots"></i> Catatan Pembimbing
    </h3>
    <p style="color:#cbd5e1;line-height:1.7;font-size:.9rem;">
        <?= htmlspecialchars($nilai['catatan']); ?>
    </p>
</div>
<?php endif; ?>

<?php endif; ?>

<?php include '_footer_siswa.php'; ?>
