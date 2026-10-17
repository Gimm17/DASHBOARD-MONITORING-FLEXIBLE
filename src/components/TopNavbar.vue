<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const currentTime = ref('')

const updateTime = () => {
  const now = new Date()
  currentTime.value = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Makassar',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  }).format(now) + ' WITA'
}

let timer: number
onMounted(() => {
  updateTime()
  timer = window.setInterval(updateTime, 1000)
})

onUnmounted(() => {
  clearInterval(timer)
})

const searchQuery = ref('')
const emit = defineEmits(['openNotifications', 'search'])
</script>

<template>
  <header class="h-[74px] bg-white border-b border-[#EAEFF5] px-8 flex items-center justify-between sticky top-0 z-30">
    <!-- Left: Global Search Pill (Shopeers Style) -->
    <div class="relative w-[380px]">
      <span class="material-symbols-outlined text-[20px] text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2">
        search
      </span>
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Cari kegiatan, kabupaten/kota, atau indikator..."
        class="w-full bg-[#F6F8FB] border border-[#EAEFF5] rounded-full py-2 pl-10 pr-14 text-[13px] font-manrope text-text-title placeholder:text-text-muted focus:outline-none focus:border-brand-navy focus:bg-white transition-all shadow-inner"
        @input="emit('search', searchQuery)"
      />
      <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] font-bold font-mono px-1.5 py-0.5 rounded bg-white border border-[#EAEFF5] text-text-muted">
        Ctrl+K
      </span>
    </div>

    <!-- Right: Controls & Profile Info -->
    <div class="flex items-center gap-4">
      <!-- Live Timezone Asia/Makassar Clock -->
      <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-navy-subtle border border-[#D5E1F0]">
        <span class="material-symbols-outlined text-[16px] text-brand-navy animate-pulse">schedule</span>
        <span class="font-outfit text-[12px] font-semibold text-brand-navy tracking-tight">
          {{ currentTime }}
        </span>
      </div>

      <!-- Notification Bell -->
      <button
        @click="emit('openNotifications')"
        class="relative w-10 h-10 rounded-full border border-[#EAEFF5] flex items-center justify-center text-text-body hover:bg-[#F6F8FB] hover:text-brand-navy transition-all"
        title="Pemberitahuan Evaluasi"
      >
        <span class="material-symbols-outlined text-[20px]">notifications</span>
        <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white"></span>
      </button>

      <div class="h-6 w-px bg-border-card"></div>

      <!-- User Profile Card -->
      <div class="flex items-center gap-3 pl-1">
        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-brand-navy to-brand-slate text-white flex items-center justify-center font-outfit font-bold text-[14px] shadow-sm">
          BS
        </div>
        <div class="flex flex-col text-left">
          <div class="flex items-center gap-1.5">
            <span class="font-outfit font-bold text-[13px] text-text-title">Budi Santoso</span>
            <span class="px-1.5 py-0.2 rounded text-[10px] font-extrabold uppercase bg-brand-cream text-brand-navy">PJK</span>
          </div>
          <span class="font-manrope text-[11px] text-text-muted">BPS Prov. Sultra</span>
        </div>
      </div>
    </div>
  </header>
</template>
