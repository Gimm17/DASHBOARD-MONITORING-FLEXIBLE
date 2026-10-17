<script setup lang="ts">
const days = [
  { name: 'Sen', val: 12, label: '12 Kab' },
  { name: 'Sel', val: 18, label: '18 Kab (Batas WITA)', active: true },
  { name: 'Rab', val: 9, label: '9 Kab' },
  { name: 'Kam', val: 14, label: '14 Kab' },
  { name: 'Jum', val: 11, label: '11 Kab' },
  { name: 'Sab', val: 6, label: '6 Kab' },
]
</script>

<template>
  <div class="shoppers-card p-5 flex flex-col justify-between">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
      <div>
        <span class="font-manrope text-[13px] font-semibold text-text-muted">Aktivitas Laporan Mingguan</span>
        <div class="font-outfit text-[16px] font-bold text-text-title">Puncak Pengiriman Laporan</div>
      </div>
      <button class="text-text-muted hover:text-text-title">
        <span class="material-symbols-outlined text-[18px]">more_horiz</span>
      </button>
    </div>

    <!-- Vertical Bar Chart (Shopeers Style) -->
    <div class="flex items-end justify-between gap-2.5 h-[120px] pt-6 px-1">
      <div
        v-for="day in days"
        :key="day.name"
        class="flex-1 flex flex-col items-center gap-2 group relative cursor-pointer"
      >
        <!-- Tooltip on peak or hover -->
        <div
          v-if="day.active"
          class="absolute -top-7 bg-brand-navy text-white text-[10px] font-outfit font-bold px-2 py-0.5 rounded-md shadow-sm whitespace-nowrap"
        >
          18 Kab
        </div>

        <!-- The Bar -->
        <div class="w-full h-[85px] bg-[#F2F4F8] rounded-lg overflow-hidden flex items-end">
          <div
            class="w-full rounded-lg transition-all duration-300"
            :class="day.active ? 'bg-brand-navy' : 'bg-[#D5E1F0] group-hover:bg-brand-slate'"
            :style="{ height: `${(day.val / 20) * 100}%` }"
          ></div>
        </div>

        <!-- Day label -->
        <span
          class="font-outfit text-[11px] font-bold"
          :class="day.active ? 'text-brand-navy' : 'text-text-muted'"
        >
          {{ day.name }}
        </span>
      </div>
    </div>
  </div>
</template>
