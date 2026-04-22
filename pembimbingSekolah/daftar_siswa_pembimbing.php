<?php
session_start();
include "../config.php";
requirePembimbing();

// ================================================================
//  AMBIL PID — handle berbagai kemungkinan key session
//  Cek: id_user → id → user_id (sesuai config.php masing-masing)
// ================================================================
$pid = (int)(
    $_SESSION['user']['id_user'] ??
    $_SESSION['user']['id']      ??
    $_SESSION['user']['user_id'] ??
    0
);

// ================================================================
//  SAFETY: jika $pid tidak ditemukan di tabel users sebagai
//  pembimbing, coba cari berdasarkan email/nama dari session
// ================================================================
if ($pid === 0) {
    die("<p style='color:red;padding:20px;'>Session tidak valid. Silakan login ulang.</p>");
}

// Verifikasi role user yang sedang login
$cek_role = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id, role, nama_depan, nama_belakang FROM users WHERE id = $pid"));

$active_page = 'siswa';
$page_title  = 'Daftar Siswa Bimbingan';

// ================================================================
//  HELPER log
// ================================================================
if (!function_exists('catat_log')) {
    function catat_log($conn, $id_user, $aktivitas) {
        $aktivitas = mysqli_real_escape_string($conn, $aktivitas);
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_users, aktivitas) VALUES ($id_user, '$aktivitas')");
    }
}

// ================================================================
//  CRUD — SETUJUI PENGAJUAN
// ================================================================
if (isset($_GET['setujui'])) {
    $idf = (int)$_GET['setujui'];
    // Izinkan jika pembimbing_id cocok ATAU jika belum ada pembimbing (assign otomatis)
    $pengajuan = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.id, p.pembimbing_id, u.nama_depan, u.nama_belakang
         FROM pkl_pengajuan p JOIN users u ON p.ketua_id=u.id
         WHERE p.id=$idf"));
    if ($pengajuan) {
        // Jika belum ada pembimbing, assign ke pembimbing yang login
        if (empty($pengajuan['pembimbing_id'])) {
            mysqli_query($conn, "UPDATE pkl_pengajuan SET pembimbing_id=$pid, status_pembimbing='disetujui' WHERE id=$idf");
        } else {
            mysqli_query($conn, "UPDATE pkl_pengajuan SET status_pembimbing='disetujui' WHERE id=$idf AND pembimbing_id=$pid");
        }
        catat_log($conn, $pid, "Menyetujui pengajuan PKL siswa {$pengajuan['nama_depan']} {$pengajuan['nama_belakang']}");
        setFlash('success', "<i class='fas fa-circle-check'></i> Pengajuan berhasil disetujui.");
    }
    header("Location: daftar_siswa_pembimbing.php"); exit;
}

// ================================================================
//  CRUD — TOLAK PENGAJUAN
// ================================================================
if (isset($_GET['tolak'])) {
    $idf = (int)$_GET['tolak'];
    $pengajuan = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.id, u.nama_depan, u.nama_belakang
         FROM pkl_pengajuan p JOIN users u ON p.ketua_id=u.id
         WHERE p.id=$idf"));
    if ($pengajuan) {
        mysqli_query($conn, "UPDATE pkl_pengajuan SET status_pembimbing='ditolak' WHERE id=$idf AND pembimbing_id=$pid");
        catat_log($conn, $pid, "Menolak pengajuan PKL siswa {$pengajuan['nama_depan']} {$pengajuan['nama_belakang']}");
        setFlash('warning', "<i class='fas fa-circle-xmark'></i> Pengajuan ditolak.");
    }
    header("Location: daftar_siswa_pembimbing.php"); exit;
}

// ================================================================
//  CRUD — LEPAS SISWA DARI BIMBINGAN
// ================================================================
if (isset($_GET['hapus'])) {
    $idf = (int)$_GET['hapus'];
    $info = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT u.nama_depan, u.nama_belakang FROM pkl_pengajuan p JOIN users u ON p.ketua_id=u.id
         WHERE p.id=$idf AND p.pembimbing_id=$pid"));
    if ($info) {
        mysqli_query($conn, "UPDATE pkl_pengajuan SET pembimbing_id=NULL, status_pembimbing='pending' WHERE id=$idf AND pembimbing_id=$pid");
        catat_log($conn, $pid, "Melepas bimbingan siswa {$info['nama_depan']} {$info['nama_belakang']}");
        setFlash('info', "<i class='fas fa-circle-info'></i> Siswa berhasil dilepas dari daftar bimbingan.");
    }
    header("Location: daftar_siswa_pembimbing.php"); exit;
}

// ================================================================
//  MODAL DETAIL SISWA
// ================================================================
$detail  = null;
$anggota = [];
if (isset($_GET['detail'])) {
    $idf    = (int)$_GET['detail'];
    $detail = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.id AS pengajuan_id, p.nama_perusahaan, p.alamat_perusahaan,
                p.status_pembimbing, p.status_wakasek, p.tanggal_pengajuan,
                u.id AS user_id, u.nama_depan, u.nama_belakang, u.email,
                ps.nis, ps.kelas, ps.jurusan, ps.no_hp, ps.foto_profil,
                ps.jenis_kelamin, ps.agama, ps.tempat_lahir, ps.tanggal_lahir,
                ps.alamat, ps.golongan_darah,
                (SELECT COUNT(*) FROM jurnal_harian j WHERE j.siswa_id=u.id) AS total_jurnal,
                (SELECT COUNT(*) FROM jurnal_harian j WHERE j.siswa_id=u.id AND j.status_validasi='valid') AS jurnal_valid,
                (SELECT COUNT(*) FROM jurnal_harian j WHERE j.siswa_id=u.id AND j.status_validasi='pending') AS jurnal_pending,
                (SELECT COUNT(*) FROM absensi a WHERE a.siswa_id=u.id AND a.status='Hadir') AS total_hadir,
                (SELECT COUNT(*) FROM laporan_pkl l WHERE l.siswa_id=u.id) AS total_laporan,
                (SELECT nilai_akhir FROM nilai_pkl np WHERE np.siswa_id=u.id LIMIT 1) AS nilai_akhir,
                (SELECT predikat   FROM nilai_pkl np WHERE np.siswa_id=u.id LIMIT 1) AS predikat
         FROM pkl_pengajuan p
         JOIN users u ON p.ketua_id = u.id
         LEFT JOIN profil_siswa ps ON ps.user_id = u.id
         WHERE p.id = $idf AND p.pembimbing_id = $pid
         LIMIT 1"));

    if ($detail) {
        $res_ang = mysqli_query($conn,
            "SELECT u.nama_depan, u.nama_belakang, u.email,
                    ps.nis, ps.kelas, ps.jurusan,
                    (SELECT COUNT(*) FROM jurnal_harian j WHERE j.siswa_id=u.id) AS total_jurnal
             FROM pkl_anggota pa
             JOIN users u ON pa.siswa_id = u.id
             LEFT JOIN profil_siswa ps ON ps.user_id = u.id
             WHERE pa.pengajuan_id = $idf AND pa.status_keanggotaan='aktif'
             ORDER BY u.nama_depan");
        while ($a = mysqli_fetch_assoc($res_ang)) $anggota[] = $a;
    }
}

// ================================================================
//  QUERY UTAMA — ambil SEMUA siswa yang pembimbing_id = $pid
//  PLUS pengajuan yang belum punya pembimbing (opsional ditampilkan)
// ================================================================
$q_bimbing = mysqli_query($conn,
    "SELECT p.id AS pengajuan_id, p.nama_perusahaan, p.alamat_perusahaan,
            p.status_pembimbing, p.tanggal_pengajuan,
            u.id AS user_id, u.nama_depan, u.nama_belakang, u.email,
            ps.nis, ps.kelas, ps.jurusan, ps.foto_profil,
            (SELECT COUNT(*) FROM jurnal_harian j WHERE j.siswa_id=u.id) AS total_jurnal,
            (SELECT COUNT(*) FROM jurnal_harian j WHERE j.siswa_id=u.id AND j.status_validasi='pending') AS jurnal_pending,
            (SELECT COUNT(*) FROM pkl_anggota pa WHERE pa.pengajuan_id=p.id AND pa.status_keanggotaan='aktif') AS jumlah_anggota
     FROM pkl_pengajuan p
     JOIN users u ON p.ketua_id = u.id
     LEFT JOIN profil_siswa ps ON ps.user_id = u.id
     WHERE p.pembimbing_id = $pid
     ORDER BY FIELD(p.status_pembimbing,'pending','disetujui','ditolak'), p.tanggal_pengajuan DESC");

// Pengajuan BELUM punya pembimbing (tampil sebagai "siswa baru menunggu assign")
$q_unassigned = mysqli_query($conn,
    "SELECT p.id AS pengajuan_id, p.nama_perusahaan, p.status_pembimbing, p.tanggal_pengajuan,
            u.id AS user_id, u.nama_depan, u.nama_belakang, u.email,
            ps.nis, ps.kelas, ps.jurusan
     FROM pkl_pengajuan p
     JOIN users u ON p.ketua_id = u.id
     LEFT JOIN profil_siswa ps ON ps.user_id = u.id
     WHERE p.pembimbing_id IS NULL
     ORDER BY p.tanggal_pengajuan DESC");

$stat = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total,
            SUM(status_pembimbing='disetujui') AS disetujui,
            SUM(status_pembimbing='pending')   AS pending,
            SUM(status_pembimbing='ditolak')   AS ditolak
     FROM pkl_pengajuan WHERE pembimbing_id=$pid"));
$unassigned_count = mysqli_num_rows($q_unassigned);

include '_header_pembimbing.php';
?>

<?php getFlash(); ?>

<!-- ============================================================
     INFO JIKA PIDs TIDAK MATCH (debug helper, hapus di produksi)
     ============================================================ -->
<?php if ($cek_role && $cek_role['role'] !== 'pembimbing'): ?>
<div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:10px;
    padding:12px 16px;margin-bottom:16px;font-size:.83rem;color:#f59e0b;">
    <i class="fas fa-triangle-exclamation"></i>
    <strong>Info:</strong> Anda login sebagai <code><?= $cek_role['role'] ?></code> (id=<?= $pid ?>).
    Data siswa ditampilkan berdasarkan pengajuan yang memiliki <code>pembimbing_id = <?= $pid ?></code>.
    Jika kosong, pastikan data pengajuan PKL sudah di-assign ke akun ini.
</div>
<?php endif; ?>

<!-- ============================================================
     PANEL SISWA BIMBINGAN SAYA
     ============================================================ -->
<div class="panel">
    <div class="panel-header">
        <div style="display:flex;align-items:center;gap:15px;">
            <div style="background:rgba(92,103,255,0.12);padding:12px;border-radius:12px;">
                <i class="fas fa-user-graduate" style="color:#5c67ff;font-size:1.4rem;"></i>
            </div>
            <div>
                <h3 style="margin:0;">Siswa Bimbingan Saya</h3>
                <p style="color:#64748b;font-size:.78rem;margin:0;">
                    Monitoring data dan status PKL siswa bimbingan Anda
                    <?php if ($cek_role): ?>(<?= htmlspecialchars($cek_role['nama_depan'].' '.$cek_role['nama_belakang']) ?>, id=<?= $pid ?>)<?php endif; ?>.
                </p>
            </div>
        </div>
        <input type="text" id="searchInput" placeholder="&#xf002;  Cari nama / perusahaan..."
               oninput="filterTable(this,'siswaTable')"
               style="margin-left:auto;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
                      border-radius:9px;padding:8px 14px;color:#f1f5f9;font-size:.82rem;
                      font-family:inherit;outline:none;width:220px;">
    </div>

    <!-- Stat -->
    <div style="display:flex;gap:10px;margin:16px 0 4px;flex-wrap:wrap;">
        <?php
        $pills = [
            ['label'=>'Total Siswa', 'val'=>(int)$stat['total'],     'c'=>'#6366f1'],
            ['label'=>'Disetujui',   'val'=>(int)$stat['disetujui'], 'c'=>'#4ade80'],
            ['label'=>'Pending',     'val'=>(int)$stat['pending'],   'c'=>'#f59e0b'],
            ['label'=>'Ditolak',     'val'=>(int)$stat['ditolak'],   'c'=>'#ef4444'],
        ];
        foreach ($pills as $p): ?>
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);
            border-radius:10px;padding:10px 18px;min-width:90px;text-align:center;">
            <div style="font-size:1.3rem;font-weight:700;color:<?= $p['c'] ?>;"><?= $p['val'] ?></div>
            <div style="font-size:.7rem;color:#64748b;margin-top:2px;"><?= $p['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="table-responsive" style="margin-top:16px;">
        <table id="siswaTable">
            <thead>
                <tr>
                    <th><i class="fas fa-id-badge" style="color:#6366f1;"></i> Identitas Siswa</th>
                    <th><i class="fas fa-building-columns" style="color:#4ade80;"></i> Tempat PKL</th>
                    <th style="text-align:center;"><i class="fas fa-book-open" style="color:#c4b5fd;"></i> Jurnal</th>
                    <th><i class="fas fa-calendar-days" style="color:#f59e0b;"></i> Tanggal</th>
                    <th style="text-align:center;"><i class="fas fa-circle-dot" style="color:#93c5fd;"></i> Status</th>
                    <th style="text-align:center;"><i class="fas fa-sliders" style="color:#c084fc;"></i> Opsi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$q_bimbing || mysqli_num_rows($q_bimbing) == 0): ?>
            <tr>
                <td colspan="6" style="text-align:center;padding:50px;color:#475569;">
                    <i class="fas fa-user-slash" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.3;color:#5c67ff;"></i>
                    Belum ada siswa yang di-assign ke akun Anda (pembimbing_id=<?= $pid ?>).
                    <?php if ($unassigned_count > 0): ?>
                    <br><span style="color:#f59e0b;font-size:.82rem;margin-top:8px;display:block;">
                        <i class="fas fa-triangle-exclamation"></i>
                        Ada <?= $unassigned_count ?> pengajuan belum punya pembimbing — lihat di tabel bawah.
                    </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php else: while ($d = mysqli_fetch_assoc($q_bimbing)):
                $status     = strtolower($d['status_pembimbing']);
                $pill_class = $status=='disetujui' ? 'pill-green' : ($status=='ditolak' ? 'pill-red' : 'pill-yellow');
                $st_icon    = $status=='disetujui' ? 'fa-circle-check' : ($status=='ditolak' ? 'fa-circle-xmark' : 'fa-hourglass-half');
                $inisial    = strtoupper(substr($d['nama_depan'],0,1).substr($d['nama_belakang'],0,1));
            ?>
            <tr class="siswa-row">
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:38px;height:38px;border-radius:50%;overflow:hidden;flex-shrink:0;
                            background:linear-gradient(135deg,#5c67ff,#8b5cf6);
                            display:flex;align-items:center;justify-content:center;
                            font-size:.75rem;font-weight:700;color:#fff;">
                            <?php if (!empty($d['foto_profil'])): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($d['foto_profil']) ?>"
                                 style="width:100%;height:100%;object-fit:cover;" alt="">
                            <?php else: echo $inisial; endif; ?>
                        </div>
                        <div>
                            <div style="font-weight:600;color:#f1f5f9;font-size:.88rem;">
                                <?= htmlspecialchars($d['nama_depan'].' '.$d['nama_belakang']) ?>
                                <?php if ($d['jumlah_anggota'] > 0): ?>
                                <span style="font-size:.68rem;background:rgba(99,102,241,.15);color:#a5b4fc;
                                    border:1px solid rgba(99,102,241,.25);border-radius:99px;padding:1px 6px;margin-left:4px;">
                                    +<?= $d['jumlah_anggota'] ?> anggota
                                </span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:.72rem;color:#94a3b8;margin-top:2px;">
                                <?php if ($d['nis']): ?>NIS: <?= htmlspecialchars($d['nis']) ?> &bull; <?php endif; ?>
                                <?php if ($d['kelas']): ?>Kelas <?= htmlspecialchars($d['kelas']) ?><?php if ($d['jurusan']): ?> – <?= htmlspecialchars($d['jurusan']) ?><?php endif; ?>
                                <?php else: ?><i class="fas fa-envelope" style="margin-right:2px;"></i><?= htmlspecialchars($d['email']) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-size:.86rem;color:#cbd5e1;">
                        <i class="fas fa-building" style="color:#4ade80;font-size:.8rem;margin-right:4px;"></i>
                        <?= htmlspecialchars($d['nama_perusahaan']) ?>
                    </div>
                    <?php if (!empty($d['alamat_perusahaan'])): ?>
                    <div style="font-size:.72rem;color:#475569;margin-top:2px;padding-left:18px;">
                        <?= htmlspecialchars(mb_strimwidth($d['alamat_perusahaan'],0,40,'…')) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <div style="font-size:.9rem;color:#c4b5fd;font-weight:600;"><?= $d['total_jurnal'] ?></div>
                    <?php if ($d['jurnal_pending'] > 0): ?>
                    <div style="font-size:.68rem;color:#f59e0b;"><?= $d['jurnal_pending'] ?> pending</div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-size:.82rem;color:#64748b;">
                        <i class="fas fa-calendar" style="color:#f59e0b;margin-right:4px;"></i>
                        <?= date('d M Y', strtotime($d['tanggal_pengajuan'])) ?>
                    </div>
                </td>
                <td style="text-align:center;">
                    <span class="pill <?= $pill_class ?>" style="display:inline-flex;align-items:center;gap:4px;">
                        <i class="fas <?= $st_icon ?>"></i> <?= strtoupper($d['status_pembimbing']) ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:5px;justify-content:center;flex-wrap:wrap;">
                        <a href="?detail=<?= $d['pengajuan_id'] ?>" class="btn-action-blue">
                            <i class="fas fa-eye"></i> Detail
                        </a>
                        <?php if ($status === 'pending'): ?>
                        <a href="?setujui=<?= $d['pengajuan_id'] ?>" class="btn-action-green"
                           onclick="return confirm('Setujui pengajuan PKL siswa ini?')">
                            <i class="fas fa-check"></i> Setujui
                        </a>
                        <a href="?tolak=<?= $d['pengajuan_id'] ?>" class="btn-action-red"
                           onclick="return confirm('Tolak pengajuan PKL siswa ini?')">
                            <i class="fas fa-xmark"></i> Tolak
                        </a>
                        <?php elseif ($status === 'disetujui'): ?>
                        <a href="absensi_pembimbing.php?siswa_id=<?= $d['user_id'] ?>" class="btn-action-teal">
                            <i class="fas fa-calendar-check"></i> Absensi
                        </a>
                        <a href="jurnal_pembimbing.php?siswa_id=<?= $d['user_id'] ?>" class="btn-action-orange">
                            <i class="fas fa-book-open"></i> Jurnal
                        </a>
                        <?php else: ?>
                        <a href="?hapus=<?= $d['pengajuan_id'] ?>" class="btn-action-red"
                           onclick="return confirm('Lepas siswa ini dari daftar bimbingan?')">
                            <i class="fas fa-user-minus"></i> Lepas
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- ============================================================
     PANEL PENGAJUAN BELUM PUNYA PEMBIMBING
     Pembimbing bisa langsung "ambil" / assign ke dirinya
     ============================================================ -->
<?php if ($unassigned_count > 0): ?>
<div class="panel" style="margin-top:20px;">
    <div class="panel-header">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="background:rgba(245,158,11,0.12);padding:10px;border-radius:10px;">
                <i class="fas fa-inbox" style="color:#f59e0b;font-size:1.2rem;"></i>
            </div>
            <div>
                <h3 style="margin:0;color:#f59e0b;">Pengajuan Belum Ada Pembimbing</h3>
                <p style="color:#64748b;font-size:.78rem;margin:0;">
                    Klik <strong>Ambil</strong> untuk menjadi pembimbing siswa ini.
                </p>
            </div>
        </div>
        <input type="text" id="searchUnassigned" placeholder="&#xf002;  Cari..."
               oninput="filterTable(this,'unassignedTable')"
               style="margin-left:auto;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
                      border-radius:9px;padding:8px 14px;color:#f1f5f9;font-size:.82rem;
                      font-family:inherit;outline:none;width:180px;">
    </div>

    <div class="table-responsive" style="margin-top:16px;">
        <table id="unassignedTable">
            <thead>
                <tr>
                    <th><i class="fas fa-id-badge" style="color:#6366f1;"></i> Identitas Siswa</th>
                    <th><i class="fas fa-building" style="color:#4ade80;"></i> Tempat PKL</th>
                    <th><i class="fas fa-calendar-days" style="color:#f59e0b;"></i> Tanggal Ajuan</th>
                    <th style="text-align:center;"><i class="fas fa-circle-dot" style="color:#93c5fd;"></i> Status</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php mysqli_data_seek($q_unassigned, 0);
            while ($u = mysqli_fetch_assoc($q_unassigned)):
                $inisial_u = strtoupper(substr($u['nama_depan'],0,1).substr($u['nama_belakang'],0,1));
            ?>
            <tr class="siswa-row">
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:50%;flex-shrink:0;
                            background:linear-gradient(135deg,#f59e0b,#f97316);
                            display:flex;align-items:center;justify-content:center;
                            font-size:.72rem;font-weight:700;color:#fff;">
                            <?= $inisial_u ?>
                        </div>
                        <div>
                            <div style="font-weight:600;color:#f1f5f9;font-size:.88rem;">
                                <?= htmlspecialchars($u['nama_depan'].' '.$u['nama_belakang']) ?>
                            </div>
                            <div style="font-size:.72rem;color:#94a3b8;">
                                <?php if ($u['nis']): ?>NIS: <?= htmlspecialchars($u['nis']) ?> &bull; <?php endif; ?>
                                <?php if ($u['kelas']): ?>Kelas <?= htmlspecialchars($u['kelas']) ?><?php else: ?><?= htmlspecialchars($u['email']) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td style="font-size:.86rem;color:#cbd5e1;">
                    <i class="fas fa-building" style="color:#4ade80;margin-right:4px;font-size:.8rem;"></i>
                    <?= htmlspecialchars($u['nama_perusahaan']) ?>
                </td>
                <td style="font-size:.82rem;color:#64748b;">
                    <i class="fas fa-calendar" style="color:#f59e0b;margin-right:4px;"></i>
                    <?= date('d M Y', strtotime($u['tanggal_pengajuan'])) ?>
                </td>
                <td style="text-align:center;">
                    <span class="pill pill-yellow" style="display:inline-flex;align-items:center;gap:4px;font-size:.72rem;">
                        <i class="fas fa-hourglass-half"></i> BELUM ADA PEMBIMBING
                    </span>
                </td>
                <td style="text-align:center;">
                    <a href="?setujui=<?= $u['pengajuan_id'] ?>" class="btn-action-orange"
                       onclick="return confirm('Ambil siswa ini sebagai bimbingan Anda dan setujui pengajuannya?')">
                        <i class="fas fa-hand-pointer"></i> Ambil & Setujui
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


<!-- ============================================================
     MODAL DETAIL SISWA
     ============================================================ -->
<?php if ($detail): ?>
<div id="modalDetail" style="position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;
    background:rgba(0,0,0,.6);backdrop-filter:blur(4px);padding:16px;">
  <div style="background:#0f172a;border:1px solid rgba(255,255,255,.1);border-radius:16px;
      width:100%;max-width:680px;max-height:90vh;overflow-y:auto;position:relative;">

    <div style="padding:20px 24px 16px;border-bottom:1px solid rgba(255,255,255,.07);
        display:flex;align-items:center;gap:14px;">
        <?php $inisial_d = strtoupper(substr($detail['nama_depan'],0,1).substr($detail['nama_belakang'],0,1)); ?>
        <div style="width:52px;height:52px;border-radius:50%;overflow:hidden;flex-shrink:0;
            background:linear-gradient(135deg,#5c67ff,#8b5cf6);
            display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;color:#fff;">
            <?php if (!empty($detail['foto_profil'])): ?>
            <img src="../assets/uploads/<?= htmlspecialchars($detail['foto_profil']) ?>"
                 style="width:100%;height:100%;object-fit:cover;">
            <?php else: echo $inisial_d; endif; ?>
        </div>
        <div style="flex:1;">
            <h2 style="margin:0;font-size:1.1rem;color:#f1f5f9;">
                <?= htmlspecialchars($detail['nama_depan'].' '.$detail['nama_belakang']) ?>
            </h2>
            <div style="font-size:.78rem;color:#64748b;margin-top:2px;">
                <?= htmlspecialchars($detail['email']) ?>
                <?php if ($detail['nis']): ?> &bull; NIS: <?= htmlspecialchars($detail['nis']) ?><?php endif; ?>
            </div>
        </div>
        <a href="daftar_siswa_pembimbing.php"
           style="color:#475569;font-size:1.2rem;line-height:1;padding:6px 10px;border-radius:8px;
           background:rgba(255,255,255,.05);text-decoration:none;">
            <i class="fas fa-xmark"></i>
        </a>
    </div>

    <div style="padding:20px 24px;">

        <!-- Stat cards -->
        <?php
        $pct = $detail['total_jurnal'] > 0
            ? round(($detail['jurnal_valid']/$detail['total_jurnal'])*100) : 0;
        $bcol = $pct>=70 ? '#4ade80' : ($pct>=40 ? '#f59e0b' : '#ef4444');
        $stats_d = [
            ['ico'=>'fa-book-open',        'c'=>'#c4b5fd','v'=>$detail['total_jurnal'],  'l'=>'Total Jurnal'],
            ['ico'=>'fa-circle-check',     'c'=>'#4ade80','v'=>$detail['jurnal_valid'],   'l'=>'Valid'],
            ['ico'=>'fa-hourglass-half',   'c'=>'#f59e0b','v'=>$detail['jurnal_pending'], 'l'=>'Pending'],
            ['ico'=>'fa-calendar-check',   'c'=>'#93c5fd','v'=>$detail['total_hadir'],    'l'=>'Hari Hadir'],
            ['ico'=>'fa-file-lines',       'c'=>'#4ade80','v'=>$detail['total_laporan'],  'l'=>'Laporan'],
        ];
        ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:10px;margin-bottom:16px;">
            <?php foreach ($stats_d as $s): ?>
            <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);
                border-radius:10px;padding:12px;text-align:center;">
                <i class="fas <?= $s['ico'] ?>" style="color:<?= $s['c'] ?>;"></i>
                <div style="font-size:1.2rem;font-weight:700;color:<?= $s['c'] ?>;margin:4px 0 2px;"><?= $s['v'] ?></div>
                <div style="font-size:.68rem;color:#475569;"><?= $s['l'] ?></div>
            </div>
            <?php endforeach; ?>
            <?php if ($detail['nilai_akhir']): ?>
            <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);
                border-radius:10px;padding:12px;text-align:center;">
                <i class="fas fa-star-half-stroke" style="color:#f59e0b;"></i>
                <div style="font-size:1.1rem;font-weight:700;color:#f59e0b;margin:4px 0 2px;">
                    <?= $detail['nilai_akhir'] ?> <span style="font-size:.8rem;">(<?= $detail['predikat'] ?>)</span>
                </div>
                <div style="font-size:.68rem;color:#475569;">Nilai Akhir</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Progress bar -->
        <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:5px;color:#94a3b8;">
                <span>Progress Validasi Jurnal</span>
                <span><?= $detail['jurnal_valid'] ?>/<?= $detail['total_jurnal'] ?> (<?= $pct ?>%)</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $bcol ?>;"></div>
            </div>
        </div>

        <!-- Info profil -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px;">
        <?php
        $info_rows = [
            ['ico'=>'fa-id-card',     'c'=>'#6366f1','l'=>'NIS',          'v'=>$detail['nis']],
            ['ico'=>'fa-school',      'c'=>'#4ade80','l'=>'Kelas',        'v'=>$detail['kelas']],
            ['ico'=>'fa-laptop-code', 'c'=>'#a78bfa','l'=>'Jurusan',      'v'=>$detail['jurusan']],
            ['ico'=>'fa-venus-mars',  'c'=>'#93c5fd','l'=>'JK',           'v'=>$detail['jenis_kelamin']],
            ['ico'=>'fa-phone',       'c'=>'#4ade80','l'=>'No. HP',       'v'=>$detail['no_hp']],
            ['ico'=>'fa-droplet',     'c'=>'#ef4444','l'=>'Gol. Darah',   'v'=>$detail['golongan_darah']],
            ['ico'=>'fa-map-pin',     'c'=>'#f59e0b','l'=>'Tempat Lahir', 'v'=>$detail['tempat_lahir']],
            ['ico'=>'fa-cake-candles','c'=>'#c4b5fd','l'=>'Tgl. Lahir',   'v'=>$detail['tanggal_lahir'] ? date('d M Y',strtotime($detail['tanggal_lahir'])) : null],
        ];
        foreach ($info_rows as $ir):
            if (!$ir['v']) continue; ?>
        <div style="display:flex;align-items:flex-start;gap:8px;background:rgba(255,255,255,.03);
            border:1px solid rgba(255,255,255,.06);border-radius:9px;padding:10px 12px;">
            <i class="fas <?= $ir['ico'] ?>" style="color:<?= $ir['c'] ?>;margin-top:2px;font-size:.8rem;"></i>
            <div>
                <div style="font-size:.68rem;color:#475569;"><?= $ir['l'] ?></div>
                <div style="font-size:.82rem;color:#cbd5e1;"><?= htmlspecialchars($ir['v']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <!-- Info PKL -->
        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);
            border-radius:10px;padding:14px;margin-bottom:16px;">
            <div style="font-size:.75rem;color:#64748b;margin-bottom:10px;font-weight:600;letter-spacing:.04em;">
                <i class="fas fa-briefcase" style="margin-right:4px;color:#4ade80;"></i> INFO PKL
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.82rem;">
                <div><span style="color:#475569;">Perusahaan:</span>
                    <span style="color:#cbd5e1;margin-left:4px;"><?= htmlspecialchars($detail['nama_perusahaan']) ?></span></div>
                <div><span style="color:#475569;">Tgl. Ajuan:</span>
                    <span style="color:#cbd5e1;margin-left:4px;"><?= date('d M Y',strtotime($detail['tanggal_pengajuan'])) ?></span></div>
                <div><span style="color:#475569;">Status Pembimbing:</span>
                    <span style="margin-left:4px;color:<?= $detail['status_pembimbing']=='disetujui'?'#4ade80':($detail['status_pembimbing']=='ditolak'?'#ef4444':'#f59e0b') ?>">
                        <?= ucfirst($detail['status_pembimbing']) ?></span></div>
                <div><span style="color:#475569;">Status Wakasek:</span>
                    <span style="margin-left:4px;color:<?= $detail['status_wakasek']=='disetujui'?'#4ade80':'#f59e0b' ?>">
                        <?= ucfirst($detail['status_wakasek']) ?></span></div>
                <?php if ($detail['alamat_perusahaan']): ?>
                <div style="grid-column:1/-1;"><span style="color:#475569;">Alamat:</span>
                    <span style="color:#94a3b8;margin-left:4px;"><?= htmlspecialchars($detail['alamat_perusahaan']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Anggota kelompok -->
        <?php if (!empty($anggota)): ?>
        <div style="margin-bottom:16px;">
            <div style="font-size:.75rem;color:#64748b;margin-bottom:8px;font-weight:600;letter-spacing:.04em;">
                <i class="fas fa-users" style="margin-right:4px;color:#6366f1;"></i>
                ANGGOTA KELOMPOK (<?= count($anggota) ?> orang)
            </div>
            <?php foreach ($anggota as $ang):
                $ini_a = strtoupper(substr($ang['nama_depan'],0,1).substr($ang['nama_belakang'],0,1)); ?>
            <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;
                background:rgba(99,102,241,.05);border:1px solid rgba(99,102,241,.12);
                border-radius:9px;margin-bottom:6px;">
                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a78bfa);
                    display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0;">
                    <?= $ini_a ?>
                </div>
                <div style="flex:1;">
                    <div style="font-size:.84rem;color:#f1f5f9;font-weight:600;">
                        <?= htmlspecialchars($ang['nama_depan'].' '.$ang['nama_belakang']) ?>
                    </div>
                    <div style="font-size:.71rem;color:#64748b;">
                        <?= htmlspecialchars($ang['email']) ?>
                        <?php if ($ang['kelas']): ?> &bull; Kelas <?= htmlspecialchars($ang['kelas']) ?><?php endif; ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:.82rem;color:#c4b5fd;font-weight:600;"><?= $ang['total_jurnal'] ?></div>
                    <div style="font-size:.65rem;color:#475569;">jurnal</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Aksi modal -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php if ($detail['status_pembimbing'] === 'disetujui'): ?>
            <a href="jurnal_pembimbing.php?siswa_id=<?= $detail['user_id'] ?>" class="btn-action-orange" style="padding:9px 14px;">
                <i class="fas fa-book-open"></i> Lihat Jurnal
            </a>
            <a href="absensi_pembimbing.php?siswa_id=<?= $detail['user_id'] ?>" class="btn-action-teal" style="padding:9px 14px;">
                <i class="fas fa-calendar-check"></i> Cek Absensi
            </a>
            <a href="input_nilai.php?siswa_id=<?= $detail['user_id'] ?>" class="btn-action-green" style="padding:9px 14px;">
                <i class="fas fa-star-half-stroke"></i> Input Nilai
            </a>
            <?php elseif ($detail['status_pembimbing'] === 'pending'): ?>
            <a href="?setujui=<?= $detail['pengajuan_id'] ?>" class="btn-action-green" style="padding:9px 14px;"
               onclick="return confirm('Setujui pengajuan PKL siswa ini?')">
                <i class="fas fa-check"></i> Setujui
            </a>
            <a href="?tolak=<?= $detail['pengajuan_id'] ?>" class="btn-action-red" style="padding:9px 14px;"
               onclick="return confirm('Tolak pengajuan PKL siswa ini?')">
                <i class="fas fa-xmark"></i> Tolak
            </a>
            <?php endif; ?>
            <a href="daftar_siswa_pembimbing.php" class="btn-action-blue" style="padding:9px 14px;margin-left:auto;">
                <i class="fas fa-arrow-left"></i> Tutup
            </a>
        </div>

    </div><!-- /padding -->
  </div><!-- /card -->
</div>
<?php endif; ?>


<style>
.btn-action-green,.btn-action-red,.btn-action-blue,.btn-action-orange,.btn-action-teal {
    display:inline-flex;align-items:center;gap:5px;padding:6px 11px;
    border-radius:7px;font-size:.78rem;font-weight:600;border:1px solid;
    transition:.2s;text-decoration:none;white-space:nowrap;
}
.btn-action-green  { color:#4ade80;border-color:rgba(74,222,128,.25);background:rgba(74,222,128,.08); }
.btn-action-green:hover  { background:rgba(74,222,128,.2); }
.btn-action-red    { color:#ef4444;border-color:rgba(239,68,68,.25);background:rgba(239,68,68,.08); }
.btn-action-red:hover    { background:rgba(239,68,68,.2); }
.btn-action-blue   { color:#5c67ff;border-color:rgba(92,103,255,.25);background:rgba(92,103,255,.08); }
.btn-action-blue:hover   { background:rgba(92,103,255,.2); }
.btn-action-orange { color:#f59e0b;border-color:rgba(245,158,11,.25);background:rgba(245,158,11,.08); }
.btn-action-orange:hover { background:rgba(245,158,11,.2); }
.btn-action-teal   { color:#2dd4bf;border-color:rgba(45,212,191,.25);background:rgba(45,212,191,.08); }
.btn-action-teal:hover   { background:rgba(45,212,191,.2); }
#searchInput::placeholder,#searchUnassigned::placeholder { color:#334155;font-family:sans-serif; }
</style>

<script>
function filterTable(input, tableId) {
    const q    = input.value.toLowerCase();
    const rows = document.querySelectorAll('#' + tableId + ' tbody tr.siswa-row');
    rows.forEach(r => { r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
}
const overlay = document.getElementById('modalDetail');
if (overlay) overlay.addEventListener('click', e => {
    if (e.target === overlay) window.location.href = 'daftar_siswa_pembimbing.php';
});
</script>

</body>
</html>