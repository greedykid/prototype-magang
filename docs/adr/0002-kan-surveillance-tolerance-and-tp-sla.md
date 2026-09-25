# [ADR-0002] KAN Surveillance Tolerance Lifecycle and TP SLA Engine

- **Status**: Accepted
- **Date**: 2026-09-25
- **Deciders**: Engineering Team, KAN Accreditation Working Group
- **Traceability**: Requirements [REQ-F-11 to REQ-F-19], Architecture [docs/04-tahap-perancangan.md]

## Context and Problem Statement

The accreditation monitoring and assessment workflow in SIMASADI previously lacked automated enforcement of KAN U-01 regulations regarding visit tolerance, suspension consequences, revocation deadlines, and Service Level Agreements (SLA) for Corrective Actions (Tindakan Perbaikan / TP). Operational statuses were prone to manual data-entry errors, and historical assessments for active LPKs were erroneously left in suspended states.

## Decision Drivers

- Full compliance with KAN U-01 accreditation rules for all 8 official assessment types.
- Elimination of manual status editing for TP by implementing dynamic system calculation.
- Clear, distinct visual indicators for temporary suspension (`SUSPENDED` with contrast purple badge) versus expiration/overdue (`EXPIRED` / `OVERDUE` with red badge).
- Clean historical data handling through automatic realization for active LPKs.
- Prevention of SLA abuse by strictly gating 1-month extensions on proven finding progress and official letters.

## Considered Options

1. **Rule Engine in Eloquent Accessors with Dynamic Evaluation**:
   Calculate status on-the-fly based on timestamps (`end_at`, `submission_due_date`, `tp_due_date`, `tp_satisfied_at`, etc.).
2. **Scheduled Database Mutation Jobs (Cron-Only)**:
   Update database columns periodically via background cron.
3. **Manual Status Entry by Operators**:
   Allow administrators and PICs to select status dropdowns manually.

## Decision Outcome

Chosen option: **Option 1 (Rule Engine in Eloquent Accessors with Dynamic Evaluation)** combined with smart seeding and test verification.

### Key Architectural Rules:
1. **Three-Stage Surveillance Lifecycle**:
   - *Stage 1 (Normal / In Progress)*: Document submission tolerance valid until the end of the visit month (`submission_due_date = end_at->endOfMonth()`).
   - *Stage 2 (Suspended / 1-Year Resolution Window)*: Automatically transitions to `SUSPENDED` (purple badge `#f3e8ff` with text `#6b21a8`) if tolerance passes without completion. The LPK is granted a 1-year resolution opportunity with a countdown display.
   - *Stage 3 (Revocation)*: Automatically transitions to `REVOKED` if the 1-year suspension window expires without resolution.
2. **Auto-Realization for Currently Active LPKs**:
   - If an LPK is currently `ACTIVE`, historical assessments and TP from past years (`end_at < now()->startOfYear()`) are automatically evaluated as `COMPLETED` and `SATISFIED`.
3. **Corrective Action SLA Engine**:
   - Base SLA: 3 calendar months for Akreditasi Awal (AA), 2 calendar months for other assessments.
   - Extension: Exactly 1 month extension allowed only if there is proven progress on finding nonconformities and an official letter number is provided. Extension is strictly disallowed if progress is empty.
   - Dynamic status: "Sedang Berlangsung" (in progress) -> "Dibekukan" (suspended, purple) when overdue without satisfaction -> "Memenuhi / Selesai" when satisfied date is recorded. Manual status input is disabled.

### Positive Consequences

- Status is always 100% synchronized with current real-time dates.
- Zero risk of operator error in setting or forgetting to update statuses.
- Complete regulatory traceability across all 119 automated feature tests (680 assertions).

### Negative Consequences / Trade-offs

- Accessor computation overhead in memory, mitigated via eager loading and attribute caching.
