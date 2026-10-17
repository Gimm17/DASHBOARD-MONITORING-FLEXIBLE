<script setup lang="ts">
defineProps<{
  isOpen: boolean
  ticketNo?: string
  regionName?: string
}>()

const emit = defineEmits(['close', 'download'])
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isOpen" class="fixed inset-0 bg-brand-navy/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl w-full max-w-[700px] max-h-[90vh] shadow-2xl border border-[#EAEFF5] flex flex-col overflow-hidden">
          <!-- Top Bar -->
          <div class="p-4 px-6 bg-brand-navy text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-[20px] text-brand-amber">picture_as_pdf</span>
              <span class="font-outfit text-[14px] font-bold">
                Berita Acara Koreksi Realisasi Evaluasi — {{ ticketNo || 'TKT-2026-10-7402' }}
              </span>
            </div>
            <button
              @click="emit('close')"
              class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white"
            >
              <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
          </div>

          <!-- Document Body (Simulated Official BPS Letterhead) -->
          <div class="p-8 overflow-y-auto bg-[#FBFDFE] text-slate-800 font-manrope flex-1">
            <div class="bg-white p-8 border border-slate-200 rounded-xl shadow-xs flex flex-col gap-5 text-[12px] leading-relaxed">
              <!-- Header Kop Surat -->
              <div class="text-center border-b-2 border-slate-800 pb-4">
                <div class="font-bold tracking-wider text-[14px] uppercase text-brand-navy font-outfit">
                  BADAN PUSAT STATISTIK PROVINSI SULAWESI TENGGARA
                </div>
                <div class="text-[11px] text-slate-600 mt-1">
                  Jl. Dr. Sam Ratulangi No. 102, Mandonga, Kendari, Sulawesi Tenggara 93111
                </div>
                <div class="text-[11px] font-semibold text-slate-700 mt-0.5">
                  Telepon (0401) 3121544, Pos-el: bps7400@bps.go.id
                </div>
              </div>

              <!-- Title -->
              <div class="text-center my-1">
                <div class="font-bold text-[13px] font-outfit uppercase underline tracking-wide">
                  BERITA ACARA KOREKSI REALISASI EVALUASI MONITORING
                </div>
                <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                  Nomor: B-1402/BPS/7402/VS/10/2026
                </div>
              </div>

              <!-- Content paragraph -->
              <p>
                Pada hari ini, <strong>Rabu tanggal Empat Belas bulan Oktober tahun Dua Ribu Dua Puluh Enam</strong>, bertempat di Kantor BPS Provinsi Sulawesi Tenggara, telah dilakukan verifikasi dan penyesuaian atas realisasi kegiatan monitoring:
              </p>

              <!-- Meta Table -->
              <table class="w-full border border-slate-200 rounded text-[11px]">
                <tr class="border-b border-slate-200 bg-slate-50">
                  <td class="p-2 font-bold w-1/3">Nama Kegiatan</td>
                  <td class="p-2">Survei Angkatan Kerja Nasional (Sakernas) 2026</td>
                </tr>
                <tr class="border-b border-slate-200">
                  <td class="p-2 font-bold">Satuan Kerja / Wilayah</td>
                  <td class="p-2 font-bold text-brand-navy">{{ regionName || 'BPS Kabupaten Muna (7402)' }}</td>
                </tr>
                <tr class="border-b border-slate-200 bg-slate-50">
                  <td class="p-2 font-bold">Periode Evaluasi</td>
                  <td class="p-2">Periode 2 (Posisi: 07 Oktober 2026)</td>
                </tr>
                <tr class="border-b border-slate-200">
                  <td class="p-2 font-bold">Nilai Sebelum Koreksi</td>
                  <td class="p-2 font-mono">70 Dokumen (100.0% Target Periode)</td>
                </tr>
                <tr class="border-b border-slate-200 bg-red-50/50">
                  <td class="p-2 font-bold text-red-700">Nilai Sesudah Koreksi</td>
                  <td class="p-2 font-mono font-bold text-red-700">65 Dokumen (92.8% Target Periode)</td>
                </tr>
                <tr>
                  <td class="p-2 font-bold">Alasan Penyesuaian</td>
                  <td class="p-2">
                    Ditemukan 5 kuesioner duplikat responden rumah tangga pada blok sensus 012B setelah re-checking oleh tim penjamin kualitas provinsi.
                  </td>
                </tr>
              </table>

              <p class="text-[11px] text-slate-600">
                Koreksi ini disahkan secara digital pada sistem <em>Dashboard Monitoring Flexible (DMF)</em> dengan rujukan audit trail <code>AUD-7402-2026-03</code> dan tidak menghapus arsip histori revisi sebelumnya (sesuai aturan PRD v1.0).
              </p>

              <!-- Signature Section -->
              <div class="grid grid-cols-2 gap-8 pt-4 mt-2">
                <div class="text-center">
                  <div class="text-[11px] text-slate-600">Penanggung Jawab Wilayah,</div>
                  <div class="font-bold text-[12px] mt-12 text-slate-900">Rahman, S.ST.</div>
                  <div class="text-[10px] text-slate-500">NIP. 19890412 201201 1 002</div>
                </div>
                <div class="text-center">
                  <div class="text-[11px] text-slate-600">Ketua Tim / PJK Provinsi,</div>
                  <div class="font-bold text-[12px] mt-12 text-slate-900">Budi Santoso, S.Si.</div>
                  <div class="text-[10px] text-slate-500">NIP. 19780821 200212 1 001</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="p-4 px-6 border-t border-[#EAEFF5] bg-white flex items-center justify-between">
            <span class="text-[11px] text-text-muted font-mono">Status: Digital Hash Verified ✓</span>
            <div class="flex items-center gap-2">
              <button
                @click="emit('close')"
                class="px-4 py-1.5 rounded-xl border border-[#EAEFF5] text-[12px] font-semibold text-text-muted hover:text-text-title"
              >
                Tutup
              </button>
              <button
                @click="emit('download')"
                class="px-4 py-1.5 rounded-xl bg-brand-navy text-white text-[12px] font-bold hover:bg-brand-navy-dark transition-all flex items-center gap-1.5 shadow-sm"
              >
                <span class="material-symbols-outlined text-[16px]">print</span>
                Cetak Dokumen
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
