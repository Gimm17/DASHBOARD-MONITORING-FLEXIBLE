<script setup lang="ts">
import { ref, watch, nextTick } from "vue";

const props = defineProps<{
  isOpen: boolean;
  regionName?: string;
  oldVal?: number;
  newVal?: number | string;
  unit?: string;
}>();

const emit = defineEmits(["close", "confirm"]);

const justificationReason = ref("");
const docRef = ref("");
const dialog = ref<HTMLDialogElement | null>(null);
watch(
  () => props.isOpen,
  async (open) => {
    await nextTick();
    if (open) {
      justificationReason.value = "";
      docRef.value = "";
      dialog.value?.showModal();
    } else dialog.value?.close();
  },
);

const submit = () => {
  if (justificationReason.value.trim().length < 5) return;
  emit("confirm", {
    reason: justificationReason.value,
    docRef: docRef.value,
  });
};
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <dialog
        ref="dialog"
        aria-labelledby="correction-title"
        class="m-auto bg-transparent p-4 max-h-[95vh] overflow-y-auto backdrop:bg-brand-navy/40 backdrop:backdrop-blur-xs"
        @cancel.prevent="emit('close')"
      >
        <div
          class="bg-white rounded-2xl w-full max-w-[500px] shadow-2xl border border-[#EAEFF5] overflow-hidden"
        >
          <!-- Header with Warning Badge -->
          <div
            class="p-6 bg-[#FFF8EE] border-b border-brand-amber/30 flex items-start gap-3"
          >
            <div
              class="w-10 h-10 rounded-xl bg-brand-amber text-brand-navy flex items-center justify-center shrink-0 shadow-xs"
            >
              <span
                aria-hidden="true"
                class="material-symbols-outlined text-[24px]"
                >warning</span
              >
            </div>
            <div>
              <h3
                id="correction-title"
                class="font-outfit text-[17px] font-bold text-text-title leading-tight"
              >
                Justifikasi Penurunan Realisasi Kumulatif
              </h3>
              <p class="font-manrope text-[12px] text-text-muted mt-0.5">
                Penurunan angka membutuhkan alasan dan persetujuan PJK.
              </p>
            </div>
          </div>

          <!-- Body -->
          <div class="p-6 flex flex-col gap-4">
            <!-- Region & Value Diff Box -->
            <div
              class="p-3.5 rounded-xl bg-[#F6F8FB] border border-[#EAEFF5] flex items-center justify-between"
            >
              <div>
                <span class="block text-[11px] font-semibold text-text-muted"
                  >Wilayah Partisipan</span
                >
                <span
                  class="font-outfit text-[14px] font-bold text-brand-navy"
                  >{{ regionName || "Wilayah" }}</span
                >
              </div>
              <div
                class="flex items-center gap-2 font-mono text-[13px] font-bold"
              >
                <span
                  class="px-2 py-0.5 rounded bg-white border border-[#EAEFF5] text-text-muted"
                >
                  {{ oldVal ?? 0 }} {{ unit }}
                </span>
                <span class="text-brand-slate">→</span>
                <span
                  class="px-2 py-0.5 rounded bg-red-100 text-red-700 border border-red-200"
                >
                  {{ newVal ?? "—" }} {{ unit }}
                </span>
              </div>
            </div>

            <!-- Reason Field -->
            <div>
              <label
                for="correction-reason"
                class="block font-manrope text-[12px] font-bold text-text-title mb-1.5"
              >
                Alasan Penurunan / Koreksi Data (Wajib)
              </label>
              <textarea
                id="correction-reason"
                autofocus
                maxlength="2000"
                v-model="justificationReason"
                rows="3"
                class="w-full bg-[#F6F8FB] border border-[#EAEFF5] rounded-xl p-3 text-[12px] font-manrope text-text-title focus:bg-white focus:border-brand-navy focus:outline-none transition-all"
                placeholder="Tuliskan alasan koreksi lapangan..."
              ></textarea>
            </div>

            <!-- Official Document Number Field -->
            <div>
              <label
                for="correction-document"
                class="block font-manrope text-[12px] font-bold text-text-title mb-1.5"
              >
                Nomor Surat / Referensi Berita Acara
              </label>
              <input
                id="correction-document"
                maxlength="160"
                v-model="docRef"
                type="text"
                class="w-full bg-[#F6F8FB] border border-[#EAEFF5] rounded-xl px-3 py-2 text-[12px] font-manrope text-text-title focus:bg-white focus:border-brand-navy focus:outline-none transition-all"
                placeholder="Contoh: B-1402/BPS/7402/VS/10/2026"
              />
            </div>
          </div>

          <!-- Footer Buttons -->
          <div
            class="p-5 border-t border-[#EAEFF5] bg-[#FBFDFE] flex items-center justify-end gap-3"
          >
            <button
              @click="emit('close')"
              class="px-4 py-2 rounded-xl border border-[#EAEFF5] font-manrope text-[12px] font-semibold text-text-muted hover:text-text-title transition-all"
            >
              Batalkan
            </button>
            <button
              @click="submit"
              :disabled="justificationReason.trim().length < 5"
              class="px-5 py-2 rounded-xl bg-brand-navy text-white font-manrope text-[12px] font-bold hover:bg-brand-navy-dark transition-all shadow-sm flex items-center gap-1.5"
            >
              <span
                aria-hidden="true"
                class="material-symbols-outlined text-[16px]"
                >check_circle</span
              >
              Gunakan alasan
            </button>
          </div>
        </div>
      </dialog>
    </transition>
  </teleport>
</template>
