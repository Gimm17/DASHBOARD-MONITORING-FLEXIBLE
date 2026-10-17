# Pengujian beban pada staging

Benchmark lokal di backend/scripts/benchmark.php mengukur layanan database secara berurutan. Pengujian itu berbeda dari 30 sesi HTTP pada cPanel. Jalankan pengujian berikut hanya pada staging yang disetujui pengelola, dengan data fiktif dan backup. Hasil server target belum tersedia pada rilis ini.

Sediakan 100 kegiatan aktif, 20 wilayah, dan setidaknya 100.000 realisasi pada database staging. Gunakan PHP dan database yang sama jenis/kapasitasnya dengan produksi. Jangan menjalankan php artisan serve untuk ukuran kapasitas produksi. Catat paket CPU/RAM/entry process cPanel, versi PHP/database, OPcache, tanggal dan latensi jaringan.

Pasang k6 pada mesin penguji. Isi DMF_BASE_URL, DMF_LOAD_EMAIL, DMF_LOAD_PASSWORD pada environment mesin penguji. Akun harus akun uji yang memiliki cakupan data acuan. Jalankan:

```text
k6 run --summary-export hasil-dashboard.json tests/performance/monitoring.js
```

Default: 30 pengguna selama 5 menit, dashboard dibaca berulang dengan jeda 1 detik. Cookie sesi dipelihara otomatis per virtual user; setiap pengguna mengambil CSRF dan login sendiri. Batas awal aplikasi adalah 30 login per IP/menit; beri waktu sebelum pengulangan tes agar pengujian login tidak mengganggu metrik dashboard.

Untuk campuran baca/tulis, siapkan berkas JSON berisi satu kegiatan berbeda untuk setiap pengguna dengan periode terbuka hari ini dan 20 wilayah yang sudah terdaftar. Kegiatan memakai tipe count, presisi nol, dan target yang cukup. Bentuk setiap entri: `{ "activity_id": 1, "period_id": 1, "region_ids": [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20] }`. Isi ID yang benar; skrip menolak jumlah fixture yang tidak mencukupi.

Tetapkan DMF_LOAD_MODE=mixed dan DMF_LOAD_FIXTURES ke lokasi JSON relatif terhadap skrip k6, lalu jalankan perintah yang sama. Setiap lima iterasi, pengguna membaca versi terbaru dan menyimpan 20 angka yang dinaikkan satu. Angka ini sengaja mengubah database uji; jangan memakai kegiatan resmi. Riwayat akan berisi audit pengujian. Kunci permintaan berbeda untuk tiap iterasi.

Ambang awal: p95 dashboard <3.000 ms, p95 batch20 <3.000 ms, kesalahan aplikasi/HTTP <1%. Latensi login dan pembacaan detail diberi tag terpisah. Ekspor ringkasan dan log server bersama hasil; jika ambang gagal, perbaiki query, kapasitas atau konfigurasi kemudian ulangi. Pengujian status konflik dan finalisasi bersamaan tetap dilakukan oleh skrip race-check terpisah. K6 ini sudah disiapkan, tetapi belum dijalankan pada hosting cPanel tujuan.

API k6 menggunakan [cookie jar per pengguna](https://grafana.com/docs/k6/latest/using-k6/cookies/) dan [threshold metrik](https://grafana.com/docs/k6/latest/using-k6/thresholds/).
