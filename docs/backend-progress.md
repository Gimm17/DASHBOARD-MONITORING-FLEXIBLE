# Backend implementation ledger

Plan: docs/superpowers/plans/2026-10-16-dashboard-monitoring-flexible.md
Spec: output/prd/PRD_Dashboard_Monitoring_Flexible_v1.0.docx
Started: 17 October 2026 Asia/Makassar

## Rulings

- Preserve the existing Vue SPA and its visual language. Add a same-origin Laravel JSON backend in backend/ instead of replacing the UI with Inertia. This avoids rebuilding the completed frontend; deployment must serve SPA and /api from one origin.
- Reuse the clean checkout on branch feat/backend-production, preserving main and user frontend. No new worktree is needed for this already dedicated workspace.
- Execute P0 end to end. Gamification policy remains unapproved; no fabricated scoring or AI predictions will appear as live operational results.
- Production deployment, real organizational UAT and production capacity depend on external infrastructure. Prepare and test the release locally, and report these gates honestly rather than claim that a local build proves production readiness.
- Locked Symfony dependencies require PHP >=8.4.1. The original plan's framework minimum 8.3 is superseded for this specific release. Composer, readiness checks and cPanel instructions enforce the actual package requirement.
- User selected cPanel. No domain or authorized DMF server connection was supplied. The repository's SSH guide explicitly describes a different project; it was not reused for deployment.

## Preflight

- Existing frontend Vue 3.5, Vite 6, TypeScript; all four operational views use mock data and simulated saves.
- PHP 8.4 with PDO SQLite/MySQL and BCMath, Composer 2.9 and Node 22 available.
- Shared contracts: access scopes precede every query and mutation; target configuration versions feed period calculations; batch writers and finalizers share activity/period locks; final snapshots survive target edits and reopening; dashboard and CSV use the same scope and denominators.

## Progress

| Task | Implementation and evidence |
| --- | --- |
| T00 | Inspected completed frontend, preserved Vue SPA, established synthetic test environment and documented architecture decisions. Production target remains to be configured. |
| T01 | Laravel 13 backend, schema, frontend build/proxy, locked dependencies, local check script and GitHub CI workflow. Local checks run; remote CI not claimed. |
| T02 | Session/CSRF authentication, login rate limit, inactive sessions, server-side scoped grants, account administration and safe first-admin provisioning. |
| T03 | Masters, activity editor, participants and lifecycle transitions integrated with database. |
| T04 | Daily/weekly/monthly/quarterly/custom schedules, exact cumulative target validation, immutable target sets and indexed projections. |
| T05 | BCMath calculation, missing versus zero, period attainment versus final progress, due-date eligibility and comparable-unit aggregation. |
| T06 | Real batch input, per-row validation/results, partial persistence, idempotent retry, optimistic versions and audit. |
| T07 | Immutable official revisions, finalization, admin reopen, reviewed decrease/clearing tickets, historical correction of closed activities. |
| T08 | Scoped SQL dashboard, activity matrix, regional filter, real charts, CSV, revision history and paginated audit/detail data. |
| T09 | Production security/configuration, keyboard dialog, mobile browser checks, indexed aggregation, concurrency proofs and local restore drill. Target cPanel HTTPS/load/physical-device verification remains external. |
| T10 | Release packaging with production dependencies, manifest/checksum, cPanel deployment and operational runbooks. Organizational UAT and actual deployment remain pending target details. |

## Verification

- SQLite backend: 39 passing test executions, 230 assertions.
- PDO MySQL against isolated MariaDB 10.4.24: 39 passing test executions, 230 assertions.
- Chrome: 2 passing end-to-end scenarios, including real authenticated writes, dirty-selection cancellation, finalization, closed-activity correction approval, revision history and regional/mobile navigation.
- Separate-process DB races pass in both finalizer-first and writer-first ordering. Concurrent admin demotions preserve one active admin.
- Local benchmark: 100 benchmark activities, 20 regions, 50 periods, over 100,000 realizations; isolated sequential p95 dashboard 2,407.44 ms and 20-row write 273.08 ms, 28 MB peak PHP memory. A busy development-machine run exceeded 3 seconds; target-server 30-user capacity is not proven.
- Consistent backup restored to a separate database in 13.24 seconds import time. All 26 table extended checksums matched at the backup point.
- npm and Composer audits reported no advisories at verification time. Build and PHP formatting checks are part of the release checks.
- Detailed evidence, constraints and remaining environment gates: docs/operations/verification.md.

## Consolidated review fixes

| Finding | Resolution |
| --- | --- |
| Writer-first finalization can use a stale MySQL repeatable-read snapshot | Routing reads occur outside the transaction; authoritative reads follow the shared activity/period lock. Separate-process regression reproduced the old failure and passed the fix. |
| Clearing an existing number bypasses decrease review | Null clearing creates a pending correction ticket, preserving the stored value until approved. |
| Removing a participant on the finalization date hides official data | Official evaluation membership comes from immutable revision items, including historical correction. |
| Concurrent admin demotions can remove every administrator | A dedicated database guard row serializes membership decisions before reading the administrator set. |
| Cancel/archive discards history or restores future obligations | Persistent cancellation cutoff remains independent of archive status; official history survives. |
| Completed/archived activities cannot correct final results | Admin can reopen an existing official period while routine input stays closed. |
| Cancelled selector change can submit old rows into another period | Frontend restores the loaded activity/period when the dirty-data confirmation is declined; browser regression covers it. |
| Gauge retained a frontend example count | Replaced with actual reported/required totals from the dashboard response; zero-denominator rate stays empty. Mobile regression covers the rendered metric. |

## Release boundary

The result is a tested local release candidate, not a statement that cPanel deployment or organization acceptance has happened. Gamification remains disabled pending policy. No production secrets, database dump, local accounts or test data are included in the release artifact. Branch remains feat/backend-production; no remote publication or deployment is performed without the actual DMF target.

Automated database tests additionally refuse to run RefreshDatabase against any database except in-memory SQLite or a MySQL database explicitly named _test_. Source checks must invoke the backend test command from backend (or through composer --working-dir=backend test).
