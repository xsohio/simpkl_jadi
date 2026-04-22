<?php
session_start();
include "../config.php";
requireAdmin();
$active_page = 'pembimbing';
$page_title  = 'Pembimbing PKL';

// --- PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $res = mysqli_query($conn, "SELECT nama FROM pembimbing_pkl WHERE id_pembimbing=$id");
    $data = mysqli_fetch_assoc($res);
    $nama = $data['nama'] ?? 'Unknown';

    if (mysqli_query($conn, "DELETE FROM pembimbing_pkl WHERE id_pembimbing=$id")) {
        catatLog($conn, "Menghapus pembimbing: $nama");
        setFlash('success','Data pembimbing berhasil dihapus.'); 
    }
    redirect('admin-pembimbing.php');
}

// --- PROSES SIMPAN ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['simpan'])) {
    $id = (int)($_POST['id']??0);
    $nama = clean($_POST["nama"]);
    $jabatan = clean($_POST["jabatan"]);
    $jab_terakhir = clean($_POST["jab_terakhir"]);
    $ttl = clean($_POST["ttl"]);
    $alamat = clean($_POST["alamat"]);
    $no_kontak = clean($_POST["no_kontak"]);
    $id_perusahaan = (int)$_POST["id_perusahaan"];

    if ($id == 0) {
        $sql = "INSERT INTO pembimbing_pkl (nama, jabatan, jab_terakhir, ttl, alamat, no_kontak, id_perusahaan) 
                VALUES ('$nama','$jabatan','$jab_terakhir','$ttl','$alamat','$no_kontak',$id_perusahaan)";
        $msg = "Menambah pembimbing baru: $nama";
    } else {
        $sql = "UPDATE pembimbing_pkl SET nama='$nama', jabatan='$jabatan', jab_terakhir='$jab_terakhir', 
                ttl='$ttl', alamat='$alamat', no_kontak='$no_kontak', id_perusahaan=$id_perusahaan 
                WHERE id_pembimbing=$id";
        $msg = "Mengubah data pembimbing: $nama";
    }

    if (mysqli_query($conn, $sql)) {
        catatLog($conn, $msg);
        setFlash('success', 'Data berhasil disimpan.');
    }
    redirect('admin-pembimbing.php');
}


$prs_opt = mysqli_query($conn,'SELECT * FROM perusahaan ORDER BY Nama_perusahaan');

$rows = mysqli_query($conn, "SELECT pb.*,p.Nama_perusahaan FROM pembimbing_pkl pb LEFT JOIN perusahaan p ON pb.id_perusahaan=p.id_perusahaan ORDER BY pb.id_pembimbing DESC");
include '_header_admin.php';
?>
<div class="page-header">
  <h2><i class="fas fa-chalkboard-teacher"></i> Pembimbing PKL</h2>
  <button class="btn btn-primary btn-sm" onclick="document.getElementById('modal_pembimbing').classList.add('active')">
    <i class="fas fa-plus"></i> Tambah
  </button>
</div>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>#</th><th>Nama</th><th>Jabatan</th><th>Pend. Terakhir</th><th>No. Kontak</th><th>Perusahaan</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php $no=1; while($row=mysqli_fetch_assoc($rows)): ?>
      <tr>
        <td><?php echo $no++;?></td>
        <td><?php echo htmlspecialchars($row['nama']??'-');?></td><td><?php echo htmlspecialchars($row['jabatan']??'-');?></td><td><?php echo htmlspecialchars($row['jab_terakhir']??'-');?></td><td><?php echo htmlspecialchars($row['no_kontak']??'-');?></td><td><?php echo htmlspecialchars($row['Nama_perusahaan']??'-');?></td>
        <td style="display:flex;gap:6px;">
          <a href="?hapus=<?php echo $row['id_pembimbing'];?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Hapus data ini?')"><i class="fas fa-trash"></i></a>
        </td>
      </tr>
      <?php endwhile;?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="modal_pembimbing">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-chalkboard-teacher" style="margin-right:8px;color:#94a3b8;"></i> Tambah Pembimbing PKL</h3>
      <button class="modal-close" onclick="document.getElementById('modal_pembimbing').classList.remove('active')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="id" value="0"/>
      
      <div class="form-group"><label>Nama Lengkap</label>
        <input type="text" name="nama" class="form-control" placeholder="Nama pembimbing"/>
      </div>
      <div class="form-group"><label>Jabatan</label>
        <input type="text" name="jabatan" class="form-control" placeholder="Supervisor, dll."/>
      </div>
      <div class="form-group"><label>Pendidikan Terakhir</label>
        <input type="text" name="jab_terakhir" class="form-control" placeholder="S1, D3, dll."/>
      </div>
      <div class="form-group"><label>Tgl. Lahir</label>
        <input type="date" name="ttl" class="form-control" />
      </div>
      <div class="form-group"><label>Alamat</label>
        <input type="text" name="alamat" class="form-control" placeholder="Alamat lengkap"/>
      </div>
      <div class="form-group"><label>No. Kontak</label>
        <input type="text" name="no_kontak" class="form-control" placeholder="08xx"/>
      </div>
      <div class="form-group"><label>Perusahaan</label>
        <select name="id_perusahaan" class="form-control">
          <?php while($rp=mysqli_fetch_assoc($prs_opt)): ?>
          <option value="<?php echo $rp['id_perusahaan'];?>"><?php echo htmlspecialchars($rp['Nama_perusahaan']);?></option>
          <?php endwhile;?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('modal_pembimbing').classList.remove('active')">Batal</button>
        <button type="submit" name="simpan" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php include '_footer_admin.php'; ?>
