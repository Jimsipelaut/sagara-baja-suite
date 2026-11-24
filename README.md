# Sagara Baja Suite

Sistem inventory, kasir (POS), invoice, pembukuan, dan HR (karyawan, absensi, lembur, gaji) untuk **Sagara Baja**, toko material dan logistik bangunan di Setu. Dibangun sebagai plugin WordPress kustom dengan tabel database sendiri.

> Status: **tahap perencanaan (SRS v0.1 selesai)**. Lihat [PROGRESS.md](PROGRESS.md) untuk kemajuan terkini dan [SOUL.md](SOUL.md) untuk aturan kerja pengembangan.

---

## 1. Tujuan

Satu sistem terpadu yang menghubungkan transaksi harian toko dengan stok, pembukuan, dan penggajian, sehingga:

- stok selalu sama dengan total mutasi,
- setiap transaksi dapat ditelusuri sampai jurnal dan pembuat datanya,
- data gaji dan keuangan hanya dapat dilihat peran yang berwenang.

## 2. Modul

| Modul | Cakupan |
|---|---|
| Inventory | Barang, multi-satuan dan konversi, harga bertingkat, multi-gudang, kartu stok, stock opname |
| Sales dan POS | Pelanggan, penawaran, penjualan, retur, shift kasir, struk, hold transaksi |
| Invoice | Invoice resmi dan nota non-resmi, penomoran terpisah, PDF, status pembayaran |
| Purchasing | Supplier, PO, penerimaan barang, hutang |
| Accounting | Bagan akun, jurnal otomatis dan manual, kas dan bank, piutang, laporan keuangan |
| HR | Profil karyawan, dokumen, riwayat jabatan dan gaji |
| Attendance | Absen HP/QR, shift, izin, cuti, rekap bulanan |
| Payroll | Komponen gaji, lembur, kasbon, potongan, slip gaji PDF |
| Reports | Dashboard, laporan stok, penjualan, piutang, gaji, ekspor Excel dan PDF |
| Core | Peran dan hak akses, audit log, penomoran dokumen, pengaturan |

Spesifikasi lengkap ada di dokumen SRS (kebutuhan fungsional, non-fungsional, alur bisnis, pertanyaan terbuka).

## 3. Invoice resmi dan non-resmi

Dua jenis dokumen dengan penomoran terpisah. Keduanya **tetap tercatat penuh** di sistem (stok keluar, kas masuk, audit log). Sistem tidak menyediakan cara menyembunyikan transaksi nyata dari pembukuan. Penentuan perlakuan pajak ada pada Owner dan akuntan.

## 4. Arsitektur

- Platform: WordPress 6.x, PHP 8.1+, MySQL/MariaDB
- Satu plugin modular, tabel berawalan `sb_`, tanpa ketergantungan WooCommerce
- Transaksi lintas modul berjalan atomik dalam satu database transaction
- Antarmuka: halaman admin kustom (back office) dan halaman front-end terproteksi (kasir, absensi)

Struktur direktori yang direncanakan:

```
sagara-baja-suite/
  sagara-baja-suite.php
  core/
  modules/
    inventory/  purchasing/  sales/  pos/  invoice/
    accounting/  hr/  attendance/  payroll/  reports/
  assets/
  languages/
  tests/
```

## 5. Referensi

Konsep dan alur dipelajari dari proyek open source berikut. Kode tidak disalin.

- WP ERP (HR, payroll, akuntansi double entry)
- Ready POS, wePOS, StoreCashier (kasir, sesi register, audit)
- Open Source Point of Sale (stok, penawaran ke invoice, cash up)

## 6. Peran pengguna

Owner, Admin, Kasir/Sales, Gudang, Keuangan, Karyawan. Prinsip: data gaji, pembukuan, dan audit log hanya untuk Owner dan Keuangan. Karyawan hanya melihat datanya sendiri.

## 7. Instalasi (target)

```
1. Salin folder sagara-baja-suite ke wp-content/plugins/
2. Aktifkan plugin di Plugins > Installed Plugins
3. Jalankan wizard awal: profil toko, mata uang, zona waktu (Asia/Jakarta), penomoran dokumen
4. Impor master data awal dari template Excel/CSV
```

Kebutuhan server: PHP 8.1+, ekstensi `mbstring`, `intl`, `gd`, `bcmath`, HTTPS aktif, backup database harian.

## 8. Konvensi pengembangan

- Seluruh aturan kerja dan standar mutu ada di [SOUL.md](SOUL.md). Wajib dibaca sebelum mengubah kode.
- Branch: `main` (stabil), `develop`, `feature/<modul>-<topik>`.
- Commit: `tipe(modul): ringkasan`, misalnya `feat(inventory): tambah konversi satuan`.
- Setiap perubahan perilaku disertai tes dan pembaruan [PROGRESS.md](PROGRESS.md).

## 9. Dokumen proyek

| Berkas | Fungsi |
|---|---|
| README.md | Gambaran proyek |
| PROGRESS.md | Status sprint, keputusan, risiko, changelog |
| SOUL.md | Prinsip dan aturan kerja developer (termasuk anti AI slop dan mitigasi cacat logika) |
| SRS | Spesifikasi kebutuhan |

## 10. Lisensi

Belum ditentukan. Putuskan bersama klien sebelum rilis (kepemilikan kode, hak pakai, dan kewajiban pemeliharaan).
