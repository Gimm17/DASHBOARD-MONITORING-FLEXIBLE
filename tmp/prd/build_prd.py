from pathlib import Path
from docx import Document
from docx.shared import Cm, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

ROOT = Path(r'E:\BPS\DASHBOARD-MONITORING-FLEXIBLE')
OUT = ROOT / 'output' / 'prd'
OUT.mkdir(parents=True, exist_ok=True)
doc = Document()
s = doc.sections[0]
s.page_width, s.page_height = Cm(21), Cm(29.7)
s.top_margin, s.bottom_margin = Cm(1.85), Cm(1.75)
s.left_margin = s.right_margin = Cm(2)
s.header_distance = s.footer_distance = Cm(.8)
for name in ['Normal', 'Title', 'Subtitle', 'Heading 1', 'Heading 2', 'Heading 3']:
    st = doc.styles[name]
    st.font.name = 'Calibri'
    st.font.color.rgb = RGBColor(0, 0, 0)
    st.paragraph_format.space_after = Pt(6)
doc.styles['Normal'].font.size = Pt(10.5)
doc.styles['Normal'].paragraph_format.line_spacing = 1.08
doc.styles['Title'].font.size = Pt(28)
doc.styles['Title'].font.bold = True
for border in list(doc.styles['Title'].element.iter(qn('w:pBdr'))):
    border.getparent().remove(border)
doc.styles['Heading 1'].font.size = Pt(17)
doc.styles['Heading 1'].paragraph_format.space_after = Pt(10)
doc.styles['Heading 2'].font.size = Pt(12)
doc.styles['Heading 2'].paragraph_format.space_before = Pt(9)
for nm in ['Heading 1', 'Heading 2', 'Heading 3']:
    doc.styles[nm].paragraph_format.keep_with_next = True
doc.core_properties.title = 'PRD Dashboard Monitoring Flexible'
doc.core_properties.subject = 'Spesifikasi kebutuhan produk monitoring kegiatan kabupaten dan kota'
doc.core_properties.author = 'Tim Pengembangan Dashboard Monitoring'
doc.core_properties.keywords = 'PRD, monitoring, evaluasi, kabupaten, kota, target, realisasi'

foot = s.footer.paragraphs[0]
foot.alignment = WD_ALIGN_PARAGRAPH.RIGHT
r = foot.add_run('PRD DMF  |  Versi 1.0  |  ')
r.font.size = Pt(8)
f = OxmlElement('w:fldSimple'); f.set(qn('w:instr'), 'PAGE'); foot._p.append(f)

def p(t, bold=False):
    a = doc.add_paragraph()
    a.add_run(t).bold = bold
    return a

def h(t): doc.add_heading(t, level=2)
def bullet(t):
    a = doc.add_paragraph(style='List Bullet')
    a.paragraph_format.space_after = Pt(4)
    a.add_run(t)

def page(t):
    doc.add_page_break()
    doc.add_heading(t, level=1)

def table(headers, rows, widths=None):
    t = doc.add_table(rows=1, cols=len(headers))
    t.alignment = WD_TABLE_ALIGNMENT.CENTER
    t.autofit = False
    if widths:
        for col,w in zip(t.columns,widths): col.width = Cm(w)
    for c,txt in zip(t.rows[0].cells,headers): c.text=txt
    for row in rows:
        cells=t.add_row().cells
        for c,txt in zip(cells,row): c.text=str(txt)
    for i,row in enumerate(t.rows):
        pr=row._tr.get_or_add_trPr()
        ns=OxmlElement('w:cantSplit'); pr.append(ns)
        if i==0:
            rep=OxmlElement('w:tblHeader'); pr.append(rep)
        for j,c in enumerate(row.cells):
            if widths: c.width=Cm(widths[j])
            c.vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.CENTER
            tcpr=c._tc.get_or_add_tcPr()
            sh=OxmlElement('w:shd'); sh.set(qn('w:fill'),'17324D' if i==0 else ('F0F4F7' if i%2==0 else 'FFFFFF')); tcpr.append(sh)
            borders=OxmlElement('w:tcBorders')
            for edge in ['top','left','bottom','right']:
                el=OxmlElement('w:'+edge); el.set(qn('w:val'),'single'); el.set(qn('w:sz'),'4'); el.set(qn('w:color'),'D9D9D9'); borders.append(el)
            tcpr.append(borders)
            margins=OxmlElement('w:tcMar')
            for edge in ['top','left','bottom','right']:
                el=OxmlElement('w:'+edge); el.set(qn('w:w'),'85'); el.set(qn('w:type'),'dxa'); margins.append(el)
            tcpr.append(margins)
            for a in c.paragraphs:
                a.paragraph_format.space_after=Pt(2)
                a.paragraph_format.line_spacing=1.0
                for r in a.runs:
                    r.font.size=Pt(9.5)
                    if i==0: r.bold=True; r.font.color.rgb=RGBColor(255,255,255)
    doc.add_paragraph().paragraph_format.space_after=Pt(1)
    return t

doc.add_paragraph('PRODUCT REQUIREMENTS DOCUMENT', style='Subtitle')
doc.add_paragraph('Dashboard Monitoring Flexible', style='Title')
p('Sistem pemantauan target dan realisasi kegiatan kabupaten dan kota', True)
p('Dokumen ini menetapkan kebutuhan produk untuk menyusun skema monitoring yang dapat dikonfigurasi, mencatat realisasi berkala, dan menampilkan capaian setiap wilayah. Dokumen menjadi acuan peninjauan kebutuhan, perancangan, pengembangan, dan uji penerimaan pengguna.')
table(['Informasi dokumen','Keterangan'],[
('Versi','1.0'),('Tanggal','16 Oktober 2026'),('Status','Untuk peninjauan dan persetujuan kebutuhan'),
('Lingkup organisasi','Pemantauan kegiatan tingkat provinsi terhadap kabupaten/kota'),
('Pembaca','Pemilik produk, pimpinan, ketua tim/PJK, operator, pengembang, dan penguji'),
('Dasar kebutuhan','Percakapan pemangku kepentingan dan analisis kebutuhan pada sesi ini')],[4.5,12.5])
h('Ringkasan keputusan produk')
p('Pengguna provinsi membuat kegiatan, memilih wilayah peserta, menentukan indikator serta target, lalu mengatur jadwal evaluasi. Operator provinsi mengisi realisasi secara manual. Sistem menghitung pencapaian target periode dan progres terhadap target akhir, menyimpan riwayat, serta menampilkan kegiatan atau wilayah yang membutuhkan perhatian.')
p('Versi awal memprioritaskan konfigurasi monitoring, kualitas pencatatan, dan dashboard. Gamifikasi serta peringkat tahunan dikembangkan setelah kebijakan penilaian disepakati. Riwayat evaluasi disimpan sejak versi awal agar dapat digunakan pada tahap tersebut.')
p('Kebutuhan yang berasal langsung dari percakapan dibedakan dari keputusan rancangan dan kebijakan yang masih perlu ditetapkan. Penandaan tersebut dijelaskan pada halaman berikutnya.')

page('Panduan membaca dan daftar isi')
h('Status kebutuhan')
table(['Penanda','Makna'],[
('Kebutuhan teridentifikasi','Disebutkan dalam percakapan; tetap memerlukan peninjauan pemilik produk.'),
('Dasar rancangan','Pilihan operasional yang dipakai agar spesifikasi dapat dikembangkan dan diuji.'),
('Perlu keputusan','Belum memiliki ketentuan final; harus ditetapkan sebelum fitur terkait dirilis.')],[4.3,12.7])
p('Prioritas P0 adalah kebutuhan versi awal. P1 adalah pengembangan lanjutan setelah P0 diterima. P2 adalah opsi yang memerlukan kajian kebutuhan tersendiri. Kode FR, BR, NFR, dan UAT digunakan untuk menelusuri kebutuhan fungsional, aturan bisnis, kebutuhan operasional, dan uji penerimaan.')
h('Isi dokumen')
table(['Bagian','Pokok bahasan'],[
('1','Tujuan produk dan cakupan'),('2','Pengguna dan hak akses'),('3','Alur kerja dan siklus kegiatan'),
('4','Konfigurasi kegiatan dan jadwal'),('5','Input realisasi dan riwayat'),('6','Perhitungan dan status evaluasi'),
('7','Dashboard dan navigasi'),('8','Gamifikasi dan peringkat'),('9','Struktur data dan validasi'),
('10','Kebutuhan operasional dan keamanan'),('11','Skenario uji penerimaan'),('12','Tahapan pengembangan dan syarat rilis'),
('13','Keputusan terbuka dan risiko'),('14','Ketertelusuran kebutuhan dan persetujuan')],[2.2,14.8])
p('Istilah utama: kegiatan adalah objek pekerjaan yang dipantau; indikator adalah ukuran capaian; periode evaluasi adalah titik pemeriksaan bertanggal; target periode adalah capaian yang diharapkan pada titik itu; realisasi adalah capaian yang dilaporkan; PJK adalah penanggung jawab kegiatan.')

page('1 Tujuan produk dan cakupan')
h('1 1 Latar belakang')
p('Tim yang berbeda memantau kegiatan dengan indikator, wilayah peserta, dan frekuensi yang berbeda. Pemantauan yang terpisah menyulitkan perbandingan dan penelusuran keterlambatan. Aplikasi perlu menyediakan satu wadah dengan pengaturan yang dapat digunakan berulang untuk berbagai kegiatan.')
h('1 2 Tujuan dan ukuran keberhasilan')
table(['Tujuan','Indikator keberhasilan versi awal'],[
('Monitoring dapat disusun mandiri','Pengguna berwenang dapat membuat kegiatan dan jadwal tanpa perubahan kode.'),
('Perbandingan capaian benar','Target periode dibedakan dari target akhir; hasil hitung sesuai UAT.'),
('Masalah cepat ditemukan','Dashboard dapat menampilkan daftar wilayah di bawah target dan belum melapor.'),
('Riwayat dapat ditelusuri','Setiap perubahan target dan realisasi mencatat pelaku, waktu, serta nilai sebelum dan sesudah.'),
('Pembatasan kewenangan berjalan','Seluruh skenario akses antarperan dan antarkegiatan lulus pengujian.')],[5,12])
h('1 3 Cakupan P0')
p('Login dan hak akses; master wilayah, tim, serta satuan; kegiatan monitoring; wilayah peserta; indikator utama; target per wilayah; jadwal rutin atau tanggal khusus; input realisasi kumulatif; status otomatis; dashboard provinsi, kegiatan, dan wilayah; riwayat perubahan; finalisasi serta koreksi evaluasi terkontrol.')
h('1 4 Cakupan lanjutan')
p('P1 meliputi gamifikasi, rekap tahunan, ekspor laporan, pengingat, dan beberapa indikator aktif dalam satu kegiatan. P2 meliputi impor dengan pemetaan kolom, integrasi sumber data, serta input mandiri kabupaten/kota. Pemindahan prioritas memerlukan keputusan pemilik produk.')
h('1 5 Di luar cakupan dokumen')
p('Gaji, honorarium, presensi, chatbot, asisten virtual, dan broadcast pesan merupakan pembahasan proyek lain. Pembuatan aplikasi seluler native, sistem analisis statistik umum, serta pembuat dashboard dengan tata letak bebas juga tidak menjadi cakupan versi awal.')

page('2 Pengguna dan hak akses')
p('Dasar rancangan: penginput realisasi berada di provinsi, mengikuti penegasan pada bagian akhir transkrip lengkap. Kabupaten/kota mempunyai akses lihat. Kemungkinan input mandiri tetap dicatat sebagai keputusan terbuka D01.')
table(['Peran','Akses utama','Batas kewenangan'],[
('Admin provinsi','Kelola master, akun, penugasan, seluruh kegiatan, dan pemulihan evaluasi.','Pembukaan hasil final wajib mencatat alasan.'),
('Ketua tim atau PJK','Buat dan aktifkan monitoring, tetapkan target, finalisasi evaluasi.','Hanya kegiatan yang ditugaskan.'),
('Operator provinsi','Isi dan koreksi realisasi, catat kendala, kirim data untuk evaluasi.','Tidak mengubah target atau hasil yang dikunci.'),
('Pimpinan','Lihat ringkasan seluruh kegiatan dan rincian wilayah.','Tidak mengubah data secara bawaan.'),
('Pengguna wilayah','Lihat kegiatan, target, realisasi, dan hasil evaluasi wilayah sendiri.','Tidak dapat menginput atau melihat wilayah lain secara bawaan.')],[3.4,7.4,6.2])
h('Kebutuhan akses P0')
bullet('FR-01 Autentikasi: pengguna aktif harus login. Akun nonaktif kehilangan akses; sesi kedaluwarsa sesuai kebijakan yang ditetapkan.')
bullet('FR-02 Penugasan: admin menetapkan peran dan cakupan tim, kegiatan, atau wilayah. Satu akun dapat memiliki lebih dari satu penugasan.')
bullet('FR-03 Otorisasi: pembatasan diterapkan pada pembacaan dan perubahan data di server, termasuk akses langsung melalui alamat halaman atau permintaan data.')
bullet('FR-04 Pengelolaan master: admin dapat menambah, mengubah, dan menonaktifkan wilayah, tim, pengguna, serta satuan. Data yang sudah digunakan tetap tersedia pada riwayat.')
h('Ketentuan pengelolaan')
p('Ketua tim dan operator boleh dirangkap melalui penugasan yang eksplisit. Penghapusan akun tidak boleh menghilangkan identitas pelaku dalam riwayat. Akses lintas wilayah bagi pengguna kabupaten/kota dapat dibuka hanya setelah kebijakan keterbukaan data disepakati.')

page('3 Alur kerja dan siklus kegiatan')
h('3 1 Alur utama')
table(['Langkah','Pelaku','Hasil'],[
('1 Siapkan master','Admin','Wilayah, tim, akun, dan penugasan tersedia.'),
('2 Susun monitoring','Ketua tim/PJK','Identitas, indikator, peserta, target, dan jadwal tersimpan sebagai draf.'),
('3 Aktifkan kegiatan','Ketua tim/PJK','Validasi lengkap; kegiatan tersedia bagi operator dan pembaca.'),
('4 Isi realisasi','Operator provinsi','Nilai, tanggal posisi data, dan kendala tercatat per wilayah.'),
('5 Tinjau evaluasi','Ketua tim/PJK','Status dihitung terhadap target pada jadwal yang berlaku.'),
('6 Finalisasi periode','Ketua tim/PJK','Salinan target, realisasi, dan hasil evaluasi dikunci.'),
('7 Pantau dan tindak lanjuti','Pimpinan/PJK','Daftar ketertinggalan dan kendala menjadi dasar tindak lanjut.'),
('8 Tutup kegiatan','Ketua tim/PJK','Kegiatan diarsipkan; riwayat tetap dapat dibaca.')],[3.4,3.4,10.2])
h('3 2 Siklus kegiatan')
p('Draf → Aktif → Selesai → Arsip. Draf dapat diedit dan belum masuk ringkasan operasional. Aktif menerima realisasi. Selesai menutup input rutin setelah evaluasi akhir ditinjau. Arsip menyimpan kegiatan untuk pembacaan historis. Pembatalan kegiatan aktif harus mencatat alasan dan menghentikan evaluasi berikutnya tanpa menghapus riwayat.')
h('3 3 Siklus evaluasi')
p('Terjadwal → Terbuka → Final. Periode terjadwal belum dinilai gagal. Periode terbuka menerima pencatatan dan peninjauan. Hasil final dikunci. Pembukaan kembali hanya dilakukan admin dengan alasan; koreksi menghasilkan versi hasil baru dengan jejak versi sebelumnya.')
p('Dasar rancangan: finalisasi dilakukan manual oleh PJK. Jadwal pembukaan input dan keterlambatan pelaporan dapat diatur kemudian; versi awal mengizinkan input periode berjalan atau lampau yang belum final, dengan penanda keterlambatan yang jelas.')

page('4 Konfigurasi kegiatan dan jadwal')
h('4 1 Kebutuhan fungsional P0')
bullet('FR-05 Identitas kegiatan: simpan kode unik, nama, tahun pelaporan, deskripsi, tim, PJK, tanggal mulai, dan tanggal akhir. Kegiatan tidak dapat diaktifkan bila data wajib belum lengkap.')
bullet('FR-06 Peserta: pilih seluruh atau sebagian wilayah aktif. Tetapkan tanggal keikutsertaan bila peserta berubah ketika kegiatan sudah berjalan; perubahan tidak menghapus hasil lama.')
bullet('FR-07 Indikator: versi awal menyediakan satu indikator utama per kegiatan dengan nama yang dapat diubah, satuan, tipe jumlah atau persentase, dan arah capaian semakin besar semakin baik.')
bullet('FR-08 Target: tetapkan target akhir serta target kumulatif pada setiap evaluasi untuk setiap wilayah. Target dapat berbeda antarwilayah. Sediakan penyalinan target ke beberapa wilayah sebelum disimpan.')
bullet('FR-09 Jadwal: buat jadwal harian, mingguan, bulanan, triwulanan, atau daftar tanggal khusus. Pengguna harus dapat meninjau dan mengubah daftar tanggal hasil pembangkitan sebelum kegiatan diaktifkan.')
bullet('FR-10 Aktivasi dan versi: validasi kelengkapan, urutan tanggal, peserta, serta target. Perubahan konfigurasi kegiatan aktif dicatat dan tidak mengubah hasil evaluasi final secara otomatis.')
h('4 2 Ketentuan jadwal')
p('Setiap evaluasi memiliki label, tanggal posisi data, dan batas waktu pelaporan. Zona waktu aplikasi menggunakan Asia/Makassar. Untuk frekuensi harian, ketua tim memilih semua tanggal atau hari kerja Senin sampai Jumat; pengecualian hari libur dilakukan manual pada versi awal.')
p('Jadwal bulanan memakai tanggal yang dipilih. Jika tanggal tidak ada pada bulan tertentu, gunakan hari terakhir bulan tersebut. Jadwal mingguan memakai hari yang dipilih; triwulanan menghasilkan empat titik evaluasi dalam tahun pelaporan. Semua hasil pembangkitan tetap harus ditinjau pengguna.')
h('4 3 Batas fleksibilitas')
p('Nama kegiatan, indikator, dan satuan dapat dikonfigurasi. Elemen inti wilayah, periode, target, dan realisasi tetap tersedia agar penghitungan konsisten. Indikator dengan arah semakin kecil semakin baik, rumus bebas, variabel turunan, serta tata letak bebas memerlukan spesifikasi lanjutan.')

page('5 Input realisasi dan riwayat')
h('5 1 Kebutuhan fungsional P0')
bullet('FR-11 Input matriks: operator memilih kegiatan dan periode, lalu mengisi realisasi beberapa wilayah pada satu tabel. Tampilkan target periode, target akhir, nilai sebelumnya, dan status penyimpanan setiap baris.')
bullet('FR-12 Pencatatan: setiap realisasi memuat wilayah, indikator, periode evaluasi, nilai kumulatif, tanggal posisi data, catatan kendala, pencatat, serta waktu pencatatan otomatis.')
bullet('FR-13 Validasi penyimpanan: kesalahan ditampilkan pada baris terkait. Baris gagal tidak dinyatakan tersimpan. Perubahan yang belum tersimpan harus terlihat; aplikasi meminta konfirmasi sebelum pengguna meninggalkan formulir yang berubah.')
bullet('FR-14 Koreksi dan audit: perubahan realisasi atau target membutuhkan alasan. Simpan nilai sebelum dan sesudah, pelaku, serta waktu. Nilai evaluasi final hanya dapat dikoreksi setelah pembukaan kembali yang berwenang.')
bullet('FR-15 Finalisasi: PJK meninjau kelengkapan dan mengunci hasil evaluasi. Sistem menyimpan target, realisasi, status, dan versi aturan yang digunakan sebagai satu rekaman hasil historis.')
bullet('FR-16 Konflik perubahan: jika baris diubah pengguna lain sejak dibuka, sistem menolak penimpaan diam-diam dan meminta pengguna memuat data terbaru.')
h('5 2 Kumulatif sebagai standar')
p('Nilai 30 pada periode pertama dan 75 pada periode kedua berarti total capaian periode kedua adalah 75. Sistem tidak menjumlahkannya menjadi 105. Tambahan pekerjaan pada periode kedua dapat ditampilkan sebagai 75 dikurangi 30, yaitu 45.')
p('Penurunan kumulatif dapat terjadi karena koreksi data. Sistem memberikan peringatan dan mewajibkan alasan; perubahan setelah periode final mengikuti mekanisme pembukaan kembali. Nilai di atas target jumlah diperbolehkan dengan penanda melampaui target.')
h('5 3 Data yang belum masuk')
p('Kosong berarti belum ada laporan; nol berarti laporan sudah tersedia dengan capaian nol. Jika data terbaru belum ada, dashboard boleh menampilkan capaian terakhir beserta tanggalnya, tetapi tidak memperlakukannya sebagai laporan periode saat ini.')

page('6 Perhitungan dan status evaluasi')
h('6 1 Rumus dan contoh')
p('BR-01 Progres akhir = realisasi kumulatif ÷ target akhir × 100%. BR-02 Pencapaian periode = realisasi kumulatif ÷ target periode × 100%. Gunakan nilai asli untuk menentukan status; pembulatan hanya untuk tampilan, maksimal dua angka desimal.')
table(['Tanggal ilustrasi','Target periode','Target akhir','Realisasi','Progres akhir','Capaian periode'],[
('5 November','40','140','35','25%','87,50%'),
('12 November','100','140','105','75%','105%'),
('19 November','140','140','140','100%','100%')],[3.2,2.6,2.6,2.5,3,3.1])
p('Seluruh tanggal dan angka di atas merupakan data ilustrasi. Pada evaluasi kedua, wilayah telah mencapai target periode meskipun progres akhirnya baru 75%. Grafik batang boleh dibatasi pada 100%, tetapi nilai numerik tetap menampilkan kelebihan capaian.')
h('6 2 Status per evaluasi')
table(['Status','Aturan'],[
('Belum jatuh tempo','Tanggal evaluasi belum tiba; tidak dinilai gagal.'),
('Belum ada data','Tanggal evaluasi sudah tiba dan laporan periode itu kosong.'),
('Tercapai','Laporan tersedia dan realisasi lebih besar atau sama dengan target periode.'),
('Di bawah target','Laporan tersedia dan realisasi lebih kecil dari target periode.'),
('Tidak dinilai','Wilayah tidak ikut, periode dibatalkan, atau ada pengecualian yang disahkan.')],[4.3,12.7])
h('6 3 Aturan tambahan')
p('BR-03 Target nol tidak menghasilkan pembagian. Dasar rancangan P0 mewajibkan target akhir dan target periode lebih besar dari nol; tahap tanpa target dicatat tanpa evaluasi atau dikecualikan. BR-04 Keterlambatan laporan ditentukan dari waktu kirim pertama terhadap batas pelaporan, terpisah dari tercapai atau tidaknya target.')
p('BR-05 Ringkasan wilayah dalam kegiatan menampilkan label selesai jika target akhir terpenuhi. Jika belum terpenuhi dan tenggat akhir lewat, tampilkan lewat tenggat. Selain itu, gunakan hasil evaluasi terakhir yang sudah jatuh tempo. Riwayat evaluasi yang gagal tetap terlihat meski pekerjaan akhirnya selesai.')
p('BR-06 Persentase agregat hanya dihitung untuk indikator dan satuan yang sebanding: jumlah realisasi dibagi jumlah target, bukan rata-rata persentase wilayah. Laporan yang hilang harus ditandai sebagai cakupan data tidak lengkap dan ditampilkan jumlahnya.')

page('7 Dashboard dan navigasi')
h('7 1 Kebutuhan dashboard P0')
bullet('FR-17 Dashboard provinsi: tampilkan jumlah kegiatan aktif, evaluasi tercapai, evaluasi di bawah target, dan laporan belum tersedia. Setiap ringkasan dapat dibuka menjadi daftar penyusunnya.')
bullet('FR-18 Dashboard kegiatan: tampilkan matriks wilayah dan periode, target, realisasi, progres akhir, pencapaian periode, grafik perkembangan, serta catatan kendala. Tampilkan waktu pembaruan terakhir dan status final atau sementara.')
bullet('FR-19 Dashboard wilayah: tampilkan kegiatan yang diikuti, hasil periode terakhir, ketertinggalan, dan riwayat evaluasi. Pengguna wilayah dibatasi sesuai penugasannya.')
bullet('FR-20 Filter: sediakan tahun pelaporan, tim, kegiatan, wilayah, periode, dan status. Definisikan tanggal acuan pada tampilan. Filter yang aktif tetap terlihat saat pengguna membuka rincian.')
bullet('FR-21 Keterlacakan angka: jelaskan pembilang, penyebut, cakupan wilayah, serta periode pada setiap persentase ringkasan. Jangan menggabungkan jumlah dokumen, laporan, dan persentase menjadi satu total pekerjaan.')
h('7 2 Definisi ringkasan lintas kegiatan')
p('Unit evaluasi adalah satu kombinasi kegiatan, indikator, wilayah peserta, dan periode yang sudah jatuh tempo. Tingkat ketercapaian = jumlah unit tercapai dibagi seluruh unit yang wajib dievaluasi, termasuk unit yang belum melapor. Tampilkan juga tingkat kelengkapan laporan agar pengguna dapat menafsirkan hasil tersebut.')
p('Ringkasan ini mengukur proporsi evaluasi, bukan volume pekerjaan atau peringkat tahunan. Periode mendatang dan wilayah tidak ikut tidak masuk penyebut. Hasil final digunakan untuk rekap resmi; hasil yang masih terbuka ditandai sementara.')
h('7 3 Halaman utama')
table(['Halaman','Tindakan utama'],[
('Beranda monitoring','Lihat prioritas dan buka kegiatan bermasalah.'),
('Daftar dan pengaturan kegiatan','Buat, tinjau, aktifkan, selesaikan, atau arsipkan kegiatan.'),
('Input realisasi','Isi tabel per periode dan periksa kesalahan.'),
('Detail kegiatan atau wilayah','Telusuri angka, grafik, kendala, dan riwayat.'),
('Master dan akses','Kelola referensi serta penugasan.'),
('Hasil evaluasi','Tinjau, finalisasi, dan telusuri koreksi.')],[6,11])

page('8 Gamifikasi dan peringkat')
p('Prioritas P1. Tujuan gamifikasi adalah menilai konsistensi pencapaian pada setiap evaluasi, termasuk ketika target akhir akhirnya terpenuhi. Rumus pada bagian ini merupakan rancangan untuk disimulasikan, belum menjadi kebijakan penilaian resmi.')
h('8 1 Kebutuhan lanjutan')
bullet('FR-22 Aturan poin dapat diberi versi dan tanggal berlaku. Poin dihitung dari evaluasi final; hasil sementara tidak masuk peringkat resmi.')
bullet('FR-23 Setiap poin dapat ditelusuri ke kegiatan, periode, target, realisasi, hasil evaluasi, dan aturan yang digunakan. Koreksi menghasilkan perhitungan ulang yang tercatat.')
bullet('FR-24 Rekap menampilkan tahun, jumlah kegiatan yang diikuti, jumlah evaluasi wajib, jumlah tercapai, total poin, dan metode peringkat. Wilayah tanpa evaluasi layak tidak diberi peringkat nol secara otomatis.')
h('8 2 Contoh poin dalam percakapan')
table(['Kategori','Contoh poin','Hal yang perlu ditetapkan'],[
('Terbaik atau tercepat','+4','Definisi pemenang, bukti waktu, dan penanganan hasil seri.'),
('Mencapai target','+2','Apakah digantikan oleh +4 ketika menjadi pemenang.'),
('Tidak mencapai target','-1','Perlakuan belum melapor dan pengecualian yang sah.')],[4.8,2.5,9.7])
p('Usulan simulasi: +4 menggantikan +2, tidak dijumlahkan. Kategori tercepat hanya digunakan jika tanggal penyelesaian dapat diverifikasi. Waktu operator menginput data tidak dianggap sebagai bukti waktu pekerjaan selesai.')
h('8 3 Keadilan peringkat')
p('Total poin mentah dipengaruhi frekuensi evaluasi dan jumlah kegiatan peserta. Kegiatan harian memberikan lebih banyak kesempatan memperoleh poin dibanding kegiatan bulanan. Karena itu, total poin tidak langsung ditetapkan sebagai peringkat tahunan.')
p('Usulan pembanding: hitung rasio evaluasi tercapai per kegiatan, lalu rata-ratakan antar kegiatan yang diikuti. Bobot awal dapat sama per kegiatan; bobot khusus harus ditetapkan sebelum periode penilaian dimulai. Tampilkan cakupan partisipasi di samping nilai agar perbandingan dapat dipahami.')
p('Syarat aktivasi: D06 dan D07 sudah diputuskan, contoh perhitungan diuji dengan pemilik produk, aturan seri serta pengecualian terdokumentasi, dan seluruh angka peringkat dapat ditelusuri.')

page('9 Struktur data dan validasi')
h('9 1 Entitas inti')
table(['Entitas','Atribut penting'],[
('Wilayah dan tim','Kode unik, nama, status aktif; hubungan penanggung jawab.'),
('Pengguna dan penugasan','Identitas, peran, wilayah/tim/kegiatan yang diizinkan, status akun.'),
('Kegiatan','Kode, nama, tahun, tim, PJK, tanggal mulai/akhir, status, versi.'),
('Peserta kegiatan','Kegiatan, wilayah, tanggal berlaku, status keikutsertaan.'),
('Indikator','Kegiatan, nama, tipe nilai, satuan, presisi, cara pencatatan kumulatif.'),
('Periode evaluasi','Kegiatan, urutan, label, tanggal posisi, batas pelaporan, status.'),
('Target','Indikator, peserta, periode atau target akhir, nilai, versi, alasan perubahan.'),
('Realisasi','Indikator, peserta, periode, nilai, tanggal posisi, waktu kirim pertama dan perubahan, catatan.'),
('Hasil evaluasi','Target dan realisasi acuan, status, versi aturan, waktu finalisasi, versi koreksi.'),
('Audit dan poin','Pelaku, waktu, objek, aksi, nilai sebelum/sesudah; transaksi poin pada P1.')],[4.6,12.4])
h('9 2 Integritas data')
bullet('BR-07 Satu nilai realisasi terkini untuk kombinasi indikator, peserta, dan periode. Permintaan simpan berulang tidak membuat catatan ganda.')
bullet('BR-08 Target kumulatif antarperiode tidak menurun dan tidak melebihi target akhir. Evaluasi terakhir memiliki target yang sama dengan target akhir.')
bullet('BR-09 Jumlah menggunakan angka nonnegatif sesuai presisi satuan. Untuk indikator persentase, target dan realisasi berada pada rentang 0 sampai 100; target bernilai positif.')
bullet('BR-10 Tanggal evaluasi unik per kegiatan, berurutan, dan berada dalam periode pelaksanaan. Batas pelaporan tidak lebih awal dari tanggal posisi data.')
bullet('BR-11 Data yang sudah dirujuk tidak dihapus permanen melalui antarmuka rutin. Gunakan nonaktif, arsip, atau pembatalan dengan alasan.')
p('Pilihan teknis basis data dan kerangka aplikasi belum dikunci. Penyimpanan relasional disarankan untuk menjaga hubungan data, keunikan kombinasi, transaksi penyimpanan, dan riwayat yang konsisten.')

page('10 Kebutuhan operasional dan keamanan')
p('Target operasional berikut merupakan dasar rancangan pengujian. Kapasitas dan kebijakan produksi dikonfirmasi pada D08 dan D09 sebelum rilis.')
table(['Kode','Kebutuhan','Kriteria verifikasi'],[
('NFR-01','Kemudahan penggunaan','Pada lebar layar 360 px, formulir inti tetap dapat digunakan. Tabel lebar memakai gulir horizontal dengan identitas wilayah tetap terbaca.'),
('NFR-02','Aksesibilitas dasar','Kontrol memiliki label, dapat digunakan dengan keyboard, fokus terlihat, dan status disampaikan dengan teks selain warna.'),
('NFR-03','Kinerja','Target p95 muat dashboard dan simpan hingga 20 baris maksimal 3 detik pada lingkungan uji yang disepakati; tanpa memperhitungkan gangguan jaringan pengguna.'),
('NFR-04','Beban uji awal','Simulasikan 20 wilayah, 100 kegiatan aktif, 100.000 baris realisasi, dan 30 pengguna bersamaan. Ini parameter uji, bukan jumlah pengguna aktual.'),
('NFR-05','Keamanan akses','HTTPS pada produksi, kata sandi di-hash, validasi server, pembatasan percobaan login, dan otorisasi setiap operasi data.'),
('NFR-06','Keandalan penyimpanan','Simpan satu baris beserta auditnya secara atomik. Gangguan tidak menghasilkan indikator sukses palsu; percobaan ulang tidak menggandakan data.'),
('NFR-07','Pencadangan','Usulan pencadangan harian, RPO 24 jam, RTO 8 jam. Pemulihan cadangan diuji sebelum rilis dan mengikuti kapasitas pengelola server.'),
('NFR-08','Keterlacakan','Waktu sistem disimpan konsisten dan ditampilkan dalam Asia/Makassar. Akses audit dibatasi; log tidak memuat kata sandi atau token.'),
('NFR-09','Kompatibilitas','Uji pada Chrome dan Edge versi stabil yang tersedia saat UAT, pada desktop dan setidaknya satu perangkat Android.'),
('NFR-10','Pemantauan operasi','Catat kegagalan aplikasi dan kegagalan pencadangan. Sediakan prosedur pelaporan, penanganan insiden, serta pemulihan.')],[2.2,4.1,10.7])
p('Retensi data, masa sesi, kebijakan kata sandi, target ketersediaan, serta petugas operasi ditetapkan bersama pengelola sistem. Data monitoring dibatasi pada capaian agregat; pencatatan data individu responden tidak diperlukan untuk tujuan produk ini.')

page('11 Skenario uji penerimaan')
p('UAT dilakukan dengan data contoh lintas wilayah dan minimal dua tim. Seluruh skenario P0 wajib lulus. Angka contoh hanya digunakan sebagai data pengujian.')
table(['Kode','Skenario dan kebutuhan','Hasil yang diharapkan'],[
('UAT-01','Akses langsung lintas kegiatan; FR-01 sampai FR-03','Operator tanpa penugasan ditolak membaca dan mengubah data; pengguna wilayah hanya melihat cakupannya.'),
('UAT-02','Buat kegiatan dengan sebagian wilayah; FR-05 sampai FR-10','Hanya peserta terpilih muncul. Kegiatan baru dapat dibuat tanpa perubahan kode.'),
('UAT-03','Jadwal rutin dan khusus; FR-09, BR-10','Daftar tanggal benar, dapat ditinjau, duplikat ditolak; tanggal tidak valid tidak tersimpan.'),
('UAT-04','Target berbeda tiap wilayah; FR-08, BR-08','Target tersimpan per wilayah; urutan menurun dan target akhir yang tidak konsisten ditolak.'),
('UAT-05','Input beberapa baris; FR-11 sampai FR-13','Baris valid berhasil; baris salah memiliki pesan yang jelas; tidak ada sukses palsu atau duplikasi.'),
('UAT-06','Jumlah kumulatif; FR-12, BR-01 dan BR-02','Input 30 lalu 75 menghasilkan total 75. Realisasi 105, target akhir 140 dan periode 100 menghasilkan 75% dan 105%.'),
('UAT-07','Kosong, nol, dan target nol; FR-13, BR-03','Kosong dibedakan dari nol; target nol ditolak dengan pesan; tidak ada pembagian nol.'),
('UAT-08','Status dan riwayat; BR-04 dan BR-05','Periode mendatang tidak gagal; laporan terlambat diberi penanda; kegagalan lama tetap ada setelah target akhir selesai.'),
('UAT-09','Agregasi; FR-21, BR-06','Realisasi 50/100 dan 9/10 menghasilkan 59/110 = 53,64%; satuan berbeda tidak dijumlahkan; cakupan data hilang terlihat.'),
('UAT-10','Finalisasi dan koreksi; FR-14 sampai FR-16','Hasil final terkunci. Pembukaan kembali mencatat alasan; perubahan serentak tidak menimpa tanpa pemberitahuan.'),
('UAT-11','Dashboard dan filter; FR-17 sampai FR-21','Ringkasan sama dengan daftar rincian; filter dan tanggal acuan konsisten; wilayah tidak ikut dikecualikan.'),
('UAT-12','Operasi sistem; NFR-01 sampai NFR-10','Akses ponsel dan keyboard lulus; beban sesuai target; cadangan berhasil dipulihkan dan hasilnya diperiksa.')],[2.1,6.5,8.4])
p('Tambahan untuk P1: simulasi poin +4, +2, dan -1; hasil seri; peserta berbeda; frekuensi berbeda; pembatalan; serta koreksi hasil final. Seluruh hasil harus dapat dihitung ulang secara independen sebelum peringkat dipublikasikan.')

page('12 Tahapan pengembangan dan syarat rilis')
h('12 1 Urutan pelaksanaan')
table(['Tahap','Hasil yang diserahkan','Syarat melanjutkan'],[
('A Penetapan kebutuhan','PRD yang ditinjau, keputusan P0, contoh data, dan penanggung jawab.','D01 sampai D05 dan batas cakupan disepakati.'),
('B Perancangan','Alur layar, contoh formulir kegiatan, tabel input, dan dashboard.','Pengguna mencoba alur dengan satu kegiatan harian dan satu jadwal khusus.'),
('C Pengembangan P0','Konfigurasi, akses, input, perhitungan, dashboard, audit, dan finalisasi.','Pemeriksaan fungsi inti serta integritas data lulus.'),
('D UAT dan pilot','Hasil UAT, panduan singkat, konfigurasi server, dan cadangan.','Seluruh UAT P0 lulus; temuan penghambat ditutup.'),
('E Operasional','Rilis versi awal dan penugasan pengelola sistem.','Akun, akses, pencadangan, serta prosedur bantuan siap.'),
('F Pengembangan P1','Gamifikasi, rekap tahunan, ekspor, dan pengingat sesuai prioritas.','Kebijakan penilaian disetujui dan simulasi diterima.')],[3.2,7.4,6.4])
p('Durasi kalender dan biaya belum ditetapkan dalam PRD ini. Estimasi dilakukan setelah rancangan layar, kapasitas server, cakupan P0, serta penanggung jawab pengisian dikonfirmasi.')
h('12 2 Syarat penerimaan versi awal')
bullet('Semua kebutuhan P0 memiliki hasil pengujian dan seluruh UAT P0 dinyatakan lulus oleh pemilik produk atau perwakilan yang ditunjuk.')
bullet('Tidak ada temuan terbuka yang memungkinkan akses tanpa wewenang, hilangnya data, perhitungan salah, atau kegagalan alur utama.')
bullet('Pilot mencakup minimal dua skema jadwal, target berbeda antarwilayah, laporan kosong, keterlambatan, serta satu koreksi hasil evaluasi.')
bullet('Cadangan berhasil dipulihkan; pengelola menerima panduan akun, konfigurasi, pencadangan, dan penanganan gangguan.')
h('12 3 Pengendalian perubahan')
p('Perubahan kebutuhan dicatat bersama alasan, dampak terhadap data, hasil evaluasi, pengujian, dan jadwal. Perubahan setelah persetujuan tidak mengubah riwayat penilaian tanpa mekanisme versi dan koreksi. Daftar kebutuhan serta UAT diperbarui bersama-sama.')

page('13 Keputusan terbuka dan risiko')
h('13 1 Daftar keputusan')
table(['ID','Keputusan dan dasar rancangan','Waktu penetapan'],[
('D01','Siapa penginput? Dasar: operator provinsi; wilayah hanya melihat.','Sebelum rancangan akses.'),
('D02','Siapa menetapkan target dan finalisasi? Dasar: ketua tim/PJK.','Sebelum pengembangan konfigurasi.'),
('D03','Jumlah indikator dan arah capaian? Dasar: satu indikator utama, semakin besar semakin baik.','Sebelum rancangan data disahkan.'),
('D04','Model dan satuan? Dasar: realisasi kumulatif, jumlah atau persentase.','Sebelum formulir input disahkan.'),
('D05','Batas pelaporan, koreksi, dan cakupan akses wilayah? Dasar: pengaturan per evaluasi, pembukaan final oleh admin, akses wilayah sendiri.','Sebelum pengujian P0.'),
('D06','Definisi terbaik, poin, hasil seri, dan laporan kosong? Contoh +4/+2/-1 belum final.','Sebelum pengembangan gamifikasi.'),
('D07','Bobot, normalisasi, dan syarat partisipasi peringkat tahunan?','Sebelum simulasi peringkat P1.'),
('D08','Server, kapasitas, jaringan, petugas operasi, RPO/RTO?','Sebelum uji beban dan rilis.'),
('D09','Retensi data, masa sesi, dan kebijakan akun?','Sebelum rilis produksi.')],[1.6,11.7,3.7])
h('13 2 Risiko utama')
table(['Risiko','Penanganan'],[
('Input tertunda atau tidak lengkap','Pisahkan waktu posisi dan waktu kirim; tampilkan kelengkapan serta tanggal pembaruan.'),
('Target diubah setelah diketahui hasilnya','Gunakan versi target, audit, hasil final, dan koreksi terkontrol.'),
('Peringkat tidak adil','Simulasikan beban, frekuensi, serta peserta berbeda sebelum kebijakan disahkan.'),
('Fleksibilitas memperlebar cakupan','Pertahankan data inti; tempatkan rumus dan tata letak bebas di kajian terpisah.')],[5.2,11.8])

page('14 Ketertelusuran dan persetujuan')
h('14 1 Hubungan percakapan dengan kebutuhan')
table(['Pokok percakapan','Kebutuhan terkait'],[
('Wadah yang dapat diisi sesuai kebutuhan kegiatan','FR-05 sampai FR-10; cakupan konfigurasi pada Bagian 4.'),
('Evaluasi harian, mingguan, bulanan, dan tanggal pilihan','FR-09; siklus evaluasi pada Bagian 3.'),
('Master wilayah dan kegiatan dengan sebagian kabupaten/kota','FR-04 dan FR-06.'),
('Target dan progres per wilayah','FR-08, FR-11, FR-12; BR-01 dan BR-02.'),
('Dashboard seluruh wilayah dan kegiatan yang bermasalah','FR-17 sampai FR-21.'),
('Penilaian tiap periode dan akumulasi sepanjang tahun','FR-15, FR-22 sampai FR-24; keputusan D06 dan D07.'),
('Input oleh admin provinsi pada penegasan akhir','FR-02, FR-03, FR-11; keputusan D01.')],[10.2,6.8])
h('14 2 Acuan dan batas penafsiran')
p('Acuan utama adalah transkrip percakapan yang disampaikan pengguna, termasuk lampiran percakapan lengkap pada bagian pengembangan dashboard sekitar menit 30:04 sampai 38:21, serta percakapan tambahan tentang progres, target, tanggal monitoring, dan gamifikasi. Ringkasan AI digunakan sebagai konteks; penegasan pembicara dalam transkrip menjadi dasar penafsiran kebutuhan.')
p('Nama tim, nama kegiatan, angka contoh, parameter uji, dan kebijakan usulan dalam dokumen ini tidak menyatakan data operasional aktual. Kebijakan yang belum final dicatat pada Bagian 13 untuk diputuskan pemilik produk.')
h('14 3 Persetujuan kebutuhan')
p('Persetujuan dokumen menetapkan cakupan versi awal serta keputusan yang sudah dicatat. Keputusan D06 dan D07 dapat disahkan terpisah sebelum pengembangan P1. Ruang di bawah diisi oleh pihak yang ditunjuk organisasi.')
table(['Peran','Nama dan tanda tangan','Tanggal'],[
('Pemilik produk / pimpinan','________________________________','________________'),
('Perwakilan ketua tim / PJK','________________________________','________________'),
('Penanggung jawab pengembangan','________________________________','________________')],[6,7.2,3.8])
p('Riwayat dokumen: versi 1.0, 16 Oktober 2026, penyusunan kebutuhan awal terstruktur. Perubahan berikutnya dicatat dengan versi, tanggal, uraian perubahan, dan pihak yang menyetujui.')

path=OUT/'PRD_Dashboard_Monitoring_Flexible_v1.0.docx'
doc.save(path)
print(path)
print('Explicit pages: 16')
