-- ================================================================
--  SIMPKL — Patch SQL untuk Panel Wakasek
--  Jalankan patch ini di atas database webpkl_fixed yang sudah ada
-- ================================================================

-- 1. Tambahkan kolom status_admin & catatan_admin ke pkl_pengajuan
--    (jika belum ada)
ALTER TABLE pkl_pengajuan
    ADD COLUMN IF NOT EXISTS status_admin  ENUM('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS catatan_admin TEXT NULL;

-- 2. Tambahkan role 'wakasek' ke kolom role di tabel users
--    (jika menggunakan ENUM)
--    Catatan: jalankan hanya jika kolom role bertipe ENUM
ALTER TABLE users MODIFY COLUMN role ENUM('siswa','pembimbing','admin','wakasek') NOT NULL DEFAULT 'siswa';

-- 3. Buat akun Wakasek contoh (password: wakasek123)
--    Hash password_verify-compatible (bcrypt)
INSERT IGNORE INTO users (nama_depan, nama_belakang, password, role, email)
VALUES (
    'Wakil', 'Kepala Sekolah',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'wakasek',
    'wakasek@sekolah.sch.id'
);

-- 4. Pastikan tabel log_aktivitas bisa menyimpan role 'wakasek'
ALTER TABLE log_aktivitas MODIFY COLUMN role VARCHAR(30) NOT NULL DEFAULT 'pembimbing';

-- ================================================================
--  CATATAN PENTING:
--  - Ganti password akun wakasek di atas setelah login pertama
--  - Panel wakasek ada di folder /wakasek/ di root project
--  - Akses: http://localhost/nama-project/wakasek/
-- ================================================================
