-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 09:44 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `webpkl_fixed`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status` enum('Hadir','Izin','Sakit','Alpa') NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `siswa_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `status`, `keterangan`) VALUES
(1, 15, '2026-04-20', '15:45:00', '22:57:00', 'Hadir', 'bhnjk'),
(2, 19, '2026-04-20', '00:00:00', '00:00:00', 'Hadir', 'bhnjk');

-- --------------------------------------------------------

--
-- Table structure for table `jurnal_harian`
--

CREATE TABLE `jurnal_harian` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `kegiatan` text NOT NULL,
  `foto_kegiatan` varchar(255) DEFAULT NULL,
  `status_validasi` enum('pending','valid','tolak') DEFAULT 'pending',
  `komentar_pembimbing` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jurnal_harian`
--

INSERT INTO `jurnal_harian` (`id`, `siswa_id`, `tanggal`, `jam_masuk`, `jam_keluar`, `kegiatan`, `foto_kegiatan`, `status_validasi`, `komentar_pembimbing`) VALUES
(1, 1, '2025-11-22', '07:00:00', '17:00:00', 'blablablablablablablabla', NULL, 'valid', NULL),
(2, 13, '2026-04-15', '07:00:00', '16:00:00', 'saya mengisi', NULL, 'valid', NULL),
(3, 15, '2026-04-15', '07:00:00', '16:00:00', 'poljks', NULL, 'valid', NULL),
(4, 13, '2026-04-16', '07:00:00', '16:00:00', 'efw', NULL, 'valid', NULL),
(6, 15, '2026-04-20', '07:00:00', '16:00:00', ' hjgiuuyduyhkuh\r\n\r\n', 'jurnal_15_1776650605.jpg', 'valid', NULL),
(7, 19, '2026-04-20', '07:00:00', '16:00:00', 'agfvfbtrfx', NULL, 'valid', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `laporan_pkl`
--

CREATE TABLE `laporan_pkl` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `judul_laporan` varchar(200) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `jenis_laporan` enum('mingguan','akhir') NOT NULL,
  `status_pembimbing` enum('pending','revisi','disetujui') DEFAULT 'pending',
  `status_wakasek` enum('pending','disetujui') DEFAULT 'pending',
  `catatan_revisi` text DEFAULT NULL,
  `tampil_di_publik` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_pkl`
--

INSERT INTO `laporan_pkl` (`id`, `siswa_id`, `judul_laporan`, `file_path`, `jenis_laporan`, `status_pembimbing`, `status_wakasek`, `catatan_revisi`, `tampil_di_publik`, `created_at`) VALUES
(1, 1, 'wikpo', NULL, 'mingguan', 'disetujui', 'pending', NULL, 0, '2026-04-14 02:44:13'),
(2, 13, 'wikpo', NULL, 'mingguan', 'pending', 'pending', NULL, 0, '2026-04-15 03:44:07'),
(3, 15, 'wikpo', NULL, 'mingguan', 'pending', 'pending', NULL, 0, '2026-04-15 06:43:52');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `id_users` int(11) DEFAULT NULL,
  `aktivitas` text NOT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id_log`, `id_users`, `aktivitas`, `waktu`) VALUES
(1, 3, 'Menghapus siswa: aku atheis', '2026-04-14 01:24:45'),
(2, 3, 'Menambah laporan PKL: wikpo', '2026-04-14 02:44:13'),
(3, 3, 'Menghapus siswa: et o', '2026-04-15 00:48:40'),
(4, 3, 'Menghapus siswa: oi santoso', '2026-04-15 00:48:42'),
(5, 3, 'Menghapus siswa: p o', '2026-04-15 00:48:44'),
(9, 3, 'Menghapus siswa: sera resa', '2026-04-15 04:15:16'),
(78, 15, 'Mengirim laporan mingguan baru', '2026-04-15 07:36:39'),
(79, 15, 'Mengirim laporan mingguan baru', '2026-04-15 07:39:48'),
(80, 15, 'Mengirim laporan mingguan baru', '2026-04-15 07:39:49'),
(81, 15, 'Mengirim laporan mingguan baru', '2026-04-15 07:46:32'),
(83, 13, 'Mengirim laporan mingguan baru', '2026-04-16 00:42:39'),
(85, 13, 'Mengirim laporan mingguan baru', '2026-04-16 00:50:52'),
(86, 13, 'Mengirim laporan mingguan baru', '2026-04-16 01:26:43'),
(87, 5, 'Bimbingan ke siswa #1: harus ada chat di siswa asu', '2026-04-17 04:03:11'),
(88, 5, 'Bimbingan ke siswa #1: asdfasdf', '2026-04-17 04:03:16'),
(89, NULL, 'Mengirim laporan mingguan baru', '2026-04-17 05:44:55'),
(90, NULL, 'Mengirim laporan mingguan baru', '2026-04-17 05:45:03'),
(91, NULL, 'Menambah laporan PKL: ,nbmnmn', '2026-04-17 05:45:03'),
(92, NULL, 'Mengirim laporan mingguan baru', '2026-04-17 05:45:03'),
(93, 3, 'Mengubah laporan PKL: ,nbmnmn (id=4)', '2026-04-20 02:19:26'),
(94, 3, 'Mengubah laporan PKL: ,nbmnmn (id=4)', '2026-04-20 02:19:33'),
(95, 3, 'Mengubah laporan PKL: ,nbmnmn (id=4)', '2026-04-20 02:19:34'),
(96, 4, 'Menyetujui pengajuan PKL siswa udin 123', '2026-04-20 03:08:00'),
(97, 4, 'Menyetujui pengajuan PKL siswa poi s', '2026-04-20 03:20:56'),
(98, 13, 'Mengirim laporan mingguan baru', '2026-04-20 03:41:10'),
(99, 13, 'Mengirim laporan mingguan baru', '2026-04-20 03:41:26'),
(100, 13, 'Mengirim laporan mingguan baru', '2026-04-20 03:55:22'),
(101, 4, 'Memvalidasi jurnal udin 123 tanggal 2026-04-20', '2026-04-20 03:56:37'),
(102, 4, 'Memvalidasi jurnal poi s tanggal 2026-04-16', '2026-04-20 03:56:39'),
(103, 4, 'Memvalidasi jurnal poi s tanggal 2026-04-15', '2026-04-20 03:56:40'),
(104, 4, 'Memvalidasi jurnal udin 123 tanggal 2026-04-15', '2026-04-20 03:56:43'),
(105, 4, 'Menambah absensi Hadir untuk siswa udin 123 tanggal 2026-04-20', '2026-04-20 03:57:48'),
(106, 3, 'Mengubah status penempatan PKL id: 5', '2026-04-20 04:17:11'),
(107, 20, 'Menyetujui pengajuan PKL siswa riko ntol', '2026-04-20 04:22:38'),
(108, 20, 'Memvalidasi jurnal riko ntol tanggal 2026-04-17', '2026-04-20 04:22:49'),
(109, 3, 'Mengubah status penempatan PKL id: 6', '2026-04-20 04:28:05'),
(110, 3, 'Menghapus data bimbingan/jurnal id: 5', '2026-04-20 06:11:19'),
(111, 3, 'Menghapus laporan PKL: ,nbmnmn (id=4)', '2026-04-20 06:11:25'),
(112, 3, 'Menghapus penempatan PKL id: 3', '2026-04-20 06:11:33'),
(113, 3, 'Menghapus siswa: riko ntol', '2026-04-20 06:11:42'),
(114, 19, 'Mengirim laporan mingguan baru', '2026-04-20 06:25:15'),
(115, 19, 'Mengirim laporan mingguan baru', '2026-04-20 06:25:19'),
(116, 19, 'Mengirim laporan mingguan baru', '2026-04-20 06:25:46'),
(117, 18, 'Memvalidasi jurnal adu hai tanggal 2026-04-20', '2026-04-20 07:38:07'),
(118, 18, 'Menambah absensi Hadir untuk siswa adu hai tanggal 2026-04-20', '2026-04-20 07:38:53');

-- --------------------------------------------------------

--
-- Table structure for table `mitra_industri`
--

CREATE TABLE `mitra_industri` (
  `id` int(11) NOT NULL,
  `nama_perusahaan` varchar(100) NOT NULL,
  `alamat_perusahaan` text DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mitra_industri`
--

INSERT INTO `mitra_industri` (`id`, `nama_perusahaan`, `alamat_perusahaan`, `website`, `logo`, `created_at`) VALUES
(1, 'PT Teknologi', 'bandung', NULL, NULL, '2025-11-22 17:19:47');

-- --------------------------------------------------------

--
-- Table structure for table `nilai_pkl`
--

CREATE TABLE `nilai_pkl` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `pembimbing_id` int(11) NOT NULL,
  `nilai_sikap` int(11) DEFAULT 0,
  `nilai_keterampilan` int(11) DEFAULT 0,
  `nilai_laporan` int(11) DEFAULT 0,
  `nilai_akhir` decimal(5,2) DEFAULT NULL,
  `predikat` char(2) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nilai_pkl`
--

INSERT INTO `nilai_pkl` (`id`, `siswa_id`, `pembimbing_id`, `nilai_sikap`, `nilai_keterampilan`, `nilai_laporan`, `nilai_akhir`, `predikat`, `catatan`, `created_at`) VALUES
(1, 1, 5, 90, 68, 100, 86.00, 'B', '', '2026-04-14 06:58:07');

-- --------------------------------------------------------

--
-- Table structure for table `pkl_anggota`
--

CREATE TABLE `pkl_anggota` (
  `id` int(11) NOT NULL,
  `pengajuan_id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `status_keanggotaan` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pkl_anggota`
--

INSERT INTO `pkl_anggota` (`id`, `pengajuan_id`, `siswa_id`, `status_keanggotaan`) VALUES
(1, 1, 1, 'aktif'),
(2, 2, 15, 'aktif'),
(4, 4, 13, 'aktif'),
(5, 5, 19, 'aktif'),
(6, 6, 21, 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `pkl_pengajuan`
--

CREATE TABLE `pkl_pengajuan` (
  `id` int(11) NOT NULL,
  `ketua_id` int(11) NOT NULL,
  `nama_perusahaan` varchar(100) NOT NULL,
  `alamat_perusahaan` text NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `file_dokumen` varchar(255) DEFAULT NULL,
  `status_pembimbing` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `status_wakasek` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `pembimbing_id` int(11) DEFAULT NULL,
  `tanggal_pengajuan` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pkl_pengajuan`
--

INSERT INTO `pkl_pengajuan` (`id`, `ketua_id`, `nama_perusahaan`, `alamat_perusahaan`, `website`, `file_dokumen`, `status_pembimbing`, `status_wakasek`, `pembimbing_id`, `tanggal_pengajuan`) VALUES
(1, 1, 'PT Teknologi', 'Bandung', NULL, '../assets/uploads/1763833684_Screenshot_23-11-2025_04039_localhost.jpeg', 'disetujui', 'disetujui', 5, '2025-11-23 00:48:04'),
(2, 15, 'pt.teknologi mahu', '0589234187', 'https://uidn', NULL, 'disetujui', 'pending', 4, '2026-04-15 13:45:36'),
(4, 13, 'pt kecap amba', 'tserdfvghbjn', 'https://masamba', NULL, 'disetujui', 'pending', 4, '2026-04-20 10:20:08'),
(5, 19, 'pt percobaan', 'affgdnb', NULL, NULL, 'disetujui', 'pending', 18, '2026-04-20 11:15:15'),
(6, 21, 'pt sampo', 'jl.dago', 'https://masamba', NULL, 'pending', 'pending', 18, '2026-04-20 11:25:07');

-- --------------------------------------------------------

--
-- Table structure for table `profil_guru`
--

CREATE TABLE `profil_guru` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `jabatan_terakhir` varchar(100) DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_guru`
--

INSERT INTO `profil_guru` (`id`, `user_id`, `nip`, `jabatan`, `jabatan_terakhir`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `no_hp`, `foto_profil`) VALUES
(1, 5, NULL, NULL, NULL, NULL, NULL, NULL, '0089866', NULL),
(2, 18, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `profil_siswa`
--

CREATE TABLE `profil_siswa` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `agama` varchar(50) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `golongan_darah` enum('A','B','AB','O') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_siswa`
--

INSERT INTO `profil_siswa` (`id`, `user_id`, `nis`, `kelas`, `jurusan`, `no_hp`, `foto_profil`, `jenis_kelamin`, `agama`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `golongan_darah`) VALUES
(1, 1, '1234123', 'XI', 'RPL', '12351231231', 'SISWA_1_1764087314.png', 'Laki-laki', 'Islam', NULL, NULL, NULL, 'B'),
(7, 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 15, '45345353', 'xi', 'rpl', '0989562748', NULL, 'Perempuan', 'Konghucu', 'Ks', '1234-02-02', '', NULL),
(11, 19, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 21, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama_depan` varchar(50) NOT NULL,
  `nama_belakang` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('siswa','pembimbing','wakasek','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama_depan`, `nama_belakang`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'samsul', 'pea', 'samsul@gmail.com', '$2y$10$MGXDT8FBZQBW7CBOITNWa.8bLcw4zCD1EQA/v0OKRB4sGUZLTtYMi', 'siswa', '2025-11-21 15:47:41'),
(3, 'Super', 'Admin', 'admin@smk.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-11-21 16:32:55'),
(4, 'Bapak', 'Wakasek', 'wakasek@smk.id', '$2y$10$Cm2BfpGztUIb6Uk9bp9VGeTj4JgbbaA.JfU5QuDNdJ88aLarNIlc2', 'wakasek', '2025-11-21 16:32:55'),
(5, 'pembimbing', 'Guru', 'pembimbing@smk.id', '$2y$10$Cm2BfpGztUIb6Uk9bp9VGeTj4JgbbaA.JfU5QuDNdJ88aLarNIlc2', 'pembimbing', '2025-11-21 16:32:55'),
(7, 'pem', 'bingbing', 'bingbing@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pembimbing', '2026-04-10 06:15:57'),
(9, 'pe', 'bingbing', 'bing@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pembimbing', '2026-04-10 06:16:27'),
(13, 'poi', 's', 'poi@gmail.com', '$2y$10$9UPb6bvwICnWG43Utzj5reE1KHnpchtNJn3G0Wnq0fW4DFSeVJPeW', 'siswa', '2026-04-15 00:52:35'),
(15, 'udin', '123', 'udin@gmail.com', '$2y$10$mbplgqp09E.eVN4SJuE46uUFc/xETjninbdUftyVFR7eWCUresl/.', 'siswa', '2026-04-15 06:29:43'),
(18, 'kakang', 'ku', 'kakangku@smk.id', '$2y$10$YYwenPrQfuAtjXipnVuNHu14YDjjK3oYbjpVwinYXSPmWMT4NkIxy', 'pembimbing', '2026-04-20 04:12:10'),
(19, 'adu', 'hai', 'aduhai@smk.id', '$2y$10$V5G/Hi.R.UCkMF9s1iDfc.M2Bb2iqBcx7fP0SyB.xuRe9ULjX7rHa', 'siswa', '2026-04-20 04:14:32'),
(20, 'mas', 'an', 'masan@gmail.com', '$2y$10$mPVt.D6itdP2IKMtyXNmgOwumShUghVg5Gvmr597OZ5URkY2KotkW', 'wakasek', '2026-04-20 04:21:43'),
(21, 'nanang', 'supriatna', 'dodol@gmail.com', '$2y$10$b/Du8GeAT4OAEhlCQt01keZPpsPp85VYQgyVxTWqQcFX9pd9bqRDe', 'siswa', '2026-04-20 04:23:40');

-- --------------------------------------------------------

--
-- Table structure for table `walikelas`
--

CREATE TABLE `walikelas` (
  `id_walikelas` int(15) NOT NULL,
  `Nama_wakel` varchar(40) NOT NULL,
  `Alamat` varchar(50) NOT NULL,
  `Agama_wakel` varchar(10) NOT NULL,
  `No_kontak` varchar(20) NOT NULL,
  `Mewalikelaskan` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `walikelas`
--

INSERT INTO `walikelas` (`id_walikelas`, `Nama_wakel`, `Alamat`, `Agama_wakel`, `No_kontak`, `Mewalikelaskan`) VALUES
(1, 'acs', 'sca', 'csa', 'csa', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `jurnal_harian`
--
ALTER TABLE `jurnal_harian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `laporan_pkl`
--
ALTER TABLE `laporan_pkl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_users` (`id_users`);

--
-- Indexes for table `mitra_industri`
--
ALTER TABLE `mitra_industri`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nilai_pkl`
--
ALTER TABLE `nilai_pkl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `pembimbing_id` (`pembimbing_id`);

--
-- Indexes for table `pkl_anggota`
--
ALTER TABLE `pkl_anggota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengajuan_id` (`pengajuan_id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `pkl_pengajuan`
--
ALTER TABLE `pkl_pengajuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ketua_id` (`ketua_id`),
  ADD KEY `pembimbing_id` (`pembimbing_id`);

--
-- Indexes for table `profil_guru`
--
ALTER TABLE `profil_guru`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `profil_siswa`
--
ALTER TABLE `profil_siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `walikelas`
--
ALTER TABLE `walikelas`
  ADD PRIMARY KEY (`id_walikelas`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jurnal_harian`
--
ALTER TABLE `jurnal_harian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `laporan_pkl`
--
ALTER TABLE `laporan_pkl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `mitra_industri`
--
ALTER TABLE `mitra_industri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nilai_pkl`
--
ALTER TABLE `nilai_pkl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pkl_anggota`
--
ALTER TABLE `pkl_anggota`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pkl_pengajuan`
--
ALTER TABLE `pkl_pengajuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `profil_guru`
--
ALTER TABLE `profil_guru`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `profil_siswa`
--
ALTER TABLE `profil_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `walikelas`
--
ALTER TABLE `walikelas`
  MODIFY `id_walikelas` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jurnal_harian`
--
ALTER TABLE `jurnal_harian`
  ADD CONSTRAINT `jurnal_harian_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `laporan_pkl`
--
ALTER TABLE `laporan_pkl`
  ADD CONSTRAINT `laporan_pkl_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `nilai_pkl`
--
ALTER TABLE `nilai_pkl`
  ADD CONSTRAINT `nilai_pkl_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nilai_pkl_ibfk_2` FOREIGN KEY (`pembimbing_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pkl_anggota`
--
ALTER TABLE `pkl_anggota`
  ADD CONSTRAINT `pkl_anggota_ibfk_1` FOREIGN KEY (`pengajuan_id`) REFERENCES `pkl_pengajuan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pkl_anggota_ibfk_2` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pkl_pengajuan`
--
ALTER TABLE `pkl_pengajuan`
  ADD CONSTRAINT `pkl_pengajuan_ibfk_1` FOREIGN KEY (`ketua_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pkl_pengajuan_ibfk_2` FOREIGN KEY (`pembimbing_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `profil_guru`
--
ALTER TABLE `profil_guru`
  ADD CONSTRAINT `profil_guru_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profil_siswa`
--
ALTER TABLE `profil_siswa`
  ADD CONSTRAINT `profil_siswa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
