# Runway — Multi-Branch POS System

Runway adalah aplikasi Point of Sale berbasis Laravel untuk mengelola master data, mencatat penjualan, dan mengurangi stok secara otomatis. Project ini dikembangkan sebagai portofolio dengan fokus pada konsistensi data, validasi backend, dan pengujian transaksi.

**Multi-branch merupakan arah pengembangan, belum implementasi operasional yang selesai.** Master Cabang sudah tersedia, tetapi pemisahan stok, transaksi, pengguna, dan laporan per cabang masih direncanakan.

## Fitur yang tersedia

- Login Admin, dashboard jumlah master data dan produk dengan stok rendah.
- Profil sendiri: perubahan nama/email dan password dengan verifikasi password saat ini. Penghapusan akun sendiri dinonaktifkan.
- Enam modul master data dengan CRUD, validasi, pagination, dan proteksi relasi.
- Penjualan satu atau beberapa produk, riwayat, dan detail transaksi.
- Ekspor daftar produk ke PDF.
- Antarmuka Blade dengan branding Runway, Tailwind CSS, dan Alpine.js.

| Master data | Kemampuan |
| --- | --- |
| Produk | Kode unik, harga/stok, kategori, supplier, keterangan; produk yang digunakan transaksi dilindungi dari penghapusan |
| Kategori | Nama dan keterangan opsional hingga 2.000 karakter |
| Supplier | Nama, kontak, alamat, dan relasi produk |
| Pelanggan | Nama, nomor telepon, dan alamat |
| Cabang | Nama cabang dan lokasi sebagai master data |
| Petugas | Nama, username unik, dan label peran master data |

## Transaksi dan integritas stok

Harga, jumlah, subtotal, dan total divalidasi serta dihitung di backend menggunakan harga database. Harga dan total yang dikirim browser tidak menjadi sumber kebenaran. Produk duplikat dalam satu keranjang digabung, jumlah harus bulat positif, dan stok diperiksa kembali saat penyimpanan.

Header transaksi, detail, klaim idempotency, dan pengurangan stok disimpan dalam satu database transaction. Produk dikunci dengan `lockForUpdate()` dalam urutan ID, pengurangan stok bersyarat mencegah stok negatif, dan operasi diulang secara terbatas ketika terjadi deadlock. Nomor transaksi menggunakan UUID dan unique index.

Setiap pengajuan menggunakan token idempotency unik. Pengiriman ulang token dengan keranjang setara oleh pengguna yang sama mengembalikan transaksi yang sudah tersimpan tanpa mengurangi stok kembali. Pemakaian token untuk keranjang atau pengguna berbeda ditolak. **Form baru menghasilkan token baru dan dapat mencatat penjualan berbeda**; idempotency bukan pembatas untuk semua keranjang yang kebetulan sama.

Form menolak jumlah kosong, pecahan, nol, negatif, atau melebihi stok. Estimasi tidak valid diberi petunjuk perbaikan dan tombol simpan tetap terkunci selama pengiriman. Total browser adalah estimasi; backend tetap memeriksa semua nilai. Perhitungan mengikuti harga produk dalam rupiah utuh dan kapasitas kolom database.

Tanggal pada daftar dan detail ditampilkan dalam **Asia/Jakarta (WIB)**. Konversi hanya dilakukan pada lapisan presentasi; timezone global aplikasi tetap UTC dan nilai tersimpan tidak diubah. Alur penjualan juga telah diverifikasi manual pada lingkungan pengembangan lokal.

Implementasi utama: [CreateSale](app/Actions/CreateSale.php), [TransaksiController](app/Http/Controllers/TransaksiController.php), dan [komponen tanggal](resources/views/components/transaction-date.blade.php).

## Teknologi dan persyaratan

| Komponen | Persyaratan / versi lingkungan yang diuji |
| --- | --- |
| PHP | `^8.2`; lingkungan Windows menggunakan **8.4.26 NTS x64** |
| Laravel | **12.64.0**, mengikuti `composer.lock` |
| Composer | **2.x**, diuji dengan **2.10.3** |
| Node.js | **24 LTS**, diuji dengan **24.22.0** |
| npm | **11.19.1** pada lingkungan pengembangan |
| Vite / Laravel Vite Plugin | **7.3.6 / 2.1.0**; engine Node `^20.19.0 || >=22.12.0` |
| Database | **MariaDB 10.4.32**, tabel InnoDB, charset `utf8mb4` |
| UI | Blade, Tailwind CSS 3, Alpine.js, Axios |
| Autentikasi dan akses | Breeze dan Spatie Laravel Permission 6.25 |
| PDF | Laravel DomPDF 3.1 |
| Pengujian | Pest/PHPUnit, PDO SQLite, PDO MySQL, Node.js test runner |

Ekstensi PHP meliputi PDO MySQL, PDO SQLite, Mbstring, OpenSSL, Fileinfo, DOM/XML, Curl, dan Zip. Gunakan `composer check-platform-reqs` untuk memeriksa kebutuhan seluruh dependency. Pertahankan verifikasi TLS saat mengunduh paket.

## Instalasi baru

Instruksi berikut ditujukan untuk checkout baru dan database development kosong, bukan prosedur upgrade database yang sudah berisi data.

```powershell
git clone https://github.com/ridhosyahputta-ai/laravel-pos-system.git
Set-Location laravel-pos-system

if (!(Test-Path .env)) { Copy-Item .env.example .env }
php -v
composer install
composer check-platform-reqs
npm ci
```

Atur koneksi database secara privat di `.env`: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`. Buat database development kosong terlebih dahulu dan pastikan targetnya benar. Gunakan PHP yang kompatibel; PHP Apache XAMPP lama tidak otomatis sama dengan PHP CLI.

Hanya untuk environment baru tersebut:

```powershell
php artisan key:generate
php artisan migrate
npm run build
```

Jangan membuat ulang APP_KEY pada instalasi yang sudah berjalan. Script `composer run setup` juga menjalankan pembuatan key dan migration sehingga tidak cocok untuk upgrade database existing tanpa pemeriksaan.

### Akun Admin awal

Repository tidak menyediakan akun demo, password default, atau salinan akun lokal. Pada database development baru, provisioning Admin melalui `DatabaseSeeder` memerlukan `SEED_ADMIN_NAME`, `SEED_ADMIN_EMAIL`, dan `SEED_ADMIN_PASSWORD` yang diisi secara privat. Model Laravel melakukan hashing password.

Setelah target database kosong dan konfigurasi privat dipastikan, provisioning dapat dilakukan secara eksplisit dengan `php artisan db:seed --class=DatabaseSeeder`. Ini bukan langkah otomatis, dan bukan cara mereset password pada database existing. UI pembuatan akun kasir oleh Admin belum tersedia.

## Menjalankan lokal

Dari terminal khusus pada root project:

```powershell
$env:SESSION_DRIVER = 'file'
$env:CACHE_STORE = 'array'
$env:APP_URL = 'http://127.0.0.1:8000'
php artisan serve --host=127.0.0.1 --port=8000 --tries=1 --no-reload
```

Buka [halaman login](http://127.0.0.1:8000/login), lalu gunakan menu Transaksi. Gunakan host yang konsisten untuk session login. Override di atas hanya berlaku pada terminal/proses tersebut dan tidak mengubah `.env`; cache `array` digunakan untuk pengembangan sementara.

Jika PHP 8.4 dipasang terpisah di Windows, jalankan dengan executable absolut, misalnya:

```powershell
& C:\tools\php84\php.exe C:\tools\composer\composer.phar check-platform-reqs
& C:\tools\php84\php.exe artisan serve --host=127.0.0.1 --port=8000 --tries=1 --no-reload
```

Untuk mengembangkan aset, jalankan `npm run dev` pada terminal terpisah. Checkout baru memerlukan build aset atau Vite karena `public/build`, `vendor`, dan `node_modules` tidak disimpan dalam Git. Fitur saat ini tidak memerlukan upload produk atau symbolic link storage.

## Pengujian dan isolasi

Hasil regresi terfokus terakhir untuk finalisasi Tahap 3, tanpa migration tambahan:

| Suite | Test lulus | Assertions |
| --- | ---: | ---: |
| Presentasi pada SQLite `:memory:` | 6 | 38 |
| MariaDB terisolasi, tanpa mengulang tes migration | 21 | 182 |
| JavaScript form dengan DOM fixture | 17 | Tidak dihitung oleh Node sebagai assertions terpisah |

Cakupan MariaDB mencakup CRUD enam master, akses Admin, PDF, harga backend, stok, rollback, token idempotency, nomor unik, dua proses dengan token sama, perebutan stok dengan token berbeda, serta deadlock InnoDB nyata dan retry. Worker menggunakan proses PHP dan koneksi database terpisah; hasil konkurensi tidak didasarkan pada simulasi SQLite.

Pengujian presentasi mencakup batas form, CSRF/token, tanggal WIB termasuk pergantian hari/tahun, dan pelestarian nilai UTC. Test JavaScript menjalankan script form yang sebenarnya dengan DOM fixture untuk input tidak valid, estimasi, penguncian submit, dan pemulihan back-forward cache. Ini bukan pengganti pengujian browser interaktif.

### SQLite dan JavaScript

Jalankan pada terminal testing tersendiri:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
$env:DB_URL = ''
$env:CACHE_STORE = 'array'
$env:SESSION_DRIVER = 'array'
$env:QUEUE_CONNECTION = 'sync'
$env:MAIL_MAILER = 'array'

php vendor/pestphp/pest/bin/pest tests/Unit/TransaksiPresentationTest.php --no-coverage --do-not-cache-result
node --test tests/Frontend/transaction-form.test.cjs
```

`tests/TestCase.php` menolak database di luar SQLite `:memory:`, lingkungan non-testing, dan konfigurasi cache sebelum setup database test. Suite backend yang lebih luas tersedia pada `tests/Feature/ProdukKategoriTest.php`, `MasterDataCrudTest.php`, `TransaksiTest.php`, dan `TransaksiIdempotencyTest.php`; suite Feature menggunakan `RefreshDatabase` dan membentuk skema sementara hanya dalam memori.

### MariaDB terpisah

Suite MariaDB menggunakan `phpunit.mariadb.xml`, bukan mengubah guard SQLite. Siapkan database **`db_runway_testing`** yang hanya berisi fixture disposable dan akun lokal **`runway_testing`** dengan hak akses hanya pada database tersebut, tanpa grant option atau akses ke database bisnis.

Buat `.env.mariadb-testing` secara privat; file ini diabaikan Git. Guard membutuhkan `APP_ENV=testing`, `DB_CONNECTION=mariadb`, `DB_HOST=127.0.0.1`, `DB_DATABASE=db_runway_testing`, `DB_USERNAME=runway_testing`, dan `RUNWAY_MARIADB_TESTS=1`. Isi `DB_PORT`, password database dan APP_KEY khusus testing secara privat; `DB_URL` dan `DB_SOCKET` harus kosong. Nama akun pada guard adalah allowlist teknis, bukan kredensial yang dapat digunakan tanpa konfigurasi privat.

Pada database testing baru saja, `php tests/MariaDb/migrate.php` memverifikasi nama database aktif dan akun sebelum menyiapkan skema. Jangan menjalankan inisialisasi ini pada database bisnis. Setelah skema testing tersedia, regresi tanpa mengulang perubahan skema dijalankan dengan:

```powershell
$filter = '/^(?!.*(?:testCategoryCorrectionPreservesLegacyDataOnNativeMariaDb|testIdempotencyMigrationPreservesLegacySalesOnNativeMariaDb)).*$/'
php vendor/phpunit/phpunit/phpunit -c phpunit.mariadb.xml --filter $filter --do-not-cache-result
```

Runner hanya memuat environment testing, memeriksa `SELECT DATABASE()` dan akun aktif, menolak URL/config override yang berbahaya, serta membuktikan akses akun testing ke database utama ditolak. Suite ini membersihkan fixture pada `db_runway_testing` setiap test; jangan gunakan database tersebut untuk data bisnis. Suite MariaDB penuh juga memuat pengujian perubahan skema kategori/idempotency dan hanya boleh dijalankan dengan izin perubahan skema testing.

Hasil di atas bukan klaim seluruh suite default project sudah lulus. Pengujian profil bawaan yang masih mengharapkan penghapusan akun perlu diselaraskan dengan kebijakan aplikasi.

## Status dan rencana pengembangan

- **Tersedia:** enam master, penjualan atomik, pengurangan stok, idempotency, detail/riwayat, PDF produk, dan presentasi WIB.
- **Akses saat ini:** seluruh modul bisnis masih untuk Admin. Label `petugas.role` tidak memberikan hak akses akun login.
- **Belum selesai:** akun kasir dan matriks permission, penonaktifan registrasi publik, serta audit pembuat transaksi. Route registrasi bawaan masih tersedia.
- **Direncanakan:** relasi transaksi/pengguna dengan cabang, stok dan laporan per cabang, pembayaran/kembalian, struk, retur, shift, dan pelaporan operasional.

Keberadaan master Cabang belum berarti isolasi multi-cabang sudah diterapkan. Project belum dinyatakan siap produksi.

## Database existing dan keamanan repository

Migration korektif kategori dan idempotency disertakan serta telah diuji pada MariaDB terisolasi. Untuk database existing, lakukan backup terverifikasi, audit pending migration, dan penerapan terbatas yang disetujui. Jangan menggunakan reset, seeding, atau `migrate:fresh` pada data bisnis. Rollback kategori menolak penyempitan otomatis; rollback idempotency menolak penghapusan kolom bila token sudah digunakan.

Database lokal, data penjualan, dump SQL, backup, `.env`, `.env.mariadb-testing`, password, APP_KEY, token autentikasi, dan konfigurasi privat tidak disertakan. `.env.example` hanya template. Simpan backup di luar repository; file session, log, dan artefak pengujian lokal juga diabaikan Git. Tidak ada kredensial demo yang perlu dipublikasikan untuk menjalankan project.
