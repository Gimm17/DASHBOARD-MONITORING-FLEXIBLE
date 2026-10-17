import { reactive } from "vue";

export interface Assignment {
  role: string;
  scope_type: string;
  scope_id: number;
}
export interface User {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
  assignments: Assignment[];
}
export const session = reactive({ user: null as User | null, checked: false });
let csrf = "";
export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public errors: Record<string, string[]> = {},
  ) {
    super(message);
  }
}
export async function api<T = any>(
  path: string,
  options: { method?: string; body?: unknown; key?: string } = {},
): Promise<T> {
  const method = options.method || "GET";
  if (method !== "GET" && !csrf) {
    const response = await fetch("/api/csrf", {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    });
    if (!response.ok)
      throw new ApiError(response.status, "Sesi tidak dapat disiapkan.");
    csrf = (await response.json()).csrf_token;
  }
  const headers: Record<string, string> = { Accept: "application/json" };
  if (method !== "GET") {
    headers["Content-Type"] = "application/json";
    headers["X-CSRF-TOKEN"] = csrf;
  }
  if (options.key) headers["Idempotency-Key"] = options.key;
  const response = await fetch("/api/" + path, {
    method,
    headers,
    credentials: "same-origin",
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
  });
  if (response.status === 204) return undefined as T;
  const data = await response
    .json()
    .catch(() => ({ message: "Server tidak memberikan respons yang valid." }));
  if (!response.ok) {
    if (response.status === 401) {
      session.user = null;
      csrf = "";
      if (session.checked && window.location.pathname !== "/login")
        window.location.assign("/login");
    }
    if (response.status === 419) csrf = "";
    const message = data.errors
      ? Object.values(data.errors).flat().join(" ")
      : data.message;
    throw new ApiError(
      response.status,
      message || "Permintaan gagal.",
      data.errors,
    );
  }
  if (data.csrf_token) csrf = data.csrf_token;
  return data as T;
}
export async function checkSession() {
  if (session.checked) return;
  try {
    session.user = (await api("me")).user;
  } catch (e) {
    if (!(e instanceof ApiError) || e.status !== 401) throw e;
  }
  session.checked = true;
}
export async function logout() {
  await api("logout", { method: "POST" });
  session.user = null;
  csrf = "";
}
export const errorText = (e: unknown) =>
  e instanceof Error ? e.message : "Terjadi kesalahan. Silakan coba lagi.";

export interface Period {
  id: number;
  label: string;
  position_date: string;
  due_at: string;
  state: string;
  version: number;
  revision: number | null;
  current_revision_id: number | null;
}
export interface Cell {
  period_id: number;
  value: string | null;
  target: string;
  final_target: string;
  attainment: number | null;
  progress: number | null;
  status: string;
  version: number;
  notes: string;
  isLate: boolean;
  updated_at: string | null;
  provisional?: Cell;
}
export interface MatrixRow {
  id: number;
  code: string;
  name: string;
  final_target: string;
  cells: Cell[];
  progress: number | null;
  joined_on: string;
  left_on: string | null;
}
export interface Activity {
  id: number;
  code: string;
  title: string;
  team_id: number;
  owner_id: number;
  team: string;
  pjk: string;
  year: number;
  status: string;
  version: number;
  indicator: string;
  unit: string;
  value_type: string;
  precision: number;
  start_date: string;
  end_date: string;
  periods: Period[];
  coverageRate?: number | null;
  capaianPeriode?: number | null;
  evaluation_count?: number;
}
export interface Detail {
  activity: Activity;
  periods: Period[];
  rows: MatrixRow[];
  can_write: boolean;
  can_manage: boolean;
  can_reopen: boolean;
}
export const number = (v: unknown) =>
  v === null || v === undefined
    ? "—"
    : Number(v).toLocaleString("id-ID", { maximumFractionDigits: 2 });
export const dateTime = (v: string) =>
  new Date(v.replace(" ", "T") + (/[Z+]/.test(v) ? "" : "Z")).toLocaleString(
    "id-ID",
    { timeZone: "Asia/Makassar" },
  ) + " WITA";
