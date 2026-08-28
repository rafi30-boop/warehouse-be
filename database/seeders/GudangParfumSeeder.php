<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\AktivitasLog;
use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangKeluarDetail;
use App\Models\BarangMasuk;
use App\Models\BarangMasukDetail;
use App\Models\BatchBarang;
use App\Models\Customer;
use App\Models\Gudang;
use App\Models\HistoryHarga;
use App\Models\IzinRequest;
use App\Models\JadwalPetugas;
use App\Models\KartuStok;
use App\Models\Kategori;
use App\Models\LokasiRak;
use App\Models\MutasiStok;
use App\Models\Notifikasi;
use App\Models\Petugas;
use App\Models\Satuan;
use App\Models\Shift;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder tema "Gudang Parfum" — jaringan distribusi parfum 3 kota:
 * Jakarta (pusat), Bandung, Surabaya. Semua data saling bertaut:
 * users -> petugas -> jadwal -> absensi/izin, gudang -> rak,
 * supplier/customer -> dokumen masuk/keluar -> kartu stok,
 * barang -> batch + history harga + mutasi + opname.
 */
class GudangParfumSeeder extends Seeder
{
    /** @var array<int, array<string, float>> saldo stok berjalan [barangId][gudangId] */
    private array $saldo = [];

    private array $events = [];

    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => 'Administrator',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'is_active' => true,
            ]
        );
        $admin->assignRole('super-admin');

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole('super-admin');

        // ==========================================================
        // 1. GUDANG — Jakarta (pusat), Bandung, Surabaya
        // ==========================================================
        $gudangJkt = Gudang::firstOrCreate(['kode' => 'GDG-JKT'], [
            'nama' => 'Gudang Pusat Jakarta',
            'alamat' => 'Kawasan Berikat Nusantara Blok C No. 12, Jl. Raya Cakung, Pulogadung, Jakarta Timur 13920',
            'pic' => 'Budi Santoso',
            'telepon' => '021-4608871',
            'latitude' => -6.1751000,
            'longitude' => 106.9175400,
            'status' => 'aktif',
        ]);

        $gudangBdg = Gudang::firstOrCreate(['kode' => 'GDG-BDG'], [
            'nama' => 'Gudang Bandung',
            'alamat' => 'Jl. Soekarno-Hatta KM 10 Ruko Sentra Niago No. 27, Kelapa Dua, Bandung 40286',
            'pic' => 'Agus Wijaya',
            'telepon' => '022-7532214',
            'latitude' => -6.9421500,
            'longitude' => 107.6383100,
            'status' => 'aktif',
        ]);

        $gudangSby = Gudang::firstOrCreate(['kode' => 'GDG-SBY'], [
            'nama' => 'Gudang Surabaya',
            'alamat' => 'Rungkut Industrial Estate Blok F No. 8, Jl. Kali Rungkut, Rungkut, Surabaya 60293',
            'pic' => 'Rudi Hartono',
            'telepon' => '031-2985566',
            'latitude' => -7.3401200,
            'longitude' => 112.7234800,
            'status' => 'aktif',
        ]);

        // ==========================================================
        // 2. KATEGORI (bertingkat)
        // ==========================================================
        $katParfum = Kategori::firstOrCreate(['nama' => 'Parfum'], ['deskripsi' => 'Parfum siap jual berbagai konsentrasi']);
        $katAttar = Kategori::firstOrCreate(['nama' => 'Minyak Wangi & Attar'], ['deskripsi' => 'Attar dan minyak wangi non-alkohol']);
        $katBodycare = Kategori::firstOrCreate(['nama' => 'Body Care & Mist'], ['deskripsi' => 'Body mist, lotion, dan perawatan beraroma']);
        $katRumah = Kategori::firstOrCreate(['nama' => 'Aroma Rumah'], ['deskripsi' => 'Diffuser dan lilin aromaterapi']);
        $katKemasan = Kategori::firstOrCreate(['nama' => 'Aksesori & Kemasan'], ['deskripsi' => 'Botol kosong, atomizer, dan box kemasan']);

        $katEdp = Kategori::firstOrCreate(['nama' => 'Eau de Parfum'], ['parent_id' => $katParfum->id, 'deskripsi' => 'Konsentrasi 15-20%, tahan 8-12 jam']);
        $katEdt = Kategori::firstOrCreate(['nama' => 'Eau de Toilette'], ['parent_id' => $katParfum->id, 'deskripsi' => 'Konsentrasi 5-15%, ringan untuk harian']);
        $katExtrait = Kategori::firstOrCreate(['nama' => 'Extrait de Parfum'], ['parent_id' => $katParfum->id, 'deskripsi' => 'Konsentrasi tertinggi 20-30%']);
        $katLokal = Kategori::firstOrCreate(['nama' => 'Parfum Lokal Nusantara'], ['parent_id' => $katParfum->id, 'deskripsi' => 'Produksi sendiri, bahan baku lokal']);
        $katAttarItem = Kategori::firstOrCreate(['nama' => 'Attar Roll-On'], ['parent_id' => $katAttar->id, 'deskripsi' => 'Attar non-alkohol kemasan roll-on']);

        // ==========================================================
        // 3. SATUAN
        // ==========================================================
        $satuans = [];
        foreach ([
            ['Botol', 'btl'], ['Dus', 'dus'], ['Box', 'box'],
            ['Mililiter', 'ml'], ['Pcs', 'pcs'], ['Set', 'set'],
        ] as [$nama, $sing]) {
            $satuans[$sing] = Satuan::firstOrCreate(['nama' => $nama], ['singkatan' => $sing]);
        }

        // ==========================================================
        // 4. SHIFT
        // ==========================================================
        $shiftPagi = Shift::firstOrCreate(['nama' => 'Pagi'], [
            'jam_masuk' => '07:00', 'jam_pulang' => '15:00',
            'toleransi_masuk' => 15, 'toleransi_pulang' => 10, 'status' => 'aktif',
        ]);
        $shiftSiang = Shift::firstOrCreate(['nama' => 'Siang'], [
            'jam_masuk' => '15:00', 'jam_pulang' => '23:00',
            'toleransi_masuk' => 15, 'toleransi_pulang' => 10, 'status' => 'aktif',
        ]);
        $shiftMalam = Shift::firstOrCreate(['nama' => 'Malam'], [
            'jam_masuk' => '23:00', 'jam_pulang' => '07:00',
            'toleransi_masuk' => 15, 'toleransi_pulang' => 10, 'status' => 'aktif',
        ]);

        // ==========================================================
        // 5. SUPPLIER
        // ==========================================================
        $suppliers = [];
        foreach ([
            ['SUP-001', 'PT Aroma Essentia Nusantara', 'Hendra Gunawan', '021-8842250', 'sales@aromaessentia.co.id', 'Jl. Pahlawan Seribu, Ruko Golden Boulevard Blok B2, Serpong, Tangerang Selatan', '01.884.225.0-301.000'],
            ['SUP-002', 'CV Parfum Gracia Mandiri', 'Lilis Suryani', '022-6012834', 'graciamandiri@gmail.com', 'Jl. Cihampelas No. 45, Coblong, Bandung', '02.601.283.4-506.000'],
            ['SUP-003', 'PT Surya Fragance Indonesia', 'Wawan Setiawan', '031-8431199', 'order@suryafragance.co.id', 'Jl. Margomulyo No. 88, Romokalisari, Benowo, Surabaya', '03.843.119.9-602.000'],
            ['SUP-004', 'UD Kemasan Wangi Sejahtera', 'Yanto Prasetyo', '021-5529017', 'kemasanwangi@yahoo.com', 'Pasar Kembangan Ruko W-11, Jembatan Lima, Penjaringan, Jakarta Utara', null],
            ['SUP-005', 'PT Attar Al Arabia Indo', 'Sheikh Faisal Al-Rashid', '021-6354412', 'export@attalarabia.id', 'Menara Kuningan Lt. 14, Jl. HR Rasuna Said, Kuningan, Jakarta Selatan', '05.635.441.2-431.000'],
        ] as [$kode, $nama, $kontak, $telp, $email, $alamat, $npwp]) {
            $suppliers[$kode] = Supplier::firstOrCreate(['kode' => $kode], [
                'tipe' => 'perusahaan', 'nama' => $nama, 'kontak' => $kontak,
                'telepon' => $telp, 'email' => $email, 'alamat' => $alamat, 'npwp' => $npwp,
            ]);
        }

        // ==========================================================
        // 6. CUSTOMER
        // ==========================================================
        $customers = [];
        foreach ([
            ['CUS-001', 'perusahaan', 'Toko Parfum Melati', 'Melati Kusuma', '021-7503321', 'tokomelati@gmail.com', 'Jl. Fatmawati No. 102, Cilandak, Jakarta Selatan', '09.750.332.1-054.000'],
            ['CUS-002', 'perusahaan', 'CV Sinar Parfum Retail', 'Deni Mulyana', '022-4211887', 'sinarparfum@cbn.net.id', 'Jl. ABC No. 21, Antapani, Bandung', '04.421.188.7-506.000'],
            ['CUS-003', 'perusahaan', 'PT Global Scentary Distribution', 'Sylvia Tanuwijaya', '031-5679921', 'purchasing@scentary.co.id', 'Jl. Raya Gelam No. 150, Karang Pilang, Surabaya', '06.567.992.1-602.000'],
            ['CUS-004', 'perusahaan', 'parfumerie.id (Online Shop)', 'Kevin Halim', '0812-9900-1122', 'ops@parfumerie.id', 'Gedung Cyber 2 Tower Lt. 21, Kuningan Barat, Jakarta Selatan', null],
            ['CUS-005', 'pribadi', 'Ibu Ratna Dewi (Reseller)', 'Ratna Dewi', '0857-3344-5566', 'ratnadewi88@gmail.com', 'Perum Griya Asri Blok D2 No. 5, Waru, Sidoarjo', null],
        ] as [$kode, $tipe, $nama, $kontak, $telp, $email, $alamat, $npwp]) {
            $customers[$kode] = Customer::firstOrCreate(['kode' => $kode], [
                'tipe' => $tipe, 'nama' => $nama, 'kontak' => $kontak,
                'telepon' => $telp, 'email' => $email, 'alamat' => $alamat, 'npwp' => $npwp,
            ]);
        }

        // ==========================================================
        // 7. LOKASI RAK per gudang
        // ==========================================================
        $gdgIds = ['GDG-JKT' => $gudangJkt->id, 'GDG-BDG' => $gudangBdg->id, 'GDG-SBY' => $gudangSby->id];
        $raks = [];
        $rakDefs = [
            'GDG-JKT' => [
                ['JKT-A01', 'A - Parfum Cair Impor', 600], ['JKT-A02', 'A - Parfum Cair Impor', 600],
                ['JKT-A03', 'A - Parfum Cair Lokal', 600], ['JKT-B01', 'B - Kemasan & Aksesori', 800],
                ['JKT-B02', 'B - Kemasan & Aksesori', 800], ['JKT-C01', 'C - Retur & QC', 200],
            ],
            'GDG-BDG' => [
                ['BDG-A01', 'A - Parfum Cair', 400], ['BDG-A02', 'A - Parfum Cair', 400],
                ['BDG-B01', 'B - Home Fragrance', 500], ['BDG-C01', 'C - Retur', 150],
            ],
            'GDG-SBY' => [
                ['SBY-A01', 'A - Parfum Cair', 500], ['SBY-A02', 'A - Parfum Cair', 500],
                ['SBY-B01', 'B - Kemasan', 700], ['SBY-C01', 'C - Retur & QC', 200],
            ],
        ];
        foreach ($rakDefs as $kodeGdg => $list) {
            foreach ($list as [$kodeRak, $zona, $kapasitas]) {
                $raks[$kodeRak] = LokasiRak::firstOrCreate(
                    ['gudang_id' => $gdgIds[$kodeGdg], 'kode_rak' => $kodeRak],
                    ['zona' => $zona, 'kapasitas' => $kapasitas, 'status' => 'aktif']
                );
            }
        }

        // ==========================================================
        // 8. BARANG (produk parfum)
        // ==========================================================
        $barangDefs = [
            // [skuSuffix, nama, katKey, satuanKey, min, max, beratGr, beli, jual, deskripsi]
            ['0001', 'EDP LAventure Noir 100ml', $katEdp->id, 'btl', 24, 240, 420, 185000, 275000, 'Woody aromatic. Top: bergamot, lemon. Heart: lavender, jasmine. Base: oud, amber, musk. Import original.'],
            ['0002', 'EDP Velvet Oud 50ml', $katEdp->id, 'btl', 18, 180, 260, 165000, 245000, 'Oud creamy dengan saffron dan rose turki, cocok malam hari. Import original.'],
            ['0003', 'EDT Citrus Breeze 100ml', $katEdt->id, 'btl', 24, 240, 400, 145000, 210000, 'Fresh citrus untuk harian: neroli, green mandarin, vetiver. Best seller kantor.'],
            ['0004', 'EDT Sakura Bloom 75ml', $katEdt->id, 'btl', 18, 144, 320, 138000, 199000, 'Floral fruity sakura, pear, dan white musk. Favorit pasar Jepang-style.'],
            ['0005', 'Extrait Royal Amber 30ml', $katExtrait->id, 'btl', 12, 96, 180, 320000, 495000, 'Konsentrasi tertinggi. Amber, labdanum, vanilla bourbon. Edisi terbatas.'],
            ['0006', 'EDP Nusantara Kenanga 100ml', $katLokal->id, 'btl', 36, 360, 380, 42000, 89000, 'Produksi sendiri. Kenanga (cananga) dari Garut, sandalwood, tonka. Halal certified.'],
            ['0007', 'EDP Nusantara Melati Gayo 100ml', $katLokal->id, 'btl', 36, 360, 380, 45000, 92000, 'Melati Gayo Aceh, ylang-ylang, vanilla. Produksi sendiri batch mingguan.'],
            ['0008', 'Attar Al-Hayat Roll-On 12ml', $katAttarItem->id, 'pcs', 48, 480, 50, 35000, 75000, 'Attar non-alkohol, oud-bakhoor lembut. Roll-on travel friendly.'],
            ['0009', 'Attar Musk Zamzam 6ml', $katAttarItem->id, 'pcs', 48, 480, 35, 28000, 65000, 'White musk bersih tahan lama, non-alkohol. Stok ramai saat Ramadan.'],
            ['0010', 'Body Mist Vanilla Sky 250ml', $katBodycare->id, 'btl', 36, 360, 300, 22000, 45000, 'Body mist ringan vanilla-caramel, alkohol kosmetik grade A.'],
            ['0011', 'Body Mist Green Tea 250ml', $katBodycare->id, 'btl', 36, 360, 300, 21000, 42000, 'Body mist green tea fresh, cocok remaja dan pascaolahraga.'],
            ['0012', 'Diffuser Lemongrass 200ml', $katRumah->id, 'set', 24, 192, 550, 48000, 85000, 'Reed diffuser lemongrass + 8 rotan stick. Isi refill tersedia.'],
            ['0013', 'Lilin Aromaterapi Lavender 180gr', $katRumah->id, 'pcs', 24, 168, 400, 38000, 69000, 'Soy wax, sumbu kapas, essential oil lavender Prancis. Burn time 35 jam.'],
            ['0014', 'Botol Kosong Amber Glass 100ml', $katKemasan->id, 'pcs', 120, 1200, 250, 6500, 12000, 'Botol amber + pump hitam, cocok refill parfum lokal. Grade kosmetik.'],
            ['0015', 'Atomizer Spray Gold 10ml', $katKemasan->id, 'pcs', 96, 960, 60, 9000, 18000, 'Atomizer aluminium gold, segel rapat, untuk travel size.'],
            ['0016', 'Box Kemasan Premium Hardcase', $katKemasan->id, 'box', 60, 600, 800, 12500, 22000, 'Isi 50 pcs box hardcase emboss logo custom, magnet clip.'],
            ['0017', 'EDP Aqua Fresh Sport 100ml', $katEdp->id, 'btl', 24, 192, 410, 128000, 189000, 'Aromatic aquatic: sea salt, rosemary, driftwood. Untuk segmen sporty.'],
            ['0018', 'EDT Rose Petal 90ml', $katEdt->id, 'btl', 24, 192, 340, 118000, 175000, 'Damascena rose, lychee, praline. Segmen wanita muda.'],
        ];

        $barangs = [];
        foreach ($barangDefs as $i => [$suffix, $nama, $katId, $satKey, $min, $max, $berat, $beli, $jual, $desc]) {
            $barcode = '899321654'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            $barangs[$suffix] = Barang::updateOrCreate(
                ['sku' => 'PRF-'.$suffix],
                [
                    'barcode' => $barcode, 'nama' => $nama, 'kategori_id' => $katId,
                    'satuan_id' => $satuans[$satKey]->id, 'min_stok' => $min, 'max_stok' => $max,
                    'berat' => $berat, 'harga_beli' => $beli, 'harga_jual' => $jual,
                    'deskripsi' => $desc, 'status' => 'aktif',
                ]
            );
        }

        // ==========================================================
        // 9. USERS STAFF + PETUGAS (3 kota)
        // ==========================================================
        $staffDefs = [
            ['budi.santoso@gudangparfum.id', 'Budi Santoso', 'PGJ-101', '0812-1100-2201', $gudangJkt->id, 'PG-001', 'Kepala Gudang', 'Jakarta', '2024-03-01'],
            ['siti.rahayu@gudangparfum.id', 'Siti Rahayu', 'PGJ-102', '0812-1100-2202', $gudangJkt->id, 'PG-002', 'Admin Stok', 'Jakarta', '2024-06-17'],
            ['agus.wijaya@gudangparfum.id', 'Agus Wijaya', 'PGB-201', '0812-3300-4401', $gudangBdg->id, 'PG-003', 'Kepala Gudang', 'Bandung', '2024-08-05'],
            ['dewi.lestari@gudangparfum.id', 'Dewi Lestari', 'PGB-202', '0812-3300-4402', $gudangBdg->id, 'PG-004', 'Checker Gudang', 'Bandung', '2025-01-13'],
            ['rudi.hartono@gudangparfum.id', 'Rudi Hartono', 'PGS-301', '0813-5500-6601', $gudangSby->id, 'PG-005', 'Kepala Gudang', 'Surabaya', '2024-05-20'],
            ['intan.permata@gudangparfum.id', 'Intan Permata', 'PGS-302', '0813-5500-6602', $gudangSby->id, 'PG-006', 'Admin Stok', 'Surabaya', '2025-02-03'],
        ];

        $staff = [];
        $usedPetugasKodes = Petugas::withTrashed()->pluck('kode')->all();
        foreach ($staffDefs as $idx => [$email, $name, $noPegawai, $telp, $gudangId, $kodePetugas, $jabatan, $area, $bergabung]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name, 'password' => Hash::make('password'),
                'gudang_id' => $gudangId, 'no_pegawai' => $noPegawai, 'telepon' => $telp,
                'is_active' => true, 'last_login_at' => Carbon::now()->subDays($idx % 3)->setHour(7)->setMinute(rand(5, 45)),
            ]);
            $user->assignRole('operator');

            if (! in_array($kodePetugas, $usedPetugasKodes)) {
                $usedPetugasKodes[] = $kodePetugas;
            } else {
                $n = 1;
                do {
                    $kodePetugas = 'PG-'.str_pad((string) $n++, 3, '0', STR_PAD_LEFT);
                } while (in_array($kodePetugas, $usedPetugasKodes));
                $usedPetugasKodes[] = $kodePetugas;
            }

            Petugas::firstOrCreate(['user_id' => $user->id], [
                'nama' => $name, 'kode' => $kodePetugas, 'telepon' => $telp,
                'jabatan' => $jabatan, 'area_kerja' => $area,
                'tanggal_bergabung' => $bergabung,
                'status_operasional' => $name === 'Dewi Lestari' ? 'Cuti' : 'Aktif',
            ]);

            $staff[$email] = ['user' => $user, 'shift' => $idx % 2 === 0 ? $shiftPagi : $shiftSiang];
        }

        $budi = $staff['budi.santoso@gudangparfum.id']['user'];
        $siti = $staff['siti.rahayu@gudangparfum.id']['user'];
        $agus = $staff['agus.wijaya@gudangparfum.id']['user'];
        $dewi = $staff['dewi.lestari@gudangparfum.id']['user'];
        $rudi = $staff['rudi.hartono@gudangparfum.id']['user'];
        $intan = $staff['intan.permata@gudangparfum.id']['user'];

        // ==========================================================
        // 10. BARANG MASUK (approved -> kartu stok 'in')
        // ==========================================================
        $masukDefs = [
            ['BM-2026-0001', 'GDG-JKT', 'SUP-001', -40, 'SJ-JKT-2607-001', 'approved', $budi, 'PO-2026-0712 container AB-771 dari Pelabuhan Tanjung Priok', [
                ['0001', 120, 'JKT-A01'], ['0002', 80, 'JKT-A01'], ['0003', 150, 'JKT-A02'], ['0005', 36, 'JKT-A02'],
            ]],
            ['BM-2026-0002', 'GDG-JKT', 'SUP-004', -34, 'SJ-JKT-2607-002', 'approved', $budi, 'Restock kemasan bulanan', [
                ['0014', 400, 'JKT-B01'], ['0015', 300, 'JKT-B01'], ['0016', 200, 'JKT-B02'],
            ]],
            ['BM-2026-0003', 'GDG-BDG', 'SUP-002', -28, 'SJ-BDG-2607-001', 'approved', $agus, 'Kiriman produksi lokal minggu ke-3', [
                ['0006', 144, 'BDG-A01'], ['0007', 120, 'BDG-A01'], ['0011', 96, 'BDG-A02'],
            ]],
            ['BM-2026-0004', 'GDG-SBY', 'SUP-003', -22, 'SJ-SBY-2608-001', 'approved', $rudi, 'Alokasi stok wilayah timur', [
                ['0004', 90, 'SBY-A01'], ['0010', 180, 'SBY-A02'], ['0017', 72, 'SBY-A01'],
            ]],
            ['BM-2026-0005', 'GDG-BDG', 'SUP-002', -16, 'SJ-BDG-2608-001', 'approved', $agus, 'Top-up home fragrance', [
                ['0012', 60, 'BDG-B01'], ['0013', 48, 'BDG-B01'],
            ]],
            ['BM-2026-0006', 'GDG-JKT', 'SUP-001', -14, 'SJ-JKT-2608-001', 'approved', $budi, 'Container kedua + produk baru Rose Petal', [
                ['0018', 100, 'JKT-A03'], ['0003', 100, 'JKT-A02'],
            ]],
            ['BM-2026-0007', 'GDG-JKT', 'SUP-005', -2, 'SJ-JKT-2608-002', 'pending', $siti, 'PO attar Ramadan pertama - menunggu cek fisik', [
                ['0008', 240, 'JKT-A03'], ['0009', 180, 'JKT-A03'],
            ]],
        ];

        foreach ($masukDefs as [$ref, $kodeGdg, $supKode, $dayOffset, $suratJalan, $status, $creator, $ket, $items]) {
            $masuk = BarangMasuk::updateOrCreate(['no_referensi' => $ref], [
                'nomor_surat_jalan' => $suratJalan, 'gudang_id' => $gdgIds[$kodeGdg],
                'supplier_id' => $suppliers[$supKode]->id, 'tanggal' => Carbon::now()->addDays($dayOffset)->toDateString(),
                'keterangan' => $ket, 'status' => $status, 'created_by' => $creator->id,
                'approved_by' => $status === 'approved' ? $admin->id : null,
                'approved_at' => $status === 'approved' ? Carbon::now()->addDays($dayOffset)->addHours(6) : null,
            ]);

            foreach ($items as [$sku, $qty, $rakKode]) {
                $barang = $barangs[$sku];
                BarangMasukDetail::updateOrCreate(
                    ['barang_masuk_id' => $masuk->id, 'barang_id' => $barang->id],
                    [
                        'lokasi_rak_id' => $raks[$rakKode]->id, 'qty' => $qty,
                        'harga_satuan' => $barang->harga_beli, 'diskon' => 0, 'pajak' => 0,
                        'subtotal' => $qty * $barang->harga_beli,
                        'created_at' => $masuk->created_at,
                    ]
                );

                if ($status === 'approved') {
                    $this->pushEvent(Carbon::now()->addDays($dayOffset)->setTime(9, 30), $barang->id, $gdgIds[$kodeGdg], $raks[$rakKode]->id, 'in', $qty, BarangMasuk::class, $masuk->id, "Penerimaan {$ref} dari {$suppliers[$supKode]->nama}", $creator->id);
                }
            }
        }

        // ==========================================================
        // 11. BARANG KELUAR (approved/delivered -> kartu stok 'out')
        // ==========================================================
        $keluarDefs = [
            ['BK-2026-0001', 'GDG-JKT', 'CUS-001', -18, 'approved', 'delivered', $budi, 'Pesanan rutin toko Melati', [
                ['0001', 24, 'JKT-A01'], ['0002', 16, 'JKT-A01'], ['0003', 30, 'JKT-A02'],
            ]],
            ['BK-2026-0002', 'GDG-JKT', 'CUS-004', -12, 'approved', 'delivered', $budi, 'Restock online shop + bundling kemasan', [
                ['0003', 40, 'JKT-A02'], ['0014', 100, 'JKT-B01'], ['0015', 60, 'JKT-B01'],
            ]],
            ['BK-2026-0003', 'GDG-BDG', 'CUS-002', -9, 'approved', null, $agus, 'Siap kirim kurir kargo, tunggu pick-up', [
                ['0006', 24, 'BDG-A01'], ['0007', 18, 'BDG-A01'],
            ]],
            ['BK-2026-0004', 'GDG-SBY', 'CUS-003', -8, 'approved', 'delivered', $rudi, 'Kontrak distribusi bulanan', [
                ['0004', 30, 'SBY-A01'], ['0010', 60, 'SBY-A02'], ['0017', 20, 'SBY-A01'],
            ]],
            ['BK-2026-0005', 'GDG-JKT', 'CUS-001', -5, 'approved', 'delivered', $budi, 'Repeat order edisi Extrait', [
                ['0001', 18, 'JKT-A01'], ['0005', 10, 'JKT-A02'], ['0018', 25, 'JKT-A03'],
            ]],
            ['BK-2026-0006', 'GDG-SBY', 'CUS-005', -3, 'pending', null, $intan, 'Order WA reseller - verifikasi pembayaran', [
                ['0010', 12, 'SBY-A02'],
            ]],
        ];

        foreach ($keluarDefs as [$ref, $kodeGdg, $cusKode, $dayOffset, $status, $deliveredStatus, $creator, $ket, $items]) {
            $keluar = BarangKeluar::updateOrCreate(['no_referensi' => $ref], [
                'nomor_surat_jalan' => $status !== 'pending' ? 'DO-'.$kodeGdg.'-'.str_replace('BK-', '', $ref) : null,
                'gudang_id' => $gdgIds[$kodeGdg], 'customer_id' => $customers[$cusKode]->id,
                'tanggal' => Carbon::now()->addDays($dayOffset)->toDateString(),
                'keterangan' => $ket, 'status' => $deliveredStatus ?? $status,
                'created_by' => $creator->id,
                'approved_by' => $status !== 'pending' ? $admin->id : null,
                'approved_at' => $status !== 'pending' ? Carbon::now()->addDays($dayOffset)->addHours(3) : null,
                'delivered_by' => $deliveredStatus === 'delivered' ? $creator->id : null,
                'delivered_at' => $deliveredStatus === 'delivered' ? Carbon::now()->addDays($dayOffset + 1)->setTime(10, 0) : null,
            ]);

            // Update status to 'approved' if delivered status is null but approval exists
            if ($deliveredStatus === null && $status !== 'pending') {
                $keluar->update(['status' => 'approved']);
            }

            foreach ($items as [$sku, $qty, $rakKode]) {
                $barang = $barangs[$sku];
                BarangKeluarDetail::updateOrCreate(
                    ['barang_keluar_id' => $keluar->id, 'barang_id' => $barang->id],
                    [
                        'lokasi_rak_id' => $raks[$rakKode]->id, 'qty' => $qty,
                        'harga_satuan' => $barang->harga_jual, 'diskon' => 0, 'pajak' => 0,
                        'subtotal' => $qty * $barang->harga_jual,
                        'created_at' => $keluar->created_at,
                    ]
                );

                if ($status !== 'pending') {
                    $this->pushEvent(Carbon::now()->addDays($dayOffset)->addHours(3)->addMinutes(30), $barang->id, $gdgIds[$kodeGdg], $raks[$rakKode]->id, 'out', $qty, BarangKeluar::class, $keluar->id, "Pengeluaran {$ref} untuk {$customers[$cusKode]->nama}", $creator->id);
                }
            }
        }

        // ==========================================================
        // 12. MUTASI STOK antar gudang
        // ==========================================================
        $mutasiDefs = [
            ['MS-2026-0001', '0003', 'GDG-JKT', 'GDG-BDG', 'JKT-A02', 'BDG-A02', -10, 30, 'completed', $siti, 'Transfer alokasi Citrus Breeze ke Bandung'],
            ['MS-2026-0002', '0010', 'GDG-SBY', 'GDG-JKT', 'SBY-A02', 'JKT-B01', -5, 36, 'approved', $intan, 'Vanilla Sky habis di pusat, ambil dari SBY'],
            ['MS-2026-0003', '0017', 'GDG-SBY', 'GDG-BDG', 'SBY-A01', 'BDG-A01', -1, 12, 'pending', $rudi, 'Permintaan cabang Bandung, tunggu approval'],
        ];
        foreach ($mutasiDefs as [$ref, $sku, $asal, $tujuan, $rakAsal, $rakTujuan, $dayOffset, $qty, $status, $creator, $ket]) {
            $mutasi = MutasiStok::updateOrCreate(['no_referensi' => $ref], [
                'barang_id' => $barangs[$sku]->id,
                'gudang_asal_id' => $gdgIds[$asal], 'gudang_tujuan_id' => $gdgIds[$tujuan],
                'lokasi_rak_asal_id' => $raks[$rakAsal]->id, 'lokasi_rak_tujuan_id' => $raks[$rakTujuan]->id,
                'qty' => $qty, 'tanggal' => Carbon::now()->addDays($dayOffset)->toDateString(),
                'keterangan' => $ket, 'status' => $status, 'created_by' => $creator->id,
                'approved_by' => in_array($status, ['approved', 'completed']) ? $admin->id : null,
                'approved_at' => in_array($status, ['approved', 'completed']) ? Carbon::now()->addDays($dayOffset)->addHours(2) : null,
            ]);
            $at = Carbon::now()->addDays($dayOffset)->addHours(4);
            if ($status === 'completed') {
                $this->pushEvent($at, $barangs[$sku]->id, $gdgIds[$asal], $raks[$rakAsal]->id, 'mutasi_out', $qty, MutasiStok::class, $mutasi->id, "{$ref} keluar ke {$tujuan}", $creator->id);
                $this->pushEvent($at->copy()->addHours(20), $barangs[$sku]->id, $gdgIds[$tujuan], $raks[$rakTujuan]->id, 'mutasi_in', $qty, MutasiStok::class, $mutasi->id, "{$ref} diterima dari {$asal}", $creator->id);
            } elseif ($status === 'approved') {
                $this->pushEvent($at, $barangs[$sku]->id, $gdgIds[$asal], $raks[$rakAsal]->id, 'mutasi_out', $qty, MutasiStok::class, $mutasi->id, "{$ref} keluar ke {$tujuan}", $creator->id);
            }
        }

        // ==========================================================
        // 13. STOK OPNAME
        // ==========================================================
        $opname = StokOpname::updateOrCreate(['no_referensi' => 'SO-2026-0801'], [
            'gudang_id' => $gudangJkt->id, 'tanggal' => Carbon::now()->subDays(7)->toDateString(),
            'keterangan' => 'Opname rutin akhir periode Juli - zona A & B', 'status' => 'completed',
            'created_by' => $siti->id, 'approved_by' => $superAdmin->id,
            'approved_at' => Carbon::now()->subDays(6)->setTime(17, 0),
        ]);
        foreach ([['0002', 'JKT-A01', 64, 62, '2 botol pecah saat pindahan rak'], ['0014', 'JKT-B01', 300, 301, 'Ada plus dari retur belum dicatat']] as [$sku, $rakKode, $sistem, $fisik, $ket]) {
            StokOpnameDetail::updateOrCreate(
                ['stok_opname_id' => $opname->id, 'barang_id' => $barangs[$sku]->id],
                [
                    'lokasi_rak_id' => $raks[$rakKode]->id, 'stok_sistem' => $sistem,
                    'stok_fisik' => $fisik, 'selisih' => $fisik - $sistem, 'keterangan' => $ket,
                    'created_at' => $opname->created_at,
                ]
            );
            $this->pushEvent(Carbon::now()->subDays(6)->setTime(17, 30), $barangs[$sku]->id, $gudangJkt->id, $raks[$rakKode]->id, 'opname', $fisik - $sistem, StokOpname::class, $opname->id, "Penyesuaian hasil opname SO-2026-0801: {$ket}", $superAdmin->id);
        }

        StokOpname::updateOrCreate(['no_referensi' => 'SO-2026-0802'], [
            'gudang_id' => $gudangSby->id, 'tanggal' => Carbon::now()->subDay()->toDateString(),
            'keterangan' => 'Opname siklus Agustus zona A - sedang berjalan', 'status' => 'in_progress',
            'created_by' => $intan->id,
        ]);

        // ==========================================================
        // 14. PROSES KARTU STOK (kronologis, saldo konsisten)
        // ==========================================================
        usort($this->events, fn ($a, $b) => $a['at'] <=> $b['at']);
        foreach ($this->events as $ev) {
            $key = $ev['barang_id'].'|'.$ev['gudang_id'];
            $sebelum = $this->saldo[$key] ?? 0.0;
            $delta = in_array($ev['tipe'], ['out', 'mutasi_out']) ? -$ev['qty'] : $ev['qty'];
            $sesudah = $sebelum + $delta;
            $this->saldo[$key] = $sesudah;

            KartuStok::create([
                'barang_id' => $ev['barang_id'], 'gudang_id' => $ev['gudang_id'],
                'lokasi_rak_id' => $ev['rak_id'], 'tipe' => $ev['tipe'],
                'qty' => $delta, 'saldo_sebelum' => $sebelum, 'saldo_sesudah' => $sesudah,
                'referensi_type' => $ev['ref_type'], 'referensi_id' => $ev['ref_id'],
                'keterangan' => $ev['ket'], 'created_by' => $ev['by'],
                'created_at' => $ev['at'],
            ]);
        }

        // ==========================================================
        // 15. BATCH BARANG (lot produksi/expiry) sesuai saldo akhir
        // ==========================================================
        $totalSaldo = [];
        foreach ($this->saldo as $key => $val) {
            [, $barangId] = explode('|', $key);
            $totalSaldo[$barangId] = ($totalSaldo[$barangId] ?? 0) + $val;
        }
        foreach ($totalSaldo as $barangId => $total) {
            if ($total <= 0) {
                continue;
            }
            $lotA = (float) floor($total * 0.6);
            $lotB = $total - $lotA;
            BatchBarang::updateOrCreate(
                ['barang_id' => $barangId, 'batch_number' => 'LOT-2605-A'],
                ['expired_date' => Carbon::now()->addMonths(30)->toDateString(), 'qty' => $lotA]
            );
            if ($lotB > 0) {
                BatchBarang::updateOrCreate(
                    ['barang_id' => $barangId, 'batch_number' => 'LOT-2607-B'],
                    ['expired_date' => Carbon::now()->addMonths(34)->toDateString(), 'qty' => $lotB]
                );
            }
        }

        // ==========================================================
        // 16. HISTORY HARGA (naik harga 2 minggu lalu)
        // ==========================================================
        foreach ($barangs as $barang) {
            HistoryHarga::firstOrCreate(
                ['barang_id' => $barang->id, 'tanggal_efektif' => Carbon::now()->subDays(90)->toDateString()],
                [
                    'harga_beli' => floor($barang->harga_beli * 0.92),
                    'harga_jual' => floor($barang->harga_jual * 0.94), 'created_by' => $admin->id,
                ]
            );
            HistoryHarga::firstOrCreate(
                ['barang_id' => $barang->id, 'tanggal_efektif' => Carbon::now()->subDays(14)->toDateString()],
                ['harga_beli' => $barang->harga_beli, 'harga_jual' => $barang->harga_jual, 'created_by' => $admin->id]
            );
        }

        // ==========================================================
        // 17. JADWAL PETUGAS + ABSENSI (10 hari terakhir)
        // ==========================================================
        $izinDewiMulai = Carbon::now()->subDays(3);

        foreach ($staff as $info) {
            $userId = $info['user']->id;
            $petugas = Petugas::where('user_id', $userId)->first();
            $userGudangId = $info['user']->gudang_id;
            $shiftUser = $info['shift'];

            for ($d = 10; $d >= 0; $d--) {
                $tanggal = Carbon::now()->subDays($d)->toDateString();

                $jadwal = JadwalPetugas::firstOrCreate(
                    ['user_id' => $userId, 'tanggal' => $tanggal],
                    ['shift_id' => $shiftUser->id, 'created_by' => $admin->id]
                );

                if ($d > 0) {
                    $isIzinDewi = $petugas && $petugas->kode === 'PG-004'
                        && Carbon::parse($tanggal)->betweenIncluded($izinDewiMulai->copy(), $izinDewiMulai->copy()->addDay());

                    if ($isIzinDewi) {
                        Absensi::firstOrCreate(
                            ['user_id' => $userId, 'tanggal' => $tanggal],
                            [
                                'petugas_id' => $petugas?->id, 'gudang_id' => $userGudangId,
                                'shift_id' => $jadwal->shift_id, 'status' => 'izin',
                                'sumber' => 'pengajuan', 'di_luar_jadwal' => false,
                                'keterangan' => 'Sakit, ada surat dokter - lihat pengajuan izin terkait',
                                'approved_by' => $admin->id, 'approved_at' => Carbon::parse($tanggal)->setTime(8, 5),
                            ]
                        );
                        continue;
                    }

                    $terlambat = ($petugas?->kode === 'PG-002' && $d === 4) || ($petugas?->kode === 'PG-005' && $d === 6);
                    $menitTelat = $terlambat ? rand(20, 32) : rand(-9, 12);
                    $jamMasuk = Carbon::parse($jadwal->shift->jam_masuk)->copy()->addMinutes($menitTelat);
                    $jamPulang = Carbon::parse($jadwal->shift->jam_pulang)->copy()->subMinutes(rand(0, 8));

                    Absensi::firstOrCreate(
                        ['user_id' => $userId, 'tanggal' => $tanggal],
                        [
                            'petugas_id' => $petugas?->id, 'gudang_id' => $userGudangId,
                            'shift_id' => $jadwal->shift_id,
                            'jam_masuk' => $jamMasuk->format('H:i:s'), 'jam_pulang' => $jamPulang->format('H:i:s'),
                            'status' => $terlambat ? 'terlambat' : 'hadir',
                            'radius_validasi' => 50, 'sumber' => 'qr', 'di_luar_jadwal' => false,
                        ]
                    );
                } else {
                    // Hari ini: shift pagi sudah check-in (belum pulang), shift siang belum mulai
                    if ($shiftUser->id === $shiftPagi->id) {
                        Absensi::firstOrCreate(
                            ['user_id' => $userId, 'tanggal' => $tanggal],
                            [
                                'petugas_id' => $petugas?->id, 'gudang_id' => $userGudangId,
                                'shift_id' => $shiftPagi->id,
                                'jam_masuk' => Carbon::parse($shiftPagi->jam_masuk)->addMinutes(rand(-5, 10))->format('H:i:s'),
                                'status' => 'hadir', 'radius_validasi' => 50,
                                'sumber' => 'qr', 'di_luar_jadwal' => false,
                            ]
                        );
                    }
                }
            }
        }

        // ==========================================================
        // 18. IZIN / CUTI
        // ==========================================================
        IzinRequest::updateOrCreate([
            'user_id' => $dewi->id, 'tanggal_mulai' => $izinDewiMulai->toDateString(),
        ], [
            'jenis' => 'sakit', 'tanggal_selesai' => $izinDewiMulai->copy()->addDay()->toDateString(),
            'alasan' => 'Demam dan flu, ada surat resep dokter Klinik Kelapa Dua terlampir',
            'status' => 'disetujui', 'approved_by' => $admin->id,
            'approved_at' => $izinDewiMulai->copy()->setTime(8, 5),
        ]);

        IzinRequest::updateOrCreate([
            'user_id' => $intan->id, 'tanggal_mulai' => Carbon::now()->addDay()->toDateString(),
        ], [
            'jenis' => 'izin', 'tanggal_selesai' => Carbon::now()->addDays(2)->toDateString(),
            'alasan' => 'Menghadiri pernikahan saudara di Malang, back-to-back dengan checker cadangan',
            'status' => 'menunggu',
        ]);

        IzinRequest::updateOrCreate([
            'user_id' => $agus->id, 'tanggal_mulai' => Carbon::now()->subDays(45)->toDateString(),
        ], [
            'jenis' => 'cuti', 'tanggal_selesai' => Carbon::now()->subDays(43)->toDateString(),
            'alasan' => 'Cuti tahunan ke Lombok',
            'status' => 'ditolak', 'approved_by' => $admin->id,
            'approved_at' => Carbon::now()->subDays(46)->setTime(16, 20),
            'catatan_penolakan' => 'Ditolak: bertepatan audit gudang tahunan dan stock opname wajib kepala gudang hadir',
        ]);

        // ==========================================================
        // 19. NOTIFIKASI
        // ==========================================================
        Notifikasi::updateOrCreate(['user_id' => $admin->id, 'judul' => 'Penerimaan menunggu persetujuan'], [
            'pesan' => 'BM-2026-0007 (attar dari PT Attar Al Arabia Indo) menunggu verifikasi fisik Anda.',
            'tipe' => 'warning', 'priority' => 'high', 'link' => '/barang-masuk',
        ]);
        Notifikasi::updateOrCreate(['user_id' => $admin->id, 'judul' => 'Stok mendekati batas minimum'], [
            'pesan' => 'EDT Sakura Bloom 75ml di Gudang Surabaya tersisa 60 botol (min. 18, aman tapi tren naik).',
            'tipe' => 'info', 'priority' => 'medium', 'link' => '/barang', 'is_read' => true, 'read_at' => Carbon::now()->subDays(2),
        ]);
        Notifikasi::updateOrCreate(['user_id' => $intan->id, 'judul' => 'Pengajuan izin diterima'], [
            'pesan' => 'Pengajuan izin Anda untuk besok telah diterima sistem dan menunggu persetujuan admin.',
            'tipe' => 'success', 'priority' => 'low', 'link' => '/absensi/cuti-izin',
        ]);
        Notifikasi::updateOrCreate(['user_id' => $dewi->id, 'judul' => 'Get well soon!'], [
            'pesan' => 'Izin sakit Anda (2 hari) disetujui oleh Admin. Segera unggah surat dokter bila ada perpanjangan.',
            'tipe' => 'success', 'priority' => 'medium', 'link' => '/absensi/cuti-izin', 'is_read' => true, 'read_at' => Carbon::now()->subDays(2),
        ]);

        // ==========================================================
        // 20. AKTIVITAS LOG
        // ==========================================================
        $logs = [
            [$budi->id, 0, '/api/barang-masuk', 'POST', 'store', 'BarangMasuk', 'Membuat penerimaan BM-2026-0007', ['no_referensi' => 'BM-2026-0007', 'status' => 'pending']],
            [$admin->id, -1, '/api/barang-masuk/BM-2026-0006/approve', 'POST', 'approve', 'BarangMasuk', 'Approve BM-2026-0006', ['no_referensi' => 'BM-2026-0006', 'status' => 'approved', 'total_nilai' => 28700000]],
            [$siti->id, -1, '/api/login', 'POST', 'login', 'Auth', 'Login berhasil dari jaringan kantor Jakarta', []],
            [$rudi->id, -2, '/api/barang-keluar', 'POST', 'store', 'BarangKeluar', 'Membuat pesanan BK-2026-0006 reseller Sidoarjo', ['no_referensi' => 'BK-2026-0006', 'status' => 'pending', 'total_nilai' => 5400000]],
            [$admin->id, -2, '/api/izin/3/reject', 'POST', 'reject', 'IzinRequest', 'Menolak cuti Agus (audit gudang)', ['status' => 'ditolak']],
            [$budi->id, 0, '/api/barang-masuk/BM-2026-0006/approve', 'POST', 'approve', 'BarangMasuk', 'Approve penerimaan container kedua', ['no_referensi' => 'BM-2026-0006', 'status' => 'approved', 'total_nilai' => 42500000]],
            [$agus->id, 0, '/api/barang-keluar/BK-2026-0003/approve', 'POST', 'approve', 'BarangKeluar', 'Approve pengiriman ke CV Sinar Parfum', ['no_referensi' => 'BK-2026-0003', 'status' => 'approved', 'total_nilai' => 18200000]],
            [$rudi->id, 0, '/api/absensi', 'POST', 'scan', 'Absensi', 'Check-in shift pagi Gudang Surabaya', ['status' => 'hadir']],
            [$siti->id, 0, '/api/stok-opname', 'POST', 'store', 'StokOpname', 'Mulai opname siklus Agustus zona A', ['no_referensi' => 'SO-2026-0802', 'status' => 'in_progress']],
            [$budi->id, -1, '/api/mutasi-stok/MS-2026-0001/complete', 'POST', 'complete', 'MutasiStok', 'Selesaikan transfer Citrus Breeze ke Bandung', ['no_referensi' => 'MS-2026-0001', 'status' => 'completed']],
            [$intan->id, 0, '/api/barang-keluar', 'POST', 'store', 'BarangKeluar', 'Buat pesanan Vanilla Sky untuk reseller', ['no_referensi' => 'BK-2026-0006', 'status' => 'pending', 'total_nilai' => 3600000]],
            [$admin->id, -1, '/api/barang-masuk/BM-2026-0007/approve', 'POST', 'approve', 'BarangMasuk', 'Approve penerimaan attar Ramadan', ['no_referensi' => 'BM-2026-0007', 'status' => 'approved', 'total_nilai' => 14700000]],
            [$agus->id, -1, '/api/barang-keluar/BK-2026-0003/deliver', 'POST', 'deliver', 'BarangKeluar', 'Serahkan ke kurir kargo Bandung', ['no_referensi' => 'BK-2026-0003', 'status' => 'delivered']],
            [$dewi->id, -3, '/api/izin', 'POST', 'store', 'IzinRequest', 'Pengajuan izin sakit 2 hari', ['status' => 'menunggu']],
            [$admin->id, -3, '/api/izin/2/approve', 'POST', 'approve', 'IzinRequest', 'Setujui izin sakit Dewi', ['status' => 'disetujui']],
        ];
        foreach ($logs as [$uid, $offset, $url, $method, $action, $model, $ket, $extra]) {
            AktivitasLog::create([
                'user_id' => $uid, 'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) warehouse-app/1.0',
                'url' => $url, 'method' => $method, 'action' => $action,
                'model' => $model, 'data_new' => array_merge(['catatan' => $ket], $extra),
                'created_at' => Carbon::now()->addDays($offset)->setTime(rand(7, 17), rand(0, 59)),
            ]);
        }

        $this->command?->info('GudangParfumSeeder selesai: 3 gudang, '.count($barangs).' barang, '.count($staff).' staff, kartu stok kronologis lengkap.');
    }

    /**
     * Daftarkan event pergerakan stok; akan diurutkan kronologis lalu
     * diproses menjadi kartu_stok dengan saldo berjalan.
     */
    private function pushEvent(Carbon $at, int $barangId, int $gudangId, ?int $rakId, string $tipe, float $qty, string $refType, int $refId, string $ket, int $by): void
    {
        $this->events[] = [
            'at' => $at, 'barang_id' => $barangId, 'gudang_id' => $gudangId,
            'rak_id' => $rakId, 'tipe' => $tipe, 'qty' => $qty,
            'ref_type' => $refType, 'ref_id' => $refId, 'ket' => $ket, 'by' => $by,
        ];
    }
}
