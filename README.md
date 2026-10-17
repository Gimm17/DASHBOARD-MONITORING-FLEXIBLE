# BPS Dashboard Monitoring Flexible

Vue 3 + TypeScript menangani antarmuka yang sudah ada; Laravel 13 menangani sesi, hak akses, penyimpanan, dan perhitungan melalui API pada origin yang sama. Frontend operasional memakai data database. Paket ini menyiapkan rilis P0; pemasangan cPanel, UAT organisasi, dan kapasitas server target memiliki pemeriksaan terpisah.

## Fitur yang tersedia

- Akun yang dikelola admin; peran admin, PJK, operator provinsi, pimpinan, dan pembaca wilayah dengan cakupan penugasan.
- Master tim/wilayah/satuan; kegiatan, indikator, peserta, jadwal harian/mingguan/bulanan/triwulanan atau tanggal khusus, serta target kumulatif per wilayah dan periode.
- Input batch hingga 20 baris per permintaan, status per baris, penyimpanan sebagian, pemeriksaan konflik, dan pengulangan aman menggunakan kunci idempotensi.
- Perbedaan nol dan belum melapor; capaian target periode dan progres akhir dihitung terpisah memakai desimal.
- Finalisasi dengan snapshot resmi berversi; pembukaan ulang oleh admin, koreksi penurunan melalui tiket persetujuan, dan riwayat revisi.
- Dashboard provinsi/kegiatan/wilayah, rincian unit evaluasi, filter, grafik dari data tersimpan, ekspor CSV, audit, dan cetak riwayat melalui browser.
- Proteksi sesi/CSRF, pembatasan login, pembatasan akses di server, header keamanan, validasi angka/jadwal, serta jejak perubahan.

Gamifikasi dan prediksi AI belum diaktifkan. Skor tidak dibuat sebelum kebijakan penilaian disepakati. Input wilayah bersifat lihat; operator yang ditugaskan di provinsi memasukkan laporan.

## Menjalankan lokal

Diperlukan Node 22, Composer 2, PHP minimal 8.4.1 dengan BCMath, mbstring, XML, PDO SQLite untuk lokal dan PDO MySQL untuk produksi. Lockfile menentukan versi dependensi.

```text
npm ci
composer install --working-dir=backend
```

Salin backend/.env.example menjadi backend/.env. Untuk database lokal SQLite, buat berkas kosong backend/database/database.sqlite. Dari direktori backend:

```text
php artisan key:generate
php artisan migrate
php artisan dmf:admin email-admin-anda --name="Administrator"
php artisan dmf:demo
```

Perintah dmf:admin meminta kata sandi melalui prompt tersembunyi. dmf:demo bersifat opsional dan ditolak pada lingkungan produksi. Demo berisi nama wilayah fiktif, bukan data resmi.

Dari akar proyek jalankan `npm run build`, lalu dari backend jalankan `php artisan serve --host=127.0.0.1 --port=8000`. Buka http://127.0.0.1:8000. Untuk pengembangan antarmuka, jalankan juga `npm run dev`; Vite meneruskan /api ke backend lokal. Jangan menggunakan server pengembangan untuk layanan publik.

## Pengujian dan rilis

```text
npm run build
composer --working-dir=backend test
npm audit --audit-level=low
composer audit --working-dir=backend
```

Pada Windows, `./scripts/check.ps1` menjalankan build, audit, pemeriksaan format PHP, dan test backend. Pastikan PHP dan Composer tersedia pada PATH. Tambahkan -Browser bila server lokal dan data browser sudah disiapkan. CI GitHub tersedia pada .github/workflows/check.yml; hasil runner jarak jauh belum diasumsikan lulus sebelum dijalankan.

`npm run test:browser` memakai Chrome dan data demo lokal. Sediakan akun uji melalui dmf:admin dengan DMF_TEST_EMAIL/DMF_TEST_PASSWORD sesuai test, atau gunakan akun uji default yang tertulis di tests/browser. Kredensial tersebut hanya untuk database pengujian terisolasi dan tidak dibuat oleh rilis produksi.

```text
npm run release -- 1.0.0-rc.1
```

Hasilnya `release/dmf-1.0.0-rc.1.zip`, hash arsip, dan folder rilis dengan vendor produksi, aset, manifest serta panduan. Skrip menolak menimpa versi. Isi paket tidak membawa .env, database lokal atau akun demo.

## Dokumentasi

- [Pemasangan cPanel](docs/operations/cpanel.md)
- [Operasi, backup, rollback dan pemeriksaan go live](docs/operations/production.md)
- [Hasil verifikasi dan batas bukti](docs/operations/verification.md)
- [Kontrak backend dan keputusan implementasi](docs/operations/backend.md)
- [Catatan kemajuan dan penyelesaian temuan](docs/backend-progress.md)
- [PRD Word](output/prd/PRD_Dashboard_Monitoring_Flexible_v1.0.docx)
- [Implementation Plan Word](output/implementation-plan/Implementation_Plan_Dashboard_Monitoring_Flexible_v1.0.docx)

Document root produksi adalah server/public pada paket rilis, atau backend/public pada checkout yang dibangun. Domain/subdomain cPanel, database, HTTPS, akses admin, backup, dan hasil UAT perlu ditetapkan sebelum peluncuran. Panduan SSH lama merupakan contoh proyek lain dan bukan konfigurasi produksi DMF.
