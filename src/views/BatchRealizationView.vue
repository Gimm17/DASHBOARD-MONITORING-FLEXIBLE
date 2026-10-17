<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { mockBatchRows } from '../data/mockData'
import { BatchRow } from '../types'
import ModalDecreaseJustification from '../components/ModalDecreaseJustification.vue'

const router = useRouter()
const rows = ref<BatchRow[]>([...mockBatchRows])

const isJustifyModalOpen = ref(false)
const targetRowForJustification = ref<BatchRow | null>(null)
const hasUnsavedChanges = ref(false)

const handleValueChange = (row: BatchRow) => {
  hasUnsavedChanges.value = true
  const numVal = Number(row.realisasiKini)

  if (row.realisasiKini !== '' && !isNaN(numVal) && numVal < row.realisasiLalu) {
    row.status = 'warning'
    row.errorMessage = `Nilai kumulatif turun dari ${row.realisasiLalu} -> ${numVal}. Alasan wajib disertakan.`
    targetRowForJustification.value = row
    isJustifyModalOpen.value = true
  } else {
    row.status = 'idle'
    row.errorMessage = undefined
  }
}

const onJustificationConfirm = (data: { reason: string; docRef: string }) => {
  if (targetRowForJustification.value) {
    targetRowForJustification.value.kendala = data.reason
    targetRowForJustification.value.status = 'saved'
    targetRowForJustification.value.errorMessage = `Justifikasi tersimpan: ${data.docRef}`
  }
  isJustifyModalOpen.value = false
}

const saveBatch = () => {
  rows.value.forEach((r) => {
    if (r.realisasiKini !== '') {
      r.status = 'saved'
    }
  })
  hasUnsavedChanges.value = false
  alert('Batch 20 baris berhasil disimpan secara atomik dengan jejak audit log!')
}

const warningCount = computed(() => rows.value.filter((r) => r.status === 'warning').length)
</script>

<template>
  <div class="space-y-6 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div class="flex items-center gap-3">
        <button
          @click="router.push('/matriks')"
          class="w-9 h-9 rounded-xl border border-[#EAEFF5] bg-white text-text-muted hover:text-brand-navy hover:bg-[#F6F8FB] flex items-center justify-center transition-all shadow-xs"
        >
          <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </button>
        <div>
          <div class="flex items-center gap-2">
            <span class="font-mono text-[11px] font-bold text-brand-navy bg-brand-navy-subtle px-2 py-0.5 rounded">
              PERIODE 3
            </span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#EAF8EE] text-[#1E824C]">
              TERBUKA UNTUK INPUT
            </span>
          </div>
          <h1 class="font-outfit text-[22px] font-bold text-text-title tracking-tight leading-snug mt-1">
            Input Realisasi Batch — Sakernas 2026
          </h1>
          <p class="font-manrope text-[12px] text-text-muted">
            Batas Waktu Pelaporan: <strong>15 Okt 2026, 17:00 WITA</strong> | Maksimum 20 baris per batch
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <button
          @click="saveBatch"
          class="flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-brand-navy text-white text-[12px] font-manrope font-bold hover:bg-brand-navy-dark transition-all shadow-md active:scale-95"
        >
          <span class="material-symbols-outlined text-[18px]">save</span>
          <span>Simpan Realisasi (Ctrl+S)</span>
        </button>
      </div>
    </div>

    <!-- Quick Help & Guidelines Banner (PRD Kumulatif) -->
    <div class="p-4 px-5 rounded-2xl bg-[#FFF8EE] border border-brand-amber/40 flex items-start gap-3 shadow-xs">
      <span class="material-symbols-outlined text-brand-amber-deep text-[22px] shrink-0 mt-0.5">lightbulb</span>
      <div class="text-[12px] font-manrope text-text-body leading-relaxed">
        <strong class="font-bold text-brand-navy">Panduan Standar Pencatatan (PRD Kumulatif):</strong>
        1. Nilai bersifat <strong>kumulatif</strong>. Jika periode 1 = 30 dan periode 2 bertambah 45, ketik <strong>75</strong>.
        2. Biarkan <strong>KOSONG</strong> jika kabupaten/kota belum melapor; ketik <strong>0</strong> hanya jika capaian memang nol.
      </div>
    </div>

    <!-- Batch Spreadsheet Card -->
    <div class="shoppers-card overflow-hidden">
      <div class="p-4 px-6 bg-[#FBFDFE] border-b border-[#EAEFF5] flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="font-outfit text-[14px] font-bold text-text-title">Tabel Matriks Input Realisasi</span>
          <span class="text-[11px] font-mono text-text-muted">(Batch 1: 7 Satker Aktif)</span>
        </div>
        <div class="text-[11px] font-semibold text-text-muted">
          Klik baris mana saja untuk mengedit angka realisasi
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[850px]">
          <thead>
            <tr class="bg-[#F6F8FB] border-b border-[#EAEFF5] text-[11px] font-outfit uppercase tracking-wider text-text-muted">
              <th class="py-3 px-4 w-[220px]">Wilayah Partisipan</th>
              <th class="py-3 px-3 text-center w-[110px]">Target Periode</th>
              <th class="py-3 px-3 text-center w-[110px]">Realisasi Lalu</th>
              <th class="py-3 px-3 w-[150px]">Realisasi Terkini</th>
              <th class="py-3 px-3 w-[140px]">Tanggal Posisi</th>
              <th class="py-3 px-4">Catatan Kendala</th>
              <th class="py-3 px-3 text-center w-[130px]">Status Simpan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#EAEFF5] text-[13px] font-manrope">
            <tr
              v-for="row in rows"
              :key="row.regionId"
              class="hover:bg-[#FCFDFE] transition-colors"
              :class="{ 'bg-amber-50/40': row.status === 'warning' }"
            >
              <!-- Wilayah -->
              <td class="py-3.5 px-4 font-outfit font-bold text-text-title">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full" :class="row.status === 'saved' ? 'bg-[#1E824C]' : 'bg-brand-slate'"></span>
                  <span>{{ row.regionCode }} {{ row.regionName }}</span>
                </div>
              </td>

              <!-- Target Periode -->
              <td class="py-3 px-3 text-center font-mono font-bold text-text-muted">
                {{ row.targetPeriode }}
              </td>

              <!-- Realisasi Lalu -->
              <td class="py-3 px-3 text-center font-mono text-text-muted">
                {{ row.realisasiLalu }}
              </td>

              <!-- Realisasi Kini Input -->
              <td class="py-2.5 px-3">
                <div class="relative">
                  <input
                    v-model="row.realisasiKini"
                    type="number"
                    step="any"
                    @input="handleValueChange(row)"
                    placeholder="[Kosong]"
                    class="w-full bg-white border rounded-xl px-3 py-1.5 font-mono text-[13px] font-bold text-text-title focus:outline-none focus:ring-2 focus:ring-brand-amber transition-all shadow-xs"
                    :class="[
                      row.status === 'warning'
                        ? 'border-red-400 bg-red-50/20 text-red-700'
                        : 'border-[#EAEFF5] focus:border-brand-navy'
                    ]"
                  />
                  <span v-if="row.status === 'warning'" class="material-symbols-outlined text-brand-amber-deep text-[18px] absolute right-2.5 top-1/2 -translate-y-1/2">
                    warning
                  </span>
                </div>
              </td>

              <!-- Tanggal Posisi Data -->
              <td class="py-2.5 px-3">
                <input
                  v-model="row.tanggalPosisi"
                  type="date"
                  class="w-full bg-white border border-[#EAEFF5] rounded-xl px-2.5 py-1.5 text-[11px] font-mono text-text-title focus:outline-none focus:border-brand-navy shadow-xs"
                />
              </td>

              <!-- Kendala -->
              <td class="py-2.5 px-4">
                <input
                  v-model="row.kendala"
                  type="text"
                  placeholder="Catatan lapangan opsional..."
                  class="w-full bg-white border border-[#EAEFF5] rounded-xl px-3 py-1.5 text-[12px] font-manrope text-text-title placeholder:text-text-muted focus:outline-none focus:border-brand-navy shadow-xs"
                />
              </td>

              <!-- Status Simpan Badge -->
              <td class="py-3 px-3 text-center">
                <span
                  v-if="row.status === 'saved'"
                  class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold font-manrope bg-[#EAF8EE] text-[#1E824C]"
                >
                  <span class="material-symbols-outlined text-[13px]">check_circle</span>
                  Tersimpan
                </span>
                <span
                  v-else-if="row.status === 'warning'"
                  class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold font-manrope bg-[#FFF7EC] text-[#B45309]"
                >
                  <span class="material-symbols-outlined text-[13px]">warning</span>
                  Koreksi
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold font-manrope bg-[#F2F4F7] text-text-muted"
                >
                  Belum Simpan
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Sticky Bottom Bar if unsaved changes exist -->
    <div
      v-if="hasUnsavedChanges"
      class="fixed bottom-0 left-[260px] right-0 bg-white border-t border-[#EAEFF5] px-8 py-3.5 flex items-center justify-between shadow-2xl z-20"
    >
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-brand-amber animate-ping"></span>
        <span class="text-[12px] font-manrope font-bold text-text-title">
          Terdapat perubahan realisasi yang belum tersimpan ke server.
        </span>
        <span v-if="warningCount > 0" class="text-[11px] font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded ml-2">
          {{ warningCount }} baris memerlukan justifikasi penurunan nilai
        </span>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="hasUnsavedChanges = false"
          class="px-4 py-2 rounded-xl text-[12px] font-semibold text-text-muted hover:text-text-title"
        >
          Batalkan
        </button>
        <button
          @click="saveBatch"
          class="px-5 py-2 rounded-xl bg-brand-navy text-white text-[12px] font-bold hover:bg-brand-navy-dark transition-all shadow-md"
        >
          Simpan Data Sekarang
        </button>
      </div>
    </div>

    <!-- Modal Justifikasi Penurunan Nilai -->
    <ModalDecreaseJustification
      :isOpen="isJustifyModalOpen"
      :regionName="targetRowForJustification?.regionName"
      :oldVal="targetRowForJustification?.realisasiLalu"
      :newVal="targetRowForJustification?.realisasiKini"
      @close="isJustifyModalOpen = false"
      @confirm="onJustificationConfirm"
    />
  </div>
</template>
