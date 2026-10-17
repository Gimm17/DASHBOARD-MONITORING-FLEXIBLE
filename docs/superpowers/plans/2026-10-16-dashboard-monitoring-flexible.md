# Dashboard Monitoring Flexible Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun aplikasi monitoring kegiatan yang dapat dikonfigurasi, dengan input provinsi, target per wilayah dan periode, dashboard, serta riwayat evaluasi yang dapat ditelusuri.

**Architecture:** Satu aplikasi monolit modular. Laravel menangani akses, validasi, transaksi, dan penghitungan; Vue melalui Inertia menangani formulir dan dashboard. Basis data relasional menyimpan target berversi, realisasi terkini, audit, dan hasil evaluasi final.

**Tech Stack:** Usulan acuan Laravel 13, PHP minimal 8.3, Vue 3, TypeScript, Inertia, Tailwind CSS, dan MySQL 8. Versi paket frontend mengikuti starter resmi yang dipilih dan dikunci pada lockfile. Ini keputusan rancangan, belum keputusan pemilik produk.

**Spec:** E:/BPS/DASHBOARD-MONITORING-FLEXIBLE/output/prd/PRD_Dashboard_Monitoring_Flexible_v1.0.docx

Versi 1.0 | 16 Oktober 2026 | Untuk peninjauan rencana teknis

Dokumen ini menerjemahkan PRD versi 1.0 menjadi urutan pekerjaan yang dapat diperiksa. Setiap tugas memiliki hasil, dependensi, berkas, antarmuka, langkah kerja, dan bukti penerimaan. Rencana tidak menyatakan bahwa kode, pengujian, atau penerapan sudah dilakukan.

Dasar versi awal: satu indikator utama, realisasi kumulatif, penginput di provinsi, dan akses lihat bagi wilayah. Gamifikasi berada pada tahap P1. Semua jalur kode yang disebut setelah bagian konteks merupakan jalur usulan relatif terhadap akar proyek.

<!-- page -->

## 1 Cakupan dan batasan global

### Global Constraints

- Acuan kebutuhan adalah PRD versi 1.0 tanggal 16 Oktober 2026. P0 mencakup FR-01 sampai FR-21; P1 mencakup FR-22 sampai FR-24 serta fitur lanjutan yang disepakati.
- Zona waktu aplikasi menggunakan Asia/Makassar.
- Nilai 30 pada periode pertama dan 75 pada periode kedua berarti total capaian periode kedua adalah 75.
- Kosong berarti belum ada laporan; nol berarti laporan sudah tersedia dengan capaian nol.
- Gunakan nilai asli untuk menentukan status; pembulatan hanya untuk tampilan, maksimal dua angka desimal.
- Target akhir dan target periode lebih besar dari nol. Target kumulatif tidak menurun; target periode terakhir sama dengan target akhir.
- Hasil final dikunci. Pembukaan kembali hanya dilakukan admin dengan alasan; koreksi menghasilkan versi hasil baru dengan jejak versi sebelumnya.
- Target uji awal: 20 wilayah, 100 kegiatan aktif, 100.000 baris realisasi, dan 30 pengguna bersamaan; p95 maksimal 3 detik untuk dashboard dan simpan hingga 20 baris pada lingkungan uji yang disepakati.
- Usulan pencadangan harian, RPO 24 jam, RTO 8 jam. Uji ponsel menggunakan lebar 360 px dan setidaknya satu perangkat Android.

### Review Focus

- Pengguna dengan beberapa penugasan tidak memperoleh akses gabungan yang lebih luas daripada penugasan validnya. Uji pada T02.
- Klik simpan berulang atau respons jaringan yang hilang tidak membuat data ganda. Uji pada T06.
- Simpan realisasi yang bersamaan dengan finalisasi tidak boleh lolos setelah periode dikunci. Uji pada T07 dengan koneksi basis data terpisah.
- Tanggal bulanan yang tidak tersedia dan tanggal kabisat menghasilkan jadwal yang benar. Uji pada T04.
- Data periode lama atau koreksi sesudah final tidak dianggap sebagai laporan baru atau menghapus hasil historis. Uji pada T07 dan T08.

### Peta dokumen

Bagian 2 sampai 4 menetapkan arsitektur, data, dan kontrak. T00 sampai T10 menjelaskan pekerjaan P0 secara berurutan. Bagian 6 memetakan kebutuhan dan pengujian, bagian 7 memberi estimasi, bagian 8 menjelaskan rilis, dan bagian 9 menjabarkan P1 serta keputusan terbuka.

<!-- page -->

## 2 Konteks proyek dan arsitektur acuan

Pemeriksaan folder menemukan dokumen PRD, berkas kerja penyusunan dokumen, dan panduan umum SSH/cPanel. Belum ada kerangka aplikasi, manifest paket, migrasi, atau test suite. Panduan hosting merupakan referensi proyek lain; alamat, kapasitas, dan akses produksi aplikasi ini belum diverifikasi.

### 2 1 Keputusan arsitektur

| Area | Rancangan | Alasan dan batas |
| --- | --- | --- |
| Aplikasi | Laravel dan Vue melalui Inertia | Satu deploy dan satu sistem sesi; tidak membutuhkan API publik terpisah untuk P0. |
| Penyimpanan | MySQL 8 dengan transaksi InnoDB | Mendukung batas unik, penguncian baris, dan transaksi input serta audit. Versi server dikonfirmasi pada T00. |
| Logika domain | Action untuk perubahan; query untuk pembacaan | Controller tipis; rumus dapat diuji tanpa antarmuka. |
| Antarmuka | Formulir konfigurasi, tabel input, tiga dashboard | Komponen bersama untuk target, status, filter, dan kesalahan per baris. |
| Autentikasi | Sesi aplikasi dan akun yang dikelola admin | Pendaftaran umum dinonaktifkan. Otorisasi tetap di server. |
| Operasi P0 | Proses web dan pencadangan terjadwal | Status dihitung berdasarkan waktu saat dibaca; tidak membutuhkan worker terus-menerus. |

Laravel 13 memerlukan PHP minimal 8.3; starter Vue resmi menyediakan Vue, TypeScript, dan Inertia [R1, R2]. Versi rinci paket harus dibuktikan melalui pemeriksaan dependensi pada T00 dan dikunci pada T01.

### 2 2 Peta berkas sebelum implementasi

| Lokasi usulan | Tanggung jawab |
| --- | --- |
| app/Domain/Access | Penugasan dan pembatasan cakupan pengguna. |
| app/Domain/Monitoring | Kegiatan, peserta, jadwal, indikator, dan target. |
| app/Domain/Reporting | Input realisasi, idempotensi, dan audit. |
| app/Domain/Evaluation | Rumus, status, finalisasi, dan koreksi. |
| app/Domain/Dashboard | Query ringkasan dan rincian sesuai cakupan. |
| app/Http dan app/Policies | Controller, validasi permintaan, serta policy. |
| resources/js/pages dan components | Halaman Vue dan komponen bersama. |
| tests/Unit, Feature, Browser, Performance | Uji rumus, aplikasi, alur layar, dan beban. |

Model Eloquent ditempatkan di app/Models. Skema basis data berada di database/migrations. Hindari repository generik yang hanya membungkus Eloquent tanpa kebutuhan nyata.

<!-- page -->

## 3 Skema data dan strategi perubahan

Gunakan ID internal, foreign key, timestamp UTC, dan tampilan waktu Asia/Makassar. Nilai jumlah atau persentase disimpan sebagai DECIMAL(18,4); presisi indikator 0 sampai 4. Angka dibandingkan sebagai desimal, bukan float. Hasil persentase dihitung presisi memadai dan dibulatkan dua desimal hanya saat ditampilkan.

| Tabel | Kolom dan batas kunci |
| --- | --- |
| regions dan teams | code unik, name, is_active. Referensi yang pernah digunakan dinonaktifkan. |
| users dan role_assignments | role, scope_type, scope_id, is_active; kombinasi pengguna, peran, dan cakupan unik. |
| activities | code unik, title, reporting_year, team_id, owner_id, starts_on, ends_on, status, lock_version. |
| activity_participants | activity_id, region_id, joined_on, left_on; satu baris per pasangan dan riwayat perubahan keikutsertaan. |
| indicators | activity_id, title, value_type, unit, precision, direction; satu indikator aktif pada P0. |
| evaluation_periods | activity_id, sequence, position_on, due_at, state, lock_version, current_final_revision_id; tanggal unik per kegiatan. |
| target_sets dan target_values | Set versi per kegiatan dan pembuat; nilai final per peserta/indikator dan nilai per periode. Set yang digunakan final tidak diubah. |
| realizations | participant_id, indicator_id, period_id, value, position_on, first_submitted_at, updated_by, lock_version; kombinasi tiga ID unik. |
| evaluation_revisions dan evaluation_items | Nomor revisi, pelaku, waktu, aturan, nilai target/realisasi, status, alasan pengecualian, dan acuan versi sumber. |
| audit_logs dan idempotency_requests | Nilai sebelum/sesudah dan pelaku; kunci idempotensi, hash payload, status hasil, dan masa berlaku teknis. |

### 3 1 Target dan hasil historis

Penyuntingan target membuat target_set baru secara atomik setelah seluruh matriks valid. Aktivitas menunjuk set aktif. Evaluasi final menyimpan angka dan ID versi target yang dipakai; pergantian set aktif tidak menghitung ulang hasil final.

Pembukaan kembali membuat periode dapat dikoreksi tanpa menghapus revisi final lama. Setelah finalisasi ulang, penunjuk current_final_revision_id berpindah ke revisi baru. Dashboard historis memakai revisi resmi yang dipilih; layar koreksi menampilkan perubahan sementara secara terpisah.

### 3 2 Keikutsertaan dan cakupan agregasi

Tanggal keikutsertaan menentukan apakah wilayah wajib pada periode tertentu. Mengeluarkan peserta tidak menghapus kewajiban atau hasil periode sebelum tanggal keluar. P0 tidak mendukung keluar lalu bergabung ulang dalam kegiatan yang sama tanpa perubahan spesifikasi. Indikator jumlah yang sebanding dapat diagregasi sebagai jumlah realisasi dibagi jumlah target. Persentase mentah antarwilayah tidak dianggap sebagai progres volume gabungan tanpa pembilang dan penyebut sumber.

<!-- page -->

## 4 Kontrak layanan dan transaksi

Semua signature berikut adalah antarmuka rancangan. DTO ditempatkan pada folder Data di domain pemiliknya; enum dan hasil uji diberi nama konsisten. CarbonImmutable digunakan untuk waktu; LocalDate adalah string ISO YYYY-MM-DD yang sudah divalidasi.

| Antarmuka dan pemilik | Masukan dan hasil |
| --- | --- |
| AccessScope::forUser(User): ScopeSet | Menghasilkan penugasan valid; canAccessActivity(User, Activity, operation): bool mengevaluasi satu penugasan utuh. T02. |
| ActivityWriter::save(User, ActivityData, expectedVersion): Activity | Menyimpan draf atau konfigurasi valid dan audit; activate(User, Activity, expectedVersion): Activity memvalidasi kelengkapan. T03. |
| ScheduleGenerator::generate(ScheduleData): array<LocalDate> | Menghasilkan tanggal terurut dan unik; tanpa efek samping. T04. |
| TargetWriter::replace(User, Activity, TargetMatrixData, expectedVersion): TargetSet | Menyimpan seluruh matriks sebagai satu versi baru. T04. |
| EvaluationCalculator::calculate(EvaluationInput): EvaluationResult | Input desimal, waktu acuan, status peserta dan periode; hasil status, progres akhir, serta capaian periode. T05. |
| RealizationWriter::saveRow(User, RealizationRowData, expectedVersion): RowResult | Hasil saved, invalid, conflict, forbidden, atau locked; simpan angka dan audit dalam satu transaksi. T06. |
| FinalizeEvaluation::handle(User, EvaluationPeriod, expectedVersion): EvaluationRevision | Menyimpan snapshot semua peserta layak dan mengunci periode. ReopenEvaluation::handle(User, period, reason): void. T07. |
| DashboardQuery::forScope(User, DashboardFilter): DashboardData | Mengembalikan ringkasan, daftar penyusun, cakupan data, dan tanggal acuan yang konsisten. T08. |

### 4 1 Permintaan dan respons penting

POST /activities membuat draf. PATCH /activities/{id} mengubah konfigurasi; POST /activities/{id}/activate mengaktifkan. PUT /activities/{id}/targets mengganti set target. POST /periods/{id}/realizations/batch menyimpan maksimal 20 baris dengan Idempotency-Key. POST /periods/{id}/finalize dan /reopen mengubah status evaluasi. GET /dashboard, /activities/{id}, dan /regions/{id} membaca hasil sesuai cakupan.

Batch mengembalikan HTTP 200 dengan batch_id dan results per row; setiap item memiliki row_key, status, errors, version, dan nilai tersimpan. Gunakan 422 untuk bentuk permintaan tidak valid, 403 untuk akses seluruh endpoint ditolak, 409 untuk kunci idempotensi dengan payload berbeda, dan 423 untuk seluruh periode terkunci. Kesalahan sebagian baris tidak membatalkan baris lain yang sudah sukses.

### 4 2 Urutan penguncian

Setiap perubahan sumber mengunci activity lalu period lalu baris data bila diperlukan, dalam urutan yang sama. Finalisasi mengunci induk yang sama sebelum membaca snapshot. Periksa status dan expectedVersion setelah lock diperoleh. Transaksi per baris mencegah sukses palsu; retry deadlock dibatasi dan mengulang keseluruhan transaksi.

<!-- page -->

## 5 Rincian pekerjaan P0

### T00 Konfirmasi dasar dan kelayakan lingkungan

Dependensi: PRD tersedia. Penanggung jawab: pemilik produk, pengembang, dan pengelola server. Hasil: keputusan teknis yang cukup untuk memulai, tanpa koneksi atau perubahan produksi pada tahap penyusunan rencana ini.

**Berkas:** buat docs/decisions/001-platform.md, 002-input-and-access.md, 003-target-and-schedule.md, docs/operations/environment-check.md, dan docs/acceptance/uat-data.csv.

**Interfaces:** mengonsumsi D01 sampai D05, D08 dan D09 pada PRD. Menghasilkan matriks keputusan berstatus diputuskan atau tertunda, pilihan platform, serta dataset UAT. Tugas pengembangan yang bergantung keputusan tertunda tidak dimulai.

- [ ] Langkah 1: validasi penginput provinsi, PJK penetap target, satu indikator kumulatif, akses wilayah sendiri, dan aturan koreksi. Catat pihak serta tanggal keputusan.
- [ ] Langkah 2: minta pengelola menjalankan php -v, php -m, composer --version, serta pemeriksaan versi MySQL pada lingkungan yang akan dipakai. Catat dukungan PHP minimal 8.3, transaksi InnoDB, ekstensi Laravel, HTTPS, document root, izin tulis, dan pencadangan.
- [ ] Langkah 3: tetapkan dependensi kompatibel dari starter Vue resmi dan lockfile. Node diperlukan pada mesin build; runtime Node di server tidak menjadi syarat bila SSR tidak dipakai. Jika platform tidak memenuhi syarat, pilih lingkungan yang sesuai sebelum T01.
- [ ] Langkah 4: buat data contoh tiga wilayah, dua tim, satu kegiatan harian dan satu kegiatan tanggal khusus, target berbeda, laporan nol/kosong, dan satu periode final. Gunakan nama serta angka fiktif.
- [ ] Langkah 5: tinjau dokumen keputusan. Lulus bila tidak ada keputusan P0 yang mengubah model akses, data, atau penghitungan yang masih ambigu. Simpan commit dokumentasi.

### T01 Fondasi aplikasi dan pemeriksaan otomatis

Dependensi: T00. Berkas: composer.json, package.json, lockfile, .env.example, routes/web.php, config/app.php, migrasi serta model regions/teams/activities sesuai Bagian 3, tests/Feature/FoundationTest.php, dan .github/workflows/ci.yml bila menggunakan GitHub; gunakan skrip CI setara jika platform berbeda.

**Interfaces:** menghasilkan aplikasi yang dapat login, koneksi uji MySQL, dan perintah standar composer install, npm ci, php artisan test, npm run build. Tidak membuat akun produksi bawaan.

- [ ] Langkah 1: scaffold starter resmi Laravel–Vue pada checkout proyek; pertahankan dokumen yang sudah ada dan lakukan inisialisasi Git hanya bila belum menjadi repositori.
- [ ] Langkah 2: tulis FoundationTest yang memeriksa aplikasi berjalan, pendaftaran umum tidak tersedia, dan waktu tampilan Asia/Makassar. Jalankan php artisan test --filter=FoundationTest dan catat kegagalan perilaku sebelum penyesuaian.
- [ ] Langkah 3: sesuaikan konfigurasi, akun admin melalui provisioning, dan CI tanpa rahasia. Buat skema dasar regions, teams, activities untuk fixture akses T02; pengelolaan bisnisnya dikerjakan T03. Gunakan basis data uji terpisah.
- [ ] Langkah 4: jalankan ulang test dan npm run build; lulus bila test hijau dan build selesai dengan exit code 0. Simpan commit fondasi.

<!-- page -->

### T02 Autentikasi dan cakupan hak akses

Dependensi: T01. Acuan: FR-01 sampai FR-03, UAT-01, NFR-05. Penanggung jawab: pengembang backend; tinjauan oleh pemilik produk untuk matriks peran.

**Berkas:** buat migrasi role_assignments dan audit_logs; app/Models/RoleAssignment.php; app/Domain/Access/AccessScope.php; app/Domain/Access/Data/ScopeSet.php; app/Domain/Audit/AuditWriter.php; app/Policies/{ActivityPolicy,EvaluationPolicy}.php; app/Http/Controllers/UserAdminController.php; resources/js/pages/admin/Users.vue; tests/Feature/Access/ScopeAuthorizationTest.php. Sesuaikan User.php dan middleware akun aktif.

**Interfaces:** mengonsumsi user sesi T01. Menghasilkan AccessScope::forUser(User): ScopeSet dan canAccessActivity(User, Activity, string $operation): bool. ScopeSet berisi daftar penugasan yang tidak dilebur menjadi hak lintas cakupan.

Penugasan yang valid adalah admin dengan scope provinsi; pimpinan dengan scope provinsi baca; ketua tim/operator dengan scope tim atau kegiatan; pengguna wilayah dengan scope wilayah baca. Pendaftaran dan pemberian peran hanya dilakukan admin. PJK penanggung jawab tidak otomatis memperoleh kewenangan lintas tim.

- [ ] Langkah 1: tulis test_guest_redirected_to_login, test_inactive_user_denied, dan test_operator_cannot_read_or_write_other_activity. Pakai fixture model T01 dan route uji sementara yang hanya terdaftar dalam test untuk memeriksa middleware/policy: tamu 302 dan pengguna tanpa hak 403. Endpoint domain nyata diuji kembali pada T03 dan T08.
- [ ] Langkah 2: tulis test_multiple_assignments_do_not_cross_expand_permissions. Operator tim A sekaligus pembaca wilayah B tidak boleh mengubah kegiatan tim B; dua penugasan harus dievaluasi masing-masing.
- [ ] Langkah 3: jalankan php artisan test --filter=ScopeAuthorizationTest; hasil awal harus gagal karena policy belum memenuhi kasus tersebut.
- [ ] Langkah 4: implementasikan service cakupan, policy, pemeriksaan akun aktif, dan filter query. Tambahkan pengelolaan akun dan penugasan khusus admin, reset akses melalui prosedur terverifikasi, serta AuditWriter::record(User, objectType, objectId, before, after, reason): void yang menggunakan transaksi pemanggil. Audit tersedia sebelum T03. Pembatasan menu bukan kontrol akses.
- [ ] Langkah 5: tambahkan test admin, ketua tim, pimpinan, dan wilayah pada endpoint langsung. Assert wilayah hanya menerima datanya sendiri, pimpinan tidak bisa PATCH, dan hanya admin dapat mengelola penugasan.
- [ ] Langkah 6: jalankan suite akses sampai seluruh kasus lulus. Simpan commit dengan hanya berkas T02 yang selesai.

**Definisi selesai:** login serta penonaktifan berlaku; matriks peran diterapkan di server; pengguna multi-penugasan tidak mendapat hak tambahan yang tidak sah. Bukti berupa hasil test akses dan matriks peran yang ditinjau.

<!-- page -->

### T03 Master dan konfigurasi kegiatan

Dependensi: T02. Acuan: FR-04 sampai FR-07, FR-10, BR-07 sampai BR-11 yang relevan, UAT-02. Penanggung jawab: backend dan frontend.

**Berkas:** gunakan model serta skema regions/teams/activities T01; buat migrasi activity_participants, indicators, dan units; model terkait; app/Domain/Monitoring/ActivityWriter.php; Data/ActivityData.php; app/Http/Requests/SaveActivityRequest.php; Controllers/{ActivityController,MasterController}.php; resources/js/pages/activities/{Index,Create,Edit}.vue; pages/admin/Masters.vue; tests/Feature/Monitoring/ActivityConfigurationTest.php.

**Interfaces:** ActivityData memuat id nullable, title, reporting_year, team_id, owner_id, starts_on, ends_on, participants, indicator, dan status draf. ActivityWriter::save(User, ActivityData, ?int $expectedVersion): Activity menghasilkan lock_version baru. activate(User, Activity, int $expectedVersion): Activity memanggil validasi target dan jadwal T04.

- [ ] Langkah 1: tulis test_create_draft_with_selected_regions dan test_inactive_master_cannot_be_selected. Assert hanya tiga peserta terpilih tersimpan dan data referensi nonaktif tidak dapat dipilih untuk kegiatan baru.
- [ ] Langkah 2: tulis test_only_one_primary_indicator_in_p0 dan test_region_removal_preserves_old_period_eligibility. Assert indikator kedua ditolak pada P0; tanggal keluar tidak menghilangkan hasil periode sebelumnya.
- [ ] Langkah 3: jalankan php artisan test --filter=ActivityConfigurationTest dan pastikan gagal pada perilaku yang belum ada.
- [ ] Langkah 4: implementasikan migrasi, model, writer, request, dan controller. Kode kegiatan unik; tanggal akhir tidak mendahului tanggal mulai; presisi indikator 0 sampai 4; tipe jumlah atau persentase. Perubahan menyimpan audit dan memeriksa expectedVersion.
- [ ] Langkah 5: buat halaman daftar, pengelolaan master wilayah/tim/satuan khusus admin, dan formulir bertahap untuk identitas, peserta, indikator, jadwal, serta target. Tampilkan status draf; jangan menyediakan aktivasi sebelum T04 lengkap. Master digunakan melalui pilihan dari server dan dinonaktifkan saat sudah dirujuk.
- [ ] Langkah 6: tambahkan aksi selesai, arsip, dan batal dengan alasan. Kegiatan selesai menolak input rutin; pembatalan menghentikan evaluasi baru dan mempertahankan hasil lama.
- [ ] Langkah 7: uji penonaktifan master, kegiatan lintas tahun yang perlu dipisah untuk rekap, serta pembatasan akses. Jalankan suite modul dan build frontend; simpan commit setelah lulus.

**Definisi selesai:** pengguna dapat membuat kegiatan draf dengan peserta terpilih dan indikator tanpa perubahan kode. Aktivasi lengkap diuji bersama T04. Keputusan tahun pelaporan: P0 membatasi jadwal ke tahun pelaporan; kebutuhan lintas tahun dipisahkan sebagai kegiatan per tahun sampai disepakati sebaliknya.

<!-- page -->

### T04 Jadwal dan target berversi

Dependensi: T03. Acuan: FR-08 sampai FR-10, BR-03, BR-08 sampai BR-10, UAT-03 dan UAT-04.

**Berkas:** buat migrasi evaluation_periods, target_sets, target_values; app/Domain/Monitoring/ScheduleGenerator.php; TargetWriter.php; Data/{ScheduleData,TargetMatrixData}.php; resources/js/components/monitoring/{ScheduleEditor,TargetMatrix}.vue; tests/Unit/Monitoring/ScheduleGeneratorTest.php; tests/Feature/Monitoring/TargetMatrixTest.php.

**Interfaces:** generate(ScheduleData): array<LocalDate> menerima starts_on, ends_on, frequency, weekday, day_of_month, weekdays_only, dan excluded_dates. replace(User, Activity, TargetMatrixData, expectedVersion): TargetSet menerima target akhir per peserta dan matriks target periode; penyimpanan satu set bersifat atomik.

- [ ] Langkah 1: tulis test_month_end_clamped. assert generate bulanan tanggal 31 menghasilkan 31 Januari, 28 Februari, dan 31 Maret 2027. test_leap_year_february mengharapkan 29 Februari 2028. Hari kerja mengecualikan Sabtu/Minggu; pengecualian manual ikut dikeluarkan.
- [ ] Langkah 2: tulis test_custom_dates_unique_and_in_range, test_non_decreasing_targets, test_last_target_matches_final, dan test_zero_target_rejected. Assert target [40,100,140] dengan final 140 valid; [40,30,140], nol, dan periode terakhir 130 ditolak.
- [ ] Langkah 3: jalankan php artisan test --filter=ScheduleGeneratorTest lalu --filter=TargetMatrixTest; pastikan kegagalan relevan.
- [ ] Langkah 4: implementasikan pembangkitan tanggal murni. Frekuensi mingguan mengikuti hari terpilih; triwulanan memakai tanggal akhir triwulan dalam rentang; daftar hasil dapat diedit sebelum disahkan. due_at diisi pengguna dalam Asia/Makassar dan disimpan UTC.
- [ ] Langkah 5: implementasikan versi target immutable, foreign key, validasi desimal dan rentang persen 0 sampai 100. Perubahan aktif membutuhkan alasan, lock induk, dan tidak mengubah snapshot final. Tambahkan test kegagalan satu sel membatalkan set baru seluruhnya.
- [ ] Langkah 6: lengkapi editor jadwal dan target, termasuk salin nilai ke beberapa wilayah serta tinjau tanggal. Hubungkan aktivasi T03: minimal satu peserta, satu indikator, satu periode, seluruh target layak lengkap.
- [ ] Langkah 7: jalankan unit, feature, dan build; simpan commit. Verifikasi tanggal terakhir boleh sebelum tenggat kegiatan namun targetnya harus sama dengan target akhir.

**Definisi selesai:** semua frekuensi dan tanggal khusus dapat digunakan; tanggal tidak valid ditolak; set target valid tersimpan sebagai satu versi; kebutuhan aktivasi lengkap lulus.

<!-- page -->

### T05 Mesin perhitungan capaian dan status

Dependensi: T04 untuk struktur; pengujian rumus dapat ditulis tanpa antarmuka. Acuan: BR-01 sampai BR-06, UAT-06 sampai UAT-09.

**Berkas:** buat app/Domain/Evaluation/EvaluationCalculator.php; Data/{EvaluationInput,EvaluationResult}.php; Enums/EvaluationStatus.php; tests/Unit/Evaluation/EvaluationCalculatorTest.php; AggregationCalculatorTest.php.

**Interfaces:** EvaluationInput berisi realisasi nullable, target akhir dan periode desimal, is_eligible, is_cancelled, evaluation_date, due_at, first_submitted_at, serta as_of. EvaluationResult berisi status, final_progress, period_attainment, is_late, dan has_report. Status dihitung dari nilai mentah; keterlambatan adalah atribut terpisah.

- [ ] Langkah 1: tulis test_period_attainment_differs_from_final_progress. calculate dengan realisasi 105, final 140, target periode 100 harus menghasilkan final_progress 75%, period_attainment 105%, dan tercapai setelah tanggal evaluasi tiba.
- [ ] Langkah 2: tulis test_null_is_not_zero, test_future_not_failed, test_rounding_does_not_change_status, dan test_non_participant_not_evaluated. Nilai 99,9999 dibanding target 100 tetap di bawah target walau tampilan dibulatkan 100,00%.
- [ ] Langkah 3: jalankan php artisan test --filter=EvaluationCalculatorTest. Pastikan kasus baru gagal, lalu implementasikan rumus memakai operasi desimal yang konsisten dan hindari float biner untuk pembandingan.
- [ ] Langkah 4: terapkan urutan status: tidak layak/dibatalkan → tidak dinilai; tanggal belum tiba → belum jatuh tempo; laporan kosong → belum ada data; realisasi minimal target periode → tercapai; sisanya di bawah target. Hitung is_late hanya untuk laporan yang pernah dikirim.
- [ ] Langkah 5: tulis AggregationCalculatorTest. assert 50/100 ditambah 9/10 menghasilkan 59/110 atau 53,64%, bukan rata-rata 70%. Campuran satuan ditolak dari agregat volume; cakupan laporan wajib terlihat.
- [ ] Langkah 6: implementasikan ringkasan kegiatan: target akhir terpenuhi → selesai; tenggat lewat dan belum terpenuhi → lewat tenggat; selain itu gunakan evaluasi terakhir yang jatuh tempo. Jika belum ada evaluasi jatuh tempo, tampilkan berjalan atau belum dimulai sesuai tanggal mulai.
- [ ] Langkah 7: jalankan kedua kelas test sampai lulus dan simpan commit. Nilai lebih dari target jumlah tetap ditampilkan; persentase mentah di atas 100 ditolak sebelum masuk kalkulator.

**Definisi selesai:** kalkulator deterministik pada waktu acuan yang sama, membedakan nol/kosong dan dua jenis persentase, serta tidak menghapus status periode lama ketika target akhir tercapai.

<!-- page -->

### T06 Input realisasi per baris dan batch

Dependensi: T02 sampai T05. Acuan: FR-11 sampai FR-14, FR-16, BR-07 dan BR-09, UAT-05 sampai UAT-07.

**Berkas:** buat migrasi realizations dan idempotency_requests; gunakan AuditWriter T02; app/Domain/Reporting/RealizationWriter.php; BatchRealizationWriter.php; Data/{RealizationRowData,RowResult,BatchResult}.php; Http/Requests/SaveRealizationsRequest.php; Controllers/RealizationController.php; resources/js/pages/realizations/Edit.vue; tests/Feature/Reporting/BatchRealizationTest.php.

**Interfaces:** saveRow(User, RealizationRowData, ?int $expectedVersion): RowResult mengonsumsi peserta, indikator, periode, nilai, tanggal posisi, dan alasan koreksi. BatchRealizationWriter::handle(User, periodId, rows, idempotencyKey): BatchResult mengembalikan row_key serta hasil tiap baris; maksimum 20 baris per batch.

- [ ] Langkah 1: tulis test_partial_batch_reports_each_row. Satu baris valid dan satu nilai negatif harus menghasilkan satu saved, satu invalid; jumlah audit sama dengan jumlah perubahan berhasil.
- [ ] Langkah 2: tulis test_retry_same_payload_returns_same_result, test_reused_key_different_payload_conflicts, dan test_stale_version_does_not_overwrite. Dua simpan versi lama tidak boleh menimpa versi terbaru.
- [ ] Langkah 3: jalankan php artisan test --filter=BatchRealizationTest dan konfirmasi kegagalan. Implementasikan transaksi per baris, unique key, lock_version, waktu kirim pertama immutable, serta audit dalam transaksi yang sama.
- [ ] Langkah 4: implementasikan idempotensi per pengguna, endpoint, dan kunci. Simpan hasil setiap baris yang telah selesai; bila proses terhenti di tengah batch, retry memulihkan hasil tersimpan dan hanya melanjutkan baris belum diproses. Batasi penyimpanan cache kunci teknis menurut kebijakan operasi; unique key data tetap berlaku setelah cache dihapus.
- [ ] Langkah 5: buat tabel input yang menunjukkan target, realisasi sebelumnya, status simpan, dan kesalahan per baris. Klik berulang dinonaktifkan saat permintaan berjalan; jangan mengandalkan kontrol frontend untuk mencegah duplikasi. Baris gagal tetap dapat diedit tanpa mengirim ulang perubahan yang sukses sebagai data baru.
- [ ] Langkah 6: uji realisasi 30 lalu 75 tetap kumulatif 75, penurunan meminta alasan, dan kosong tidak dikonversi ke nol. Tolak input periode yang belum tiba atau tanggal posisi berbeda dari periode terpilih. Meninggalkan formulir yang berubah memunculkan konfirmasi.
- [ ] Langkah 7: jalankan feature tests dan build; simpan commit. Sertakan uji pembatalan transaksi yang memastikan angka tanpa audit tidak pernah tersimpan.

**Definisi selesai:** operator dapat mengisi banyak wilayah dengan umpan balik yang benar; retry aman, konflik terlihat, dan nilai serta waktu sumber dapat ditelusuri.

<!-- page -->

### T07 Finalisasi dan koreksi evaluasi

Dependensi: T04 sampai T06. Acuan: FR-14 sampai FR-16, BR-04, BR-05, UAT-08 dan UAT-10.

**Berkas:** buat migrasi evaluation_revisions dan evaluation_items; app/Domain/Evaluation/{FinalizeEvaluation,ReopenEvaluation}.php; app/Http/Controllers/EvaluationController.php; resources/js/pages/evaluations/Review.vue; tests/Feature/Evaluation/FinalizeEvaluationTest.php; tests/Integration/Evaluation/FinalizeRaceTest.php.

**Interfaces:** handle(User, EvaluationPeriod, expectedVersion): EvaluationRevision menghasilkan snapshot immutable seluruh peserta layak, termasuk status belum ada data. ReopenEvaluation::handle(User, period, reason): void hanya menerima admin, mempertahankan revisi resmi lama, dan membuka sumber untuk koreksi.

- [ ] Langkah 1: tulis test_finalize_locks_sources_and_records_versions. Assert target, realisasi, status, pelaku, waktu, serta rule_version tersalin; operator mendapat locked ketika mengubah periode final.
- [ ] Langkah 2: tulis test_reopen_requires_admin_and_reason serta test_refinalize_preserves_previous_revision. Setelah koreksi, revisi 1 tetap dapat dibaca, revisi 2 menjadi acuan resmi, dan alasan perubahan tersedia.
- [ ] Langkah 3: jalankan php artisan test --filter=FinalizeEvaluationTest. Implementasikan transaksi dengan urutan lock activity → period → sumber; baca sumber setelah lock, periksa akses dan expectedVersion, hitung hasil melalui T05, lalu simpan snapshot dan status final.
- [ ] Langkah 4: buat FinalizeRaceTest dengan dua koneksi MySQL ke basis data uji. Jalankan simpan bersamaan dengan finalisasi dan perubahan target bersamaan dengan finalisasi. Assert snapshot memakai satu keadaan data yang konsisten; tidak ada perubahan yang masuk setelah lock final berlaku.
- [ ] Langkah 5: jalankan php artisan test --filter=FinalizeRaceTest pada MySQL asli. Uji dengan SQLite tidak cukup untuk menyatakan perilaku lock produksi benar. Terapkan penanganan deadlock dengan retry transaksi terbatas.
- [ ] Langkah 6: bangun halaman tinjau yang memperlihatkan laporan kosong dan pengecualian, konfirmasi finalisasi, serta alasan buka kembali. PJK boleh memfinalkan laporan kosong sebagai belum ada data; P0 tidak otomatis mengubahnya menjadi penalti poin.
- [ ] Langkah 7: uji target akhir selesai tidak menghapus kegagalan periode sebelumnya. Jalankan suite evaluasi, dokumentasikan bukti revisi, dan simpan commit.

**Definisi selesai:** snapshot konsisten, final terkunci, koreksi berwenang mempertahankan riwayat, dan hasil resmi tidak bergantung pada target aktif yang kemudian berubah.

<!-- page -->

### T08 Dashboard dan penelusuran data

Dependensi: T02, T05 sampai T07. Acuan: FR-17 sampai FR-21, BR-06, UAT-09 dan UAT-11.

**Berkas:** buat app/Domain/Dashboard/{DashboardQuery,ActivityQuery,RegionQuery}.php; Data/{DashboardFilter,DashboardData}.php; Controllers/DashboardController.php; resources/js/pages/dashboard/Index.vue; pages/activities/Show.vue; pages/regions/Show.vue; components/monitoring/{StatusBadge,ProgressMetric,FilterBar,EvaluationMatrix}.vue; tests/Feature/Dashboard/DashboardConsistencyTest.php.

**Interfaces:** forScope(User, DashboardFilter): DashboardData menerima year, team_id, activity_id, region_id, period_id, status, dan as_of. Hasil berisi summaries, detail_rows, filters, data_coverage, calculated_at, dan is_provisional. DTO lain memakai nama filter yang sama.

- [ ] Langkah 1: tulis test_summary_equals_filtered_details, test_non_participant_excluded, dan test_scope_applies_before_aggregation. Assert angka ringkasan sama dengan jumlah detail yang dapat dibaca pengguna, bukan seluruh data provinsi.
- [ ] Langkah 2: tulis test_old_report_not_current_report dan test_official_revision_stable_during_reopen. Data periode sebelumnya boleh tampil berlabel tanggal lama; laporan periode kini tetap kosong. Hasil koreksi terbuka tidak menggantikan hasil resmi diam-diam.
- [ ] Langkah 3: jalankan php artisan test --filter=DashboardConsistencyTest. Implementasikan query terpusat yang memakai policy scope sebelum pengelompokan dan kalkulator T05 sebagai satu sumber aturan.
- [ ] Langkah 4: implementasikan denominator ringkasan. Unit evaluasi adalah peserta × indikator × periode wajib yang telah jatuh tempo. Laporan hilang masuk denominator ketercapaian; periode mendatang, pembatalan, dan peserta tidak layak dikecualikan. Tampilkan jumlah laporan tersedia dan wajib.
- [ ] Langkah 5: buat tiga layar dashboard. Setiap kartu membuka detail dengan filter identik; matriks menampilkan target, realisasi, dua persentase, status, tanggal pembaruan, dan kendala. Label hasil sementara dan hasil final harus terlihat.
- [ ] Langkah 6: uji paginasi, filter yang tidak memiliki data, wilayah tanpa kegiatan, dan tahun tanpa evaluasi. Denominator nol ditampilkan sebagai belum dapat dihitung, bukan 0% kinerja atau error.
- [ ] Langkah 7: jalankan feature tests, build, dan uji pengguna dengan data T00; simpan commit setelah angka layar dan rincian cocok.

**Definisi selesai:** pimpinan dapat menemukan wilayah tertinggal, membedakan laporan belum masuk, dan menelusuri asal angka. Ringkasan lintas kegiatan tidak dijadikan peringkat tahunan pada P0.

<!-- page -->

### T09 Kualitas antarmuka dan operasi

Dependensi: T06 sampai T08. Acuan: NFR-01 sampai NFR-10, UAT-12. Penanggung jawab: frontend, backend, dan pengelola server.

**Berkas:** buat playwright.config.ts; tests/Browser/monitoring.spec.ts; tests/Performance/dashboard.js; app/Console/Commands/VerifyBackupReadiness.php; docs/operations/{backup-restore,monitoring,security-baseline}.md; sesuaikan layout, CSS, konfigurasi sesi, dan throttling. Playwright dipasang sebagai dependensi pengembangan; k6 disiapkan pada mesin uji, bukan runtime produksi.

**Interfaces:** mempertahankan kontrak T06 dan T08. Menghasilkan laporan performa dengan data, perangkat, concurrency, durasi, p95, dan kegagalan, serta runbook pemulihan yang telah dicoba pada lingkungan terisolasi.

- [ ] Langkah 1: buat uji browser alur keyboard login → kegiatan → input → dashboard. Pada viewport 360 px, assert tombol utama terlihat, label tersedia, kesalahan terhubung ke isian, dan tabel dapat digulir tanpa menghilangkan identitas wilayah.
- [ ] Langkah 2: jalankan npx playwright test tests/Browser/monitoring.spec.ts; perbaiki fokus, status berbasis teks, kontras, dan tampilan. Verifikasi manual pada satu Android selain uji viewport.
- [ ] Langkah 3: buat fixture beban 20 wilayah, 100 kegiatan aktif, dan 100.000 realisasi pada basis data uji. Jalankan k6 run tests/Performance/dashboard.js dengan 30 virtual users selama 10 menit setelah pemanasan 1 menit. Uji dashboard dan batch 20 baris; hasil sah bila p95 maksimal 3 detik dan tidak ada kegagalan fungsional pada data yang valid.
- [ ] Langkah 4: ukur query dan tambah indeks berdasarkan filter nyata: periods(activity_id,position_on), participants(activity_id,region_id), realizations(period_id,participant_id), serta indeks revisi aktif. Hindari cache P0 sebelum kebutuhan terbukti; cache mendatang wajib mengandung cakupan pengguna dan versi data.
- [ ] Langkah 5: verifikasi HTTPS, APP_DEBUG=false, cookie Secure/HttpOnly, CSRF, rate limit login, dan validasi server. Uji akun dinonaktifkan terhadap sesi yang masih terbuka. Besaran sesi serta batas percobaan mengikuti D09.
- [ ] Langkah 6: lakukan pencadangan database dan konfigurasi penting; pulihkan pada database baru tanpa data produksi. Hitung RPO/RTO aktual dan cocokkan target. Simpan log hasil, bukan kata sandi atau isi rahasia.
- [ ] Langkah 7: tinjau alarm kegagalan aplikasi dan backup, jalankan suite terkait dan build, lalu simpan commit.

**Definisi selesai:** layar inti dapat dipakai pada ponsel dan keyboard; kinerja serta pemulihan memiliki bukti. Target yang belum tercapai menjadi penghambat rilis atau perubahan NFR yang disetujui, bukan diabaikan.

<!-- page -->

### T10 UAT dan paket rilis P0

Dependensi: T00 sampai T09. Acuan: seluruh P0 dan UAT-01 sampai UAT-12. Penanggung jawab: penguji, PJK, perwakilan operator, pemilik produk, dan pengelola server.

**Berkas:** buat docs/acceptance/{uat-results,defect-register,release-signoff}.md; docs/user-guide/{admin,operator,pimpinan}.md; docs/operations/{deploy,rollback}.md; scripts/release-check.sh. Skrip shell hanya dijalankan pada lingkungan yang sesuai, bukan melalui penggabungan perintah lintas shell.

**Interfaces:** mengonsumsi aplikasi kandidat rilis, dataset T00, hasil suite, dan runbook T09. Menghasilkan nomor versi, commit acuan, artifact build, status setiap UAT, serta keputusan rilis oleh penanggung jawab yang ditunjuk.

- [ ] Langkah 1: petakan setiap UAT ke akun, data awal, langkah, hasil harapan, hasil aktual, bukti, dan penguji. Gunakan minimal dua tim serta kegiatan dengan jadwal berbeda.
- [ ] Langkah 2: jalankan UAT-01 sampai UAT-12 dari PRD. Uji secara nyata input sebagian wilayah, target berbeda, kosong/nol, retry batch, finalisasi, koreksi, serta akses langsung tanpa kewenangan.
- [ ] Langkah 3: catat cacat beserta tingkat dampaknya. Perbaikan harus memiliki uji regresi pada lapisan pemiliknya; jalankan ulang skenario gagal dan skenario terkait. Jangan menutup cacat hanya karena tampilan berubah.
- [ ] Langkah 4: jalankan php artisan test, pemeriksaan kode PHP yang dipilih, npm run build, uji browser, dan uji beban terakhir bila ada perubahan relevan pada query atau penyimpanan. Exit code 0 dan hasil sesuai NFR menjadi bukti, bukan sekadar keberhasilan build.
- [ ] Langkah 5: lengkapi panduan ringkas membuat kegiatan, mengisi realisasi, membaca dua jenis persentase, finalisasi, dan koreksi. Pengelola menerima konfigurasi serta prosedur backup dan rollback.
- [ ] Langkah 6: lakukan pilot minimal dua putaran evaluasi pada kegiatan uji atau kegiatan yang disepakati. Bandingkan perhitungan aplikasi dengan perhitungan independen; periksa ketepatan tanggal dan kelengkapan laporan.
- [ ] Langkah 7: tetapkan status siap rilis hanya bila semua UAT P0 lulus, tidak ada cacat penghambat, pemulihan teruji, dan penanggung jawab telah menandatangani hasil. Simpan commit dan tag kandidat rilis sesuai konvensi repositori.

**Definisi selesai:** tersedia aplikasi P0 yang diterima beserta bukti pengujian dan paket operasi. Publikasi produksi dilakukan setelah langkah kesiapan konkret selesai dan sesuai kewenangan organisasi. Penyusunan plan ini tidak menjalankan deploy.

<!-- page -->

## 6 Ketertelusuran kebutuhan dan pengujian

Tabel berikut menjadi daftar kontrol cakupan. Setiap kebutuhan yang berubah harus memperbarui tugas dan skenario penerimaannya. UAT yang meliputi banyak aturan memiliki beberapa test otomatis di domain terkait.

| Kebutuhan PRD | Tugas pemilik | Bukti utama |
| --- | --- | --- |
| FR-01 sampai FR-03 | T01, T02 | FoundationTest, ScopeAuthorizationTest; UAT-01. |
| FR-04 sampai FR-07 | T03 | ActivityConfigurationTest; UAT-02. |
| FR-08 sampai FR-10 | T03, T04 | ScheduleGeneratorTest, TargetMatrixTest; UAT-03 dan UAT-04. |
| FR-11 sampai FR-13 | T06 | BatchRealizationTest; UAT-05 sampai UAT-07. |
| FR-14 sampai FR-16 | T06, T07 | Audit, conflict, finalization, race tests; UAT-10. |
| FR-17 sampai FR-21 | T08 | DashboardConsistencyTest; UAT-09 dan UAT-11. |
| BR-01 dan BR-02 | T05 | Kasus 105/140 dan 105/100; UAT-06. |
| BR-03 dan BR-04 | T04 sampai T07 | Target nol, waktu kirim pertama, dan terlambat; UAT-07 dan UAT-08. |
| BR-05 dan BR-06 | T05, T08 | Selesai, lewat tenggat, 59/110, cakupan kosong; UAT-08 dan UAT-09. |
| BR-07 sampai BR-11 | T03, T04, T06 | Unik, target monotonic, batas persen, tanggal, arsip; UAT-02 sampai UAT-07. |
| NFR-01 dan NFR-02 | T09 | Uji browser 360 px, keyboard, dan Android; UAT-12. |
| NFR-03 dan NFR-04 | T09 | Laporan k6 30 pengguna dengan 100.000 baris; UAT-12. |
| NFR-05 dan NFR-06 | T02, T06, T07, T09 | Policy, CSRF, transaksi, idempotensi dan race tests. |
| NFR-07 sampai NFR-10 | T09, T10 | Restore drill, audit waktu, browser, log, runbook. |
| FR-22 sampai FR-24 | P1-A sampai P1-C | Simulasi aturan dan pengujian ledger; belum menjadi syarat rilis P0. |

### 6 1 Pemeriksaan per tugas

Setiap tugas perubahan perilaku memakai urutan test gagal → implementasi → test lulus → tinjauan perubahan → commit. Test tidak ditulis untuk meniru setiap detail tampilan; fokus pada akses, angka, transaksi, riwayat, serta alur yang berisiko gagal. Commit tidak menyertakan perubahan tugas lain yang belum lolos.

### 6 2 Bukti penerimaan

Catat tanggal, lingkungan, versi commit, data uji, perintah, exit code, dan hasil. Pengujian MySQL diperlukan untuk lock dan transaksi. Pengujian manual pengguna tetap diperlukan untuk membaca dashboard dan pengisian tabel; hasil otomatis tidak menggantikan penerimaan pemilik produk.

<!-- page -->

## 7 Dependensi dan estimasi pelaksanaan

### 7 1 Jalur pengerjaan

T00 → T01 → T02 → T03 → T04 → T05 → T06 → T07 → T08 → T09 → T10. Ini urutan acuan untuk satu pengembang. Penyusunan contoh layar dapat berjalan saat kontrak data telah stabil, tetapi implementasi input dan dashboard tetap mengikuti kontrak yang telah diuji. Delegasi tidak menjadi asumsi rencana.

### 7 2 Estimasi usaha P0

Estimasi berikut adalah usulan perencanaan, bukan komitmen tanggal. Satu hari orang berarti sekitar enam jam kerja fokus. Waktu menunggu keputusan, akses hosting, serta ketersediaan penguji berada di luar usaha pengembangan.

| Tugas | Estimasi hari orang | Keluaran yang diperiksa |
| --- | --- | --- |
| T00 Dasar dan lingkungan | 1 sampai 2 | Keputusan dan bukti kompatibilitas. |
| T01 Fondasi | 1 sampai 2 | Aplikasi dasar dan CI. |
| T02 Akses | 2 sampai 3 | Matriks peran dan policy test. |
| T03 Konfigurasi | 3 sampai 4 | Master dan kegiatan draf. |
| T04 Jadwal dan target | 3 sampai 4 | Tanggal serta matriks target valid. |
| T05 Perhitungan | 2 sampai 3 | Rumus dan status teruji. |
| T06 Input realisasi | 3 sampai 5 | Batch, audit, retry, dan konflik. |
| T07 Finalisasi | 2 sampai 4 | Snapshot dan koreksi konsisten. |
| T08 Dashboard | 3 sampai 4 | Tiga tampilan dan rincian konsisten. |
| T09 Kualitas dan operasi | 2 sampai 3 | Beban, ponsel, dan restore. |
| T10 UAT dan rilis | 2 sampai 4 | Bukti penerimaan dan runbook. |
| Total sebelum cadangan | 24 sampai 38 | Seluruh P0. |
| Cadangan sekitar 20 persen | 5 sampai 8 | Revisi terbatas dan temuan uji. |
| Rentang perencanaan | 29 sampai 46 | Sekitar 6 sampai 10 minggu kerja untuk satu pengembang penuh waktu. |

### 7 3 Titik peninjauan

M1 setelah T00: ruang lingkup dan platform jelas. M2 setelah T04: pengguna dapat menyusun monitoring lengkap. M3 setelah T07: satu siklus evaluasi berfungsi dan dapat dikoreksi. M4 setelah T09: dashboard serta operasi lolos uji. M5 setelah T10: rilis P0 dapat diterima. Tinjauan dilakukan dengan demonstrasi data contoh, bukan hanya tangkapan layar.

<!-- page -->

## 8 Runbook rilis dan pemulihan

### 8 1 Sebelum penerapan

- [ ] Tetapkan lingkungan staging terpisah dan verifikasi PHP, ekstensi, basis data, HTTPS, document root ke public, serta izin storage dan bootstrap/cache. Konfirmasi kondisi riil; panduan cPanel yang tersedia tidak membuktikan kesiapan server aplikasi ini.
- [ ] Ambil artifact dari commit yang sudah lulus pengujian. Jalankan composer install --no-dev --optimize-autoloader pada proses build/deploy yang sesuai. Build frontend memakai npm ci dan npm run build di mesin build.
- [ ] Sediakan konfigurasi produksi melalui penyimpanan rahasia, set APP_DEBUG=false, dan gunakan APP_KEY tetap. Cadangkan kunci secara aman; jangan memasukkannya dalam repository atau dokumen serah terima terbuka.
- [ ] Cadangkan database dan berkas yang diperlukan, catat waktu serta lokasi pemulihan, dan pastikan pengelola dapat mengakses salinan tersebut. Rilis pertama membuat akun admin dengan kredensial unik melalui provisioning, bukan password contoh.

### 8 2 Urutan penerapan

- [ ] Jadwalkan jendela perubahan sesuai kebutuhan pengguna. Bila migrasi mengubah skema secara tidak kompatibel, hentikan input terlebih dahulu.
- [ ] Tempatkan artifact pada lokasi rilis baru. Jalankan php artisan migrate --force hanya setelah migrasi ditinjau; gunakan perubahan skema yang kompatibel ke belakang bila memungkinkan.
- [ ] Jalankan php artisan optimize, alihkan document root atau mekanisme rilis yang disepakati, lalu lakukan pemeriksaan login, cakupan akses, baca kegiatan, simpan realisasi uji, dan lihat hasil. Jangan menggunakan data penilaian aktual untuk percobaan tanpa kesepakatan.
- [ ] Aktifkan kembali input dan pantau error serta waktu respons. Pencadangan terjadwal mulai diverifikasi oleh pengelola. P0 tidak memerlukan scheduler untuk mengubah status evaluasi; status mengikuti waktu acuan saat dibaca.

### 8 3 Kapan dan bagaimana rollback

Rollback dipertimbangkan bila login atau input utama gagal, angka salah, pembatasan akses bocor, atau integritas data terganggu. Hentikan input, catat insiden, dan arahkan ke artifact sebelumnya bila skema masih kompatibel. Jangan menjalankan migrate:rollback secara otomatis pada produksi.

Jika skema tidak kompatibel, pengelola memilih perbaikan maju atau pemulihan database dari cadangan setelah menghitung kehilangan perubahan sejak backup. Ambil salinan kondisi terkini untuk rekonsiliasi, dokumentasikan keputusan, pulihkan pada target yang diverifikasi, dan lakukan pemeriksaan ulang sebelum layanan dibuka. Uji prosedur ini di staging sebelum rilis.

<!-- page -->

## 9 Tahap lanjutan dan keputusan pelaksanaan

### 9 1 Rencana P1 terpisah

| Paket | Pekerjaan dan bukti penerimaan |
| --- | --- |
| P1-A Kebijakan penilaian | Putuskan D06 dan D07; simulasi +4/+2/-1, hasil seri, kosong, peserta berbeda, dan frekuensi berbeda. Tetapkan apakah bonus menggantikan poin biasa. |
| P1-B Mesin poin | Buat app/Domain/Scoring/ScoreCalculator.php dan tabel scoring_rules serta score_transactions. Antarmuka calculate(EvaluationRevision, RuleVersion): ScoreResult. Ledger unik per item evaluasi, revisi, dan versi aturan; replay tidak menggandakan poin. |
| P1-C Rekap tahunan | Buat RankingQuery dan halaman rekap. Uji koreksi revisi mengganti kontribusi lama secara terlacak; wilayah tanpa evaluasi tidak diberi peringkat. Publikasi memerlukan metode normalisasi yang disetujui. |
| P1-D Ekspor dan pengingat | Ekspor mengikuti filter dan akses pengguna. Pengingat mengikuti tenggat serta preferensi penerima; desain antrean atau cron setelah kanal dan dukungan hosting diputuskan. |
| P1-E Beberapa indikator | Hapus batas satu indikator aktif, tambah pemilihan indikator pada seluruh alur, dan tentukan bobot. Uji agregasi tidak mencampur satuan serta hak akses tetap sama. |

P2 meliputi impor dengan pemetaan kolom, integrasi sumber data, dan input mandiri kabupaten/kota. Setiap paket memerlukan rencana serta UAT tersendiri; tidak termasuk estimasi P0.

### 9 2 Keputusan tambahan yang dibawa ke peninjauan

Selain D01 sampai D09 pada PRD, tinjau usulan Laravel–Vue–MySQL, presisi empat desimal, pembatasan tahun kegiatan pada P0, tidak mendukung keluar lalu masuk ulang, batch maksimal 20 baris, dan penggunaan hasil resmi terakhir selama koreksi terbuka. Perubahan keputusan tersebut harus memperbarui kontrak, test, serta estimasi terkait.

Risiko utama: target diubah setelah hasil diketahui ditangani dengan versi dan snapshot; input lambat ditangani dengan penanda posisi/waktu kirim; penimpaan data ditangani dengan version check dan lock; keterbatasan hosting ditangani pada T00; perluasan fleksibilitas ditangani dengan batas P0 dan paket P1/P2.

### 9 3 Referensi teknis dan penggunaan rencana

[R1] Laravel Deployment dan Server Requirements: https://laravel.com/framework/docs/deployment

[R2] Laravel Vue Starter Kit: https://laravel.com/starter-kits

Dokumentasi resmi diperiksa pada 16 Oktober 2026. Versi tepat paket dan kompatibilitas server diverifikasi ulang pada T00 sebelum instalasi. PRD tetap menjadi sumber kebutuhan produk; plan menetapkan rincian teknis usulan untuk mewujudkannya.

Pelaksanaan dilakukan tugas demi tugas setelah rencana ditinjau. Metode inline menggunakan skill executing-plans; metode dengan subagent menggunakan subagent-driven-development apabila dipilih pengguna. Pada sesi ini hasil yang diserahkan adalah rencana, bukan implementasi aplikasi.
