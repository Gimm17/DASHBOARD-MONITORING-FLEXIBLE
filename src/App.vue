<script setup lang="ts">
import { ref } from 'vue'
import Sidebar from './components/Sidebar.vue'
import TopNavbar from './components/TopNavbar.vue'

const showSopModal = ref(false)
const showNotificationToast = ref(false)

const handleOpenSop = () => {
  showSopModal.value = true
}

const handleOpenNotifications = () => {
  showNotificationToast.value = true
  setTimeout(() => {
    showNotificationToast.value = false
  }, 4000)
}

const downloadSop = () => {
  showSopModal.value = false
  window.alert('Mengunduh SOP_Monitoring_DMF_v1.0.pdf...')
}
</script>

<template>
  <div class="min-h-screen bg-[#F6F8FB] font-manrope text-text-body flex">
    <!-- Left Fixed Navigation Sidebar -->
    <Sidebar @openSop="handleOpenSop" />

    <!-- Main Content Area -->
    <div class="ml-[260px] flex-1 flex flex-col min-h-screen">
      <!-- Top Sticky Navigation Bar -->
      <TopNavbar @openNotifications="handleOpenNotifications" />

      <!-- Main Router View Page -->
      <main class="flex-1 p-6 md:p-8 max-w-[1600px] w-full mx-auto">
        <router-view />
      </main>
    </div>

    <!-- SOP Modal Preview -->
    <teleport to="body">
      <div
        v-if="showSopModal"
        class="fixed inset-0 bg-brand-navy/40 backdrop-blur-xs flex items-center justify-center p-4 z-50"
      >
        <div class="bg-white rounded-2xl w-full max-w-[550px] shadow-2xl border border-[#EAEFF5] p-6 space-y-4">
          <div class="flex items-center justify-between border-b border-[#EAEFF5] pb-3">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-brand-amber-deep">menu_book</span>
              <h3 class="font-outfit text-[17px] font-bold text-brand-navy">
                Pedoman SOP &amp; Evaluasi BPS Prov. 2026
              </h3>
            </div>
            <button @click="showSopModal = false" class="text-text-muted hover:text-text-title">
              <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
          </div>

          <div class="text-[12px] font-manrope text-text-body space-y-2 leading-relaxed">
            <p><strong>1. Sifat Data Kumulatif:</strong> Seluruh capaian yang diinput adalah total agregat sampai titik tanggal evaluasi (bukan penambahan berkala).</p>
            <p><strong>2. Batas Pelaporan (Due Date):</strong> Laporan melewati pukul 17:00 WITA pada hari jadwal evaluasi akan diberi label status keterlambatan.</p>
            <p><strong>3. Finalisasi Terkunci:</strong> Setelah PJK memfinalkan periode, koreksi hanya dapat diproses melalui pengajuan tiket resmi dengan Berita Acara tertulis.</p>
          </div>

          <div class="pt-3 border-t border-[#EAEFF5] flex justify-end gap-2">
            <button
              @click="showSopModal = false"
              class="px-4 py-2 rounded-xl border border-[#EAEFF5] text-[12px] font-semibold text-text-muted"
            >
              Tutup
            </button>
            <button
              @click="downloadSop"
              class="px-5 py-2 rounded-xl bg-brand-navy text-white text-[12px] font-bold hover:bg-brand-navy-dark transition-all flex items-center gap-1.5 shadow-sm"
            >
              <span class="material-symbols-outlined text-[16px]">download</span>
              Unduh Dokumen Lengkap (.PDF)
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Notification Toast -->
    <teleport to="body">
      <transition enter-active-class="transition duration-200 ease-out" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100">
        <div
          v-if="showNotificationToast"
          class="fixed bottom-6 right-6 bg-brand-navy text-white p-4 px-5 rounded-2xl shadow-2xl border border-white/10 z-50 flex items-center gap-3 text-[12px] font-manrope max-w-[380px]"
        >
          <span class="material-symbols-outlined text-brand-amber text-[22px]">notifications_active</span>
          <div>
            <strong class="font-bold block font-outfit text-white">Notifikasi Evaluasi Terkini</strong>
            BPS Kab. Muna mengajukan tiket koreksi data Sakernas (70 -> 65 Dokumen).
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>
