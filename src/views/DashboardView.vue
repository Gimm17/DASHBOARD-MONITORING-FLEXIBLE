<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import StatCard from '../components/StatCard.vue'
import HeroSplineChart from '../components/HeroSplineChart.vue'
import WeeklyActivityChart from '../components/WeeklyActivityChart.vue'
import SpeedometerGauge from '../components/SpeedometerGauge.vue'
import AiAssistantCard from '../components/AiAssistantCard.vue'
import SlideOverAddWidget from '../components/SlideOverAddWidget.vue'
import { mockActivities } from '../data/mockData'

const router = useRouter()

const isAddWidgetOpen = ref(false)
const selectedTeam = ref('Semua Tim')
const searchQuery = ref('')
const selectedYear = ref('2026')

const teams = ['Semua Tim', 'Statistik Sosial', 'Statistik Produksi', 'Statistik Distribusi', 'Nerwilis']

const filteredActivities = computed(() => {
  return mockActivities.filter((act) => {
    const matchTeam = selectedTeam.value === 'Semua Tim' || act.team === selectedTeam.value
    const matchQuery =
      act.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      act.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      act.pjk.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchTeam && matchQuery
  })
})

const exportData = () => {
  alert('Mengunduh rekap kinerja kegiatan monitoring (XLSX)...')
}
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header (Shopeers Style) -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div>
        <h1 class="font-outfit text-[26px] font-bold text-text-title tracking-tight leading-none">
          Dashboard Monitoring Eksekutif
        </h1>
        <p class="font-manrope text-[13px] text-text-muted mt-1.5">
          Pemantauan Capaian Target, Evaluasi Berkala, &amp; Partisipasi BPS Kabupaten/Kota
        </p>
      </div>

      <!-- Right Header Actions (Shopeers Style) -->
      <div class="flex flex-wrap items-center gap-2.5">
        <!-- Date Range Pill -->
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-[#EAEFF5] text-[12px] font-manrope font-semibold text-text-body shadow-xs">
          <span class="material-symbols-outlined text-[16px] text-text-muted">calendar_today</span>
          <span>01 Jan 2026 – 31 Des 2026</span>
          <span class="text-slate-300">|</span>
          <select v-model="selectedYear" class="bg-transparent font-bold text-brand-navy focus:outline-none cursor-pointer">
            <option value="2026">Tahun 2026</option>
            <option value="2025">Tahun 2025</option>
          </select>
        </div>

        <!-- Add Widget Drawer Button -->
        <button
          @click="isAddWidgetOpen = true"
          class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white border border-[#EAEFF5] text-[12px] font-manrope font-semibold text-text-body hover:border-brand-slate hover:bg-[#F6F8FB] transition-all shadow-xs"
        >
          <span class="material-symbols-outlined text-[16px] text-brand-slate">add_circle</span>
          <span>Tambah Widget</span>
        </button>

        <!-- Primary Export CTA -->
        <button
          @click="exportData"
          class="flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-brand-navy text-white text-[12px] font-manrope font-bold hover:bg-brand-navy-dark transition-all shadow-sm active:scale-95"
        >
          <span class="material-symbols-outlined text-[16px]">file_download</span>
          <span>Ekspor Data</span>
        </button>
      </div>
    </div>

    <!-- Alert / Attention Banner for Lagging Regions -->
    <div class="p-3.5 px-5 rounded-2xl bg-[#FFF8EE] border border-brand-amber/40 flex items-center justify-between shadow-xs">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-brand-amber-deep text-[22px]">notification_important</span>
        <div class="text-[12px] font-manrope text-text-title">
          <strong class="font-bold text-brand-navy">Perhatian Khusus:</strong>
          Terdapat <strong>2 Satker</strong> dengan ketertinggalan pengiriman data Sakernas (Kab. Muna &amp; Kab. Bombana).
        </div>
      </div>
      <button
        @click="router.push('/matriks')"
        class="text-[12px] font-bold font-manrope text-brand-navy hover:underline flex items-center gap-1 shrink-0"
      >
        Tindak Lanjuti
        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
      </button>
    </div>

    <!-- ROW 1: 4 Stat Cards KPI (Shopeers Inspired) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <StatCard
        title="Total Kegiatan Aktif"
        value="42"
        subValue="Kegiatan"
        trend="+4 bulan ini"
        :isPositive="true"
        icon="assignment"
        iconBgColor="bg-brand-navy-subtle"
        iconColor="text-brand-navy"
      />

      <StatCard
        title="Tingkat Ketercapaian Target"
        value="78.4%"
        subValue="312 / 398 Unit"
        trend="+2.6% vs periode lalu"
        :isPositive="true"
        icon="check_circle"
        iconBgColor="bg-[#EAF8EE]"
        iconColor="text-[#1E824C]"
      />

      <StatCard
        title="Evaluasi Di Bawah Target"
        value="66"
        subValue="Unit Evaluasi"
        trend="16.6% butuh tindak lanjut"
        :isWarning="true"
        icon="warning"
        iconBgColor="bg-[#FFF7EC]"
        iconColor="text-[#B45309]"
      />

      <StatCard
        title="Laporan Belum Masuk"
        value="20"
        subValue="Wilayah Partisipan"
        trend="5.0% jatuh tempo"
        :isPositive="false"
        icon="pending_actions"
        iconBgColor="bg-[#FEEEEE]"
        iconColor="text-[#C53030]"
      />
    </div>

    <!-- ROW 2: Analytics Split Section (Shopeers Inspired) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
      <!-- Left Hero Card (8 Cols): Spline Area Chart -->
      <div class="lg:col-span-8">
        <HeroSplineChart />
      </div>

      <!-- Right Column Widgets (4 Cols): Weekly Activity, Speedometer Gauge, AI Assistant -->
      <div class="lg:col-span-4 flex flex-col gap-5">
        <SpeedometerGauge />
        <WeeklyActivityChart />
        <AiAssistantCard />
      </div>
    </div>

    <!-- ROW 3: Data-Dense Table Card ("Kinerja Kegiatan Monitoring & Partisipasi") -->
    <div class="shoppers-card p-6">
      <!-- Table Header & Controls -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
        <div>
          <h3 class="font-outfit text-[17px] font-bold text-text-title leading-tight">
            Kinerja Kegiatan Monitoring &amp; Partisipasi Wilayah
          </h3>
          <p class="font-manrope text-[12px] text-text-muted mt-0.5">
            Daftar survei dan pendataan rutin tingkat BPS Provinsi tahun 2026
          </p>
        </div>

        <div class="flex items-center gap-3">
          <!-- Team Filter Dropdown -->
          <select
            v-model="selectedTeam"
            class="bg-[#F6F8FB] border border-[#EAEFF5] rounded-xl px-3 py-1.5 text-[12px] font-manrope font-semibold text-text-body focus:outline-none focus:border-brand-navy cursor-pointer"
          >
            <option v-for="team in teams" :key="team" :value="team">{{ team }}</option>
          </select>

          <!-- Search Input in Table -->
          <div class="relative w-[200px]">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Filter kegiatan..."
              class="w-full bg-[#F6F8FB] border border-[#EAEFF5] rounded-xl py-1.5 pl-8 pr-3 text-[12px] font-manrope text-text-title placeholder:text-text-muted focus:outline-none focus:border-brand-navy"
            />
            <span class="material-symbols-outlined text-[16px] text-text-muted absolute left-2.5 top-1/2 -translate-y-1/2">
              search
            </span>
          </div>

          <button
            @click="router.push('/matriks')"
            class="px-3.5 py-1.5 rounded-xl border border-brand-navy text-brand-navy text-[12px] font-manrope font-bold hover:bg-brand-navy hover:text-white transition-all shadow-xs"
          >
            Buka Matriks Lengkap
          </button>
        </div>
      </div>

      <!-- Data Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-[#EAEFF5] text-[11px] font-outfit uppercase tracking-wider text-text-muted">
              <th class="py-3 px-3">Kode &amp; Nama Kegiatan</th>
              <th class="py-3 px-3">Tim Kerja</th>
              <th class="py-3 px-3">Target Akhir</th>
              <th class="py-3 px-3">Realisasi Kini</th>
              <th class="py-3 px-3">Progres Akhir</th>
              <th class="py-3 px-3">Capaian Periode</th>
              <th class="py-3 px-3">Kelengkapan</th>
              <th class="py-3 px-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#EAEFF5] text-[13px] font-manrope">
            <tr
              v-for="act in filteredActivities"
              :key="act.id"
              class="hover:bg-[#FBFDFE] transition-colors group cursor-pointer"
              @click="router.push('/matriks')"
            >
              <!-- Name & Code -->
              <td class="py-3.5 px-3">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-lg bg-brand-navy-subtle text-brand-navy flex items-center justify-center font-mono font-bold text-[11px] shrink-0">
                    {{ act.code.replace('#', '') }}
                  </div>
                  <div>
                    <span class="font-outfit font-bold text-text-title block leading-snug group-hover:text-brand-navy">
                      {{ act.title }}
                    </span>
                    <span class="text-[11px] text-text-muted">PJK: {{ act.pjk }}</span>
                  </div>
                </div>
              </td>

              <!-- Team -->
              <td class="py-3.5 px-3">
                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold font-manrope bg-[#F2F4F7] text-text-body">
                  {{ act.team }}
                </span>
              </td>

              <!-- Target Akhir -->
              <td class="py-3.5 px-3 font-mono font-bold text-text-title">
                {{ act.targetAkhir }} <span class="font-normal text-[11px] text-text-muted">{{ act.satuan }}</span>
              </td>

              <!-- Realisasi Kini -->
              <td class="py-3.5 px-3 font-mono font-bold" :class="act.realisasiKumulatif === 0 ? 'text-red-500' : 'text-brand-navy'">
                {{ act.realisasiKumulatif === 0 ? '[Kosong]' : `${act.realisasiKumulatif} ${act.satuan}` }}
              </td>

              <!-- Progres Akhir (Bar) -->
              <td class="py-3.5 px-3 min-w-[140px]">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-2 bg-[#F0F4F8] rounded-full overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-300"
                      :class="act.progresAkhir >= 100 ? 'bg-[#1E824C]' : 'bg-brand-navy'"
                      :style="{ width: `${Math.min(act.progresAkhir, 100)}%` }"
                    ></div>
                  </div>
                  <span class="font-mono text-[11px] font-bold text-text-title">{{ act.progresAkhir }}%</span>
                </div>
              </td>

              <!-- Capaian Periode (Pill) -->
              <td class="py-3.5 px-3">
                <span
                  class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold font-manrope"
                  :class="[
                    act.capaianPeriode >= 100
                      ? 'bg-[#EAF8EE] text-[#1E824C]'
                      : act.capaianPeriode > 0
                      ? 'bg-[#FFF7EC] text-[#B45309]'
                      : 'bg-[#FEEEEE] text-[#C53030]'
                  ]"
                >
                  <span class="material-symbols-outlined text-[13px]">
                    {{ act.capaianPeriode >= 100 ? 'check' : act.capaianPeriode > 0 ? 'warning' : 'close' }}
                  </span>
                  {{ act.capaianPeriode }}%
                </span>
              </td>

              <!-- Coverage Rate -->
              <td class="py-3.5 px-3 text-[12px] font-mono font-semibold text-text-muted">
                {{ act.coverageRate }}% Satker
              </td>

              <!-- Action Detail Button -->
              <td class="py-3.5 px-3 text-right">
                <button
                  @click.stop="router.push('/matriks')"
                  class="w-8 h-8 rounded-lg border border-[#EAEFF5] text-text-muted hover:text-brand-navy hover:bg-[#F2F4F7] inline-flex items-center justify-center transition-all"
                  title="Lihat Detail Matriks"
                >
                  <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Slide-over Add Widget Drawer -->
    <SlideOverAddWidget :isOpen="isAddWidgetOpen" @close="isAddWidgetOpen = false" />
  </div>
</template>
