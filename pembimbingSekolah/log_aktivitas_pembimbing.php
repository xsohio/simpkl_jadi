<?php
// ============================================================
//  log_aktivitas_pembimbing.php
//  Halaman Log Aktivitas untuk role Pembimbing — SIMPKL
//  Database: webpkl_fixed
//
//  - Menampilkan log dari tabel log_aktivitas
//    (log milik pembimbing ini + siswa bimbingannya)
//  - Admin tetap bisa lihat SEMUA log via log-aktivitas.php
//  - Tidak mengubah desain apapun yang sudah ada
// ============================================================
session_start();
include "../config.php";
requirePembimbing(); // sesuaikan dengan nama fungsi guard pembimbing di config.php Anda

// Sertakan helper log
$helper_path = file_exists("../log_helper.php") ? "../log_helper.php" : "log_helper.php";
if (file_exists($helper_path)) include $helper_path;

$pid = (int)$_SESSION['user']['id_user'];

// ---- Proses hapus log (hanya log milik sendiri) ----
if (isset($_GET['hapus'])) {
    $id_log = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM log_aktivitas WHERE id_log = $id_log AND id_users = $pid");
    header("Location: log_aktivitas_pembimbing.php?deleted=1");
    exit;
}

$active_page = 'log';
$page_title  = 'Log Aktivitas';
include '_header_pembimbing.php';
?>

<!-- Header halaman — mengikuti gaya welcome-section yang sama dengan dashboard_pembimbing.php -->
<div class="welcome-section delay-1">
  <div>
    <h1>
      <i class="fas fa-clock-rotate-left" style="color:#93c5fd;margin-right:8px;"></i>
      Log Aktivitas
    </h1>
    <p>Rekam jejak aktivitas Anda dan siswa bimbingan.</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:99px;
      padding:8px 16px;font-size:.78rem;color:#64748b;">
      <i class="fas fa-calendar-day" style="color:#6366f1;margin-right:5px;"></i>
      <?= date('l, d F Y') ?>
    </div>
  </div>
</div>

<?php if (isset($_GET['deleted'])): ?>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:10px;
  padding:10px 16px;margin-bottom:16px;font-size:.82rem;color:#ef4444;">
  <i class="fas fa-check-circle" style="margin-right:6px;"></i> Log berhasil dihapus.
</div>
<?php endif; ?>

<div class="panel delay-2">
  <div class="panel-header">
    <h3><i class="fas fa-clock-rotate-left" style="color:#93c5fd;"></i> Riwayat Aktivitas</h3>
    <span style="font-size:.75rem;color:#64748b;">Aktivitas Anda &amp; siswa bimbingan</span>
  </div>
  <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
      <thead>
        <tr style="border-bottom:1px solid rgba(255,255,255,.07);">
          <th style="padding:10px 12px;text-align:left;color:#64748b;font-weight:500;width:40px;">No</th>
          <th style="padding:10px 12px;text-align:left;color:#64748b;font-weight:500;">Waktu</th>
          <th style="padding:10px 12px;text-align:left;color:#64748b;font-weight:500;">Pengguna</th>
          <th style="padding:10px 12px;text-align:left;color:#64748b;font-weight:500;">Role</th>
          <th style="padding:10px 12px;text-align:left;color:#64748b;font-weight:500;">Aktivitas</th>
          <th style="padding:10px 12px;text-align:center;color:#64748b;font-weight:500;">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php
      // ---- Query sesuai skema webpkl_fixed ----
      // users: id, nama_depan, nama_belakang, role
      // log_aktivitas: id_log, id_users, aktivitas, waktu
      // pkl_pengajuan: pembimbing_id, ketua_id
      $res_log = mysqli_query($conn, "
          SELECT
              la.id_log,
              la.aktivitas,
              la.waktu,
              u.nama_depan,
              u.nama_belakang,
              u.role,
              IF(la.id_users = $pid, 1, 0) AS is_mine
          FROM log_aktivitas la
          LEFT JOIN users u ON la.id_users = u.id
          WHERE la.id_users = $pid
             OR la.id_users IN (
                 SELECT ketua_id FROM pkl_pengajuan WHERE pembimbing_id = $pid
             )
          ORDER BY la.waktu DESC
      ");

      $role_style = [
          'admin'      => ['icon' => 'fa-crown',           'color' => '#f59e0b'],
          'pembimbing' => ['icon' => 'fa-chalkboard-user', 'color' => '#93c5fd'],
          'wakasek'    => ['icon' => 'fa-user-tie',        'color' => '#a78bfa'],
          'siswa'      => ['icon' => 'fa-user-graduate',   'color' => '#c4b5fd'],
      ];

      $no = 1;
      if ($res_log && mysqli_num_rows($res_log) > 0):
          while ($row = mysqli_fetch_assoc($res_log)):
              $akt = strtolower($row['aktivitas']);
              if (str_contains($akt, 'hapus') || str_contains($akt, 'tolak')) {
                  $lc = '#ef4444'; $li = 'fa-circle-xmark';
              } elseif (
                  str_contains($akt, 'validasi') || str_contains($akt, 'setujui') ||
                  str_contains($akt, 'simpan')   || str_contains($akt, 'tambah')  ||
                  str_contains($akt, 'input')    || str_contains($akt, 'kirim')   ||
                  str_contains($akt, 'mengirim') || str_contains($akt, 'bimbingan')
              ) {
                  $lc = '#4ade80'; $li = 'fa-circle-check';
              } else {
                  $lc = '#93c5fd'; $li = 'fa-circle-dot';
              }
              $role = $row['role'] ?? '';
              $rs   = $role_style[$role] ?? ['icon' => 'fa-user', 'color' => '#64748b'];
              $nama = trim(($row['nama_depan'] ?? '') . ' ' . ($row['nama_belakang'] ?? '')) ?: 'Sistem';
      ?>
      <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
        <td style="padding:9px 12px;color:#475569;"><?= $no++ ?></td>
        <td style="padding:9px 12px;white-space:nowrap;color:#94a3b8;">
          <?= date('d M Y, H:i', strtotime($row['waktu'])) ?>
        </td>
        <td style="padding:9px 12px;color:#cbd5e1;"><?= htmlspecialchars($nama) ?></td>
        <td style="padding:9px 12px;">
          <span style="font-size:.72rem;color:<?= $rs['color'] ?>;">
            <i class="fas <?= $rs['icon'] ?>" style="margin-right:3px;"></i>
            <?= ucfirst($role ?: 'sistem') ?>
          </span>
        </td>
        <td style="padding:9px 12px;">
          <span style="display:flex;align-items:center;gap:6px;">
            <i class="fas <?= $li ?>" style="color:<?= $lc ?>;font-size:.75rem;flex-shrink:0;"></i>
            <span style="color:#cbd5e1;"><?= htmlspecialchars($row['aktivitas']) ?></span>
          </span>
        </td>
        <td style="padding:9px 12px;text-align:center;">
          <?php if ($row['is_mine']): ?>
          <a href="log_aktivitas_pembimbing.php?hapus=<?= $row['id_log'] ?>"
             onclick="return confirm('Hapus log ini?')"
             style="color:#ef4444;font-size:.78rem;text-decoration:none;">
            <i class="fas fa-trash"></i>
          </a>
          <?php else: ?>
          <span style="color:#334155;font-size:.72rem;">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr>
        <td colspan="6" style="text-align:center;padding:30px;color:#475569;">
          <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px;color:#334155;"></i>
          Belum ada log aktivitas yang tercatat.
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
