import http from "k6/http";
import { check, sleep } from "k6";
import { Rate } from "k6/metrics";

const base = (__ENV.DMF_BASE_URL || "").replace(/\/$/, "");
if (
  !/^https:\/\//.test(base) &&
  !/^http:\/\/(127\.0\.0\.1|localhost)(:[0-9]+)?$/.test(base)
) {
  throw new Error("Set DMF_BASE_URL to the authorized staging HTTPS origin.");
}
if (!__ENV.DMF_LOAD_EMAIL || !__ENV.DMF_LOAD_PASSWORD)
  throw new Error("Staging test credentials required.");
const fixtures = __ENV.DMF_LOAD_FIXTURES
  ? JSON.parse(open(__ENV.DMF_LOAD_FIXTURES))
  : [];
const mixed = __ENV.DMF_LOAD_MODE === "mixed";
const users = Number(__ENV.DMF_LOAD_USERS || 30);
if (
  mixed &&
  (fixtures.length < users ||
    new Set(fixtures.map((f) => f.activity_id)).size < users)
)
  throw new Error(
    "Mixed mode requires one distinct activity fixture per virtual user.",
  );
const failures = new Rate("application_failures");
export const options = {
  noCookiesReset: true,
  vus: users,
  duration: __ENV.DMF_LOAD_DURATION || "5m",
  thresholds: {
    "http_req_duration{operation:dashboard}": ["p(95)<3000"],
    ...(mixed
      ? { "http_req_duration{operation:batch20}": ["p(95)<3000"] }
      : {}),
    application_failures: ["rate<0.01"],
    http_req_failed: ["rate<0.01"],
  },
};
let token = "";
function headers() {
  return {
    Accept: "application/json",
    "Content-Type": "application/json",
    "X-CSRF-TOKEN": token,
  };
}
function login() {
  const csrf = http.get(base + "/api/csrf", {
    headers: { Accept: "application/json" },
    tags: { operation: "session" },
  });
  if (csrf.status !== 200) throw new Error("Session initialization failed.");
  token = csrf.json("csrf_token");
  const response = http.post(
    base + "/api/login",
    JSON.stringify({
      email: __ENV.DMF_LOAD_EMAIL,
      password: __ENV.DMF_LOAD_PASSWORD,
    }),
    { headers: headers(), tags: { operation: "login" } },
  );
  if (response.status !== 200) throw new Error("Staging login failed.");
  token = response.json("csrf_token");
}
export default function () {
  if (!token) login();
  const dashboard = http.get(base + "/api/dashboard", {
    headers: headers(),
    tags: { operation: "dashboard" },
  });
  failures.add(
    !check(dashboard, {
      "dashboard responds with scoped summary": (r) =>
        r.status === 200 && r.json("summary.total") !== undefined,
    }),
  );
  if (mixed && __ITER % 5 === 0) {
    const f = fixtures[__VU - 1];
    const detail = http.get(base + "/api/activities/" + f.activity_id, {
      headers: headers(),
      tags: { operation: "read_for_batch" },
    });
    if (detail.status !== 200) {
      failures.add(true);
      return;
    }
    const body = detail.json();
    const period = body.periods.find((p) => p.id === f.period_id);
    const rows = body.rows
      .filter((r) => f.region_ids.includes(r.id))
      .map((r) => {
        const original = r.cells.find((c) => c.period_id === f.period_id);
        const cell = original.provisional || original;
        return {
          region_id: r.id,
          version: cell.version,
          position_date: period.position_date,
          value: String(Number(cell.value || 0) + 1),
          reason: "Staging load test increment",
        };
      });
    if (rows.length !== 20)
      throw new Error("Fixture must contain exactly 20 writable regions.");
    const response = http.post(
      base + "/api/periods/" + f.period_id + "/realizations",
      JSON.stringify({ rows }),
      {
        headers: {
          ...headers(),
          "Idempotency-Key": `load-${__VU}-${__ITER}-${Date.now()}`,
        },
        tags: { operation: "batch20" },
      },
    );
    failures.add(
      !check(response, {
        "all 20 rows saved": (r) =>
          r.status === 200 &&
          r.json("results").length === 20 &&
          r.json("results").every((x) => x.status === "saved"),
      }),
    );
  }
  sleep(1);
}
