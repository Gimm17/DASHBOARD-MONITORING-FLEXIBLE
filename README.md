# BPS Dashboard Monitoring Flexible (DMF)

> **Sistem Pemantauan Terpadu Target & Realisasi Kegiatan BPS Se-Provinsi**  
> Didesain berdasarkan spesifikasi **PRD v1.0**, **Implementation Plan**, dan tema visual modern **Shopeers (Dribbble)**.

---

## 🎨 Palet Warna Resmi
- **Deep Navy (`#1A3263`)**: Brand utama, tombol aksi primer, active navigation pill, heading utama.
- **Slate Blue (`#547792`)**: Sub-headers, grid garis, header tabel, icon sekunder, border kartu.
- **Warm Gold Accent (`#FFC570`)**: Highlight metrik, busur aktif speedometer, badge perhatian.
- **Soft Sand Cream (`#EFD2B0`)**: Latar belakang kartu ringkasan, pill tags, zebra striping.

---

## 🚀 Fitur Utama Frontend

1. **Dashboard Monitoring Eksekutif**:
   - 4 Stat Cards KPI dengan pill tren mini (`▲ 15.5% vs periode lalu`).
   - Hero Spline Area Chart bergradasi dengan interactive tooltip dan 3-segment color breakdown bar (Sosial, Produksi, Nerwilis/Distribusi).
   - Aktivitas Laporan Mingguan (Bar chart dengan penanda puncak hari pengiriman laporan).
   - Speedometer Radial Gauge (Busur radial 180° simetris dengan segmented ticks).
   - DMF AI Assistant (Kartu asisten interaktif dengan bola 3D gradasi Navy-Gold).
   - Tabel Kinerja Kegiatan & Wilayah dengan filter tim kerja.

2. **Matriks Evaluasi Wilayah**:
   - **Sticky Freeze Column** untuk kolom *Kabupaten / Kota* saat digeser secara horizontal.
   - Sel metrik ganda: Realisasi kumulatif terhadap target (`105 / 100`) dan badge status ketercapaian (`105.0% ✓` atau `87.5% ⚠️`), serta penanda keterlambatan (`⏰ WITA`).
   - Footer agregat provinsi dengan perhitungan pembobotan volume murni sesuai aturan **BR-06**.

3. **Input Realisasi Batch**:
   - Spreadsheet 20 baris per batch untuk penginputan cepat operator provinsi.
   - Validasi otomatis penurunan kumulatif (**BR-07**) yang memicu modal justifikasi resmi.
   - Sticky bottom bar konfirmasi simpan data ber-audit log.

4. **Modul Audit Trail & Koreksi**:
   - Log jejak perubahan nilai (*before/after*, operator, timestamp, dan alasan).
   - Review tiket koreksi via drawer samping.
   - Pratinjau cetak resmi Berita Acara Koreksi dalam format kop surat BPS Provinsi.

5. **Slide-Over Drawer Tambah Widget**:
   - Katalog widget modular dengan tag filter (`#Performa`, `#Wilayah`, `#Jadwal`, `#Prioritas`).

---

## 🛠️ Tech Stack
- **Frontend Framework**: Vue 3 (Composition API) + TypeScript
- **Styling**: Tailwind CSS
- **Build Tool**: Vite
- **Icons & Fonts**: Google Fonts (*Outfit*, *Manrope*) & *Material Symbols Outlined*

---

## 💻 Cara Menjalankan

### 1. Instalasi Dependensi
```bash
npm install
```

### 2. Menjalankan Server Development
```bash
npm run dev
```
Akses di browser pada: **http://localhost:5173/**

### 3. Build Produksi
```bash
npm run build
```
Hasil build siap disajikan di folder `dist/`.
