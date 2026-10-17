<script setup lang="ts">
import { ref } from 'vue'

defineProps<{
  isOpen: boolean
  regionName?: string
  oldVal?: number
  newVal?: number | string
}>()

const emit = defineEmits(['close', 'confirm'])

const justificationReason = ref('Koreksi 5 kuesioner duplikat pada blok sensus 012B setelah verifikasi pengawas lapangan.')
const docRef = ref('B-1402/BPS/7402/VS/10/2026')

const submit = () => {
  emit('confirm', {
    reason: justificationReason.value,
    docRef: docRef.value,
  })
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
        <div class="bg-white rounded-2xl w-full max-w-[500px] shadow-2xl border border-[#EAEFF5] overflow-hidden">
          <!-- Header with Warning Badge -->
          <div class="p-6 bg-[#FFF8EE] border-b border-brand-amber/30 flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-amber text-brand-navy flex items-center justify-center shrink-0 shadow-xs">
              <span class="material-symbols-outlined text-[24px]">warning</span>
            </div>
            <div>
              <h3 class="font-outfit text-[17px] font-bold text-text-title leading-tight">
                Justifikasi Penurunan Realisasi Kumulatif
              </h3>
              <p class="font-manrope text-[12px] text-text-muted mt-0.5">
                Aturan Bisnis BR-07: Nilai kumulatif tidak boleh menurun tanpa verifikasi dan alasan resmi.
              </p>
            </div>
          </div>

          <!-- Body -->
          <div class="p-6 flex flex-col gap-4">
            <!-- Region & Value Diff Box -->
            <div class="p-3.5 rounded-xl bg-[#F6F8FB] border border-[#EAEFF5] flex items-center justify-between">
              <div>
                <span class="block text-[11px] font-semibold text-text-muted">Wilayah Partisipan</span>
                <span class="font-outfit text-[14px] font-bold text-brand-navy">{{ regionName || '7402 KAB. MUNA' }}</span>
              </div>
              <div class="flex items-center gap-2 font-mono text-[13px] font-bold">
                <span class="px-2 py-0.5 rounded bg-white border border-[#EAEFF5] text-text-muted">
                  {{ oldVal || 70 }} Dok
                </span>
                <span class="text-brand-slate">→</span>
                <span class="px-2 py-0.5 rounded bg-red-100 text-red-700 border border-red-200">
                  {{ newVal || 65 }} Dok
                </span>
                <span class="text-[11px] text-red-600 font-bold">(-5)</span>
              </div>
            </div>

            <!-- Reason Field -->
            <div>
              <label class="block font-manrope text-[12px] font-bold text-text-title mb-1.5">
                Alasan Penurunan / Koreksi Data (Wajib)
              </label>
              <textarea
                v-model="justificationReason"
                rows="3"
                class="w-full bg-[#F6F8FB] border border-[#EAEFF5] rounded-xl p-3 text-[12px] font-manrope text-text-title focus:bg-white focus:border-brand-navy focus:outline-none transition-all"
                placeholder="Tuliskan alasan koreksi lapangan..."
              ></textarea>
            </div>

            <!-- Official Document Number Field -->
            <div>
              <label class="block font-manrope text-[12px] font-bold text-text-title mb-1.5">
                Nomor Surat / Referensi Berita Acara
              </label>
              <input
                v-model="docRef"
                type="text"
                class="w-full bg-[#F6F8FB] border border-[#EAEFF5] rounded-xl px-3 py-2 text-[12px] font-manrope text-text-title focus:bg-white focus:border-brand-navy focus:outline-none transition-all"
                placeholder="Contoh: B-1402/BPS/7402/VS/10/2026"
              />
            </div>
          </div>

          <!-- Footer Buttons -->
          <div class="p-5 border-t border-[#EAEFF5] bg-[#FBFDFE] flex items-center justify-end gap-3">
            <button
              @click="emit('close')"
              class="px-4 py-2 rounded-xl border border-[#EAEFF5] font-manrope text-[12px] font-semibold text-text-muted hover:text-text-title transition-all"
            >
              Batalkan
            </button>
            <button
              @click="submit"
              class="px-5 py-2 rounded-xl bg-brand-navy text-white font-manrope text-[12px] font-bold hover:bg-brand-navy-dark transition-all shadow-sm flex items-center gap-1.5"
            >
              <span class="material-symbols-outlined text-[16px]">check_circle</span>
              Simpan &amp; Ajukan Tiket
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
