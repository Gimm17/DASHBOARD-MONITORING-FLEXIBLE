# DESIGN SPECIFICATION: DASHBOARD MONITORING FLEXIBLE (DMF)
> **Tema Desain:** *Modern B2B Analytics & Modular Executive Dashboard*  
> **Inspirasi Visual:** Dribbble Style — *Shopeers: AI-Powered B2B eCommerce Analytics Dashboard by Dipa Inhouse*  
> **Color Palette:** `#FFC570` (Gold), `#EFD2B0` (Cream), `#547792` (Slate), `#1A3263` (Navy)  
> **Target Penggunaan:** Google Stitch Prompting, Figma AI, & Modern Frontend (Vue 3 / Tailwind CSS)  
> **Zona Waktu:** Asia/Makassar (WITA)

---

## 1. VISUAL IDENTITY & DESIGN PHILOSOPHY

Desain antarmuka **Dashboard Monitoring Flexible (DMF)** mengadopsi estetika *modern luxury dashboard* ala Dribbble Dipa Inhouse (Shopeers style) yang memadukan keanggunan, fungsionalitas data densitas tinggi, modularitas widget (*Add Widget Drawer*), serta keterbacaan statistik BPS yang sangat presisi.

```
┌────────────────────────────────────────────────────────────────────────┐
│                        PALET WARNA RESMI DMF                           │
├─────────────────┬─────────────────┬──────────────────┬─────────────────┤
│    #1A3263      │     #547792     │     #FFC570      │     #EFD2B0     │
│ Deep Brand Navy │   Slate Blue    │ Warm Gold Accent │ Soft Sand Cream │
│ (Primary Brand) │ (Sub-structures)│  (Key Highlights)│(Surface & Tints)│
└─────────────────┴─────────────────┴──────────────────┴─────────────────┘
```

### Karakteristik Visual Utama (Dipa Inhouse Inspired)
1. **Clean Airy Canvas with Floating Card Widgets**: Latar belakang abu-abu/biru es super lembut (`#F6F8FB`), kartu data putih bersih (`#FFFFFF`) dengan sudut membulat (*border-radius: 16px - 20px*), garis tepi halus `1px solid #EAEFF5`, dan bayangan lembut bergradasi (*soft ambient depth*).
2. **Modular Dashboard with Slide-Over "Add Widget" Drawer**: Dashboard mendukung personalisasi kartu analitik melalui drawer samping kanan (*widget catalogue*) lengkap dengan tag kategori (`#Performance`, `#Wilayah`, `#Jadwal`).
3. **Stat Cards Row with Micro-charts & Badges**: 4 kartu KPI atas dengan angka tebal bersih, ikon berlatar pastel lembut, dan lencana tren persentase pill mini berpanah (`▲ 15.5% vs periode lalu`).
4. **Interactive Hero Chart & Multi-Segment Metrics Bar**: Grafik spline area lembut bergradasi diiringi 3 bar segmen indikator berwarna di bagian bawah (Nerwilis, Sosial, Produksi).
5. **Speedometer Gauge & AI Assistant Orb Widget**: Menghadirkan widget *Radial Gauge Speedometer* untuk ketercapaian target dan widget interaktif *AI Assistant* dengan bola 3D gradasi Navy-Gold glow.
6. **Strict BPS Data Integrity**: Menjamin pembedaan visual tegas antara **Kosong** vs **Nol**, serta **Progres Akhir** vs **Pencapaian Periode** (BR-01 s/d BR-06).

---

## 2. DESIGN TOKENS & SYSTEM VARIABLES

```css
:root {
  /* ========================================================
     PALET UTAMA (USER PALETTE)
     ======================================================== */
  --brand-navy: #1A3263;           /* Primary brand, headings, primary buttons */
  --brand-navy-hover: #122347;     /* Hover state for dark navy */
  --brand-navy-subtle: #EDF2FA;    /* Navy ultra-light background tint */
  
  --brand-slate: #547792;          /* Secondary text, icons, table borders */
  --brand-slate-light: #7A9AB5;    /* Subdued chart lines and secondary tags */
  --brand-slate-subtle: #F0F4F8;   /* Inactive tabs, badge backgrounds */
  
  --brand-amber: #FFC570;          /* Warm gold highlights, radial gauge, KPI bar */
  --brand-amber-deep: #E6A74B;     /* Amber border & warning text */
  --brand-amber-subtle: #FFF8EE;   /* Soft gold tint container */
  
  --brand-cream: #EFD2B0;          /* Card tint, light contrast elements */
  --brand-cream-soft: #FAF4ED;     /* Zebra table rows, hover surface */

  /* ========================================================
     SURFACES & ELEVATION (SHOPPERS STYLE)
     ======================================================== */
  --bg-app: #F6F8FB;               /* Airy canvas background */
  --bg-card: #FFFFFF;              /* Crisp white card surface */
  --bg-card-hover: #FCFDFE;        /* Subtle card hover */
  --border-card: #EAEFF5;          /* 1px ultra-crisp border */
  --border-focus: #1A3263;         /* Sharp focus ring */

  /* ========================================================
     TYPOGRAPHIC TOKENS
     ======================================================== */
  --text-title: #0F1A30;           /* Deep high-contrast title */
  --text-body: #3E4B5E;            /* Balanced readable body */
  --text-muted: #7E8D9F;           /* Subtle metadata & sublabels */
  --text-white: #FFFFFF;

  /* ========================================================
     STATUS SEMANTIK (EVALUASI & REALISASI BPS)
     ======================================================== */
  --status-tercapai-bg: #EAF8EE;
  --status-tercapai-text: #1E824C;
  --status-tercapai-border: #B7E4C7;

  --status-dibawah-bg: #FFF7EC;
  --status-dibawah-text: #B45309;
  --status-dibawah-border: #FFC570;

  --status-kosong-bg: #FEEEEE;
  --status-kosong-text: #C53030;
  --status-kosong-border: #FCA5A5;

  --status-terjadwal-bg: #EDF3F9;
  --status-terjadwal-text: #2B6CB0;
  --status-terjadwal-border: #BEE3F8;

  /* ========================================================
     RADIUS & AMBIENT SHADOWS (DIPAINHOUSE SPEC)
     ======================================================== */
  --radius-xs: 6px;
  --radius-sm: 10px;
  --radius-md: 14px;
  --radius-card: 18px;             /* Rounded Shopeers card corner */
  --radius-pill: 9999px;

  --shadow-card: 0 4px 20px -2px rgba(26, 50, 99, 0.05);
  --shadow-card-hover: 0 10px 25px -4px rgba(26, 50, 99, 0.09);
  --shadow-drawer: -12px 0 35px -5px rgba(26, 50, 99, 0.12);
  --shadow-button-primary: 0 4px 14px rgba(26, 50, 99, 0.22);
  --shadow-gold-glow: 0 4px 18px rgba(255, 197, 112, 0.45);
}
```

---

## 3. LAYOUT ARSITEKTUR: TIGA AREA UTAMA (3-ZONE SHELL)

Tata letak antarmuka mengacu persis pada layout dashboard **Shopeers**:

```
┌─────────────┬───────────────────────────────────────────────────────────────────┐
│  SIDEBAR    │  TOP NAVIGATION BAR (Search, Asia/Makassar Clock, Actions, User)   │
│             ├───────────────────────────────────────────────────────────────────┤
│ [Logo BPS]  │  PAGE HEADER (Title, Date Filter Pill, Add Widget Btn, Export Btn)│
│             ├───────────────────────────────────────────────────────────────────┤
│ • Dashboard │  [ ROW 1: 4 STAT CARDS KPI ]                                      │
│ • Kegiatan  │  [Card 1: Aktif]  [Card 2: % Tercapai] [Card 3: Bawah] [Card 4: ∅]│
│ • Realisasi ├─────────────────────────────────────┬─────────────────────────────┤
│ • Evaluasi  │  [ ROW 2: HERO ANALYTICS CHART ]    │ [ ROW 2: SIDE WIDGETS ]     │
│ • Wilayah   │  - Spline Chart Kumulatif           │ 1. Activity Bar Chart       │
│             │  - Sub-metrics 3 Segment Bar        │ 2. Speedometer Gauge 78%    │
│ [Settings]  ├─────────────────────────────────────┤ 3. AI Monitoring Assistant  │
│             │  [ ROW 3: KINERJA TABEL DATA ]      │                             │
│ [Promo/Card]│  - Tabel Kegiatan & Kab/Kota        │                             │
└─────────────┴─────────────────────────────────────┴─────────────────────────────┘
```

---

## 4. SCREEN-BY-SCREEN BLUEPRINTS (DETIL KOMPONEN & WIREFRAME)

---

### LAYAR 1: DASHBOARD MONITORING EKSEKUTIF (PROVINSI)
*Mengadopsi komposisi grid persis seperti pada Screenshot Shopeers.*

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [⬡ DMF BPS]   🔍 Cari kegiatan, kab/kota, indikator... [Ctrl+K]       [☀️/🌙] [🔔 3] [👤 Budi Santoso ▼]│
├───────────────┬────────────────────────────────────────────────────────────────────────────────────────┤
│ 📊 Dashboard  │ Dashboard Monitoring                               📅 01 Jan 2026 - 31 Des 2026  [Tahun 2026 ▼]│
│   (Active)    │ Pemantauan Capaian Kegiatan BPS Se-Provinsi          [+ Tambah Widget]  [📥 Ekspor Data]│
│ 📁 Kegiatan 42├────────────────────────────────────────────────────────────────────────────────────────┤
│ ✍️ Realisasi  │ ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────────┐ │
│ 🔒 Evaluasi   │ │ Total Kegiatan   │ │ Ketercapaian Tgt │ │ Di Bawah Target  │ │ Laporan Belum Masuk  │ │
│ 🏛️ Wilayah    │ │ 42 Kegiatan   📁 │ │ 78.4%          🎯│ │ 66 Unit        ⚠️│ │ 20 Wilayah       ⏰ │ │
│ ───────────── │ │ ▲ +4 bulan ini   │ │ ▲ +2.6% vs bln ll│ │ ▼ 16.6% evaluasi │ │ ⏳ 5.0% jatuh tempo  │ │
│ ⚙️ Pengaturan │ └──────────────────┘ └──────────────────┘ └──────────────────┘ └──────────────────────┘ │
│ ❓ Bantuan    ├────────────────────────────────────────┬───────────────────────────────────────────────┤
│               │ 📈 Realisasi Kumulatif Provinsi        │ 📊 Aktivitas Laporan Harian (Pukul WITA)     │
│ ┌───────────┐ │ 184.2K Dokumen                         │          [18 Kab]                             │
│ │Ringkasan  │ │ ▲ 92.1% dari Target Akhir 200K         │             █                                 │
│ │SOP 2026   │ │ ~~~~~~/\~~~~~[Tooltip 105%]            │     ▄   ▄   █   ▄   ▄                         │
│ │Panduan    │ │    /      \                            │    Sen Sel Rab Kam Jum Sab                    │
│ │Evaluasi   │ │ ┌────────────────────────────────────┐ ├───────────────────────────────────────────────┤
│ │[Unduh PDF]│ │ │■ Sosial: 72.4K | ■ Produksi: 54.2K │ │ 🎯 Tingkat Ketercapaian Target (Gauge)        │
│ └───────────┘ │ │■ Nerwilis: 38.6K | ■ IPDS: 19.0K   │ │          ╭───────────╮                        │
│               │ └────────────────────────────────────┘ │         │    78.4%    │  [Speedometer Gold]   │
│               ├────────────────────────────────────────┤          ╰───────────╯                        │
│               │ 📋 Kegiatan Prioritas & Kinerja Wilayah│      On track menuju target tahunan 85%       │
│               │ ID     KEGIATAN       TIM    REALISASI │      [Lihat Rincian Unit Evaluasi ↗]          │
│               │ #83009 Sakernas 2026  Sosial 105/140   ├───────────────────────────────────────────────┤
│               │ #83001 Ubinan Jagung  Prod   20/30     │ 🤖 DMF AI Assistant                           │
│               │ #83004 Pendataan IHPB Distrib 45/50    │           (🔵 Glossy 3D Sphere)               │
│               │ #83002 Updating PDRB  Nerwil 12/12     │   "Tanyakan progres kegiatan atau wilayah..." │
│               │ #83007 Survei Wisata  Sosial [KOSONG]  │   [🔍 Ask DMF AI anything...            🎙️ ↗]│
└───────────────┴────────────────────────────────────────┴───────────────────────────────────────────────┘
```

#### Spesifikasi Elemen Layar 1:
1. **Sidebar Navigation**:
   - Background `#FFFFFF` dengan garis batas kanan `1px solid #EAEFF5`.
   - Item aktif *"Dashboard"*: Pill rounded-md dengan background Deep Navy `#1A3263`, teks putih, dan ikon terang.
   - Badge counter kegiatan `"42"`: Pill rounded-full dengan background `#EFD2B0` (Cream) dan teks Deep Navy `#1A3263`.
   - Kartu Panduan SOP di bagian bawah: Latar belakang Deep Navy `#1A3263` dengan teks putih, icon gembok/buku, dan tombol pill *"Unduh Panduan PDF"* berlatar Warm Gold `#FFC570` dengan teks Deep Navy.
2. **Top Actions & Filters**:
   - Date range selector pill bergaya Shopeers: `📅 01 Jan 2026 - 31 Des 2026` dengan dropdown `Tahun 2026 ▼`.
   - Tombol secondary: `+ Tambah Widget` (Membuka Slide-Over Drawer kanan).
   - Tombol primary CTA: `📥 Ekspor Data` berlatar Deep Navy `#1A3263` dengan efek bayangan `--shadow-button-primary`.
3. **4 Top Stat Cards**:
   - Menggunakan layout Shopeers: Label di kiri atas, icon dalam lingkaran warna aksen di kanan atas.
   - Angka metrik tebal berukuran `26px` tabular font.
   - Badge tren persentase pill di bawah: Latar soft green / soft amber berpanah indikator.
4. **Hero Area Chart & Sub-Metrics Bar**:
   - Area chart halus (Spline) dengan garis utama Deep Navy `#1A3263` dan gradien fill lembut ke arah Cream `#EFD2B0`.
   - Tooltip hover interaktif menunjukkan tanggal evaluasi, realisasi kumulatif, dan % capaian.
   - Sub-metrics 3 baris di bawah grafik dengan garis indikator berwarna di samping tiap kategori (Sosial, Produksi, Nerwilis, IPDS).
5. **Speedometer Gauge Widget**:
   - Busur semi-lingkaran bersegmen menggunakan gradien warna Slate Blue `#547792` ke Warm Gold `#FFC570`.
   - Menampilkan angka `78.4%` tebal di tengah dan keterangan *"On track menuju target tahunan 85%"*.
6. **AI Assistant Widget**:
   - Persis seperti widget AI pada Shopeers: Kartu putih bersih dengan ilustrasi bola 3D biru bercahaya (*ambient navy-gold glow*).
   - Input pill di bawah kartu: *"Tanyakan progres wilayah atau kendala lapangan..."* dengan ikon mic dan tombol kirim bulat Navy `#1A3263`.

---

### LAYAR 2: SLIDE-OVER DRAWER "TAMBAH & ATUR WIDGET"
*Terinspirasi langsung dari drawer samping pada Screenshot bagian atas Shopeers.*

```
┌────────────────────────────────────────────────────────┐
│ Tambah & Kelola Widget Dashboard                   [✕] │
├────────────────────────────────────────────────────────┤
│ Pilih widget analitik untuk disematkan pada beranda.   │
│                                                        │
│ TAG KATEGORI:                                          │
│ [ Semua ]  [ #Performa ]  [ #Wilayah ]  [ #Jadwal ]    │
├────────────────────────────────────────────────────────┤
│ DAFTAR WIDGET TERSEDIA:                                │
│                                                        │
│ ┌────────────────────────────────────────────────────┐ │
│ │  ╭───╮  Matriks Ketertinggalan Wilayah             │ │
│ │  │ 📊│  Pantau daftar 5 kabupaten/kota dengan      │ │
│ │  ╰───╯  capaian di bawah target paling kritis.     │ │
│ │  #Wilayah #Prioritas               [ + Pasang ]    │ │
│ └────────────────────────────────────────────────────┘ │
│                                                        │
│ ┌────────────────────────────────────────────────────┐ │
│ │  ╭───╮  Kepatuhan Jadwal & Deadline (WITA)         │ │
│ │  │ ⏰│  Diagram batang ketepatan waktu pengiriman │ │
│ │  ╰───╯  laporan sebelum tanggal jatuh tempo.       │ │
│ │  #Jadwal #Kepatuhan                [ Terpasang ✓ ] │ │
│ └────────────────────────────────────────────────────┘ │
│                                                        │
│ ┌────────────────────────────────────────────────────┐ │
│ │  ╭───╮  Sebaran Capaian per Tim Kerja              │ │
│ │  │ 🍩│  Grafik donat kontribusi realisasi dari tim │ │
│ │  ╰───╯  Sosial, Produksi, Nerwilis, dan IPDS.      │ │
│ │  #Performa                         [ + Pasang ]    │ │
│ └────────────────────────────────────────────────────┘ │
│                                                        │
│ ┌────────────────────────────────────────────────────┐ │
│ │  ╭───╮  Monitoring Laporan Kosong (Missing Data)   │ │
│ │  │ ⚠️│  Daftar peringatan dini wilayah yang belum   │ │
│ │  ╰───╯  mengirimkan data pada periode aktif.       │ │
│ │  #Prioritas                        [ + Pasang ]    │ │
│ └────────────────────────────────────────────────────┘ │
├────────────────────────────────────────────────────────┤
│ [ Pulihkan Tata Letak Standar ]      [ Simpan Tata Letak ]│
└────────────────────────────────────────────────────────┘
```

#### Spesifikasi Drawer:
- Muncul (*slide-in*) dari tepi kanan layar dengan lebar `420px` dan bayangan tebal `--shadow-drawer`.
- Setiap kartu widget memiliki ilustrasi mini/ikon di sisi kiri, deskripsi ringkas, lencana tag abu-abu lembut `#F0F4F8`, dan tombol `[ + Pasang ]` (Navy outline) atau `[ Terpasang ✓ ]` (Pill Slate lembut).
- Mendukung fitur *drag-and-drop* untuk mengatur urutan kartu di dashboard utama.

---

### LAYAR 3: MATRIKS DETAIL KEGIATAN & PERIODE EVALUASI (`/activities/:id`)
*Menggabungkan densitas data BPS dengan kebersihan visual Shopeers.*

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ < Kembali ke Daftar Kegiatan   KEGIATAN: SURVEI ANGKATAN KERJA NASIONAL 2026        [Status: AKTIF ●] │
│ Penanggung Jawab: Statistik Sosial | PJK: Budi Santoso | Target Akhir: 140 Dokumen | Sifat: Kumulatif │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ TABS: [ Matriks Realisasi & Target ]  [ Grafik Tren Satker ]  [ Log Kendala Lapangan ]  [ Audit Trail ]│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ TAMPILAN: [ Filter Wilayah ▼ ]  [ Status: Semua ▼ ]   [ 🔒 Kunci Finalisasi Periode ]  [ 📥 Ekspor XLS ]│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ TABEL MATRIKS BERGAYA SHOPPERS (DENGAN FROZEN COLUMN WILAYAH):                                         │
│ ┌─────────────────────────┬──────────────┬──────────────┬──────────────┬──────────────┬──────────────┐ │
│ │ KABUPATEN / KOTA        │ TARGET AKHIR │ PERIODE 1 🔒 │ PERIODE 2 🔒 │ PERIODE 3 📝 │ PROGRES      │ │
│ │ [Sticky Left Column]    │              │ (30 Sep 26)  │ (07 Okt 26)  │ (14 Okt 26)  │ AKHIR (%)    │ │
│ ├─────────────────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┤ │
│ │ 🏢 7401 KAB. BUTON      │ 140 Dokumen  │ 35 / 40      │ 105 / 100    │ 125 / 120    │ [████████░]  │ │
│ │                         │              │ 87.5% ⚠️     │ 105.0% ✓     │ 104.2% ✓ ⏰  │ 89.2%        │ │
│ ├─────────────────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┤ │
│ │ 🏢 7402 KAB. MUNA       │ 100 Dokumen  │ 25 / 25      │ 70 / 70      │ [ Kosong ]   │ [███████░░]  │ │
│ │                         │              │ 100.0% ✓     │ 100.0% ✓     │ Blm Ada Data │ 70.0%        │ │
│ ├─────────────────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┤ │
│ │ 🏢 7471 KOTA KENDARI    │ 200 Dokumen  │ 60 / 60      │ 150 / 150    │ 200 / 200    │ [██████████] │ │
│ │                         │              │ 100.0% ✓     │ 100.0% ✓     │ 100.0% ✓     │ 100% SELESAI │ │
│ └─────────────────────────┴──────────────┴──────────────┴──────────────┴──────────────┴──────────────┘ │
│ FOOTER AGREGAT PROVINSI (BR-06):                                                                       │
│ Total Realisasi: 430 / Target Akhir: 440  |  Capaian Bobot Kumulatif: 97.7% (Bukan rata-rata persen)  │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

#### Spesifikasi UI Matriks:
- Kolom kiri *Kabupaten / Kota* terkunci (*sticky freeze column*) saat pengguna menggeser tabel ke kanan.
- Sel data menampilkan 2 tingkatan informasi (*Dual-Layer Metric*):
  - **Tingkat 1**: Nilai realisasi kumulatif terhadap target periode dalam angka monospaced tebal (`105 / 100`).
  - **Tingkat 2**: Pill badge persentase capaian periode (`105.0% ✓` hijau atau `87.5% ⚠️` oranye/amber) lengkap dengan penanda keterlambatan `⏰` jika laporan melewati batas waktu `due_at`.

---

### LAYAR 4: INPUT REALISASI BATCH PERIODE AKTIF (`/periods/:id/realizations`)
*Formulir entri data masif yang cepat, bersih, dan aman dari kesalahan jaringan.*

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ INPUT REALISASI BATCH: PERIODE 3 (14 OKT 2026)                       Batas Pengisian: 15 Okt 17:00 WITA│
│ Kegiatan: Sakernas 2026 | Indikator: Jumlah Dokumen Clean | Satuan: Dokumen | Status: TERBUKA UNTUK INPUT│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ 💡 PANDUAN CEPAT OPERATOR:                                                                             │
│ • Nilai yang dimasukkan bersifat KUMULATIF. Contoh: Periode lalu 70, sekarang capai 100, ketik 100.    │
│ • Jangan isi 0 jika data belum ada dari kab/kota; biarkan KOSONG. Isi 0 hanya jika capaian memang nol. │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ TABEL INPUT BATCH (MAX 20 BARIS):                                       [ 💾 Simpan Batch (Ctrl+S) ]   │
│ ┌──────────────────────┬──────────┬──────────┬─────────────┬────────────┬──────────────┬─────────────┐ │
│ │ KABUPATEN / KOTA     │ TARGET   │ LALU     │ KINI        │ POSISI DATA│ KENDALA      │ STATUS      │ │
│ ├──────────────────────┼──────────┼──────────┼─────────────┼────────────┼──────────────┼─────────────┤ │
│ │ 7401 KAB. BUTON      │ 120      │ 105      │ [ 125     ] │ 14/10/2026 │ Pengawasan OK│ ✓ Tersimpan │ │
│ ├──────────────────────┼──────────┼──────────┼─────────────┼────────────┼──────────────┼─────────────┤ │
│ │ 7402 KAB. MUNA       │ 85       │ 70       │ [ 65  ⚠️  ] │ 14/10/2026 │ [Alasan...*] │ Koreksi Tgt │ │
│ │                      │ *Nilai kumulatif turun dari 70 -> 65. Alasan wajib diisi.                   │ │
│ ├──────────────────────┼──────────┼──────────┼─────────────┼────────────┼──────────────┼─────────────┤ │
│ │ 7403 KAB. KONAWE     │ 95       │ 60       │ [         ] │ 14/10/2026 │ Belum melapor│ ∅ Belum Ada │ │
│ └──────────────────────┴──────────┴──────────┴─────────────┴────────────┴──────────────┴─────────────┘ │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [!] 1 baris mengalami penurunan nilai dan membutuhkan alasan verifikasi.     [ Batal ] [ Simpan Data ] │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 5. REKAP PERBANDINGAN TAMPILAN (SHOPPERS VS. BPS DMF)

| Komponen Shopeers (Dribbble) | Padanan pada BPS Dashboard Monitoring Flexible (DMF) | Palet & Styling yang Digunakan |
| :--- | :--- | :--- |
| **Header Total Profit ($446.7K)** | **Total Realisasi Kumulatif Provinsi (184.2K Dokumen)** | Angka tebal Deep Navy `#1A3263` + Sub-bar 3 warna (Sosial, Produksi, Nerwilis). |
| **Active Visitors Bar Chart** | **Aktivitas Pengiriman Laporan Harian (Pukul WITA)** | Kolom aktif warna Deep Navy `#1A3263`, kolom lainnya abu-abu lembut `#EDF2FA`. |
| **Repeat Customer Rate (Gauge 68%)** | **Tingkat Ketercapaian Target Evaluasi (Gauge 78.4%)** | Busur radial bertingkat Warm Gold `#FFC570` berlatar Cream `#EFD2B0`. |
| **AI Assistant Floating Card** | **DMF AI Monitoring Assistant** | Kartu putih bersih dengan bola 3D gradasi Navy-Gold dan bar prompt terintegrasi. |
| **Best Selling Products Table** | **Matriks Kinerja Kegiatan & Partisipasi Wilayah** | Baris ber-border tipis `#EAEFF5`, tag status pill, dan tombol aksi detail. |
| **Add Widget Drawer (Slide-Over)** | **Drawer Kustomisasi & Tambah Widget Monitoring** | Slide-over panel dari kanan dengan filter tag `#Performa`, `#Wilayah`, `#Jadwal`. |
| **Promo Card "Upgrade to Premium"** | **Kartu Ringkasan Regulasi & SOP Monitoring 2026** | Kartu sudut bawah kiri Deep Navy `#1A3263` dengan tombol emas Warm Gold `#FFC570`. |

---

## 6. PROMPT GOOGLE STITCH SIAP PAKAI (READY-TO-PASTE)

Salin prompt di bawah ini langsung ke **Google Stitch** untuk menghasilkan antarmuka web persis seperti rancangan:

```text
Design a luxury, modern B2B analytics dashboard inspired by "Shopeers by Dipa Inhouse" on Dribbble, customized for "BPS (Statistics Indonesia) - Flexible Monitoring Dashboard (DMF)".

Color Palette:
- Deep Navy: #1A3263 (Primary Brand, primary buttons, headings, active sidebar state)
- Slate Blue: #547792 (Secondary elements, table headers, icons, borders)
- Warm Gold: #FFC570 (KPI progress indicators, gauge needle/arc, attention highlights)
- Soft Cream: #EFD2B0 (Subtle badge tints, card accents, secondary gauge tracks)
- Background: Airy light canvas (#F6F8FB) with floating pure white cards (#FFFFFF), crisp 1px borders (#EAEFF5), and smooth 18px rounded corners.

Layout Structure:
1. Left Navigation Sidebar:
   - Top logo: BPS logo icon with text "DMF Monitoring".
   - Navigation links with clean line icons: Dashboard (active Navy #1A3263 pill with white text), Kegiatan (badge count "42" in soft cream pill), Realisasi, Evaluasi, Wilayah Kab/Kota, Pengaturan.
   - Bottom promo-style card: Solid Navy #1A3263 background with title "SOP & Panduan Monitoring 2026", white description text, and a glowing Warm Gold (#FFC570) pill button "Unduh PDF".

2. Top Header Bar:
   - Search input pill with keyboard shortcut badge (Ctrl+K).
   - Real-time clock badge: "Asia/Makassar (WITA) — 17 Okt 2026, 21:07".
   - Theme toggle button, notification bell with unread badge, and user avatar profile with role pill "Katim Nerwilis (Provinsi)".

3. Dashboard Header:
   - Title "Dashboard Monitoring Eksekutif" with subtitle "Pemantauan Capaian Kegiatan BPS Se-Provinsi".
   - Date range pill "📅 01 Jan 2026 - 31 Des 2026", filter dropdown "Semua Tim Kerja ▼", button "+ Tambah Widget" (opens right drawer), and primary Navy button "📥 Ekspor Data".

4. Row 1: 4 High-Density Stat Cards (Exact Shopeers style):
   - Card 1: "Total Kegiatan Aktif" -> 42 Kegiatan (▲ +4 bulan ini).
   - Card 2: "Tingkat Ketercapaian" -> 78.4% (312/398 Unit Wajib - ▲ +2.6% vs periode lalu).
   - Card 3: "Di Bawah Target" -> 66 Unit (▼ 16.6% butuh tindak lanjut - Amber badge #FFC570).
   - Card 4: "Laporan Belum Masuk" -> 20 Wilayah (⏳ 5.0% jatuh tempo - Soft red alert badge).

5. Row 2: Analytics Split Section:
   - Left Hero Card (8 Cols): "Realisasi Kumulatif Provinsi" showing bold metric "184.2K Dokumen", spline area curve chart in Deep Navy #1A3263 fading to Cream #EFD2B0, interactive tooltip "Periode 2 - 105% Capaian", and bottom 3-segment bar representing Tim Sosial (72.4K), Produksi (54.2K), and Nerwilis (38.6K).
   - Right Side Widgets (4 Cols):
     * Widget A: "Aktivitas Pengiriman Laporan" vertical bar chart for days of the week with Tuesday peak highlighted in vibrant Navy.
     * Widget B: "Tingkat Ketercapaian Target" semi-circle Speedometer Gauge with gold #FFC570 active arc showing "78.4%" in center.
     * Widget C: "DMF AI Assistant" floating card with a glowing 3D sphere in Navy/Gold and an input box "Ask DMF AI anything...".

6. Row 3: Data-Dense Table Card:
   - "Kegiatan Prioritas & Kinerja Wilayah" table with search bar, status pills (Tercapai, Di Bawah Target, Belum Ada Data), progress bars, and view details action.

7. Include Slide-over "Tambah Widget" Drawer on the right:
   - Displays available modular widgets with tags (#Performa, #Wilayah, #Jadwal) and "+ Pasang" buttons.

Ensure the design looks extraordinarily sleek, ultra-clean, pixel-perfect, responsive down to 360px mobile viewports, and matches the premium Dribbble Dipa Inhouse aesthetic.
```

---

## 7. DOKUMEN CHECKLIST & KELENGKAPAN

- [x] Struktur visual dan komponen identik dengan *Shopeers by Dipa Inhouse*.
- [x] Color palette terintegrasi penuh: `#FFC570`, `#EFD2B0`, `#547792`, `#1A3263`.
- [x] Drawer slide-over *"Add Widget"* dirancang lengkap dengan tag filter.
- [x] Speedometer Radial Gauge dan AI Assistant Widget diadaptasi untuk konteks BPS.
- [x] Seluruh kebutuhan data PRD v1.0 dan Implementation Plan (T00 s/d T10) terpenuhi.
- [x] Prompt siap pakai untuk Google Stitch tersedia pada Bagian 6.
