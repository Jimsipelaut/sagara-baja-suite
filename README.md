# Sagara Baja Suite

Sistem inventory, kasir (POS), dan invoice untuk **Sagara Baja** — toko material dan logistik bangunan di Setu, Bekasi. Dibangun sebagai plugin WordPress kustom dengan tabel database sendiri (prefix `wp_sb_*`), tanpa dependensi WooCommerce.

> Status: **Sprint 3 selesai** — Inventory, Kasir POS, Invoice, dan Purchasing (PO & penerimaan barang) aktif. Lihat [PROGRESS.md](PROGRESS.md) untuk status terkini dan [SOUL.md](SOUL.md) untuk aturan kerja pengembangan.

---

## Demo cepat (Laragon, Windows)

```bash
git clone https://github.com/Jimsipelaut/sagara-baja-suite.git
# Symlink ke wp-content/plugins/sagara-baja-suite, aktifkan plugin
# Import database/plugin_tables_dump.sql
# Buka http://localhost/sagara-baja/ → Login → Buka Shift → 30 produk siap
```

**Panduan instalasi lengkap → [INSTALL.md](INSTALL.md)**

---

## 1. Tujuan

Satu sistem terpadu yang menghubungkan transaksi harian toko dengan stok, pembukuan, dan penggajian, sehingga:

- stok selalu sama dengan total mutasi,
- setiap transaksi dapat ditelusuri sampai jurnal dan pembuat datanya,
- data gaji dan keuangan hanya dapat dilihat peran yang berwenang.

---

## 2. Modul

| Modul | Status | Cakupan |
|---|---|---|
| **Core** | ✅ Selesai | Peran & hak akses, audit log, penomoran dokumen, pengaturan toko |
| **Inventory** | ✅ Selesai | Barang, satuan & konversi, harga bertingkat, multi-gudang, kartu stok, stock opname, impor CSV |
| **Sales & POS** | ✅ Selesai | Pelanggan, supplier, shift kasir, terminal POS frontend, invoice resmi & nota non-resmi, pembayaran multi-metode |
| **Purchasing** | ✅ Selesai (Sprint 3) | PO (draft → disetujui → diterima/batal), penerimaan barang (sebagian/penuh, tolak over-receipt), hutang usaha tercatat; pembayaran hutang di Sprint 4 |
| Accounting | 🔲 Sprint 4 | Bagan akun, jurnal otomatis dan manual, kas & bank, laporan keuangan |
| HR | 🔲 Sprint 5 | Profil karyawan, dokumen, riwayat jabatan dan gaji |
| Attendance | 🔲 Sprint 5 | Absen HP/QR, shift, izin, cuti, rekap bulanan |
| Payroll | 🔲 Sprint 6 | Komponen gaji, lembur, kasbon, potongan, slip gaji PDF |
| Reports | 🔲 Sprint 7 | Dashboard, laporan stok, penjualan, piutang, gaji, ekspor Excel dan PDF |

Spesifikasi lengkap ada di dokumen SRS (kebutuhan fungsional, non-fungsional, alur bisnis, pertanyaan terbuka).

---

## 3. Data dummy siap pakai

Folder `database/` berisi dua file SQL yang dapat langsung diimport:

| File | Isi |
|---|---|
| `plugin_tables_dump.sql` | **30 produk material bangunan**, 2 gudang, 4 pelanggan, 4 supplier, tier harga lengkap |
| `sagara_baja_dump.sql` | Seluruh setup WordPress (halaman, konfigurasi) — tanpa data user/password |

**30 produk dummy** mencakup: Besi Beton Ulir/Polos SNI (6–16mm), Hollow Galvanis, Wiremesh M6/M8, Kanal C75, Reng Baja Ringan, Atap Spandek, Genteng Metal, Semen Tiga Roda 50kg, Semen Gresik 40kg, Mortar Perekat Hebel, Bata Ringan Hebel, Pasir Pasang, Batu Split, Triplek 9/12/18mm, Papan GRC, Baut Roofing, Paku Kayu.

---

## 4. Invoice resmi dan non-resmi

Dua jenis dokumen dengan penomoran terpisah. Keduanya **tetap tercatat penuh** di sistem (stok keluar, kas masuk, audit log). Sistem tidak menyediakan cara menyembunyikan transaksi nyata dari pembukuan. Penentuan perlakuan pajak ada pada Owner dan akuntan.

## 5. Arsitektur

- Platform: WordPress 6.x, PHP 8.1+, MySQL/MariaDB
- Satu plugin modular, tabel berawalan `wp_sb_*`, tanpa ketergantungan WooCommerce
- Transaksi lintas modul berjalan atomik dalam satu database transaction
- **Row lock** `SELECT … FOR UPDATE` pada setiap mutasi stok — mencegah race condition multi-kasir
- **Nilai moneter** disimpan `BIGINT` (satuan Rupiah) atau `DECIMAL`, tidak pernah `float`
- Antarmuka: halaman admin kustom (back office) + halaman front-end terproteksi (kasir POS)

```
sagara-baja-suite/
├── sagara-baja-suite.php
├── core/                   # Bootstrap, Migrator, Roles, AuditLog, DocumentSequence, Settings
├── modules/
│   ├── inventory/          # ProductRepository, StockService, StockOpnameService, CsvImport, Seeders
│   ├── sales/              # CustomerRepository, SupplierRepository, ShiftService, SaleService, PdfInvoiceService, PosFrontend
│   └── purchasing/         # PurchaseOrderService, GoodsReceiptService (tanpa admin UI di Sprint 3)
├── assets/
├── database/               # SQL dump siap pakai
└── tests/                  # Unit test mandiri (tanpa PHPUnit)
```

---

## 6. Peran pengguna

| Peran WordPress | Akses |
|---|---|
| `sb_owner` | Semua fitur termasuk laporan keuangan dan gaji |
| `sb_admin` | Semua fitur kecuali pengaturan peran pengguna |
| `sb_cashier` | Terminal POS, shift kasir, cetak struk |
| `sb_warehouse` | Stok masuk/keluar, opname, transfer gudang |
| `sb_finance` | Invoice, pembayaran, laporan piutang/hutang |
| `sb_employee` | Data diri sendiri, slip gaji sendiri |

---

## 7. Referensi

Konsep dan alur dipelajari dari proyek open source berikut. Kode tidak disalin.

- WP ERP (HR, payroll, akuntansi double entry)
- Ready POS, wePOS, StoreCashier (kasir, sesi register, audit)
- Open Source Point of Sale (stok, penawaran ke invoice, cash up)

---

## 8. Konvensi pengembangan

- Seluruh aturan kerja dan standar mutu ada di [SOUL.md](SOUL.md). Wajib dibaca sebelum mengubah kode.
- Branch: `main` (stabil, selalu lulus test), `develop` (aktif).
- Commit: `tipe(modul): ringkasan`, misalnya `feat(inventory): tambah konversi satuan`.
- Jalankan `php -d zend.assertions=1 -d assert.exception=1 tests/run.php` sebelum setiap commit — harus exit 0. (Tanpa flag itu, `php tests/run.php` menolak jalan: assert() tidak aktif di php.ini production dan hasil "lulus" akan palsu.)
- Setiap perubahan perilaku disertai tes dan pembaruan [PROGRESS.md](PROGRESS.md).

---

## 9. Dokumen proyek

| Berkas | Fungsi |
|---|---|
| README.md | Gambaran proyek dan status |
| INSTALL.md | Panduan instalasi lengkap (Laragon, cPanel, symlink, import SQL) |
| PROGRESS.md | Status sprint, log keputusan, risiko, changelog |
| SOUL.md | Prinsip dan aturan kerja developer (termasuk anti AI slop dan mitigasi cacat logika) |

---

## 10. Lisensi

Belum ditentukan. Putuskan bersama klien sebelum rilis (kepemilikan kode, hak pakai, dan kewajiban pemeliharaan).
