<?php
// ============================================================
//  login.php — Halaman Login SIMPKL
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
include "config.php";

// Jika sudah login, redirect sesuai role
if (isset($_SESSION['user'])) {
    $r = $_SESSION['user']['role'];
    if ($r === 'admin')      redirect('admin/dashboard_admin2.php');
    if ($r === 'pembimbing') redirect('pembimbingSekolah/dashboard_pembimbing.php');
    if ($r === 'wakasek')    redirect('pembimbingPerusahaan/dashboard.php');
    if ($r === 'siswa')      redirect('siswa/dashboard_siswa.php');
}

$error = "";
if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $role     = mysqli_real_escape_string($conn, $_POST['role']);

    $query  = "SELECT * FROM users WHERE email = '$email' AND role = '$role' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);

        if (password_verify($password, $data['password'])) {
            // ── Session seragam untuk semua role ─────────────────────────
            $_SESSION['user'] = [
                'id_user' => $data['id'],
                'nama'    => trim($data['nama_depan'] . ' ' . $data['nama_belakang']),
                'email'   => $data['email'],
                'role'    => $data['role'],
            ];

            // Catat log login
            catatLog($conn, 'Login ke sistem sebagai ' . $data['role']);

            if ($data['role'] === 'admin')      redirect('admin/dashboard_admin2.php');
            if ($data['role'] === 'pembimbing') redirect('pembimbingSekolah/dashboard_pembimbing.php');
            if ($data['role'] === 'wakasek')    redirect('pembimbingPerusahaan/dashboard.php');
            if ($data['role'] === 'siswa')      redirect('siswa/dashboard_siswa.php');
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Akun tidak ditemukan atau Role salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — SIMPKL</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    body{background:#090913;font-family:'Poppins',sans-serif;margin:0;color:#fff}
    .wrapper{min-height:100vh;display:flex;align-items:center;justify-content:center;background:radial-gradient(circle at 15% 40%,#1c1c52 0%,#090913 100%);padding:20px}
    .box{width:100%;max-width:420px;background:rgba(255,255,255,.03);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.1);padding:40px;border-radius:24px;box-shadow:0 25px 50px -12px rgba(0,0,0,.5)}
    .brand{display:flex;align-items:center;gap:15px;margin-bottom:30px}
    .brand-icon{width:45px;height:45px;background:#fff;color:#090913;display:flex;align-items:center;justify-content:center;border-radius:12px;font-size:1.2rem}
    .form-group{margin-bottom:20px}
    .form-group label{display:block;font-size:.85rem;margin-bottom:8px;color:#94a3b8}
    .input-wrap{position:relative}
    .input-wrap i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#64748b}
    .form-control{width:100%;background:rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.1);padding:12px 15px 12px 45px;border-radius:12px;color:#fff;font-size:.9rem;box-sizing:border-box;outline:none;transition:.2s;font-family:'Poppins',sans-serif}
    .form-control:focus{border-color:rgba(255,255,255,.3)}
    .form-control::placeholder{color:#475569}
    .role-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}
    .role-grid input{display:none}
    .role-grid label{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);padding:10px;border-radius:12px;text-align:center;font-size:.8rem;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:5px;color:#94a3b8;transition:.2s}
    .role-grid input:checked+label{background:#fff;color:#090913;font-weight:600}
    .btn{width:100%;padding:14px;background:#fff;color:#090913;border:none;border-radius:12px;font-weight:700;cursor:pointer;margin-top:25px;transition:.3s;font-family:'Poppins',sans-serif;font-size:.95rem}
    .btn:hover{transform:translateY(-3px);box-shadow:0 10px 20px rgba(255,255,255,.1)}
    .error-msg{background:rgba(239,68,68,.1);color:#f87171;padding:10px;border-radius:10px;font-size:.8rem;margin-bottom:20px;border:1px solid rgba(239,68,68,.2)}
    .footer-link{text-align:center;margin-top:20px;font-size:.85rem;color:#94a3b8}
    .footer-link a{color:#fff;font-weight:600;text-decoration:none}
    .hint-box{background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:10px;padding:12px 14px;margin-top:20px;font-size:.75rem;color:#94a3b8;line-height:1.8}
    .hint-box strong{color:#818cf8}
  </style>
</head>
<body>
<div class="wrapper">
  <div class="box">
    <div class="brand">
      <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
      <div>
        <h1 style="margin:0;font-size:1.5rem">SIMPKL</h1>
        <p style="margin:0;font-size:.75rem;color:#64748b">Sistem Informasi PKL</p>
      </div>
    </div>

    <?php if ($error): ?>
    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Email</label>
        <div class="input-wrap">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
      </div>
      <div class="form-group">
        <label>Login Sebagai:</label>
        <div class="role-grid">
          <input type="radio" name="role" value="siswa"      id="r-siswa" checked>
          <label for="r-siswa"><i class="fas fa-user-graduate"></i>Siswa</label>

          <input type="radio" name="role" value="admin"      id="r-admin">
          <label for="r-admin"><i class="fas fa-user-shield"></i>Admin</label>

          <input type="radio" name="role" value="pembimbing" id="r-pembimbing">
          <label for="r-pembimbing"><i class="fas fa-chalkboard-teacher"></i>Guru Pembimbing</label>

          <input type="radio" name="role" value="wakasek"    id="r-wakasek">
          <label for="r-wakasek"><i class="fas fa-user-tie"></i>Wakasek hubin</label>
        </div>
      </div>
      <button type="submit" name="login" class="btn">MASUK SEKARANG</button>
    </form>

    

    <div class="footer-link">Belum punya akun? <a href="registrasi.php">Daftar di sini</a></div>
  </div>
</div>
</body>
</html>