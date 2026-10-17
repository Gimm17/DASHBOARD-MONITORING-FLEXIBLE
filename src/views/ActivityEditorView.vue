<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { api, session, errorText, type Detail } from "../services/api";
const route = useRoute(),
  router = useRouter(),
  id = Number(route.params.id || 0);
const today = new Date().toLocaleDateString("sv-SE", {
  timeZone: "Asia/Makassar",
});
const form = ref<any>({
  code: "",
  title: "",
  team_id: "",
  owner_id: session.user?.id,
  year: Number(today.slice(0, 4)),
  start_date: today,
  end_date: today,
  indicator: "",
  unit: "",
  value_type: "count",
  precision: 0,
  periods: [],
  region_targets: [],
  reason: "",
});
const refs = ref<any>({ teams: [], users: [], regions: [], units: [] }),
  detail = ref<Detail | null>(null),
  error = ref(""),
  busy = ref(false),
  frequency = ref("weekly"),
  day = ref(1);
function localDue(utc: string) {
  return new Date(new Date(utc.replace(" ", "T") + "Z").getTime() + 8 * 3600000)
    .toISOString()
    .slice(0, 16);
}
onMounted(async () => {
  try {
    refs.value = await api("references");
    if (id) {
      detail.value = await api("activities/" + id);
      const d = detail.value!;
      form.value = {
        ...d.activity,
        reason: "",
        periods: d.periods.map((p) => ({
          label: p.label,
          position_date: p.position_date,
          due_at: localDue(p.due_at),
        })),
        region_targets: d.rows
          .filter((r) => !r.left_on)
          .map((r) => ({
            region_id: r.id,
            final_target: r.final_target,
            targets: r.cells.map((c) => (c.provisional || c).target),
          })),
      };
    }
  } catch (e) {
    error.value = errorText(e);
  }
});
function selected(regionId: number) {
  return form.value.region_targets.some((r: any) => r.region_id === regionId);
}
function toggle(regionId: number) {
  if (selected(regionId))
    form.value.region_targets = form.value.region_targets.filter(
      (r: any) => r.region_id !== regionId,
    );
  else
    form.value.region_targets.push({
      region_id: regionId,
      final_target: "",
      targets: form.value.periods.map(() => ""),
    });
}
function addPeriod(date = "") {
  form.value.periods.push({
    label: "Periode " + (form.value.periods.length + 1),
    position_date: date,
    due_at: date ? date + "T17:00" : "",
  });
  form.value.region_targets.forEach((r: any) => r.targets.push(""));
}
function removePeriod(index: number) {
  form.value.periods.splice(index, 1);
  form.value.region_targets.forEach((r: any) => r.targets.splice(index, 1));
}
async function generate() {
  if (
    form.value.periods.length &&
    !confirm("Ganti jadwal dan kosongkan target periode yang lama?")
  )
    return;
  try {
    const d = await api("schedule", {
      method: "POST",
      body: {
        start: form.value.start_date,
        end: form.value.end_date,
        frequency: frequency.value,
        day: day.value,
      },
    });
    form.value.periods = [];
    form.value.region_targets.forEach((r: any) => (r.targets = []));
    d.dates.forEach((d: string) => addPeriod(d));
  } catch (e) {
    error.value = errorText(e);
  }
}
async function save(activate = false) {
  error.value = "";
  busy.value = true;
  try {
    const body = {
      ...form.value,
      team_id: Number(form.value.team_id),
      owner_id: Number(form.value.owner_id),
      year: Number(form.value.year),
      precision: Number(form.value.precision),
      periods: form.value.periods.map((p: any) => ({
        ...p,
        due_at: p.due_at + ":00+08:00",
      })),
    };
    const d: Detail = await api("activities" + (id ? "/" + id : ""), {
      method: id ? "PUT" : "POST",
      body,
    });
    if (activate)
      await api("activities/" + d.activity.id + "/status", {
        method: "POST",
        body: { status: "AKTIF", version: d.activity.version },
      });
    await router.push({ path: "/matriks", query: { activity: d.activity.id } });
  } catch (e) {
    error.value = errorText(e);
  } finally {
    busy.value = false;
  }
}
async function transition(status: string) {
  const reason = prompt("Catatan perubahan status (minimal 5 karakter):");
  if (!reason || !detail.value) return;
  try {
    await api("activities/" + id + "/status", {
      method: "POST",
      body: { status, version: detail.value.activity.version, reason },
    });
    await router.push({ path: "/matriks", query: { activity: id } });
  } catch (e) {
    error.value = errorText(e);
  }
}
</script>
<template>
  <form class="space-y-5" @submit.prevent="save(false)">
    <div class="flex justify-between flex-wrap gap-3">
      <div>
        <h1 class="font-outfit text-2xl font-bold text-brand-navy">
          {{ id ? "Pengaturan Kegiatan" : "Buat Monitoring Baru" }}
        </h1>
        <p class="text-sm text-text-muted">
          Atur indikator, peserta, jadwal, dan target kumulatif.
        </p>
      </div>
      <div class="flex gap-2">
        <button type="button" class="btn-secondary" @click="router.back()">
          Kembali</button
        ><button :disabled="busy" class="btn-primary">
          Simpan
          {{
            !id || detail?.activity.status === "DRAF" ? "draf" : "perubahan"
          }}</button
        ><button
          v-if="!id || detail?.activity.status === 'DRAF'"
          type="button"
          :disabled="busy"
          class="btn-secondary"
          @click="save(true)"
        >
          Simpan dan aktifkan
        </button>
      </div>
    </div>
    <p v-if="error" role="alert" class="notice error">{{ error }}</p>
    <section class="shoppers-card p-5">
      <h2 class="font-bold text-brand-navy mb-4">Identitas dan indikator</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <label class="field-label"
          >Kode kegiatan<input
            v-model="form.code"
            required
            maxlength="60"
            class="field" /></label
        ><label class="field-label sm:col-span-2"
          >Nama kegiatan<input
            v-model="form.title"
            required
            maxlength="220"
            class="field"
        /></label>
        <label class="field-label"
          >Tim<select v-model="form.team_id" required class="field">
            <option value="">Pilih tim</option>
            <option
              v-for="t in refs.teams.filter((t: any) => t.is_active)"
              :key="t.id"
              :value="t.id"
            >
              {{ t.name }}
            </option>
          </select></label
        >
        <label class="field-label"
          >Penanggung jawab<select
            v-model="form.owner_id"
            required
            class="field"
          >
            <option v-for="u in refs.users" :key="u.id" :value="u.id">
              {{ u.name }}
            </option>
          </select></label
        ><label class="field-label"
          >Tahun<input
            v-model.number="form.year"
            type="number"
            min="2020"
            max="2100"
            required
            class="field"
        /></label>
        <label class="field-label"
          >Tanggal mulai<input
            v-model="form.start_date"
            type="date"
            required
            class="field" /></label
        ><label class="field-label"
          >Tanggal akhir<input
            v-model="form.end_date"
            type="date"
            required
            class="field" /></label
        ><label class="field-label"
          >Nama indikator<input v-model="form.indicator" required class="field"
        /></label>
        <label class="field-label"
          >Satuan<input
            v-model="form.unit"
            list="units"
            required
            maxlength="80"
            class="field" /><datalist id="units">
            <option
              v-for="u in refs.units"
              :key="u.id"
              :value="u.name"
            /></datalist></label
        ><label class="field-label"
          >Jenis nilai<select v-model="form.value_type" class="field">
            <option value="count">Jumlah</option>
            <option value="percentage">Persentase 0–100</option>
          </select></label
        ><label class="field-label"
          >Angka di belakang koma<input
            v-model.number="form.precision"
            type="number"
            min="0"
            max="4"
            required
            class="field"
        /></label>
      </div>
    </section>
    <section class="shoppers-card p-5">
      <h2 class="font-bold text-brand-navy mb-4">Jadwal evaluasi</h2>
      <div class="flex flex-wrap gap-2 items-end mb-4">
        <label class="field-label"
          >Frekuensi<select v-model="frequency" class="field">
            <option value="daily">Harian</option>
            <option value="weekly">Mingguan</option>
            <option value="monthly">Bulanan</option>
            <option value="quarterly">Triwulanan</option>
          </select></label
        ><label
          v-if="['weekly', 'monthly'].includes(frequency)"
          class="field-label"
          >{{
            frequency === "weekly"
              ? "Hari ISO 1 Senin sampai 7 Minggu"
              : "Tanggal 1 sampai 31"
          }}<input
            v-model.number="day"
            type="number"
            min="1"
            :max="frequency === 'weekly' ? 7 : 31"
            class="field !w-36" /></label
        ><button type="button" class="btn-secondary" @click="generate">
          Bangkitkan jadwal</button
        ><button type="button" class="btn-secondary" @click="addPeriod()">
          + Tanggal khusus
        </button>
      </div>
      <div
        v-for="(p, index) in form.periods"
        :key="index"
        class="grid sm:grid-cols-[1fr_1fr_1fr_auto] gap-3 border-t py-3"
      >
        <label class="field-label"
          >Label<input v-model="p.label" required class="field" /></label
        ><label class="field-label"
          >Tanggal posisi<input
            v-model="p.position_date"
            type="date"
            required
            class="field" /></label
        ><label class="field-label"
          >Batas laporan WITA<input
            v-model="p.due_at"
            type="datetime-local"
            required
            class="field" /></label
        ><button
          type="button"
          class="btn-secondary self-end"
          @click="removePeriod(Number(index))"
        >
          Hapus
        </button>
      </div>
      <p class="text-xs text-text-muted mt-3">
        Urutkan tanggal dari awal ke akhir. Jadwal yang sudah memiliki realisasi
        tidak dapat diganti.
      </p>
    </section>
    <section class="shoppers-card p-5">
      <h2 class="font-bold text-brand-navy mb-4">Wilayah peserta</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <label
          v-for="r in refs.regions.filter((r: any) => r.is_active)"
          :key="r.id"
          class="text-sm flex gap-2"
          ><input
            type="checkbox"
            :checked="selected(r.id)"
            @change="toggle(r.id)"
          />{{ r.code }} {{ r.name }}</label
        >
      </div>
    </section>
    <section class="shoppers-card overflow-x-auto">
      <h2 class="font-bold text-brand-navy p-5">
        Target per wilayah dan periode
      </h2>
      <table class="data-table">
        <thead>
          <tr>
            <th>Wilayah</th>
            <th>Target akhir</th>
            <th v-for="(p, i) in form.periods" :key="i">{{ p.label }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in form.region_targets" :key="r.region_id">
            <td>
              {{ refs.regions.find((v: any) => v.id === r.region_id)?.name }}
            </td>
            <td>
              <input
                v-model="r.final_target"
                type="number"
                min="0.0001"
                step="any"
                required
                class="field min-w-[110px]"
                :aria-label="'Target akhir wilayah ' + r.region_id"
              />
            </td>
            <td v-for="(_, i) in form.periods" :key="i">
              <input
                v-model="r.targets[i]"
                type="number"
                min="0.0001"
                step="any"
                required
                class="field min-w-[100px]"
                :aria-label="
                  'Target periode ' +
                  (Number(i) + 1) +
                  ' wilayah ' +
                  r.region_id
                "
              />
            </td>
          </tr>
        </tbody>
      </table>
      <p class="text-xs text-text-muted p-5">
        Target harus positif, tidak menurun, dan target terakhir sama dengan
        target akhir. Target adalah kumulatif.
      </p>
    </section>
    <label v-if="id" class="field-label"
      >Alasan perubahan<input
        v-model="form.reason"
        required
        minlength="5"
        maxlength="2000"
        class="field"
    /></label>
    <div v-if="id" class="flex gap-3">
      <button
        v-if="detail?.activity.status === 'AKTIF'"
        type="button"
        class="btn-secondary"
        @click="transition('SELESAI')"
      >
        Tutup kegiatan</button
      ><button
        v-if="['SELESAI', 'BATAL'].includes(detail?.activity.status || '')"
        type="button"
        class="btn-secondary"
        @click="transition('ARSIP')"
      >
        Arsipkan</button
      ><button
        v-if="['DRAF', 'AKTIF'].includes(detail?.activity.status || '')"
        type="button"
        class="btn-secondary"
        @click="transition('BATAL')"
      >
        Batalkan kegiatan
      </button>
    </div>
  </form>
</template>
