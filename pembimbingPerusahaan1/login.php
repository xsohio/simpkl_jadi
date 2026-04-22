<?php
// ============================================================
//  login.php — Panel Pembimbing SIMPKL
//  Disambungkan dengan config.php, logout.php sistem ini
//  DAN dengan session sistem utama webpkl
// ============================================================
require_once 'config.php';   // sudah panggil session_start() + _bridgeSession()

// Kalau sudah login (dari sini ATAU dari sistem utama) langsung masuk
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'pembimbing') {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'pembimbing'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // ——— Set session format pembimbing ———
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['nama']    = $user['nama_depan'] . ' ' . $user['nama_belakang'];

            // ——— Set JUGA session format sistem utama (agar logout.php root ikut bersih) ———
            $_SESSION['user'] = [
                'id_user'  => $user['id'],
                'nama'     => $user['nama_depan'] . ' ' . $user['nama_belakang'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email atau password salah, atau akun bukan pembimbing.';
        }
    } else {
        $error = 'Isi email dan password terlebih dahulu.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIMPKL – Login Pembimbing</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
      SIMPKL
    </div>
    <h2 style="text-align:center;margin-bottom:6px;color:#f1f5f9">Masuk Panel Pembimbing</h2>
    <p style="text-align:center;margin-bottom:28px;font-size:.85rem;color:#64748b">Sistem Informasi Monitoring PKL</p>

    <?php if ($error): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:.85rem;color:#ef4444">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email</label>
        <div style="position:relative">
          <i class="fas fa-envelope" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b"></i>
          <input type="email" name="email" class="form-control" style="padding-left:38px"
                 placeholder="email@sekolah.id"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div style="position:relative">
          <i class="fas fa-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b"></i>
          <input type="password" name="password" class="form-control" style="padding-left:38px"
                 placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;padding:12px">
        <i class="fas fa-sign-in-alt"></i> Masuk
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:.78rem;color:#334155">
      <a href="../login.php" style="color:#6366f1;text-decoration:none">
        <i class="fas fa-arrow-left"></i> Kembali ke Login Utama SIMPKL
      </a>
    </p>
  </div>
</div>
</body>
</html>
