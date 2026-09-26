# 🎓 CRM UCIC — Sistem CRM Pemasaran & Penerimaan Mahasiswa Baru

**CRM UCIC** adalah aplikasi *Customer Relationship Management* (CRM) terpadu yang dirancang khusus untuk mengoptimalkan alur kerja tim pemasaran dan penerimaan mahasiswa baru di kampus **Universitas Catur Insan Cendekia (UCIC)**. 

Aplikasi ini menghubungkan seluruh proses dari kegiatan lapangan, penanganan *lead*, pencapaian target berjenjang, hingga transaksi pelunasan secara *real-time*.

---

## 🌟 Fitur Utama & Hirarki Hak Akses

Sistem ini mendukung 6 tingkat hak akses (*role-based access control*) yang saling terintegrasi:

### 1. 🛡️ **Admin Panel**
* **Manajemen Pengguna**: Pendaftaran, verifikasi, reset password, dan status aktif/nonaktif akun.
* **Penugasan Wilayah HM**: Pengaturan wilayah binaan untuk Head of Marketing.
* **Master Data**: Pengelolaan data Sekolah (SMA/SMK), Perusahaan/Corporate, Program Studi, dan Wilayah (Provinsi/Kota/Kecamatan).
* **Pengaturan CRM & Audit**: Pengaturan bobot performa indikator, tahun akademik aktif, dan *audit log*.

### 2. 📊 **HM (Head of Marketing)**
* **Hierarki Target**: Penentuan target tahunan & bulanan per wilayah ke Supervisor (SPV).
* **Monitoring Evaluasi Performa**: Pantauan akumulasi realisasi target tim, defisit target, dan matriks kesehatan wilayah.
* **Potensi Wilayah & Infografis**: Peta sebaran potensi wilayah binaan HM.

### 3. 👥 **SPV (Supervisor)**
* **Pembagian Target Tim**: Pembagian target bulanan ke Sales/CS (dibatasi tidak melebihi target SPV).
* **Breakdown Target Otomatis**: Pembagian target bulanan menjadi mingguan dan harian secara akurat tanpa pembengkakan target (menggunakan *floor math*).
* **Penugasan Event & Bantuan Closing**: Penugasan event ke Sales serta fitur bantuan *closing* transaksi di lapangan.

### 4. 🚀 **Sales Lapangan**
* **Input Prospek Lapangan**: Pencatatan prospek baru (Sekolah, Corporate, Individu) lengkap dengan fitur *Searchable Dropdown Plugin*.
* **Laporan Kunjungan Mandiri**: Dokumentasi foto kegiatan dan deteksi geolokasi GPS otomatis.
* **Jadwal Event & Pipeline**: Pengelolaan pipeline prospek dari prospek awal hingga tahap `CLOSING`.

### 5. 🎧 **CS (Customer Service)**
* **Handover Lead**: Menerima pendelegasian prospek dari Sales tanpa pembatas wilayah.
* **Follow-Up & Transaksi**: Pencatatan riwayat interaksi serta pembayaran Formulir Pendaftaran dan Termin 1.
* **Status Lunas**: Memvalidasi dan mengubah status prospek menjadi `LUNAS` (Stage 7).

### 6. 🎪 **EO (Event Organizer)**
* **Pengelolaan Event**: Pembuatan jadwal pameran, sosialisasi, dan pengelolaan Master Tipe Event.

---

## ⚡ Teknologi & Dependensi

* **Backend Framework**: Laravel 11.x (PHP 8.2+)
* **Frontend Components**: Blade Templating, Alpine.js, TailwindCSS (Vanilla UI)
* **Database**: MySQL / MariaDB
* **Build Tool**: Vite (JavaScript & CSS bundling)

---

## 🚀 Panduan Instalasi & Penggunaan

### 1. Clone Repository & Install Dependensi
```bash
git clone https://github.com/aurelcalista/rakitai-crm.git
cd rakitai-crm

composer install
npm install
```

### 2. Konfigurasi Environment
Salin file `.env.example` menjadi `.env` dan sesuaikan konfigurasi database:
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Migrasi Database & Seeding Data Test
Jalankan migrasi dan seeder untuk mengisi database dengan data dummy awal beserta seluruh akun uji coba:
```bash
php artisan migrate:fresh --seed
```

### 4. Menjalankan Server Lokal
Buka dua terminal dan jalankan server lokal:
```bash
# Terminal 1: Server Laravel
php artisan serve

# Terminal 2: Vite Dev Server
npm run dev
```
Aplikasi dapat diakses melalui browser di **`http://127.0.0.1:8000`**.

---

## 🔑 Kredensial Akun Uji Coba (Testing Accounts)

Semua akun seeder menggunakan password baku: **`password`**

| Role | Email Login | Password | Nama / Keterangan |
| :--- | :--- | :--- | :--- |
| **Admin 1** | `admin@cic.ac.id` | `password` | Admin Utama CRM |
| **Admin 2** | `admin2@cic.ac.id` | `password` | Admin System Support |
| **HM 1** | `hm@cic.ac.id` | `password` | HM Marketing Eksekutif (Cirebon) |
| **HM 2** | `hm2@cic.ac.id` | `password` | HM Marketing (Majalengka & Kuningan) |
| **HM 3** | `hm3@cic.ac.id` | `password` | HM Marketing (Indramayu) |
| **SPV 1** | `spv@cic.ac.id` | `password` | Hendra Setiawan, S.Kom (SPV) |
| **SPV 2** | `spv2@cic.ac.id` | `password` | Maya Kartika, M.M (SPV) |
| **SPV 3** | `spv3@cic.ac.id` | `password` | Rian Hidayat, S.T (SPV) |
| **EO 1** | `eo@cic.ac.id` | `password` | Tim Event Organizer (EO Utama) |
| **EO 2** | `eo2@cic.ac.id` | `password` | EO Event Pameran & Expo |
| **CS 1** | `cs@cic.ac.id` | `password` | Dina Marlina (CS) |
| **CS 2** | `cs2@cic.ac.id` | `password` | Siti Nurhaliza (CS) |
| **CS 3** | `cs3@cic.ac.id` | `password` | Amanda Putri (CS) |
| **Sales 1** | `sales@cic.ac.id` | `password` | Sales Utama CIC |
| **Sales 2** | `aurel.calista@cic.ac.id` | `password` | Aurel Calista |
| **Sales 3** | `rizky.pratama@cic.ac.id` | `password` | Rizky Pratama |
| **Sales 4** | `budi.santoso@cic.ac.id` | `password` | Budi Santoso |
| **Sales 5** | `dewi.anggraini@cic.ac.id` | `password` | Dewi Anggraini |
| **Sales 6** | `fajar.ramadhan@cic.ac.id` | `password` | Fajar Ramadhan |
| **Sales 7** | `nabila.syahrani@cic.ac.id` | `password` | Nabila Syahrani |
| **Sales 8** | `kevin.wijaya@cic.ac.id` | `password` | Kevin Wijaya |

---

## 📝 Lisensi & Hak Cipta

Dikembangkan untuk **Universitas Catur Insan Cendekia (UCIC)** — CRM Pemasaran & Penerimaan Mahasiswa Baru.
