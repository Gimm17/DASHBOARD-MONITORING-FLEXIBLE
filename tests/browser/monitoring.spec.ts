import { test, expect } from "@playwright/test";

test("real session, CSRF, activity configuration, cumulative report, snapshot and audit", async ({
  page,
}) => {
  const errors: string[] = [];
  page.on("pageerror", (e) => errors.push(e.message));
  await page.goto("/login");
  await page
    .getByLabel("Email", { exact: true })
    .fill(process.env.DMF_TEST_EMAIL || "admin@dmf.test");
  await page
    .getByLabel("Kata sandi", { exact: true })
    .fill(process.env.DMF_TEST_PASSWORD || "Local-Dmf-Testing-2026!");
  await page.getByRole("button", { name: "Masuk", exact: true }).click();
  await expect(
    page.getByRole("heading", { name: "Dashboard Monitoring Eksekutif" }),
  ).toBeVisible();
  await expect(
    page.getByText("Monitoring Contoh", { exact: true }),
  ).toBeVisible();
  await page
    .getByRole("row")
    .filter({ hasText: "Monitoring Contoh" })
    .getByRole("button", { name: "Rincian" })
    .click();
  await expect(
    page.getByRole("heading", { name: "Matriks Evaluasi Wilayah" }),
  ).toBeVisible();
  await expect(
    page.getByText("Wilayah Contoh A", { exact: false }).first(),
  ).toBeVisible();
  await page.getByRole("link", { name: "Input realisasi" }).first().click();
  await expect(
    page.getByRole("heading", { name: "Input Realisasi Batch" }),
  ).toBeVisible();
  await expect(
    page.getByLabel("Realisasi Wilayah Contoh B", { exact: true }),
  ).toBeEnabled();
  const originalPeriod = await page
    .getByRole("combobox", { name: "Periode", exact: true })
    .inputValue();
  await page
    .getByLabel("Realisasi Wilayah Contoh B", { exact: true })
    .fill("50");
  const otherPeriod = await page
    .getByRole("combobox", { name: "Periode", exact: true })
    .locator("option")
    .last()
    .getAttribute("value");
  page.once("dialog", (d) => d.dismiss());
  await page
    .getByRole("combobox", { name: "Periode", exact: true })
    .selectOption(otherPeriod!);
  await expect(
    page.getByRole("combobox", { name: "Periode", exact: true }),
  ).toHaveValue(originalPeriod);
  await expect(
    page.getByLabel("Realisasi Wilayah Contoh B", { exact: true }),
  ).toHaveValue("50");
  await page
    .getByLabel("Realisasi Wilayah Contoh B", { exact: true })
    .fill("40");
  await page
    .getByRole("button", { name: "Simpan perubahan", exact: true })
    .click();
  await expect(page.getByRole("status")).toContainText("Semua perubahan");
  await page.reload();
  await expect(
    page.getByLabel("Realisasi Wilayah Contoh B", { exact: true }),
  ).toHaveValue("40.0000");
  await page.goto("/audit");
  await expect(page.getByText("realization.saved").first()).toBeVisible();
  await page.goto("/pengaturan");
  await expect(
    page.getByRole("button", { name: "Akun", exact: true }),
  ).toBeVisible();
  await page.getByRole("button", { name: "Akun", exact: true }).click();
  await expect(
    page.getByRole("cell", { name: "admin@dmf.test", exact: true }),
  ).toBeVisible();
  await page.goto("/kegiatan/baru");
  await expect(
    page.getByRole("heading", { name: "Buat Monitoring Baru" }),
  ).toBeVisible();
  await page.getByLabel("Kode kegiatan").fill("E2E-" + Date.now());
  await page
    .getByLabel("Nama kegiatan", { exact: true })
    .fill("Monitoring Uji Browser");
  await page
    .getByRole("combobox", { name: "Tim", exact: true })
    .selectOption({ label: "Tim Contoh" });
  await page.getByLabel("Nama indikator").fill("Dokumen diperiksa");
  await page.getByLabel("Satuan", { exact: true }).fill("Dokumen");
  await page.getByRole("button", { name: "+ Tanggal khusus" }).click();
  const today = new Date().toLocaleDateString("sv-SE", {
    timeZone: "Asia/Makassar",
  });
  await page.getByLabel("Tanggal posisi", { exact: true }).fill(today);
  await page.getByLabel("Batas laporan WITA").fill(today + "T17:00");
  await page.getByLabel("REG01 Wilayah Contoh A").check();
  await page.getByLabel(/Target akhir wilayah/).fill("100");
  await page.getByLabel(/Target periode 1 wilayah/).fill("100");
  await page.getByRole("button", { name: "Simpan dan aktifkan" }).click();
  await expect(
    page.getByRole("heading", { name: "Monitoring Uji Browser", exact: true }),
  ).toBeVisible();
  await expect(page.locator('[role="alert"]')).toHaveCount(0);
  await page.getByRole("link", { name: "Input realisasi" }).click();
  await page
    .getByLabel("Realisasi Wilayah Contoh A", { exact: true })
    .fill("100");
  await page
    .getByRole("button", { name: "Simpan perubahan", exact: true })
    .click();
  await expect(page.getByRole("status")).toContainText("Semua perubahan");
  const query = new URL(page.url()).searchParams;
  await page.goto("/matriks?activity=" + query.get("activity"));
  page.once("dialog", (d) => d.accept());
  await page.getByRole("button", { name: "Finalisasi", exact: true }).click();
  await expect(page.getByText("Final revisi 1", { exact: true })).toBeVisible();
  await page.getByText("Riwayat revisi resmi", { exact: true }).click();
  await expect(page.getByText("Revisi 1", { exact: true })).toBeVisible();
  await page
    .getByRole("link", { name: "Pengaturan kegiatan", exact: true })
    .click();
  page.once("dialog", (d) => d.accept("Penutupan kegiatan uji browser"));
  await page
    .getByRole("button", { name: "Tutup kegiatan", exact: true })
    .click();
  await expect(page.getByText(/SELESAI/).first()).toBeVisible();
  page.once("dialog", (d) => d.accept("Koreksi resmi setelah penutupan"));
  await page.getByRole("button", { name: "Buka koreksi", exact: true }).click();
  await expect(
    page.getByRole("link", { name: "Input realisasi", exact: true }),
  ).toBeVisible();
  await page
    .getByRole("link", { name: "Input realisasi", exact: true })
    .click();
  await page
    .getByLabel("Realisasi Wilayah Contoh A", { exact: true })
    .fill("90");
  await page
    .getByLabel("Realisasi Wilayah Contoh A", { exact: true })
    .press("Tab");
  await expect(page.getByRole("dialog")).toBeVisible();
  await expect(
    page.getByLabel("Alasan Penurunan / Koreksi Data (Wajib)"),
  ).toBeFocused();
  await page
    .getByLabel("Alasan Penurunan / Koreksi Data (Wajib)")
    .fill("Dokumen duplikat telah diverifikasi");
  await page
    .getByRole("button", { name: "Gunakan alasan", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Simpan perubahan", exact: true })
    .click();
  await expect(
    page.getByText("Menunggu tinjauan PJK; angka resmi belum berubah."),
  ).toBeVisible();
  await page.goto("/audit");
  page.once("dialog", (d) => d.accept("Disetujui berdasarkan dokumen koreksi"));
  await page
    .getByRole("button", { name: "Setujui", exact: true })
    .first()
    .click();
  await expect(
    page.getByText("DISETUJUI", { exact: true }).first(),
  ).toBeVisible();
  await page.goto("/matriks?activity=" + query.get("activity"));
  page.once("dialog", (d) => d.accept());
  await page.getByRole("button", { name: "Finalisasi", exact: true }).click();
  await expect(page.getByText("Final revisi 2", { exact: true })).toBeVisible();
  await page.getByRole("button", { name: "Keluar", exact: true }).click();
  await expect(
    page.getByRole("button", { name: "Masuk", exact: true }),
  ).toBeVisible();
  expect(errors).toEqual([]);
});

test("login and navigation work on a narrow screen", async ({ page }) => {
  await page.setViewportSize({ width: 360, height: 800 });
  await page.goto("/login");
  await expect(
    page.getByRole("button", { name: "Masuk", exact: true }),
  ).toBeVisible();
  await page.getByLabel("Email", { exact: true }).fill("admin@dmf.test");
  await page
    .getByLabel("Kata sandi", { exact: true })
    .fill("Local-Dmf-Testing-2026!");
  await page.getByRole("button", { name: "Masuk", exact: true }).click();
  await expect(
    page.getByRole("navigation", { name: "Navigasi seluler" }),
  ).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Keluar", exact: true }),
  ).toBeVisible();
  await expect(
    page.getByText("Ringkasan monitoring", { exact: true }),
  ).toBeVisible();
  await expect(page.getByText(/\d+ dari \d+ unit evaluasi/)).toBeVisible();
  await page.screenshot({
    path: "test-results/mobile-dashboard.png",
    fullPage: true,
  });
  await page.goto("/wilayah");
  await expect(
    page.getByRole("heading", { name: "Monitoring per Wilayah", exact: true }),
  ).toBeVisible();
  await page
    .getByRole("combobox", { name: "Filter wilayah", exact: true })
    .selectOption({ label: "Wilayah Contoh B" });
  await expect(
    page.getByRole("combobox", { name: "Filter wilayah", exact: true }),
  ).toHaveValue(/\d+/);
  await expect(
    page.getByRole("heading", { name: "Rincian evaluasi sesuai filter" }),
  ).toBeVisible();
});
