# PROGRESS

Terakhir diperbarui: 2026-11-24
Fase saat ini: **Perencanaan / SRS**

Legenda: `[x]` selesai, `[~]` sedang dikerjakan, `[ ]` belum mulai, `[!]` terblokir

---

## 1. Ringkasan Status

| Area | Status |
|---|---|
| SRS v0.1 | Selesai (draft, menunggu jawaban klien) |
| Desain arsitektur gabungan | Selesai (usulan) |
| Kode | Sprint 0 (Core) & Sprint 1 (Inventory) Selesai |
| Klien: jawaban pertanyaan terbuka | Menunggu |

## 2. Rencana Sprint

### Sprint 0 - Core
- [x] Bootstrap plugin dan sistem migrasi tabel
- [x] Peran dan capability (Owner, Admin, Kasir, Gudang, Keuangan, Karyawan)
- [x] Audit log (append-only)
- [x] Penomoran dokumen (resmi, non-resmi, PO, dll.) tanpa celah dan aman dari race condition
- [x] Halaman pengaturan (profil toko, zona waktu, mata uang)

### Sprint 1 - Inventory dan master data
- [x] Barang, kategori, satuan dan konversi
- [x] Harga bertingkat (eceran, grosir, proyek)
- [x] Gudang dan kartu stok (sb_stock_moves)
- [x] Impor Excel/CSV dengan validasi
- [x] Stock opname dengan persetujuan

### Sprint 2 - Sales, POS, Invoice
- [x] Pelanggan dan supplier
- [x] Penjualan dan pembatalan (void) dengan pembalikan mutasi stok otomatis
- [x] Layar kasir (POS) Admin & Frontend Shortcode `[sb_pos]`, shift kasir buka/tutup, modal awal & akhir
- [x] Halaman utama toko (`/pos/` & root `/`) otomatis terhubung ke antarmuka terminal POS Kasir responsif
- [x] Invoice resmi dan nota non-resmi, cetak thermal struk & PDF browser-ready (Rp0)
- [x] Pembayaran tunai/transfer/tempo, alokasi pembayaran, dan tracking status lunas/sebagian

### Sprint 3 - Pembelian, hutang, piutang
- [ ] PO dan penerimaan barang
- [ ] Hutang usaha dan piutang (umur piutang)

### Sprint 4 - Pembukuan
- [ ] Bagan akun dan jurnal otomatis
- [ ] Kas dan bank, biaya operasional
- [ ] Penutupan periode
- [ ] Laporan: laba rugi, neraca, arus kas, buku besar

### Sprint 5 - Karyawan dan absensi
- [ ] Data karyawan, dokumen, riwayat
- [ ] Absen HP/QR, shift, izin, cuti
- [ ] Rekap absensi bulanan

### Sprint 6 - Lembur dan penggajian
- [ ] Pengajuan dan persetujuan lembur
- [ ] Komponen gaji, potongan, kasbon
- [ ] Proses gaji (pratinjau, kunci), slip gaji PDF
- [ ] Jurnal penggajian

### Sprint 7 - UAT dan go-live
- [ ] Uji penerimaan dengan data nyata
- [ ] Pelatihan pengguna
- [ ] Paralel run dengan cara lama
- [ ] Go-live dan masa garansi

## 3. Log Keputusan

| Tanggal | Keputusan | Alasan |
|---|---|---|
| 2026-11-24 | Plugin kustom modular, tabel sendiri, tanpa WooCommerce | Toko fisik, perlu invoice ganda dan penggajian yang tidak ada di plugin siap pakai |
| 2026-11-24 | Referensi GitHub hanya untuk konsep, kode tidak disalin | Hindari masalah lisensi dan utang teknis |
| 2026-11-24 | Modul kasir masuk Fase 1 | Kasir adalah pintu transaksi harian |
| 2026-11-24 | Modul LMS tidak dimasukkan | Di luar kebutuhan |
| 2026-11-24 | Invoice non-resmi tetap tercatat di sistem | Mencegah penjualan nyata keluar dari pembukuan |
| 2026-11-24 | Core arsitektur Sprint 0 selesai dengan unit test mandiri | Memastikan audit log, sequence number berurutan/lock-safe, hak akses, dan pengaturan toko lolos pengujian tanpa dependensi sebelum modul inventory |
| 2026-11-24 | WordPress local runtime ditautkan via junction di Laragon (`C:\laragon\www\sagara-baja`) | Memungkinkan testing runtime WordPress nyata tanpa mencemari repo plugin dengan source core WordPress |
| 2026-11-24 | Data dummy material bangunan toko besi & baja (8 SKU representatif) | Permintaan owner menggunakan dummy data dulu untuk kategori material besi, baja ringan, semen, pasir, spandek, triplek |
| 2026-11-24 | Harga bertingkat (eceran, grosir, proyek) dan modal disimpan integer murni | Memenuhi aturan SOUL.md: tidak menggunakan float untuk nilai moneter |
| 2026-11-24 | Row lock `FOR UPDATE` dan penolakan stok minus default pada mutasi stok | Memastikan integritas saldo fisik vs kartu stok (`sb_stock_moves`) dan mencegah race condition saat multi-kasir |
| 2026-11-25 | Status PKP toko diset non-PKP saat ini (PPN 0 Rupiah di skema) | Menyesuaikan status riil toko dengan tetap menyediakan field `tax_amount` di database agar siap saat beralih PKP tanpa migrasi |
| 2026-11-25 | Format dokumen cetak (Invoice & Nota) menggunakan HTML/CSS Print Responsive murni (Rp0) | Menghindari dependensi library binary eksternal seperti dompdf/mpdf/wkhtmltopdf demi performa ringan dan ketaatan prinsip Rp0 |
| 2026-11-25 | Pengurangan stok dipicu saat penjualan berubah dari draft ke 'terbit' | Mencegah hold / keranjang belanja mengunci stok fisik yang belum disepakati pembeli |

## 4. Pertanyaan Terbuka (menunggu klien)

- [x] Status PKP dan perlakuan PPN? → **Bukan PKP untuk sekarang. Kolom tax_amount disiapkan di skema tapi diisi 0. Dapat diaktifkan nanti tanpa migrasi ulang.**
- [x] Definisi pasti invoice non-resmi dan siapa pemakainya? → **Keduanya: struk kasir tunai tanpa nama pelanggan, dan nota ke pelanggan langganan yang tidak minta invoice resmi.**
- [x] Perlu mode offline di kasir? → **Tidak. Kasir selalu online.**
- [ ] Jumlah SKU, karyawan, transaksi per hari, gudang?
- [ ] Metode stok: rata-rata atau FIFO?
- [ ] Skema gaji (bulanan, mingguan, harian), aturan lembur, BPJS, PPh 21?
- [ ] Cara absensi: HP, QR, atau mesin fingerprint?
- [ ] Jumlah kasir dan perangkat (printer thermal, scanner)?
- [ ] Hosting dan anggaran, target go-live?

## 5. Risiko

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Requirement invoice non-resmi ambigu | Desain salah, risiko hukum | Klarifikasi dengan Owner dan akuntan sebelum Sprint 2 |
| Stok tidak konsisten (transaksi bersamaan) | Stok minus, laporan salah | Database transaction, row lock, tes konkurensi |
| Salah hitung uang dan gaji | Kerugian finansial | Decimal/integer sen, tes tabel kasus, review ganda |
| Data gaji bocor | Pelanggaran privasi | Capability ketat, tanpa endpoint publik, uji akses |
| Scope creep | Jadwal molor | Perubahan lewat log keputusan dan persetujuan Owner |
| Hosting lemah | Kasir lambat | Spesifikasi minimum, cache, uji beban 10.000 SKU |

## 6. Changelog

### 0.3.0-sales (2026-11-25)
- Migrasi database `Migration_003_Sales` membuat 6 tabel: `sb_customers`, `sb_suppliers`, `sb_cashier_shifts`, `sb_sales`, `sb_sale_items`, `sb_payments`.
- `CustomerRepository`: CRUD master pelanggan, limit kredit, validasi kode unik, dan pelacakan saldo piutang.
- `SupplierRepository`: CRUD master supplier material bangunan dengan kontak dan syarat pembayaran.
- `ShiftService`: Alur shift kasir POS dari buka shift dengan modal awal tunai, pencatatan transaksi terikat shift, hingga tutup shift dengan rekap uang fisik.
- `SaleService`: Mesin transaksi penjualan dari draft keranjang, penerbitan invoice dengan mutasi stok atomik `deduct_stock` dan rekonsiliasi kartu stok seimbang, multi-metode pembayaran (tunai, transfer, tempo) dengan update otomatis status `lunas`/`sebagian`, dan fungsi pembatalan (`void`) dengan pembalikan stok otomatis `add_stock`.
- `PdfInvoiceService`: Engine pencetakan dokumen nota non-resmi (kasir & langganan) dan invoice resmi A4 dengan format siap cetak printer thermal 80mm dan browser PDF (Rp0 eksternal library).
- `SalesAdmin`: Antarmuka admin WordPress terpadu untuk Terminal Kasir (POS), Riwayat Penjualan, Master Pelanggan, Master Supplier, dan Shift Kasir.
- Unit Test suite Sales lengkap (CustomerRepository, SupplierRepository, ShiftService, SaleService, PdfInvoiceService) lulus 100% (total 49 assertions di 14 suite pengujian).

### 0.2.0-inventory (2026-11-24)
- Migrasi database `Migration_002_Inventory` membuat 9 tabel: `sb_categories`, `sb_units`, `sb_warehouses`, `sb_products`, `sb_unit_conversions`, `sb_stock_balances`, `sb_stock_moves`, `sb_stock_opnames`, `sb_stock_opname_items`.
- `UnitConversionService`: Konversi multi-satuan material (batang, ikat, zak, ton, kg, m3, lembar) dengan pembuktian direct & inverse ratio serta validasi kuantitas positif.
- `ProductRepository`: CRUD master produk dengan SKU unik dan 3 tier harga integer (`sell_price_retail`, `sell_price_wholesale`, `sell_price_project`).
- `StockService`: Mutasi stok berbasis kartu stok transaksional (`sb_stock_moves`), saldo berjalan (`sb_stock_balances`), row lock `FOR UPDATE`, pencegahan stok minus default, transfer antar gudang, dan fungsi rekonsiliasi saldo vs mutasi.
- `StockOpnameService`: Alur opname lengkap dari draft, verifikasi fisik, approval, hingga posting penyesuaian otomatis ke kartu stok.
- `CsvImportService`: Fasilitas impor data produk batch via CSV dengan pembungkusan transaksi database.
- `DummyDataSeeder`: Seeder otomatis master data material bangunan (besi beton polos/ulir SNI, kanal C, reng baja ringan, semen 50kg, pasir pasang, spandek pasir, triplek cor) lengkap dengan saldo awal di gudang utama & toko.
- `InventoryAdmin`: Tampilan admin WordPress untuk Master Barang, Kartu Stok Mutasi, Stock Opname, Impor CSV, dan tombol inisialisasi Seeder dummy.
- Unit Test suite inventory lengkap (27 test cases pass 100%, lolos `php -l`).

### 0.2.0-core (2026-11-24)
- Entrypoint plugin `sagara-baja-suite.php` dibuat dengan verifikasi PHP 8.1 dan hooks.
- Autoloader PSR-4 untuk namespace `SagaraBaja\Core` dan `SagaraBaja\Modules`.
- Sistem migrasi tabel otomatis (`sb_schema_versions`) dengan migrasi awal `Migration_001_Initial_Core`.
- Pendaftaran 6 peran: `sb_owner`, `sb_admin`, `sb_cashier`, `sb_warehouse`, `sb_finance`, `sb_employee` beserta capabilities.
- Audit logger append-only (`sb_audit_logs`) dengan pelacakan pengguna, payload JSON, dan IP.
- Layanan penomoran dokumen transaksional berurutan bebas celah (`sb_document_sequences`) dengan row lock `FOR UPDATE`.
- Panel admin WordPress (Dashboard, Pengaturan Toko, dan Penampil Audit Log) dengan verifikasi nonce dan capability.
- Test suite mandiri 13 assertions lulus 100% dan lolos `php -l`.

### 0.1.0-planning (2026-11-24)
- SRS v0.1 dibuat (modul inventory, invoice, pembukuan, HR, absensi, lembur, gaji)
- Ditambahkan modul kasir (POS) dan integrasi antar modul
- Disusun desain gabungan dari referensi GitHub dan rencana sprint
- README, PROGRESS, dan SOUL dibuat

## 7. Aturan Memperbarui Berkas Ini

- Perbarui setiap selesai satu butir, bukan menunggu akhir sprint.
- Butir hanya boleh `[x]` jika kode, tes, dan dokumentasinya selesai (lihat Definition of Done di SOUL.md).
- Setiap keputusan arsitektur atau bisnis dicatat di Log Keputusan beserta alasannya.
