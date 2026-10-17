<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { mockRegions } from '../data/mockData'

const router = useRouter()
const activeTab = ref<'matriks' | 'grafik' | 'kendala'>('matriks')
const isFinalized = ref(false)

const finalizePeriod = () => {
  if (confirm('Konfirmasi: Kunci dan finalkan Periode 3 untuk Sakernas 2026? Snapshot akan disimpan resmi dan terkunci.')) {
    isFinalized.value = true
    alert('Periode 3 berhasil dikunci sebagai Revisi 1 Resmi!')
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Navigation & Title -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div class="flex items-center gap-3">
        <button
          @click="router.push('/')"
          class="w-9 h-9 rounded-xl border border-[#EAEFF5] bg-white text-text-muted hover:text-brand-navy hover:bg-[#F6F8FB] flex items-center justify-center transition-all shadow-xs"
        >
          <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </button>
        <div>
          <div class="flex items-center gap-2">
            <span class="font-mono text-[11px] font-bold text-brand-navy bg-brand-navy-subtle px-2 py-0.5 rounded">
              #83009
            </span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#EAF8EE] text-[#1E824C]">
              AKTIF
            </span>
            <span v-if="isFinalized" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-navy text-white flex items-center gap-1">
              <span class="material-symbols-outlined text-[12px]">lock</span>
              FINAL REVISI 1
            </span>
          </div>
          <h1 class="font-outfit text-[22px] font-bold text-text-title tracking-tight leading-snug mt-1">
            Survei Angkatan Kerja Nasional (Sakernas) 2026
          </h1>
          <p class="font-manrope text-[12px] text-text-muted">
            Tim: <strong>Statistik Sosial</strong> | PJK: <strong>Budi Santoso, S.Si.</strong> | Satuan: <strong>Dokumen Rumah Tangga</strong>
          </p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2.5">
        <button
          @click="router.push('/realisasi')"
          class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-brand-amber text-brand-navy text-[12px] font-manrope font-bold hover:bg-brand-amber-deep transition-all shadow-sm"
        >
          <span class="material-symbols-outlined text-[18px]">edit_note</span>
          <span>Input Realisasi Batch</span>
        </button>

        <button
          @click="finalizePeriod"
          class="flex items-center gap-1.5 px-4 py-2 rounded-xl border border-brand-navy text-brand-navy text-[12px] font-manrope font-bold hover:bg-brand-navy hover:text-white transition-all shadow-xs"
        >
          <span class="material-symbols-outlined text-[18px]">lock</span>
          <span>Kunci Finalisasi Periode 3</span>
        </button>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-[#EAEFF5] pb-1">
      <button
        @click="activeTab = 'matriks'"
        class="px-4 py-2 rounded-xl text-[13px] font-manrope font-bold transition-all"
        :class="activeTab === 'matriks' ? 'bg-brand-navy text-white shadow-xs' : 'text-text-muted hover:text-text-title'"
      >
        Matriks Realisasi &amp; Target (Wilayah)
      </button>
      <button
        @click="activeTab = 'kendala'"
        class="px-4 py-2 rounded-xl text-[13px] font-manrope font-bold transition-all"
        :class="activeTab === 'kendala' ? 'bg-brand-navy text-white shadow-xs' : 'text-text-muted hover:text-text-title'"
      >
        Catatan Kendala Lapangan
      </button>
      <button
        @click="router.push('/audit')"
        class="px-4 py-2 rounded-xl text-[13px] font-manrope font-bold text-text-muted hover:text-text-title"
      >
        Log Audit Trail Koreksi (BR-05)
      </button>
    </div>

    <!-- Matriks Tab Content -->
    <div v-if="activeTab === 'matriks'" class="shoppers-card overflow-hidden">
      <!-- Info Legend Bar -->
      <div class="p-4 px-6 bg-[#FBFDFE] border-b border-[#EAEFF5] flex flex-wrap items-center justify-between gap-4 text-[12px] font-manrope">
        <div class="flex items-center gap-4 text-text-muted">
          <span class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] text-brand-navy">lock</span>
            Periode Terkunci (Final)
          </span>
          <span class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] text-brand-amber-deep">edit</span>
            Periode Berjalan (Sementara)
          </span>
          <span class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] text-red-500">schedule</span>
            Terlambat (WITA)
          </span>
        </div>

        <span class="text-[11px] font-semibold text-brand-slate bg-brand-slate-subtle px-3 py-1 rounded-full">
          💡 Kolom Wilayah terkunci (Sticky Freeze Column) saat digeser
        </span>
      </div>

      <!-- Horizontally Scrolling Table with Sticky Left Column -->
      <div class="overflow-x-auto relative">
        <table class="w-full text-left border-collapse min-w-[900px]">
          <thead>
            <tr class="bg-[#F6F8FB] border-b border-[#EAEFF5] text-[11px] font-outfit uppercase tracking-wider text-text-muted">
              <!-- STICKY FROZEN COLUMN -->
              <th class="py-3.5 px-4 sticky left-0 bg-[#F6F8FB] z-20 shadow-[2px_0_5px_rgba(0,0,0,0.03)] border-r border-[#EAEFF5] w-[220px]">
                Kabupaten / Kota
              </th>
              <th class="py-3.5 px-4 text-center w-[120px]">Target Akhir</th>
              <th class="py-3.5 px-4 text-center">
                Periode 1 🔒<br />
                <span class="text-[10px] text-text-muted font-normal">30 Sep 2026</span>
              </th>
              <th class="py-3.5 px-4 text-center">
                Periode 2 🔒<br />
                <span class="text-[10px] text-text-muted font-normal">07 Okt 2026</span>
              </th>
              <th class="py-3.5 px-4 text-center bg-brand-cream/15">
                Periode 3 📝 (Aktif)<br />
                <span class="text-[10px] text-text-muted font-normal">14 Okt 2026</span>
              </th>
              <th class="py-3.5 px-4 text-center w-[150px]">Progres Target Akhir</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#EAEFF5] text-[13px] font-manrope">
            <tr v-for="reg in mockRegions" :key="reg.id" class="hover:bg-[#FCFDFE] transition-colors">
              <!-- STICKY FROZEN REGION CELL -->
              <td class="py-3.5 px-4 sticky left-0 bg-white z-10 shadow-[2px_0_5px_rgba(0,0,0,0.03)] border-r border-[#EAEFF5] font-outfit font-bold text-text-title">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-[18px] text-brand-slate">apartment</span>
                  <span>{{ reg.code }} {{ reg.name }}</span>
                </div>
              </td>

              <!-- Target Akhir -->
              <td class="py-3.5 px-4 text-center font-mono font-bold text-text-title">
                {{ reg.targetAkhir }}
              </td>

              <!-- Periode 1 -->
              <td class="py-3 px-4 text-center">
                <div class="font-mono font-bold text-[12px] text-text-title">
                  {{ reg.periode1.realisasi }} / {{ reg.periode1.target }}
                </div>
                <span
                  class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-manrope"
                  :class="reg.periode1.status === 'TERCAPAI' ? 'bg-[#EAF8EE] text-[#1E824C]' : 'bg-[#FFF7EC] text-[#B45309]'"
                >
                  {{ reg.periode1.attainment }}%
                </span>
              </td>

              <!-- Periode 2 -->
              <td class="py-3 px-4 text-center">
                <div class="font-mono font-bold text-[12px] text-text-title">
                  {{ reg.periode2.realisasi }} / {{ reg.periode2.target }}
                </div>
                <span
                  class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-manrope"
                  :class="reg.periode2.status === 'TERCAPAI' ? 'bg-[#EAF8EE] text-[#1E824C]' : 'bg-[#FFF7EC] text-[#B45309]'"
                >
                  {{ reg.periode2.attainment }}%
                </span>
              </td>

              <!-- Periode 3 (Aktif) -->
              <td class="py-3 px-4 text-center bg-brand-cream/10">
                <template v-if="reg.periode3.realisasi !== null">
                  <div class="font-mono font-bold text-[12px] text-brand-navy flex items-center justify-center gap-1">
                    <span>{{ reg.periode3.realisasi }} / {{ reg.periode3.target }}</span>
                    <span v-if="reg.periode3.isLate" class="material-symbols-outlined text-[14px] text-red-500" title="Terlambat melewati deadline WITA">schedule</span>
                  </div>
                  <span
                    class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-manrope"
                    :class="reg.periode3.status === 'TERCAPAI' ? 'bg-[#EAF8EE] text-[#1E824C]' : 'bg-[#FFF7EC] text-[#B45309]'"
                  >
                    {{ reg.periode3.attainment }}% {{ reg.periode3.status === 'TERCAPAI' ? '✓' : '⚠️' }}
                  </span>
                </template>
                <template v-else>
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#FEEEEE] text-[#C53030]">
                    ○ Belum Ada Data
                  </span>
                </template>
              </td>

              <!-- Progres Target Akhir -->
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-2 bg-[#F0F4F8] rounded-full overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-300"
                      :class="reg.progresAkhir >= 100 ? 'bg-[#1E824C]' : 'bg-brand-navy'"
                      :style="{ width: `${Math.min(reg.progresAkhir, 100)}%` }"
                    ></div>
                  </div>
                  <span class="font-mono text-[11px] font-bold text-text-title">{{ reg.progresAkhir }}%</span>
                </div>
              </td>
            </tr>
          </tbody>

          <!-- Aggregat Footer (BR-06 Weighted Formula) -->
          <tfoot class="bg-[#F6F8FB] border-t-2 border-[#D5E1F0] font-outfit text-[12px] font-bold">
            <tr>
              <td class="py-3.5 px-4 sticky left-0 bg-[#F6F8FB] border-r border-[#EAEFF5] text-brand-navy">
                AGREGAT PROVINSI (BR-06)
              </td>
              <td class="py-3.5 px-4 text-center font-mono">1,070 Dok</td>
              <td class="py-3.5 px-4 text-center text-[#1E824C] font-mono">293 / 300 (97.7%)</td>
              <td class="py-3.5 px-4 text-center text-[#1E824C] font-mono">745 / 750 (99.3%)</td>
              <td class="py-3.5 px-4 text-center text-brand-navy font-mono bg-brand-cream/20">902 / 955 (94.5%)</td>
              <td class="py-3.5 px-4 text-center text-brand-navy font-mono">84.3% Akhir</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Kendala Lapangan Tab -->
    <div v-else class="shoppers-card p-6 space-y-4">
      <h3 class="font-outfit text-[16px] font-bold text-text-title">
        Daftar Catatan Kendala Lapangan Sakernas 2026
      </h3>
      <div class="divide-y divide-[#EAEFF5]">
        <div class="py-3.5 flex items-start gap-4">
          <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-brand-navy-subtle text-brand-navy shrink-0 font-mono">
            7402 KAB. MUNA
          </span>
          <div class="flex-1">
            <span class="text-[13px] font-bold text-text-title block">Koreksi Kuesioner Duplikat</span>
            <p class="text-[12px] text-text-muted mt-0.5">
              Ditemukan 5 dokumen rumah tangga berulang pada blok sensus perbatasan. Sedang menunggu pengesahan berita acara koreksi.
            </p>
          </div>
          <span class="text-[11px] text-text-muted">14 Okt 2026, 09:30 WITA</span>
        </div>

        <div class="py-3.5 flex items-start gap-4">
          <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-brand-navy-subtle text-brand-navy shrink-0 font-mono">
            7406 KAB. BOMBANA
          </span>
          <div class="flex-1">
            <span class="text-[13px] font-bold text-text-title block">Cuaca Ekstrem Jalur Laut</span>
            <p class="text-[12px] text-text-muted mt-0.5">
              Pengiriman berkas dari Pulau Kabaena tertunda gelombang tinggi. Data diperkirakan tiba 16 Oktober.
            </p>
          </div>
          <span class="text-[11px] text-text-muted">13 Okt 2026, 16:45 WITA</span>
        </div>
      </div>
    </div>
  </div>
</template>
