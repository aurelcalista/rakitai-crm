-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 30, 2026 at 06:32 AM
-- Server version: 8.0.30
-- PHP Version: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rakitai-crm`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `attendance_location_id` bigint UNSIGNED DEFAULT NULL,
  `check_in_at` datetime NOT NULL,
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `distance` decimal(10,2) NOT NULL,
  `selfie_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_locations`
--

CREATE TABLE `attendance_locations` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `radius` int UNSIGNED NOT NULL DEFAULT '100',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` bigint UNSIGNED NOT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `bank_name`, `account_number`, `account_name`, `is_active`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Bank Mandiri', '1380010015599', 'Universitas Catur Insan Cendekia', 1, 'Rekening utama penerimaan PMB UCIC via Bank Mandiri.', '2026-09-30 05:54:38', '2026-09-30 05:54:38'),
(2, 'BCA', '8210998877', 'Universitas Catur Insan Cendekia', 1, 'Rekening penerimaan PMB UCIC via BCA.', '2026-09-30 05:54:38', '2026-09-30 05:54:38'),
(3, 'BNI', '0298877665', 'Universitas Catur Insan Cendekia', 1, 'Rekening penerimaan PMB UCIC via BNI.', '2026-09-30 05:54:38', '2026-09-30 05:54:38');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` bigint UNSIGNED NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `lokasi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `academic_year_id` bigint UNSIGNED DEFAULT NULL,
  `qr_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eo_id` bigint UNSIGNED NOT NULL,
  `sekolah_id` bigint UNSIGNED DEFAULT NULL,
  `perusahaan_id` bigint UNSIGNED DEFAULT NULL,
  `sales_id` bigint UNSIGNED DEFAULT NULL,
  `dosen_id` bigint UNSIGNED DEFAULT NULL,
  `dosen_pemateri` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dokumentasi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `absen_peserta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type_id` bigint UNSIGNED DEFAULT NULL,
  `prodi_id` bigint UNSIGNED DEFAULT NULL,
  `jenis_institusi` enum('Sekolah','Perusahaan') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_institusi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `pic_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_whatsapp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `waktu_mulai` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `waktu_selesai` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `nama`, `tanggal_mulai`, `tanggal_selesai`, `lokasi`, `deskripsi`, `status`, `academic_year_id`, `qr_code`, `eo_id`, `sekolah_id`, `perusahaan_id`, `sales_id`, `dosen_id`, `dosen_pemateri`, `dokumentasi`, `absen_peserta`, `created_at`, `updated_at`, `type_id`, `prodi_id`, `jenis_institusi`, `nama_institusi`, `alamat`, `pic_name`, `pic_whatsapp`, `name`, `tanggal`, `waktu_mulai`, `waktu_selesai`) VALUES
(1, 'Edufair Kampus UCIC 2026', '2026-10-05 00:00:00', '2026-10-05 00:00:00', 'Hall Utama Kampus UCIC Cirebon', 'Pameran pendidikan dan sosialisasi program studi penerimaan mahasiswa baru UCIC.', 'Terjadwal', 3, 'EVT-QR-20260926-XMX2SUMSFECP', 7, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 19:33:04', '2026-09-30 05:31:50', NULL, 1, 'Sekolah', 'SMA Negeri 1 Cirebon', NULL, 'Bpk. Ahmad Suhendar', '081234567890', 'Edufair Kampus UCIC 2026', '2026-10-05', '08:00', '14:00'),
(2, 'Workshop AI & Coding SMAN 1 Cirebon', '2026-09-28 00:00:00', '2026-09-28 00:00:00', 'Lab Komputer SMAN 1 Cirebon', 'Pelatihan pengenalan Artificial Intelligence dan pemrograman dasar untuk siswa kelas XII.', 'Selesai', 3, 'EVT-QR-20260926-LFSEPIOY0WQW', 7, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-25 19:33:04', '2026-09-30 05:31:50', NULL, 1, 'Sekolah', 'SMA Negeri 1 Cirebon', NULL, 'Ibu Ratna Dewi, M.Pd', '081987654321', 'Workshop AI & Coding SMAN 1 Cirebon', '2026-09-28', '09:00', '12:00');

-- --------------------------------------------------------

--
-- Table structure for table `event_sales`
--

CREATE TABLE `event_sales` (
  `id` bigint UNSIGNED NOT NULL,
  `event_id` bigint UNSIGNED NOT NULL,
  `sales_id` bigint UNSIGNED NOT NULL,
  `assigned_by_spv_id` bigint UNSIGNED DEFAULT NULL,
  `kehadiran` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan_kehadiran` text COLLATE utf8mb4_unicode_ci,
  `foto_kehadiran` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `google_event_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_sales`
--

INSERT INTO `event_sales` (`id`, `event_id`, `sales_id`, `assigned_by_spv_id`, `kehadiran`, `catatan_kehadiran`, `foto_kehadiran`, `created_at`, `updated_at`, `google_event_id`) VALUES
(1, 1, 6, NULL, NULL, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL),
(2, 1, 7, NULL, NULL, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL),
(3, 1, 8, NULL, NULL, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL),
(4, 1, 9, NULL, NULL, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL),
(5, 2, 6, NULL, NULL, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL),
(6, 2, 7, NULL, NULL, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL),
(7, 2, 8, NULL, NULL, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL),
(8, 2, 9, NULL, NULL, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL),
(9, 1, 5, NULL, NULL, NULL, NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46', NULL),
(10, 1, 11, NULL, NULL, NULL, NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46', NULL),
(11, 2, 5, NULL, NULL, NULL, NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46', NULL),
(12, 2, 11, NULL, NULL, NULL, NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_spv`
--

CREATE TABLE `event_spv` (
  `id` bigint UNSIGNED NOT NULL,
  `event_id` bigint UNSIGNED NOT NULL,
  `spv_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_spv`
--

INSERT INTO `event_spv` (`id`, `event_id`, `spv_id`, `created_at`, `updated_at`) VALUES
(1, 1, 3, '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(2, 2, 3, '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(3, 1, 4, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(4, 2, 4, '2026-09-29 16:08:46', '2026-09-29 16:08:46');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `follow_ups`
--

CREATE TABLE `follow_ups` (
  `id` bigint UNSIGNED NOT NULL,
  `prospek_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `metode` enum('WhatsApp','Telepon','Meeting','Email') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WhatsApp',
  `tanggal` datetime NOT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `hasil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `next_follow_up` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `follow_ups`
--

INSERT INTO `follow_ups` (`id`, `prospek_id`, `user_id`, `metode`, `tanggal`, `catatan`, `hasil`, `next_follow_up`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 'Email', '2026-09-20 02:33:05', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-26 02:33:05', '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
(2, 2, 7, 'Email', '2026-09-18 02:33:05', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-24 02:33:05', '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
(3, 2, 7, 'Email', '2026-09-22 02:33:05', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-27 02:33:05', '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
(4, 3, 8, 'Telepon', '2026-09-14 02:33:05', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-17 02:33:05', '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
(5, 4, 9, 'Telepon', '2026-09-17 02:33:05', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-21 02:33:05', '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
(6, 5, 6, 'WhatsApp', '2026-09-23 02:33:05', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-28 02:33:05', '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
(7, 5, 6, 'Meeting', '2026-09-16 02:33:05', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-20 02:33:05', '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
(8, 6, 7, 'Email', '2026-09-21 02:33:05', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-24 02:33:05', '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
(9, 6, 7, 'Email', '2026-09-22 02:33:05', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-27 02:33:05', '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
(10, 7, 8, 'Meeting', '2026-09-15 02:33:05', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-22 02:33:05', '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
(11, 7, 8, 'WhatsApp', '2026-09-15 02:33:06', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-21 02:33:06', '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
(12, 8, 9, 'Email', '2026-09-22 02:33:06', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-29 02:33:06', '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
(13, 9, 6, 'WhatsApp', '2026-09-13 02:33:06', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-20 02:33:06', '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
(14, 10, 7, 'WhatsApp', '2026-09-20 02:33:06', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-25 02:33:06', '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
(15, 10, 7, 'Meeting', '2026-09-12 02:33:06', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-17 02:33:06', '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
(16, 10, 7, 'Email', '2026-09-15 02:33:06', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-21 02:33:06', '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
(17, 1, 6, 'WhatsApp', '2026-09-16 02:34:09', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-22 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(18, 1, 6, 'Meeting', '2026-09-15 02:34:09', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-18 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(19, 1, 6, 'Email', '2026-09-12 02:34:09', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-18 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(20, 2, 7, 'Email', '2026-09-12 02:34:09', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-18 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(21, 2, 7, 'Email', '2026-09-22 02:34:09', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-28 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(22, 3, 8, 'Email', '2026-09-19 02:34:09', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-26 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(23, 3, 8, 'Email', '2026-09-13 02:34:09', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-20 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(24, 3, 8, 'Email', '2026-09-24 02:34:09', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-27 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(25, 4, 9, 'Email', '2026-09-21 02:34:09', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-27 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(26, 5, 6, 'Email', '2026-09-12 02:34:09', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-16 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(27, 5, 6, 'WhatsApp', '2026-09-24 02:34:09', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-27 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(28, 6, 7, 'Meeting', '2026-09-19 02:34:09', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-26 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(29, 6, 7, 'WhatsApp', '2026-09-25 02:34:09', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-01 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(30, 6, 7, 'Meeting', '2026-09-25 02:34:09', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-02 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(31, 7, 8, 'Email', '2026-09-14 02:34:09', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-20 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(32, 8, 9, 'WhatsApp', '2026-09-24 02:34:09', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-29 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(33, 9, 6, 'Meeting', '2026-09-15 02:34:09', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-20 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(34, 9, 6, 'Meeting', '2026-09-21 02:34:09', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-26 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(35, 9, 6, 'Email', '2026-09-12 02:34:09', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-18 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(36, 10, 7, 'WhatsApp', '2026-09-21 02:34:09', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-26 02:34:09', '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
(37, 11, 6, 'WhatsApp', '2026-09-29 22:29:37', '[Status: Dijawab] p', 'Dijawab', '2026-10-02 00:00:00', '2026-09-29 15:29:37', '2026-09-29 15:29:37'),
(38, 11, 6, 'WhatsApp', '2026-09-29 22:30:19', '[Status: Dijawab] p', 'Dijawab', '2026-10-02 00:00:00', '2026-09-29 15:30:19', '2026-09-29 15:30:19'),
(40, 1, 5, 'Meeting', '2026-09-19 23:08:46', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-24 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(41, 1, 5, 'Telepon', '2026-09-22 23:08:46', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(42, 2, 11, 'Telepon', '2026-09-28 23:08:46', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-05 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(43, 3, 5, 'WhatsApp', '2026-09-16 23:08:46', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-23 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(44, 4, 11, 'Meeting', '2026-09-21 23:08:46', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-24 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(45, 4, 11, 'Telepon', '2026-09-28 23:08:46', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-01 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(46, 4, 11, 'Meeting', '2026-09-15 23:08:46', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-21 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(47, 5, 5, 'Telepon', '2026-09-19 23:08:46', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-26 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(48, 5, 5, 'WhatsApp', '2026-09-18 23:08:46', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-22 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(49, 6, 11, 'Email', '2026-09-28 23:08:46', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-01 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(50, 7, 5, 'WhatsApp', '2026-09-16 23:08:46', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-22 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(51, 7, 5, 'WhatsApp', '2026-09-23 23:08:46', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-26 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(52, 8, 11, 'Meeting', '2026-09-17 23:08:46', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-22 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(53, 8, 11, 'Meeting', '2026-09-26 23:08:46', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-03 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(54, 9, 5, 'Telepon', '2026-09-26 23:08:46', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-01 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(55, 9, 5, 'WhatsApp', '2026-09-15 23:08:46', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-20 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(56, 9, 5, 'Meeting', '2026-09-28 23:08:46', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-01 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(57, 10, 11, 'Email', '2026-09-21 23:08:46', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-27 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(58, 10, 11, 'Telepon', '2026-09-28 23:08:46', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-05 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(59, 11, 6, 'Email', '2026-09-23 23:08:46', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-28 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(60, 11, 6, 'WhatsApp', '2026-09-26 23:08:46', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-02 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(61, 1, 5, 'Telepon', '2026-09-29 12:31:51', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-05 12:31:51', '2026-09-30 05:31:51', '2026-09-30 05:31:51'),
(62, 1, 5, 'WhatsApp', '2026-09-21 12:31:53', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-24 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(63, 2, 11, 'Email', '2026-09-23 12:31:53', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-27 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(64, 3, 5, 'WhatsApp', '2026-09-26 12:31:53', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-02 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(65, 3, 5, 'WhatsApp', '2026-09-27 12:31:53', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-01 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(66, 4, 11, 'Email', '2026-09-18 12:31:53', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-25 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(67, 4, 11, 'Meeting', '2026-09-29 12:31:53', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-05 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(68, 5, 5, 'Meeting', '2026-09-19 12:31:53', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-24 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(69, 6, 11, 'Telepon', '2026-09-23 12:31:53', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-26 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(70, 7, 5, 'WhatsApp', '2026-09-16 12:31:53', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-19 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(71, 7, 5, 'WhatsApp', '2026-09-25 12:31:53', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-02 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(72, 8, 11, 'Email', '2026-09-25 12:31:53', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-02 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(73, 8, 11, 'WhatsApp', '2026-09-29 12:31:53', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-06 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(74, 8, 11, 'Meeting', '2026-09-17 12:31:53', 'Follow-up via Meeting membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-23 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(75, 9, 5, 'Email', '2026-09-28 12:31:53', 'Follow-up via Email membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-10-01 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(76, 10, 11, 'Telepon', '2026-09-21 12:31:53', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-26 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(77, 10, 11, 'Telepon', '2026-09-19 12:31:53', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-26 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(78, 11, 6, 'Telepon', '2026-09-17 12:31:53', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-21 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(79, 11, 6, 'Telepon', '2026-09-21 12:31:53', 'Follow-up via Telepon membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-28 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(80, 11, 6, 'WhatsApp', '2026-09-21 12:31:53', 'Follow-up via WhatsApp membahas rincian program studi & kurikulum AI UCIC.', 'Prospek merespon positif dan berminat mengikuti sesi presentasi.', '2026-09-27 12:31:53', '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
(81, 12, 5, 'WhatsApp', '2026-09-30 12:37:04', '[Status: Dijawab] p', 'Dijawab', '2026-10-03 00:00:00', '2026-09-30 05:37:04', '2026-09-30 05:37:04'),
(82, 12, 5, 'WhatsApp', '2026-09-30 12:37:51', '[Status: Dijawab] p', 'Dijawab', '2026-10-03 00:00:00', '2026-09-30 05:37:51', '2026-09-30 05:37:51');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `prospek_id` bigint UNSIGNED DEFAULT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `due_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kunjungans`
--

CREATE TABLE `kunjungans` (
  `id` bigint UNSIGNED NOT NULL,
  `nomor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `tahun_akademik` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2027/2028',
  `waktu` time NOT NULL,
  `sales_id` bigint UNSIGNED NOT NULL,
  `dosen_id` bigint UNSIGNED DEFAULT NULL,
  `dosen_pemateri` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prodi_id` bigint UNSIGNED DEFAULT NULL,
  `prodi_ids` json DEFAULT NULL,
  `jenis` enum('Sekolah','Perusahaan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tujuan_id` bigint UNSIGNED NOT NULL,
  `tujuan_kunjungan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `hasil` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `nama_institusi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tier` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget_maksimum` decimal(14,2) DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lokasi_penugasan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_whatsapp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_outside_radius` tinyint(1) NOT NULL DEFAULT '0',
  `potensi_mahasiswa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sekolah: potensi jumlah beasiswa',
  `detail_potensi_mahasiswa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Sekolah: detail program beasiswa',
  `kesediaan_training_ai` tinyint(1) DEFAULT NULL COMMENT 'Sekolah: kesediaan mengikuti training AI/Robotik',
  `bidang_usaha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Perusahaan: bidang usaha',
  `potensi_s1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Perusahaan: potensi kelas karyawan S1',
  `potensi_s2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Perusahaan: potensi magister S2',
  `potensi_csr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Perusahaan: potensi CSR',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_verifikasi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Terverifikasi',
  `qr_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `jarak_meter` double DEFAULT NULL,
  `status_lokasi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `academic_year_id` bigint UNSIGNED DEFAULT NULL,
  `event_id` bigint UNSIGNED DEFAULT NULL,
  `kehadiran` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kunjungans`
--

INSERT INTO `kunjungans` (`id`, `nomor`, `tanggal`, `tahun_akademik`, `waktu`, `sales_id`, `dosen_id`, `dosen_pemateri`, `prodi_id`, `prodi_ids`, `jenis`, `tujuan_id`, `tujuan_kunjungan`, `hasil`, `catatan`, `nama_institusi`, `tier`, `budget_maksimum`, `alamat`, `lokasi_penugasan`, `pic_name`, `pic_whatsapp`, `foto_path`, `is_outside_radius`, `potensi_mahasiswa`, `detail_potensi_mahasiswa`, `kesediaan_training_ai`, `bidang_usaha`, `potensi_s1`, `potensi_s2`, `potensi_csr`, `status`, `status_verifikasi`, `qr_code`, `created_at`, `updated_at`, `lat`, `lng`, `jarak_meter`, `status_lokasi`, `is_verified`, `academic_year_id`, `event_id`, `kehadiran`) VALUES
(1, 'KNJ-20260926-001', '2026-09-23', '2027/2028', '09:30:00', 6, NULL, NULL, 1, NULL, 'Sekolah', 1, 'Presentasi Sosialisasi PMB & Demo AI', 'Presentasi berjalan lancar di hadapan 120 siswa kelas XII.', 'Pihak sekolah mengizinkan pendaftaran via jalur khusus beasiswa.', 'SMA Negeri 1 Cirebon', 'Tier 1', NULL, 'Jl. Wahidin No. 81, Sukapura', 'Kota Cirebon', 'Bpk. Drs. H. Mulyadi', '081234567890', NULL, 0, '150', 'Siswa kelas XII IPA & IPS sangat antusias terhadap prodi Informatika & DKV.', 1, NULL, NULL, NULL, NULL, 'Terverifikasi', 'Terverifikasi', 'KNJ-QR-20260926-ZN4WPWCMDCYL', '2026-09-25 19:33:06', '2026-09-25 19:33:06', -6.71350000, 108.55800000, 45.5, 'Dalam Radius', 1, 3, NULL, 'Hadir'),
(2, 'KNJ-20260926-002', '2026-09-25', '2027/2028', '14:00:00', 7, NULL, NULL, 1, NULL, 'Perusahaan', 1, 'Audensi Program Kelas Karyawan & Upskilling AI', 'HRD PT Cirebon Power menyetujui MoU kerjasama perkuliahan karyawan.', 'Rencana pendaftaran 25 karyawan untuk perkuliahan semester ganjil.', 'PT Cirebon Electric Power', 'Tier 1', NULL, 'Jl. Raya Kanci, Cirebon', 'Kabupaten Cirebon', 'Bpk. Ir. Rahmat Hidayat', '081399887766', NULL, 0, NULL, NULL, NULL, 'Pembangkit Listrik & Energi', '20', '5', '50000000', 'Terverifikasi', 'Terverifikasi', 'KNJ-QR-20260926-V7T7VHPXPLUY', '2026-09-25 19:33:06', '2026-09-25 19:33:06', -6.77210000, 108.62100000, 60, 'Dalam Radius', 1, 3, NULL, 'Hadir'),
(3, 'KNJ-20260929-001', '2026-09-26', '2027/2028', '09:30:00', 5, NULL, NULL, 1, NULL, 'Sekolah', 1, 'Presentasi Sosialisasi PMB & Demo AI', 'Presentasi berjalan lancar di hadapan 120 siswa kelas XII.', 'Pihak sekolah mengizinkan pendaftaran via jalur khusus beasiswa.', 'SMA Negeri 1 Cirebon', 'Tier 1', NULL, 'Jl. Wahidin No. 81, Sukapura', 'Kota Cirebon', 'Bpk. Drs. H. Mulyadi', '081234567890', NULL, 0, '150', 'Siswa kelas XII IPA & IPS sangat antusias terhadap prodi Informatika & DKV.', 1, NULL, NULL, NULL, NULL, 'Terverifikasi', 'Terverifikasi', 'KNJ-QR-20260929-VVR3SC0URTVR', '2026-09-29 16:08:46', '2026-09-29 16:08:46', -6.71350000, 108.55800000, 45.5, 'Dalam Radius', 1, 3, NULL, 'Hadir'),
(4, 'KNJ-20260929-002', '2026-09-28', '2027/2028', '14:00:00', 11, NULL, NULL, 1, NULL, 'Perusahaan', 1, 'Audensi Program Kelas Karyawan & Upskilling AI', 'HRD PT Cirebon Power menyetujui MoU kerjasama perkuliahan karyawan.', 'Rencana pendaftaran 25 karyawan untuk perkuliahan semester ganjil.', 'PT Cirebon Electric Power', 'Tier 1', NULL, 'Jl. Raya Kanci, Cirebon', 'Kabupaten Cirebon', 'Bpk. Ir. Rahmat Hidayat', '081399887766', NULL, 0, NULL, NULL, NULL, 'Pembangkit Listrik & Energi', '20', '5', '50000000', 'Terverifikasi', 'Terverifikasi', 'KNJ-QR-20260929-PET4ZDBYWI9E', '2026-09-29 16:08:47', '2026-09-29 16:08:47', -6.77210000, 108.62100000, 60, 'Dalam Radius', 1, 3, NULL, 'Hadir'),
(5, 'KNJ-20260930-001', '2026-09-27', '2027/2028', '09:30:00', 5, NULL, NULL, 1, NULL, 'Sekolah', 1, 'Presentasi Sosialisasi PMB & Demo AI', 'Presentasi berjalan lancar di hadapan 120 siswa kelas XII.', 'Pihak sekolah mengizinkan pendaftaran via jalur khusus beasiswa.', 'SMA Negeri 1 Cirebon', 'Tier 1', NULL, 'Jl. Wahidin No. 81, Sukapura', 'Kota Cirebon', 'Bpk. Drs. H. Mulyadi', '081234567890', NULL, 0, '150', 'Siswa kelas XII IPA & IPS sangat antusias terhadap prodi Informatika & DKV.', 1, NULL, NULL, NULL, NULL, 'Terverifikasi', 'Terverifikasi', 'KNJ-QR-20260930-LDST5TBTOQF5', '2026-09-30 05:31:53', '2026-09-30 05:31:53', -6.71350000, 108.55800000, 45.5, 'Dalam Radius', 1, 3, NULL, 'Hadir'),
(6, 'KNJ-20260930-002', '2026-09-29', '2027/2028', '14:00:00', 11, NULL, NULL, 1, NULL, 'Perusahaan', 1, 'Audensi Program Kelas Karyawan & Upskilling AI', 'HRD PT Cirebon Power menyetujui MoU kerjasama perkuliahan karyawan.', 'Rencana pendaftaran 25 karyawan untuk perkuliahan semester ganjil.', 'PT Cirebon Electric Power', 'Tier 1', NULL, 'Jl. Raya Kanci, Cirebon', 'Kabupaten Cirebon', 'Bpk. Ir. Rahmat Hidayat', '081399887766', NULL, 0, NULL, NULL, NULL, 'Pembangkit Listrik & Energi', '20', '5', '50000000', 'Terverifikasi', 'Terverifikasi', 'KNJ-QR-20260930-PYHSPBV3PPHF', '2026-09-30 05:31:53', '2026-09-30 05:31:53', -6.77210000, 108.62100000, 60, 'Dalam Radius', 1, 3, NULL, 'Hadir');

-- --------------------------------------------------------

--
-- Table structure for table `master_data`
--

CREATE TABLE `master_data` (
  `id` bigint UNSIGNED NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Aktif','Nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `master_data`
--

INSERT INTO `master_data` (`id`, `type`, `kode`, `nama`, `deskripsi`, `status`, `created_at`, `updated_at`) VALUES
(1, 'status_prospek', 'SP-01-BARU', 'BARU', 'Kontak baru, belum dihubungi', 'Aktif', '2026-09-25 19:33:01', '2026-09-25 19:33:01'),
(2, 'status_prospek', 'SP-02-KONTAK', 'KONTAK', 'Sudah dihubungi, belum respons', 'Aktif', '2026-09-25 19:33:01', '2026-09-25 19:33:01'),
(3, 'status_prospek', 'SP-03-HANGAT', 'HANGAT', 'Merespons, menanyakan biaya/jadwal', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(4, 'status_prospek', 'SP-04-PANAS', 'PANAS', 'Menyatakan berminat mendaftar', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(5, 'status_prospek', 'SP-05-FORMULIR', 'FORMULIR', 'Sudah bayar biaya pendaftaran', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(6, 'status_prospek', 'SP-06-BERKAS', 'BERKAS', 'Formulir dibayar, berkas belum lengkap', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(7, 'status_prospek', 'SP-07-LUNAS', 'LUNAS', 'Termin-1 lunas, resmi mahasiswa', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(8, 'status_prospek', 'SP-08-DINGIN', 'DINGIN', '14 hari tanpa respons setelah 5 sentuhan', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(9, 'status_followup', 'FU-DIJAWAB', 'Dijawab', 'Telepon/Pesan dijawab', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(10, 'status_followup', 'FU-TIDAK-DIJAWAB', 'Tidak Dijawab', 'Tidak ada respon', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(11, 'status_followup', 'FU-DITOLAK', 'Ditolak', 'Prospek menolak dihubungi', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(12, 'status_followup', 'FU-TERTARIK', 'Tertarik & Minta Brosur', 'Prospek minta brosur', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(13, 'status_followup', 'FU-JADWAL-KUNJUNGAN', 'Jadwalkan Kunjungan', 'Kunjungan ke sekolah', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(14, 'status_followup', 'FU-BELUM-RESPON', 'Belum Respon', 'Belum ada balasan', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(15, 'status_followup', 'FU-BELI-FORMULIR', 'FORMULIR', 'Sudah membeli form', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(16, 'jenis_kunjungan', 'JK-PRESENTASI', 'Presentasi', 'Presentasi ke siswa/guru', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(17, 'jenis_kunjungan', 'JK-SEBAR-BROSUR', 'Sebar Brosur', 'Menyebarkan brosur', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(18, 'jenis_kunjungan', 'JK-MOU', 'MoU', 'Kerjasama/MoU', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(19, 'kategori_prospek', 'KP-SANGAT-BERPELUANG', 'Sangat Berpeluang', 'Prospek sangat tertarik (Hot)', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(20, 'kategori_prospek', 'KP-MASIH-RAGU', 'Masih Ragu', 'Prospek masih pikir-pikir (Warm)', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(21, 'kategori_prospek', 'KP-BELUM-TERTARIK', 'Belum Tertarik', 'Prospek belum tertarik (Cold)', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(22, 'sumber_prospek', 'SRC-01', 'Teman/Keluarga/Saudara', 'Rujukan dari teman, keluarga, atau saudara', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(23, 'sumber_prospek', 'SRC-02', 'Sekolah', 'Dari pihak sekolah/guru BK', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(24, 'sumber_prospek', 'SRC-03', 'Sosial Media (Facebook, Instagram, X)', 'Dari konten/ads sosial media', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(25, 'sumber_prospek', 'SRC-04', 'Website CIC', 'Mengisi form di website resmi CIC', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(26, 'sumber_prospek', 'SRC-05', 'Brosur/Poster', 'Dari penyebaran brosur atau cetak', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(27, 'sumber_prospek', 'SRC-06', 'Sekretariat Kampus (Walk-in)', 'Datang langsung ke sekretariat PMB', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(28, 'sumber_prospek', 'SRC-07', 'Pameran/Expo/University Day', 'Hasil partisipasi event pameran', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(29, 'sumber_prospek', 'SRC-08', 'Acara Kampus', 'Dari acara/seminar kampus', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(30, 'sumber_prospek', 'SRC-09', 'MGBK/Miniclass', 'Hasil miniclass atau MGBK', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(31, 'sumber_prospek', 'SRC-10', 'Spanduk/Baliho', 'Media baliho atau spanduk luar ruangan', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(32, 'sumber_prospek', 'SRC-11', 'Lainnya', 'Sumber informasi lainnya', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(33, 'kategori_sekolah', 'KAT-SMA', 'SMA', 'Sekolah Menengah Atas', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(34, 'kategori_sekolah', 'KAT-SMK', 'SMK', 'Sekolah Menengah Kejuruan', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(35, 'kategori_sekolah', 'KAT-MA', 'MA', 'Madrasah Aliyah', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(36, 'kategori_perusahaan', 'KAT-IT', 'IT / Software House', 'Perusahaan bidang teknologi', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(37, 'kategori_perusahaan', 'KAT-MANUFAKTUR', 'Manufaktur', 'Pabrik dan industri', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(38, 'program_studi', 'PRD-MNJ', 'Manajemen', 'Program Studi S1 Manajemen', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(39, 'program_studi', 'PRD-TI', 'Teknik Informatika', 'Program Studi S1 Teknik Informatika', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(40, 'program_studi', 'PRD-DKV', 'DKV', 'Program Studi S1 Desain Komunikasi Visual', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(41, 'program_studi', 'PRD-BD', 'Bisnis Digital (Baru)', 'Program Studi S1 Bisnis Digital', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(42, 'program_studi', 'PRD-AKT', 'Akuntansi', 'Program Studi S1 Akuntansi', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(43, 'program_studi', 'PRD-SI', 'Sistem Informasi', 'Program Studi S1 Sistem Informasi', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(44, 'program_studi', 'PRD-PKOR', 'PKOR (Baru)', 'Program Studi S1 Pendidikan Kepelatihan Olahraga', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(45, 'program_studi', 'PRD-PMAT', 'Pendidikan Matematika (Baru)', 'Program Studi S1 Pendidikan Matematika', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(46, 'program_studi', 'PRD-MB-D3', 'Manajemen Bisnis (D3)', 'Program Studi D3 Manajemen Bisnis', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(47, 'program_studi', 'PRD-MI-D3', 'Manajemen Informatika (D3)', 'Program Studi D3 Manajemen Informatika', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(48, 'program_studi', 'PRD-S2-MNJ', 'S2 Manajemen (Tanpa Tesis)', 'Program Magister S2 Manajemen', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(49, 'jenjang', 'JENJANG-D3', 'D3', 'Diploma 3', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(50, 'jenjang', 'JENJANG-S1', 'S1', 'Strata 1', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(51, 'gelombang', 'GLB-1-24', 'Gelombang 1 2024/2025', 'Pendaftaran Gel 1', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(52, 'gelombang', 'GLB-2-24', 'Gelombang 2 2024/2025', 'Pendaftaran Gel 2', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_09_14_032741_create_master_data_table', 1),
(5, '2026_09_14_032742_create_wilayahs_table', 1),
(6, '2026_09_14_032743_create_sekolahs_table', 1),
(7, '2026_09_14_032744_create_prodis_table', 1),
(8, '2026_09_14_032745_create_perusahaans_table', 1),
(9, '2026_09_14_032746_create_kunjungans_table', 1),
(10, '2026_09_14_032747_create_targets_table', 1),
(11, '2026_09_15_024720_create_prospeks_table', 1),
(12, '2026_09_15_024721_create_follow_ups_table', 1),
(13, '2026_09_15_024722_create_prospek_timelines_table', 1),
(14, '2026_09_15_024723_add_hierarchy_to_users_table', 1),
(15, '2026_09_15_045230_alter_wilayahs_table_add_hierarchy', 1),
(16, '2026_09_16_020953_create_notifications_table', 1),
(17, '2026_09_16_044517_modify_wilayahs_kode_unique_constraint', 1),
(18, '2026_09_17_040000_add_lost_fields_to_prospeks_table', 1),
(19, '2026_09_17_040001_add_metode_to_follow_ups_table', 1),
(20, '2026_09_17_040002_add_visit_detail_fields_to_kunjungans_table', 1),
(21, '2026_09_17_114654_add_master_data_to_prospeks_table', 1),
(22, '2026_09_17_120925_add_source_to_prospeks_table', 1),
(23, '2026_09_17_120952_add_gps_to_kunjungans_table', 1),
(24, '2026_09_17_121406_remove_gps_from_kunjungans_table', 1),
(25, '2026_09_17_132948_change_status_columns_to_string', 1),
(26, '2026_09_18_023254_add_needs_visit_report_to_prospeks_table', 1),
(27, '2026_09_18_024545_create_transaksis_table', 1),
(28, '2026_09_18_034329_add_sales_id_to_sekolahs_and_perusahaans', 1),
(29, '2026_09_21_015627_add_target_menghubungi_to_targets_table', 1),
(30, '2026_09_22_000001_add_spv_p0_fields_to_tables', 1),
(31, '2026_09_22_030523_modify_role_column_in_users_table', 1),
(32, '2026_09_22_031516_add_geo_and_photo_to_kunjungans_table', 1),
(33, '2026_09_22_031735_add_geo_to_sekolahs_and_perusahaans_table', 1),
(34, '2026_09_22_032654_add_active_follow_up_count_to_prospeks_table', 1),
(35, '2026_09_22_033432_create_events_table', 1),
(36, '2026_09_22_035054_add_handover_at_to_prospeks_table', 1),
(37, '2026_09_22_035227_add_sales_id_to_events_table', 1),
(38, '2026_09_22_040110_create_personal_access_tokens_table', 1),
(39, '2026_09_22_040714_create_events_table', 1),
(40, '2026_09_22_040729_create_event_spv_table', 1),
(41, '2026_09_22_040740_create_event_sales_table', 1),
(42, '2026_09_22_043236_alter_users_add_eo_role', 1),
(43, '2026_09_22_053250_create_tahun_akademiks_table', 1),
(44, '2026_09_22_053314_add_academic_year_id_to_transactions_table', 1),
(45, '2026_09_22_054054_change_kunjungans_status_column_to_string', 1),
(46, '2026_09_22_054130_rename_beasiswa_fields_in_kunjungans_table', 1),
(47, '2026_09_22_110526_add_type_id_to_events_table', 1),
(48, '2026_09_22_121007_add_prodi_id_to_prospeks_table', 2),
(49, '2026_09_22_140000_add_phase5_fields_to_kunjungans_and_events', 3),
(50, '2026_09_22_160000_add_is_locked_to_targets_table', 4),
(51, '2026_09_22_170000_add_academic_year_id_to_events_table', 4),
(52, '2026_09_22_180000_add_google_fields_and_pivot_event_id', 4),
(53, '2026_09_22_210000_create_target_defisits_and_spv_enhancements', 4),
(54, '2026_09_23_040750_add_institution_fields_to_events_table', 4),
(55, '2026_09_23_041024_add_event_id_to_kunjungans_table', 4),
(56, '2026_09_23_042425_add_kehadiran_to_event_sales_table', 4),
(57, '2026_09_23_043144_extend_wilayah_level_enum_add_kelurahan', 4),
(58, '2026_09_23_093000_add_prodi_and_kelas_to_prospeks_table', 4),
(59, '2026_09_23_112000_update_targets_structure_and_scope', 4),
(60, '2026_09_23_130000_add_avatar_to_users_table', 4),
(61, '2026_09_23_140000_enhance_targets_cadence_and_parent', 4),
(62, '2026_09_23_150000_create_user_wilayah_table', 4),
(63, '2026_09_23_180000_add_name_and_time_columns_to_events_table', 4),
(64, '2026_09_23_180500_change_events_status_column_to_string', 4),
(65, '2026_09_24_140000_update_crm_revisions', 4),
(66, '2026_09_24_150000_add_jabatan_to_users_table', 4),
(67, '2026_09_24_155523_add_pending_to_users_status_enum', 4),
(68, '2026_09_29_213000_add_prodi_ids_to_kunjungans_table', 5),
(69, '2026_09_29_232000_update_users_unique_index_to_email_role', 6),
(70, '2026_09_29_100001_create_attendance_locations_table', 7),
(71, '2026_09_29_100002_create_attendances_table', 7),
(72, '2026_09_29_100003_create_invoices_table', 7),
(73, '2026_09_29_100004_create_payments_table', 7),
(74, '2026_09_29_232726_add_payment_verification_to_transaksis_table', 7),
(75, '2026_09_29_232733_create_bank_accounts_table', 7);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint UNSIGNED NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('009f8bd7-1ad1-49f3-9e8c-9ecf0226813d', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'Andi Wijaya\' (Yuda Thomas) masuk tahap Formulir dan dilimpahkan ke CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('00b01c7e-b0fc-43ff-bf28-9ab02623ce51', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Siti Nurhaliza\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('0205530c-0a3b-478e-b5c3-46e026dae8e3', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Budi Hendrawan\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('0299a479-18b1-4ff1-966c-59b128dee0b0', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\ud83d\\udccd Laporan Kunjungan Baru\",\"message\":\"Sales Lorenz Adam telah melaporkan kunjungan ke PT Cirebon Electric Power.\",\"type\":\"success\",\"link\":\"\\/spv\\/kunjungan\",\"icon\":\"\\ud83d\\udccd\",\"sender_name\":\"Lorenz Adam\",\"sender_role\":\"Sales\",\"action\":\"kunjungan_created\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('02bb7f36-20ea-45f3-b99d-3325d1b6d877', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Andi Wijaya\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('057a2925-a529-4873-a6df-7e7930424a19', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Rina Kartika\' (Sales: Sales Lorenz Adam).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('073cbc1a-fe5a-415a-9059-6d5c0e6a1c8b', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'Andi Wijaya\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"cs_handover\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('076bf8c9-3ecb-42b7-bd00-61398266c503', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 9, '{\"title\":\"\\ud83d\\udc64 Prospek Baru Ditugaskan\",\"message\":\"Sistem menugaskan prospek baru kepada Anda: Aulia Putri (SMA Santa Maria Cirebon).\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('0853b11e-e5d4-438e-a1d4-8a82f21b4fe7', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Rina Kartika\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('09c28c49-9e01-4331-92d5-8057c1f3e715', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Eko Prasetyo\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('09f1616b-c03a-454b-a30f-ffccfc6c0c9d', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'Rina Kartika\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"cs_handover\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('0a1786f6-3aaa-4da4-a7b2-a68ba78446d9', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Andi Wijaya\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('0a1ba539-3878-4245-b410-70d188f1abde', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Eko Prasetyo\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('0a2ddf85-1dfb-4e0b-9a7f-c70ae4382b27', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Andi Wijaya\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('0b265c79-a59d-425f-aeb4-55d56807536d', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'PT Cirebon Power Development\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('0d89b6a5-89a9-4d71-a136-1f2a1af3351c', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) diterima untuk \'Eko Prasetyo\' (Sales: Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('0e7ffa00-58cd-4bf0-9fe2-10f0206645f8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'PT Cirebon Power Development\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('100e3f64-55ff-40a0-914b-480082d74ba8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Rina Kartika\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('10cdcb42-8ae9-408e-bba3-fd1eba1d8ff7', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Aulia Putri\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('11a40491-536c-4f17-8c83-f0a664438965', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'Fajar Nugraha\' (Yuda Thomas) masuk tahap Formulir dan dilimpahkan ke CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('158d5cb5-6b8b-4b2e-8ee2-872029921f48', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'Dewi Lestari\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"cs_handover\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('15b13e45-65c2-46e6-80c8-f9d1f7b1fd12', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Andi Wijaya\'.\",\"type\":\"success\",\"link\":\"http:\\/\\/localhost:8000\\/cs\\/verifikasi\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('1ac4258f-a527-43a0-a408-070bb25a3532', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Budi Hendrawan\' (Sales: Sales CIC).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('1ce8821f-688e-4c21-b982-84a346e79928', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Fajar Nugraha\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('1e033065-d531-4219-9315-87c432e3ca3a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Dewi Lestari\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('1efdf1d7-e234-4160-bda8-3c9535248dfb', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 9, '{\"title\":\"\\ud83d\\udc64 Prospek Baru Ditugaskan\",\"message\":\"Sistem menugaskan prospek baru kepada Anda: Dewi Lestari (SMA Negeri 2 Cirebon).\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('1fb82a1e-11b1-4f7a-8664-2095b0d0ea22', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udccd Laporan Kunjungan Baru\",\"message\":\"Sales Aurel Calista telah melaporkan kunjungan ke PT Cirebon Electric Power.\",\"type\":\"success\",\"link\":\"\\/spv\\/kunjungan\",\"icon\":\"\\ud83d\\udccd\",\"sender_name\":\"Aurel Calista\",\"sender_role\":\"Sales\",\"action\":\"kunjungan_created\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('212bc15b-7c5b-4ff4-a31a-9a4c33c1c626', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Eko Prasetyo\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('21569f96-9281-40ce-a569-1b34d86b3f7b', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Siti Nurhaliza\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('21625285-c4b4-4550-af8e-0e0e10d81cbe', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Eko Prasetyo\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('21fad3a8-1815-4540-a5e2-a9eaa43bf7f4', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Andi Wijaya\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('230eabc7-f525-46c7-82e4-3a95b23be801', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'Rina Kartika\' (Lorenz Adam) masuk tahap Formulir dan dilimpahkan ke CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Lorenz Adam\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('238da84e-3878-4589-8371-44655911d7db', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Prospek Baru (CS)\",\"message\":\"Sistem menugaskan prospek baru: PT Cirebon Power Development.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned_cs\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('25d49e1e-80f4-4fa8-abb2-6af3f7ed134e', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Rina Kartika\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('29280638-5326-4cd5-84bf-1e191e81ab63', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Budi Hendrawan\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('29344b56-e1f5-4d9d-af10-e6d44727217e', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Eko Prasetyo\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('2a0b225a-13ae-46e8-b71f-79c26f4ffe15', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udc64 Prospek Baru Ditugaskan\",\"message\":\"Sistem menugaskan prospek baru kepada Anda: Budi Hendrawan (SMA Negeri 1 Cirebon).\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:05', '2026-09-29 05:39:35'),
('2a173a4e-2808-49aa-a7d9-34b7c39f81c8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Andi Wijaya\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('2c4cbe7e-b117-4fa0-8307-c8b492285ffc', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'Eko Prasetyo\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"cs_handover\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('302a3bdd-045e-4845-b382-5fc4b42b5602', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) diterima untuk \'Budi Hendrawan\' (Sales: Sales Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('312bc750-2cfb-4e3e-b7e2-b02861cae380', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Prospek Baru (CS)\",\"message\":\"Sistem menugaskan prospek baru: Andi Wijaya.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned_cs\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('33b2a9df-a172-4947-9544-5a044a0b05c8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'Dewi Lestari\' (Lorenz Adam) masuk tahap Formulir dan dilimpahkan ke CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Lorenz Adam\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('36ecd516-bddf-44fd-878a-bef58a93f181', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Budi Hendrawan\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('37420b1f-b20f-44e7-92a0-cf5696f0948c', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Budi Hendrawan\' (Sales: Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('38617c52-ad7e-448b-aeef-4486a0313cce', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'Fajar Nugraha\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"cs_handover\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('389401ef-6587-4db8-a42f-85d8075ae56a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Andi Wijaya\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('3913f1b6-a120-44c5-99e0-76770a2e9e2b', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udc64 Prospek Baru dari Sales\",\"message\":\"Sales Sales Utama CIC menambahkan prospek baru: SMA 1 BREBES (SMA 1 BREBES).\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":\"Sales Utama CIC\",\"sender_role\":\"Sales\",\"action\":\"prospek_created_sales\"}', NULL, '2026-09-29 06:57:34', '2026-09-29 06:57:34'),
('391e7a7c-c564-4442-92f8-3b0cb7206bc2', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\ud83d\\udccd Laporan Kunjungan Baru\",\"message\":\"Sales Sales Lorenz Adam telah melaporkan kunjungan ke PT Cirebon Electric Power.\",\"type\":\"success\",\"link\":\"\\/spv\\/kunjungan\",\"icon\":\"\\ud83d\\udccd\",\"sender_name\":\"Sales Lorenz Adam\",\"sender_role\":\"Sales\",\"action\":\"kunjungan_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('39630be3-5a08-4ef7-acd1-fb0e41e9a1d6', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udc64 Pengalihan Prospek\",\"message\":\"Prospek \'Rina Kartika\' telah dialihkan kepada Anda oleh Sistem.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_reassigned\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('396f305e-1166-4366-838c-96c2d2fb5ac0', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Budi Hendrawan\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('3a5a561d-93ab-4819-a676-5cb636c42915', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 3.500.000 (Pembayaran Termin 1) tercatat untuk prospek \'Budi Hendrawan\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('3be81f42-f058-4202-ad1c-ddb4b410010a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Rian Hidayat\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:05', '2026-09-29 05:39:35'),
('3d751f3f-f060-4481-bd31-a7f25debf617', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Rina Kartika\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('3e072c99-96df-4e82-9fde-7940c5da1a2b', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Rina Kartika\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('3e7822ca-3d3a-4b51-ab18-e97a2a26805b', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udc64 Pengalihan Prospek\",\"message\":\"Prospek \'Aulia Putri\' telah dialihkan kepada Anda oleh Sistem.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_reassigned\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('3f4e27ce-7ba8-4b64-b253-53eeeba95ee9', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Fajar Nugraha\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('3f9871b6-cbda-41c3-92db-6d3f489c6f54', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Prospek Baru (CS)\",\"message\":\"Sistem menugaskan prospek baru: Eko Prasetyo.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned_cs\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('3fe8c7eb-60f7-4d92-9152-a93937e8297d', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Budi Hendrawan\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('40fdbf3f-61c1-4add-b412-7467362abc70', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Budi Hendrawan\'.\",\"type\":\"success\",\"link\":\"http:\\/\\/localhost:8000\\/cs\\/verifikasi\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('4123ea66-6b81-42ec-b9a8-f8c29d597f85', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udc64 Pengalihan Prospek\",\"message\":\"Prospek \'Rian Hidayat\' telah dialihkan kepada Anda oleh Sistem.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_reassigned\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('41ba2169-6e19-4905-a056-93202ae0acb8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'Rian Hidayat\' (Yuda Thomas) masuk tahap Formulir dan dilimpahkan ke CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('42183922-ae2b-4a6d-90ea-d98c35d7b511', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Eko Prasetyo\'.\",\"type\":\"success\",\"link\":\"http:\\/\\/localhost:8000\\/cs\\/verifikasi\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('434bedff-7ae4-43c8-8331-518e2a0b72b6', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 9, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Aulia Putri\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('443e7cdd-1641-4da1-955a-dbea8e08de49', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Siti Nurhaliza\' (Sales: Sales Lorenz Adam).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('452f7092-c5ff-425b-9813-47cf351188ab', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udd14 Verifikasi Pembayaran Diperlukan\",\"message\":\"Ada pembayaran Pembayaran Termin 1 yang perlu diverifikasi dari Sales Sales Yuda Thomas untuk calon mahasiswa \'SMA 1 BREBES\', nominal Rp 5.000.000 via GoPay.\",\"type\":\"warning\",\"link\":\"http:\\/\\/127.0.0.1:8000\\/cs\\/verifikasi\",\"icon\":\"\\ud83d\\udd14\",\"sender_name\":\"Sales Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"verifikasi_pembayaran_cs\"}', NULL, '2026-09-30 05:59:29', '2026-09-30 05:59:29'),
('4586b010-dcbf-4a34-bb93-fa17756d0f8a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 3.500.000 (Pembayaran Termin 1) tercatat untuk prospek \'Eko Prasetyo\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('47532f10-3f70-4568-b75d-7b3df49e0026', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Rina Kartika\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('4b1c3c41-e573-46f7-9faf-024f7f771e41', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 3.500.000 (Pembayaran Termin 1) tercatat untuk prospek \'Eko Prasetyo\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('4b389ae7-d661-42b7-a601-7bfd197cf38a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'SMA 1 BREBES\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":\"Sales Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"cs_handover\"}', NULL, '2026-09-30 05:37:04', '2026-09-30 05:37:04'),
('4c8226d6-3018-4bf4-a5f2-e69e14c7ed38', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Rian Hidayat\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('4cfe3794-b150-44ac-930e-d3361401a577', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udccd Laporan Kunjungan Baru\",\"message\":\"Sales Sales Yuda Thomas telah melaporkan kunjungan ke SMA Negeri 1 Cirebon.\",\"type\":\"success\",\"link\":\"\\/spv\\/kunjungan\",\"icon\":\"\\ud83d\\udccd\",\"sender_name\":\"Sales Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"kunjungan_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('4d0838db-5fa5-4259-9d94-034256f4ca39', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udd14 Verifikasi Pembayaran Diperlukan\",\"message\":\"Ada pembayaran Pembayaran Termin 1 yang perlu diverifikasi dari Sales Sales Yuda Thomas untuk calon mahasiswa \'Budi Hendrawan\', nominal Rp 250.000 via Transfer Bank.\",\"type\":\"warning\",\"link\":\"http:\\/\\/127.0.0.1:8000\\/cs\\/verifikasi\",\"icon\":\"\\ud83d\\udd14\",\"sender_name\":\"Sales Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"verifikasi_pembayaran_cs\"}', NULL, '2026-09-30 06:06:26', '2026-09-30 06:06:26'),
('4d66bdc6-49cd-4de9-9026-83f2b355f663', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'PT Cirebon Power Development\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"cs_handover\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('4dfa309e-e335-4ed6-aa13-816471c3b552', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udc64 Pengalihan Prospek\",\"message\":\"Prospek \'Andi Wijaya\' telah dialihkan kepada Anda oleh Sistem.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_reassigned\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('4e7fb6e1-637e-48e0-823f-73a7bce3dce9', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Aulia Putri\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('50b2e888-8325-4a21-994f-a5ceb51172b2', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udc64 Prospek Baru Ditugaskan\",\"message\":\"Sistem menugaskan prospek baru kepada Anda: Fajar Nugraha (SMK Negeri 2 Cirebon).\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('50fadc08-5faf-4220-9cb7-416e4bf6b15f', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'SMA 1 BREBES\' (Sales Utama CIC) masuk tahap Formulir dan dilimpahkan ke CS Dina Marlina (CS).\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Sales Utama CIC\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 15:29:38', '2026-09-29 15:29:38'),
('52e961f2-88ae-4e12-8f2f-14e7a3dd6686', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Eko Prasetyo\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('53116d02-b45f-4656-a27e-80bed1e483c0', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Fajar Nugraha\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('53a13b99-412f-4b5f-8eae-22faa6818b8c', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udccd Laporan Kunjungan Baru\",\"message\":\"Sales Sales CIC telah melaporkan kunjungan ke SMA Negeri 1 Cirebon.\",\"type\":\"success\",\"link\":\"\\/spv\\/kunjungan\",\"icon\":\"\\ud83d\\udccd\",\"sender_name\":\"Sales CIC\",\"sender_role\":\"Sales\",\"action\":\"kunjungan_created\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('54019810-06cb-459c-b845-6439ce556b0e', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 9, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Aulia Putri\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('57a948b4-3173-4d8e-abe7-0a67d2db1700', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Siti Nurhaliza\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('5952f27d-b1c3-42ec-b01c-a529abab88c4', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'PT Cirebon Power Development\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('59901b36-fd18-472e-8b61-2825ab3f7081', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Rian Hidayat\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('5aa2785d-f6dc-43eb-be18-be72f7e1cb5e', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Andi Wijaya\' (Sales: Rizky Pratama).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('5b43e76d-c857-4122-b8fd-565ddddc1e19', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Eko Prasetyo\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:06', '2026-09-29 05:39:35'),
('5dfaca3c-3491-46d8-b1cb-a24b4517f5dd', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Budi Hendrawan\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('5e46c9ed-43f7-40e1-be70-c7c77481f076', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Siti Nurhaliza\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('5f52bcfc-b9ec-4218-b33b-3f3f97284774', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) tervalidasi untuk prospek \'Budi Hendrawan\'.\",\"type\":\"success\",\"link\":\"http:\\/\\/localhost:8000\\/cs\\/verifikasi\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('5fd8f371-daab-4783-a3b9-d4c2a182e0bf', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udc64 Pengalihan Prospek\",\"message\":\"Prospek \'Siti Nurhaliza\' telah dialihkan kepada Anda oleh Sistem.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_reassigned\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('60adec4c-a9e4-44c9-80ab-bd4fd95160fd', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udc64 Prospek Baru Ditugaskan\",\"message\":\"Sistem menugaskan prospek baru kepada Anda: PT Cirebon Power Development (PT Krakatau Steel Cirebon).\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('6393ff1f-a672-4852-9e6d-b76a570bc9ed', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Andi Wijaya\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('64408af1-a971-4e7b-8ba6-fe72a7b72cde', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 3.500.000 (Pembayaran Termin 1) tercatat untuk prospek \'Budi Hendrawan\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('674ecd7d-7c3c-45c9-9e24-b521bac92b36', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udc64 Pengalihan Prospek\",\"message\":\"Prospek \'Dewi Lestari\' telah dialihkan kepada Anda oleh Sistem.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_reassigned\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('6768b921-3026-4458-b109-f430eb0275cf', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Budi Hendrawan\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('67f5ae8a-dc99-4e15-b521-74d951a258fe', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Fajar Nugraha\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('6800e543-d4c1-4939-8bdf-c54a92520d7a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Budi Hendrawan\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('68f9ec87-f76e-4522-b4fb-62d8028bee11', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Aulia Putri\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('69b5760b-63cd-4437-9ef7-3de7938a4bd8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) diterima untuk \'Eko Prasetyo\' (Sales: Sales CIC).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('6a40610c-c6a3-40ba-b737-6b7a889790ad', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'SMA 1 BREBES\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('6c625174-2221-456e-950b-1f759fcf7451', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Andi Wijaya\' (Sales: Rizky Pratama).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('6d51921a-ec91-47fd-a9fa-3898a2d3522a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udc64 Pengalihan Prospek\",\"message\":\"Prospek \'Eko Prasetyo\' telah dialihkan kepada Anda oleh Sistem.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_reassigned\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('6e2bdb0b-c1d6-4a17-9dfa-babdbe1012a3', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Siti Nurhaliza\' (Sales: Lorenz Adam).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('704348d9-9ce2-471d-8d6a-d162a7332dae', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Dewi Lestari\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('711ce7e8-beb1-49dc-ad22-8d1b6ca23801', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Rina Kartika\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06');
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('71e9cc56-2e34-4129-a384-35cf1b6319bf', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Siti Nurhaliza\' (Sales: Aurel Calista).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('72e412b2-5dbc-4206-a7b9-e45c9c7bd1fb', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udccd Laporan Kunjungan Baru\",\"message\":\"Sales Yuda Thomas telah melaporkan kunjungan ke SMA Negeri 1 Cirebon.\",\"type\":\"success\",\"link\":\"\\/spv\\/kunjungan\",\"icon\":\"\\ud83d\\udccd\",\"sender_name\":\"Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"kunjungan_created\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('75496939-6425-4f52-9299-893752ca2e5a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Dewi Lestari\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('764f1393-c1f0-4f5c-a24f-9da0eb3c05f9', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) diterima untuk \'Eko Prasetyo\' (Sales: Sales Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('77ac811f-db91-45d4-9bee-48bb4adb0566', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'SMA 1 BREBES\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('78e7b63c-0a6d-4028-9bcd-dec9af421d32', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 3.500.000 (Pembayaran Termin 1) tercatat untuk prospek \'Eko Prasetyo\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('79bf27ed-7935-4e20-a173-856659c7b37b', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Eko Prasetyo\' (Sales: Sales Utama CIC).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('7a71c5b2-066a-4960-be96-f6455ca81a97', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Prospek Baru (CS)\",\"message\":\"Sistem menugaskan prospek baru: Aulia Putri.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned_cs\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('7acac13b-d039-405e-b4d9-245dd043f576', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Budi Hendrawan\' (Sales: Sales Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('7b18093d-92d0-4927-a280-5b12d4c131c8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udc64 Pengalihan Prospek\",\"message\":\"Prospek \'Fajar Nugraha\' telah dialihkan kepada Anda oleh Sistem.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_reassigned\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('7b2332a1-a3c3-4dd7-8e31-b1a6c1b949dc', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'Aulia Putri\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"cs_handover\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('7d10eca1-7927-4ec2-bef0-ae0f040b8b65', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Andi Wijaya\' (Sales: Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('80eed625-7121-4324-9083-b8b0fdf5bacf', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Prospek Baru (CS)\",\"message\":\"Sistem menugaskan prospek baru: Siti Nurhaliza.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned_cs\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('8172318b-8dbb-45f2-adeb-8799e47465f9', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Rina Kartika\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('818eb151-0170-4f26-881d-88bcc0aba65c', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Rina Kartika\'.\",\"type\":\"success\",\"link\":\"http:\\/\\/localhost:8000\\/cs\\/verifikasi\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('85a5fc92-4c1f-4f31-b7d4-adf569498c83', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'PT Cirebon Power Development\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('880fab2a-4cc6-4ffa-ab2d-1e0e297c15fe', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udc64 Prospek Baru Ditugaskan\",\"message\":\"Sistem menugaskan prospek baru kepada Anda: Siti Nurhaliza (SMK Informatika Al-Irsyad).\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('8936e17b-6287-4e08-b81f-a8f8bb378712', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Budi Hendrawan\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:06', '2026-09-29 05:39:35'),
('8ae6acd3-3432-4c98-9b6d-de741e99de76', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Rina Kartika\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('8ae9a057-978c-4d70-bce8-aff5d5f5072d', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcc8 Prospek Masuk Tahap FORMULIR\",\"message\":\"Sales Yuda Thomas (Sales) memperbarui status \'SMA 1 BREBES\' ke tahap FORMULIR.\",\"type\":\"info\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcc8\",\"sender_name\":\"Sales Yuda Thomas (Sales)\",\"sender_role\":\"Sales\",\"action\":\"prospek_status_update\"}', NULL, '2026-09-30 05:37:04', '2026-09-30 05:37:04'),
('8b538c42-31f8-49a6-9380-2349fda86baa', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 200.000.000 (Beli Formulir) tervalidasi untuk prospek \'SMA 1 BREBES\'.\",\"type\":\"success\",\"link\":\"http:\\/\\/127.0.0.1:8000\\/cs\\/verifikasi\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":\"Sales Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-30 05:44:26', '2026-09-30 05:44:26'),
('8c7a390c-d3b8-498d-8752-cbd54b9c44fe', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Siti Nurhaliza\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('90920b13-0a58-4972-8eac-d106750dc143', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Budi Hendrawan\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('90ee8920-b1ec-41d0-bcdc-c8ae8cce07d0', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) diterima untuk \'Budi Hendrawan\' (Sales: Sales CIC).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('94a7103f-5605-44ee-82cf-c891aed08c1d', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Siti Nurhaliza\' (Sales: Aurel Calista).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('9528b6fe-c707-494a-a16e-bea3475c9e2d', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 200.000.000 (Beli Formulir) diterima untuk \'SMA 1 BREBES\' (Sales: Sales Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":\"Sales Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-30 05:44:26', '2026-09-30 05:44:26'),
('95d935a2-2fdc-4526-9160-5440ad73faa7', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Budi Hendrawan\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('96763388-cf3f-4b43-9687-efc677714615', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Siti Nurhaliza\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('97fcc93e-3bfb-4473-b2f7-a2e3c7f4cef3', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'PT Cirebon Power Development\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('99248f93-be11-4bd3-8e81-f4b55e1a7541', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Eko Prasetyo\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('9b91c7b3-14ec-4e75-a975-ead41b9c36a4', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) tervalidasi untuk prospek \'Eko Prasetyo\'.\",\"type\":\"success\",\"link\":\"http:\\/\\/localhost:8000\\/cs\\/verifikasi\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('9bde728e-7718-4373-8f1d-7a8f7edc9baa', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Andi Wijaya\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('9d0ec8c5-d688-4491-b201-2ad3ebf6172b', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'Rian Hidayat\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"cs_handover\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('9fe61320-1306-4276-aab8-a719cb3c25d2', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Siti Nurhaliza\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('a0195ac1-0e71-4fb7-962e-d0b347ddd153', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Rian Hidayat\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('a0fbfef7-b36b-4541-a094-f58504843c7b', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'SMA 1 BREBES\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('a13e6bec-b5ce-4b9d-8426-d891d89fc017', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) tervalidasi untuk prospek \'Budi Hendrawan\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('a2efab73-c7dd-4971-b5e6-3b7d4e916b1d', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Rina Kartika\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('a36c4ec2-64e5-41ca-9867-3067d702314e', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'Budi Hendrawan\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"cs_handover\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('a5deaa6a-2f33-4c58-8292-224ebb253cad', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'PT Cirebon Power Development\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('a93855f3-61ad-4311-833f-177c97959110', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Fajar Nugraha\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('a9c7088a-eff2-4a07-b9d8-27c353d5f3b8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Prospek Baru (CS)\",\"message\":\"Sistem menugaskan prospek baru: Rian Hidayat.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned_cs\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('a9f3d414-fd2a-415b-9369-32c8527d624f', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'Eko Prasetyo\' (Yuda Thomas) masuk tahap Formulir dan dilimpahkan ke CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('ac11cb29-dab7-490d-bd4f-6f4db3b029fa', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Budi Hendrawan\' (Sales: Sales Utama CIC).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('ac4a7cb2-14ad-4e7b-b1e3-64a296801e0c', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Andi Wijaya\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('ace9cfc0-55ac-495a-9064-497754e08c67', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Siti Nurhaliza\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('ad5dce85-8f66-438f-a730-81403e8623ee', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Prospek Baru (CS)\",\"message\":\"Sistem menugaskan prospek baru: Fajar Nugraha.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned_cs\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('ae10606d-970a-48b5-8661-22dba1e47d8b', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Siti Nurhaliza\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('af16c0df-2a87-4ae5-a30a-5eb969baea46', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Siti Nurhaliza\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('af2ba57a-16e0-412a-82c6-c63f60444bba', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Pembayaran Termin 1) diterima untuk \'Budi Hendrawan\' (Sales: Sales Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":\"Sales Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-30 06:06:26', '2026-09-30 06:06:26'),
('af45514c-ffc5-4277-bd0d-abf638ac9976', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcc8 Prospek Masuk Tahap CLOSING\",\"message\":\"Sales Utama CIC (Sales) memperbarui status \'SMA 1 BREBES\' ke tahap CLOSING.\",\"type\":\"info\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcc8\",\"sender_name\":\"Sales Utama CIC (Sales)\",\"sender_role\":\"Sales\",\"action\":\"prospek_status_update\"}', NULL, '2026-09-29 15:30:19', '2026-09-29 15:30:19'),
('b0a6993e-f31b-48e4-9c16-c4a12cf1c2a0', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Eko Prasetyo\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('b1b6d286-8676-412d-b26a-18dee6abdb9a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) diterima untuk \'Budi Hendrawan\' (Sales: Sales Utama CIC).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('b2b5877f-430b-4732-95b8-ce267d5eda2e', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Eko Prasetyo\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('b4e1143e-ddc9-471b-92c4-b6f03296c620', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Andi Wijaya\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('b50cb5ec-def4-4253-ba3b-ae088fb94b94', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udc64 Pengalihan Prospek\",\"message\":\"Prospek \'PT Cirebon Power Development\' telah dialihkan kepada Anda oleh Sistem.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_reassigned\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('b52697ff-18bd-40a4-970d-407b8ae62f2d', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'SMA 1 BREBES\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('b5499fe9-b259-4075-a62f-32e0a6a6bcfa', 'App\\Notifications\\TargetNotification', 'App\\Models\\User', 5, '{\"title\":\"\\u26a0\\ufe0f Evaluasi Target: Belum Tuntas (Defisit 4 Maba Lunas)\",\"message\":\"Perhatian: Target Bulanan Anda tersisa 1 hari dengan defisit 4 Maba Lunas. Segera lakukan koordinasi dan tindak lanjut untuk mengejar target.\",\"type\":\"warning\",\"link\":\"http:\\/\\/127.0.0.1:8000\\/target-performa\",\"icon\":\"\\u26a0\\ufe0f\",\"target_id\":6,\"event_type\":\"target_warning\"}', NULL, '2026-09-30 06:25:53', '2026-09-30 06:25:53'),
('b5aff9ea-0edd-41ac-b044-82b0a2388e7c', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Rina Kartika\' (Sales: Aurel Calista).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('b6963f9c-e100-4e73-8128-2d73665b4d63', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udc64 Prospek Baru Ditugaskan\",\"message\":\"Sistem menugaskan prospek baru kepada Anda: Rian Hidayat (SMA Negeri 3 Cirebon).\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:05', '2026-09-29 05:39:35'),
('bb350f57-3582-4a06-a8b6-2f6574567317', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Eko Prasetyo\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('bbeaa24c-b384-4e4e-9b7c-ec2f71ae64b4', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcc8 Prospek Masuk Tahap BERKAS\",\"message\":\"Sales Yuda Thomas (Sales) memperbarui status \'Budi Hendrawan\' ke tahap BERKAS.\",\"type\":\"info\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcc8\",\"sender_name\":\"Sales Yuda Thomas (Sales)\",\"sender_role\":\"Sales\",\"action\":\"prospek_status_update\"}', NULL, '2026-09-30 06:06:26', '2026-09-30 06:06:26'),
('bd167581-b8d9-4563-b307-2bafe00ceff7', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Andi Wijaya\' (Sales: Sales Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('bf431479-c32d-4658-a1df-3cae57f3c6ff', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Rina Kartika\' (Sales: Aurel Calista).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('bffa1ed9-b405-410d-85e9-e4e638023861', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Andi Wijaya\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('c0121cee-1719-4784-ad54-bde8f50cce9c', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'SMA 1 BREBES\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":\"Sales Utama CIC\",\"sender_role\":\"Sales\",\"action\":\"cs_handover\"}', NULL, '2026-09-29 15:29:38', '2026-09-29 15:29:38'),
('c0b43440-adcd-4d8b-bbdd-b4e4ddb404be', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Siti Nurhaliza\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('c102bb4b-d962-41a2-906b-954c32f1e3cb', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\u2705 Pembayaran Diverifikasi!\",\"message\":\"Pembayaran Rp 5.000.000 untuk \'SMA 1 BREBES\' telah diverifikasi oleh CS. Prospek dinyatakan LUNAS (Closing).\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\u2705\",\"sender_name\":\"CS Yuda Thomas\",\"sender_role\":\"CS\",\"action\":\"pembayaran_diverifikasi\"}', NULL, '2026-09-30 06:25:52', '2026-09-30 06:25:52'),
('c16cc301-01d7-4f41-870d-0f6f06a9713e', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Rina Kartika\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('c189327b-ec83-4fb2-9f0a-5021aa0bb8eb', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Eko Prasetyo\' (Sales: Sales Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('c2e97f1d-3dd0-43e8-a373-b333e12f1cf0', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Rian Hidayat\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('c4935cb0-c2d7-403f-a466-4d748febc382', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'PT Cirebon Power Development\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('c5e8a8e5-9686-4906-9cfd-8c8c44430870', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcc8 Prospek Masuk Tahap FORMULIR\",\"message\":\"Sales Utama CIC (Sales) memperbarui status \'SMA 1 BREBES\' ke tahap FORMULIR.\",\"type\":\"info\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcc8\",\"sender_name\":\"Sales Utama CIC (Sales)\",\"sender_role\":\"Sales\",\"action\":\"prospek_status_update\"}', NULL, '2026-09-29 15:29:38', '2026-09-29 15:29:38'),
('c642ec79-92ce-4c7f-a2b1-d93cff029a09', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Budi Hendrawan\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('c6c7df24-d5ed-415c-9108-23b4785bb3b1', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) tervalidasi untuk prospek \'Eko Prasetyo\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('c75ecd9d-fdf9-43d8-aa19-bf48b526a08d', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Prospek Baru (CS)\",\"message\":\"Sistem menugaskan prospek baru: Dewi Lestari.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned_cs\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('c80f796b-bafd-4401-992f-4ea0b99f7ec9', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) tervalidasi untuk prospek \'Eko Prasetyo\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('c85393eb-8fe2-423a-b3ca-c0463a19ceb7', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) diterima untuk \'Budi Hendrawan\' (Sales: Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('c8c1117c-8856-4c4f-aedd-42b242986ed7', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Eko Prasetyo\' (Sales: Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('c91c2f88-3b13-4663-817c-dff491d3fa9f', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Andi Wijaya\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('c936bc23-7ae7-47f1-8387-cd92fd582dd3', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'Siti Nurhaliza\' (Lorenz Adam) masuk tahap Formulir dan dilimpahkan ke CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Lorenz Adam\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('c992ce07-8d2a-4854-97c0-c237ccdcc9ad', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Dewi Lestari\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('ca3ca1fa-a4d4-4bc6-951a-a234a1ed8119', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udc64 Pengalihan Prospek\",\"message\":\"Prospek \'Budi Hendrawan\' telah dialihkan kepada Anda oleh Sistem.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_reassigned\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('ca884534-2716-4c98-94fa-c9eca8c76721', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Rina Kartika\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('cb73f22e-00fe-4c31-9aa5-1fcdc3b25e68', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udc64 Prospek Baru Ditugaskan\",\"message\":\"Sistem menugaskan prospek baru kepada Anda: Rina Kartika (SMA Negeri 1 Sumber).\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('cdf13f65-a582-43ab-916a-d20486644173', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Budi Hendrawan\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:05', '2026-09-29 05:39:35'),
('cf6a9b2f-a861-44d4-a517-76822cb4f4aa', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) diterima untuk \'Eko Prasetyo\' (Sales: Sales Utama CIC).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('d1a18529-05eb-4173-ae9f-0c1306eeaad8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Eko Prasetyo\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('d473b84a-61ae-4d30-8140-5202f6899faf', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Rina Kartika\' (Sales: Lorenz Adam).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('d4a54f47-9141-4c0b-8710-6f97376f8663', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udc64 Prospek Baru Ditugaskan\",\"message\":\"Sistem menugaskan prospek baru kepada Anda: Eko Prasetyo (MAN 1 Kota Cirebon).\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:05', '2026-09-29 05:39:35'),
('d4bc4406-7604-479e-8b7b-5d57ce61f460', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 3.500.000 (Pembayaran Termin 1) tercatat untuk prospek \'Eko Prasetyo\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:06', '2026-09-29 05:39:35'),
('d4f11861-e117-49fb-a3a4-42c57f7ee79a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Fajar Nugraha\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('d546b83a-89ac-4eed-8619-cbb8111ce007', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Aulia Putri\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('d6819182-1b66-4684-bf25-0d4264254d1e', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Eko Prasetyo\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', '2026-09-29 05:39:35', '2026-09-25 19:34:09', '2026-09-29 05:39:35'),
('d6c86cf0-7ac1-4dda-a610-9f834239b812', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Budi Hendrawan\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('d6e5b8b8-402c-4d20-8e79-24df39d0c882', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udc64 Prospek Baru Ditugaskan\",\"message\":\"Sistem menugaskan prospek baru kepada Anda: Andi Wijaya (SMA Negeri 6 Cirebon).\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('d799f40f-2e8c-4543-ade7-d80bdc485c29', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Siti Nurhaliza\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('d7cda6c4-0e96-4339-aca4-1f827f23c7bd', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Rina Kartika\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('d7d43e0f-42cb-4b56-9cf3-0536eb6ebbe8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Rian Hidayat\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:05', '2026-09-29 05:39:35'),
('d7edeb05-c2f1-4fb4-bcfd-fe7397c8e1ba', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Prospek Baru (CS)\",\"message\":\"Sistem menugaskan prospek baru: Budi Hendrawan.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned_cs\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('d89e3b40-16dc-47c7-8eac-28e9a752ddb9', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Dewi Lestari\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('d9b3175b-7d02-49da-939f-ea7fd6249267', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 9, '{\"title\":\"\\ud83c\\udf89 Closing PMB Baru!\",\"message\":\"CS Yuda Thomas (CS) berhasil closing calon mahasiswa \'SMA 1 BREBES\' (LUNAS).\",\"type\":\"success\",\"link\":\"\\/hm\\/pipeline\",\"icon\":\"\\ud83c\\udf93\",\"sender_name\":\"CS Yuda Thomas (CS)\",\"sender_role\":\"CS\",\"action\":\"closing_lunas_hm\"}', NULL, '2026-09-30 06:25:52', '2026-09-30 06:25:52'),
('dc8eb1bc-5928-49e1-9ce0-49d21605f1c7', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'SMA 1 BREBES\' (Sales Yuda Thomas) masuk tahap Formulir dan dilimpahkan ke CS CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Sales Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-30 05:37:04', '2026-09-30 05:37:04'),
('dd1b4f65-348d-40b2-bd26-dc1855b1e6e1', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) diterima untuk \'Eko Prasetyo\' (Sales: Sales CIC).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('dd468323-6867-4b80-97e5-4132475581af', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 9, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Dewi Lestari\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09');
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('de8b8aa7-ff14-4120-b88e-6294bbae7867', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'Aulia Putri\' (Lorenz Adam) masuk tahap Formulir dan dilimpahkan ke CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Lorenz Adam\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('dec33bd7-fe73-46be-ad5b-b454bb3651cc', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) tervalidasi untuk prospek \'Eko Prasetyo\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('df49c350-b6fe-45c8-be07-13873f8b8d26', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcc8 Prospek Masuk Tahap CLOSING\",\"message\":\"Sales Yuda Thomas (Sales) memperbarui status \'SMA 1 BREBES\' ke tahap CLOSING.\",\"type\":\"info\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcc8\",\"sender_name\":\"Sales Yuda Thomas (Sales)\",\"sender_role\":\"Sales\",\"action\":\"prospek_status_update\"}', NULL, '2026-09-30 05:37:52', '2026-09-30 05:37:52'),
('dff47064-5d52-41be-b934-80070edfcbec', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'Rina Kartika\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('e11b5a65-bb7c-4ec3-b54a-af1498e31ffd', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 11, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Meeting) pada prospek \'Aulia Putri\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('e210bdf1-3763-4aff-be6c-21ed48076232', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk Tim\",\"message\":\"Pembayaran Rp 5.000.000 (Pembayaran Termin 1) diterima untuk \'SMA 1 BREBES\' (Sales: Sales Yuda Thomas).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":\"Sales Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"transaksi_created_spv\"}', NULL, '2026-09-30 05:59:29', '2026-09-30 05:59:29'),
('e31bece8-b9f3-4954-8ec2-6eb355ca2edf', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 7, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Rina Kartika\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('e34b61e8-fc28-499e-b81c-d6a1ca4d80c8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) tervalidasi untuk prospek \'Budi Hendrawan\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:33:06', '2026-09-25 19:33:06'),
('e3839617-613a-467a-8193-b59c3e7c1ce7', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 3.500.000 (Pembayaran Termin 1) tervalidasi untuk prospek \'Budi Hendrawan\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('e3fc0680-5a51-4edb-b30f-2d9c05c8d1e9', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 3.500.000 (Pembayaran Termin 1) tercatat untuk prospek \'Budi Hendrawan\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('e52daa81-beca-42b2-9094-e204c3c8a438', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Budi Hendrawan\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-30 05:31:53', '2026-09-30 05:31:53'),
('e56b1060-b981-4a26-9d31-f4cc4a382ff0', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udccb Prospek Baru (CS)\",\"message\":\"Sistem menugaskan prospek baru: Rina Kartika.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"prospek_assigned_cs\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('e570980b-8fd0-4367-84df-3188185fa310', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83d\\udc64 Prospek Baru dari Sales\",\"message\":\"Sales Sales Yuda Thomas menambahkan prospek baru: SMA 1 BREBES (SMA 1 BREBES).\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\ud83d\\udc64\",\"sender_name\":\"Sales Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"prospek_created_sales\"}', NULL, '2026-09-30 05:34:45', '2026-09-30 05:34:45'),
('e75e7a20-94e3-4f09-92fb-119dec628c00', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\ud83c\\udf89 Closing Berhasil!\",\"message\":\"Selamat! Prospek \'SMA 1 BREBES\' berhasil LUNAS melalui CS Yuda Thomas (CS).\",\"type\":\"success\",\"link\":\"\\/spv\\/pipeline\",\"icon\":\"\\ud83c\\udf93\",\"sender_name\":\"CS Yuda Thomas (CS)\",\"sender_role\":\"CS\",\"action\":\"closing_lunas\"}', NULL, '2026-09-30 06:25:52', '2026-09-30 06:25:52'),
('eaf9b7cc-b641-4c4b-b69a-e8031795062c', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Budi Hendrawan\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 15:31:32', '2026-09-29 15:31:16', '2026-09-29 15:31:32'),
('eb289c6a-158f-4df0-a914-255af17522a5', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Eko Prasetyo\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:06', '2026-09-29 05:39:35'),
('eca880bc-9318-41cd-99d8-7c6e50ca939c', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Siti Nurhaliza\'.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('eca9df8a-fc14-43fe-a402-5fcb73a8783a', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Email) pada prospek \'SMA 1 BREBES\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('ee1f9fef-bfe2-4511-9447-c10b726f6009', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Siswa CS\",\"message\":\"Pembayaran Rp 250.000 (Beli Formulir) tervalidasi untuk prospek \'Siti Nurhaliza\'.\",\"type\":\"success\",\"link\":\"http:\\/\\/localhost:8000\\/cs\\/verifikasi\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_cs\"}', NULL, '2026-09-30 05:31:54', '2026-09-30 05:31:54'),
('ef41797d-794f-4fd4-a233-8bfd68fa3252', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 10, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'PT Cirebon Power Development\' (Lorenz Adam) masuk tahap Formulir dan dilimpahkan ke CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Lorenz Adam\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('f2e82f2a-af94-4605-872e-a4b134b5694e', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udccb Pelimpahan Prospek (Handover CS)\",\"message\":\"Prospek \'Siti Nurhaliza\' telah dilimpahkan ke Anda untuk tahapan formulir\\/pendaftaran.\",\"type\":\"warning\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udccb\",\"sender_name\":null,\"sender_role\":null,\"action\":\"cs_handover\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('f2fcb4e7-5abc-48e0-a7b1-e6b33cb61341', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Fajar Nugraha\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('f37e356f-c976-4480-a15e-6cc2865e3c2c', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Eko Prasetyo\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-29 16:08:47', '2026-09-29 16:08:47'),
('f45777e1-7bd4-453e-a8c6-e6e699ed65b0', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 3, '{\"title\":\"\\ud83c\\udf89 Closing PMB Baru!\",\"message\":\"CS Yuda Thomas (CS) berhasil closing calon mahasiswa \'SMA 1 BREBES\' (LUNAS).\",\"type\":\"success\",\"link\":\"\\/hm\\/pipeline\",\"icon\":\"\\ud83c\\udf93\",\"sender_name\":\"CS Yuda Thomas (CS)\",\"sender_role\":\"CS\",\"action\":\"closing_lunas_hm\"}', NULL, '2026-09-30 06:25:52', '2026-09-30 06:25:52'),
('f5a15380-4982-4e31-bf4e-5aa12fe9e2be', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Andi Wijaya\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('f6fef77a-8084-4a8e-a3f6-2a941d8c1af8', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 4, '{\"title\":\"\\u2728 Handover Prospek ke CS\",\"message\":\"Prospek \'Budi Hendrawan\' (Yuda Thomas) masuk tahap Formulir dan dilimpahkan ke CS Yuda Thomas.\",\"type\":\"info\",\"link\":\"\\/spv\\/prospek\",\"icon\":\"\\u2728\",\"sender_name\":\"Yuda Thomas\",\"sender_role\":\"Sales\",\"action\":\"cs_handover_spv\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('f7993968-ea94-46a7-b463-51ff2dd0d486', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 8, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 250.000 (Beli Formulir) tercatat untuk prospek \'Andi Wijaya\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', NULL, '2026-09-25 19:34:09', '2026-09-25 19:34:09'),
('f9087e15-b8b6-4ef4-ab98-0baeb01f1051', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 9, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (Telepon) pada prospek \'Dewi Lestari\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-25 19:33:05', '2026-09-25 19:33:05'),
('f97e7c39-3b16-4aef-82e3-603a05a45bd0', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 5, '{\"title\":\"\\ud83d\\udcac Aktivitas Follow-Up Baru\",\"message\":\"Rekan Tim mencatat follow-up (WhatsApp) pada prospek \'Rian Hidayat\'.\",\"type\":\"info\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcac\",\"sender_name\":null,\"sender_role\":null,\"action\":\"follow_up_created\"}', NULL, '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
('f9fa1994-3599-46dd-8044-f23231eea34f', 'App\\Notifications\\CrmActivityNotification', 'App\\Models\\User', 6, '{\"title\":\"\\ud83d\\udcb3 Pembayaran Masuk\",\"message\":\"Pembayaran sebesar Rp 3.500.000 (Pembayaran Termin 1) tercatat untuk prospek \'Budi Hendrawan\' oleh Sistem.\",\"type\":\"success\",\"link\":\"\\/prospek\",\"icon\":\"\\ud83d\\udcb3\",\"sender_name\":null,\"sender_role\":null,\"action\":\"transaksi_created_sales\"}', '2026-09-29 05:39:35', '2026-09-25 19:33:06', '2026-09-29 05:39:35');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint UNSIGNED NOT NULL,
  `invoice_id` bigint UNSIGNED NOT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_gateway` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simulation',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'qris',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `perusahaans`
--

CREATE TABLE `perusahaans` (
  `id` bigint UNSIGNED NOT NULL,
  `kode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_id` bigint UNSIGNED DEFAULT NULL,
  `wilayah_id` bigint UNSIGNED DEFAULT NULL,
  `kecamatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `telepon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_jabatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `sales_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `perusahaans`
--

INSERT INTO `perusahaans` (`id`, `kode`, `nama`, `kategori_id`, `wilayah_id`, `kecamatan`, `alamat`, `telepon`, `email`, `website`, `pic_name`, `pic_jabatan`, `pic_phone`, `status`, `sales_id`, `created_at`, `updated_at`, `lat`, `lng`) VALUES
(1, 'PRU-001', 'PT Cirebon Electric Power', NULL, NULL, 'Astanajapura', 'Jl. Raya Kanci, Cirebon', '0231-321001', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL),
(2, 'PRU-002', 'PT Bank Mandiri Cabang Cirebon', NULL, NULL, 'Kejaksan', 'Jl. Siliwangi No. 123, Cirebon', '0231-234567', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL),
(3, 'PRU-003', 'PT Indocement Tunggal Prakarsa', NULL, NULL, 'Palimanan', 'Jl. Cirebon–Bandung Km 14, Palimanan', '0231-341000', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL),
(4, 'PRU-004', 'PT BRI Cabang Cirebon', NULL, NULL, 'Kesambi', 'Jl. Pekiringan No. 6, Cirebon', '0231-202222', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL),
(5, 'PRU-005', 'PT Telkom Indonesia Regional Cirebon', NULL, NULL, 'Kejaksan', 'Jl. Terusan Pemuda No. 1, Cirebon', '0231-231100', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL),
(6, 'PRU-006', 'PT Krakatau Steel Cirebon', NULL, NULL, 'Astanajapura', 'Kawasan Industri Cirebon', '0231-880001', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL),
(7, 'PRU-007', 'Rumah Sakit Mitra Plumbon', NULL, NULL, 'Plumbon', 'Jl. Raya Plumbon No. 20, Cirebon', '0231-321500', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL),
(8, 'PRU-008', 'PT Tiga Pilar Sejahtera Cirebon', NULL, NULL, 'Harjamukti', 'Jl. Brigjen Darsono No. 12, Cirebon', '0231-488001', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL),
(9, 'PRU-009', 'PT Sumber Alfaria Trijaya (Alfamart) Cirebon', NULL, NULL, 'Kedawung', 'Jl. Tuparev No. 12, Cirebon', '0231-484000', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL),
(10, 'PRU-010', 'Pemerintah Kota Cirebon - Dinas Pendidikan', NULL, NULL, 'Harjamukti', 'Jl. Brigjend Darsono No. 2, Cirebon', '0231-235501', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prodis`
--

CREATE TABLE `prodis` (
  `id` bigint UNSIGNED NOT NULL,
  `kode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenjang` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fakultas` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kuota` int NOT NULL DEFAULT '0',
  `spp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ukt` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ukt_reguler` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prodis`
--

INSERT INTO `prodis` (`id`, `kode`, `nama`, `jenjang`, `fakultas`, `kuota`, `spp`, `ukt`, `ukt_reguler`, `status`, `created_at`, `updated_at`) VALUES
(1, 'MNJ', 'Manajemen', 'S1', 'Fakultas Ekonomi dan Bisnis', 210, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(2, 'TI', 'Teknik Informatika', 'S1', 'Fakultas Teknologi Informasi', 180, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(3, 'DKV', 'DKV', 'S1', 'Fakultas Teknologi Informasi', 130, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(4, 'BD', 'Bisnis Digital (Baru)', 'S1', 'Fakultas Ekonomi dan Bisnis', 160, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(5, 'AKT', 'Akuntansi', 'S1', 'Fakultas Ekonomi dan Bisnis', 105, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(6, 'SI', 'Sistem Informasi', 'S1', 'Fakultas Teknologi Informasi', 85, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(7, 'PKOR', 'PKOR (Baru)', 'S1', 'Fakultas Ilmu Kesehatan & Olahraga', 50, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(8, 'PMAT', 'Pendidikan Matematika (Baru)', 'S1', 'Fakultas Keguruan & Ilmu Pendidikan', 50, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(9, 'MB-D3', 'Manajemen Bisnis (D3)', 'D3', 'Fakultas Ekonomi dan Bisnis', 25, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(10, 'MI-D3', 'Manajemen Informatika (D3)', 'D3', 'Fakultas Teknologi Informasi', 20, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04'),
(11, 'S2-MNJ', 'S2 Manajemen (Tanpa Tesis)', 'S2', 'Pascasarjana', 50, NULL, NULL, NULL, 'Aktif', '2026-09-25 19:33:04', '2026-09-25 19:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `prospeks`
--

CREATE TABLE `prospeks` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('Sekolah','Corporate','Individu') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun_akademik` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2027/2028',
  `needs_visit_report` tinyint(1) NOT NULL DEFAULT '0',
  `stage_number` int NOT NULL DEFAULT '1',
  `active_follow_up_count` int NOT NULL DEFAULT '0',
  `follow_up_count` int NOT NULL DEFAULT '0',
  `potential` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kelas` enum('Reguler','Karyawan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Reguler',
  `ai_training` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lost_reason` enum('Tidak tertarik','Tidak dapat dihubungi','Membatalkan','Memilih kampus lain','Lainnya') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lost_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `wilayah_id` bigint UNSIGNED DEFAULT NULL,
  `sales_id` bigint UNSIGNED DEFAULT NULL,
  `cs_id` bigint UNSIGNED DEFAULT NULL,
  `handover_at` datetime DEFAULT NULL,
  `owner_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sekolah_id` bigint UNSIGNED DEFAULT NULL,
  `perusahaan_id` bigint UNSIGNED DEFAULT NULL,
  `academic_year_id` bigint UNSIGNED DEFAULT NULL,
  `prodi_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prospeks`
--

INSERT INTO `prospeks` (`id`, `name`, `type`, `category`, `pic`, `pic_phone`, `whatsapp`, `source`, `status`, `tahun_akademik`, `needs_visit_report`, `stage_number`, `active_follow_up_count`, `follow_up_count`, `potential`, `kelas`, `ai_training`, `notes`, `lost_reason`, `lost_note`, `wilayah_id`, `sales_id`, `cs_id`, `handover_at`, `owner_id`, `created_at`, `updated_at`, `sekolah_id`, `perusahaan_id`, `academic_year_id`, `prodi_id`) VALUES
(1, 'Budi Hendrawan', 'Individu', 'B2C', 'Budi Hendrawan', '081234567801', '081234567801', 'Kunjungan Sekolah', 'BERKAS', '2027/2028', 0, 6, 2, 2, 'Tinggi', 'Reguler', NULL, 'Telah membayar lunas biaya registrasi dan UKT semester 1 Informatika.', NULL, NULL, 1, 5, 6, '2026-09-26 02:33:04', 1, '2026-09-25 19:33:04', '2026-09-30 06:06:26', 1, NULL, 3, 1),
(2, 'Siti Nurhaliza', 'Individu', 'B2C', 'Siti Nurhaliza', '081234567802', '081234567802', 'Website PMB', 'BERKAS', '2027/2028', 0, 1, 1, 4, 'Tinggi', 'Reguler', NULL, 'Sudah beli formulir & melengkapi ijazah/SKL.', NULL, NULL, 2, 11, 6, '2026-09-26 02:33:05', 1, '2026-09-25 19:33:05', '2026-09-30 05:31:51', 2, NULL, 3, 2),
(3, 'Andi Wijaya', 'Individu', 'B2C', 'Andi Wijaya', '081234567803', '081234567803', 'Edufair Kampus', 'FORMULIR', '2027/2028', 0, 1, 3, 2, 'Sedang', 'Reguler', NULL, 'Membeli voucher formulir PMB program Sistem Informasi.', NULL, NULL, 3, 5, 6, '2026-09-26 02:33:05', 1, '2026-09-25 19:33:05', '2026-09-30 05:31:51', 3, NULL, 3, 3),
(4, 'Dewi Lestari', 'Individu', 'B2C', 'Dewi Lestari', '081234567804', '081234567804', 'Sosial Media / IG', 'PRESENTATION', '2027/2028', 0, 1, 3, 1, 'Tinggi', 'Reguler', NULL, 'Tertarik program Beasiswa AI & Cyber Security.', NULL, NULL, 4, 11, 6, NULL, 1, '2026-09-25 19:33:05', '2026-09-30 05:31:51', 4, NULL, 3, 4),
(5, 'Rian Hidayat', 'Sekolah', 'B2C', 'Rian Hidayat', '081234567805', '081234567805', 'Kunjungan Sekolah', 'PROSPECT', '2027/2028', 0, 1, 3, 4, 'Sedang', 'Reguler', NULL, 'Prospek hangat dari presentasi kelas XII IPA SMAN 1 Cirebon.', NULL, NULL, 5, 5, 6, NULL, 1, '2026-09-25 19:33:05', '2026-09-30 05:31:51', 5, NULL, 3, 5),
(6, 'PT Cirebon Power Development', 'Corporate', 'B2B', 'Bpk. Ir. Rahmat Hidayat (HRD Manager)', '081234567806', '081234567806', 'Kerjasama Corporate', 'PROSPECT', '2027/2028', 0, 1, 3, 5, 'Sangat Tinggi', 'Karyawan', NULL, 'Prospek program kelas karyawan & upskilling AI karyawan PT Cirebon Power.', NULL, NULL, 6, 11, 6, NULL, 1, '2026-09-25 19:33:05', '2026-09-30 05:31:51', NULL, 6, 3, 6),
(7, 'Fajar Nugraha', 'Individu', 'B2C', 'Fajar Nugraha', '081234567807', '081234567807', 'WhatsApp Inbound', 'DISTRIBUTED', '2027/2028', 0, 1, 1, 5, 'Sedang', 'Reguler', NULL, 'Telah didistribusikan ke sales area Cirebon Kota.', NULL, NULL, 7, 5, 6, NULL, 1, '2026-09-25 19:33:05', '2026-09-30 05:31:51', 7, NULL, 3, 7),
(8, 'Aulia Putri', 'Individu', 'B2C', 'Aulia Putri', '081234567808', '081234567808', 'Kunjungan Sekolah', 'DINGIN', '2027/2028', 0, 1, 1, 3, 'Rendah', 'Reguler', NULL, NULL, 'Memilih kampus lain', 'Diterima di PTN Jalur SNBP.', 8, 11, 6, NULL, 1, '2026-09-25 19:33:05', '2026-09-30 05:31:51', 8, NULL, 3, 8),
(9, 'Eko Prasetyo', 'Individu', 'B2C', 'Eko Prasetyo', '081234567809', '081234567809', 'Rekomendasi Alumni', 'LUNAS', '2027/2028', 0, 1, 2, 1, 'Tinggi', 'Reguler', NULL, 'Mendaftar Prodi Desain Komunikasi Visual (DKV).', NULL, NULL, 9, 5, 6, '2026-09-26 02:33:05', 1, '2026-09-25 19:33:05', '2026-09-30 05:31:51', 9, NULL, 3, 9),
(10, 'Rina Kartika', 'Individu', 'B2C', 'Rina Kartika', '081234567810', '081234567810', 'Website PMB', 'FORMULIR', '2027/2028', 0, 1, 2, 4, 'Tinggi', 'Reguler', NULL, 'Membeli formulir jalur reguler prodi Manajemen.', NULL, NULL, 10, 11, 6, '2026-09-26 02:33:05', 1, '2026-09-25 19:33:05', '2026-09-30 05:31:51', 10, NULL, 3, 10),
(11, 'SMA 1 BREBES', 'Sekolah', 'Sangat Berpeluang', 'marvel', NULL, '0882000134734', 'Acara Kampus', 'CLOSING', '2027/2028', 0, 7, 0, 0, NULL, 'Reguler', NULL, NULL, NULL, NULL, 2, 6, 5, '2026-09-29 22:29:37', 6, '2026-09-29 06:57:32', '2026-09-29 15:30:19', 15, NULL, 3, NULL),
(12, 'SMA 1 BREBES', 'Sekolah', 'Masih Ragu', 'adinda', NULL, '0895398505207', 'MGBK/Miniclass', 'LUNAS', '2027/2028', 0, 7, 0, 0, NULL, 'Reguler', NULL, NULL, NULL, NULL, 2, 5, 6, '2026-09-30 12:37:04', 5, '2026-09-30 05:34:45', '2026-09-30 06:25:52', 15, NULL, 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prospek_timelines`
--

CREATE TABLE `prospek_timelines` (
  `id` bigint UNSIGNED NOT NULL,
  `prospek_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status_before` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_after` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prospek_timelines`
--

INSERT INTO `prospek_timelines` (`id`, `prospek_id`, `user_id`, `title`, `notes`, `status_before`, `status_after`, `time`, `created_at`, `updated_at`) VALUES
(1, 11, 6, 'Prospek Dibuat', 'Prospek baru ditambahkan oleh Sales Utama CIC (Sales)', NULL, 'BARU', '2026-09-29 13:57:34', '2026-09-29 06:57:34', '2026-09-29 06:57:34'),
(2, 11, 5, 'Handover', 'Prospek dialihkan ke CS: Dina Marlina (CS)', NULL, NULL, '2026-09-29 22:29:37', '2026-09-29 15:29:37', '2026-09-29 15:29:37'),
(3, 11, 6, 'Follow Up & Update Status', 'Status diubah dari BARU menjadi FORMULIR', 'BARU', 'FORMULIR', '2026-09-29 22:29:38', '2026-09-29 15:29:38', '2026-09-29 15:29:38'),
(4, 11, 6, 'Follow Up & Update Status', 'Status diubah dari FORMULIR menjadi CLOSING', 'FORMULIR', 'CLOSING', '2026-09-29 22:30:19', '2026-09-29 15:30:19', '2026-09-29 15:30:19'),
(5, 1, 6, 'Handover', 'Prospek dialihkan ke CS: Yuda Thomas', NULL, NULL, '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(6, 2, 6, 'Handover', 'Prospek dialihkan ke CS: Yuda Thomas', NULL, NULL, '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(7, 3, 6, 'Handover', 'Prospek dialihkan ke CS: Yuda Thomas', NULL, NULL, '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(8, 4, 6, 'Handover', 'Prospek dialihkan ke CS: Yuda Thomas', NULL, NULL, '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(9, 5, 6, 'Handover', 'Prospek dialihkan ke CS: Yuda Thomas', NULL, NULL, '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(10, 6, 6, 'Handover', 'Prospek dialihkan ke CS: Yuda Thomas', NULL, NULL, '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(11, 7, 6, 'Handover', 'Prospek dialihkan ke CS: Yuda Thomas', NULL, NULL, '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(12, 8, 6, 'Handover', 'Prospek dialihkan ke CS: Yuda Thomas', NULL, NULL, '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(13, 9, 6, 'Handover', 'Prospek dialihkan ke CS: Yuda Thomas', NULL, NULL, '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(14, 10, 6, 'Handover', 'Prospek dialihkan ke CS: Yuda Thomas', NULL, NULL, '2026-09-29 23:08:46', '2026-09-29 16:08:46', '2026-09-29 16:08:46'),
(15, 12, 5, 'Prospek Dibuat', 'Prospek baru ditambahkan oleh Sales Yuda Thomas (Sales)', NULL, 'BARU', '2026-09-30 12:34:45', '2026-09-30 05:34:45', '2026-09-30 05:34:45'),
(16, 12, 6, 'Handover', 'Prospek dialihkan ke CS: CS Yuda Thomas', NULL, NULL, '2026-09-30 12:37:04', '2026-09-30 05:37:04', '2026-09-30 05:37:04'),
(17, 12, 5, 'Follow Up & Update Status', 'Status diubah dari BARU menjadi FORMULIR', 'BARU', 'FORMULIR', '2026-09-30 12:37:04', '2026-09-30 05:37:04', '2026-09-30 05:37:04'),
(18, 12, 5, 'Follow Up & Update Status', 'Status diubah dari FORMULIR menjadi CLOSING', 'FORMULIR', 'CLOSING', '2026-09-30 12:37:52', '2026-09-30 05:37:52', '2026-09-30 05:37:52'),
(19, 12, 5, 'Input Transaksi: Beli Formulir', 'Nominal: Rp 200.000.000 | Catatan: p', 'CLOSING', 'CLOSING', '2026-09-30 12:44:26', '2026-09-30 05:44:26', '2026-09-30 05:44:26'),
(20, 12, 5, 'Input Transaksi: Pembayaran Termin 1', 'Nominal: Rp 5.000.000 | Metode: GoPay | Status: Menunggu Verifikasi CS | p', 'CLOSING', 'CLOSING', '2026-09-30 12:59:29', '2026-09-30 05:59:29', '2026-09-30 05:59:29'),
(21, 1, 5, 'Input Transaksi: Pembayaran Termin 1', 'Nominal: Rp 250.000 | Metode: Transfer Bank | Status: Menunggu Verifikasi CS | Transfer Bank: Bank Mandiri - No. 1380010015599 (A/N: Universitas Catur Insan Cendekia)', 'LUNAS', 'BERKAS', '2026-09-30 13:06:26', '2026-09-30 06:06:26', '2026-09-30 06:06:26'),
(22, 12, 6, 'Closing Terverifikasi oleh CS', 'CS CS Yuda Thomas memverifikasi pembayaran. Transaksi Pembayaran Termin 1 dinyatakan sah.', 'CLOSING', 'LUNAS', '2026-09-30 13:25:52', '2026-09-30 06:25:52', '2026-09-30 06:25:52');

-- --------------------------------------------------------

--
-- Table structure for table `sekolahs`
--

CREATE TABLE `sekolahs` (
  `id` bigint UNSIGNED NOT NULL,
  `kode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tier` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'B',
  `kategori_id` bigint UNSIGNED DEFAULT NULL,
  `wilayah_id` bigint UNSIGNED DEFAULT NULL,
  `kecamatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `telepon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_jabatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `sales_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sekolahs`
--

INSERT INTO `sekolahs` (`id`, `kode`, `nama`, `tier`, `kategori_id`, `wilayah_id`, `kecamatan`, `alamat`, `telepon`, `email`, `website`, `pic_name`, `pic_jabatan`, `pic_phone`, `status`, `sales_id`, `created_at`, `updated_at`, `lat`, `lng`) VALUES
(1, 'SKL-001', 'SMA Negeri 1 Cirebon', 'A', 33, 2, 'Kejaksan', 'Jl. Wahidin No. 81, Sukapura', '0231-203541', 'info@sman1cirebon.sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456001', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(2, 'SKL-007', 'SMK Informatika Al-Irsyad', 'A', 34, 2, 'Kejaksan', 'Jl. Panjunan No. 54', '0231-208765', 'smk@alirsyad-crb.sch.id', NULL, 'Faisal Basri, M.Kom', 'Kaprog RPL', '08123456007', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(3, 'SKL-011', 'SMA Negeri 6 Cirebon', 'B', 33, 2, 'Kejaksan', 'Jl. Wahidin No. 79', '0231-203542', 'info@sman6cirebon.sch.id', NULL, 'Drs. H. Sutisna', 'Guru BK', '08123456011', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(4, 'SKL-002', 'SMA Negeri 2 Cirebon', 'A', 33, 3, 'Kesambi', 'Jl. Dr. Cipto Mangunkusumo No. 1', '0231-204120', 'info@sman2cirebon.sch.id', NULL, 'Hj. Nurlaila, M.Pd', 'Guru BK', '08123456002', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(5, 'SKL-003', 'SMA Negeri 3 Cirebon', 'B', 33, 3, 'Kesambi', 'Jl. Nyi Ageng Serang No. 33', '0231-207889', 'info@sman3cirebon.sch.id', NULL, 'Bambang Irawan, S.Pd', 'Kesiswaan', '08123456003', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(6, 'SKL-005', 'SMK Negeri 1 Cirebon', 'A', 34, 3, 'Kesambi', 'Jl. Perjuangan No. 10', '0231-200987', 'smkn1crb@sch.id', NULL, 'Dr. H. Ahmad Santoso', 'Waka Hubinmas', '08123456005', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(7, 'SKL-006', 'SMK Negeri 2 Cirebon', 'B', 34, 3, 'Kesambi', 'Jl. Dr. Cipto Mangunkusumo No. 20', '0231-206543', 'smkn2cirebon@sch.id', NULL, 'Indra Gunawan, S.T', 'BKK / Hubinmas', '08123456006', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(8, 'SKL-004', 'SMA Santa Maria Cirebon', 'A', 33, 4, 'Pekalipan', 'Jl. Sisingamangaraja No. 22', '0231-202311', 'info@santamaria-crb.sch.id', NULL, 'Theresia Endang, S.Pd', 'Koordinator BK', '08123456004', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(9, 'SKL-008', 'MAN 1 Kota Cirebon', 'B', 35, 6, 'Harjamukti', 'Jl. Pilang Raya No. 4', '0231-201234', 'man1cirebon@kemenag.go.id', NULL, 'Dra. Hj. Siti Rohmah', 'Guru BK', '08123456008', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(10, 'SKL-009', 'SMA Negeri 1 Sumber', 'A', 33, 10, 'Sumber', 'Jl. Raden Dewi Sartika No. 102', '0231-321123', 'info@sman1sumber.sch.id', NULL, 'Drs. H. Suharjo', 'Kepala Sekolah', '08123456009', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(11, 'SKL-010', 'SMK Negeri 1 Kedawung', 'A', 34, 8, 'Kedawung', 'Jl. Tuparev No. 12', '0231-209988', 'smkn1kedawung@sch.id', NULL, 'Eko Prasetyo, S.Pd', 'Hubinmas', '08123456010', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(12, 'SKL-018', 'SMA Negeri 1 Indramayu', 'A', 33, 17, 'Indramayu', 'Jl. Soekarno-Hatta No. 2', '0234-271234', 'info@sman1indramayu.sch.id', NULL, 'Drs. Agus Wijaya', 'Kepala Sekolah', '08123456018', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(13, 'SKL-020', 'SMA Negeri 1 Majalengka', 'A', 33, 22, 'Majalengka', 'Jl. KH Abdul Halim No. 113', '0233-281001', 'info@sman1majalengka.sch.id', NULL, 'H. Endang Rahmat, M.Pd', 'Kepala Sekolah', '08123456020', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(14, 'SKL-021', 'SMA Negeri 1 Kuningan', 'A', 33, 27, 'Kuningan', 'Jl. Siliwangi No. 55', '0232-871022', 'info@sman1kuningan.sch.id', NULL, 'Drs. H. Tri Suknaedi', 'Kepala Sekolah', '08123456021', 'Aktif', NULL, '2026-09-25 19:33:04', '2026-09-25 19:33:04', NULL, NULL),
(15, 'SCH-R3IICQ', 'SMA 1 BREBES', 'B', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-29 06:57:32', '2026-09-29 06:57:32', NULL, NULL),
(16, 'SKL-022', 'SMK Veteran Cirebon', 'B', 34, 4, 'Pekalipan', 'Jl. Kanggraksan No. 45', '0231-203344', 'smkveteran@sch.id', NULL, 'Drs. H. Maman', 'Guru BK', '08123456022', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(17, 'SKL-023', 'SMA Negeri 7 Cirebon', 'B', 33, 5, 'Lemahwungkuk', 'Jl. Lemahwungkuk No. 12', '0231-208877', 'sman7cirebon@sch.id', NULL, 'Drs. Hendra Setiawan', 'Kepala Sekolah', '08123456023', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(18, 'SKL-024', 'SMK Pelayaran Bahari Cirebon', 'A', 34, 5, 'Lemahwungkuk', 'Jl. Samadikun No. 8', '0231-209911', 'smkbahari@sch.id', NULL, 'Kapten Suryono', 'Hubinmas', '08123456024', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(19, 'SKL-025', 'SMA Negeri 8 Cirebon', 'B', 33, 6, 'Harjamukti', 'Jl. Ahmad Yani No. 15', '0231-201999', 'sman8cirebon@sch.id', NULL, 'H. Dedi Supriyadi, M.Pd', 'Kepala Sekolah', '08123456025', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(20, 'SKL-026', 'SMK Negeri 3 Cirebon', 'A', 34, 6, 'Harjamukti', 'Jl. Ciremai Raya No. 1', '0231-207766', 'smkn3cirebon@sch.id', NULL, 'Drs. Asep Saepudin', 'BKK', '08123456026', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(21, 'SKL-027', 'SMK Muhammadiyah Sumber', 'B', 34, 10, 'Sumber', 'Jl. Pangeran Kejaksan No. 5', '0231-321555', 'smkmuh_sumber@sch.id', NULL, 'Agus Budiman, S.T', 'Hubinmas', '08123456027', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(22, 'SKL-028', 'SMA Islam Cirebon', 'B', 33, 8, 'Kedawung', 'Jl. Ir. H. Juanda No. 8', '0231-208833', 'smaislam@sch.id', NULL, 'H. M. Syafei, S.Ag', 'Guru BK', '08123456028', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(23, 'SKL-029', 'SMK Negeri 1 Weru', 'A', 34, 9, 'Weru', 'Jl. Otista No. 66 Weru', '0231-320011', 'smkn1weru@sch.id', NULL, 'Drs. H. Kholid', 'Kepala Sekolah', '08123456029', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(24, 'SKL-030', 'SMA PGRI Weru', 'C', 33, 9, 'Weru', 'Jl. Fatahillah No. 4', '0231-320022', 'smapgriweru@sch.id', NULL, 'Siti Khotimah, S.Pd', 'Guru BK', '08123456030', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(25, 'SKL-031', 'SMA Negeri 1 Plumbon', 'A', 33, 11, 'Plumbon', 'Jl. Raya Plumbon KM 12', '0231-321789', 'sman1plumbon@sch.id', NULL, 'H. Lukman Hakim, M.Pd', 'Kepala Sekolah', '08123456031', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(26, 'SKL-032', 'SMK Bina Warga 1 Plumbon', 'B', 34, 11, 'Plumbon', 'Jl. Lurah Suka No. 1', '0231-321790', 'smkbinawarga@sch.id', NULL, 'Deni Irawan, S.T', 'Hubinmas', '08123456032', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(27, 'SKL-033', 'SMA Negeri 1 Astanajapura', 'B', 33, 12, 'Astanajapura', 'Jl. KH Wahid Hasyim No. 10', '0231-510011', 'sman1asjap@sch.id', NULL, 'Drs. H. Sanusi', 'Guru BK', '08123456033', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(28, 'SKL-034', 'SMA Negeri 1 Arjawinangun', 'A', 33, 13, 'Arjawinangun', 'Jl. Jungjang No. 20', '0231-357111', 'sman1arjawinangun@sch.id', NULL, 'Drs. H. Tarkim', 'Kepala Sekolah', '08123456034', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(29, 'SKL-035', 'SMK Ulil Albab Arjawinangun', 'B', 34, 13, 'Arjawinangun', 'Jl. Kebon Melati No. 5', '0231-357222', 'smkulilalbab@sch.id', NULL, 'M. Fauzi, M.Kom', 'Waka Humas', '08123456035', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(30, 'SKL-036', 'SMA Negeri 1 Ciwaringin', 'B', 33, 14, 'Ciwaringin', 'Jl. Babakan Ciwaringin No. 1', '0231-358001', 'sman1ciwaringin@sch.id', NULL, 'Dra. Hj. Maimunah', 'Guru BK', '08123456036', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(31, 'SKL-037', 'SMA Negeri 1 Babakan', 'A', 33, 15, 'Babakan', 'Jl. Pangeran Sutajaya No. 11', '0231-661122', 'sman1babakan@sch.id', NULL, 'Drs. H. Suwandi', 'Kepala Sekolah', '08123456037', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(32, 'SKL-038', 'SMK Negeri 1 Babakan', 'B', 34, 15, 'Babakan', 'Jl. Babakan Losari KM 2', '0231-661133', 'smkn1babakan@sch.id', NULL, 'Heri Kiswanto, S.T', 'BKK', '08123456038', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(33, 'SKL-039', 'SMA Negeri 2 Indramayu', 'A', 33, 17, 'Indramayu', 'Jl. Pahlawan No. 24', '0234-271255', 'sman2indramayu@sch.id', NULL, 'Drs. H. Masudi', 'Guru BK', '08123456039', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(34, 'SKL-040', 'SMK Negeri 1 Indramayu', 'A', 34, 17, 'Indramayu', 'Jl. Gatot Subroto No. 5', '0234-272001', 'smkn1indramayu@sch.id', NULL, 'Drs. H. Rastita', 'Hubinmas', '08123456040', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(35, 'SKL-041', 'SMA Negeri 1 Karangampel', 'B', 33, 18, 'Karangampel', 'Jl. Dampuawangi No. 1', '0234-351122', 'sman1karangampel@sch.id', NULL, 'H. Kasidin, M.Pd', 'Kepala Sekolah', '08123456041', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(36, 'SKL-042', 'SMK Negeri 1 Karangampel', 'B', 34, 18, 'Karangampel', 'Jl. Raya Karangampel Timur', '0234-351133', 'smkn1karangampel@sch.id', NULL, 'Didi Supriyadi, S.Pd', 'BKK', '08123456042', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(37, 'SKL-043', 'SMA Negeri 1 Jatibarang', 'A', 33, 19, 'Jatibarang', 'Jl. Mayor Dasuki No. 88', '0234-352001', 'sman1jatibarang@sch.id', NULL, 'Drs. H. Asrori', 'Guru BK', '08123456043', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(38, 'SKL-044', 'SMK Negeri 1 Jatibarang', 'A', 34, 19, 'Jatibarang', 'Jl. Raya Bulak No. 12', '0234-352022', 'smkn1jatibarang@sch.id', NULL, 'Ir. Hendra Gunawan', 'Hubinmas', '08123456044', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(39, 'SKL-045', 'SMA Negeri 1 Haurgeulis', 'B', 33, 20, 'Haurgeulis', 'Jl. Jenderal Sudirman No. 10', '0234-712100', 'sman1haurgeulis@sch.id', NULL, 'Drs. H. Mulyadi', 'Kepala Sekolah', '08123456045', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(40, 'SKL-046', 'SMK Negeri 1 Haurgeulis', 'B', 34, 20, 'Haurgeulis', 'Jl. Raya Cipancuh No. 5', '0234-712200', 'smkn1haurgeulis@sch.id', NULL, 'Ade Ruhiyat, S.T', 'BKK', '08123456046', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(41, 'SKL-047', 'SMA Negeri 2 Majalengka', 'A', 33, 22, 'Majalengka', 'Jl. Ahmad Yani No. 2', '0233-281055', 'sman2majalengka@sch.id', NULL, 'Dra. Hj. Yayah', 'Guru BK', '08123456047', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(42, 'SKL-048', 'SMK Negeri 1 Majalengka', 'A', 34, 22, 'Majalengka', 'Jl. Tonjong No. 22', '0233-281300', 'smkn1majalengka@sch.id', NULL, 'Drs. H. Nono', 'Hubinmas', '08123456048', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(43, 'SKL-049', 'SMA Negeri 1 Kadipaten', 'B', 33, 23, 'Kadipaten', 'Jl. Raya Kadipaten KM 1', '0233-661001', 'sman1kadipaten@sch.id', NULL, 'Drs. H. Didi Rohidi', 'Kepala Sekolah', '08123456049', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(44, 'SKL-050', 'SMK Korpri Majalengka', 'B', 34, 23, 'Kadipaten', 'Jl. Pasar Ternak No. 10', '0233-661200', 'smkkorpri@sch.id', NULL, 'Rahman Hakim, S.T', 'BKK', '08123456050', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(45, 'SKL-051', 'SMA Negeri 1 Jatiwangi', 'A', 33, 24, 'Jatiwangi', 'Jl. Raya Jatiwangi - Cirebon', '0233-881122', 'sman1jatiwangi@sch.id', NULL, 'Drs. H. Asep Wahyu', 'Guru BK', '08123456051', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(46, 'SKL-052', 'SMK Negeri 1 Jatiwangi', 'A', 34, 24, 'Jatiwangi', 'Jl. Burujul No. 15', '0233-881233', 'smkn1jatiwangi@sch.id', NULL, 'H. Toto Suwarto, M.Pd', 'Hubinmas', '08123456052', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(47, 'SKL-053', 'SMA Negeri 1 Rajagaluh', 'B', 33, 25, 'Rajagaluh', 'Jl. Mutiara No. 4', '0233-511001', 'sman1rajagaluh@sch.id', NULL, 'Drs. H. Suparman', 'Kepala Sekolah', '08123456053', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(48, 'SKL-054', 'SMA Negeri 2 Kuningan', 'A', 33, 27, 'Kuningan', 'Jl. RE Martadinata No. 20', '0232-871055', 'sman2kuningan@sch.id', NULL, 'Dra. Hj. Aan Suhanah', 'Guru BK', '08123456054', 'Aktif', NULL, '2026-09-29 14:55:07', '2026-09-29 14:55:07', NULL, NULL),
(49, 'SKL-055', 'SMK Negeri 1 Kuningan', 'A', 34, 27, 'Kuningan', 'Jl. Sukamulya No. 7', '0232-871300', 'smkn1kuningan@sch.id', NULL, 'Drs. H. Toto Suharto', 'Hubinmas', '08123456055', 'Aktif', NULL, '2026-09-29 14:55:08', '2026-09-29 14:55:08', NULL, NULL),
(50, 'SKL-056', 'SMA Negeri 1 Cilimus', 'A', 33, 28, 'Cilimus', 'Jl. Raya Cilimus No. 100', '0232-614001', 'sman1cilimus@sch.id', NULL, 'H. Mamat Rohimat, M.Pd', 'Kepala Sekolah', '08123456056', 'Aktif', NULL, '2026-09-29 14:55:08', '2026-09-29 14:55:08', NULL, NULL),
(51, 'SKL-057', 'SMK Negeri 1 Cilimus', 'B', 34, 28, 'Cilimus', 'Jl. Bandorasa No. 15', '0232-614022', 'smkn1cilimus@sch.id', NULL, 'Dra. Hj. Eni', 'BKK', '08123456057', 'Aktif', NULL, '2026-09-29 14:55:08', '2026-09-29 14:55:08', NULL, NULL),
(52, 'SKL-058', 'SMA Negeri 1 Luragung', 'B', 33, 29, 'Luragung', 'Jl. Raya Luragung - Kuningan KM 15', '0232-876111', 'sman1luragung@sch.id', NULL, 'Drs. H. Dadang', 'Guru BK', '08123456058', 'Aktif', NULL, '2026-09-29 14:55:08', '2026-09-29 14:55:08', NULL, NULL),
(53, 'SKL-059', 'SMA Negeri 1 Jalaksana', 'A', 33, 30, 'Jalaksana', 'Jl. Raya Padamenak No. 1', '0232-613001', 'sman1jalaksana@sch.id', NULL, 'Drs. H. Ruspendi', 'Kepala Sekolah', '08123456059', 'Aktif', NULL, '2026-09-29 14:55:08', '2026-09-29 14:55:08', NULL, NULL),
(54, 'SKL-060', 'SMA Negeri 1 Brebes', 'A', 33, 32, 'Brebes', 'Jl. Dr. Setiabudi No. 11', '0283-671001', 'sman1brebes@sch.id', NULL, 'Drs. H. Samsudin', 'Kepala Sekolah', '08123456060', 'Aktif', NULL, '2026-09-29 14:55:08', '2026-09-29 14:55:08', NULL, NULL),
(55, 'SKL-061', 'SMK Negeri 1 Brebes', 'A', 34, 32, 'Brebes', 'Jl. Yos Sudarso No. 8', '0283-671022', 'smkn1brebes@sch.id', NULL, 'Bambang Sugiharto, S.T', 'Hubinmas', '08123456061', 'Aktif', NULL, '2026-09-29 14:55:08', '2026-09-29 14:55:08', NULL, NULL),
(56, 'SKL-CRB-HMJ-01', 'SMA Negeri 8 Cirebon', 'B', 33, 6, 'Harjamukti', 'Jl. Ahmad Yani No. 15, Harjamukti', '0231-201999', 'sman8cirebon@sch.id', NULL, 'H. Dedi Supriyadi, M.Pd', 'Kepala Sekolah', '08123456025', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(57, 'SKL-CRB-HMJ-02', 'SMA Negeri 9 Cirebon', 'B', 33, 6, 'Harjamukti', 'Jl. Rajawali Raya No. 8, Harjamukti', '0231-201888', 'sman9cirebon@sch.id', NULL, 'Dra. Hj. Nunung', 'Guru BK', '08123456027', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(58, 'SKL-CRB-HMJ-03', 'SMK Negeri 3 Cirebon', 'A', 34, 6, 'Harjamukti', 'Jl. Ciremai Raya No. 1, Harjamukti', '0231-207766', 'smkn3cirebon@sch.id', NULL, 'Drs. Asep Saepudin', 'BKK / Hubinmas', '08123456026', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(59, 'SKL-CRB-HMJ-04', 'MAN 1 Kota Cirebon', 'B', 35, 6, 'Harjamukti', 'Jl. Pilang Raya No. 4, Harjamukti', '0231-201234', 'man1cirebon@kemenag.go.id', NULL, 'Dra. Hj. Siti Rohmah', 'Guru BK', '08123456008', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(60, 'SKL-CRB-HMJ-05', 'SMA IT Akmala Sabila', 'A', 33, 6, 'Harjamukti', 'Jl. Rajawali Timur No. 12, Harjamukti', '0231-205566', 'info@akmalasabila.sch.id', NULL, 'Ust. M. Hidayat, Lc', 'Kepala Sekolah', '08123456028', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(61, 'SKL-CRB-KJS-01', 'SMA Negeri 1 Cirebon', 'A', 33, 2, 'Kejaksan', 'Jl. Wahidin No. 81, Sukapura', '0231-203541', 'info@sman1cirebon.sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456001', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(62, 'SKL-CRB-KJS-02', 'SMA Negeri 6 Cirebon', 'B', 33, 2, 'Kejaksan', 'Jl. Wahidin No. 79, Sukapura', '0231-203542', 'info@sman6cirebon.sch.id', NULL, 'Drs. H. Sutisna', 'Guru BK', '08123456011', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(63, 'SKL-CRB-KJS-03', 'SMK Informatika Al-Irsyad', 'A', 34, 2, 'Kejaksan', 'Jl. Panjunan No. 54, Kejaksan', '0231-208765', 'smk@alirsyad-crb.sch.id', NULL, 'Faisal Basri, M.Kom', 'Kaprog RPL', '08123456007', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(64, 'SKL-CRB-KJS-04', 'SMA Santa Maria 1 Cirebon', 'A', 33, 2, 'Kejaksan', 'Jl. Sisingamangaraja No. 22', '0231-202311', 'santamaria1@sch.id', NULL, 'Theresia Endang, S.Pd', 'Koordinator BK', '08123456004', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(65, 'SKL-CRB-KSB-01', 'SMA Negeri 2 Cirebon', 'A', 33, 3, 'Kesambi', 'Jl. Dr. Cipto Mangunkusumo No. 1', '0231-204120', 'info@sman2cirebon.sch.id', NULL, 'Hj. Nurlaila, M.Pd', 'Guru BK', '08123456002', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(66, 'SKL-CRB-KSB-02', 'SMA Negeri 3 Cirebon', 'B', 33, 3, 'Kesambi', 'Jl. Nyi Ageng Serang No. 33', '0231-207889', 'info@sman3cirebon.sch.id', NULL, 'Bambang Irawan, S.Pd', 'Kesiswaan', '08123456003', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(67, 'SKL-CRB-KSB-03', 'SMA Negeri 4 Cirebon', 'B', 33, 3, 'Kesambi', 'Jl. Perjuangan No. 1, Kesambi', '0231-205111', 'sman4cirebon@sch.id', NULL, 'Drs. H. Sukardi', 'Guru BK', '08123456029', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(68, 'SKL-CRB-KSB-04', 'SMA Negeri 5 Cirebon', 'B', 33, 3, 'Kesambi', 'Jl. Dr. Cipto Mangunkusumo No. 18', '0231-205222', 'sman5cirebon@sch.id', NULL, 'Dra. Endah Sulistyo', 'Kepala Sekolah', '08123456030', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(69, 'SKL-CRB-KSB-05', 'SMK Negeri 1 Cirebon', 'A', 34, 3, 'Kesambi', 'Jl. Perjuangan No. 10', '0231-200987', 'smkn1crb@sch.id', NULL, 'Dr. H. Ahmad Santoso', 'Waka Hubinmas', '08123456005', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(70, 'SKL-CRB-KSB-06', 'SMK Negeri 2 Cirebon', 'B', 34, 3, 'Kesambi', 'Jl. Dr. Cipto Mangunkusumo No. 20', '0231-206543', 'smkn2cirebon@sch.id', NULL, 'Indra Gunawan, S.T', 'BKK / Hubinmas', '08123456006', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(71, 'SKL-CRB-KSB-07', 'SMK Farmasi Cirebon', 'A', 34, 3, 'Kesambi', 'Jl. Perjuangan No. 9, Sunyaragi', '0231-206789', 'info@smkfarmasicirebon.sch.id', NULL, 'apt. Fitri Rahmawati, S.Farm', 'Kepala Sekolah', '08123456031', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(72, 'SKL-CRB-LMW-01', 'SMA Negeri 7 Cirebon', 'B', 33, 5, 'Lemahwungkuk', 'Jl. Lemahwungkuk No. 12', '0231-208877', 'sman7cirebon@sch.id', NULL, 'Drs. Hendra Setiawan', 'Kepala Sekolah', '08123456023', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(73, 'SKL-CRB-LMW-02', 'SMK Pelayaran Bahari Cirebon', 'A', 34, 5, 'Lemahwungkuk', 'Jl. Samadikun No. 8', '0231-209911', 'smkbahari@sch.id', NULL, 'Kapten Suryono', 'Hubinmas', '08123456024', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(74, 'SKL-CRB-PLP-01', 'SMA Santa Maria Cirebon', 'A', 33, 4, 'Pekalipan', 'Jl. Sisingamangaraja No. 22', '0231-202311', 'info@santamaria-crb.sch.id', NULL, 'Theresia Endang, S.Pd', 'Koordinator BK', '08123456004', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(75, 'SKL-CRB-PLP-02', 'SMK Veteran Cirebon', 'B', 34, 4, 'Pekalipan', 'Jl. Kanggraksan No. 45', '0231-203344', 'smkveteran@sch.id', NULL, 'Drs. H. Maman', 'Guru BK', '08123456022', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(76, 'SKL-KCRB-ARJ-01', 'SMA Negeri 1 Arjawinangun', 'A', 33, 13, 'Arjawinangun', 'Jl. Nyimas Gandasari No. 1, Arjawinangun', '0231-357111', 'sman1arjawinangun@sch.id', NULL, 'Drs. H. Solihin, M.Pd', 'Kepala Sekolah', '08123456015', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(77, 'SKL-KCRB-ARJ-02', 'SMK Negeri 1 Arjawinangun', 'A', 34, 13, 'Arjawinangun', 'Jl. Ki Hajar Dewantara No. 12, Arjawinangun', '0231-357222', 'smkn1arjawinangun@sch.id', NULL, 'Ir. Budi Santoso', 'Waka Hubinmas', '08123456032', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(78, 'SKL-KCRB-AST-01', 'SMA Negeri 1 Astanajapura', 'A', 33, 12, 'Astanajapura', 'Jl. K.H. Wahid Hasyim, Astanajapura', '0231-510001', 'sman1asjap@sch.id', NULL, 'Drs. H. Taufik Hidayat', 'Kepala Sekolah', '08123456014', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(79, 'SKL-KCRB-AST-02', 'SMK Negeri 1 Astanajapura', 'A', 34, 12, 'Astanajapura', 'Jl. Buntet Pesantren, Astanajapura', '0231-510002', 'smkn1asjap@sch.id', NULL, 'K.H. Ahmad Fauzi, M.Pd', 'Kepala Sekolah', '08123456033', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(80, 'SKL-KCRB-BBK-01', 'SMA Negeri 1 Babakan', 'A', 33, 15, 'Babakan', 'Jl. Pangeran Sutajaya No. 1, Babakan', '0231-661001', 'sman1babakan@sch.id', NULL, 'Drs. H. Ruspendi', 'Kepala Sekolah', '08123456017', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(81, 'SKL-KCRB-BBK-02', 'SMK Negeri 1 Babakan', 'A', 34, 15, 'Babakan', 'Jl. Raya Babakan KM 2, Babakan', '0231-661002', 'smkn1babakan@sch.id', NULL, 'H. Suherman, S.T', 'Hubinmas', '08123456034', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(82, 'SKL-KCRB-BBK-03', 'MAN 4 Cirebon', 'B', 35, 15, 'Babakan', 'Jl. Pangeran Sutajaya No. 25, Babakan', '0231-661003', 'man4cirebon@kemenag.go.id', NULL, 'Dra. Hj. Aminah', 'Guru BK', '08123456035', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(83, 'SKL-KCRB-BBR-01', 'SMA Negeri 1 Beber', 'B', 33, 33, 'Beber', 'Jl. Raya Beber No. 18, Beber', '0231-771001', 'sman1beber@sch.id', NULL, 'Drs. H. Maman', 'Kepala Sekolah', '08123456036', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(84, 'SKL-KCRB-BBR-02', 'SMK Negeri 1 Beber', 'B', 34, 33, 'Beber', 'Jl. Pangeran Drajat No. 5, Beber', '0231-771002', 'smkn1beber@sch.id', NULL, 'Agus Prasetyo, S.Pd', 'Guru BK', '08123456037', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(85, 'SKL-KCRB-CLD-01', 'SMA Negeri 1 Ciledug', 'A', 33, 34, 'Ciledug', 'Jl. Merdeka Barat No. 23, Ciledug', '0231-662001', 'sman1ciledug@sch.id', NULL, 'Drs. H. Didi Supriyadi', 'Kepala Sekolah', '08123456038', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(86, 'SKL-KCRB-CLD-02', 'SMK Negeri 1 Ciledug', 'A', 34, 34, 'Ciledug', 'Jl. Mayjen Sutoyo No. 10, Ciledug', '0231-662002', 'smkn1ciledug@sch.id', NULL, 'Eko Prasetyo, M.T', 'BKK Hubin', '08123456039', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(87, 'SKL-KCRB-CWR-01', 'SMA Negeri 1 Ciwaringin', 'A', 33, 14, 'Ciwaringin', 'Jl. Babakan-Ciwaringin No. 1', '0231-358001', 'sman1ciwaringin@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456016', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(88, 'SKL-KCRB-CWR-02', 'MAN 2 Cirebon', 'A', 35, 14, 'Ciwaringin', 'Jl. Babakan Timur No. 1, Ciwaringin', '0231-358002', 'man2cirebon@kemenag.go.id', NULL, 'Drs. H. Muhaimin', 'Kepala Madrasah', '08123456040', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(89, 'SKL-KCRB-DPK-01', 'SMA Negeri 1 Depok', 'B', 33, 35, 'Depok', 'Jl. Kasugengan Kidul No. 1, Depok', '0231-341001', 'sman1depokcrb@sch.id', NULL, 'Dra. Hj. Ratna', 'Guru BK', '08123456041', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(90, 'SKL-KCRB-DPK-02', 'SMK Ulil Albab Depok', 'A', 34, 35, 'Depok', 'Jl. Pangeran Cakrabuana, Depok', '0231-341002', 'smkulilalbab@sch.id', NULL, 'K.H. Lukman Hakim, S.Pd.I', 'Kepala Sekolah', '08123456042', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(91, 'SKL-KCRB-DKP-01', 'SMA Negeri 1 Dukupuntang', 'A', 33, 36, 'Dukupuntang', 'Jl. Nyi Ageng Serang No. 8, Dukupuntang', '0231-830001', 'sman1dukupuntang@sch.id', NULL, 'Drs. H. Sukirno', 'Kepala Sekolah', '08123456043', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(92, 'SKL-KCRB-DKP-02', 'SMK Negeri 1 Dukupuntang', 'B', 34, 36, 'Dukupuntang', 'Jl. Cikalahang No. 3, Dukupuntang', '0231-830002', 'smkn1dukupuntang@sch.id', NULL, 'Ahmad Fauzan, S.Pd', 'Guru BK', '08123456044', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(93, 'SKL-KCRB-GBG-01', 'SMA Negeri 1 Gebang', 'B', 33, 37, 'Gebang', 'Jl. Pangeran Sutajaya No. 14, Gebang', '0231-663001', 'sman1gebang@sch.id', NULL, 'Drs. H. Abdul Ghofur', 'Kepala Sekolah', '08123456045', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(94, 'SKL-KCRB-GBG-02', 'SMK Negeri 1 Gebang', 'A', 34, 37, 'Gebang', 'Jl. Kalimaro No. 20, Gebang', '0231-663002', 'smkn1gebang@sch.id', NULL, 'Rahmat Hidayat, M.Kom', 'Waka Hubin', '08123456046', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(95, 'SKL-KCRB-GGS-01', 'SMA Negeri 1 Gegesik', 'B', 33, 38, 'Gegesik', 'Jl. Raya Gegesik No. 21, Gegesik', '0231-356001', 'sman1gegesik@sch.id', NULL, 'Drs. Supardi, M.Pd', 'Kepala Sekolah', '08123456047', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(96, 'SKL-KCRB-GGS-02', 'SMK Negeri 1 Gegesik', 'B', 34, 38, 'Gegesik', 'Jl. Syekh Bayanillah No. 5, Gegesik', '0231-356002', 'smkn1gegesik@sch.id', NULL, 'Drs. H. Bambang', 'Guru BK', '08123456048', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(97, 'SKL-KCRB-GMP-01', 'SMA Negeri 1 Gempol', 'B', 33, 39, 'Gempol', 'Jl. Palimanan-Gempol KM 2', '0231-343001', 'sman1gempol@sch.id', NULL, 'Drs. H. Taryono', 'Kepala Sekolah', '08123456049', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(98, 'SKL-KCRB-GMP-02', 'SMK Negeri 1 Gempol', 'B', 34, 39, 'Gempol', 'Jl. Raya Ciwaringin-Gempol No. 8', '0231-343002', 'smkn1gempol@sch.id', NULL, 'Hendra Gunawan, S.T', 'Hubinmas', '08123456050', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(99, 'SKL-KCRB-GRG-01', 'SMA Negeri 1 Greged', 'B', 33, 40, 'Greged', 'Jl. Durian No. 12, Greged', '0231-772001', 'sman1greged@sch.id', NULL, 'Dra. Hj. Yayah', 'Kepala Sekolah', '08123456051', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(100, 'SKL-KCRB-GRG-02', 'SMK Negeri 1 Greged', 'B', 34, 40, 'Greged', 'Jl. Raya Lebakmekar No. 4, Greged', '0231-772002', 'smkn1greged@sch.id', NULL, 'Drs. Didi', 'Guru BK', '08123456052', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(101, 'SKL-KCRB-GNJ-01', 'SMA Negeri 1 Gunungjati', 'B', 33, 41, 'Gunungjati', 'Jl. Sunan Gunung Jati KM 5', '0231-209001', 'sman1gunungjati@sch.id', NULL, 'Drs. H. Syarifudin', 'Kepala Sekolah', '08123456053', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(102, 'SKL-KCRB-GNJ-02', 'SMK Negeri 1 Gunung Jati', 'A', 34, 41, 'Gunungjati', 'Jl. Raya Mertasinga No. 11, Gunungjati', '0231-209002', 'smkn1gunungjati@sch.id', NULL, 'Drs. H. Asyari', 'Waka Hubinmas', '08123456054', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(103, 'SKL-KCRB-JMB-01', 'SMA Negeri 1 Jamblang', 'A', 33, 42, 'Jamblang', 'Jl. Nyi Mas Ratu No. 1, Jamblang', '0231-344001', 'sman1jamblang@sch.id', NULL, 'Drs. H. Subur', 'Kepala Sekolah', '08123456055', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(104, 'SKL-KCRB-JMB-02', 'SMK Negeri 1 Jamblang', 'A', 34, 42, 'Jamblang', 'Jl. Nyi Mas Gandasari No. 10, Jamblang', '0231-344002', 'smkn1jamblang@sch.id', NULL, 'Drs. H. Bambang Sugiarto', 'Hubinmas', '08123456056', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(105, 'SKL-KCRB-KLW-01', 'SMA Negeri 1 Kaliwedi', 'B', 33, 43, 'Kaliwedi', 'Jl. Raya Kaliwedi No. 17, Kaliwedi', '0231-355001', 'sman1kaliwedi@sch.id', NULL, 'Drs. Suwarno', 'Kepala Sekolah', '08123456057', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(106, 'SKL-KCRB-KLW-02', 'SMK Negeri 1 Kaliwedi', 'B', 34, 43, 'Kaliwedi', 'Jl. Guwa Kidul No. 5, Kaliwedi', '0231-355002', 'smkn1kaliwedi@sch.id', NULL, 'Nurjaman, S.Pd', 'Guru BK', '08123456058', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(107, 'SKL-KCRB-KPT-01', 'SMA Negeri 1 Kapetakan', 'B', 33, 44, 'Kapetakan', 'Jl. Raya Sunan Gunung Jati No. 88', '0231-209555', 'sman1kapetakan@sch.id', NULL, 'Drs. H. Kasturi', 'Kepala Sekolah', '08123456059', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(108, 'SKL-KCRB-KPT-02', 'SMK Negeri 1 Kapetakan', 'B', 34, 44, 'Kapetakan', 'Jl. Raya Kapetakan No. 12', '0231-209556', 'smkn1kapetakan@sch.id', NULL, 'Agus Salim, M.Pd', 'Guru BK', '08123456060', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(109, 'SKL-KCRB-KSB-01', 'SMA Negeri 1 Karangsembung', 'B', 33, 45, 'Karangsembung', 'Jl. Karangsuwung No. 2, Karangsembung', '0231-664001', 'sman1karangsembung@sch.id', NULL, 'Drs. H. Nana', 'Kepala Sekolah', '08123456061', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(110, 'SKL-KCRB-KSB-02', 'SMK Negeri 1 Karangsembung', 'B', 34, 45, 'Karangsembung', 'Jl. Kubangkarang No. 7, Karangsembung', '0231-664002', 'smkn1karangsembung@sch.id', NULL, 'Dedi Iskandar, S.T', 'Hubinmas', '08123456062', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(111, 'SKL-KCRB-KWR-01', 'SMA Negeri 1 Karangwareng', 'B', 33, 46, 'Karangwareng', 'Jl. Raya Karangwareng No. 3', '0231-665001', 'sman1karangwareng@sch.id', NULL, 'Drs. H. Kusnadi', 'Kepala Sekolah', '08123456063', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(112, 'SKL-KCRB-KWR-02', 'SMK Negeri 1 Karangwareng', 'B', 34, 46, 'Karangwareng', 'Jl. Pangeran Girilaya No. 1, Karangwareng', '0231-665002', 'smkn1karangwareng@sch.id', NULL, 'Asep Gunawan, S.Pd', 'Guru BK', '08123456064', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(113, 'SKL-KCRB-KDW-01', 'SMA Negeri 1 Kedawung', 'A', 33, 8, 'Kedawung', 'Jl. Tuparev No. 70, Kedawung', '0231-203401', 'sman1kedawung@sch.id', NULL, 'Drs. H. Rasidi, M.Pd', 'Kepala Sekolah', '08123456012', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(114, 'SKL-KCRB-KDW-02', 'SMK Negeri 1 Kedawung', 'A', 34, 8, 'Kedawung', 'Jl. Tuparev No. 87, Kedawung', '0231-203402', 'smkn1kedawung@sch.id', NULL, 'Hj. Sri Rahayu, M.M', 'Kepala Sekolah', '08123456013', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(115, 'SKL-KCRB-KDW-03', 'SMK Yadika Kedawung', 'A', 34, 8, 'Kedawung', 'Jl. Tuparev No. 112, Kedawung', '0231-203403', 'smkyadikacedawung@sch.id', NULL, 'Drs. Antonius Siregar', 'Kepala Sekolah', '08123456065', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(116, 'SKL-KCRB-KLG-01', 'SMA Negeri 1 Klangenan', 'B', 33, 47, 'Klangenan', 'Jl. Oto Iskandardinata No. 5, Klangenan', '0231-345001', 'sman1klangenan@sch.id', NULL, 'Drs. H. Mamat', 'Kepala Sekolah', '08123456066', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(117, 'SKL-KCRB-KLG-02', 'SMK Negeri 1 Klangenan', 'B', 34, 47, 'Klangenan', 'Jl. Raya Klangenan No. 18, Klangenan', '0231-345002', 'smkn1klangenan@sch.id', NULL, 'Agus Supriyadi, S.Pd', 'Guru BK', '08123456067', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(118, 'SKL-KCRB-LMH-01', 'SMA Negeri 1 Lemahabang', 'A', 33, 48, 'Lemahabang', 'Jl. Raya Lemahabang No. 10, Lemahabang', '0231-666001', 'sman1lemahabang@sch.id', NULL, 'Drs. H. Ade Sunardi', 'Kepala Sekolah', '08123456068', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(119, 'SKL-KCRB-LMH-02', 'SMK Negeri 1 Lemahabang', 'A', 34, 48, 'Lemahabang', 'Jl. KH. Wahid Hasyim No. 9, Lemahabang', '0231-666002', 'smkn1lemahabang@sch.id', NULL, 'Drs. H. Maman Suratman', 'Waka Hubinmas', '08123456069', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(120, 'SKL-KCRB-LSR-01', 'SMA Negeri 1 Losari', 'A', 33, 49, 'Losari', 'Jl. Raya Losari No. 12, Losari', '0231-881001', 'sman1losari@sch.id', NULL, 'Drs. H. Mulyadi', 'Kepala Sekolah', '08123456070', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(121, 'SKL-KCRB-LSR-02', 'SMK Negeri 1 Losari', 'A', 34, 49, 'Losari', 'Jl. Raya Cisanggarung No. 5, Losari', '0231-881002', 'smkn1losari@sch.id', NULL, 'Ir. Hartono', 'Hubinmas', '08123456071', 'Aktif', NULL, '2026-09-29 15:06:51', '2026-09-29 15:06:51', NULL, NULL),
(122, 'SKL-KCRB-MND-01', 'SMA Negeri 1 Mundu', 'B', 33, 50, 'Mundu', 'Jl. Luwung - Setupatok, Mundu', '0231-511001', 'sman1mundu@sch.id', NULL, 'Drs. H. Suwito', 'Kepala Sekolah', '08123456072', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(123, 'SKL-KCRB-MND-02', 'SMK Negeri 1 Mundu Cirebon', 'A', 34, 50, 'Mundu', 'Jl. Kyai Haji Abdul Halim No. 1, Cirebon', '0231-511002', 'smkn1mundu@sch.id', NULL, 'Dr. H. Ruspendi, M.Pd', 'Kepala Sekolah', '08123456073', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(124, 'SKL-KCRB-PBD-01', 'SMA Negeri 1 Pabedilan', 'B', 33, 51, 'Pabedilan', 'Jl. Mayjen Sutoyo No. 45, Pabedilan', '0231-667001', 'sman1pabedilan@sch.id', NULL, 'Drs. H. Tarkim', 'Kepala Sekolah', '08123456074', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(125, 'SKL-KCRB-PBD-02', 'SMK Negeri 1 Pabedilan', 'B', 34, 51, 'Pabedilan', 'Jl. Babakan-Pabedilan KM 3', '0231-667002', 'smkn1pabedilan@sch.id', NULL, 'Dra. Hj. Nunung', 'Guru BK', '08123456075', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(126, 'SKL-KCRB-PBR-01', 'SMA Negeri 1 Pabuaran', 'B', 33, 52, 'Pabuaran', 'Jl. Pangeran Sutajaya No. 88, Pabuaran', '0231-668001', 'sman1pabuaran@sch.id', NULL, 'Drs. H. Wahyudi', 'Kepala Sekolah', '08123456076', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(127, 'SKL-KCRB-PBR-02', 'SMK Negeri 1 Pabuaran', 'B', 34, 52, 'Pabuaran', 'Jl. Raya Pabuaran No. 12', '0231-668002', 'smkn1pabuaran@sch.id', NULL, 'Samsudin, S.Pd', 'Guru BK', '08123456077', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(128, 'SKL-KCRB-PLM-01', 'SMA Negeri 1 Palimanan', 'A', 33, 53, 'Palimanan', 'Jl. Dr. Setiabudi No. 1, Pegagan, Palimanan', '0231-341111', 'sman1palimanan@sch.id', NULL, 'Drs. H. Karnadi', 'Kepala Sekolah', '08123456010', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(129, 'SKL-KCRB-PLM-02', 'SMK Negeri 1 Palimanan', 'A', 34, 53, 'Palimanan', 'Jl. KH Agus Salim No. 5, Palimanan', '0231-341222', 'smkn1palimanan@sch.id', NULL, 'Dr. H. Subarjo', 'Waka Hubin', '08123456078', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(130, 'SKL-KCRB-PGN-01', 'SMA Negeri 1 Pangenan', 'B', 33, 54, 'Pangenan', 'Jl. Raya Pantura Astanamukti, Pangenan', '0231-512001', 'sman1pangenan@sch.id', NULL, 'Drs. H. Maman', 'Kepala Sekolah', '08123456079', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(131, 'SKL-KCRB-PGN-02', 'SMK Negeri 1 Pangenan', 'B', 34, 54, 'Pangenan', 'Jl. Pangeran Diponegoro No. 8, Pangenan', '0231-512002', 'smkn1pangenan@sch.id', NULL, 'Agus Santoso, S.T', 'Hubinmas', '08123456080', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(132, 'SKL-KCRB-PGR-01', 'SMA Negeri 1 Panguragan', 'B', 33, 55, 'Panguragan', 'Jl. Panguragan Kulon No. 5', '0231-354001', 'sman1panguragan@sch.id', NULL, 'Drs. H. Suwandi', 'Kepala Sekolah', '08123456081', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(133, 'SKL-KCRB-PGR-02', 'SMK Negeri 1 Panguragan', 'B', 34, 55, 'Panguragan', 'Jl. Nyi Mas Gandasari No. 15, Panguragan', '0231-354002', 'smkn1panguragan@sch.id', NULL, 'Budi Waluyo, S.Pd', 'Guru BK', '08123456082', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(134, 'SKL-KCRB-PSL-01', 'SMA Negeri 1 Pasaleman', 'B', 33, 56, 'Pasaleman', 'Jl. Cilengkrang No. 11, Pasaleman', '0231-669001', 'sman1pasaleman@sch.id', NULL, 'Drs. H. Sukmana', 'Kepala Sekolah', '08123456083', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(135, 'SKL-KCRB-PSL-02', 'SMK Negeri 1 Pasaleman', 'B', 34, 56, 'Pasaleman', 'Jl. Raya Tonjong No. 6, Pasaleman', '0231-669002', 'smkn1pasaleman@sch.id', NULL, 'Dedi Supriadi, S.Pd', 'Guru BK', '08123456084', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(136, 'SKL-KCRB-PLR-01', 'SMA Negeri 1 Plered', 'B', 33, 57, 'Plered', 'Jl. Syekh Datul Kahfi No. 5, Plered', '0231-321001', 'sman1plered@sch.id', NULL, 'Drs. H. Kholid', 'Kepala Sekolah', '08123456085', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(137, 'SKL-KCRB-PLR-02', 'SMK Negeri 1 Plered', 'A', 34, 57, 'Plered', 'Jl. Nyi Ageng Serang No. 12, Plered', '0231-321002', 'smkn1plered@sch.id', NULL, 'Ir. Bambang Sugiarto', 'Hubinmas', '08123456086', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(138, 'SKL-KCRB-PLB-01', 'SMA Negeri 1 Plumbon', 'A', 33, 11, 'Plumbon', 'Jl. Pangeran Antasari No. 4, Plumbon', '0231-321111', 'sman1plumbon@sch.id', NULL, 'Drs. H. Masturo', 'Kepala Sekolah', '08123456018', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(139, 'SKL-KCRB-PLB-02', 'SMK Negeri 1 Plumbon', 'B', 34, 11, 'Plumbon', 'Jl. Raya Cirebon-Bandung KM 10, Plumbon', '0231-321222', 'smkn1plumbon@sch.id', NULL, 'Drs. H. Maman', 'Guru BK', '08123456087', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(140, 'SKL-KCRB-SDG-01', 'SMA Negeri 1 Sedong', 'B', 33, 58, 'Sedong', 'Jl. Putat Sedong No. 5, Sedong', '0231-660001', 'sman1sedong@sch.id', NULL, 'Drs. H. Suharto', 'Kepala Sekolah', '08123456088', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(141, 'SKL-KCRB-SDG-02', 'SMK Negeri 1 Sedong', 'B', 34, 58, 'Sedong', 'Jl. Raya Panongan No. 9, Sedong', '0231-660002', 'smkn1sedong@sch.id', NULL, 'Ahmad Rifa\'i, S.Pd', 'Guru BK', '08123456089', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(142, 'SKL-KCRB-SBR-01', 'SMA Negeri 1 Sumber', 'A', 33, 10, 'Sumber', 'Jl. Perjuangan No. 1, Sumber', '0231-321456', 'info@sman1sumber.sch.id', NULL, 'Drs. H. Kosim', 'Kepala Sekolah', '08123456009', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(143, 'SKL-KCRB-SBR-02', 'SMK Negeri 1 Sumber', 'A', 34, 10, 'Sumber', 'Jl. Ki Gede Mayung No. 12, Sumber', '0231-321457', 'smkn1sumber@sch.id', NULL, 'Dr. H. Ruspendi', 'Waka Hubinmas', '08123456090', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(144, 'SKL-KCRB-SBR-03', 'MAN 1 Cirebon', 'A', 35, 10, 'Sumber', 'Jl. R. Dewi Sartika No. 36, Sumber', '0231-321458', 'man1cirebon@kemenag.go.id', NULL, 'Drs. H. Imron, M.Ag', 'Kepala Madrasah', '08123456091', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(145, 'SKL-KCRB-SRN-01', 'SMA Negeri 1 Suranenggala', 'B', 33, 59, 'Suranenggala', 'Jl. Sunan Gunung Jati KM 14, Suranenggala', '0231-209888', 'sman1suranenggala@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456092', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(146, 'SKL-KCRB-SRN-02', 'SMK Negeri 1 Suranenggala', 'B', 34, 59, 'Suranenggala', 'Jl. Karangreja No. 4, Suranenggala', '0231-209889', 'smkn1suranenggala@sch.id', NULL, 'Suherman, S.Pd', 'Guru BK', '08123456093', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(147, 'SKL-KCRB-SSK-01', 'SMA Negeri 1 Susukan', 'B', 33, 60, 'Susukan', 'Jl. Raya Susukan-Gegesik KM 2', '0231-353001', 'sman1susukan@sch.id', NULL, 'Drs. H. Didi Sutisna', 'Kepala Sekolah', '08123456094', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(148, 'SKL-KCRB-SSK-02', 'SMK Negeri 1 Susukan', 'B', 34, 60, 'Susukan', 'Jl. Budi Utomo No. 10, Susukan', '0231-353002', 'smkn1susukan@sch.id', NULL, 'Asep Saepul, S.T', 'Hubinmas', '08123456095', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(149, 'SKL-KCRB-SSL-01', 'SMA Negeri 1 Susukanlebak', 'B', 33, 61, 'Susukanlebak', 'Jl. Pasawahan No. 6, Susukanlebak', '0231-660111', 'sman1susukanlebak@sch.id', NULL, 'Drs. H. Karnoto', 'Kepala Sekolah', '08123456096', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(150, 'SKL-KCRB-SSL-02', 'SMK Negeri 1 Susukanlebak', 'B', 34, 61, 'Susukanlebak', 'Jl. Ciawiasih No. 2, Susukanlebak', '0231-660112', 'smkn1susukanlebak@sch.id', NULL, 'Dedi Gunawan, S.Pd', 'Guru BK', '08123456097', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(151, 'SKL-KCRB-TLN-01', 'SMA Negeri 1 Talun', 'B', 33, 62, 'Talun', 'Jl. Pangeran Cakrabuana No. 28, Talun', '0231-831001', 'sman1talun@sch.id', NULL, 'Drs. H. Mulyadi', 'Kepala Sekolah', '08123456098', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(152, 'SKL-KCRB-TLN-02', 'SMK Negeri 1 Talun', 'B', 34, 62, 'Talun', 'Jl. Kemantren No. 9, Talun', '0231-831002', 'smkn1talun@sch.id', NULL, 'Rahmat Hidayat, M.Pd', 'Hubinmas', '08123456099', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(153, 'SKL-KCRB-TGT-01', 'SMA Negeri 1 Tengahtani', 'B', 33, 63, 'Tengahtani', 'Jl. Raya Pantura Battembat, Tengahtani', '0231-322001', 'sman1tengahtani@sch.id', NULL, 'Drs. H. Sukardi', 'Kepala Sekolah', '08123456100', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(154, 'SKL-KCRB-TGT-02', 'SMK Mandiri Tengahtani', 'B', 34, 63, 'Tengahtani', 'Jl. Raya Kemlakagede No. 8, Tengahtani', '0231-322002', 'smkmandiritgt@sch.id', NULL, 'Dra. Hj. Nunung', 'Guru BK', '08123456101', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(155, 'SKL-KCRB-WLD-01', 'SMA Negeri 1 Waled', 'A', 33, 64, 'Waled', 'Jl. Prabu Kiansantang No. 7, Waled', '0231-660222', 'sman1waled@sch.id', NULL, 'Drs. H. Solihin', 'Kepala Sekolah', '08123456102', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(156, 'SKL-KCRB-WLD-02', 'SMK Negeri 1 Waled', 'B', 34, 64, 'Waled', 'Jl. Raya Waled No. 15, Waled', '0231-660223', 'smkn1waled@sch.id', NULL, 'Budi Santoso, S.T', 'Hubinmas', '08123456103', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(157, 'SKL-KCRB-WRU-01', 'SMA Negeri 1 Weru', 'B', 33, 9, 'Weru', 'Jl. Fatahillah No. 45, Megu Cilik, Weru', '0231-321789', 'info@sman1weru.sch.id', NULL, 'Dra. Hj. Sri Wahyuni', 'Guru BK', '08123456019', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(158, 'SKL-KCRB-WRU-02', 'SMK Negeri 1 Weru', 'A', 34, 9, 'Weru', 'Jl. Otista No. 45, Weru', '0231-321790', 'smkn1weru@sch.id', NULL, 'Drs. H. Maman Suratman', 'Kepala Sekolah', '08123456020', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(159, 'SKL-KCRB-WRU-03', 'SMK Karya Nasional Weru', 'B', 34, 9, 'Weru', 'Jl. Fatahillah No. 88, Weru', '0231-321791', 'smkkarnasweru@sch.id', NULL, 'Drs. H. Subur', 'Hubinmas', '08123456104', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(160, 'SKL-IND-ANJ-01', 'SMA Negeri 1 Anjatan', 'A', 33, 65, 'Anjatan', 'Jl. Raya Anjatan Utara No. 4, Anjatan', '0234-610001', 'sman1anjatan@sch.id', NULL, 'Drs. H. Rastim, M.Pd', 'Kepala Sekolah', '08123456105', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(161, 'SKL-IND-ANJ-02', 'SMK Negeri 1 Anjatan', 'A', 34, 65, 'Anjatan', 'Jl. Lempuyang No. 8, Anjatan', '0234-610002', 'smkn1anjatan@sch.id', NULL, 'Bambang Irawan, S.T', 'Hubinmas', '08123456106', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(162, 'SKL-IND-ARH-01', 'SMA Negeri 1 Arahan', 'B', 33, 66, 'Arahan', 'Jl. Raya Arahan Lor No. 12, Arahan', '0234-271001', 'sman1arahan@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456107', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(163, 'SKL-IND-ARH-02', 'SMK Negeri 1 Arahan', 'B', 34, 66, 'Arahan', 'Jl. Linggajati No. 4, Arahan', '0234-271002', 'smkn1arahan@sch.id', NULL, 'Suherman, S.Pd', 'Guru BK', '08123456108', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(164, 'SKL-IND-BLG-01', 'SMA Negeri 1 Balongan', 'A', 33, 67, 'Balongan', 'Jl. Raya Sukaurip No. 1, Balongan', '0234-428111', 'sman1balongan@sch.id', NULL, 'Drs. H. Kusen', 'Kepala Sekolah', '08123456109', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(165, 'SKL-IND-BLG-02', 'SMK Negeri 1 Balongan', 'A', 34, 67, 'Balongan', 'Jl. Raya Balongan No. 8, Balongan', '0234-428222', 'smkn1balongan@sch.id', NULL, 'Dr. H. Suwandi, M.T', 'Kepala Sekolah', '08123456110', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(166, 'SKL-IND-BGD-01', 'SMA Negeri 1 Bangodua', 'B', 33, 68, 'Bangodua', 'Jl. Raya Bangodua No. 11, Bangodua', '0234-351001', 'sman1bangodua@sch.id', NULL, 'Drs. H. Kholid', 'Kepala Sekolah', '08123456111', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(167, 'SKL-IND-BGD-02', 'SMK Negeri 1 Bangodua', 'B', 34, 68, 'Bangodua', 'Jl. Beduyut No. 5, Bangodua', '0234-351002', 'smkn1bangodua@sch.id', NULL, 'Asep Hidayat, S.Pd', 'Guru BK', '08123456112', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(168, 'SKL-IND-BGS-01', 'SMA Negeri 1 Bongas', 'B', 33, 69, 'Bongas', 'Jl. Raya Margamulya No. 5, Bongas', '0234-611001', 'sman1bongas@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456113', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(169, 'SKL-IND-BGS-02', 'SMK Negeri 1 Bongas', 'B', 34, 69, 'Bongas', 'Jl. Kertajaya No. 8, Bongas', '0234-611002', 'smkn1bongas@sch.id', NULL, 'Agus Salim, S.T', 'Guru BK', '08123456114', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(170, 'SKL-IND-CTG-01', 'SMA Negeri 1 Cantigi', 'B', 33, 70, 'Cantigi', 'Jl. Cantigi Kulon No. 2, Cantigi', '0234-272001', 'sman1cantigi@sch.id', NULL, 'Drs. H. Sutisna', 'Kepala Sekolah', '08123456115', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(171, 'SKL-IND-CTG-02', 'SMK Negeri 1 Cantigi', 'B', 34, 70, 'Cantigi', 'Jl. Raya Pangkalan No. 7, Cantigi', '0234-272002', 'smkn1cantigi@sch.id', NULL, 'Rudi Hartono, S.Pd', 'Hubinmas', '08123456116', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(172, 'SKL-IND-CKD-01', 'SMA Negeri 1 Cikedung', 'B', 33, 71, 'Cikedung', 'Jl. Jambak No. 15, Cikedung', '0234-481001', 'sman1cikedung@sch.id', NULL, 'Drs. H. Rasidi', 'Kepala Sekolah', '08123456117', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(173, 'SKL-IND-CKD-02', 'SMK Negeri 1 Cikedung', 'B', 34, 71, 'Cikedung', 'Jl. Amis-Cikedung KM 3, Cikedung', '0234-481002', 'smkn1cikedung@sch.id', NULL, 'Dedi Supriatna, S.T', 'Guru BK', '08123456118', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(174, 'SKL-IND-GBW-01', 'SMA Negeri 1 Gabuswetan', 'A', 33, 72, 'Gabuswetan', 'Jl. Raya Gabuswetan No. 33, Gabuswetan', '0234-551001', 'sman1gabuswetan@sch.id', NULL, 'Drs. H. Taryono', 'Kepala Sekolah', '08123456119', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(175, 'SKL-IND-GBW-02', 'SMK Negeri 1 Gabuswetan', 'A', 34, 72, 'Gabuswetan', 'Jl. Dr. Setiabudi No. 1, Gabuswetan', '0234-551002', 'smkn1gabuswetan@sch.id', NULL, 'H. Suherman, S.Pd', 'Hubinmas', '08123456120', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(176, 'SKL-IND-GTR-01', 'SMA Negeri 1 Gantar', 'B', 33, 73, 'Gantar', 'Jl. Haurgeulis-Gantar KM 6, Gantar', '0234-612001', 'sman1gantar@sch.id', NULL, 'Drs. H. Didi Supriyadi', 'Kepala Sekolah', '08123456121', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(177, 'SKL-IND-GTR-02', 'SMK Negeri 1 Gantar', 'B', 34, 73, 'Gantar', 'Jl. Mekarjaya No. 10, Gantar', '0234-612002', 'smkn1gantar@sch.id', NULL, 'Nurdiansyah, S.T', 'Guru BK', '08123456122', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(178, 'SKL-IND-HGL-01', 'SMA Negeri 1 Haurgeulis', 'A', 33, 20, 'Haurgeulis', 'Jl. Jend. Sudirman No. 10, Haurgeulis', '0234-741001', 'sman1haurgeulis@sch.id', NULL, 'Drs. H. Supardi', 'Kepala Sekolah', '08123456041', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(179, 'SKL-IND-HGL-02', 'SMK Negeri 1 Haurgeulis', 'A', 34, 20, 'Haurgeulis', 'Jl. Mekarwangi No. 5, Haurgeulis', '0234-741002', 'smkn1haurgeulis@sch.id', NULL, 'Drs. H. Kasturi', 'Waka Hubin', '08123456123', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(180, 'SKL-IND-IDM-01', 'SMA Negeri 1 Indramayu', 'A', 33, 17, 'Indramayu', 'Jl. Mayor Dasuki No. 39, Indramayu', '0234-271234', 'info@sman1indramayu.sch.id', NULL, 'Drs. H. Kusworo', 'Kepala Sekolah', '08123456020', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(181, 'SKL-IND-IDM-02', 'SMA Negeri 2 Indramayu', 'A', 33, 17, 'Indramayu', 'Jl. Pahlawan No. 4, Indramayu', '0234-272345', 'info@sman2indramayu.sch.id', NULL, 'Dra. Hj. Siti Rohani', 'Guru BK', '08123456021', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(182, 'SKL-IND-IDM-03', 'SMK Negeri 1 Indramayu', 'A', 34, 17, 'Indramayu', 'Jl. Gatot Subroto No. 5, Indramayu', '0234-273456', 'smkn1indramayu@sch.id', NULL, 'Dr. Ir. Bambang Sugiarto', 'Kepala Sekolah', '08123456124', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL);
INSERT INTO `sekolahs` (`id`, `kode`, `nama`, `tier`, `kategori_id`, `wilayah_id`, `kecamatan`, `alamat`, `telepon`, `email`, `website`, `pic_name`, `pic_jabatan`, `pic_phone`, `status`, `sales_id`, `created_at`, `updated_at`, `lat`, `lng`) VALUES
(183, 'SKL-IND-IDM-04', 'MAN 1 Indramayu', 'A', 35, 17, 'Indramayu', 'Jl. Soekarno Hatta No. 7, Indramayu', '0234-274567', 'man1indramayu@kemenag.go.id', NULL, 'Drs. H. Mulyadi', 'Kepala Madrasah', '08123456125', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(184, 'SKL-IND-JTB-01', 'SMA Negeri 1 Jatibarang', 'A', 33, 19, 'Jatibarang', 'Jl. Mayor Dasuki No. 55, Jatibarang', '0234-351222', 'sman1jatibarang@sch.id', NULL, 'Drs. H. Solihin', 'Kepala Sekolah', '08123456039', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(185, 'SKL-IND-JTB-02', 'SMK Negeri 1 Jatibarang', 'A', 34, 19, 'Jatibarang', 'Jl. Raya Bulak No. 1, Jatibarang', '0234-351333', 'smkn1jatibarang@sch.id', NULL, 'Drs. H. Ade Sunardi', 'Waka Hubinmas', '08123456040', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(186, 'SKL-IND-JNT-01', 'SMA Negeri 1 Juntinyuat', 'B', 33, 74, 'Juntinyuat', 'Jl. Raya Segeran No. 22, Juntinyuat', '0234-429001', 'sman1juntinyuat@sch.id', NULL, 'Drs. H. Rasidin', 'Kepala Sekolah', '08123456126', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(187, 'SKL-IND-JNT-02', 'SMK Negeri 1 Juntinyuat', 'B', 34, 74, 'Juntinyuat', 'Jl. Raya Dadap No. 15, Juntinyuat', '0234-429002', 'smkn1juntinyuat@sch.id', NULL, 'Ahmad Rifa\'i, S.T', 'Guru BK', '08123456127', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(188, 'SKL-IND-KDH-01', 'SMA Negeri 1 Kandanghaur', 'A', 33, 75, 'Kandanghaur', 'Jl. Raya Eretan Kulon No. 4, Kandanghaur', '0234-505001', 'sman1kandanghaur@sch.id', NULL, 'Drs. H. Sukirno', 'Kepala Sekolah', '08123456128', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(189, 'SKL-IND-KDH-02', 'SMK Negeri 1 Kandanghaur', 'A', 34, 75, 'Kandanghaur', 'Jl. Raya Pantura Ilir KM 12, Kandanghaur', '0234-505002', 'smkn1kandanghaur@sch.id', NULL, 'Drs. H. Maman', 'Waka Hubin', '08123456129', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(190, 'SKL-IND-KRA-01', 'SMA Negeri 1 Karangampel', 'A', 33, 18, 'Karangampel', 'Jl. Dampoawang No. 1, Karangampel', '0234-352001', 'sman1karangampel@sch.id', NULL, 'Drs. H. Tarkim, M.Pd', 'Kepala Sekolah', '08123456037', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(191, 'SKL-IND-KRA-02', 'SMK Negeri 1 Karangampel', 'A', 34, 18, 'Karangampel', 'Jl. Raya Pringgacala No. 12, Karangampel', '0234-352002', 'smkn1karangampel@sch.id', NULL, 'Drs. H. Kusnadi', 'Waka Hubinmas', '08123456038', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(192, 'SKL-IND-KRA-03', 'MAN 2 Indramayu', 'A', 35, 18, 'Karangampel', 'Jl. Raya Karangampel Barat No. 8', '0234-352003', 'man2indramayu@kemenag.go.id', NULL, 'Drs. H. Muhaimin', 'Kepala Madrasah', '08123456130', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(193, 'SKL-IND-KDB-01', 'SMA Negeri 1 Kedokan Bunder', 'B', 33, 76, 'Kedokan Bunder', 'Jl. Raya Kedokan Agung No. 19', '0234-353001', 'sman1kedokanbunder@sch.id', NULL, 'Drs. H. Sulaeman', 'Kepala Sekolah', '08123456131', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(194, 'SKL-IND-KDB-02', 'SMK Negeri 1 Kedokan Bunder', 'B', 34, 76, 'Kedokan Bunder', 'Jl. Kaplongan No. 2, Kedokan Bunder', '0234-353002', 'smkn1kedokanbunder@sch.id', NULL, 'H. Dedi Supriyadi, S.Pd', 'Guru BK', '08123456132', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(195, 'SKL-IND-KTS-01', 'SMA Negeri 1 Kertasemaya', 'B', 33, 77, 'Kertasemaya', 'Jl. Tulungagung No. 14, Kertasemaya', '0234-354001', 'sman1kertasemaya@sch.id', NULL, 'Drs. H. Subur', 'Kepala Sekolah', '08123456133', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(196, 'SKL-IND-KTS-02', 'SMK Negeri 1 Kertasemaya', 'B', 34, 77, 'Kertasemaya', 'Jl. Raya Kertasemaya KM 1, Kertasemaya', '0234-354002', 'smkn1kertasemaya@sch.id', NULL, 'Budi Waluyo, S.T', 'Guru BK', '08123456134', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(197, 'SKL-IND-KRG-01', 'SMA Negeri 1 Krangkeng', 'B', 33, 78, 'Krangkeng', 'Jl. Raya Krangkeng KM 30, Krangkeng', '0234-355001', 'sman1krangkeng@sch.id', NULL, 'Drs. H. Masturo', 'Kepala Sekolah', '08123456135', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(198, 'SKL-IND-KRG-02', 'SMK Negeri 1 Krangkeng', 'B', 34, 78, 'Krangkeng', 'Jl. Dukuh Jati No. 4, Krangkeng', '0234-355002', 'smkn1krangkeng@sch.id', NULL, 'Ahmad Fauzi, S.Pd', 'Guru BK', '08123456136', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(199, 'SKL-IND-KRY-01', 'SMA Negeri 1 Kroya', 'B', 33, 79, 'Kroya', 'Jl. Sukamelang No. 10, Kroya', '0234-552001', 'sman1kroya@sch.id', NULL, 'Drs. H. Suwito', 'Kepala Sekolah', '08123456137', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(200, 'SKL-IND-KRY-02', 'SMK Negeri 1 Kroya', 'B', 34, 79, 'Kroya', 'Jl. Raya Kroya-Temiyang No. 8', '0234-552002', 'smkn1kroya@sch.id', NULL, 'Dra. Hj. Aminah', 'Guru BK', '08123456138', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(201, 'SKL-IND-LLA-01', 'SMA Negeri 1 Lelea', 'B', 33, 80, 'Lelea', 'Jl. Raya Tugu No. 9, Lelea', '0234-482001', 'sman1lelea@sch.id', NULL, 'Drs. H. Nana', 'Kepala Sekolah', '08123456139', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(202, 'SKL-IND-LLA-02', 'SMK Negeri 1 Lelea', 'B', 34, 80, 'Lelea', 'Jl. Raya Lelea-Tunggulpayung KM 2', '0234-482002', 'smkn1lelea@sch.id', NULL, 'Dedi Iskandar, S.T', 'Hubinmas', '08123456140', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(203, 'SKL-IND-LHB-01', 'SMA Negeri 1 Lohbener', 'B', 33, 81, 'Lohbener', 'Jl. Raya Celancang No. 5, Lohbener', '0234-275001', 'sman1lohbener@sch.id', NULL, 'Drs. H. Wahyudi', 'Kepala Sekolah', '08123456141', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(204, 'SKL-IND-LHB-02', 'SMK Negeri 1 Lohbener', 'A', 34, 81, 'Lohbener', 'Jl. Raya Lohbener Timur No. 1, Lohbener', '0234-275002', 'smkn1lohbener@sch.id', NULL, 'Dr. H. Ruspendi, M.Pd', 'Kepala Sekolah', '08123456142', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(205, 'SKL-IND-LSR-01', 'SMA Negeri 1 Losarang', 'A', 33, 82, 'Losarang', 'Jl. Raya Pantura Puntang, Losarang', '0234-506001', 'sman1losarang@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456143', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(206, 'SKL-IND-LSR-02', 'SMK Negeri 1 Losarang', 'A', 34, 82, 'Losarang', 'Jl. Santing No. 12, Losarang', '0234-506002', 'smkn1losarang@sch.id', NULL, 'Ir. Suherman', 'Hubinmas', '08123456144', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(207, 'SKL-IND-PSK-01', 'SMA Negeri 1 Pasekan', 'B', 33, 83, 'Pasekan', 'Jl. Raya Pasekan No. 7, Pasekan', '0234-276001', 'sman1pasekan@sch.id', NULL, 'Drs. H. Suharto', 'Kepala Sekolah', '08123456145', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(208, 'SKL-IND-PSK-02', 'SMK Negeri 1 Pasekan', 'B', 34, 83, 'Pasekan', 'Jl. Pabean Ilir No. 4, Pasekan', '0234-276002', 'smkn1pasekan@sch.id', NULL, 'Budi Santoso, S.Pd', 'Guru BK', '08123456146', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(209, 'SKL-IND-PTR-01', 'SMA Negeri 1 Patrol', 'A', 33, 84, 'Patrol', 'Jl. Raya Patrol KM 42, Patrol', '0234-613001', 'sman1patrol@sch.id', NULL, 'Drs. H. Rasidi', 'Kepala Sekolah', '08123456147', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(210, 'SKL-IND-PTR-02', 'SMK Negeri 1 Patrol', 'A', 34, 84, 'Patrol', 'Jl. Patrol Lor No. 18, Patrol', '0234-613002', 'smkn1patrol@sch.id', NULL, 'Drs. H. Maman', 'Waka Hubinmas', '08123456148', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(211, 'SKL-IND-SDG-01', 'SMA Negeri 1 Sindang', 'A', 33, 85, 'Sindang', 'Jl. MT Haryono No. 1, Sindang', '0234-272111', 'sman1sindang@sch.id', NULL, 'Drs. H. Ade Sunardi', 'Kepala Sekolah', '08123456149', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(212, 'SKL-IND-SDG-02', 'SMA Negeri 2 Sindang', 'B', 33, 85, 'Sindang', 'Jl. Murah Nara No. 7, Sindang', '0234-272222', 'sman2sindang@sch.id', NULL, 'Dra. Hj. Nunung', 'Guru BK', '08123456150', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(213, 'SKL-IND-SDG-03', 'SMK Negeri 1 Sindang', 'A', 34, 85, 'Sindang', 'Jl. Mayor Dasuki No. 88, Sindang', '0234-272333', 'smkn1sindang@sch.id', NULL, 'Dr. H. Ruspendi', 'Kepala Sekolah', '08123456151', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(214, 'SKL-IND-SLY-01', 'SMA Negeri 1 Sliyeg', 'B', 33, 86, 'Sliyeg', 'Jl. Raya Sliyeg Lor No. 7, Sliyeg', '0234-356001', 'sman1sliyeg@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456152', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(215, 'SKL-IND-SLY-02', 'SMK Negeri 1 Sliyeg', 'B', 34, 86, 'Sliyeg', 'Jl. Tambi No. 3, Sliyeg', '0234-356002', 'smkn1sliyeg@sch.id', NULL, 'Suherman, S.Pd', 'Guru BK', '08123456153', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(216, 'SKL-IND-SKG-01', 'SMA Negeri 1 Sukagumiwang', 'B', 33, 87, 'Sukagumiwang', 'Jl. Cadangpinggan No. 2, Sukagumiwang', '0234-357001', 'sman1sukagumiwang@sch.id', NULL, 'Drs. H. Tarkim', 'Kepala Sekolah', '08123456154', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(217, 'SKL-IND-SKG-02', 'SMK Negeri 1 Sukagumiwang', 'B', 34, 87, 'Sukagumiwang', 'Jl. Raya By Pass Bondan, Sukagumiwang', '0234-357002', 'smkn1sukagumiwang@sch.id', NULL, 'Dra. Hj. Aminah', 'Guru BK', '08123456155', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(218, 'SKL-IND-SKR-01', 'SMA Negeri 1 Sukra', 'B', 33, 88, 'Sukra', 'Jl. Raya Pantura Sumuradem, Sukra', '0234-614001', 'sman1sukra@sch.id', NULL, 'Drs. H. Solihin', 'Kepala Sekolah', '08123456156', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(219, 'SKL-IND-SKR-02', 'SMK Negeri 1 Sukra', 'B', 34, 88, 'Sukra', 'Jl. Ujunggebang No. 3, Sukra', '0234-614002', 'smkn1sukra@sch.id', NULL, 'Asep Gunawan, S.T', 'Guru BK', '08123456157', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(220, 'SKL-IND-TRS-01', 'SMA Negeri 1 Terisi', 'B', 33, 89, 'Terisi', 'Jl. Rajasinga No. 8, Terisi', '0234-483001', 'sman1terisi@sch.id', NULL, 'Drs. H. Sukirno', 'Kepala Sekolah', '08123456158', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(221, 'SKL-IND-TRS-02', 'SMK Negeri 1 Terisi', 'B', 34, 89, 'Terisi', 'Jl. Jatimulya No. 4, Terisi', '0234-483002', 'smkn1terisi@sch.id', NULL, 'Dedi Iskandar, S.Pd', 'Guru BK', '08123456159', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(222, 'SKL-IND-TKD-01', 'SMA Negeri 1 Tukdana', 'B', 33, 90, 'Tukdana', 'Jl. Raya Sukamulya No. 4, Tukdana', '0234-358001', 'sman1tukdana@sch.id', NULL, 'Drs. H. Mamat', 'Kepala Sekolah', '08123456160', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(223, 'SKL-IND-TKD-02', 'SMK Negeri 1 Tukdana', 'B', 34, 90, 'Tukdana', 'Jl. Gadel-Tukdana KM 2, Tukdana', '0234-358002', 'smkn1tukdana@sch.id', NULL, 'Suwandi, S.T', 'Guru BK', '08123456161', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(224, 'SKL-IND-WDS-01', 'SMA Negeri 1 Widasari', 'B', 33, 91, 'Widasari', 'Jl. Kongsijaya No. 3, Widasari', '0234-359001', 'sman1widasari@sch.id', NULL, 'Drs. H. Kusnadi', 'Kepala Sekolah', '08123456162', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(225, 'SKL-IND-WDS-02', 'SMK Negeri 1 Widasari', 'B', 34, 91, 'Widasari', 'Jl. By Pass Widasari No. 11, Widasari', '0234-359002', 'smkn1widasari@sch.id', NULL, 'Nurjaman, S.Pd', 'Guru BK', '08123456163', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(226, 'SKL-MJL-AGP-01', 'SMA Negeri 1 Argapura', 'B', 33, 92, 'Argapura', 'Jl. Raya Sukasari Kaler, Argapura', '0233-828001', 'sman1argapura@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456164', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(227, 'SKL-MJL-AGP-02', 'SMK Negeri 1 Argapura', 'B', 34, 92, 'Argapura', 'Jl. Tejamulya No. 4, Argapura', '0233-828002', 'smkn1argapura@sch.id', NULL, 'Dra. Hj. Nunung', 'Guru BK', '08123456165', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(228, 'SKL-MJL-BJR-01', 'SMA Negeri 1 Banjaran', 'B', 33, 93, 'Banjaran', 'Jl. Raya Banjaran No. 10, Banjaran', '0233-829001', 'sman1banjaran@sch.id', NULL, 'Drs. H. Solihin', 'Kepala Sekolah', '08123456166', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(229, 'SKL-MJL-BJR-02', 'SMK Negeri 1 Banjaran', 'B', 34, 93, 'Banjaran', 'Jl. Sunia No. 4, Banjaran', '0233-829002', 'smkn1banjaran@sch.id', NULL, 'Ahmad Rifa\'i, S.T', 'Hubinmas', '08123456167', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(230, 'SKL-MJL-BTJ-01', 'SMA Negeri 1 Bantarujeg', 'A', 33, 94, 'Bantarujeg', 'Jl. Siliwangi No. 78, Bantarujeg', '0233-831001', 'sman1bantarujeg@sch.id', NULL, 'Drs. H. Rasidi, M.Pd', 'Kepala Sekolah', '08123456168', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(231, 'SKL-MJL-BTJ-02', 'SMK Negeri 1 Bantarujeg', 'B', 34, 94, 'Bantarujeg', 'Jl. Sukamenak No. 12, Bantarujeg', '0233-831002', 'smkn1bantarujeg@sch.id', NULL, 'Dedi Supriatna, S.Pd', 'Guru BK', '08123456169', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(232, 'SKL-MJL-CGS-01', 'SMA Negeri 1 Cigasong', 'B', 33, 95, 'Cigasong', 'Jl. Raya Simpeureum No. 1, Cigasong', '0233-281001', 'sman1cigasong@sch.id', NULL, 'Drs. H. Ade Sunardi', 'Kepala Sekolah', '08123456170', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(233, 'SKL-MJL-CGS-02', 'SMK Negeri 1 Cigasong', 'B', 34, 95, 'Cigasong', 'Jl. Baribis No. 10, Cigasong', '0233-281002', 'smkn1cigasong@sch.id', NULL, 'Suherman, S.T', 'Hubinmas', '08123456171', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(234, 'SKL-MJL-CKJ-01', 'SMA Negeri 1 Cikijing', 'A', 33, 96, 'Cikijing', 'Jl. Raya Kasturi No. 1, Cikijing', '0233-832001', 'sman1cikijing@sch.id', NULL, 'Drs. H. Sukirno, M.Pd', 'Kepala Sekolah', '08123456172', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(235, 'SKL-MJL-CKJ-02', 'SMK Negeri 1 Cikijing', 'A', 34, 96, 'Cikijing', 'Jl. Raya Cikijing-Kuningan KM 1', '0233-832002', 'smkn1cikijing@sch.id', NULL, 'Drs. H. Maman', 'Waka Hubin', '08123456173', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(236, 'SKL-MJL-CGB-01', 'SMA Negeri 1 Cingambul', 'B', 33, 97, 'Cingambul', 'Jl. Raya Cingambul-Ciamis KM 2', '0233-833001', 'sman1cingambul@sch.id', NULL, 'Drs. H. Taryono', 'Kepala Sekolah', '08123456174', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(237, 'SKL-MJL-CGB-02', 'SMK Negeri 1 Cingambul', 'B', 34, 97, 'Cingambul', 'Jl. Wangkelang No. 5, Cingambul', '0233-833002', 'smkn1cingambul@sch.id', NULL, 'Bambang Irawan, S.Pd', 'Guru BK', '08123456175', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(238, 'SKL-MJL-DWN-01', 'SMA Negeri 1 Dawuan', 'B', 33, 98, 'Dawuan', 'Jl. Raya Baturuyuk No. 8, Dawuan', '0233-661001', 'sman1dawuan@sch.id', NULL, 'Drs. H. Kusnadi', 'Kepala Sekolah', '08123456176', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(239, 'SKL-MJL-DWN-02', 'SMK Negeri 1 Dawuan', 'B', 34, 98, 'Dawuan', 'Jl. Gandu No. 3, Dawuan', '0233-661002', 'smkn1dawuan@sch.id', NULL, 'Asep Gunawan, S.T', 'Hubinmas', '08123456177', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(240, 'SKL-MJL-JT7-01', 'SMA Negeri 1 Jatitujuh', 'B', 33, 99, 'Jatitujuh', 'Jl. Raya Jatitujuh No. 4, Jatitujuh', '0233-881001', 'sman1jatitujuh@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456178', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(241, 'SKL-MJL-JT7-02', 'SMK Negeri 1 Jatitujuh', 'B', 34, 99, 'Jatitujuh', 'Jl. Pangkalan No. 8, Jatitujuh', '0233-881002', 'smkn1jatitujuh@sch.id', NULL, 'Suwandi, S.Pd', 'Guru BK', '08123456179', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(242, 'SKL-MJL-JTW-01', 'SMA Negeri 1 Jatiwangi', 'A', 33, 24, 'Jatiwangi', 'Jl. Pos Timur No. 1, Jatiwangi', '0233-882111', 'info@sman1jatiwangi.sch.id', NULL, 'Drs. H. Didi Sutisna', 'Kepala Sekolah', '08123456045', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(243, 'SKL-MJL-JTW-02', 'SMK Negeri 1 Jatiwangi', 'A', 34, 24, 'Jatiwangi', 'Jl. Cicadas No. 8, Jatiwangi', '0233-882222', 'smkn1jatiwangi@sch.id', NULL, 'Ir. Bambang Sugiarto', 'Waka Hubinmas', '08123456046', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(244, 'SKL-MJL-KDP-01', 'SMA Negeri 1 Kadipaten', 'A', 33, 23, 'Kadipaten', 'Jl. Liangjulang No. 1, Kadipaten', '0233-661222', 'info@sman1kadipaten.sch.id', NULL, 'Drs. H. Rasidin, M.Pd', 'Kepala Sekolah', '08123456043', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(245, 'SKL-MJL-KDP-02', 'SMK Negeri 1 Kadipaten', 'A', 34, 23, 'Kadipaten', 'Jl. Siliwangi No. 30, Kadipaten', '0233-661333', 'smkn1kadipaten@sch.id', NULL, 'Drs. H. Masturo', 'Waka Hubinmas', '08123456044', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(246, 'SKL-MJL-KSK-01', 'SMA Negeri 1 Kasokandel', 'B', 33, 100, 'Kasokandel', 'Jl. Raya Kasokandel No. 10, Kasokandel', '0233-662001', 'sman1kasokandel@sch.id', NULL, 'Drs. H. Solihin', 'Kepala Sekolah', '08123456180', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(247, 'SKL-MJL-KSK-02', 'SMK Negeri 1 Kasokandel', 'B', 34, 100, 'Kasokandel', 'Jl. Gunungsari No. 5, Kasokandel', '0233-662002', 'smkn1kasokandel@sch.id', NULL, 'Nurjaman, S.T', 'Guru BK', '08123456181', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(248, 'SKL-MJL-KTJ-01', 'SMA Negeri 1 Kertajati', 'A', 33, 101, 'Kertajati', 'Jl. Raya Bandara BIJB No. 1, Kertajati', '0233-883001', 'sman1kertajati@sch.id', NULL, 'Drs. H. Tarkim', 'Kepala Sekolah', '08123456182', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(249, 'SKL-MJL-KTJ-02', 'SMK Negeri 1 Kertajati', 'A', 34, 101, 'Kertajati', 'Jl. Kertawinangun No. 15, Kertajati', '0233-883002', 'smkn1kertajati@sch.id', NULL, 'Ir. Suherman', 'Waka Hubin', '08123456183', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(250, 'SKL-MJL-LMS-01', 'SMA Negeri 1 Lemahsugih', 'B', 33, 102, 'Lemahsugih', 'Jl. Padarek No. 1, Lemahsugih', '0233-834001', 'sman1lemahsugih@sch.id', NULL, 'Drs. H. Mulyadi', 'Kepala Sekolah', '08123456184', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(251, 'SKL-MJL-LMS-02', 'SMK Negeri 1 Lemahsugih', 'B', 34, 102, 'Lemahsugih', 'Jl. Borogojol No. 7, Lemahsugih', '0233-834002', 'smkn1lemahsugih@sch.id', NULL, 'Dra. Hj. Aminah', 'Guru BK', '08123456185', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(252, 'SKL-MJL-LWM-01', 'SMA Negeri 1 Leuwimunding', 'B', 33, 103, 'Leuwimunding', 'Jl. Raya Leuwimunding No. 25, Leuwimunding', '0233-884001', 'sman1leuwimunding@sch.id', NULL, 'Drs. H. Suharto', 'Kepala Sekolah', '08123456186', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(253, 'SKL-MJL-LWM-02', 'SMK Negeri 1 Leuwimunding', 'B', 34, 103, 'Leuwimunding', 'Jl. Parungjaya No. 8, Leuwimunding', '0233-884002', 'smkn1leuwimunding@sch.id', NULL, 'Dedi Iskandar, S.T', 'Guru BK', '08123456187', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(254, 'SKL-MJL-LGG-01', 'SMA Negeri 1 Ligung', 'B', 33, 104, 'Ligung', 'Jl. Raya Ligung No. 19, Ligung', '0233-885001', 'sman1ligung@sch.id', NULL, 'Drs. H. Maman', 'Kepala Sekolah', '08123456188', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(255, 'SKL-MJL-LGG-02', 'SMK Negeri 1 Ligung', 'B', 34, 104, 'Ligung', 'Jl. Sukawera No. 11, Ligung', '0233-885002', 'smkn1ligung@sch.id', NULL, 'Agus Salim, S.Pd', 'Hubinmas', '08123456189', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(256, 'SKL-MJL-MJA-01', 'SMA Negeri 1 Maja', 'A', 33, 105, 'Maja', 'Jl. Pasukan Sindangkasih No. 20, Maja', '0233-828555', 'sman1maja@sch.id', NULL, 'Drs. H. Rasidi', 'Kepala Sekolah', '08123456190', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(257, 'SKL-MJL-MJA-02', 'SMK Negeri 1 Maja', 'B', 34, 105, 'Maja', 'Jl. Wanahayu No. 8, Maja', '0233-828556', 'smkn1maja@sch.id', NULL, 'Asep Gunawan, S.Pd', 'Guru BK', '08123456191', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(258, 'SKL-MJL-MJL-01', 'SMA Negeri 1 Majalengka', 'A', 33, 22, 'Majalengka', 'Jl. K.H. Abdul Halim No. 113, Majalengka', '0233-281456', 'info@sman1majalengka.sch.id', NULL, 'Drs. H. Moh. Ali, M.Pd', 'Kepala Sekolah', '08123456042', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(259, 'SKL-MJL-MJL-02', 'SMA Negeri 2 Majalengka', 'A', 33, 22, 'Majalengka', 'Jl. Ahmad Yani No. 2, Majalengka', '0233-281789', 'info@sman2majalengka.sch.id', NULL, 'Dra. Hj. Titin, M.M', 'Guru BK', '08123456049', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(260, 'SKL-MJL-MJL-03', 'SMK Negeri 1 Majalengka', 'A', 34, 22, 'Majalengka', 'Jl. Raya Tonjong-Pinangraja No. 55', '0233-281999', 'smkn1majalengka@sch.id', NULL, 'Dr. H. Ruspendi', 'Kepala Sekolah', '08123456192', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(261, 'SKL-MJL-MJL-04', 'MAN 1 Majalengka', 'A', 35, 22, 'Majalengka', 'Jl. Siti Armilah No. 1, Majalengka', '0233-282001', 'man1majalengka@kemenag.go.id', NULL, 'Drs. H. Muhaimin', 'Kepala Madrasah', '08123456193', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(262, 'SKL-MJL-MLS-01', 'SMA Negeri 1 Malausma', 'B', 33, 106, 'Malausma', 'Jl. Cirawa No. 5, Malausma', '0233-835001', 'sman1malausma@sch.id', NULL, 'Drs. H. Sukmana', 'Kepala Sekolah', '08123456194', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(263, 'SKL-MJL-MLS-02', 'SMK Negeri 1 Malausma', 'B', 34, 106, 'Malausma', 'Jl. Lebakwangi No. 12, Malausma', '0233-835002', 'smkn1malausma@sch.id', NULL, 'Suherman, S.T', 'Guru BK', '08123456195', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(264, 'SKL-MJL-PYK-01', 'SMA Negeri 1 Panyingkiran', 'B', 33, 107, 'Panyingkiran', 'Jl. Raya Panyingkiran No. 12, Panyingkiran', '0233-283001', 'sman1panyingkiran@sch.id', NULL, 'Drs. H. Solihin', 'Kepala Sekolah', '08123456196', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(265, 'SKL-MJL-PYK-02', 'SMK Negeri 1 Panyingkiran', 'B', 34, 107, 'Panyingkiran', 'Jl. Kertabasuki No. 8, Panyingkiran', '0233-283002', 'smkn1panyingkiran@sch.id', NULL, 'Budi Santoso, S.Pd', 'Guru BK', '08123456197', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(266, 'SKL-MJL-PLS-01', 'SMA Negeri 1 Palasah', 'B', 33, 108, 'Palasah', 'Jl. Raya Majalengka-Cirebon KM 18, Palasah', '0233-886001', 'sman1palasah@sch.id', NULL, 'Drs. H. Tarkim', 'Kepala Sekolah', '08123456198', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(267, 'SKL-MJL-PLS-02', 'SMK Negeri 1 Palasah', 'A', 34, 108, 'Palasah', 'Jl. Raya Weragati No. 2, Palasah', '0233-886002', 'smkn1palasah@sch.id', NULL, 'Ir. Hartono', 'Waka Hubin', '08123456199', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(268, 'SKL-MJL-RJG-01', 'SMA Negeri 1 Rajagaluh', 'A', 33, 25, 'Rajagaluh', 'Jl. Mutiara No. 1, Rajagaluh', '0233-510111', 'sman1rajagaluh@sch.id', NULL, 'Drs. H. Encep Suherman', 'Kepala Sekolah', '08123456047', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(269, 'SKL-MJL-RJG-02', 'SMK Negeri 1 Rajagaluh', 'B', 34, 25, 'Rajagaluh', 'Jl. Tanjungsari No. 14, Rajagaluh', '0233-510222', 'smkn1rajagaluh@sch.id', NULL, 'Dedi Supriadi, S.Pd', 'Guru BK', '08123456048', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(270, 'SKL-MJL-SDG-01', 'SMA Negeri 1 Sindang Majalengka', 'B', 33, 109, 'Sindang', 'Jl. Sindang-Garawastu No. 1, Sindang', '0233-828888', 'sman1sindangmjl@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456200', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(271, 'SKL-MJL-SDG-02', 'SMK Negeri 1 Sindang Majalengka', 'B', 34, 109, 'Sindang', 'Jl. Pasirhanja No. 5, Sindang', '0233-828889', 'smkn1sindangmjl@sch.id', NULL, 'Suherman, S.T', 'Hubinmas', '08123456201', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(272, 'SKL-MJL-SDW-01', 'SMA Negeri 1 Sindangwangi', 'B', 33, 110, 'Sindangwangi', 'Jl. Raya Bantaragung No. 3, Sindangwangi', '0233-511001', 'sman1sindangwangi@sch.id', NULL, 'Drs. H. Sukirno', 'Kepala Sekolah', '08123456202', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(273, 'SKL-MJL-SDW-02', 'SMK Negeri 1 Sindangwangi', 'B', 34, 110, 'Sindangwangi', 'Jl. Jerukleueut No. 8, Sindangwangi', '0233-511002', 'smkn1sindangwangi@sch.id', NULL, 'Ahmad Fauzi, S.Pd', 'Guru BK', '08123456203', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(274, 'SKL-MJL-SKH-01', 'SMA Negeri 1 Sukahaji', 'B', 33, 111, 'Sukahaji', 'Jl. Raya Sukahaji No. 12, Sukahaji', '0233-827001', 'sman1sukahaji@sch.id', NULL, 'Drs. H. Ade Sunardi', 'Kepala Sekolah', '08123456204', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(275, 'SKL-MJL-SKH-02', 'SMK Negeri 1 Sukahaji', 'B', 34, 111, 'Sukahaji', 'Jl. Cikoneng No. 5, Sukahaji', '0233-827002', 'smkn1sukahaji@sch.id', NULL, 'Bambang Irawan, S.T', 'Hubinmas', '08123456205', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(276, 'SKL-MJL-SBJ-01', 'SMA Negeri 1 Sumberjaya', 'A', 33, 112, 'Sumberjaya', 'Jl. Panjalin Kidul No. 15, Sumberjaya', '0233-887001', 'sman1sumberjaya@sch.id', NULL, 'Drs. H. Rasidi', 'Kepala Sekolah', '08123456206', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(277, 'SKL-MJL-SBJ-02', 'SMK Negeri 1 Sumberjaya', 'B', 34, 112, 'Sumberjaya', 'Jl. Cidenok No. 7, Sumberjaya', '0233-887002', 'smkn1sumberjaya@sch.id', NULL, 'Suherman, S.Pd', 'Guru BK', '08123456207', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(278, 'SKL-MJL-TLG-01', 'SMA Negeri 1 Talaga', 'A', 33, 113, 'Talaga', 'Jl. Gajah Mada No. 4, Talaga', '0233-836001', 'sman1talaga@sch.id', NULL, 'Drs. H. Tarkim, M.Pd', 'Kepala Sekolah', '08123456208', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(279, 'SKL-MJL-TLG-02', 'SMK Negeri 1 Talaga', 'A', 34, 113, 'Talaga', 'Jl. Talaga-Bantarujeg KM 1, Talaga', '0233-836002', 'smkn1talaga@sch.id', NULL, 'Ir. Bambang Sugiarto', 'Waka Hubin', '08123456209', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(280, 'SKL-KNG-CWG-01', 'SMA Negeri 1 Ciawigebang', 'A', 33, 114, 'Ciawigebang', 'Jl. Siliwangi No. 106, Ciawigebang', '0232-878001', 'sman1ciawigebang@sch.id', NULL, 'Drs. H. Rasidi, M.Pd', 'Kepala Sekolah', '08123456210', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(281, 'SKL-KNG-CWG-02', 'SMK Negeri 1 Ciawigebang', 'A', 34, 114, 'Ciawigebang', 'Jl. Raya Sidaraja No. 5, Ciawigebang', '0232-878002', 'smkn1ciawigebang@sch.id', NULL, 'Drs. H. Maman', 'Waka Hubinmas', '08123456211', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(282, 'SKL-KNG-CWG-03', 'MAN 1 Kuningan', 'A', 35, 114, 'Ciawigebang', 'Jl. Siliwangi No. 120, Ciawigebang', '0232-878003', 'man1kuningan@kemenag.go.id', NULL, 'Drs. H. Muhaimin', 'Kepala Madrasah', '08123456212', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(283, 'SKL-KNG-CBR-01', 'SMA Negeri 1 Cibeureum', 'B', 33, 115, 'Cibeureum', 'Jl. Raya Cimara No. 19, Cibeureum', '0232-879001', 'sman1cibeureum@sch.id', NULL, 'Drs. H. Solihin', 'Kepala Sekolah', '08123456213', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(284, 'SKL-KNG-CBR-02', 'SMK Negeri 1 Cibeureum', 'B', 34, 115, 'Cibeureum', 'Jl. Sukadana No. 4, Cibeureum', '0232-879002', 'smkn1cibeureum@sch.id', NULL, 'Suherman, S.T', 'Guru BK', '08123456214', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(285, 'SKL-KNG-CBB-01', 'SMA Negeri 1 Cibingbin', 'A', 33, 116, 'Cibingbin', 'Jl. Raya Sukamaju No. 1, Cibingbin', '0232-880001', 'sman1cibingbin@sch.id', NULL, 'Drs. H. Sukirno', 'Kepala Sekolah', '08123456215', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(286, 'SKL-KNG-CBB-02', 'SMK Negeri 1 Cibingbin', 'B', 34, 116, 'Cibingbin', 'Jl. Sindangjawa No. 7, Cibingbin', '0232-880002', 'smkn1cibingbin@sch.id', NULL, 'Ahmad Rifa\'i, S.Pd', 'Guru BK', '08123456216', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(287, 'SKL-KNG-CDH-01', 'SMA Negeri 1 Cidahu', 'B', 33, 117, 'Cidahu', 'Jl. Raya Cidahu No. 18, Cidahu', '0232-881001', 'sman1cidahu@sch.id', NULL, 'Drs. H. Tarkim', 'Kepala Sekolah', '08123456217', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(288, 'SKL-KNG-CDH-02', 'SMK Negeri 1 Cidahu', 'B', 34, 117, 'Cidahu', 'Jl. Bunder No. 4, Cidahu', '0232-881002', 'smkn1cidahu@sch.id', NULL, 'Budi Santoso, S.T', 'Guru BK', '08123456218', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(289, 'SKL-KNG-CGM-01', 'SMA Negeri 1 Cigandamekar', 'B', 33, 118, 'Cigandamekar', 'Jl. Raya Bunigeulis No. 1, Cigandamekar', '0232-614001', 'sman1cigandamekar@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456219', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(290, 'SKL-KNG-CGM-02', 'SMK Negeri 1 Cigandamekar', 'B', 34, 118, 'Cigandamekar', 'Jl. Koreak No. 8, Cigandamekar', '0232-614002', 'smkn1cigandamekar@sch.id', NULL, 'Dra. Hj. Nunung', 'Guru BK', '08123456220', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(291, 'SKL-KNG-CGG-01', 'SMA Negeri 1 Cigugur', 'A', 33, 119, 'Cigugur', 'Jl. Sukamulya No. 4, Cigugur', '0232-871555', 'sman1cigugur@sch.id', NULL, 'Drs. H. Ade Sunardi', 'Kepala Sekolah', '08123456221', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(292, 'SKL-KNG-CGG-02', 'SMK Negeri 1 Cigugur', 'B', 34, 119, 'Cigugur', 'Jl. Cigugur-Palutungan KM 1, Cigugur', '0232-871556', 'smkn1cigugur@sch.id', NULL, 'Suherman, S.T', 'Hubinmas', '08123456222', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(293, 'SKL-KNG-CLB-01', 'SMA Negeri 1 Cilebak', 'B', 33, 120, 'Cilebak', 'Jl. Raya Cilebak No. 1, Cilebak', '0232-882001', 'sman1cilebak@sch.id', NULL, 'Drs. H. Mulyadi', 'Kepala Sekolah', '08123456223', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(294, 'SKL-KNG-CLB-02', 'SMK Negeri 1 Cilebak', 'B', 34, 120, 'Cilebak', 'Jl. Legokherang No. 5, Cilebak', '0232-882002', 'smkn1cilebak@sch.id', NULL, 'Asep Gunawan, S.Pd', 'Guru BK', '08123456224', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(295, 'SKL-KNG-CLM-01', 'SMA Negeri 1 Cilimus', 'A', 33, 28, 'Cilimus', 'Jl. Raya Cilimus No. 238, Cilimus', '0232-614120', 'info@sman1cilimus.sch.id', NULL, 'Drs. H. Jaja Subagja, M.Pd', 'Kepala Sekolah', '08123456053', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(296, 'SKL-KNG-CLM-02', 'SMK Negeri 1 Cilimus', 'A', 34, 28, 'Cilimus', 'Jl. Bandorasa No. 1, Cilimus', '0232-614567', 'smkn1cilimus@sch.id', NULL, 'Dr. H. Ruspendi', 'Kepala Sekolah', '08123456054', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(297, 'SKL-KNG-CLM-03', 'SMA IT Husnul Khotimah', 'A', 33, 28, 'Cilimus', 'Jl. Maniskidul, Cilimus', '0232-614888', 'info@husnulkhotimah.sch.id', NULL, 'Ust. M. Syafei, M.Pd.I', 'Kepala Sekolah', '08123456225', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(298, 'SKL-KNG-CMH-01', 'SMA Negeri 1 Cimahi Kuningan', 'B', 33, 121, 'Cimahi', 'Jl. Raya Cimahi No. 12, Cimahi', '0232-883001', 'sman1cimahi@sch.id', NULL, 'Drs. H. Kusnadi', 'Kepala Sekolah', '08123456226', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(299, 'SKL-KNG-CMH-02', 'SMK Negeri 1 Cimahi Kuningan', 'B', 34, 121, 'Cimahi', 'Jl. Cikeusal No. 4, Cimahi', '0232-883002', 'smkn1cimahi@sch.id', NULL, 'Dedi Iskandar, S.T', 'Hubinmas', '08123456227', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(300, 'SKL-KNG-CNR-01', 'SMA Negeri 1 Ciniru', 'B', 33, 122, 'Ciniru', 'Jl. Raya Ciniru No. 9, Ciniru', '0232-872001', 'sman1ciniru@sch.id', NULL, 'Drs. H. Suharto', 'Kepala Sekolah', '08123456228', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(301, 'SKL-KNG-CNR-02', 'SMK Negeri 1 Ciniru', 'B', 34, 122, 'Ciniru', 'Jl. Pinara No. 3, Ciniru', '0232-872002', 'smkn1ciniru@sch.id', NULL, 'Budi Waluyo, S.Pd', 'Guru BK', '08123456229', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(302, 'SKL-KNG-CPC-01', 'SMA Negeri 1 Cipicung', 'B', 33, 123, 'Cipicung', 'Jl. Raya Sukamukti No. 7, Cipicung', '0232-873001', 'sman1cipicung@sch.id', NULL, 'Drs. H. Maman', 'Kepala Sekolah', '08123456230', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(303, 'SKL-KNG-CPC-02', 'SMK Negeri 1 Cipicung', 'B', 34, 123, 'Cipicung', 'Jl. Pamulihan No. 2, Cipicung', '0232-873002', 'smkn1cipicung@sch.id', NULL, 'Suwandi, S.T', 'Guru BK', '08123456231', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(304, 'SKL-KNG-CWR-01', 'SMA Negeri 1 Ciwaru', 'B', 33, 124, 'Ciwaru', 'Jl. Raya Ciwaru No. 14, Ciwaru', '0232-884001', 'sman1ciwaru@sch.id', NULL, 'Drs. H. Solihin', 'Kepala Sekolah', '08123456232', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(305, 'SKL-KNG-CWR-02', 'SMK Negeri 1 Ciwaru', 'B', 34, 124, 'Ciwaru', 'Jl. Baok No. 7, Ciwaru', '0232-884002', 'smkn1ciwaru@sch.id', NULL, 'Dra. Hj. Aminah', 'Guru BK', '08123456233', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(306, 'SKL-KNG-DRM-01', 'SMA Negeri 1 Darma', 'A', 33, 125, 'Darma', 'Jl. Raya Darma No. 10, Darma', '0232-874001', 'sman1darma@sch.id', NULL, 'Drs. H. Rasidi', 'Kepala Sekolah', '08123456234', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(307, 'SKL-KNG-DRM-02', 'SMK Negeri 1 Darma', 'B', 34, 125, 'Darma', 'Jl. Sakerta Timur No. 5, Darma', '0232-874002', 'smkn1darma@sch.id', NULL, 'Suherman, S.T', 'Hubinmas', '08123456235', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(308, 'SKL-KNG-GRW-01', 'SMA Negeri 1 Garawangi', 'A', 33, 126, 'Garawangi', 'Jl. Raya Lengkong No. 5, Garawangi', '0232-875001', 'sman1garawangi@sch.id', NULL, 'Drs. H. Sukirno, M.Pd', 'Kepala Sekolah', '08123456236', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(309, 'SKL-KNG-GRW-02', 'SMK Negeri 1 Garawangi', 'B', 34, 126, 'Garawangi', 'Jl. Purwasari No. 2, Garawangi', '0232-875002', 'smkn1garawangi@sch.id', NULL, 'Ahmad Fauzi, S.Pd', 'Guru BK', '08123456237', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(310, 'SKL-KNG-HTR-01', 'SMA Negeri 1 Hantara', 'B', 33, 127, 'Hantara', 'Jl. Raya Hantara No. 15, Hantara', '0232-876001', 'sman1hantara@sch.id', NULL, 'Drs. H. Tarkim', 'Kepala Sekolah', '08123456238', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(311, 'SKL-KNG-HTR-02', 'SMK Negeri 1 Hantara', 'B', 34, 127, 'Hantara', 'Jl. Pasiragung No. 6, Hantara', '0232-876002', 'smkn1hantara@sch.id', NULL, 'Bambang Irawan, S.T', 'Guru BK', '08123456239', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(312, 'SKL-KNG-JLX-01', 'SMA Negeri 1 Jalaksana', 'A', 33, 30, 'Jalaksana', 'Jl. Raya Padamenak No. 1, Jalaksana', '0232-613001', 'sman1jalaksana@sch.id', NULL, 'Drs. H. Ruspendi', 'Kepala Sekolah', '08123456059', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(313, 'SKL-KNG-JLX-02', 'SMK Negeri 1 Jalaksana', 'B', 34, 30, 'Jalaksana', 'Jl. Sembawa No. 10, Jalaksana', '0232-613002', 'smkn1jalaksana@sch.id', NULL, 'Dra. Hj. Nunung', 'Guru BK', '08123456240', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(314, 'SKL-KNG-JLX-03', 'SMA IT Al-Multazam', 'A', 33, 30, 'Jalaksana', 'Jl. Maniskidul - Jalaksana KM 1', '0232-613888', 'info@almultazam.sch.id', NULL, 'Ust. H. Budi, Lc', 'Kepala Sekolah', '08123456241', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(315, 'SKL-KNG-JPR-01', 'SMA Negeri 1 Japara', 'B', 33, 128, 'Japara', 'Jl. Raya Japara No. 2, Japara', '0232-615001', 'sman1japara@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456242', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(316, 'SKL-KNG-JPR-02', 'SMK Negeri 1 Japara', 'A', 34, 128, 'Japara', 'Jl. Cengal No. 11, Japara', '0232-615002', 'smkn1japara@sch.id', NULL, 'Ir. Suherman', 'Waka Hubin', '08123456243', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(317, 'SKL-KNG-KDG-01', 'SMA Negeri 1 Kadugede', 'A', 33, 129, 'Kadugede', 'Jl. Raya Kadugede No. 47, Kadugede', '0232-871666', 'sman1kadugede@sch.id', NULL, 'Drs. H. Rasidi', 'Kepala Sekolah', '08123456244', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(318, 'SKL-KNG-KDG-02', 'SMK Negeri 1 Kadugede', 'B', 34, 129, 'Kadugede', 'Jl. Bayuning No. 9, Kadugede', '0232-871667', 'smkn1kadugede@sch.id', NULL, 'Suwandi, S.Pd', 'Guru BK', '08123456245', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(319, 'SKL-KNG-KLM-01', 'SMA Negeri 1 Kalimanggis', 'B', 33, 130, 'Kalimanggis', 'Jl. Raya Cipancur No. 5, Kalimanggis', '0232-877001', 'sman1kalimanggis@sch.id', NULL, 'Drs. H. Sukirno', 'Kepala Sekolah', '08123456246', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(320, 'SKL-KNG-KLM-02', 'SMK Negeri 1 Kalimanggis', 'B', 34, 130, 'Kalimanggis', 'Jl. Wanasaraya No. 3, Kalimanggis', '0232-877002', 'smkn1kalimanggis@sch.id', NULL, 'Ahmad Rifa\'i, S.T', 'Guru BK', '08123456247', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(321, 'SKL-KNG-KRC-01', 'SMA Negeri 1 Karangkancana', 'B', 33, 131, 'Karangkancana', 'Jl. Raya Margacina No. 6, Karangkancana', '0232-885001', 'sman1karangkancana@sch.id', NULL, 'Drs. H. Mulyadi', 'Kepala Sekolah', '08123456248', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(322, 'SKL-KNG-KRC-02', 'SMK Negeri 1 Karangkancana', 'B', 34, 131, 'Karangkancana', 'Jl. Segong No. 2, Karangkancana', '0232-885002', 'smkn1karangkancana@sch.id', NULL, 'Suherman, S.Pd', 'Guru BK', '08123456249', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(323, 'SKL-KNG-KRM-01', 'SMA Negeri 1 Kramatmulya', 'B', 33, 132, 'Kramatmulya', 'Jl. Raya Kramatmulya No. 88, Kramatmulya', '0232-871777', 'sman1kramatmulya@sch.id', NULL, 'Drs. H. Ade Sunardi', 'Kepala Sekolah', '08123456250', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(324, 'SKL-KNG-KRM-02', 'SMK Negeri 1 Kramatmulya', 'B', 34, 132, 'Kramatmulya', 'Jl. Kalapagunung No. 5, Kramatmulya', '0232-871778', 'smkn1kramatmulya@sch.id', NULL, 'Dedi Iskandar, S.T', 'Hubinmas', '08123456251', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(325, 'SKL-KNG-KNG-01', 'SMA Negeri 1 Kuningan', 'A', 33, 27, 'Kuningan', 'Jl. Siliwangi No. 55, Kuningan', '0232-871020', 'info@sman1kuningan.sch.id', NULL, 'Drs. H. Agus Supriyadi', 'Kepala Sekolah', '08123456050', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(326, 'SKL-KNG-KNG-02', 'SMA Negeri 2 Kuningan', 'A', 33, 27, 'Kuningan', 'Jl. Aruji Kartawinata No. 16, Kuningan', '0232-871030', 'info@sman2kuningan.sch.id', NULL, 'Dra. Hj. Lina Marlina', 'Guru BK', '08123456051', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(327, 'SKL-KNG-KNG-03', 'SMA Negeri 3 Kuningan', 'A', 33, 27, 'Kuningan', 'Jl. Siliwangi No. 13, Kuningan', '0232-871040', 'info@sman3kuningan.sch.id', NULL, 'Drs. H. Maman Suratman', 'Kepala Sekolah', '08123456052', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(328, 'SKL-KNG-KNG-04', 'SMK Negeri 1 Kuningan', 'A', 34, 27, 'Kuningan', 'Jl. Sukamulya No. 8, Kuningan', '0232-871050', 'smkn1kuningan@sch.id', NULL, 'Dr. H. Ruspendi', 'Kepala Sekolah', '08123456252', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(329, 'SKL-KNG-KNG-05', 'SMK Negeri 2 Kuningan', 'A', 34, 27, 'Kuningan', 'Jl. RE Martadinata No. 2, Kuningan', '0232-871060', 'smkn2kuningan@sch.id', NULL, 'Ir. Suherman', 'Waka Hubinmas', '08123456253', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(330, 'SKL-KNG-KNG-06', 'SMK Negeri 3 Kuningan', 'A', 34, 27, 'Kuningan', 'Jl. Raya Cirendang No. 1, Kuningan', '0232-871070', 'smkn3kuningan@sch.id', NULL, 'Drs. H. Kusnadi', 'Waka Hubin', '08123456254', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(331, 'SKL-KNG-KNG-07', 'MAN 2 Kuningan', 'A', 35, 27, 'Kuningan', 'Jl. Pramuka No. 12, Kuningan', '0232-871080', 'man2kuningan@kemenag.go.id', NULL, 'Drs. H. Muhaimin', 'Kepala Madrasah', '08123456255', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(332, 'SKL-KNG-LBW-01', 'SMA Negeri 1 Lebakwangi', 'A', 33, 133, 'Lebakwangi', 'Jl. Raya Cinagara No. 1, Lebakwangi', '0232-877555', 'sman1lebakwangi@sch.id', NULL, 'Drs. H. Rasidi', 'Kepala Sekolah', '08123456256', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(333, 'SKL-KNG-LBW-02', 'SMK Negeri 1 Lebakwangi', 'B', 34, 133, 'Lebakwangi', 'Jl. Mekarwangi No. 11, Lebakwangi', '0232-877556', 'smkn1lebakwangi@sch.id', NULL, 'Suwandi, S.T', 'Guru BK', '08123456257', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(334, 'SKL-KNG-LRG-01', 'SMA Negeri 1 Luragung', 'A', 33, 29, 'Luragung', 'Jl. Luragung-Cidahu No. 10, Luragung', '0232-876111', 'sman1luragung@sch.id', NULL, 'Drs. H. Kusen, M.Pd', 'Kepala Sekolah', '08123456055', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(335, 'SKL-KNG-LRG-02', 'SMK Negeri 1 Luragung', 'A', 34, 29, 'Luragung', 'Jl. Raya Luragung No. 45, Luragung', '0232-876222', 'smkn1luragung@sch.id', NULL, 'Drs. H. Taufik Hidayat', 'Waka Hubinmas', '08123456056', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(336, 'SKL-KNG-MLB-01', 'SMA Negeri 1 Maleber', 'B', 33, 134, 'Maleber', 'Jl. Raya Maleber No. 12, Maleber', '0232-878555', 'sman1maleber@sch.id', NULL, 'Drs. H. Sukirno', 'Kepala Sekolah', '08123456258', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(337, 'SKL-KNG-MLB-02', 'SMK Negeri 1 Maleber', 'B', 34, 134, 'Maleber', 'Jl. Galaherang No. 5, Maleber', '0232-878556', 'smkn1maleber@sch.id', NULL, 'Asep Gunawan, S.Pd', 'Guru BK', '08123456259', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(338, 'SKL-KNG-MDR-01', 'SMA Negeri 1 Mandirancan', 'A', 33, 135, 'Mandirancan', 'Jl. Siliwangi No. 99, Mandirancan', '0232-615555', 'sman1mandirancan@sch.id', NULL, 'Drs. H. Tarkim', 'Kepala Sekolah', '08123456260', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(339, 'SKL-KNG-MDR-02', 'SMK Negeri 1 Mandirancan', 'B', 34, 135, 'Mandirancan', 'Jl. Kertawinangun No. 3, Mandirancan', '0232-615556', 'smkn1mandirancan@sch.id', NULL, 'Dra. Hj. Nunung', 'Guru BK', '08123456261', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(340, 'SKL-KNG-NSH-01', 'SMA Negeri 1 Nusaherang', 'B', 33, 136, 'Nusaherang', 'Jl. Raya Nusaherang No. 11, Nusaherang', '0232-871888', 'sman1nusaherang@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456262', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(341, 'SKL-KNG-NSH-02', 'SMK Negeri 1 Nusaherang', 'B', 34, 136, 'Nusaherang', 'Jl. Haurkuning No. 4, Nusaherang', '0232-871889', 'smkn1nusaherang@sch.id', NULL, 'Suherman, S.T', 'Guru BK', '08123456263', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(342, 'SKL-KNG-PCL-01', 'SMA Negeri 1 Pancalang', 'B', 33, 137, 'Pancalang', 'Jl. Raya Pancalang No. 8, Pancalang', '0232-616001', 'sman1pancalang@sch.id', NULL, 'Drs. H. Ade Sunardi', 'Kepala Sekolah', '08123456264', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(343, 'SKL-KNG-PCL-02', 'SMK Negeri 1 Pancalang', 'B', 34, 137, 'Pancalang', 'Jl. Sarewu No. 2, Pancalang', '0232-616002', 'smkn1pancalang@sch.id', NULL, 'Ahmad Rifa\'i, S.Pd', 'Guru BK', '08123456265', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(344, 'SKL-KNG-PSW-01', 'SMA Negeri 1 Pasawahan Kuningan', 'A', 33, 138, 'Pasawahan', 'Jl. Raya Pasawahan No. 14, Pasawahan', '0232-617001', 'sman1pasawahankng@sch.id', NULL, 'Drs. H. Solihin', 'Kepala Sekolah', '08123456266', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(345, 'SKL-KNG-PSW-02', 'SMK Negeri 1 Pasawahan Kuningan', 'B', 34, 138, 'Pasawahan', 'Jl. Padamatang No. 5, Pasawahan', '0232-617002', 'smkn1pasawahankng@sch.id', NULL, 'Budi Santoso, S.T', 'Hubinmas', '08123456267', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(346, 'SKL-KNG-SLJ-01', 'SMA Negeri 1 Selajambe', 'B', 33, 139, 'Selajambe', 'Jl. Raya Selajambe No. 8, Selajambe', '0232-872555', 'sman1selajambe@sch.id', NULL, 'Drs. H. Rasidi', 'Kepala Sekolah', '08123456268', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(347, 'SKL-KNG-SLJ-02', 'SMK Negeri 1 Selajambe', 'B', 34, 139, 'Selajambe', 'Jl. Cantilan No. 2, Selajambe', '0232-872556', 'smkn1selajambe@sch.id', NULL, 'Suwandi, S.Pd', 'Guru BK', '08123456269', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(348, 'SKL-KNG-SDA-01', 'SMA Negeri 1 Sindangagung', 'B', 33, 140, 'Sindangagung', 'Jl. Raya Kertawangunan No. 12, Sindangagung', '0232-871999', 'sman1sindangagung@sch.id', NULL, 'Drs. H. Sukirno', 'Kepala Sekolah', '08123456270', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(349, 'SKL-KNG-SDA-02', 'SMK Negeri 1 Sindangagung', 'B', 34, 140, 'Sindangagung', 'Jl. Babakanreuma No. 4, Sindangagung', '0232-871998', 'smkn1sindangagung@sch.id', NULL, 'Dedi Iskandar, S.T', 'Hubinmas', '08123456271', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(350, 'SKL-KNG-SBG-01', 'SMA Negeri 1 Subang Kuningan', 'B', 33, 141, 'Subang', 'Jl. Raya Subang No. 21, Subang', '0232-873555', 'sman1subangkng@sch.id', NULL, 'Drs. H. Mulyadi', 'Kepala Sekolah', '08123456272', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(351, 'SKL-KNG-SBG-02', 'SMK Negeri 1 Subang Kuningan', 'B', 34, 141, 'Subang', 'Jl. Situgede No. 4, Subang', '0232-873556', 'smkn1subangkng@sch.id', NULL, 'Asep Gunawan, S.Pd', 'Guru BK', '08123456273', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(352, 'SKL-BBS-BBS-01', 'SMA Negeri 1 Brebes', 'A', 33, 145, 'Brebes', 'Jl. Dr. Setiabudi No. 11, Brebes', '0283-671001', 'sman1brebes@sch.id', NULL, 'Drs. H. Samsudin', 'Kepala Sekolah', '08123456060', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(353, 'SKL-BBS-BBS-02', 'SMA Negeri 2 Brebes', 'A', 33, 145, 'Brebes', 'Jl. Ahmad Yani No. 77, Brebes', '0283-671002', 'sman2brebes@sch.id', NULL, 'Dra. Hj. Sri Lestari', 'Guru BK', '08123456274', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(354, 'SKL-BBS-BBS-03', 'SMK Negeri 1 Brebes', 'A', 34, 145, 'Brebes', 'Jl. Yos Sudarso No. 8, Brebes', '0283-671022', 'smkn1brebes@sch.id', NULL, 'Bambang Sugiharto, S.T', 'Hubinmas', '08123456061', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(355, 'SKL-BBS-JTB-01', 'SMA Negeri 1 Jatibarang Brebes', 'A', 33, 148, 'Jatibarang', 'Jl. Raya Barat Jatibarang, Brebes', '0283-672001', 'sman1jatibarangbbs@sch.id', NULL, 'Drs. H. Suwarno', 'Kepala Sekolah', '08123456275', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(356, 'SKL-BBS-BMY-01', 'SMA Negeri 1 Bumiayu', 'A', 33, 147, 'Bumiayu', 'Jl. Pangeran Diponegoro No. 12, Bumiayu', '0283-432001', 'sman1bumiayu@sch.id', NULL, 'Drs. H. Rasidi, M.Pd', 'Kepala Sekolah', '08123456276', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(357, 'SKL-BBS-BMY-02', 'SMK Negeri 1 Bumiayu', 'A', 34, 147, 'Bumiayu', 'Jl. Lingkar Luar Bumiayu KM 2', '0283-432002', 'smkn1bumiayu@sch.id', NULL, 'Ir. Suherman', 'Hubinmas', '08123456277', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(358, 'SKL-BBS-KTG-01', 'SMA Negeri 1 Ketanggungan', 'A', 33, 149, 'Ketanggungan', 'Jl. Jend. Sudirman No. 4, Ketanggungan', '0283-673001', 'sman1ketanggungan@sch.id', NULL, 'Drs. H. Tarkim', 'Kepala Sekolah', '08123456278', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(359, 'SKL-BBS-TJG-01', 'SMA Negeri 1 Tanjung Brebes', 'A', 33, 157, 'Tanjung', 'Jl. Cemara No. 24, Tanjung', '0283-674001', 'sman1tanjung@sch.id', NULL, 'Drs. H. Mulyono', 'Kepala Sekolah', '08123456279', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL);
INSERT INTO `sekolahs` (`id`, `kode`, `nama`, `tier`, `kategori_id`, `wilayah_id`, `kecamatan`, `alamat`, `telepon`, `email`, `website`, `pic_name`, `pic_jabatan`, `pic_phone`, `status`, `sales_id`, `created_at`, `updated_at`, `lat`, `lng`) VALUES
(360, 'SKL-BBS-BLK-01', 'SMA Negeri 1 Bulakamba', 'A', 33, 146, 'Bulakamba', 'Jl. Raya Bulakamba KM 8, Brebes', '0283-675001', 'sman1bulakamba@sch.id', NULL, 'Drs. H. Sukirno', 'Kepala Sekolah', '08123456280', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL),
(361, 'SKL-BBS-LSR-01', 'SMA Negeri 1 Losari Brebes', 'A', 33, 152, 'Losari', 'Jl. Jenderal Sudirman No. 1, Losari Brebes', '0283-676001', 'sman1losaribbs@sch.id', NULL, 'Drs. H. Solihin', 'Kepala Sekolah', '08123456281', 'Aktif', NULL, '2026-09-29 15:06:52', '2026-09-29 15:06:52', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('BzocGHIEkIuylm52ZBiBF6hhldyaGzY0FKKTj7os', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', 'eyJfdG9rZW4iOiJLTUM4RmI3VXFrRmxEaFVxbnBJYmxjakxQZmhBUzREOEdwQ1cyZTNOIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==', 1790746341),
('S0aMo05PxfyH9RVE6TbhiiLejnR7pZz8uzAbGb4w', 6, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJISzI5MEsyektnWHM5VmlaMU5pUnhKOUh6cDlUVTByb29mRDg4MlYyIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL3NhbGVzXC9wcm9zcGVrIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvY3NcL3ZlcmlmaWthc2kiLCJyb3V0ZSI6ImNzLnZlcmlmaWthc2kuaW5kZXgifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6NiwidXNlcl9yb2xlIjoiY3MifQ==', 1790749799),
('WII54AcrBml65Y3XRoGCYEaFMceqGbvtyKGxBcip', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', 'eyJfdG9rZW4iOiJYRGFJenJ4cmZKRWJUV1ZZclZGSVpORXRxbGJSVkl5T2Q3RVQ3MFFsIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL3NhbGVzXC9wZW1iYXlhcmFuP3BhZ2U9MSIsInJvdXRlIjoic2FsZXMucGVtYmF5YXJhbi5pbmRleCJ9LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6NX0=', 1790749832);

-- --------------------------------------------------------

--
-- Table structure for table `tahun_akademiks`
--

CREATE TABLE `tahun_akademiks` (
  `id` bigint UNSIGNED NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Aktif','Non-Aktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Non-Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tahun_akademiks`
--

INSERT INTO `tahun_akademiks` (`id`, `nama`, `status`, `created_at`, `updated_at`) VALUES
(1, '2025/2026', 'Non-Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(2, '2026/2027', 'Non-Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(3, '2027/2028', 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02');

-- --------------------------------------------------------

--
-- Table structure for table `targets`
--

CREATE TABLE `targets` (
  `id` bigint UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `target_type` enum('Wilayah','Individual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Individual',
  `wilayah_id` bigint UNSIGNED DEFAULT NULL,
  `spv_id` bigint UNSIGNED DEFAULT NULL,
  `sales_id` bigint UNSIGNED DEFAULT NULL,
  `allocated_by` bigint UNSIGNED DEFAULT NULL,
  `tipe_periode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Bulanan',
  `gelombang` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahun_akademik` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2027/2028',
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `target_kontak` int NOT NULL DEFAULT '0',
  `target_menghubungi` int NOT NULL DEFAULT '0',
  `target_followup` int NOT NULL DEFAULT '0',
  `target_kunjungan` int NOT NULL DEFAULT '0',
  `target_formulir` int NOT NULL DEFAULT '0',
  `target_pemberkasan` int NOT NULL DEFAULT '0',
  `target_lunas` int NOT NULL DEFAULT '0',
  `status` enum('Aktif','Nonaktif','Selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `academic_year_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `targets`
--

INSERT INTO `targets` (`id`, `parent_id`, `target_type`, `wilayah_id`, `spv_id`, `sales_id`, `allocated_by`, `tipe_periode`, `gelombang`, `tahun_akademik`, `tanggal_mulai`, `tanggal_selesai`, `target_kontak`, `target_menghubungi`, `target_followup`, `target_kunjungan`, `target_formulir`, `target_pemberkasan`, `target_lunas`, `status`, `is_locked`, `locked_at`, `locked_by`, `created_at`, `updated_at`, `academic_year_id`) VALUES
(1, NULL, 'Wilayah', 1, 4, 4, 3, 'Bulanan', NULL, '2027/2028', '2026-09-01', '2026-09-30', 100, 80, 60, 20, 30, 20, 15, 'Aktif', 1, '2026-09-30 05:31:50', 3, '2026-09-25 19:33:04', '2026-09-30 05:31:50', 3),
(2, NULL, 'Individual', 2, 3, 6, 3, 'Bulanan', NULL, '2027/2028', '2026-09-01', '2026-09-30', 25, 20, 15, 5, 8, 5, 4, 'Aktif', 0, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 20:09:56', 3),
(3, NULL, 'Individual', 3, 3, 7, 3, 'Bulanan', NULL, '2027/2028', '2026-09-01', '2026-09-30', 25, 20, 15, 5, 8, 5, 4, 'Aktif', 0, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 20:09:56', 3),
(4, NULL, 'Individual', 4, 3, 8, 3, 'Bulanan', NULL, '2027/2028', '2026-09-01', '2026-09-30', 25, 20, 15, 5, 7, 5, 4, 'Aktif', 0, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 20:09:56', 3),
(5, NULL, 'Individual', 5, 3, 9, 3, 'Bulanan', NULL, '2027/2028', '2026-09-01', '2026-09-30', 25, 20, 15, 5, 7, 5, 3, 'Aktif', 0, NULL, NULL, '2026-09-25 19:33:04', '2026-09-25 20:09:56', 3),
(6, NULL, 'Individual', 2, 4, 5, 4, 'Bulanan', NULL, '2027/2028', '2026-09-01', '2026-09-30', 25, 20, 15, 5, 8, 5, 4, 'Aktif', 0, NULL, NULL, '2026-09-29 16:08:46', '2026-09-30 05:31:50', 3),
(7, NULL, 'Individual', 3, 4, 11, 4, 'Bulanan', NULL, '2027/2028', '2026-09-01', '2026-09-30', 25, 20, 15, 5, 8, 5, 4, 'Aktif', 0, NULL, NULL, '2026-09-29 16:08:46', '2026-09-30 05:31:50', 3);

-- --------------------------------------------------------

--
-- Table structure for table `target_defisits`
--

CREATE TABLE `target_defisits` (
  `id` bigint UNSIGNED NOT NULL,
  `spv_id` bigint UNSIGNED NOT NULL,
  `sales_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `defisit_kontak` int NOT NULL DEFAULT '0',
  `defisit_formulir` int NOT NULL DEFAULT '0',
  `defisit_lunas` int NOT NULL DEFAULT '0',
  `is_locked` tinyint(1) NOT NULL DEFAULT '1',
  `locked_at` timestamp NULL DEFAULT NULL,
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksis`
--

CREATE TABLE `transaksis` (
  `id` bigint UNSIGNED NOT NULL,
  `prospek_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `jenis` enum('Beli Formulir','Pembayaran Termin 1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tanggal` datetime NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metode_pembayaran` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'virtual_account|gopay|dana|bank_transfer|null',
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'verified' COMMENT 'verified=lama (backward compat), pending=menunggu CS, rejected=ditolak CS',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `academic_year_id` bigint UNSIGNED DEFAULT NULL,
  `verified_by` bigint UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint UNSIGNED DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaksis`
--

INSERT INTO `transaksis` (`id`, `prospek_id`, `user_id`, `jenis`, `nominal`, `tanggal`, `notes`, `metode_pembayaran`, `payment_status`, `created_at`, `updated_at`, `academic_year_id`, `verified_by`, `verified_at`, `rejected_by`, `rejected_at`, `rejection_reason`) VALUES
(1, 1, 6, 'Beli Formulir', 250000.00, '2026-09-16 02:33:06', 'Pembayaran voucher formulir PMB online.', NULL, 'verified', '2026-09-25 19:33:06', '2026-09-25 19:33:06', 3, NULL, NULL, NULL, NULL, NULL),
(2, 1, 6, 'Pembayaran Termin 1', 3500000.00, '2026-09-23 02:33:06', 'Pelunasan biaya registrasi ulang dan SPP semester 1.', NULL, 'verified', '2026-09-25 19:33:06', '2026-09-25 19:33:06', 3, NULL, NULL, NULL, NULL, NULL),
(3, 9, 6, 'Beli Formulir', 250000.00, '2026-09-16 02:33:06', 'Pembayaran voucher formulir PMB online.', NULL, 'verified', '2026-09-25 19:33:06', '2026-09-25 19:33:06', 3, NULL, NULL, NULL, NULL, NULL),
(4, 9, 6, 'Pembayaran Termin 1', 3500000.00, '2026-09-23 02:33:06', 'Pelunasan biaya registrasi ulang dan SPP semester 1.', NULL, 'verified', '2026-09-25 19:33:06', '2026-09-25 19:33:06', 3, NULL, NULL, NULL, NULL, NULL),
(5, 2, 7, 'Beli Formulir', 250000.00, '2026-09-21 02:33:06', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-25 19:33:06', '2026-09-25 19:33:06', 3, NULL, NULL, NULL, NULL, NULL),
(6, 3, 8, 'Beli Formulir', 250000.00, '2026-09-21 02:33:06', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-25 19:33:06', '2026-09-25 19:33:06', 3, NULL, NULL, NULL, NULL, NULL),
(7, 10, 7, 'Beli Formulir', 250000.00, '2026-09-21 02:33:06', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-25 19:33:06', '2026-09-25 19:33:06', 3, NULL, NULL, NULL, NULL, NULL),
(8, 1, 6, 'Beli Formulir', 250000.00, '2026-09-16 02:34:09', 'Pembayaran voucher formulir PMB online.', NULL, 'verified', '2026-09-25 19:34:09', '2026-09-25 19:34:09', 3, NULL, NULL, NULL, NULL, NULL),
(9, 1, 6, 'Pembayaran Termin 1', 3500000.00, '2026-09-23 02:34:09', 'Pelunasan biaya registrasi ulang dan SPP semester 1.', NULL, 'verified', '2026-09-25 19:34:09', '2026-09-25 19:34:09', 3, NULL, NULL, NULL, NULL, NULL),
(10, 9, 6, 'Beli Formulir', 250000.00, '2026-09-16 02:34:09', 'Pembayaran voucher formulir PMB online.', NULL, 'verified', '2026-09-25 19:34:09', '2026-09-25 19:34:09', 3, NULL, NULL, NULL, NULL, NULL),
(11, 9, 6, 'Pembayaran Termin 1', 3500000.00, '2026-09-23 02:34:09', 'Pelunasan biaya registrasi ulang dan SPP semester 1.', NULL, 'verified', '2026-09-25 19:34:09', '2026-09-25 19:34:09', 3, NULL, NULL, NULL, NULL, NULL),
(12, 2, 7, 'Beli Formulir', 250000.00, '2026-09-21 02:34:09', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-25 19:34:09', '2026-09-25 19:34:09', 3, NULL, NULL, NULL, NULL, NULL),
(13, 3, 8, 'Beli Formulir', 250000.00, '2026-09-21 02:34:09', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-25 19:34:09', '2026-09-25 19:34:09', 3, NULL, NULL, NULL, NULL, NULL),
(14, 10, 7, 'Beli Formulir', 250000.00, '2026-09-21 02:34:09', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-25 19:34:09', '2026-09-25 19:34:09', 3, NULL, NULL, NULL, NULL, NULL),
(15, 1, 5, 'Beli Formulir', 250000.00, '2026-09-19 23:08:47', 'Pembayaran voucher formulir PMB online.', NULL, 'verified', '2026-09-29 16:08:47', '2026-09-29 16:08:47', 3, NULL, NULL, NULL, NULL, NULL),
(16, 1, 5, 'Pembayaran Termin 1', 3500000.00, '2026-09-26 23:08:47', 'Pelunasan biaya registrasi ulang dan SPP semester 1.', NULL, 'verified', '2026-09-29 16:08:47', '2026-09-29 16:08:47', 3, NULL, NULL, NULL, NULL, NULL),
(17, 9, 5, 'Beli Formulir', 250000.00, '2026-09-19 23:08:47', 'Pembayaran voucher formulir PMB online.', NULL, 'verified', '2026-09-29 16:08:47', '2026-09-29 16:08:47', 3, NULL, NULL, NULL, NULL, NULL),
(18, 9, 5, 'Pembayaran Termin 1', 3500000.00, '2026-09-26 23:08:47', 'Pelunasan biaya registrasi ulang dan SPP semester 1.', NULL, 'verified', '2026-09-29 16:08:47', '2026-09-29 16:08:47', 3, NULL, NULL, NULL, NULL, NULL),
(19, 2, 11, 'Beli Formulir', 250000.00, '2026-09-24 23:08:47', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-29 16:08:47', '2026-09-29 16:08:47', 3, NULL, NULL, NULL, NULL, NULL),
(20, 3, 5, 'Beli Formulir', 250000.00, '2026-09-24 23:08:47', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-29 16:08:47', '2026-09-29 16:08:47', 3, NULL, NULL, NULL, NULL, NULL),
(21, 10, 11, 'Beli Formulir', 250000.00, '2026-09-24 23:08:47', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-29 16:08:47', '2026-09-29 16:08:47', 3, NULL, NULL, NULL, NULL, NULL),
(22, 1, 5, 'Beli Formulir', 250000.00, '2026-09-20 12:31:53', 'Pembayaran voucher formulir PMB online.', NULL, 'verified', '2026-09-30 05:31:53', '2026-09-30 05:31:53', 3, NULL, NULL, NULL, NULL, NULL),
(23, 1, 5, 'Pembayaran Termin 1', 3500000.00, '2026-09-27 12:31:53', 'Pelunasan biaya registrasi ulang dan SPP semester 1.', NULL, 'verified', '2026-09-30 05:31:53', '2026-09-30 05:31:53', 3, NULL, NULL, NULL, NULL, NULL),
(24, 9, 5, 'Beli Formulir', 250000.00, '2026-09-20 12:31:53', 'Pembayaran voucher formulir PMB online.', NULL, 'verified', '2026-09-30 05:31:53', '2026-09-30 05:31:53', 3, NULL, NULL, NULL, NULL, NULL),
(25, 9, 5, 'Pembayaran Termin 1', 3500000.00, '2026-09-27 12:31:53', 'Pelunasan biaya registrasi ulang dan SPP semester 1.', NULL, 'verified', '2026-09-30 05:31:53', '2026-09-30 05:31:53', 3, NULL, NULL, NULL, NULL, NULL),
(26, 2, 11, 'Beli Formulir', 250000.00, '2026-09-25 12:31:54', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-30 05:31:54', '2026-09-30 05:31:54', 3, NULL, NULL, NULL, NULL, NULL),
(27, 3, 5, 'Beli Formulir', 250000.00, '2026-09-25 12:31:54', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-30 05:31:54', '2026-09-30 05:31:54', 3, NULL, NULL, NULL, NULL, NULL),
(28, 10, 11, 'Beli Formulir', 250000.00, '2026-09-25 12:31:54', 'Pembelian formulir pendaftaran PMB.', NULL, 'verified', '2026-09-30 05:31:54', '2026-09-30 05:31:54', 3, NULL, NULL, NULL, NULL, NULL),
(29, 12, 5, 'Beli Formulir', 200000000.00, '2026-09-30 00:00:00', 'p', NULL, 'verified', '2026-09-30 05:44:26', '2026-09-30 05:44:26', 3, NULL, NULL, NULL, NULL, NULL),
(30, 12, 5, 'Pembayaran Termin 1', 5000000.00, '2026-09-30 00:00:00', 'p', 'gopay', 'verified', '2026-09-30 05:59:29', '2026-09-30 06:25:52', 3, 6, '2026-09-30 06:25:52', NULL, NULL, NULL),
(31, 1, 5, 'Pembayaran Termin 1', 250000.00, '2026-09-30 00:00:00', 'Transfer Bank: Bank Mandiri - No. 1380010015599 (A/N: Universitas Catur Insan Cendekia)', 'bank_transfer', 'pending', '2026-09-30 06:06:26', '2026-09-30 06:06:26', 3, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `kode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('Admin','HM','SPV','Sales','CS','Telesales','EO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sales',
  `jabatan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_id` bigint UNSIGNED DEFAULT NULL,
  `wilayah_id` bigint UNSIGNED DEFAULT NULL,
  `lokasi_penugasan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Aktif','Nonaktif','Pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `google_access_token` text COLLATE utf8mb4_unicode_ci,
  `google_refresh_token` text COLLATE utf8mb4_unicode_ci,
  `google_token_expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `kode`, `name`, `username`, `email`, `phone`, `avatar`, `role`, `jabatan`, `supervisor_id`, `wilayah_id`, `lokasi_penugasan`, `status`, `last_login_at`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `google_access_token`, `google_refresh_token`, `google_token_expires_at`) VALUES
(1, '2609A001', 'Admin Utama', NULL, 'admin@cic.ac.id', '081122334455', NULL, 'Admin', NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:45', '$2y$12$1fpYGW3dBYjTgwkmqhN0/.GL5fOEX8crXKCXC5gsrqqtIvz1/VaJe', NULL, '2026-09-30 05:31:45', '2026-09-30 05:31:45', NULL, NULL, NULL),
(2, '2609A002', 'Admin Yuda Thomas', NULL, 'admin.yuda.thomas@cic.ac.id', '081234567001', NULL, 'Admin', NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:45', '$2y$12$DC7mEER56Y85gCa8A.sNQOLLRd6RE7JRuJK5FnNjF9t7.m4ZdxY9i', NULL, '2026-09-30 05:31:45', '2026-09-30 05:31:45', NULL, NULL, NULL),
(3, '2609H001', 'HM Yuda Thomas', NULL, 'hm.yuda.thomas@cic.ac.id', '081234567002', NULL, 'HM', NULL, NULL, 1, NULL, 'Aktif', NULL, '2026-09-30 05:31:46', '$2y$12$wJMWQboUneEnuyE5wjJ0Q.o/jc3YrbsInDU1R1Tvai8l/vhrDuWQu', NULL, '2026-09-30 05:31:46', '2026-09-30 05:31:46', NULL, NULL, NULL),
(4, '2609V001', 'SPV Yuda Thomas', NULL, 'spv.yuda.thomas@cic.ac.id', '081234567003', NULL, 'SPV', NULL, NULL, 1, NULL, 'Aktif', NULL, '2026-09-30 05:31:46', '$2y$12$ACa2s.HYUSzuK6THLbk9EuOqJfBT.93k4ZMzSltEHwiuAqAkJ4m06', NULL, '2026-09-30 05:31:46', '2026-09-30 05:31:46', NULL, NULL, NULL),
(5, '2609S001', 'Sales Yuda Thomas', NULL, 'sales.yuda.thomas@cic.ac.id', '081234567004', NULL, 'Sales', NULL, 4, 2, NULL, 'Aktif', NULL, '2026-09-30 05:31:47', '$2y$12$F/VNSOG/12S9EiaKSgcOIeosrGdH60ZUNfzxCkR8x9hZ77CDKfJV.', NULL, '2026-09-30 05:31:47', '2026-09-30 05:31:50', NULL, NULL, NULL),
(6, '2609C001', 'CS Yuda Thomas', NULL, 'cs.yuda.thomas@cic.ac.id', '081234567005', NULL, 'CS', NULL, 4, 1, NULL, 'Aktif', NULL, '2026-09-30 05:31:47', '$2y$12$FWMvaltKt7JGyHC6.c/9curCZVjDh7C81kpYflZ8MKkRXAmZqAXkW', NULL, '2026-09-30 05:31:47', '2026-09-30 05:31:50', NULL, NULL, NULL),
(7, '2609E001', 'EO Yuda Thomas', NULL, 'eo.yuda.thomas@cic.ac.id', '081234567006', NULL, 'EO', NULL, NULL, 1, NULL, 'Aktif', NULL, '2026-09-30 05:31:47', '$2y$12$o8XrFmxNpFOoj16iOULjxu2xP3hEjKlRTvXJba9d8aySU1kuxYk6O', NULL, '2026-09-30 05:31:47', '2026-09-30 05:31:47', NULL, NULL, NULL),
(8, '2609A003', 'Admin Lorenz Adam', NULL, 'admin.lorenz.adam@cic.ac.id', '081398765001', NULL, 'Admin', NULL, NULL, NULL, NULL, 'Aktif', NULL, '2026-09-30 05:31:48', '$2y$12$YgmDP5MoC..ca9/LGfpTLO9vNa9MAImOJ5TIE4CI/BGOJ3SXUwUXq', NULL, '2026-09-30 05:31:48', '2026-09-30 05:31:48', NULL, NULL, NULL),
(9, '2609H002', 'HM Lorenz Adam', NULL, 'hm.lorenz.adam@cic.ac.id', '081398765002', NULL, 'HM', NULL, NULL, 7, NULL, 'Aktif', NULL, '2026-09-30 05:31:48', '$2y$12$UH3T5cEtTPHR47skdfEwY.0sxQ1NPU6ndcMmp2hG.AQaFw16bvaEi', NULL, '2026-09-30 05:31:48', '2026-09-30 05:31:48', NULL, NULL, NULL),
(10, '2609V002', 'SPV Lorenz Adam', NULL, 'spv.lorenz.adam@cic.ac.id', '081398765003', NULL, 'SPV', NULL, NULL, 7, NULL, 'Aktif', NULL, '2026-09-30 05:31:48', '$2y$12$XtfiLzhFwHS9HxmPioanseHg129ZO0wbBpA6IDRHZ3MKIM7ZldyMS', NULL, '2026-09-30 05:31:48', '2026-09-30 05:31:48', NULL, NULL, NULL),
(11, '2609S002', 'Sales Lorenz Adam', NULL, 'sales.lorenz.adam@cic.ac.id', '081398765004', NULL, 'Sales', NULL, 10, 3, NULL, 'Aktif', NULL, '2026-09-30 05:31:49', '$2y$12$FJynjFwUDGQwNMuYSD7bWOM1Wu1iScY2EAM2.3dwXowMPdXX3ERTK', NULL, '2026-09-30 05:31:49', '2026-09-30 05:31:50', NULL, NULL, NULL),
(12, '2609C002', 'CS Lorenz Adam', NULL, 'cs.lorenz.adam@cic.ac.id', '081398765005', NULL, 'CS', NULL, 10, 7, NULL, 'Aktif', NULL, '2026-09-30 05:31:49', '$2y$12$qwkQnj6t2ymx8T3GqCcYTOfMgAw7Q9s9XgPgh4jgoI4VkvZse1U3O', NULL, '2026-09-30 05:31:49', '2026-09-30 05:31:50', NULL, NULL, NULL),
(13, '2609E002', 'EO Lorenz Adam', NULL, 'eo.lorenz.adam@cic.ac.id', '081398765006', NULL, 'EO', NULL, NULL, 1, NULL, 'Aktif', NULL, '2026-09-30 05:31:49', '$2y$12$21KnC7suMql3zWH5qUMfGulyyZoA0VVy1Xs2dDTyy87nfw0mIWLEi', NULL, '2026-09-30 05:31:49', '2026-09-30 05:31:49', NULL, NULL, NULL),
(14, '2609C003', 'Mei Fie', NULL, 'mei.fie@cic.ac.id', '081711223399', NULL, 'CS', NULL, 4, 1, NULL, 'Aktif', NULL, '2026-09-30 05:31:50', '$2y$12$V5hITBHcr/bc4Rb./p1udOn4ovQWh5V/9VBd/PwOPrbK7hRDGmJ2u', NULL, '2026-09-30 05:31:50', '2026-09-30 05:31:50', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_wilayah`
--

CREATE TABLE `user_wilayah` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `wilayah_id` bigint UNSIGNED NOT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deactivated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_wilayah`
--

INSERT INTO `user_wilayah` (`id`, `user_id`, `wilayah_id`, `role`, `is_active`, `assigned_at`, `deactivated_at`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'HM', 1, '2026-09-30 05:31:46', NULL, NULL, '2026-09-30 05:31:46'),
(2, 4, 1, 'SPV', 1, '2026-09-30 05:31:46', NULL, NULL, '2026-09-30 05:31:46'),
(3, 5, 2, 'Sales', 1, '2026-09-30 05:31:47', NULL, NULL, '2026-09-30 05:31:47'),
(4, 6, 1, 'CS', 1, '2026-09-30 05:31:47', NULL, NULL, '2026-09-30 05:31:47'),
(5, 7, 1, 'EO', 1, '2026-09-30 05:31:47', NULL, NULL, '2026-09-30 05:31:47'),
(6, 9, 7, 'HM', 1, '2026-09-30 05:31:48', NULL, NULL, '2026-09-30 05:31:48'),
(7, 10, 7, 'SPV', 1, '2026-09-30 05:31:48', NULL, NULL, '2026-09-30 05:31:48'),
(8, 11, 3, 'Sales', 1, '2026-09-30 05:31:49', NULL, NULL, '2026-09-30 05:31:49'),
(9, 12, 7, 'CS', 1, '2026-09-30 05:31:49', NULL, NULL, '2026-09-30 05:31:49'),
(10, 13, 1, 'EO', 1, '2026-09-30 05:31:50', NULL, NULL, '2026-09-30 05:31:50'),
(11, 14, 1, 'CS', 1, '2026-09-30 05:31:50', NULL, NULL, '2026-09-30 05:31:50');

-- --------------------------------------------------------

--
-- Table structure for table `wilayahs`
--

CREATE TABLE `wilayahs` (
  `id` bigint UNSIGNED NOT NULL,
  `kode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` enum('Provinsi','Kota/Kabupaten','Kecamatan','Kelurahan/Desa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Kecamatan',
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_id_unique` bigint UNSIGNED GENERATED ALWAYS AS (coalesce(`parent_id`,0)) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wilayahs`
--

INSERT INTO `wilayahs` (`id`, `kode`, `nama`, `level`, `parent_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'W-CRB', 'Kota Cirebon', 'Kota/Kabupaten', NULL, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(2, 'CRB-KJS-06', 'Kejaksan', 'Kecamatan', 1, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(3, 'CRB-KSB-07', 'Kesambi', 'Kecamatan', 1, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(4, 'CRB-PLP-08', 'Pekalipan', 'Kecamatan', 1, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(5, 'CRB-LMH-09', 'Lemahwungkuk', 'Kecamatan', 1, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(6, 'CRB-HMJ-10', 'Harjamukti', 'Kecamatan', 1, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(7, 'W-KAB-CRB', 'Kabupaten Cirebon', 'Kota/Kabupaten', NULL, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(8, 'CRB-KDW-09', 'Kedawung', 'Kecamatan', 7, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(9, 'CRB-WER-10', 'Weru', 'Kecamatan', 7, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(10, 'CRB-SMB-11', 'Sumber', 'Kecamatan', 7, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(11, 'CRB-PLM-12', 'Plumbon', 'Kecamatan', 7, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(12, 'CRB-STN-13', 'Astanajapura', 'Kecamatan', 7, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(13, 'CRB-RJW-14', 'Arjawinangun', 'Kecamatan', 7, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(14, 'CRB-CWR-15', 'Ciwaringin', 'Kecamatan', 7, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(15, 'CRB-BBK-16', 'Babakan', 'Kecamatan', 7, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:04'),
(16, 'W-IND', 'Kabupaten Indramayu', 'Kota/Kabupaten', NULL, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(17, 'NDR-NDR-05', 'Indramayu', 'Kecamatan', 16, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(18, 'NDR-KRN-06', 'Karangampel', 'Kecamatan', 16, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(19, 'NDR-JTB-07', 'Jatibarang', 'Kecamatan', 16, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(20, 'NDR-HRG-08', 'Haurgeulis', 'Kecamatan', 16, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(21, 'W-MJL', 'Kabupaten Majalengka', 'Kota/Kabupaten', NULL, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(22, 'MJL-MJL-05', 'Majalengka', 'Kecamatan', 21, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(23, 'MJL-KDP-06', 'Kadipaten', 'Kecamatan', 21, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(24, 'MJL-JTW-07', 'Jatiwangi', 'Kecamatan', 21, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(25, 'MJL-RJG-08', 'Rajagaluh', 'Kecamatan', 21, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(26, 'W-KNG', 'Kabupaten Kuningan', 'Kota/Kabupaten', NULL, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:33:02'),
(27, 'KNN-KNN-05', 'Kuningan', 'Kecamatan', 26, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(28, 'KNN-CLM-06', 'Cilimus', 'Kecamatan', 26, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(29, 'KNN-LRG-07', 'Luragung', 'Kecamatan', 26, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(30, 'KNN-JLK-08', 'Jalaksana', 'Kecamatan', 26, 'Aktif', '2026-09-25 19:33:02', '2026-09-25 19:34:05'),
(32, 'BRB-BRB-01', 'Brebes', 'Kecamatan', 142, 'Aktif', '2026-09-29 14:55:08', '2026-09-29 15:08:22'),
(33, 'CRB-BBR-17', 'Beber', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:42', '2026-09-29 15:06:42'),
(34, 'CRB-CLD-18', 'Ciledug', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:42', '2026-09-29 15:06:42'),
(35, 'CRB-DPK-19', 'Depok', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:42', '2026-09-29 15:06:42'),
(36, 'CRB-DKP-20', 'Dukupuntang', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:42', '2026-09-29 15:06:42'),
(37, 'CRB-GBN-21', 'Gebang', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:42', '2026-09-29 15:06:42'),
(38, 'CRB-GGS-22', 'Gegesik', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:42', '2026-09-29 15:06:42'),
(39, 'CRB-GMP-23', 'Gempol', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(40, 'CRB-GRG-24', 'Greged', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(41, 'CRB-GNN-25', 'Gunungjati', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(42, 'CRB-JMB-26', 'Jamblang', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(43, 'CRB-KLW-27', 'Kaliwedi', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(44, 'CRB-KPT-28', 'Kapetakan', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(45, 'CRB-KRN-29', 'Karangsembung', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(46, 'CRB-KRN-30', 'Karangwareng', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(47, 'CRB-KLN-31', 'Klangenan', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(48, 'CRB-LMH-32', 'Lemahabang', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(49, 'CRB-LSR-33', 'Losari', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(50, 'CRB-MND-34', 'Mundu', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(51, 'CRB-PBD-35', 'Pabedilan', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(52, 'CRB-PBR-36', 'Pabuaran', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(53, 'CRB-PLM-37', 'Palimanan', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(54, 'CRB-PNG-38', 'Pangenan', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(55, 'CRB-PNG-39', 'Panguragan', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(56, 'CRB-PSL-40', 'Pasaleman', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(57, 'CRB-PLR-41', 'Plered', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(58, 'CRB-SDN-42', 'Sedong', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(59, 'CRB-SRN-43', 'Suranenggala', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(60, 'CRB-SSK-44', 'Susukan', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(61, 'CRB-SSK-45', 'Susukanlebak', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(62, 'CRB-TLN-46', 'Talun', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(63, 'CRB-TNG-47', 'Tengahtani', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(64, 'CRB-WLD-48', 'Waled', 'Kecamatan', 7, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(65, 'NDR-NJT-09', 'Anjatan', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(66, 'NDR-RHN-10', 'Arahan', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(67, 'NDR-BLN-11', 'Balongan', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(68, 'NDR-BNG-12', 'Bangodua', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(69, 'NDR-BNG-13', 'Bongas', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(70, 'NDR-CNT-14', 'Cantigi', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(71, 'NDR-CKD-15', 'Cikedung', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(72, 'NDR-GBS-16', 'Gabuswetan', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(73, 'NDR-GNT-17', 'Gantar', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(74, 'NDR-JNT-18', 'Juntinyuat', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(75, 'NDR-KND-19', 'Kandanghaur', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(76, 'NDR-KEB-20', 'Kedokan Bunder', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(77, 'NDR-KRT-21', 'Kertasemaya', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(78, 'NDR-KRN-22', 'Krangkeng', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(79, 'NDR-KRY-23', 'Kroya', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(80, 'NDR-LEL-24', 'Lelea', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(81, 'NDR-LHB-25', 'Lohbener', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(82, 'NDR-LSR-26', 'Losarang', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(83, 'NDR-PSK-27', 'Pasekan', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(84, 'NDR-PTR-28', 'Patrol', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(85, 'NDR-SND-29', 'Sindang', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(86, 'NDR-SLY-30', 'Sliyeg', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(87, 'NDR-SKG-31', 'Sukagumiwang', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(88, 'NDR-SKR-32', 'Sukra', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(89, 'NDR-TRS-33', 'Terisi', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(90, 'NDR-TKD-34', 'Tukdana', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(91, 'NDR-WDS-35', 'Widasari', 'Kecamatan', 16, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(92, 'MJL-RGP-09', 'Argapura', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(93, 'MJL-BNJ-10', 'Banjaran', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(94, 'MJL-BNT-11', 'Bantarujeg', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(95, 'MJL-CGS-12', 'Cigasong', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(96, 'MJL-CKJ-13', 'Cikijing', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(97, 'MJL-CNG-14', 'Cingambul', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(98, 'MJL-DWN-15', 'Dawuan', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(99, 'MJL-JTT-16', 'Jatitujuh', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(100, 'MJL-KSK-17', 'Kasokandel', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(101, 'MJL-KRT-18', 'Kertajati', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(102, 'MJL-LMH-19', 'Lemahsugih', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(103, 'MJL-LWM-20', 'Leuwimunding', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(104, 'MJL-LGN-21', 'Ligung', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(105, 'MJL-MAJ-22', 'Maja', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(106, 'MJL-MLS-23', 'Malausma', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(107, 'MJL-PNY-24', 'Panyingkiran', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(108, 'MJL-PLS-25', 'Palasah', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(109, 'MJL-SND-26', 'Sindang', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(110, 'MJL-SND-27', 'Sindangwangi', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(111, 'MJL-SKH-28', 'Sukahaji', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(112, 'MJL-SMB-29', 'Sumberjaya', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(113, 'MJL-TLG-30', 'Talaga', 'Kecamatan', 21, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(114, 'KNN-CWG-09', 'Ciawigebang', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(115, 'KNN-CBR-10', 'Cibeureum', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(116, 'KNN-CBN-11', 'Cibingbin', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(117, 'KNN-CDH-12', 'Cidahu', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(118, 'KNN-CGN-13', 'Cigandamekar', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(119, 'KNN-CGG-14', 'Cigugur', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(120, 'KNN-CLB-15', 'Cilebak', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(121, 'KNN-CMH-16', 'Cimahi', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(122, 'KNN-CNR-17', 'Ciniru', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(123, 'KNN-CPC-18', 'Cipicung', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(124, 'KNN-CWR-19', 'Ciwaru', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(125, 'KNN-DRM-20', 'Darma', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(126, 'KNN-GRW-21', 'Garawangi', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(127, 'KNN-HNT-22', 'Hantara', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(128, 'KNN-JPR-23', 'Japara', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(129, 'KNN-KDG-24', 'Kadugede', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(130, 'KNN-KLM-25', 'Kalimanggis', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(131, 'KNN-KRN-26', 'Karangkancana', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(132, 'KNN-KRM-27', 'Kramatmulya', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(133, 'KNN-LBK-28', 'Lebakwangi', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(134, 'KNN-MLB-29', 'Maleber', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(135, 'KNN-MND-30', 'Mandirancan', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(136, 'KNN-NSH-31', 'Nusaherang', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(137, 'KNN-PNC-32', 'Pancalang', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(138, 'KNN-PSW-33', 'Pasawahan', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:43', '2026-09-29 15:06:43'),
(139, 'KNN-SLJ-34', 'Selajambe', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(140, 'KNN-SND-35', 'Sindangagung', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(141, 'KNN-SBN-36', 'Subang', 'Kecamatan', 26, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(142, 'W-BBS', 'Kabupaten Brebes', 'Kota/Kabupaten', NULL, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(143, 'BRB-BNJ-01', 'Banjarharjo', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(144, 'BRB-BNT-02', 'Bantarkawung', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(145, 'BRB-BRB-03', 'Brebes', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(146, 'BRB-BLK-04', 'Bulakamba', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(147, 'BRB-BMY-05', 'Bumiayu', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(148, 'BRB-JTB-06', 'Jatibarang', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(149, 'BRB-KTN-07', 'Ketanggungan', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(150, 'BRB-KRS-08', 'Kersana', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(151, 'BRB-LRN-09', 'Larangan', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(152, 'BRB-LSR-10', 'Losari', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(153, 'BRB-PGY-11', 'Paguyangan', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(154, 'BRB-SLM-12', 'Salem', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(155, 'BRB-SRM-13', 'Sirampog', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(156, 'BRB-SNG-14', 'Songgom', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(157, 'BRB-TNJ-15', 'Tanjung', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(158, 'BRB-TNJ-16', 'Tonjong', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44'),
(159, 'BRB-WNS-17', 'Wanasari', 'Kecamatan', 142, 'Aktif', '2026-09-29 15:06:44', '2026-09-29 15:06:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendances_attendance_location_id_foreign` (`attendance_location_id`),
  ADD KEY `attendances_user_id_check_in_at_index` (`user_id`,`check_in_at`);

--
-- Indexes for table `attendance_locations`
--
ALTER TABLE `attendance_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `events_qr_code_unique` (`qr_code`),
  ADD KEY `events_eo_id_foreign` (`eo_id`),
  ADD KEY `events_sales_id_foreign` (`sales_id`),
  ADD KEY `events_type_id_foreign` (`type_id`),
  ADD KEY `events_dosen_id_foreign` (`dosen_id`),
  ADD KEY `events_prodi_id_foreign` (`prodi_id`),
  ADD KEY `events_sekolah_id_foreign` (`sekolah_id`),
  ADD KEY `events_perusahaan_id_foreign` (`perusahaan_id`),
  ADD KEY `events_academic_year_id_foreign` (`academic_year_id`);

--
-- Indexes for table `event_sales`
--
ALTER TABLE `event_sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_sales_event_id_sales_id_unique` (`event_id`,`sales_id`),
  ADD KEY `event_sales_sales_id_foreign` (`sales_id`),
  ADD KEY `event_sales_assigned_by_spv_id_foreign` (`assigned_by_spv_id`);

--
-- Indexes for table `event_spv`
--
ALTER TABLE `event_spv`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_spv_event_id_spv_id_unique` (`event_id`,`spv_id`),
  ADD KEY `event_spv_spv_id_foreign` (`spv_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `follow_ups`
--
ALTER TABLE `follow_ups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `follow_ups_prospek_id_foreign` (`prospek_id`),
  ADD KEY `follow_ups_user_id_foreign` (`user_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  ADD KEY `invoices_user_id_foreign` (`user_id`),
  ADD KEY `invoices_prospek_id_foreign` (`prospek_id`),
  ADD KEY `invoices_created_by_id_foreign` (`created_by_id`),
  ADD KEY `invoices_invoice_number_status_index` (`invoice_number`,`status`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kunjungans`
--
ALTER TABLE `kunjungans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kunjungans_nomor_unique` (`nomor`),
  ADD UNIQUE KEY `kunjungans_qr_code_unique` (`qr_code`),
  ADD KEY `kunjungans_sales_id_foreign` (`sales_id`),
  ADD KEY `kunjungans_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `kunjungans_prodi_id_foreign` (`prodi_id`),
  ADD KEY `kunjungans_dosen_id_foreign` (`dosen_id`),
  ADD KEY `kunjungans_event_id_foreign` (`event_id`);

--
-- Indexes for table `master_data`
--
ALTER TABLE `master_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `master_data_kode_unique` (`kode`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_transaction_id_unique` (`transaction_id`),
  ADD KEY `payments_invoice_id_foreign` (`invoice_id`),
  ADD KEY `payments_transaction_id_status_index` (`transaction_id`,`status`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `perusahaans`
--
ALTER TABLE `perusahaans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `perusahaans_kode_unique` (`kode`),
  ADD KEY `perusahaans_kategori_id_foreign` (`kategori_id`),
  ADD KEY `perusahaans_wilayah_id_foreign` (`wilayah_id`),
  ADD KEY `perusahaans_sales_id_foreign` (`sales_id`);

--
-- Indexes for table `prodis`
--
ALTER TABLE `prodis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prodis_kode_unique` (`kode`);

--
-- Indexes for table `prospeks`
--
ALTER TABLE `prospeks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prospeks_wilayah_id_foreign` (`wilayah_id`),
  ADD KEY `prospeks_sales_id_foreign` (`sales_id`),
  ADD KEY `prospeks_cs_id_foreign` (`cs_id`),
  ADD KEY `prospeks_owner_id_foreign` (`owner_id`),
  ADD KEY `prospeks_sekolah_id_foreign` (`sekolah_id`),
  ADD KEY `prospeks_perusahaan_id_foreign` (`perusahaan_id`),
  ADD KEY `prospeks_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `prospeks_prodi_id_foreign` (`prodi_id`);

--
-- Indexes for table `prospek_timelines`
--
ALTER TABLE `prospek_timelines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prospek_timelines_prospek_id_foreign` (`prospek_id`),
  ADD KEY `prospek_timelines_user_id_foreign` (`user_id`);

--
-- Indexes for table `sekolahs`
--
ALTER TABLE `sekolahs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sekolahs_kode_unique` (`kode`),
  ADD KEY `sekolahs_kategori_id_foreign` (`kategori_id`),
  ADD KEY `sekolahs_wilayah_id_foreign` (`wilayah_id`),
  ADD KEY `sekolahs_sales_id_foreign` (`sales_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `tahun_akademiks`
--
ALTER TABLE `tahun_akademiks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tahun_akademiks_nama_unique` (`nama`);

--
-- Indexes for table `targets`
--
ALTER TABLE `targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `targets_sales_id_foreign` (`sales_id`),
  ADD KEY `targets_allocated_by_foreign` (`allocated_by`),
  ADD KEY `targets_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `targets_locked_by_foreign` (`locked_by`),
  ADD KEY `targets_wilayah_id_foreign` (`wilayah_id`),
  ADD KEY `targets_spv_id_foreign` (`spv_id`),
  ADD KEY `targets_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `target_defisits`
--
ALTER TABLE `target_defisits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `spv_sales_tanggal_unique` (`spv_id`,`sales_id`,`tanggal`),
  ADD KEY `target_defisits_sales_id_foreign` (`sales_id`);

--
-- Indexes for table `transaksis`
--
ALTER TABLE `transaksis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksis_prospek_id_foreign` (`prospek_id`),
  ADD KEY `transaksis_user_id_foreign` (`user_id`),
  ADD KEY `transaksis_academic_year_id_foreign` (`academic_year_id`),
  ADD KEY `transaksis_verified_by_foreign` (`verified_by`),
  ADD KEY `transaksis_rejected_by_foreign` (`rejected_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_role_unique` (`email`,`role`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_kode_unique` (`kode`),
  ADD KEY `users_supervisor_id_foreign` (`supervisor_id`),
  ADD KEY `users_wilayah_id_foreign` (`wilayah_id`);

--
-- Indexes for table `user_wilayah`
--
ALTER TABLE `user_wilayah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_wilayah_wilayah_id_role_is_active_index` (`wilayah_id`,`role`,`is_active`),
  ADD KEY `user_wilayah_user_id_is_active_index` (`user_id`,`is_active`);

--
-- Indexes for table `wilayahs`
--
ALTER TABLE `wilayahs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wilayahs_kode_parent_unique` (`kode`,`parent_id_unique`),
  ADD KEY `wilayahs_parent_id_foreign` (`parent_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_locations`
--
ALTER TABLE `attendance_locations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_sales`
--
ALTER TABLE `event_sales`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `event_spv`
--
ALTER TABLE `event_spv`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `follow_ups`
--
ALTER TABLE `follow_ups`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kunjungans`
--
ALTER TABLE `kunjungans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `master_data`
--
ALTER TABLE `master_data`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `perusahaans`
--
ALTER TABLE `perusahaans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `prodis`
--
ALTER TABLE `prodis`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `prospeks`
--
ALTER TABLE `prospeks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `prospek_timelines`
--
ALTER TABLE `prospek_timelines`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `sekolahs`
--
ALTER TABLE `sekolahs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=362;

--
-- AUTO_INCREMENT for table `tahun_akademiks`
--
ALTER TABLE `tahun_akademiks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `targets`
--
ALTER TABLE `targets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `target_defisits`
--
ALTER TABLE `target_defisits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksis`
--
ALTER TABLE `transaksis`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user_wilayah`
--
ALTER TABLE `user_wilayah`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wilayahs`
--
ALTER TABLE `wilayahs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `attendances_attendance_location_id_foreign` FOREIGN KEY (`attendance_location_id`) REFERENCES `attendance_locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademiks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_dosen_id_foreign` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_eo_id_foreign` FOREIGN KEY (`eo_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_perusahaan_id_foreign` FOREIGN KEY (`perusahaan_id`) REFERENCES `perusahaans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolahs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_type_id_foreign` FOREIGN KEY (`type_id`) REFERENCES `master_data` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_sales`
--
ALTER TABLE `event_sales`
  ADD CONSTRAINT `event_sales_assigned_by_spv_id_foreign` FOREIGN KEY (`assigned_by_spv_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_sales_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_sales_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_spv`
--
ALTER TABLE `event_spv`
  ADD CONSTRAINT `event_spv_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_spv_spv_id_foreign` FOREIGN KEY (`spv_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `follow_ups`
--
ALTER TABLE `follow_ups`
  ADD CONSTRAINT `follow_ups_prospek_id_foreign` FOREIGN KEY (`prospek_id`) REFERENCES `prospeks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follow_ups_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_prospek_id_foreign` FOREIGN KEY (`prospek_id`) REFERENCES `prospeks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kunjungans`
--
ALTER TABLE `kunjungans`
  ADD CONSTRAINT `kunjungans_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademiks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `kunjungans_dosen_id_foreign` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `kunjungans_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `kunjungans_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `kunjungans_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `perusahaans`
--
ALTER TABLE `perusahaans`
  ADD CONSTRAINT `perusahaans_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `master_data` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `perusahaans_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `perusahaans_wilayah_id_foreign` FOREIGN KEY (`wilayah_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `prospeks`
--
ALTER TABLE `prospeks`
  ADD CONSTRAINT `prospeks_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademiks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prospeks_cs_id_foreign` FOREIGN KEY (`cs_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prospeks_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prospeks_perusahaan_id_foreign` FOREIGN KEY (`perusahaan_id`) REFERENCES `perusahaans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prospeks_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prospeks_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prospeks_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolahs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prospeks_wilayah_id_foreign` FOREIGN KEY (`wilayah_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `prospek_timelines`
--
ALTER TABLE `prospek_timelines`
  ADD CONSTRAINT `prospek_timelines_prospek_id_foreign` FOREIGN KEY (`prospek_id`) REFERENCES `prospeks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prospek_timelines_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sekolahs`
--
ALTER TABLE `sekolahs`
  ADD CONSTRAINT `sekolahs_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `master_data` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sekolahs_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sekolahs_wilayah_id_foreign` FOREIGN KEY (`wilayah_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `targets`
--
ALTER TABLE `targets`
  ADD CONSTRAINT `targets_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademiks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `targets_allocated_by_foreign` FOREIGN KEY (`allocated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `targets_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `targets_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `targets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `targets_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `targets_spv_id_foreign` FOREIGN KEY (`spv_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `targets_wilayah_id_foreign` FOREIGN KEY (`wilayah_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `target_defisits`
--
ALTER TABLE `target_defisits`
  ADD CONSTRAINT `target_defisits_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `target_defisits_spv_id_foreign` FOREIGN KEY (`spv_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksis`
--
ALTER TABLE `transaksis`
  ADD CONSTRAINT `transaksis_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademiks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaksis_prospek_id_foreign` FOREIGN KEY (`prospek_id`) REFERENCES `prospeks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksis_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaksis_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaksis_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_wilayah_id_foreign` FOREIGN KEY (`wilayah_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_wilayah`
--
ALTER TABLE `user_wilayah`
  ADD CONSTRAINT `user_wilayah_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_wilayah_wilayah_id_foreign` FOREIGN KEY (`wilayah_id`) REFERENCES `wilayahs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wilayahs`
--
ALTER TABLE `wilayahs`
  ADD CONSTRAINT `wilayahs_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
