<script setup lang="ts">
import { ref } from 'vue'

defineProps<{
  isOpen: boolean
  regionName?: string
}>()

const emit = defineEmits(['close', 'send'])
const message = ref('')

const sendMessage = () => {
  if (!message.value.trim()) return
  emit('send', message.value)
  message.value = ''
}
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
      <div v-if="isOpen" class="fixed inset-0 bg-brand-navy/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl w-full max-w-[480px] shadow-2xl border border-[#EAEFF5] overflow-hidden">
          <div class="p-5 border-b border-[#EAEFF5] flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-brand-navy text-[20px]">chat</span>
              <h3 class="font-outfit text-[16px] font-bold text-text-title">
                Kirim Klarifikasi ke {{ regionName || 'Satker Kabupaten' }}
              </h3>
            </div>
            <button @click="emit('close')" class="text-text-muted hover:text-text-title">
              <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
          </div>

          <div class="p-5 space-y-3">
            <p class="text-[12px] font-manrope text-text-muted">
              Pesan ini akan langsung diteruskan ke dasbor operator {{ regionName }} beserta penanda tiket koreksi.
            </p>
            <textarea
              v-model="message"
              rows="4"
              placeholder="Contoh: Mohon unggah lampiran berita acara pemeriksaan kuesioner duplikat untuk kelengkapan berkas audit..."
              class="w-full bg-[#F6F8FB] border border-[#EAEFF5] rounded-xl p-3 text-[12px] font-manrope text-text-title focus:bg-white focus:border-brand-navy focus:outline-none"
            ></textarea>
          </div>

          <div class="p-4 px-5 border-t border-[#EAEFF5] bg-[#FBFDFE] flex items-center justify-end gap-2.5">
            <button
              @click="emit('close')"
              class="px-4 py-2 rounded-xl text-[12px] font-semibold text-text-muted hover:text-text-title"
            >
              Batal
            </button>
            <button
              @click="sendMessage"
              class="px-5 py-2 rounded-xl bg-brand-navy text-white text-[12px] font-bold hover:bg-brand-navy-dark transition-all shadow-sm flex items-center gap-1.5"
            >
              <span class="material-symbols-outlined text-[16px]">send</span>
              Kirim Pesan
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
