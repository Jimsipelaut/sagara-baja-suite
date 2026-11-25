# PROGRESS

Terakhir diperbarui: 2026-11-25
Fase saat ini: **Sprint 3 selesai (Purchasing) — Sprint 4 berikutnya**

Legenda: `[x]` selesai, `[~]` sedang dikerjakan, `[ ]` belum mulai, `[!]` terblokir

---

## 1. Ringkasan Status

| Area | Status |
|---|---|
| SRS v0.1 | Selesai (draft, menunggu jawaban klien) |
| Desain arsitektur gabungan | Selesai (usulan) |
| Kode | Sprint 0 (Core), Sprint 1 (Inventory), Sprint 2 (Sales/POS) & Sprint 3 (Purchasing) Selesai |
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
- [x] PO dan penerimaan barang
- [x] Hutang usaha dasar tercatat (grand_total − paid_amount; paid_amount tetap 0)
- [ ] Pembayaran hutang usaha (pindah ke Sprint 4 — Accounting)
- [ ] Piutang (umur piutang)

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
| 2026-11-25 | Sprint 3: PO tidak mengubah stok; stok hanya bertambah saat penerimaan barang via StockService | Mencegah stok tercatat sebelum barang benar-benar diterima; satu sumber kebenaran stok tetap kartu stok |
| 2026-11-25 | Sequence 'purchase_order' (PO-SB/) sudah ada sejak Migration_001 — dipakai langsung; sequence baru 'goods_receipt' (GR-SB/) di-seed di Migration_004 | Nomor PO dan nomor tanda terima tidak boleh berbagi urutan (SOUL 4.4) |
| 2026-11-25 | Hutang usaha = grand_total − paid_amount, paid_amount tetap 0 di Sprint 3; TIDAK ada pembayaran hutang di Sprint 3 | Pembayaran hutang butuh jurnal/kas — itu Sprint 4 (Accounting). PO hanya mencatat hutang saat barang diterima |
| 2026-11-25 | Kolom `cancel_reason` ditambahkan ke sb_purchase_orders (tidak ada di spek awal) | Alasan batal wajib diisi (SOUL: koreksi beralasan); tanpa kolom ini alasan tidak punya tempat tersimpan |
| 2026-11-25 | Admin UI Purchasing TIDAK dibuat di Sprint 3 | SalesAdmin/InventoryAdmin besar dan butuh nonce + capability per aksi; UI setengah jadi dilarang SOUL 3. Service + migrasi + tes adalah deliverable; UI menyusul saat dibutuhkan |
| 2026-11-25 | Migrator menemukan migrasi lewat glob `core/Database/Migrations/*.php` (urut nama file) — tidak ada daftar migrasi yang diedit | Migration_004 terdaftar otomatis karena namanya urut setelah Migration_003 |

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
| Transaksi bersarang (NumberingService & StockService masing-masing COMMIT sendiri) | Di MySQL nyata, COMMIT di dalam service yang dipanggil dari transaksi luar bersifat progresif — gagal di akhir penerimaan tidak membatalkan mutasi stok awal | Seluruh validasi (status, over-receipt, duplikat baris) dilakukan SEBELUM mutasi pertama; penerimaan juga mengunci baris PO (FOR UPDATE) dan memvalidasi ulang di dalam transaksi. Pola ini diwarisi dari SaleService::publish yang sudah ada — perbaikan menyeluruh (transaksi bersarang yang benar) butuh refactor StockService/NumberingService, di luar Sprint 3 |
| assert() mati di php.ini production | `php tests/run.php` tampil "SEMUA LULUS" padahal assert() tidak dieksekusi sama sekali (zend.assertions=-1 di VM ini) | tests/run.php kini BERHENTI (exit 2) bila assert() tidak aktif dan memberi tahu perintah yang benar: `php -d zend.assertions=1 -d assert.exception=1 tests/run.php`. Risiko ini berlaku juga untuk suite lama, bukan hanya Sprint 3 |

## 6. Changelog

### 0.4.0-purchasing (2026-11-25)
- Migrasi database `Migration_004_Purchasing` membuat 4 tabel: `sb_purchase_orders`, `sb_purchase_items`, `sb_goods_receipts`, `sb_goods_receipt_items`; men-seed sequence `goods_receipt` (GR-SB/). Sequence `purchase_order` (PO-SB/) sudah ada sejak Migration_001.
- `PurchaseOrderService`: buat draft PO (total dihitung server, pajak 0 non-PKP), setujui (draft → disetujui), batalkan (draft/disetujui saja, alasan wajib, tidak bisa setelah ada penerimaan), `find`/`getItems`/`getReceipts`, `getPayableAmount` (= grand_total − paid_amount). PO tidak mengubah stok.
- `GoodsReceiptService`: terima barang hanya untuk PO disetujui/diterima_sebagian; tolak over-receipt (kumulatif ≤ qty_ordered), duplikat baris, dan qty ≤ 0; stok bertambah tepat satu kali per tanda terima lewat `StockService->add_stock` (ref_type `goods_receipt`); status PO menjadi `diterima_sebagian` atau `diterima` bila semua baris penuh; baris PO dikunci `FOR UPDATE` dan divalidasi ulang di dalam transaksi.
- Hutang usaha tercatat saat penerimaan (grand_total − paid_amount, paid_amount tetap 0). Pembayaran hutang TIDAK dibangun — Sprint 4.
- Admin UI Purchasing tidak dibangun (dicatat sebagai belum dikerjakan, bukan setengah jadi).
- Unit Test suite Purchasing lengkap (24 test: 14 PurchaseOrder + 10 GoodsReceipt) lulus 100% bersama seluruh suite lama, dijalankan dengan assert() aktif: `php -d zend.assertions=1 -d assert.exception=1 tests/run.php` (exit 0).
- Perbaikan harness: `tests/run.php` kini menolak jalan bila `zend.assertions` tidak aktif (sebelumnya `php tests/run.php` di VM ini tampil lulus padahal assert() tidak dieksekusi — zend.assertions=-1). Mock `MockWPDB` diperluas: tabel + sequence Purchasing dan handler `get_results` untuk baris PO, tanda terima, dan baris tanda terima.

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
