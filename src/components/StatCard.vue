<script setup lang="ts">
defineProps<{
  title: string
  value: string | number
  subValue?: string
  trend: string
  isPositive?: boolean
  isWarning?: boolean
  isNeutral?: boolean
  icon: string
  iconBgColor?: string
  iconColor?: string
}>()
</script>

<template>
  <div class="shoppers-card p-5 flex flex-col justify-between">
    <!-- Card Top Row: Title & Pastel Icon -->
    <div class="flex items-center justify-between">
      <span class="font-manrope text-[13px] font-semibold text-text-muted">{{ title }}</span>
      <div
        class="w-9 h-9 rounded-xl flex items-center justify-center"
        :class="iconBgColor || 'bg-brand-navy-subtle'"
      >
        <span class="material-symbols-outlined text-[20px]" :class="iconColor || 'text-brand-navy'">
          {{ icon }}
        </span>
      </div>
    </div>

    <!-- Metric Value -->
    <div class="my-3 flex items-baseline gap-2">
      <span class="font-outfit text-[28px] font-bold text-text-title tracking-tight leading-none">
        {{ value }}
      </span>
      <span v-if="subValue" class="font-manrope text-[12px] font-medium text-text-muted">
        {{ subValue }}
      </span>
    </div>

    <!-- Trend Badge Row (Shopeers style) -->
    <div class="flex items-center gap-2">
      <span
        v-if="isPositive"
        class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[11px] font-bold font-manrope bg-[#EAF8EE] text-[#1E824C]"
      >
        <span class="material-symbols-outlined text-[14px]">arrow_upward</span>
        {{ trend }}
      </span>
      <span
        v-else-if="isWarning"
        class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[11px] font-bold font-manrope bg-brand-amber/20 text-brand-amber-deep"
      >
        <span class="material-symbols-outlined text-[14px]">warning</span>
        {{ trend }}
      </span>
      <span
        v-else
        class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[11px] font-bold font-manrope bg-[#FEEEEE] text-[#C53030]"
      >
        <span class="material-symbols-outlined text-[14px]">schedule</span>
        {{ trend }}
      </span>
      <span class="font-manrope text-[11px] text-text-muted">vs periode lalu</span>
    </div>
  </div>
</template>
