# Sistem Permohonan dan Monitoring Layanan PLN UP3 Kudus

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Filament-3.0-2B6CB0?style=for-the-badge&logo=filament&logoColor=white" alt="Filament">
  <img src="https://img.shields.io/badge/TailwindCSS-3.4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="TailwindCSS">
</p>

---

## 📌 Informasi Proyek

**Nama Proyek:**
Sistem Permohonan dan Monitoring Layanan PLN UP3 Kudus

**Nama Anggota:**

- Hasan Suryadharma — 24060123140208
- Muhammad Affan Firdaus — 24060123140209

**Dosen Pembimbing:**

- Dr. Aris Puji Widodo, S.Si., M.T.
- NIP. 197404011999031002

**Institusi:**

- Informatika | Fakultas Sains dan Matematika | Universitas Diponegoro

**Program:**
Praktek Kerja Lapangan (PKL) PLN UP3 Kudus

---

## 📖 Deskripsi Proyek

Sistem Permohonan dan Monitoring Layanan PLN UP3 Kudus merupakan aplikasi berbasis web yang dikembangkan untuk mendukung digitalisasi layanan pelanggan PLN, khususnya layanan Pasang Baru dan Tambah Daya.

Sistem ini menyediakan:

- Proses pengajuan layanan yang terstandarisasi
- Monitoring progres secara real-time
- Estimasi waktu penyelesaian pada setiap tahapan layanan
- Notifikasi perubahan status
- Pembayaran digital terintegrasi menggunakan QRIS

Konsep monitoring yang diterapkan mengadopsi pengalaman pelacakan layanan seperti marketplace sehingga pelanggan dapat mengetahui perkembangan permohonan secara transparan dari awal hingga selesai.

---

## 🎯 Latar Belakang

Proses permohonan layanan pelanggan PLN UP3 Kudus, khususnya Pasang Baru dan Tambah Daya, masih belum memberikan visibilitas progres yang transparan kepada pelanggan. Diperlukan sistem monitoring real-time dengan konsep pelacakan seperti marketplace yang menampilkan status terkini serta estimasi waktu pada setiap tahapan proses untuk meningkatkan kepastian dan kepercayaan pelanggan.

Variasi format dan kelengkapan data permohonan sering menimbulkan ketidakkonsistenan dalam pengelolaan layanan. Oleh karena itu, diperlukan sistem yang mampu menstandarkan proses pengajuan dan dokumentasi permohonan sehingga data lebih akurat, terstruktur, dan mudah ditindaklanjuti sekaligus mendukung pelayanan pelanggan yang lebih cepat dan efektif.

---

## 🎯 Tujuan

1. **Menyediakan sistem permohonan Pasang Baru dan Tambah Daya** yang mudah digunakan (user-friendly) dengan formulir yang terstandarisasi.
2. **Memberikan transparansi layanan** melalui monitoring real-time.
3. **Membantu koordinasi antar unit kerja PLN** agar proses penanganan permohonan lebih terstruktur dan efisien.
4. **Meningkatkan kualitas layanan pelanggan** melalui layanan digital yang praktis dan transparan.

---

## ✨ Fitur Utama

### 1. Pengajuan Layanan Online

- Pengajuan Pasang Baru
- Pengajuan Tambah Daya
- Formulir digital terstandarisasi
- Upload dokumen persyaratan

### 2. Monitoring Progres Real-Time

- Monitoring status permohonan
- Timeline progres layanan
- Riwayat aktivitas permohonan
- Tracking layaknya marketplace

### 3. Estimasi Waktu Layanan

- Estimasi waktu setiap tahapan
- Informasi SLA proses layanan
- Transparansi progres pekerjaan

### 4. Notifikasi Otomatis

- Perubahan status layanan
- Informasi proses terbaru
- Notifikasi aktivitas permohonan

### 5. Dashboard Internal PLN

- Dashboard monitoring
- Manajemen permohonan
- Monitoring progres unit kerja
- Pengelolaan data pelanggan

### 6. Pembayaran Digital Terintegrasi

- QRIS Payment
- Verifikasi pembayaran
- Monitoring transaksi
- Status pembayaran real-time

---

## 🔄 Metode Pengembangan

Metode **Agile** digunakan karena memungkinkan pengembangan sistem dilakukan secara iteratif dan fleksibel sesuai kebutuhan pengguna.

### Tahapan Pengembangan

#### 🔍 Analisis Kebutuhan

- Analisis proses bisnis PLN
- Identifikasi kebutuhan pelanggan
- Identifikasi kebutuhan monitoring real-time

#### 🎨 Perancangan Sistem

- Perancangan database
- Perancangan UI/UX
- Perancangan arsitektur sistem
- Perancangan alur bisnis

#### ⚙️ Pengembangan Fitur

- Modul permohonan layanan
- Modul monitoring
- Modul pembayaran
- Modul notifikasi
- Dashboard internal

#### 🧪 Pengujian dan Evaluasi

- Functional Testing
- Black Box Testing
- User Acceptance Testing

#### 🚀 Implementasi dan Penyempurnaan

- Deployment sistem
- Evaluasi pengguna
- Perbaikan dan penyempurnaan

---

## 📋 Daftar Isi (Table of Contents)

1. [Teknologi](#teknologi)
2. [Persyaratan Sistem](#persyaratan-sistem)
3. [Instalasi](#instalasi)
4. [Konfigurasi](#konfigurasi)
5. [Struktur Database](#struktur-database)
6. [Struktur Folder](#struktur-folder)
7. [API Endpoint](#api-endpoint)
8. [Panduan QRIS Simulator](#panduan-qris-simulator)
9. [Panduan Ngrok](#panduan-ngrok)
10. [Panduan Pengguna](#panduan-pengguna)
11. [Panduan Pegawai Internal PLN](#panduan-pegawai-internal-pln)
12. [Testing](#testing)
13. [Deployment](#deployment)
14. [Troubleshooting](#troubleshooting)
15. [Dokumentasi PKL](#dokumentasi-pkl)
16. [Hasil Implementasi](#hasil-implementasi)
17. [Dampak Utama Sistem](#dampak-utama-sistem)
18. [Kesimpulan](#kesimpulan)
19. [Tim Pengembang](#tim-pengembang)
20. [Changelog](#changelog)
21. [Future Development](#future-development)
22. [Lisensi](#lisensi)

---

## Teknologi

### Backend Stack

| Teknologi | Versi | Deskripsi                 |
| --------- | ----- | ------------------------- |
| PHP       | 8.2+  | Bahasa pemrograman server |
| Laravel   | 12.x  | Framework PHP             |
| Filament  | 3.x   | Admin Panel Builder       |
| MySQL     | 8.x   | Database                  |

### Frontend Stack

| Teknologi   | Versi   | Deskripsi            |
| ----------- | ------- | -------------------- |
| TailwindCSS | 3.4     | CSS Framework        |
| Alpine.js   | 3.x     | JavaScript Framework |
| Blade       | Laravel | Template Engine      |
| Vite        | 5.x     | Build Tool           |

### Package/Dependency

| Package        | Versi | Fungsi             |
| -------------- | ----- | ------------------ |
| simple-qrcode  | 4.2   | Generate QR Code   |
| laravel/sail   | 1.41  | Testing Assertions |
| fakerphp/faker | 1.23  | Data Seeding       |

### Integrasi Sistem

- REST API
- QRIS Simulator
- Ngrok (untuk development)

---

## Persyaratan Sistem

### Minimum Requirements

| Komponen       | Spesifikasi                     |
| -------------- | ------------------------------- |
| **PHP**        | 8.2 atau lebih tinggi           |
| **Composer**   | 2.x atau lebih tinggi           |
| **Node.js**    | 20.x LTS atau lebih tinggi      |
| **NPM**        | 10.x atau lebih tinggi          |
| **MySQL**      | 8.0 atau lebih tinggi           |
| **Git**        | 2.x atau lebih tinggi           |
| **Web Server** | Apache/Nginx (untuk production) |

### Recommended

| Komponen     | Spesifikasi        |
| ------------ | ------------------ |
| **PHP**      | 8.3                |
| **Composer** | 2.8+               |
| **Node.js**  | LTS (v20.x)        |
| **RAM**      | 4GB atau lebih     |
| **Storage**  | 2GB atau lebih     |
| **IDE**      | Visual Studio Code |

### Eksternal

| Tool                | Keterangan                        |
| ------------------- | --------------------------------- |
| **Ngrok**           | Untuk QRIS callback (development) |
| **MySQL Workbench** | Database GUI (opsional)           |
| **Postman**         | API Testing (opsional)            |

---

## Instalasi

### Clone Project

```bash
# Clone repository
git clone https://github.com/Hasansurya321/Sistem-Permohonan-dan-Monitoring-Layanan-PLN-UP3-Kudus.git

# Masuk ke direktori project
cd PLN-Monitoring-Backup-B
```

### Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### Konfigurasi Environment

```bash
# Copy file .env
copy .env.example .env
```

Edit file `.env` sesuai konfigurasi database Anda:

```env
APP_NAME="PLN Monitoring"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pln_monitoring
DB_USERNAME=root
DB_PASSWORD=

# QRIS Configuration
QRIS_EXPIRY_SECONDS=120
NGROK_URL=https://your-ngrok-url.ngrok-free.app
```

### Generate APP_KEY

```bash
php artisan key:generate
```

### Membuat Database

```sql
-- Login ke MySQL
mysql -u root -p

-- Buat database baru
CREATE DATABASE pln_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Exit
EXIT;
```

### Menjalankan Migration

```bash
php artisan migrate
```

### Menjalankan Seeder

```bash
# Jalankan semua seeder
php artisan db:seed

# Atau jalankan seeder spesifik
php artisan db:seed --class=InternalUsersSeeder
php artisan db:seed --class=MasterPelangganSeeder
php artisan db:seed --class=MasterSloSeeder
php artisan db:seed --class=FinalDemoSeeder
```

### Build Asset Frontend

```bash
# Development
npm run dev

# Production
npm run build
```

### Menjalankan Aplikasi

```bash
# Development Server
php artisan serve

# Akses aplikasi
# http://localhost:8000
```

### Akses Panel Admin

| Panel                | URL               | Credential                               |
| -------------------- | ----------------- | ---------------------------------------- |
| **Admin Layanan**    | /admin-layanan    | affan@adminlayanan.com / Password123!    |
| **Unit Survey**      | /unit-survey      | budi@unitsurvey.com / Password123!       |
| **Unit Perencanaan** | /unit-perencanaan | citra@unitperencanaan.com / Password123! |
| **Unit Konstruksi**  | /unit-konstruksi  | dedi@unitkonstruksi.com / Password123!   |
| **Unit TE**          | /unit-te          | eka@unitte.com / Password123!            |

### Akses Pelanggan

1. Buka `http://localhost:8000`
2. Pilih menu "Daftar" untuk registrasi
3. Atau gunakan akun demo:
    - Email: pelanggan1@kudus.id
    - Password: Password123!

---

## Konfigurasi

### Penjelasan Variabel .env

| Variabel              | Deskripsi                      | Contoh                     |
| --------------------- | ------------------------------ | -------------------------- |
| `APP_NAME`            | Nama aplikasi                  | PLN Monitoring             |
| `APP_ENV`             | Environment (local/production) | local                      |
| `APP_KEY`             | Laravel application key        | base64:xxxxx               |
| `APP_DEBUG`           | Debug mode                     | true/false                 |
| `APP_URL`             | URL utama aplikasi             | http://localhost           |
| `APP_TIMEZONE`        | Timezone                       | Asia/Jakarta               |
| `DB_CONNECTION`       | Tipe database                  | mysql                      |
| `DB_HOST`             | Host database                  | 127.0.0.1                  |
| `DB_PORT`             | Port database                  | 3306                       |
| `DB_DATABASE`         | Nama database                  | pln_monitoring             |
| `DB_USERNAME`         | Username database              | root                       |
| `DB_PASSWORD`         | Password database              |                            |
| `MAIL_MAILER`         | Driver email                   | log/smtp                   |
| `QUEUE_CONNECTION`    | Queue driver                   | sync                       |
| `LOG_CHANNEL`         | Log channel                    | stack                      |
| `FILESYSTEM_DISK`     | Storage driver                 | local                      |
| `QRIS_EXPIRY_SECONDS` | QRIS expiry time               | 120                        |
| `NGROK_URL`           | Ngrok public URL               | https://xxx.ngrok-free.app |

### Konfigurasi Ngrok (Penting!)

Untuk QRIS callback berfungsi:

1. **Daftar Akun Ngrok**
    - Kunjungi https://ngrok.com
    - Buat akun dan dapatkan authtoken

2. **Konfigurasi Ngrok**

    ```bash
    ngrok config add-authtoken YOUR_AUTH_TOKEN
    ```

3. **Update .env**

    ```env
    NGROK_URL=https://your-ngrok-url.ngrok-free.app
    ```

4. **Jalankan Ngrok**

    ```bash
    ngrok http 8000
    ```

### Konfigurasi Roles

Roles pegawai didefinisikan di `config/internal_roles.php`:

| Role             | Panel            | Akses             |
| ---------------- | ---------------- | ----------------- |
| admin_pelayanan  | admin-layanan    | Full access       |
| unit_survey      | unit-survey      | Survey only       |
| unit_perencanaan | unit-perencanaan | Planning only     |
| unit_konstruksi  | unit-konstruksi  | Construction only |
| unit_te          | unit-te          | Termination only  |
| supervisor       | admin-layanan    | Full access       |

---

## Struktur Database

### Entity Relationship Diagram (ERD)

```
┌─────────────────┐       ┌─────────────────────────┐       ┌─────────────────┐
│     users       │       │   service_requests      │       │  master_pelanggan│
├─────────────────┤       ├─────────────────────────┤       ├─────────────────┤
│ id (PK)         │──────▶│ submitter_user_id (FK)  │       │ id (PK)         │
│ name            │       │ applicant_id (FK)       │       │ user_id (FK)    │
│ email           │       │ id (PK)                 │       │ nik             │
│ password        │       │ nomor_permohonan        │       │ nama_lengkap    │
│ role            │       │ jenis_layanan           │       │ id_pelanggan_12 │
│ nik             │       │ status                  │       │ no_meter        │
│ is_active       │       │ status_detail           │       │ alamat          │
└─────────────────┘       │ payment_status          │       └─────────────────┘
        │                   │ ...                     │
        │                   └────────────┬────────────┘
        │                              │
        ▼                              ▼
┌─────────────────┐       ┌─────────────────────────┐
│   employees     │       │ service_request_events │
├─────────────────┤       ├─────────────────────────┤
│ id (PK)         │       │ id (PK)                 │
│ name            │       │ service_request_id (FK) │
│ email           │       │ status                  │
│ password        │       │ status_detail           │
│ role            │       │ title                   │
│ is_active       │       │ description             │
└─────────────────┘       │ occurred_at             │
                          │ updated_by_name         │
                          └─────────────────────────┘

                                  ┌─────────────────────────┐
                                  │       payments         │
                                  ├─────────────────────────┤
                                  │ id (PK)                 │
                                  │ service_request_id (FK) │
                                  │ amount                  │
                                  │ status                  │
                                  │ payment_token           │
                                  │ ref_no                  │
                                  │ expired_at              │
                                  │ paid_at                 │
                                  └─────────────────────────┘

                                  ┌─────────────────────────┐
                                  │   applicant_identities  │
                                  ├─────────────────────────┤
                                  │ id (PK)                 │
                                  │ user_id (FK)            │
                                  │ nik                     │
                                  │ nama_lengkap            │
                                  │ no_kk                   │
                                  │ no_hp                   │
                                  │ foto_ktp_selfie         │
                                  │ foto_bangunan           │
                                  └─────────────────────────┘
```

### Tabel Utama

#### **users**

| Kolom     | Tipe    | Deskripsi       |
| --------- | ------- | --------------- |
| id        | bigint  | Primary key     |
| name      | varchar | Nama lengkap    |
| email     | varchar | Email (unique)  |
| password  | varchar | Hashed password |
| role      | enum    | pelanggan/admin |
| nik       | varchar | NIK (16 digit)  |
| is_active | boolean | Status aktif    |

#### **service_requests**

| Kolom             | Tipe    | Deskripsi                  |
| ----------------- | ------- | -------------------------- |
| id                | bigint  | Primary key                |
| nomor_permohonan  | varchar | Nomor unik permohonan      |
| submitter_user_id | bigint  | FK ke users                |
| applicant_id      | bigint  | FK ke applicant_identities |
| jenis_layanan     | enum    | PASANG_BARU/TAMBAH_DAYA    |
| status            | enum    | Status workflow            |
| status_detail     | enum    | Detail status              |
| daya_baru         | int     | Daya yang diminta (VA)     |
| payment_status    | varchar | Status pembayaran          |
| is_draft          | boolean | Draft flag                 |

#### **service_request_events**

| Kolom              | Tipe      | Deskripsi              |
| ------------------ | --------- | ---------------------- |
| id                 | bigint    | Primary key            |
| service_request_id | bigint    | FK ke service_requests |
| status             | varchar   | Status global          |
| status_detail      | varchar   | Status detail          |
| title              | varchar   | Judul event            |
| description        | text      | Deskripsi              |
| occurred_at        | timestamp | Kapan terjadi          |

#### **payments**

| Kolom              | Tipe      | Deskripsi                      |
| ------------------ | --------- | ------------------------------ |
| id                 | bigint    | Primary key                    |
| service_request_id | bigint    | FK ke service_requests         |
| amount             | decimal   | Jumlah tagihan                 |
| status             | enum      | PENDING/SUCCESS/FAILED/EXPIRED |
| payment_token      | varchar   | Token unik                     |
| expired_at         | timestamp | Batas waktu                    |
| paid_at            | timestamp | Waktu bayar                    |

### Sistem Workflow

| Tahapan             | Unit Responsible | Status Detail                                                                       |
| ------------------- | ---------------- | ----------------------------------------------------------------------------------- |
| Verifikasi Data     | Admin Layanan    | Menunggu Verifikasi, Verifikasi Sukses, Dikembalikan, Ditolak, Administrasi Selesai |
| Unit Survey         | Unit Survey      | Diterima, Dijadwalkan, Survey Lapangan, Sukses, Gagal, Selesai                      |
| Unit Perencanaan    | Unit Perencanaan | Diterima, Analisa Material, Cek Ketersediaan, Material Tersedia/Menunggu, Selesai   |
| Pembayaran          | Pelanggan        | Tagihan Terbit, Menunggu, Pending, Sukses, Gagal, Selesai                           |
| Unit Konstruksi     | Unit Konstruksi  | Diterima, Dijadwalkan, Pembangunan Jaringan, Berhasil, Gagal, Selesai               |
| Unit Penyalaan (TE) | Unit TE          | Diterima, Dijadwalkan, Berhasil, Gagal, Selesai                                     |
| Selesai             | Sistem           | Close, Penolakan, Pembayaran Gagal                                                  |

---

## Struktur Folder

```
PLN-Monitoring-Backup-B/
├── app/
│   ├── Console/Commands/      # Artisan commands
│   ├── Enums/                  # PHP Enums
│   ├── Events/                 # Event classes
│   ├── Filament/               # Filament Admin Panels
│   │   ├── AdminLayanan/       # Main admin panel
│   │   ├── UnitSurvey/
│   │   ├── UnitPerencanaan/
│   │   ├── UnitKonstruksi/
│   │   └── UnitTe/
│   ├── Helpers/               # Helper functions
│   ├── Http/Controllers/       # Controllers
│   │   ├── Auth/              # Authentication
│   │   └── Admin/             # Admin controllers
│   ├── Listeners/             # Event listeners
│   ├── Livewire/             # Livewire components
│   ├── Mail/                 # Email templates
│   ├── Models/               # Eloquent models
│   ├── Notifications/         # Notification classes
│   ├── Providers/            # Service providers
│   ├── Services/            # Business logic services
│   └── Support/             # Support classes
├── bootstrap/                # Laravel bootstrap
├── config/                   # Configuration files
├── database/
│   ├── factories/           # Model factories
│   ├── migrations/         # Database migrations
│   └── seeders/           # Database seeders
├── public/                  # Public assets
├── resources/
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript
│   └── views/             # Blade templates
├── routes/                 # Route definitions
├── storage/               # Storage (uploads, logs)
├── tests/                 # Test cases
├── vendor/                # Dependencies
├── composer.json
├── package.json
└── README.md
```

### Fungsi Direktori

| Direktori               | Fungsi                                          |
| ----------------------- | ----------------------------------------------- |
| `app/Console/Commands/` | Artisan CLI commands untuk tugas-tugas tertentu |
| `app/Enums/`            | Definisi status dan tipe data konstan           |
| `app/Filament/`         | Panel admin berbasis Filament                   |
| `app/Http/Controllers/` | Logika bisnis untuk HTTP requests               |
| `app/Models/`           | Model Eloquent untuk database tables            |
| `app/Services/`         | Business logic classes                          |
| `resources/views/`      | Blade template files                            |
| `storage/app/public/`   | File uploads                                    |
| `storage/logs/`         | Application logs                                |
| `tests/Feature/`        | Feature/integration tests                       |

---

## API Endpoint

### Authentication Endpoints

#### Pelanggan

| Method | Endpoint              | Deskripsi          |
| ------ | --------------------- | ------------------ |
| GET    | `/pelanggan/login`    | Halaman login      |
| POST   | `/pelanggan/login`    | Proses login       |
| GET    | `/pelanggan/register` | Halaman registrasi |
| POST   | `/pelanggan/register` | Proses registrasi  |
| POST   | `/pelanggan/logout`   | Logout             |
| GET    | `/aktivasi/{token}`   | Aktivasi akun      |

#### Pegawai

| Method | Endpoint          | Deskripsi     |
| ------ | ----------------- | ------------- |
| GET    | `/pegawai/login`  | Halaman login |
| POST   | `/pegawai/login`  | Proses login  |
| POST   | `/pegawai/logout` | Logout        |

### Permohonan Endpoints

#### Pasang Baru

| Method | Endpoint                       | Deskripsi     |
| ------ | ------------------------------ | ------------- |
| GET    | `/pelanggan/pasang-baru/step1` | Step 1        |
| POST   | `/pelanggan/pasang-baru/step1` | Simpan Step 1 |
| GET    | `/pelanggan/pasang-baru/step2` | Step 2        |
| POST   | `/pelanggan/pasang-baru/step2` | Simpan Step 2 |
| GET    | `/pelanggan/pasang-baru/step3` | Step 3        |
| POST   | `/pelanggan/pasang-baru/step3` | Simpan Step 3 |
| GET    | `/pelanggan/pasang-baru/step4` | Step 4        |
| POST   | `/pelanggan/pasang-baru/step4` | Simpan Step 4 |
| GET    | `/pelanggan/pasang-baru/step5` | Step 5        |
| POST   | `/pelanggan/pasang-baru/step5` | Submit        |

#### Tambah Daya

| Method | Endpoint                       | Deskripsi     |
| ------ | ------------------------------ | ------------- |
| GET    | `/pelanggan/tambah-daya/step1` | Step 1        |
| POST   | `/pelanggan/tambah-daya/step1` | Simpan Step 1 |
| GET    | `/pelanggan/tambah-daya/step2` | Step 2        |
| POST   | `/pelanggan/tambah-daya/step2` | Simpan Step 2 |
| GET    | `/pelanggan/tambah-daya/step3` | Step 3        |
| POST   | `/pelanggan/tambah-daya/step3` | Simpan Step 3 |
| GET    | `/pelanggan/tambah-daya/step4` | Step 4        |
| POST   | `/pelanggan/tambah-daya/step4` | Simpan Step 4 |
| GET    | `/pelanggan/tambah-daya/step5` | Step 5        |
| POST   | `/pelanggan/tambah-daya/step5` | Submit        |

### QRIS/Payment Endpoints

| Method | Endpoint               | Deskripsi              |
| ------ | ---------------------- | ---------------------- |
| GET    | `/pay/{token}`         | Tampilkan QR Code      |
| GET    | `/pay/{token}/status`  | Check status (polling) |
| GET    | `/pay/{token}/success` | Callback sukses (GET)  |
| POST   | `/pay/{token}/success` | Callback sukses (POST) |
| POST   | `/pay/{token}/fail`    | Callback gagal         |

### Filament Admin Panels

| Panel            | URL                 |
| ---------------- | ------------------- |
| Admin Layanan    | `/admin-layanan`    |
| Unit Survey      | `/unit-survey`      |
| Unit Perencanaan | `/unit-perencanaan` |
| Unit Konstruksi  | `/unit-konstruksi`  |
| Unit TE          | `/unit-te`          |

---

## Panduan QRIS Simulator

### Overview

Sistem QRIS menggunakan simulator untuk development. Pembayaran dilakukan via QR Code yang di-scan menggunakan HP, kemudian callback diproses melalui Ngrok tunnel.

### Menjalankan QRIS Service

```bash
# Pastikan Ngrok sudah berjalan
ngrok http 8000

# Update NGROK_URL di .env dengan URL dari Ngrok
```

### Endpoint Callback

| Endpoint               | Method   | Fungsi                 |
| ---------------------- | -------- | ---------------------- |
| `/pay/{token}`         | GET      | Tampilkan QR Code      |
| `/pay/{token}/status`  | GET      | Check status (polling) |
| `/pay/{token}/success` | GET/POST | Trigger sukses         |
| `/pay/{token}/fail`    | POST     | Trigger gagal          |

### Simulasi Pembayaran Berhasil

**Metode 1: Scan QR (Recommended)**

1. Buka halaman pembayaran di desktop
2. Scan QR Code dengan HP
3. Browser HP akan terbuka ke URL callback
4. Pembayaran otomatis sukses

**Metode 2: Klik Tombol**

1. Buka `/pay/{token}` di mobile browser
2. Klik tombol "Bayar Sekarang"
3. Pilih "Simulasi Sukses"
4. Status akan berubah otomatis

### Simulasi Pembayaran Gagal

1. Buka `/pay/{token}?simulator=1`
2. Klik tombol "Simulasi Gagal"
3. Attempt counter akan bertambah
4. Setelah 3x gagal → permohonan gagal

### Flowchart QRIS Payment

```
┌─────────────────────────────────────────────────────────────────┐
│                     PAYMENT FLOW                                  │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────┐    ┌──────────────┐    ┌─────────────────────┐  │
│  │ Pelanggan│───▶│ Generate QR  │───▶│ QR Code Display     │  │
│  └──────────┘    └──────────────┘    └─────────┬───────────┘  │
│                              ┌──────────────────┼──────────────┐│
│                              │                  │              ││
│                              ▼                  ▼              ││
│                      ┌──────────────┐    ┌───────────┐    ││
│                      │   Scan QR    │    │ Expired?  │    ││
│                      │   (Mobile)   │    └─────┬─────┘    ││
│                      └──────┬───────┘          │ Yes     ││
│                             │                   ▼         ││
│                             │          ┌────────────────┐  ││
│                             │          │ Handle Expired  │  ││
│                             │          └────────────────┘  ││
│                             ▼                             ││
│                      ┌──────────────┐                      ││
│                      │ User Confirm │                      ││
│                      │  Payment    │                      ││
│                      └──────┬───────┘                      ││
│                             │                              ││
│                        ┌────┴────┐                        ││
│                        │         │                        ││
│                        ▼         ▼                        ││
│                     ┌──────┐ ┌────────┐                  ││
│                     │Success│ │ Failed │                  ││
│                     └──┬───┘ └───┬────┘                  ││
│                        │         │                        ││
│                        ▼         ▼                        ││
│                 ┌──────────────────┐ ┌───────────────┐   ││
│                 │ Update Payment    │ │ Increment     │   ││
│                 │ + Workflow       │ │ Attempt +1    │   ││
│                 └──────────────────┘ └───────┬───────┘   ││
│                                               │           ││
│                                               ▼           ││
│                                      ┌────────────────┐    ││
│                                      │ Attempt >= 3?  │────┘│
│                                      └───────┬────────┘      │
│                                              │               │
│                                      ┌───────┴───────┐      │
│                                      │               │      │
│                                      ▼               ▼      │
│                                 ┌─────────┐    ┌──────────┐   │
│                                 │   Yes   │    │    No    │   │
│                                 └────┬────┘    └────┬─────┘   │
│                                      │              │         │
│                                      ▼              ▼         │
│                             ┌────────────────┐ ┌──────────────┐│
│                             │ Mark as FAILED │ │ Show Retry  ││
│                             │ (SELESAI)      │ │ Option      ││
│                             └────────────────┘ └──────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

---

## Panduan Ngrok

### Apa itu Ngrok?

Ngrok adalah tool untuk membuat tunnel dari internet publik ke localhost. Ini diperlukan agar HP dapat mengakses QRIS callback URL dari luar jaringan lokal.

### Instalasi Ngrok

**Windows:**

1. Download dari https://ngrok.com/download
2. Extract file ngrok.exe
3. Tambahkan ke PATH sistem

**Mac/Linux:**

```bash
# via Homebrew
brew install ngrok
```

### Konfigurasi Awal

```bash
# Login ke Ngrok dashboard
# Dapatkan authtoken dari https://dashboard.ngrok.com/get-started/your-authtoken

# Konfigurasi authtoken
ngrok config add-authtoken YOUR_AUTH_TOKEN
```

### Menjalankan Ngrok

```bash
# Terminal baru - Jalankan Laravel
php artisan serve

# Terminal lain - Jalankan Ngrok
ngrok http 8000
```

### Mendapatkan URL Publik

Setelah Ngrok berjalan, Anda akan melihat:

```
Session Status                online
Account                       your-email@example.com
Forwarding                    https://abc123def456.ngrok-free.app -> http://localhost:8000
```

### Update Konfigurasi

Update file `.env`:

```env
APP_URL=http://localhost
NGROK_URL=https://abc123def456.ngrok-free.app
```

### Testing QRIS Callback via Ngrok

1. **Pastikan Ngrok Berjalan**
2. **Update APP_URL dan NGROK_URL**
3. **Buka QR Payment Page** - Di desktop: buka `/pay/{token}`
4. **Scan dengan HP** - Browser akan terbuka ke URL callback
5. **Verifikasi** - Cek status permohonan di dashboard

### Troubleshooting Ngrok

| Masalah                    | Solusi                                |
| -------------------------- | ------------------------------------- |
| URL berubah setiap restart | Update NGROK_URL di .env              |
| Callback tidak berfungsi   | Pastikan Ngrok status "online"        |
| Connection refused         | Pastikan `php artisan serve` berjalan |

---

## Panduan Pengguna

### Registrasi Akun

**Step 1:** Kunjungi `http://localhost:8000/pelanggan/register`

**Step 2:** Isi Form Registrasi

- Nama lengkap
- Email aktif
- Password (min. 8 karakter)
- Konfirmasi password

**Step 3:** Verifikasi Email

- Buka email dari PLN Monitoring
- Klik link aktivasi

### Login Pelanggan

**Step 1:** Kunjungi `http://localhost:8000/pelanggan/login`

**Step 2:** Masukkan Kredensial

- Email yang terdaftar
- Password

### Pasang Baru - Step by Step

#### Step 1: Pilih Pemohon

- Pilih "Untuk Saya" atau "Orang Lain"

#### Step 2: Detail Lokasi

- Provinsi: Jawa Tengah (default)
- Kabupaten/Kota: Kabupaten Kudus
- Kecamatan, Kelurahan, RT/RW

#### Step 3: Detail Layanan

- Daya Baru: Pilih dari dropdown (450-66000 VA)
- Jenis Produk: Pascabayar atau Prabayar
- Peruntukan: Rumah Tangga, Bisnis, Industri, dll

#### Step 4: Data SLO

- No. Registrasi SLO
- No. Sertifikat SLO

#### Step 5: Finalisasi

- Upload Foto Bangunan
- Upload Foto KTP + Selfie
- Submit permohonan

### Tambah Daya - Step by Step

#### Step 1: Pilih ID Pelanggan

- Masukkan ID Pelanggan (12 digit)

#### Step 2-5: Sama dengan Pasang Baru

### Monitoring Permohonan

1. Login ke akun pelanggan
2. Klik menu "Monitoring"
3. Pilih permohonan untuk melihat detail
4. Lihat timeline visual progress

### Pembayaran

1. Permohonan harus berstatus "Menunggu Pembayaran"
2. Klik tombol "Bayar"
3. Scan QRIS dengan mobile banking
4. Tunggu konfirmasi otomatis

---

## Panduan Pegawai Internal PLN

### Login Pegawai

1. Kunjungi `http://localhost:8000/pegawai/login`
2. Masukkan kredensial sesuai role
3. Redirect ke panel sesuai role

### Dashboard Admin Layanan

**Overview Panel:**

- Total Permohonan
- Permohonan Menunggu Verifikasi
- Permohonan Sedang Diproses
- Permohonan Selesai
- Statistik Pembayaran

**Charts:**

- Chart Permohonan Layanan
- Chart Pembayaran
- Chart Akun Pelanggan
- Chart Distribusi Unit

### Verifikasi Permohonan

1. Pilih menu "Permohonan Layanan"
2. Filter permohonan (Menunggu/Pending/Sukses/Gagal)
3. Review detail permohonan
4. Aksi: Terima / Kembalikan / Tolak

### Update Status

1. Buka detail permohonan
2. Pilih status detail baru
3. Tambahkan catatan
4. Klik "Update Status"

---

## Testing

### Overview

Proyek ini menggunakan **PHPUnit** dan **Laravel Sail** untuk testing.

### Menjalankan Test

```bash
# Jalankan semua test
php artisan test

# Jalankan test spesifik
php artisan test --filter=AuthRedirectTest
php artisan test --filter=CustomerRegistrationTest
php artisan test --filter=ServiceRequestWorkflowTest
```

### Test Cases

- **AuthRedirectTest** - Pengalihan autentikasi
- **CustomerRegistrationTest** - Registrasi pelanggan
- **ServiceRequestWorkflowTest** - Workflow permohonan
- **EndToEndWorkflowTest** - End-to-end workflow
- **WorkflowTransitionTest** - Transisi status
- **SessionIsolationTest** - Isolasi session

### Pengujian PKL

- **Functional Testing** - Pengujian fungsi utama
- **Black Box Testing** - Pengujian tanpa melihat kode
- **User Acceptance Testing** - Validasi oleh pengguna

---

## Deployment

### Local Development

```bash
git clone https://github.com/Hasansurya321/Sistem-Permohonan-dan-Monitoring-Layanan-PLN-UP3-Kudus.git
cd PLN-Monitoring-Backup-B
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Staging/Production Server

**Requirements:**

- VPS dengan Ubuntu 22.04
- PHP 8.2+, MySQL 8.x
- Nginx atau Apache
- SSL certificate

**Steps:**

1. Clone repository ke server
2. Install dependencies
3. Konfigurasi environment
4. Setup Nginx/Apache
5. Setup SSL (Let's Encrypt)
6. Jalankan migration dan seeder

### Backup Database

```bash
# Backup
mysqldump -u root -p pln_monitoring > backup_$(date +%Y%m%d).sql

# Restore
mysql -u root -p pln_monitoring < backup_20260101.sql
```

---

## Troubleshooting

### Common Issues

#### Composer Error

**Masalah:** `Composer memory limit exhausted`

```bash
php -d memory_limit=-1 composer install
```

#### Migration Error

**Masalah:** `Table already exists`

```bash
php artisan migrate:fresh
```

#### QRIS Callback Error

1. Pastikan Ngrok berjalan dan status "online"
2. Update NGROK_URL di .env
3. Pastikan port sama (8000)

#### Permission Error

```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data .
```

### Debugging Tips

```bash
# Clear all caches
php artisan optimize:clear

# Check routes
php artisan route:list

# View logs
tail -f storage/logs/laravel.log
```

---

## Dokumentasi PKL

### Flowchart Pasang Baru

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLOWCHART PASANG BARU                         │
├─────────────────────────────────────────────────────────────────┤
│  START → Login/Register → Pilih Layanan "Pasang Baru"           │
│         │                                                        │
│         ▼                                                        │
│  Step 1: Data Pemohon (Verifikasi NIK)                          │
│         │                                                        │
│         ▼                                                        │
│  Step 2: Lokasi Instalasi                                       │
│         │                                                        │
│         ▼                                                        │
│  Step 3: Detail Layanan (Daya, Produk, Peruntukan)               │
│         │                                                        │
│         ▼                                                        │
│  Step 4: Data SLO (Registrasi, Sertifikat)                       │
│         │                                                        │
│         ▼                                                        │
│  Step 5: Upload Dokumen (Foto, KTP)                             │
│         │                                                        │
│         ▼                                                        │
│  Submit → Verifikasi Admin (Accept/Reject/Return)                │
│         │                                                        │
│    ┌────┴────┐                                                 │
│    │         │                                                 │
│    ▼         ▼                                                 │
│  Accept    Reject                                              │
│    │         │                                                 │
│    ▼         ▼                                                 │
│  Auto-Advance    Pemberitahuan Penolakan                        │
│  (Survey,        ke Pelanggan                                  │
│  Perencanaan,                                                   │
│  Billing)                                                       │
│    │                                                            │
│    ▼                                                            │
│  Pembayaran QRIS (Sukses/Failed)                                │
│    │                                                            │
│    ▼                                                            │
│  Unit Konstruksi → Unit Penyalaan → CLOSE/SELESAI               │
│         │                                                        │
│         ▼                                                        │
│  END                                                            │
└─────────────────────────────────────────────────────────────────┘
```

### Use Case Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                      USE CASE DIAGRAM                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│          ┌─────────────────────────────────────────┐            │
│          │     PLN UP3 Kudus Service System        │            │
│          └─────────────────────────────────────────┘            │
│                                                                  │
│    ┌───────────┐                              ┌───────────────┐ │
│    │  PELANGGAN │                              │ ADMIN/PEGAWAI│ │
│    └─────┬─────┘                              └──────┬────────┘ │
│          │                                             │         │
│          ▼                                             ▼         │
│    ┌───────────────┐                          ┌───────────────┐│
│    │ UC1: Register │                          │UC10: Dashboard││
│    │ UC2: Login    │                          │UC11: Verifikasi││
│    │ UC3: Pasang   │                          │UC12: Update    ││
│    │    Baru       │                          │    Status      ││
│    │ UC4: Tambah   │                          │UC13: Kelola    ││
│    │    Daya       │                          │    Akun        ││
│    │ UC5: Monitor  │                          └───────────────┘│
│    │ UC6: Bayar    │                                           │
│    │    QRIS       │                                           │
│    │ UC7: Reset    │                                           │
│    │    Password   │                                           │
│    └───────────────┘                                           │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Hasil Implementasi

### ✅ Transparansi Layanan Meningkat

Pelanggan dapat memantau status permohonan secara real-time lengkap dengan riwayat proses dan estimasi penyelesaian.

### ✅ Standarisasi Proses Permohonan

Formulir digital membantu menghasilkan data yang lebih lengkap, konsisten, dan terdokumentasi dengan baik.

### ✅ Monitoring Proses Lebih Terukur

Setiap unit kerja dapat memantau progres sesuai tanggung jawab masing-masing.

### ✅ Meningkatkan Pengalaman Pelanggan

Pelanggan dapat mengajukan layanan, menerima notifikasi, dan melakukan pembayaran digital melalui satu platform.

---

## Dampak Utama Sistem

- **Monitoring permohonan** secara real-time dan transparan
- **Estimasi waktu** pada setiap tahapan proses layanan
- **Data permohonan** lebih rapi dan seragam
- **Koordinasi antar unit kerja** lebih terstruktur
- **Layanan pelanggan** lebih cepat dan praktis

---

## Kesimpulan

Sistem Permohonan dan Monitoring Layanan PLN UP3 Kudus menjadi solusi terintegrasi yang menghubungkan pelanggan dan setiap unit kerja dalam satu alur layanan yang transparan dan terukur.

Melalui monitoring real-time, estimasi waktu proses, standarisasi data, dashboard terpusat, serta integrasi pembayaran digital, sistem ini tidak hanya meningkatkan pengalaman pelanggan tetapi juga memperkuat kolaborasi antar unit kerja sehingga proses layanan menjadi lebih terkoordinasi, efisien, dan mudah dipantau dari awal hingga selesai.

---

## 👨‍💻 Tim Pengembang

**Hasan Suryadharma**
24060123140208

**Muhammad Affan Firdaus**
24060123140209

Informatika || Fakultas Sains dan Matematika || Universitas Diponegoro

PKL PLN UP3 Kudus

---

## Changelog

### v1.2.0 (2026-06-10)

**Fitur Baru:**

- ✅ Implementasi QRIS Simulator dengan Ngrok integration
- ✅ Auto-advance workflow setelah pembayaran sukses
- ✅ Dashboard Admin Filament dengan charts dan statistik
- ✅ Sistem monitoring real-time dengan timeline
- ✅ Pagination dan filtering di halaman admin
- ✅ Session restoration untuk wizard forms

**Perbaikan:**

- ✅ Fix workflow transition authorization
- ✅ Fix duplicate event recording
- ✅ Fix expired payment handling
- ✅ Fix idempotent payment processing
- ✅ Fix role-based panel access

**Refactoring:**

- ✅ Move ke Laravel 12
- ✅ Cleanup unused code
- ✅ Optimize database queries
- ✅ Add performance indexes

### v1.1.0 (2026-05-30)

**Fitur Baru:**

- ✅ Multi-panel Filament (Unit Survey, Perencanaan, Konstruksi, TE)
- ✅ Customer notifications system
- ✅ Service request events tracking
- ✅ Payment retry mechanism
- ✅ Revision system untuk permohonan

### v1.0.0 (2026-01-15)

**Fitur Awal:**

- ✅ Sistem autentikasi pelanggan
- ✅ Form pasang baru 5 step
- ✅ Form tambah daya 5 step
- ✅ Dashboard monitoring
- ✅ Basic Filament admin panel

---

## Future Development

### Short Term (v1.3.x)

| Fitur                 | Prioritas | Estimasi   |
| --------------------- | --------- | ---------- |
| Email Notification    | Tinggi    | 1-2 minggu |
| WhatsApp Notification | Tinggi    | 1-2 minggu |
| Dashboard Unit Kerja  | Sedang    | 2-3 minggu |
| Export PDF Laporan    | Sedang    | 1 minggu   |

### Medium Term (v2.0.x)

| Fitur                             | Prioritas | Estimasi   |
| --------------------------------- | --------- | ---------- |
| Payment Gateway Production        | Tinggi    | 2-4 minggu |
| Mobile Application (React Native) | Tinggi    | 4-6 minggu |
| SLA Monitoring                    | Sedang    | 2-3 minggu |
| Advanced Analytics Dashboard      | Sedang    | 3-4 minggu |

### Long Term (v3.0.x)

| Fitur                               | Prioritas | Estimasi   |
| ----------------------------------- | --------- | ---------- |
| Multi-tenant Support                | Sedang    | 4-6 minggu |
| API REST/GraphQL                    | Sedang    | 3-4 minggu |
| Integration dengan Sistem PLN Pusat | Rendah    | 8+ minggu  |
| AI-based Forecasting                | Rendah    | 6+ minggu  |

### Technical Debt

- [ ] Unit testing coverage (saat ini ~40%)
- [ ] Dokumentasi API (Swagger/OpenAPI)
- [ ] Performance optimization (caching, indexing)
- [ ] Security audit
- [ ] Code refactoring untuk modularitas

---

## Lisensi

Proyek ini adalah **open-source** dan tersedia di bawah lisensi MIT License.

### Penggunaan untuk PKL

Dokumentasi ini dapat digunakan sebagai referensi untuk:

- Tugas akhir PKL
- Skripsi/Tesis yang berkaitan
- Proyek pengembangan serupa

### Kredit

Dikembangkan sebagai bagian dari program **PKL PLN UP3 Kudus**.

**Kontributor:**

- Hasan Suryadharma (Developer)
- Muhammad Affan Firdaus (Developer)

**Dosen Pembimbing:**

- Dr. Aris Puji Widodo, S.Si., M.T.

---

## 📞 Kontak & Support

**GitHub Issues:**
https://github.com/Hasansurya321/Sistem-Permohonan-dan-Monitoring-Layanan-PLN-UP3-Kudus/issues

Pull requests diterima! Untuk perubahan major, harap buka issue terlebih dahulu untuk didiskusikan.

---

## 🙏 Acknowledgments

Terima kasih kepada:

- **PLN UP3 Kudus** - Untuk kesempatan PKL dan akses ke sistem
- **Universitas Diponegoro** - Untuk dukungan akademik

---

<p align="center">
  <strong>Sistem Permohonan dan Monitoring Layanan PLN UP3 Kudus</strong>
  <br>
  Informatika || Fakultas Sains dan Matematika || Universitas Diponegoro
  <br>
  PKL PLN UP3 Kudus • 2025/2026
</p>
