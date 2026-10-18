# Runway — Laravel POS System

Aplikasi Point of Sale berbasis Laravel untuk pengelolaan master data toko dan pencatatan penjualan. Project masih dalam tahap pengembangan; fitur transaksi dan akses kasir belum selesai difinalisasi.

## Teknologi dan persyaratan

| Komponen | Persyaratan / kondisi terverifikasi |
| --- | --- |
| PHP | `^8.2` menurut Composer; lingkungan Windows diuji dengan PHP **8.4.26 NTS x64** |
| Laravel | **12.64.0**, mengikuti `composer.lock` |
| Composer | **2.x**; diuji dengan **2.10.3** |
| Node.js | Node **24 LTS** direkomendasikan; diuji dengan **24.22.0** |
| npm | Diuji dengan **11.19.1**, bawaan instalasi Node terpisah |
| Vite / Laravel Vite Plugin | **7.3.6 / 2.1.0**; requirement Node `^20.19.0 || >=22.12.0` |
| Database | MariaDB melalui PDO MySQL; lingkungan lokal memakai **MariaDB 10.4.32** |
| Frontend | Blade, Tailwind CSS 3, Alpine.js, Axios |
| Autentikasi | Scaffolding Breeze dan Spatie Laravel Permission **6.25.0** |
| PDF | Laravel DomPDF **3.1.2** |
| Pengujian | Pest/PHPUnit dengan SQLite `:memory:` |

Ekstensi PHP mencakup PDO MySQL, PDO SQLite untuk pengujian, Mbstring, OpenSSL, Fileinfo, DOM/XML, Curl, dan Zip. Jalankan `composer check-platform-reqs` untuk memeriksa seluruh kebutuhan paket. Jangan menonaktifkan verifikasi TLS untuk mengunduh dependency.

## Fitur yang sudah tersedia

- **Login Admin:** autentikasi email/password dan proteksi middleware admin pada modul bisnis saat ini.
- **Dashboard:** jumlah data master dan daftar produk dengan stok kurang dari 10; belum merupakan dashboard omzet.
- **Profil sendiri:** lihat/ubah nama dan email serta perubahan password dengan password saat ini. Komponen hapus akun tidak ditampilkan dan route penghapusan profil tidak tersedia.
- **Produk:** CRUD, kode unik, validasi harga/stok, relasi kategori/supplier, dan keterangan. Produk yang dipakai transaksi tidak dapat dihapus.
- **Kategori:** CRUD dengan keterangan opsional maksimal 2.000 karakter setelah skema database diselaraskan.
- **Supplier, pelanggan, cabang, petugas:** CRUD, validasi, pesan kesalahan, dan pagination. Binding route Petugas sudah menggunakan `{petugas}`.
- **Relasi penghapusan:** penghapusan kategori/supplier mempertahankan produk dan mengosongkan referensi terkait sesuai foreign key.
- **Ekspor PDF:** daftar produk dapat diunduh sebagai `data-produk.pdf`.
- **Transaksi dasar:** pemilihan beberapa produk, riwayat/detail, penghitungan harga di server, database transaction, pemeriksaan dan pengurangan stok dengan penguncian baris. Finalisasi serta pengujian khusus transaksi masih tertunda.
- **Branding Runway:** komponen biru dan layout autentikasi responsif dipertahankan.

### Batas fitur dan akses saat ini

Seluruh modul bisnis, termasuk transaksi, masih dibatasi untuk role `admin`. Data `petugas.role` adalah label master data, bukan pemberian role akun login. UI pembuatan akun kasir oleh admin belum tersedia.

Kebijakan yang dituju adalah POS internal. Penonaktifan registrasi publik dan implementasi akses kasir belum dikerjakan; routes registrasi bawaan masih tersedia. Jangan menganggap kebijakan tersebut sudah diterapkan seluruhnya.

Belum tersedia pembayaran/kembalian, struk thermal, retur, shift kasir, laporan penjualan lengkap, atau stok per cabang.

## Instalasi baru

Instruksi ini hanya untuk checkout baru dan database pengembangan baru yang kosong. Jangan menjalankannya sebagai prosedur upgrade database lokal yang sudah berisi data.

```powershell
git clone https://github.com/ridhosyahputta-ai/laravel-pos-system.git
Set-Location laravel-pos-system

# Jangan menimpa environment yang sudah ada.
if (!(Test-Path .env)) { Copy-Item .env.example .env }

php -v
composer install
composer check-platform-reqs
npm ci
```

Pastikan `php` benar-benar menunjuk PHP yang kompatibel. Pada laptop yang menggunakan instalasi terpisah, Composer dapat dijalankan dengan:

```powershell
& C:\tools\php84\php.exe C:\tools\composer\composer.phar --version
& C:\tools\php84\php.exe C:\tools\composer\composer.phar check-platform-reqs
```

Untuk environment baru saja, buat application key:

```powershell
php artisan key:generate
```

Jangan membuat ulang APP_KEY pada instalasi yang sudah berjalan. Konfigurasikan koneksi MariaDB/MySQL secara privat dalam `.env`, termasuk `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`. Buat database pengembangan baru terlebih dahulu dan pastikan nama targetnya benar.

Hanya setelah database baru yang kosong tersebut dipastikan:

```powershell
php artisan migrate
npm run build
```

Jangan memakai `composer run setup` pada database existing: script tersebut mencakup instalasi dependency, pembuatan key, dan migration.

### Akun awal

Repository tidak menyediakan kredensial demo atau password default. Untuk provisioning yang disetujui pada database baru, seeder Admin memerlukan `SEED_ADMIN_NAME`, `SEED_ADMIN_EMAIL`, dan `SEED_ADMIN_PASSWORD` dari environment privat. Nilai contoh dibiarkan kosong; password di-hash oleh model Laravel.

Seeder akun alternatif juga memerlukan variabel `SEED_CASHIER_*` dan role yang sesuai. Seeder bukan proses otomatis saat aplikasi dijalankan. Jangan menjalankan seeder pada database existing untuk memulihkan login atau mereset password.

## Menjalankan lokal di Windows

Apache XAMPP pada lingkungan yang diaudit masih memakai PHP 8.0. Gunakan PHP 8.4 terpisah untuk aplikasi ini, tanpa mengganti PHP Apache.

Dari root project, buka terminal pengembangan khusus:

```powershell
$env:SESSION_DRIVER = 'file'
$env:CACHE_STORE = 'array'
$env:APP_URL = 'http://127.0.0.1:8000'

& C:\tools\php84\php.exe artisan serve --host=127.0.0.1 --port=8000 --tries=1 --no-reload
```

Buka `http://127.0.0.1:8000/login`. Override tersebut hanya berlaku pada proses terminal dan tidak mengubah `.env`. Session file mempertahankan login; session `array` tidak mempertahankan login antar-request. Cache `array` ditujukan untuk pemeriksaan lokal sementara, bukan konfigurasi deployment.

Untuk perubahan frontend, jalankan Vite pada terminal terpisah dengan PATH sementara:

```powershell
$env:PATH = "C:\tools\node24;$env:PATH"
& C:\tools\node24\npm.cmd run dev
```

Aset build tidak disimpan dalam Git. Instalasi baru perlu membangun aset atau menjalankan Vite. Folder `public/storage` belum diperlukan oleh fitur upload saat ini; link tersebut tidak dibuat otomatis.

## Pengujian terisolasi

Hasil terakhir untuk suite CRUD terfokus dan regresinya: **87 test lulus, 658 assertions**. Ini bukan klaim bahwa seluruh suite project atau seluruh alur transaksi sudah diuji.

Cakupan: enam modul master, validasi create/update, batas input, binding record, username/kode duplikat, pembatasan akses, pelestarian produk/transaksi saat master dihapus, PDF, dan migration korektif kategori pada SQLite.

`tests/TestCase.php` menolak bootstrap database test di luar `APP_ENV=testing` dan SQLite `:memory:` serta menolak konfigurasi aplikasi yang di-cache. Pengaman berjalan sebelum setup `RefreshDatabase`.

Jalankan dari terminal testing tersendiri:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
$env:DB_URL = ''
$env:CACHE_STORE = 'array'
$env:SESSION_DRIVER = 'array'
$env:QUEUE_CONNECTION = 'sync'
$env:MAIL_MAILER = 'array'

& C:\tools\php84\php.exe vendor/pestphp/pest/bin/pest tests/Feature/MasterDataCrudTest.php tests/Feature/ProdukKategoriTest.php tests/Feature/KategoriSchemaMigrationTest.php --no-coverage --do-not-cache-result
```

Jangan mengarahkan `RefreshDatabase`, `migrate:fresh`, atau test mutasi ke database bisnis. Pengujian MariaDB memerlukan suite dan database testing terpisah yang disiapkan secara khusus; guard saat ini sengaja hanya mengizinkan SQLite in-memory.

## Pekerjaan berikutnya

1. Finalisasi transaksi: pasangan produk/jumlah, kode transaksi unik, penanganan request ulang, total/stok, dan pengujian concurrency.
2. Uji CRUD serta migration pada MariaDB terpisah, termasuk perilaku collation dan foreign key.
3. Terapkan migration `2026_10_18_000001_normalize_keterangan_on_kategoris_table` pada database utama **hanya setelah backup, pengujian MariaDB, dan persetujuan terpisah**. Pada database lokal existing `db_rpld`, kolom masih `VARCHAR(50) NOT NULL`; input kosong atau panjang belum aman sampai penyelarasan dilakukan.
4. Migration korektif mengubah kolom menjadi `TEXT NULL`. Rollback penyempitan otomatis ditolak untuk menghindari kehilangan data.
5. Implementasikan role kasir, pembuatan akun oleh admin, penonaktifan registrasi publik, dan batas akses riwayat transaksi. Transaksi saat ini belum memiliki identitas pembuat untuk membatasi riwayat per kasir.
6. Selaraskan UTC/WIB setelah memeriksa timestamp historis; aplikasi saat ini menggunakan UTC.
7. Sesuaikan pengujian bawaan yang masih mengharapkan penghapusan akun, lalu jalankan suite penuh.

## Database dan keamanan repository

Database lokal, dump, backup, `.env`, APP_KEY, password, token, dan autentikasi GitHub tidak disertakan dalam repository. `.env.example` hanya template konfigurasi tanpa kredensial rahasia. Dependency `vendor`, `node_modules`, file session/log, dan hasil build juga tidak diunggah.

Simpan backup di luar repository. Jangan memasukkan kredensial ke README, commit, URL remote, atau command line yang tersimpan dalam riwayat. Seeder tidak menyimpan password bawaan dan tidak dijalankan oleh pengujian terfokus di atas.

## English summary

Runway is a Laravel POS work in progress. Six master-data modules, self-profile editing, and product PDF export have focused regression coverage: **87 passing tests / 658 assertions** on SQLite in memory. Basic sales transactions exist but still need finalization. Cashier access, MariaDB integration tests, and the category schema correction on the existing local database remain pending. Public registration is not disabled yet. Local databases, backups, environment secrets, and demo credentials are not distributed with this repository.
