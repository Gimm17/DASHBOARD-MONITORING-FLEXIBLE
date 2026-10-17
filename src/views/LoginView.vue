<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { api, session, errorText } from "../services/api";
const email = ref(""),
  password = ref(""),
  error = ref(""),
  busy = ref(false);
const router = useRouter();
async function submit() {
  error.value = "";
  busy.value = true;
  try {
    session.user = (
      await api("login", {
        method: "POST",
        body: { email: email.value, password: password.value },
      })
    ).user;
    password.value = "";
    await router.replace("/");
  } catch (e) {
    error.value = errorText(e);
  } finally {
    busy.value = false;
  }
}
</script>
<template>
  <main class="min-h-screen grid place-items-center bg-[#F6F8FB] p-5">
    <form
      class="shoppers-card p-8 w-full max-w-md space-y-5"
      @submit.prevent="submit"
    >
      <span class="material-symbols-outlined text-brand-navy text-4xl"
        >fact_check</span
      >
      <h1 class="font-outfit font-bold text-2xl text-brand-navy">
        Dashboard Monitoring Flexible
      </h1>
      <p class="text-sm text-text-muted">
        Masuk dengan akun yang diberikan administrator.
      </p>
      <p v-if="error" role="alert" class="notice error">{{ error }}</p>
      <label class="field-label"
        >Email<input
          v-model="email"
          type="email"
          autocomplete="username"
          required
          class="field"
      /></label>
      <label class="field-label"
        >Kata sandi<input
          v-model="password"
          type="password"
          autocomplete="current-password"
          required
          class="field"
      /></label>
      <button :disabled="busy" class="btn-primary w-full">
        {{ busy ? "Memeriksa akun…" : "Masuk" }}
      </button>
      <p class="text-xs text-text-muted">
        Lupa kata sandi? Hubungi administrator provinsi untuk pemulihan akses.
      </p>
    </form>
  </main>
</template>
