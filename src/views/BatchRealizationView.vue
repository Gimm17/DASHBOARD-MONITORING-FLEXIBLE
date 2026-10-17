<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute, useRouter, onBeforeRouteLeave } from "vue-router";
import ModalDecreaseJustification from "../components/ModalDecreaseJustification.vue";
import {
  api,
  errorText,
  number,
  type Detail,
  type Activity,
  type Period,
} from "../services/api";
const route = useRoute(),
  router = useRouter();
const detail = ref<Detail | null>(null),
  activities = ref<Activity[]>([]),
  activityId = ref(String(route.query.activity || "")),
  periodId = ref(String(route.query.period || ""));
const rows = ref<any[]>([]),
  error = ref(""),
  message = ref(""),
  busy = ref(false),
  dirty = ref(false),
  justify = ref(false),
  selected = ref<any>(null);
const period = computed<Period | undefined>(() =>
  detail.value?.periods.find((p) => String(p.id) === periodId.value),
);
const today = new Date().toLocaleDateString("sv-SE", {
  timeZone: "Asia/Makassar",
});
const editable = computed(
  () =>
    detail.value?.can_write &&
    period.value?.state !== "final" &&
    !!period.value &&
    period.value.position_date <= today &&
    (detail.value?.activity.status === "AKTIF" ||
      period.value?.current_revision_id),
);
let loadedActivity = "",
  loadedPeriod = "";
let retry: { payload: string; key: string } | null = null;
async function load() {
  busy.value = true;
  try {
    activities.value = (await api("dashboard")).activities;
    if (!activityId.value)
      activityId.value = String(activities.value[0]?.id || "");
    if (!activityId.value) return;
    detail.value = await api("activities/" + activityId.value);
    if (!periodId.value)
      periodId.value = String(
        detail
          .value!.periods.filter(
            (p) =>
              p.state !== "final" &&
              p.position_date <=
                new Date().toLocaleDateString("sv-SE", {
                  timeZone: "Asia/Makassar",
                }),
          )
          .at(-1)?.id ||
          detail.value!.periods.at(-1)?.id ||
          "",
      );
    const index = detail.value!.periods.findIndex(
      (p) => String(p.id) === periodId.value,
    );
    rows.value = detail
      .value!.rows.filter((r) => r.cells[index]?.status !== "TIDAK_DINILAI")
      .map((r) => {
        const c = r.cells[index]?.provisional || r.cells[index];
        return {
          regionId: r.id,
          regionCode: r.code,
          regionName: r.name,
          targetPeriode: c?.target,
          realisasiLalu:
            r.cells
              .slice(0, index)
              .filter((c) => c.value !== null)
              .at(-1)?.value ?? null,
          realisasiKini: c?.value ?? "",
          baseline: c?.value ?? null,
          tanggalPosisi: period.value?.position_date,
          kendala: c?.notes || "",
          version: c?.version ?? 0,
          reason: "",
          document_ref: "",
          status: "idle",
          changed: false,
        };
      });
    dirty.value = false;
    loadedActivity = activityId.value;
    loadedPeriod = periodId.value;
  } catch (e) {
    error.value = errorText(e);
  } finally {
    busy.value = false;
  }
}
onMounted(load);
function choose() {
  if (dirty.value && !confirm("Abaikan perubahan yang belum tersimpan?")) {
    activityId.value = loadedActivity;
    periodId.value = loadedPeriod;
    return;
  }
  dirty.value = false;
  router.replace({
    path: "/realisasi",
    query: {
      activity: activityId.value,
      ...(periodId.value ? { period: periodId.value } : {}),
    },
  });
}
function changed(row: any) {
  row.changed = true;
  row.status = "idle";
  dirty.value = true;
}
function checkDecrease(row: any) {
  if (
    ((row.realisasiKini === "" && row.baseline !== null) ||
      (row.realisasiKini !== "" &&
        (row.baseline !== null || row.realisasiLalu !== null) &&
        Number(row.realisasiKini) <
          Math.max(
            Number(row.baseline || 0),
            Number(row.realisasiLalu || 0),
          ))) &&
    !row.reason
  ) {
    selected.value = row;
    justify.value = true;
  }
}
function justification(data: { reason: string; docRef: string }) {
  if (selected.value) {
    selected.value.reason = data.reason;
    selected.value.document_ref = data.docRef;
    changed(selected.value);
  }
  justify.value = false;
}
async function save() {
  if (!period.value || !editable.value) return;
  error.value = "";
  message.value = "";
  busy.value = true;
  try {
    const pending = rows.value.filter((r) => r.changed);
    if (!pending.length) {
      message.value = "Tidak ada perubahan untuk disimpan.";
      return;
    }
    for (let offset = 0; offset < pending.length; offset += 20) {
      const slice = pending.slice(offset, offset + 20);
      const body = {
        rows: slice.map((r) => ({
          region_id: r.regionId,
          value: r.realisasiKini === "" ? null : String(r.realisasiKini),
          version: r.version,
          position_date: r.tanggalPosisi,
          notes: r.kendala,
          reason: r.reason || undefined,
          document_ref: r.document_ref || undefined,
        })),
      };
      const payload = JSON.stringify(body);
      if (!retry || retry.payload !== payload)
        retry = { payload, key: crypto.randomUUID() };
      const result = await api("periods/" + period.value.id + "/realizations", {
        method: "POST",
        body,
        key: retry.key,
      });
      retry = null;
      for (const res of result.results) {
        const row = slice.find((r) => r.regionId === res.region_id);
        if (!row) continue;
        row.status = res.status;
        row.errorMessage = res.errors
          ? Object.values(res.errors).flat().join(" ")
          : res.status === "pending"
            ? "Menunggu tinjauan PJK; angka resmi belum berubah."
            : "";
        if (["saved", "pending"].includes(res.status)) {
          row.changed = false;
          row.version = res.version;
          row.reason = "";
        }
      }
    }
    dirty.value = rows.value.some((r) => r.changed);
    message.value = dirty.value
      ? "Sebagian baris belum tersimpan. Periksa pesan tiap baris."
      : "Semua perubahan telah diproses. Periksa status tiap baris.";
  } catch (e) {
    error.value = errorText(e);
  } finally {
    busy.value = false;
  }
}
function leaving(e: BeforeUnloadEvent) {
  if (dirty.value) {
    e.preventDefault();
    e.returnValue = "";
  }
}
onMounted(() => window.addEventListener("beforeunload", leaving));
onUnmounted(() => window.removeEventListener("beforeunload", leaving));
onBeforeRouteLeave(
  () =>
    !dirty.value ||
    confirm("Masih ada perubahan belum tersimpan. Tinggalkan halaman?"),
);
</script>
<template>
  <div class="space-y-5">
    <div class="flex flex-wrap justify-between gap-3">
      <div>
        <h1 class="font-outfit text-2xl font-bold text-brand-navy">
          Input Realisasi Batch
        </h1>
        <p class="text-sm text-text-muted">
          Nilai kumulatif · maksimum 20 baris per permintaan
        </p>
      </div>
      <button
        :disabled="busy || !editable || !dirty"
        class="btn-primary"
        @click="save"
      >
        {{ busy ? "Menyimpan…" : "Simpan perubahan" }}
      </button>
    </div>
    <div class="flex flex-wrap gap-3">
      <select
        v-model="activityId"
        :disabled="busy"
        class="field max-w-lg"
        aria-label="Kegiatan"
        @change="
          periodId = '';
          choose();
        "
      >
        <option value="">Pilih kegiatan</option>
        <option v-for="a in activities" :value="String(a.id)" :key="a.id">
          {{ a.title }}
        </option></select
      ><select
        v-model="periodId"
        :disabled="busy"
        class="field max-w-xs"
        aria-label="Periode"
        @change="choose"
      >
        <option v-for="p in detail?.periods" :value="String(p.id)" :key="p.id">
          {{ p.label }} · {{ p.position_date }} · {{ p.state }}
        </option>
      </select>
    </div>
    <p v-if="error" role="alert" class="notice error">{{ error }}</p>
    <p v-if="message" role="status" class="notice success">{{ message }}</p>
    <p v-if="!editable && detail" class="notice">
      Periode terkunci atau akun Anda hanya memiliki akses lihat.
    </p>
    <p class="notice">
      Jika periode pertama 30 dan tambahan berikutnya 45, isi
      <strong>75</strong>. Kosong berarti belum melapor;
      <strong>0</strong> berarti capaian nol. Penurunan angka diajukan sebagai
      tiket koreksi dan baru berlaku setelah disetujui PJK.
    </p>
    <div class="shoppers-card overflow-x-auto">
      <table class="data-table min-w-[900px]">
        <thead>
          <tr>
            <th>Wilayah</th>
            <th>Target periode</th>
            <th>Realisasi lalu</th>
            <th>Realisasi kini</th>
            <th>Tanggal posisi</th>
            <th>Kendala dan alasan koreksi</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.regionId">
            <td class="font-bold">{{ row.regionCode }} {{ row.regionName }}</td>
            <td>{{ number(row.targetPeriode) }}</td>
            <td>{{ number(row.realisasiLalu) }}</td>
            <td>
              <input
                v-model="row.realisasiKini"
                :disabled="!editable || busy"
                type="number"
                min="0"
                step="any"
                :aria-label="'Realisasi ' + row.regionName"
                placeholder="Kosong"
                class="field min-w-[100px]"
                @input="changed(row)"
                @blur="checkDecrease(row)"
              />
            </td>
            <td>{{ row.tanggalPosisi }}</td>
            <td>
              <input
                v-model="row.kendala"
                :disabled="!editable || busy"
                :aria-label="'Kendala ' + row.regionName"
                placeholder="Catatan kendala"
                class="field"
                @input="changed(row)"
              /><input
                v-model="row.reason"
                :disabled="!editable || busy"
                :aria-label="'Alasan koreksi ' + row.regionName"
                placeholder="Alasan wajib jika mengubah data"
                class="field"
                @input="changed(row)"
              />
            </td>
            <td>
              <span
                class="badge"
                :class="{
                  good: row.status === 'saved',
                  warning: ['invalid', 'conflict', 'pending'].includes(
                    row.status,
                  ),
                }"
                >{{
                  row.status === "idle"
                    ? row.changed
                      ? "Belum disimpan"
                      : "Belum berubah"
                    : row.status
                }}</span
              >
              <p
                v-if="row.errorMessage"
                class="text-xs text-red-700 mt-1 max-w-[160px]"
              >
                {{ row.errorMessage }}
              </p>
            </td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="7" class="!py-10 text-center text-text-muted">
              Pilih kegiatan dan periode yang memiliki peserta.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <ModalDecreaseJustification
      :isOpen="justify"
      :regionName="selected?.regionName"
      :oldVal="
        Math.max(
          Number(selected?.baseline || 0),
          Number(selected?.realisasiLalu || 0),
        )
      "
      :newVal="selected?.realisasiKini"
      :unit="detail?.activity.unit"
      @close="justify = false"
      @confirm="justification"
    />
  </div>
</template>
