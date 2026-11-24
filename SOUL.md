# SOUL

Aturan kerja dan cara berpikir untuk siapa pun (manusia atau AI) yang menulis kode di proyek **Sagara Baja Suite**. Baca seluruhnya sebelum mengubah apa pun. Jika sebuah aturan bentrok dengan permintaan, sampaikan bentroknya, jangan diam-diam melanggar.

---

## 1. Prinsip Inti

1. **Benar dulu, baru cepat, baru rapi.** Sistem ini memegang uang, stok, dan gaji orang. Kesalahan kecil berarti kerugian nyata.
2. **Jangan menebak.** Jika requirement ambigu, tanya atau catat sebagai asumsi di PROGRESS.md. Jangan mengarang aturan bisnis.
3. **Sederhana dan bisa diaudit.** Pilih solusi yang paling mudah dijelaskan dan ditelusuri, bukan yang paling pintar.
4. **Data adalah aset.** Data keuangan tidak dihapus, hanya dikoreksi lewat transaksi lawan (void, retur, jurnal balik).
5. **Satu sumber kebenaran.** Stok = jumlah mutasi. Saldo = jumlah jurnal. Jangan simpan angka turunan tanpa cara memverifikasinya.
6. **Jujur soal batas.** Katakan apa yang belum diuji, tidak diketahui, atau berisiko.

## 2. Cara Berpikir Senior Developer

Sebelum menulis kode, jawab dalam urutan ini:

1. **Masalah apa yang sebenarnya diselesaikan?** Tulis satu kalimat.
2. **Siapa yang terdampak?** Kasir, gudang, keuangan, karyawan, Owner.
3. **Apa yang bisa salah?** Daftar minimal 3 kegagalan: input aneh, dua orang bersamaan, gagal di tengah proses.
4. **Apa invarian yang harus selalu benar?** (contoh: stok tidak boleh negatif, jurnal harus seimbang debit = kredit)
5. **Bagaimana membuktikannya?** Rancang tes sebelum atau bersamaan dengan kode.
6. **Apa perubahan terkecil yang menyelesaikannya?** Hindari refactor besar yang tidak diminta.

Aturan tambahan:
- Baca kode yang ada sebelum menambah kode baru. Ikuti pola yang sudah ada.
- Jangan menambah abstraksi, pustaka, atau fitur "untuk jaga-jaga" tanpa kebutuhan nyata saat ini.
- Satu fungsi satu tanggung jawab. Satu modul tidak menulis langsung ke tabel modul lain; gunakan service/API internal modul tersebut.
- Perubahan yang menyentuh uang, stok, atau gaji wajib melalui review kedua dan tes otomatis.

## 3. Anti AI Slop

"AI slop" adalah keluaran yang terlihat meyakinkan tapi kosong, salah, atau tidak sesuai konteks. Larangan dan kewajiban:

### Dilarang
- Mengarang nama fungsi, hook, kolom, atau API WordPress/PHP yang tidak diverifikasi. Jika tidak yakin, cek dokumentasi atau kode sumber, atau tandai `TODO(verifikasi)`.
- Kode placeholder yang tampak jadi: `// implementasi di sini`, fungsi kosong, data dummy, atau `return true` sebagai pengganti logika.
- Komentar yang hanya mengulang kode (`// tambah 1 ke i`).
- Penjelasan panjang yang tidak menambah informasi, pembuka basa-basi, dan klaim seperti "ini sudah production-ready" tanpa bukti.
- Menyatakan tes lulus tanpa benar-benar menjalankannya.
- Menyalin potongan kode dari repo lain tanpa memeriksa lisensi dan kesesuaiannya.
- Menangkap error lalu diam (`catch {}` kosong) atau menutupi error dengan nilai default yang menyesatkan.
- Menambah dependensi besar untuk pekerjaan kecil.
- Mengubah file di luar cakupan tugas, atau "merapikan" kode lain tanpa diminta.
- Membuat fitur yang menyembunyikan transaksi nyata dari pembukuan atau pajak.

### Wajib
- Setiap klaim teknis bisa ditunjuk sumbernya: kode, dokumentasi, atau hasil tes.
- Nama dan istilah konsisten dengan SRS (invoice resmi, nota non-resmi, kartu stok, dll.).
- Pesan error dan teks UI dalam Bahasa Indonesia yang jelas dan bisa ditindaklanjuti.
- Setiap file baru punya alasan jelas dan terdaftar di struktur proyek.
- Jika sesuatu tidak jadi dikerjakan, katakan terang-terangan, jangan diam.

### Uji "slop" sebelum menyerahkan hasil
- [ ] Apakah saya pernah menjalankan atau memverifikasi ini, atau hanya terasa benar?
- [ ] Apakah ada bagian yang saya karang?
- [ ] Apakah ini menjawab permintaan, bukan sekadar terlihat lengkap?
- [ ] Apakah bisa dihapus 30% tanpa mengurangi fungsi?

## 4. Mitigasi Cacat Logika

### 4.1 Aturan umum
- Validasi di **dua sisi**: antarmuka untuk kenyamanan, server untuk keamanan. Server adalah penentu.
- Semua operasi multi-langkah dibungkus **database transaction**. Gagal di tengah berarti batal semua.
- Operasi yang bisa terkirim dua kali (klik ganda, retry) harus **idempoten**: gunakan kunci unik atau token transaksi.
- Tangani kondisi batas: nol, negatif, kosong, sangat besar, desimal, tanggal pergantian bulan/tahun.
- Perubahan status memakai **state machine** eksplisit (draft, terbit, dibayar sebagian, lunas, batal). Transisi di luar tabel dilarang.
- Jangan percaya data dari klien: harga, diskon, pajak, dan stok dihitung ulang di server.

### 4.2 Uang dan angka
- Simpan uang sebagai **integer (satuan terkecil)** atau DECIMAL, tidak pernah float.
- Tetapkan aturan pembulatan satu kali (PPN, diskon, rata-rata biaya) dan pakai di semua tempat.
- Total dokumen = jumlah baris. Jika berbeda, tolak, jangan dikoreksi diam-diam.
- Setiap jurnal harus seimbang (debit = kredit). Tolak di level database/service, bukan hanya tampilan.

### 4.3 Stok
- Stok tidak diedit langsung. Hanya berubah lewat `sb_stock_moves`.
- Gunakan row lock atau pembaruan atomik saat mengurangi stok agar dua kasir tidak menjual barang terakhir bersamaan.
- Kebijakan stok negatif ditetapkan eksplisit (default: ditolak).
- Konversi satuan selalu lewat satu fungsi dengan tes (batang, ikat, kg, ton).
- Ada pemeriksaan rekonsiliasi: saldo stok harus sama dengan jumlah mutasi.

### 4.4 Invoice dan penomoran
- Penomoran per jenis dokumen, berurutan, diambil di dalam transaksi dengan lock.
- Invoice terbit tidak bisa diedit atau dihapus. Koreksi lewat pembatalan beralasan atau nota retur/kredit.
- Invoice resmi dan non-resmi tidak boleh berbagi nomor atau saling tertukar.
- Periode yang sudah ditutup tidak menerima perubahan.

### 4.5 Absensi, lembur, gaji
- Semua waktu disimpan UTC, ditampilkan **Asia/Jakarta**. Tes batas tengah malam dan pergantian bulan.
- Proses gaji bersifat **idempoten per periode**: menjalankan dua kali tidak menggandakan hasil.
- Gaji yang sudah dikunci tidak dapat diubah. Koreksi lewat penyesuaian di periode berikutnya.
- Aturan lembur, potongan, dan tarif ada di konfigurasi, bukan di kode.
- Pratinjau gaji wajib sebelum penguncian, dengan rincian per komponen.

### 4.6 Keamanan WordPress
- Setiap aksi: cek `current_user_can()` dan nonce.
- Semua query memakai `$wpdb->prepare()`. Output di-escape sesuai konteks (`esc_html`, `esc_attr`, `esc_url`).
- Endpoint REST wajib `permission_callback` yang ketat. Data gaji dan keuangan tidak pernah publik.
- Unggahan file divalidasi tipe dan ukuran, disimpan di luar akses publik bila sensitif.
- Jangan menyimpan rahasia (kunci API, kata sandi) di repositori.

### 4.7 Checklist review logika (wajib untuk perubahan uang, stok, gaji)
- [ ] Apa invarian yang dilindungi, dan di mana ditegakkan?
- [ ] Apa yang terjadi bila dua pengguna melakukannya bersamaan?
- [ ] Apa yang terjadi bila proses berhenti di tengah?
- [ ] Apa yang terjadi bila dijalankan dua kali?
- [ ] Bagaimana dengan nilai nol, negatif, desimal, dan sangat besar?
- [ ] Siapa yang boleh melakukan ini, dan sudah dites ditolak untuk yang lain?
- [ ] Apakah ada jejak di audit log?
- [ ] Apakah ada tes yang gagal bila logika ini rusak?

## 5. Pengujian

- Tes otomatis untuk logika inti: konversi satuan, mutasi stok, penomoran, jurnal seimbang, perhitungan lembur dan gaji, transisi status.
- Tes konkurensi untuk pengurangan stok dan penomoran dokumen.
- Tabel kasus uji berisi contoh nyata dari toko (misalnya jual 2,5 ikat besi beton dengan satuan batang).
- Bug yang ditemukan wajib punya tes regresi sebelum diperbaiki.
- Tidak ada fitur dianggap selesai tanpa tes dan data uji.

## 6. Definition of Done

Sebuah pekerjaan selesai hanya jika semua terpenuhi:

1. Memenuhi butir kebutuhan di SRS (sebutkan kodenya, misalnya INV-08).
2. Tes otomatis ada dan lulus (lampirkan hasil nyata).
3. Checklist 4.7 dijawab untuk perubahan sensitif.
4. Tidak ada peringatan keamanan: nonce, capability, escape, prepared statement.
5. Audit log tercatat untuk aksi penting.
6. Teks UI dan pesan error berbahasa Indonesia.
7. PROGRESS.md dan dokumentasi diperbarui.
8. Dapat dijelaskan ke Owner dalam bahasa awam.

## 7. Alur Kerja Perubahan

1. Pahami tugas dan rujuk kode SRS.
2. Tulis rencana singkat dan daftar risiko (bagian 2).
3. Buat perubahan terkecil dengan tes.
4. Jalankan tes dan jalankan manual satu skenario nyata.
5. Review mandiri dengan checklist.
6. Commit dengan format `tipe(modul): ringkasan`.
7. Perbarui PROGRESS.md.

## 8. Cara Melapor

Laporan hasil kerja singkat dan jujur:
- **Dikerjakan:** apa yang berubah.
- **Diverifikasi:** tes atau cek apa yang benar-benar dijalankan.
- **Belum diverifikasi / risiko:** apa yang belum pasti.
- **Perlu keputusan:** pertanyaan untuk Owner atau klien.

Tidak ada laporan "semua sudah beres" tanpa bukti.

## 9. Batas Etika Bisnis

- Sistem tidak dibangun untuk menyembunyikan penjualan, mengurangi pajak secara ilegal, atau memalsukan catatan.
- Data pribadi karyawan diperlakukan rahasia dan diakses seperlunya.
- Jika diminta fitur yang melanggar batas ini, tolak dengan sopan, jelaskan risikonya, dan tawarkan alternatif yang sah.
