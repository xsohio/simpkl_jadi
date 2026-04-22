<?php
// wakasek/log-aktivitas.php
require_once 'config.php';
requireRole('wakasek');

$activePage = 'log';
$pageTitle  = 'Log Aktivitas';
$wid = getWakasekId();
$pdo = getDB();

// Insert log: buka halaman ini
require_once 'log_helper.php';
logAktivitas($pdo, $wid, 'Melihat halaman log aktivitas', 'wakasek');

// Filter
$filter_jenis  = $_GET['jenis']  ?? 'semua';
$filter_dari   = $_GET['dari']   ?? '';
$filter_sampai = $_GET['sampai'] ?? '';
$cari          = trim($_GET['q'] ?? '');

$where  = ["la.id_users = ?"];
$params = [$wid];

if ($filter_dari)   { $where[] = "DATE(la.waktu) >= ?"; $params[] = $filter_dari; }
if ($filter_sampai) { $where[] = "DATE(la.waktu) <= ?"; $params[] = $filter_sampai; }
if ($cari)          { $where[] = "la.aktivitas LIKE ?"; $params[] = "%$cari%"; }
if ($filter_jenis !== 'semua') {
    $keyword_map = [
        'login'    => ['login','logout'],
        'jurnal'   => ['jurnal'],
        'laporan'  => ['laporan'],
        'nilai'    => ['nilai'],
        'pengajuan'=> ['pengajuan'],
        'penempatan'=> ['penempatan'],
    ];
    if (isset($keyword_map[$filter_jenis])) {
        $kws = $keyword_map[$filter_jenis];
        $sub = implode(' OR ', array_fill(0, count($kws), 'la.aktivitas LIKE ?'));
        $where[] = "($sub)";
        foreach ($kws as $kw) $params[] = "%$kw%";
    }
}

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT la.*, u.nama_depan, u.nama_belakang
    FROM log_aktivitas la
    JOIN users u ON u.id = la.id_users
    WHERE $whereSQL
    ORDER BY la.waktu DESC
    LIMIT 100
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Statistik
$stmtTotal  = $pdo->prepare("SELECT COUNT(*) FROM log_aktivitas WHERE id_users=?");
$stmtTotal->execute([$wid]); $totalLog = $stmtTotal->fetchColumn();

$stmtHari   = $pdo->prepare("SELECT COUNT(*) FROM log_aktivitas WHERE id_users=? AND DATE(waktu)=CURDATE()");
$stmtHari->execute([$wid]); $logHariIni = $stmtHari->fetchColumn();

$stmtMinggu = $pdo->prepare("SELECT COUNT(*) FROM log_aktivitas WHERE id_users=? AND waktu >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmtMinggu->execute([$wid]); $logMingguIni = $stmtMinggu->fetchColumn();

// Hapus log
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $idLog = (int)$_GET['hapus'];
    $chk = $pdo->prepare("SELECT id_log FROM log_aktivitas WHERE id_log=? AND id_users=?");
    $chk->execute([$idLog, $wid]);
    if ($chk->fetch()) {
        $pdo->prepare("DELETE FROM log_aktivitas WHERE id_log=?")->execute([$idLog]);
        header('Location: log-aktivitas.php?success=1'); exit;
    }
}

// Tambah log manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_log'])) {
    $aktivitas_baru = trim($_POST['aktivitas_manual'] ?? '');
    if ($aktivitas_baru !== '') {
        logAktivitas($pdo, $wid, $aktivitas_baru, 'wakasek');
    }
    header('Location: log-aktivitas.php?added=1'); exit;
}

function getLogStyle(string $aktivitas): array {
    $a = strtolower($aktivitas);
    if (str_contains($a,'login'))      return ['#4ade80','rgba(74,222,128,.12)','fa-sign-in-alt'];
    if (str_contains($a,'logout'))     return ['#f87171','rgba(239,68,68,.12)','fa-sign-out-alt'];
    if (str_contains($a,'jurnal'))     return ['#f59e0b','rgba(245,158,11,.12)','fa-book-open'];
    if (str_contains($a,'laporan'))    return ['#ef4444','rgba(239,68,68,.12)','fa-file-alt'];
    if (str_contains($a,'nilai'))      return ['#4ade80','rgba(74,222,128,.12)','fa-star'];
    if (str_contains($a,'pengajuan'))  return ['#f59e0b','rgba(245,158,11,.12)','fa-file-signature'];
    if (str_contains($a,'penempatan')) return ['#2dd4bf','rgba(20,184,166,.12)','fa-map-marker-alt'];
    if (str_contains($a,'absensi'))    return ['#2dd4bf','rgba(20,184,166,.12)','fa-calendar-check'];
    return ['#94a3b8','rgba(100,116,139,.12)','fa-fingerprint'];
}

include 'partials/header.php';
?>

<div class="breadcrumb">
  <i class="fas fa-home"></i>
  <i class="fas fa-chevron-right" style="font-size:.6rem"></i>
  <span>Log Aktivitas</span>
</div>

<?php if (isset($_GET['success'])): ?>
<div style="background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.25);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:.85rem;color:#4ade80;">
  <i class="fas fa-check-circle"></i> Log berhasil dihapus.
</div>
<?php endif; ?>

<?php if (isset($_GET['added'])): ?>
<div style="background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.3);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:.85rem;color:#818cf8;">
  <i class="fas fa-check-circle"></i> Log aktivitas berhasil ditambahkan.
</div>
<?php endif; ?>

<!-- Form Tambah Log Manual -->
<div class="panel" style="margin-bottom:20px;padding:18px 20px">
  <div class="panel-header" style="margin-bottom:14px">
    <h3><i class="fas fa-plus-circle" style="color:#4ade80"></i> Tambah Catatan Aktivitas</h3>
  </div>
  <form method="POST" action="" style="display:flex;gap:10px;align-items:flex-end">
    <div style="flex:1">
      <label style="font-size:.72rem;color:#64748b;display:block;margin-bottom:6px">Deskripsi Aktivitas</label>
      <input type="text" name="aktivitas_manual" required
             placeholder="Contoh: Melakukan rapat koordinasi PKL dengan pembimbing..."
             style="width:100%;background:#162032;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:10px 14px;color:#e2e8f0;font-family:'Poppins',sans-serif;font-size:.85rem;outline:none;transition:.2s"
             onfocus="this.style.borderColor='rgba(99,102,241,.5)'"
             onblur="this.style.borderColor='rgba(255,255,255,.08)'">
    </div>
    <button type="submit" name="tambah_log" class="btn btn-success btn-sm" style="white-space:nowrap;height:40px">
      <i class="fas fa-plus"></i> Tambah Log
    </button>
  </form>
</div>

<!-- Stat Cards -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card delay-1">
    <div class="card-icon" style="background:rgba(99,102,241,.15)"><i class="fas fa-history" style="color:#818cf8"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Total Log</div>
    <div class="number"><?= number_format($totalLog) ?></div>
    <div class="trend"><i class="fas fa-database" style="color:#818cf8;font-size:.5rem"></i> Semua waktu</div>
  </div>
  <div class="stat-card delay-2">
    <div class="card-icon" style="background:rgba(74,222,128,.15)"><i class="fas fa-calendar-day" style="color:#4ade80"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Log Hari Ini</div>
    <div class="number"><?= $logHariIni ?></div>
    <div class="trend"><i class="fas fa-circle" style="color:#4ade80;font-size:.5rem"></i> <?= date('d M Y') ?></div>
  </div>
  <div class="stat-card delay-3">
    <div class="card-icon" style="background:rgba(245,158,11,.15)"><i class="fas fa-calendar-week" style="color:#f59e0b"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Log Minggu Ini</div>
    <div class="number"><?= $logMingguIni ?></div>
    <div class="trend"><i class="fas fa-clock" style="color:#f59e0b;font-size:.5rem"></i> 7 hari terakhir</div>
  </div>
  <div class="stat-card delay-4">
    <div class="card-icon" style="background:rgba(239,68,68,.15)"><i class="fas fa-filter" style="color:#ef4444"></i></div>
    <div style="font-size:.8rem;color:#94a3b8">Hasil Filter</div>
    <div class="number"><?= count($logs) ?></div>
    <div class="trend"><i class="fas fa-search" style="color:#ef4444;font-size:.5rem"></i> Ditampilkan</div>
  </div>
</div>

<!-- Filter Bar -->
<div class="panel" style="margin-bottom:20px;padding:18px 20px">
  <form method="GET" action="">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;flex-wrap:wrap">
      <div>
        <label style="font-size:.72rem;color:#64748b;display:block;margin-bottom:6px">Cari Aktivitas</label>
        <div class="search-bar" style="margin:0">
          <i class="fas fa-search"></i>
          <input type="text" name="q" value="<?= htmlspecialchars($cari) ?>" placeholder="Cari aktivitas…">
        </div>
      </div>
      <div>
        <label style="font-size:.72rem;color:#64748b;display:block;margin-bottom:6px">Dari Tanggal</label>
        <input type="date" name="dari" value="<?= htmlspecialchars($filter_dari) ?>"
               style="width:100%;background:#162032;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:9px 12px;color:#e2e8f0;font-family:'Poppins',sans-serif;font-size:.82rem">
      </div>
      <div>
        <label style="font-size:.72rem;color:#64748b;display:block;margin-bottom:6px">Sampai Tanggal</label>
        <input type="date" name="sampai" value="<?= htmlspecialchars($filter_sampai) ?>"
               style="width:100%;background:#162032;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:9px 12px;color:#e2e8f0;font-family:'Poppins',sans-serif;font-size:.82rem">
      </div>
      <div style="display:flex;gap:8px">
        <button type="submit" class="btn btn-indigo btn-sm"><i class="fas fa-search"></i> Cari</button>
        <a href="log-aktivitas.php" class="btn btn-sm" style="background:rgba(255,255,255,.05);color:#94a3b8;border:1px solid rgba(255,255,255,.08)"><i class="fas fa-redo"></i></a>
      </div>
    </div>
    <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
      <?php $jenis_list = ['semua'=>'Semua','login'=>'Login/Logout','jurnal'=>'Jurnal','laporan'=>'Laporan','nilai'=>'Nilai','pengajuan'=>'Pengajuan','penempatan'=>'Penempatan']; ?>
      <?php foreach($jenis_list as $key=>$label): ?>
      <label style="cursor:pointer">
        <input type="radio" name="jenis" value="<?=$key?>" <?= $filter_jenis===$key?'checked':'' ?> style="display:none" onchange="this.form.submit()">
        <span style="
          display:inline-block;padding:5px 14px;border-radius:99px;font-size:.75rem;font-weight:500;
          border:1px solid <?= $filter_jenis===$key ? 'rgba(99,102,241,.4)' : 'rgba(255,255,255,.08)' ?>;
          background:<?= $filter_jenis===$key ? 'rgba(99,102,241,.15)' : 'rgba(255,255,255,.04)' ?>;
          color:<?= $filter_jenis===$key ? '#818cf8' : '#64748b' ?>
        "><?=$label?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </form>
</div>

<!-- Log Timeline -->
<div class="panel">
  <div class="panel-header">
    <h3><i class="fas fa-history" style="color:#818cf8"></i> Riwayat Aktivitas Saya</h3>
    <span style="font-size:.75rem;color:#64748b"><?= count($logs) ?> entri ditemukan</span>
  </div>

  <?php if (empty($logs)): ?>
  <div class="empty-state"><i class="fas fa-history"></i><p>Belum ada log aktivitas yang ditemukan.</p></div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:0">
    <?php
    $prevDate = null;
    foreach ($logs as $i => $log):
      [$color, $bg, $icon] = getLogStyle($log['aktivitas']);
      $thisDate = date('Y-m-d', strtotime($log['waktu']));
      $isNewDay = ($thisDate !== $prevDate);
      $prevDate = $thisDate;
    ?>
    <?php if ($isNewDay): ?>
    <div style="display:flex;align-items:center;gap:12px;padding:16px 0 10px;<?=$i>0?'margin-top:8px':''?>">
      <div style="flex:1;height:1px;background:rgba(255,255,255,.06)"></div>
      <span style="font-size:.7rem;font-weight:600;color:#475569;background:#1e293b;padding:4px 12px;border-radius:99px;border:1px solid rgba(255,255,255,.06);white-space:nowrap">
        <i class="fas fa-calendar-alt" style="margin-right:6px"></i>
        <?= date('l, d F Y', strtotime($log['waktu'])) ?>
      </span>
      <div style="flex:1;height:1px;background:rgba(255,255,255,.06)"></div>
    </div>
    <?php endif; ?>
    <div class="activity-item" style="padding:14px 4px;display:flex;align-items:flex-start;gap:14px;border-bottom:1px solid rgba(255,255,255,.04)">
      <div style="width:38px;height:38px;border-radius:10px;background:<?=$bg?>;color:<?=$color?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px">
        <i class="fas <?=$icon?>" style="font-size:.8rem"></i>
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-size:.875rem;color:#e2e8f0;font-weight:500;line-height:1.4"><?= htmlspecialchars($log['aktivitas']) ?></div>
        <div style="margin-top:5px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
          <span style="font-size:.72rem;color:#64748b">
            <i class="far fa-clock" style="margin-right:4px"></i>
            <?= date('H:i:s', strtotime($log['waktu'])) ?> WIB
          </span>
          <?php if (!empty($log['ip_address'])): ?>
          <span style="font-size:.72rem;color:#475569">
            <i class="fas fa-network-wired" style="margin-right:4px"></i>
            <?= htmlspecialchars($log['ip_address']) ?>
          </span>
          <?php endif; ?>
        </div>
      </div>
      <a href="log-aktivitas.php?hapus=<?=$log['id_log']?><?= $filter_dari?"&dari=$filter_dari":'' ?><?= $filter_sampai?"&sampai=$filter_sampai":'' ?>"
         onclick="return confirm('Hapus entri log ini?')"
         class="btn btn-danger btn-xs"
         style="flex-shrink:0;display:inline-flex;align-items:center;gap:5px"
         title="Hapus log ini">
        <i class="fas fa-trash"></i> Hapus
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div style="margin-top:16px;padding:14px 16px;background:rgba(99,102,241,.07);border:1px solid rgba(99,102,241,.15);border-radius:10px;font-size:.78rem;color:#64748b">
  <i class="fas fa-info-circle" style="color:#818cf8;margin-right:6px"></i>
  Log aktivitas ini menampilkan aktivitas akun Wakasek Anda. Untuk log seluruh pengguna, gunakan panel Admin.
</div>

<?php include 'partials/footer.php'; ?>
