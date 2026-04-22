<?php
ob_start();
session_start();
include "../config.php";

// ============================================================
// user_aksi.php — CRUD user sesuai struktur database webpkl
// Kolom users: id, nama_depan, nama_belakang, email, password, role, created_at
// Tidak ada kolom: username, is_deleted, nis_nik, id_users
// ============================================================

// --- HAPUS USER ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    // Ambil nama user untuk log
    $res  = mysqli_query($conn, "SELECT nama_depan, nama_belakang, email FROM users WHERE id = $id");
    $data = mysqli_fetch_assoc($res);
    $nama = $data ? ($data['nama_depan'] . ' ' . $data['nama_belakang']) : 'Unknown';

    // Hard delete (database webpkl tidak punya kolom is_deleted)
    // Profil terkait (profil_siswa / profil_guru) akan terhapus otomatis via ON DELETE CASCADE
    if (mysqli_query($conn, "DELETE FROM users WHERE id = $id")) {
        setFlash('success', "User '$nama' berhasil dihapus.");
    } else {
        setFlash('error', 'Gagal menghapus user: ' . mysqli_error($conn));
    }
    redirect('admin-users.php');
}

// --- TAMBAH / EDIT USER ---
if (isset($_POST['simpan'])) {
    $id           = (int)$_POST['id'];
    $nama_depan   = mysqli_real_escape_string($conn, trim($_POST['nama_depan']));
    $nama_belakang = mysqli_real_escape_string($conn, trim($_POST['nama_belakang']));
    $email        = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role         = mysqli_real_escape_string($conn, $_POST['role']);
    $password     = $_POST['password'];

    if ($id == 0) {
        // ---- TAMBAH ----
        // Cek email unik (kolom email punya UNIQUE KEY)
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($cek) > 0) {
            setFlash('error', 'Gagal! Email sudah digunakan oleh user lain.');
            redirect('admin-users.php');
        }

        if (empty($password)) {
            setFlash('error', 'Password tidak boleh kosong untuk user baru.');
            redirect('admin-users.php');
        }

        $hashed = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_BCRYPT));

        $query = "INSERT INTO users (nama_depan, nama_belakang, email, password, role)
                  VALUES ('$nama_depan', '$nama_belakang', '$email', '$hashed', '$role')";

        if (mysqli_query($conn, $query)) {
            $new_id = mysqli_insert_id($conn);

            // Buat profil kosong sesuai role
            if ($role === 'siswa') {
                mysqli_query($conn, "INSERT INTO profil_siswa (user_id) VALUES ($new_id)");
            } elseif (in_array($role, ['pembimbing', 'wakasek', 'admin'])) {
                mysqli_query($conn, "INSERT INTO profil_guru (user_id) VALUES ($new_id)");
            }

            setFlash('success', "User '$nama_depan' berhasil ditambahkan.");
        } else {
            setFlash('error', 'Gagal menambah user: ' . mysqli_error($conn));
        }

    } else {
        // ---- EDIT ----
        // Pastikan email tidak bentrok dengan user lain
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $id");
        if (mysqli_num_rows($cek) > 0) {
            setFlash('error', 'Gagal! Email tersebut sudah milik user lain.');
            redirect('admin-users.php');
        }

        // Update data dasar
        $query = "UPDATE users SET
                    nama_depan   = '$nama_depan',
                    nama_belakang = '$nama_belakang',
                    email        = '$email',
                    role         = '$role'";

        // Hanya update password kalau diisi
        if (!empty($password)) {
            $hashed = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_BCRYPT));
            $query .= ", password = '$hashed'";
        }

        $query .= " WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            setFlash('success', "Data user '$nama_depan' berhasil diperbarui.");
        } else {
            setFlash('error', 'Gagal memperbarui user: ' . mysqli_error($conn));
        }
    }

    redirect('admin-users.php');
}

// Kalau akses langsung tanpa aksi
redirect('admin-users.php');
exit();
