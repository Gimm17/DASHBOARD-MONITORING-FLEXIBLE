<script setup lang="ts">
import { computed } from "vue";
const props = withDefaults(
  defineProps<{ days?: { date: string; label: string; count: number }[] }>(),
  { days: () => [] },
);
const max = computed(() => Math.max(1, ...props.days.map((d) => d.count)));
</script>
<template>
  <div class="shoppers-card p-5">
    <p class="text-[13px] text-text-muted">Aktivitas Laporan Mingguan</p>
    <h2 class="font-outfit font-bold mt-1">Laporan pertama dikirim</h2>
    <div class="flex items-end justify-between gap-2 h-[135px] mt-4">
      <div v-for="day in days" :key="day.date" class="flex-1 text-center">
        <span class="text-[10px] font-bold text-brand-navy">{{
          day.count
        }}</span>
        <div
          class="bg-[#F2F4F8] h-[80px] rounded-lg flex items-end overflow-hidden"
        >
          <div
            class="w-full rounded-lg bg-brand-navy"
            :style="{ height: (day.count / max) * 100 + '%' }"
          ></div>
        </div>
        <span class="text-[10px] text-text-muted">{{ day.label }}</span>
      </div>
    </div>
    <p class="text-xs text-text-muted mt-3">
      7 hari terakhir · WITA · sesuai cakupan akses
    </p>
  </div>
</template>
