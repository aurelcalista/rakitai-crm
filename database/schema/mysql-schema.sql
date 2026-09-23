/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `sales_id` bigint unsigned NOT NULL,
  `assigned_by_spv_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_sales_event_id_sales_id_unique` (`event_id`,`sales_id`),
  KEY `event_sales_sales_id_foreign` (`sales_id`),
  KEY `event_sales_assigned_by_spv_id_foreign` (`assigned_by_spv_id`),
  CONSTRAINT `event_sales_assigned_by_spv_id_foreign` FOREIGN KEY (`assigned_by_spv_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_sales_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_sales_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_spv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_spv` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `spv_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_spv_event_id_spv_id_unique` (`event_id`,`spv_id`),
  KEY `event_spv_spv_id_foreign` (`spv_id`),
  CONSTRAINT `event_spv_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_spv_spv_id_foreign` FOREIGN KEY (`spv_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `lokasi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Rencana','Sedang Berjalan','Selesai','Batal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Rencana',
  `qr_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eo_id` bigint unsigned NOT NULL,
  `sekolah_id` bigint unsigned DEFAULT NULL,
  `perusahaan_id` bigint unsigned DEFAULT NULL,
  `sales_id` bigint unsigned DEFAULT NULL,
  `dosen_id` bigint unsigned DEFAULT NULL,
  `dosen_pemateri` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dokumentasi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `absen_peserta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type_id` bigint unsigned DEFAULT NULL,
  `prodi_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_qr_code_unique` (`qr_code`),
  KEY `events_eo_id_foreign` (`eo_id`),
  KEY `events_sales_id_foreign` (`sales_id`),
  KEY `events_type_id_foreign` (`type_id`),
  KEY `events_dosen_id_foreign` (`dosen_id`),
  KEY `events_prodi_id_foreign` (`prodi_id`),
  KEY `events_sekolah_id_foreign` (`sekolah_id`),
  KEY `events_perusahaan_id_foreign` (`perusahaan_id`),
  CONSTRAINT `events_dosen_id_foreign` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_eo_id_foreign` FOREIGN KEY (`eo_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_perusahaan_id_foreign` FOREIGN KEY (`perusahaan_id`) REFERENCES `perusahaans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolahs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_type_id_foreign` FOREIGN KEY (`type_id`) REFERENCES `master_data` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `follow_ups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `follow_ups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prospek_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `metode` enum('WhatsApp','Telepon','Meeting','Email') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WhatsApp',
  `tanggal` datetime NOT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `hasil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `next_follow_up` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `follow_ups_prospek_id_foreign` (`prospek_id`),
  KEY `follow_ups_user_id_foreign` (`user_id`),
  CONSTRAINT `follow_ups_prospek_id_foreign` FOREIGN KEY (`prospek_id`) REFERENCES `prospeks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `follow_ups_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kunjungans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kunjungans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nomor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `tahun_akademik` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2027/2028',
  `waktu` time NOT NULL,
  `sales_id` bigint unsigned NOT NULL,
  `dosen_id` bigint unsigned DEFAULT NULL,
  `dosen_pemateri` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prodi_id` bigint unsigned DEFAULT NULL,
  `jenis` enum('Sekolah','Perusahaan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tujuan_id` bigint unsigned NOT NULL,
  `tujuan_kunjungan` text COLLATE utf8mb4_unicode_ci,
  `hasil` text COLLATE utf8mb4_unicode_ci,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `nama_institusi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tier` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget_maksimum` decimal(14,2) DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `lokasi_penugasan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_whatsapp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_outside_radius` tinyint(1) NOT NULL DEFAULT '0',
  `potensi_mahasiswa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sekolah: potensi jumlah beasiswa',
  `detail_potensi_mahasiswa` text COLLATE utf8mb4_unicode_ci COMMENT 'Sekolah: detail program beasiswa',
  `kesediaan_training_ai` tinyint(1) DEFAULT NULL COMMENT 'Sekolah: kesediaan mengikuti training AI/Robotik',
  `bidang_usaha` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Perusahaan: bidang usaha',
  `potensi_s1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Perusahaan: potensi kelas karyawan S1',
  `potensi_s2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Perusahaan: potensi magister S2',
  `potensi_csr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Perusahaan: potensi CSR',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_verifikasi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Terverifikasi',
  `qr_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `jarak_meter` double DEFAULT NULL,
  `status_lokasi` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `academic_year_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kunjungans_nomor_unique` (`nomor`),
  UNIQUE KEY `kunjungans_qr_code_unique` (`qr_code`),
  KEY `kunjungans_sales_id_foreign` (`sales_id`),
  KEY `kunjungans_academic_year_id_foreign` (`academic_year_id`),
  KEY `kunjungans_prodi_id_foreign` (`prodi_id`),
  KEY `kunjungans_dosen_id_foreign` (`dosen_id`),
  CONSTRAINT `kunjungans_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademiks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kunjungans_dosen_id_foreign` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kunjungans_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kunjungans_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Aktif','Nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_data_kode_unique` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perusahaans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perusahaans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `wilayah_id` bigint unsigned DEFAULT NULL,
  `kecamatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `telepon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_jabatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `sales_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `perusahaans_kode_unique` (`kode`),
  KEY `perusahaans_kategori_id_foreign` (`kategori_id`),
  KEY `perusahaans_wilayah_id_foreign` (`wilayah_id`),
  KEY `perusahaans_sales_id_foreign` (`sales_id`),
  CONSTRAINT `perusahaans_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `master_data` (`id`) ON DELETE SET NULL,
  CONSTRAINT `perusahaans_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `perusahaans_wilayah_id_foreign` FOREIGN KEY (`wilayah_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prodis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prodis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenjang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fakultas` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kuota` int NOT NULL DEFAULT '0',
  `spp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prodis_kode_unique` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prospek_timelines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prospek_timelines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prospek_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status_before` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_after` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prospek_timelines_prospek_id_foreign` (`prospek_id`),
  KEY `prospek_timelines_user_id_foreign` (`user_id`),
  CONSTRAINT `prospek_timelines_prospek_id_foreign` FOREIGN KEY (`prospek_id`) REFERENCES `prospeks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prospek_timelines_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prospeks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prospeks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('Sekolah','Corporate','Individu') COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun_akademik` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2027/2028',
  `needs_visit_report` tinyint(1) NOT NULL DEFAULT '0',
  `stage_number` int NOT NULL DEFAULT '1',
  `active_follow_up_count` int NOT NULL DEFAULT '0',
  `follow_up_count` int NOT NULL DEFAULT '0',
  `potential` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_training` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `lost_reason` enum('Tidak tertarik','Tidak dapat dihubungi','Membatalkan','Memilih kampus lain','Lainnya') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lost_note` text COLLATE utf8mb4_unicode_ci,
  `wilayah_id` bigint unsigned DEFAULT NULL,
  `sales_id` bigint unsigned DEFAULT NULL,
  `cs_id` bigint unsigned DEFAULT NULL,
  `handover_at` datetime DEFAULT NULL,
  `owner_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sekolah_id` bigint unsigned DEFAULT NULL,
  `perusahaan_id` bigint unsigned DEFAULT NULL,
  `academic_year_id` bigint unsigned DEFAULT NULL,
  `prodi_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prospeks_wilayah_id_foreign` (`wilayah_id`),
  KEY `prospeks_sales_id_foreign` (`sales_id`),
  KEY `prospeks_cs_id_foreign` (`cs_id`),
  KEY `prospeks_owner_id_foreign` (`owner_id`),
  KEY `prospeks_sekolah_id_foreign` (`sekolah_id`),
  KEY `prospeks_perusahaan_id_foreign` (`perusahaan_id`),
  KEY `prospeks_academic_year_id_foreign` (`academic_year_id`),
  KEY `prospeks_prodi_id_foreign` (`prodi_id`),
  CONSTRAINT `prospeks_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademiks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospeks_cs_id_foreign` FOREIGN KEY (`cs_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospeks_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospeks_perusahaan_id_foreign` FOREIGN KEY (`perusahaan_id`) REFERENCES `perusahaans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospeks_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospeks_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospeks_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolahs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospeks_wilayah_id_foreign` FOREIGN KEY (`wilayah_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sekolahs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sekolahs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tier` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'B',
  `kategori_id` bigint unsigned DEFAULT NULL,
  `wilayah_id` bigint unsigned DEFAULT NULL,
  `kecamatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `telepon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_jabatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `sales_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sekolahs_kode_unique` (`kode`),
  KEY `sekolahs_kategori_id_foreign` (`kategori_id`),
  KEY `sekolahs_wilayah_id_foreign` (`wilayah_id`),
  KEY `sekolahs_sales_id_foreign` (`sales_id`),
  CONSTRAINT `sekolahs_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `master_data` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sekolahs_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sekolahs_wilayah_id_foreign` FOREIGN KEY (`wilayah_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tahun_akademiks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tahun_akademiks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Aktif','Non-Aktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Non-Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tahun_akademiks_nama_unique` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `targets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_id` bigint unsigned NOT NULL,
  `allocated_by` bigint unsigned DEFAULT NULL,
  `tipe_periode` enum('Harian','Mingguan','Bulanan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun_akademik` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2027/2028',
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `target_kontak` int NOT NULL DEFAULT '0',
  `target_menghubungi` int NOT NULL DEFAULT '0',
  `target_followup` int NOT NULL DEFAULT '0',
  `target_kunjungan` int NOT NULL DEFAULT '0',
  `target_formulir` int NOT NULL DEFAULT '0',
  `target_lunas` int NOT NULL DEFAULT '0',
  `status` enum('Aktif','Nonaktif','Selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `academic_year_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `targets_sales_id_foreign` (`sales_id`),
  KEY `targets_allocated_by_foreign` (`allocated_by`),
  KEY `targets_academic_year_id_foreign` (`academic_year_id`),
  CONSTRAINT `targets_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademiks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `targets_allocated_by_foreign` FOREIGN KEY (`allocated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `targets_sales_id_foreign` FOREIGN KEY (`sales_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transaksis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prospek_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `jenis` enum('Beli Formulir','Pembayaran Termin 1') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tanggal` datetime NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `academic_year_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaksis_prospek_id_foreign` (`prospek_id`),
  KEY `transaksis_user_id_foreign` (`user_id`),
  KEY `transaksis_academic_year_id_foreign` (`academic_year_id`),
  CONSTRAINT `transaksis_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademiks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transaksis_prospek_id_foreign` FOREIGN KEY (`prospek_id`) REFERENCES `prospeks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaksis_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('Admin','HM','SPV','Sales','CS','Telesales','EO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sales',
  `supervisor_id` bigint unsigned DEFAULT NULL,
  `wilayah_id` bigint unsigned DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_supervisor_id_foreign` (`supervisor_id`),
  KEY `users_wilayah_id_foreign` (`wilayah_id`),
  CONSTRAINT `users_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_wilayah_id_foreign` FOREIGN KEY (`wilayah_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wilayahs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wilayahs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` enum('Provinsi','Kota/Kabupaten','Kecamatan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Kecamatan',
  `parent_id` bigint unsigned DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_id_unique` bigint unsigned GENERATED ALWAYS AS (coalesce(`parent_id`,0)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wilayahs_kode_parent_unique` (`kode`,`parent_id_unique`),
  KEY `wilayahs_parent_id_foreign` (`parent_id`),
  CONSTRAINT `wilayahs_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `wilayahs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_09_14_032741_create_master_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_09_14_032742_create_wilayahs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_09_14_032743_create_sekolahs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_09_14_032744_create_prodis_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_09_14_032745_create_perusahaans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_09_14_032746_create_kunjungans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_09_14_032747_create_targets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_09_15_024720_create_prospeks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_09_15_024721_create_follow_ups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_09_15_024722_create_prospek_timelines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_09_15_024723_add_hierarchy_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_09_15_045230_alter_wilayahs_table_add_hierarchy',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_09_16_020953_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_09_16_044517_modify_wilayahs_kode_unique_constraint',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_09_17_040000_add_lost_fields_to_prospeks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_09_17_040001_add_metode_to_follow_ups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_09_17_040002_add_visit_detail_fields_to_kunjungans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_09_17_114654_add_master_data_to_prospeks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_09_17_120925_add_source_to_prospeks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_09_17_120952_add_gps_to_kunjungans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_09_17_121406_remove_gps_from_kunjungans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_09_17_132948_change_status_columns_to_string',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_09_18_023254_add_needs_visit_report_to_prospeks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_09_18_024545_create_transaksis_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_09_18_034329_add_sales_id_to_sekolahs_and_perusahaans',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_09_21_015627_add_target_menghubungi_to_targets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_09_22_000001_add_spv_p0_fields_to_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_09_22_030523_modify_role_column_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_09_22_031516_add_geo_and_photo_to_kunjungans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_09_22_031735_add_geo_to_sekolahs_and_perusahaans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_09_22_032654_add_active_follow_up_count_to_prospeks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_09_22_033432_create_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_09_22_035054_add_handover_at_to_prospeks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_09_22_035227_add_sales_id_to_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_09_22_040110_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_09_22_040714_create_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_09_22_040729_create_event_spv_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_09_22_040740_create_event_sales_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_09_22_043236_alter_users_add_eo_role',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_09_22_053250_create_tahun_akademiks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_09_22_053314_add_academic_year_id_to_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_09_22_054054_change_kunjungans_status_column_to_string',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_09_22_054130_rename_beasiswa_fields_in_kunjungans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_09_22_110526_add_type_id_to_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_09_22_121007_add_prodi_id_to_prospeks_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_09_22_140000_add_phase5_fields_to_kunjungans_and_events',3);
