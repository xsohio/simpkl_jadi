<?php
session_start();
include "../config.php";
requirePembimbing();

$pid = (int)$_SESSION['user']['id_user'];
$active_page = 'nilai';
$page_title = 'Input Nilai Siswa';

function catat_log($conn, $user_id, $aktivitas) {
    $aktivitas = mysqli_real_escape_string($conn, $aktivitas);
    mysqli_query($conn, "INSERT INTO log_aktivitas (id_users, aktivitas) VALUES ($user_id, '$aktivitas')");
}

// -------- SIMPAN / UPDATE NILAI --------
// Struktur DB: nilai_sikap, nilai_keterampilan, nilai_laporan, nilai_akhir, predikat, catatan
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['simpan_nilai'])) {
    $siswa_id        = (int)$_POST['siswa_id'];
    $n_sikap         = min(100,(int)$_POST['n_sikap']);
    $n_keterampilan  = min(100,(int)$_POST['n_keterampilan']);
    $n_laporan       = min(100,(int)$_POST['n_laporan']);
    $catatan         = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');

    // Nilai akhir & predikat (bobot: sikap 30%, keterampilan 40%, laporan 30%)
    $nilai_akhir = round(($n_sikap * 0.30) + ($n_keterampilan * 0.40) + ($n_laporan * 0.30), 2);
    $predikat = $nilai_akhir >= 90 ? 'A' : ($nilai_akhir >= 80 ? 'B' : ($nilai_akhir >= 70 ? 'C' : ($nilai_akhir >= 60 ? 'D' : 'E')));

    $cek = mysqli_query($conn, "SELECT id FROM nilai_pkl WHERE siswa_id=$siswa_id AND pembimbing_id=$pid");
    $nama = mysqli_fetch_assoc(mysqli_query($conn,"SELECT CONCAT(nama_depan,' ',nama_belakang) n FROM users WHERE id=$siswa_id"))['n'] ?? "ID#$siswa_id";

    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "UPDATE nilai_pkl SET nilai_sikap=$n_sikap, nilai_keterampilan=$n_keterampilan,
            nilai_laporan=$n_laporan, nilai_akhir=$nilai_akhir, predikat='$predikat', catatan='$catatan'
            WHERE siswa_id=$siswa_id AND pembimbing_id=$pid");
        catat_log($conn, $pid, "Memperbarui nilai PKL siswa $nama (Akhir: $nilai_akhir / $predikat)");
        setFlash('success', "<i class='fas fa-rotate'></i> Nilai siswa <b>$nama</b> berhasil diperbarui!");
    } else {
        mysqli_query($conn, "INSERT INTO nilai_pkl (siswa_id,pembimbing_id,nilai_sikap,nilai_keterampilan,nilai_laporan,nilai_akhir,predikat,catatan)
            VALUES ($siswa_id,$pid,$n_sikap,$n_keterampilan,$n_laporan,$nilai_akhir,'$predikat','$catatan')");
        catat_log($conn, $pid, "Menginput nilai PKL siswa $nama (Akhir: $nilai_akhir / $predikat)");
        setFlash('success', "<i class='fas fa-star'></i> Nilai siswa <b>$nama</b> berhasil disimpan!");
    }
    header("Location: input_nilai.php"); exit();
}

include '_header_pembimbing.php';
?>

<div class="panel">
    <div class="panel-header">
        <div style="display:flex;align-items:center;gap:15px;">
            <div style="background:rgba(245,158,11,0.12);padding:12px;border-radius:12px;">
                <i class="fas fa-star-half-stroke" style="color:#f59e0b;font-size:1.4rem;"></i>
            </div>
            <div>
                <h3 style="margin:0;">Input Nilai PKL</h3>
                <p style="color:#64748b;font-size:.78rem;margin:0;">Bobot: Sikap 30% · Keterampilan 40% · Laporan 30%</p>
            </div>
        </div>
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);
          border-radius:8px;padding:8px 14px;font-size:.78rem;color:#64748b;">
            <i class="fas fa-circle-info" style="color:#93c5fd;margin-right:5px;"></i>
            Nilai range 0–100
        </div>
    </div>

    <div class="table-responsive" style="margin-top:25px;">
        <table>
            <thead>
                <tr>
                    <th><i class="fas fa-user-graduate" style="color:#6366f1;"></i> Nama Siswa</th>
                    <th style="text-align:center;width:90px;"><i class="fas fa-heart" style="color:#f87171;"></i> Sikap<br><span style="font-size:.64rem;color:#475569;font-weight:400;">30%</span></th>
                    <th style="text-align:center;width:90px;"><i class="fas fa-wrench" style="color:#4ade80;"></i> Keterampilan<br><span style="font-size:.64rem;color:#475569;font-weight:400;">40%</span></th>
                    <th style="text-align:center;width:90px;"><i class="fas fa-file-lines" style="color:#93c5fd;"></i> Laporan<br><span style="font-size:.64rem;color:#475569;font-weight:400;">30%</span></th>
                    <th style="text-align:center;"><i class="fas fa-calculator" style="color:#c084fc;"></i> Nilai Akhir</th>
                    <th style="text-align:center;width:50px;"><i class="fas fa-medal" style="color:#f59e0b;"></i> Predikat</th>
                    <th><i class="fas fa-pen-to-square" style="color:#94a3b8;"></i> Catatan</th>
                    <th style="text-align:center;"><i class="fas fa-floppy-disk" style="color:#4ade80;"></i> Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $res = mysqli_query($conn,
                "SELECT u.id, u.nama_depan, u.nama_belakang,
                  n.nilai_sikap, n.nilai_keterampilan, n.nilai_laporan, n.nilai_akhir, n.predikat, n.catatan
                 FROM pkl_pengajuan p
                 JOIN users u ON p.ketua_id = u.id
                 LEFT JOIN nilai_pkl n ON u.id = n.siswa_id AND n.pembimbing_id = $pid
                 WHERE p.pembimbing_id = $pid AND p.status_pembimbing = 'disetujui'");

            if (!$res || mysqli_num_rows($res) == 0): ?>
            <tr>
                <td colspan="8" style="text-align:center;padding:50px;color:#475569;">
                    <i class="fas fa-users-slash" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.3;color:#f59e0b;"></i>
                    Belum ada siswa dengan status disetujui.
                </td>
            </tr>
            <?php else: while($d = mysqli_fetch_assoc($res)):
                $na = $d['nilai_akhir'] ?? 0;
                $bar_col = $na >= 80 ? '#4ade80' : ($na >= 70 ? '#f59e0b' : '#ef4444');
                $pred_color = ['A'=>'#4ade80','B'=>'#93c5fd','C'=>'#f59e0b','D'=>'#f87171','E'=>'#ef4444'][$d['predikat']??''] ?? '#64748b';
            ?>
            <form method="POST">
                <input type="hidden" name="siswa_id" value="<?= $d['id'] ?>">
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#d97706);
                                display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;color:#fff;flex-shrink:0;">
                                <?= strtoupper(substr($d['nama_depan'],0,1).substr($d['nama_belakang'],0,1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:600;color:#f1f5f9;font-size:.85rem;"><?= htmlspecialchars($d['nama_depan'].' '.$d['nama_belakang']) ?></div>
                                <div style="font-size:.7rem;color:#475569;">ID: #<?= $d['id'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <input type="number" name="n_sikap" class="input-nilai" value="<?= (int)($d['nilai_sikap']??0) ?>" min="0" max="100">
                    </td>
                    <td style="text-align:center;">
                        <input type="number" name="n_keterampilan" class="input-nilai" value="<?= (int)($d['nilai_keterampilan']??0) ?>" min="0" max="100">
                    </td>
                    <td style="text-align:center;">
                        <input type="number" name="n_laporan" class="input-nilai" value="<?= (int)($d['nilai_laporan']??0) ?>" min="0" max="100">
                    </td>
                    <td style="text-align:center;">
                        <div>
                            <div class="avg-badge"><?= number_format($na,1) ?></div>
                            <div style="margin-top:6px;">
                                <div class="progress-bar" style="max-width:80px;margin:0 auto;">
                                    <div class="progress-fill" style="width:<?= $na ?>%;background:<?= $bar_col ?>;"></div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <?php if($d['predikat']): ?>
                        <span style="font-size:1.4rem;font-weight:800;color:<?= $pred_color ?>;"><?= $d['predikat'] ?></span>
                        <?php else: ?>
                        <span style="color:#334155;font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <input type="text" name="catatan" class="input-teks" 
                          value="<?= htmlspecialchars($d['catatan']??'') ?>" placeholder="Catatan opsional...">
                    </td>
                    <td style="text-align:center;">
                        <button type="submit" name="simpan_nilai" class="btn-save" title="Simpan Nilai">
                            <i class="fas fa-floppy-disk"></i> Simpan
                        </button>
                    </td>
                </tr>
            </form>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.input-nilai {
    width:68px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
    color:#fff;padding:7px;border-radius:8px;text-align:center;font-weight:700;font-size:.88rem;
}
.input-nilai::-webkit-inner-spin-button,.input-nilai::-webkit-outer-spin-button { opacity:1;height:28px; }
.input-nilai:focus { outline:none;border-color:#f59e0b;background:rgba(245,158,11,.08); }
.input-teks {
    width:100%;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);
    color:#94a3b8;padding:7px 10px;border-radius:8px;font-size:.78rem;font-family:'Poppins',sans-serif;
}
.input-teks:focus { outline:none;border-color:#6366f1;color:#e2e8f0; }
.avg-badge {
    display:inline-block;background:rgba(245,158,11,.12);color:#f59e0b;
    padding:4px 12px;border-radius:20px;font-weight:700;font-size:.88rem;
    border:1px solid rgba(245,158,11,.3);
}
.btn-save {
    display:inline-flex;align-items:center;gap:5px;
    background:#6366f1;color:#fff;border:none;padding:7px 13px;border-radius:8px;
    font-size:.78rem;font-weight:600;cursor:pointer;transition:.2s;white-space:nowrap;
}
.btn-save:hover { background:#4f46e5;transform:translateY(-1px); }
th { white-space:normal;line-height:1.3; }
</style>
