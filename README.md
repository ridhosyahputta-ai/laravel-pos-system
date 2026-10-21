# Runway — Multi-Branch POS System

Runway adalah aplikasi Point of Sale berbasis Laravel untuk mengelola master data, mencatat penjualan, dan mengurangi stok secara otomatis. Project ini dikembangkan sebagai portofolio dengan fokus pada konsistensi data, validasi backend, dan pengujian transaksi.

**Multi-branch merupakan arah pengembangan, belum implementasi operasional yang selesai.** Master Cabang, penempatan satu Kasir ke satu Cabang, serta pencatatan pengguna dan cabang transaksi sudah tersedia. **Stok masih global**; stok per cabang dan laporan multi-cabang belum tersedia.

## Fitur yang tersedia

- Login Admin dan Kasir menggunakan role Spatie. Dashboard jumlah master data dan produk dengan stok rendah khusus Admin.
- Admin dapat membuat, mengedit, dan menonaktifkan akun Kasir; password di-hash dan role ditetapkan backend. Satu Kasir wajib ditempatkan pada satu Cabang.
- Akun nonaktif ditolak saat login dan pada request berikutnya, termasuk session lama. Registrasi publik dinonaktifkan.
- Profil sendiri: perubahan nama/email dan password dengan verifikasi password saat ini. Penghapusan akun sendiri dinonaktifkan.
- Enam modul master data dengan CRUD, validasi, pagination, proteksi relasi, serta tampilan form dan tabel yang konsisten.
- Penjualan satu atau beberapa produk, riwayat, dan detail transaksi. Admin melihat seluruh transaksi; Kasir hanya melihat transaksi miliknya.
- Ekspor daftar produk ke PDF.
- Antarmuka Blade dengan branding Runway, Tailwind CSS, dan Alpine.js.

| Master data | Kemampuan |
| --- | --- |
| Produk | Kode unik, harga/stok, kategori, supplier, keterangan; produk yang digunakan transaksi dilindungi dari penghapusan |
| Kategori | Nama dan keterangan opsional hingga 2.000 karakter |
| Supplier | Nama, kontak, alamat, dan relasi produk |
| Pelanggan | Nama, nomor telepon, dan alamat |
| Cabang | Nama cabang dan lokasi; penghapusan dibatasi jika masih direferensikan akun atau transaksi |
| Petugas | Nama, username unik, dan label peran master data; terpisah dari akun login |

Akses master data, ekspor PDF produk, dan pengelolaan Kasir hanya untuk Admin. Admin dan Kasir aktif dapat membuat transaksi; Kasir tanpa Cabang dan pengguna tanpa role tidak dapat mengakses fitur penjualan. Profil/password sendiri tetap tersedia, sedangkan penghapusan akun sendiri dinonaktifkan.

Halaman index, tambah, dan edit Produk, Kategori, Supplier, Pelanggan, Cabang, serta Petugas menggunakan komponen Blade `master-*` yang dapat digunakan kembali: layout, input, label, tombol, toolbar, feedback, tabel, empty state, dan pagination. Form responsif, tabel dengan scroll internal, pesan validasi yang jelas, serta focus/hover state diterapkan dengan styling khusus master data.

Palet master data menggunakan background abu-abu kebiruan (`#e2e8f0`), kartu lembut (`#e8eef5`), dan input terang (`#f1f5f9`) untuk mengurangi silau; tombol utama tetap biru Runway. Penyesuaian warna terakhir hanya mengubah background, tanpa mengubah layout atau fungsi.

Fondasi akun Kasir telah diimplementasikan dan diuji otomatis. **Alur utama login, pembatasan akses, dan penjualan Kasir telah dikonfirmasi melalui pengujian manual pada 21 Oktober 2026.** Verifikasi rinci penonaktifan dan cakupan operasional end-to-end masih memerlukan konfirmasi.

## Transaksi dan integritas stok

Harga, jumlah, subtotal, dan total divalidasi serta dihitung di backend menggunakan harga database. Harga dan total yang dikirim browser tidak menjadi sumber kebenaran. Produk duplikat dalam satu keranjang digabung, jumlah harus bulat positif, dan stok diperiksa kembali saat penyimpanan.

Header transaksi, detail, klaim idempotency, dan pengurangan stok disimpan dalam satu database transaction. Produk dikunci dengan `lockForUpdate()` dalam urutan ID, pengurangan stok bersyarat mencegah stok negatif, dan operasi diulang secara terbatas ketika terjadi deadlock. Nomor transaksi menggunakan UUID dan unique index.

Setiap pengajuan menggunakan token idempotency unik. Pengiriman ulang token dengan keranjang setara oleh pengguna yang sama mengembalikan transaksi yang sudah tersimpan tanpa mengurangi stok kembali. Pemakaian token untuk keranjang atau pengguna berbeda ditolak. **Form baru menghasilkan token baru dan dapat mencatat penjualan berbeda**; idempotency bukan pembatas untuk semua keranjang yang kebetulan sama.

Form menolak jumlah kosong, pecahan, nol, negatif, atau melebihi stok. Estimasi tidak valid diberi petunjuk perbaikan dan tombol simpan tetap terkunci selama pengiriman. Total browser adalah estimasi; backend tetap memeriksa semua nilai. Perhitungan mengikuti harga produk dalam rupiah utuh dan kapasitas kolom database.

Tanggal pada daftar dan detail ditampilkan dalam **Asia/Jakarta (WIB)**. Konversi hanya dilakukan pada lapisan presentasi; timezone global aplikasi tetap UTC dan nilai tersimpan tidak diubah. Alur penjualan juga telah diverifikasi manual pada lingkungan pengembangan lokal.

Transaksi baru mencatat `user_id` dan `cabang_id` dari akun yang login, bukan dari input browser. Cabang transaksi menyimpan riwayat saat penjualan dan tidak berubah ketika penempatan Kasir dipindahkan. Transaksi lama tetap utuh dengan relasi nullable, tanpa menebak pengguna/cabang; aksesnya tetap untuk Admin. Fingerprint idempotency lama dipertahankan.

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

Setelah target database kosong dan konfigurasi privat dipastikan, provisioning dapat dilakukan secara eksplisit dengan `php artisan db:seed --class=DatabaseSeeder`. Ini bukan langkah otomatis, dan bukan cara mereset password pada database existing. Setelah login sebagai Admin, buat master Cabang terlebih dahulu, lalu gunakan menu **Akun Kasir** untuk membuat akun. Form menampilkan petunjuk dan menonaktifkan penyimpanan jika belum ada Cabang. Tidak ada Cabang atau Kasir yang dibuat otomatis.

Provisioning role dipisahkan dari pembuatan akun melalui `EnsurePosRoles`. Instalasi baru melalui seeder Admin menyiapkan role `admin/web` dan `kasir/web`. Pada instalasi existing yang kehilangan role, command berikut hanya menambahkan role yang belum tersedia, tanpa membuat akun atau mengubah role/penugasan yang sudah ada:

```powershell
php artisan runway:provision-roles
```

Command bersifat idempotent. Halaman daftar Kasir tetap dapat dibuka meskipun role Kasir belum diprovision, tanpa melakukan penulisan otomatis pada GET.

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

Hasil regresi terakhir untuk finalisasi UI/UX master data dan perbaikan provisioning role Kasir:

| Suite | Test lulus | Assertions |
| --- | ---: | ---: |
| Regresi backend dan presentasi pada SQLite `:memory:` | 233 | 1.416 |
| Fondasi Kasir dan provisioning role pada MariaDB terisolasi dengan rollback | 5 | 41 |
| JavaScript form dengan DOM fixture | 17 | Tidak dihitung oleh Node sebagai assertions terpisah |

**Pengujian manual yang dikonfirmasi (21 Oktober 2026):**

- Login Admin berhasil; dashboard, Cabang, dan Akun Kasir dapat diakses Admin.
- Cabang dan akun Kasir uji berhasil dibuat melalui antarmuka Admin, kemudian login Kasir berhasil.
- Akses langsung Kasir ke Dashboard, Produk, Cabang, dan Akun Kasir menghasilkan HTTP 403.
- Penjualan manual melalui Admin dan Kasir berhasil; stok berkurang masing-masing satu unit dan transaksi Kasir tampil pada riwayatnya.
- Audit database read-only memverifikasi atribusi pengguna/cabang transaksi baru, kesesuaian jumlah dan total, pelestarian transaksi lama, serta tidak adanya duplikasi yang tidak diharapkan.

Pengujian penonaktifan Kasir dilaporkan telah dilakukan. Detail penghentian session lama dan penolakan login ulang belum diverifikasi secara independen pada audit ini, sehingga tidak dinyatakan lulus secara manual. Identitas akun uji dan data bisnis lokal tidak disertakan dalam dokumentasi.

Regresi SQLite mencakup autentikasi Admin/Kasir, registrasi yang dinonaktifkan, akun nonaktif dan session lama, validasi pengelolaan Kasir, pembatasan endpoint Admin, kepemilikan transaksi, manipulasi input, data lama, idempotency, dan enam master data. Lima test MariaDB terbaru memeriksa skema/foreign key native, kepemilikan transaksi, idempotency, stok, rollback, akun nonaktif, dan provisioning role idempotent dengan perubahan fixture yang di-rollback. Test regresi juga memastikan halaman Kasir aman saat role belum tersedia serta penugasan Admin tetap utuh.

Suite MariaDB transaksi dari Tahap 3 juga tersedia untuk CRUD enam master, PDF, harga backend, nomor unik, dua proses dengan token sama, perebutan stok, serta deadlock InnoDB nyata dan retry. Pengujian konkurensi tersebut menggunakan proses PHP dan koneksi terpisah, bukan simulasi SQLite; suite itu tidak diulang dalam finalisasi UI/UX ini karena membersihkan fixture existing.

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

php vendor/pestphp/pest/bin/pest --no-coverage --do-not-cache-result
node --test tests/Frontend/transaction-form.test.cjs
```

`tests/TestCase.php` menolak database di luar SQLite `:memory:`, lingkungan non-testing, dan konfigurasi cache sebelum setup database test. Suite backend yang lebih luas tersedia pada `tests/Feature/ProdukKategoriTest.php`, `MasterDataCrudTest.php`, `TransaksiTest.php`, `TransaksiIdempotencyTest.php`, dan `CashierFoundationTest.php`; suite Feature menggunakan `RefreshDatabase` dan membentuk skema sementara hanya dalam memori.

### MariaDB terpisah

Suite MariaDB menggunakan `phpunit.mariadb.xml`, bukan mengubah guard SQLite. Siapkan database **`db_runway_testing`** yang hanya berisi fixture disposable dan akun lokal **`runway_testing`** dengan hak akses hanya pada database tersebut, tanpa grant option atau akses ke database bisnis.

Buat `.env.mariadb-testing` secara privat; file ini diabaikan Git. Guard membutuhkan `APP_ENV=testing`, `DB_CONNECTION=mariadb`, `DB_HOST=127.0.0.1`, `DB_DATABASE=db_runway_testing`, `DB_USERNAME=runway_testing`, dan `RUNWAY_MARIADB_TESTS=1`. Isi `DB_PORT`, password database dan APP_KEY khusus testing secara privat; `DB_URL` dan `DB_SOCKET` harus kosong. Nama akun pada guard adalah allowlist teknis, bukan kredensial yang dapat digunakan tanpa konfigurasi privat.

Pada database testing baru saja, `php tests/MariaDb/migrate.php` memverifikasi nama database aktif dan akun sebelum menyiapkan skema. Jangan menjalankan inisialisasi ini pada database bisnis. Setelah skema testing tersedia, pengujian fondasi Kasir yang mempertahankan baris existing dijalankan dengan:

```powershell
php vendor/phpunit/phpunit/phpunit -c phpunit.mariadb.xml tests/MariaDb/CashierFoundationTest.php --do-not-cache-result
```

Runner hanya memuat environment testing, memeriksa `SELECT DATABASE()` dan akun aktif, menolak URL/config override yang berbahaya, serta membuktikan akses akun testing ke database utama ditolak. `CashierFoundationTest` memakai transaksi dan rollback tanpa menghapus baris existing. Suite MariaDB lama membersihkan fixture pada `db_runway_testing` setiap test; jangan menjalankan seluruh suite pada data yang harus dipertahankan. Suite penuh juga memuat pengujian perubahan skema kategori/idempotency dan hanya boleh dijalankan dengan izin perubahan skema serta pembersihan fixture testing.

Seluruh suite default SQLite lulus; pengujian registrasi dan penghapusan akun telah diselaraskan dengan kebijakan POS internal. Build frontend, pemeriksaan sintaks/route, dan diff juga diverifikasi. Hasil otomatis tidak menggantikan pengujian manual melalui browser. Alur utama Kasir telah dikonfirmasi sebagaimana dicatat di atas; verifikasi visual desktop/mobile menyeluruh serta detail pengujian penonaktifan masih memerlukan konfirmasi.

## Status dan rencana pengembangan

- **Tersedia:** enam master, penjualan atomik, pengurangan stok global, idempotency, detail/riwayat, PDF produk, presentasi WIB, pengelolaan Kasir, provisioning role idempotent, status aktif/nonaktif, atribusi pengguna/cabang transaksi, serta UI master data yang konsisten.
- **Akses saat ini:** Admin mengelola sistem; Kasir aktif dengan Cabang dapat menjual dan hanya melihat transaksi miliknya. Label `petugas.role` tidak memberikan hak akses akun login. Registrasi publik dinonaktifkan.
- **Belum selesai:** verifikasi rinci penonaktifan Kasir (session lama dan login ulang), review visual desktop/mobile menyeluruh, serta validasi seluruh skenario operasional end-to-end.
- **Direncanakan:** stok per cabang, isolasi operasional dan laporan multi-cabang, pembayaran/kembalian, struk, retur, shift, dan pelaporan operasional.

Keberadaan master Cabang belum berarti isolasi multi-cabang sudah diterapkan. Project belum dinyatakan siap produksi.

## Database existing dan keamanan repository

Migration korektif kategori, idempotency, dan fondasi Kasir disertakan serta telah diuji pada MariaDB terisolasi. Migration fondasi Kasir mempertahankan data lama, memberi default akun aktif, dan menggunakan foreign key dengan pembatasan penghapusan. Relasi nullable tidak diisi otomatis; rollback fondasi Kasir akan menghapus metadata kolom baru sehingga tidak boleh digunakan sebagai pemulihan otomatis. Untuk database existing, lakukan backup terverifikasi, audit pending migration, dan penerapan terbatas yang disetujui. Jangan menggunakan reset, seeding, atau `migrate:fresh` pada data bisnis. Rollback kategori menolak penyempitan otomatis; rollback idempotency menolak penghapusan kolom bila token sudah digunakan.

Database lokal, data penjualan, dump SQL, backup, `.env`, `.env.mariadb-testing`, password, APP_KEY, token autentikasi, dan konfigurasi privat tidak disertakan. `.env.example` hanya template. Simpan backup di luar repository; file session, log, dan artefak pengujian lokal juga diabaikan Git. Tidak ada kredensial demo yang perlu dipublikasikan untuk menjalankan project.
