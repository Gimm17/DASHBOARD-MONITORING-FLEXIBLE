<script setup lang="ts">
import { ref } from 'vue'

const activePoint = ref({
  date: '14 Okt 2026 (Periode 3)',
  realisasi: '184.2K Dokumen',
  target: '175.0K Dokumen',
  attainment: '105.2%',
  x: 480,
  y: 55,
})

const hoveredIndex = ref<number | null>(4)

const points = [
  { label: '01 Sep', x: 40, y: 160, realisasi: '32.5K', target: '40.0K', attainment: '81.2%' },
  { label: '15 Sep', x: 150, y: 130, realisasi: '68.0K', target: '70.0K', attainment: '97.1%' },
  { label: '30 Sep', x: 260, y: 95, realisasi: '112.4K', target: '110.0K', attainment: '102.1%' },
  { label: '07 Okt', x: 370, y: 75, realisasi: '148.6K', target: '145.0K', attainment: '102.4%' },
  { label: '14 Okt', x: 480, y: 55, realisasi: '184.2K', target: '175.0K', attainment: '105.2%' },
  { label: '21 Okt', x: 590, y: 35, realisasi: 'Proyeksi 195K', target: '190.0K', attainment: '102.6%' },
]

const selectPoint = (pt: typeof points[0], idx: number) => {
  hoveredIndex.value = idx
  activePoint.value = {
    date: pt.label + ' 2026',
    realisasi: pt.realisasi + ' Dokumen',
    target: pt.target,
    attainment: pt.attainment,
    x: pt.x,
    y: pt.y,
  }
}
</script>

<template>
  <div class="shoppers-card p-6 flex flex-col justify-between">
    <!-- Top Header & Metric -->
    <div class="flex items-start justify-between mb-4">
      <div>
        <div class="flex items-center gap-2">
          <span class="font-manrope text-[13px] font-semibold text-text-muted">Realisasi Kumulatif Provinsi</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-cream text-brand-navy">BR-01 Kumulatif</span>
        </div>
        <div class="flex items-baseline gap-3 mt-1.5">
          <span class="font-outfit text-[32px] font-bold text-text-title tracking-tight leading-none">
            184.2K
          </span>
          <span class="font-manrope text-[14px] text-text-muted">Dokumen Clean</span>
          <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[11px] font-bold font-manrope bg-[#EAF8EE] text-[#1E824C]">
            <span class="material-symbols-outlined text-[14px]">arrow_upward</span>
            92.1% dari Target Akhir
          </span>
        </div>
      </div>

      <!-- Quick time filter buttons -->
      <div class="flex items-center gap-1 bg-[#F6F8FB] p-1 rounded-xl border border-[#EAEFF5]">
        <button class="px-2.5 py-1 rounded-lg text-[11px] font-bold font-manrope bg-white text-brand-navy shadow-xs">
          Mingguan
        </button>
        <button class="px-2.5 py-1 rounded-lg text-[11px] font-semibold font-manrope text-text-muted hover:text-text-title">
          Bulanan
        </button>
        <button class="px-2.5 py-1 rounded-lg text-[11px] font-semibold font-manrope text-text-muted hover:text-text-title">
          Triwulanan
        </button>
      </div>
    </div>

    <!-- Interactive Spline Area Chart (SVG with Gradient) -->
    <div class="relative w-full h-[200px] my-2">
      <svg class="w-full h-full overflow-visible" viewBox="0 0 640 180" preserveAspectRatio="none">
        <defs>
          <!-- Spline Area Fill Gradient -->
          <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#1A3263" stop-opacity="0.22" />
            <stop offset="60%" stop-color="#FFC570" stop-opacity="0.08" />
            <stop offset="100%" stop-color="#EFD2B0" stop-opacity="0.0" />
          </linearGradient>

          <!-- Shadow Filter for line -->
          <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
            <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#1A3263" flood-opacity="0.2" />
          </filter>
        </defs>

        <!-- Horizontal Guide Grid Lines -->
        <line x1="30" y1="30" x2="620" y2="30" stroke="#EAEFF5" stroke-dasharray="4 4" />
        <line x1="30" y1="80" x2="620" y2="80" stroke="#EAEFF5" stroke-dasharray="4 4" />
        <line x1="30" y1="130" x2="620" y2="130" stroke="#EAEFF5" stroke-dasharray="4 4" />

        <!-- Target Line (Dashed Slate) -->
        <path
          d="M 40,150 L 150,120 L 260,90 L 370,70 L 480,50 L 590,30"
          fill="none"
          stroke="#7A9AB5"
          stroke-width="1.5"
          stroke-dasharray="5 4"
        />

        <!-- Area Curve -->
        <path
          d="M 40,160 C 100,145 120,135 150,130 C 200,120 220,105 260,95 C 310,85 340,80 370,75 C 420,68 450,60 480,55 C 530,45 560,38 590,35 L 590,175 L 40,175 Z"
          fill="url(#areaGradient)"
        />

        <!-- Spline Stroke (Deep Navy #1A3263) -->
        <path
          d="M 40,160 C 100,145 120,135 150,130 C 200,120 220,105 260,95 C 310,85 340,80 370,75 C 420,68 450,60 480,55 C 530,45 560,38 590,35"
          fill="none"
          stroke="#1A3263"
          stroke-width="3"
          stroke-linecap="round"
          filter="url(#glow)"
        />

        <!-- Data Points on Curve -->
        <g v-for="(pt, idx) in points" :key="idx" @mouseenter="selectPoint(pt, idx)" class="cursor-pointer">
          <circle
            :cx="pt.x"
            :cy="pt.y"
            r="5"
            fill="#FFFFFF"
            stroke="#1A3263"
            stroke-width="2.5"
            class="transition-all hover:scale-150"
          />
          <circle
            v-if="hoveredIndex === idx"
            :cx="pt.x"
            :cy="pt.y"
            r="9"
            fill="none"
            stroke="#FFC570"
            stroke-width="2"
            class="animate-ping"
          />
        </g>
      </svg>

      <!-- Interactive Floating Tooltip (Shopeers Style) -->
      <div
        class="absolute -top-1 bg-brand-navy text-white text-[11px] font-manrope px-3 py-2 rounded-xl shadow-lg border border-white/10 pointer-events-none transition-all duration-200"
        :style="{ left: `${Math.min(activePoint.x - 60, 460)}px` }"
      >
        <div class="font-outfit font-bold text-[12px] text-brand-amber flex items-center justify-between gap-4">
          <span>{{ activePoint.date }}</span>
          <span class="text-white">{{ activePoint.attainment }}</span>
        </div>
        <div class="mt-1 flex items-center justify-between gap-4 text-slate-300">
          <span>Realisasi: {{ activePoint.realisasi }}</span>
          <span>Target: {{ activePoint.target }}</span>
        </div>
      </div>
    </div>

    <!-- X-Axis Labels -->
    <div class="flex items-center justify-between px-3 text-[11px] font-manrope font-semibold text-text-muted mt-1">
      <span v-for="pt in points" :key="pt.label">{{ pt.label }}</span>
    </div>

    <!-- Bottom 3-Segment Breakdown Metrics Bar (Shopeers Inspired) -->
    <div class="mt-4 pt-4 border-t border-[#EAEFF5] grid grid-cols-3 gap-4">
      <div class="flex items-center gap-3">
        <div class="w-1.5 h-8 rounded-full bg-brand-navy"></div>
        <div>
          <span class="block text-[11px] font-medium text-text-muted">Statistik Sosial</span>
          <span class="font-outfit text-[14px] font-bold text-text-title">72,400 Dok</span>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div class="w-1.5 h-8 rounded-full bg-brand-slate"></div>
        <div>
          <span class="block text-[11px] font-medium text-text-muted">Statistik Produksi</span>
          <span class="font-outfit text-[14px] font-bold text-text-title">54,200 Dok</span>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div class="w-1.5 h-8 rounded-full bg-brand-amber-deep"></div>
        <div>
          <span class="block text-[11px] font-medium text-text-muted">Distribusi &amp; Nerwilis</span>
          <span class="font-outfit text-[14px] font-bold text-text-title">57,600 Dok</span>
        </div>
      </div>
    </div>
  </div>
</template>
