<script setup lang="ts">
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";

const route = useRoute();
const router = useRouter();

const navItems = [
  { name: "Dashboard", path: "/", icon: "dashboard", badge: null },
  { name: "Kegiatan", path: "/matriks", icon: "assignment", badge: null },
  { name: "Realisasi", path: "/realisasi", icon: "trending_up", badge: null },
  { name: "Evaluasi", path: "/audit", icon: "fact_check", badge: null },
  { name: "Wilayah", path: "/wilayah", icon: "map", badge: null },
];

const currentPath = computed(() => route.path);

const emit = defineEmits(["openSop"]);
</script>

<template>
  <aside
    class="app-sidebar h-screen w-[260px] fixed left-0 top-0 flex flex-col justify-between py-6 px-4 bg-white border-r border-[#EAEFF5] z-40 shrink-0"
  >
    <!-- Top Brand & Navigation Section -->
    <div class="flex flex-col gap-6">
      <!-- Brand Logo / Header -->
      <div
        class="flex items-center gap-3 px-2 pt-1 cursor-pointer"
        @click="router.push('/')"
      >
        <div
          class="w-10 h-10 rounded-xl bg-brand-navy flex items-center justify-center text-white shadow-md shadow-brand-navy/20"
        >
          <span class="material-symbols-outlined text-[24px]">fact_check</span>
        </div>
        <div class="flex flex-col">
          <span
            class="font-outfit text-[18px] font-bold text-brand-navy tracking-tight leading-none"
            >BPS DMF</span
          >
          <span
            class="font-manrope text-[11px] font-medium text-text-muted mt-1"
            >Monitoring Flexible</span
          >
        </div>
      </div>

      <!-- Main Navigation Menu -->
      <nav class="flex flex-col gap-1.5">
        <button
          v-for="item in navItems"
          :key="item.name"
          @click="router.push(item.path)"
          class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 text-left w-full"
          :class="[
            currentPath === item.path
              ? 'bg-brand-navy text-white font-semibold shadow-sm shadow-brand-navy/25'
              : 'text-text-muted hover:text-text-title hover:bg-[#F2F4F7] font-medium',
          ]"
        >
          <div class="flex items-center gap-3">
            <span
              class="material-symbols-outlined text-[20px]"
              :style="
                currentPath === item.path
                  ? { fontVariationSettings: `'FILL' 1` }
                  : {}
              "
            >
              {{ item.icon }}
            </span>
            <span class="font-manrope text-[13px] tracking-wide">{{
              item.name
            }}</span>
          </div>

          <!-- Active dot indicator -->
          <span
            v-if="currentPath === item.path"
            class="w-1.5 h-1.5 rounded-full bg-brand-amber-deep"
          ></span>

          <!-- Counter badge -->
          <span
            v-else-if="item.badge"
            class="px-2 py-0.5 rounded-full bg-brand-cream text-brand-navy font-outfit text-[11px] font-bold"
          >
            {{ item.badge }}
          </span>
        </button>

        <div class="h-px bg-border-card my-2 mx-1"></div>

        <!-- Footer Menu Items -->
        <router-link
          to="/pengaturan"
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-text-muted hover:text-text-title hover:bg-[#F2F4F7] font-medium transition-all duration-150 text-left w-full"
        >
          <span class="material-symbols-outlined text-[20px]">settings</span>
          <span class="font-manrope text-[13px]">Pengaturan</span>
        </router-link>

        <button
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-text-muted hover:text-text-title hover:bg-[#F2F4F7] font-medium transition-all duration-150 text-left w-full"
          @click="emit('openSop')"
        >
          <span class="material-symbols-outlined text-[20px]"
            >help_outline</span
          >
          <span class="font-manrope text-[13px]">Bantuan &amp; SOP</span>
        </button>
      </nav>
    </div>

    <!-- Bottom Promo-Style Card (Shopeers Inspired) -->
    <div
      class="relative overflow-hidden rounded-2xl bg-brand-navy p-4 text-white shadow-lg shadow-brand-navy/20"
    >
      <div
        class="absolute -right-4 -bottom-4 w-24 h-24 rounded-full bg-white/5 pointer-events-none"
      ></div>
      <div class="flex items-center gap-2 mb-2">
        <span class="material-symbols-outlined text-brand-amber text-[20px]"
          >menu_book</span
        >
        <span class="font-outfit text-[14px] font-bold text-white"
          >SOP &amp; Panduan 2026</span
        >
      </div>
      <p
        class="font-manrope text-[12px] text-brand-slate-light mb-3 leading-relaxed"
      >
        Pedoman metodologi &amp; verifikasi capaian kegiatan statistik
        se-provinsi.
      </p>
      <button
        @click="emit('openSop')"
        class="w-full py-2 px-3 rounded-xl bg-brand-amber text-brand-navy font-manrope text-[12px] font-bold hover:bg-brand-amber-deep transition-all duration-150 flex items-center justify-center gap-1.5 shadow-sm active:scale-95"
      >
        <span class="material-symbols-outlined text-[16px]">download</span>
        Buka Panduan
      </button>
    </div>
  </aside>
</template>
