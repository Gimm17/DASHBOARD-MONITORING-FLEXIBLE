<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import StatCard from "../components/StatCard.vue";
import HeroSplineChart from "../components/HeroSplineChart.vue";
import WeeklyActivityChart from "../components/WeeklyActivityChart.vue";
import SpeedometerGauge from "../components/SpeedometerGauge.vue";
import {
  api,
  session,
  errorText,
  number,
  type Activity,
} from "../services/api";
const router = useRouter(),
  route = useRoute();
const data = ref<any>(null),
  error = ref(""),
  busy = ref(false);
const year = ref(Number(route.query.year || new Date().getFullYear())),
  team = ref(String(route.query.team_id || "")),
  region = ref(String(route.query.region_id || "")),
  search = ref(String(route.query.q || ""));
const teams = ref<any[]>([]);
const regions = ref<any[]>([]);
const canCreate = computed(() =>
  session.user?.assignments.some((a) => ["admin", "pjk"].includes(a.role)),
);
const activities = computed<Activity[]>(() =>
  (data.value?.activities || []).filter((a: Activity) =>
    (a.title + " " + a.code + " " + a.pjk)
      .toLowerCase()
      .includes(search.value.toLowerCase()),
  ),
);
const summary = computed(() => data.value?.summary || {});
async function load() {
  busy.value = true;
  error.value = "";
  const params = new URLSearchParams({ year: String(year.value) });
  if (team.value) params.set("team_id", team.value);
  if (region.value) params.set("region_id", region.value);
  if (route.query.status) params.set("status", String(route.query.status));
  if (route.query.page) params.set("page", String(route.query.page));
  try {
    data.value = await api("dashboard?" + params);
    const references = await api("references");
    teams.value = references.teams;
    regions.value = references.regions;
  } catch (e) {
    error.value = errorText(e);
  } finally {
    busy.value = false;
  }
}
onMounted(load);
function changeFilters() {
  router.replace({
    path: route.path,
    query: {
      ...route.query,
      year: String(year.value),
      team_id: team.value || undefined,
      region_id: region.value || undefined,
      page: undefined,
    },
  });
}
function open(id: number) {
  router.push({ path: "/matriks", query: { activity: id } });
}
function exportData() {
  window.location.href =
    "/api/export?" +
    new URLSearchParams({
      year: String(year.value),
      ...(team.value ? { team_id: team.value } : {}),
      ...(region.value ? { region_id: region.value } : {}),
      ...(route.query.status ? { status: String(route.query.status) } : {}),
    });
}
</script>
<template>
  <div class="space-y-6">
    <div class="flex flex-wrap justify-between gap-4 items-center">
      <div>
        <h1 class="font-outfit text-[26px] font-bold text-text-title">
          {{
            route.path === "/wilayah"
              ? "Monitoring per Wilayah"
              : "Dashboard Monitoring Eksekutif"
          }}
        </h1>
        <p class="text-[13px] text-text-muted">
          Target, realisasi, dan kelengkapan pelaporan kabupaten/kota
        </p>
      </div>
      <div class="flex gap-2">
        <input
          v-model="year"
          type="number"
          min="2020"
          max="2100"
          aria-label="Tahun pelaporan"
          class="field !w-24 !mt-0"
          @change="changeFilters"
        /><button
          v-if="canCreate"
          class="btn-primary"
          @click="router.push('/kegiatan/baru')"
        >
          + Kegiatan</button
        ><button class="btn-secondary" @click="exportData">Ekspor CSV</button>
      </div>
    </div>
    <p v-if="error" role="alert" class="notice error">
      {{ error }} <button class="underline" @click="load">Coba lagi</button>
    </p>
    <p v-if="busy" role="status" class="notice">Memuat data monitoring…</p>
    <template v-if="data">
      <div v-if="summary.missing" class="notice flex justify-between gap-3">
        <span
          ><strong>Perhatian:</strong> {{ number(summary.missing) }} unit
          evaluasi belum memiliki laporan pada periode yang telah tiba.</span
        ><router-link
          :to="{
            path: route.path,
            query: {
              ...route.query,
              status: 'BELUM_ADA_DATA',
              page: undefined,
            },
          }"
          class="font-bold underline"
          >Lihat</router-link
        >
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <StatCard
          title="Total Kegiatan Aktif"
          :value="number(summary.active)"
          subValue="Kegiatan"
          icon="assignment"
        />
        <StatCard
          title="Tingkat Ketercapaian Target"
          :value="summary.rate === null ? '—' : number(summary.rate) + '%'"
          :subValue="summary.attained + ' / ' + summary.total + ' unit'"
          icon="check_circle"
        />
        <StatCard
          title="Evaluasi Di Bawah Target"
          :value="number(summary.below)"
          subValue="Unit evaluasi"
          :isWarning="true"
          icon="warning"
        />
        <StatCard
          title="Laporan Belum Tersedia"
          :value="number(summary.missing)"
          subValue="Kosong berbeda dari nol"
          icon="pending_actions"
        />
      </div>
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <HeroSplineChart class="xl:col-span-2" :trend="data.trend" />
        <div class="space-y-5">
          <WeeklyActivityChart :days="data.weekly" /><SpeedometerGauge
            :rate="summary.coverage"
            :reported="summary.total - summary.missing"
            :total="summary.total"
            :target="100"
          />
        </div>
      </div>
      <div class="shoppers-card p-5 flex flex-wrap justify-between gap-3">
        <div>
          <strong class="text-brand-navy">Ringkasan monitoring</strong>
          <p class="text-sm text-text-muted mt-1">
            {{ summary.attained }} dari {{ summary.total }} evaluasi wajib
            mencapai target. Kelengkapan laporan
            {{ number(summary.coverage) }}%.
            {{
              data.is_provisional
                ? "Masih mencakup hasil sementara."
                : "Hasil evaluasi telah final."
            }}
          </p>
        </div>
        <span class="text-xs text-text-muted">Status diperiksa dalam WITA</span>
      </div>
      <div class="shoppers-card overflow-hidden">
        <div class="p-5 flex flex-wrap justify-between gap-3 items-center">
          <h2 class="font-outfit font-bold text-lg text-brand-navy">
            Kinerja Kegiatan dan Wilayah
          </h2>
          <div class="flex flex-wrap gap-3">
            <input
              v-model="search"
              aria-label="Cari kegiatan"
              placeholder="Cari kegiatan…"
              class="field !mt-0"
            /><select
              v-model="team"
              aria-label="Filter tim"
              class="field !mt-0"
              @change="changeFilters"
            >
              <option value="">Semua tim</option>
              <option v-for="t in teams" :key="t.id" :value="t.id">
                {{ t.name }}
              </option>
            </select>
            <select
              v-model="region"
              aria-label="Filter wilayah"
              class="field !mt-0"
              @change="changeFilters"
            >
              <option value="">Semua wilayah</option>
              <option v-for="r in regions" :key="r.id" :value="String(r.id)">
                {{ r.name }}
              </option>
            </select>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Kegiatan</th>
                <th>Tim dan PJK</th>
                <th>Status</th>
                <th>Evaluasi tercapai</th>
                <th>Kelengkapan laporan</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in activities" :key="a.id">
                <td>
                  <button
                    class="font-bold text-brand-navy text-left"
                    @click="open(a.id)"
                  >
                    {{ a.title }}
                  </button>
                  <p class="text-xs text-text-muted">
                    {{ a.code }} · {{ a.indicator }} · {{ a.unit }}
                  </p>
                </td>
                <td>
                  {{ a.team }}
                  <p class="text-xs text-text-muted">{{ a.pjk }}</p>
                </td>
                <td>
                  <span class="badge">{{ a.status }}</span>
                </td>
                <td>
                  {{ number(a.capaianPeriode)
                  }}{{ a.capaianPeriode === null ? "" : "%" }}
                </td>
                <td>
                  {{ number(a.coverageRate)
                  }}{{ a.coverageRate === null ? "" : "%" }}
                </td>
                <td>
                  <button class="btn-secondary" @click="open(a.id)">
                    Rincian
                  </button>
                </td>
              </tr>
              <tr v-if="!activities.length">
                <td colspan="6" class="text-center !py-12 text-text-muted">
                  Belum ada kegiatan yang sesuai filter.
                  {{
                    canCreate
                      ? "Mulai dengan membuat kegiatan monitoring."
                      : "Hubungi penanggung jawab kegiatan."
                  }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div
        v-if="route.query.status || route.path === '/wilayah'"
        class="shoppers-card p-5"
      >
        <h2 class="font-bold text-brand-navy mb-3">
          Rincian evaluasi sesuai filter
        </h2>
        <div
          v-for="u in data.units"
          :key="u.activity_id + ':' + u.region_id + ':' + u.period_id"
          class="border-t py-2 text-sm"
        >
          {{ u.activity_title }} · {{ u.region }} · {{ u.period }}
          <span class="badge">{{ u.status.replaceAll("_", " ") }}</span>
        </div>
        <div class="flex gap-3 mt-4 items-center text-xs">
          <router-link
            v-if="data.unit_page > 1"
            :to="{
              path: route.path,
              query: { ...route.query, page: data.unit_page - 1 },
            }"
            class="btn-secondary"
            >Sebelumnya</router-link
          >
          <span
            >{{ data.unit_total }} unit · halaman {{ data.unit_page }}/{{
              data.unit_last_page
            }}</span
          >
          <router-link
            v-if="data.unit_page < data.unit_last_page"
            :to="{
              path: route.path,
              query: { ...route.query, page: data.unit_page + 1 },
            }"
            class="btn-secondary"
            >Berikutnya</router-link
          >
        </div>
        <router-link to="/" class="btn-secondary mt-3"
          >Hapus filter</router-link
        >
      </div>
    </template>
  </div>
</template>
