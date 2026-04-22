<?php
require_once 'config.php';
// Jika sudah login sebagai wakasek, redirect ke dashboard
if (isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['wakasek','admin'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo      = getDB();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? AND role IN ('wakasek','admin') LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['nama']    = $user['nama_depan'] . ' ' . $user['nama_belakang'];

        require_once 'log_helper.php';
        logAktivitas(getDB(), $user['id'], 'Login ke panel Wakasek', 'wakasek');

        header('Location: dashboard.php'); exit;
    } else {
        $error = 'Username atau password salah, atau akun bukan Wakasek.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Panel Wakasek SIMPKL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
      <span>SIMPKL <span style="color:#818cf8">Wakasek</span></span>
    </div>
    <p style="text-align:center;margin-bottom:28px;color:#64748b;font-size:.85rem">Panel Wakil Kepala Sekolah · Bidang PKL</p>
    <?php if ($error): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:8px;padding:12px 16px;font-size:.83rem;color:#ef4444;margin-bottom:16px">
      <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px">
        <i class="fas fa-sign-in-alt"></i> Masuk
      </button>
    </form>
  </div>
</div>
</body>
</html>
