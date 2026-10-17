<script setup lang="ts">
import { computed, ref } from "vue";
import { number } from "../services/api";
const props = withDefaults(
  defineProps<{
    trend?: {
      date: string;
      total: number;
      reported: number;
      attained: number;
    }[];
  }>(),
  { trend: () => [] },
);
const hovered = ref<number | null>(null);
const points = computed(() =>
  props.trend.map((p, i) => ({
    ...p,
    x:
      40 +
      (props.trend.length === 1 ? 260 : (i * 520) / (props.trend.length - 1)),
    y: 190 - (p.total ? (p.attained / p.total) * 150 : 0),
    rate: p.total ? (p.attained / p.total) * 100 : 0,
  })),
);
const line = computed(() => points.value.map((p) => p.x + "," + p.y).join(" "));
const selected = computed(() =>
  hovered.value === null ? points.value.at(-1) : points.value[hovered.value],
);
</script>
<template>
  <div class="shoppers-card p-6 flex flex-col">
    <div class="flex flex-wrap justify-between gap-2">
      <div>
        <p class="text-[13px] text-text-muted">Tren Ketercapaian Evaluasi</p>
        <p class="font-outfit text-3xl font-bold text-brand-navy mt-2">
          {{ selected ? number(selected.rate) + "%" : "—" }}
        </p>
      </div>
      <span class="badge self-start">Rasio evaluasi tercapai</span>
    </div>
    <p class="text-xs text-text-muted mt-2">
      {{
        selected
          ? selected.date +
            " · " +
            selected.attained +
            " / " +
            selected.total +
            " evaluasi"
          : "Data muncul setelah jadwal evaluasi tiba."
      }}
    </p>
    <svg
      v-if="points.length"
      viewBox="0 0 600 240"
      role="img"
      aria-label="Grafik persentase evaluasi tercapai menurut tanggal"
      class="w-full my-5 flex-1 min-h-[180px]"
    >
      <defs>
        <linearGradient id="monitoring-fill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#547792" stop-opacity="0.25" />
          <stop offset="100%" stop-color="#547792" stop-opacity="0" />
        </linearGradient>
      </defs>
      <g v-for="(v, i) in [100, 50, 0]" :key="v">
        <line
          x1="38"
          :y1="40 + i * 75"
          x2="575"
          :y2="40 + i * 75"
          stroke="#EAEFF5"
        />
        <text x="2" :y="44 + i * 75" font-size="10" fill="#547792">
          {{ v }}%
        </text>
      </g>
      <polygon
        :points="'40,200 ' + line + ' ' + (points.at(-1)?.x || 40) + ',200'"
        fill="url(#monitoring-fill)"
      />
      <polyline
        :points="line"
        fill="none"
        stroke="#1A3263"
        stroke-width="3"
        stroke-linejoin="round"
      />
      <g
        v-for="(p, i) in points"
        :key="p.date"
        @mouseenter="hovered = i"
        @mouseleave="hovered = null"
      >
        <circle
          :cx="p.x"
          :cy="p.y"
          r="7"
          fill="#FFC570"
          stroke="#1A3263"
          stroke-width="2"
        />
        <title>{{ p.date }}: {{ number(p.rate) }}%</title>
        <text
          v-if="i === 0 || i === points.length - 1"
          :x="p.x"
          y="225"
          font-size="10"
          text-anchor="middle"
          fill="#547792"
        >
          {{ p.date.slice(5) }}
        </text>
      </g>
    </svg>
    <div
      v-else
      class="grid place-items-center flex-1 py-16 text-sm text-text-muted"
    >
      Belum ada evaluasi
    </div>
    <p class="text-xs text-text-muted border-t pt-3">
      Jumlah pekerjaan dengan satuan berbeda tidak digabung menjadi satu volume.
    </p>
  </div>
</template>
