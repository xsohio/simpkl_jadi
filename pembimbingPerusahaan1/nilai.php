<?php
require_once 'config.php';
requireRole('pembimbing');
$activePage = 'nilai';
$pageTitle  = 'Input Nilai PKL';
$pid = getPembimbingId();
$pdo = getDB();
$siswaList = getSiswaBimbingan($pid);

// Handle simpan nilai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['siswa_id'])) {
    $siswa_id         = (int)$_POST['siswa_id'];
    $nilai_sikap      = min(100, max(0, (int)$_POST['nilai_sikap']));
    $nilai_keterampilan = min(100, max(0, (int)$_POST['nilai_keterampilan']));
    $nilai_laporan    = min(100, max(0, (int)$_POST['nilai_laporan']));
    $catatan          = trim($_POST['catatan'] ?? '');

    // Hitung nilai akhir: sikap 30%, keterampilan 40%, laporan 30%
    $nilai_akhir = round($nilai_sikap * 0.30 + $nilai_keterampilan * 0.40 + $nilai_laporan * 0.30, 2);
    $predikat = $nilai_akhir >= 90 ? 'A' : ($nilai_akhir >= 80 ? 'B' : ($nilai_akhir >= 70 ? 'C' : 'D'));

    // Upsert
    $check = $pdo->prepare("SELECT id FROM nilai_pkl WHERE siswa_id=? AND pembimbing_id=?");
    $check->execute([$siswa_id, $pid]);
    if ($check->fetch()) {
        $stmt = $pdo->prepare("UPDATE nilai_pkl SET nilai_sikap=?,nilai_keterampilan=?,nilai_laporan=?,nilai_akhir=?,predikat=?,catatan=? WHERE siswa_id=? AND pembimbing_id=?");
        $stmt->execute([$nilai_sikap,$nilai_keterampilan,$nilai_laporan,$nilai_akhir,$predikat,$catatan,$siswa_id,$pid]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO nilai_pkl (siswa_id,pembimbing_id,nilai_sikap,nilai_keterampilan,nilai_laporan,nilai_akhir,predikat,catatan) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$siswa_id,$pid,$nilai_sikap,$nilai_keterampilan,$nilai_laporan,$nilai_akhir,$predikat,$catatan]);
    }
    $_SESSION['toast'] = ['msg'=>'Nilai berhasil disimpan!','type'=>'success'];
    header('Location: nilai.php'); exit;
}

// Ambil nilai existing
$nilaiMap = [];
if (!empty($siswaList)) {
    $ids = implode(',', array_column($siswaList, 'id'));
    $stmt = $pdo->query("SELECT * FROM nilai_pkl WHERE pembimbing_id=$pid AND siswa_id IN ($ids)");
    foreach ($stmt->fetchAll() as $n) $nilaiMap[$n['siswa_id']] = $n;
}

$filterSiswa = (int)($_GET['siswa_id'] ?? 0);

include 'partials/header.php';
?>

<div class="breadcrumb"><i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:.6rem"></i> <span>Input Nilai PKL</span></div>

<div class="panel-full">
  <div class="panel-header">
    <div style="display:flex;align-items:center;gap:15px">
      <div style="background:rgba(74,222,128,.1);padding:10px;border-radius:10px"><i class="fas fa-star" style="color:#4ade80;font-size:1.5rem"></i></div>
      <div>
        <h3 style="margin:0;color:#f1f5f9">Input Nilai PKL</h3>
        <p style="margin:0;color:#64748b;font-size:.8rem">Berikan penilaian untuk setiap siswa bimbingan</p>
      </div>
    </div>
  </div>

  <?php if(empty($siswaList)): ?>
  <div class="empty-state"><i class="fas fa-star"></i><p>Tidak ada siswa bimbingan</p></div>
  <?php else: foreach($siswaList as $s):
    if ($filterSiswa && $s['id'] != $filterSiswa) continue;
    $n = $nilaiMap[$s['id']] ?? null;
    $inisial = initials($s['nama_depan'], $s['nama_belakang']);
    $color   = avatarColor($s['id']);
    $nama    = htmlspecialchars($s['nama_depan'].' '.$s['nama_belakang']);
    $vs  = $n['nilai_sikap'] ?? 0;
    $vk  = $n['nilai_keterampilan'] ?? 0;
    $vl  = $n['nilai_laporan'] ?? 0;
    $va  = $n['nilai_akhir'] ?? 0;
    $vp  = $n['predikat'] ?? '-';
    $predColor = ['A'=>'#4ade80','B'=>'#818cf8','C'=>'#f59e0b','D'=>'#ef4444'][$vp] ?? '#94a3b8';
  ?>
  <div class="nilai-card" id="card-<?=$s['id']?>">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:38px;height:38px;border-radius:50%;background:<?=$color?>22;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:<?=$color?>"><?=$inisial?></div>
        <div>
          <div style="font-weight:600;color:#f1f5f9"><?=$nama?></div>
          <div style="font-size:.72rem;color:#64748b"><?= htmlspecialchars($s['kelas']??'-') ?> · <?= htmlspecialchars($s['nama_perusahaan']??'-') ?></div>
        </div>
      </div>
      <div style="text-align:right">
        <div style="font-size:1.8rem;font-weight:700;color:#f1f5f9" id="na-<?=$s['id']?>"><?=$va?></div>
        <div style="font-size:.72rem;font-weight:600;color:<?=$predColor?>" id="pred-<?=$s['id']?>"><?=$vp !== '-' ? 'Predikat '.$vp : '-'?></div>
      </div>
    </div>

    <form method="POST">
      <input type="hidden" name="siswa_id" value="<?=$s['id']?>">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:14px">
        <?php
        $fields = [
          ['nilai_sikap',       'Sikap & Kedisiplinan', 30, $vs],
          ['nilai_keterampilan','Kompetensi / Keterampilan', 40, $vk],
          ['nilai_laporan',     'Laporan PKL', 30, $vl],
        ];
        foreach($fields as [$fname,$flabel,$fweight,$fval]):
        ?>
        <div>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span style="font-size:.75rem;color:#64748b"><?=$flabel?> <span style="color:#334155">(<?=$fweight?>%)</span></span>
            <span style="font-size:.8rem;font-weight:600;color:#f1f5f9" id="val-<?=$s['id']?>-<?=$fname?>"><?=$fval?></span>
          </div>
          <input type="range" class="nilai-slider" name="<?=$fname?>" min="0" max="100" value="<?=$fval?>"
            oninput="updateNilai(<?=$s['id']?>, this)">
          <input type="number" class="form-control" style="margin-top:6px;padding:6px 10px;font-size:.8rem" name="<?=$fname?>_num" min="0" max="100" value="<?=$fval?>"
            oninput="syncSlider(<?=$s['id']?>, '<?=$fname?>', this.value)">
        </div>
        <?php endforeach; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Catatan</label>
        <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan untuk nilai siswa..."><?= htmlspecialchars($n['catatan']??'') ?></textarea>
      </div>
      <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save"></i> Simpan Nilai</button>
    </form>
  </div>
  <?php endforeach; endif; ?>
</div>

<script>
function calcNilai(id) {
  const form = document.getElementById('card-'+id).querySelector('form');
  const s = parseInt(form.querySelector('[name="nilai_sikap"]').value)||0;
  const k = parseInt(form.querySelector('[name="nilai_keterampilan"]').value)||0;
  const l = parseInt(form.querySelector('[name="nilai_laporan"]').value)||0;
  const na = Math.round(s*0.30 + k*0.40 + l*0.30);
  document.getElementById('na-'+id).textContent = na;
  const pred = na>=90?'A':na>=80?'B':na>=70?'C':'D';
  const colors = {A:'#4ade80',B:'#818cf8',C:'#f59e0b',D:'#ef4444'};
  const el = document.getElementById('pred-'+id);
  el.textContent = 'Predikat '+pred;
  el.style.color = colors[pred];
}

function updateNilai(id, slider) {
  const name = slider.name;
  const val  = slider.value;
  const card = document.getElementById('card-'+id);
  card.querySelector('[name="'+name+'_num"]').value = val;
  card.querySelector('#val-'+id+'-'+name).textContent = val;
  calcNilai(id);
}

function syncSlider(id, name, val) {
  const card = document.getElementById('card-'+id);
  card.querySelector('[name="'+name+'"]').value = val;
  card.querySelector('#val-'+id+'-'+name).textContent = val;
  calcNilai(id);
}
</script>

<?php include 'partials/footer.php'; ?>
