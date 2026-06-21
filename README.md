---
title: Ezpost App
emoji: 📊
colorFrom: blue
colorTo: green
sdk: docker
pinned: false
license: mit
short_description: ezpost
---

Check out the configuration reference at https://huggingface.co/docs/hub/spaces-config-reference

Connected to https://github.com/thrdchld/ezpost-app

---

## 🛠️ Informasi untuk Developer & AI Coding Assistant

Proyek ini telah dikonfigurasi menggunakan metodologi **Test-Driven Development (TDD)** untuk menjamin kestabilan kode saat fitur-fitur baru dikembangkan.

### 📁 Struktur Direktori Utama
*   `src/`: Tempat utama menulis semua logika bisnis (Business Logic), Service, dan Repository. **Selalu tulis kode baru di sini.**
    *   `src/Services/`: Berisi service classes (misal: `ThreadsSplitter.php`).
*   `tests/`: Tempat menulis semua pengujian otomatis.
    *   `tests/Unit/`: Pengujian unit terisolasi (tidak membutuhkan database/koneksi internet Meta API).
    *   `tests/Integration/`: Pengujian integrasi (menggunakan SQLite in-memory).
*   `TDD_GUIDELINE.md`: Dokumentasi mendalam mengenai metodologi TDD dan refactoring yang diterapkan pada proyek ini.

### 🧪 Cara Menjalankan Tes Otomatis (PHPUnit)
Untuk menjalankan semua unit test di terminal lokal, jalankan perintah berikut:

```bash
# Menjalankan seluruh test suite
php ./vendor/phpunit/phpunit/phpunit

# Menjalankan test suite Unit saja
php ./vendor/phpunit/phpunit/phpunit --testsuite=Unit
```

### 📜 Aturan Pengembangan Fitur Baru
1.  **Jangan Menambahkan Logika Bisnis ke File Prosedural**: File `api.php` dan `cron.php` hanya boleh bertindak sebagai controller tipis. Semua logika pengolahan data harus diletakkan di dalam kelas Service di bawah folder `src/`.
2.  **Siklus TDD**: Sebelum menulis logika fitur baru, tulis berkas pengujian terlebih dahulu di folder `tests/` (Siklus RED), jalankan tes hingga gagal, lalu buat kodenya hingga tes berhasil (Siklus GREEN), dan lakukan refactoring.
3.  **Gunakan Mocking untuk API Meta**: Jangan menembak API Graph Meta secara live saat testing. Selalu gunakan *mock interface* untuk client API.
4.  **Gunakan SQLite untuk Test Database**: Gunakan driver SQLite in-memory (`sqlite::memory:`) untuk pengujian integrasi database agar pengujian berjalan cepat dan aman.

