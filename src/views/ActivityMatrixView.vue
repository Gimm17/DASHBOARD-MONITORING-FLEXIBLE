<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  api,
  errorText,
  number,
  dateTime,
  type Detail,
  type Activity,
  type Period,
} from "../services/api";
const route = useRoute(),
  router = useRouter();
const detail = ref<Detail | null>(null),
  activities = ref<Activity[]>([]),
  activityId = ref(String(route.query.activity || "")),
  error = ref(""),
  busy = ref(false);
const canCreate = ref(false);
const history = ref<Record<number, any[]>>({});
async function loadHistory(p: Period) {
  try {
    history.value[p.id] = (
      await api("periods/" + p.id + "/revisions")
    ).revisions;
  } catch (e) {
    error.value = errorText(e);
  }
}
async function load() {
  error.value = "";
  busy.value = true;
  try {
    const d = await api("dashboard");
    activities.value = d.activities;
    canCreate.value = (await api("references")).assignments.some((a: any) =>
      ["admin", "pjk"].includes(a.role),
    );
    if (!activityId.value)
      activityId.value = String(activities.value[0]?.id || "");
    if (activityId.value)
      detail.value = await api("activities/" + activityId.value);
  } catch (e) {
    error.value = errorText(e);
  } finally {
    busy.value = false;
  }
}
onMounted(load);
function choose() {
  router.replace({ path: "/matriks", query: { activity: activityId.value } });
}
async function finalize(p: Period) {
  if (
    !confirm(
      "Finalkan " +
        p.label +
        "? Nilai dan target disimpan sebagai snapshot resmi.",
    )
  )
    return;
  busy.value = true;
  try {
    await api("periods/" + p.id + "/finalize", {
      method: "POST",
      body: { version: p.version },
    });
    await load();
  } catch (e) {
    error.value = errorText(e);
  } finally {
    busy.value = false;
  }
}
async function reopen(p: Period) {
  const reason = prompt(
    "Alasan membuka kembali evaluasi final (minimal 5 karakter):",
  );
  if (!reason) return;
  try {
    await api("periods/" + p.id + "/reopen", {
      method: "POST",
      body: { reason },
    });
    await load();
  } catch (e) {
    error.value = errorText(e);
  }
}
const totals = computed(
  () =>
    detail.value?.periods.map((_, i) => {
      const cells = detail
        .value!.rows.map((r) => r.cells[i])
        .filter((c) => c.status !== "TIDAK_DINILAI");
      const reported = cells.filter((c) => c.value !== null);
      return {
        target: cells.reduce((a, c) => a + Number(c.target), 0),
        value: reported.reduce((a, c) => a + Number(c.value), 0),
        reported: reported.length,
        total: cells.length,
      };
    }) || [],
);
const today = new Intl.DateTimeFormat("en-CA", {
  timeZone: "Asia/Makassar",
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
}).format(new Date());
</script>
<template>
  <div class="space-y-5">
    <div class="flex flex-wrap justify-between gap-3 items-center">
      <div>
        <h1 class="font-outfit text-2xl font-bold text-brand-navy">
          Matriks Evaluasi Wilayah
        </h1>
        <p class="text-sm text-text-muted">
          Target dan realisasi per jadwal monitoring
        </p>
      </div>
      <router-link v-if="canCreate" to="/kegiatan/baru" class="btn-primary"
        >+ Kegiatan</router-link
      >
    </div>
    <select
      v-model="activityId"
      aria-label="Pilih kegiatan"
      class="field max-w-xl"
      @change="choose"
    >
      <option value="">Pilih kegiatan</option>
      <option v-for="a in activities" :key="a.id" :value="String(a.id)">
        {{ a.title }}
      </option>
    </select>
    <p v-if="error" role="alert" class="notice error">{{ error }}</p>
    <p v-if="busy" class="notice">Memuat…</p>
    <template v-if="detail">
      <div class="flex flex-wrap justify-between gap-3">
        <div>
          <span class="badge"
            >{{ detail.activity.code }} · {{ detail.activity.status }}</span
          >
          <h2 class="font-outfit font-bold text-xl mt-2">
            {{ detail.activity.title }}
          </h2>
          <p class="text-sm text-text-muted">
            {{ detail.activity.team }} · {{ detail.activity.pjk }} ·
            {{ detail.activity.indicator }} ({{ detail.activity.unit }})
          </p>
        </div>
        <router-link
          v-if="detail.can_manage"
          :to="'/kegiatan/' + detail.activity.id + '/edit'"
          class="btn-secondary self-start"
          >Pengaturan kegiatan</router-link
        >
      </div>
      <div class="shoppers-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="data-table min-w-[800px]">
            <thead>
              <tr>
                <th class="sticky left-0 z-10">Kabupaten / Kota</th>
                <th>Target akhir</th>
                <th v-for="p in detail.periods" :key="p.id">
                  <span>{{ p.label }}</span>
                  <p class="font-normal mt-1">{{ p.position_date }}</p>
                  <span class="badge">{{
                    p.state === "final"
                      ? "Final revisi " + p.revision
                      : p.revision
                        ? "Koreksi terbuka · resmi r" + p.revision
                        : "Sementara"
                  }}</span>
                </th>
                <th>Progres akhir</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in detail.rows" :key="row.id">
                <td class="sticky left-0 bg-white font-bold z-10">
                  {{ row.code }} {{ row.name }}
                  <p v-if="row.left_on" class="text-xs font-normal">
                    Keluar {{ row.left_on }}
                  </p>
                </td>
                <td>{{ number(row.final_target) }}</td>
                <td v-for="cell in row.cells" :key="cell.period_id">
                  <p class="font-mono font-bold">
                    {{ number(cell.value) }} / {{ number(cell.target) }}
                  </p>
                  <span
                    class="badge mt-1"
                    :class="{
                      good: cell.status === 'TERCAPAI',
                      warning: ['BELUM_ADA_DATA', 'DI_BAWAH_TARGET'].includes(
                        cell.status,
                      ),
                    }"
                    >{{ cell.status.replaceAll("_", " ") }}</span
                  >
                  <p v-if="cell.attainment !== null" class="text-xs mt-1">
                    {{ number(cell.attainment) }}% target periode
                  </p>
                  <p v-if="cell.isLate" class="text-xs text-red-700">
                    Laporan terlambat
                  </p>
                  <p
                    v-if="cell.notes"
                    class="text-xs text-text-muted mt-1 max-w-[240px]"
                  >
                    {{ cell.notes }}
                  </p>
                </td>
                <td>
                  <strong
                    >{{ number(row.progress)
                    }}{{ row.progress === null ? "" : "%" }}</strong
                  >
                  <div class="h-1.5 w-24 rounded bg-slate-100 mt-2">
                    <div
                      class="h-1.5 rounded bg-brand-navy"
                      :style="{ width: Math.min(row.progress || 0, 100) + '%' }"
                    ></div>
                  </div>
                </td>
              </tr>
              <tr v-if="!detail.rows.length">
                <td
                  :colspan="detail.periods.length + 3"
                  class="!py-10 text-center text-text-muted"
                >
                  Belum ada peserta.
                </td>
              </tr>
            </tbody>
            <tfoot v-if="detail.activity.value_type === 'count'">
              <tr class="bg-brand-cream/20 font-bold">
                <td class="p-4">Agregat peserta terlihat</td>
                <td></td>
                <td v-for="(t, i) in totals" :key="i" class="p-4">
                  {{ number(t.value) }} / {{ number(t.target) }}
                  <p class="text-xs font-normal">
                    {{ t.reported }}/{{ t.total }} laporan ·
                    {{
                      t.total && t.target
                        ? number((t.value / t.target) * 100) + "%"
                        : "—"
                    }}
                  </p>
                </td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="grid md:grid-cols-2 gap-4">
        <div v-for="p in detail.periods" :key="p.id" class="shoppers-card p-5">
          <h3 class="font-bold text-brand-navy">{{ p.label }}</h3>
          <p class="text-xs text-text-muted mt-1">
            Posisi {{ p.position_date }} · Batas {{ dateTime(p.due_at) }}
          </p>
          <div class="flex flex-wrap gap-2 mt-4">
            <router-link
              v-if="
                detail.can_write &&
                p.state !== 'final' &&
                p.position_date <= today &&
                (detail.activity.status === 'AKTIF' || p.current_revision_id)
              "
              :to="{
                path: '/realisasi',
                query: { activity: detail.activity.id, period: p.id },
              }"
              class="btn-primary"
              >Input realisasi</router-link
            ><button
              v-if="detail.can_manage && p.state !== 'final'"
              :disabled="
                busy ||
                p.position_date > today ||
                (detail.activity.status !== 'AKTIF' && !p.current_revision_id)
              "
              class="btn-secondary"
              @click="finalize(p)"
            >
              Finalisasi</button
            ><button
              v-if="detail.can_reopen && p.state === 'final'"
              :disabled="busy"
              class="btn-secondary"
              @click="reopen(p)"
            >
              Buka koreksi
            </button>
          </div>
          <details
            v-if="p.revision"
            class="mt-4 text-xs"
            @toggle="
              ($event.target as HTMLDetailsElement).open && loadHistory(p)
            "
          >
            <summary class="cursor-pointer font-semibold">
              Riwayat revisi resmi
            </summary>
            <div
              v-for="revision in history[p.id]"
              :key="revision.id"
              class="border-t mt-3 pt-3"
            >
              <strong>Revisi {{ revision.revision }}</strong> ·
              {{ dateTime(revision.created_at) }} · {{ revision.actor }}
              <p class="text-text-muted">
                Versi target {{ revision.snapshot.target_version }} · aturan
                {{ revision.snapshot.rule_version }}
              </p>
              <table class="data-table mt-2">
                <thead>
                  <tr>
                    <th>Wilayah</th>
                    <th>Realisasi / target</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(cell, id) in revision.snapshot.cells" :key="id">
                    <td>
                      {{
                        detail.rows.find((r) => String(r.id) === String(id))
                          ?.name || id
                      }}
                    </td>
                    <td>
                      {{ number(cell.value) }} / {{ number(cell.target) }}
                    </td>
                    <td>{{ cell.status.replaceAll("_", " ") }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </details>
        </div>
      </div>
    </template>
    <p v-else-if="!busy && !error" class="notice">
      Belum ada kegiatan yang dapat dilihat.
    </p>
  </div>
</template>
