<?php
// ============================================================
//  log_helper.php — Helper pencatatan log aktivitas SIMPKL
//  Letakkan di root project (satu level dengan config.php)
//
//  Database: webpkl_fixed
//  Tabel log_aktivitas SUDAH ADA dengan struktur:
//    id_log    INT AUTO_INCREMENT PK
//    id_users  INT (FK → users.id, ON DELETE SET NULL)
//    aktivitas TEXT NOT NULL
//    waktu     TIMESTAMP DEFAULT current_timestamp()
//
//  Cara pakai di file PHP mana saja:
//    include "../log_helper.php";   // sesuaikan path
//    catat_log($conn, $_SESSION['user']['id_user'], "Deskripsi aksi");
// ============================================================

/**
 * Catat satu baris log ke tabel log_aktivitas.
 *
 * @param mysqli   $conn      Koneksi database aktif
 * @param int|null $id_user   ID user yang beraksi (null = sistem)
 * @param string   $aktivitas Deskripsi singkat aktivitas
 * @return bool
 */
function catat_log($conn, $id_user, $aktivitas) {
    $id_val    = $id_user ? (int)$id_user : 'NULL';
    $aktivitas = mysqli_real_escape_string($conn, trim($aktivitas));
    return (bool)mysqli_query($conn,
        "INSERT INTO log_aktivitas (id_users, aktivitas) VALUES ($id_val, '$aktivitas')"
    );
}
