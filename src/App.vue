<script setup lang="ts">
import { useRoute, useRouter } from "vue-router";
import Sidebar from "./components/Sidebar.vue";
import TopNavbar from "./components/TopNavbar.vue";
import { session } from "./services/api";
const route = useRoute(),
  router = useRouter();
</script>
<template>
  <router-view v-if="route.path === '/login' || !session.user" />
  <div
    v-else
    class="min-h-screen bg-[#F6F8FB] font-manrope text-text-body flex"
  >
    <a
      href="#main-content"
      class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 btn-primary"
      >Langsung ke konten utama</a
    >
    <Sidebar @openSop="router.push('/bantuan')" />
    <div
      class="app-content ml-[260px] flex-1 flex flex-col min-h-screen min-w-0"
    >
      <TopNavbar
        @openNotifications="router.push('/?status=BELUM_ADA_DATA')"
        @search="(q) => router.replace({ path: '/', query: { q } })"
      />
      <nav
        class="md:hidden flex gap-2 p-3 overflow-x-auto bg-white no-print"
        aria-label="Navigasi seluler"
      >
        <router-link
          v-for="item in [
            ['/', 'Dashboard'],
            ['/matriks', 'Kegiatan'],
            ['/realisasi', 'Realisasi'],
            ['/audit', 'Audit'],
            ['/wilayah', 'Wilayah'],
            ['/pengaturan', 'Pengaturan'],
          ]"
          :key="item[0]"
          :to="item[0]"
          class="btn-secondary whitespace-nowrap"
          >{{ item[1] }}</router-link
        >
      </nav>
      <main
        id="main-content"
        tabindex="-1"
        class="flex-1 p-4 md:p-8 max-w-[1600px] w-full mx-auto"
      >
        <router-view :key="route.fullPath" />
      </main>
    </div>
  </div>
</template>
