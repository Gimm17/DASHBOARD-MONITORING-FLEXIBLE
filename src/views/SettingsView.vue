<script setup lang="ts">
import { ref, onMounted } from "vue";
import { api, session, errorText } from "../services/api";
const refs = ref<any>({ teams: [], regions: [], units: [] }),
  users = ref<any[]>([]),
  error = ref(""),
  message = ref(""),
  busy = ref(false),
  tab = ref("regions");
const master = ref<any>({ code: "", name: "", is_active: true }),
  user = ref<any>(null),
  activities = ref<any[]>([]);
async function load() {
  try {
    refs.value = await api("references");
    if (session.user?.is_admin) {
      users.value = (await api("users")).users;
      activities.value = (await api("dashboard")).activities;
    }
  } catch (e) {
    error.value = errorText(e);
  }
}
onMounted(load);
function newUser() {
  user.value = {
    name: "",
    email: "",
    password: "",
    is_active: true,
    assignments: [{ role: "operator", scope_type: "team", scope_id: 0 }],
  };
}
function editUser(u: any) {
  user.value = { ...structuredClone(u), password: "" };
}
function roleChanged(a: any) {
  a.scope_type = ["admin", "pimpinan"].includes(a.role)
    ? "province"
    : a.role === "wilayah"
      ? "region"
      : "team";
  a.scope_id = 0;
}
async function saveMaster() {
  busy.value = true;
  error.value = "";
  try {
    await api("masters/" + tab.value, { method: "POST", body: master.value });
    master.value = { code: "", name: "", is_active: true };
    message.value = "Master tersimpan.";
    await load();
  } catch (e) {
    error.value = errorText(e);
  } finally {
    busy.value = false;
  }
}
async function saveUser() {
  busy.value = true;
  error.value = "";
  try {
    await api("users", {
      method: "POST",
      body: { ...user.value, password: user.value.password || undefined },
    });
    user.value = null;
    message.value = "Akun dan penugasan tersimpan.";
    await load();
  } catch (e) {
    error.value = errorText(e);
  } finally {
    busy.value = false;
  }
}
function options(scope: string) {
  return scope === "team"
    ? refs.value.teams
    : scope === "region"
      ? refs.value.regions
      : activities.value;
}
</script>
<template>
  <div class="space-y-5">
    <h1 class="font-outfit text-2xl font-bold text-brand-navy">Pengaturan</h1>
    <p v-if="error" role="alert" class="notice error">{{ error }}</p>
    <p v-if="message" role="status" class="notice success">{{ message }}</p>
    <p class="notice">
      {{ session.user?.name }} · {{ session.user?.email }}. Hak akses mengikuti
      penugasan administrator.
    </p>
    <template v-if="session.user?.is_admin"
      ><div class="flex gap-2">
        <button
          v-for="(label, key) in {
            regions: 'Wilayah',
            teams: 'Tim',
            units: 'Satuan',
            users: 'Akun',
          }"
          :key="key"
          class="btn-secondary"
          :class="{ '!bg-brand-navy !text-white': tab === key }"
          @click="
            tab = key;
            master = { code: '', name: '', is_active: true };
          "
        >
          {{ label }}
        </button>
      </div>
      <template v-if="tab !== 'users'"
        ><form
          class="shoppers-card p-5 grid md:grid-cols-[1fr_2fr_auto_auto] gap-3 items-end"
          @submit.prevent="saveMaster"
        >
          <label class="field-label"
            >Kode<input
              v-model="master.code"
              required
              maxlength="40"
              class="field" /></label
          ><label class="field-label"
            >Nama<input
              v-model="master.name"
              required
              maxlength="160"
              class="field" /></label
          ><label class="text-xs py-3"
            ><input v-model="master.is_active" type="checkbox" /> Aktif</label
          ><button :disabled="busy" class="btn-primary">Simpan</button>
        </form>
        <div class="shoppers-card overflow-x-auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in refs[tab]" :key="row.id">
                <td>{{ row.code }}</td>
                <td>{{ row.name }}</td>
                <td>{{ row.is_active ? "Aktif" : "Nonaktif" }}</td>
                <td>
                  <button
                    class="btn-secondary"
                    @click="master = { ...row, is_active: !!row.is_active }"
                  >
                    Ubah
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div></template
      >
      <template v-else
        ><button class="btn-primary" @click="newUser">+ Akun</button>
        <form
          v-if="user"
          class="shoppers-card p-5 space-y-4"
          @submit.prevent="saveUser"
        >
          <div class="grid sm:grid-cols-3 gap-3">
            <label class="field-label"
              >Nama<input v-model="user.name" required class="field" /></label
            ><label class="field-label"
              >Email<input
                v-model="user.email"
                type="email"
                required
                class="field" /></label
            ><label class="field-label"
              >{{ user.id ? "Kata sandi baru jika direset" : "Kata sandi"
              }}<input
                v-model="user.password"
                type="password"
                autocomplete="new-password"
                :required="!user.id"
                minlength="12"
                class="field"
            /></label>
          </div>
          <p class="text-xs text-text-muted">
            Kata sandi minimal 12 karakter, huruf besar/kecil, angka, dan
            simbol. Sampaikan akses melalui saluran internal yang aman.
          </p>
          <label class="text-sm"
            ><input v-model="user.is_active" type="checkbox" /> Akun
            aktif</label
          >
          <div
            v-for="(a, i) in user.assignments"
            :key="i"
            class="flex flex-wrap gap-3"
          >
            <label class="field-label"
              >Peran<select
                v-model="a.role"
                class="field"
                @change="roleChanged(a)"
              >
                <option
                  v-for="role in [
                    'admin',
                    'pjk',
                    'operator',
                    'pimpinan',
                    'wilayah',
                  ]"
                  :key="role"
                >
                  {{ role }}
                </option>
              </select></label
            ><label
              v-if="['pjk', 'operator'].includes(a.role)"
              class="field-label"
              >Jenis cakupan<select
                v-model="a.scope_type"
                class="field"
                @change="a.scope_id = 0"
              >
                <option value="team">Tim</option>
                <option value="activity">Kegiatan</option>
              </select></label
            ><label v-if="a.scope_type !== 'province'" class="field-label"
              >Cakupan<select
                v-model.number="a.scope_id"
                required
                class="field"
              >
                <option :value="0">Pilih cakupan</option>
                <option
                  v-for="o in options(a.scope_type)"
                  :key="o.id"
                  :value="o.id"
                >
                  {{ o.name || o.title }}
                </option>
              </select></label
            ><span v-else class="badge self-end">Provinsi</span
            ><button
              type="button"
              class="btn-secondary self-end"
              @click="user.assignments.splice(Number(i), 1)"
            >
              Hapus
            </button>
          </div>
          <div class="flex gap-3">
            <button
              type="button"
              class="btn-secondary"
              @click="
                user.assignments.push({
                  role: 'operator',
                  scope_type: 'team',
                  scope_id: 0,
                })
              "
            >
              + Penugasan</button
            ><button :disabled="busy" class="btn-primary">Simpan akun</button
            ><button type="button" class="btn-secondary" @click="user = null">
              Batal
            </button>
          </div>
        </form>
        <div class="shoppers-card overflow-x-auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Status</th>
                <th>Penugasan</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in users" :key="u.id">
                <td>{{ u.name }}</td>
                <td>{{ u.email }}</td>
                <td>{{ u.is_active ? "Aktif" : "Nonaktif" }}</td>
                <td>
                  <span
                    v-for="a in u.assignments"
                    :key="a.id"
                    class="badge mr-1"
                    >{{ a.role }} · {{ a.scope_type }}
                    {{ a.scope_id || "" }}</span
                  >
                </td>
                <td>
                  <button class="btn-secondary" @click="editUser(u)">
                    Ubah
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div></template
      >
    </template>
  </div>
</template>
