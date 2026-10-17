<script setup lang="ts">
import { ref, computed } from 'vue'
import { mockWidgets } from '../data/mockData'
import { WidgetItem } from '../types'

defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits(['close', 'updateWidgets'])

const widgets = ref<WidgetItem[]>([...mockWidgets])
const selectedCategory = ref('Semua')

const categories = ['Semua', '#Performa', '#Wilayah', '#Jadwal', '#Prioritas']

const filteredWidgets = computed(() => {
  if (selectedCategory.value === 'Semua') return widgets.value
  return widgets.value.filter((w) => w.tags.includes(selectedCategory.value))
})

const toggleInstall = (widget: WidgetItem) => {
  widget.installed = !widget.installed
  emit('updateWidgets', widgets.value)
}
</script>

<template>
  <teleport to="body">
    <!-- Backdrop -->
    <transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 bg-brand-navy/30 backdrop-blur-xs z-50"
        @click="emit('close')"
      ></div>
    </transition>

    <!-- Slide-over Drawer Panel -->
    <transition
      enter-active-class="transition-transform duration-300 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <div
        v-if="isOpen"
        class="fixed right-0 top-0 h-screen w-full max-w-[430px] bg-white shadow-2xl z-50 flex flex-col justify-between border-l border-[#EAEFF5]"
      >
        <!-- Header -->
        <div class="p-6 border-b border-[#EAEFF5] flex items-center justify-between">
          <div>
            <h3 class="font-outfit text-[18px] font-bold text-text-title leading-tight">
              Tambah &amp; Kelola Widget
            </h3>
            <p class="font-manrope text-[12px] text-text-muted mt-0.5">
              Sesuaikan kartu analisis performa pada dashboard Anda.
            </p>
          </div>
          <button
            @click="emit('close')"
            class="w-8 h-8 rounded-full border border-[#EAEFF5] flex items-center justify-center text-text-muted hover:text-text-title hover:bg-[#F6F8FB] transition-all"
          >
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </div>

        <!-- Category Tags Bar -->
        <div class="px-6 py-3 border-b border-[#EAEFF5] flex items-center gap-1.5 overflow-x-auto">
          <button
            v-for="cat in categories"
            :key="cat"
            @click="selectedCategory = cat"
            class="px-3 py-1 rounded-full text-[11px] font-bold font-manrope transition-all shrink-0"
            :class="[
              selectedCategory === cat
                ? 'bg-brand-navy text-white shadow-sm'
                : 'bg-[#F2F4F7] text-text-muted hover:text-text-title'
            ]"
          >
            {{ cat }}
          </button>
        </div>

        <!-- Widget List -->
        <div class="flex-1 overflow-y-auto p-6 flex flex-col gap-3.5">
          <div
            v-for="widget in filteredWidgets"
            :key="widget.id"
            class="p-4 rounded-2xl border border-[#EAEFF5] bg-[#FCFDFE] hover:border-brand-slate transition-all flex flex-col gap-2.5"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-brand-navy-subtle flex items-center justify-center text-brand-navy">
                  <span class="material-symbols-outlined text-[20px]">{{ widget.icon }}</span>
                </div>
                <div>
                  <h4 class="font-outfit text-[14px] font-bold text-text-title leading-snug">
                    {{ widget.title }}
                  </h4>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <span
                      v-for="tag in widget.tags"
                      :key="tag"
                      class="text-[10px] font-bold font-mono text-text-muted"
                    >
                      {{ tag }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Action Button -->
              <button
                @click="toggleInstall(widget)"
                class="px-3 py-1 rounded-full text-[11px] font-bold font-manrope transition-all shrink-0"
                :class="[
                  widget.installed
                    ? 'bg-[#EAF8EE] text-[#1E824C] border border-[#B7E4C7]'
                    : 'bg-white border border-brand-navy text-brand-navy hover:bg-brand-navy hover:text-white shadow-xs'
                ]"
              >
                {{ widget.installed ? 'Terpasang ✓' : '+ Pasang' }}
              </button>
            </div>

            <p class="font-manrope text-[12px] text-text-muted leading-relaxed">
              {{ widget.desc }}
            </p>
          </div>
        </div>

        <!-- Drawer Footer -->
        <div class="p-5 border-t border-[#EAEFF5] flex items-center justify-between bg-[#FBFDFE]">
          <button
            @click="emit('close')"
            class="font-manrope text-[12px] font-semibold text-text-muted hover:text-text-title"
          >
            Tutup
          </button>
          <button
            @click="emit('close')"
            class="px-5 py-2 rounded-xl bg-brand-navy text-white font-manrope text-[12px] font-bold hover:bg-brand-navy-dark transition-all shadow-sm"
          >
            Simpan Tata Letak
          </button>
        </div>
      </div>
    </transition>
  </teleport>
</template>
