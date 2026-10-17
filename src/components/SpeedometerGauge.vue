<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const props = withDefaults(
  defineProps<{
    rate?: number
    target?: number
  }>(),
  {
    rate: 78.4,
    target: 85,
  }
)

// Generate 36 radial tick marks across a 180-degree semi-circle (from 180° left to 0° right)
const totalTicks = 36
const activeTicksCount = computed(() => Math.round((props.rate / 100) * totalTicks))

const ticks = computed(() => {
  const result = []
  const cx = 100
  const cy = 96
  const rInner = 64
  const rOuter = 78

  for (let i = 0; i <= totalTicks; i++) {
    // angle from PI (180 deg, left) down to 0 (0 deg, right)
    const angle = Math.PI - (i / totalTicks) * Math.PI
    const x1 = cx + rInner * Math.cos(angle)
    const y1 = cy - rInner * Math.sin(angle)
    const x2 = cx + rOuter * Math.cos(angle)
    const y2 = cy - rOuter * Math.sin(angle)

    const isActive = i <= activeTicksCount.value
    // Color gradient effect: the last 5 active ticks get Warm Gold accent, others get Deep Navy
    let color = '#EAEFF5'
    if (isActive) {
      if (i > activeTicksCount.value - 6) {
        color = '#FFC570' // Warm Gold Accent
      } else {
        color = '#1A3263' // Deep Navy Brand
      }
    }

    result.push({
      x1,
      y1,
      x2,
      y2,
      color,
      isActive,
    })
  }
  return result
})
</script>

<template>
  <div class="shoppers-card p-5 flex flex-col justify-between items-center text-center">
    <!-- Header -->
    <div class="w-full flex items-center justify-between mb-1">
      <span class="font-manrope text-[13px] font-semibold text-text-muted">Ketercapaian Target</span>
      <button class="text-text-muted hover:text-text-title">
        <span class="material-symbols-outlined text-[18px]">more_horiz</span>
      </button>
    </div>

    <!-- Radial Speedometer Gauge (Shopeers Segmented Ticks) -->
    <div class="relative w-[210px] h-[115px] flex items-end justify-center my-1">
      <svg class="w-full h-full" viewBox="0 0 200 105">
        <!-- Render Segmented Radial Ticks -->
        <g>
          <line
            v-for="(tick, idx) in ticks"
            :key="idx"
            :x1="tick.x1"
            :y1="tick.y1"
            :x2="tick.x2"
            :y2="tick.y2"
            :stroke="tick.color"
            stroke-width="3"
            stroke-linecap="round"
            class="transition-all duration-300"
          />
        </g>
      </svg>

      <!-- Center Metric Label -->
      <div class="absolute bottom-1 flex flex-col items-center">
        <span class="font-outfit text-[28px] font-bold text-text-title tracking-tight leading-none">
          {{ rate }}%
        </span>
        <span class="font-manrope text-[11px] font-semibold text-text-muted mt-1">
          312 dari 398 Unit
        </span>
      </div>
    </div>

    <!-- Subtitle and Link -->
    <p class="font-manrope text-[12px] text-text-muted mt-1">
      On track menuju target tahunan {{ target }}%
    </p>
    <button
      @click="router.push('/matriks')"
      class="mt-2 font-manrope text-[12px] font-bold text-brand-navy hover:text-brand-slate inline-flex items-center gap-1 transition-colors"
    >
      Lihat Rincian Unit
      <span class="material-symbols-outlined text-[14px]">arrow_outward</span>
    </button>
  </div>
</template>
