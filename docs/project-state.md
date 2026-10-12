# SDLC Project State

State version: 5
Updated: 2026-10-12T09:40:00+07:00
Mode: Plan | Build | Verify
Phase: Production-Ready Prototype & Full SDLC Documentation Alignment
Project classification: Documented existing
Iteration: IT-13-AUTHORIZATION-SCOPING-TACTILE-DESIGN-AND-PERFORMANCE-HARDENING
Active outcome: Consolidated authorization scoping (`Lpk::scopeManageableBy`), bounded calendar queries (`whereBetween` & `subMonths(5)`), assessment import inverted date clamping (`end_at < start_at` auto-adjusted), ergonomic mobile bottom navigation bar (`.bottom-nav`) and quick actions modal sheet, standardized tactile navigation buttons with 10px radius (`var(--radius-md)`), login page primary blue gradient canvas in light mode (`#1972f5` to `#0456ce`) and charcoal zinc in dark mode (`#111215`), background cron chunking optimization, dual-layer automated test suites (183 backend PHPUnit tests + 8 frontend Node.js unit tests), and complete SDLC documentation alignment (.md & .docx).

## Baseline
- Repository and stack: PHP 8.2+ / PHP 8.3+, Laravel 12 / 13, SQLite 3, Vite 8, Vanilla CSS Design System with Dark Mode, Vanilla JS, Node.js Native Test Runner
- Entry points and commands: `php artisan serve`, `npm run dev`, `npm run build`, `php artisan test`, `node --test tests/frontend/*.test.mjs`, `docker compose up -d`
- Pre-existing failures: None found (183/183 PHPUnit feature tests passing, 8/8 Node.js frontend tests passing, Vite assets build cleanly).

## Active Traceability
- Requirements: REQ-F-01 to REQ-F-32, REQ-NF-01 to REQ-NF-10 (docs/03-tahap-analisis.md)
- Decisions:
  - 8 Official KAN Assessment Types (KAN U-01): Akreditasi Awal, Surveilen 1, Surveilen 1 + PRL, Surveilen 2, Surveilen 2 + PRL, Surveilen Tidak Terjadwal, Perluasan Ruang Lingkup, Re-Akreditasi.
  - 3-Stage Surveillance Tolerance Lifecycle: Stage 1 (Visit month tolerance), Stage 2 (1-Year Suspension Window with countdown, purple badge `#f3e8ff` / `#6b21a8`), Stage 3 (Revocation / Dicabut).
  - Auto-realization rule: Past assessments and TP for currently ACTIVE LPKs auto-resolve to COMPLETED and SATISFIED.
  - Strict TP SLA Engine: Base SLA (3 months AA, 2 months others), maximum 1 month extension with official letter and proven finding progress, extension disallowed if progress is empty, automatic status display without manual input.
  - Consolidated Authorization Scoping: `Lpk::scopeManageableBy($user)` and `Lpk::scopeAccessibleBy($user)` unifying Admin and Lead PIC management vs Viewer PIC read-only permissions across CalendarEvent, Assessment, and AssessmentImport.
  - Tactile Design System & Ergonomics: Standardized 10px tactile buttons (`var(--radius-md)`), mobile bottom navigation bar (`.bottom-nav`) with quick action sheet, and official primary blue gradient login page canvas.
  - Performance & Memory Guards: Bounded calendar historical queries and chunked cron execution (`chunk(100)`).
  - Document Validation: EHA workflow and Release Readiness Quality Gate.
- Diagrams and Specifications:
  - PRD Document: prd.md
  - Master SDLC Document: docs/PERANCANGAN_SISTEM.md
  - Navigation Sitemap: docs/04-tahap-perancangan.md#41
  - Use Case Diagram: docs/04-tahap-perancangan.md#42
  - Activity Diagrams: docs/04-tahap-perancangan.md#43
  - Sequence Diagrams: docs/04-tahap-perancangan.md#44
  - Entity Relationship Diagram (ERD): docs/04-tahap-perancangan.md#45
  - Class Diagram: docs/04-tahap-perancangan.md#46
  - Wireframes: docs/04-tahap-perancangan.md#47
- Tests: Feature test suite (183 tests, 1102 assertions) and Frontend test suite (8 tests).

## Open Decisions and Risks
| ID | Decision or risk | Impact | Owner | Status |
|---|---|---|---|---|
| RSK-01 | Loop rekursif accessor status model | Tinggi | Tim Pengembang | Dimodelkan dengan pembacaan langsung `$rawStatus = $this->attributes['status']` sebelum memanggil accessor dinamis (Terselesaikan) |
| RSK-02 | Integrasi API KANMIS eksternal | Rendah (Scope prototype) | Tim Pengembang | Menggunakan arsitektur representasional internal dan live CSV feed Google Sheets (Terselesaikan) |
| RSK-03 | Kompatibilitas browser lama terhadap CSS modern | Rendah | Tim Pengembang | Baseline modern browser (Chrome 105+, Safari 15.4+, Firefox 121+) (Terselesaikan) |

## Last Verification
- Checks: `npm run build` (success), `php artisan test` (183 passed, 1102 assertions), `node --test tests/frontend/*.test.mjs` (8 passed)
- Evidence grade: Fully Verified
- Result: All tests, builds, and business rules pass with 100% success rate. All documentation synchronized.

## Next Action
Ready for final presentation and stakeholder review.
