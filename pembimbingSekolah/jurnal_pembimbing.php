<?php
session_start();
include "../config.php";
requirePembimbing();

$pid = (int)$_SESSION['user']['id_user'];
$active_page = 'jurnal';
$page_title = 'Validasi Jurnal Harian';

// ============================================================
//  FUNGSI LOG AKTIVITAS — catat ke tabel log_aktivitas
// ============================================================
function catat_log($conn, $user_id, $aktivitas) {
    $aktivitas = mysqli_real_escape_string($conn, $aktivitas);
    mysqli_query($conn, "INSERT INTO log_aktivitas (id_users, aktivitas) VALUES ($user_id, '$aktivitas')");
}

// -------- PROSES AKSI GET --------
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $jid    = (int)$_GET['id'];
    $aksi   = $_GET['aksi'];
    $komentar = isset($_GET['komentar']) ? mysqli_real_escape_string($conn, $_GET['komentar']) : '';

    // Ambil info jurnal untuk log
    $info = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT j.tanggal, u.nama_depan, u.nama_belakang
         FROM jurnal_harian j JOIN users u ON j.siswa_id = u.id
         WHERE j.id = $jid"));
    $nama_siswa = $info ? ($info['nama_depan'].' '.$info['nama_belakang']) : "ID#$jid";
    $tgl_jurnal = $info ? $info['tanggal'] : '-';

    if ($aksi === 'valid') {
        $kol_komen = $komentar ? ", komentar_pembimbing = '$komentar'" : '';
        mysqli_query($conn, "UPDATE jurnal_harian SET status_validasi = 'valid' $kol_komen WHERE id = $jid");
        catat_log($conn, $pid, "Memvalidasi jurnal $nama_siswa tanggal $tgl_jurnal");
        setFlash('success', "<i class='fas fa-check-circle'></i> Jurnal berhasil divalidasi.");
    } elseif ($aksi === 'tolak') {
        $kol_komen = $komentar ? ", komentar_pembimbing = '$komentar'" : '';
        mysqli_query($conn, "UPDATE jurnal_harian SET status_validasi = 'tolak' $kol_komen WHERE id = $jid");
        catat_log($conn, $pid, "Menolak jurnal $nama_siswa tanggal $tgl_jurnal");
        setFlash('warning', "<i class='fas fa-times-circle'></i> Jurnal ditolak.");
    } elseif ($aksi === 'reset') {
        mysqli_query($conn, "UPDATE jurnal_harian SET status_validasi = 'pending', komentar_pembimbing = NULL WHERE id = $jid");
        catat_log($conn, $pid, "Me-reset status jurnal $nama_siswa tanggal $tgl_jurnal ke pending");
        setFlash('info', "<i class='fas fa-rotate'></i> Status jurnal direset ke pending.");
    }
    header("Location: jurnal_pembimbing.php");
    exit();
}

// -------- PROSES KOMENTAR POST --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_komentar'])) {
    $jid      = (int)$_POST['jurnal_id'];
    $komentar = mysqli_real_escape_string($conn, $_POST['komentar']);
    mysqli_query($conn, "UPDATE jurnal_harian SET komentar_pembimbing = '$komentar' WHERE id = $jid");

    $info = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT j.tanggal, u.nama_depan, u.nama_belakang
         FROM jurnal_harian j JOIN users u ON j.siswa_id = u.id WHERE j.id = $jid"));
    $nama_siswa = $info ? ($info['nama_depan'].' '.$info['nama_belakang']) : "ID#$jid";
    catat_log($conn, $pid, "Menambah komentar jurnal $nama_siswa");
    setFlash('success', "<i class='fas fa-comment-dots'></i> Komentar berhasil disimpan.");
    header("Location: jurnal_pembimbing.php");
    exit();
}

// -------- FILTER --------
$filter_status = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$where_status  = ($filter_status !== 'semua') ? "AND j.status_validasi = '$filter_status'" : '';

include '_header_pembimbing.php';
?>

<div class="panel">
    <div class="panel-header">
        <div style="display:flex;align-items:center;gap:15px;">
            <div style="background:rgba(245,158,11,0.12);padding:12px;border-radius:12px;">
                <i class="fas fa-book-open-reader" style="color:#f59e0b;font-size:1.4rem;"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:1rem;">Validasi Jurnal Harian</h3>
                <p style="color:#64748b;font-size:0.78rem;margin:0;">Review dan validasi kegiatan harian siswa bimbingan.</p>
            </div>
        </div>
        <!-- Filter Tab -->
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <?php
            $filters = ['semua'=>['fas fa-list','#94a3b8'],'pending'=>['fas fa-hourglass-half','#f59e0b'],'valid'=>['fas fa-circle-check','#4ade80'],'tolak'=>['fas fa-circle-xmark','#ef4444']];
            foreach($filters as $k=>[$ic,$cl]):
            ?>
            <a href="?filter=<?= $k ?>" style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:8px;font-size:.75rem;font-weight:600;border:1px solid;transition:.2s;
              <?= $filter_status===$k ? "background:rgba(99,102,241,.15);border-color:rgba(99,102,241,.4);color:#a5b4fc;" : "background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.1);color:#64748b;" ?>">
              <i class="<?= $ic ?>" style="color:<?= $cl ?>;"></i> <?= ucfirst($k) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="table-responsive" style="margin-top:20px;">
        <table>
            <thead>
                <tr>
                    <th><i class="fas fa-user" style="color:#6366f1;"></i> Siswa</th>
                    <th><i class="fas fa-calendar-days" style="color:#f59e0b;"></i> Tanggal & Waktu</th>
                    <th><i class="fas fa-clipboard-list" style="color:#4ade80;"></i> Kegiatan</th>
                    <th><i class="fas fa-comment-dots" style="color:#93c5fd;"></i> Komentar</th>
                    <th style="text-align:center;"><i class="fas fa-circle-dot" style="color:#94a3b8;"></i> Status</th>
                    <th style="text-align:center;"><i class="fas fa-sliders" style="color:#c084fc;"></i> Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $q = mysqli_query($conn,
                "SELECT j.*, u.nama_depan, u.nama_belakang
                 FROM jurnal_harian j
                 JOIN pkl_pengajuan p ON j.siswa_id = p.ketua_id
                 JOIN users u ON j.siswa_id = u.id
                 WHERE p.pembimbing_id = $pid $where_status
                 ORDER BY j.tanggal DESC");

            if (!$q || mysqli_num_rows($q) == 0):
            ?>
            <tr>
                <td colspan="6" style="text-align:center;padding:50px;color:#475569;">
                    <i class="fas fa-folder-open" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.3;color:#f59e0b;"></i>
                    Tidak ada data jurnal<?= $filter_status !== 'semua' ? " dengan status <b>$filter_status</b>" : '' ?>.
                </td>
            </tr>
            <?php else: while($d = mysqli_fetch_assoc($q)):
                $st = strtolower($d['status_validasi']);
                $pill_class = $st=='valid' ? 'pill-green' : ($st=='tolak' ? 'pill-red' : 'pill-yellow');
                $st_icon = $st=='valid' ? 'fa-circle-check' : ($st=='tolak' ? 'fa-circle-xmark' : 'fa-hourglass-half');
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);
                            display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0;">
                            <?= strtoupper(substr($d['nama_depan'],0,1).substr($d['nama_belakang'],0,1)) ?>
                        </div>
                        <div style="font-weight:600;color:#f1f5f9;font-size:.85rem;">
                            <?= htmlspecialchars($d['nama_depan'].' '.$d['nama_belakang']) ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-size:.88rem;color:#cbd5e1;font-weight:500;">
                        <i class="fas fa-calendar" style="color:#6366f1;margin-right:4px;"></i>
                        <?= date('d M Y', strtotime($d['tanggal'])) ?>
                    </div>
                    <div style="font-size:.74rem;color:#64748b;margin-top:3px;">
                        <i class="fas fa-clock" style="margin-right:3px;"></i>
                        <?= substr($d['jam_masuk']??'--:--',0,5) ?> – <?= substr($d['jam_keluar']??'--:--',0,5) ?>
                    </div>
                </td>
                <td>
                    <div style="font-size:.83rem;max-width:220px;line-height:1.5;color:#cbd5e1;">
                        <?= htmlspecialchars(mb_strimwidth($d['kegiatan'],0,80,'…')) ?>
                    </div>
                </td>
                <td>
                    <?php if(!empty($d['komentar_pembimbing'])): ?>
                    <div style="font-size:.78rem;color:#93c5fd;font-style:italic;max-width:160px;">
                        <i class="fas fa-quote-left" style="font-size:.6rem;opacity:.5;"></i>
                        <?= htmlspecialchars(mb_strimwidth($d['komentar_pembimbing'],0,60,'…')) ?>
                    </div>
                    <?php else: ?>
                    <span style="font-size:.75rem;color:#334155;">— Belum ada</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <span class="pill <?= $pill_class ?>" style="display:inline-flex;align-items:center;gap:4px;">
                        <i class="fas <?= $st_icon ?>"></i> <?= strtoupper($st) ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:5px;justify-content:center;flex-wrap:wrap;">
                        <?php if($st === 'pending'): ?>
                        <a href="?aksi=valid&id=<?= $d['id'] ?>" class="btn-aksi btn-aksi-green" title="Validasi">
                            <i class="fas fa-check"></i>
                        </a>
                        <a href="?aksi=tolak&id=<?= $d['id'] ?>" class="btn-aksi btn-aksi-red" title="Tolak">
                            <i class="fas fa-xmark"></i>
                        </a>
                        <?php else: ?>
                        <a href="?aksi=reset&id=<?= $d['id'] ?>" class="btn-aksi btn-aksi-gray" title="Reset ke Pending"
                           onclick="return confirm('Reset status jurnal ini ke pending?')">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                        <?php endif; ?>
                        <!-- Tombol Komentar -->
                        <button onclick="toggleKomentar(<?= $d['id'] ?>)" class="btn-aksi btn-aksi-blue" title="Tambah Komentar">
                            <i class="fas fa-comment-pen"></i>
                        </button>
                    </div>
                    <!-- Form Komentar Inline -->
                    <div id="form-komen-<?= $d['id'] ?>" style="display:none;margin-top:8px;">
                        <form method="POST" style="display:flex;flex-direction:column;gap:5px;min-width:180px;">
                            <input type="hidden" name="jurnal_id" value="<?= $d['id'] ?>">
                            <textarea name="komentar" rows="2" placeholder="Tulis komentar..."
                              style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
                              color:#e2e8f0;border-radius:6px;padding:6px 8px;font-size:.76rem;resize:none;
                              font-family:'Poppins',sans-serif;"><?= htmlspecialchars($d['komentar_pembimbing']??'') ?></textarea>
                            <button type="submit" name="simpan_komentar"
                              style="background:#6366f1;color:#fff;border:none;border-radius:6px;
                              padding:5px 10px;font-size:.75rem;font-weight:600;cursor:pointer;">
                                <i class="fas fa-floppy-disk"></i> Simpan
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.btn-aksi {
    display:inline-flex;align-items:center;justify-content:center;
    width:30px;height:30px;border-radius:7px;font-size:.82rem;
    border:1px solid;transition:.2s;cursor:pointer;text-decoration:none;
    background:transparent;
}
.btn-aksi-green { color:#4ade80;border-color:rgba(74,222,128,.25);background:rgba(74,222,128,.08); }
.btn-aksi-green:hover { background:rgba(74,222,128,.2); }
.btn-aksi-red { color:#ef4444;border-color:rgba(239,68,68,.25);background:rgba(239,68,68,.08); }
.btn-aksi-red:hover { background:rgba(239,68,68,.2); }
.btn-aksi-blue { color:#93c5fd;border-color:rgba(147,197,253,.25);background:rgba(147,197,253,.08); }
.btn-aksi-blue:hover { background:rgba(147,197,253,.2); }
.btn-aksi-gray { color:#94a3b8;border-color:rgba(148,163,184,.25);background:rgba(148,163,184,.08); }
.btn-aksi-gray:hover { background:rgba(148,163,184,.2); }
th i { margin-right:5px; }
</style>
<script>
function toggleKomentar(id) {
    const el = document.getElementById('form-komen-'+id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
