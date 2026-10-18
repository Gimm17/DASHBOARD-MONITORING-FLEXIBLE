# Operasional Dashboard Monitoring Flexible

## Bentuk rilis

Aplikasi terdiri dari Vue SPA pada `server/public/spa` dan Laravel pada `server`. Document root harus menunjuk **server/public**, bukan akar repositori atau server. Browser dan /api memakai satu origin HTTPS. Node hanya diperlukan pada mesin build. PHP minimal 8.4.1 dengan BCMath, PDO MySQL, mbstring, openssl, tokenizer, xml, ctype, fileinfo dan ekstensi standar Laravel. Gunakan PHP-FPM dengan OPcache pada produksi.

Rilis dibangun dari akar proyek dengan `npm run release -- 1.0.0-rc.2`. Skrip menolak menimpa versi yang sudah ada, memasang vendor tanpa dependensi pengembangan, dan menghasilkan ZIP, manifest SHA256 per file, serta hash arsip. Tidak ada .env, database lokal, data demo, akun contoh, log, test, atau node_modules di dalam paket. Berkas production.env.example adalah contoh konfigurasi; sesuaikan domain dan kredensial melalui saluran pengelola, bukan Git.

## Instalasi pertama

1. Siapkan database kosong khusus aplikasi dan pengguna MySQL dengan akses hanya ke database tersebut. Gunakan akun migrasi terpisah jika akun runtime tidak boleh mengubah skema.
2. Ekstrak rilis ke direktori versi baru. Arahkan domain ke server/public. Pastikan storage dan bootstrap/cache dapat ditulis oleh pengguna web; jangan menggunakan izin global 777.
3. Salin production.env.example menjadi server/.env dan isi domain HTTPS, nama database, pengguna, password, dan APP_KEY. Pada instalasi baru jalankan `php artisan key:generate` dari server. APP_KEY harus tetap sama pada pembaruan berikutnya dan dicadangkan secara aman.
4. Jalankan `php artisan migrate --force`. Jangan menjalankan migrate:fresh atau seed demo di produksi.
5. Buat administrator melalui `php artisan dmf:admin email-admin-organisasi --name="Administrator Provinsi"`. Masukkan kata sandi melalui prompt tersembunyi. Minimal 12 karakter, huruf besar/kecil, angka, dan simbol; tidak ada kata sandi bawaan.
6. Jalankan `php artisan optimize`, lalu `php artisan dmf:check --production`. Semua pemeriksaan teknis harus PASS. Endpoint /up adalah liveness; perintah check memeriksa database, skema, akun admin, build, dan konfigurasi.
7. Login, buat master wilayah/tim/satuan dan akun, lalu coba kegiatan pilot dengan target serta jadwal yang disepakati. Jangan menganggap lulus pemeriksaan teknis sama dengan persetujuan UAT organisasi.

## Konfigurasi web

Apache/cPanel: aktifkan mod_rewrite dan izinkan .htaccess bawaan Laravel pada public. Pastikan URL /login dan /matriks masuk index.php, sedangkan /spa/assets dibaca sebagai berkas statis. Jangan memindahkan .env ke public. Jangan memakai pengaturan akses dari proyek lain tanpa verifikasi pengelola.

Nginx: set root ke direktori server/public; `try_files $uri $uri/ /index.php?$query_string;`. Teruskan hanya index.php ke PHP-FPM menggunakan konfigurasi distribusi yang benar, tolak berkas tersembunyi, dan redirect HTTP ke HTTPS. Terapkan cache panjang hanya untuk aset ber-hash di /spa/assets; HTML dan API tidak boleh dicache lintas pengguna. Bila ada reverse proxy, tetapkan proxy tepercaya yang benar; jangan mempercayai forwarded headers dari semua alamat.

## Pembaruan dan rollback

Ambil backup database dan .env/kunci sebelum migrasi. Siapkan rilis pada lokasi baru dan gunakan migrasi kompatibel ke belakang. Bila perlu hentikan input pada jendela perubahan. Jalankan migrasi, optimize, health check, login, input, dan pembacaan dashboard sebelum membuka layanan. Simpan versi artifact sebelumnya.

Jika terdapat kesalahan akses, perhitungan, atau penyimpanan, hentikan input dan simpan salinan keadaan terkini. Kembalikan artifact sebelumnya jika skema kompatibel. Jangan menjalankan migrate:rollback otomatis. Jika pemulihan database diperlukan, pengelola harus menghitung perubahan setelah backup, menyimpan bukti, dan melakukan rekonsiliasi sebelum membuka layanan.

## Backup dan pemulihan

Backup database InnoDB setiap hari dengan mysqldump --single-transaction dan akun backup khusus. Simpan pengaturan koneksi pada berkas konfigurasi dengan izin terbatas atau secret store; jangan menaruh password pada argumen command line. Cadangkan .env, APP_KEY, dan lampiran jika kelak diaktifkan. Enkripsi backup dan salin ke lokasi berbeda dari server aplikasi.

Usulan awal RPO 24 jam dan RTO 8 jam harus dikonfirmasi. Pengelola mengatur retensi dan alarm kegagalan. Uji restore pada database baru yang terisolasi: impor backup, pasang konfigurasi uji, jalankan check, cocokkan jumlah kegiatan/realisasi/revisi, dan periksa satu snapshot. Catat waktu mulai/selesai, titik backup, pelaksana, dan hasil. Backup yang belum pernah dipulihkan belum membuktikan pemulihan berhasil.

## Kebijakan yang perlu ditetapkan

Organisasi menetapkan petugas admin/PJK/operator, retensi data/audit, periode akhir input, perubahan peserta, akses lintas wilayah, dan pemulihan akun. Versi awal memakai sesi 120 menit, login dibatasi 5 percobaan per menit per pasangan email/IP, akses wilayah sendiri, dan alasan koreksi minimal 5 karakter. Admin terakhir tidak dapat dinonaktifkan. Penurunan realisasi diajukan sebagai tiket sebelum mengubah angka tersimpan. Komentar klarifikasi hanya tersimpan dalam tiket; aplikasi tidak mengirim pesan eksternal.

Gamifikasi, bobot, dan peringkat tahunan belum diaktifkan karena kebijakan D06/D07 belum final. Grafik menggunakan data tersimpan; tidak ada prediksi AI atau angka contoh yang diklaim sebagai hasil operasional.

## Syarat sebelum go live

Semua test otomatis dan build lulus; audit dependensi bersih; UAT organisasi disetujui; domain, HTTPS dan izin server diverifikasi; admin unik tersedia; backup dan restore terbukti; uji beban 30 pengguna dilakukan pada server target dengan data acuan; hasil p95 dashboard dan batch 20 baris memenuhi 3 detik. Hasil benchmark lokal harus dibedakan dari pengujian server target. Dokumen hasil verifikasi menyatakan batas bukti yang sudah diperoleh.
