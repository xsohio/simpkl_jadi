<?php
require 'config.php';

if (isset($_POST['register'])) {
    $nama_depan = mysqli_real_escape_string($conn, $_POST['nama_depan']);
    $nama_belakang = mysqli_real_escape_string($conn, $_POST['nama_belakang']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'siswa'; // Default tetap siswa

    $cek = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Email sudah terdaftar!";
    } else {
        $query = "INSERT INTO users (nama_depan, nama_belakang, email, password, role) 
                  VALUES ('$nama_depan', '$nama_belakang', '$email', '$password', '$role')";
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Berhasil! Silakan Login.'); window.location='login.php';</script>";
        } else {
            $error = "Terjadi kesalahan sistem.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PKL Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="auth-container">
        <div class="auth-card">
            <div class="brand-logo">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            
            <h2>Buat Akun Siswa</h2>
            <p class="subtitle">Isi data diri untuk memulai pendaftaran PKL</p>
            
            <?php if(isset($error)) : ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Nama Depan</label>
                            <input type="text" name="nama_depan" placeholder="Depan" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Nama Belakang</label>
                            <input type="text" name="nama_belakang" placeholder="Belakang" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Sekolah</label>
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="nis@smk.sch.id" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" placeholder="Buat password kuat" required>
                </div>

                <button type="submit" name="register" class="btn">
                    Daftar Akun
                </button>
            </form>
            
            <div class="footer-link">
                Sudah punya akun? <a href="login.php">Login disini</a>
            </div>
        </div>
    </div>

</body>
</html>