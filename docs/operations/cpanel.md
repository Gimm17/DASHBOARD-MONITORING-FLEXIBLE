# Pemasangan melalui cPanel

Paket ini memakai satu domain atau subdomain khusus, misalnya monitoring.domain-instansi. Jangan menaruhnya di subfolder situs lain karena URL aset dan API menggunakan akar domain. Panduan akses SSH lama dalam repositori merupakan contoh proyek SIKLAS-NB; alamat dan kredensialnya belum ditetapkan sebagai target DMF.

## Persiapan hosting

1. Pilih PHP 8.4 pada MultiPHP Manager atau Select PHP Version. Pastikan CLI Terminal memakai versi yang sama; periksa `php -v` dan `php -m`. Laravel memerlukan minimal PHP 8.4.1 dan aplikasi memerlukan BCMath serta PDO MySQL. Bila CLI default berbeda, gunakan lokasi executable yang dikonfirmasi penyedia hosting untuk seluruh perintah Artisan dan Composer.
2. Buat subdomain dengan document root menuju direktori **server/public** pada rilis, misalnya `/home/akun/dmf/releases/1.0.0-rc.2/server/public`. Gunakan direktori baru yang tidak berisi aplikasi lain. Pastikan batas document root di akun mengizinkan lokasi ini. Jika pembatasan hosting menghalangi, minta penyedia mengatur document root; jangan mengekspos seluruh folder server.
3. Aktifkan sertifikat melalui SSL/TLS Status atau fasilitas penyedia, lalu Force HTTPS Redirect. Konfirmasikan domain sebenarnya sebelum membuat APP_URL.
4. Buat database dan pengguna melalui MySQL Database Wizard. Nama pada cPanel biasanya memakai awalan akun. Berikan pengguna izin pada database aplikasi tersebut; masukkan nama lengkap berawalan ke konfigurasi.
5. Pastikan tersedia Terminal atau SSH untuk menjalankan migrasi dan provisioning. Tanpa akses tersebut, minta pengelola hosting menjalankannya. Paket sudah membawa vendor dan aset; Node serta Composer tidak diperlukan di hosting bila paket kompatibel dengan platform PHP server.

## Upload dan konfigurasi

1. Upload `dmf-1.0.0-rc.2.zip` ke direktori rilis baru dengan File Manager, lalu Extract. ZIP berisi folder server, operations, manifest, dan contoh environment. Pastikan File Manager menampilkan hidden files sehingga public/.htaccess terlihat.
2. Salin production.env.example menjadi server/.env. Isi domain, koneksi database, dan APP_KEY. Gunakan APP_KEY baru hanya pada instalasi pertama. Simpan kunci yang sama pada pembaruan.
3. Dari direktori server jalankan:

```text
php artisan key:generate
php artisan migrate --force
php artisan dmf:admin alamat-email-admin --name="Administrator Provinsi"
php artisan optimize
php artisan dmf:check --production
```

Masukkan kata sandi admin melalui prompt. Jangan memasang data demo. Setelah seluruh pemeriksaan PASS, coba login dan alur pilot sesuai daftar UAT. Jika sesi selalu hilang, periksa HTTPS, domain cookie, izin storage, APP_KEY, dan session driver database. Jangan menonaktifkan CSRF untuk mengatasi masalah sesi.

## Backup, pembaruan dan pemeriksaan

Atur backup database harian melalui fasilitas cPanel atau Cron Jobs dengan akun backup khusus. Simpan salinan terenkripsi di lokasi berbeda dan uji pemulihan berkala. Fitur ini tidak membutuhkan queue worker atau scheduler aplikasi yang terus berjalan. Jam database disimpan UTC; antarmuka menggunakan WITA.

Untuk pembaruan, gunakan direktori versi baru, salin environment secara aman, ambil backup, lakukan migrasi kompatibel, jalankan optimize/check, kemudian alihkan document root pada jendela perubahan. Simpan rilis sebelumnya. Jika hosting tidak mengizinkan pengalihan document root, sepakati prosedur pembaruan dengan pengelola terlebih dahulu.

Verifikasi URL /login dan /matriks dapat dibuka langsung, /spa/assets menyajikan aset, /api/dashboard tanpa login ditolak, serta .env/vendor/storage tidak dapat diakses melalui web. Konfigurasi cache LiteSpeed/CDN harus mengecualikan /api dan halaman HTML sesi. Uji 30 pengguna dan pemulihan pada hosting target sebelum layanan resmi digunakan.

Panduan umum backup/rollback dan syarat go live ada pada production.md. Laporan bukti lokal ada pada verification.md. Penyusunan paket dan panduan ini belum membuktikan bahwa domain atau akun cPanel sudah dipasang.
