<?php
require_once 'config.php';
requireRole('pembimbing');
$activePage = 'absensi';
$pageTitle  = 'Absensi PKL';
$pid = getPembimbingId();
$pdo = getDB();
$siswaList = getSiswaBimbingan($pid);

$selectedSiswa = $_GET['siswa_id'] ?? ($siswaList[0]['id'] ?? null);
$bulan = (int)($_GET['bulan'] ?? date('m'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$absensiData = [];
$rekapBulan  = [];
if ($selectedSiswa) {
    $stmt = $pdo->prepare("SELECT * FROM absensi WHERE siswa_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=? ORDER BY tanggal");
    $stmt->execute([$selectedSiswa, $bulan, $tahun]);
    foreach($stmt->fetchAll() as $row) {
        $absensiData[date('j', strtotime($row['tanggal']))] = $row;
    }
    // Rekap total
    $stmtR = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM absensi WHERE siswa_id=? GROUP BY status");
    $stmtR->execute([$selectedSiswa]);
    foreach($stmtR->fetchAll() as $r) $rekapBulan[$r['status']] = $r['cnt'];
}

$bulanNama = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$firstDow = date('N', mktime(0,0,0,$bulan,1,$tahun)); // 1=Mon..7=Sun
$firstDow = $firstDow % 7; // adjust to 0=Sun

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Absensi PKL</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(74,222,128,.1);padding:10px;border-radius:10px"><i class="fas fa-calendar-check" style="color:#4ade80;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Monitoring Absensi PKL</h3>
        <p style="margin:0;color:#64748b;font-size:.8rem">Rekap kehadiran siswa selama pelaksanaan PKL</p>
      </div>
    </div>
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <select name="siswa_id" class="form-control" style="width:auto" onchange="this.form.submit()">
        <?php foreach($siswaList as $s):
          $selected = $s['id'] == $selectedSiswa ? 'selected' : '';
        ?>
        <option value="<?=$s['id']?>" <?=$selected?>><?= htmlspecialchars($s['nama_depan'].' '.$s['nama_belakang']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="bulan" class="form-control" style="width:auto" onchange="this.form.submit()">
        <?php for($m=1;$m<=12;$m++): ?>
        <option value="<?=$m?>" <?=$m===$bulan?'selected':''?>><?=$bulanNama[$m]?></option>
        <?php endfor; ?>
      </select>
      <select name="tahun" class="form-control" style="width:auto" onchange="this.form.submit()">
        <?php for($y=2024;$y<=2026;$y++): ?>
        <option value="<?=$y?>" <?=$y===$tahun?'selected':''?>><?=$y?></option>
        <?php endfor; ?>
      </select>
    </form>
  </div>

  <?php if(empty($siswaList)): ?>
  <div class="empty-state"><i class="fas fa-calendar"></i><p>Tidak ada siswa bimbingan</p></div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <h3 style="font-size:.9rem;color:#94a3b8"><?=$bulanNama[$bulan]?> <?=$tahun?></h3>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <?php foreach(['Hadir'=>'#4ade80','Izin'=>'#f59e0b','Sakit'=>'#818cf8','Alpa'=>'#ef4444'] as $s=>$c): ?>
          <div style="display:flex;align-items:center;gap:5px;font-size:.72rem;color:<?=$c?>"><div style="width:10px;height:10px;border-radius:3px;background:<?=$c?>33"></div><?=$s?></div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="absensi-calendar">
        <?php foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $h): ?>
        <div class="cal-header"><?=$h?></div>
        <?php endforeach; ?>

        <?php for($i=0;$i<$firstDow;$i++): ?><div class="cal-day empty"></div><?php endfor; ?>

        <?php for($d=1;$d<=$daysInMonth;$d++):
          $row = $absensiData[$d] ?? null;
          $cls = 'cal-day';
          if ($row) {
            $cls .= ' ' . match($row['status']) {
              'Hadir' => 'hadir', 'Izin' => 'izin', 'Sakit' => 'sakit', 'Alpa' => 'alpha', default => ''
            };
          }
          $today = (date('Y-m-d') === sprintf('%04d-%02d-%02d',$tahun,$bulan,$d));
          if ($today) $cls .= ' today';
          $dow = ($firstDow + $d - 1) % 7;
          if ($dow === 0 || $dow === 6) $cls .= ' libur';
          $title = $row ? $row['status'].($row['keterangan']?' – '.$row['keterangan']:'') : '';
        ?>
        <div class="<?= $cls ?>" title="<?= htmlspecialchars($title) ?>"><?=$d?></div>
        <?php endfor; ?>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px">
      <div style="background:#162032;border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:16px">
        <div style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px">Rekap Total</div>
        <?php foreach(['Hadir'=>['#4ade80','fa-check-circle'],'Izin'=>['#f59e0b','fa-envelope'],'Sakit'=>['#818cf8','fa-procedures'],'Alpa'=>['#ef4444','fa-times-circle']] as $s=>[$c,$ic]):
          $cnt = $rekapBulan[$s] ?? 0; ?>
        <div class="detail-row">
          <span class="detail-label"><i class="fas <?=$ic?>" style="color:<?=$c?>;margin-right:6px"></i><?=$s?></span>
          <span class="detail-value" style="color:<?=$c?>"><?=$cnt?> hari</span>
        </div>
        <?php endforeach; ?>
        <div class="detail-row">
          <span class="detail-label" style="font-weight:700;color:#f1f5f9">Total</span>
          <span class="detail-value" style="font-weight:700;color:#f1f5f9"><?= array_sum($rekapBulan) ?> hari</span>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>
