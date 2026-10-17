import { createRouter, createWebHistory } from "vue-router";
import DashboardView from "../views/DashboardView.vue";
import ActivityMatrixView from "../views/ActivityMatrixView.vue";
import BatchRealizationView from "../views/BatchRealizationView.vue";
import AuditTrailView from "../views/AuditTrailView.vue";
import LoginView from "../views/LoginView.vue";
import ActivityEditorView from "../views/ActivityEditorView.vue";
import SettingsView from "../views/SettingsView.vue";
import HelpView from "../views/HelpView.vue";
import { checkSession, session } from "../services/api";

const routes = [
  { path: "/login", component: LoginView },
  { path: "/kegiatan/baru", component: ActivityEditorView },
  { path: "/kegiatan/:id/edit", component: ActivityEditorView },
  { path: "/pengaturan", component: SettingsView },
  { path: "/bantuan", component: HelpView },
  { path: "/", name: "Dashboard", component: DashboardView },
  { path: "/wilayah", name: "Regions", component: DashboardView },
  { path: "/matriks", name: "ActivityMatrix", component: ActivityMatrixView },
  {
    path: "/realisasi",
    name: "BatchRealization",
    component: BatchRealizationView,
  },
  { path: "/audit", name: "AuditTrail", component: AuditTrailView },
  { path: "/:pathMatch(.*)*", redirect: "/" },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});
router.beforeEach(async (to) => {
  try {
    await checkSession();
  } catch {
    return to.path === "/login" ? true : "/login";
  }
  if (!session.user && to.path !== "/login") return "/login";
  if (session.user && to.path === "/login") return "/";
});
