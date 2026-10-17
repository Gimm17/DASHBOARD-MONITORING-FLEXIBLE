<script setup lang="ts">
function printGuide() {
  window.print();
}
</script>
<template>
  <article class="shoppers-card p-6 space-y-5 max-w-3xl">
    <h1 class="font-outfit text-2xl font-bold text-brand-navy">
      Panduan Monitoring
    </h1>
    <section>
      <h2 class="font-bold">1 Siapkan kegiatan</h2>
      <p class="text-sm mt-2">
        Admin menyiapkan tim, wilayah, satuan, dan akun. PJK membuat kegiatan,
        memilih peserta, menentukan indikator, serta menetapkan jadwal dan
        target positif yang tidak menurun. Target periode terakhir harus sama
        dengan target akhir. Aktifkan kegiatan setelah lengkap.
      </p>
    </section>
    <section>
      <h2 class="font-bold">2 Isi realisasi kumulatif</h2>
      <p class="text-sm mt-2">
        Pilih kegiatan dan tanggal evaluasi. Isi total sampai tanggal posisi: 30
        lalu tambahan 45 menjadi 75. Kosong berarti belum ada laporan, sedangkan
        0 berarti capaian nol. Koreksi nilai yang sudah tersimpan memerlukan
        alasan. Periksa hasil setiap baris sesudah menyimpan.
      </p>
    </section>
    <section>
      <h2 class="font-bold">3 Tinjau dan finalkan</h2>
      <p class="text-sm mt-2">
        Penurunan angka diajukan sebagai tiket. PJK meninjau sebelum angka resmi
        berubah. Finalisasi menyimpan target serta realisasi sebagai snapshot
        dan mengunci periode. Admin dapat membuka evaluasi final dengan alasan;
        revisi resmi sebelumnya tetap dapat ditelusuri.
      </p>
    </section>
    <section>
      <h2 class="font-bold">4 Baca dashboard</h2>
      <p class="text-sm mt-2">
        Progres akhir membandingkan realisasi dengan seluruh target. Capaian
        periode membandingkannya dengan target pada jadwal itu. Realisasi 105
        dari target akhir 140 adalah 75%, sementara terhadap target periode 100
        adalah 105%. Laporan terlambat diberi penanda terpisah. Peringkat
        tahunan menunggu kebijakan penilaian yang disepakati.
      </p>
    </section>
    <p class="notice">
      Kesalahan data atau akses akun ditangani administrator dan penanggung
      jawab kegiatan. Semua waktu pelaporan ditampilkan dalam WITA.
    </p>
    <button class="btn-secondary no-print" @click="printGuide">
      Cetak panduan
    </button>
  </article>
</template>
