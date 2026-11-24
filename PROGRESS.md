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
| Kode | Belum dimulai |
| Klien: jawaban pertanyaan terbuka | Menunggu |

## 2. Rencana Sprint

### Sprint 0 - Core
- [ ] Bootstrap plugin dan sistem migrasi tabel
- [ ] Peran dan capability (Owner, Admin, Kasir, Gudang, Keuangan, Karyawan)
- [ ] Audit log (append-only)
- [ ] Penomoran dokumen (resmi, non-resmi, PO, dll.) tanpa celah dan aman dari race condition
- [ ] Halaman pengaturan (profil toko, zona waktu, mata uang)

### Sprint 1 - Inventory dan master data
- [ ] Barang, kategori, satuan dan konversi
- [ ] Harga bertingkat (eceran, grosir, proyek)
- [ ] Gudang dan kartu stok (sb_stock_moves)
- [ ] Impor Excel/CSV dengan validasi
- [ ] Stock opname dengan persetujuan

### Sprint 2 - Sales, POS, Invoice
- [ ] Pelanggan dan supplier
- [ ] Penjualan dan retur
- [ ] Layar kasir, shift kasir, hold, struk
- [ ] Invoice resmi dan non-resmi, PDF
- [ ] Pembayaran dan alokasi ke invoice

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

## 4. Pertanyaan Terbuka (menunggu klien)

- [ ] Status PKP dan perlakuan PPN?
- [ ] Definisi pasti invoice non-resmi dan siapa pemakainya?
- [ ] Jumlah SKU, karyawan, transaksi per hari, gudang?
- [ ] Metode stok: rata-rata atau FIFO?
- [ ] Skema gaji (bulanan, mingguan, harian), aturan lembur, BPJS, PPh 21?
- [ ] Cara absensi: HP, QR, atau mesin fingerprint?
- [ ] Jumlah kasir dan perangkat (printer thermal, scanner)?
- [ ] Perlu mode offline di kasir?
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

### 0.1.0-planning (2026-11-24)
- SRS v0.1 dibuat (modul inventory, invoice, pembukuan, HR, absensi, lembur, gaji)
- Ditambahkan modul kasir (POS) dan integrasi antar modul
- Disusun desain gabungan dari referensi GitHub dan rencana sprint
- README, PROGRESS, dan SOUL dibuat

## 7. Aturan Memperbarui Berkas Ini

- Perbarui setiap selesai satu butir, bukan menunggu akhir sprint.
- Butir hanya boleh `[x]` jika kode, tes, dan dokumentasinya selesai (lihat Definition of Done di SOUL.md).
- Setiap keputusan arsitektur atau bisnis dicatat di Log Keputusan beserta alasannya.
