<?php
// log_helper.php
// Include file ini di config.php atau di setiap halaman yang butuh logging
// Usage: logAktivitas($pdo_or_conn, $user_id, 'Deskripsi aktivitas', 'pembimbing');

/**
 * Insert log aktivitas ke tabel log_aktivitas
 * 
 * @param mixed  $db        PDO atau mysqli connection
 * @param int    $user_id   ID user yang melakukan aktivitas
 * @param string $aktivitas Deskripsi aktivitas
 * @param string $role      Role user: 'pembimbing', 'siswa', 'admin'
 * @param string $ip        IP address (opsional, auto-detect jika null)
 */
function logAktivitas($db, $user_id, $aktivitas, $role = 'pembimbing', $ip = null) {
    $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    
    try {
        // Deteksi PDO atau mysqli
        if ($db instanceof PDO) {
            $stmt = $db->prepare(
                "INSERT INTO log_aktivitas (id_users, aktivitas, role, ip_address, user_agent, waktu)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$user_id, $aktivitas, $role, $ip, $user_agent]);
        } else {
            // mysqli
            $aktivitas   = mysqli_real_escape_string($db, $aktivitas);
            $role        = mysqli_real_escape_string($db, $role);
            $ip          = mysqli_real_escape_string($db, $ip);
            $user_agent  = mysqli_real_escape_string($db, $user_agent);
            mysqli_query($db, "INSERT INTO log_aktivitas (id_users, aktivitas, role, ip_address, user_agent, waktu)
                               VALUES ($user_id, '$aktivitas', '$role', '$ip', '$user_agent', NOW())");
        }
    } catch (Exception $e) {
        // Silent fail — log tidak boleh crash aplikasi
        error_log('logAktivitas error: ' . $e->getMessage());
    }
}
