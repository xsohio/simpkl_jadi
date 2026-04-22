<?php
session_start();
session_destroy();
session_start();
include "config.php";

$error = ""; $success = "";
if (isset($_POST['register'])) {
    // Database webpkl: id, nama_depan, nama_belakang, email, password, role, created_at
    $nama_depan  = mysqli_real_escape_string($conn, trim($_POST['nama_depan']));
    $nama_belakang = mysqli_real_escape_string($conn, trim($_POST['nama_belakang']));
    $email       = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password    = $_POST['password'];
    $role        = mysqli_real_escape_string($conn, $_POST['role']);

    // Validasi email unik (kolom email memiliki UNIQUE KEY di database)
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email '$email' sudah terdaftar!";
    } elseif (empty($nama_depan)) {
        $error = "Nama depan tidak boleh kosong!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Hash password dengan bcrypt sesuai data di database (password_hash)
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $hashed_escaped = mysqli_real_escape_string($conn, $hashed);

        $query = "INSERT INTO users (nama_depan, nama_belakang, email, password, role)
                  VALUES ('$nama_depan', '$nama_belakang', '$email', '$hashed_escaped', '$role')";

        if (mysqli_query($conn, $query)) {
            $new_id = mysqli_insert_id($conn);

            // Buat profil awal sesuai role
            if ($role === 'siswa') {
                mysqli_query($conn, "INSERT INTO profil_siswa (user_id) VALUES ($new_id)");
            } elseif ($role === 'pembimbing' || $role === 'wakasek') {
                mysqli_query($conn, "INSERT INTO profil_guru (user_id) VALUES ($new_id)");
            }

            $success = "Akun <b>$role</b> berhasil dibuat! Silakan <a href='login.php' style='color:#4ade80;'>login di sini</a>.";
        } else {
            $error = "Gagal mendaftar: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — SIMPKL</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background: #090913; font-family: 'Poppins', sans-serif; margin: 0; color: #fff; }
        .wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at 85% 70%, #1c1c52 0%, #090913 100%); padding: 20px; }
        .box { width: 100%; max-width: 440px; background: rgba(255,255,255,0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .brand { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
        .brand-icon { width: 45px; height: 45px; background: #fff; color: #090913; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-size: 1.2rem; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 0.85rem; margin-bottom: 8px; color: #94a3b8; }
        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #64748b; }
        .form-control { width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 12px 15px 12px 45px; border-radius: 12px; color: #fff; font-size: 0.9rem; box-sizing: border-box; transition: 0.3s; font-family: 'Poppins', sans-serif; }
        .form-control::placeholder { color: #475569; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
       .role-grid {  justify-content: center; margin: 35px; }
        .role-grid input { display: none; }
        .role-grid label { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 10px; border-radius: 12px; text-align: center; font-size: 0.8rem; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 5px; color: #94a3b8; transition: 0.2s; }
        .role-grid input:checked + label { background: #fff; color: #090913; font-weight: 600; }
        .btn { display: block; width: 80%; padding: 14px; background: #fff; color: #090913; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; margin: 20px auto 0 auto;transition: 0.3s; font-family: 'Poppins', sans-serif; }
        .msg { padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px; border: 1px solid; }
        .error  { background: rgba(239,68,68,0.1); color: #f87171; border-color: rgba(239,68,68,0.2); }
        .success { background: rgba(34,197,94,0.1); color: #4ade80; border-color: rgba(34,197,94,0.2); }
        .footer-link { text-align: center; margin-top: 20px; font-size: 0.85rem; color: #94a3b8; }
        .footer-link a { color: #fff; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="box">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-user-plus"></i></div>
            <h1 style="margin:0; font-size:1.5rem;">DAFTAR</h1>
        </div>

        <?php if ($error):   ?><div class="msg error">  <i class="fas fa-times-circle"></i> <?= $error ?>  </div><?php endif; ?>
        <?php if ($success): ?><div class="msg success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <form method="POST">
            <!-- Nama depan & belakang sesuai kolom DB -->
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Depan</label>
                    <div class="input-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" name="nama_depan" class="form-control" placeholder="Budi" required autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label>Nama Belakang</label>
                    <div class="input-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" name="nama_belakang" class="form-control" placeholder="Santoso" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                </div>
            </div>

<div class="form-group">
    <label style="text-align: center;">Daftar Sebagai:</label> <div class="role-grid">
        <input type="radio" name="role" value="siswa" id="r-siswa" checked>
        <label for="r-siswa"><i class="fas fa-user-graduate"></i>Siswa</label>
    </div>
</div>

<button type="submit" name="register" class="btn">DAFTAR SEKARANG</button>
        </form>
        <div class="footer-link">Sudah punya akun? <a href="login.php">Login di sini</a></div>
    </div>
</div>
</body>
</html>
