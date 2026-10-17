# Hasil verifikasi rilis 1.0.0-rc.1

Tanggal pemeriksaan: 17 Oktober 2026, WITA. Status: kandidat rilis P0 untuk pemasangan dan UAT cPanel. Layanan produksi belum diterapkan. Tidak ada data organisasi atau kredensial produksi pada paket.

## Lingkungan dan hasil

| Pemeriksaan | Bukti lokal |
|---|---|
| PHP dan framework | PHP 8.4.16; Laravel 13.37.0; paket memerlukan PHP >=8.4.1 |
| Database fungsional | SQLite untuk aplikasi demo; PDO MySQL terhadap MariaDB 10.4.24 untuk uji integrasi dan konkurensi |
| Frontend | Vue 3, TypeScript, Vite; build produksi berhasil |
| Uji backend SQLite | 39 pengujian lulus, 230 assertions |
| Uji backend MariaDB | 39 pengujian lulus, 230 assertions, database terisolasi |
| Browser Chrome desktop | Login/CSRF, target kegiatan, batch nyata, pembatalan pergantian periode dengan data belum disimpan, finalisasi, riwayat revisi, penutupan, pembukaan koreksi, dialog keyboard, tiket persetujuan, revisi kedua, logout |
| Browser 360 px | Login dan navigasi seluler; pemeriksaan tampilan dashboard dan filter wilayah |
| Audit paket | npm dan Composer tidak menemukan advisory pada waktu pemeriksaan |
| Penguncian finalizer-first | Penulis menunggu finalisasi, lalu ditolak HTTP423; tidak ada laporan terlambat yang lolos |
| Penguncian writer-first | Snapshot final menyertakan angka yang baru dikomit penulis saat finalizer menunggu |
| Admin bersamaan | Dua perubahan hak admin meninggalkan satu admin aktif; perubahan kedua ditolak HTTP422 |
| Backup/restore | Dump konsisten diimpor ke database baru; 26 tabel memiliki extended checksum yang sama; 107 kegiatan, 100.002 realisasi, 5 revisi pada titik backup |
| Waktu restore lokal | Sekitar 13,24 detik untuk impor dump; tidak termasuk provisioning, transfer, rekonsiliasi atau pembukaan layanan |

Uji backend mencakup akses lintas tim/wilayah, akun nonaktif, angka kosong vs nol, nilai negatif/malformed, jadwal mendatang, target tidak valid, konflik versi, idempotensi, hasil sebagian, perbandingan desimal sebelum pembulatan, target berversi, pembatalan/pengarsipan, peserta keluar pada hari final, koreksi sesudah penutupan dan CSV formula escaping. Jumlah eksekusi test mencakup pengulangan kontrak dasar pada kelas uji tambahan.

## Performa lokal

Dataset acuan: 100 kegiatan benchmark × 20 wilayah × 50 periode = 100.000 realisasi, ditambah sedikit fixture konkurensi. Pengukuran langsung terhadap layanan backend, 2 pemanasan dan 10 iterasi terukur, satu proses berurutan. Rincian dashboard dibatasi 50 unit; agregat mencakup seluruh data.

| Ukuran | Hasil run terisolasi |
|---|---|
| p95 layanan dashboard | 2.407,44 ms |
| p95 simpan batch 20 baris | 273,08 ms |
| Puncak memori PHP proses benchmark | 28 MB |

Angka berubah mengikuti beban mesin. Saat build dan test lain berjalan bersamaan, run pengembangan pernah menghasilkan p95 dashboard 4.938,64 ms. Ini alasan pengukuran lokal tidak dipakai untuk menyatakan kapasitas 30 pengguna cPanel sudah memenuhi batas. Benchmark layanan juga tidak mencakup jaringan, HTTP, proses PHP hosting dan rendering browser.

## Pemeriksaan yang masih bergantung lingkungan tujuan

- Domain/subdomain dan akun cPanel yang benar, document root server/public, versi PHP CLI/web minimal 8.4.1 serta ekstensi.
- Database runtime dengan kredensial tersendiri; kompatibilitas aktual server tujuan. Suite MySQL 8.4 pada CI telah disiapkan tetapi belum diklaim dijalankan di GitHub.
- HTTPS, sertifikat, cookie Secure/HttpOnly, proxy/cache hosting, header keamanan serta akses berkas privat melalui web.
- Uji beban HTTP 30 pengguna pada data acuan dan server tujuan; skrip k6 tersedia tetapi belum dijalankan di cPanel.
- Backup terjadwal, salinan di lokasi berbeda, restore pada infrastruktur tujuan dan penerimaan RPO/RTO oleh pengelola.
- UAT organisasi, pengesahan master/peran, kebijakan retensi dan perangkat Android nyata. Lebar browser 360 px bukan bukti pengujian perangkat fisik.
- Audit aksesibilitas menyeluruh/screen reader dan penetrasi independen belum dilakukan; perbaikan keyboard/dialog dan label tidak dipresentasikan sebagai sertifikasi.

Hosting yang dipilih pengguna adalah cPanel. Panduan SSH lama pada repositori menyebut proyek lain sehingga bukan bukti bahwa akses dan domain tersebut berlaku bagi DMF. Diperlukan penetapan target sebelum mengunggah atau mengubah layanan di hosting.

## Reproduksi

Jalankan build, php artisan test pada database uji, audit dependensi, dan pemeriksaan format. scripts/check.ps1 serta workflow CI menggabungkan pemeriksaan dasar. Browser test memerlukan server lokal dan demo terisolasi. backend/scripts/race-check.php dan benchmark.php menolak koneksi selain database MySQL bernama _test_; admin-race-check.php memerlukan database kosong bernama _admin_test_. Jangan menjalankan suite RefreshDatabase terhadap database resmi.

Dokumen ini mencatat bukti lokal, bukan persetujuan peluncuran. Artefak rilis harus diperiksa pada hosting tujuan menggunakan production.md dan cpanel.md.
