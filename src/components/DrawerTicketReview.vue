<script setup lang="ts">
import { AuditRecord } from '../types'

defineProps<{
  isOpen: boolean
  ticket: AuditRecord | null
}>()

const emit = defineEmits(['close', 'approve', 'clarify', 'previewPdf'])
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

    <!-- Drawer Panel -->
    <transition
      enter-active-class="transition-transform duration-300 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <div
        v-if="isOpen && ticket"
        class="fixed right-0 top-0 h-screen w-full max-w-[460px] bg-white shadow-2xl z-50 flex flex-col justify-between border-l border-[#EAEFF5]"
      >
        <!-- Header -->
        <div class="p-6 border-b border-[#EAEFF5] flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2">
              <span class="font-mono text-[11px] font-bold text-text-muted bg-[#F2F4F7] px-2 py-0.5 rounded">
                {{ ticket.ticketNo }}
              </span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-bold font-manrope"
                :class="ticket.status === 'DISETUJUI' ? 'bg-[#EAF8EE] text-[#1E824C]' : 'bg-[#FFF7EC] text-[#B45309]'"
              >
                {{ ticket.status === 'DISETUJUI' ? 'DISETUJUI ✓' : 'MENUNGGU VERIFIKASI' }}
              </span>
            </div>
            <h3 class="font-outfit text-[17px] font-bold text-text-title mt-1.5 leading-tight">
              Review Usulan Koreksi Data
            </h3>
          </div>
          <button
            @click="emit('close')"
            class="w-8 h-8 rounded-full border border-[#EAEFF5] flex items-center justify-center text-text-muted hover:text-text-title hover:bg-[#F6F8FB]"
          >
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 flex flex-col gap-4">
          <!-- Wilayah Box -->
          <div class="p-4 rounded-xl bg-[#F6F8FB] border border-[#EAEFF5]">
            <span class="block text-[11px] font-semibold text-text-muted">Satuan Kerja</span>
            <span class="font-outfit text-[16px] font-bold text-brand-navy">{{ ticket.regionName }}</span>
            <span class="block text-[12px] text-text-body mt-0.5">{{ ticket.activityTitle }}</span>
            <span class="block text-[11px] font-medium text-brand-slate mt-0.5">{{ ticket.periodLabel }}</span>
          </div>

          <!-- Value Change Comparison -->
          <div class="p-4 rounded-xl border border-[#EAEFF5] bg-white flex items-center justify-between">
            <div>
              <span class="block text-[11px] font-semibold text-text-muted">Realisasi Semula</span>
              <span class="font-outfit text-[18px] font-bold text-text-title font-mono">{{ ticket.oldValue }} Dok</span>
            </div>
            <span class="material-symbols-outlined text-brand-slate text-[22px]">trending_flat</span>
            <div>
              <span class="block text-[11px] font-semibold text-text-muted">Usulan Koreksi</span>
              <span
                class="font-outfit text-[18px] font-bold font-mono"
                :class="ticket.newValue < ticket.oldValue ? 'text-red-600' : 'text-emerald-600'"
              >
                {{ ticket.newValue }} Dok
              </span>
            </div>
          </div>

          <!-- Justification Reason -->
          <div class="p-4 rounded-xl border border-[#EAEFF5] bg-white">
            <span class="block text-[11px] font-bold text-text-title mb-1">Keterangan / Alasan Lapangan</span>
            <p class="font-manrope text-[12px] text-text-body leading-relaxed">
              {{ ticket.alasan }}
            </p>
          </div>

          <!-- Surat Ref -->
          <div v-if="ticket.suratRef" class="p-3 rounded-xl bg-brand-cream/20 border border-brand-cream flex items-center justify-between">
            <div class="flex items-center gap-2 text-[12px]">
              <span class="material-symbols-outlined text-[18px] text-brand-navy">description</span>
              <span class="font-mono text-brand-navy font-semibold">{{ ticket.suratRef }}</span>
            </div>
            <button
              @click="emit('previewPdf')"
              class="text-[11px] font-bold text-brand-navy hover:underline flex items-center gap-0.5"
            >
              Lihat PDF
              <span class="material-symbols-outlined text-[14px]">open_in_new</span>
            </button>
          </div>

          <!-- Operator Info -->
          <div class="text-[11px] font-manrope text-text-muted flex items-center justify-between pt-2">
            <span>Diajukan oleh: <strong>{{ ticket.operator }}</strong></span>
            <span>{{ ticket.timestamp }}</span>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="p-5 border-t border-[#EAEFF5] bg-[#FBFDFE] flex items-center justify-between">
          <button
            @click="emit('clarify')"
            class="px-3.5 py-2 rounded-xl border border-brand-slate text-brand-slate hover:bg-brand-slate-subtle text-[12px] font-bold transition-all flex items-center gap-1.5"
          >
            <span class="material-symbols-outlined text-[16px]">chat</span>
            Klarifikasi
          </button>

          <div class="flex items-center gap-2">
            <button
              @click="emit('close')"
              class="px-3 py-2 rounded-xl text-text-muted hover:text-text-title text-[12px] font-semibold"
            >
              Tutup
            </button>
            <button
              v-if="ticket.status !== 'DISETUJUI'"
              @click="emit('approve')"
              class="px-4 py-2 rounded-xl bg-brand-navy text-white text-[12px] font-bold hover:bg-brand-navy-dark transition-all shadow-sm flex items-center gap-1"
            >
              <span class="material-symbols-outlined text-[16px]">check</span>
              Setujui Koreksi
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
