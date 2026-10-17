<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { mockAuditRecords } from '../data/mockData'
import { AuditRecord } from '../types'
import DrawerTicketReview from '../components/DrawerTicketReview.vue'
import ModalPdfPreview from '../components/ModalPdfPreview.vue'
import DialogClarification from '../components/DialogClarification.vue'

const router = useRouter()
const records = ref<AuditRecord[]>([...mockAuditRecords])

const selectedTicket = ref<AuditRecord | null>(null)
const isDrawerOpen = ref(false)
const isPdfOpen = ref(false)
const isClarificationOpen = ref(false)

const openTicketReview = (record: AuditRecord) => {
  selectedTicket.value = record
  isDrawerOpen.value = true
}

const openPdf = (record?: AuditRecord) => {
  if (record) selectedTicket.value = record
  isPdfOpen.value = true
}

const openClarify = (record?: AuditRecord) => {
  if (record) selectedTicket.value = record
  isClarificationOpen.value = true
}

const handleApprove = () => {
  if (selectedTicket.value) {
    selectedTicket.value.status = 'DISETUJUI'
    alert(`Tiket ${selectedTicket.value.ticketNo} berhasil disetujui! Perubahan nilai kini resmi tercatat.`)
  }
  isDrawerOpen.value = false
}

const handleSendClarification = (msg: string) => {
  window.alert(`Pesan berhasil dikirim ke ${selectedTicket.value?.regionName}: "${msg}"`)
  isClarificationOpen.value = false
}

const downloadPdf = () => {
  window.alert('Mencetak berkas Berita Acara resmi BPS...')
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div class="flex items-center gap-3">
        <button
          @click="router.push('/')"
          class="w-9 h-9 rounded-xl border border-[#EAEFF5] bg-white text-text-muted hover:text-brand-navy hover:bg-[#F6F8FB] flex items-center justify-center transition-all shadow-xs"
        >
          <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </button>
        <div>
          <h1 class="font-outfit text-[22px] font-bold text-text-title tracking-tight leading-snug">
            Log Audit Trail &amp; Tiket Koreksi Nilai
          </h1>
          <p class="font-manrope text-[12px] text-text-muted">
            Riwayat modifikasi data, penurunan nilai kumulatif (BR-07), dan persetujuan PJK BPS Provinsi
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <span class="px-3 py-1.5 rounded-xl bg-white border border-[#EAEFF5] text-[12px] font-manrope font-semibold text-text-muted">
          Total: <strong>{{ records.length }} Tiket</strong>
        </span>
      </div>
    </div>

    <!-- Table Card -->
    <div class="shoppers-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[850px]">
          <thead>
            <tr class="bg-[#F6F8FB] border-b border-[#EAEFF5] text-[11px] font-outfit uppercase tracking-wider text-text-muted">
              <th class="py-3 px-4">No. Tiket</th>
              <th class="py-3 px-4">Satuan Kerja &amp; Kegiatan</th>
              <th class="py-3 px-4 text-center">Perubahan Nilai</th>
              <th class="py-3 px-4">Alasan Koreksi</th>
              <th class="py-3 px-4">Waktu &amp; Pengaju</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#EAEFF5] text-[13px] font-manrope">
            <tr v-for="rec in records" :key="rec.id" class="hover:bg-[#FCFDFE] transition-colors">
              <!-- Ticket No -->
              <td class="py-3.5 px-4 font-mono font-bold text-brand-navy">
                {{ rec.ticketNo }}
              </td>

              <!-- Satker & Activity -->
              <td class="py-3.5 px-4">
                <span class="font-outfit font-bold text-text-title block leading-snug">{{ rec.regionName }}</span>
                <span class="text-[11px] text-text-muted">{{ rec.activityTitle }} — {{ rec.periodLabel }}</span>
              </td>

              <!-- Value Change -->
              <td class="py-3.5 px-4 text-center font-mono font-bold">
                <span class="text-text-muted">{{ rec.oldValue }}</span>
                <span class="text-brand-slate mx-1.5">→</span>
                <span :class="rec.newValue < rec.oldValue ? 'text-red-600' : 'text-emerald-600'">
                  {{ rec.newValue }}
                </span>
                <span v-if="rec.newValue < rec.oldValue" class="text-[10px] text-red-500 ml-1">
                  ({{ rec.newValue - rec.oldValue }})
                </span>
              </td>

              <!-- Reason -->
              <td class="py-3.5 px-4 max-w-[280px]">
                <p class="text-[12px] text-text-body line-clamp-2 leading-relaxed">
                  {{ rec.alasan }}
                </p>
                <span v-if="rec.suratRef" class="text-[10px] font-mono text-brand-slate block mt-0.5">
                  Ref: {{ rec.suratRef }}
                </span>
              </td>

              <!-- Timestamp & Operator -->
              <td class="py-3.5 px-4 text-[12px]">
                <span class="text-text-title font-semibold block">{{ rec.operator }}</span>
                <span class="text-[11px] text-text-muted font-mono">{{ rec.timestamp }}</span>
              </td>

              <!-- Status -->
              <td class="py-3.5 px-4 text-center">
                <span
                  class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold font-manrope"
                  :class="rec.status === 'DISETUJUI' ? 'bg-[#EAF8EE] text-[#1E824C]' : 'bg-[#FFF7EC] text-[#B45309]'"
                >
                  {{ rec.status === 'DISETUJUI' ? 'DISETUJUI ✓' : 'MENUNGGU VERIFIKASI' }}
                </span>
              </td>

              <!-- Actions -->
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openPdf(rec)"
                    class="p-1.5 rounded-lg border border-[#EAEFF5] text-text-muted hover:text-brand-navy hover:bg-[#F2F4F7]"
                    title="Lihat Berita Acara PDF"
                  >
                    <span class="material-symbols-outlined text-[17px]">picture_as_pdf</span>
                  </button>
                  <button
                    @click="openTicketReview(rec)"
                    class="px-2.5 py-1.5 rounded-lg bg-brand-navy text-white text-[11px] font-bold hover:bg-brand-navy-dark transition-all shadow-xs"
                  >
                    Review Tiket
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Review Drawer -->
    <DrawerTicketReview
      :isOpen="isDrawerOpen"
      :ticket="selectedTicket"
      @close="isDrawerOpen = false"
      @approve="handleApprove"
      @clarify="openClarify(selectedTicket ?? undefined)"
      @previewPdf="openPdf(selectedTicket ?? undefined)"
    />

    <!-- PDF Modal -->
    <ModalPdfPreview
      :isOpen="isPdfOpen"
      :ticketNo="selectedTicket?.ticketNo"
      :regionName="selectedTicket?.regionName"
      @close="isPdfOpen = false"
      @download="downloadPdf"
    />

    <!-- Clarification Dialog -->
    <DialogClarification
      :isOpen="isClarificationOpen"
      :regionName="selectedTicket?.regionName"
      @close="isClarificationOpen = false"
      @send="handleSendClarification"
    />
  </div>
</template>
