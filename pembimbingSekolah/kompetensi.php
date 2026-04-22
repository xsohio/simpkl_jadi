<?php
session_start();
include "../config.php";
requirePembimbing();

$pid = (int)$_SESSION['user']['id_user'];
$active_page = 'kompetensi';
$page_title = 'Input Nilai Kompetensi';

function catat_log($conn, $user_id, $aktivitas) {
    $aktivitas = mysqli_real_escape_string($conn, $aktivitas);
    mysqli_query($conn, "INSERT INTO log_aktivitas (id_users, aktivitas) VALUES ($user_id, '$aktivitas')");
}

// -------- SIMPAN KOMPETENSI --------
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['simpan_kompetensi'])) {
    $siswa_id  = (int)$_POST['siswa_id'];
    $data_komp = [
        'analisis' => min(100,(int)$_POST['n_analisis']),
        'database' => min(100,(int)$_POST['n_db']),
        'frontend' => min(100,(int)$_POST['n_front']),
        'backend'  => min(100,(int)$_POST['n_back']),
        'git'      => min(100,(int)$_POST['n_git']),
    ];

    foreach ($data_komp as $key => $val) {
        $cek = mysqli_query($conn, "SELECT id FROM kompetensi_siswa WHERE siswa_id=$siswa_id AND kompetensi_key='$key'");
        if (mysqli_num_rows($cek) > 0) {
            mysqli_query($conn, "UPDATE kompetensi_siswa SET nilai=$val WHERE siswa_id=$siswa_id AND kompetensi_key='$key'");
        } else {
            mysqli_query($conn, "INSERT INTO kompetensi_siswa (siswa_id,kompetensi_key,nilai) VALUES ($siswa_id,'$key',$val)");
        }
    }

    $nama = mysqli_fetch_assoc(mysqli_query($conn,"SELECT CONCAT(nama_depan,' ',nama_belakang) n FROM users WHERE id=$siswa_id"))['n'] ?? "ID#$siswa_id";
    $avg_komp = round(array_sum($data_komp)/count($data_komp));
    catat_log($conn, $pid, "Menyimpan nilai kompetensi $nama (rata-rata: $avg_komp)");
    setFlash('success', "<i class='fas fa-list-check'></i> Nilai kompetensi <b>$nama</b> berhasil disimpan!");
    header("Location: kompetensi.php"); exit();
}

// -------- RESET KOMPETENSI SISWA --------
if (isset($_GET['reset'])) {
    $sid = (int)$_GET['reset'];
    mysqli_query($conn, "DELETE FROM kompetensi_siswa WHERE siswa_id=$sid");
    $nama = mysqli_fetch_assoc(mysqli_query($conn,"SELECT CONCAT(nama_depan,' ',nama_belakang) n FROM users WHERE id=$sid"))['n'] ?? "ID#$sid";
    catat_log($conn, $pid, "Mereset semua nilai kompetensi siswa $nama");
    setFlash('warning', "<i class='fas fa-rotate'></i> Nilai kompetensi <b>$nama</b> telah direset.");
    header("Location: kompetensi.php"); exit();
}

// Definisi kompetensi
$komp_defs = [
    'analisis' => ['icon'=>'fas fa-magnifying-glass-chart', 'label'=>'Analisis', 'color'=>'#a78bfa'],
    'database' => ['icon'=>'fas fa-database',              'label'=>'Database',  'color'=>'#34d399'],
    'frontend' => ['icon'=>'fas fa-palette',               'label'=>'Frontend',  'color'=>'#38bdf8'],
    'backend'  => ['icon'=>'fas fa-server',                'label'=>'Backend',   'color'=>'#fb923c'],
    'git'      => ['icon'=>'fas fa-code-branch',           'label'=>'Git/VCS',   'color'=>'#f472b6'],
];

include '_header_pembimbing.php';
?>

<div class="panel">
    <div class="panel-header">
        <div style="display:flex;align-items:center;gap:15px;">
            <div style="background:rgba(99,102,241,0.12);padding:12px;border-radius:12px;">
                <i class="fas fa-list-check" style="color:#6366f1;font-size:1.4rem;"></i>
            </div>
            <div>
                <h3 style="margin:0;">Input Nilai Kompetensi</h3>
                <p style="color:#64748b;font-size:.78rem;margin:0;">Skor kompetensi teknis siswa PKL (0–100).</p>
            </div>
        </div>
        <!-- Legenda Kompetensi -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php foreach($komp_defs as $k=>$kd): ?>
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:.72rem;color:#64748b;">
                <i class="<?= $kd['icon'] ?>" style="color:<?= $kd['color'] ?>;"></i>
                <?= $kd['label'] ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="table-responsive" style="margin-top:20px;">
        <table>
            <thead>
                <tr>
                    <th><i class="fas fa-user-graduate" style="color:#6366f1;"></i> Siswa</th>
                    <?php foreach($komp_defs as $kd): ?>
                    <th style="text-align:center;width:80px;">
                        <i class="<?= $kd['icon'] ?>" style="color:<?= $kd['color'] ?>;"></i><br>
                        <span style="font-size:.65rem;font-weight:400;"><?= $kd['label'] ?></span>
                    </th>
                    <?php endforeach; ?>
                    <th style="text-align:center;"><i class="fas fa-chart-bar" style="color:#f59e0b;"></i> Rata-rata</th>
                    <th style="text-align:center;"><i class="fas fa-sliders" style="color:#c084fc;"></i> Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $q = mysqli_query($conn,
                "SELECT u.id, u.nama_depan, u.nama_belakang
                 FROM pkl_pengajuan p JOIN users u ON p.ketua_id=u.id
                 WHERE p.pembimbing_id=$pid AND p.status_pembimbing='disetujui'");

            if (!$q || mysqli_num_rows($q) == 0): ?>
            <tr>
                <td colspan="<?= count($komp_defs)+3 ?>" style="text-align:center;padding:50px;color:#475569;">
                    <i class="fas fa-users-slash" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.3;color:#6366f1;"></i>
                    Belum ada siswa bimbingan dengan status disetujui.
                </td>
            </tr>
            <?php else: while($s = mysqli_fetch_assoc($q)):
                $sid = $s['id'];
                $vals = [];
                foreach(array_keys($komp_defs) as $kk) {
                    $r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nilai FROM kompetensi_siswa WHERE siswa_id=$sid AND kompetensi_key='$kk'"));
                    $vals[$kk] = $r ? (int)$r['nilai'] : 0;
                }
                $avg = count($vals) ? round(array_sum($vals)/count($vals)) : 0;
                $avg_color = $avg>=80?'#4ade80':($avg>=60?'#f59e0b':'#ef4444');
            ?>
            <form method="POST">
                <input type="hidden" name="siswa_id" value="<?= $sid ?>">
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a78bfa);
                                display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;color:#fff;flex-shrink:0;">
                                <?= strtoupper(substr($s['nama_depan'],0,1).substr($s['nama_belakang'],0,1)) ?>
                            </div>
                            <span style="font-weight:600;color:#f1f5f9;font-size:.85rem;">
                                <?= htmlspecialchars($s['nama_depan'].' '.$s['nama_belakang']) ?>
                            </span>
                        </div>
                    </td>
                    <?php
                    $input_names = ['analisis'=>'n_analisis','database'=>'n_db','frontend'=>'n_front','backend'=>'n_back','git'=>'n_git'];
                    foreach($komp_defs as $kk=>$kd):
                    ?>
                    <td style="text-align:center;">
                        <input type="number" name="<?= $input_names[$kk] ?>" class="input-nilai"
                          value="<?= $vals[$kk] ?>" min="0" max="100"
                          style="border-color:<?= $kd['color'] ?>33;">
                    </td>
                    <?php endforeach; ?>
                    <td style="text-align:center;">
                        <span style="font-size:1.1rem;font-weight:800;color:<?= $avg_color ?>;"><?= $avg ?></span>
                        <div class="progress-bar" style="max-width:60px;margin:4px auto 0;">
                            <div class="progress-fill" style="width:<?= $avg ?>%;background:<?= $avg_color ?>;"></div>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:5px;justify-content:center;">
                            <button type="submit" name="simpan_kompetensi" class="btn-save" title="Simpan">
                                <i class="fas fa-floppy-disk"></i>
                            </button>
                            <a href="?reset=<?= $sid ?>" class="btn-aksi btn-aksi-red"
                               onclick="return confirm('Reset semua nilai kompetensi siswa ini?')" title="Reset Nilai">
                                <i class="fas fa-rotate"></i>
                            </a>
                        </div>
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
    width:62px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
    color:#fff;padding:7px 4px;border-radius:8px;text-align:center;font-weight:700;font-size:.86rem;
}
.input-nilai::-webkit-inner-spin-button,.input-nilai::-webkit-outer-spin-button { opacity:1;height:26px; }
.input-nilai:focus { outline:none; }
.btn-save {
    display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;
    background:#6366f1;color:#fff;border:none;border-radius:7px;font-size:.82rem;cursor:pointer;transition:.2s;
}
.btn-save:hover { background:#4f46e5; }
.btn-aksi { display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;font-size:.82rem;border:1px solid;transition:.2s;cursor:pointer;text-decoration:none; }
.btn-aksi-red { color:#ef4444;border-color:rgba(239,68,68,.25);background:rgba(239,68,68,.08); }
.btn-aksi-red:hover { background:rgba(239,68,68,.2); }
th { white-space:normal;line-height:1.4; }
</style>
