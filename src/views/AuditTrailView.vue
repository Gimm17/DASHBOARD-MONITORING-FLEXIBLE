<script setup lang="ts">
import { ref, onMounted } from "vue";
import { api, errorText, number, dateTime } from "../services/api";
const tickets = ref<any[]>([]),
  logs = ref<any[]>([]),
  error = ref(""),
  busy = ref(false),
  page = ref(1),
  lastPage = ref(1);
async function load() {
  busy.value = true;
  try {
    tickets.value = (await api("tickets")).tickets;
    const d = (await api("audit?page=" + page.value)).logs;
    logs.value = d.data;
    lastPage.value = d.last_page;
  } catch (e) {
    error.value = errorText(e);
  } finally {
    busy.value = false;
  }
}
onMounted(load);
async function review(t: any, action: string) {
  const message = prompt(
    action === "clarify"
      ? "Pertanyaan klarifikasi (tersimpan pada tiket):"
      : "Catatan keputusan (minimal 5 karakter):",
  );
  if (!message) return;
  error.value = "";
  try {
    await api("tickets/" + t.id + "/review", {
      method: "POST",
      body: { action, message },
    });
    await load();
  } catch (e) {
    error.value = errorText(e);
  }
}
function pretty(v: string | null) {
  if (!v) return "—";
  try {
    return JSON.stringify(JSON.parse(v));
  } catch {
    return v;
  }
}
function print() {
  window.print();
}
</script>
<template>
  <div class="space-y-5">
    <div class="flex justify-between">
      <div>
        <h1 class="font-outfit text-2xl font-bold text-brand-navy">
          Audit Trail dan Koreksi
        </h1>
        <p class="text-sm text-text-muted">
          Riwayat angka tersimpan dan keputusan penanggung jawab
        </p>
      </div>
      <button class="btn-secondary no-print" @click="print">
        Cetak riwayat
      </button>
    </div>
    <p v-if="error" role="alert" class="notice error">{{ error }}</p>
    <p v-if="busy" class="notice">Memuat riwayat…</p>
    <div class="shoppers-card overflow-x-auto">
      <h2 class="font-bold text-brand-navy p-5">Tiket koreksi</h2>
      <table class="data-table min-w-[800px]">
        <thead>
          <tr>
            <th>Tiket dan kegiatan</th>
            <th>Wilayah</th>
            <th>Perubahan</th>
            <th>Alasan</th>
            <th>Status</th>
            <th>Keputusan</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in tickets" :key="t.id">
            <td>
              #{{ t.id }} · {{ t.activity_title }}
              <p class="text-xs text-text-muted">
                {{ t.period_label }} · {{ t.operator }} ·
                {{ dateTime(t.created_at) }}
              </p>
            </td>
            <td>{{ t.region_name }}</td>
            <td>{{ number(t.old_value) }} → {{ number(t.new_value) }}</td>
            <td class="max-w-xs">
              {{ t.reason }}
              <p class="text-xs text-text-muted">{{ t.document_ref }}</p>
              <p v-if="t.response" class="mt-2">Tanggapan: {{ t.response }}</p>
            </td>
            <td>
              <span class="badge">{{ t.status.replaceAll("_", " ") }}</span>
            </td>
            <td>
              <div
                v-if="
                  t.can_review && !['DISETUJUI', 'DITOLAK'].includes(t.status)
                "
                class="flex flex-col gap-2 no-print"
              >
                <button class="btn-primary" @click="review(t, 'approve')">
                  Setujui</button
                ><button class="btn-secondary" @click="review(t, 'clarify')">
                  Klarifikasi</button
                ><button class="btn-secondary" @click="review(t, 'reject')">
                  Tolak
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!tickets.length">
            <td colspan="6" class="text-center !py-8 text-text-muted">
              Belum ada tiket koreksi.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="shoppers-card overflow-x-auto">
      <h2 class="font-bold text-brand-navy p-5">Jejak perubahan</h2>
      <table class="data-table min-w-[800px]">
        <thead>
          <tr>
            <th>Waktu dan pelaku</th>
            <th>Kegiatan</th>
            <th>Tindakan</th>
            <th>Sebelum</th>
            <th>Sesudah</th>
            <th>Alasan</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id">
            <td>
              {{ dateTime(log.created_at) }}
              <p>{{ log.operator }}</p>
            </td>
            <td>
              {{ log.activity_title || "Administrasi" }}
              <p>{{ log.region_name }}</p>
            </td>
            <td>{{ log.action }}</td>
            <td class="max-w-xs break-all text-xs">
              {{ pretty(log.before_data) }}
            </td>
            <td class="max-w-xs break-all text-xs">
              {{ pretty(log.after_data) }}
            </td>
            <td>{{ log.reason || "—" }}</td>
          </tr>
        </tbody>
      </table>
      <div class="p-4 flex gap-3 items-center no-print">
        <button
          :disabled="page <= 1"
          class="btn-secondary"
          @click="
            page--;
            load();
          "
        >
          Sebelumnya</button
        ><span class="text-xs">Halaman {{ page }}/{{ lastPage }}</span
        ><button
          :disabled="page >= lastPage"
          class="btn-secondary"
          @click="
            page++;
            load();
          "
        >
          Berikutnya
        </button>
      </div>
    </div>
  </div>
</template>
