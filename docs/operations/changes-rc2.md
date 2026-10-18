# Perbaikan rilis 1.0.0-rc.2

Tanggal: 18 Oktober 2026, WITA. Gunakan paket rc.2 untuk pemasangan baru atau pembaruan kode rc.1. Tidak ada perubahan skema atau pengubahan otomatis data lama pada perbaikan ini.

## Perubahan perilaku

1. Input dan koreksi realisasi memeriksa periode terdekat sebelum serta sesudahnya. Contoh: nilai 30 lalu 75, kemudian koreksi nilai pertama menjadi 100, sekarang menghasilkan tiket menunggu persetujuan PJK. Angka tersimpan tetap 30 sampai disetujui. Memasukkan periode kedua lebih dahulu juga tidak melewati pemeriksaan ini. Perbandingan mempertimbangkan angka kerja dan angka resmi ketika evaluasi tetangga sedang dibuka untuk koreksi.
2. Penyimpanan konfigurasi aktif menolak daftar periode atau peserta kosong. Penolakan tidak mengubah versi kegiatan, jadwal, keikutsertaan atau versi target. Draf yang belum lengkap tetap dapat disimpan.
3. Tanggal keikutsertaan awal peserta mengikuti tanggal mulai draf yang diperbarui selama belum ada laporan. Tanggal peserta kegiatan aktif dan riwayat final tetap dipertahankan.

## Verifikasi

Enam tes regresi ditambahkan untuk input historis, urutan input terbalik, batas nilai resmi selama koreksi, transaksi penolakan konfigurasi kosong, sinkronisasi tanggal draf, serta perlindungan draf kosong/riwayat aktif. Kegagalan asli direproduksi sebelum kode diperbaiki.

- Backend SQLite: 45 tes lulus, 284 assertions.
- Backend PDO MySQL terhadap MariaDB: 45 tes lulus, 284 assertions, database khusus pengujian.
- Build frontend berhasil; format PHP diperiksa.
- Dua skenario browser Chrome lulus dengan database fiktif terisolasi, termasuk koreksi kegiatan tertutup, persetujuan PJK, riwayat revisi dan navigasi seluler.
- Pengujian dua proses terhadap finalizer-first dan writer-first tetap lulus; perubahan tidak melewati penguncian finalisasi.
- Pemeriksaan independen terfokus pada perbaikan tidak menemukan regresi yang perlu ditindaklanjuti.

Syarat penerapan cPanel, HTTPS, backup, UAT dan uji beban pada server tujuan tetap mengikuti production.md dan cpanel.md. Perbaikan kode ini tidak menyatakan bahwa hosting produksi sudah dipasang atau kapasitas server tujuan sudah teruji.
