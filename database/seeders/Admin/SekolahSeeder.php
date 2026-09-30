<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use App\Models\Sekolah;
use App\Models\MasterData;
use App\Models\Wilayah;

class SekolahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $smaCat = MasterData::where('type', 'kategori_sekolah')->where('nama', 'SMA')->first();
        $smkCat = MasterData::where('type', 'kategori_sekolah')->where('nama', 'SMK')->first();
        $maCat  = MasterData::where('type', 'kategori_sekolah')->where('nama', 'MA')->first();

        // Preload Wilayah cache to speed up lookup
        $wilayahCache = [];
        $allKotas = Wilayah::where('level', 'Kota/Kabupaten')->with('children')->get();
        foreach ($allKotas as $kota) {
            foreach ($kota->children as $kec) {
                $wilayahCache[strtolower(trim($kota->nama)) . '|' . strtolower(trim($kec->nama))] = $kec->id;
            }
        }

        $getKecamatanId = function(string $kotaNama, string $kecNama) use (&$wilayahCache) {
            $key = strtolower(trim($kotaNama)) . '|' . strtolower(trim($kecNama));
            if (isset($wilayahCache[$key])) {
                return $wilayahCache[$key];
            }

            $kota = Wilayah::where('nama', $kotaNama)->where('level', 'Kota/Kabupaten')->first();
            if (!$kota) {
                $kota = Wilayah::create([
                    'kode' => 'W-' . strtoupper(substr(preg_replace('/[^A-Z]/', '', strtoupper($kotaNama)), 0, 3)),
                    'nama' => $kotaNama,
                    'level' => 'Kota/Kabupaten',
                    'status' => 'Aktif',
                ]);
            }

            $kec = Wilayah::where('parent_id', $kota->id)->where('nama', $kecNama)->first();
            if (!$kec) {
                $kec = Wilayah::create([
                    'kode' => Wilayah::generateKecamatanKode($kota, $kecNama),
                    'nama' => $kecNama,
                    'level' => 'Kecamatan',
                    'parent_id' => $kota->id,
                    'status' => 'Aktif',
                ]);
            }

            $wilayahCache[$key] = $kec->id;
            return $kec->id;
        };

        // Complete list of real-world schools for Ciayumajakuning & Brebes
        $sekolahs = [
            // ══════════════════════════════════════════════════════════════════
            // 1. KOTA CIREBON (5 Kecamatan)
            // ══════════════════════════════════════════════════════════════════
            // Harjamukti
            [
                'kode' => 'SKL-CRB-HMJ-01', 'nama' => 'SMA Negeri 8 Cirebon', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Harjamukti', 'alamat' => 'Jl. Ahmad Yani No. 15, Harjamukti',
                'telepon' => '0231-201999', 'email' => 'sman8cirebon@sch.id', 'pic_name' => 'H. Dedi Supriyadi, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456025', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-HMJ-02', 'nama' => 'SMA Negeri 9 Cirebon', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Harjamukti', 'alamat' => 'Jl. Rajawali Raya No. 8, Harjamukti',
                'telepon' => '0231-201888', 'email' => 'sman9cirebon@sch.id', 'pic_name' => 'Dra. Hj. Nunung', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456027', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-HMJ-03', 'nama' => 'SMK Negeri 3 Cirebon', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Harjamukti', 'alamat' => 'Jl. Ciremai Raya No. 1, Harjamukti',
                'telepon' => '0231-207766', 'email' => 'smkn3cirebon@sch.id', 'pic_name' => 'Drs. Asep Saepudin', 'pic_jabatan' => 'BKK / Hubinmas', 'pic_phone' => '08123456026', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-HMJ-04', 'nama' => 'MAN 1 Kota Cirebon', 'tier' => 'B', 'kategori' => 'MA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Harjamukti', 'alamat' => 'Jl. Pilang Raya No. 4, Harjamukti',
                'telepon' => '0231-201234', 'email' => 'man1cirebon@kemenag.go.id', 'pic_name' => 'Dra. Hj. Siti Rohmah', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456008', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-HMJ-05', 'nama' => 'SMA IT Akmala Sabila', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Harjamukti', 'alamat' => 'Jl. Rajawali Timur No. 12, Harjamukti',
                'telepon' => '0231-205566', 'email' => 'info@akmalasabila.sch.id', 'pic_name' => 'Ust. M. Hidayat, Lc', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456028', 'status' => 'Aktif'
            ],

            // Kejaksan
            [
                'kode' => 'SKL-CRB-KJS-01', 'nama' => 'SMA Negeri 1 Cirebon', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kejaksan', 'alamat' => 'Jl. Wahidin No. 81, Sukapura',
                'telepon' => '0231-203541', 'email' => 'info@sman1cirebon.sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456001', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-KJS-02', 'nama' => 'SMA Negeri 6 Cirebon', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kejaksan', 'alamat' => 'Jl. Wahidin No. 79, Sukapura',
                'telepon' => '0231-203542', 'email' => 'info@sman6cirebon.sch.id', 'pic_name' => 'Drs. H. Sutisna', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456011', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-KJS-03', 'nama' => 'SMK Informatika Al-Irsyad', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kejaksan', 'alamat' => 'Jl. Panjunan No. 54, Kejaksan',
                'telepon' => '0231-208765', 'email' => 'smk@alirsyad-crb.sch.id', 'pic_name' => 'Faisal Basri, M.Kom', 'pic_jabatan' => 'Kaprog RPL', 'pic_phone' => '08123456007', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-KJS-04', 'nama' => 'SMA Santa Maria 1 Cirebon', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kejaksan', 'alamat' => 'Jl. Sisingamangaraja No. 22',
                'telepon' => '0231-202311', 'email' => 'santamaria1@sch.id', 'pic_name' => 'Theresia Endang, S.Pd', 'pic_jabatan' => 'Koordinator BK', 'pic_phone' => '08123456004', 'status' => 'Aktif'
            ],

            // Kesambi
            [
                'kode' => 'SKL-CRB-KSB-01', 'nama' => 'SMA Negeri 2 Cirebon', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kesambi', 'alamat' => 'Jl. Dr. Cipto Mangunkusumo No. 1',
                'telepon' => '0231-204120', 'email' => 'info@sman2cirebon.sch.id', 'pic_name' => 'Hj. Nurlaila, M.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456002', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-KSB-02', 'nama' => 'SMA Negeri 3 Cirebon', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kesambi', 'alamat' => 'Jl. Nyi Ageng Serang No. 33',
                'telepon' => '0231-207889', 'email' => 'info@sman3cirebon.sch.id', 'pic_name' => 'Bambang Irawan, S.Pd', 'pic_jabatan' => 'Kesiswaan', 'pic_phone' => '08123456003', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-KSB-03', 'nama' => 'SMA Negeri 4 Cirebon', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kesambi', 'alamat' => 'Jl. Perjuangan No. 1, Kesambi',
                'telepon' => '0231-205111', 'email' => 'sman4cirebon@sch.id', 'pic_name' => 'Drs. H. Sukardi', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456029', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-KSB-04', 'nama' => 'SMA Negeri 5 Cirebon', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kesambi', 'alamat' => 'Jl. Dr. Cipto Mangunkusumo No. 18',
                'telepon' => '0231-205222', 'email' => 'sman5cirebon@sch.id', 'pic_name' => 'Dra. Endah Sulistyo', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456030', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-KSB-05', 'nama' => 'SMK Negeri 1 Cirebon', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kesambi', 'alamat' => 'Jl. Perjuangan No. 10',
                'telepon' => '0231-200987', 'email' => 'smkn1crb@sch.id', 'pic_name' => 'Dr. H. Ahmad Santoso', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456005', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-KSB-06', 'nama' => 'SMK Negeri 2 Cirebon', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kesambi', 'alamat' => 'Jl. Dr. Cipto Mangunkusumo No. 20',
                'telepon' => '0231-206543', 'email' => 'smkn2cirebon@sch.id', 'pic_name' => 'Indra Gunawan, S.T', 'pic_jabatan' => 'BKK / Hubinmas', 'pic_phone' => '08123456006', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-KSB-07', 'nama' => 'SMK Farmasi Cirebon', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Kesambi', 'alamat' => 'Jl. Perjuangan No. 9, Sunyaragi',
                'telepon' => '0231-206789', 'email' => 'info@smkfarmasicirebon.sch.id', 'pic_name' => 'apt. Fitri Rahmawati, S.Farm', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456031', 'status' => 'Aktif'
            ],

            // Lemahwungkuk
            [
                'kode' => 'SKL-CRB-LMW-01', 'nama' => 'SMA Negeri 7 Cirebon', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Lemahwungkuk', 'alamat' => 'Jl. Lemahwungkuk No. 12',
                'telepon' => '0231-208877', 'email' => 'sman7cirebon@sch.id', 'pic_name' => 'Drs. Hendra Setiawan', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456023', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-LMW-02', 'nama' => 'SMK Pelayaran Bahari Cirebon', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Lemahwungkuk', 'alamat' => 'Jl. Samadikun No. 8',
                'telepon' => '0231-209911', 'email' => 'smkbahari@sch.id', 'pic_name' => 'Kapten Suryono', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456024', 'status' => 'Aktif'
            ],

            // Pekalipan
            [
                'kode' => 'SKL-CRB-PLP-01', 'nama' => 'SMA Santa Maria Cirebon', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Pekalipan', 'alamat' => 'Jl. Sisingamangaraja No. 22',
                'telepon' => '0231-202311', 'email' => 'info@santamaria-crb.sch.id', 'pic_name' => 'Theresia Endang, S.Pd', 'pic_jabatan' => 'Koordinator BK', 'pic_phone' => '08123456004', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-CRB-PLP-02', 'nama' => 'SMK Veteran Cirebon', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kota Cirebon', 'kecamatan' => 'Pekalipan', 'alamat' => 'Jl. Kanggraksan No. 45',
                'telepon' => '0231-203344', 'email' => 'smkveteran@sch.id', 'pic_name' => 'Drs. H. Maman', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456022', 'status' => 'Aktif'
            ],

            // ══════════════════════════════════════════════════════════════════
            // 2. KABUPATEN CIREBON (40 Kecamatan)
            // ══════════════════════════════════════════════════════════════════
            // Arjawinangun
            [
                'kode' => 'SKL-KCRB-ARJ-01', 'nama' => 'SMA Negeri 1 Arjawinangun', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Arjawinangun', 'alamat' => 'Jl. Nyimas Gandasari No. 1, Arjawinangun',
                'telepon' => '0231-357111', 'email' => 'sman1arjawinangun@sch.id', 'pic_name' => 'Drs. H. Solihin, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456015', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-ARJ-02', 'nama' => 'SMK Negeri 1 Arjawinangun', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Arjawinangun', 'alamat' => 'Jl. Ki Hajar Dewantara No. 12, Arjawinangun',
                'telepon' => '0231-357222', 'email' => 'smkn1arjawinangun@sch.id', 'pic_name' => 'Ir. Budi Santoso', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456032', 'status' => 'Aktif'
            ],
            // Astanajapura
            [
                'kode' => 'SKL-KCRB-AST-01', 'nama' => 'SMA Negeri 1 Astanajapura', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Astanajapura', 'alamat' => 'Jl. K.H. Wahid Hasyim, Astanajapura',
                'telepon' => '0231-510001', 'email' => 'sman1asjap@sch.id', 'pic_name' => 'Drs. H. Taufik Hidayat', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456014', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-AST-02', 'nama' => 'SMK Negeri 1 Astanajapura', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Astanajapura', 'alamat' => 'Jl. Buntet Pesantren, Astanajapura',
                'telepon' => '0231-510002', 'email' => 'smkn1asjap@sch.id', 'pic_name' => 'K.H. Ahmad Fauzi, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456033', 'status' => 'Aktif'
            ],
            // Babakan
            [
                'kode' => 'SKL-KCRB-BBK-01', 'nama' => 'SMA Negeri 1 Babakan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Babakan', 'alamat' => 'Jl. Pangeran Sutajaya No. 1, Babakan',
                'telepon' => '0231-661001', 'email' => 'sman1babakan@sch.id', 'pic_name' => 'Drs. H. Ruspendi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456017', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-BBK-02', 'nama' => 'SMK Negeri 1 Babakan', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Babakan', 'alamat' => 'Jl. Raya Babakan KM 2, Babakan',
                'telepon' => '0231-661002', 'email' => 'smkn1babakan@sch.id', 'pic_name' => 'H. Suherman, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456034', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-BBK-03', 'nama' => 'MAN 4 Cirebon', 'tier' => 'B', 'kategori' => 'MA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Babakan', 'alamat' => 'Jl. Pangeran Sutajaya No. 25, Babakan',
                'telepon' => '0231-661003', 'email' => 'man4cirebon@kemenag.go.id', 'pic_name' => 'Dra. Hj. Aminah', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456035', 'status' => 'Aktif'
            ],
            // Beber
            [
                'kode' => 'SKL-KCRB-BBR-01', 'nama' => 'SMA Negeri 1 Beber', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Beber', 'alamat' => 'Jl. Raya Beber No. 18, Beber',
                'telepon' => '0231-771001', 'email' => 'sman1beber@sch.id', 'pic_name' => 'Drs. H. Maman', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456036', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-BBR-02', 'nama' => 'SMK Negeri 1 Beber', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Beber', 'alamat' => 'Jl. Pangeran Drajat No. 5, Beber',
                'telepon' => '0231-771002', 'email' => 'smkn1beber@sch.id', 'pic_name' => 'Agus Prasetyo, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456037', 'status' => 'Aktif'
            ],
            // Ciledug
            [
                'kode' => 'SKL-KCRB-CLD-01', 'nama' => 'SMA Negeri 1 Ciledug', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Ciledug', 'alamat' => 'Jl. Merdeka Barat No. 23, Ciledug',
                'telepon' => '0231-662001', 'email' => 'sman1ciledug@sch.id', 'pic_name' => 'Drs. H. Didi Supriyadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456038', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-CLD-02', 'nama' => 'SMK Negeri 1 Ciledug', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Ciledug', 'alamat' => 'Jl. Mayjen Sutoyo No. 10, Ciledug',
                'telepon' => '0231-662002', 'email' => 'smkn1ciledug@sch.id', 'pic_name' => 'Eko Prasetyo, M.T', 'pic_jabatan' => 'BKK Hubin', 'pic_phone' => '08123456039', 'status' => 'Aktif'
            ],
            // Ciwaringin
            [
                'kode' => 'SKL-KCRB-CWR-01', 'nama' => 'SMA Negeri 1 Ciwaringin', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Ciwaringin', 'alamat' => 'Jl. Babakan-Ciwaringin No. 1',
                'telepon' => '0231-358001', 'email' => 'sman1ciwaringin@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456016', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-CWR-02', 'nama' => 'MAN 2 Cirebon', 'tier' => 'A', 'kategori' => 'MA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Ciwaringin', 'alamat' => 'Jl. Babakan Timur No. 1, Ciwaringin',
                'telepon' => '0231-358002', 'email' => 'man2cirebon@kemenag.go.id', 'pic_name' => 'Drs. H. Muhaimin', 'pic_jabatan' => 'Kepala Madrasah', 'pic_phone' => '08123456040', 'status' => 'Aktif'
            ],
            // Depok
            [
                'kode' => 'SKL-KCRB-DPK-01', 'nama' => 'SMA Negeri 1 Depok', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Depok', 'alamat' => 'Jl. Kasugengan Kidul No. 1, Depok',
                'telepon' => '0231-341001', 'email' => 'sman1depokcrb@sch.id', 'pic_name' => 'Dra. Hj. Ratna', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456041', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-DPK-02', 'nama' => 'SMK Ulil Albab Depok', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Depok', 'alamat' => 'Jl. Pangeran Cakrabuana, Depok',
                'telepon' => '0231-341002', 'email' => 'smkulilalbab@sch.id', 'pic_name' => 'K.H. Lukman Hakim, S.Pd.I', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456042', 'status' => 'Aktif'
            ],
            // Dukupuntang
            [
                'kode' => 'SKL-KCRB-DKP-01', 'nama' => 'SMA Negeri 1 Dukupuntang', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Dukupuntang', 'alamat' => 'Jl. Nyi Ageng Serang No. 8, Dukupuntang',
                'telepon' => '0231-830001', 'email' => 'sman1dukupuntang@sch.id', 'pic_name' => 'Drs. H. Sukirno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456043', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-DKP-02', 'nama' => 'SMK Negeri 1 Dukupuntang', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Dukupuntang', 'alamat' => 'Jl. Cikalahang No. 3, Dukupuntang',
                'telepon' => '0231-830002', 'email' => 'smkn1dukupuntang@sch.id', 'pic_name' => 'Ahmad Fauzan, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456044', 'status' => 'Aktif'
            ],
            // Gebang
            [
                'kode' => 'SKL-KCRB-GBG-01', 'nama' => 'SMA Negeri 1 Gebang', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Gebang', 'alamat' => 'Jl. Pangeran Sutajaya No. 14, Gebang',
                'telepon' => '0231-663001', 'email' => 'sman1gebang@sch.id', 'pic_name' => 'Drs. H. Abdul Ghofur', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456045', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-GBG-02', 'nama' => 'SMK Negeri 1 Gebang', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Gebang', 'alamat' => 'Jl. Kalimaro No. 20, Gebang',
                'telepon' => '0231-663002', 'email' => 'smkn1gebang@sch.id', 'pic_name' => 'Rahmat Hidayat, M.Kom', 'pic_jabatan' => 'Waka Hubin', 'pic_phone' => '08123456046', 'status' => 'Aktif'
            ],
            // Gegesik
            [
                'kode' => 'SKL-KCRB-GGS-01', 'nama' => 'SMA Negeri 1 Gegesik', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Gegesik', 'alamat' => 'Jl. Raya Gegesik No. 21, Gegesik',
                'telepon' => '0231-356001', 'email' => 'sman1gegesik@sch.id', 'pic_name' => 'Drs. Supardi, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456047', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-GGS-02', 'nama' => 'SMK Negeri 1 Gegesik', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Gegesik', 'alamat' => 'Jl. Syekh Bayanillah No. 5, Gegesik',
                'telepon' => '0231-356002', 'email' => 'smkn1gegesik@sch.id', 'pic_name' => 'Drs. H. Bambang', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456048', 'status' => 'Aktif'
            ],
            // Gempol
            [
                'kode' => 'SKL-KCRB-GMP-01', 'nama' => 'SMA Negeri 1 Gempol', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Gempol', 'alamat' => 'Jl. Palimanan-Gempol KM 2',
                'telepon' => '0231-343001', 'email' => 'sman1gempol@sch.id', 'pic_name' => 'Drs. H. Taryono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456049', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-GMP-02', 'nama' => 'SMK Negeri 1 Gempol', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Gempol', 'alamat' => 'Jl. Raya Ciwaringin-Gempol No. 8',
                'telepon' => '0231-343002', 'email' => 'smkn1gempol@sch.id', 'pic_name' => 'Hendra Gunawan, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456050', 'status' => 'Aktif'
            ],
            // Greged
            [
                'kode' => 'SKL-KCRB-GRG-01', 'nama' => 'SMA Negeri 1 Greged', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Greged', 'alamat' => 'Jl. Durian No. 12, Greged',
                'telepon' => '0231-772001', 'email' => 'sman1greged@sch.id', 'pic_name' => 'Dra. Hj. Yayah', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456051', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-GRG-02', 'nama' => 'SMK Negeri 1 Greged', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Greged', 'alamat' => 'Jl. Raya Lebakmekar No. 4, Greged',
                'telepon' => '0231-772002', 'email' => 'smkn1greged@sch.id', 'pic_name' => 'Drs. Didi', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456052', 'status' => 'Aktif'
            ],
            // Gunungjati
            [
                'kode' => 'SKL-KCRB-GNJ-01', 'nama' => 'SMA Negeri 1 Gunungjati', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Gunungjati', 'alamat' => 'Jl. Sunan Gunung Jati KM 5',
                'telepon' => '0231-209001', 'email' => 'sman1gunungjati@sch.id', 'pic_name' => 'Drs. H. Syarifudin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456053', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-GNJ-02', 'nama' => 'SMK Negeri 1 Gunung Jati', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Gunungjati', 'alamat' => 'Jl. Raya Mertasinga No. 11, Gunungjati',
                'telepon' => '0231-209002', 'email' => 'smkn1gunungjati@sch.id', 'pic_name' => 'Drs. H. Asyari', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456054', 'status' => 'Aktif'
            ],
            // Jamblang
            [
                'kode' => 'SKL-KCRB-JMB-01', 'nama' => 'SMA Negeri 1 Jamblang', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Jamblang', 'alamat' => 'Jl. Nyi Mas Ratu No. 1, Jamblang',
                'telepon' => '0231-344001', 'email' => 'sman1jamblang@sch.id', 'pic_name' => 'Drs. H. Subur', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456055', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-JMB-02', 'nama' => 'SMK Negeri 1 Jamblang', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Jamblang', 'alamat' => 'Jl. Nyi Mas Gandasari No. 10, Jamblang',
                'telepon' => '0231-344002', 'email' => 'smkn1jamblang@sch.id', 'pic_name' => 'Drs. H. Bambang Sugiarto', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456056', 'status' => 'Aktif'
            ],
            // Kaliwedi
            [
                'kode' => 'SKL-KCRB-KLW-01', 'nama' => 'SMA Negeri 1 Kaliwedi', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Kaliwedi', 'alamat' => 'Jl. Raya Kaliwedi No. 17, Kaliwedi',
                'telepon' => '0231-355001', 'email' => 'sman1kaliwedi@sch.id', 'pic_name' => 'Drs. Suwarno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456057', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-KLW-02', 'nama' => 'SMK Negeri 1 Kaliwedi', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Kaliwedi', 'alamat' => 'Jl. Guwa Kidul No. 5, Kaliwedi',
                'telepon' => '0231-355002', 'email' => 'smkn1kaliwedi@sch.id', 'pic_name' => 'Nurjaman, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456058', 'status' => 'Aktif'
            ],
            // Kapetakan
            [
                'kode' => 'SKL-KCRB-KPT-01', 'nama' => 'SMA Negeri 1 Kapetakan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Kapetakan', 'alamat' => 'Jl. Raya Sunan Gunung Jati No. 88',
                'telepon' => '0231-209555', 'email' => 'sman1kapetakan@sch.id', 'pic_name' => 'Drs. H. Kasturi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456059', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-KPT-02', 'nama' => 'SMK Negeri 1 Kapetakan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Kapetakan', 'alamat' => 'Jl. Raya Kapetakan No. 12',
                'telepon' => '0231-209556', 'email' => 'smkn1kapetakan@sch.id', 'pic_name' => 'Agus Salim, M.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456060', 'status' => 'Aktif'
            ],
            // Karangsembung
            [
                'kode' => 'SKL-KCRB-KSB-01', 'nama' => 'SMA Negeri 1 Karangsembung', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Karangsembung', 'alamat' => 'Jl. Karangsuwung No. 2, Karangsembung',
                'telepon' => '0231-664001', 'email' => 'sman1karangsembung@sch.id', 'pic_name' => 'Drs. H. Nana', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456061', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-KSB-02', 'nama' => 'SMK Negeri 1 Karangsembung', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Karangsembung', 'alamat' => 'Jl. Kubangkarang No. 7, Karangsembung',
                'telepon' => '0231-664002', 'email' => 'smkn1karangsembung@sch.id', 'pic_name' => 'Dedi Iskandar, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456062', 'status' => 'Aktif'
            ],
            // Karangwareng
            [
                'kode' => 'SKL-KCRB-KWR-01', 'nama' => 'SMA Negeri 1 Karangwareng', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Karangwareng', 'alamat' => 'Jl. Raya Karangwareng No. 3',
                'telepon' => '0231-665001', 'email' => 'sman1karangwareng@sch.id', 'pic_name' => 'Drs. H. Kusnadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456063', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-KWR-02', 'nama' => 'SMK Negeri 1 Karangwareng', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Karangwareng', 'alamat' => 'Jl. Pangeran Girilaya No. 1, Karangwareng',
                'telepon' => '0231-665002', 'email' => 'smkn1karangwareng@sch.id', 'pic_name' => 'Asep Gunawan, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456064', 'status' => 'Aktif'
            ],
            // Kedawung
            [
                'kode' => 'SKL-KCRB-KDW-01', 'nama' => 'SMA Negeri 1 Kedawung', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Kedawung', 'alamat' => 'Jl. Tuparev No. 70, Kedawung',
                'telepon' => '0231-203401', 'email' => 'sman1kedawung@sch.id', 'pic_name' => 'Drs. H. Rasidi, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456012', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-KDW-02', 'nama' => 'SMK Negeri 1 Kedawung', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Kedawung', 'alamat' => 'Jl. Tuparev No. 87, Kedawung',
                'telepon' => '0231-203402', 'email' => 'smkn1kedawung@sch.id', 'pic_name' => 'Hj. Sri Rahayu, M.M', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456013', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-KDW-03', 'nama' => 'SMK Yadika Kedawung', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Kedawung', 'alamat' => 'Jl. Tuparev No. 112, Kedawung',
                'telepon' => '0231-203403', 'email' => 'smkyadikacedawung@sch.id', 'pic_name' => 'Drs. Antonius Siregar', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456065', 'status' => 'Aktif'
            ],
            // Klangenan
            [
                'kode' => 'SKL-KCRB-KLG-01', 'nama' => 'SMA Negeri 1 Klangenan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Klangenan', 'alamat' => 'Jl. Oto Iskandardinata No. 5, Klangenan',
                'telepon' => '0231-345001', 'email' => 'sman1klangenan@sch.id', 'pic_name' => 'Drs. H. Mamat', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456066', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-KLG-02', 'nama' => 'SMK Negeri 1 Klangenan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Klangenan', 'alamat' => 'Jl. Raya Klangenan No. 18, Klangenan',
                'telepon' => '0231-345002', 'email' => 'smkn1klangenan@sch.id', 'pic_name' => 'Agus Supriyadi, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456067', 'status' => 'Aktif'
            ],
            // Lemahabang
            [
                'kode' => 'SKL-KCRB-LMH-01', 'nama' => 'SMA Negeri 1 Lemahabang', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Lemahabang', 'alamat' => 'Jl. Raya Lemahabang No. 10, Lemahabang',
                'telepon' => '0231-666001', 'email' => 'sman1lemahabang@sch.id', 'pic_name' => 'Drs. H. Ade Sunardi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456068', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-LMH-02', 'nama' => 'SMK Negeri 1 Lemahabang', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Lemahabang', 'alamat' => 'Jl. KH. Wahid Hasyim No. 9, Lemahabang',
                'telepon' => '0231-666002', 'email' => 'smkn1lemahabang@sch.id', 'pic_name' => 'Drs. H. Maman Suratman', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456069', 'status' => 'Aktif'
            ],
            // Losari
            [
                'kode' => 'SKL-KCRB-LSR-01', 'nama' => 'SMA Negeri 1 Losari', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Losari', 'alamat' => 'Jl. Raya Losari No. 12, Losari',
                'telepon' => '0231-881001', 'email' => 'sman1losari@sch.id', 'pic_name' => 'Drs. H. Mulyadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456070', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-LSR-02', 'nama' => 'SMK Negeri 1 Losari', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Losari', 'alamat' => 'Jl. Raya Cisanggarung No. 5, Losari',
                'telepon' => '0231-881002', 'email' => 'smkn1losari@sch.id', 'pic_name' => 'Ir. Hartono', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456071', 'status' => 'Aktif'
            ],
            // Mundu
            [
                'kode' => 'SKL-KCRB-MND-01', 'nama' => 'SMA Negeri 1 Mundu', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Mundu', 'alamat' => 'Jl. Luwung - Setupatok, Mundu',
                'telepon' => '0231-511001', 'email' => 'sman1mundu@sch.id', 'pic_name' => 'Drs. H. Suwito', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456072', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-MND-02', 'nama' => 'SMK Negeri 1 Mundu Cirebon', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Mundu', 'alamat' => 'Jl. Kyai Haji Abdul Halim No. 1, Cirebon',
                'telepon' => '0231-511002', 'email' => 'smkn1mundu@sch.id', 'pic_name' => 'Dr. H. Ruspendi, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456073', 'status' => 'Aktif'
            ],
            // Pabedilan
            [
                'kode' => 'SKL-KCRB-PBD-01', 'nama' => 'SMA Negeri 1 Pabedilan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Pabedilan', 'alamat' => 'Jl. Mayjen Sutoyo No. 45, Pabedilan',
                'telepon' => '0231-667001', 'email' => 'sman1pabedilan@sch.id', 'pic_name' => 'Drs. H. Tarkim', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456074', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-PBD-02', 'nama' => 'SMK Negeri 1 Pabedilan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Pabedilan', 'alamat' => 'Jl. Babakan-Pabedilan KM 3',
                'telepon' => '0231-667002', 'email' => 'smkn1pabedilan@sch.id', 'pic_name' => 'Dra. Hj. Nunung', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456075', 'status' => 'Aktif'
            ],
            // Pabuaran
            [
                'kode' => 'SKL-KCRB-PBR-01', 'nama' => 'SMA Negeri 1 Pabuaran', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Pabuaran', 'alamat' => 'Jl. Pangeran Sutajaya No. 88, Pabuaran',
                'telepon' => '0231-668001', 'email' => 'sman1pabuaran@sch.id', 'pic_name' => 'Drs. H. Wahyudi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456076', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-PBR-02', 'nama' => 'SMK Negeri 1 Pabuaran', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Pabuaran', 'alamat' => 'Jl. Raya Pabuaran No. 12',
                'telepon' => '0231-668002', 'email' => 'smkn1pabuaran@sch.id', 'pic_name' => 'Samsudin, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456077', 'status' => 'Aktif'
            ],
            // Palimanan
            [
                'kode' => 'SKL-KCRB-PLM-01', 'nama' => 'SMA Negeri 1 Palimanan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Palimanan', 'alamat' => 'Jl. Dr. Setiabudi No. 1, Pegagan, Palimanan',
                'telepon' => '0231-341111', 'email' => 'sman1palimanan@sch.id', 'pic_name' => 'Drs. H. Karnadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456010', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-PLM-02', 'nama' => 'SMK Negeri 1 Palimanan', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Palimanan', 'alamat' => 'Jl. KH Agus Salim No. 5, Palimanan',
                'telepon' => '0231-341222', 'email' => 'smkn1palimanan@sch.id', 'pic_name' => 'Dr. H. Subarjo', 'pic_jabatan' => 'Waka Hubin', 'pic_phone' => '08123456078', 'status' => 'Aktif'
            ],
            // Pangenan
            [
                'kode' => 'SKL-KCRB-PGN-01', 'nama' => 'SMA Negeri 1 Pangenan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Pangenan', 'alamat' => 'Jl. Raya Pantura Astanamukti, Pangenan',
                'telepon' => '0231-512001', 'email' => 'sman1pangenan@sch.id', 'pic_name' => 'Drs. H. Maman', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456079', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-PGN-02', 'nama' => 'SMK Negeri 1 Pangenan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Pangenan', 'alamat' => 'Jl. Pangeran Diponegoro No. 8, Pangenan',
                'telepon' => '0231-512002', 'email' => 'smkn1pangenan@sch.id', 'pic_name' => 'Agus Santoso, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456080', 'status' => 'Aktif'
            ],
            // Panguragan
            [
                'kode' => 'SKL-KCRB-PGR-01', 'nama' => 'SMA Negeri 1 Panguragan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Panguragan', 'alamat' => 'Jl. Panguragan Kulon No. 5',
                'telepon' => '0231-354001', 'email' => 'sman1panguragan@sch.id', 'pic_name' => 'Drs. H. Suwandi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456081', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-PGR-02', 'nama' => 'SMK Negeri 1 Panguragan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Panguragan', 'alamat' => 'Jl. Nyi Mas Gandasari No. 15, Panguragan',
                'telepon' => '0231-354002', 'email' => 'smkn1panguragan@sch.id', 'pic_name' => 'Budi Waluyo, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456082', 'status' => 'Aktif'
            ],
            // Pasaleman
            [
                'kode' => 'SKL-KCRB-PSL-01', 'nama' => 'SMA Negeri 1 Pasaleman', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Pasaleman', 'alamat' => 'Jl. Cilengkrang No. 11, Pasaleman',
                'telepon' => '0231-669001', 'email' => 'sman1pasaleman@sch.id', 'pic_name' => 'Drs. H. Sukmana', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456083', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-PSL-02', 'nama' => 'SMK Negeri 1 Pasaleman', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Pasaleman', 'alamat' => 'Jl. Raya Tonjong No. 6, Pasaleman',
                'telepon' => '0231-669002', 'email' => 'smkn1pasaleman@sch.id', 'pic_name' => 'Dedi Supriadi, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456084', 'status' => 'Aktif'
            ],
            // Plered
            [
                'kode' => 'SKL-KCRB-PLR-01', 'nama' => 'SMA Negeri 1 Plered', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Plered', 'alamat' => 'Jl. Syekh Datul Kahfi No. 5, Plered',
                'telepon' => '0231-321001', 'email' => 'sman1plered@sch.id', 'pic_name' => 'Drs. H. Kholid', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456085', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-PLR-02', 'nama' => 'SMK Negeri 1 Plered', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Plered', 'alamat' => 'Jl. Nyi Ageng Serang No. 12, Plered',
                'telepon' => '0231-321002', 'email' => 'smkn1plered@sch.id', 'pic_name' => 'Ir. Bambang Sugiarto', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456086', 'status' => 'Aktif'
            ],
            // Plumbon
            [
                'kode' => 'SKL-KCRB-PLB-01', 'nama' => 'SMA Negeri 1 Plumbon', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Plumbon', 'alamat' => 'Jl. Pangeran Antasari No. 4, Plumbon',
                'telepon' => '0231-321111', 'email' => 'sman1plumbon@sch.id', 'pic_name' => 'Drs. H. Masturo', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456018', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-PLB-02', 'nama' => 'SMK Negeri 1 Plumbon', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Plumbon', 'alamat' => 'Jl. Raya Cirebon-Bandung KM 10, Plumbon',
                'telepon' => '0231-321222', 'email' => 'smkn1plumbon@sch.id', 'pic_name' => 'Drs. H. Maman', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456087', 'status' => 'Aktif'
            ],
            // Sedong
            [
                'kode' => 'SKL-KCRB-SDG-01', 'nama' => 'SMA Negeri 1 Sedong', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Sedong', 'alamat' => 'Jl. Putat Sedong No. 5, Sedong',
                'telepon' => '0231-660001', 'email' => 'sman1sedong@sch.id', 'pic_name' => 'Drs. H. Suharto', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456088', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-SDG-02', 'nama' => 'SMK Negeri 1 Sedong', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Sedong', 'alamat' => 'Jl. Raya Panongan No. 9, Sedong',
                'telepon' => '0231-660002', 'email' => 'smkn1sedong@sch.id', 'pic_name' => 'Ahmad Rifa\'i, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456089', 'status' => 'Aktif'
            ],
            // Sumber
            [
                'kode' => 'SKL-KCRB-SBR-01', 'nama' => 'SMA Negeri 1 Sumber', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Sumber', 'alamat' => 'Jl. Perjuangan No. 1, Sumber',
                'telepon' => '0231-321456', 'email' => 'info@sman1sumber.sch.id', 'pic_name' => 'Drs. H. Kosim', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456009', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-SBR-02', 'nama' => 'SMK Negeri 1 Sumber', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Sumber', 'alamat' => 'Jl. Ki Gede Mayung No. 12, Sumber',
                'telepon' => '0231-321457', 'email' => 'smkn1sumber@sch.id', 'pic_name' => 'Dr. H. Ruspendi', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456090', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-SBR-03', 'nama' => 'MAN 1 Cirebon', 'tier' => 'A', 'kategori' => 'MA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Sumber', 'alamat' => 'Jl. R. Dewi Sartika No. 36, Sumber',
                'telepon' => '0231-321458', 'email' => 'man1cirebon@kemenag.go.id', 'pic_name' => 'Drs. H. Imron, M.Ag', 'pic_jabatan' => 'Kepala Madrasah', 'pic_phone' => '08123456091', 'status' => 'Aktif'
            ],
            // Suranenggala
            [
                'kode' => 'SKL-KCRB-SRN-01', 'nama' => 'SMA Negeri 1 Suranenggala', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Suranenggala', 'alamat' => 'Jl. Sunan Gunung Jati KM 14, Suranenggala',
                'telepon' => '0231-209888', 'email' => 'sman1suranenggala@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456092', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-SRN-02', 'nama' => 'SMK Negeri 1 Suranenggala', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Suranenggala', 'alamat' => 'Jl. Karangreja No. 4, Suranenggala',
                'telepon' => '0231-209889', 'email' => 'smkn1suranenggala@sch.id', 'pic_name' => 'Suherman, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456093', 'status' => 'Aktif'
            ],
            // Susukan
            [
                'kode' => 'SKL-KCRB-SSK-01', 'nama' => 'SMA Negeri 1 Susukan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Susukan', 'alamat' => 'Jl. Raya Susukan-Gegesik KM 2',
                'telepon' => '0231-353001', 'email' => 'sman1susukan@sch.id', 'pic_name' => 'Drs. H. Didi Sutisna', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456094', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-SSK-02', 'nama' => 'SMK Negeri 1 Susukan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Susukan', 'alamat' => 'Jl. Budi Utomo No. 10, Susukan',
                'telepon' => '0231-353002', 'email' => 'smkn1susukan@sch.id', 'pic_name' => 'Asep Saepul, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456095', 'status' => 'Aktif'
            ],
            // Susukanlebak
            [
                'kode' => 'SKL-KCRB-SSL-01', 'nama' => 'SMA Negeri 1 Susukanlebak', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Susukanlebak', 'alamat' => 'Jl. Pasawahan No. 6, Susukanlebak',
                'telepon' => '0231-660111', 'email' => 'sman1susukanlebak@sch.id', 'pic_name' => 'Drs. H. Karnoto', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456096', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-SSL-02', 'nama' => 'SMK Negeri 1 Susukanlebak', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Susukanlebak', 'alamat' => 'Jl. Ciawiasih No. 2, Susukanlebak',
                'telepon' => '0231-660112', 'email' => 'smkn1susukanlebak@sch.id', 'pic_name' => 'Dedi Gunawan, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456097', 'status' => 'Aktif'
            ],
            // Talun
            [
                'kode' => 'SKL-KCRB-TLN-01', 'nama' => 'SMA Negeri 1 Talun', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Talun', 'alamat' => 'Jl. Pangeran Cakrabuana No. 28, Talun',
                'telepon' => '0231-831001', 'email' => 'sman1talun@sch.id', 'pic_name' => 'Drs. H. Mulyadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456098', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-TLN-02', 'nama' => 'SMK Negeri 1 Talun', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Talun', 'alamat' => 'Jl. Kemantren No. 9, Talun',
                'telepon' => '0231-831002', 'email' => 'smkn1talun@sch.id', 'pic_name' => 'Rahmat Hidayat, M.Pd', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456099', 'status' => 'Aktif'
            ],
            // Tengahtani
            [
                'kode' => 'SKL-KCRB-TGT-01', 'nama' => 'SMA Negeri 1 Tengahtani', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Tengahtani', 'alamat' => 'Jl. Raya Pantura Battembat, Tengahtani',
                'telepon' => '0231-322001', 'email' => 'sman1tengahtani@sch.id', 'pic_name' => 'Drs. H. Sukardi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456100', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-TGT-02', 'nama' => 'SMK Mandiri Tengahtani', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Tengahtani', 'alamat' => 'Jl. Raya Kemlakagede No. 8, Tengahtani',
                'telepon' => '0231-322002', 'email' => 'smkmandiritgt@sch.id', 'pic_name' => 'Dra. Hj. Nunung', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456101', 'status' => 'Aktif'
            ],
            // Waled
            [
                'kode' => 'SKL-KCRB-WLD-01', 'nama' => 'SMA Negeri 1 Waled', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Waled', 'alamat' => 'Jl. Prabu Kiansantang No. 7, Waled',
                'telepon' => '0231-660222', 'email' => 'sman1waled@sch.id', 'pic_name' => 'Drs. H. Solihin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456102', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-WLD-02', 'nama' => 'SMK Negeri 1 Waled', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Waled', 'alamat' => 'Jl. Raya Waled No. 15, Waled',
                'telepon' => '0231-660223', 'email' => 'smkn1waled@sch.id', 'pic_name' => 'Budi Santoso, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456103', 'status' => 'Aktif'
            ],
            // Weru
            [
                'kode' => 'SKL-KCRB-WRU-01', 'nama' => 'SMA Negeri 1 Weru', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Weru', 'alamat' => 'Jl. Fatahillah No. 45, Megu Cilik, Weru',
                'telepon' => '0231-321789', 'email' => 'info@sman1weru.sch.id', 'pic_name' => 'Dra. Hj. Sri Wahyuni', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456019', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-WRU-02', 'nama' => 'SMK Negeri 1 Weru', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Weru', 'alamat' => 'Jl. Otista No. 45, Weru',
                'telepon' => '0231-321790', 'email' => 'smkn1weru@sch.id', 'pic_name' => 'Drs. H. Maman Suratman', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456020', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KCRB-WRU-03', 'nama' => 'SMK Karya Nasional Weru', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Cirebon', 'kecamatan' => 'Weru', 'alamat' => 'Jl. Fatahillah No. 88, Weru',
                'telepon' => '0231-321791', 'email' => 'smkkarnasweru@sch.id', 'pic_name' => 'Drs. H. Subur', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456104', 'status' => 'Aktif'
            ],

            // ══════════════════════════════════════════════════════════════════
            // 3. KABUPATEN INDRAMAYU (31 Kecamatan)
            // ══════════════════════════════════════════════════════════════════
            // Anjatan
            [
                'kode' => 'SKL-IND-ANJ-01', 'nama' => 'SMA Negeri 1 Anjatan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Anjatan', 'alamat' => 'Jl. Raya Anjatan Utara No. 4, Anjatan',
                'telepon' => '0234-610001', 'email' => 'sman1anjatan@sch.id', 'pic_name' => 'Drs. H. Rastim, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456105', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-ANJ-02', 'nama' => 'SMK Negeri 1 Anjatan', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Anjatan', 'alamat' => 'Jl. Lempuyang No. 8, Anjatan',
                'telepon' => '0234-610002', 'email' => 'smkn1anjatan@sch.id', 'pic_name' => 'Bambang Irawan, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456106', 'status' => 'Aktif'
            ],
            // Arahan
            [
                'kode' => 'SKL-IND-ARH-01', 'nama' => 'SMA Negeri 1 Arahan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Arahan', 'alamat' => 'Jl. Raya Arahan Lor No. 12, Arahan',
                'telepon' => '0234-271001', 'email' => 'sman1arahan@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456107', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-ARH-02', 'nama' => 'SMK Negeri 1 Arahan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Arahan', 'alamat' => 'Jl. Linggajati No. 4, Arahan',
                'telepon' => '0234-271002', 'email' => 'smkn1arahan@sch.id', 'pic_name' => 'Suherman, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456108', 'status' => 'Aktif'
            ],
            // Balongan
            [
                'kode' => 'SKL-IND-BLG-01', 'nama' => 'SMA Negeri 1 Balongan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Balongan', 'alamat' => 'Jl. Raya Sukaurip No. 1, Balongan',
                'telepon' => '0234-428111', 'email' => 'sman1balongan@sch.id', 'pic_name' => 'Drs. H. Kusen', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456109', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-BLG-02', 'nama' => 'SMK Negeri 1 Balongan', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Balongan', 'alamat' => 'Jl. Raya Balongan No. 8, Balongan',
                'telepon' => '0234-428222', 'email' => 'smkn1balongan@sch.id', 'pic_name' => 'Dr. H. Suwandi, M.T', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456110', 'status' => 'Aktif'
            ],
            // Bangodua
            [
                'kode' => 'SKL-IND-BGD-01', 'nama' => 'SMA Negeri 1 Bangodua', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Bangodua', 'alamat' => 'Jl. Raya Bangodua No. 11, Bangodua',
                'telepon' => '0234-351001', 'email' => 'sman1bangodua@sch.id', 'pic_name' => 'Drs. H. Kholid', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456111', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-BGD-02', 'nama' => 'SMK Negeri 1 Bangodua', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Bangodua', 'alamat' => 'Jl. Beduyut No. 5, Bangodua',
                'telepon' => '0234-351002', 'email' => 'smkn1bangodua@sch.id', 'pic_name' => 'Asep Hidayat, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456112', 'status' => 'Aktif'
            ],
            // Bongas
            [
                'kode' => 'SKL-IND-BGS-01', 'nama' => 'SMA Negeri 1 Bongas', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Bongas', 'alamat' => 'Jl. Raya Margamulya No. 5, Bongas',
                'telepon' => '0234-611001', 'email' => 'sman1bongas@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456113', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-BGS-02', 'nama' => 'SMK Negeri 1 Bongas', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Bongas', 'alamat' => 'Jl. Kertajaya No. 8, Bongas',
                'telepon' => '0234-611002', 'email' => 'smkn1bongas@sch.id', 'pic_name' => 'Agus Salim, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456114', 'status' => 'Aktif'
            ],
            // Cantigi
            [
                'kode' => 'SKL-IND-CTG-01', 'nama' => 'SMA Negeri 1 Cantigi', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Cantigi', 'alamat' => 'Jl. Cantigi Kulon No. 2, Cantigi',
                'telepon' => '0234-272001', 'email' => 'sman1cantigi@sch.id', 'pic_name' => 'Drs. H. Sutisna', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456115', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-CTG-02', 'nama' => 'SMK Negeri 1 Cantigi', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Cantigi', 'alamat' => 'Jl. Raya Pangkalan No. 7, Cantigi',
                'telepon' => '0234-272002', 'email' => 'smkn1cantigi@sch.id', 'pic_name' => 'Rudi Hartono, S.Pd', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456116', 'status' => 'Aktif'
            ],
            // Cikedung
            [
                'kode' => 'SKL-IND-CKD-01', 'nama' => 'SMA Negeri 1 Cikedung', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Cikedung', 'alamat' => 'Jl. Jambak No. 15, Cikedung',
                'telepon' => '0234-481001', 'email' => 'sman1cikedung@sch.id', 'pic_name' => 'Drs. H. Rasidi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456117', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-CKD-02', 'nama' => 'SMK Negeri 1 Cikedung', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Cikedung', 'alamat' => 'Jl. Amis-Cikedung KM 3, Cikedung',
                'telepon' => '0234-481002', 'email' => 'smkn1cikedung@sch.id', 'pic_name' => 'Dedi Supriatna, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456118', 'status' => 'Aktif'
            ],
            // Gabuswetan
            [
                'kode' => 'SKL-IND-GBW-01', 'nama' => 'SMA Negeri 1 Gabuswetan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Gabuswetan', 'alamat' => 'Jl. Raya Gabuswetan No. 33, Gabuswetan',
                'telepon' => '0234-551001', 'email' => 'sman1gabuswetan@sch.id', 'pic_name' => 'Drs. H. Taryono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456119', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-GBW-02', 'nama' => 'SMK Negeri 1 Gabuswetan', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Gabuswetan', 'alamat' => 'Jl. Dr. Setiabudi No. 1, Gabuswetan',
                'telepon' => '0234-551002', 'email' => 'smkn1gabuswetan@sch.id', 'pic_name' => 'H. Suherman, S.Pd', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456120', 'status' => 'Aktif'
            ],
            // Gantar
            [
                'kode' => 'SKL-IND-GTR-01', 'nama' => 'SMA Negeri 1 Gantar', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Gantar', 'alamat' => 'Jl. Haurgeulis-Gantar KM 6, Gantar',
                'telepon' => '0234-612001', 'email' => 'sman1gantar@sch.id', 'pic_name' => 'Drs. H. Didi Supriyadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456121', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-GTR-02', 'nama' => 'SMK Negeri 1 Gantar', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Gantar', 'alamat' => 'Jl. Mekarjaya No. 10, Gantar',
                'telepon' => '0234-612002', 'email' => 'smkn1gantar@sch.id', 'pic_name' => 'Nurdiansyah, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456122', 'status' => 'Aktif'
            ],
            // Haurgeulis
            [
                'kode' => 'SKL-IND-HGL-01', 'nama' => 'SMA Negeri 1 Haurgeulis', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Haurgeulis', 'alamat' => 'Jl. Jend. Sudirman No. 10, Haurgeulis',
                'telepon' => '0234-741001', 'email' => 'sman1haurgeulis@sch.id', 'pic_name' => 'Drs. H. Supardi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456041', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-HGL-02', 'nama' => 'SMK Negeri 1 Haurgeulis', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Haurgeulis', 'alamat' => 'Jl. Mekarwangi No. 5, Haurgeulis',
                'telepon' => '0234-741002', 'email' => 'smkn1haurgeulis@sch.id', 'pic_name' => 'Drs. H. Kasturi', 'pic_jabatan' => 'Waka Hubin', 'pic_phone' => '08123456123', 'status' => 'Aktif'
            ],
            // Indramayu (Kota)
            [
                'kode' => 'SKL-IND-IDM-01', 'nama' => 'SMA Negeri 1 Indramayu', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Indramayu', 'alamat' => 'Jl. Mayor Dasuki No. 39, Indramayu',
                'telepon' => '0234-271234', 'email' => 'info@sman1indramayu.sch.id', 'pic_name' => 'Drs. H. Kusworo', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456020', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-IDM-02', 'nama' => 'SMA Negeri 2 Indramayu', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Indramayu', 'alamat' => 'Jl. Pahlawan No. 4, Indramayu',
                'telepon' => '0234-272345', 'email' => 'info@sman2indramayu.sch.id', 'pic_name' => 'Dra. Hj. Siti Rohani', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456021', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-IDM-03', 'nama' => 'SMK Negeri 1 Indramayu', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Indramayu', 'alamat' => 'Jl. Gatot Subroto No. 5, Indramayu',
                'telepon' => '0234-273456', 'email' => 'smkn1indramayu@sch.id', 'pic_name' => 'Dr. Ir. Bambang Sugiarto', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456124', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-IDM-04', 'nama' => 'MAN 1 Indramayu', 'tier' => 'A', 'kategori' => 'MA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Indramayu', 'alamat' => 'Jl. Soekarno Hatta No. 7, Indramayu',
                'telepon' => '0234-274567', 'email' => 'man1indramayu@kemenag.go.id', 'pic_name' => 'Drs. H. Mulyadi', 'pic_jabatan' => 'Kepala Madrasah', 'pic_phone' => '08123456125', 'status' => 'Aktif'
            ],
            // Jatibarang
            [
                'kode' => 'SKL-IND-JTB-01', 'nama' => 'SMA Negeri 1 Jatibarang', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Jatibarang', 'alamat' => 'Jl. Mayor Dasuki No. 55, Jatibarang',
                'telepon' => '0234-351222', 'email' => 'sman1jatibarang@sch.id', 'pic_name' => 'Drs. H. Solihin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456039', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-JTB-02', 'nama' => 'SMK Negeri 1 Jatibarang', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Jatibarang', 'alamat' => 'Jl. Raya Bulak No. 1, Jatibarang',
                'telepon' => '0234-351333', 'email' => 'smkn1jatibarang@sch.id', 'pic_name' => 'Drs. H. Ade Sunardi', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456040', 'status' => 'Aktif'
            ],
            // Juntinyuat
            [
                'kode' => 'SKL-IND-JNT-01', 'nama' => 'SMA Negeri 1 Juntinyuat', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Juntinyuat', 'alamat' => 'Jl. Raya Segeran No. 22, Juntinyuat',
                'telepon' => '0234-429001', 'email' => 'sman1juntinyuat@sch.id', 'pic_name' => 'Drs. H. Rasidin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456126', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-JNT-02', 'nama' => 'SMK Negeri 1 Juntinyuat', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Juntinyuat', 'alamat' => 'Jl. Raya Dadap No. 15, Juntinyuat',
                'telepon' => '0234-429002', 'email' => 'smkn1juntinyuat@sch.id', 'pic_name' => 'Ahmad Rifa\'i, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456127', 'status' => 'Aktif'
            ],
            // Kandanghaur
            [
                'kode' => 'SKL-IND-KDH-01', 'nama' => 'SMA Negeri 1 Kandanghaur', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Kandanghaur', 'alamat' => 'Jl. Raya Eretan Kulon No. 4, Kandanghaur',
                'telepon' => '0234-505001', 'email' => 'sman1kandanghaur@sch.id', 'pic_name' => 'Drs. H. Sukirno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456128', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-KDH-02', 'nama' => 'SMK Negeri 1 Kandanghaur', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Kandanghaur', 'alamat' => 'Jl. Raya Pantura Ilir KM 12, Kandanghaur',
                'telepon' => '0234-505002', 'email' => 'smkn1kandanghaur@sch.id', 'pic_name' => 'Drs. H. Maman', 'pic_jabatan' => 'Waka Hubin', 'pic_phone' => '08123456129', 'status' => 'Aktif'
            ],
            // Karangampel
            [
                'kode' => 'SKL-IND-KRA-01', 'nama' => 'SMA Negeri 1 Karangampel', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Karangampel', 'alamat' => 'Jl. Dampoawang No. 1, Karangampel',
                'telepon' => '0234-352001', 'email' => 'sman1karangampel@sch.id', 'pic_name' => 'Drs. H. Tarkim, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456037', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-KRA-02', 'nama' => 'SMK Negeri 1 Karangampel', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Karangampel', 'alamat' => 'Jl. Raya Pringgacala No. 12, Karangampel',
                'telepon' => '0234-352002', 'email' => 'smkn1karangampel@sch.id', 'pic_name' => 'Drs. H. Kusnadi', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456038', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-KRA-03', 'nama' => 'MAN 2 Indramayu', 'tier' => 'A', 'kategori' => 'MA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Karangampel', 'alamat' => 'Jl. Raya Karangampel Barat No. 8',
                'telepon' => '0234-352003', 'email' => 'man2indramayu@kemenag.go.id', 'pic_name' => 'Drs. H. Muhaimin', 'pic_jabatan' => 'Kepala Madrasah', 'pic_phone' => '08123456130', 'status' => 'Aktif'
            ],
            // Kedokan Bunder
            [
                'kode' => 'SKL-IND-KDB-01', 'nama' => 'SMA Negeri 1 Kedokan Bunder', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Kedokan Bunder', 'alamat' => 'Jl. Raya Kedokan Agung No. 19',
                'telepon' => '0234-353001', 'email' => 'sman1kedokanbunder@sch.id', 'pic_name' => 'Drs. H. Sulaeman', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456131', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-KDB-02', 'nama' => 'SMK Negeri 1 Kedokan Bunder', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Kedokan Bunder', 'alamat' => 'Jl. Kaplongan No. 2, Kedokan Bunder',
                'telepon' => '0234-353002', 'email' => 'smkn1kedokanbunder@sch.id', 'pic_name' => 'H. Dedi Supriyadi, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456132', 'status' => 'Aktif'
            ],
            // Kertasemaya
            [
                'kode' => 'SKL-IND-KTS-01', 'nama' => 'SMA Negeri 1 Kertasemaya', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Kertasemaya', 'alamat' => 'Jl. Tulungagung No. 14, Kertasemaya',
                'telepon' => '0234-354001', 'email' => 'sman1kertasemaya@sch.id', 'pic_name' => 'Drs. H. Subur', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456133', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-KTS-02', 'nama' => 'SMK Negeri 1 Kertasemaya', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Kertasemaya', 'alamat' => 'Jl. Raya Kertasemaya KM 1, Kertasemaya',
                'telepon' => '0234-354002', 'email' => 'smkn1kertasemaya@sch.id', 'pic_name' => 'Budi Waluyo, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456134', 'status' => 'Aktif'
            ],
            // Krangkeng
            [
                'kode' => 'SKL-IND-KRG-01', 'nama' => 'SMA Negeri 1 Krangkeng', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Krangkeng', 'alamat' => 'Jl. Raya Krangkeng KM 30, Krangkeng',
                'telepon' => '0234-355001', 'email' => 'sman1krangkeng@sch.id', 'pic_name' => 'Drs. H. Masturo', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456135', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-KRG-02', 'nama' => 'SMK Negeri 1 Krangkeng', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Krangkeng', 'alamat' => 'Jl. Dukuh Jati No. 4, Krangkeng',
                'telepon' => '0234-355002', 'email' => 'smkn1krangkeng@sch.id', 'pic_name' => 'Ahmad Fauzi, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456136', 'status' => 'Aktif'
            ],
            // Kroya
            [
                'kode' => 'SKL-IND-KRY-01', 'nama' => 'SMA Negeri 1 Kroya', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Kroya', 'alamat' => 'Jl. Sukamelang No. 10, Kroya',
                'telepon' => '0234-552001', 'email' => 'sman1kroya@sch.id', 'pic_name' => 'Drs. H. Suwito', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456137', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-KRY-02', 'nama' => 'SMK Negeri 1 Kroya', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Kroya', 'alamat' => 'Jl. Raya Kroya-Temiyang No. 8',
                'telepon' => '0234-552002', 'email' => 'smkn1kroya@sch.id', 'pic_name' => 'Dra. Hj. Aminah', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456138', 'status' => 'Aktif'
            ],
            // Lelea
            [
                'kode' => 'SKL-IND-LLA-01', 'nama' => 'SMA Negeri 1 Lelea', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Lelea', 'alamat' => 'Jl. Raya Tugu No. 9, Lelea',
                'telepon' => '0234-482001', 'email' => 'sman1lelea@sch.id', 'pic_name' => 'Drs. H. Nana', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456139', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-LLA-02', 'nama' => 'SMK Negeri 1 Lelea', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Lelea', 'alamat' => 'Jl. Raya Lelea-Tunggulpayung KM 2',
                'telepon' => '0234-482002', 'email' => 'smkn1lelea@sch.id', 'pic_name' => 'Dedi Iskandar, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456140', 'status' => 'Aktif'
            ],
            // Lohbener
            [
                'kode' => 'SKL-IND-LHB-01', 'nama' => 'SMA Negeri 1 Lohbener', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Lohbener', 'alamat' => 'Jl. Raya Celancang No. 5, Lohbener',
                'telepon' => '0234-275001', 'email' => 'sman1lohbener@sch.id', 'pic_name' => 'Drs. H. Wahyudi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456141', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-LHB-02', 'nama' => 'SMK Negeri 1 Lohbener', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Lohbener', 'alamat' => 'Jl. Raya Lohbener Timur No. 1, Lohbener',
                'telepon' => '0234-275002', 'email' => 'smkn1lohbener@sch.id', 'pic_name' => 'Dr. H. Ruspendi, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456142', 'status' => 'Aktif'
            ],
            // Losarang
            [
                'kode' => 'SKL-IND-LSR-01', 'nama' => 'SMA Negeri 1 Losarang', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Losarang', 'alamat' => 'Jl. Raya Pantura Puntang, Losarang',
                'telepon' => '0234-506001', 'email' => 'sman1losarang@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456143', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-LSR-02', 'nama' => 'SMK Negeri 1 Losarang', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Losarang', 'alamat' => 'Jl. Santing No. 12, Losarang',
                'telepon' => '0234-506002', 'email' => 'smkn1losarang@sch.id', 'pic_name' => 'Ir. Suherman', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456144', 'status' => 'Aktif'
            ],
            // Pasekan
            [
                'kode' => 'SKL-IND-PSK-01', 'nama' => 'SMA Negeri 1 Pasekan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Pasekan', 'alamat' => 'Jl. Raya Pasekan No. 7, Pasekan',
                'telepon' => '0234-276001', 'email' => 'sman1pasekan@sch.id', 'pic_name' => 'Drs. H. Suharto', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456145', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-PSK-02', 'nama' => 'SMK Negeri 1 Pasekan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Pasekan', 'alamat' => 'Jl. Pabean Ilir No. 4, Pasekan',
                'telepon' => '0234-276002', 'email' => 'smkn1pasekan@sch.id', 'pic_name' => 'Budi Santoso, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456146', 'status' => 'Aktif'
            ],
            // Patrol
            [
                'kode' => 'SKL-IND-PTR-01', 'nama' => 'SMA Negeri 1 Patrol', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Patrol', 'alamat' => 'Jl. Raya Patrol KM 42, Patrol',
                'telepon' => '0234-613001', 'email' => 'sman1patrol@sch.id', 'pic_name' => 'Drs. H. Rasidi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456147', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-PTR-02', 'nama' => 'SMK Negeri 1 Patrol', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Patrol', 'alamat' => 'Jl. Patrol Lor No. 18, Patrol',
                'telepon' => '0234-613002', 'email' => 'smkn1patrol@sch.id', 'pic_name' => 'Drs. H. Maman', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456148', 'status' => 'Aktif'
            ],
            // Sindang
            [
                'kode' => 'SKL-IND-SDG-01', 'nama' => 'SMA Negeri 1 Sindang', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Sindang', 'alamat' => 'Jl. MT Haryono No. 1, Sindang',
                'telepon' => '0234-272111', 'email' => 'sman1sindang@sch.id', 'pic_name' => 'Drs. H. Ade Sunardi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456149', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-SDG-02', 'nama' => 'SMA Negeri 2 Sindang', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Sindang', 'alamat' => 'Jl. Murah Nara No. 7, Sindang',
                'telepon' => '0234-272222', 'email' => 'sman2sindang@sch.id', 'pic_name' => 'Dra. Hj. Nunung', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456150', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-SDG-03', 'nama' => 'SMK Negeri 1 Sindang', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Sindang', 'alamat' => 'Jl. Mayor Dasuki No. 88, Sindang',
                'telepon' => '0234-272333', 'email' => 'smkn1sindang@sch.id', 'pic_name' => 'Dr. H. Ruspendi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456151', 'status' => 'Aktif'
            ],
            // Sliyeg
            [
                'kode' => 'SKL-IND-SLY-01', 'nama' => 'SMA Negeri 1 Sliyeg', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Sliyeg', 'alamat' => 'Jl. Raya Sliyeg Lor No. 7, Sliyeg',
                'telepon' => '0234-356001', 'email' => 'sman1sliyeg@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456152', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-SLY-02', 'nama' => 'SMK Negeri 1 Sliyeg', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Sliyeg', 'alamat' => 'Jl. Tambi No. 3, Sliyeg',
                'telepon' => '0234-356002', 'email' => 'smkn1sliyeg@sch.id', 'pic_name' => 'Suherman, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456153', 'status' => 'Aktif'
            ],
            // Sukagumiwang
            [
                'kode' => 'SKL-IND-SKG-01', 'nama' => 'SMA Negeri 1 Sukagumiwang', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Sukagumiwang', 'alamat' => 'Jl. Cadangpinggan No. 2, Sukagumiwang',
                'telepon' => '0234-357001', 'email' => 'sman1sukagumiwang@sch.id', 'pic_name' => 'Drs. H. Tarkim', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456154', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-SKG-02', 'nama' => 'SMK Negeri 1 Sukagumiwang', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Sukagumiwang', 'alamat' => 'Jl. Raya By Pass Bondan, Sukagumiwang',
                'telepon' => '0234-357002', 'email' => 'smkn1sukagumiwang@sch.id', 'pic_name' => 'Dra. Hj. Aminah', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456155', 'status' => 'Aktif'
            ],
            // Sukra
            [
                'kode' => 'SKL-IND-SKR-01', 'nama' => 'SMA Negeri 1 Sukra', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Sukra', 'alamat' => 'Jl. Raya Pantura Sumuradem, Sukra',
                'telepon' => '0234-614001', 'email' => 'sman1sukra@sch.id', 'pic_name' => 'Drs. H. Solihin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456156', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-SKR-02', 'nama' => 'SMK Negeri 1 Sukra', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Sukra', 'alamat' => 'Jl. Ujunggebang No. 3, Sukra',
                'telepon' => '0234-614002', 'email' => 'smkn1sukra@sch.id', 'pic_name' => 'Asep Gunawan, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456157', 'status' => 'Aktif'
            ],
            // Terisi
            [
                'kode' => 'SKL-IND-TRS-01', 'nama' => 'SMA Negeri 1 Terisi', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Terisi', 'alamat' => 'Jl. Rajasinga No. 8, Terisi',
                'telepon' => '0234-483001', 'email' => 'sman1terisi@sch.id', 'pic_name' => 'Drs. H. Sukirno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456158', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-TRS-02', 'nama' => 'SMK Negeri 1 Terisi', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Terisi', 'alamat' => 'Jl. Jatimulya No. 4, Terisi',
                'telepon' => '0234-483002', 'email' => 'smkn1terisi@sch.id', 'pic_name' => 'Dedi Iskandar, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456159', 'status' => 'Aktif'
            ],
            // Tukdana
            [
                'kode' => 'SKL-IND-TKD-01', 'nama' => 'SMA Negeri 1 Tukdana', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Tukdana', 'alamat' => 'Jl. Raya Sukamulya No. 4, Tukdana',
                'telepon' => '0234-358001', 'email' => 'sman1tukdana@sch.id', 'pic_name' => 'Drs. H. Mamat', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456160', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-TKD-02', 'nama' => 'SMK Negeri 1 Tukdana', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Tukdana', 'alamat' => 'Jl. Gadel-Tukdana KM 2, Tukdana',
                'telepon' => '0234-358002', 'email' => 'smkn1tukdana@sch.id', 'pic_name' => 'Suwandi, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456161', 'status' => 'Aktif'
            ],
            // Widasari
            [
                'kode' => 'SKL-IND-WDS-01', 'nama' => 'SMA Negeri 1 Widasari', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Widasari', 'alamat' => 'Jl. Kongsijaya No. 3, Widasari',
                'telepon' => '0234-359001', 'email' => 'sman1widasari@sch.id', 'pic_name' => 'Drs. H. Kusnadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456162', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-IND-WDS-02', 'nama' => 'SMK Negeri 1 Widasari', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Indramayu', 'kecamatan' => 'Widasari', 'alamat' => 'Jl. By Pass Widasari No. 11, Widasari',
                'telepon' => '0234-359002', 'email' => 'smkn1widasari@sch.id', 'pic_name' => 'Nurjaman, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456163', 'status' => 'Aktif'
            ],

            // ══════════════════════════════════════════════════════════════════
            // 4. KABUPATEN MAJALENGKA (26 Kecamatan)
            // ══════════════════════════════════════════════════════════════════
            // Argapura
            [
                'kode' => 'SKL-MJL-AGP-01', 'nama' => 'SMA Negeri 1 Argapura', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Argapura', 'alamat' => 'Jl. Raya Sukasari Kaler, Argapura',
                'telepon' => '0233-828001', 'email' => 'sman1argapura@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456164', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-AGP-02', 'nama' => 'SMK Negeri 1 Argapura', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Argapura', 'alamat' => 'Jl. Tejamulya No. 4, Argapura',
                'telepon' => '0233-828002', 'email' => 'smkn1argapura@sch.id', 'pic_name' => 'Dra. Hj. Nunung', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456165', 'status' => 'Aktif'
            ],
            // Banjaran
            [
                'kode' => 'SKL-MJL-BJR-01', 'nama' => 'SMA Negeri 1 Banjaran', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Banjaran', 'alamat' => 'Jl. Raya Banjaran No. 10, Banjaran',
                'telepon' => '0233-829001', 'email' => 'sman1banjaran@sch.id', 'pic_name' => 'Drs. H. Solihin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456166', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-BJR-02', 'nama' => 'SMK Negeri 1 Banjaran', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Banjaran', 'alamat' => 'Jl. Sunia No. 4, Banjaran',
                'telepon' => '0233-829002', 'email' => 'smkn1banjaran@sch.id', 'pic_name' => 'Ahmad Rifa\'i, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456167', 'status' => 'Aktif'
            ],
            // Bantarujeg
            [
                'kode' => 'SKL-MJL-BTJ-01', 'nama' => 'SMA Negeri 1 Bantarujeg', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Bantarujeg', 'alamat' => 'Jl. Siliwangi No. 78, Bantarujeg',
                'telepon' => '0233-831001', 'email' => 'sman1bantarujeg@sch.id', 'pic_name' => 'Drs. H. Rasidi, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456168', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-BTJ-02', 'nama' => 'SMK Negeri 1 Bantarujeg', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Bantarujeg', 'alamat' => 'Jl. Sukamenak No. 12, Bantarujeg',
                'telepon' => '0233-831002', 'email' => 'smkn1bantarujeg@sch.id', 'pic_name' => 'Dedi Supriatna, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456169', 'status' => 'Aktif'
            ],
            // Cigasong
            [
                'kode' => 'SKL-MJL-CGS-01', 'nama' => 'SMA Negeri 1 Cigasong', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Cigasong', 'alamat' => 'Jl. Raya Simpeureum No. 1, Cigasong',
                'telepon' => '0233-281001', 'email' => 'sman1cigasong@sch.id', 'pic_name' => 'Drs. H. Ade Sunardi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456170', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-CGS-02', 'nama' => 'SMK Negeri 1 Cigasong', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Cigasong', 'alamat' => 'Jl. Baribis No. 10, Cigasong',
                'telepon' => '0233-281002', 'email' => 'smkn1cigasong@sch.id', 'pic_name' => 'Suherman, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456171', 'status' => 'Aktif'
            ],
            // Cikijing
            [
                'kode' => 'SKL-MJL-CKJ-01', 'nama' => 'SMA Negeri 1 Cikijing', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Cikijing', 'alamat' => 'Jl. Raya Kasturi No. 1, Cikijing',
                'telepon' => '0233-832001', 'email' => 'sman1cikijing@sch.id', 'pic_name' => 'Drs. H. Sukirno, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456172', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-CKJ-02', 'nama' => 'SMK Negeri 1 Cikijing', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Cikijing', 'alamat' => 'Jl. Raya Cikijing-Kuningan KM 1',
                'telepon' => '0233-832002', 'email' => 'smkn1cikijing@sch.id', 'pic_name' => 'Drs. H. Maman', 'pic_jabatan' => 'Waka Hubin', 'pic_phone' => '08123456173', 'status' => 'Aktif'
            ],
            // Cingambul
            [
                'kode' => 'SKL-MJL-CGB-01', 'nama' => 'SMA Negeri 1 Cingambul', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Cingambul', 'alamat' => 'Jl. Raya Cingambul-Ciamis KM 2',
                'telepon' => '0233-833001', 'email' => 'sman1cingambul@sch.id', 'pic_name' => 'Drs. H. Taryono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456174', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-CGB-02', 'nama' => 'SMK Negeri 1 Cingambul', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Cingambul', 'alamat' => 'Jl. Wangkelang No. 5, Cingambul',
                'telepon' => '0233-833002', 'email' => 'smkn1cingambul@sch.id', 'pic_name' => 'Bambang Irawan, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456175', 'status' => 'Aktif'
            ],
            // Dawuan
            [
                'kode' => 'SKL-MJL-DWN-01', 'nama' => 'SMA Negeri 1 Dawuan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Dawuan', 'alamat' => 'Jl. Raya Baturuyuk No. 8, Dawuan',
                'telepon' => '0233-661001', 'email' => 'sman1dawuan@sch.id', 'pic_name' => 'Drs. H. Kusnadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456176', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-DWN-02', 'nama' => 'SMK Negeri 1 Dawuan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Dawuan', 'alamat' => 'Jl. Gandu No. 3, Dawuan',
                'telepon' => '0233-661002', 'email' => 'smkn1dawuan@sch.id', 'pic_name' => 'Asep Gunawan, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456177', 'status' => 'Aktif'
            ],
            // Jatitujuh
            [
                'kode' => 'SKL-MJL-JT7-01', 'nama' => 'SMA Negeri 1 Jatitujuh', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Jatitujuh', 'alamat' => 'Jl. Raya Jatitujuh No. 4, Jatitujuh',
                'telepon' => '0233-881001', 'email' => 'sman1jatitujuh@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456178', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-JT7-02', 'nama' => 'SMK Negeri 1 Jatitujuh', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Jatitujuh', 'alamat' => 'Jl. Pangkalan No. 8, Jatitujuh',
                'telepon' => '0233-881002', 'email' => 'smkn1jatitujuh@sch.id', 'pic_name' => 'Suwandi, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456179', 'status' => 'Aktif'
            ],
            // Jatiwangi
            [
                'kode' => 'SKL-MJL-JTW-01', 'nama' => 'SMA Negeri 1 Jatiwangi', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Jatiwangi', 'alamat' => 'Jl. Pos Timur No. 1, Jatiwangi',
                'telepon' => '0233-882111', 'email' => 'info@sman1jatiwangi.sch.id', 'pic_name' => 'Drs. H. Didi Sutisna', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456045', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-JTW-02', 'nama' => 'SMK Negeri 1 Jatiwangi', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Jatiwangi', 'alamat' => 'Jl. Cicadas No. 8, Jatiwangi',
                'telepon' => '0233-882222', 'email' => 'smkn1jatiwangi@sch.id', 'pic_name' => 'Ir. Bambang Sugiarto', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456046', 'status' => 'Aktif'
            ],
            // Kadipaten
            [
                'kode' => 'SKL-MJL-KDP-01', 'nama' => 'SMA Negeri 1 Kadipaten', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Kadipaten', 'alamat' => 'Jl. Liangjulang No. 1, Kadipaten',
                'telepon' => '0233-661222', 'email' => 'info@sman1kadipaten.sch.id', 'pic_name' => 'Drs. H. Rasidin, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456043', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-KDP-02', 'nama' => 'SMK Negeri 1 Kadipaten', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Kadipaten', 'alamat' => 'Jl. Siliwangi No. 30, Kadipaten',
                'telepon' => '0233-661333', 'email' => 'smkn1kadipaten@sch.id', 'pic_name' => 'Drs. H. Masturo', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456044', 'status' => 'Aktif'
            ],
            // Kasokandel
            [
                'kode' => 'SKL-MJL-KSK-01', 'nama' => 'SMA Negeri 1 Kasokandel', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Kasokandel', 'alamat' => 'Jl. Raya Kasokandel No. 10, Kasokandel',
                'telepon' => '0233-662001', 'email' => 'sman1kasokandel@sch.id', 'pic_name' => 'Drs. H. Solihin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456180', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-KSK-02', 'nama' => 'SMK Negeri 1 Kasokandel', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Kasokandel', 'alamat' => 'Jl. Gunungsari No. 5, Kasokandel',
                'telepon' => '0233-662002', 'email' => 'smkn1kasokandel@sch.id', 'pic_name' => 'Nurjaman, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456181', 'status' => 'Aktif'
            ],
            // Kertajati
            [
                'kode' => 'SKL-MJL-KTJ-01', 'nama' => 'SMA Negeri 1 Kertajati', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Kertajati', 'alamat' => 'Jl. Raya Bandara BIJB No. 1, Kertajati',
                'telepon' => '0233-883001', 'email' => 'sman1kertajati@sch.id', 'pic_name' => 'Drs. H. Tarkim', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456182', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-KTJ-02', 'nama' => 'SMK Negeri 1 Kertajati', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Kertajati', 'alamat' => 'Jl. Kertawinangun No. 15, Kertajati',
                'telepon' => '0233-883002', 'email' => 'smkn1kertajati@sch.id', 'pic_name' => 'Ir. Suherman', 'pic_jabatan' => 'Waka Hubin', 'pic_phone' => '08123456183', 'status' => 'Aktif'
            ],
            // Lemahsugih
            [
                'kode' => 'SKL-MJL-LMS-01', 'nama' => 'SMA Negeri 1 Lemahsugih', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Lemahsugih', 'alamat' => 'Jl. Padarek No. 1, Lemahsugih',
                'telepon' => '0233-834001', 'email' => 'sman1lemahsugih@sch.id', 'pic_name' => 'Drs. H. Mulyadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456184', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-LMS-02', 'nama' => 'SMK Negeri 1 Lemahsugih', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Lemahsugih', 'alamat' => 'Jl. Borogojol No. 7, Lemahsugih',
                'telepon' => '0233-834002', 'email' => 'smkn1lemahsugih@sch.id', 'pic_name' => 'Dra. Hj. Aminah', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456185', 'status' => 'Aktif'
            ],
            // Leuwimunding
            [
                'kode' => 'SKL-MJL-LWM-01', 'nama' => 'SMA Negeri 1 Leuwimunding', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Leuwimunding', 'alamat' => 'Jl. Raya Leuwimunding No. 25, Leuwimunding',
                'telepon' => '0233-884001', 'email' => 'sman1leuwimunding@sch.id', 'pic_name' => 'Drs. H. Suharto', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456186', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-LWM-02', 'nama' => 'SMK Negeri 1 Leuwimunding', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Leuwimunding', 'alamat' => 'Jl. Parungjaya No. 8, Leuwimunding',
                'telepon' => '0233-884002', 'email' => 'smkn1leuwimunding@sch.id', 'pic_name' => 'Dedi Iskandar, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456187', 'status' => 'Aktif'
            ],
            // Ligung
            [
                'kode' => 'SKL-MJL-LGG-01', 'nama' => 'SMA Negeri 1 Ligung', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Ligung', 'alamat' => 'Jl. Raya Ligung No. 19, Ligung',
                'telepon' => '0233-885001', 'email' => 'sman1ligung@sch.id', 'pic_name' => 'Drs. H. Maman', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456188', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-LGG-02', 'nama' => 'SMK Negeri 1 Ligung', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Ligung', 'alamat' => 'Jl. Sukawera No. 11, Ligung',
                'telepon' => '0233-885002', 'email' => 'smkn1ligung@sch.id', 'pic_name' => 'Agus Salim, S.Pd', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456189', 'status' => 'Aktif'
            ],
            // Maja
            [
                'kode' => 'SKL-MJL-MJA-01', 'nama' => 'SMA Negeri 1 Maja', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Maja', 'alamat' => 'Jl. Pasukan Sindangkasih No. 20, Maja',
                'telepon' => '0233-828555', 'email' => 'sman1maja@sch.id', 'pic_name' => 'Drs. H. Rasidi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456190', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-MJA-02', 'nama' => 'SMK Negeri 1 Maja', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Maja', 'alamat' => 'Jl. Wanahayu No. 8, Maja',
                'telepon' => '0233-828556', 'email' => 'smkn1maja@sch.id', 'pic_name' => 'Asep Gunawan, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456191', 'status' => 'Aktif'
            ],
            // Majalengka (Kota)
            [
                'kode' => 'SKL-MJL-MJL-01', 'nama' => 'SMA Negeri 1 Majalengka', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Majalengka', 'alamat' => 'Jl. K.H. Abdul Halim No. 113, Majalengka',
                'telepon' => '0233-281456', 'email' => 'info@sman1majalengka.sch.id', 'pic_name' => 'Drs. H. Moh. Ali, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456042', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-MJL-02', 'nama' => 'SMA Negeri 2 Majalengka', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Majalengka', 'alamat' => 'Jl. Ahmad Yani No. 2, Majalengka',
                'telepon' => '0233-281789', 'email' => 'info@sman2majalengka.sch.id', 'pic_name' => 'Dra. Hj. Titin, M.M', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456049', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-MJL-03', 'nama' => 'SMK Negeri 1 Majalengka', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Majalengka', 'alamat' => 'Jl. Raya Tonjong-Pinangraja No. 55',
                'telepon' => '0233-281999', 'email' => 'smkn1majalengka@sch.id', 'pic_name' => 'Dr. H. Ruspendi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456192', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-MJL-04', 'nama' => 'MAN 1 Majalengka', 'tier' => 'A', 'kategori' => 'MA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Majalengka', 'alamat' => 'Jl. Siti Armilah No. 1, Majalengka',
                'telepon' => '0233-282001', 'email' => 'man1majalengka@kemenag.go.id', 'pic_name' => 'Drs. H. Muhaimin', 'pic_jabatan' => 'Kepala Madrasah', 'pic_phone' => '08123456193', 'status' => 'Aktif'
            ],
            // Malausma
            [
                'kode' => 'SKL-MJL-MLS-01', 'nama' => 'SMA Negeri 1 Malausma', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Malausma', 'alamat' => 'Jl. Cirawa No. 5, Malausma',
                'telepon' => '0233-835001', 'email' => 'sman1malausma@sch.id', 'pic_name' => 'Drs. H. Sukmana', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456194', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-MLS-02', 'nama' => 'SMK Negeri 1 Malausma', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Malausma', 'alamat' => 'Jl. Lebakwangi No. 12, Malausma',
                'telepon' => '0233-835002', 'email' => 'smkn1malausma@sch.id', 'pic_name' => 'Suherman, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456195', 'status' => 'Aktif'
            ],
            // Panyingkiran
            [
                'kode' => 'SKL-MJL-PYK-01', 'nama' => 'SMA Negeri 1 Panyingkiran', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Panyingkiran', 'alamat' => 'Jl. Raya Panyingkiran No. 12, Panyingkiran',
                'telepon' => '0233-283001', 'email' => 'sman1panyingkiran@sch.id', 'pic_name' => 'Drs. H. Solihin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456196', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-PYK-02', 'nama' => 'SMK Negeri 1 Panyingkiran', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Panyingkiran', 'alamat' => 'Jl. Kertabasuki No. 8, Panyingkiran',
                'telepon' => '0233-283002', 'email' => 'smkn1panyingkiran@sch.id', 'pic_name' => 'Budi Santoso, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456197', 'status' => 'Aktif'
            ],
            // Palasah
            [
                'kode' => 'SKL-MJL-PLS-01', 'nama' => 'SMA Negeri 1 Palasah', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Palasah', 'alamat' => 'Jl. Raya Majalengka-Cirebon KM 18, Palasah',
                'telepon' => '0233-886001', 'email' => 'sman1palasah@sch.id', 'pic_name' => 'Drs. H. Tarkim', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456198', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-PLS-02', 'nama' => 'SMK Negeri 1 Palasah', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Palasah', 'alamat' => 'Jl. Raya Weragati No. 2, Palasah',
                'telepon' => '0233-886002', 'email' => 'smkn1palasah@sch.id', 'pic_name' => 'Ir. Hartono', 'pic_jabatan' => 'Waka Hubin', 'pic_phone' => '08123456199', 'status' => 'Aktif'
            ],
            // Rajagaluh
            [
                'kode' => 'SKL-MJL-RJG-01', 'nama' => 'SMA Negeri 1 Rajagaluh', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Rajagaluh', 'alamat' => 'Jl. Mutiara No. 1, Rajagaluh',
                'telepon' => '0233-510111', 'email' => 'sman1rajagaluh@sch.id', 'pic_name' => 'Drs. H. Encep Suherman', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456047', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-RJG-02', 'nama' => 'SMK Negeri 1 Rajagaluh', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Rajagaluh', 'alamat' => 'Jl. Tanjungsari No. 14, Rajagaluh',
                'telepon' => '0233-510222', 'email' => 'smkn1rajagaluh@sch.id', 'pic_name' => 'Dedi Supriadi, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456048', 'status' => 'Aktif'
            ],
            // Sindang
            [
                'kode' => 'SKL-MJL-SDG-01', 'nama' => 'SMA Negeri 1 Sindang Majalengka', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Sindang', 'alamat' => 'Jl. Sindang-Garawastu No. 1, Sindang',
                'telepon' => '0233-828888', 'email' => 'sman1sindangmjl@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456200', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-SDG-02', 'nama' => 'SMK Negeri 1 Sindang Majalengka', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Sindang', 'alamat' => 'Jl. Pasirhanja No. 5, Sindang',
                'telepon' => '0233-828889', 'email' => 'smkn1sindangmjl@sch.id', 'pic_name' => 'Suherman, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456201', 'status' => 'Aktif'
            ],
            // Sindangwangi
            [
                'kode' => 'SKL-MJL-SDW-01', 'nama' => 'SMA Negeri 1 Sindangwangi', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Sindangwangi', 'alamat' => 'Jl. Raya Bantaragung No. 3, Sindangwangi',
                'telepon' => '0233-511001', 'email' => 'sman1sindangwangi@sch.id', 'pic_name' => 'Drs. H. Sukirno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456202', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-SDW-02', 'nama' => 'SMK Negeri 1 Sindangwangi', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Sindangwangi', 'alamat' => 'Jl. Jerukleueut No. 8, Sindangwangi',
                'telepon' => '0233-511002', 'email' => 'smkn1sindangwangi@sch.id', 'pic_name' => 'Ahmad Fauzi, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456203', 'status' => 'Aktif'
            ],
            // Sukahaji
            [
                'kode' => 'SKL-MJL-SKH-01', 'nama' => 'SMA Negeri 1 Sukahaji', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Sukahaji', 'alamat' => 'Jl. Raya Sukahaji No. 12, Sukahaji',
                'telepon' => '0233-827001', 'email' => 'sman1sukahaji@sch.id', 'pic_name' => 'Drs. H. Ade Sunardi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456204', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-SKH-02', 'nama' => 'SMK Negeri 1 Sukahaji', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Sukahaji', 'alamat' => 'Jl. Cikoneng No. 5, Sukahaji',
                'telepon' => '0233-827002', 'email' => 'smkn1sukahaji@sch.id', 'pic_name' => 'Bambang Irawan, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456205', 'status' => 'Aktif'
            ],
            // Sumberjaya
            [
                'kode' => 'SKL-MJL-SBJ-01', 'nama' => 'SMA Negeri 1 Sumberjaya', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Sumberjaya', 'alamat' => 'Jl. Panjalin Kidul No. 15, Sumberjaya',
                'telepon' => '0233-887001', 'email' => 'sman1sumberjaya@sch.id', 'pic_name' => 'Drs. H. Rasidi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456206', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-SBJ-02', 'nama' => 'SMK Negeri 1 Sumberjaya', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Sumberjaya', 'alamat' => 'Jl. Cidenok No. 7, Sumberjaya',
                'telepon' => '0233-887002', 'email' => 'smkn1sumberjaya@sch.id', 'pic_name' => 'Suherman, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456207', 'status' => 'Aktif'
            ],
            // Talaga
            [
                'kode' => 'SKL-MJL-TLG-01', 'nama' => 'SMA Negeri 1 Talaga', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Talaga', 'alamat' => 'Jl. Gajah Mada No. 4, Talaga',
                'telepon' => '0233-836001', 'email' => 'sman1talaga@sch.id', 'pic_name' => 'Drs. H. Tarkim, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456208', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-MJL-TLG-02', 'nama' => 'SMK Negeri 1 Talaga', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Majalengka', 'kecamatan' => 'Talaga', 'alamat' => 'Jl. Talaga-Bantarujeg KM 1, Talaga',
                'telepon' => '0233-836002', 'email' => 'smkn1talaga@sch.id', 'pic_name' => 'Ir. Bambang Sugiarto', 'pic_jabatan' => 'Waka Hubin', 'pic_phone' => '08123456209', 'status' => 'Aktif'
            ],

            // ══════════════════════════════════════════════════════════════════
            // 5. KABUPATEN KUNINGAN (32 Kecamatan)
            // ══════════════════════════════════════════════════════════════════
            // Ciawigebang
            [
                'kode' => 'SKL-KNG-CWG-01', 'nama' => 'SMA Negeri 1 Ciawigebang', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Ciawigebang', 'alamat' => 'Jl. Siliwangi No. 106, Ciawigebang',
                'telepon' => '0232-878001', 'email' => 'sman1ciawigebang@sch.id', 'pic_name' => 'Drs. H. Rasidi, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456210', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CWG-02', 'nama' => 'SMK Negeri 1 Ciawigebang', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Ciawigebang', 'alamat' => 'Jl. Raya Sidaraja No. 5, Ciawigebang',
                'telepon' => '0232-878002', 'email' => 'smkn1ciawigebang@sch.id', 'pic_name' => 'Drs. H. Maman', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456211', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CWG-03', 'nama' => 'MAN 1 Kuningan', 'tier' => 'A', 'kategori' => 'MA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Ciawigebang', 'alamat' => 'Jl. Siliwangi No. 120, Ciawigebang',
                'telepon' => '0232-878003', 'email' => 'man1kuningan@kemenag.go.id', 'pic_name' => 'Drs. H. Muhaimin', 'pic_jabatan' => 'Kepala Madrasah', 'pic_phone' => '08123456212', 'status' => 'Aktif'
            ],
            // Cibeureum
            [
                'kode' => 'SKL-KNG-CBR-01', 'nama' => 'SMA Negeri 1 Cibeureum', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cibeureum', 'alamat' => 'Jl. Raya Cimara No. 19, Cibeureum',
                'telepon' => '0232-879001', 'email' => 'sman1cibeureum@sch.id', 'pic_name' => 'Drs. H. Solihin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456213', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CBR-02', 'nama' => 'SMK Negeri 1 Cibeureum', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cibeureum', 'alamat' => 'Jl. Sukadana No. 4, Cibeureum',
                'telepon' => '0232-879002', 'email' => 'smkn1cibeureum@sch.id', 'pic_name' => 'Suherman, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456214', 'status' => 'Aktif'
            ],
            // Cibingbin
            [
                'kode' => 'SKL-KNG-CBB-01', 'nama' => 'SMA Negeri 1 Cibingbin', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cibingbin', 'alamat' => 'Jl. Raya Sukamaju No. 1, Cibingbin',
                'telepon' => '0232-880001', 'email' => 'sman1cibingbin@sch.id', 'pic_name' => 'Drs. H. Sukirno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456215', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CBB-02', 'nama' => 'SMK Negeri 1 Cibingbin', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cibingbin', 'alamat' => 'Jl. Sindangjawa No. 7, Cibingbin',
                'telepon' => '0232-880002', 'email' => 'smkn1cibingbin@sch.id', 'pic_name' => 'Ahmad Rifa\'i, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456216', 'status' => 'Aktif'
            ],
            // Cidahu
            [
                'kode' => 'SKL-KNG-CDH-01', 'nama' => 'SMA Negeri 1 Cidahu', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cidahu', 'alamat' => 'Jl. Raya Cidahu No. 18, Cidahu',
                'telepon' => '0232-881001', 'email' => 'sman1cidahu@sch.id', 'pic_name' => 'Drs. H. Tarkim', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456217', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CDH-02', 'nama' => 'SMK Negeri 1 Cidahu', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cidahu', 'alamat' => 'Jl. Bunder No. 4, Cidahu',
                'telepon' => '0232-881002', 'email' => 'smkn1cidahu@sch.id', 'pic_name' => 'Budi Santoso, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456218', 'status' => 'Aktif'
            ],
            // Cigandamekar
            [
                'kode' => 'SKL-KNG-CGM-01', 'nama' => 'SMA Negeri 1 Cigandamekar', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cigandamekar', 'alamat' => 'Jl. Raya Bunigeulis No. 1, Cigandamekar',
                'telepon' => '0232-614001', 'email' => 'sman1cigandamekar@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456219', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CGM-02', 'nama' => 'SMK Negeri 1 Cigandamekar', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cigandamekar', 'alamat' => 'Jl. Koreak No. 8, Cigandamekar',
                'telepon' => '0232-614002', 'email' => 'smkn1cigandamekar@sch.id', 'pic_name' => 'Dra. Hj. Nunung', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456220', 'status' => 'Aktif'
            ],
            // Cigugur
            [
                'kode' => 'SKL-KNG-CGG-01', 'nama' => 'SMA Negeri 1 Cigugur', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cigugur', 'alamat' => 'Jl. Sukamulya No. 4, Cigugur',
                'telepon' => '0232-871555', 'email' => 'sman1cigugur@sch.id', 'pic_name' => 'Drs. H. Ade Sunardi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456221', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CGG-02', 'nama' => 'SMK Negeri 1 Cigugur', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cigugur', 'alamat' => 'Jl. Cigugur-Palutungan KM 1, Cigugur',
                'telepon' => '0232-871556', 'email' => 'smkn1cigugur@sch.id', 'pic_name' => 'Suherman, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456222', 'status' => 'Aktif'
            ],
            // Cilebak
            [
                'kode' => 'SKL-KNG-CLB-01', 'nama' => 'SMA Negeri 1 Cilebak', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cilebak', 'alamat' => 'Jl. Raya Cilebak No. 1, Cilebak',
                'telepon' => '0232-882001', 'email' => 'sman1cilebak@sch.id', 'pic_name' => 'Drs. H. Mulyadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456223', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CLB-02', 'nama' => 'SMK Negeri 1 Cilebak', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cilebak', 'alamat' => 'Jl. Legokherang No. 5, Cilebak',
                'telepon' => '0232-882002', 'email' => 'smkn1cilebak@sch.id', 'pic_name' => 'Asep Gunawan, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456224', 'status' => 'Aktif'
            ],
            // Cilimus
            [
                'kode' => 'SKL-KNG-CLM-01', 'nama' => 'SMA Negeri 1 Cilimus', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cilimus', 'alamat' => 'Jl. Raya Cilimus No. 238, Cilimus',
                'telepon' => '0232-614120', 'email' => 'info@sman1cilimus.sch.id', 'pic_name' => 'Drs. H. Jaja Subagja, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456053', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CLM-02', 'nama' => 'SMK Negeri 1 Cilimus', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cilimus', 'alamat' => 'Jl. Bandorasa No. 1, Cilimus',
                'telepon' => '0232-614567', 'email' => 'smkn1cilimus@sch.id', 'pic_name' => 'Dr. H. Ruspendi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456054', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CLM-03', 'nama' => 'SMA IT Husnul Khotimah', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cilimus', 'alamat' => 'Jl. Maniskidul, Cilimus',
                'telepon' => '0232-614888', 'email' => 'info@husnulkhotimah.sch.id', 'pic_name' => 'Ust. M. Syafei, M.Pd.I', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456225', 'status' => 'Aktif'
            ],
            // Cimahi
            [
                'kode' => 'SKL-KNG-CMH-01', 'nama' => 'SMA Negeri 1 Cimahi Kuningan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cimahi', 'alamat' => 'Jl. Raya Cimahi No. 12, Cimahi',
                'telepon' => '0232-883001', 'email' => 'sman1cimahi@sch.id', 'pic_name' => 'Drs. H. Kusnadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456226', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CMH-02', 'nama' => 'SMK Negeri 1 Cimahi Kuningan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cimahi', 'alamat' => 'Jl. Cikeusal No. 4, Cimahi',
                'telepon' => '0232-883002', 'email' => 'smkn1cimahi@sch.id', 'pic_name' => 'Dedi Iskandar, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456227', 'status' => 'Aktif'
            ],
            // Ciniru
            [
                'kode' => 'SKL-KNG-CNR-01', 'nama' => 'SMA Negeri 1 Ciniru', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Ciniru', 'alamat' => 'Jl. Raya Ciniru No. 9, Ciniru',
                'telepon' => '0232-872001', 'email' => 'sman1ciniru@sch.id', 'pic_name' => 'Drs. H. Suharto', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456228', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CNR-02', 'nama' => 'SMK Negeri 1 Ciniru', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Ciniru', 'alamat' => 'Jl. Pinara No. 3, Ciniru',
                'telepon' => '0232-872002', 'email' => 'smkn1ciniru@sch.id', 'pic_name' => 'Budi Waluyo, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456229', 'status' => 'Aktif'
            ],
            // Cipicung
            [
                'kode' => 'SKL-KNG-CPC-01', 'nama' => 'SMA Negeri 1 Cipicung', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cipicung', 'alamat' => 'Jl. Raya Sukamukti No. 7, Cipicung',
                'telepon' => '0232-873001', 'email' => 'sman1cipicung@sch.id', 'pic_name' => 'Drs. H. Maman', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456230', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CPC-02', 'nama' => 'SMK Negeri 1 Cipicung', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Cipicung', 'alamat' => 'Jl. Pamulihan No. 2, Cipicung',
                'telepon' => '0232-873002', 'email' => 'smkn1cipicung@sch.id', 'pic_name' => 'Suwandi, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456231', 'status' => 'Aktif'
            ],
            // Ciwaru
            [
                'kode' => 'SKL-KNG-CWR-01', 'nama' => 'SMA Negeri 1 Ciwaru', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Ciwaru', 'alamat' => 'Jl. Raya Ciwaru No. 14, Ciwaru',
                'telepon' => '0232-884001', 'email' => 'sman1ciwaru@sch.id', 'pic_name' => 'Drs. H. Solihin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456232', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-CWR-02', 'nama' => 'SMK Negeri 1 Ciwaru', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Ciwaru', 'alamat' => 'Jl. Baok No. 7, Ciwaru',
                'telepon' => '0232-884002', 'email' => 'smkn1ciwaru@sch.id', 'pic_name' => 'Dra. Hj. Aminah', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456233', 'status' => 'Aktif'
            ],
            // Darma
            [
                'kode' => 'SKL-KNG-DRM-01', 'nama' => 'SMA Negeri 1 Darma', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Darma', 'alamat' => 'Jl. Raya Darma No. 10, Darma',
                'telepon' => '0232-874001', 'email' => 'sman1darma@sch.id', 'pic_name' => 'Drs. H. Rasidi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456234', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-DRM-02', 'nama' => 'SMK Negeri 1 Darma', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Darma', 'alamat' => 'Jl. Sakerta Timur No. 5, Darma',
                'telepon' => '0232-874002', 'email' => 'smkn1darma@sch.id', 'pic_name' => 'Suherman, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456235', 'status' => 'Aktif'
            ],
            // Garawangi
            [
                'kode' => 'SKL-KNG-GRW-01', 'nama' => 'SMA Negeri 1 Garawangi', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Garawangi', 'alamat' => 'Jl. Raya Lengkong No. 5, Garawangi',
                'telepon' => '0232-875001', 'email' => 'sman1garawangi@sch.id', 'pic_name' => 'Drs. H. Sukirno, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456236', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-GRW-02', 'nama' => 'SMK Negeri 1 Garawangi', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Garawangi', 'alamat' => 'Jl. Purwasari No. 2, Garawangi',
                'telepon' => '0232-875002', 'email' => 'smkn1garawangi@sch.id', 'pic_name' => 'Ahmad Fauzi, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456237', 'status' => 'Aktif'
            ],
            // Hantara
            [
                'kode' => 'SKL-KNG-HTR-01', 'nama' => 'SMA Negeri 1 Hantara', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Hantara', 'alamat' => 'Jl. Raya Hantara No. 15, Hantara',
                'telepon' => '0232-876001', 'email' => 'sman1hantara@sch.id', 'pic_name' => 'Drs. H. Tarkim', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456238', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-HTR-02', 'nama' => 'SMK Negeri 1 Hantara', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Hantara', 'alamat' => 'Jl. Pasiragung No. 6, Hantara',
                'telepon' => '0232-876002', 'email' => 'smkn1hantara@sch.id', 'pic_name' => 'Bambang Irawan, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456239', 'status' => 'Aktif'
            ],
            // Jalaksana
            [
                'kode' => 'SKL-KNG-JLX-01', 'nama' => 'SMA Negeri 1 Jalaksana', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Jalaksana', 'alamat' => 'Jl. Raya Padamenak No. 1, Jalaksana',
                'telepon' => '0232-613001', 'email' => 'sman1jalaksana@sch.id', 'pic_name' => 'Drs. H. Ruspendi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456059', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-JLX-02', 'nama' => 'SMK Negeri 1 Jalaksana', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Jalaksana', 'alamat' => 'Jl. Sembawa No. 10, Jalaksana',
                'telepon' => '0232-613002', 'email' => 'smkn1jalaksana@sch.id', 'pic_name' => 'Dra. Hj. Nunung', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456240', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-JLX-03', 'nama' => 'SMA IT Al-Multazam', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Jalaksana', 'alamat' => 'Jl. Maniskidul - Jalaksana KM 1',
                'telepon' => '0232-613888', 'email' => 'info@almultazam.sch.id', 'pic_name' => 'Ust. H. Budi, Lc', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456241', 'status' => 'Aktif'
            ],
            // Japara
            [
                'kode' => 'SKL-KNG-JPR-01', 'nama' => 'SMA Negeri 1 Japara', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Japara', 'alamat' => 'Jl. Raya Japara No. 2, Japara',
                'telepon' => '0232-615001', 'email' => 'sman1japara@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456242', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-JPR-02', 'nama' => 'SMK Negeri 1 Japara', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Japara', 'alamat' => 'Jl. Cengal No. 11, Japara',
                'telepon' => '0232-615002', 'email' => 'smkn1japara@sch.id', 'pic_name' => 'Ir. Suherman', 'pic_jabatan' => 'Waka Hubin', 'pic_phone' => '08123456243', 'status' => 'Aktif'
            ],
            // Kadugede
            [
                'kode' => 'SKL-KNG-KDG-01', 'nama' => 'SMA Negeri 1 Kadugede', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kadugede', 'alamat' => 'Jl. Raya Kadugede No. 47, Kadugede',
                'telepon' => '0232-871666', 'email' => 'sman1kadugede@sch.id', 'pic_name' => 'Drs. H. Rasidi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456244', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-KDG-02', 'nama' => 'SMK Negeri 1 Kadugede', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kadugede', 'alamat' => 'Jl. Bayuning No. 9, Kadugede',
                'telepon' => '0232-871667', 'email' => 'smkn1kadugede@sch.id', 'pic_name' => 'Suwandi, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456245', 'status' => 'Aktif'
            ],
            // Kalimanggis
            [
                'kode' => 'SKL-KNG-KLM-01', 'nama' => 'SMA Negeri 1 Kalimanggis', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kalimanggis', 'alamat' => 'Jl. Raya Cipancur No. 5, Kalimanggis',
                'telepon' => '0232-877001', 'email' => 'sman1kalimanggis@sch.id', 'pic_name' => 'Drs. H. Sukirno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456246', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-KLM-02', 'nama' => 'SMK Negeri 1 Kalimanggis', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kalimanggis', 'alamat' => 'Jl. Wanasaraya No. 3, Kalimanggis',
                'telepon' => '0232-877002', 'email' => 'smkn1kalimanggis@sch.id', 'pic_name' => 'Ahmad Rifa\'i, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456247', 'status' => 'Aktif'
            ],
            // Karangkancana
            [
                'kode' => 'SKL-KNG-KRC-01', 'nama' => 'SMA Negeri 1 Karangkancana', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Karangkancana', 'alamat' => 'Jl. Raya Margacina No. 6, Karangkancana',
                'telepon' => '0232-885001', 'email' => 'sman1karangkancana@sch.id', 'pic_name' => 'Drs. H. Mulyadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456248', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-KRC-02', 'nama' => 'SMK Negeri 1 Karangkancana', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Karangkancana', 'alamat' => 'Jl. Segong No. 2, Karangkancana',
                'telepon' => '0232-885002', 'email' => 'smkn1karangkancana@sch.id', 'pic_name' => 'Suherman, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456249', 'status' => 'Aktif'
            ],
            // Kramatmulya
            [
                'kode' => 'SKL-KNG-KRM-01', 'nama' => 'SMA Negeri 1 Kramatmulya', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kramatmulya', 'alamat' => 'Jl. Raya Kramatmulya No. 88, Kramatmulya',
                'telepon' => '0232-871777', 'email' => 'sman1kramatmulya@sch.id', 'pic_name' => 'Drs. H. Ade Sunardi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456250', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-KRM-02', 'nama' => 'SMK Negeri 1 Kramatmulya', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kramatmulya', 'alamat' => 'Jl. Kalapagunung No. 5, Kramatmulya',
                'telepon' => '0232-871778', 'email' => 'smkn1kramatmulya@sch.id', 'pic_name' => 'Dedi Iskandar, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456251', 'status' => 'Aktif'
            ],
            // Kuningan (Kota)
            [
                'kode' => 'SKL-KNG-KNG-01', 'nama' => 'SMA Negeri 1 Kuningan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kuningan', 'alamat' => 'Jl. Siliwangi No. 55, Kuningan',
                'telepon' => '0232-871020', 'email' => 'info@sman1kuningan.sch.id', 'pic_name' => 'Drs. H. Agus Supriyadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456050', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-KNG-02', 'nama' => 'SMA Negeri 2 Kuningan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kuningan', 'alamat' => 'Jl. Aruji Kartawinata No. 16, Kuningan',
                'telepon' => '0232-871030', 'email' => 'info@sman2kuningan.sch.id', 'pic_name' => 'Dra. Hj. Lina Marlina', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456051', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-KNG-03', 'nama' => 'SMA Negeri 3 Kuningan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kuningan', 'alamat' => 'Jl. Siliwangi No. 13, Kuningan',
                'telepon' => '0232-871040', 'email' => 'info@sman3kuningan.sch.id', 'pic_name' => 'Drs. H. Maman Suratman', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456052', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-KNG-04', 'nama' => 'SMK Negeri 1 Kuningan', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kuningan', 'alamat' => 'Jl. Sukamulya No. 8, Kuningan',
                'telepon' => '0232-871050', 'email' => 'smkn1kuningan@sch.id', 'pic_name' => 'Dr. H. Ruspendi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456252', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-KNG-05', 'nama' => 'SMK Negeri 2 Kuningan', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kuningan', 'alamat' => 'Jl. RE Martadinata No. 2, Kuningan',
                'telepon' => '0232-871060', 'email' => 'smkn2kuningan@sch.id', 'pic_name' => 'Ir. Suherman', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456253', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-KNG-06', 'nama' => 'SMK Negeri 3 Kuningan', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kuningan', 'alamat' => 'Jl. Raya Cirendang No. 1, Kuningan',
                'telepon' => '0232-871070', 'email' => 'smkn3kuningan@sch.id', 'pic_name' => 'Drs. H. Kusnadi', 'pic_jabatan' => 'Waka Hubin', 'pic_phone' => '08123456254', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-KNG-07', 'nama' => 'MAN 2 Kuningan', 'tier' => 'A', 'kategori' => 'MA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Kuningan', 'alamat' => 'Jl. Pramuka No. 12, Kuningan',
                'telepon' => '0232-871080', 'email' => 'man2kuningan@kemenag.go.id', 'pic_name' => 'Drs. H. Muhaimin', 'pic_jabatan' => 'Kepala Madrasah', 'pic_phone' => '08123456255', 'status' => 'Aktif'
            ],
            // Lebakwangi
            [
                'kode' => 'SKL-KNG-LBW-01', 'nama' => 'SMA Negeri 1 Lebakwangi', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Lebakwangi', 'alamat' => 'Jl. Raya Cinagara No. 1, Lebakwangi',
                'telepon' => '0232-877555', 'email' => 'sman1lebakwangi@sch.id', 'pic_name' => 'Drs. H. Rasidi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456256', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-LBW-02', 'nama' => 'SMK Negeri 1 Lebakwangi', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Lebakwangi', 'alamat' => 'Jl. Mekarwangi No. 11, Lebakwangi',
                'telepon' => '0232-877556', 'email' => 'smkn1lebakwangi@sch.id', 'pic_name' => 'Suwandi, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456257', 'status' => 'Aktif'
            ],
            // Luragung
            [
                'kode' => 'SKL-KNG-LRG-01', 'nama' => 'SMA Negeri 1 Luragung', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Luragung', 'alamat' => 'Jl. Luragung-Cidahu No. 10, Luragung',
                'telepon' => '0232-876111', 'email' => 'sman1luragung@sch.id', 'pic_name' => 'Drs. H. Kusen, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456055', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-LRG-02', 'nama' => 'SMK Negeri 1 Luragung', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Luragung', 'alamat' => 'Jl. Raya Luragung No. 45, Luragung',
                'telepon' => '0232-876222', 'email' => 'smkn1luragung@sch.id', 'pic_name' => 'Drs. H. Taufik Hidayat', 'pic_jabatan' => 'Waka Hubinmas', 'pic_phone' => '08123456056', 'status' => 'Aktif'
            ],
            // Maleber
            [
                'kode' => 'SKL-KNG-MLB-01', 'nama' => 'SMA Negeri 1 Maleber', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Maleber', 'alamat' => 'Jl. Raya Maleber No. 12, Maleber',
                'telepon' => '0232-878555', 'email' => 'sman1maleber@sch.id', 'pic_name' => 'Drs. H. Sukirno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456258', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-MLB-02', 'nama' => 'SMK Negeri 1 Maleber', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Maleber', 'alamat' => 'Jl. Galaherang No. 5, Maleber',
                'telepon' => '0232-878556', 'email' => 'smkn1maleber@sch.id', 'pic_name' => 'Asep Gunawan, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456259', 'status' => 'Aktif'
            ],
            // Mandirancan
            [
                'kode' => 'SKL-KNG-MDR-01', 'nama' => 'SMA Negeri 1 Mandirancan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Mandirancan', 'alamat' => 'Jl. Siliwangi No. 99, Mandirancan',
                'telepon' => '0232-615555', 'email' => 'sman1mandirancan@sch.id', 'pic_name' => 'Drs. H. Tarkim', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456260', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-MDR-02', 'nama' => 'SMK Negeri 1 Mandirancan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Mandirancan', 'alamat' => 'Jl. Kertawinangun No. 3, Mandirancan',
                'telepon' => '0232-615556', 'email' => 'smkn1mandirancan@sch.id', 'pic_name' => 'Dra. Hj. Nunung', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456261', 'status' => 'Aktif'
            ],
            // Nusaherang
            [
                'kode' => 'SKL-KNG-NSH-01', 'nama' => 'SMA Negeri 1 Nusaherang', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Nusaherang', 'alamat' => 'Jl. Raya Nusaherang No. 11, Nusaherang',
                'telepon' => '0232-871888', 'email' => 'sman1nusaherang@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456262', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-NSH-02', 'nama' => 'SMK Negeri 1 Nusaherang', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Nusaherang', 'alamat' => 'Jl. Haurkuning No. 4, Nusaherang',
                'telepon' => '0232-871889', 'email' => 'smkn1nusaherang@sch.id', 'pic_name' => 'Suherman, S.T', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456263', 'status' => 'Aktif'
            ],
            // Pancalang
            [
                'kode' => 'SKL-KNG-PCL-01', 'nama' => 'SMA Negeri 1 Pancalang', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Pancalang', 'alamat' => 'Jl. Raya Pancalang No. 8, Pancalang',
                'telepon' => '0232-616001', 'email' => 'sman1pancalang@sch.id', 'pic_name' => 'Drs. H. Ade Sunardi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456264', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-PCL-02', 'nama' => 'SMK Negeri 1 Pancalang', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Pancalang', 'alamat' => 'Jl. Sarewu No. 2, Pancalang',
                'telepon' => '0232-616002', 'email' => 'smkn1pancalang@sch.id', 'pic_name' => 'Ahmad Rifa\'i, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456265', 'status' => 'Aktif'
            ],
            // Pasawahan
            [
                'kode' => 'SKL-KNG-PSW-01', 'nama' => 'SMA Negeri 1 Pasawahan Kuningan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Pasawahan', 'alamat' => 'Jl. Raya Pasawahan No. 14, Pasawahan',
                'telepon' => '0232-617001', 'email' => 'sman1pasawahankng@sch.id', 'pic_name' => 'Drs. H. Solihin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456266', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-PSW-02', 'nama' => 'SMK Negeri 1 Pasawahan Kuningan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Pasawahan', 'alamat' => 'Jl. Padamatang No. 5, Pasawahan',
                'telepon' => '0232-617002', 'email' => 'smkn1pasawahankng@sch.id', 'pic_name' => 'Budi Santoso, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456267', 'status' => 'Aktif'
            ],
            // Selajambe
            [
                'kode' => 'SKL-KNG-SLJ-01', 'nama' => 'SMA Negeri 1 Selajambe', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Selajambe', 'alamat' => 'Jl. Raya Selajambe No. 8, Selajambe',
                'telepon' => '0232-872555', 'email' => 'sman1selajambe@sch.id', 'pic_name' => 'Drs. H. Rasidi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456268', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-SLJ-02', 'nama' => 'SMK Negeri 1 Selajambe', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Selajambe', 'alamat' => 'Jl. Cantilan No. 2, Selajambe',
                'telepon' => '0232-872556', 'email' => 'smkn1selajambe@sch.id', 'pic_name' => 'Suwandi, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456269', 'status' => 'Aktif'
            ],
            // Sindangagung
            [
                'kode' => 'SKL-KNG-SDA-01', 'nama' => 'SMA Negeri 1 Sindangagung', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Sindangagung', 'alamat' => 'Jl. Raya Kertawangunan No. 12, Sindangagung',
                'telepon' => '0232-871999', 'email' => 'sman1sindangagung@sch.id', 'pic_name' => 'Drs. H. Sukirno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456270', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-SDA-02', 'nama' => 'SMK Negeri 1 Sindangagung', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Sindangagung', 'alamat' => 'Jl. Babakanreuma No. 4, Sindangagung',
                'telepon' => '0232-871998', 'email' => 'smkn1sindangagung@sch.id', 'pic_name' => 'Dedi Iskandar, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456271', 'status' => 'Aktif'
            ],
            // Subang
            [
                'kode' => 'SKL-KNG-SBG-01', 'nama' => 'SMA Negeri 1 Subang Kuningan', 'tier' => 'B', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Subang', 'alamat' => 'Jl. Raya Subang No. 21, Subang',
                'telepon' => '0232-873555', 'email' => 'sman1subangkng@sch.id', 'pic_name' => 'Drs. H. Mulyadi', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456272', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-KNG-SBG-02', 'nama' => 'SMK Negeri 1 Subang Kuningan', 'tier' => 'B', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Kuningan', 'kecamatan' => 'Subang', 'alamat' => 'Jl. Situgede No. 4, Subang',
                'telepon' => '0232-873556', 'email' => 'smkn1subangkng@sch.id', 'pic_name' => 'Asep Gunawan, S.Pd', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456273', 'status' => 'Aktif'
            ],

            // ══════════════════════════════════════════════════════════════════
            // 6. KABUPATEN BREBES (Jawa Tengah)
            // ══════════════════════════════════════════════════════════════════
            [
                'kode' => 'SKL-BBS-BBS-01', 'nama' => 'SMA Negeri 1 Brebes', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Brebes', 'kecamatan' => 'Brebes', 'alamat' => 'Jl. Dr. Setiabudi No. 11, Brebes',
                'telepon' => '0283-671001', 'email' => 'sman1brebes@sch.id', 'pic_name' => 'Drs. H. Samsudin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456060', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-BBS-BBS-02', 'nama' => 'SMA Negeri 2 Brebes', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Brebes', 'kecamatan' => 'Brebes', 'alamat' => 'Jl. Ahmad Yani No. 77, Brebes',
                'telepon' => '0283-671002', 'email' => 'sman2brebes@sch.id', 'pic_name' => 'Dra. Hj. Sri Lestari', 'pic_jabatan' => 'Guru BK', 'pic_phone' => '08123456274', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-BBS-BBS-03', 'nama' => 'SMK Negeri 1 Brebes', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Brebes', 'kecamatan' => 'Brebes', 'alamat' => 'Jl. Yos Sudarso No. 8, Brebes',
                'telepon' => '0283-671022', 'email' => 'smkn1brebes@sch.id', 'pic_name' => 'Bambang Sugiharto, S.T', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456061', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-BBS-JTB-01', 'nama' => 'SMA Negeri 1 Jatibarang Brebes', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Brebes', 'kecamatan' => 'Jatibarang', 'alamat' => 'Jl. Raya Barat Jatibarang, Brebes',
                'telepon' => '0283-672001', 'email' => 'sman1jatibarangbbs@sch.id', 'pic_name' => 'Drs. H. Suwarno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456275', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-BBS-BMY-01', 'nama' => 'SMA Negeri 1 Bumiayu', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Brebes', 'kecamatan' => 'Bumiayu', 'alamat' => 'Jl. Pangeran Diponegoro No. 12, Bumiayu',
                'telepon' => '0283-432001', 'email' => 'sman1bumiayu@sch.id', 'pic_name' => 'Drs. H. Rasidi, M.Pd', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456276', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-BBS-BMY-02', 'nama' => 'SMK Negeri 1 Bumiayu', 'tier' => 'A', 'kategori' => 'SMK',
                'kota_nama' => 'Kabupaten Brebes', 'kecamatan' => 'Bumiayu', 'alamat' => 'Jl. Lingkar Luar Bumiayu KM 2',
                'telepon' => '0283-432002', 'email' => 'smkn1bumiayu@sch.id', 'pic_name' => 'Ir. Suherman', 'pic_jabatan' => 'Hubinmas', 'pic_phone' => '08123456277', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-BBS-KTG-01', 'nama' => 'SMA Negeri 1 Ketanggungan', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Brebes', 'kecamatan' => 'Ketanggungan', 'alamat' => 'Jl. Jend. Sudirman No. 4, Ketanggungan',
                'telepon' => '0283-673001', 'email' => 'sman1ketanggungan@sch.id', 'pic_name' => 'Drs. H. Tarkim', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456278', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-BBS-TJG-01', 'nama' => 'SMA Negeri 1 Tanjung Brebes', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Brebes', 'kecamatan' => 'Tanjung', 'alamat' => 'Jl. Cemara No. 24, Tanjung',
                'telepon' => '0283-674001', 'email' => 'sman1tanjung@sch.id', 'pic_name' => 'Drs. H. Mulyono', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456279', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-BBS-BLK-01', 'nama' => 'SMA Negeri 1 Bulakamba', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Brebes', 'kecamatan' => 'Bulakamba', 'alamat' => 'Jl. Raya Bulakamba KM 8, Brebes',
                'telepon' => '0283-675001', 'email' => 'sman1bulakamba@sch.id', 'pic_name' => 'Drs. H. Sukirno', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456280', 'status' => 'Aktif'
            ],
            [
                'kode' => 'SKL-BBS-LSR-01', 'nama' => 'SMA Negeri 1 Losari Brebes', 'tier' => 'A', 'kategori' => 'SMA',
                'kota_nama' => 'Kabupaten Brebes', 'kecamatan' => 'Losari', 'alamat' => 'Jl. Jenderal Sudirman No. 1, Losari Brebes',
                'telepon' => '0283-676001', 'email' => 'sman1losaribbs@sch.id', 'pic_name' => 'Drs. H. Solihin', 'pic_jabatan' => 'Kepala Sekolah', 'pic_phone' => '08123456281', 'status' => 'Aktif'
            ],
        ];

        foreach ($sekolahs as $item) {
            $kecId = $getKecamatanId($item['kota_nama'], $item['kecamatan']);
            $kotaNama = $item['kota_nama'];
            unset($item['kota_nama']);

            // Map category ID
            $kategoriName = $item['kategori'] ?? 'SMA';
            unset($item['kategori']);

            if ($kategoriName === 'SMK') {
                $item['kategori_id'] = $smkCat?->id;
            } elseif ($kategoriName === 'MA') {
                $item['kategori_id'] = $maCat?->id;
            } else {
                $item['kategori_id'] = $smaCat?->id;
            }

            $item['wilayah_id'] = $kecId;

            Sekolah::updateOrCreate(
                ['kode' => $item['kode']],
                $item
            );
        }
    }
}
