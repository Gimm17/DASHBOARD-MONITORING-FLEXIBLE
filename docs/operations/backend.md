# Kontrak backend dan keputusan implementasi P0

## Arsitektur

Laravel berada di backend; Vue SPA tetap di src dan dibangun ke backend/public/spa. Semua API menggunakan awalan /api di middleware web agar sesi dan CSRF berlaku pada satu origin. Tidak ada pendaftaran umum, token yang disimpan pada localStorage, atau akses database langsung dari browser. Perubahan dari rancangan awal Inertia dibuat untuk mempertahankan frontend yang sudah dikerjakan.

Domain Access menangani hak akses, Activities menangani konfigurasi dan target, Reporting menangani transaksi input/koreksi/finalisasi, EvaluationCalculator menangani angka, Dashboard menangani query agregasi dan rincian. Controller memvalidasi kontrak HTTP. Seluruh perubahan domain mencatat audit dalam transaksi yang sama.

## Peran dan pembatasan

| Peran | Cakupan | Kemampuan |
|---|---|---|
| admin | province, id 0 | Administrasi, konfigurasi, input, evaluasi, buka koreksi |
| pjk | team atau activity | Kelola kegiatan sesuai penugasan, input, finalisasi, review tiket |
| operator | team atau activity | Input realisasi sesuai penugasan |
| pimpinan | province, id 0 | Lihat seluruh kegiatan |
| wilayah | region | Lihat data wilayah sendiri |

Beberapa penugasan digabung sebagai grant lengkap per pasangan peran/cakupan. Misalnya operator tim A sekaligus pembaca wilayah B tidak memperoleh hak input pada tim lain. Filter berlaku sebelum agregasi dashboard, ekspor, revisi dan audit. Akun tidak aktif ditolak meskipun sesi masih ada. Perubahan password/nonaktif mencabut sesi database. Penghapusan hak admin memakai penguncian global agar perubahan bersamaan tidak menghilangkan admin terakhir.

## Endpoint utama

Semua endpoint selain csrf/login memerlukan sesi aktif. Mutasi menggunakan X-CSRF-TOKEN dari GET /api/csrf; login mengembalikan token baru setelah regenerasi sesi. Kirim Accept application/json. Respons kesalahan mengikuti JSON Laravel dengan message dan errors bila ada. Status penting: 401 belum login, 403 di luar akses, 409 konflik versi/idempotensi, 419 sesi/CSRF tidak berlaku, 422 validasi, 423 periode terkunci, 429 pembatasan percobaan.

| Metode dan path sesudah /api | Fungsi |
|---|---|
| GET csrf; POST login/logout; GET me | Sesi dan identitas |
| GET references | Referensi tim/wilayah/satuan/PJK dan penugasan akun |
| POST masters/{teams,regions,units} | Tambah/perbarui master oleh admin |
| GET/POST users | Daftar dan pengelolaan akun oleh admin |
| POST schedule | Menghasilkan tanggal tanpa menyimpan kegiatan |
| POST activities; GET/PUT activities/{id} | Membuat, membaca, memperbarui konfigurasi |
| POST activities/{id}/status | Transisi draf/aktif/selesai/batal/arsip |
| POST periods/{id}/realizations | Input rows maksimum 20; header Idempotency-Key wajib |
| POST periods/{id}/finalize | Kunci evaluasi; version periode wajib |
| POST periods/{id}/reopen | Admin membuka koreksi; reason wajib |
| GET periods/{id}/revisions | Riwayat snapshot sesuai cakupan wilayah |
| GET tickets; POST tickets/{id}/review | Review approve/clarify/reject dengan catatan |
| GET dashboard; GET export | Agregasi/rincian dan CSV dengan filter sama |
| GET audit | Riwayat perubahan, 50 per halaman |

Dashboard/export mendukung year, team_id, activity_id, region_id, period_id dan status. Page pada dashboard mengatur rincian unit, 50 per halaman; ringkasan selalu mencakup semua unit yang sesuai filter. Tanggal dan batas masukan jadwal dibatasi pada rentang kegiatan di satu tahun. Presisi indikator 0–4, target positif, persentase 0–100; target kumulatif tidak menurun dan target terakhir sama dengan target akhir.

## Input dan transaksi

Nilai disimpan DECIMAL(18,4), dibandingkan dengan BCMath sebelum pembulatan. Nilai kosong menjadi null; angka 0 tetap laporan yang tersedia. Realisasi setiap periode adalah jumlah kumulatif, bukan tambahan. Belum ada data dan belum waktunya tidak disamakan dengan nol.

Contoh payload batch:

```json
{"rows":[{"region_id":1,"value":"105","position_date":"2026-10-12","version":0,"notes":"","reason":""}]}
```

Version berasal dari sel/provisional terbaru, bukan versi konfigurasi kegiatan. Kunci idempotensi 8–100 karakter alfanumerik, garis bawah atau tanda hubung berlaku per pengguna/periode. Payload sama dengan kunci sama mengembalikan hasil tersimpan per baris; payload berbeda ditolak 409. Untuk percobaan baru setelah memperbaiki data gunakan kunci baru. Hasil tiap baris: saved, pending, invalid, forbidden atau conflict. Kegagalan satu baris validasi tidak membatalkan baris yang sudah tersimpan. Pembatasan periode/akses menolak permintaan.

Urutan kunci perubahan selalu activity → period → realization/ticket. Penulis dan finalisasi memakai urutan sama. Pembacaan penentu routing dilakukan sebelum transaksi; pembacaan data resmi dilakukan setelah kunci diperoleh supaya snapshot MySQL tidak tertinggal dari transaksi penulis yang baru selesai. Kondisi writer-first dan finalizer-first diuji menggunakan proses/koneksi terpisah.

Penurunan terhadap nilai tersimpan atau periode sebelumnya, termasuk mengosongkan laporan yang sudah berangka, menghasilkan tiket tanpa mengubah nilai efektif. Persetujuan memeriksa versi sumber; jika sudah berubah, tiket tidak boleh menimpa data terbaru. Klarifikasi/penolakan menyimpan catatan, tanpa mengirim pesan ke pihak luar.

## Target, peserta dan evaluasi resmi

target_sets berisi konfigurasi immutable dengan proyeksi target_values. Pointer current_target_set_id menunjuk versi kerja. Finalisasi membuat evaluation_revisions dan evaluation_items yang tidak diperbarui; period menyimpan pointer revisi resmi terakhir. Snapshot menyimpan nilai/target/status, versi target, versi kegiatan, versi aturan dan batas laporan. Membuka koreksi mempertahankan tampilan resmi terakhir sampai finalisasi revisi berikutnya. Riwayat lama tetap dapat dibaca.

Peserta baru pada kegiatan aktif efektif pada tanggal bergabung; keluar efektif dari tanggal keluar. Peserta yang pernah keluar tidak masuk ulang pada P0. Riwayat final mempertahankan keikutsertaan pada snapshot; keluarnya peserta pada hari yang sama tidak menghapus evaluasi resmi, termasuk ketika evaluasi tersebut dikoreksi kemudian.

Pembatalan memiliki cancelled_on yang tetap ada setelah status menjadi ARSIP. Periode sebelum pembatalan dan snapshot final tetap tersimpan; periode sesudah pembatalan tidak muncul kembali akibat pengarsipan. Pembatalan draf tidak membuat evaluasi kewajiban baru. Kegiatan selesai/batal/arsip dapat dikoreksi hanya pada evaluasi final yang dibuka oleh admin; input rutin tidak diaktifkan kembali. Konfigurasi kegiatan tertutup hanya dapat diubah admin selama ada koreksi terbuka.

Dashboard menghitung unit kegiatan × wilayah × periode yang sudah tiba dan dinilai. Data kosong tetap berada pada penyebut tingkat ketercapaian. Volume dengan satuan berbeda tidak dijumlahkan. Agregat jumlah dalam satu kegiatan dapat dijumlahkan; indikator persentase tidak memperoleh total volume palsu. Grafik mingguan memakai waktu pertama laporan berangka disimpan, bukan waktu edit terakhir.

## Keterbatasan rilis P0

Satu indikator utama per kegiatan; tidak ada impor spreadsheet bebas, lampiran file, offline, SSO, notifikasi eksternal, pembobotan gamifikasi atau peringkat tahunan. Jadwal tanggal khusus tersedia, namun input bukan mesin formulir variabel tak terbatas. Daftar tiket menampilkan 500 tiket terbaru dan audit memiliki paginasi. Kebijakan retensi, cakupan organisasi resmi, bobot penilaian dan keputusan UAT ditetapkan organisasi.
