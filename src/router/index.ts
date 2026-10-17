import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../views/DashboardView.vue'
import ActivityMatrixView from '../views/ActivityMatrixView.vue'
import BatchRealizationView from '../views/BatchRealizationView.vue'
import AuditTrailView from '../views/AuditTrailView.vue'

const routes = [
  { path: '/', name: 'Dashboard', component: DashboardView },
  { path: '/matriks', name: 'ActivityMatrix', component: ActivityMatrixView },
  { path: '/realisasi', name: 'BatchRealization', component: BatchRealizationView },
  { path: '/audit', name: 'AuditTrail', component: AuditTrailView },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})
