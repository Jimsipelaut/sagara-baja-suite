# Panduan Instalasi — Sagara Baja Suite

Panduan ini menjelaskan cara menyiapkan lingkungan lokal Sagara Baja Suite dari awal menggunakan **Laragon** (Windows) atau stack LAMP/LEMP setara.

---

## Prasyarat

| Kebutuhan | Versi minimum |
|---|---|
| PHP | 8.1+ |
| MySQL / MariaDB | 8.0+ / 10.4+ |
| WordPress | 6.4+ |
| Ekstensi PHP | `mbstring`, `intl`, `gd`, `bcmath`, `pdo_mysql` |
| Laragon *(opsional, direkomendasikan untuk Windows)* | 6.0+ |

---

## Langkah 1 — Clone repo

```bash
git clone https://github.com/Jimsipelaut/sagara-baja-suite.git
```

Simpan di folder yang mudah dijangkau, misalnya:
```
C:\Users\<kamu>\Desktop\sagara-baja\
```

---

## Langkah 2 — Siapkan WordPress

### Opsi A — Laragon (Windows, direkomendasikan)

1. Buka Laragon → **Menu → www → Tambah situs baru**.
2. Beri nama `sagara-baja`, Laragon akan membuat folder `C:\laragon\www\sagara-baja\`.
3. Unduh WordPress terbaru dari [wordpress.org](https://wordpress.org/download/) dan ekstrak ke `C:\laragon\www\sagara-baja\`.
4. Buat database baru di phpMyAdmin (buka dari Laragon): `sagara_baja`, charset `utf8mb4`.
5. Jalankan wizard instalasi WordPress di `http://localhost/sagara-baja/`.
   - Database: `sagara_baja`, user: `root`, password: kosong, host: `127.0.0.1`.
   - Catat username & password admin yang kamu buat.

### Opsi B — cPanel / Hosting

1. Buat database dan user MySQL via cPanel Wizard.
2. Unggah WordPress ke subfolder atau root domain.
3. Ikuti wizard instalasi WordPress seperti biasa.

---

## Langkah 3 — Pasang plugin

### Cara A — Symlink (untuk pengembang, sangat direkomendasikan)

Buat symlink dari folder plugins WordPress menuju repo yang sudah di-clone:

**Windows (PowerShell Administrator):**
```powershell
New-Item -ItemType Junction `
  -Path "C:\laragon\www\sagara-baja\wp-content\plugins\sagara-baja-suite" `
  -Target "C:\Users\<kamu>\Desktop\sagara-baja"
```

**Linux/Mac:**
```bash
ln -s /path/ke/sagara-baja /var/www/html/sagara-baja/wp-content/plugins/sagara-baja-suite
```

### Cara B — Copy biasa

Salin seluruh folder hasil clone ke:
```
wp-content/plugins/sagara-baja-suite/
```

---

## Langkah 4 — Aktifkan plugin

1. Buka WordPress Admin → **Plugins → Installed Plugins**.
2. Aktifkan **Sagara Baja Suite**.
3. Plugin otomatis membuat semua tabel `wp_sb_*` dan mendaftarkan 6 peran pengguna.
4. Menu **Sagara Baja** muncul di sidebar kiri admin.

---

## Langkah 5 — Import database dummy (opsional, siap pakai)

Tersedia dua file SQL di folder `database/`:

| File | Isi |
|---|---|
| `plugin_tables_dump.sql` | **Hanya tabel plugin** `wp_sb_*` — 30 produk material bangunan, 2 gudang, 4 pelanggan, 4 supplier, tier harga lengkap. Import ini jika WordPress sudah berjalan dan plugin sudah diaktifkan. |
| `sagara_baja_dump.sql` | Seluruh database WordPress (kecuali `wp_users` dan `wp_usermeta`) termasuk halaman, pengaturan, dan konfigurasi plugin. Import ini jika ingin langsung menggunakan setup persis seperti environment asli. |

### Import via phpMyAdmin (Laragon)

1. Buka phpMyAdmin → pilih database `sagara_baja`.
2. Klik tab **Import** → pilih file SQL → klik **Go**.

### Import via CLI

```bash
# Hanya tabel plugin (direkomendasikan jika WP sudah berjalan):
mysql -u root -p sagara_baja < database/plugin_tables_dump.sql

# Seluruh setup (kecuali users):
mysql -u root -p sagara_baja < database/sagara_baja_dump.sql
```

> **Catatan:** `sagara_baja_dump.sql` mengandung `siteurl = http://localhost/sagara-baja`.
> Jika URL-mu berbeda, jalankan setelah import:
> ```sql
> UPDATE wp_options SET option_value = 'http://localhost/nama-sitemu'
> WHERE option_name IN ('siteurl', 'home');
> ```

---

## Langkah 6 — Konfigurasi halaman POS sebagai beranda

Jika import database dilakukan, halaman POS sudah otomatis jadi beranda.

Jika WordPress dipasang segar (tanpa import):

1. Buka **WordPress Admin → Pages → Add New**.
2. Judul: `Kasir / POS`, konten: `[sb_pos]`, status: **Published**.
3. Buka **Settings → Reading** → pilih **A static page** → pilih halaman yang baru dibuat.
4. Buka `http://localhost/sagara-baja/` → langsung tampil antarmuka POS.

---

## Langkah 7 — Buka Shift dan mulai transaksi

1. Buka `http://localhost/sagara-baja/` di browser.
2. Login dengan akun admin atau kasir WordPress.
3. Klik **Buka Shift** → pilih gudang → masukkan modal awal → klik konfirmasi.
4. 30 kartu produk material bangunan tampil di grid.
5. Klik produk → masukkan kuantitas → proses pembayaran.

---

## Langkah 8 — Isi data master baru (tanpa dummy)

Jika tidak memakai import dummy dan ingin input data sendiri:

1. **Sagara Baja → Inventori → Kategori** — tambah kategori (Besi & Baja, Atap, Semen & Pasir, dll.)
2. **Sagara Baja → Inventori → Satuan** — tambah satuan (Batang, Zak, Lembar, Kg, M³, dll.)
3. **Sagara Baja → Inventori → Gudang** — tambah minimal 1 gudang
4. **Sagara Baja → Inventori → Barang** — tambah produk dengan harga eceran/grosir/proyek
5. **Sagara Baja → Inventori → Seeder Dummy** — klik tombol untuk inisialisasi data contoh (opsional)
6. **Sagara Baja → Penjualan → Pelanggan** — tambah pelanggan tetap
7. **Sagara Baja → Penjualan → Supplier** — tambah supplier/pemasok

---

## Menjalankan test suite (pengembang)

```bash
php -d zend.assertions=1 -d assert.exception=1 tests/run.php
```

Semua test harus lulus (exit 0) sebelum commit ke `develop`. Flag `-d` wajib: tanpa itu `assert()` tidak aktif di php.ini production dan `tests/run.php` akan menolak jalan.

---

## Struktur folder repo

```
sagara-baja-suite/
├── sagara-baja-suite.php   # Entrypoint plugin
├── core/                   # Bootstrap, Autoloader, Migrator, Roles, AuditLog, Settings
├── modules/
│   ├── inventory/          # Barang, satuan, gudang, stok, opname, impor CSV
│   └── sales/              # Pelanggan, supplier, shift kasir, POS, invoice, nota
├── assets/                 # CSS & JS frontend POS
├── database/               # SQL dump tabel plugin (data dummy siap pakai)
│   ├── plugin_tables_dump.sql
│   └── sagara_baja_dump.sql
├── tests/                  # Unit test mandiri (tanpa PHPUnit)
│   ├── run.php
│   ├── Core/
│   ├── Inventory/
│   └── Sales/
├── INSTALL.md              # Panduan ini
├── PROGRESS.md             # Status sprint dan changelog
├── SOUL.md                 # Prinsip dan aturan kerja
└── README.md               # Gambaran proyek
```

---

## Troubleshooting

### Halaman POS menampilkan "Buka Shift" tapi produk tidak muncul setelah shift dibuka

Pastikan tabel sudah terisi. Cek via phpMyAdmin:
```sql
SELECT count(*) FROM wp_sb_products WHERE is_active = 1;
SELECT count(*) FROM wp_sb_stock_balances WHERE qty > 0;
```
Jika kosong, import `plugin_tables_dump.sql` atau jalankan seeder dari menu admin.

### Plugin aktif tapi menu "Sagara Baja" tidak muncul

Pastikan user WordPress punya role yang benar. Role bawaan WordPress (`administrator`) secara otomatis menerima capabilities Sagara Baja. Jika menggunakan role kustom, assign capability `sb_manage_inventory` secara manual.

### Tabel `wp_sb_*` tidak terbuat setelah aktivasi

Pastikan user database MySQL punya hak `CREATE TABLE`. Cek error di **WordPress Admin → Tools → Site Health → Info → Database**.

### Error `column buy_price does not exist`

Versi lama POS frontend memakai nama kolom yang salah. Pastikan kode terbaru di-pull dari repo (fix sudah ada di commit `10d4b0d`).

### URL beranda tidak sesuai setelah import SQL

Jalankan di phpMyAdmin atau CLI:
```sql
UPDATE wp_options
SET option_value = REPLACE(option_value, 'http://localhost/sagara-baja', 'http://URL_BARUMU')
WHERE option_name IN ('siteurl', 'home');
```

---

## Konvensi pengembangan

- Branch: `main` (stabil, selalu lulus test), `develop` (aktif)
- Commit format: `tipe(modul): ringkasan` → contoh: `feat(inventory): tambah konversi satuan`
- Seluruh aturan mutu kode ada di **[SOUL.md](SOUL.md)** — wajib dibaca sebelum membuat perubahan
- Setiap perubahan perilaku harus disertai test yang lulus dan pembaruan `PROGRESS.md`
