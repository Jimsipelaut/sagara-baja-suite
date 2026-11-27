# PROGRESS

Terakhir diperbarui: 2026-11-27
Fase saat ini: **Sprint 4 berjalan — AP payment + Admin UI Purchasing selesai, umur piutang selesai; bagan akun, jurnal, kas/bank, penutupan, laporan masih terbuka**

Legenda: `[x]` selesai, `[~]` sedang dikerjakan, `[ ]` belum mulai, `[!]` terblokir

---

## 1. Ringkasan Status

| Area | Status |
|---|---|
| SRS v0.1 | Selesai (draft, menunggu jawaban klien) |
| Desain arsitektur gabungan | Selesai (usulan) |
| Kode | Sprint 0 (Core), Sprint 1 (Inventory), Sprint 2 (Sales/POS), Sprint 3 (Purchasing) Selesai; Sprint 4 parsial (AP payment + Admin UI Purchasing selesai) |
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
- [x] Pembayaran hutang usaha (selesai di Sprint 4 — lihat Sprint 4)
- [x] Piutang (umur piutang) — laporan umur piutang (ReceivablesAgingService + 5 tes)

### Sprint 4 - Pembukuan
- [x] Pembayaran hutang usaha (AP payment): SupplierPaymentService + Migration_005 (tabel sb_supplier_payments, sequence BYR-SB/), 14 tes baru
- [x] Admin UI Purchasing: Daftar PO (filter status), Detail PO (baris barang, tanda terima, riwayat pembayaran, formulir catat pembayaran), Daftar Tanda Terima, Daftar Pembayaran Hutang
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
| 2026-11-27 | Sequence baru 'supplier_payment' prefix BYR-SB/ (Migration_005) — urutan sendiri, tidak berbagi dengan PO (PO-SB/) atau tanda terima (GR-SB/) | SOUL 4.4: penomoran per jenis dokumen |
| 2026-11-27 | PO TIDAK punya status 'lunas' — lunas diturunkan dari paid_amount == grand_total | State machine PO di Migration_004 tidak punya status lunas; menambah status baru akan mengubah mesin status Sprint 3. Status bayar (belum_bayar/sebagian/lunas) dihitung di service, bukan disimpan di PO |
| 2026-11-27 | Pembayaran hutang hanya metode tunai/transfer; pencatatan butuh capability sb_manage_purchasing (Admin/Owner) — peran Keuangan (sb_finance) hanya bisa melihat (sb_view_purchasing) | Sesuai lingkup slice + pemetaan peran yang sudah ada di Roles.php. Bila Owner ingin Keuangan bisa mencatat pembayaran, itu perubahan peran, bukan kode |
| 2026-11-27 | Slice AP payment TIDAK membuat jurnal dan TIDAK menggerakkan kas/bank — yang tercatat hanya baris sb_supplier_payments + paid_amount di PO | Bagan akun + jurnal + kas/bank adalah sisa Sprint 4. Pembayaran tercatat tapi saldo kas belum berkurang sampai jurnal dibuat — jangan anggap laporan kas sudah benar |
| 2026-11-27 | Migration_004 jalan ulang secara idempotent saat reaktivasi di runtime yang di-rebuild (schema_versions ter-reset oleh impor dump lama) — CREATE TABLE IF NOT EXISTS + seed-jika-belum-ada | Dump di repo diambil sebelum Sprint 3, jadi impor dump menimpa schema_versions dan document_sequences ke kondisi pra-Sprint 3; reaktivasi memperbaiki keduanya tanpa merusak data |

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
| Transaksi bersarang (NumberingService & StockService masing-masing COMMIT sendiri) — **DIPERBAIKI 27 Nov 2026 (C-01)** | Di MySQL nyata, COMMIT di dalam service yang dipanggil dari transaksi luar bersifat progresif — gagal di akhir penerimaan tidak membatalkan mutasi stok awal | Semua batas transaksi kini lewat `Core\Database\Transaction` (penghitung kedalaman per koneksi): COMMIT bersarang tidak lagi meng-commit transaksi luar; ROLLBACK dari kedalaman berapa pun membatalkan seluruh transaksi. Dibuktikan unit test `test_failed_receipt_rolls_back_stock_and_number` + verifikasi MySQL nyata di VM (penerimaan gagal di baris 2 → 0 mutasi stok, nomor GR tidak terpakai). Mock `MockWPDB` kini mensimulasikan snapshot/rollback agar tes regresi bermakna. Caveat: bila proses PHP mati fatal di tengah transaksi, kedalaman bisa bocor ke request berikutnya pada proses yang sama — terburuk sama dengan perilaku lama (auto-commit), bukan korupsi data |
| Batas idempotensi POS: retry checkout setelah error jaringan membuat dokumen penjualan baru | Kasir yang retry setelah timeout bisa mendapat 2 nomor invoice untuk 1 keranjang (pembayaran di dalam satu penjualan tetap idempoten) | Di luar C-13 (idempotensi pembayaran, bukan pembuatan dokumen). Dicatat sebagai batasan; perbaikan butuh kunci idempotensi di level dokumen penjualan, belum dirancang |
| Pembayaran hutang tercatat tanpa jurnal/kas | Saldo kas tidak berkurang saat hutang dibayar — laporan kas/arus kas akan salah sampai jurnal dibuat | Sisa Sprint 4 (bagan akun + jurnal + kas/bank) wajib dikerjakan sebelum laporan keuangan dipakai. Sementara ini pembayaran hanya jejak di sb_supplier_payments + audit log |
| assert() mati di php.ini production | `php tests/run.php` tampil "SEMUA LULUS" padahal assert() tidak dieksekusi sama sekali (zend.assertions=-1 di VM ini) | tests/run.php kini BERHENTI (exit 2) bila assert() tidak aktif dan memberi tahu perintah yang benar: `php -d zend.assertions=1 -d assert.exception=1 tests/run.php`. Risiko ini berlaku juga untuk suite lama, bukan hanya Sprint 3 |

## 6. Changelog

### 0.5.1-logic-defects (2026-11-27)
Paket perbaikan cacat logika C-05, C-12, C-13, C-01 (disetujui user 27 Nov 2026 14:31 WIB). Suite: **103/103 lulus** (dari 91), dijalankan dengan assert() aktif: `php -d zend.assertions=1 -d assert.exception=1 tests/run.php` (exit 0).
- **C-13 — Idempotensi pembayaran (klik ganda tidak menggandakan).** Migrasi baru `Migration_006_IdempotencyKeys` menambah kolom `idempotency_key VARCHAR(64)` + UNIQUE KEY ke `wp_sb_supplier_payments` dan `wp_sb_payments` (guarded: hanya bila belum ada, agar reaktivasi setelah import dump lama tetap jalan; NULL ganda diizinkan sehingga baris lama tanpa kunci tidak pernah bentrok). `SupplierPaymentService::recordPayment()` dan `SaleService::addPayment()` menerima `idempotency_key`; pengiriman ulang dengan kunci sama mengembalikan pembayaran yang sudah tercatat (nomor sama, tanpa baris/audit baru); kunci tidak valid (kosong → perilaku lama, >64 karakter, karakter di luar `[A-Za-z0-9_-]`) ditolak. UNIQUE KEY adalah pertahanan terakhir terhadap dua submit bersamaan. Formulir `PurchasingAdmin` membawa kunci tersembunyi (`wp_generate_uuid4()` per render); `SalesAdmin::handleAddPayment()` memeriksa dulu kunci yang sudah ada agar replay tidak menulis audit `payment_added` ganda; checkout `PosFrontend` membuat satu kunci per percobaan submit (`crypto.randomUUID()`). **Temuan saat verifikasi VM (diperbaiki, bukan sekadar dicatat):** pemeriksaan replay harus jalan SEBELUM pemeriksaan sisa hutang/status — replay pertama ditolak keliru sebagai overpayment setelah PO lunas (`Jumlah bayar melebihi sisa hutang`); urutan diperbaiki di kedua service + tes regresi `test_idempotent_replay_after_po_lunas`. Batasan: retry checkout setelah error jaringan masih membuat dokumen penjualan baru (di luar C-13; lihat Risiko).
- **C-05 — Audit eksplisit `po_approve` dan `goods_receipt`.** `PurchaseOrderService::approve()` kini mencatat audit `po_approve` (draft → disetujui); `GoodsReceiptService::receive()` mencatat audit `goods_receipt` (PO, nomor tanda terima, item, penerima). Tes: `test_approve_writes_audit_log`, `test_receipt_writes_audit_log`.
- **C-12 — Uji konkurensi nyata MySQL: LULUS, tidak ada cacat baru.** Dua sesi MySQL bersamaan membayar Rp300.000 atas satu PO Rp450.000 di runtime VM: satu berhasil (BYR-SB/2026/00001), satu ditolak overpayment; akhir: 1 pembayaran, total Rp300.000 ≤ Rp450.000 (row lock FOR UPDATE + validasi ulang di dalam transaksi terbukti menahan balapan). Skrip uji disimpan di `tests/vm/concurrency-test.php` agar bisa diulang.
- **C-01 — Transaksi bersarang dihapus (satu-satunya cacat kritis, dikerjakan terakhir dan terpisah).** Helper baru `Core\Database\Transaction` (penghitung kedalaman per koneksi wpdb): `begin()` hanya mengirim `START TRANSACTION` di kedalaman 0, `commit()` hanya mengirim `COMMIT` di level terluar, `rollback()` selalu mengirim `ROLLBACK` dan mereset kedalaman ke 0. Semua situs `START TRANSACTION`/`COMMIT`/`ROLLBACK` mentah dikonversi ke helper: NumberingService, StockService (termasuk `transfer_stock`), PurchaseOrderService, GoodsReceiptService, SupplierPaymentService, SaleService, StockOpnameService, CsvImportService (termasuk jalur ROLLBACK-lalu-return di tengah), DummyDataSeeder. `MockWPDB` kini mensimulasikan transaksi MySQL (snapshot tabel di START, pulihkan di ROLLBACK) agar rollback bisa diobservasi di unit test; `Transaction::resetAll()` dipanggil di `MockWPDB::reset()`. Tes baru: 3 tes unit `TransactionTest` (nested begin/commit hanya 1 START + 1 COMMIT; rollback dari kedalaman bersarang membatalkan semua; commit/rollback tanpa transaksi = no-op) + regresi `test_failed_receipt_rolls_back_stock_and_number` (penerimaan gagal di baris 2 → 0 tanda terima, PO tetap disetujui, qty_received 0, saldo stok kembali, nomor GR tidak terpakai, tanpa audit goods_receipt, kedalaman transaksi kembali 0). **Verifikasi runtime VM (MySQL nyata, 14/14 pemeriksaan):** penerimaan gagal di baris 2 → 0 tanda terima, 0 mutasi stok, nomor GR tidak terpakai, tanpa audit; penerimaan normal sesudahnya berhasil penuh (stok +10/+5, status diterima, audit po_approve + goods_receipt tercatat, kartu stok balance). Skrip: `tests/vm/nested-transaction-test.php`.
- **Verifikasi idempotensi C-13 di VM:** kunci `vm-klik-ganda-001` dibayar 2× atas PO yang sama → kedua panggilan mengembalikan BYR-SB/2026/00002 yang sama, 1 baris, paid_amount Rp400.000. Skrip: `tests/vm/idempotency-test.php`.
- **Runtime VM dibangun ulang 27 Nov 2026 (~14:35 WIB):** VM diganti lagi, database hilang seluruhnya; dibangun ulang dari `SETUP.md` + `database/plugin_tables_dump.sql` (30 produk, 2 gudang, 4 pelanggan, 4 supplier, 7 shift, 7 penjualan; skema 001–006 terpasang). Data uji dari verifikasi paket ini di DB hasil rebuild: PO-SB/2026/00002 (Rp450.000, terbayar Rp400.000, sisa Rp50.000), BYR-SB/2026/00001 (Rp300.000 tunai), BYR-SB/2026/00002 (Rp100.000 transfer, kunci `vm-klik-ganda-001`), PO-SB/2026/00003 + GR-SB/2026/00003 dan PO-SB/2026/00004 + GR-SB/2026/00004 (dua jalan skrip verifikasi C-01; jalan pertama berhenti di pemeriksaan audit yang keliru di skrip — baseline diambil setelah approve — diperbaiki, jalan kedua lulus 14/14).
- Tidak dikerjakan (batas paket): C-11 (tanpa pengurang kas manual), C-07 (metode stok menunggu keputusan Owner), peran `sb_finance` dan `SB_VERSION` tidak diubah, README.md tidak diubah.

### 0.5.0-ap (2026-11-27)
- Migrasi database `Migration_005_AP_Payments` membuat tabel `sb_supplier_payments` (supplier_id, purchase_order_id, nomor pembayaran, jumlah integer, metode tunai/transfer, tanggal bayar, referensi, catatan, dibuat_oleh) dan men-seed sequence `supplier_payment` (BYR-SB/). Ditemukan otomatis oleh Migrator (glob) — tidak ada daftar migrasi yang diedit.
- `SupplierPaymentService`: catat pembayaran hutang hanya untuk PO yang sudah ada penerimaan barang (draft/batal/tanpa penerimaan ditolak); jumlah integer > 0 dan ≤ sisa hutang (grand_total − paid_amount), overpayment ditolak; nomor pembayaran dari NumberingService di dalam transaksi; paid_amount PO diperbarui atomik (row lock FOR UPDATE + validasi ulang di dalam transaksi); audit log per pembayaran; mengembalikan pembayaran + sisa hutang + status (lunas bila paid_amount == grand_total). PO tidak punya status 'lunas' — status bayar diturunkan, bukan disimpan.
- `PurchasingAdmin`: halaman admin di bawah menu Sagara Baja — Daftar PO (filter status, kolom sisa hutang + status bayar), Detail PO (baris barang, tanda terima, riwayat pembayaran, formulir catat pembayaran dengan nonce + capability sb_manage_purchasing), Daftar Tanda Terima, Daftar Pembayaran Hutang. Didaftarkan di `Plugin.php` seperti SalesAdmin.
- Slice ini TIDAK membuat jurnal dan TIDAK menggerakkan kas/bank (tetap terbuka di sisa Sprint 4).
- Unit Test suite AP payment lengkap (14 test: sukses sebagian, lunas penuh, overpayment, 0/negatif, draft/batal/tanpa penerimaan, sebagian-lalu-lunas, nomor unik, getOutstanding, metode tidak dikenal, PO tidak ada, daftar hutang, daftar pembayaran) lulus 100% bersama seluruh suite lama: total 86 test (dari 72), dijalankan dengan assert() aktif: `php -d zend.assertions=1 -d assert.exception=1 tests/run.php` (exit 0). Mock `MockWPDB` diperluas: tabel + sequence supplier_payment dan handler `get_results` untuk pembayaran per PO, daftar pembayaran, dan daftar PO.
- Verifikasi runtime WordPress VM (MySQL nyata): reaktivasi plugin menjalankan Migration_004 ulang (idempotent) + Migration_005; alur end-to-end nyata PO-SB/2026/00001 (Besi Beton 8mm, 10 batang × Rp45.000 = Rp450.000) → disetujui → tanda terima GR-SB/2026/00001 (stok 500 → 510) → bayar Rp200.000 tunai via Admin UI (BYR-SB/2026/00001) → bayar Rp250.000 transfer via Admin UI (BYR-SB/2026/00002) → lunas; keempat halaman admin Purchasing mengembalikan 200 saat login; 2 entri audit log 'supplier_payment'.

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
