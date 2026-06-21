# EZPost - Analisis Sistem & Panduan Test-Driven Development (TDD)

Dokumen ini berfungsi sebagai panduan arsitektur dan pengembangan berbasis pengujian (TDD) untuk aplikasi **EZPost**. Tujuannya adalah memastikan bahwa saat aplikasi ini tumbuh dengan fitur-fitur baru, stabilitas sistem tetap terjaga, regresi (bug baru dari kode lama) dapat dicegah, dan logika bisnis utama tetap teruji secara otomatis.

---

## 1. Analisis Arsitektur Sistem Saat Ini

Aplikasi EZPost adalah sistem penjadwalan dan penerbitan postingan media sosial otomatis ke platform **Facebook** dan **Threads** menggunakan Meta Graph API.

### Struktur File Aktif:
*   [config.php](file:///data/data/com.termux/files/home/storage/downloads/1.Project/ezpost-app/config.php): Mengatur koneksi database TiDB Cloud via PDO, session, verifikasi CSRF, dan zona waktu (`Asia/Jakarta`).
*   [index.php](file:///data/data/com.termux/files/home/storage/downloads/1.Project/ezpost-app/index.php): Berfungsi sebagai frontend dashboard (Tailwind CSS, HTML, Vanilla JS) sekaligus melakukan otentikasi login pengguna di sisi backend (PHP).
*   [api.php](file:///data/data/com.termux/files/home/storage/downloads/1.Project/ezpost-app/api.php): Endpoint utama untuk interaksi frontend (Ajax). Mengatur aksi login/logout, CRUD postingan, upload media ke folder `uploads/`, dan *direct publish* ke Meta API.
*   [cron.php](file:///data/data/com.termux/files/home/storage/downloads/1.Project/ezpost-app/cron.php): Dipanggil oleh scheduler eksternal (misal: Pipedream) secara berkala dengan validasi `secret` key. Mengambil antrean postingan terjadwal (`scheduled`) yang sudah masuk waktunya, melakukan penguncian database (locking) untuk mencegah *double-post*, mengirim data ke API Meta, dan mengubah status postingan.
*   [schema.sql](file:///data/data/com.termux/files/home/storage/downloads/1.Project/ezpost-app/schema.sql): Struktur skema database relasional (TiDB/MySQL) yang terdiri dari tabel `users`, `social_accounts`, `posts`, dan `post_media`.

### Tantangan Testing & Technical Debt (Hutang Teknis):
1.  **Kode Bersifat Prosedural (Coupled)**: Logika bisnis, penanganan HTTP request, koneksi database, dan panggilan HTTP client (cURL ke Meta) tercampur di satu tempat (`api.php` dan `cron.php`). Hal ini membuat unit testing mustahil dilakukan tanpa mengeksekusi koneksi DB nyata dan menembak server Meta secara live.
2.  **Duplikasi Kode**: Logika pemecah paragraf Threads (`split_threads` di `api.php` dan `cron_split_threads` di `cron.php`) serta fungsi penerbitan media sosial terduplikasi secara identik. Jika ada perubahan limit karakter Threads, Anda harus mengubahnya di dua tempat berbeda.
3.  **Ketergantungan Eksternal (Hard Dependencies)**: cURL langsung ditembak dari dalam fungsi bantu. Jika koneksi internet mati atau token kedaluwarsa saat pengujian, tes otomatis akan gagal (*flaky tests*).

---

## 2. Tujuan & Arah Pengembangan EZPost

### Tujuan Utama Aplikasi:
*   **Akurasi Publikasi**: Menjamin postingan terbit tepat waktu sesuai zona waktu Asia/Jakarta.
*   **Keandalan Transaksi**: Mencegah penerbitan ganda (anti-triple post) jika cron-job terpanggil beberapa kali secara simultan (menggunakan mekanisme lock status database).
*   **Integritas Data**: Mengelola file media agar terhapus otomatis dari penyimpanan lokal jika postingan dihapus, namun tetap dilindungi jika postingan masih dalam antrean jadwal.

### Menjaga Arah Pengembangan (Guardrails):
Setiap fitur baru (seperti integrasi Twitter/X, Instagram, filter postingan, atau penjadwalan berulang) **wajib** mengikuti aturan arsitektur yang didefinisikan dalam dokumen ini agar tidak mengotori file prosedural utama.

---

## 3. Strategi Refactoring Menuju TDD

Untuk menjalankan TDD, kode harus didecouple (dilepas ketergantungannya) agar setiap komponen dapat diuji secara terpisah (*isolated testing*).

### Struktur Direktori Baru yang Direkomendasikan:
```text
ezpost-app/
├── src/                    # Kode Sumber Aplikasi (Logika Bisnis)
│   ├── Database/           # Pengelolaan Koneksi DB
│   │   └── Connection.php
│   ├── Repository/         # Akses ke Database (CRUD query)
│   │   ├── PostRepository.php
│   │   └── UserRepository.php
│   └── Services/           # Logika Bisnis Utama (Uji dengan Unit Test)
│       ├── ThreadsSplitter.php
│       ├── MetaApiClient.php
│       └── PostScheduler.php
├── tests/                  # Semua File Pengujian
│   ├── Unit/               # Unit Testing murni (tanpa DB & API nyata)
│   │   ├── ThreadsSplitterTest.php
│   │   └── PostSchedulerTest.php
│   └── Integration/        # Integration Testing (menggunakan SQLite in-memory)
│       └── PostRepositoryTest.php
├── uploads/                # Penyimpanan file sementara
├── composer.json           # Manajemen dependensi testing (PHPUnit)
├── phpunit.xml             # Konfigurasi PHPUnit
└── index.php, api.php...   # Controller entry points (tipis, hanya delegasi ke Services)
```

---

## 4. Setup Lingkungan Pengujian (Testing Environment)

### Langkah 1: Instalasi Composer & PHPUnit
Buat file `composer.json` di root direktori dengan menjalankan perintah instalasi PHPUnit:

```json
{
    "require": {},
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

*Jalankan `composer install` untuk mengunduh PHPUnit dan menyiapkan autoloader.*

### Langkah 2: Buat Konfigurasi `phpunit.xml`
Buat file `phpunit.xml` di root direktori untuk mengarahkan PHPUnit ke direktori test:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

## 5. Metodologi TDD (Red-Green-Refactor)

TDD mewajibkan Anda untuk menulis pengujian **SEBELUM** menulis kode implementasi. Siklusnya adalah:
1.  **🔴 RED (Gagal)**: Tulis test case terlebih dahulu. Jalankan tes dan pastikan tes tersebut gagal (karena kode fitur belum dibuat).
2.  **🟢 GREEN (Berhasil)**: Tulis kode implementasi minimal agar tes tersebut lolos/sukses.
3.  **🔵 REFACTOR (Perbaiki)**: Rapikan kode implementasi Anda tanpa merusak tes (pastikan tes tetap hijau). Hilangkan duplikasi dan perbaiki penamaan variable.

### Contoh Kasus TDD: Menguji Fitur `ThreadsSplitter`

Mari kita pindahkan logika duplikasi pemecah paragraf Threads ke sebuah class mandiri `App\Services\ThreadsSplitter`.

#### Tahap 1: Tulis Tes Terlebih Dahulu (RED)
Buat file `tests/Unit/ThreadsSplitterTest.php`:

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ThreadsSplitter;

class ThreadsSplitterTest extends TestCase
{
    public function test_it_does_not_split_short_text()
    {
        $splitter = new ThreadsSplitter();
        $text = "Halo ini postingan pendek kurang dari 490 karakter.";
        
        $result = $splitter->split($text);
        
        $this->assertCount(1, $result);
        $this->assertEquals("Halo ini postingan pendek kurang dari 490 karakter.", $result[0]);
    }

    public function test_it_splits_long_paragraphs_accurately()
    {
        $splitter = new ThreadsSplitter();
        // Buat paragraf yang sangat panjang (> 490 karakter)
        $textPart1 = str_repeat("a", 480);
        $textPart2 = str_repeat("b", 480);
        $longText = $textPart1 . "\n\n" . $textPart2;
        
        $result = $splitter->split($longText);
        
        $this->assertCount(2, $result);
        $this->assertEquals($textPart1, $result[0]);
        $this->assertEquals($textPart2, $result[1]);
    }
}
```
*Jalankan vendor/bin/phpunit. Hasilnya akan **RED (Error: Class App\Services\ThreadsSplitter not found)**.*

#### Tahap 2: Tulis Kode Implementasi Terkecil (GREEN)
Buat file `src/Services/ThreadsSplitter.php`:

```php
<?php
namespace App\Services;

class ThreadsSplitter
{
    /**
     * Memecah text panjang menjadi thread-thread pendek maksimal 490 karakter
     * berdasarkan paragraf atau spasi kata jika melebihi batas.
     */
    public function split(string $text): array
    {
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $threads = [];
        foreach ($paragraphs as $p) {
            $trimmed = trim($p);
            if (empty($trimmed)) continue;
            if (mb_strlen($trimmed) > 490) {
                $words = explode(' ', $trimmed);
                $currentChunk = '';
                foreach ($words as $word) {
                    if (mb_strlen($currentChunk . $word) > 490) {
                        $threads[] = trim($currentChunk);
                        $currentChunk = $word . ' ';
                    } else { 
                        $currentChunk .= $word . ' '; 
                    }
                }
                if (trim($currentChunk)) $threads[] = trim($currentChunk);
            } else { 
                $threads[] = $trimmed; 
            }
        }
        return empty($threads) ? [""] : $threads;
    }
}
```
*Jalankan vendor/bin/phpunit. Hasilnya akan **GREEN (Semua tes lolos)**.*

#### Tahap 3: Refactor (Integrasi Kembali ke Prosedural)
Sekarang Anda dapat mengganti fungsi `split_threads` di `api.php` dan `cron.php` dengan memanggil class `ThreadsSplitter`:

```php
// Di dalam api.php & cron.php
use App\Services\ThreadsSplitter;

$splitter = new ThreadsSplitter();
$threadsArray = $splitter->split($content);
```

Dengan begini, jika besok Meta Threads memperluas batas karakter menjadi 1000 karakter, Anda hanya perlu:
1. Mengubah angka `490` menjadi `990` di `ThreadsSplitterTest.php` (Tes menjadi RED).
2. Mengubah nilai batas di `ThreadsSplitter.php` (Tes menjadi GREEN kembali).
3. Anda tidak perlu khawatir merusak bagian aplikasi lain karena tes menjamin perilakunya tetap konsisten.

---

## 6. Panduan Menghindari Penyimpangan Fitur (Feature Creep & Regression Guardrails)

Untuk menjaga kualitas kode ketika fitur terus bertambah, ikuti 5 aturan emas berikut:

### Rule #1: Isolasi Client HTTP (Mocking Meta API)
Jangan biarkan kode cURL ke Meta dipanggil langsung di dalam logika bisnis scheduler. Bungkus dalam interface `MetaApiClientInterface`.
Saat pengujian unit scheduler (`PostSchedulerTest`), berikan *mock* atau *double* dari client API Meta agar tidak menembak jaringan internet.

### Rule #2: SQLite in-memory untuk Integrasi Database
Untuk menguji query database (seperti filter status scheduled), jangan gunakan database produksi TiDB Cloud.
Gunakan SQLite in-memory dalam integration test:
```php
$pdo = new PDO('sqlite::memory:');
// Jalankan setup schema.sql di database sqlite virtual ini sebelum tes dimulai.
```
Hal ini memastikan tes berjalan sangat cepat (kurang dari 1 detik) dan tidak merusak data nyata.

### Rule #3: Setiap Bug Wajib Memiliki Test Case (Regression Test)
Jika pengguna melaporkan bug (contoh: karakter emoji menyebabkan pemenggalan kalimat gagal di Threads), lakukan hal berikut:
1.  Tulis test case baru di `ThreadsSplitterTest` yang memasukkan emoji dan teks panjang, lalu jalankan tes (pasti gagal/RED).
2.  Perbaiki regex pemotong string di `ThreadsSplitter` sampai tes lolos (GREEN).
3.  Bug tersebut tidak akan pernah kembali lagi di masa depan karena tes ini selalu dijalankan setiap kali ada perubahan kode.

### Rule #4: Pisahkan Controller dengan Business Logic
File `api.php` hanya boleh berfungsi sebagai penerima request (mengambil data POST/GET), validasi CSRF, memanggil Service Class yang sesuai, dan mengembalikan JSON. Logika pengiriman, parsing, dan validasi format tidak boleh ditulis di dalam `api.php`.

### Rule #5: CI/CD Pipeline Otomatis
Integrasikan testing ke dalam repositori GitHub menggunakan GitHub Actions. Setiap kali developer melakukan Pull Request atau Push ke branch `main`, jalankan tes secara otomatis. Jika ada tes yang gagal, cegah penggabungan (*merge block*).

---

## Kesimpulan
Dengan memisahkan utility pemisah paragraf, pembungkus API cURL, dan pembungkus database menggunakan pendekatan TDD ini, aplikasi EZPost akan memiliki arsitektur yang kuat, bebas bug regresi, serta siap untuk ditambahkan platform media sosial baru kapan pun tanpa risiko merusak fitur penjadwal yang sudah berjalan.
