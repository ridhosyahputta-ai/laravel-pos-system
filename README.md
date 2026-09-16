# Sistem Kasir (Laravel POS System)

**[Bahasa Indonesia](#bahasa-indonesia) | [English](#english)**

---

## Bahasa Indonesia

Aplikasi kasir (Point of Sale) berbasis web untuk mengelola data master toko dan mencatat transaksi penjualan secara real-time, lengkap dengan validasi stok otomatis.

### Fitur

- **Dashboard** — ringkasan aktivitas toko.
- **Manajemen Data Master (CRUD)**
  - Produk — dengan relasi ke Kategori dan Supplier
  - Kategori
  - Supplier / Pemasok
  - Pelanggan
  - Cabang
  - Petugas
- **Transaksi Penjualan**
  - Form transaksi multi-produk dalam satu kali checkout
  - Validasi stok di sisi server (tidak bisa menjual melebihi stok yang tersedia)
  - Proses penyimpanan atomik menggunakan database transaction — jika satu produk gagal (stok tidak cukup), seluruh transaksi dibatalkan (rollback), tidak ada data yang tersimpan setengah jalan
  - Riwayat transaksi dan halaman detail rincian per transaksi
- **Export PDF** — cetak data produk ke PDF (DomPDF).
- **Autentikasi & Role** — login/register (Laravel Breeze) dengan proteksi role admin pada seluruh menu manajemen.

### Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL
- **Frontend**: Blade Templating, Tailwind CSS, Alpine.js
- **PDF Generator**: barryvdh/laravel-dompdf
- **Role & Permission**: spatie/laravel-permission
- **Build Tool**: Vite

### Instalasi

1. Clone repository dan masuk ke folder project:
   ```bash
   git clone <url-repo-ini>
   cd laravel-pos-system
   ```

2. Install dependency PHP:
   ```bash
   composer install
   ```

3. Install dependency JavaScript:
   ```bash
   npm install
   ```

4. Salin file environment dan generate application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. Sesuaikan konfigurasi database di file `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), lalu buat database-nya di MySQL.

6. Jalankan migrasi:
   ```bash
   php artisan migrate
   ```

7. Build asset frontend:
   ```bash
   npm run build
   ```

8. Jalankan server:
   ```bash
   php artisan serve
   ```

   Aplikasi dapat diakses di `http://localhost:8000`.

   Untuk mode pengembangan (dengan hot-reload asset), jalankan sebagai gantinya:
   ```bash
   composer run dev
   ```

---

## English

A web-based Point of Sale (POS) application for managing a store's master data and recording sales transactions in real time, complete with automatic stock validation.

### Features

- **Dashboard** — store activity overview.
- **Master Data Management (CRUD)**
  - Products — with relations to Category and Supplier
  - Categories
  - Suppliers
  - Customers
  - Branches
  - Staff
- **Sales Transactions**
  - Multi-product checkout form in a single transaction
  - Server-side stock validation (prevents selling more than the available stock)
  - Atomic persistence using a database transaction — if one product fails (insufficient stock), the entire transaction is rolled back and no partial data is saved
  - Transaction history and per-transaction detail pages
- **PDF Export** — export product data to PDF (DomPDF).
- **Authentication & Roles** — login/register (Laravel Breeze) with admin-role protection across all management menus.

### Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL
- **Frontend**: Blade Templating, Tailwind CSS, Alpine.js
- **PDF Generator**: barryvdh/laravel-dompdf
- **Role & Permission**: spatie/laravel-permission
- **Build Tool**: Vite

### Installation

1. Clone the repository and enter the project folder:
   ```bash
   git clone <this-repo-url>
   cd laravel-pos-system
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install JavaScript dependencies:
   ```bash
   npm install
   ```

4. Copy the environment file and generate the application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. Configure the database connection in `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), then create the database in MySQL.

6. Run the migrations:
   ```bash
   php artisan migrate
   ```

7. Build the frontend assets:
   ```bash
   npm run build
   ```

8. Start the server:
   ```bash
   php artisan serve
   ```

   The application will be available at `http://localhost:8000`.

   For development mode (with asset hot-reload), run this instead:
   ```bash
   composer run dev
   ```
