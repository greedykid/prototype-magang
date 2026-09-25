# SDLC Project State

State version: 2
Updated: 2026-09-25T19:50:00+07:00
Mode: Plan | Build | Verify
Phase: Production-Ready Prototype & Full SDLC Documentation Alignment
Project classification: Documented existing
Iteration: IT-10-KAN-SLA-TOLERANCE-AND-SURVEILLANCE-LIFECYCLE
Active outcome: Implementation of 8 official KAN assessment types, 3-stage surveillance tolerance and revocation lifecycle, strict TP/VTP SLA engine with extension gating, bold dynamic keterangan, contrast purple suspension badge, mass importer for LPK and Assessments, EHA workflow, QR verification, 4-role RBAC, and full SDLC documentation alignment across all chapters.

## Baseline
- Repository and stack: PHP 8.2+ / PHP 8.3+, Laravel 12 / 13, SQLite 3, Vite, Vanilla CSS Design System, Vanilla JS
- Entry points and commands: `php artisan serve`, `npm run dev`, `npm run build`, `php artisan test`, `docker compose up -d`
- Pre-existing failures: None found (119/119 PHPUnit feature tests passing, 680 assertions, Vite assets build cleanly).

## Active Traceability
- Requirements: REQ-F-01 to REQ-F-32, REQ-NF-01 to REQ-NF-10 (docs/03-tahap-analisis.md)
- Decisions:
  - 8 Official KAN Assessment Types (KAN U-01): Akreditasi Awal, Surveilen 1, Surveilen 1 + PRL, Surveilen 2, Surveilen 2 + PRL, Surveilen Tidak Terjadwal, Perluasan Ruang Lingkup, Re-Akreditasi.
  - 3-Stage Surveillance Tolerance Lifecycle: Stage 1 (Visit month tolerance), Stage 2 (1-Year Suspension Window with countdown, purple badge `#f3e8ff` / `#6b21a8`), Stage 3 (Revocation / Dicabut).
  - Auto-realization rule: Past assessments and TP for currently ACTIVE LPKs auto-resolve to COMPLETED and SATISFIED.
  - Strict TP SLA Engine: Base SLA (3 months AA, 2 months others), maximum 1 month extension with official letter and proven finding progress, extension disallowed if progress is empty, automatic status display without manual input.
  - Multi-Role RBAC: 4 distinct roles (`admin`, `pic`, `assessor`, `lpk`) with dedicated portals (`/assessor`, `/portal` with QR code verification).
  - Financial Compliance: SBM PMK cost reporting, SIMPONI 15-digit billing, and Release Readiness Quality Gate.
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
- Tests: 14 test files in tests/Feature/ (119 tests, 680 assertions).

## Open Decisions and Risks
| ID | Decision or risk | Impact | Owner | Status |
|---|---|---|---|---|
| RSK-01 | Loop rekursif accessor status model | Tinggi | Tim Pengembang | Dimodelkan dengan pembacaan langsung `$rawStatus = $this->attributes['status']` sebelum memanggil accessor dinamis (Terselesaikan) |
| RSK-02 | Integrasi API KANMIS eksternal | Rendah (Scope prototype) | Tim Pengembang | Menggunakan arsitektur representasional internal dan live CSV feed Google Sheets (Terselesaikan) |
| RSK-03 | Kompatibilitas browser lama terhadap CSS modern | Rendah | Tim Pengembang | Baseline modern browser (Chrome 105+, Safari 15.4+, Firefox 121+) (Terselesaikan) |

## Last Verification
- Checks: `npm run build` (success in 1.4s), `php artisan test` (119 passed, 680 assertions)
- Evidence grade: Fully Verified
- Result: All tests, builds, and business rules pass with 100% success rate. All documentation synchronized.

## Next Action
Ready for final presentation and stakeholder review.
