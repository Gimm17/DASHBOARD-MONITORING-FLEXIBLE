<script setup lang="ts">
import { ref } from 'vue'

const question = ref('')
const responseMessage = ref<string | null>(null)
const isThinking = ref(false)

const askAI = () => {
  if (!question.value.trim()) return

  isThinking.value = true
  responseMessage.value = null

  setTimeout(() => {
    isThinking.value = false
    const q = question.value.toLowerCase()
    if (q.includes('muna') || q.includes('buton') || q.includes('tinggal') || q.includes('kurang')) {
      responseMessage.value = '💡 Analisis DMF: Kab. Muna mengalami penurunan kumulatif sebesar 5 dokumen (70 -> 65) pada Sakernas Periode 2 dan tiket klarifikasi sedang menunggu verifikasi PJK.'
    } else if (q.includes('tercapai') || q.includes('tertinggi') || q.includes('terbaik')) {
      responseMessage.value = '🏆 Satker Terbaik: Kota Kendari & Kab. Kolaka telah mencapai 100% target akhir untuk Sakernas 2026 tanpa ada keterlambatan.'
    } else {
      responseMessage.value = `📊 Rekap WITA: Dari 42 kegiatan aktif, tingkat ketercapaian agregat berada di 78.4%. Sebanyak 20 wilayah masih menunggu pengiriman data periode ini.`
    }
  }, 600)
}
</script>

<template>
  <div class="shoppers-card p-5 flex flex-col justify-between relative overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between z-10">
      <div class="flex items-center gap-2">
        <span class="font-outfit text-[15px] font-bold text-text-title">DMF AI Assistant</span>
        <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-brand-cream text-brand-navy">AI PRO</span>
      </div>
      <button class="text-text-muted hover:text-text-title">
        <span class="material-symbols-outlined text-[16px]">open_in_full</span>
      </button>
    </div>

    <!-- Center 3D Glowing Orb Visual (Shopeers Style) -->
    <div class="my-3 flex flex-col items-center justify-center relative">
      <div class="w-16 h-16 rounded-full orb-3d flex items-center justify-center animate-pulse">
        <span class="material-symbols-outlined text-white text-[24px]">smart_toy</span>
      </div>
      <span class="font-manrope text-[11px] font-medium text-text-muted mt-2 text-center">
        Tanyakan evaluasi capaian atau kendala lapangan
      </span>

      <!-- AI Response Box -->
      <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
        <div
          v-if="responseMessage"
          class="mt-2 p-2.5 rounded-xl bg-brand-navy-subtle border border-[#D5E1F0] text-[11px] font-manrope text-brand-navy leading-relaxed w-full text-left"
        >
          {{ responseMessage }}
        </div>
      </transition>

      <div v-if="isThinking" class="mt-2 text-[11px] font-manrope text-brand-slate flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-brand-amber animate-ping"></span>
        Menganalisis data BPS...
      </div>
    </div>

    <!-- Interactive Prompt Input Bar -->
    <div class="relative w-full z-10 mt-1">
      <input
        v-model="question"
        @keyup.enter="askAI"
        type="text"
        placeholder="Tanya asisten DMF..."
        class="w-full bg-[#F6F8FB] border border-[#EAEFF5] rounded-full py-2 pl-4 pr-16 text-[12px] font-manrope text-text-title placeholder:text-text-muted focus:outline-none focus:border-brand-navy focus:bg-white transition-all"
      />
      <div class="absolute right-1.5 top-1/2 -translate-y-1/2 flex items-center gap-1">
        <button
          @click="question = 'Wilayah mana yang paling tertinggal?'; askAI()"
          class="w-6 h-6 rounded-full flex items-center justify-center text-text-muted hover:text-brand-navy"
          title="Contoh pertanyaan"
        >
          <span class="material-symbols-outlined text-[14px]">mic</span>
        </button>
        <button
          @click="askAI"
          class="w-6 h-6 rounded-full bg-brand-navy text-white flex items-center justify-center hover:bg-brand-navy-dark transition-transform active:scale-90"
        >
          <span class="material-symbols-outlined text-[13px]">arrow_upward</span>
        </button>
      </div>
    </div>
  </div>
</template>
